<?php
declare(strict_types=1);

namespace Lab12\Servicios;

use JsonException;
use RuntimeException;

final class ClienteApiPublica
{
    public function obtenerTodo(int $id = 1): array
    {
        if ($id <= 0) {
            throw new RuntimeException(
                'El identificador debe ser positivo.'
            );
        }

        $url = 'https://jsonplaceholder.typicode.com/todos/' . $id;
        $curl = curl_init($url);

        if ($curl === false) {
            throw new RuntimeException('No se pudo iniciar cURL.');
        }

        try {
            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_HTTPHEADER => ['Accept: application/json'],
                CURLOPT_USERAGENT => 'Lab12-PHP/1.0',
            ]);

            $inicio = microtime(true);
            $respuesta = curl_exec($curl);
            $duracionMs = (microtime(true) - $inicio) * 1000;
            $estadoHttp = (int) curl_getinfo(
                $curl,
                CURLINFO_HTTP_CODE
            );

            if ($respuesta === false) {
                throw new RuntimeException(
                    'Error cURL: ' . curl_error($curl)
                );
            }

            if ($estadoHttp < 200 || $estadoHttp >= 300) {
                throw new RuntimeException(
                    "La API respondió con HTTP {$estadoHttp}."
                );
            }

            try {
                $datos = json_decode(
                    $respuesta,
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );
            } catch (JsonException $e) {
                throw new RuntimeException(
                    'La API no devolvió JSON válido.',
                    0,
                    $e
                );
            }

            if (!is_array($datos)) {
                throw new RuntimeException(
                    'La respuesta no tiene la estructura esperada.'
                );
            }

            return [
                'estado_http' => $estadoHttp,
                'duracion_ms' => round($duracionMs, 2),
                'bytes_recibidos' => strlen($respuesta),
                'datos' => $datos,
            ];
        } finally {
            curl_close($curl);
        }
    }
}