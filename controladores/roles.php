<?php
require_once __DIR__ . '/../modelos/util.php';
iniciarApi();

try {
    $db = Conexion::conectar();

    switch (operacion()) {
        case 'consultar':
            $stmt = $db->query("SELECT * FROM roles ORDER BY id_rol DESC");
            responder($stmt->fetchAll(PDO::FETCH_ASSOC));

        case 'insertar':
            $d = cuerpo();
            if (trim($d['nombre'] ?? '') === '') fallo('El nombre del rol es obligatorio.');
            $stmt = $db->prepare("INSERT INTO roles (nombre, descripcion) VALUES (:nombre, :descripcion)");
            $stmt->execute([':nombre' => trim($d['nombre']), ':descripcion' => trim($d['descripcion'] ?? '')]);
            exito('Rol registrado correctamente');

        case 'editar':
            $d = cuerpo();
            if (empty($d['id_rol']) || trim($d['nombre'] ?? '') === '') fallo('Datos incompletos.');
            $stmt = $db->prepare("UPDATE roles SET nombre = :nombre, descripcion = :descripcion WHERE id_rol = :id");
            $stmt->execute([':nombre' => trim($d['nombre']), ':descripcion' => trim($d['descripcion'] ?? ''), ':id' => (int)$d['id_rol']]);
            exito('Rol actualizado correctamente');

        case 'eliminar':
            $d = cuerpo();
            if (empty($d['id_rol'])) fallo('No se recibió el ID del rol.');
            $stmt = $db->prepare("DELETE FROM roles WHERE id_rol = :id");
            $stmt->execute([':id' => (int)$d['id_rol']]);
            if ($stmt->rowCount() === 0) fallo('El rol no existe o ya fue eliminado.');
            exito('Rol eliminado correctamente');

        default:
            fallo('Acción no válida');
    }
} catch (PDOException $e) {
    errorBD($e, 'este rol (hay usuarios que lo usan)');
}
