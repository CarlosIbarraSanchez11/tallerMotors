<?php
session_start();
require_once "Poo/Conexion.php";
$db = new Conexion();

// 1. FUNCIÓN PARA ELIMINAR EL "SÃ¡nchez" (UTF-8 Blindado)
function txt($texto) {
    if (!$texto) return "";
    return mb_convert_encoding($texto, 'UTF-8', 'auto');
}

$id_cita = $_GET['id_cita'] ?? die("ID no recibido");

// CONSULTA DE DATOS (Maestra + Orden)
$sql = "SELECT ci.*, v.placa, v.marca, v.modelo, cl.nombre_completo, cl.telefono,
               ot.id_orden, ot.km_ingreso, ot.fecha_creacion, s.nombre_servicio, s.precio_base
        FROM citas ci
        LEFT JOIN vehiculos v ON ci.id_vehiculo = v.id_vehiculo
        LEFT JOIN clientes cl ON ci.id_cliente = cl.id_cliente
        LEFT JOIN ordenes_trabajo ot ON ci.id_cita = ot.id_cita
        LEFT JOIN servicios s ON ci.id_servicio = s.id_servicio
        WHERE ci.id_cita = '$id_cita' LIMIT 1";

$res_m = $db->ejecutar($sql);
$d = $db->recorrer($res_m);

// LÓGICA DE PRECIOS
$items_ok = [];

// Item 1: El servicio principal
$items_ok[] = [
    'cant' => 1.00, 
    'desc' => txt($d['nombre_servicio']), 
    'p_unit' => $d['precio_base'],
    'es_kit' => false
];

// Item 2: Repuestos adicionales
$sql_rep = "SELECT pr.*, p.nombre_producto, p.precio_mano_obra 
            FROM pedidos_repuestos pr 
            JOIN productos p ON pr.id_producto = p.id_producto 
            WHERE pr.id_cita = '$id_cita' AND pr.estado_pedido != 'RECHAZADO'";
$res_rep = $db->ejecutar($sql_rep);

while($r = $db->recorrer($res_rep)){
    $es_kit = ($r['precio_unidad'] <= 0);
    $precio_final = $es_kit ? 0.00 : ($r['precio_unidad'] + $r['precio_mano_obra']);

    $items_ok[] = [
        'cant' => $r['cantidad'], 
        'desc' => txt($r['nombre_producto']), 
        'p_unit' => $precio_final,
        'es_kit' => $es_kit
    ];
}

// ITEMS RECHAZADOS
$items_no = [];
$sql_rechazo = "SELECT h.*, p.nombre_producto 
                FROM orden_hallazgos h 
                LEFT JOIN productos p ON h.id_producto = p.id_producto 
                WHERE h.id_cita = '$id_cita' AND h.estado_aprobacion = 'RECHAZADO'";
$res_rechazo = $db->ejecutar($sql_rechazo);
while($rh = $db->recorrer($res_rechazo)){
    $items_no[] = [
        'desc' => !empty($rh['nombre_producto']) ? txt($rh['nombre_producto']) : txt($rh['punto_falla']),
        'precio' => $rh['precio_producto']
    ];
}

$subtotal = 0;
foreach($items_ok as $it) { 
    $subtotal += ($it['cant'] * $it['p_unit']); 
}
$igv = $subtotal * 0.18;
$total_final = $subtotal + $igv;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cotización Dr. Motors #COT-<?php echo str_pad($d['id_orden'], 6, "0", STR_PAD_LEFT); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; background: #f4f4f4; color: #333; }
        .invoice-box { max-width: 850px; margin: 20px auto; padding: 30px; background: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .header-box { border: 2px solid #333; padding: 15px; text-align: center; border-radius: 10px; }
        
        .header-logo {
            max-height: 55px;
            width: auto;
            margin-bottom: 12px;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
        }

        .table-main thead { background: #333; color: #fff; }
        .section-title { font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #eee; padding-bottom: 5px; margin-bottom: 10px; font-size: 0.9rem; }
        
        /* 🚀 AQUÍ ESTÁ EL CAMBIO: REDUCIMOS EL ANCHO (WIDTH) A 40% */
        .box-rechazo-ancho-reducido {
            width: 40%; 
            padding: 8px 12px !important; 
            border: 1px solid rgba(220, 53, 69, 0.2) !important;
            background-color: rgba(220, 53, 69, 0.02) !important;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .table-rechazo td { font-size: 11px !important; padding: 0 !important; line-height: 1.2; }
        .text-rechazo-label { color: #dc3545; font-size: 10px; font-weight: 800; text-transform: uppercase; margin-bottom: 3px; display: block; }

        @media print { .no-print { display: none; } body { background: #fff; } .invoice-box { margin: 0; box-shadow: none; padding: 10px; } }
    </style>
</head>
<body onload="window.print()">

<div class="invoice-box">
    <div class="row mb-4 align-items-center">
        <div class="col-7">
            <img src="image/logo_taller.png" alt="Logo" class="header-logo">
            <p class="small mb-0">RUC: 20512345678 | Lima, Perú | Tel: 999-999-999</p>
        </div>
        <div class="col-5 text-end">
            <div class="header-box">
                <h4 class="mb-0 fw-bold">COTIZACIÓN</h4>
                <p class="mb-0 small text-muted">N° COT-<?php echo str_pad($d['id_orden'], 6, "0", STR_PAD_LEFT); ?></p>
                <p class="mb-0 small fw-bold">Fecha: <?php echo date('d/m/Y'); ?></p>
            </div>
        </div>
    </div>

    <div class="bg-light p-3 rounded-3 mb-4 border shadow-sm">
        <div class="row">
            <div class="col-6"><small class="text-muted d-block fw-bold" style="font-size: 10px;">Cliente</small><p class="mb-0 fw-bold"><?php echo txt($d['nombre_completo']); ?></p></div>
            <div class="col-6 text-end"><small class="text-muted d-block fw-bold" style="font-size: 10px;">Vehículo / Placa</small><p class="mb-0 fw-bold"><?php echo txt($d['marca'])." ".txt($d['modelo']); ?> | <?php echo $d['placa']; ?></p></div>
        </div>
    </div>

    <div class="section-title text-primary"><i class="fa fa-check-circle me-2"></i>Servicios y Repuestos Autorizados</div>
    <table class="table table-sm table-main table-hover align-middle mb-3">
        <thead>
            <tr class="small">
                <th width="10%">Cant.</th>
                <th>Descripción del Servicio</th>
                <th width="15%" class="text-end">P.Unit</th>
                <th width="15%" class="text-end">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($items_ok as $it): $imp = $it['cant'] * $it['p_unit']; ?>
            <tr class="small">
                <td><?php echo number_format($it['cant'], 2); ?></td>
                <td><?php echo $it['desc']; ?><?php if($it['es_kit']) echo ' <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25" style="font-size: 9px; padding: 2px 6px;">INCLUIDO EN KIT</span>'; ?></td>
                <td class="text-end"><?php echo ($it['p_unit'] > 0) ? 'S/ '.number_format($it['p_unit'], 2) : '<b class="text-success small">INCLUIDO</b>'; ?></td>
                <td class="text-end fw-bold"><?php echo ($imp > 0) ? 'S/ '.number_format($imp, 2) : 'S/ 0.00'; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if(count($items_no) > 0): ?>
    <div style="margin-top: 10px;">
        <span class="text-rechazo-label"><i class="fa fa-exclamation-triangle me-1"></i> Pendientes (No Autorizados)</span>
        <div class="box-rechazo-ancho-reducido shadow-sm">
            <table class="table table-borderless table-sm mb-0 table-rechazo">
                <tbody>
                    <?php foreach($items_no as $no): ?>
                    <tr>
                        <td class="text-muted">• <?php echo $no['desc']; ?></td>
                        <td class="text-end text-muted italic" style="font-size: 10px;">Ref: S/ <?php echo number_format($no['precio'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <div class="row justify-content-end mt-3">
        <div class="col-5">
            <table class="table table-sm table-borderless">
                <tr class="small"><td class="text-muted">Subtotal:</td><td class="text-end">S/ <?php echo number_format($subtotal, 2); ?></td></tr>
                <tr class="small"><td class="text-muted">IGV (18%):</td><td class="text-end">S/ <?php echo number_format($igv, 2); ?></td></tr>
                <tr class="border-top"><td class="fw-bold h5">TOTAL:</td><td class="text-end fw-bold h5 text-primary">S/ <?php echo number_format($total_final, 2); ?></td></tr>
            </table>
        </div>
    </div>

    <div class="row mt-5">
        <div class="col-6 text-center"><div style="border-top: 1px solid #ccc; width: 180px; margin: 40px auto 5px;"></div><p class="small text-muted fw-bold">Dr. Motors</p></div>
        <div class="col-6 text-center"><div style="border-top: 1px solid #ccc; width: 180px; margin: 40px auto 5px;"></div><p class="small text-muted fw-bold">Firma del Cliente</p></div>
    </div>
</div>

<div class="text-center no-print my-4">
    <button onclick="window.print()" class="btn btn-primary px-5 shadow rounded-pill fw-bold">
        <i class="fa fa-print me-2"></i>Imprimir Cotización
    </button>
</div>

</body>
</html>