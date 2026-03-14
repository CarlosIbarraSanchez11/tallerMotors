<?php
require_once "Conexion.php";
$db = new Conexion();

$busqueda = isset($_GET['q']) ? $db->conexion->real_escape_string($_GET['q']) : '';

$sql = "SELECT id_producto, nombre_producto, stock_actual, precio_venta 
        FROM productos 
        WHERE nombre_producto LIKE '%$busqueda%' AND stock_actual > 0 LIMIT 5";
$res = $db->ejecutar($sql);

$html = "";
if($db->conexion->affected_rows > 0){
    while($p = $db->recorrer($res)){
        $html .= "
            <div class='list-group-item list-group-item-action py-3'>
                <div class='d-flex justify-content-between align-items-center'>
                    <div class='flex-grow-1' onclick='agregarInsumo({$p['id_producto']}, \"{$p['nombre_producto']}\", {$p['precio_venta']})' style='cursor:pointer;'>
                        <div class='fw-bold'>{$p['nombre_producto']}</div>
                        <small class='text-primary'>Precio: S/ ".number_format($p['precio_venta'], 2)."</small>
                    </div>
                    <div class='d-flex align-items-center'>
                        <input type='number' id='cant_{$p['id_producto']}' value='1' min='1' max='{$p['stock_actual']}' 
                            class='form-control form-control-sm me-2' style='width: 60px;'>
                        <button type='button' class='btn btn-primary btn-sm rounded-pill' 
                                onclick='agregarInsumo({$p['id_producto']}, \"{$p['nombre_producto']}\", {$p['precio_venta']})'>
                            <i class='fa fa-plus'></i>
                        </button>
                    </div>
                </div>
            </div>";
    }
} else {
    $html = "<div class='list-group-item text-muted small'>No se encontraron productos con stock.</div>";
}
echo $html;