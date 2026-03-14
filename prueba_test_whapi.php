<?php
require_once "libs/class.php";

// Pon AQUÍ tu número real con 51 adelante para probar
$mi_numero = "51989748367"; 
$test_msg = "Prueba de conexión desde Dr. Motors 🚗💨";

echo "<h3>Enviando prueba a $mi_numero...</h3>";

$res = enviarNotificacionWhapi($mi_numero, $test_msg);

if ($res) {
    echo "<b>Respuesta de la API:</b><br><pre>";
    print_r(json_decode($res, true));
    echo "</pre>";
} else {
    echo "<b style='color:red;'>Error total: No se pudo conectar con Whapi.</b>";
}
?>