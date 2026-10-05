<?php
require_once __DIR__ . '/../modelos/util.php';
iniciarApi();

function camposCliente(array $d): array {
    if (trim($d['documento'] ?? '') === '' || trim($d['nombre'] ?? '') === '') {
        fallo('El documento y el nombre son obligatorios.');
    }
    return [
        ':documento' => trim($d['documento']),
        ':nombre'    => trim($d['nombre']),
        ':telefono'  => trim($d['telefono'] ?? ''),
        ':email'     => trim($d['email'] ?? ''),
        ':direccion' => trim($d['direccion'] ?? ''),
    ];
}

try {
    $db = Conexion::conectar();

    switch (operacion()) {
        case 'consultar':
            $stmt = $db->query("SELECT * FROM clientes ORDER BY id_cliente DESC");
            responder($stmt->fetchAll(PDO::FETCH_ASSOC));

        // Busca un cliente por su documento (para autocompletar el nombre en la pantalla de ventas)
        case 'buscar':
            $documento = trim($_GET['documento'] ?? '');
            if ($documento === '') fallo('Escribe el documento del cliente.');
            $stmt = $db->prepare("SELECT * FROM clientes WHERE documento = :documento LIMIT 1");
            $stmt->execute([':documento' => $documento]);
            $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
            responder(['status' => 'success', 'encontrado' => $cliente !== false, 'cliente' => $cliente ?: null]);

        case 'insertar':
            $campos = camposCliente(cuerpo());
            $stmt = $db->prepare("INSERT INTO clientes (documento, nombre, telefono, email, direccion)
                                  VALUES (:documento, :nombre, :telefono, :email, :direccion)");
            $stmt->execute($campos);
            exito('Cliente guardado exitosamente');

        case 'editar':
            $d = cuerpo();
            if (empty($d['id_cliente'])) fallo('No se recibió el ID del cliente.');
            $campos = camposCliente($d);
            $campos[':id'] = (int)$d['id_cliente'];
            $stmt = $db->prepare("UPDATE clientes SET documento = :documento, nombre = :nombre, telefono = :telefono,
                                  email = :email, direccion = :direccion WHERE id_cliente = :id");
            $stmt->execute($campos);
            exito('Cliente actualizado correctamente');

        case 'eliminar':
            $d = cuerpo();
            if (empty($d['id_cliente'])) fallo('No se recibió el ID del cliente.');
            $stmt = $db->prepare("DELETE FROM clientes WHERE id_cliente = :id");
            $stmt->execute([':id' => (int)$d['id_cliente']]);
            if ($stmt->rowCount() === 0) fallo('El cliente no existe o ya fue eliminado.');
            exito('Cliente eliminado correctamente');

        default:
            fallo('Opción no válida');
    }
} catch (PDOException $e) {
    if (($e->errorInfo[1] ?? 0) == 1062) {
        fallo('Ya existe un cliente con ese documento.');
    }
    errorBD($e, 'este cliente (tiene ventas registradas)');
}
