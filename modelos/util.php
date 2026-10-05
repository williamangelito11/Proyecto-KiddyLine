<?php
// Funciones comunes de los controladores (cabeceras, cuerpo JSON, respuestas y errores de BD)
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/auth.php';

function iniciarApi(bool $publico = false): void {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Content-Type: application/json; charset=UTF-8");

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code(200);
        exit();
    }

    if (!$publico) {
        exigirSesion();
    }
}

function operacion(): string {
    return $_GET['op'] ?? $_GET['accion'] ?? $_POST['op'] ?? $_POST['accion'] ?? 'consultar';
}

function cuerpo(): array {
    $datos = json_decode(file_get_contents('php://input'), true);
    return is_array($datos) ? $datos : [];
}

function responder($datos): void {
    echo json_encode($datos);
    exit();
}

function exito(string $mensaje): void {
    responder(['status' => 'success', 'mensaje' => $mensaje]);
}

function fallo(string $mensaje): void {
    responder(['status' => 'error', 'mensaje' => $mensaje]);
}

function valorONulo($valor) {
    return ($valor === null || $valor === '') ? null : $valor;
}

function errorBD(PDOException $e, string $recurso = 'este registro'): void {
    $codigo  = $e->errorInfo[1] ?? 0;
    $detalle = $e->errorInfo[2] ?? $e->getMessage();

    if ($codigo == 1451) {
        fallo("No se puede eliminar $recurso porque tiene registros asociados.");
    }
    if ($codigo == 1062) {
        fallo("Ya existe otro registro con ese dato único (código, documento o correo repetido).");
    }
    if ($codigo == 1452) {
        fallo("Una de las referencias indicadas no existe: " . $detalle);
    }
    if ($codigo == 1048) {
        fallo("Falta un dato obligatorio: " . $detalle);
    }
    fallo("Error de base de datos: " . $detalle);
}
