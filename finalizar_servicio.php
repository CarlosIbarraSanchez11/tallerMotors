<?php
// 1. Activar reporte de errores para diagnóstico
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "Poo/Conexion.php";
$db = new Conexion();

$id_cita = $_GET['id_cita'] ?? null;

if ($id_cita) {
    try {
        // Actualizamos la tabla principal de CITAS
        $sql1 = "UPDATE citas SET estado = 'FINALIZADO' WHERE id_cita = '$id_cita'";
        $db->ejecutar($sql1);

        // Actualizamos la tabla de ORDENES_TRABAJO (Estado que se ve en el PDF)
        $sql2 = "UPDATE ordenes_trabajo SET estado_orden = 'FINALIZADO' WHERE id_cita = '$id_cita'";
        $db->ejecutar($sql2);

        // Redireccionar con éxito
        header("Location: checkout_orden.php?id_cita=$id_cita&res=finalizado_ok");
        exit();

    } catch (Exception $e) {
        die("Error en la base de datos: " . $e->getMessage());
    }
} else {
    header("Location: citas.php");
}