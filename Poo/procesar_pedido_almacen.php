<?php
header('Content-Type: application/json');
require_once "Conexion.php";
session_start();
$db = new Conexion();

try {
    if (!isset($_POST['id_pedido']) || !isset($_POST['estado'])) {
        throw new Exception("Datos incompletos para procesar el pedido.");
    }

    $id_pedido = $_POST['id_pedido'];
    $nuevo_estado = $_POST['estado']; 
    $id_usuario = $_SESSION['id_usuario'] ?? 1; 

    // 1. OBTENER DATOS Y CALCULAR STOCK REAL (ENTRADAS - SALIDAS)
    // Sinceramente, aquí calculamos la "verdad" del almacén en tiempo real
    $sql_p = "SELECT pr.*, p.precio_venta, p.stock_actual, v.placa
              FROM pedidos_repuestos pr
              JOIN productos p ON pr.id_producto = p.id_producto
              JOIN citas ci ON pr.id_cita = ci.id_cita
              JOIN vehiculos v ON ci.id_vehiculo = v.id_vehiculo
              WHERE pr.id_pedido = '$id_pedido'";
    
    $res_p = $db->ejecutar($sql_p);
    $data = $db->recorrer($res_p);

    if (!$data) throw new Exception("El pedido no existe.");
    
    $id_prod   = $data['id_producto'];
    $cantidad  = $data['cantidad'];
    $id_cita   = $data['id_cita'];
    $placa     = $data['placa'];
    $stock_disponible = $data['stock_actual']; // 👈 Usamos el stock real que ya tienes
    $precio_un = ($data['precio_unidad'] > 0) ? $data['precio_unidad'] : $data['precio_venta'];

    $db->conexion->begin_transaction();

    if ($nuevo_estado == 'RECIBIDO') {
        // A. VALIDAR DISPONIBILIDAD REAL
        if ($stock_disponible < $cantidad) {
            throw new Exception("No hay stock suficiente. Stock real disponible: " . $stock_disponible);
        }

        // B. DESCONTAR DE PRODUCTOS (Para mantener sincronizada la tabla rápida)
        $db->ejecutar("UPDATE productos SET stock_actual = stock_actual - $cantidad WHERE id_producto = '$id_prod'");

        // C. REGISTRAR KARDEX (SALIDA)
        $motivo = "Entrega de repuesto - Placa: $placa (Cita #$id_cita)";
        
        $sql_mov = "INSERT INTO inventario_movimientos (
                        id_producto, tipo_movimiento, cantidad, motivo, 
                        fecha_registro, id_usuario, id_cita, precio_aplicado
                    ) VALUES (
                        '$id_prod', 'SALIDA', '$cantidad', '$motivo', 
                        NOW(), '$id_usuario', '$id_cita', '$precio_un'
                    )";
        $db->ejecutar($sql_mov);

        // D. ACTUALIZAR ESTADO DEL PEDIDO
        $sql_final = "UPDATE pedidos_repuestos SET 
                      estado_pedido = '$nuevo_estado', 
                      fecha_entrega = NOW() 
                      WHERE id_pedido = '$id_pedido'";
    } else {
        $sql_final = "UPDATE pedidos_repuestos SET estado_pedido = '$nuevo_estado' WHERE id_pedido = '$id_pedido'";
    }

    $db->ejecutar($sql_final);
    $db->conexion->commit();

    echo json_encode(['status' => 'success', 'message' => 'Operación realizada con éxito. El repuesto ha sido procesado.']);

} catch (Exception $e) {
    if(isset($db->conexion)) $db->conexion->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}