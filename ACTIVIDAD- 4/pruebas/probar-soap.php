<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

$wsdl = 'http://127.0.0.1:8001/soap.php?wsdl';

try {
    $cliente = new SoapClient($wsdl, [
        'cache_wsdl' => WSDL_CACHE_NONE,
        'exceptions' => true,
        'connection_timeout' => 5,
        'trace' => true,
    ]);

    $inicio = microtime(true);

    $producto = $cliente->obtenerProducto('MIE-500');

    $duracionMs = (microtime(true) - $inicio) * 1000;
    $respuestaXml = $cliente->__getLastResponse();

    $resultado = [
        'servicio' => 'SOAP',
        'operacion' => 'obtenerProducto',
        'codigo_consultado' => 'MIE-500',
        'duracion_ms' => round($duracionMs, 2),
        'tamano_respuesta_bytes' => strlen($respuestaXml),
        'formato_respuesta' => 'XML SOAP',
        'producto' => $producto,
    ];

    echo json_encode(
        $resultado,
        JSON_PRETTY_PRINT
        | JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
        | JSON_THROW_ON_ERROR
    ) . PHP_EOL;

} catch (Throwable $e) {
    fwrite(STDERR, 'Error SOAP: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}