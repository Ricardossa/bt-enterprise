<?php
require_once __DIR__ . '/../../bootstrap.php';
include __DIR__ . '/../includes/components/brand.php';

header('Content-Type: text/html; charset=utf-8');
echo "<h1>Debug Logo NOC</h1>";
echo "Empresa: $empresa <br>";
echo "Logo Existe (Disk): " . ($logoExiste ? 'SIM' : 'NÃO') . "<br>";
echo "Logo Path (PHP): $logoPath <br>";
echo "Logo URL (Browser): $logoUrl <br>";
echo "<hr>";
echo "HTML Gerado: <br>";
echo htmlspecialchars("<img src='$logoUrl'>");
echo "<br><br>Visualização:<br>";
echo "<img src='../$logoUrl' style='width:100px; background:red;'>"; // Adiciona ../ pq estamos na pasta api/
