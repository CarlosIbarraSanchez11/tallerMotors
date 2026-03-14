<div class="modal fade" id="modalAgregarRepuesto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold">Descargar Repuesto de Almacén</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="Poo/consumir_repuesto.php" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="id_cita" value="<?php echo $id_cita; ?>">
                    
                    <div class="mb-3">
                        <label class="small fw-bold text-muted">Producto / Repuesto</label>
                        <select name="id_producto" id="select_producto_ot" class="form-select bg-light border-0" required>
                            <option value="">-- Buscar en Inventario --</option>
                            <?php
                            // Solo mostramos productos que tengan stock
                            $prod = $db->ejecutar("SELECT * FROM productos WHERE stock_actual > 0 ORDER BY nombre_producto ASC");
                            while($pr = $db->recorrer($prod)) {
                                echo "<option value='".$pr['id_producto']."' data-precio='".$pr['precio_venta']."'>".$pr['nombre_producto']." (Stock: ".$pr['stock_actual'].")</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="small fw-bold text-muted">Cantidad</label>
                            <input type="number" name="cantidad" class="form-control bg-light border-0" value="1" min="1" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="small fw-bold text-muted">Precio Sugerido</label>
                            <input type="number" step="0.01" name="precio" id="precio_sugerido" class="form-control bg-light border-0" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold">AGREGAR A LA ORDEN</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Script para poner el precio automático al seleccionar producto
document.getElementById('select_producto_ot').addEventListener('change', function() {
    var selectedOption = this.options[this.selectedIndex];
    var precio = selectedOption.getAttribute('data-precio');
    document.getElementById('precio_sugerido').value = precio;
});
</script>