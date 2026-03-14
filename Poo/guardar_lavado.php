<?php
session_start();
// 1. Capturamos al responsable de la limpieza (Nivel LIMPIEZA o ADMIN)
$id_usuario_actual = $_SESSION['id_usuario'] ?? null;

require_once "Conexion.php"; 
require_once "../libs/classY.php"; // Tu librería actualizada con enviarTemplateEntregaVehiculo

$db = new Conexion();

// Diagnóstico de errores (solo para desarrollo)
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_POST) {
    $id_cita = mysqli_real_escape_string($db->conexion, $_POST['id_cita']);
    $id_orden = mysqli_real_escape_string($db->conexion, $_POST['id_orden']);
    
    // 🚀 2. OBTENER INFORMACIÓN PARA EL WHATSAPP Y LA REDIRECCIÓN
    $sql_info = "SELECT ci.fecha_cita, ci.token_confirmacion, cl.nombre_completo, cl.telefono, v.placa 
                 FROM citas ci 
                 JOIN clientes cl ON ci.id_cliente = cl.id_cliente 
                 JOIN vehiculos v ON ci.id_vehiculo = v.id_vehiculo
                 WHERE ci.id_cita = '$id_cita' LIMIT 1";
    
    $res_info = $db->ejecutar($sql_info);
    $datos = $db->recorrer($res_info);
    
    if (!$datos) { die("Error: No se encontró información de la cita."); }

    $fecha_retorno = $datos['fecha_cita'];
    $telefono = $datos['telefono'];
    $nombre_cliente = explode(' ', $datos['nombre_completo'])[0]; // Solo el primer nombre
    $placa = $datos['placa'];
    $token = $datos['token_confirmacion'];

    // 🚀 3. PROCESAR FOTO FINAL DEL VEHÍCULO
    $nombre_foto = "";
    if (!empty($_FILES['foto_lavado']['name'])) {
        $dir = "../uploads/lavado/"; 
        if (!file_exists($dir)) { mkdir($dir, 0777, true); }

        $ext = pathinfo($_FILES['foto_lavado']['name'], PATHINFO_EXTENSION);
        $nombre_foto = "final_" . $id_orden . "_" . time() . "." . $ext;
        
        move_uploaded_file($_FILES['foto_lavado']['tmp_name'], $dir . $nombre_foto);
    }

    // 🚀 4. ACTUALIZACIÓN MAESTRA DE BASE DE DATOS
    // Guardamos la foto y quién fue el encargado de dejarlo reluciente
    $sql_ot = "UPDATE ordenes_trabajo SET 
                foto_lavado = '$nombre_foto', 
                id_usuario_limpieza = '$id_usuario_actual',
                estado_orden = 'FINALIZADO' 
               WHERE id_orden = '$id_orden'";
    $db->ejecutar($sql_ot);

    // Pasamos la cita a 'POR ENTREGAR' para que Facturación tome el mando
    $db->ejecutar("UPDATE citas SET estado = 'POR ENTREGAR' WHERE id_cita = '$id_cita'");

    // 🚀 5. NOTIFICACIÓN YCLOUD (Plantilla: entrega_vehiculo_taller)
    // Sinceramente, usamos tu nueva función con botones dinámicos
    enviarTemplateEntregaVehiculo($telefono, $nombre_cliente, $placa, $id_cita, $token);

    // 🚀 6. REDIRECCIÓN INTELIGENTE
    // Regresamos al panel de citas filtrando por la fecha original del trabajo
    header("Location: ../citas.php?fecha=$fecha_retorno&res=finalizado");
    exit();
} else {
    header("Location: ../citas.php");
    exit();
}
?>