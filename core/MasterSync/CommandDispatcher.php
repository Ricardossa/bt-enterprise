<?php

declare(strict_types=1);

namespace BTQueue\Core\MasterSync;

use BTQueue\Core\Logger;

/**
 * Processador de Comandos vindos da Central.
 */
final class CommandDispatcher
{
    public function dispatch(array $commands): void
    {
        foreach ($commands as $cmd) {
            $action = $cmd['action'] ?? '';

            Logger::info("Executando comando da MasterSync: $action", $cmd);

            switch ($action) {
                case 'REBOOT':
                    // Exemplo futuro: Executar comando de sistema
                    break;
                case 'UPDATE':
                    // Simulação de atualização para teste de homologação
                    $logFile = dirname(dirname(__DIR__)) . '/logs/update_check.log';
                    $msg = "[" . date('Y-m-d H:i:s') . "] Comando de UPDATE recebido da Master. Verificando repositórios..." . PHP_EOL;
                    file_put_contents($logFile, $msg, FILE_APPEND);
                    break;
                default:
                    Logger::warning("Comando desconhecido ignorado: $action");
            }
        }
    }
}
