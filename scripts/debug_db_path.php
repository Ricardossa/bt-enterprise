<?php
require_once __DIR__ . '/../bootstrap.php';
$db = \BTQueue\Core\Database::getInstance();
$res = $db->query('PRAGMA database_list')->fetch();
echo "DB FILE: " . realpath($res['file']) . "\n";
echo "IS WRITABLE: " . (is_writable($res['file']) ? 'YES' : 'NO') . "\n";
?>
