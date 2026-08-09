<?php

declare(strict_types=1);

namespace BTQueue\Core\MasterSync;

use BTQueue\Core\Database;
use BTQueue\Core\Logger;
use Exception;
use ZipArchive;

final class UpdateService
{
    private string $masterUrl;
    private string $tempDir;
    private string $wwwDir;
    private string $backupDir;

    public function __construct()
    {
        $urlConfig = Database::fetch("SELECT valor FROM configuracoes WHERE chave = 'master_url' LIMIT 1");
        $this->masterUrl = $urlConfig ? $urlConfig['valor'] : 'http://api.brandaotech.com.br:8080/api/v1/sync.php';
        $this->wwwDir = dirname(__DIR__, 2);
        $this->tempDir = $this->wwwDir . '/cache/updates';
        $this->backupDir = $this->wwwDir . '/cache/backups';
    }

    public function check(): array
    {
        $checkUrl = preg_replace('~/sync\.php$~', '/updates_check.php', $this->masterUrl) . '?v=' . rawurlencode($this->getCurrentVersion());
        $ch = curl_init($checkUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !is_string($response)) {
            Logger::error("Falha ao consultar Master para updates (HTTP $httpCode)", [], 'update');
            throw new Exception("Falha ao consultar Master (HTTP $httpCode)");
        }

        $json = json_decode($response, true);
        return isset($json['data']) ? $json['data'] : ($json ?? ['update_available' => false]);
    }

    public function applyRelease(int $releaseId): array
    {
        // Aumenta o tempo de vida do script para pacotes grandes
        set_time_limit(600);
        ini_set('memory_limit', '512M');

        $manifest = $this->check();
        if (empty($manifest['update_available']) || (int) ($manifest['release_id'] ?? 0) !== $releaseId) {
            throw new Exception('O release solicitado nao esta disponivel na Master.');
        }

        $hash = (string) ($manifest['sha256'] ?? '');
        if (!preg_match('/^[a-f0-9]{64}$/i', $hash)) {
            throw new Exception('Manifesto OTA invalido.');
        }

        return $this->update($this->buildDownloadUrl($releaseId), $hash);
    }

    private function update(string $url, string $expectedHash): array
    {
        $zipFile = $this->tempDir . '/update_package.zip';
        $backupFile = $this->backupDir . '/backup_before_update_' . date('Ymd_His') . '.zip';

        try {
            if (!$this->isMasterDownloadUrl($url)) {
                throw new Exception('Origem do pacote OTA invalida.');
            }
            if (!is_dir($this->tempDir)) mkdir($this->tempDir, 0775, true);
            if (!is_dir($this->backupDir)) mkdir($this->backupDir, 0775, true);

            $file = fopen($zipFile, 'w+b');
            if ($file === false) throw new Exception('Nao foi possivel preparar o download OTA.');
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_TIMEOUT, 300);
            curl_setopt($ch, CURLOPT_FILE, $file);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            fclose($file);

            if ($httpCode !== 200 || !is_file($zipFile)) {
                throw new Exception("Falha ao baixar o pacote OTA (HTTP $httpCode).");
            }
            if (!hash_equals(strtolower($expectedHash), strtolower((string) hash_file('sha256', $zipFile)))) {
                throw new Exception('Falha na integridade do pacote OTA.');
            }

            $this->createBackup($backupFile);
            $zip = new ZipArchive();
            if ($zip->open($zipFile) !== true) throw new Exception('Falha ao abrir o pacote OTA.');
            $this->validateArchive($zip);

            // Tenta extrair com supressão de erros para capturar a falha real (v5.9.1)
            if (!@$zip->extractTo($this->wwwDir)) {
                $error = error_get_last();
                $zip->close();
                $msg = 'Falha ao aplicar o pacote OTA: ' . ($error['message'] ?? 'Erro de permissão ou arquivo ocupado');
                Logger::error($msg, ['error' => $error], 'update');
                throw new Exception($msg);
            }

            // --- MELHORIA: ATUALIZADOR DE LANÇADORES E PRINT BRIDGE ---
            $rootPath = dirname($this->wwwDir); // C:\BT Queue Enterprise
            $filesToMove = ['Ligar_Invisivel.vbs', 'Ligar_Impressora_Local.bat', 'print_bridge.php'];

            foreach ($filesToMove as $f) {
                $src = $this->wwwDir . '/' . $f;
                $dst = $rootPath . '/' . $f;
                if (file_exists($src)) {
                    @copy($src, $dst);
                    @unlink($src);
                }
            }

            $zip->close();
            @unlink($zipFile);

            Logger::info('Atualizacao OTA aplicada: ' . basename($backupFile), [], 'update');
            return ['success' => true, 'message' => 'Sistema atualizado com sucesso.'];
        } catch (Exception $e) {
            @unlink($zipFile);
            if (is_file($backupFile)) $this->restoreBackup($backupFile);
            Logger::error('Erro OTA: ' . $e->getMessage(), [], 'update');
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function buildDownloadUrl(int $releaseId): string
    {
        $url = preg_replace('~/sync\.php$~', '/updates_download.php', $this->masterUrl);
        if (!is_string($url) || $url === $this->masterUrl) throw new Exception('URL da Master nao suporta OTA.');
        return $url . '?id=' . $releaseId;
    }

    private function isMasterDownloadUrl(string $url): bool
    {
        $master = parse_url($this->masterUrl);
        $download = parse_url($url);
        return isset($master['scheme'], $master['host'], $download['scheme'], $download['host'])
            && strtolower($master['scheme']) === strtolower($download['scheme'])
            && strtolower($master['host']) === strtolower($download['host'])
            && ($master['port'] ?? null) === ($download['port'] ?? null);
    }

    private function validateArchive(ZipArchive $zip): void
    {
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $path = str_replace('\\', '/', (string) $zip->getNameIndex($index));
            if ($path === '' || str_starts_with($path, '/') || str_contains($path, "\0")) {
                throw new Exception('Pacote OTA contem caminho invalido.');
            }
            foreach (explode('/', $path) as $segment) {
                if ($segment === '..') throw new Exception('Pacote OTA tenta sair da aplicacao.');
            }
            if (preg_match('#^(database|public/uploads|cache|logs)/#i', $path)) {
                throw new Exception('Pacote OTA tenta substituir dados persistentes.');
            }
        }
    }

    private function createBackup(string $destination): void
    {
        $zip = new ZipArchive();
        if ($zip->open($destination, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception('Nao foi possivel criar backup OTA.');
        }

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->wwwDir), \RecursiveIteratorIterator::LEAVES_ONLY);

        // Pastas para ignorar no backup (muito grandes ou desnecessarias)
        $ignore = ['cache', 'logs', 'runtime', 'database', '.git', 'output'];

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $path = $file->getRealPath();
                $relative = substr($path, strlen($this->wwwDir) + 1);

                $shouldIgnore = false;
                foreach ($ignore as $folder) {
                    if (str_starts_with($relative, $folder . DIRECTORY_SEPARATOR) || str_starts_with($relative, $folder . '/')) {
                        $shouldIgnore = true;
                        break;
                    }
                }

                if (!$shouldIgnore) {
                    $zip->addFile($path, $relative);
                }
            }
        }
        $zip->close();
    }

    private function restoreBackup(string $backupFile): void
    {
        $zip = new ZipArchive();
        if ($zip->open($backupFile) === true) {
            $zip->extractTo($this->wwwDir);
            $zip->close();
            Logger::info('Rollback OTA executado.', [], 'update');
        }
    }

    private function getCurrentVersion(): string
    {
        $path = $this->wwwDir . '/public/version.json';
        $json = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;
        return is_array($json) ? (string) ($json['version'] ?? '4.0.0') : '4.0.0';
    }
}
