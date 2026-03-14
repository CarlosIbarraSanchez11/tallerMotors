<?php
// Poo/guardar_cita.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "Conexion.php";
require_once "../libs/classY.php"; 
$db = new Conexion();

if ($_POST) {
    // 🚀 DINÁMICO: Ahora el taller viene de la sesión del usuario
    $id_taller    = $_SESSION['id_taller'] ?? 1; 
    
    $id_cliente   = $_POST['id_cliente'];
    $id_vehiculo  = $_POST['id_vehiculo'];
    $id_servicio  = $_POST['id_servicio'];
    $id_tecnico   = $_POST['id_tecnico'];
    $fecha_cita   = $_POST['fecha'];
    $hora_inicio  = $_POST['hora_seleccionada'];

    try {
        // 1. Datos del servicio
        $resServ = $db->ejecutar("SELECT nombre_servicio, duracion_horas, precio_base FROM servicios WHERE id_servicio = '$id_servicio'");
        $datosS  = $db->recorrer($resServ);
        $servicio_nom = $datosS['nombre_servicio'];
        $duracion     = $datosS['duracion_horas']; // Lo tenemos por si necesitas calcular hora_fin
        $precio       = number_format($datosS['precio_base'], 2);

        // 🚀 CÁLCULO DE HORA FINAL (Para evitar que la tabla muestre NULL)
        $inicio = new DateTime($fecha_cita . ' ' . $hora_inicio);
        $fin = clone $inicio;
        $fin->add(new DateInterval('PT' . ($duracion * 60) . 'M')); 
        $hora_fin = $fin->format('H:i:s');

        // 2. Token y Registro de Cita
        $token = bin2hex(random_bytes(16));
        $sql = "INSERT INTO citas (id_taller, id_cliente, id_vehiculo, id_servicio, id_tecnico, fecha_cita, hora_inicio, hora_fin, estado, token_confirmacion, wsp_notificado) 
                VALUES ('$id_taller', '$id_cliente', '$id_vehiculo', '$id_servicio', '$id_tecnico', '$fecha_cita', '$hora_inicio', '$hora_fin', 'EN_CONFIRMACION', '$token', 1)";
        
        if ($db->ejecutar($sql)) {
            // 3. Datos del cliente
            $info = $db->ejecutar("SELECT nombre_completo, telefono FROM clientes WHERE id_cliente = '$id_cliente'");
            $d = $db->recorrer($info);

            $celular = $d['telefono'];
            $nombreC = $d['nombre_completo'];
            $f_cita  = date('d/m/Y', strtotime($fecha_cita));
            $h_cita  = date('H:i', strtotime($hora_inicio));

            // 🚀 DISPARO ÚNICO YCLOUD
            $envio = enviarConfirmacionCitaTaller($celular, $nombreC, $servicio_nom, $f_cita, $h_cita, $precio);

            // Redirigimos con códigos que el SweetAlert de tu header.php ya entiende
            if ($envio) {
                header("Location: ../clientes.php?res=cita_enviada");
            } else {
                header("Location: ../clientes.php?res=cita_ok_wsp_error");
            }
        }
    } catch (Exception $e) {
        header("Location: ../clientes.php?res=cita_error&msg=" . urlencode($e->getMessage()));
    }
}
?>