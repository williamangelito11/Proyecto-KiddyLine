<?php
class Clientes {

    private $conexion;

    public function __construct($conexion) {
        $this->conexion = $conexion;
    }

    // metodos

    public function consultar() {
        $sql = "SELECT * FROM clientes ORDER BY id DESC";
        $registros = mysqli_query($this->conexion, $sql);
        $vec = [];
        while ($re = mysqli_fetch_array($registros)) {
            $vec[] = $re;
        }
        return $vec;
    }

    public function insertar($params) {
        $sql = "INSERT INTO clientes(documento, nombre, telefono, email, direccion) 
                VALUES('$params->documento', '$params->nombre', '$params->telefono', '$params->email', '$params->direccion')";
        
        mysqli_query($this->conexion, $sql) or die('No inserto el registro');

        $vec = [];
        $vec['resultado'] = "OK";
        $vec['mensaje'] = "Se inserto el registro";

        return $vec;
    }

    public function eliminar($id) {
        $sql = "DELETE FROM clientes WHERE id = $id";
        mysqli_query($this->conexion, $sql) or die('No se elimino el cliente');

        $vec = [];
        $vec['resultado'] = "OK";
        $vec['mensaje'] = "Se elimino el registro";

        return $vec;
    }

    public function editar($id, $params) {
        $sql = "UPDATE clientes SET documento = '$params->documento', nombre = '$params->nombre', 
                telefono = '$params->telefono', email = '$params->email', direccion = '$params->direccion' 
                WHERE id = $id";

        mysqli_query($this->conexion, $sql) or die('No edito el registro');

        $vec = [];
        $vec['resultado'] = "OK";
        $vec['mensaje'] = "Se edito el registro";

        return $vec;
    }
}
?>