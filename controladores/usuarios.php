<?php
require_once __DIR__ . '/../modelos/util.php';
iniciarApi();

try {
    $db = Conexion::conectar();

    switch (operacion()) {
        case 'consultar':
            $stmt = $db->query("SELECT u.id_usuario, u.id_rol, u.nombre_usuario, u.email, u.celular, u.estado, u.fecha_creacion,
                                       r.nombre AS rol_nombre
                                FROM usuarios u
                                LEFT JOIN roles r ON u.id_rol = r.id_rol
                                ORDER BY u.id_usuario DESC");
            responder($stmt->fetchAll(PDO::FETCH_ASSOC));

        case 'insertar':
            $d = cuerpo();
            if (trim($d['nombre_usuario'] ?? '') === '' || ($d['clave'] ?? '') === '' || empty($d['id_rol'])) {
                fallo('El usuario, la contraseña y el rol son obligatorios.');
            }
            $stmt = $db->prepare("INSERT INTO usuarios (nombre_usuario, clave, email, celular, id_rol, estado)
                                  VALUES (:usuario, :clave, :email, :celular, :rol, :estado)");
            $stmt->execute([
                ':usuario' => trim($d['nombre_usuario']),
                ':clave'   => password_hash($d['clave'], PASSWORD_DEFAULT),
                ':email'   => trim($d['email'] ?? ''),
                ':celular' => trim($d['celular'] ?? ''),
                ':rol'     => (int)$d['id_rol'],
                ':estado'  => !empty($d['estado']) ? $d['estado'] : 'activo',
            ]);
            exito('Usuario registrado correctamente');

        case 'editar':
            $d = cuerpo();
            if (empty($d['id_usuario']) || trim($d['nombre_usuario'] ?? '') === '' || empty($d['id_rol'])) {
                fallo('Datos incompletos.');
            }
            $campos = [
                ':usuario' => trim($d['nombre_usuario']),
                ':email'   => trim($d['email'] ?? ''),
                ':celular' => trim($d['celular'] ?? ''),
                ':rol'     => (int)$d['id_rol'],
                ':estado'  => !empty($d['estado']) ? $d['estado'] : 'activo',
                ':id'      => (int)$d['id_usuario'],
            ];
            // Solo se cambia la contraseña si se escribió una nueva
            if (!empty($d['clave'])) {
                $campos[':clave'] = password_hash($d['clave'], PASSWORD_DEFAULT);
                $sql = "UPDATE usuarios SET nombre_usuario = :usuario, clave = :clave, email = :email, celular = :celular,
                        id_rol = :rol, estado = :estado WHERE id_usuario = :id";
            } else {
                $sql = "UPDATE usuarios SET nombre_usuario = :usuario, email = :email, celular = :celular,
                        id_rol = :rol, estado = :estado WHERE id_usuario = :id";
            }
            $db->prepare($sql)->execute($campos);
            exito('Usuario actualizado correctamente');

        case 'eliminar':
            $d = cuerpo();
            $id = (int)($d['id_usuario'] ?? $_GET['id'] ?? 0);
            if ($id <= 0) fallo('No se recibió el ID del usuario.');
            $stmt = $db->prepare("DELETE FROM usuarios WHERE id_usuario = :id");
            $stmt->execute([':id' => $id]);
            if ($stmt->rowCount() === 0) fallo('El usuario no existe o ya fue eliminado.');
            exito('Usuario eliminado correctamente');

        default:
            fallo('Opción no válida');
    }
} catch (PDOException $e) {
    errorBD($e, 'este usuario (tiene ventas o solicitudes registradas)');
}
