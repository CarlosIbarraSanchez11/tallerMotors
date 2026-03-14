<?php
require_once "Conexion.php";
$db = new Conexion();

if ($_POST) {
    // Sinceramente, usamos los nombres exactos de tu tabla
    $id_proveedor = $_POST['id_proveedor'] ?? ''; 
    $ruc          = mysqli_real_escape_string($db->conexion, $_POST['ruc']);
    $razon_social = mysqli_real_escape_string($db->conexion, $_POST['razon_social']);
    $telefono     = mysqli_real_escape_string($db->conexion, $_POST['telefono']);
    $correo       = mysqli_real_escape_string($db->conexion, $_POST['correo']);
    $direccion    = mysqli_real_escape_string($db->conexion, $_POST['direccion']); // Nueva

    if (empty($id_proveedor)) {
        // 🚀 NUEVO REGISTRO: Validamos que el RUC no se repita
        $checkRuc = $db->ejecutar("SELECT id_proveedor FROM proveedores WHERE ruc = '$ruc'");
        
        if ($db->contar($checkRuc) > 0) {
            header("Location: ../proveedores.php?res=error&msg=El RUC $ruc ya existe.");
            exit();
        }
        
        $sql = "INSERT INTO proveedores (ruc, razon_social, telefono, correo, direccion, estado) 
                VALUES ('$ruc', '$razon_social', '$telefono', '$correo', '$direccion', 1)";
    } else {
        // 🚀 EDICIÓN: Usamos id_proveedor para actualizar
        $sql = "UPDATE proveedores SET 
                ruc='$ruc', 
                razon_social='$razon_social', 
                telefono='$telefono', 
                correo='$correo',
                direccion='$direccion'
                WHERE id_proveedor='$id_proveedor'";
    }

    $db->ejecutar($sql);
    header("Location: ../proveedores.php?res=ok");
    exit();
}