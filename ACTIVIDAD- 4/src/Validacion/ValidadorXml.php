<?php
declare(strict_types=1);

namespace Lab12\Validacion;

use DOMDocument;
use RuntimeException;

final class ValidadorXml
{
    public function validar(string $archivoXml, string $archivoXsd): array
    {
        if (!is_file($archivoXml) || !is_readable($archivoXml)) {
            throw new RuntimeException('No se puede leer el archivo XML.');
        }

        if (!is_file($archivoXsd) || !is_readable($archivoXsd)) {
            throw new RuntimeException('No se puede leer el esquema XSD.');
        }

        $documento = new DOMDocument();
        $documento->resolveExternals = false;
        $documento->substituteEntities = false;

        $errores = [];

        $anterior = libxml_use_internal_errors(true);
        libxml_clear_errors();

        try {
            $cargado = $documento->load(
                $archivoXml,
                LIBXML_NONET
            );

            if (!$cargado) {
                foreach (libxml_get_errors() as $error) {
                    $errores[] = trim($error->message);
                }

                return [
                    'valido' => false,
                    'mensaje' => 'El documento XML no está bien formado.',
                    'errores' => $errores,
                ];
            }

            libxml_clear_errors();

            $valido = $documento->schemaValidate($archivoXsd);

            foreach (libxml_get_errors() as $error) {
                $errores[] = trim($error->message);
            }

            return [
                'valido' => $valido,
                'mensaje' => $valido
                    ? 'El XML cumple con el esquema XSD.'
                    : 'El XML no cumple con el esquema XSD.',
                'errores' => $errores,
            ];
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($anterior);
        }
    }
}