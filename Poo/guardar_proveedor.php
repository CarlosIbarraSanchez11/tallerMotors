<?php
require_once "Conexion.php";
$db = new Conexion();

$id_provider  = $_POST['id_provider'];
$ruc          = $_POST['ruc'];
$razon_social = $_POST['razon_social'];
$telefono     = $_POST['telefono'];
$correo       = $_POST['correo'];

if (empty($id_provider)) {
    $sql = "INSERT INTO proveedores (ruc, razon_social, telefono, correo) VALUES ('$ruc', '$razon_social', '$telefono', '$correo')";
} else {
    $sql = "UPDATE proveedores SET ruc='$ruc', razon_social='$razon_social', telefono='$telefono', correo='$correo' WHERE id_provider='$id_provider'";
}

$db->ejecutar($sql);
header("Location: ../proveedores.php");
exit();