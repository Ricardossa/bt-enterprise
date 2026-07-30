<?php

declare(strict_types=1);

namespace BTQueue\Core\MasterSync;

use BTQueue\Core\Config;
use BTQueue\Core\Logger;
use BTQueue\Core\Database;
use Exception;

/**
 * Orquestrador Central da Sincronização MasterSync.
 */
final class SyncService
{
    private Client $client;
    private LicenseManager $license;
    private QueueSender $queue;
    private CommandDispatcher $dispatcher;

    public function __construct()
    {
        // Tenta buscar a URL da Master na tabela de configurações local
        $urlConfig = Database::fetch("SELECT valor FROM configuracoes WHERE chave = 'master_url' LIMIT 1");
        $url = $urlConfig ? $urlConfig['valor'] : Config::get('sync.endpoint');

        // Fallback final caso tudo falhe
        if (!$url) {
            $url = 'http://api.brandaotech.com.br:8080/api/v1/sync.php';
        }

        $this->client = new Client($url);
        $this->license = new LicenseManager();
        $this->queue = new QueueSender();
        $this->dispatcher = new CommandDispatcher();
    }

    /**
     * Executa o Ciclo de Sincronização de 9 Passos.
     */
    public function synchronize(): array
    {
        try {
            // 1. Obtém Identidade da Licença (mesmo se bloqueado)
            $licencaLocal = $this->license->getLicenseIdentity();
            $uuid = $licencaLocal['uuid'] ?? '';
            $token = $licencaLocal['token'] ?? '';

            if (empty($uuid) || empty($token)) {
                Logger::error("Provisionamento ausente (UUID/Token).", [], 'sync');
                return ['success' => false, 'message' => 'Provisionamento ausente (UUID/Token).'];
            }

            // 2. Prepara Pulse (Heartbeat + Fila Pendente)
            $pendentes = $this->queue->getPendingItems();

            $payload = [
                'uuid' => $uuid,
                'token' => $token,
                'produto' => 'BT_QUEUE_ENTERPRISE',
                'versao' => Config::get('app.version', '4.0.0'),
                'device_uuid' => gethostname(), // Identificador da VM/Máquina
                'sync_queue' => $pendentes,
                'device' => [
                    'os' => PHP_OS,
                    'php' => PHP_VERSION,
                    'time' => date('Y-m-d H:i:s')
                ]
            ];

            // 3. Envia Pulse e Recebe Resposta
            $response = $this->client->post($payload);

            if ($response['status'] !== 'OK') {
                $this->queue->markProcessed(array_column($pendentes, 'id'), false);
                Logger::warning("Master recusou o pulso: " . ($response['message'] ?? 'Erro desconhecido'), [], 'sync');
                return ['success' => false, 'message' => $response['message'] ?? 'Erro desconhecido na Master.'];
            }

            $syncPackage = $response['sync'];

            // 4. Atualiza Tabela Licencas (Diretrizes da Master)
            $this->license->updateLicense($syncPackage['license'], $uuid, $token);

            // 5. Processa Comandos
            if (!empty($syncPackage['commands'])) {
                $this->dispatcher->dispatch($syncPackage['commands']);
            }

            // 6. Marca Sincronizados (Outbox)
            $this->queue->markProcessed(array_column($pendentes, 'id'), true);

            // 7. Grava Log Operacional
            Logger::info("Sincronização MasterSync concluída. ID: " . ($syncPackage['id'] ?? 'N/A'), [], 'sync');

            return [
                'success' => true,
                'message' => 'Sincronização realizada. Status Master: ' . ($syncPackage['license']['status'] ?? 'N/A'),
                'sync_id' => $syncPackage['id'] ?? null
            ];

        } catch (Exception $e) {
            Logger::error("Falha no Ciclo MasterSync: " . $e->getMessage(), [], 'sync');
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
