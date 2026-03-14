<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Poo/test_envio_whapi.php
require_once "libs/class.php"; // Cargamos tu función

$miCelular = "51921872052"; // Tu número de prueba
$mensajeTest = "✅ *¡Dr. Motors Informa!* \n\nPrueba de Whapi exitosa. Sin cobros de Meta. 🚗💨";

// Prueba 1: Envío de Texto simple
echo "Enviando texto...<br>";
$resTexto = enviarNotificacionWhapi($miCelular, $mensajeTest);
echo "Resultado Texto: " . $resTexto . "<br><br>";

// Prueba 2: Envío con Imagen (Opcional)
// echo "Enviando imagen...<br>";
// $urlImg = "https://tu-web.com/taller/img/logo_dr_motors.png";
// $resImagen = enviarNotificacionWhapi($miCelular, "Tu carro está listo", $urlImg);
// echo "Resultado Imagen: " . $resImagen;
?>