<?php
session_start();
require_once "Poo/Conexion.php";
$db = new Conexion();

if ($_POST) {
    $user = trim(mysqli_real_escape_string($db->conexion, $_POST['user']));
    $pass = trim($_POST['pass']);

    $sql = "SELECT u.*, t.nombre_taller 
            FROM usuarios u 
            LEFT JOIN talleres t ON u.id_taller = t.id_taller 
            WHERE u.usuario = '$user' LIMIT 1";
    $res = $db->ejecutar($sql);

    if ($db->contar($res) > 0) {
        $row = $db->recorrer($res);
        
        // Verificamos si la clave es correcta (Ya sea encriptada o texto plano)
        if (password_verify($pass, $row['password']) || $pass == $row['password']) {
            
            // 1. Datos del Usuario (La persona)
            $_SESSION['id_usuario']     = $row['id_usuario'];
            $_SESSION['nombre_usuario'] = $row['nombre']; 
            $_SESSION['nivel_taller']   = $row['nivel'];

            // 2. Datos de la Sede (El lugar)
            $_SESSION['id_taller']          = $row['id_taller']; 
            $_SESSION['nombre_taller_sede'] = $row['nombre_taller'] ?? 'SIN SEDE';

            header("Location: dashboard.php");
            exit();
        } else {
            header("Location: index.php?error=pass_incorrecto");
            exit();
        }
    } else {
        header("Location: index.php?error=usuario_no_existe");
        exit();
    }
}
?>