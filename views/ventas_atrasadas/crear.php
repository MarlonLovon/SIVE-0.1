<div class="ventas-atrasadas-crear-page max-w-800 mx-auto">
    <div class="page-header flex flex-between flex-wrap gap-2 mb-3">
        <div>
            <h1 class="page-title">📝 Registrar Ventas Atrasadas</h1>
            <p class="text-muted">Cálculo de ventas por cantidades globales para regularización</p>
        </div>
        <div>
            <a href="<?= url('ventas-atrasadas') ?>" class="btn btn-outline">
                <span>← Volver al listado</span>
            </a>
        </div>
    </div>

    <!-- Advertencia Regla de Cremoladas -->
    <div class="alert alert-warning mb-3">
        <div class="alert-icon">⚠️</div>
        <div class="alert-content">
            <strong>Nota sobre precios de cremolada (DP-06):</strong>
            En los resúmenes atrasados no se conocen los sabores exactos de cada vaso.
            El cálculo automático asume la tarifa base de <strong><?= formatMoney($precios['precio_cremolada_1_2_sabores'] ?? 6) ?></strong>.
            Si el total que usted anotó en papel difiere, ingréselo en el campo <em>"Total Anotado en Papel"</em> para registrar la observación.
        </div>
    </div>

    <form method="POST" action="<?= url('ventas-atrasadas/guardar') ?>" id="formVentaAtrasada">
        <?= csrf_field() ?>

        <!-- 1. Fecha de Venta Real -->
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">1. Fecha de Venta Real</h3>
            </div>
            <div class="card-body">
                <div class="form-group mb-0">
                    <label for="fecha_venta" class="form-label font-bold">Fecha en que se realizaron las ventas:</label>
                    <input type="date" class="form-control" id="fecha_venta" name="fecha_venta" 
                           value="<?= date('Y-m-d', strtotime('-1 day')) ?>" max="<?= date('Y-m-d') ?>" required>
                    <span class="text-xs text-muted">La fecha y hora de registro en el sistema se guardará automáticamente como hoy.</span>
                </div>
            </div>
        </div>

        <!-- 2. Cantidades de Helados -->
        <div class="card mb-3">
            <div class="card-header flex flex-between">
                <h3 class="card-title">2. Cantidades de Helados</h3>
                <span class="badge badge-primary">Helados Artesanales</span>
            </div>
            <div class="card-body">
                <div class="form-row grid-2-cols gap-3">
                    <div class="form-group">
                        <label class="form-label">
                            <strong>Vaso 4 oz (1 bola)</strong>
                            <span class="text-primary font-bold ml-1"><?= formatMoney($precios['precio_helado_1bola'] ?? 4) ?> c/u</span>
                        </label>
                        <input type="number" min="0" step="1" class="form-control qty-input" 
                               name="helados_4oz" id="qty_h4" value="0" 
                               data-precio="<?= e($precios['precio_helado_1bola'] ?? 4) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <strong>Vaso 6 oz (2 bolas)</strong>
                            <span class="text-primary font-bold ml-1"><?= formatMoney($precios['precio_helado_2bolas'] ?? 7) ?> c/u</span>
                        </label>
                        <input type="number" min="0" step="1" class="form-control qty-input" 
                               name="helados_6oz" id="qty_h6" value="0" 
                               data-precio="<?= e($precios['precio_helado_2bolas'] ?? 7) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <strong>Vaso 8 oz (3 bolas)</strong>
                            <span class="text-primary font-bold ml-1"><?= formatMoney($precios['precio_helado_3bolas'] ?? 9) ?> c/u</span>
                        </label>
                        <input type="number" min="0" step="1" class="form-control qty-input" 
                               name="helados_8oz" id="qty_h8" value="0" 
                               data-precio="<?= e($precios['precio_helado_3bolas'] ?? 9) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <strong>Pote 1 Litro</strong>
                            <span class="text-primary font-bold ml-1"><?= formatMoney($precios['precio_litro_helado'] ?? 28) ?> c/u</span>
                        </label>
                        <input type="number" min="0" step="1" class="form-control qty-input" 
                               name="litros_helado" id="qty_hlitro" value="0" 
                               data-precio="<?= e($precios['precio_litro_helado'] ?? 28) ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Cantidades de Cremoladas -->
        <div class="card mb-3">
            <div class="card-header flex flex-between">
                <h3 class="card-title">3. Cantidades de Cremoladas</h3>
                <span class="badge badge-info">Cremoladas de Fruta</span>
            </div>
            <div class="card-body">
                <div class="form-row grid-2-cols gap-3">
                    <div class="form-group">
                        <label class="form-label">
                            <strong>Vaso Cremolada 6 oz</strong>
                            <span class="text-primary font-bold ml-1"><?= formatMoney($precios['precio_cremolada_1_2_sabores'] ?? 6) ?> base</span>
                        </label>
                        <input type="number" min="0" step="1" class="form-control qty-input" 
                               name="cremoladas_6oz" id="qty_c6" value="0" 
                               data-precio="<?= e($precios['precio_cremolada_1_2_sabores'] ?? 6) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <strong>Vaso Cremolada 12 oz</strong>
                            <span class="text-primary font-bold ml-1"><?= formatMoney($precios['precio_cremolada_1_2_sabores'] ?? 6) ?> base</span>
                        </label>
                        <input type="number" min="0" step="1" class="form-control qty-input" 
                               name="cremoladas_12oz" id="qty_c12" value="0" 
                               data-precio="<?= e($precios['precio_cremolada_1_2_sabores'] ?? 6) ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Totales y Comparación con Papel -->
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">4. Cálculo y Comparación de Totales</h3>
            </div>
            <div class="card-body">
                <div class="totals-calculation-box p-3 bg-light rounded mb-3">
                    <div class="flex flex-between mb-2">
                        <span class="font-bold text-muted">Total Calculado Automático:</span>
                        <span class="font-bold text-2xl text-primary" id="lblTotalCalculado">S/ 0.00</span>
                    </div>
                    <div class="text-xs text-muted">
                        Resultado de multiplicar las cantidades ingresadas por los precios configurados.
                    </div>
                </div>

                <div class="form-group mb-3">
                    <label for="total_anotado" class="form-label font-bold">
                        Total Anotado en Papel (Opcional):
                    </label>
                    <div class="input-prefix-wrapper">
                        <input type="number" step="0.50" min="0" class="form-control" 
                               id="total_anotado" name="total_anotado" placeholder="0.00">
                    </div>
                    <div id="boxDiferencia" class="mt-2 text-sm hidden font-bold"></div>
                </div>

                <div class="form-group mb-0">
                    <label for="notas" class="form-label font-bold">Notas u Observaciones:</label>
                    <textarea class="form-control" id="notas" name="notas" rows="3" 
                              placeholder="Ej: Día con corte de fluido eléctrico; atendido con talonario de mano."></textarea>
                </div>
            </div>
        </div>

        <!-- Botones de Acción -->
        <div class="form-actions flex flex-between mb-4">
            <a href="<?= url('ventas-atrasadas') ?>" class="btn btn-outline">Cancelar</a>
            <button type="submit" class="btn btn-primary btn-lg" id="btnGuardarResumen">
                <span>💾 Guardar Resumen de Ventas</span>
            </button>
        </div>
    </form>
</div>

<!-- Lógica interactiva para cálculo dinámico de totales -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const qtyInputs = document.querySelectorAll('.qty-input');
    const lblCalculado = document.getElementById('lblTotalCalculado');
    const inputAnotado = document.getElementById('total_anotado');
    const boxDiferencia = document.getElementById('boxDiferencia');

    function recalcular() {
        let total = 0;
        qtyInputs.forEach(input => {
            const qty = parseInt(input.value) || 0;
            const precio = parseFloat(input.dataset.precio) || 0;
            total += qty * precio;
        });

        lblCalculado.textContent = 'S/ ' + total.toFixed(2);

        // Comparar con anotado si existe
        const anotadoVal = parseFloat(inputAnotado.value);
        if (!isNaN(anotadoVal) && inputAnotado.value.trim() !== '') {
            const dif = (anotadoVal - total).toFixed(2);
            boxDiferencia.classList.remove('hidden');
            if (Math.abs(dif) < 0.01) {
                boxDiferencia.className = 'mt-2 text-sm font-bold text-success';
                boxDiferencia.innerHTML = '✅ El total anotado coincide exactamente con el cálculo.';
            } else if (dif > 0) {
                boxDiferencia.className = 'mt-2 text-sm font-bold text-info';
                boxDiferencia.innerHTML = `⚠️ Diferencia positiva: El monto anotado es mayor por S/ ${dif} (posibles cremoladas de 3 sabores o propinas).`;
            } else {
                boxDiferencia.className = 'mt-2 text-sm font-bold text-danger';
                boxDiferencia.innerHTML = `⚠️ Diferencia negativa: El monto anotado es menor por S/ ${Math.abs(dif).toFixed(2)}.`;
            }
        } else {
            boxDiferencia.classList.add('hidden');
        }
    }

    qtyInputs.forEach(input => {
        input.addEventListener('input', recalcular);
        input.addEventListener('change', recalcular);
    });

    inputAnotado.addEventListener('input', recalcular);
    inputAnotado.addEventListener('change', recalcular);
});
</script>
