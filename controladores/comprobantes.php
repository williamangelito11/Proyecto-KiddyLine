<?php
require_once __DIR__ . '/../modelos/util.php';
iniciarApi();

function camposComprobante(array $d): array {
    if (trim($d['tipo'] ?? '') === '' || trim($d['concepto'] ?? '') === '') {
        fallo('El tipo y el concepto son obligatorios.');
    }
    return [
        ':tipo'     => trim($d['tipo']),
        ':concepto' => trim($d['concepto']),
        ':monto'    => (float)($d['monto'] ?? 0),
        ':fecha'    => !empty($d['fecha']) ? $d['fecha'] : date('Y-m-d'),
        ':venta'    => !empty($d['id_venta']) ? (int)$d['id_venta'] : null,
        ':orden'    => !empty($d['id_orden_compra']) ? (int)$d['id_orden_compra'] : null,
    ];
}

try {
    $db = Conexion::conectar();

    switch (operacion()) {
        case 'consultar':
            $stmt = $db->query("SELECT * FROM comprobantes ORDER BY id_comprobante DESC");
            responder($stmt->fetchAll(PDO::FETCH_ASSOC));

        case 'insertar':
            $campos = camposComprobante(cuerpo());
            $stmt = $db->prepare("INSERT INTO comprobantes (tipo, concepto, monto, fecha, id_venta, id_orden_compra)
                                  VALUES (:tipo, :concepto, :monto, :fecha, :venta, :orden)");
            $stmt->execute($campos);
            exito('Comprobante registrado con éxito');

        case 'editar':
            $d = cuerpo();
            if (empty($d['id_comprobante'])) fallo('No se recibió el ID del comprobante.');
            $campos = camposComprobante($d);
            $campos[':id'] = (int)$d['id_comprobante'];
            $stmt = $db->prepare("UPDATE comprobantes SET tipo = :tipo, concepto = :concepto, monto = :monto, fecha = :fecha,
                                  id_venta = :venta, id_orden_compra = :orden WHERE id_comprobante = :id");
            $stmt->execute($campos);
            exito('Comprobante actualizado correctamente');

        case 'eliminar':
            $d = cuerpo();
            if (empty($d['id_comprobante'])) fallo('No se recibió el ID del comprobante.');
            $stmt = $db->prepare("DELETE FROM comprobantes WHERE id_comprobante = :id");
            $stmt->execute([':id' => (int)$d['id_comprobante']]);
            if ($stmt->rowCount() === 0) fallo('El comprobante no existe o ya fue eliminado.');
            exito('Comprobante eliminado correctamente');

        default:
            fallo('Opción no válida');
    }
} catch (PDOException $e) {
    errorBD($e, 'este comprobante');
}
