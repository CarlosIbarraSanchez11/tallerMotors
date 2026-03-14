<?php
// ver_qr.php
require_once "Poo/Conexion.php";
$db = new Conexion();

$token = isset($_GET['t']) ? $_GET['t'] : '';

if (empty($token)) {
    die("<h1 style='color:white; text-align:center; padding-top:50px;'>Error: Pase no válido.</h1>");
}

$sql = "SELECT ci.*, c.nombre_completo, v.placa, v.marca, v.modelo, s.nombre_servicio 
        FROM citas ci 
        JOIN clientes c ON ci.id_cliente = c.id_cliente 
        JOIN vehiculos v ON ci.id_vehiculo = v.id_vehiculo 
        JOIN servicios s ON ci.id_servicio = s.id_servicio
        WHERE ci.token_confirmacion = '$token'";

$res = $db->ejecutar($sql);
$d = $db->recorrer($res);

if (!$d) {
    die("<div style='background:#000; color:white; height:100vh; display:flex; align-items:center; justify-content:center; font-family:sans-serif;'><h2>PASE NO ENCONTRADO</h2></div>");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pase Dr. Motors</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #121212; font-family: 'Segoe UI', sans-serif; }
        .card-qr { background: #ffffff; border-radius: 24px; max-width: 380px; margin: 40px auto; overflow: hidden; }
        .qr-container { background: #f8f9fa; padding: 20px; border-radius: 16px; display: inline-block; }
        .badge-placa { background: #007bff; color: white; font-weight: bold; padding: 5px 12px; border-radius: 6px; }
        .data-row { border-top: 1px solid #f0f0f0; padding: 12px 0; }
        /* Estilo para el logo */
        .logo-header { 
    max-height: 55px; 
    width: auto; 
    /* Hemos quitado los filtros de brillo e inversión */
}
    </style>
</head>
<body>
    <div class="container px-3">
        <div class="card-qr shadow-2xl">
            <div class="p-4 text-center" style="background: linear-gradient(135deg, #007bff, #0056b3);">
                <img src="image/logo_taller.png" alt="Dr. Motors Logo" class="logo-header mb-2">
                <p class="text-white-50 small mb-0">Pase de Entrada Autorizado</p>
            </div>

            <div class="p-4 text-center">
                <div class="qr-container mb-4">
                    <?php 
                        // URL que el recepcionista abrirá al escanear
                        $url_recepcion = "https://www.jambosystems.com/taller/recepcion_qr.php?t=" . $token; 
                    ?>
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=<?php echo urlencode($url_recepcion); ?>" 
                        alt="QR Entrada" style="width: 180px; height: 180px;">
                </div>

                <div class="mb-4">
                    <small class="text-muted text-uppercase fw-bold d-block mb-1" style="letter-spacing: 1px;">Cliente</small>
                    <h4 class="fw-bold text-dark"><?php echo $d['nombre_completo']; ?></h4>
                </div>

                <div class="row g-0 data-row">
                    <div class="col-6 text-start">
                        <small class="text-muted d-block">Fecha</small>
                        <span class="fw-bold text-dark"><i class="fa fa-calendar-alt text-primary me-1"></i> <?php echo date('d/m/Y', strtotime($d['fecha_cita'])); ?></span>
                    </div>
                    <div class="col-6 text-end border-start ps-3">
                        <small class="text-muted d-block">Hora</small>
                        <span class="fw-bold text-dark"><i class="fa fa-clock text-primary me-1"></i> <?php echo date('H:i', strtotime($d['hora_inicio'])); ?> hrs</span>
                    </div>
                </div>

                <div class="row g-0 data-row">
                    <div class="col-7 text-start">
                        <small class="text-muted d-block">Vehículo</small>
                        <span class="fw-bold text-dark"><?php echo $d['marca'] . " " . $d['modelo']; ?></span>
                    </div>
                    <div class="col-5 text-end border-start ps-3">
                        <small class="text-muted d-block">Placa</small>
                        <span class="badge-placa"><?php echo $d['placa']; ?></span>
                    </div>
                </div>

                <div class="mt-4 p-3 bg-light rounded-3">
                    <p class="small text-muted mb-0">
                        <i class="fa fa-info-circle me-1"></i> Presenta este código al técnico para agilizar tu atención.
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>