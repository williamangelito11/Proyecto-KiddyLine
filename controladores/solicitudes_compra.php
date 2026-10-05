<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../modelos/util.php';
exigirSesion();

$op = $_GET['op'] ?? 'consultar';

try {
    $db = Conexion::conectar();

    switch ($op) {
        case 'consultar':
            $sql = "SELECT s.*, u.nombre_usuario
                    FROM solicitudes_compra s
                    LEFT JOIN usuarios u ON s.id_usuario = u.id_usuario
                    ORDER BY s.id_solicitud DESC";
            $stmt = $db->prepare($sql);
            $stmt->execute();
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'insertar':
            $d = json_decode(file_get_contents('php://input'), true);

            if (!$d || empty($d['id_usuario'])) {
                echo json_encode(['status' => 'error', 'mensaje' => 'Debes seleccionar el usuario solicitante.']);
                break;
            }

            $stmt = $db->prepare(
                "INSERT INTO solicitudes_compra (id_usuario, fecha_solicitud, fecha_deseada, estado)
                 VALUES (:usuario, :fecha_solicitud, :fecha_deseada, :estado)"
            );
            $stmt->execute([
                ':usuario'         => (int)$d['id_usuario'],
                ':fecha_solicitud' => !empty($d['fecha_solicitud']) ? $d['fecha_solicitud'] : date('Y-m-d'),
                ':fecha_deseada'   => !empty($d['fecha_deseada']) ? $d['fecha_deseada'] : null,
                ':estado'          => !empty($d['estado']) ? $d['estado'] : 'pendiente',
            ]);

            echo json_encode(['status' => 'success', 'mensaje' => 'Solicitud de compra registrada correctamente']);
            break;

        case 'editar':
            $d = json_decode(file_get_contents('php://input'), true);

            if (!$d || empty($d['id_solicitud']) || empty($d['id_usuario'])) {
                echo json_encode(['status' => 'error', 'mensaje' => 'Faltan datos obligatorios.']);
                break;
            }

            $stmt = $db->prepare(
                "UPDATE solicitudes_compra
                 SET id_usuario = :usuario,
                     fecha_solicitud = :fecha_solicitud,
                     fecha_deseada = :fecha_deseada,
                     estado = :estado
                 WHERE id_solicitud = :id"
            );
            $stmt->execute([
                ':usuario'         => (int)$d['id_usuario'],
                ':fecha_solicitud' => !empty($d['fecha_solicitud']) ? $d['fecha_solicitud'] : date('Y-m-d'),
                ':fecha_deseada'   => !empty($d['fecha_deseada']) ? $d['fecha_deseada'] : null,
                ':estado'          => !empty($d['estado']) ? $d['estado'] : 'pendiente',
                ':id'              => (int)$d['id_solicitud'],
            ]);

            echo json_encode(['status' => 'success', 'mensaje' => 'Solicitud de compra actualizada correctamente']);
            break;

        case 'eliminar':
            $d = json_decode(file_get_contents('php://input'), true);

            if (!$d || empty($d['id_solicitud'])) {
                echo json_encode(['status' => 'error', 'mensaje' => 'No se recibió el ID de la solicitud a eliminar.']);
                break;
            }

            $stmt = $db->prepare("DELETE FROM solicitudes_compra WHERE id_solicitud = :id");
            $stmt->execute([':id' => (int)$d['id_solicitud']]);

            if ($stmt->rowCount() === 0) {
                echo json_encode(['status' => 'error', 'mensaje' => 'La solicitud no existe o ya fue eliminada.']);
            } else {
                echo json_encode(['status' => 'success', 'mensaje' => 'Solicitud de compra eliminada correctamente']);
            }
            break;

        default:
            echo json_encode(['status' => 'error', 'mensaje' => 'Operación no válida']);
            break;
    }
} catch (PDOException $e) {
    $codigo = $e->errorInfo[1] ?? 0;
    if ($codigo == 1451) {
        $mensaje = 'No se puede eliminar la solicitud porque tiene órdenes de compra asociadas.';
    } elseif ($codigo == 1452) {
        $mensaje = 'El usuario seleccionado no existe.';
    } else {
        $mensaje = 'Error de base de datos: ' . $e->getMessage();
    }
    echo json_encode(['status' => 'error', 'mensaje' => $mensaje]);
}
