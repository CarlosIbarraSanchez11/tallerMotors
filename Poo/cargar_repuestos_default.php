<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

require_once "Conexion.php"; 
$db = new Conexion();

if (!isset($_GET['id_cita']) || !isset($_GET['id_servicio'])) {
    die("Faltan parámetros en la URL");
}

$id_cita = $_GET['id_cita'];
$id_servicio = $_GET['id_servicio'];

// 🚀 1. BUSCAMOS AL TÉCNICO RESPONSABLE DE LA CITA
// Sinceramente, esto es lo que evita el NULL en tu base de datos.
$resCita = $db->ejecutar("SELECT id_tecnico FROM citas WHERE id_cita = '$id_cita'");
$regCita = $db->recorrer($resCita);
$id_mecanico = $regCita['id_tecnico'] ?? null;

// 2. Buscamos la "receta" del servicio (lo que el kit debería llevar)
$sql_receta = "SELECT sp.id_producto, sp.cantidad_sugerida 
               FROM servicio_productos sp
               WHERE sp.id_servicio = '$id_servicio'";

$res = $db->ejecutar($sql_receta);

if ($res && $res->num_rows > 0) {
    while($item = $db->recorrer($res)) {
        $id_p = $item['id_producto'];
        $cant = $item['cantidad_sugerida'];
        
        // El precio es 0.00 porque ya está incluido en el costo del servicio
        $precio_paquete = "0.00"; 

        // 3. Verificamos si ya existe el PEDIDO para no duplicar
        $sql_check = "SELECT id_pedido FROM pedidos_repuestos 
                      WHERE id_cita = '$id_cita' AND id_producto = '$id_p'";
        $check = $db->ejecutar($sql_check);
        
        if ($check->num_rows == 0) {
            // 🚀 4. INSERCIÓN COMPLETA CON TRAZABILIDAD
            // Añadimos 'tipo_procedencia' (KIT) e 'id_mecanico_pide'
            $sql_ins = "INSERT INTO pedidos_repuestos (
                            id_cita, 
                            id_producto, 
                            tipo_procedencia, 
                            cantidad, 
                            precio_unidad, 
                            estado_pedido, 
                            id_mecanico_pide, 
                            fecha_pedido
                        ) VALUES (
                            '$id_cita', 
                            '$id_p', 
                            'KIT', 
                            '$cant', 
                            '$precio_paquete', 
                            'SOLICITADO', 
                            '$id_mecanico', 
                            NOW()
                        )";
            $db->ejecutar($sql_ins);
        }
    }
    // Redireccionamos avisando que el pedido está en espera
    header("Location: ../gestion_taller.php?id_cita=$id_cita&msj=esperando_almacen");
} else {
    header("Location: ../gestion_taller.php?id_cita=$id_cita&msj=error_sin_config");
}
?>