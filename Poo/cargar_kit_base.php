<?php
// MODO DIAGNÓSTICO ACTIVO
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

require_once "Conexion.php"; 
$db = new Conexion();

$id_cita = $_GET['id_cita'];
$id_servicio = $_GET['id_servicio'];

// 1. Verificamos si hay una receta para este servicio
$sql_receta = "SELECT id_producto, cantidad_sugerida FROM servicio_productos WHERE id_servicio = '$id_servicio'";
$res = $db->ejecutar($sql_receta);

// SI NO HAY FILAS, ES QUE NO HAS CONFIGURADO EL KIT PARA ESTE ID DE SERVICIO
if ($res->num_rows == 0) {
    die("Error: No hay productos configurados para el Servicio ID: " . $id_servicio . ". Verifica tu tabla servicio_productos.");
}

while($item = $db->recorrer($res)) {
    $id_p = $item['id_producto'];
    $cant = $item['cantidad_sugerida'];

    // 2. Insertamos en la tabla de PEDIDOS (con precio 0 porque es kit)
    $sql_ins = "INSERT INTO pedidos_repuestos (id_cita, id_producto, cantidad, precio_unidad, estado_pedido) 
                VALUES ('$id_cita', '$id_p', '$cant', '0.00', 'SOLICITADO')";
    
    if(!$db->ejecutar($sql_ins)) {
        die("Error al insertar producto ".$id_p.": " . $db->conexion->error);
    }
}

// Si todo salió bien, regresamos
header("Location: ../gestion_taller.php?id_cita=$id_cita&msj=kit_cargado");
exit();
?>