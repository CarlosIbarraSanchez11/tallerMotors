<?php
// recepcion_qr.php
require_once "Poo/Conexion.php";
$db = new Conexion();

function txt($texto) {
    if (!$texto) return "";
    return mb_convert_encoding($texto, 'UTF-8', 'auto');
}

$token = isset($_GET['t']) ? $_GET['t'] : '';
if (empty($token)) { die("Token no proporcionado."); }

// 1. CONSULTA AMPLIADA: Traemos el tipo de servicio y el precio
$sql = "SELECT ci.id_cita, ci.fecha_cita, ci.hora_inicio, c.nombre_completo, 
               v.placa, v.marca, v.modelo, s.nombre_servicio, s.precio_base, 
               s.tipo_raiz, ci.estado
        FROM citas ci 
        JOIN clientes c ON ci.id_cliente = c.id_cliente 
        JOIN vehiculos v ON ci.id_vehiculo = v.id_vehiculo 
        JOIN servicios s ON ci.id_servicio = s.id_servicio
        WHERE ci.token_confirmacion = '$token' LIMIT 1";

$res = $db->ejecutar($sql);
$d = $db->recorrer($res);

if (!$d) { die("Cita no encontrada."); }

$fecha_cita_db = $d['fecha_cita']; 

if (isset($_POST['iniciar'])) {
    header("Location: citas.php?fecha=" . $fecha_cita_db); 
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles de Ingreso - Dr. Motors</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .card-custom { border-radius: 25px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); border: none; }
        .bg-price { background-color: #f0fdf4; border: 1px dashed #22c55e; border-radius: 15px; }
        .service-badge { font-size: 10px; letter-spacing: 1px; padding: 5px 12px; }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                <div class="card card-custom overflow-hidden">
                    <div class="bg-primary p-4 text-center text-white">
                        <div class="mb-2 opacity-75 small fw-bold">ORDEN DE INGRESO</div>
                        <h1 class="fw-bold m-0" style="letter-spacing: -1px;"><?php echo $d['placa']; ?></h1>
                    </div>
                    
                    <div class="card-body p-4 text-center">
                        <p class="text-muted mb-4"><?php echo txt($d['marca'] . " " . $d['modelo']); ?></p>

                        <div class="text-start mb-4">
                            <label class="text-muted small fw-bold text-uppercase d-block mb-1">Cliente</label>
                            <div class="fw-bold text-dark"><i class="fa fa-user-circle me-2 text-primary"></i><?php echo txt($d['nombre_completo']); ?></div>
                        </div>

                        <div class="text-start bg-light p-3 rounded-4 mb-3 border">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <label class="text-muted small fw-bold text-uppercase">Servicio Programado</label>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill service-badge">
                                    <?php echo $d['tipo_raiz']; ?>
                                </span>
                            </div>
                            <div class="fw-bold text-dark fs-5 mb-0"><?php echo txt($d['nombre_servicio']); ?></div>
                        </div>

                        <div class="row g-2 mb-4 text-start">
                            <div class="col-6">
                                <div class="p-3 border rounded-4 bg-white h-100">
                                    <label class="text-muted small fw-bold d-block mb-1 text-uppercase">Costo Base</label>
                                    <div class="fw-bold text-success fs-5">S/ <?php echo number_format($d['precio_base'], 2); ?></div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 border rounded-4 bg-white h-100">
                                    <label class="text-muted small fw-bold d-block mb-1 text-uppercase">Programación</label>
                                    <div class="fw-bold text-dark small">
                                        <i class="fa fa-calendar me-1"></i> <?php echo date('d/m', strtotime($d['fecha_cita'])); ?><br>
                                        <i class="fa fa-clock me-1"></i> <?php echo date('H:i', strtotime($d['hora_inicio'])); ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <form method="POST">
                            <button type="submit" name="iniciar" class="btn btn-primary btn-lg w-100 rounded-pill py-3 fw-bold shadow-sm mb-3">
                                <i class="fa fa-arrow-right me-2"></i> REGISTRAR INGRESO
                            </button>
                        </form>

                        <a href="citas.php?fecha=<?php echo $fecha_cita_db; ?>" class="text-decoration-none text-muted small fw-bold">
                            <i class="fa fa-calendar-day me-1"></i> Ir directamente a la agenda
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>