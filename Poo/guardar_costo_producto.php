<?php
// Poo/guardar_costo_producto.php
require_once "Conexion.php";
$db = new Conexion();

// 1. Recibimos los datos del formulario (incluyendo los nuevos campos)
$id_prod   = $_POST['id_producto'];
$precio    = $_POST['precio_compra'];
$tiempo    = $_POST['tiempo_hh_obra'];
$costo_hh  = $_POST['costo_hh']; // 🚀 Nuevo
$tecnicos  = $_POST['cant_tecnicos']; // 🚀 Nuevo

// 2. Ejecutamos la consulta actualizada
// Sinceramente, usamos ON DUPLICATE KEY UPDATE para que si el producto ya existe, solo se actualicen sus costos
$sql = "INSERT INTO costos_productos (id_producto, precio_compra, tiempo_hh_obra, costo_hh, cant_tecnicos) 
        VALUES ('$id_prod', '$precio', '$tiempo', '$costo_hh', '$tecnicos') 
        ON DUPLICATE KEY UPDATE 
        precio_compra = '$precio', 
        tiempo_hh_obra = '$tiempo',
        costo_hh = '$costo_hh',
        cant_tecnicos = '$tecnicos'";

$res = $db->ejecutar($sql);

if ($res) {
    echo "ok";
} else {
    // Sinceramente, si hay un error, esto te ayudará a debugear en la consola
    echo "Error en SQL: " . mysqli_error($db->conexion);
}
?>