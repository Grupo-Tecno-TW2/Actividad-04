<?php
declare(strict_types=1);

$cliente = new SoapClient(
    'http://127.0.0.1:8001/soap.php?wsdl',
    [
        'cache_wsdl' => WSDL_CACHE_NONE,
        'trace' => true,
        'exceptions' => true,
    ]
);

try {
    $cliente->obtenerProducto(['codigo' => 'MIE-500']);
} catch (Throwable $e) {
    echo "ERROR:\n";
    echo $e->getMessage() . PHP_EOL;

    echo "\nSOLICITUD SOAP ENVIADA:\n";
    echo $cliente->__getLastRequest() ?: 'No disponible';
    echo PHP_EOL;

    echo "\nRESPUESTA SOAP RECIBIDA:\n";
    echo $cliente->__getLastResponse() ?: 'No disponible';
    echo PHP_EOL;
}