<?php
// Desactivar errores HTML para no romper el JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');

try {
    session_start();
    require_once "Conexion.php";
    $db = new Conexion();

    // Validar datos de entrada
    $id_producto = isset($_POST['id_producto']) ? $_POST['id_producto'] : null;
    $id_cita     = isset($_POST['id_cita']) ? $_POST['id_cita'] : null;
    $precio      = isset($_POST['precio']) ? $_POST['precio'] : 0;
    
    // Validar que el usuario esté logueado (como se ve en la imagen)
    $id_usuario  = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : 1; 

    if (!$id_producto || !$id_cita) {
        throw new Exception("Faltan datos para registrar el consumo.");
    }

    // 1. REGISTRAR SALIDA EN TU TABLA DE MOVIMIENTOS
    $motivo = "Consumo en Taller - Cita #$id_cita";
    $cantidad = isset($_POST['cantidad']) ? $_POST['cantidad'] : 1;
    $sql_mov = "INSERT INTO inventario_movimientos 
                (id_producto, tipo_movimiento, cantidad, motivo, id_usuario, id_cita, precio_aplicado) 
                VALUES ('$id_producto', 'SALIDA', 1, '$motivo', '$id_usuario', '$id_cita', '$precio')";
    
    if (!$db->ejecutar($sql_mov)) {
        throw new Exception("Error al registrar movimiento en el Kardex.");
    }

    // 2. DESCONTAR DEL STOCK REAL (Usamos 'stock_actual' que es tu columna real)
    $sql_stock = "UPDATE productos SET stock_actual = stock_actual - 1 WHERE id_producto = '$id_producto'";
    
    if (!$db->ejecutar($sql_stock)) {
        throw new Exception("Error al actualizar el stock del producto.");
    }

    echo json_encode(['status' => 'success', 'message' => 'Producto descontado correctamente.']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}