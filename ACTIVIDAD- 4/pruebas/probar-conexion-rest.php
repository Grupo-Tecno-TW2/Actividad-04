<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

$config = require dirname(__DIR__) . '/config/bd.php';
$pdo = \Apicultores\BaseDatos\Conexion::crear($config);

$total = (int) $pdo->query('SELECT COUNT(*) FROM productos')->fetchColumn();

echo "Conexion correcta. Productos: {$total}" . PHP_EOL;
