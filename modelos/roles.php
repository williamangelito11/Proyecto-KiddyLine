<?php

require_once "conexion.php";

class ModeloRoles {

    /*=============================================
    MOSTRAR ROLES (CONSULTAR)
    =============================================*/
    public static function mdlMostrarRoles($tabla, $item, $valor) {

        if ($item != null) {
            $stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla WHERE $item = :$item");
            $stmt->bindParam(":" . $item, $valor, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch();
        } else {
            $conexion = new Conexion();
            $stmt = $conexion->conectar()->prepare("SELECT * FROM $tabla");
            $stmt->execute();
            return $stmt->fetchAll();
        }

        $stmt = null;
    }

    /*=============================================
    CREAR ROL
    =============================================*/
    public static function mdlCrearRol($tabla, $datos) {

        $stmt = Conexion::conectar()->prepare("INSERT INTO $tabla (nombre, descripcion) VALUES (:nombre, :descripcion)");

        $stmt->bindParam(":nombre", $datos["nombre"], PDO::PARAM_STR);
        $stmt->bindParam(":descripcion", $datos["descripcion"], PDO::PARAM_STR);

        if ($stmt->execute()) {
            return "ok";
        } else {
            return "error";
        }

        $stmt = null;
    }

    /*=============================================
    EDITAR ROL
    =============================================*/
    public static function mdlEditarRol($tabla, $datos) {

        $stmt = Conexion::conectar()->prepare("UPDATE $tabla SET nombre = :nombre, descripcion = :descripcion WHERE id = :id");

        $stmt->bindParam(":id", $datos["id"], PDO::PARAM_INT);
        $stmt->bindParam(":nombre", $datos["nombre"], PDO::PARAM_STR);
        $stmt->bindParam(":descripcion", $datos["descripcion"], PDO::PARAM_STR);

        if ($stmt->execute()) {
            return "ok";
        } else {
            return "error";
        }

        $stmt = null;
    }

    /*=============================================
    ELIMINAR ROL
    =============================================*/
    public static function mdlEliminarRol($tabla, $id) {

        $stmt = Conexion::conectar()->prepare("DELETE FROM $tabla WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return "ok";
        } else {
            return "error";
        }

        $stmt = null;
    }
}