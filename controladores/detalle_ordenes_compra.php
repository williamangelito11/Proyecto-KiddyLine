<?php
require_once __DIR__ . '/../modelos/util.php';
require_once __DIR__ . '/../modelos/inventario.php';
iniciarApi();

// Recalcula subtotal y total de la orden a partir de todos sus detalles
function recalcularOrden(PDO $db, int $orden): void {
    $stmt = $db->prepare(
        "UPDATE ordenes_compra
         SET subtotal = (SELECT COALESCE(SUM(subtotal), 0) FROM detalle_ordenes_compra WHERE id_orden_compra = :o1),
             total = GREATEST(0, (SELECT COALESCE(SUM(subtotal), 0) FROM detalle_ordenes_compra WHERE id_orden_compra = :o2) - COALESCE(descuento, 0))
         WHERE id_orden_compra = :o3"
    );
    $stmt->execute([':o1' => $orden, ':o2' => $orden, ':o3' => $orden]);
}

// Bloquea la orden y verifica que exista y que su mercancía no se haya sumado ya al inventario
function validarOrdenAbierta(PDO $db, int $orden): void {
    $stmt = $db->prepare("SELECT id_orden_compra FROM ordenes_compra WHERE id_orden_compra = :id FOR UPDATE");
    $stmt->execute([':id' => $orden]);
    if ($stmt->fetchColumn() === false) {
        cancelarConError($db, 'La orden de compra indicada no existe.');
    }
    if (recepcionAplicada($db, $orden)) {
        cancelarConError($db, "La orden #$orden ya fue recibida y su mercancía está en el inventario; no se pueden modificar sus detalles.");
    }
}

try {
    $db = Conexion::conectar();

    switch (operacion()) {
        case 'consultar':
            $stmt = $db->query("SELECT d.*, a.nombre AS nombre_articulo
                                FROM detalle_ordenes_compra d
                                LEFT JOIN articulos a ON d.id_articulo = a.id_articulo
                                ORDER BY d.id_detalle_compra DESC");
            responder($stmt->fetchAll(PDO::FETCH_ASSOC));

        case 'insertar':
            $d = cuerpo();
            if (empty($d['id_orden_compra']) || empty($d['id_articulo'])) {
                fallo('Faltan datos obligatorios (orden y artículo).');
            }
            $orden    = (int)$d['id_orden_compra'];
            $cantidad = (int)($d['cantidad'] ?? 0);
            $precio   = (float)($d['precio_unitario'] ?? 0);

            $db->beginTransaction();
            validarOrdenAbierta($db, $orden);

            $stmt = $db->prepare("INSERT INTO detalle_ordenes_compra (id_orden_compra, id_articulo, cantidad, precio_unitario, subtotal)
                                  VALUES (:orden, :articulo, :cantidad, :precio, :subtotal)");
            $stmt->execute([
                ':orden' => $orden, ':articulo' => (int)$d['id_articulo'],
                ':cantidad' => $cantidad, ':precio' => $precio, ':subtotal' => $cantidad * $precio,
            ]);
            recalcularOrden($db, $orden);
            $db->commit();
            exito('Registrado correctamente');

        case 'editar':
            $d = cuerpo();
            if (empty($d['id_detalle_compra']) || empty($d['id_articulo'])) {
                fallo('Faltan datos obligatorios.');
            }
            $id       = (int)$d['id_detalle_compra'];
            $cantidad = (int)($d['cantidad'] ?? 0);
            $precio   = (float)($d['precio_unitario'] ?? 0);

            $stmt = $db->prepare("SELECT id_orden_compra FROM detalle_ordenes_compra WHERE id_detalle_compra = :id");
            $stmt->execute([':id' => $id]);
            $orden = $stmt->fetchColumn();
            if ($orden === false) fallo('El detalle no existe.');
            $orden = (int)$orden;

            $db->beginTransaction();
            validarOrdenAbierta($db, $orden);

            $stmt = $db->prepare("UPDATE detalle_ordenes_compra
                                  SET id_articulo = :articulo, cantidad = :cantidad, precio_unitario = :precio, subtotal = :subtotal
                                  WHERE id_detalle_compra = :id");
            $stmt->execute([
                ':articulo' => (int)$d['id_articulo'], ':cantidad' => $cantidad,
                ':precio' => $precio, ':subtotal' => $cantidad * $precio, ':id' => $id,
            ]);
            recalcularOrden($db, $orden);
            $db->commit();
            exito('Detalle actualizado correctamente');

        case 'eliminar':
            $d = cuerpo();
            if (empty($d['id_detalle_compra'])) fallo('No se recibió el ID del detalle.');
            $id = (int)$d['id_detalle_compra'];

            $stmt = $db->prepare("SELECT id_orden_compra FROM detalle_ordenes_compra WHERE id_detalle_compra = :id");
            $stmt->execute([':id' => $id]);
            $orden = $stmt->fetchColumn();
            if ($orden === false) fallo('El detalle no existe o ya fue eliminado.');
            $orden = (int)$orden;

            $db->beginTransaction();
            validarOrdenAbierta($db, $orden);

            $db->prepare("DELETE FROM detalle_ordenes_compra WHERE id_detalle_compra = :id")->execute([':id' => $id]);
            recalcularOrden($db, $orden);
            $db->commit();
            exito('Detalle eliminado correctamente');

        default:
            fallo('Operación no válida');
    }
} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    if (($e->errorInfo[1] ?? 0) == 1452) {
        fallo('La orden de compra o el artículo indicado no existe. Verifica la orden seleccionada.');
    }
    errorBD($e, 'este detalle');
}
