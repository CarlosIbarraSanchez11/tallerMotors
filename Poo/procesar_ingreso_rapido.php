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
        // 2. Captura de datos básicos (Los que sí vienen del modal)
        $id_producto  = $_POST['id_producto'];
        $cantidad     = (int)$_POST['cantidad'];
        $tipo_ingreso = $_POST['tipo_ingreso'];

        // 🚀 VALORES POR DEFECTO (Ya que los quitamos del modal)
        $costo        = 0; 
        $lote         = "N/A"; 
        $vencimiento  = "NULL"; 

        // 3. Lógica de Proveedor (Solo si seleccionaron "Formal")
        if ($tipo_ingreso === 'formal' && !empty($_POST['id_proveedor'])) {
            $id_proveedor = $_POST['id_proveedor']; 
            $num_doc      = "'".mysqli_real_escape_string($con, $_POST['num_documento'])."'";
        } else {
            $id_proveedor = "NULL";
            $num_doc      = "NULL";
        }

        // 4. PASO 1: Registrar en el historial de INGRESOS
        // Sinceramente, mantenemos la estructura de la tabla pero con los valores por defecto
        $sql_historial = "INSERT INTO ingresos (id_producto, id_proveedor, cantidad, lote, num_documento, fecha_vencimiento, costo_unitario) 
                          VALUES ('$id_producto', $id_proveedor, '$cantidad', '$lote', $num_doc, $vencimiento, '$costo')";
        $db->ejecutar($sql_historial);

        // 5. PASO 2: Actualizar el STOCK ACTUAL en la tabla productos
        $sql_stock = "UPDATE productos SET stock_actual = stock_actual + $cantidad WHERE id_producto = '$id_producto'";
        $db->ejecutar($sql_stock);

        // 6. PASO 3: Registrar en el KARDEX (inventario_movimientos)
        // Sinceramente, el Kardex es sagrado para saber quién cargó qué cosa
        $id_user = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : 1;
        
        $sql_mov = "INSERT INTO inventario_movimientos (id_producto, tipo_movimiento, cantidad, motivo, id_usuario, numero_lote, fecha_vencimiento) 
                    VALUES ('$id_producto', 'ENTRADA', '$cantidad', 'Ingreso rápido ($tipo_ingreso)', '$id_user', '$lote', $vencimiento)";
        $db->ejecutar($sql_mov);

        // 7. Redirigir al éxito
        header("Location: ../ingresos.php?status=ok");
        exit();

    } catch (mysqli_sql_exception $e) {
        echo "<div style='color:red; font-family:sans-serif; padding:20px; border:2px solid red;'>";
        echo "<h4>❌ Error en el proceso:</h4>" . $e->getMessage();
        echo "<br><br><a href='../ingresos.php'>Regresar y corregir</a></div>";
        exit();
    }
}