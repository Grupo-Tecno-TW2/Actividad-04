<?php
declare(strict_types=1);

namespace Lab12\Datos;

use XMLWriter;

final class CatalogoControlador
{
    public function json(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode(
            [
                'generado' => date(DATE_ATOM),
                'total' => count(Catalogo::todos()),
                'productos' => Catalogo::todos(),
            ],
            JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_PRETTY_PRINT
                | JSON_THROW_ON_ERROR
        );
    }

    public function xml(): void
    {
        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->setIndent(true);

        $xml->startElement('catalogo');
        $xml->writeAttribute('generado', date(DATE_ATOM));
        $xml->writeAttribute('total', (string) count(Catalogo::todos()));

        foreach (Catalogo::todos() as $producto) {
            $xml->startElement('producto');

            foreach ($producto as $campo => $valor) {
                $xml->writeElement($campo, (string) $valor);
            }

            $xml->endElement();
        }

        $xml->endElement();
        $xml->endDocument();

        header('Content-Type: application/xml; charset=utf-8');
        echo $xml->outputMemory();
    }

    public function producto(string $codigo): void
    {
        $producto = Catalogo::buscar($codigo);

        header('Content-Type: application/json; charset=utf-8');

        if ($producto === null) {
            http_response_code(404);
            echo json_encode(
                ['error' => 'Producto no encontrado'],
                JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
            );
            return;
        }

        echo json_encode(
            $producto,
            JSON_UNESCAPED_UNICODE
                | JSON_PRETTY_PRINT
                | JSON_THROW_ON_ERROR
        );
    }
}