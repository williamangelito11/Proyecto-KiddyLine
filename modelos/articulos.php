<?php
   class Articulos{

   private $conexion;

public function __construct($conexion) {
    $this->conexion = $conexion;
}

//metodos

public function consultar(){
    $sql = "INSERT INTO articulos (
             id_categoria, 
             codigo_referencia,
             nombre,
             tipo_articulo,
             talla,
             color,
             stock_actual,
             stock_minimo,
             precio_costo,
             precio_venta
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $this->conexion->prepare($sql);
        $res = $stmt->execute([
            $params->id_categoria,
            $params->codigo_referencia,
            $params->nombre,
            $params->tipo_articulo,
            $params->talla,
            $params->color,
            $params->stock_actual,
            $params->stock_minimo,
            $params->precio_costo,
            $params->precio_venta
        ]);

        return ['resultado' => $res];
}
  
  
public function eliminar($id){
    $sql = "DELETE FROM articulos WHERE id_articulo = $id";
    mysqli_query($this->conexion, $sql) or die('No selimino el articulo');

    $vec = [];
    $vec['resultado'] = "OK";
    $VEC['mensaje'] = "Se elimino el registro";

    return $vec;
}

public function insertar($params){
    $sql = "INSERT INTO articulos(codigo_referencia, nombre, tipo_articulo, talla, color, stock_actual, stock_minimo, precio_costo, precio_venta) 
    VALUES('$params->codig', '$params->nombre', '$params->tipo_articulo', $params->talla, '$params->color', '$params->stock_actual', 
    $params->stock_actual, $params->stock_minimo, $params->precio_costo, $params->precio_venta)";

    mysqli_query($this->conexion, $sql) or die('No inserto el registro');

    $vec = [];
    $vec['resultado'] = "OK";
    $vec['mensaje'] = "Se inserto el registro";
    
    return $vec;
    }

public function editar($id, $params){
        $sql = "UPDATE articulos SET codigo_referencia = '$params->codigo_referencia', nombre = '$params->nombre', tipo_articulo =
         '$params->tipo_articulo', talla = '$params->talla', color = '$params->color', stock_actual = '$params->atock_actual', stock_minimo = '$params->stock_minimo', 
         precio_costo = '$params->precio_costo', precio_venta = $params->precio_venta
          WHERE id_articulo = $id";
          
        mysqli_query($this->conexion, $sql) or die('No edito el registro');

    $vec = [];
    $vec['resultado'] = "OK";
    $vec['mensaje'] = "Se edito el registro";

    return $vec;
    }
}

?>