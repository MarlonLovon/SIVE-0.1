<div class="caja-cerrar-page max-w-700 mx-auto">
    <div class="page-header mb-3 text-center">
        <span style="font-size: 3rem;">🔒</span>
        <h1 class="page-title mt-2">Arqueo y Cierre Diario de Caja</h1>
        <p class="text-muted">Cierre de jornada del <?= formatDate($cajaHoy['fecha']) ?> (Apertura: <?= formatDateTime($cajaHoy['apertura']) ?>)</p>
    </div>

    <form method="POST" action="<?= url('caja/cerrar') ?>" id="formCerrarCaja">
        <?= csrf_field() ?>

        <!-- Resumen Contable del Día -->
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">1. Resumen de Flujo de Efectivo</h3>
            </div>
            <div class="card-body">
                <div class="arqueo-breakdown-box">
                    <div class="flex flex-between py-2 border-bottom">
                        <span class="text-muted">Fondo Inicial de Apertura:</span>
                        <span class="font-bold"><?= formatMoney($resumen['fondo_inicial'] ?? 0) ?></span>
                    </div>
                    <div class="flex flex-between py-2 border-bottom">
                        <span class="text-muted">(+) Ventas en Efectivo:</span>
                        <span class="font-bold text-success">+<?= formatMoney($resumen['ventas_efectivo'] ?? 0) ?></span>
                    </div>
                    <div class="flex flex-between py-2 border-bottom">
                        <span class="text-muted">(-) Gastos Operativos en Efectivo:</span>
                        <span class="font-bold text-danger">-<?= formatMoney($resumen['gastos_efectivo'] ?? 0) ?></span>
                    </div>
                    <div class="flex flex-between py-2 border-bottom">
                        <span class="text-muted">(-) Retiros de Caja en Efectivo:</span>
                        <span class="font-bold text-danger">-<?= formatMoney($resumen['retiros_efectivo'] ?? 0) ?></span>
                    </div>
                    <div class="flex flex-between py-3 bg-light rounded px-3 mt-2">
                        <span class="font-bold text-lg">(=) Efectivo Físico Esperado:</span>
                        <span class="font-bold text-2xl text-primary" id="lblEfectivoEsperado" data-esperado="<?= e($resumen['efectivo_esperado'] ?? 0) ?>">
                            <?= formatMoney($resumen['efectivo_esperado'] ?? 0) ?>
                        </span>
                    </div>
                </div>

                <!-- Resumen informativo de Yape (separado) -->
                <div class="mt-3 p-2 bg-light rounded text-xs flex flex-between">
                    <span>📱 Total cobrado por Yape (digital en cuenta):</span>
                    <strong class="text-info"><?= formatMoney($resumen['ventas_yape'] ?? 0) ?></strong>
                </div>
            </div>
        </div>

        <!-- 2. Arqueo Físico (Efectivo Contado) -->
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">2. Conteo de Efectivo en Gaveta</h3>
            </div>
            <div class="card-body">
                <div class="form-group mb-3">
                    <label for="efectivo_contado" class="form-label font-bold text-lg">
                        Efectivo Real Contado en Gaveta (S/):
                    </label>
                    <p class="text-xs text-muted mb-2">
                        Cuente todos los billetes y monedas físicos en la gaveta e ingrese el total.
                    </p>
                    <div class="input-money-wrapper">
                        <span class="currency-prefix">S/</span>
                        <input type="number" step="0.50" min="0" class="form-control form-control-lg font-bold" 
                               id="efectivo_contado" name="efectivo_contado" placeholder="0.00" required autofocus>
                    </div>
                </div>

                <!-- Caja de Visualización de Diferencia en Vivo -->
                <div id="boxDiferenciaArqueo" class="p-3 rounded mb-3 bg-light text-center">
                    <span class="text-xs text-muted d-block">Diferencia de Arqueo (Contado - Esperado):</span>
                    <h2 class="font-bold mt-1 mb-0" id="lblDiferenciaMonto">S/ 0.00</h2>
                    <span class="text-xs d-block mt-1 font-bold" id="lblDiferenciaTexto">Ingrese el monto contado para calcular</span>
                </div>

                <!-- Observaciones -->
                <div class="form-group mb-0">
                    <label for="observaciones" class="form-label font-bold">Observaciones o Justificación de Descuadre:</label>
                    <textarea class="form-control" id="observaciones" name="observaciones" rows="3"
                              placeholder="Ej: Sencillo faltante por redondeo de monedas de 10 céntimos, etc."></textarea>
                </div>
            </div>
        </div>

        <!-- Botones de Acción -->
        <div class="form-actions flex flex-between mb-4">
            <a href="<?= url('caja') ?>" class="btn btn-outline">Cancelar</a>
            <button type="submit" class="btn btn-danger btn-lg" id="btnConfirmarCierre" onclick="return confirm('¿Está seguro de cerrar la caja de hoy? Una vez cerrada no se podrán registrar más ventas en esta fecha.');">
                <span>🔒 Confirmar y Cerrar Caja</span>
            </button>
        </div>
    </form>
</div>

<!-- Script interactivo específico de caja -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputContado = document.getElementById('efectivo_contado');
    const lblEsperado = document.getElementById('lblEfectivoEsperado');
    const lblDifMonto = document.getElementById('lblDiferenciaMonto');
    const lblDifTexto = document.getElementById('lblDiferenciaTexto');
    const boxDif = document.getElementById('boxDiferenciaArqueo');

    const esperado = parseFloat(lblEsperado.dataset.esperado) || 0;

    function calcularArqueo() {
        const val = inputContado.value.trim();
        if (val === '') {
            lblDifMonto.textContent = 'S/ 0.00';
            lblDifTexto.textContent = 'Ingrese el monto contado para calcular';
            boxDif.style.backgroundColor = 'var(--bg-light, #f8f9fa)';
            lblDifMonto.className = 'font-bold mt-1 mb-0';
            return;
        }

        const contado = parseFloat(val) || 0;
        const dif = Math.round((contado - esperado) * 100) / 100;

        lblDifMonto.textContent = (dif >= 0 ? '+' : '') + 'S/ ' + dif.toFixed(2);

        if (Math.abs(dif) < 0.01) {
            boxDif.style.backgroundColor = '#d4edda';
            lblDifMonto.className = 'font-bold mt-1 mb-0 text-success';
            lblDifTexto.textContent = '🎉 ¡Caja cuadrada perfectamente! (Sin diferencias)';
        } else if (dif > 0) {
            boxDif.style.backgroundColor = '#d1ecf1';
            lblDifMonto.className = 'font-bold mt-1 mb-0 text-info';
            lblDifTexto.textContent = '⚠️ Sobrante de caja: Hay más efectivo físico del esperado.';
        } else {
            boxDif.style.backgroundColor = '#f8d7da';
            lblDifMonto.className = 'font-bold mt-1 mb-0 text-danger';
            lblDifTexto.textContent = '❌ Faltante de caja: Falta dinero físico respecto a las ventas registradas.';
        }
    }

    inputContado.addEventListener('input', calcularArqueo);
    inputContado.addEventListener('change', calcularArqueo);
});
</script>
