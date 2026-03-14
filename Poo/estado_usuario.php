<?php
require_once "Conexion.php";
$db = new Conexion();

// Recibimos los datos por URL
$id = $_GET['id'];
$st = $_GET['st'];

// Lógica simple: si es 1, pasa a 0. Si es 0, pasa a 1.
$nuevo_estado = ($st == 1) ? 0 : 1;

$sql = "UPDATE usuarios SET estado = '$nuevo_estado' WHERE id_usuario = '$id'";

if($db->ejecutar($sql)){
    header("Location: ../usuarios.php"); // Regresa a la lista
} else {
    echo "Error al cambiar estado";
}
?>