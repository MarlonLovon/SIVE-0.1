<div class="reposiciones-crear-page max-w-700 mx-auto">
    <div class="page-header flex flex-between flex-wrap gap-2 mb-3">
        <div>
            <h1 class="page-title">🔄 Nueva Reposición de Inventario</h1>
            <p class="text-muted">Incorpore una nueva cubeta de helado (5L) o balde de cremolada (7L)</p>
        </div>
        <div>
            <a href="<?= url('reposiciones') ?>" class="btn btn-outline">
                <span>← Volver al listado</span>
            </a>
        </div>
    </div>

    <form method="POST" action="<?= url('reposiciones/guardar') ?>" id="formReposicion">
        <?= csrf_field() ?>

        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">1. Recipiente y Sabor</h3>
            </div>
            <div class="card-body">
                <!-- Selector de Tipo de Recipiente -->
                <div class="form-group mb-3">
                    <label class="form-label font-bold">Tipo de Recipiente:</label>
                    <div class="grid-2-cols gap-2">
                        <button type="button" class="btn btn-outline active py-3 text-center" id="btnTipoHeladoRepo" data-tipo="bandeja_helado" data-litros="5.00">
                            <span style="font-size: 1.5rem; display: block;">🍨</span>
                            <strong>Bandeja de Helado</strong>
                            <span class="text-xs text-muted d-block">Estándar: 5 Litros</span>
                        </button>
                        <button type="button" class="btn btn-outline py-3 text-center" id="btnTipoCremoladaRepo" data-tipo="balde_cremolada" data-litros="7.00">
                            <span style="font-size: 1.5rem; display: block;">🍧</span>
                            <strong>Balde de Cremolada</strong>
                            <span class="text-xs text-muted d-block">Estándar: 7 Litros</span>
                        </button>
                    </div>
                    <input type="hidden" name="tipo" id="inputTipoRepo" value="bandeja_helado">
                </div>

                <!-- Selector de Sabor -->
                <div class="form-group mb-3">
                    <label for="sabor_id" class="form-label font-bold">Sabor a Reponer:</label>
                    <select class="form-control form-control-lg" id="sabor_id" name="sabor_id" required>
                        <option value="">-- Seleccione el sabor --</option>
                        <optgroup label="🍦 Helados Artesanales" id="optgroupHelados">
                            <?php foreach ($sabores as $s): ?>
                                <?php if ($s['tipo'] === 'helado'): ?>
                                    <option value="<?= e($s['id']) ?>" data-tipo="helado"><?= e($s['nombre']) ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </optgroup>
                        <optgroup label="🍧 Cremoladas de Fruta" id="optgroupCremoladas">
                            <?php foreach ($sabores as $s): ?>
                                <?php if ($s['tipo'] === 'cremolada'): ?>
                                    <option value="<?= e($s['id']) ?>" data-tipo="cremolada"><?= e($s['nombre']) ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                </div>

                <!-- Litros a Ingresar -->
                <div class="form-row grid-2-cols gap-2">
                    <div class="form-group mb-0">
                        <label for="litros" class="form-label font-bold">Litros a Ingresar:</label>
                        <input type="number" step="0.5" min="0.5" class="form-control" id="litros" name="litros" value="5.00" required>
                    </div>
                    <div class="form-group mb-0">
                        <label for="fecha" class="form-label font-bold">Fecha de Elaboración / Ingreso:</label>
                        <input type="date" class="form-control" id="fecha" name="fecha" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Costo y Vínculo con Caja -->
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">2. Costo y Registro en Caja</h3>
            </div>
            <div class="card-body">
                <div class="form-group mb-3">
                    <label for="costo" class="form-label font-bold">
                        Costo de Elaboración / Compra (S/):
                    </label>
                    <p class="text-xs text-muted mb-2">
                        Si se pagó con dinero del negocio y debe descontarse de la caja, ingrese el monto. Deje 0.00 si ya fue pagado externamente.
                    </p>
                    <div class="input-money-wrapper">
                        <span class="currency-prefix">S/</span>
                        <input type="number" step="0.50" min="0" class="form-control" id="costo" name="costo" value="0.00">
                    </div>
                </div>

                <div class="form-group mb-3 hidden" id="boxMedioPagoRepo">
                    <label for="medio_pago" class="form-label font-bold">¿Cómo se pagó este costo?</label>
                    <select class="form-control" id="medio_pago" name="medio_pago">
                        <option value="efectivo" selected>💵 Efectivo de la gaveta de caja</option>
                        <option value="yape">📱 Yape del negocio</option>
                    </select>
                    <span class="text-xs text-muted mt-1 d-block">
                        El sistema generará un movimiento de tipo <strong>reposición</strong> en la caja de hoy, previniendo duplicidades.
                    </span>
                </div>

                <div class="form-group mb-0">
                    <label for="observaciones" class="form-label font-bold">Observaciones / Lote:</label>
                    <textarea class="form-control" id="observaciones" name="observaciones" rows="2" 
                              placeholder="Ej: Lote #14, fruta fresca de mercado, proveedor habitual..."></textarea>
                </div>
            </div>
        </div>

        <div class="form-actions flex flex-between mb-4">
            <a href="<?= url('reposiciones') ?>" class="btn btn-outline">Cancelar</a>
            <button type="submit" class="btn btn-primary btn-lg">
                <span>➕ Registrar Reposición en Inventario</span>
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnHelado = document.getElementById('btnTipoHeladoRepo');
    const btnCremolada = document.getElementById('btnTipoCremoladaRepo');
    const inputTipo = document.getElementById('inputTipoRepo');
    const inputLitros = document.getElementById('litros');
    const inputCosto = document.getElementById('costo');
    const boxMedioPago = document.getElementById('boxMedioPagoRepo');
    const selectSabor = document.getElementById('sabor_id');
    const optHelados = document.getElementById('optgroupHelados');
    const optCremoladas = document.getElementById('optgroupCremoladas');

    btnHelado.addEventListener('click', function() {
        btnHelado.classList.add('active');
        btnCremolada.classList.remove('active');
        inputTipo.value = 'bandeja_helado';
        inputLitros.value = '5.00';
        selectSabor.value = '';
    });

    btnCremolada.addEventListener('click', function() {
        btnCremolada.classList.add('active');
        btnHelado.classList.remove('active');
        inputTipo.value = 'balde_cremolada';
        inputLitros.value = '7.00';
        selectSabor.value = '';
    });

    inputCosto.addEventListener('input', function() {
        const val = parseFloat(inputCosto.value) || 0;
        if (val > 0) {
            boxMedioPago.classList.remove('hidden');
        } else {
            boxMedioPago.classList.add('hidden');
        }
    });
});
</script>
