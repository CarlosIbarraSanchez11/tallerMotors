<?php
session_start();
if (!isset($_SESSION['id_taller'])) { exit("Sesión no válida"); }
require_once "Conexion.php";
$db = new Conexion();

// Usamos la propiedad pública de tu clase para las limpiezas manuales
$con = $db->conexion; 

if (!$con) {
    die("Error: La conexión no se estableció correctamente.");
}

// 1. Capturar identificadores
$id_producto     = $_POST['id_producto'];
$id_taller       = $_SESSION['id_taller'];

// 2. Limpiar y capturar datos de IDENTIDAD (Sin precios)
$codigo_barras   = mysqli_real_escape_string($con, $_POST['codigo_barras']);
$nombre_producto = mysqli_real_escape_string($con, $_POST['nombre_producto']);
$marca           = mysqli_real_escape_string($con, $_POST['marca']);
$categoria       = mysqli_real_escape_string($con, $_POST['categoria']);
$unidad_medida   = mysqli_real_escape_string($con, $_POST['unidad_medida']);
$stock_minimo    = (int)$_POST['stock_minimo'];

// 3. Lógica SQL (INSERT / UPDATE)
if (empty($id_producto)) {
    // --- INSERTAR ---
    // Sinceramente, eliminamos precio_venta y precio_mano_obra de la lista
    $sql = "INSERT INTO productos (
                id_taller, codigo_barras, nombre_producto, marca, 
                categoria, unidad_medida, stock_minimo, stock_actual
            ) VALUES (
                '$id_taller', '$codigo_barras', '$nombre_producto', '$marca', 
                '$categoria', '$unidad_medida', '$stock_minimo', 0
            )";
} else {
    // --- ACTUALIZAR ---
    // Sinceramente, aquí solo actualizamos la ficha técnica, los costos no se tocan
    $sql = "UPDATE productos SET 
                codigo_barras = '$codigo_barras',
                nombre_producto = '$nombre_producto', 
                marca = '$marca', 
                categoria = '$categoria', 
                unidad_medida = '$unidad_medida',
                stock_minimo = '$stock_minimo' 
            WHERE id_producto = '$id_producto' AND id_taller = '$id_taller'";
}

// 4. Ejecutar
if ($db->ejecutar($sql)) {
    // Sinceramente, regresamos al catálogo con éxito
    header("Location: ../productos.php?res=success");
} else {
    echo "Error en la base de datos: " . mysqli_error($con);
}
exit();
?>