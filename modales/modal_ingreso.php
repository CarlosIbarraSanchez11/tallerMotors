<div class="modal fade" id="modalNuevoIngreso" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold m-0"><i class="fa fa-file-import text-primary me-2"></i> Carga de Inventario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <form action="Poo/procesar_ingreso_rapido.php" method="POST">
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="small fw-bold text-muted">Origen del Movimiento</label>
                            <select name="tipo_ingreso" id="tipo_ingreso" class="form-select bg-primary bg-opacity-10 border-0 fw-bold text-primary py-2" onchange="toggleProveedor()">
                                <option value="informal">📦 Ingreso Interno (Stock Inicial / Ajuste)</option>
                                <option value="formal">🏢 Compra Formal (Con Proveedor)</option>
                            </select>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="small fw-bold text-muted">¿Qué repuesto está ingresando?</label>
                            <select name="id_producto" id="select_producto" class="form-select bg-light border-0 py-2" required>
                                <option value="">-- Buscar repuesto en catálogo --</option>
                                <?php
                                $prod = $db->ejecutar("SELECT * FROM productos WHERE id_taller = '1' ORDER BY nombre_producto ASC");
                                while($pr = $db->recorrer($prod)) {
                                    echo "<option value='".$pr['id_producto']."'>".$pr['nombre_producto']." (".$pr['marca'].")</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div id="seccion_formal" class="row mx-0 px-0" style="display: none;">
                            <div class="col-md-12 mb-3">
                                <label class="small fw-bold text-muted">Proveedor</label>
                                <select name="id_proveedor" id="select_proveedor" class="form-select">
                                    <option value="">Seleccionar Proveedor...</option>
                                    <?php
                                    $prov = $db->ejecutar("SELECT * FROM proveedores ORDER BY razon_social ASC");
                                    while($p = $db->recorrer($prov)) {
                                        echo "<option value='".$p['id_proveedor']."'>".$p['razon_social']." (".$p['ruc'].")</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <hr class="my-3 opacity-25">

                        <div class="col-md-12">
                            <div class="bg-light p-3 rounded-4 border-2 border-dashed text-center">
                                <label class="fw-bold text-dark mb-2 d-block">CANTIDAD A INGRESAR</label>
                                <div class="input-group justify-content-center">
                                    <input type="number" name="cantidad" class="form-control form-control-lg text-center fw-bold text-primary border-0 bg-white shadow-sm" 
                                           style="max-width: 200px; border-radius: 15px !important; font-size: 2rem;" 
                                           placeholder="0" min="1" required>
                                </div>
                                <small class="text-muted mt-2 d-block">Verifica la cantidad antes de confirmar.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold py-3 shadow-lg">
                        <i class="fa fa-check-circle me-2"></i>CONFIRMAR INGRESO AL ALMACÉN
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleProveedor() {
    var tipo = document.getElementById('tipo_ingreso').value;
    var seccion = document.getElementById('seccion_formal');
    if (tipo === 'formal') {
        seccion.style.display = 'block'; // Sinceramente, 'block' funciona mejor para filas de 12
    } else {
        seccion.style.display = 'none';
    }
}
</script>