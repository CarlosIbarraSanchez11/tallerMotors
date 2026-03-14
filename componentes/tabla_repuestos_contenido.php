<?php
// 1. Diagnóstico y Conexión
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . "/../Poo/Conexion.php";
$db = new Conexion();

$id_cita = $_GET['id_cita'] ?? null;

if (!$id_cita) {
    header('Content-Type: application/json');
    echo json_encode(["html" => "<tr><td colspan='6' class='text-center'>Error de ID</td></tr>", "total_repuestos" => 0]);
    exit;
}

// 2. Inicializamos contadores y sumatoria
$n_kit = 1;
$n_hallazgo = 1;
$total_repuestos = 0;

// 3. Iniciamos captura del HTML
ob_start(); 

$sql_pedidos = "SELECT pr.*, p.nombre_producto 
                FROM pedidos_repuestos pr 
                JOIN productos p ON pr.id_producto = p.id_producto
                WHERE pr.id_cita = '$id_cita'
                ORDER BY pr.id_pedido ASC"; 

$res_i = $db->ejecutar($sql_pedidos);
?>

<table class="table table-hover align-middle mb-0">
    <thead class="bg-light small text-muted">
        <tr>
            <th class="ps-4">Repuesto</th>
            <th>Cant.</th>
            <th>Estado</th> 
            <th>Precio</th>
            <th>Subtotal</th>
            <th class="text-end pe-4">Acción</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($db->contar($res_i) > 0): ?>
            <?php while($i = $db->recorrer($res_i)): 
                $sub = $i['cantidad'] * $i['precio_unidad'];
                $procedencia = $i['tipo_procedencia']; 
                
                // LÓGICA DE SUMA: Sumamos todo lo que no esté RECHAZADO (Aquí entran los Hallazgos)
                if($i['estado_pedido'] != 'RECHAZADO') {
                    $total_repuestos += $sub;
                }

                // Lógica de etiquetas
                if ($procedencia == 'HALLAZGO') {
                    $etiqueta = "HALLAZGO #" . $n_hallazgo++;
                    $badge_procedencia = "bg-danger bg-opacity-10 text-danger";
                    $icono = "fa-search-plus";
                } else {
                    $etiqueta = "KIT DE SERVICIO #" . $n_kit++;
                    $badge_procedencia = "bg-secondary bg-opacity-10 text-secondary";
                    $icono = "fa-box";
                }

                $badge_estado = "bg-warning text-dark";
                if($i['estado_pedido'] == 'ENTREGADO' || $i['estado_pedido'] == 'RECIBIDO') $badge_estado = "bg-success text-white";
                if($i['estado_pedido'] == 'SOLICITADO POR CLIENTE') $badge_estado = "bg-primary text-white";
                if($i['estado_pedido'] == 'RECHAZADO') $badge_estado = "bg-danger text-white";
            ?>
            <tr>
                <td class="ps-4">
                    <div class="fw-bold small text-dark"><?php echo $i['nombre_producto']; ?></div>
                    <span class="badge <?php echo $badge_procedencia; ?> x-small" style="font-size: 9px;">
                        <i class="fa <?php echo $icono; ?> me-1"></i><?php echo $etiqueta; ?>
                    </span>
                    <?php if($i['precio_unidad'] == 0 && $procedencia == 'KIT'): ?>
                        <span class="badge bg-success bg-opacity-10 text-white x-small ms-2">INCLUIDO</span>
                    <?php endif; ?>
                </td>
                <td class="text-center"><?php echo number_format($i['cantidad'], 0); ?></td>
                <td class="text-center">
                    <span class="badge <?php echo $badge_estado; ?> rounded-pill small">
                        <?php echo $i['estado_pedido']; ?>
                    </span>
                </td>
                <td class="text-center">
                    <?php echo ($i['precio_unidad'] == 0 && $procedencia == 'KIT') ? '<span class="text-muted">---</span>' : 'S/ ' . number_format($i['precio_unidad'], 2); ?>
                </td>
                <td class="fw-bold text-dark text-center">S/ <?php echo number_format($sub, 2); ?></td>
                <td class="text-end pe-4">
                    <?php if($i['estado_pedido'] == 'SOLICITADO' || $i['estado_pedido'] == 'SOLICITADO POR CLIENTE'): ?>
                        <a href="Poo/eliminar_pedido.php?id_pedido=<?php echo $i['id_pedido']; ?>&id_cita=<?php echo $id_cita; ?>" 
                           class="btn btn-sm btn-light text-danger rounded-circle shadow-sm">
                            <i class="fa fa-times"></i>
                        </a>
                    <?php else: ?>
                        <i class="fa fa-lock text-muted opacity-50"></i>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6" class="text-center py-4 text-muted small">No hay repuestos registrados.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<?php
// 4. Finalizamos captura
$html_tabla = ob_get_clean();

// LA CORRECCIÓN: Solo enviamos JSON si es una petición de recarga (AJAX)
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    echo json_encode([
        "html" => $html_tabla,
        "total_repuestos" => $total_repuestos,
        "total_formateado" => number_format($total_repuestos, 2)
    ]);
    exit; // Detenemos la ejecución para que no envíe nada más
}

// Si NO es AJAX (es el include inicial), simplemente imprimimos el HTML
echo $html_tabla;
?>