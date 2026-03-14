<div class="modal fade" id="modalProducto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold m-0">
                    <i class="fa fa-box text-primary me-2"></i> 
                    <span id="tituloModalProd">Producto</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formProducto" action="Poo/guardar_producto_catalogo.php" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="id_producto" id="id_producto">

                    <div class="mb-3">
                        <label class="small fw-bold text-muted">Código de Barras / SKU</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0"><i class="fa fa-barcode"></i></span>
                            <input type="text" name="codigo_barras" id="codigo_barras" class="form-control bg-light border-0" placeholder="Opcional">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold text-muted">Nombre del Repuesto (Sea específico)</label>
                        <input type="text" name="nombre_producto" id="nombre_producto" class="form-control bg-light border-0" 
                               placeholder="Ej: Filtro de Aceite Sintético" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold text-muted">Marca</label>
                            <input type="text" name="marca" id="marca" class="form-control bg-light border-0" 
                                   list="marcas_sugeridas" placeholder="Ej: Bosch">
                            <datalist id="marcas_sugeridas">
                                <option value="Bosch">
                                <option value="Castrol">
                                <option value="Mobil">
                                <option value="Toyota">
                                <option value="Denso">
                            </datalist>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold text-muted">Categoría</label>
                            <select name="categoria" id="categoria" class="form-select bg-light border-0" required>
                                <option value="" selected disabled>Seleccione...</option>
                                <option value="Aceites">Lubricantes / Fluidos</option> 
                                <option value="Filtros">Filtros</option>
                                <option value="Fluidos">Fluidos Varios</option> 
                                <option value="Frenos">Frenos</option>
                                <option value="Suspension">Suspensión</option>
                                <option value="Motor">Motor</option>
                                <option value="Electrico">Eléctrico</option>
                                <option value="Quimicos">Químicos</option>
                                <option value="Otros">Otros</option> 
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold text-muted">Unidad de Medida</label>
                            <select name="unidad_medida" id="unidad_medida" class="form-select bg-light border-0" required>
                                <option value="UNID">Unidad (Pza/Und)</option>
                                <option value="GALON">Galón</option>
                                <option value="LITROS">Litros</option>
                                <option value="SET">Set / Juego</option>
                                <option value="KIT">Kit</option>
                                <option value="PAR">Par</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold text-muted">Stock Mínimo</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0"><i class="fa fa-boxes-stacked"></i></span>
                                <input type="number" name="stock_minimo" id="stock_minimo" 
                                    class="form-control bg-light border-0" value="5" required>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-secondary border-0 small py-2 mt-2">
                        <i class="fa fa-info-circle me-1"></i> Los precios y costos de obra se configuran en el <b>Gestor de Costos</b>.
                    </div>
                </div>
                
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold py-2 shadow">
                        GUARDAR FICHA TÉCNICA
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>