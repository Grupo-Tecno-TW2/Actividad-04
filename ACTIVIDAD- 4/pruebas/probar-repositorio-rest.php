<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

$config = require dirname(__DIR__) . '/config/bd.php';
$pdo = \Apicultores\BaseDatos\Conexion::crear($config);

$repositorio = new \Apicultores\Api\ProductoRepositorio($pdo);
$resultado = $repositorio->listar(['pagina' => 1, 'limite' => 10]);

echo json_encode(
    $resultado,
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
) . PHP_EOL;
