<?php
require_once "Conexion.php";
$db = new Conexion();

if ($_POST) {
    // Sinceramente, ahora que el modal envía "id_proveedor", lo recibimos así:
    $id_proveedor = $_POST['id_proveedor'] ?? ''; 
    
    $ruc          = mysqli_real_escape_string($db->conexion, $_POST['ruc']);
    $razon_social = mysqli_real_escape_string($db->conexion, $_POST['razon_social']);
    $telefono     = mysqli_real_escape_string($db->conexion, $_POST['telefono']);
    $correo       = mysqli_real_escape_string($db->conexion, $_POST['correo']);
    $direccion    = mysqli_real_escape_string($db->conexion, $_POST['direccion']);

    if (empty($id_proveedor)) {
        // Validar RUC solo si es NUEVO
        $check = $db->ejecutar("SELECT id_proveedor FROM proveedores WHERE ruc = '$ruc'");
        if ($db->contar($check) > 0) {
            header("Location: ../proveedores.php?res=error&msg=El RUC ya existe.");
            exit();
        }
        $sql = "INSERT INTO proveedores (ruc, razon_social, telefono, correo, direccion, estado) 
                VALUES ('$ruc', '$razon_social', '$telefono', '$correo', '$direccion', 1)";
    } else {
        // ACTUALIZAR si ya tenemos el ID
        $sql = "UPDATE proveedores SET 
                ruc='$ruc', razon_social='$razon_social', telefono='$telefono', 
                correo='$correo', direccion='$direccion' 
                WHERE id_proveedor='$id_proveedor'";
    }

    $db->ejecutar($sql);
    header("Location: ../proveedores.php?res=ok");
    exit();
}