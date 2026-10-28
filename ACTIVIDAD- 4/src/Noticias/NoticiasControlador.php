<?php
declare(strict_types=1);

namespace Lab12\Noticias;

use XMLWriter;

final class NoticiasControlador
{
    public function rss(): void
    {
        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->setIndent(true);

        $xml->startElement('rss');
        $xml->writeAttribute('version', '2.0');

        $xml->startElement('channel');
        $xml->writeElement('title', 'Noticias del catálogo');
        $xml->writeElement('link', 'http://127.0.0.1:8000/');
        $xml->writeElement(
            'description',
            'Novedades de productos y derivados de la miel'
        );
        $xml->writeElement('language', 'es-bo');
        $xml->writeElement('lastBuildDate', date(DATE_RSS));

        $noticias = [
            [
                'titulo' => 'Catálogo de productos disponible',
                'descripcion' => 'Se publicó el catálogo de miel, polen y propóleo.',
                'id' => 'noticia-catalogo-001',
            ],
            [
                'titulo' => 'Datos disponibles en JSON y XML',
                'descripcion' => 'El catálogo puede consultarse en formatos JSON y XML.',
                'id' => 'noticia-formatos-002',
            ],
        ];

        foreach ($noticias as $noticia) {
            $xml->startElement('item');
            $xml->writeElement('title', $noticia['titulo']);
            $xml->writeElement('description', $noticia['descripcion']);
            $xml->writeElement('guid', $noticia['id']);
            $xml->writeElement('pubDate', date(DATE_RSS));
            $xml->endElement();
        }

        $xml->endElement();
        $xml->endElement();
        $xml->endDocument();

        header('Content-Type: application/rss+xml; charset=utf-8');
        echo $xml->outputMemory();
    }
}