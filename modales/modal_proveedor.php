<div class="modal fade" id="modalProveedor" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold m-0">
                    <i class="fa fa-truck-loading text-primary me-2"></i> 
                    <span id="tituloModalProv">Nuevo Proveedor</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="formProveedor" action="Poo/guardar_proveedor.php" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="id_proveedor" id="id_proveedor">

                    <div class="mb-3">
                        <label class="small fw-bold text-muted">RUC (11 dígitos)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0"><i class="fa fa-id-card"></i></span>
                            <input type="text" name="ruc" id="prov_ruc" class="form-control bg-light border-0" 
                                   maxlength="11" placeholder="Ej: 20600000000" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold text-muted">Razón Social / Nombre Empresa</label>
                        <input type="text" name="razon_social" id="prov_social" class="form-control bg-light border-0" 
                               placeholder="Ej: Repuestos El Chamo SAC" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold text-muted">Teléfono de Contacto</label>
                            <input type="text" name="telefono" id="prov_tel" class="form-control bg-light border-0" 
                                   placeholder="999 999 999">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold text-muted">Correo Electrónico</label>
                            <input type="email" name="correo" id="prov_mail" class="form-control bg-light border-0" 
                                   placeholder="ventas@proveedor.com">
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="small fw-bold text-muted">Dirección Fiscal / Oficina</label>
                        <textarea name="direccion" id="prov_dir" class="form-control bg-light border-0" rows="2" 
                                  placeholder="Av. Las Malvinas 123..."></textarea>
                    </div>
                </div>

                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold py-2 shadow">
                        <i class="fa fa-save me-2"></i> GUARDAR PROVEEDOR
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>