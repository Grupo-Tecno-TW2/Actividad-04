<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Lab12\Servicios\CatalogoSoap;

$archivoWsdl = __DIR__ . '/soap.wsdl';

try {
    $metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    // Permite consultar la descripción del servicio.
    if ($metodo === 'GET' && isset($_GET['wsdl'])) {
        header('Content-Type: text/xml; charset=utf-8');
        readfile($archivoWsdl);
        exit;
    }

    // El servicio SOAP recibe solicitudes POST.
    if ($metodo !== 'POST') {
        http_response_code(405);
        header('Content-Type: text/plain; charset=utf-8');
        header('Allow: GET, POST');
        echo 'Metodo no permitido';
        exit;
    }

    if (!extension_loaded('soap')) {
        throw new RuntimeException('La extension SOAP de PHP no esta habilitada.');
    }

    $servidor = new SoapServer($archivoWsdl, [
        'cache_wsdl' => WSDL_CACHE_NONE,
        'exceptions' => true,
    ]);

    $servidor->setClass(CatalogoSoap::class);
    $servidor->handle();
} catch (Throwable $e) {
    error_log('Error en el servicio SOAP: ' . $e->getMessage());

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
    }

    echo 'Error interno del servicio SOAP';
}