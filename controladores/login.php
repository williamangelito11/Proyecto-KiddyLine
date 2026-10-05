<?php
require_once __DIR__ . '/../modelos/util.php';
iniciarApi(true);   // público: aquí todavía no hay sesión

try {
    $db = Conexion::conectar();

    switch (operacion()) {
        case 'login':
            $d       = cuerpo();
            $usuario = trim($d['nombre_usuario'] ?? '');
            $clave   = (string)($d['clave'] ?? '');

            if ($usuario === '' || $clave === '') {
                fallo('Ingresa tu usuario y tu contraseña.');
            }

            // Se puede entrar con el nombre de usuario o con el correo
            $stmt = $db->prepare(
                "SELECT u.id_usuario, u.id_rol, u.nombre_usuario, u.clave, u.email, u.estado, r.nombre AS rol_nombre
                 FROM usuarios u
                 LEFT JOIN roles r ON u.id_rol = r.id_rol
                 WHERE u.nombre_usuario = :u1 OR u.email = :u2
                 ORDER BY (u.nombre_usuario = :u3) DESC
                 LIMIT 1"
            );
            $stmt->execute([':u1' => $usuario, ':u2' => $usuario, ':u3' => $usuario]);
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);

            $guardada = $fila ? (string)$fila['clave'] : '';
            $esHash   = preg_match('/^\$(2y|argon2id?)\$/', $guardada) === 1;

            if ($fila) {
                // Las claves antiguas están en texto plano; las nuevas, cifradas
                $correcta = $esHash ? password_verify($clave, $guardada) : hash_equals($guardada, $clave);
            } else {
                $correcta = false;
            }

            if (!$correcta) {
                usleep(500000);   // frena los intentos de adivinar la contraseña
                fallo('Usuario o contraseña incorrectos.');
            }

            if (($fila['estado'] ?? '') !== 'activo') {
                fallo('Tu usuario está inactivo. Contacta al administrador.');
            }

            // Si la clave estaba en texto plano, se cifra ahora. Se comprueba que quedó bien guardada
            // (si la columna fuera muy corta, se restaura la original para no dejar al usuario sin acceso).
            if (!$esHash || password_needs_rehash($guardada, PASSWORD_DEFAULT)) {
                $nueva = password_hash($clave, PASSWORD_DEFAULT);
                $db->prepare("UPDATE usuarios SET clave = :c WHERE id_usuario = :id")
                   ->execute([':c' => $nueva, ':id' => (int)$fila['id_usuario']]);

                $stmt = $db->prepare("SELECT clave FROM usuarios WHERE id_usuario = :id");
                $stmt->execute([':id' => (int)$fila['id_usuario']]);
                if (!password_verify($clave, (string)$stmt->fetchColumn())) {
                    $db->prepare("UPDATE usuarios SET clave = :c WHERE id_usuario = :id")
                       ->execute([':c' => $guardada, ':id' => (int)$fila['id_usuario']]);
                }
            }

            $expira = time() + AUTH_DURACION;
            $token  = crearToken([
                'uid' => (int)$fila['id_usuario'],
                'usr' => $fila['nombre_usuario'],
                'rol' => $fila['rol_nombre'],
                'exp' => $expira,
            ]);

            responder([
                'status'  => 'success',
                'mensaje' => 'Bienvenido, ' . $fila['nombre_usuario'],
                'token'   => $token,
                'expira'  => $expira,
                'usuario' => [
                    'id_usuario'     => (int)$fila['id_usuario'],
                    'nombre_usuario' => $fila['nombre_usuario'],
                    'email'          => $fila['email'],
                    'id_rol'         => $fila['id_rol'] !== null ? (int)$fila['id_rol'] : null,
                    'rol_nombre'     => $fila['rol_nombre'],
                ],
            ]);

        // Comprueba si el token enviado sigue siendo válido
        case 'sesion':
            $sesion = usuarioAutenticado();
            if ($sesion === null) {
                http_response_code(401);
                fallo('Sesión no válida o expirada.');
            }
            responder(['status' => 'success', 'usuario' => $sesion]);

        default:
            fallo('Opción no válida');
    }
} catch (PDOException $e) {
    errorBD($e);
}
