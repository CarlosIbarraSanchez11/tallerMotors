<?php
session_start();
require_once "Conexion.php";
$db = new Conexion();
$con = $db->conexion;

if (isset($_GET['id_movimiento'])) {
    $id_mov     = $_GET['id_movimiento'];
    $id_cita    = $_GET['id_cita'];

    // 1. Buscamos los datos del movimiento antes de borrarlo
    $sql_info = "SELECT id_producto, cantidad FROM inventario_movimientos WHERE id_movimiento = '$id_mov'";
    $res_info = $db->ejecutar($sql_info);
    $mov      = $db->recorrer($res_info);

    if ($mov) {
        $id_prod  = $mov['id_producto'];
        $cantidad = $mov['cantidad'];

        // 2. Devolvemos el stock a la tabla productos (Stock Actual + Cantidad)
        $sql_return = "UPDATE productos SET stock_actual = stock_actual + $cantidad WHERE id_producto = '$id_prod'";
        $db->ejecutar($sql_return);

        // 3. Borramos el registro del Kardex
        $sql_delete = "DELETE FROM inventario_movimientos WHERE id_movimiento = '$id_mov'";
        $db->ejecutar($sql_delete);

        // 4. Redirigimos con éxito
        header("Location: ../gestion_taller.php?id_cita=$id_cita&res=eliminado");
    } else {
        echo "Movimiento no encontrado.";
    }
}
?>