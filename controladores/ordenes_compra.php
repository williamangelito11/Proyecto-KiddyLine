<?php
require_once __DIR__ . '/../modelos/util.php';
require_once __DIR__ . '/../modelos/inventario.php';
iniciarApi();

const ESTADOS_ORDEN = ['emitida', 'recibida', 'cancelada'];

// Usuario que queda registrado en los movimientos: el de la sesión o, si no hay, el de la solicitud
function usuarioQueRecibe(PDO $db, array $orden): int {
    $sesion = usuarioAutenticado();
    if ($sesion !== null && !empty($sesion['uid'])) {
        return (int)$sesion['uid'];
    }
    if (!empty($orden['id_solicitud'])) {
        $stmt = $db->prepare("SELECT id_usuario FROM solicitudes_compra WHERE id_solicitud = :id");
        $stmt->execute([':id' => (int)$orden['id_solicitud']]);
        $usuario = $stmt->fetchColumn();
        if ($usuario !== false) {
            return (int)$usuario;
        }
    }
    return 0;
}

try {
    $db = Conexion::conectar();

    switch (operacion()) {
        case 'consultar':
            $stmt = $db->query("SELECT o.*, p.razon_social AS nombre_proveedor,
                                       (SELECT COUNT(*) FROM detalle_ordenes_compra d WHERE d.id_orden_compra = o.id_orden_compra) AS lineas
                                FROM ordenes_compra o
                                LEFT JOIN proveedores p ON o.id_proveedor = p.id_proveedor
                                ORDER BY o.id_orden_compra DESC");
            $ordenes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Marca las órdenes cuya mercancía ya se sumó al inventario
            $stmt = $db->query("SELECT motivo FROM movimientos_inventario
                                WHERE tipo_movimiento = 'entrada' AND motivo LIKE 'Orden de compra #% recibida'");
            $aplicadas = array_flip($stmt->fetchAll(PDO::FETCH_COLUMN));
            foreach ($ordenes as &$o) {
                $o['stock_aplicado'] = isset($aplicadas[motivoRecepcion((int)$o['id_orden_compra'])]) ? 1 : 0;
            }
            unset($o);

            responder($ordenes);

        case 'insertar':
            $d = cuerpo();
            if (empty($d['id_proveedor'])) fallo('Debes seleccionar un proveedor.');

            $estado = !empty($d['estado']) ? $d['estado'] : 'emitida';
            if (!in_array($estado, ESTADOS_ORDEN, true)) fallo('Estado de orden no válido.');
            if ($estado === 'recibida') {
                fallo('Una orden nueva no se puede crear como recibida. Agrega sus detalles y luego usa el botón Recibir.');
            }

            // El subtotal y el total se calculan con el detalle de la orden (empiezan en 0)
            $stmt = $db->prepare("INSERT INTO ordenes_compra (id_solicitud, id_proveedor, fecha_emision, subtotal, descuento, total, estado)
                                  VALUES (:solicitud, :proveedor, :fecha, 0, :descuento, 0, :estado)");
            $stmt->execute([
                ':solicitud' => !empty($d['id_solicitud']) ? (int)$d['id_solicitud'] : null,
                ':proveedor' => (int)$d['id_proveedor'],
                ':fecha'     => !empty($d['fecha_emision']) ? $d['fecha_emision'] : date('Y-m-d'),
                ':descuento' => (float)($d['descuento'] ?? 0),
                ':estado'    => $estado,
            ]);
            exito('Orden de compra registrada correctamente');

        case 'editar':
            $d  = cuerpo();
            $id = (int)($d['id_orden_compra'] ?? 0);
            if ($id <= 0 || empty($d['id_proveedor'])) fallo('Faltan datos obligatorios.');

            $nuevo = !empty($d['estado']) ? $d['estado'] : 'emitida';
            if (!in_array($nuevo, ESTADOS_ORDEN, true)) fallo('Estado de orden no válido.');

            $db->beginTransaction();

            $stmt = $db->prepare("SELECT estado FROM ordenes_compra WHERE id_orden_compra = :id FOR UPDATE");
            $stmt->execute([':id' => $id]);
            $actual = $stmt->fetchColumn();
            if ($actual === false) cancelarConError($db, 'La orden no existe.');

            if (recepcionAplicada($db, $id)) {
                cancelarConError($db, 'Esta orden ya fue recibida y su mercancía ya está en el inventario; no se puede modificar.');
            }
            if ($nuevo === 'recibida' && $actual !== 'recibida') {
                cancelarConError($db, 'Para recibir una orden usa el botón Recibir: así su mercancía se suma al inventario.');
            }

            $descuento = (float)($d['descuento'] ?? 0);
            $stmt = $db->prepare("UPDATE ordenes_compra
                                  SET id_solicitud = :solicitud, id_proveedor = :proveedor, fecha_emision = :fecha,
                                      descuento = :descuento, total = GREATEST(0, subtotal - :descuento2), estado = :estado
                                  WHERE id_orden_compra = :id");
            $stmt->execute([
                ':solicitud'  => !empty($d['id_solicitud']) ? (int)$d['id_solicitud'] : null,
                ':proveedor'  => (int)$d['id_proveedor'],
                ':fecha'      => !empty($d['fecha_emision']) ? $d['fecha_emision'] : date('Y-m-d'),
                ':descuento'  => $descuento,
                ':descuento2' => $descuento,
                ':estado'     => $nuevo,
                ':id'         => $id,
            ]);
            $db->commit();
            exito('Orden de compra actualizada correctamente');

        case 'recibir':
            $d  = cuerpo();
            $id = (int)($d['id_orden_compra'] ?? 0);
            if ($id <= 0) fallo('No se recibió el ID de la orden.');

            $db->beginTransaction();

            $stmt = $db->prepare("SELECT id_solicitud, estado FROM ordenes_compra WHERE id_orden_compra = :id FOR UPDATE");
            $stmt->execute([':id' => $id]);
            $orden = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$orden) cancelarConError($db, 'La orden no existe.');
            if ($orden['estado'] === 'cancelada') cancelarConError($db, 'Una orden cancelada no se puede recibir.');
            if (recepcionAplicada($db, $id)) cancelarConError($db, 'Esta orden ya fue recibida.');

            $usuario = usuarioQueRecibe($db, $orden);
            if ($usuario <= 0) {
                cancelarConError($db, 'No se pudo identificar al usuario que recibe la orden. Inicia sesión de nuevo.');
            }

            [$articulos, $unidades] = aplicarRecepcion($db, $id, $usuario);
            $db->prepare("UPDATE ordenes_compra SET estado = 'recibida' WHERE id_orden_compra = :id")->execute([':id' => $id]);
            $db->commit();

            exito("Orden #$id recibida: se sumaron $unidades unidades de $articulos artículo(s) al inventario.");

        case 'eliminar':
            $d  = cuerpo();
            $id = (int)($d['id_orden_compra'] ?? 0);
            if ($id <= 0) fallo('No se recibió el ID de la orden a eliminar.');

            $db->beginTransaction();

            $stmt = $db->prepare("SELECT estado FROM ordenes_compra WHERE id_orden_compra = :id FOR UPDATE");
            $stmt->execute([':id' => $id]);
            if ($stmt->fetchColumn() === false) cancelarConError($db, 'La orden no existe o ya fue eliminada.');

            if (recepcionAplicada($db, $id)) {
                cancelarConError($db, 'No se puede eliminar una orden recibida: su mercancía ya está en el inventario.');
            }

            // Se eliminan primero sus detalles y luego la orden
            $db->prepare("DELETE FROM detalle_ordenes_compra WHERE id_orden_compra = :id")->execute([':id' => $id]);
            $db->prepare("DELETE FROM ordenes_compra WHERE id_orden_compra = :id")->execute([':id' => $id]);
            $db->commit();
            exito('Orden de compra eliminada correctamente');

        default:
            fallo('Operación no válida');
    }
} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    $codigo  = $e->errorInfo[1] ?? 0;
    $detalle = $e->errorInfo[2] ?? '';

    if ($codigo == 1451) {
        fallo('No se puede eliminar la orden porque tiene comprobantes u otros registros asociados.');
    }
    if ($codigo == 1452) {
        if (strpos($detalle, 'id_solicitud') !== false) {
            fallo('La solicitud de compra indicada no existe. Déjala vacía o crea la solicitud primero.');
        }
        if (strpos($detalle, 'id_proveedor') !== false) {
            fallo('El proveedor seleccionado no existe.');
        }
    }
    errorBD($e);
}
