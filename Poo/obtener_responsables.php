<?php
session_start();
require_once "Conexion.php";
$db = new Conexion();

$id_taller = $_SESSION['id_taller'] ?? 1;
$id_cita   = mysqli_real_escape_string($db->conexion, $_POST['id_cita']);

// 🚀 1. Buscamos quién es el técnico que ya está guardado en esta cita (Ej: ID 13)
$resCita = $db->ejecutar("SELECT id_tecnico FROM citas WHERE id_cita = '$id_cita'");
$citaInfo = $db->recorrer($resCita);
$id_actual = $citaInfo['id_tecnico'];

// 🚀 2. Traemos a los mecánicos y marcamos al que coincide
$sql = "SELECT id_usuario, nombre, nivel 
        FROM usuarios 
        WHERE id_taller = '$id_taller' 
        AND estado = 1 
        AND nivel LIKE 'MECANICO%' 
        ORDER BY (id_usuario = '$id_actual') DESC, nombre ASC";

$res = $db->ejecutar($sql);
$tecnicos = [];

while ($u = $db->recorrer($res)) {
    $tecnicos[] = [
        'id_usuario' => $u['id_usuario'],
        'nombre'     => $u['nombre'],
        'nivel'      => $u['nivel'],
        'seleccionado' => ($u['id_usuario'] == $id_actual) // 👈 Esto es la clave
    ];
}

header('Content-Type: application/json');
echo json_encode($tecnicos);