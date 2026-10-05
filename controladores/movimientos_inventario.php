<?php
require_once __DIR__ . '/../modelos/util.php';
iniciarApi();

try {
    $db = Conexion::conectar();

    switch (operacion()) {
        case 'consultar':
            $stmt = $db->query("SELECT m.*, a.nombre AS nombre_articulo, u.nombre_usuario
                                FROM movimientos_inventario m
                                LEFT JOIN articulos a ON m.id_articulo = a.id_articulo
                                LEFT JOIN usuarios u ON m.id_usuario = u.id_usuario
                                ORDER BY m.fecha DESC, m.id_movimiento DESC");
            responder($stmt->fetchAll(PDO::FETCH_ASSOC));

        // Ajuste manual de inventario: entrada o salida que también modifica el stock del artículo
        case 'insertar':
            $d = cuerpo();
            $articulo = (int)($d['id_articulo'] ?? 0);
            $usuario  = (int)($d['id_usuario'] ?? 0);
            $cantidad = (int)($d['cantidad'] ?? 0);
            $tipo     = $d['tipo_movimiento'] ?? '';

            if ($articulo <= 0 || $usuario <= 0) fallo('Debes seleccionar el artículo y el usuario.');
            if ($cantidad <= 0) fallo('La cantidad debe ser mayor que cero.');
            if (!in_array($tipo, ['entrada', 'salida'], true)) fallo('El tipo de movimiento debe ser entrada o salida.');

            $db->beginTransaction();

            $stmt = $db->prepare("SELECT nombre, stock_actual FROM articulos WHERE id_articulo = :id FOR UPDATE");
            $stmt->execute([':id' => $articulo]);
            $art = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$art) {
                $db->rollBack();
                fallo('El artículo no existe.');
            }
            if ($tipo === 'salida' && (int)$art['stock_actual'] < $cantidad) {
                $db->rollBack();
                fallo("Stock insuficiente de {$art['nombre']}: disponible {$art['stock_actual']}.");
            }

            $signo = $tipo === 'entrada' ? '+' : '-';
            $db->prepare("UPDATE articulos SET stock_actual = stock_actual $signo :cantidad WHERE id_articulo = :id")
               ->execute([':cantidad' => $cantidad, ':id' => $articulo]);

            $motivo = trim($d['motivo'] ?? '');
            $db->prepare("INSERT INTO movimientos_inventario (id_articulo, tipo_movimiento, cantidad, motivo, id_usuario)
                          VALUES (:articulo, :tipo, :cantidad, :motivo, :usuario)")
               ->execute([
                   ':articulo' => $articulo, ':tipo' => $tipo, ':cantidad' => $cantidad,
                   ':motivo' => $motivo !== '' ? $motivo : 'Ajuste manual', ':usuario' => $usuario,
               ]);

            $db->commit();
            exito('Movimiento registrado y stock actualizado.');

        default:
            fallo('Opción no válida');
    }
} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    errorBD($e);
}
