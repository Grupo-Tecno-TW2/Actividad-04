<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Lab12\Datos\CatalogoControlador;
use Lab12\Noticias\NoticiasControlador;
use Lab12\Servicios\ClienteApiPublica;

$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$ruta = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if ($metodo !== 'GET') {
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    header('Allow: GET');
    echo json_encode(
        ['error' => 'Metodo no permitido'],
        JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
    );
    exit;
}

try {
    $catalogo = new CatalogoControlador();
    $noticias = new NoticiasControlador();

    switch ($ruta) {
        case '/':
            header('Content-Type: text/plain; charset=utf-8');
            echo "Laboratorio 12 - Tecnologia Web II\n";
            echo "Intercambio de datos y servicios web\n";
            echo "Rutas disponibles:\n";
            echo "/datos/catalogo.json\n";
            echo "/datos/catalogo.xml\n";
            echo "/noticias.rss\n";
            echo "/datos/productos/MIE-500\n";
            echo "/servicios/api-publica\n";
            break;

        case '/datos/catalogo.json':
            $catalogo->json();
            break;

        case '/datos/catalogo.xml':
            $catalogo->xml();
            break;

        case '/noticias.rss':
            $noticias->rss();
            break;

        case '/datos/productos/MIE-500':
            $catalogo->producto('MIE-500');
            break;

        case '/servicios/api-publica':
            header('Content-Type: application/json; charset=utf-8');
            $cliente = new ClienteApiPublica();
            echo json_encode(
                $cliente->obtenerTodo(1),
                JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
                    | JSON_PRETTY_PRINT
                    | JSON_THROW_ON_ERROR
            );
            break;

        default:
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(
                ['error' => 'Ruta no encontrada'],
                JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
            );
    }
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(
        ['error' => 'Error interno del servidor'],
        JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
    );
}