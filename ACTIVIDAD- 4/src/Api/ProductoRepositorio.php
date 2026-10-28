<?php
declare(strict_types=1);

namespace Apicultores\Api;

use PDO;

final class ProductoRepositorio
{
    public function __construct(private PDO $pdo)
    {
    }

    public function listar(array $filtros = []): array
    {
        $pagina = max(1, (int) ($filtros['pagina'] ?? 1));
        $limite = max(1, min(100, (int) ($filtros['limite'] ?? 10)));
        $ordenesPermitidos = [
            'nombre' => 'nombre',
            'precio' => 'precio',
            'stock' => 'stock',
        ];

        $criterioOrden = $ordenesPermitidos[
            $filtros['orden'] ?? 'nombre'
        ] ?? 'nombre';
        $condiciones = [];
        $parametros = [];

        if (!empty($filtros['q'])) {
            $condiciones[] = '(codigo LIKE :codigo_q OR nombre LIKE :nombre_q)';
            $busqueda = '%' . $filtros['q'] . '%';
            $parametros['codigo_q'] = $busqueda;
            $parametros['nombre_q'] = $busqueda;
        }

        if (!empty($filtros['categoria'])) {
            $condiciones[] = 'categoria = :categoria';
            $parametros['categoria'] = $filtros['categoria'];
        }

        $where = $condiciones
            ? ' WHERE ' . implode(' AND ', $condiciones)
            : '';

        $consultaTotal = $this->pdo->prepare(
            'SELECT COUNT(*) FROM productos' . $where
        );

       
        $consultaTotal->execute($parametros);
        $total = (int) $consultaTotal->fetchColumn();

        $offset = ($pagina - 1) * $limite;

        $consulta = $this->pdo->prepare(
            'SELECT id, codigo, nombre, categoria, precio, stock, socio_id,
                    actualizado_en
             FROM productos' . $where . '
             ORDER BY ' . $criterioOrden . ' ASC, id ASC
             LIMIT :limite OFFSET :offset'
        );

        foreach ($parametros as $nombre => $valor) {
            $consulta->bindValue(':' . $nombre, $valor, PDO::PARAM_STR);
        }

        $consulta->bindValue(':limite', $limite, PDO::PARAM_INT);
        $consulta->bindValue(':offset', $offset, PDO::PARAM_INT);
        $consulta->execute();

        return [
            'datos' => $consulta->fetchAll(),
            'total' => $total,
            'pagina' => $pagina,
            'limite' => $limite,
        ];
    }

    public function obtenerPorCodigo(string $codigo): ?array
    {
        $consulta = $this->pdo->prepare(
            'SELECT id, codigo, nombre, categoria, precio, stock, socio_id,
                    actualizado_en
             FROM productos
             WHERE codigo = :codigo
             LIMIT 1'
        );

        $consulta->execute(['codigo' => $codigo]);
        $producto = $consulta->fetch();

        return $producto ?: null ; 
    }

    public function crear(array $datos): int
    {
        $consulta = $this->pdo->prepare(
            'INSERT INTO productos
                (codigo, nombre, categoria, precio, stock, socio_id)
             VALUES
                (:codigo, :nombre, :categoria, :precio, :stock, :socio_id)'
        );

        $consulta->execute([
            'codigo' => $datos['codigo'],
            'nombre' => $datos['nombre'],
            'categoria' => $datos['categoria'],
            'precio' => $datos['precio'],
            'stock' => $datos['stock'] ?? 0,
            'socio_id' => $datos['socio_id'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function actualizar(string $codigo, array $datos): bool
    {
        $consulta = $this->pdo->prepare(
            'UPDATE productos
             SET nombre = :nombre,
                 categoria = :categoria,
                 precio = :precio,
                 stock = :stock,
                 socio_id = :socio_id
             WHERE codigo = :codigo'
        );

        $consulta->execute([
            'nombre' => $datos['nombre'],
            'categoria' => $datos['categoria'],
            'precio' => $datos['precio'],
            'stock' => $datos['stock'],
            'socio_id' => $datos['socio_id'],
            'codigo' => $codigo,
        ]);

        return $consulta->rowCount() > 0;
    }

    public function eliminar(string $codigo): bool
    {
        $consulta = $this->pdo->prepare(
            'DELETE FROM productos WHERE codigo = :codigo'
        );

        $consulta->execute(['codigo' => $codigo]);

        return $consulta->rowCount() > 0;
    }

    public function sugerencias(string $termino): array
    {
        $consulta = $this->pdo->prepare(
            'SELECT codigo, nombre, categoria, precio
             FROM productos
             WHERE nombre LIKE :nombre_q OR codigo LIKE :codigo_q
             ORDER BY nombre ASC
             LIMIT 10'
        );

        $busqueda = '%' . $termino . '%';

        $consulta->execute([
            'nombre_q' => $busqueda,
            'codigo_q' => $busqueda,
        ]);

        return $consulta->fetchAll();
    }
}
