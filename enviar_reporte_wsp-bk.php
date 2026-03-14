<?php
ob_start(); 
session_start();

// 1. CARGAMOS TODO
require_once "Poo/Conexion.php";
require_once "libs/class.php"; // Tu función Whapi actualizada
require_once "libs/dompdf/autoload.inc.php"; // Tu carpeta dompdf

use Dompdf\Dompdf;
use Dompdf\Options;

$db = new Conexion();
$id_cita = $_GET['id_cita'] ?? die("ID no recibido");

// 2. OBTENER DATOS (Para el nombre del archivo y el teléfono)
$sql = "SELECT cl.nombre_completo, cl.telefono, v.placa, ot.id_orden 
        FROM citas ci 
        JOIN clientes cl ON ci.id_cliente = cl.id_cliente 
        JOIN vehiculos v ON ci.id_vehiculo = v.id_vehiculo
        JOIN ordenes_trabajo ot ON ci.id_cita = ot.id_cita
        WHERE ci.id_cita = '$id_cita' LIMIT 1";
$d = $db->recorrer($db->ejecutar($sql));

if (!$d) die("No hay datos para esta cita.");

// 3. GENERAR EL HTML (Sin que pida sesión)
// TRUCO: En lugar de file_get_contents, usamos un "buffer" o llamamos al archivo
// Para este ejemplo, asumiremos que creaste 'ver_orden_cliente.php' (la versión sin sesión)
$protocolo = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
$url_base = $protocolo . "://" . $_SERVER['HTTP_HOST'] . str_replace("enviar_pdf_whapi.php", "", $_SERVER['REQUEST_URI']);
$url_final = $url_base . "ver_orden_cliente.php?id_cita=" . $id_cita;

$html = file_get_contents($url_final); // Captura el diseño

// 4. CONFIGURAR Y CREAR EL PDF
$options = new Options();
$options->set('isRemoteEnabled', true); // Para que cargue tu logo y fotos
$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// 5. GUARDAR EL ARCHIVO TEMPORALMENTE
$nombre_pdf = "Reporte_DrMotors_" . $d['placa'] . ".pdf";
$ruta_destino = "temp_pdfs/" . $nombre_pdf;

// Crea la carpeta si no existe
if (!file_exists('temp_pdfs')) { mkdir('temp_pdfs', 0777, true); }

file_put_contents($ruta_destino, $dompdf->output());

// 6. ENVIAR POR WHAPI COMO DOCUMENTO
$url_archivo_publico = $url_base . $ruta_destino;
$mensaje = "🚗 *DR. MOTORS* \nHola {$d['nombre_completo']}, aquí tienes el reporte técnico de tu unidad *{$d['placa']}*.";

// Usamos tu función de class.php con el tipo "document"
$resultado = enviarNotificacionWhapi($d['telefono'], $mensaje, $url_archivo_publico, "document");

// 7. FINALIZAR
if ($resultado) {
    header("Location: citas.php?res=enviado_ok");
} else {
    echo "Error al enviar el archivo físico.";
}
ob_end_flush();