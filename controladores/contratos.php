<?php
require_once __DIR__ . '/../modelos/util.php';
iniciarApi();

function camposContrato(array $d): array {
    if (empty($d['id_empleado']) || trim($d['tipo_contrato'] ?? '') === '' || empty($d['fecha_inicio'])) {
        fallo('Debes indicar el empleado, el tipo de contrato y la fecha de inicio.');
    }
    return [
        ':empleado' => (int)$d['id_empleado'],
        ':tipo'     => trim($d['tipo_contrato']),
        ':inicio'   => $d['fecha_inicio'],
        ':fin'      => !empty($d['fecha_fin']) ? $d['fecha_fin'] : null,
        ':salario'  => (float)($d['salario'] ?? 0),
        ':pdf'      => !empty($d['ruta_archivo_pdf']) ? trim($d['ruta_archivo_pdf']) : null,
    ];
}

try {
    $db = Conexion::conectar();

    switch (operacion()) {
        case 'consultar':
            $stmt = $db->query("SELECT c.*, TRIM(CONCAT(COALESCE(e.nombres, ''), ' ', COALESCE(e.apellidos, ''))) AS nombre_empleado
                                FROM contratos_documentos c
                                LEFT JOIN empleados e ON c.id_empleado = e.id_empleado
                                ORDER BY c.id_contrato DESC");
            $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($datos as &$fila) {
                if (trim($fila['nombre_empleado'] ?? '') === '') {
                    $fila['nombre_empleado'] = 'Empleado #' . $fila['id_empleado'];
                }
            }
            responder($datos);

        case 'consultar_empleados':
            $stmt = $db->query("SELECT id_empleado, nombres, apellidos FROM empleados ORDER BY nombres ASC");
            $lista = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $e) {
                $lista[] = [
                    'id_empleado' => $e['id_empleado'],
                    'nombre'      => trim(($e['nombres'] ?? '') . ' ' . ($e['apellidos'] ?? '')) ?: 'Empleado ' . $e['id_empleado'],
                ];
            }
            responder($lista);

        case 'insertar':
            $campos = camposContrato(cuerpo());
            $stmt = $db->prepare("INSERT INTO contratos_documentos (id_empleado, tipo_contrato, fecha_inicio, fecha_fin, salario, ruta_archivo_pdf)
                                  VALUES (:empleado, :tipo, :inicio, :fin, :salario, :pdf)");
            $stmt->execute($campos);
            exito('Contrato registrado con éxito');

        case 'editar':
            $d = cuerpo();
            if (empty($d['id_contrato'])) fallo('No se recibió el ID del contrato.');
            $campos = camposContrato($d);
            $campos[':id'] = (int)$d['id_contrato'];
            $stmt = $db->prepare("UPDATE contratos_documentos SET id_empleado = :empleado, tipo_contrato = :tipo, fecha_inicio = :inicio,
                                  fecha_fin = :fin, salario = :salario, ruta_archivo_pdf = :pdf WHERE id_contrato = :id");
            $stmt->execute($campos);
            exito('Contrato actualizado correctamente');

        case 'eliminar':
            $d = cuerpo();
            if (empty($d['id_contrato'])) fallo('No se recibió el ID del contrato.');
            $stmt = $db->prepare("DELETE FROM contratos_documentos WHERE id_contrato = :id");
            $stmt->execute([':id' => (int)$d['id_contrato']]);
            if ($stmt->rowCount() === 0) fallo('El contrato no existe o ya fue eliminado.');
            exito('Contrato eliminado correctamente');

        default:
            fallo('Opción no válida');
    }
} catch (PDOException $e) {
    errorBD($e, 'este contrato');
}
