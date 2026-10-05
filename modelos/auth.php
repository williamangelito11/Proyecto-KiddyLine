<?php
// Autenticación por token firmado (HMAC-SHA256). No necesita tablas ni columnas nuevas.

// Clave con la que se firman los tokens. Cámbiala por una propia y mantenla en secreto:
// si la cambias, todas las sesiones abiertas se cierran.
const AUTH_CLAVE_SECRETA = '426ea3ee08198fcd1ed6f9e831a5c4303c6393853de39bf876ba559e14b3cad2';

// Duración de una sesión, en segundos (8 horas)
const AUTH_DURACION = 28800;

// false: la API sigue abierta (el login solo protege las pantallas).
// true:  todos los controladores exigen un token válido, excepto login.php.
// Actívalo cuando compruebes que el login funciona.
const AUTH_OBLIGATORIA = false;

function base64UrlCodificar(string $datos): string {
    return rtrim(strtr(base64_encode($datos), '+/', '-_'), '=');
}

function base64UrlDecodificar(string $texto): string {
    $resultado = base64_decode(strtr($texto, '-_', '+/'), true);
    return $resultado === false ? '' : $resultado;
}

function crearToken(array $datos): string {
    $cuerpo = base64UrlCodificar(json_encode($datos));
    $firma  = base64UrlCodificar(hash_hmac('sha256', $cuerpo, AUTH_CLAVE_SECRETA, true));
    return $cuerpo . '.' . $firma;
}

function validarToken(string $token): ?array {
    $partes = explode('.', $token);
    if (count($partes) !== 2) {
        return null;
    }
    [$cuerpo, $firma] = $partes;

    $esperada = base64UrlCodificar(hash_hmac('sha256', $cuerpo, AUTH_CLAVE_SECRETA, true));
    if (!hash_equals($esperada, $firma)) {
        return null;
    }

    $datos = json_decode(base64UrlDecodificar($cuerpo), true);
    if (!is_array($datos) || ($datos['exp'] ?? 0) < time()) {
        return null;
    }
    return $datos;
}

function tokenDeLaPeticion(): string {
    $cabecera = '';

    if (function_exists('getallheaders')) {
        foreach (getallheaders() as $nombre => $valor) {
            if (strtolower($nombre) === 'authorization') {
                $cabecera = $valor;
            }
        }
    }
    if ($cabecera === '') {
        $cabecera = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    }

    if (stripos($cabecera, 'Bearer ') === 0) {
        return trim(substr($cabecera, 7));
    }
    return '';
}

function usuarioAutenticado(): ?array {
    $token = tokenDeLaPeticion();
    return $token === '' ? null : validarToken($token);
}

// Corta la petición con 401 si no hay una sesión válida (solo si AUTH_OBLIGATORIA es true)
function exigirSesion(): void {
    if (!AUTH_OBLIGATORIA) {
        return;
    }
    if (usuarioAutenticado() === null) {
        http_response_code(401);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['status' => 'error', 'mensaje' => 'Sesión no válida o expirada. Inicia sesión de nuevo.']);
        exit();
    }
}
