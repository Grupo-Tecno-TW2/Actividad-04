<?php
declare(strict_types=1);

$raiz = __DIR__;
$correctas = 0;
$total = 0;

$extensiones = ['curl', 'soap', 'dom', 'xmlwriter'];

foreach ($extensiones as $extension) {
    $total++;
    $ok = extension_loaded($extension);

    echo ($ok ? '[OK] ' : '[ERROR] ')
        . "Extension PHP: {$extension}" . PHP_EOL;

    if ($ok) {
        $correctas++;
    }
}

$archivos = [
    'public/index.php',
    'public/soap.php',
    'public/soap.wsdl',
    'schemas/pedido.xsd',
    'src/Validacion/ValidadorJson.php',
    'src/Validacion/ValidadorXml.php',
    'pruebas/verificar-validaciones.php',
    'consumir_publico.php',
];

foreach ($archivos as $archivo) {
    $total++;
    $ok = is_file($raiz . DIRECTORY_SEPARATOR . $archivo);

    echo ($ok ? '[OK] ' : '[ERROR] ')
        . "Archivo: {$archivo}" . PHP_EOL;

    if ($ok) {
        $correctas++;
    }
}

echo PHP_EOL;
echo "Comprobaciones generales: {$correctas}/{$total}" . PHP_EOL;
echo PHP_EOL;

if ($correctas !== $total) {
    exit(1);
}

require $raiz . '/pruebas/verificar-validaciones.php';
