<?php
declare(strict_types=1);

namespace Lab12\Servicios;

use Lab12\Datos\Catalogo;
use SoapFault;
use stdClass;

final class CatalogoSoap
{
    public function obtenerProducto($parametros): stdClass
    {
        if (is_string($parametros)) {
            $codigo = $parametros;
        } elseif (is_object($parametros)) {
            $codigo = $parametros->codigo ?? null;
        } elseif (is_array($parametros)) {
            $codigo = $parametros['codigo'] ?? null;
        } else {
            $codigo = null;
        }

        if (!is_string($codigo) || trim($codigo) === '') {
            throw new SoapFault(
                'Client',
                'El codigo del producto no fue recibido correctamente.'
            );
        }

        $producto = Catalogo::buscar(trim($codigo));

        if ($producto === null) {
            throw new SoapFault(
                'Client',
                'Producto no encontrado: ' . $codigo
            );
        }

        $respuesta = new stdClass();
        $respuesta->return = (object) $producto;

        return $respuesta;
    }
}