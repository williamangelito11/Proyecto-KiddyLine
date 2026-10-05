<?php
require_once __DIR__ . '/../modelos/util.php';
iniciarApi();

function camposProveedor(array $d): array {
    if (trim($d['nit_cedula'] ?? '') === '' || trim($d['razon_social'] ?? '') === '') {
        fallo('Faltan campos obligatorios (NIT/Cédula y Razón Social).');
    }
    return [
        ':nit'      => trim($d['nit_cedula']),
        ':razon'    => trim($d['razon_social']),
        ':contacto' => trim($d['contacto'] ?? ''),
        ':telefono' => trim($d['telefono'] ?? ''),
        ':email'    => trim($d['email'] ?? ''),
    ];
}

try {
    $db = Conexion::conectar();

    switch (operacion()) {
        case 'consultar':
            $stmt = $db->query("SELECT * FROM proveedores ORDER BY id_proveedor DESC");
            responder($stmt->fetchAll(PDO::FETCH_ASSOC));

        case 'insertar':
            $campos = camposProveedor(cuerpo());
            $stmt = $db->prepare("INSERT INTO proveedores (nit_cedula, razon_social, contacto, telefono, email)
                                  VALUES (:nit, :razon, :contacto, :telefono, :email)");
            $stmt->execute($campos);
            exito('Proveedor registrado con éxito');

        case 'editar':
            $d = cuerpo();
            if (empty($d['id_proveedor'])) fallo('No se recibió el ID del proveedor.');
            $campos = camposProveedor($d);
            $campos[':id'] = (int)$d['id_proveedor'];
            $stmt = $db->prepare("UPDATE proveedores SET nit_cedula = :nit, razon_social = :razon, contacto = :contacto,
                                  telefono = :telefono, email = :email WHERE id_proveedor = :id");
            $stmt->execute($campos);
            exito('Proveedor actualizado correctamente');

        case 'eliminar':
            $d = cuerpo();
            if (empty($d['id_proveedor'])) fallo('No se recibió el ID del proveedor.');
            $stmt = $db->prepare("DELETE FROM proveedores WHERE id_proveedor = :id");
            $stmt->execute([':id' => (int)$d['id_proveedor']]);
            if ($stmt->rowCount() === 0) fallo('El proveedor no existe o ya fue eliminado.');
            exito('Proveedor eliminado correctamente');

        default:
            fallo('Opción no válida');
    }
} catch (PDOException $e) {
    errorBD($e, 'este proveedor (tiene órdenes de compra)');
}
