<?php
session_start();
require_once "Conexion.php";
$db = new Conexion();

if ($_POST) {
    // 1. Datos del Cliente
    $id_cliente = $_POST['id_cliente']; 
    $nombre = mysqli_real_escape_string($db->conexion, $_POST['nombre_completo']);
    $dni = mysqli_real_escape_string($db->conexion, $_POST['dni_ruc']);
    $telefono = mysqli_real_escape_string($db->conexion, $_POST['telefono']);
    $correo = isset($_POST['correo']) ? mysqli_real_escape_string($db->conexion, $_POST['correo']) : '';

    // 2. Datos del Vehículo
    $placa = strtoupper(mysqli_real_escape_string($db->conexion, $_POST['placa']));
    $marca = mysqli_real_escape_string($db->conexion, $_POST['marca']);
    $modelo = mysqli_real_escape_string($db->conexion, $_POST['modelo']);
    $anio = !empty($_POST['anio']) ? $_POST['anio'] : 'NULL';
    $color = mysqli_real_escape_string($db->conexion, $_POST['color']);
    $vin_chasis = mysqli_real_escape_string($db->conexion, $_POST['vin']);
    $kilometraje = !empty($_POST['kilometraje']) ? $_POST['kilometraje'] : 0;
    $combustible = mysqli_real_escape_string($db->conexion, $_POST['combustible']);
    $tipo_vehiculo = mysqli_real_escape_string($db->conexion, $_POST['tipo_vehiculo']);

    // 🚀 VALIDACIÓN DE PLACA GLOBAL
    if (empty($id_cliente)) {
        $checkPlaca = $db->ejecutar("SELECT id_vehiculo FROM vehiculos WHERE placa = '$placa'");
        if ($db->contar($checkPlaca) > 0) {
            header("Location: ../clientes.php?res=error&msg=El vehículo con placa $placa ya existe en el sistema.");
            exit();
        }
    }

    $db->conexion->begin_transaction();

    try {
        if (empty($id_cliente)) {
            // --- INSERTAR CLIENTE (Sin id_taller, ahora es global) ---
            $sqlCliente = "INSERT INTO clientes (nombre_completo, telefono, dni_ruc, correo) 
                           VALUES ('$nombre', '$telefono', '$dni', '$correo')";
            $db->ejecutar($sqlCliente);
            $id_cliente = $db->conexion->insert_id; 

            // --- INSERTAR VEHÍCULO ---
            $sqlVehiculo = "INSERT INTO vehiculos (id_cliente, placa, marca, modelo, anio, color, vin_chasis, kilometraje, combustible, tipo_vehiculo) 
                            VALUES ('$id_cliente', '$placa', '$marca', '$modelo', $anio, '$color', '$vin_chasis', '$kilometraje', '$combustible', '$tipo_vehiculo')";
            $db->ejecutar($sqlVehiculo);
        } else {
            // --- EDITAR CLIENTE ---
            $sqlEditCliente = "UPDATE clientes SET 
                nombre_completo='$nombre', 
                dni_ruc='$dni', 
                telefono='$telefono', 
                correo='$correo'
                WHERE id_cliente='$id_cliente'";
            $db->ejecutar($sqlEditCliente);
            
            // --- ACTUALIZAR VEHÍCULO ---
            $sqlEditVehiculo = "UPDATE vehiculos SET 
                placa='$placa', 
                marca='$marca', 
                modelo='$modelo', 
                anio=$anio, 
                color='$color', 
                vin_chasis='$vin_chasis',
                kilometraje='$kilometraje',
                combustible='$combustible',
                tipo_vehiculo='$tipo_vehiculo'
                WHERE id_cliente='$id_cliente'";
            $db->ejecutar($sqlEditVehiculo);
        }

        $db->conexion->commit();
        if (empty($_POST['id_cliente'])) {
            header("Location: ../clientes.php?res=ok");
        } else {
            header("Location: ../clientes.php?res=edit");
        }
        exit();

    } catch (Exception $e) {
        $db->conexion->rollback();
        header("Location: ../clientes.php?res=error&msg=" . urlencode($e->getMessage()));
    }
}