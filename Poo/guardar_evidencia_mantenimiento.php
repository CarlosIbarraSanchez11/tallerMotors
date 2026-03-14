<?php
session_start();
require_once "Conexion.php";
$db = new Conexion();

// Diagnóstico de errores para desarrollo
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    if (!isset($_FILES['foto_item']) || $_FILES['foto_item']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("No se recibió la foto correctamente.");
    }

    $id_cita    = $_POST['id_cita'] ?? null;
    $id_orden   = $_POST['id_orden'] ?? null;
    
    // Capturamos la descripción enviada por el modal
    $descripcion = mysqli_real_escape_string($db->conexion, $_POST['nombre_item_texto'] ?? 'Sin descripción');

    if (!$id_cita || !$id_orden) {
        throw new Exception("Faltan identificadores de orden o cita.");
    }

    // 1. GESTIÓN DE DIRECTORIO Y ARCHIVO
    $directorio = "../img/evidencias/"; 
    if (!file_exists($directorio)) { mkdir($directorio, 0777, true); }

    $ext = pathinfo($_FILES['foto_item']['name'], PATHINFO_EXTENSION);
    $nuevo_nombre = "mante_" . $id_orden . "_" . time() . "." . $ext;

    if (move_uploaded_file($_FILES['foto_item']['tmp_name'], $directorio . $nuevo_nombre)) {
        
        // 2. INSERTAR EN LA BASE DE DATOS
        $sql = "INSERT INTO orden_evidencias (id_orden, id_cita, descripcion, foto) 
                VALUES ('$id_orden', '$id_cita', '$descripcion', '$nuevo_nombre')";
        
        if ($db->ejecutar($sql)) {

            // 🚀 3. LÓGICA DE RETORNO SEGÚN TIPO_RAIZ
            // Buscamos si el servicio original es MANTENIMIENTO o DIAGNOSTICO
            $sql_tipo = "SELECT s.tipo_raiz 
                         FROM citas ci 
                         INNER JOIN servicios s ON ci.id_servicio = s.id_servicio 
                         WHERE ci.id_cita = '$id_cita' LIMIT 1";
            
            $res_tipo = $db->ejecutar($sql_tipo);
            $dato_serv = $db->recorrer($res_tipo);
            $tipo_raiz = $dato_serv['tipo_raiz'] ?? 'MANTENIMIENTO';

            // Sinceramente, definimos la ruta según el flujo técnico
            if ($tipo_raiz === 'DIAGNOSTICO') {
                $url_retorno = "../ejecucion_diagnostico.php";
            } else {
                $url_retorno = "../gestion_taller.php";
            }

            header("Location: $url_retorno?id_cita=$id_cita&res=foto_ok");
            exit();

        } else {
            throw new Exception("Error al guardar en BD: " . mysqli_error($db->conexion));
        }
    } else {
        throw new Exception("Error al procesar el archivo en el servidor.");
    }

} catch (Exception $e) {
    echo "<div style='color:red; font-family:sans-serif; padding:20px;'>";
    echo "<h3>❌ Error Crítico:</h3>" . $e->getMessage();
    echo "<br><br><a href='javascript:history.back()'>Volver a intentar</a></div>";
}
?>