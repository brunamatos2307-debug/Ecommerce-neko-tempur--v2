<?php
require_once __DIR__ . '/conexao.php';

$stmt = $pdo->query("SHOW TABLES");
$tabelas = $stmt->fetchAll();

echo "<h1>Ligacao OK com neko_tempura!</h1>";
echo "<pre>";
print_r($tabelas);
echo "</pre>";