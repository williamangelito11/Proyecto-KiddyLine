<?php

 class categoria{
    //atributos
private $conexion;

public function __construct($conexion) {
    $this->conexion = $conexion;
}

//metodos

public function consultar(){
    $sql = "SELECT * FROM categorias ORDER BY nombre";
    $res = mysqli_query($this->conexion, $sql) or die('no encontro la tabla categoria');
    
    $vec = [];

    while ($row = mysqli_fetch_assoc($res)){
        $vec[] = $row;
        
    }
    return $vec;
}
public function eliminar($id){
    $sql = "DELETE FROM categorias WHERE id_categoria = $id";
    mysqli_query($this->conexion, $sql) or die('No selimino el registro');

    $vec = [];
    $vec['resultado'] = "OK";
    $VEC['mensaje'] = "Se elimino el registro";

    return $vec;
}

public function insertar($params){
    $sql = "INSERT INTO categorias(nombre, tipo) VALUES('$params->nombre', $params->tipo)";
    mysqli_query($this->conexion, $sql) or die('No inserto el registro');

    $vec = [];
    $vec['resultado'] = "OK";
    $vec['mensaje'] = "Se inserto el registro";
    
    return $vec;
    }

public function editar($id, $params){
        $sql = "UPDATE categoria SET nombre = '$params->nombre', tipo = '$params->tipo' WHERE id_categoria = $id";
        mysqli_query($this->conexion, $sql) or die('No edito el registro');

    $vec = [];
    $vec['resultado'] = "OK";
    $vec['mensaje'] = "Se edito el registro";

    return $vec;
    }
}

?>