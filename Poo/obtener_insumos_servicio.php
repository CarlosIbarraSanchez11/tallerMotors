<?php
ob_start();
ob_clean();

require_once "Conexion.php";
$db = new Conexion();

$id_serv = isset($_GET['id']) ? $_GET['id'] : 0;

$sql = "SELECT sp.id_servicio_producto, sp.cantidad_sugerida, p.nombre_producto, p.unidad_medida 
        FROM servicio_productos sp 
        INNER JOIN productos p ON sp.id_producto = p.id_producto 
        WHERE sp.id_servicio = '$id_serv'";

$res = $db->ejecutar($sql);

$html = "";
while($i = $db->recorrer($res)) {
    $html .= '
    <div class="list-group-item d-flex justify-content-between align-items-center border-start-0 border-end-0 px-0">
        <div>
            <i class="fa fa-box text-muted me-2"></i>
            <span class="fw-bold">'.htmlspecialchars($i['nombre_producto']).'</span>
            <small class="text-muted d-block">Sugerido: '.$i['cantidad_sugerida'].' '.$i['unidad_medida'].'</small>
        </div>
        <button type="button" class="btn btn-outline-danger btn-sm border-0" onclick="eliminarInsumo('.$i['id_servicio_producto'].')">
            <i class="fa fa-trash-can"></i>
        </button>
    </div>';
}

echo ($html == "") ? '<div class="text-center py-3 text-muted">No hay insumos vinculados todavía.</div>' : $html;
exit;
?>