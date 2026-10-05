<?php

class Conexion {

    public static function conectar() {
        $link = new PDO(
            "mysql:host=localhost;dbname=kiddylineropabebe;charset=utf8",
            "root",
            ""
        );
        $link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $link;
    }

}

// Conexión mysqli para los módulos que usan $conexion
mysqli_report(MYSQLI_REPORT_OFF);
$conexion = @mysqli_connect("localhost", "root", "", "kiddylineropabebe");

if ($conexion) {
    mysqli_set_charset($conexion, "utf8");
} else {
    $conexion = null; // los endpoints ya validan "if (!$conexion)"
}