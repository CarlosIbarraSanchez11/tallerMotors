<?php
session_start();
require_once "Poo/Conexion.php";
$db = new Conexion();
include 'master/header.php';

// Cargamos la configuración maestra (Matriz de rentabilidad)
$config = $db->recorrer($db->ejecutar("SELECT * FROM config_taller WHERE id_config = 1"));
?>

<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
        --glass-bg: rgba(255, 255, 255, 0.95);
    }
    body { background-color: #f1f5f9; }
    
    .config-card {
        background: var(--glass-bg);
        border-radius: 1.5rem;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    
    .section-title {
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
        font-weight: 700;
        margin-bottom: 1.5rem;
    }

    .input-group-custom {
        background: #f8fafc;
        border: 2px solid #f1f5f9;
        border-radius: 1rem;
        padding: 0.8rem 1.2rem;
        transition: all 0.2s;
    }
    .input-group-custom:focus-within {
        border-color: #2563eb;
        background: white;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
    }
    .input-group-custom input {
        border: none;
        background: transparent;
        font-weight: 800;
        color: #1e293b;
        width: 100%;
        font-size: 1.2rem;
    }
    .input-group-custom input:focus { outline: none; }
</style>

<div class="container-fluid px-4 py-5">
    <div class="row mb-5">
        <div class="col-12">
            <h2 class="fw-bold text-dark"><i class="fa fa-gears me-2 text-primary"></i>Configuración de Rentabilidad</h2>
            <p class="text-muted">Ajusta los porcentajes de la Matriz Maestra que se aplican a los costos de taller.</p>
        </div>
    </div>

    <form id="formConfigMaster">
        <div class="row">
            <div class="col-12">
                <div class="config-card p-5">
                    <div class="section-title"><i class="fa fa-calculator me-2"></i>Matriz de Gastos y Operaciones</div>
                    
                    <div class="row g-4">
                        <div class="col-md-12">
                            <label class="small fw-bold text-dark mb-4 d-block">GASTOS OPERATIVOS (PORCENTAJES)</label>
                            <div class="row g-4">
                                <div class="col-md-4">
                                    <div class="input-group-custom">
                                        <small class="text-muted d-block fw-bold mb-1" style="font-size: 0.65rem;">ALQUILER (%)</small>
                                        <input type="number" step="0.1" name="pct_alquiler" value="<?php echo $config['pct_alquiler']; ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group-custom">
                                        <small class="text-muted d-block fw-bold mb-1" style="font-size: 0.65rem;">GESTIÓN (%)</small>
                                        <input type="number" step="0.1" name="pct_gestion" value="<?php echo $config['pct_gestion']; ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group-custom">
                                        <small class="text-muted d-block fw-bold mb-1" style="font-size: 0.65rem;">MARKETING (%)</small>
                                        <input type="number" step="0.1" name="pct_marketing" value="<?php echo $config['pct_marketing']; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group-custom">
                                        <small class="text-muted d-block fw-bold mb-1" style="font-size: 0.65rem;">HERRAMIENTAS (%)</small>
                                        <input type="number" step="0.1" name="pct_herramientas" value="<?php echo $config['pct_herramientas'] ?? 2.0; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group-custom">
                                        <small class="text-muted d-block fw-bold mb-1" style="font-size: 0.65rem;">TRANSPORTE (%)</small>
                                        <input type="number" step="0.1" name="pct_transporte" value="<?php echo $config['pct_transporte'] ?? 5.0; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-5 opacity-10">

                        <div class="col-md-12">
                            <div class="p-4 rounded-4 shadow-sm" style="background: linear-gradient(to right, #f0fdf4, #ffffff); border: 2px dashed #bbf7d0;">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h5 class="fw-bold text-success mb-1">Margen de Ganancia Neto (Utilidad)</h5>
                                        <p class="text-muted small mb-0">Este porcentaje se aplica después de cubrir todos los gastos operativos mencionados arriba.</p>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="input-group-custom border-success border-opacity-25 bg-white">
                                            <small class="text-success fw-bold d-block" style="font-size: 0.7rem;">UTILIDAD FINAL (%)</small>
                                            <input type="number" step="0.1" name="pct_utilidad" class="text-success fs-3" value="<?php echo $config['pct_utilidad']; ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 pt-4 border-top d-flex align-items-center justify-content-between">
                        <div class="text-muted small d-none d-md-block">
                            <i class="fa fa-circle-info me-1 text-primary"></i> 
                            Los cambios afectarán el "Precio Sugerido" en todo el Gestor de Costos.
                        </div>
                        <button type="button" class="btn btn-primary btn-lg rounded-pill px-5 fw-bold shadow-lg" onclick="guardarConfigGlobal()">
                            <i class="fa fa-save me-2"></i> ACTUALIZAR MATRIZ MAESTRA
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php include 'master/footer.php'; ?>

<script>
function guardarConfigGlobal() {
    const btn = $('button[onclick="guardarConfigGlobal()"]');
    const originalContent = btn.html();
    
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> Guardando...');

    $.ajax({
        url: 'Poo/actualizar_config_maestra.php',
        method: 'POST',
        data: $('#formConfigMaster').serialize(),
        success: function(response) {
            if (response.trim() === 'ok') {
                alert("¡Matriz Maestra actualizada correctamente!");
                location.reload(); 
            } else {
                btn.prop('disabled', false).html(originalContent);
                alert("Error al guardar: " + response);
            }
        },
        error: function() {
            btn.prop('disabled', false).html(originalContent);
            alert("Error de conexión con el servidor.");
        }
    });
}
</script>