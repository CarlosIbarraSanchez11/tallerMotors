<?php
// 1. Iniciamos el buffer de salida
ob_start();

require_once "Conexion.php";
$db = new Conexion();

// 2. LIMPIEZA TOTAL: Borramos cualquier cosa que Conexion.php haya podido imprimir (espacios, errores, etc)
if (ob_get_length()) ob_end_clean();

// 3. Consulta con tus columnas confirmadas
$sql = "SELECT id_producto, nombre_producto, marca FROM productos ORDER BY nombre_producto ASC";
$res = $db->ejecutar($sql);

$html = '<option value="">-- Seleccionar Repuesto --</option>';

if($res) {
    while($p = $db->recorrer($res)) {
        $nombre = htmlspecialchars($p['nombre_producto']);
        $marca  = htmlspecialchars($p['marca']);
        $html .= "<option value='".$p['id_producto']."'>$nombre ($marca)</option>";
    }
} else {
    $html = '<option value="">Error al consultar productos</option>';
}

// 4. Enviamos el resultado y matamos el proceso para que sea una respuesta "limpia"
echo $html;
exit;
?>