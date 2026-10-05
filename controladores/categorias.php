<?php
require_once __DIR__ . '/../modelos/util.php';
iniciarApi();

try {
    $db = Conexion::conectar();

    switch (operacion()) {
        case 'consultar':
            $stmt = $db->query("SELECT * FROM categorias ORDER BY id_categoria ASC");
            responder($stmt->fetchAll(PDO::FETCH_ASSOC));

        case 'insertar':
            $d = cuerpo();
            if (trim($d['nombre'] ?? '') === '') fallo('El nombre de la categoría es obligatorio.');
            $stmt = $db->prepare("INSERT INTO categorias (nombre, tipo) VALUES (:nombre, :tipo)");
            $stmt->execute([':nombre' => trim($d['nombre']), ':tipo' => !empty($d['tipo']) ? $d['tipo'] : 'producto_terminado']);
            exito('Categoría guardada exitosamente');

        case 'editar':
            $d = cuerpo();
            if (empty($d['id_categoria']) || trim($d['nombre'] ?? '') === '') fallo('Datos incompletos.');
            $stmt = $db->prepare("UPDATE categorias SET nombre = :nombre, tipo = :tipo WHERE id_categoria = :id");
            $stmt->execute([':nombre' => trim($d['nombre']), ':tipo' => !empty($d['tipo']) ? $d['tipo'] : 'producto_terminado', ':id' => (int)$d['id_categoria']]);
            exito('Categoría actualizada correctamente');

        case 'eliminar':
            $d = cuerpo();
            if (empty($d['id_categoria'])) fallo('No se recibió el ID de la categoría.');
            $stmt = $db->prepare("DELETE FROM categorias WHERE id_categoria = :id");
            $stmt->execute([':id' => (int)$d['id_categoria']]);
            if ($stmt->rowCount() === 0) fallo('La categoría no existe o ya fue eliminada.');
            exito('Categoría eliminada correctamente');

        default:
            fallo('Opción no válida');
    }
} catch (PDOException $e) {
    errorBD($e, 'esta categoría (hay artículos que la usan)');
}
