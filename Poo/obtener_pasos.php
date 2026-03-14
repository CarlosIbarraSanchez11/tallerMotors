<?php
require_once "Conexion.php";
$db = new Conexion();
$id = $_GET['id'];

// Asegúrate de que la columna se llame orden_paso o orden
$sql = "SELECT * FROM pasos_servicio 
        WHERE id_servicio = '$id' 
        ORDER BY seccion_paso ASC, orden_paso ASC";
$res = $db->ejecutar($sql);

if($db->conexion->affected_rows > 0) {
    while($p = $db->recorrer($res)) {
        echo "
        <div class='list-group-item d-flex justify-content-between align-items-center border-0 bg-white shadow-sm rounded-3 mb-2 py-3 px-4'>
            <div>
                <span class='badge bg-primary rounded-pill me-3'>{$p['orden_paso']}</span>
                <span class='text-dark fw-medium'>{$p['descripcion_paso']}</span>
            </div>
            <button onclick='eliminarPaso({$p['id_paso']})' class='btn btn-sm btn-outline-danger border-0 rounded-circle'>
                <i class='fa fa-trash-alt'></i>
            </button>
        </div>";
    }
} else {
    echo "
    <div class='text-center py-5 text-muted small'>
        <i class='fa fa-list fa-2x mb-3 opacity-25'></i><br>
        Aún no hay pasos configurados para este servicio.
    </div>";
}