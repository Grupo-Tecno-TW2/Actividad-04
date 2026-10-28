<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Apicultores\Api\AutenticacionBearer;
use Apicultores\Api\ProductoRepositorio;
use Apicultores\Api\ProductosControlador;
use Apicultores\BaseDatos\Conexion;
use Lab12\Datos\CatalogoControlador;
use Lab12\Noticias\NoticiasControlador;
use Lab12\Servicios\ClienteApiPublica;

$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$ruta = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

// Permitir que el servidor integrado de PHP entregue archivos estáticos
// existentes dentro de public, por ejemplo /js/catalogo.js y /js/api.js.
if (PHP_SAPI === 'cli-server') {
    $archivo = realpath(__DIR__ . $ruta);
    $directorioPublico = realpath(__DIR__);

    if (
        $archivo !== false &&
        $directorioPublico !== false &&
        str_starts_with(
            $archivo,
            $directorioPublico . DIRECTORY_SEPARATOR
        ) &&
        is_file($archivo)
    ) {
        return false;
    }
}

try {
    // API REST del Laboratorio 13.
    if ($ruta === '/api/v1' || str_starts_with($ruta, '/api/v1/')) {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, If-None-Match');

        if ($metodo === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        $config = require dirname(__DIR__) . '/config/bd.php';
        $pdo = Conexion::crear($config);
        $repositorio = new ProductoRepositorio($pdo);
        $autenticacion = new AutenticacionBearer($pdo);
        $controlador = new ProductosControlador($repositorio, $autenticacion);

        $controlador->manejar($metodo, $ruta);
        exit;
    }

    // Las rutas anteriores del Laboratorio 12 solo admiten GET.
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
            echo "/api/v1/productos\n";
            echo "/catalogo\n";
            break;

        case '/catalogo':
            require dirname(__DIR__) . '/plantillas/catalogo.php';
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
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES |
                JSON_PRETTY_PRINT |
                JSON_THROW_ON_ERROR
            );
            break;

        default:
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');

            echo json_encode(
                ['error' => 'Ruta no encontrada'],
                JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
            );
            break;
    }
} catch (Throwable $e) {
    error_log((string) $e);
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode(
        ['error' => 'Error interno del servidor'],
        JSON_UNESCAPED_UNICODE
    );
}
