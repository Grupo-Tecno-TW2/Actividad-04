<?php
declare(strict_types=1);

namespace Lab12\Datos;

final class Catalogo
{
    public static function todos(): array
    {
        return [
            [
                'codigo' => 'MIE-500',
                'nombre' => 'Miel natural',
                'descripcion' => 'Miel pura de abeja',
                'categoria' => 'Miel',
                'precio' => '45.00',
                'stock' => 25,
                'actualizado' => '2026-10-25T12:00:00-04:00',
            ],
            [
                'codigo' => 'POL-250',
                'nombre' => 'Polen natural',
                'descripcion' => 'Polen de abeja',
                'categoria' => 'Derivados',
                'precio' => '30.00',
                'stock' => 18,
                'actualizado' => '2026-10-25T12:00:00-04:00',
            ],
            [
                'codigo' => 'PRO-100',
                'nombre' => 'Propoleo',
                'descripcion' => 'Polen & propóleo: muestra de texto XML',
                'categoria' => 'Derivados',
                'precio' => '25.50',
                'stock' => 12,
                'actualizado' => '2026-10-25T12:00:00-04:00',
            ],
        ];
    }

    public static function buscar(string $codigo): ?array
    {
        foreach (self::todos() as $producto) {
            if ($producto['codigo'] === $codigo) {
                return $producto;
            }
        }

        return null;
    }
}