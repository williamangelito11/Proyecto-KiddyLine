<?php
require_once __DIR__ . '/../modelos/util.php';
iniciarApi();

const IVA_TASA = 0.19;

// Cancela la transacción abierta (si la hay) y responde con el error
function abortar(PDO $db, string $mensaje): void {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    fallo($mensaje);
}

try {
    $db = Conexion::conectar();

    switch (operacion()) {
        case 'consultar':
            $stmt = $db->query("SELECT v.*, c.nombre AS nombre_cliente, u.nombre_usuario AS nombre_usuario
                                FROM ventas v
                                LEFT JOIN clientes c ON v.id_cliente = c.id_cliente
                                LEFT JOIN usuarios u ON v.id_usuario = u.id_usuario
                                ORDER BY v.fecha_venta DESC, v.id_venta DESC");
            responder($stmt->fetchAll(PDO::FETCH_ASSOC));

        case 'detalle':
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $db->prepare("SELECT d.*, a.nombre AS nombre_articulo
                                  FROM detalle_ventas d
                                  LEFT JOIN articulos a ON d.id_articulo = a.id_articulo
                                  WHERE d.id_venta = :id
                                  ORDER BY d.id_detalle_venta ASC");
            $stmt->execute([':id' => $id]);
            responder($stmt->fetchAll(PDO::FETCH_ASSOC));

        case 'insertar':
            $d = cuerpo();
            $items = $d['items'] ?? [];

            // El vendedor es el usuario con sesión iniciada; si el token no llega, se usa el que envía la pantalla
            $sesion   = usuarioAutenticado();
            $vendedor = ($sesion !== null && !empty($sesion['uid'])) ? (int)$sesion['uid'] : (int)($d['id_usuario'] ?? 0);

            if (empty($d['id_cliente'])) {
                fallo('Debes seleccionar el cliente.');
            }
            if ($vendedor <= 0) {
                fallo('No se pudo identificar al vendedor. Inicia sesión de nuevo.');
            }
            if (!is_array($items) || count($items) === 0) {
                fallo('Agrega al menos un artículo a la venta.');
            }

            // Se suma lo pedido por artículo (por si un artículo se repite)
            $pedido = [];
            foreach ($items as $it) {
                $idArt = (int)($it['id_articulo'] ?? 0);
                $cant  = (int)($it['cantidad'] ?? 0);
                if ($idArt <= 0 || $cant <= 0) {
                    fallo('Hay una línea con artículo o cantidad inválidos.');
                }
                $pedido[$idArt] = ($pedido[$idArt] ?? 0) + $cant;
            }

            $db->beginTransaction();

            // Se bloquea cada artículo, se verifica el stock y el precio sale siempre de la base de datos
            $sel = $db->prepare("SELECT nombre, stock_actual, precio_venta FROM articulos WHERE id_articulo = :id FOR UPDATE");
            $lineas   = [];
            $subtotal = 0;
            foreach ($pedido as $idArt => $cant) {
                $sel->execute([':id' => $idArt]);
                $art = $sel->fetch(PDO::FETCH_ASSOC);
                if (!$art) {
                    abortar($db, "El artículo #$idArt no existe.");
                }
                if ((int)$art['stock_actual'] < $cant) {
                    abortar($db, "Stock insuficiente de {$art['nombre']}: disponible {$art['stock_actual']}, solicitado $cant.");
                }
                $precio = (float)$art['precio_venta'];
                if ($precio <= 0) {
                    abortar($db, "El artículo {$art['nombre']} no tiene precio de venta. Asígnalo en Artículos.");
                }
                $lineas[]  = ['id' => $idArt, 'cantidad' => $cant, 'precio' => $precio];
                $subtotal += $cant * $precio;
            }

            $subtotal = round($subtotal, 2);
            $impuesto = round($subtotal * IVA_TASA, 2);
            $total    = round($subtotal + $impuesto, 2);

            $stmt = $db->prepare("INSERT INTO ventas (id_cliente, id_usuario, fecha_venta, subtotal, impuesto, total, estado)
                                  VALUES (:cliente, :usuario, NOW(), :subtotal, :impuesto, :total, 'completada')");
            $stmt->execute([
                ':cliente'  => (int)$d['id_cliente'],
                ':usuario'  => $vendedor,
                ':subtotal' => $subtotal,
                ':impuesto' => $impuesto,
                ':total'    => $total,
            ]);
            $idVenta = (int)$db->lastInsertId();
            $motivo  = "Venta #$idVenta";

            $insDetalle = $db->prepare("INSERT INTO detalle_ventas (id_venta, id_articulo, cantidad, precio_unitario, subtotal)
                                        VALUES (:venta, :articulo, :cantidad, :precio, :subtotal)");
            $updStock   = $db->prepare("UPDATE articulos SET stock_actual = stock_actual - :cantidad WHERE id_articulo = :id");
            $insMov     = $db->prepare("INSERT INTO movimientos_inventario (id_articulo, tipo_movimiento, cantidad, motivo, id_usuario)
                                        VALUES (:articulo, 'salida', :cantidad, :motivo, :usuario)");

            foreach ($lineas as $l) {
                $insDetalle->execute([
                    ':venta' => $idVenta, ':articulo' => $l['id'], ':cantidad' => $l['cantidad'],
                    ':precio' => $l['precio'], ':subtotal' => round($l['cantidad'] * $l['precio'], 2),
                ]);
                $updStock->execute([':cantidad' => $l['cantidad'], ':id' => $l['id']]);
                $insMov->execute([
                    ':articulo' => $l['id'], ':cantidad' => $l['cantidad'],
                    ':motivo' => $motivo, ':usuario' => $vendedor,
                ]);
            }

            $db->commit();
            responder([
                'status'   => 'success',
                'mensaje'  => "Venta #$idVenta registrada correctamente",
                'id_venta' => $idVenta,
                'total'    => $total,
            ]);

        case 'anular':
            $d  = cuerpo();
            $id = (int)($d['id_venta'] ?? 0);
            if ($id <= 0) fallo('No se recibió el ID de la venta.');

            $db->beginTransaction();

            $stmt = $db->prepare("SELECT estado, id_usuario FROM ventas WHERE id_venta = :id FOR UPDATE");
            $stmt->execute([':id' => $id]);
            $venta = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$venta) abortar($db, 'La venta no existe.');
            if ($venta['estado'] === 'anulada') abortar($db, 'La venta ya está anulada.');

            // Solo se devuelve stock si esta venta lo descontó (las ventas anteriores al control de inventario no)
            $motivoVenta = "Venta #$id";
            $stmt = $db->prepare("SELECT COUNT(*) FROM movimientos_inventario WHERE tipo_movimiento = 'salida' AND motivo = :motivo");
            $stmt->execute([':motivo' => $motivoVenta]);
            $descontoStock = (int)$stmt->fetchColumn() > 0;

            if ($descontoStock) {
                $stmt = $db->prepare("SELECT id_articulo, cantidad FROM detalle_ventas WHERE id_venta = :id");
                $stmt->execute([':id' => $id]);
                $lineas = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $updStock = $db->prepare("UPDATE articulos SET stock_actual = stock_actual + :cantidad WHERE id_articulo = :id");
                $insMov   = $db->prepare("INSERT INTO movimientos_inventario (id_articulo, tipo_movimiento, cantidad, motivo, id_usuario)
                                          VALUES (:articulo, 'entrada', :cantidad, :motivo, :usuario)");
                foreach ($lineas as $l) {
                    $updStock->execute([':cantidad' => (int)$l['cantidad'], ':id' => (int)$l['id_articulo']]);
                    $insMov->execute([
                        ':articulo' => (int)$l['id_articulo'], ':cantidad' => (int)$l['cantidad'],
                        ':motivo' => "Anulación de la venta #$id", ':usuario' => (int)$venta['id_usuario'],
                    ]);
                }
            }

            $db->prepare("UPDATE ventas SET estado = 'anulada' WHERE id_venta = :id")->execute([':id' => $id]);
            $db->commit();

            exito($descontoStock
                ? 'Venta anulada y stock devuelto al inventario.'
                : 'Venta anulada. Era anterior al control de inventario, así que el stock no se modificó.');

        case 'eliminar':
            $d  = cuerpo();
            $id = (int)($d['id_venta'] ?? 0);
            if ($id <= 0) fallo('No se recibió el ID de la venta.');

            $db->beginTransaction();

            $stmt = $db->prepare("SELECT estado FROM ventas WHERE id_venta = :id FOR UPDATE");
            $stmt->execute([':id' => $id]);
            $estado = $stmt->fetchColumn();
            if ($estado === false) abortar($db, 'La venta no existe o ya fue eliminada.');
            if ($estado !== 'anulada') abortar($db, 'Solo se pueden eliminar ventas anuladas. Anula la venta primero para devolver el stock.');

            $db->prepare("DELETE FROM detalle_ventas WHERE id_venta = :id")->execute([':id' => $id]);
            $db->prepare("DELETE FROM ventas WHERE id_venta = :id")->execute([':id' => $id]);
            $db->commit();
            exito('Venta eliminada correctamente');

        default:
            fallo('Opción no válida');
    }
} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    errorBD($e, 'esta venta (tiene comprobantes asociados)');
}
