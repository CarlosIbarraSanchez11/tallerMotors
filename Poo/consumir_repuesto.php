<?php
session_start();
require_once "Conexion.php";
$db = new Conexion();
$con = $db->conexion;

if ($_POST) {
    $id_cita     = $_POST['id_cita'];
    $id_producto = $_POST['id_producto'];
    $cantidad    = (int)$_POST['cantidad'];
    $precio      = $_POST['precio']; // Precio al que se le vende al cliente
    $id_user     = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : 1;

    // 1. VALIDACIÓN CRÍTICA: ¿Hay stock suficiente?
    $sql_check = "SELECT stock_actual, nombre_producto FROM productos WHERE id_producto = '$id_producto'";
    $res_check = $db->ejecutar($sql_check);
    $prod = $db->recorrer($res_check);

    if ($prod['stock_actual'] < $cantidad) {
        // Si no hay suficiente, regresamos con un error
        header("Location: ../gestion_taller.php?id_cita=$id_cita&error=insuficiente");
        exit();
    }

    // 2. PASO 1: Restar del stock real en la tabla productos
    $sql_update_stock = "UPDATE productos SET stock_actual = stock_actual - $cantidad WHERE id_producto = '$id_producto'";
    
    if ($db->ejecutar($sql_update_stock)) {
        
        // 3. PASO 2: Registrar la SALIDA en el Kardex (inventario_movimientos)
        // Vinculamos el id_cita para saber en qué carro se usó
        $motivo = "Consumo en Taller - Cita #$id_cita";
        
        $sql_mov = "INSERT INTO inventario_movimientos (
            id_producto, tipo_movimiento, cantidad, motivo, id_usuario, id_cita, precio_aplicado
        ) VALUES (
            '$id_producto', 'SALIDA', '$cantidad', '$motivo', '$id_user', '$id_cita', '$precio'
        )";
        
        $db->ejecutar($sql_mov);

        // 4. Todo bien, regresamos a la gestión del taller
        header("Location: ../gestion_taller.php?id_cita=$id_cita&res=agregado");
    } else {
        echo "Error al procesar el consumo.";
    }
}