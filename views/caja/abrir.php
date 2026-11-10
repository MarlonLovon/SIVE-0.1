<div class="caja-abrir-page max-w-600 mx-auto">
    <div class="page-header mb-3 text-center">
        <span style="font-size: 3rem;">🔓</span>
        <h1 class="page-title mt-2">Apertura de Caja Registradora</h1>
        <p class="text-muted">Inicie la jornada comercial del <?= date('d/m/Y') ?></p>
    </div>

    <!-- Advertencia si la caja anterior no fue cerrada -->
    <?php if ($cajaAnteriorSinCerrar): ?>
        <div class="alert alert-warning mb-3">
            <div class="alert-icon">⚠️</div>
            <div class="alert-content">
                <strong>¡Caja anterior pendiente de cierre!</strong>
                <p class="mb-1 text-sm">
                    La caja del día <strong><?= formatDate($cajaAnteriorSinCerrar['fecha']) ?></strong> quedó en estado ABIERTA con un fondo inicial de <?= formatMoney($cajaAnteriorSinCerrar['fondo_inicial']) ?>.
                </p>
                <p class="mb-0 text-xs">
                    El sistema le permite continuar y abrir la caja de hoy, pero se recomienda revisar y cerrar la caja previa para mantener la exactitud de los informes contables.
                </p>
            </div>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">Fondo Inicial de Caja</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="<?= url('caja/abrir') ?>" id="formAbrirCaja">
                <?= csrf_field() ?>

                <div class="form-group mb-4">
                    <label for="fondo_inicial" class="form-label font-bold text-lg">
                        Monto de Apertura (S/):
                    </label>
                    <p class="text-xs text-muted mb-2">
                        Dinero en billetes y monedas que se colocan en la gaveta para iniciar el día (cambio/sencillo).
                    </p>
                    <div class="input-money-wrapper">
                        <span class="currency-prefix">S/</span>
                        <input type="number" step="0.50" min="0" class="form-control form-control-lg font-bold" 
                               id="fondo_inicial" name="fondo_inicial" value="50.00" required autofocus>
                    </div>
                </div>

                <div class="quick-amounts-bar flex gap-2 mb-4 flex-wrap">
                    <span class="text-xs text-muted d-block w-100">Montos rápidos sugeridos:</span>
                    <button type="button" class="btn btn-sm btn-outline" onclick="document.getElementById('fondo_inicial').value = '30.00'">S/ 30.00</button>
                    <button type="button" class="btn btn-sm btn-outline" onclick="document.getElementById('fondo_inicial').value = '50.00'">S/ 50.00</button>
                    <button type="button" class="btn btn-sm btn-outline" onclick="document.getElementById('fondo_inicial').value = '100.00'">S/ 100.00</button>
                    <button type="button" class="btn btn-sm btn-outline" onclick="document.getElementById('fondo_inicial').value = '0.00'">S/ 0.00</button>
                </div>

                <div class="form-actions flex flex-between">
                    <a href="<?= url('caja') ?>" class="btn btn-outline">Cancelar</a>
                    <button type="submit" class="btn btn-success btn-lg">
                        <span>🔓 Confirmar y Abrir Caja</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
