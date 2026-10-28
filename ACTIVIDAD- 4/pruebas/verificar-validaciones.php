<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Lab12\Validacion\ValidadorJson;
use Lab12\Validacion\ValidadorXml;

$raiz = dirname(__DIR__);
$json = new ValidadorJson();
$xml = new ValidadorXml();

$pruebas = [
    [
        'nombre' => 'JSON valido',
        'resultado' => $json->validar($raiz . '/pruebas/pedido-valido.json'),
        'esperado' => true,
    ],
    [
        'nombre' => 'JSON invalido',
        'resultado' => $json->validar($raiz . '/pruebas/pedido-invalido.json'),
        'esperado' => false,
    ],
    [
        'nombre' => 'XML valido',
        'resultado' => $xml->validar(
            $raiz . '/pruebas/pedido-valido.xml',
            $raiz . '/schemas/pedido.xsd'
        ),
        'esperado' => true,
    ],
    [
        'nombre' => 'XML invalido',
        'resultado' => $xml->validar(
            $raiz . '/pruebas/pedido-invalido.xml',
            $raiz . '/schemas/pedido.xsd'
        ),
        'esperado' => false,
    ],
];

$correctas = 0;

foreach ($pruebas as $prueba) {
    $resultado = $prueba['resultado'];
    $coincide = $resultado['valido'] === $prueba['esperado'];

    if ($coincide) {
        $correctas++;
    }

    echo ($coincide ? '[OK] ' : '[ERROR] ')
        . $prueba['nombre'] . PHP_EOL;

    echo 'Resultado: ' . $resultado['mensaje'] . PHP_EOL;

    foreach ($resultado['errores'] as $error) {
        echo 'Detalle: ' . $error . PHP_EOL;
    }

    echo PHP_EOL;
}

$total = count($pruebas);
echo "Resumen: {$correctas}/{$total} pruebas correctas." . PHP_EOL;

exit($correctas === $total ? 0 : 1);