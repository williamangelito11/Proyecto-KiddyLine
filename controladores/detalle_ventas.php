<?php
require_once __DIR__ . '/../modelos/util.php';
iniciarApi();

try {
    $db = Conexion::conectar();

    if (operacion() === 'consultar') {
        $sql = "SELECT d.*, a.nombre AS nombre_articulo
                FROM detalle_ventas d
                LEFT JOIN articulos a ON d.id_articulo = a.id_articulo";
        $params = [];
        if (!empty($_GET['id_venta'])) {
            $sql .= " WHERE d.id_venta = :venta";
            $params[':venta'] = (int)$_GET['id_venta'];
        }
        $sql .= " ORDER BY d.id_detalle_venta DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        responder($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    fallo('Opción no válida');
} catch (PDOException $e) {
    errorBD($e);
}
