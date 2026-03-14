<?php
session_start();
require_once "Conexion.php";
$db = new Conexion();

// Reporte de errores para desarrollo
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    if (!isset($_POST['id_orden']) || !isset($_POST['id_cita'])) {
        throw new Exception("Faltan datos de identificación de la orden.");
    }

    $id_orden = $_POST['id_orden'];
    $id_cita  = $_POST['id_cita'];
    $punto    = mysqli_real_escape_string($db->conexion, $_POST['punto_falla']);
    $desc     = mysqli_real_escape_string($db->conexion, $_POST['descripcion']);
    $id_prod  = !empty($_POST['id_producto']) ? $_POST['id_producto'] : "NULL";
    
    $p_venta  = !empty($_POST['precio_producto']) ? $_POST['precio_producto'] : 0;
    $p_obra   = !empty($_POST['precio_mano_obra']) ? $_POST['precio_mano_obra'] : 0;
    $cantidad = !empty($_POST['cantidad']) ? $_POST['cantidad'] : 1;

    // 1. GESTIÓN DE FOTO
    $nombre_foto = null;
    if (isset($_FILES['foto_falla']) && $_FILES['foto_falla']['error'] === UPLOAD_ERR_OK) {
        $directorio = "../img/evidencias/";
        if (!file_exists($directorio)) { mkdir($directorio, 0777, true); }

        $ext = pathinfo($_FILES['foto_falla']['name'], PATHINFO_EXTENSION);
        $nombre_foto = "falla_" . $id_orden . "_" . time() . "." . $ext;
        
        if (!move_uploaded_file($_FILES['foto_falla']['tmp_name'], $directorio . $nombre_foto)) {
            throw new Exception("No se pudo guardar la imagen.");
        }
    }

    // 2. INSERTAR EL HALLAZGO
    $sql = "INSERT INTO orden_hallazgos (
                id_orden, id_cita, punto_falla, descripcion, 
                id_producto, cantidad, precio_producto, precio_mano_obra, foto_evidencia
            ) VALUES (
                '$id_orden', '$id_cita', '$punto', '$desc', 
                $id_prod, '$cantidad', '$p_venta', '$p_obra', '$nombre_foto'
            )";

    if ($db->ejecutar($sql)) {
        
        // 🚀 3. LÓGICA DE REDIRECCIÓN MAESTRA
        // Buscamos el tipo_raiz del servicio que originó la cita
        $sql_tipo = "SELECT s.tipo_raiz 
                     FROM citas ci 
                     INNER JOIN servicios s ON ci.id_servicio = s.id_servicio 
                     WHERE ci.id_cita = '$id_cita' LIMIT 1";
        
        $res_tipo = $db->ejecutar($sql_tipo);
        $dato_serv = $db->recorrer($res_tipo);
        $tipo_raiz = $dato_serv['tipo_raiz'] ?? 'MANTENIMIENTO'; // Valor por defecto

        // Definimos la ruta según el tipo de servicio
        // Sinceramente, si es DIAGNOSTICO va a una página, si es MANTENIMIENTO a otra
        if ($tipo_raiz === 'DIAGNOSTICO') {
            $url_retorno = "../ejecucion_diagnostico.php";
        } else {
            // MANTENIMIENTO (Menor, Mayor, Gama Alta, etc) regresan aquí
            $url_retorno = "../gestion_taller.php";
        }

        header("Location: $url_retorno?id_cita=$id_cita&res=hallazgo_guardado");
        exit();

    } else {
        throw new Exception("Error al insertar el hallazgo en la base de datos.");
    }

} catch (Exception $e) {
    echo "Error crítico: " . $e->getMessage();
}