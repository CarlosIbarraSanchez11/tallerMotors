<div class="modal fade" id="modalCliente" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold m-0" id="tituloModalCliente"><i class="fa fa-user-plus text-primary me-2"></i> Nuevo Registro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formCliente" action="Poo/guardar_cliente.php" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="id_cliente" id="id_cliente">
                    
                    <h6 class="text-primary fw-bold mb-3 small text-uppercase">Información del Propietario</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-7">
                            <label class="small fw-bold text-muted">Nombre Completo</label>
                            <input type="text" name="nombre_completo" id="nombre_completo" class="form-control bg-light border-0 shadow-none" placeholder="Juan Pérez" required>
                        </div>
                        <div class="col-md-5">
                            <label class="small fw-bold text-muted">DNI / RUC</label>
                            <input type="text" name="dni_ruc" id="dni_ruc" class="form-control bg-light border-0 shadow-none" placeholder="1075..." required>
                        </div>
                        <div class="col-md-7">
                            <label class="small fw-bold text-muted">Correo Electrónico (Opcional)</label>
                            <input type="email" name="correo" id="correo" class="form-control bg-light border-0 shadow-none" placeholder="cliente@correo.com">
                        </div>
                        <div class="col-md-5">
                            <label class="small fw-bold text-muted">Teléfono</label>
                            <input type="text" name="telefono" id="telefono" class="form-control bg-light border-0 shadow-none" placeholder="987..." required>
                        </div>
                    </div>

                    <hr class="opacity-10">

                    <h6 class="text-primary fw-bold mb-3 small text-uppercase">Información del Vehículo</h6>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="small fw-bold text-muted">Placa</label>
                            <input type="text" name="placa" id="placa" class="form-control bg-primary bg-opacity-10 border-0 fw-bold text-center text-primary shadow-none" placeholder="ABC-123" required style="text-transform:uppercase;">
                        </div>
                        <div class="col-md-3">
                            <label class="small fw-bold text-muted">Marca</label>
                            <input type="text" name="marca" id="marca" class="form-control bg-light border-0 shadow-none" placeholder="Toyota">
                        </div>
                        <div class="col-md-3">
                            <label class="small fw-bold text-muted">Modelo</label>
                            <input type="text" name="modelo" id="modelo" class="form-control bg-light border-0 shadow-none" placeholder="Corolla">
                        </div>
                        <div class="col-md-3">
                            <label class="small fw-bold text-muted">Año</label>
                            <input type="number" name="anio" id="anio" class="form-control bg-light border-0 shadow-none" placeholder="2022">
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-md-4">
                            <label class="small fw-bold text-muted">Color</label>
                            <input type="text" name="color" id="color" class="form-control bg-light border-0 shadow-none" placeholder="Gris Plata">
                        </div>
                        <div class="col-md-4">
                            <label class="small fw-bold text-muted">Combustible</label>
                            <select name="combustible" id="combustible" class="form-select bg-light border-0 shadow-none">
                                <option value="" disabled selected>Elegir combustible...</option>
                                <option value="Gasolina">Gasolina</option>
                                <option value="Diesel">Diesel</option>
                                <option value="GLP">GLP</option>
                                <option value="GNV">GNV</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="small fw-bold text-muted">Tipo de Vehículo</label>
                            <select name="tipo_vehiculo" id="tipo_vehiculo" class="form-select bg-primary bg-opacity-10 border-0 fw-bold text-primary shadow-none" required>
                                <option value="" disabled selected>Elegir categoría...</option>
                                <option value="AUTO">Auto (Sedán/Hatch)</option>
                                <option value="CAMIONETA">Camioneta (SUV/Pick-up)</option>
                                <option value="FURGON">Furgón / Van</option>
                                <option value="GAMA_ALTA">GAMA ALTA (Premium)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn btn-primary w-100 rounded-3 shadow-sm py-2 fw-bold">
                        <i class="fa fa-save me-2"></i> REGISTRAR CLIENTE Y VEHÍCULO
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>