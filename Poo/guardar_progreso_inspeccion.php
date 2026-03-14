<?php
// Poo/procesar_inspeccion.php
session_start();
require_once "Conexion.php";
require_once "../libs/classY.php"; // 👈 Cambiado a tu librería de YCloud

$db = new Conexion();

try {
    $id_cita = $_POST['id_cita'];
    $id_orden = $_POST['id_orden'];
    $estados = $_POST['estado'] ?? [];
    $fotos = $_FILES['foto_punto'] ?? null;

    // 1. CONSULTA UNIFICADA: Traemos datos de notificación Y el tipo de servicio
    $sql_cita = "SELECT c.token_confirmacion, c.notificado_inspeccion, cl.telefono, cl.nombre_completo, s.tipo_raiz
                FROM citas c 
                INNER JOIN clientes cl ON c.id_cliente = cl.id_cliente 
                INNER JOIN servicios s ON c.id_servicio = s.id_servicio
                WHERE c.id_cita = '$id_cita' LIMIT 1";

    $res_cita = $db->ejecutar($sql_cita);
    $datos_cita = $db->recorrer($res_cita);

    if (!$datos_cita) throw new Exception("Cita no encontrada.");

    $tipo_raiz = $datos_cita['tipo_raiz'] ?? 'PREVENTIVO';

    // 2. 🚀 LÓGICA DE NOTIFICACIÓN YCLOUD (Solo la primera vez)
    if ($datos_cita['notificado_inspeccion'] == 0) {
        $telefono = $datos_cita['telefono']; 
        $nombre = $datos_cita['nombre_completo'];
        $token = $datos_cita['token_confirmacion'];
        
        // Disparamos la plantilla con el botón dinámico que creaste
        // Sinceramente, esto usará el saldo de tus $10 de forma eficiente
        if (enviarTemplateSeguimientoTaller($telefono, $nombre, $token)) {
            $db->ejecutar("UPDATE citas SET notificado_inspeccion = 1 WHERE id_cita = '$id_cita'");
        }
    }

    // 3. PROCESAMIENTO DE ESTADOS Y FOTOS (Se mantiene igual)
    $carpeta = "../img/evidencias/";
    if (!file_exists($carpeta)) mkdir($carpeta, 0777, true);

    foreach ($estados as $id_paso => $valor_estado) {
    $tiene_foto_nueva = isset($fotos['name'][$id_paso]) && $fotos['error'][$id_paso] === UPLOAD_ERR_OK;
    
    $check_previo = $db->recorrer($db->ejecutar("SELECT foto_evidencia FROM inspeccion_resultados WHERE id_orden = '$id_orden' AND id_paso = '$id_paso'"));
    $tiene_foto_previa = !empty($check_previo['foto_evidencia']);

    // 🚀 NUEVA REGLA: 
    // Si el estado es 'NO_TIENE', pasa directo.
    // Si es cualquier otro, exige foto nueva o previa.
    if ($valor_estado === 'NO_TIENE' || (!empty($valor_estado) && ($tiene_foto_nueva || $tiene_foto_previa))) {
        
        $nombre_foto_db = null;
        if ($tiene_foto_nueva) {
            $nombre_archivo = "evid_" . $id_orden . "_" . $id_paso . "_" . time() . ".jpg";
            if (move_uploaded_file($fotos['tmp_name'][$id_paso], $carpeta . $nombre_archivo)) {
                $nombre_foto_db = $nombre_archivo;
            }
        }

        // Lógica de INSERT / UPDATE (se mantiene igual que antes)
        if ($db->contar($db->ejecutar("SELECT id_resultado FROM inspeccion_resultados WHERE id_orden = '$id_orden' AND id_paso = '$id_paso'")) > 0) {
            $sql_foto = ($nombre_foto_db) ? ", foto_evidencia = '$nombre_foto_db'" : "";
            $db->ejecutar("UPDATE inspeccion_resultados SET estado = '$valor_estado' $sql_foto WHERE id_orden = '$id_orden' AND id_paso = '$id_paso'");
        } else {
            $db->ejecutar("INSERT INTO inspeccion_resultados (id_orden, id_cita, id_paso, estado, foto_evidencia) 
                           VALUES ('$id_orden', '$id_cita', '$id_paso', '$valor_estado', '$nombre_foto_db')");
        }
    }
}

    // 4. REDIRECCIÓN INTELIGENTE
    $destino = ($tipo_raiz === 'DIAGNOSTICO') ? "ejecucion_diagnostico.php" : "gestion_taller.php";
    header("Location: ../$destino?id_cita=$id_cita&status=success");

} catch (Exception $e) {
    $fallback = (isset($tipo_raiz) && $tipo_raiz === 'DIAGNOSTICO') ? "ejecucion_diagnostico.php" : "gestion_taller.php";
    header("Location: ../$fallback?id_cita=$id_cita&status=error&msg=" . urlencode($e->getMessage()));
}
exit();