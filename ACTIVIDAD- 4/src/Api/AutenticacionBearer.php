<?php
declare(strict_types=1);

namespace Apicultores\Api;

use PDO;

final class AutenticacionBearer
{
    public function __construct(private PDO $pdo)
    {
    }

    public function esValido(): bool
    {
        $cabecera = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if ($cabecera === '' && function_exists('getallheaders')) {
            $cabeceras = getallheaders();

            foreach ($cabeceras as $nombre => $valor) {
                if (strcasecmp($nombre, 'Authorization') === 0) {
                    $cabecera = $valor;
                    break;
                }
            }
        }

        if (!preg_match('/^Bearer\s+([a-f0-9]{64})$/i', trim($cabecera), $coincidencias)) {
            return false;
        }

        $hash = hash('sha256', $coincidencias[1]);

        $consulta = $this->pdo->prepare(
            'SELECT id FROM api_tokens WHERE token_hash = :hash AND activo = 1 LIMIT 1'
        );
        $consulta->execute(['hash' => $hash]);

        return $consulta->fetchColumn() !== false;
    }
}
