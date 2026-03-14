<?php
require_once "Conexion.php";
$db = new Conexion();

if ($_POST) {
    // Sinceramente, limpiamos los datos para evitar errores y hackers
    $id_provider  = $_POST['id_provider'] ?? '';
    $ruc          = mysqli_real_escape_string($db->conexion, $_POST['ruc']);
    $razon_social = mysqli_real_escape_string($db->conexion, $_POST['razon_social']);
    $telefono     = mysqli_real_escape_string($db->conexion, $_POST['telefono']);
    $correo       = mysqli_real_escape_string($db->conexion, $_POST['correo']);

    if (empty($id_provider)) {
        // 🚀 VALIDACIÓN: ¿Ya existe este RUC?
        $checkRuc = $db->ejecutar("SELECT id_provider FROM proveedores WHERE ruc = '$ruc'");
        if ($db->contar($checkRuc) > 0) {
            // Sinceramente, si ya existe, lo mandamos de vuelta con un mensaje
            header("Location: ../proveedores.php?res=error&msg=El RUC $ruc ya está registrado.");
            exit();
        }
        
        $sql = "INSERT INTO proveedores (ruc, razon_social, telefono, correo, estado) 
                VALUES ('$ruc', '$razon_social', '$telefono', '$correo', 1)";
    } else {
        // ES UNA EDICIÓN
        $sql = "UPDATE proveedores SET 
                ruc='$ruc', 
                razon_social='$razon_social', 
                telefono='$telefono', 
                correo='$correo' 
                WHERE id_provider='$id_provider'";
    }

    $db->ejecutar($sql);
    header("Location: ../proveedores.php?res=ok");
    exit();
}