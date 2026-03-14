<?php
header('Content-Type: application/json');
require_once "Conexion.php";
$db = new Conexion();

$id_mov = $_POST['id_movimiento'];
$id_prod = $_POST['id_producto'];
$cant = $_POST['cantidad'];

try {
    // 1. Borramos el movimiento del Kardex
    $db->ejecutar("DELETE FROM inventario_movimientos WHERE id_movimiento = '$id_mov'");
    
    // 2. IMPORTANTE: Devolvemos el stock al producto
    $db->ejecutar("UPDATE productos SET stock_actual = stock_actual + $cant WHERE id_producto = '$id_prod'");

    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}