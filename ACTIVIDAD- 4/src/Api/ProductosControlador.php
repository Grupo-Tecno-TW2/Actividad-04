<?php
declare(strict_types=1);
namespace Apicultores\Api;

use JsonException;
use PDOException;

final class ProductosControlador
{
    public function __construct(
        private ProductoRepositorio $repositorio,
        private AutenticacionBearer $autenticacion
    ) {
    }

    public function manejar(string $metodo, string $ruta): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (
            in_array($metodo, ['POST', 'PUT', 'DELETE'], true) &&
            !$this->autenticacion->esValido()
        ) {
            header('WWW-Authenticate: Bearer');
            $this->responder(401, [
                'error' => 'Se requiere un token Bearer valido.'
            ]);
            return;
        }

        // GET /api/v1/productos/sugerencias?q=miel
        // Esta ruta fija debe comprobarse antes de /productos/{codigo}.
        if ($ruta === '/api/v1/productos/sugerencias') {
            if ($metodo !== 'GET') {
                header('Allow: GET, OPTIONS');
                $this->responder(405, [
                    'error' => 'Metodo no permitido.'
                ]);
                return;
            }

            $termino = trim((string) ($_GET['q'] ?? ''));

            if (mb_strlen($termino) < 2 || mb_strlen($termino) > 80) {
                $this->responder(422, [
                    'error' => 'El parametro q debe tener entre 2 y 80 caracteres.'
                ]);
                return;
            }

            $this->responder(200, [
                'datos' => $this->repositorio->sugerencias($termino)
            ]);
            return;
        }

        // GET /api/v1/productos
        // POST /api/v1/productos
        if ($ruta === '/api/v1/productos') {
            if ($metodo === 'POST') {
                $this->crearProducto();
                return;
            }

            if ($metodo !== 'GET') {
                header('Allow: GET, POST, OPTIONS');
                $this->responder(405, [
                    'error' => 'Metodo no permitido.'
                ]);
                return;
            }

            $pagina = filter_var(
                $_GET['pagina'] ?? 1,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );

            // Se prefiere por_pagina; limite se conserva por compatibilidad.
            $valorLimite = $_GET['por_pagina']
                ?? $_GET['limite']
                ?? 10;

            $limite = filter_var(
                $valorLimite,
                FILTER_VALIDATE_INT,
                ['options' => [
                    'min_range' => 1,
                    'max_range' => 100
                ]]
            );

            // No aceptar valores inválidos ni corregirlos silenciosamente.
            if ($pagina === false || $limite === false) {
                $this->responder(422, [
                    'error' => 'pagina debe ser un entero positivo y por_pagina debe estar entre 1 y 100.'
                ]);
                return;
            }

            // Si se proporcionan ambos parámetros, validar que no sean
            // contradictorios.
            if (
                isset($_GET['por_pagina'], $_GET['limite']) &&
                (string) $_GET['por_pagina'] !== (string) $_GET['limite']
            ) {
                $this->responder(422, [
                    'error' => 'No envie valores diferentes para por_pagina y limite.'
                ]);
                return;
            }

            $filtros = [
                'pagina' => $pagina,
                'limite' => $limite,
                'q' => trim((string) ($_GET['q'] ?? '')),
                'categoria' => trim((string) ($_GET['categoria'] ?? '')),
                'orden' => trim((string) ($_GET['orden'] ?? 'nombre')),
            ];

            if (!in_array(
                $filtros['orden'],
                ['nombre', 'precio', 'stock'],
                true
            )) {
                $this->responder(422, [
                    'error' => 'Orden no valido. Use nombre, precio o stock.'
                ]);
                return;
            }

            if (
                $filtros['categoria'] !== '' &&
                !in_array(
                    $filtros['categoria'],
                    ['Miel', 'Derivados', 'Cera'],
                    true
                )
            ) {
                $this->responder(422, [
                    'error' => 'Categoria no valida.'
                ]);
                return;
            }

            $resultado = $this->repositorio->listar($filtros);
            $base = '/api/v1/productos';

            $ultimaPagina = max(
                1,
                (int) ceil(
                    $resultado['total'] / $resultado['limite']
                )
            );

            $resultado['meta'] = [
                'total' => $resultado['total'],
                'pagina' => $resultado['pagina'],
                'limite' => $resultado['limite'],
                'ultima_pagina' => $ultimaPagina,
            ];

            // Los enlaces de respuesta utilizan el nombre de la guía.
            $parametrosEnlace = [
                'pagina' => $pagina,
                'por_pagina' => $limite,
            ];

            if ($filtros['q'] !== '') {
                $parametrosEnlace['q'] = $filtros['q'];
            }

            if ($filtros['categoria'] !== '') {
                $parametrosEnlace['categoria'] = $filtros['categoria'];
            }

            $resultado['enlaces'] = [
                'self' => $base . '?' . http_build_query($parametrosEnlace),
                'primera' => $base . '?' . http_build_query(
                    array_merge($parametrosEnlace, ['pagina' => 1])
                ),
                'ultima' => $base . '?' . http_build_query(
                    array_merge(
                        $parametrosEnlace,
                        ['pagina' => $ultimaPagina]
                    )
                ),
                'sugerencias' => $base . '/sugerencias',
            ];

            unset(
                $resultado['total'],
                $resultado['pagina'],
                $resultado['limite']
            );

            $this->responder(200, $resultado);
            return;
        }

        // Recurso individual: GET, PUT o DELETE /productos/{codigo}
        if (
            preg_match(
                '#^/api/v1/productos/([A-Za-z0-9-]+)$#',
                $ruta,
                $coincidencias
            )
        ) {
            $codigo = $coincidencias[1];

            if ($metodo === 'PUT') {
                $this->actualizarProducto($codigo);
                return;
            }

            if ($metodo === 'DELETE') {
                $this->eliminarProducto($codigo);
                return;
            }

            if ($metodo !== 'GET') {
                header('Allow: GET, PUT, DELETE, OPTIONS');
                $this->responder(405, [
                    'error' => 'Metodo no permitido.'
                ]);
                return;
            }

            $producto = $this->repositorio->obtenerPorCodigo($codigo);

            if ($producto === null) {
                $this->responder(404, [
                    'error' => 'Producto no encontrado.'
                ]);
                return;
            }

            $json = json_encode(
                $producto,
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES |
                JSON_THROW_ON_ERROR
            );

            $etag = '"' . hash('sha256', $json) . '"';

            header('ETag: ' . $etag);
            header('Cache-Control: no-cache');

            $ifNoneMatch = trim(
                (string) ($_SERVER['HTTP_IF_NONE_MATCH'] ?? '')
            );

            if ($ifNoneMatch !== '') {
                $etags = array_map(
                    static fn(string $valor): string =>
                        trim($valor, " \t\n\r\0\x0B\""),
                    explode(',', $ifNoneMatch)
                );

                if (
                    in_array('*', $etags, true) ||
                    in_array(trim($etag, '"'), $etags, true)
                ) {
                    http_response_code(304);
                    return;
                }
            }

            http_response_code(200);
            echo $json;
            return;
        }

        $this->responder(404, [
            'error' => 'Ruta no encontrada.'
        ]);
    }

    private function crearProducto(): void
    {
        if (!$this->esJson()) {
            $this->responder(415, [
                'error' => 'El Content-Type debe ser application/json.'
            ]);
            return;
        }

        $datos = $this->leerJson();

        if ($datos === null) {
            return;
        }

        $errores = $this->validarProducto($datos);

        if ($errores !== []) {
            $this->responder(422, ['errores' => $errores]);
            return;
        }

        try {
            $this->repositorio->crear($datos);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $this->responder(409, [
                    'error' => 'El codigo ya existe o el socio no es valido.'
                ]);
                return;
            }

            throw $e;
        }

        $producto = $this->repositorio->obtenerPorCodigo(
            $datos['codigo']
        );

        header(
            'Location: /api/v1/productos/' .
            rawurlencode($datos['codigo'])
        );

        $this->responder(201, ['datos' => $producto]);
    }

    private function actualizarProducto(string $codigo): void
    {
        if (!$this->esJson()) {
            $this->responder(415, [
                'error' => 'El Content-Type debe ser application/json.'
            ]);
            return;
        }

        $datos = $this->leerJson();

        if ($datos === null) {
            return;
        }

        $existente = $this->repositorio->obtenerPorCodigo($codigo);

        if ($existente === null) {
            $this->responder(404, [
                'error' => 'Producto no encontrado.'
            ]);
            return;
        }

        // Conservar el stock actual si no se envía.
        if (!array_key_exists('stock', $datos)) {
            $datos['stock'] = $existente['stock'];
        }

        // El código de la URL no se modifica.
        $datos['codigo'] = $codigo;

        $errores = $this->validarProducto($datos);

        if ($errores !== []) {
            $this->responder(422, ['errores' => $errores]);
            return;
        }

        try {
            $this->repositorio->actualizar($codigo, $datos);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $this->responder(409, [
                    'error' => 'El socio no es valido.'
                ]);
                return;
            }

            throw $e;
        }

        $actualizado = $this->repositorio->obtenerPorCodigo($codigo);

        $this->responder(200, [
            'datos' => $actualizado,
            'mensaje' => 'Producto actualizado correctamente.'
        ]);
    }

    private function eliminarProducto(string $codigo): void
    {
        if ($this->repositorio->obtenerPorCodigo($codigo) === null) {
            $this->responder(404, [
                'error' => 'Producto no encontrado.'
            ]);
            return;
        }

        $this->repositorio->eliminar($codigo);

        // HTTP 204 no debe incluir cuerpo.
        http_response_code(204);
    }

    private function esJson(): bool
    {
        $tipo = trim((string) ($_SERVER['CONTENT_TYPE'] ?? ''));

        return stripos($tipo, 'application/json') === 0;
    }

    private function leerJson(): ?array
    {
        try {
            $datos = json_decode(
                file_get_contents('php://input'),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $e) {
            $this->responder(400, [
                'error' => 'El JSON enviado no es valido.'
            ]);
            return null;
        }

        if (!is_array($datos) || array_is_list($datos)) {
            $this->responder(422, [
                'error' => 'El cuerpo debe ser un objeto JSON.'
            ]);
            return null;
        }

        return $datos;
    }

    private function validarProducto(array $datos): array
    {
        $errores = [];

        foreach (
            ['codigo', 'nombre', 'categoria', 'precio', 'socio_id']
            as $campo
        ) {
            if (!array_key_exists($campo, $datos)) {
                $errores[$campo] = 'Campo obligatorio.';
            }
        }

        if ($errores !== []) {
            return $errores;
        }

        if (
            !is_string($datos['codigo']) ||
            !preg_match('/^[A-Za-z0-9-]{1,8}$/', $datos['codigo'])
        ) {
            $errores['codigo'] =
                'Debe tener entre 1 y 8 caracteres alfanumericos o guiones.';
        }

        if (
            !is_string($datos['nombre']) ||
            trim($datos['nombre']) === '' ||
            mb_strlen($datos['nombre']) > 80
        ) {
            $errores['nombre'] = 'Debe tener entre 1 y 80 caracteres.';
        }

        if (
            !is_string($datos['categoria']) ||
            !in_array(
                $datos['categoria'],
                ['Miel', 'Derivados', 'Cera'],
                true
            )
        ) {
            $errores['categoria'] = 'Categoria no valida.';
        }

        if (
            !is_string($datos['precio']) &&
            !is_int($datos['precio']) &&
            !is_float($datos['precio'])
        ) {
            $errores['precio'] = 'El precio debe ser numerico positivo.';
        } elseif (
            !is_finite((float) $datos['precio']) ||
            (float) $datos['precio'] <= 0 ||
            !preg_match(
                '/^\d+(\.\d{1,2})?$/',
                (string) $datos['precio']
            )
        ) {
            $errores['precio'] =
                'Debe ser un precio positivo con hasta dos decimales.';
        }

        if (
            filter_var($datos['socio_id'], FILTER_VALIDATE_INT) === false ||
            (int) $datos['socio_id'] < 1
        ) {
            $errores['socio_id'] =
                'Debe ser un identificador entero positivo.';
        }

        if (
            isset($datos['stock']) &&
            (
                filter_var($datos['stock'], FILTER_VALIDATE_INT) === false ||
                (int) $datos['stock'] < 0
            )
        ) {
            $errores['stock'] =
                'Debe ser un entero mayor o igual a cero.';
        }

        return $errores;
    }
    private function responder(int $estado, array $datos): void
    {
    http_response_code($estado);

    $esError = $estado >= 400;

    if ($esError) {
        header('Content-Type: application/problem+json; charset=utf-8');

        $problema = [
            'type' => 'about:blank',
            'title' => match ($estado) {
                400 => 'Solicitud incorrecta',
                401 => 'No autenticado',
                403 => 'Acceso prohibido',
                404 => 'Recurso no encontrado',
                405 => 'Método no permitido',
                409 => 'Conflicto',
                415 => 'Tipo de contenido no admitido',
                422 => 'Entidad no procesable',
                default => 'Error de solicitud',
            },
            'status' => $estado,
            'detail' => (string) ($datos['error'] ?? 'Se produjo un error.'),
        ];

        if (isset($datos['errores'])) {
            $problema['errores'] = $datos['errores'];
        }

        echo json_encode(
            $problema,
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES |
            JSON_PRETTY_PRINT
        );
        return;
    }

    header('Content-Type: application/json; charset=utf-8');

    echo json_encode(
        $datos,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES |
        JSON_PRETTY_PRINT
    );
    }
}
