<?php
require_once "Conexion.php";
$db = new Conexion();

// Recibimos los valores del formulario
$v_hh    = $_POST['valor_hh_soles'];
$util    = $_POST['pct_utilidad'];
$alq     = $_POST['pct_alquiler'];
$gest    = $_POST['pct_gestion'];
$mkt     = $_POST['pct_marketing'];
// Sinceramente, añadimos estos para que coincida con tu Excel de 34% G.A.
$herram  = $_POST['pct_herramientas'] ?? 2.00; 
$transp  = $_POST['pct_transporte'] ?? 5.00;

// SQL de actualización para la fila maestra
$sql = "UPDATE config_taller SET 
        valor_hh_soles = '$v_hh',
        pct_utilidad = '$util',
        pct_alquiler = '$alq',
        pct_gestion = '$gest',
        pct_marketing = '$mkt',
        pct_herramientas = '$herram',
        pct_transporte = '$transp'
        WHERE id_config = 1";

$res = $db->ejecutar($sql);

if ($res) {
    echo "ok";
} else {
    echo "Error en la actualización de la base de datos";
}
?>