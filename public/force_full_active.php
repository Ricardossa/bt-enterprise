<?php
// Script de Desbloqueio de Emergência - Enterprise FULL
require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Database;

try {
    // No SQLite da Full, a tabela licencas é a que manda
    Database::execute("UPDATE licencas SET status = 'ATIVA', validade = DATE_ADD(DATE('now'), '+1 year')");

    echo "<h1>✅ ENTERPRISE FULL LIBERADO!</h1>";
    echo "<p>A licença local (SQLite) foi forçada para ATIVA.</p>";
    echo "<a href='dashboard.php' style='padding: 10px 20px; background: #1565C0; color: #fff; text-decoration: none; border-radius: 5px;'>VOLTAR AO DASHBOARD</a>";
} catch (Exception $e) {
    echo "<h1>❌ ERRO:</h1> " . $e->getMessage();
}
