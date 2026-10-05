<?php
require_once __DIR__ . '/../modelos/util.php';
iniciarApi();

function camposArticulo(array $d): array {
    if (trim($d['codigo_referencia'] ?? '') === '' || trim($d['nombre'] ?? '') === '') {
        fallo('El código de referencia y el nombre son obligatorios.');
    }
    if (empty($d['id_categoria'])) {
        fallo('Debes seleccionar una categoría.');
    }
    return [
        ':categoria' => (int)$d['id_categoria'],
        ':codigo'    => trim($d['codigo_referencia']),
        ':nombre'    => trim($d['nombre']),
        ':tipo'      => !empty($d['tipo_articulo']) ? $d['tipo_articulo'] : 'producto_terminado',
        ':talla'     => !empty($d['talla']) ? trim($d['talla']) : 'M',
        ':color'     => !empty($d['color']) ? trim($d['color']) : 'Blanco',
        ':stock'     => (int)($d['stock_actual'] ?? 0),
        ':minimo'    => (int)($d['stock_minimo'] ?? 5),
        ':costo'     => (float)($d['precio_costo'] ?? 0),
        ':venta'     => (float)($d['precio_venta'] ?? 0),
    ];
}

try {
    $db = Conexion::conectar();

    switch (operacion()) {
        case 'consultar':
            $stmt = $db->query("SELECT a.*, c.nombre AS nombre_categoria
                                FROM articulos a
                                LEFT JOIN categorias c ON a.id_categoria = c.id_categoria
                                ORDER BY a.id_articulo DESC");
            responder($stmt->fetchAll(PDO::FETCH_ASSOC));

        case 'insertar':
            $campos = camposArticulo(cuerpo());
            $stmt = $db->prepare("INSERT INTO articulos (id_categoria, codigo_referencia, nombre, tipo_articulo, talla, color,
                                  stock_actual, stock_minimo, precio_costo, precio_venta)
                                  VALUES (:categoria, :codigo, :nombre, :tipo, :talla, :color, :stock, :minimo, :costo, :venta)");
            $stmt->execute($campos);
            exito('Artículo guardado exitosamente');

        case 'editar':
            $d = cuerpo();
            if (empty($d['id_articulo'])) fallo('No se recibió el ID del artículo.');
            $campos = camposArticulo($d);
            $campos[':id'] = (int)$d['id_articulo'];
            $stmt = $db->prepare("UPDATE articulos SET id_categoria = :categoria, codigo_referencia = :codigo, nombre = :nombre,
                                  tipo_articulo = :tipo, talla = :talla, color = :color, stock_actual = :stock,
                                  stock_minimo = :minimo, precio_costo = :costo, precio_venta = :venta
                                  WHERE id_articulo = :id");
            $stmt->execute($campos);
            exito('Artículo actualizado correctamente');

        case 'eliminar':
            $d = cuerpo();
            if (empty($d['id_articulo'])) fallo('No se recibió el ID del artículo.');
            $stmt = $db->prepare("DELETE FROM articulos WHERE id_articulo = :id");
            $stmt->execute([':id' => (int)$d['id_articulo']]);
            if ($stmt->rowCount() === 0) fallo('El artículo no existe o ya fue eliminado.');
            exito('Artículo eliminado correctamente');

        default:
            fallo('Opción no válida');
    }
} catch (PDOException $e) {
    if (($e->errorInfo[1] ?? 0) == 1062) {
        fallo('El código de referencia ya existe en otro artículo.');
    }
    errorBD($e, 'este artículo (aparece en órdenes, ventas o movimientos)');
}
