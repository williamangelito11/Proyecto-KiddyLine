<?php
// Funciones comunes para sumar al inventario la mercancía de una orden de compra recibida
require_once __DIR__ . '/util.php';

// Texto fijo del movimiento: sirve para saber si una orden ya sumó su stock (solo letras sin tilde a propósito)
function motivoRecepcion(int $idOrden): string {
    return "Orden de compra #$idOrden recibida";
}

function recepcionAplicada(PDO $db, int $idOrden): bool {
    $stmt = $db->prepare("SELECT COUNT(*) FROM movimientos_inventario WHERE tipo_movimiento = 'entrada' AND motivo = :motivo");
    $stmt->execute([':motivo' => motivoRecepcion($idOrden)]);
    return (int)$stmt->fetchColumn() > 0;
}

// Cancela la transacción abierta (si la hay) y responde con el error
function cancelarConError(PDO $db, string $mensaje): void {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    fallo($mensaje);
}

// Suma al stock cada artículo de la orden y deja un movimiento de entrada por artículo.
// Debe llamarse dentro de una transacción. Devuelve [artículos, unidades].
function aplicarRecepcion(PDO $db, int $idOrden, int $usuario): array {
    $stmt = $db->prepare("SELECT id_articulo, SUM(cantidad) AS cantidad
                          FROM detalle_ordenes_compra
                          WHERE id_orden_compra = :id
                          GROUP BY id_articulo");
    $stmt->execute([':id' => $idOrden]);
    $lineas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($lineas) === 0) {
        cancelarConError($db, 'La orden no tiene detalles. Agrega al menos un artículo antes de recibirla.');
    }

    $actualizar = $db->prepare("UPDATE articulos SET stock_actual = stock_actual + :cantidad WHERE id_articulo = :id");
    $movimiento = $db->prepare("INSERT INTO movimientos_inventario (id_articulo, tipo_movimiento, cantidad, motivo, id_usuario)
                                VALUES (:articulo, 'entrada', :cantidad, :motivo, :usuario)");

    $unidades = 0;
    foreach ($lineas as $l) {
        $cantidad = (int)$l['cantidad'];
        $unidades += $cantidad;
        $actualizar->execute([':cantidad' => $cantidad, ':id' => (int)$l['id_articulo']]);
        $movimiento->execute([
            ':articulo' => (int)$l['id_articulo'],
            ':cantidad' => $cantidad,
            ':motivo'   => motivoRecepcion($idOrden),
            ':usuario'  => $usuario,
        ]);
    }

    return [count($lineas), $unidades];
}
