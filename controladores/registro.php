<?php
require_once __DIR__ . '/../modelos/util.php';
iniciarApi(true);   // público: quien se registra todavía no tiene sesión

// Nombre del rol que reciben las cuentas nuevas. Tiene que existir en la tabla roles.
const ROL_REGISTRO = 'Empleado';

try {
    $db = Conexion::conectar();

    switch (operacion()) {
        // Un empleado pide su cuenta. Queda INACTIVA hasta que el administrador la active en el módulo Usuarios.
        case 'registrar':
            $d         = cuerpo();
            $documento = trim($d['documento'] ?? '');
            $correo    = strtolower(trim($d['correo'] ?? ''));
            $usuario   = trim($d['nombre_usuario'] ?? '');
            $celular   = trim($d['celular'] ?? '');
            $clave     = (string)($d['clave'] ?? '');

            if ($documento === '' || $correo === '' || $usuario === '' || $clave === '') {
                fallo('Completa el documento, el correo, el usuario y la contraseña.');
            }
            if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                fallo('El correo no es válido.');
            }
            // Sin "@" para que un usuario nunca se confunda con el correo de otra persona al iniciar sesión
            if (!preg_match('/^[A-Za-z0-9._-]{3,30}$/', $usuario)) {
                fallo('El usuario debe tener de 3 a 30 caracteres: letras, números, punto, guion o guion bajo.');
            }
            if (strlen($clave) < 8) {
                fallo('La contraseña debe tener al menos 8 caracteres.');
            }
            if (strlen($clave) > 72) {
                fallo('La contraseña no puede tener más de 72 caracteres.');
            }

            // 1) Debe ser un empleado activo: el documento Y el correo tienen que coincidir con los registrados
            $stmt = $db->prepare("SELECT id_empleado FROM empleados
                                  WHERE documento = :doc AND LOWER(correo) = :correo AND estado_laboral = 'activo'
                                  LIMIT 1");
            $stmt->execute([':doc' => $documento, ':correo' => $correo]);
            if (!$stmt->fetchColumn()) {
                usleep(500000);   // frena los intentos de adivinar documentos
                fallo('No encontramos un empleado activo con ese documento y ese correo. '
                    . 'Verifica tus datos o pide al administrador que te registre como empleado.');
            }

            // 2) No debe existir ya una cuenta con ese correo ni con ese usuario
            $stmt = $db->prepare("SELECT 1 FROM usuarios WHERE LOWER(email) = :correo LIMIT 1");
            $stmt->execute([':correo' => $correo]);
            if ($stmt->fetchColumn()) {
                fallo('Ya existe una cuenta con ese correo. Si olvidaste la contraseña, pide al administrador que la restablezca.');
            }
            $stmt = $db->prepare("SELECT 1 FROM usuarios WHERE nombre_usuario = :u LIMIT 1");
            $stmt->execute([':u' => $usuario]);
            if ($stmt->fetchColumn()) {
                fallo('Ese nombre de usuario ya está en uso. Elige otro.');
            }

            // 3) Rol que se asigna a las cuentas nuevas
            $stmt = $db->prepare("SELECT id_rol FROM roles WHERE LOWER(nombre) = LOWER(:n) LIMIT 1");
            $stmt->execute([':n' => ROL_REGISTRO]);
            $idRol = $stmt->fetchColumn();
            if ($idRol === false) {
                fallo('El registro todavía no está disponible: falta el rol "' . ROL_REGISTRO . '". Avisa al administrador.');
            }

            $stmt = $db->prepare("INSERT INTO usuarios (nombre_usuario, clave, email, celular, id_rol, estado)
                                  VALUES (:usuario, :clave, :email, :celular, :rol, 'inactivo')");
            $stmt->execute([
                ':usuario' => $usuario,
                ':clave'   => password_hash($clave, PASSWORD_DEFAULT),
                ':email'   => $correo,
                ':celular' => $celular,
                ':rol'     => (int)$idRol,
            ]);

            exito('Solicitud enviada. Tu cuenta quedó pendiente de aprobación: cuando el administrador la active podrás iniciar sesión.');

        default:
            fallo('Opción no válida');
    }
} catch (PDOException $e) {
    errorBD($e, 'esta cuenta');
}
