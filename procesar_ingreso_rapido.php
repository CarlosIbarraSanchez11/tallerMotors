<?php
// 1. Diagnóstico de errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "Conexion.php";
$db = new Conexion();
$con = $db->conexion;

if ($_POST) {
    try {
        // 2. Captura de datos
        $id_producto  = $_POST['id_producto'];
        $cantidad     = (int)$_POST['cantidad'];
        $costo        = !empty($_POST['costo']) ? $_POST['costo'] : 0;
        $lote         = mysqli_real_escape_string($con, $_POST['lote']);
        
        // Manejo de fecha de vencimiento para que sea compatible con tu SQL
        $vencimiento  = (!empty($_POST['vencimiento'])) ? "'".$_POST['vencimiento']."'" : "NULL";

        // 3. Lógica de Proveedor (Formal vs Informal)
        $tipo_ingreso = $_POST['tipo_ingreso'];
        if ($tipo_ingreso === 'formal' && !empty($_POST['id_proveedor'])) {
            $id_proveedor = $_POST['id_proveedor']; 
            $num_doc      = "'".mysqli_real_escape_string($con, $_POST['num_documento'])."'";
        } else {
            $id_proveedor = "NULL";
            $num_doc      = "NULL";
        }

        // 4. PASO 1: Registrar en el historial de INGRESOS
        $sql_historial = "INSERT INTO ingresos (id_producto, id_proveedor, cantidad, lote, num_documento, fecha_vencimiento, costo_unitario) 
                          VALUES ('$id_producto', $id_proveedor, '$cantidad', '$lote', $num_doc, $vencimiento, '$costo')";
        $db->ejecutar($sql_historial);

        // 5. PASO 2: Actualizar el STOCK ACTUAL en la tabla productos
        $sql_stock = "UPDATE productos SET stock_actual = stock_actual + $cantidad WHERE id_producto = '$id_producto'";
        $db->ejecutar($sql_stock);

        // 6. PASO 3: Registrar en el KARDEX (inventario_movimientos)
        $id_user = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : 1;
        
        // Usamos los nombres exactos de tus columnas: id_movimiento (auto), id_producto, tipo_movimiento, cantidad, motivo, etc.
        $sql_mov = "INSERT INTO inventario_movimientos (id_producto, tipo_movimiento, cantidad, motivo, id_usuario, numero_lote, fecha_vencimiento) 
                    VALUES ('$id_producto', 'ENTRADA', '$cantidad', 'Ingreso por Almacén ($tipo_ingreso)', '$id_user', '$lote', $vencimiento)";
        $db->ejecutar($sql_mov);

        // 7. Redirigir al éxito
        header("Location: ../ingresos.php?status=ok");
        exit();

    } catch (mysqli_sql_exception $e) {
        echo "<h4>Error detectado:</h4>" . $e->getMessage();
        echo "<br><br><a href='../ingresos.php'>Regresar</a>";
        exit();
    }
}