<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../modelos/util.php';
exigirSesion();

$accion = $_GET['accion'] ?? $_GET['op'] ?? $_POST['accion'] ?? $_POST['op'] ?? '';

try {
    $conexion = Conexion::conectar();

    if ($accion === 'consultar') {
        $stmt = $conexion->prepare("SELECT * FROM empleados ORDER BY id_empleado DESC");
        $stmt->execute();
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit();
    }

    if ($accion === 'insertar' || $accion === 'editar') {
        $datos = json_decode(file_get_contents("php://input"), true);

        if (empty($datos['nombres']) || empty(trim($datos['nombres']))) {
            echo json_encode(["status" => "error", "mensaje" => "El nombre del empleado es obligatorio."]);
            exit();
        }

        $campos = [
            ':documento'      => trim($datos['documento'] ?? ''),
            ':nombres'        => trim($datos['nombres']),
            ':apellidos'      => trim($datos['apellidos'] ?? ''),
            ':telefono'       => trim($datos['telefono'] ?? ''),
            ':correo'         => trim($datos['correo'] ?? ''),
            ':cargo'          => trim($datos['cargo'] ?? ''),
            ':estado_laboral' => !empty($datos['estado_laboral']) ? $datos['estado_laboral'] : 'activo',
        ];

        if ($accion === 'insertar') {
            $stmt = $conexion->prepare(
                "INSERT INTO empleados (documento, nombres, apellidos, telefono, correo, cargo, estado_laboral)
                 VALUES (:documento, :nombres, :apellidos, :telefono, :correo, :cargo, :estado_laboral)"
            );
            $stmt->execute($campos);
            echo json_encode(["status" => "success", "mensaje" => "Empleado registrado correctamente"]);
            exit();
        }

        // editar
        if (empty($datos['id_empleado'])) {
            echo json_encode(["status" => "error", "mensaje" => "No se recibió el ID del empleado a editar."]);
            exit();
        }

        $campos[':id_empleado'] = (int)$datos['id_empleado'];
        $stmt = $conexion->prepare(
            "UPDATE empleados
             SET documento = :documento, nombres = :nombres, apellidos = :apellidos,
                 telefono = :telefono, correo = :correo, cargo = :cargo, estado_laboral = :estado_laboral
             WHERE id_empleado = :id_empleado"
        );
        $stmt->execute($campos);
        echo json_encode(["status" => "success", "mensaje" => "Empleado actualizado correctamente"]);
        exit();
    }

    if ($accion === 'eliminar') {
        $datos = json_decode(file_get_contents("php://input"), true);

        if (empty($datos['id_empleado'])) {
            echo json_encode(["status" => "error", "mensaje" => "No se recibió el ID del empleado a eliminar."]);
            exit();
        }

        $stmt = $conexion->prepare("DELETE FROM empleados WHERE id_empleado = :id");
        $stmt->execute([':id' => (int)$datos['id_empleado']]);

        if ($stmt->rowCount() === 0) {
            echo json_encode(["status" => "error", "mensaje" => "El empleado no existe o ya fue eliminado."]);
        } else {
            echo json_encode(["status" => "success", "mensaje" => "Empleado eliminado correctamente"]);
        }
        exit();
    }

    echo json_encode(["status" => "error", "mensaje" => "Acción no válida"]);

} catch (PDOException $e) {
    $codigo = $e->errorInfo[1] ?? 0;
    if ($codigo == 1451) {
        $mensaje = "No se puede eliminar: el empleado tiene registros asociados (por ejemplo, contratos). Cambia su estado laboral a inactivo en lugar de borrarlo.";
    } elseif ($codigo == 1062) {
        $mensaje = "Ya existe un empleado con ese documento o correo.";
    } elseif ($codigo == 1054) {
        $mensaje = "Una columna no existe en la tabla empleados: " . ($e->errorInfo[2] ?? '');
    } else {
        $mensaje = "Error de base de datos: " . $e->getMessage();
    }
    echo json_encode(["status" => "error", "mensaje" => $mensaje]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "mensaje" => "Error de servidor: " . $e->getMessage()]);
}
