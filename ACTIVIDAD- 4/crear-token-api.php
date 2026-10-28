<?php
declare(strict_types=1);

$config = require __DIR__ . '/config/bd.php';

$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
    $config['host'],
    $config['puerto'],
    $config['base']
);

$pdo = new PDO($dsn, $config['usuario'], $config['clave'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$token = bin2hex(random_bytes(32));
$hash = hash('sha256', $token);

$consulta = $pdo->prepare(
    'INSERT INTO api_tokens (token_hash) VALUES (:hash)'
);
$consulta->execute(['hash' => $hash]);

echo "TOKEN (guárdalo ahora): " . $token . PHP_EOL;
