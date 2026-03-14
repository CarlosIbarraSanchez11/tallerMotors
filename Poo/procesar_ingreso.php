<?php
require_once "Conexion.php";
$db = new Conexion();

if ($_POST) {
    $doc = mysqli_real_escape_string($db->conexion, $_POST['documento']);
    $nom = mysqli_real_escape_string($db->conexion, $_POST['nombre_cliente']);
    $tel = mysqli_real_escape_string($db->conexion, $_POST['telefono']);
    $pla = mysqli_real_escape_string($db->conexion, $_POST['placa']);
    // ... recibir los demás campos ...

    // 1. Verificar si el cliente existe, sino crearlo
    $checkCliente = $db->ejecutar("SELECT id_cliente FROM clientes WHERE documento = '$doc'");
    if ($db->contar($checkCliente) > 0) {
        $rC = $db->recorrer($checkCliente);
        $id_cliente = $rC['id_cliente'];
    } else {
        $db->ejecutar("INSERT INTO clientes (documento, nombre, telefono) VALUES ('$doc', '$nom', '$tel')");
        $id_cliente = mysqli_insert_id($db->conexion);
    }

    // 2. Registrar el vehículo vinculado al cliente
    $sqlVehiculo = "INSERT INTO vehiculos (id_cliente, placa, marca, modelo) VALUES ('$id_cliente', '$pla', '$mar', '$mod')";
    
    if($db->ejecutar($sqlVehiculo)) {
        header("Location: ../dashboard.php?msj=registrado");
    }
}
?>