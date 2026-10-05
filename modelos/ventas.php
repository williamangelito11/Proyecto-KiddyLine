<?php
   class Ventas{

   private $conexion;

public function __construct($conexion) {
    $this->conexion = $conexion;
}

//metodos

public function consultar(){
    $sql = "SELECT v.*, 
                   c.nombre AS nombre_cliente,
                   u.nombre_usuario AS nombre_usuario
     FROM ventas v 
     INNER JOIN clientes c ON v.id_cliente = c.id_cliente
     INNER JOIN usuarios u ON v.id_usuario = u.id_usuario
     ORDER BY v.fecha_venta DESC";
    $res = mysqli_query($this->conexion, $sql) or die('No se pudo realizar la consulta de ventas');
    $vec = [];

    while ($row = mysqli_fetch_assoc($res)){
        $vec[] = $row;
        
    }
    return $vec;
}



public function insertar($params){
    $sql = "INSERT INTO ventas(id_cliente, id_usuario, fecha_venta, subtotal, impuesto, total, estado) 
    VALUES('$params->id_cliente', '$params->id_usuario', '$params->fecha_venta', $params->subtotal,
     $params->impuesto, $params->total, '$params->estado')";

    mysqli_query($this->conexion, $sql) or die('No inserto el registro');

    $vec = [];
    $vec['resultado'] = "OK";
    $vec['mensaje'] = "Se inserto el registro";
    
    return $vec;
    }

public function editar($id, $params){
        $sql = "UPDATE ventas SET id_cliente = '$params->id_cliente', 
         id_usuario = '$params->id_usuario', fecha_venta ='$params->fecha_venta',
         subtotal = $params->subtotal, impuesto = $params->impuesto,
          total = $params->total, estado = '$params->estado'
          WHERE id_venta = $id";
          
        mysqli_query($this->conexion, $sql) or die('No edito la venta');

    $vec = [];
    $vec['resultado'] = "OK";
    $vec['mensaje'] = "Se edito el registro";

    return $vec;
    }
   }
?>