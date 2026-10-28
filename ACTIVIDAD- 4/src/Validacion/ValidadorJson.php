<?php
declare(strict_types=1);

namespace Lab12\Validacion;

use JsonException;
use RuntimeException;

final class ValidadorJson
{
    public function validar(string $archivoJson): array
    {
        if (!is_file($archivoJson) || !is_readable($archivoJson)) {
            throw new RuntimeException('No se puede leer el archivo JSON.');
        }

        try {
            $pedido = json_decode(
                file_get_contents($archivoJson),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $e) {
            return [
                'valido' => false,
                'mensaje' => 'El archivo no contiene JSON valido.',
                'errores' => [$e->getMessage()],
            ];
        }

        $errores = [];

        if (!is_array($pedido)) {
            return [
                'valido' => false,
                'mensaje' => 'El pedido debe ser un objeto JSON.',
                'errores' => ['La estructura principal debe ser un objeto.'],
            ];
        }

        if (
            !isset($pedido['codigo']) ||
            !is_string($pedido['codigo']) ||
            trim($pedido['codigo']) === ''
        ) {
            $errores[] = 'El codigo del pedido es obligatorio.';
        }

        $fechaValida = false;

        if (isset($pedido['fecha']) && is_string($pedido['fecha'])) {
            $fecha = \DateTimeImmutable::createFromFormat('!Y-m-d', $pedido['fecha']);

            $fechaValida = $fecha !== false
                && $fecha->format('Y-m-d') === $pedido['fecha'];
        }

        if (!$fechaValida) {
            $errores[] = 'La fecha debe tener el formato YYYY-MM-DD y ser valida.';
        }

        if (
            !isset($pedido['productos']) ||
            !is_array($pedido['productos']) ||
            count($pedido['productos']) === 0
        ) {
            $errores[] = 'El pedido debe contener al menos un producto.';
        } else {
            foreach ($pedido['productos'] as $indice => $producto) {
                $numero = $indice + 1;

                if (!is_array($producto)) {
                    $errores[] = "El producto {$numero} debe ser un objeto.";
                    continue;
                }

                if (
                    !isset($producto['codigo']) ||
                    !is_string($producto['codigo']) ||
                    trim($producto['codigo']) === ''
                ) {
                    $errores[] = "El producto {$numero} necesita un codigo.";
                }

                if (
                    !isset($producto['cantidad']) ||
                    !is_int($producto['cantidad']) ||
                    $producto['cantidad'] <= 0
                ) {
                    $errores[] = "La cantidad del producto {$numero} debe ser un entero positivo.";
                }

                if (
                    !isset($producto['precio']) ||
                    (!is_int($producto['precio']) && !is_float($producto['precio'])) ||
                    !is_finite((float) ($producto['precio'] ?? 0)) ||
                    $producto['precio'] < 0
                ) {
                    $errores[] = "El precio del producto {$numero} debe ser numerico y no negativo.";
                }
            }
        }

        return [
            'valido' => count($errores) === 0,
            'mensaje' => count($errores) === 0
                ? 'El pedido JSON es valido.'
                : 'El pedido JSON contiene errores.',
            'errores' => $errores,
        ];
    }
}