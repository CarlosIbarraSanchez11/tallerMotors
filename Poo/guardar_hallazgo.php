<?php
// Desactiva la visualización de errores HTML para que no rompan el JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

try {
    session_start();
    require_once "Conexion.php"; // Verifica que Conexion.php esté en la misma carpeta Poo/
    $db = new Conexion();

    // Validamos que lleguen los datos mínimos
    if (!isset($_POST['id_cita']) || empty($_POST['h_descripcion'])) {
        echo json_encode(['status' => 'error', 'message' => 'Faltan datos obligatorios']);
        exit;
    }

    $id_cita     = $_POST['id_cita'];
    $descripcion = $db->conexion->real_escape_string($_POST['h_descripcion']);
    $prioridad   = $_POST['h_prioridad'];
    $precio      = !empty($_POST['h_precio']) ? $_POST['h_precio'] : 0;

    // --- MANEJO DE LA IMAGEN ---
    $nombre_imagen = "";
    if (isset($_FILES['h_foto']) && $_FILES['h_foto']['error'] == 0) {
        $extension = pathinfo($_FILES['h_foto']['name'], PATHINFO_EXTENSION);
        $nombre_imagen = "hallazgo_" . $id_cita . "_" . time() . "." . $extension;
        
        // La ruta debe subir un nivel (../) porque estamos dentro de Poo/
        $ruta_destino = "../uploads/" . $nombre_imagen;
        
        // Verifica si la carpeta existe, si no, créala
        if (!is_dir('../uploads/')) {
            mkdir('../uploads/', 0777, true);
        }

        if (!move_uploaded_file($_FILES['h_foto']['tmp_name'], $ruta_destino)) {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo guardar la imagen en el servidor']);
            exit;
        }
    }

    // --- INSERTAR EN BD ---
    $sql = "INSERT INTO hallazgos (id_cita, descripcion_falla, prioridad, monto_estimado, imagen_evidencia, estado_aprobacion) 
            VALUES ('$id_cita', '$descripcion', '$prioridad', '$precio', '$nombre_imagen', 'PENDIENTE')";

    if($db->ejecutar($sql)) {
        // Generamos el HTML para la lista lateral
        $badge_color = ($prioridad == 'ALTA') ? 'danger' : 'warning';
        $html = "
        <div class='p-3 border-bottom bg-white'>
            <div class='d-flex justify-content-between mb-1'>
                <span class='badge bg-$badge_color small'>$prioridad</span>
                <span class='fw-bold text-success small'>S/ " . number_format($precio, 2) . "</span>
            </div>
            <div class='small text-dark'>$descripcion</div>
        </div>";

        echo json_encode(['status' => 'success', 'html' => $html]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al insertar en la base de datos']);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}