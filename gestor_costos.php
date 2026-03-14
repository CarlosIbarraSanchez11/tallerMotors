<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "Poo/Conexion.php";
$db = new Conexion();

// 1. Cargamos los porcentajes de la Matriz Maestra (Gastos y Utilidad)
$resConfig = $db->ejecutar("SELECT * FROM config_taller WHERE id_config = 1");
$config = $db->recorrer($resConfig);

if (!$config) { die("Error crítico: No se encontró la configuración de la matriz."); }

// Sumatoria de porcentajes de gastos operativos
$suma_pct_gastos = (float)($config['pct_alquiler'] ?? 0) + 
                   (float)($config['pct_gestion'] ?? 0) + 
                   (float)($config['pct_herramientas'] ?? 0) + 
                   (float)($config['pct_marketing'] ?? 0) + 
                   (float)($config['pct_transporte'] ?? 0);
$utilidad_pct = (float)($config['pct_utilidad'] ?? 30);

include 'master/header.php';
?>

<style>
    :root { 
        --primary-color: #2563eb; 
        --success-color: #10b981; 
        --dark-slate: #1e293b; 
    }
    body { background-color: #f8fafc; font-family: 'Inter', sans-serif; }
    
    .glass-card { 
        background: white; 
        border-radius: 1.25rem; 
        border: 1px solid rgba(226, 232, 240, 0.8); 
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05); 
    }

    .table thead th { 
        background-color: #f1f5f9; 
        color: #64748b; 
        font-weight: 700; 
        text-transform: uppercase; 
        font-size: 0.7rem; 
        padding: 1rem; 
        border: none; 
    }

    /* 🕒 Estilo corregido para el tiempo (Ya no se verá blanco) */
    .badge-time { 
        background-color: #e0f2fe; 
        color: #0369a1; 
        border: 1px solid #bae6fd;
        font-weight: 700;
        padding: 0.5rem 0.8rem;
    }

    /* 👥 Estilo para Técnicos */
    .badge-tecnicos {
        background-color: #f0fdf4;
        color: #166534;
        border: 1px solid #bbf7d0;
        font-weight: 700;
        padding: 0.5rem 0.8rem;
    }

    .price-tag { color: var(--success-color); font-weight: 800; font-size: 1.15rem; }
    
    .input-premium { 
        border-radius: 0.75rem; 
        padding: 0.75rem; 
        border: 2px solid #f1f5f9; 
        font-weight: 600;
    }
    .input-premium:focus { border-color: var(--primary-color); outline: none; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
</style>

<div class="container-fluid px-4 py-5">
    <div class="row align-items-center mb-5">
        <div class="col-md-6">
            <h2 class="fw-bold text-dark m-0">Gestor de Costos Maestros</h2>
            <p class="text-muted mb-0">Configuración individual de Mano de Obra, Costo HH y Personal.</p>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <div class="d-inline-flex align-items-center bg-white p-2 rounded-4 shadow-sm border">
                <div class="bg-success bg-opacity-10 p-2 rounded-3 me-3">
                    <i class="fa fa-percent text-success"></i>
                </div>
                <div class="text-start pe-3">
                    <small class="text-muted d-block lh-1">Matriz de Rentabilidad</small>
                    <span class="fw-bold text-dark"><?php echo $suma_pct_gastos; ?>% Gastos + <?php echo $utilidad_pct; ?>% Utilidad</span>
                </div>
            </div>
        </div>
    </div>

    <div class="glass-card overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Producto / Repuesto</th>
                        <th class="text-center">Costo Insumo</th>
                        <th class="text-center">HH</th>
                        <th class="text-center">Costo HH</th>
                        <th class="text-center">Técnicos</th>
                        <th class="text-end pe-4">P. Venta Sugerido</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql_prod = "SELECT p.id_producto, p.nombre_producto, p.marca, 
                                        c.precio_compra, c.tiempo_hh_obra, c.costo_hh, c.cant_tecnicos 
                                 FROM productos p 
                                 LEFT JOIN costos_productos c ON p.id_producto = c.id_producto 
                                 WHERE p.id_taller = '1' 
                                 ORDER BY p.nombre_producto ASC";
                    
                    $res = $db->ejecutar($sql_prod);

                    while($row = $db->recorrer($res)):
                        // Aseguramos que los valores sean numéricos para evitar errores en JS
                        $id_p          = (int)$row['id_producto'];
                        $nombre_p      = addslashes($row['nombre_producto']);
                        $precio_compra = (float)($row['precio_compra'] ?? 0); 
                        $tiempo_hh     = (float)($row['tiempo_hh_obra'] ?? 0);
                        $costo_hh_ind  = (float)($row['costo_hh'] ?? 0);
                        $n_tecnicos    = (int)($row['cant_tecnicos'] ?? 1);

                        // Cálculo de Mano de Obra (MO)
                        $costo_mo = $tiempo_hh * $costo_hh_ind * $n_tecnicos;
                        $subtotal_directo = $precio_compra + $costo_mo;

                        // Aplicación de la Matriz (Gastos + Utilidad)
                        $total_con_gastos = $subtotal_directo * (1 + ($suma_pct_gastos / 100));
                        $precio_final = $total_con_gastos * (1 + ($utilidad_pct / 100));
                    ?>
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold text-dark"><?php echo $row['nombre_producto']; ?></div>
                            <small class="text-muted text-uppercase" style="font-size: 0.65rem;"><?php echo $row['marca']; ?></small>
                        </td>
                        <td class="text-center text-muted">S/ <?php echo number_format($precio_compra, 2); ?></td>
                        <td class="text-center">
                            <span class="badge badge-time rounded-pill">
                                <i class="far fa-clock me-1"></i> <?php echo number_format($tiempo_hh, 2); ?> h
                            </span>
                        </td>
                        <td class="text-center fw-bold text-primary">S/ <?php echo number_format($costo_hh_ind, 2); ?></td>
                        <td class="text-center">
                            <span class="badge badge-tecnicos rounded-pill">
                                <i class="fa fa-users me-1"></i> x<?php echo $n_tecnicos; ?>
                            </span>
                        </td>
                        <td class="text-end pe-4">
                            <div class="d-flex align-items-center justify-content-end gap-3">
                                <div class="text-end">
                                    <div class="price-tag">S/ <?php echo number_format($precio_final, 2); ?></div>
                                    <small class="text-muted" style="font-size: 0.6rem;">BASE MO: S/ <?php echo number_format($costo_mo, 2); ?></small>
                                </div>
                                <button class="btn btn-light btn-edit-cost text-primary border" 
                                        onclick="editarCosto(<?php echo $id_p; ?>, '<?php echo $nombre_p; ?>', <?php echo $precio_compra; ?>, <?php echo $tiempo_hh; ?>, <?php echo $costo_hh_ind; ?>, <?php echo $n_tecnicos; ?>)">
                                    <i class="fa fa-pen-to-square"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditarCosto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-2xl border-0" style="border-radius: 2rem;">
            <div class="modal-body p-5">
                <div class="text-center mb-5">
                    <div class="bg-primary bg-opacity-10 d-inline-block p-3 rounded-circle mb-3">
                        <i class="fa fa-hand-holding-dollar fa-2x text-primary"></i>
                    </div>
                    <h4 class="fw-800 text-dark mb-1">Ajuste de Estructura de Costos</h4>
                    <p class="text-muted small" id="nombreProdModal"></p>
                </div>

                <form id="formCostos">
                    <input type="hidden" name="id_producto" id="modal_id_producto">
                    
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">COSTO INSUMO (S/)</label>
                            <input type="number" step="0.01" class="form-control input-premium" name="precio_compra" id="modal_precio_compra" oninput="recalcularEnVivo()">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">TIEMPO ESTIMADO (HH)</label>
                            <input type="number" step="0.1" class="form-control input-premium" name="tiempo_hh_obra" id="modal_tiempo_hh" oninput="recalcularEnVivo()">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">COSTO HORA TÉCNICO (S/)</label>
                            <input type="number" step="0.01" class="form-control input-premium" name="costo_hh" id="modal_costo_hh" oninput="recalcularEnVivo()">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">N° DE TÉCNICOS REQUERIDOS</label>
                            <input type="number" class="form-control input-premium" name="cant_tecnicos" id="modal_tecnicos" min="1" oninput="recalcularEnVivo()">
                        </div>
                    </div>

                    <div class="bg-primary bg-opacity-10 rounded-4 p-4 mt-5 border border-primary border-opacity-25">
                        <div class="row align-items-center">
                            <div class="col-7">
                                <span class="text-primary fw-bold d-block mb-1">PRECIO VENTA SUGERIDO</span>
                                <small class="text-muted d-block lh-1">Aplicando Matriz de Rentabilidad Maestra</small>
                            </div>
                            <div class="col-5 text-end">
                                <h2 class="fw-900 text-primary mb-0" id="label_precio_sugerido">S/ 0.00</h2>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2 mt-5">
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill fw-bold py-3 shadow">
                            <i class="fa fa-save me-2"></i>GUARDAR CAMBIOS
                        </button>
                        <button type="button" class="btn btn-link text-muted fw-bold" data-bs-dismiss="modal">CANCELAR</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'master/footer.php'; ?>

<script>
    // Parámetros de la Matriz cargados desde PHP
    const PCT_GASTOS = <?php echo (float)$suma_pct_gastos; ?>;
    const PCT_UTILIDAD = <?php echo (float)$utilidad_pct; ?>;

    /**
     * Función principal para abrir el modal y cargar datos
     */
    function editarCosto(id, nombre, compra, tiempo, costo_hh, tecnicos) {
        // Llenamos los campos del formulario
        $('#modal_id_producto').val(id);
        $('#nombreProdModal').text(nombre);
        $('#modal_precio_compra').val(compra);
        $('#modal_tiempo_hh').val(tiempo);
        $('#modal_costo_hh').val(costo_hh);
        $('#modal_tecnicos').val(tecnicos);
        
        // Calculamos el precio inicial antes de mostrar
        recalcularEnVivo();

        // 🚀 ABRIR MODAL (Sintaxis compatible Bootstrap 5)
        var myModal = new bootstrap.Modal(document.getElementById('modalEditarCosto'));
        myModal.show();
    }

    /**
     * Calcula el precio de venta sugerido en tiempo real
     */
    function recalcularEnVivo() {
        const compra = parseFloat($('#modal_precio_compra').val()) || 0;
        const hh = parseFloat($('#modal_tiempo_hh').val()) || 0;
        const costo_hh = parseFloat($('#modal_costo_hh').val()) || 0;
        const tecs = parseInt($('#modal_tecnicos').val()) || 1;

        // 1. Costo Directo de Mano de Obra
        const costo_mo = hh * costo_hh * tecs;
        
        // 2. Costo Total Directo (Insumo + MO)
        const subtotal = compra + costo_mo;
        
        // 3. Aplicación de Gastos Operativos
        const con_gastos = subtotal * (1 + (PCT_GASTOS / 100));
        
        // 4. Aplicación de Utilidad Neta
        const final = con_gastos * (1 + (PCT_UTILIDAD / 100));

        // Actualizamos el label con formato de moneda
        $('#label_precio_sugerido').text("S/ " + final.toLocaleString('en-US', {
            minimumFractionDigits: 2, 
            maximumFractionDigits: 2
        }));
    }

    $(document).ready(function() {
        /**
         * Envío del formulario vía AJAX
         */
        $('#formCostos').on('submit', function(e) {
            e.preventDefault();
            const btn = $(this).find('button[type="submit"]');
            const originalHtml = btn.html();
            
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> Guardando...');

            $.ajax({
                url: 'Poo/guardar_costo_producto.php',
                method: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.trim() == 'ok') {
                        location.reload(); 
                    } else {
                        btn.prop('disabled', false).html(originalHtml);
                        alert("Error al guardar: " + response);
                    }
                },
                error: function() {
                    btn.prop('disabled', false).html(originalHtml);
                    alert("Error de conexión con el servidor.");
                }
            });
        });
    });
</script>