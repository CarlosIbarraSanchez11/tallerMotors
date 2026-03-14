<?php
require_once "Conexion.php";
$db = new Conexion();

if (isset($_POST['id_cliente'])) {
    $id_c = mysqli_real_escape_string($db->conexion, $_POST['id_cliente']);
    
    $sql = "SELECT id_vehiculo, placa, marca, modelo FROM vehiculos WHERE id_cliente = '$id_c'";
    $res = $db->ejecutar($sql);
    
    echo '<option value="" disabled selected>Seleccione vehículo...</option>';
    if ($db->conexion->affected_rows > 0) {
        while ($v = $db->recorrer($res)) {
            echo '<option value="' . $v['id_vehiculo'] . '">' . $v['placa'] . ' - ' . $v['marca'] . ' ' . $v['modelo'] . '</option>';
        }
    } else {
        echo '<option value="">Sin vehículos registrados</option>';
    }
}
?>