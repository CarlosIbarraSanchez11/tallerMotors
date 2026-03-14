<?php
session_start();
require_once "Conexion.php";
$db = new Conexion();

if ($_POST) {
    // Sinceramente, usamos esta función para limpiar datos de forma segura
    function limpiar($db, $campo) {
        return isset($_POST[$campo]) ? mysqli_real_escape_string($db->conexion, $_POST[$campo]) : '';
    }

    // 1. Datos del Cliente
    $id_cliente = $_POST['id_cliente'] ?? ''; 
    $nombre = limpiar($db, 'nombre_completo');
    $dni = limpiar($db, 'dni_ruc');
    $telefono = limpiar($db, 'telefono');
    $correo = limpiar($db, 'correo');

    // 2. Datos del Vehículo
    $placa = strtoupper(limpiar($db, 'placa'));
    $marca = limpiar($db, 'marca');
    $modelo = limpiar($db, 'modelo');
    $anio = !empty($_POST['anio']) ? $_POST['anio'] : 'NULL';
    $color = limpiar($db, 'color');
    
    // 🛡️ AQUÍ ESTABA EL ERROR: Protegemos el campo 'vin'
    $vin_chasis = limpiar($db, 'vin'); 
    
    $kilometraje = !empty($_POST['kilometraje']) ? $_POST['kilometraje'] : 0;
    $combustible = limpiar($db, 'combustible');
    $tipo_vehiculo = limpiar($db, 'tipo_vehiculo');

    // 🚀 VALIDACIÓN DE PLACA GLOBAL
    if (empty($id_cliente)) {
        $checkPlaca = $db->ejecutar("SELECT id_vehiculo FROM vehiculos WHERE placa = '$placa'");
        if ($db->contar($checkPlaca) > 0) {
            header("Location: ../clientes.php?res=error&msg=El vehículo con placa $placa ya existe.");
            exit();
        }
    }

    $db->conexion->begin_transaction();

    try {
        if (empty($id_cliente)) {
            // INSERTAR CLIENTE
            $sqlCliente = "INSERT INTO clientes (nombre_completo, telefono, dni_ruc, correo) 
                           VALUES ('$nombre', '$telefono', '$dni', '$correo')";
            $db->ejecutar($sqlCliente);
            $id_cliente = $db->conexion->insert_id; 

            // INSERTAR VEHÍCULO
            $sqlVehiculo = "INSERT INTO vehiculos (id_cliente, placa, marca, modelo, anio, color, vin_chasis, kilometraje, combustible, tipo_vehiculo) 
                            VALUES ('$id_cliente', '$placa', '$marca', '$modelo', $anio, '$color', '$vin_chasis', '$kilometraje', '$combustible', '$tipo_vehiculo')";
            $db->ejecutar($sqlVehiculo);
        } else {
            // EDITAR CLIENTE
            $sqlEditCliente = "UPDATE clientes SET 
                nombre_completo='$nombre', dni_ruc='$dni', telefono='$telefono', correo='$correo'
                WHERE id_cliente='$id_cliente'";
            $db->ejecutar($sqlEditCliente);
            
            // ACTUALIZAR VEHÍCULO
            $sqlEditVehiculo = "UPDATE vehiculos SET 
                placa='$placa', marca='$marca', modelo='$modelo', anio=$anio, color='$color', 
                vin_chasis='$vin_chasis', kilometraje='$kilometraje', combustible='$combustible', tipo_vehiculo='$tipo_vehiculo'
                WHERE id_cliente='$id_cliente'";
            $db->ejecutar($sqlEditVehiculo);
        }

        $db->conexion->commit();
        
        // Sinceramente, esto ahora funcionará porque ya no hay errores previos
        $redirect = empty($_POST['id_cliente']) ? "ok" : "edit";
        header("Location: ../clientes.php?res=$redirect");
        exit();

    } catch (Exception $e) {
        $db->conexion->rollback();
        header("Location: ../clientes.php?res=error&msg=" . urlencode($e->getMessage()));
        exit();
    }
}