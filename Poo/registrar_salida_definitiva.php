<?php
session_start();
require_once "Conexion.php";
$db = new Conexion();

if (isset($_GET['id_cita'])) {
    $id_cita = $_GET['id_cita'];
    
    // 1. Cerramos el ciclo global
    $sql = "UPDATE citas SET estado = 'FINALIZADO' WHERE id_cita = '$id_cita'";
    
    if($db->ejecutar($sql)) {
        // 2. Redirigimos con un mensaje de éxito
        // Al volver a citas.php, este vehículo ya NO aparecerá por el filtro SQL
        header("Location: ../citas.php?res=salida_confirmada");
        exit();
    }
}
?>