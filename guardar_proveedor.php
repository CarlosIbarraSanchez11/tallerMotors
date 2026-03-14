<?php
require_once "Conexion.php";
$db = new Conexion();

$id_proveedor = $_POST['id_proveedor']; // Cambiado
$ruc          = $_POST['ruc'];
$razon_social = $_POST['razon_social'];
$telefono     = $_POST['telefono'];
$correo       = $_POST['correo'];
$direccion    = $_POST['direccion'];

if (empty($id_proveedor)) {
    $sql = "INSERT INTO proveedores (ruc, razon_social, telefono, correo, direccion, estado) 
            VALUES ('$ruc', '$razon_social', '$telefono', '$correo', '$direccion', 1)";
} else {
    $sql = "UPDATE proveedores SET 
                ruc='$ruc', 
                razon_social='$razon_social', 
                telefono='$telefono', 
                correo='$correo', 
                direccion='$direccion' 
            WHERE id_proveedor='$id_proveedor'";
}

$db->ejecutar($sql);
header("Location: ../proveedores.php?res=success");
exit();