<?php
declare(strict_types=1);

$url = 'https://jsonplaceholder.typicode.com/todos/1';
$inicio = microtime(true);

$ch = curl_init($url);

if ($ch === false) {
    fwrite(STDERR, "Error: no se pudo iniciar cURL." . PHP_EOL);
    exit(1);
}

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_HTTPHEADER => ['Accept: application/json'],
]);

$respuesta = curl_exec($ch);

if ($respuesta === false) {
    $error = curl_error($ch);
    curl_close($ch);
    fwrite(STDERR, "Error de cURL: " . $error . PHP_EOL);
    exit(1);
}

$estadoHttp = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$bytes = strlen($respuesta);
curl_close($ch);

$duracionMs = round((microtime(true) - $inicio) * 1000, 2);

if ($estadoHttp !== 200) {
    fwrite(STDERR, "La API respondió con HTTP " . $estadoHttp . PHP_EOL);
    exit(1);
}

try {
    $datos = json_decode($respuesta, true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $e) {
    fwrite(STDERR, "Error al interpretar el JSON: " . $e->getMessage() . PHP_EOL);
    exit(1);
}

if (!is_array($datos)) {
    fwrite(STDERR, "Error: la respuesta no tiene la estructura esperada." . PHP_EOL);
    exit(1);
}

$resultado = [
    'estado_http' => $estadoHttp,
    'duracion_ms' => $duracionMs,
    'bytes_recibidos' => $bytes,
    'datos' => $datos,
];

echo json_encode(
    $resultado,
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
) . PHP_EOL;