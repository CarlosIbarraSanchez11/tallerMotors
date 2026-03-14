<?php
session_start();
require_once "Conexion.php";
$db = new Conexion();

if ($_POST) {
    $id = $_POST['id_usuario'];
    $nombre = mysqli_real_escape_string($db->conexion, $_POST['nombre']);
    $usuario = mysqli_real_escape_string($db->conexion, $_POST['usuario']);
    $nivel = $_POST['nivel'];
    $id_taller = !empty($_POST['id_taller']) ? $_POST['id_taller'] : "NULL"; // Maneja el valor nulo
    $pass = $_POST['password'];

    if (empty($id)) {
        $pass_hash = password_hash($pass, PASSWORD_DEFAULT);
        $sql = "INSERT INTO usuarios (nombre, usuario, password, nivel, estado, id_taller) 
                VALUES ('$nombre', '$usuario', '$pass_hash', '$nivel', 1, $id_taller)";
    } else {
        $extra_sql = "";
        if (!empty($pass)) {
            $pass_hash = password_hash($pass, PASSWORD_DEFAULT);
            $extra_sql = ", password = '$pass_hash'";
        }
        
        $sql = "UPDATE usuarios SET 
                nombre = '$nombre', 
                usuario = '$usuario', 
                nivel = '$nivel',
                id_taller = $id_taller 
                $extra_sql 
                WHERE id_usuario = '$id'";
    }

    if ($db->ejecutar($sql)) {
        header("Location: ../usuarios.php?res=success");
    } else {
        header("Location: ../usuarios.php?res=error");
    }
}