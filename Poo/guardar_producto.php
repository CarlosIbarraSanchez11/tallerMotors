<?php
session_start();
require_once "Conexion.php";
$db = new Conexion();

$id_producto     = $_POST['id_producto'];
$id_taller       = $_SESSION['id_taller'];
$nombre          = $_POST['nombre_producto'];
$marca           = $_POST['marca'];
$precio_compra   = $_POST['precio_compra'];
$precio_venta    = $_POST['precio_venta'];
$stock_actual    = $_POST['stock_actual'];
$stock_minimo    = $_POST['stock_minimo'];
$categoria       = $_POST['categoria'];

if (empty($id_producto)) {
    // Es un producto nuevo
    $sql = "INSERT INTO productos (id_taller, nombre_producto, marca, precio_compra, precio_venta, stock_actual, stock_minimo, categoria) 
            VALUES ('$id_taller', '$nombre', '$marca', '$precio_compra', '$precio_venta', '$stock_actual', '$stock_minimo', '$categoria')";
} else {
    // Es una edición para corregir errores
    $sql = "UPDATE productos SET 
            nombre_producto = '$nombre', 
            marca = '$marca', 
            precio_compra = '$precio_compra', 
            precio_venta = '$precio_venta', 
            stock_actual = '$stock_actual', 
            stock_minimo = '$stock_minimo', 
            categoria = '$categoria' 
            WHERE id_producto = '$id_producto'";
}

if ($db->ejecutar($sql)) {
    header("Location: ../inventario.php?res=success");
} else {
    header("Location: ../inventario.php?res=error");
}