<div class="config-page">
    <div class="page-header flex flex-between flex-wrap gap-2 mb-3">
        <div>
            <h1 class="page-title">⚙️ Configuración del Negocio</h1>
            <p class="text-muted">Ajuste de tarifas, factores de consumo estimado, catálogo de sabores y gastos</p>
        </div>
    </div>

    <!-- Pestañas de Navegación de Configuración -->
    <div class="tabs-nav flex gap-2 mb-4 border-bottom pb-2 flex-wrap">
        <button type="button" class="tab-btn active" data-tab="tabPrecios">
            <span>🏷️ Precios y Tarifas</span>
        </button>
        <button type="button" class="tab-btn" data-tab="tabConsumos">
            <span>📏 Factores de Consumo</span>
        </button>
        <button type="button" class="tab-btn" data-tab="tabSabores">
            <span>🍦 Catálogo de Sabores</span>
        </button>
        <button type="button" class="tab-btn" data-tab="tabCategorias">
            <span>📂 Categorías de Gastos</span>
        </button>
    </div>

    <!-- PESTAÑA 1: PRECIOS -->
    <div class="tab-pane active" id="tabPrecios">
        <div class="card max-w-700">
            <div class="card-header">
                <h3 class="card-title">Precios Vigentes de Venta</h3>
                <span class="text-xs text-muted">Los cambios no afectarán las ventas ya registradas históricamente.</span>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= url('config/guardar-precios') ?>">
                    <?= csrf_field() ?>

                    <h4 class="font-bold text-primary mb-2">🍨 Helados Artesanales</h4>
                    <div class="grid-2-cols gap-3 mb-4">
                        <div class="form-group mb-0">
                            <label for="p_h1" class="form-label font-bold">Vaso 4 oz (1 bola) - S/:</label>
                            <input type="number" step="0.50" min="0" class="form-control" id="p_h1" 
                                   name="precio_helado_1bola" value="<?= e($precios['precio_helado_1bola'] ?? '4.00') ?>" required>
                        </div>
                        <div class="form-group mb-0">
                            <label for="p_h2" class="form-label font-bold">Vaso 6 oz (2 bolas) - S/:</label>
                            <input type="number" step="0.50" min="0" class="form-control" id="p_h2" 
                                   name="precio_helado_2bolas" value="<?= e($precios['precio_helado_2bolas'] ?? '7.00') ?>" required>
                        </div>
                        <div class="form-group mb-0">
                            <label for="p_h3" class="form-label font-bold">Vaso 8 oz (3 bolas) - S/:</label>
                            <input type="number" step="0.50" min="0" class="form-control" id="p_h3" 
                                   name="precio_helado_3bolas" value="<?= e($precios['precio_helado_3bolas'] ?? '9.00') ?>" required>
                        </div>
                        <div class="form-group mb-0">
                            <label for="p_hlitro" class="form-label font-bold">Pote de 1 Litro - S/:</label>
                            <input type="number" step="0.50" min="0" class="form-control" id="p_hlitro" 
                                   name="precio_litro_helado" value="<?= e($precios['precio_litro_helado'] ?? '28.00') ?>" required>
                        </div>
                    </div>

                    <h4 class="font-bold text-info mb-2 border-top pt-3">🍧 Cremoladas de Fruta</h4>
                    <div class="grid-2-cols gap-3 mb-4">
                        <div class="form-group mb-0">
                            <label for="p_c12" class="form-label font-bold">1 o 2 Sabores (Tarifa Base) - S/:</label>
                            <input type="number" step="0.50" min="0" class="form-control" id="p_c12" 
                                   name="precio_cremolada_1_2_sabores" value="<?= e($precios['precio_cremolada_1_2_sabores'] ?? '6.00') ?>" required>
                            <span class="text-xs text-muted">Aplica a vasos de 6 oz y 12 oz con 1 o 2 sabores.</span>
                        </div>
                        <div class="form-group mb-0">
                            <label for="p_c3" class="form-label font-bold">3 Sabores - S/:</label>
                            <input type="number" step="0.50" min="0" class="form-control" id="p_c3" 
                                   name="precio_cremolada_3_sabores" value="<?= e($precios['precio_cremolada_3_sabores'] ?? '8.00') ?>" required>
                            <span class="text-xs text-muted">Tarifa especial cuando el cliente solicita 3 sabores combinados.</span>
                        </div>
                    </div>

                    <div class="form-actions text-right border-top pt-3">
                        <button type="submit" class="btn btn-primary">
                            <span>💾 Guardar Nuevos Precios</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- PESTAÑA 2: PARÁMETROS DE CONSUMO ESTIMADO -->
    <div class="tab-pane hidden" id="tabConsumos">
        <div class="card max-w-700">
            <div class="card-header">
                <h3 class="card-title">Parámetros de Calibración de Inventario</h3>
                <span class="text-xs text-muted">Ajustan el cálculo matemático de litros consumidos por porción servida.</span>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= url('config/guardar-consumos') ?>">
                    <?= csrf_field() ?>

                    <div class="form-group mb-3">
                        <label for="c_bola" class="form-label font-bold">Consumo por Bola de Helado (Litros):</label>
                        <input type="number" step="0.005" min="0.01" class="form-control" id="c_bola" 
                               name="consumo_estimado_bola_litros" value="<?= e($consumos['consumo_estimado_bola_litros'] ?? '0.05') ?>" required>
                        <span class="text-xs text-muted">Referencia: 0.050 L (50 ml) por bola con cuchara #16 (Ø 57mm).</span>
                    </div>

                    <div class="form-row grid-2-cols gap-3 mb-3">
                        <div class="form-group mb-0">
                            <label for="c_c6" class="form-label font-bold">Consumo Cremolada 6 oz (Litros):</label>
                            <input type="number" step="0.01" min="0.05" class="form-control" id="c_c6" 
                                   name="consumo_estimado_cremolada_6oz_litros" value="<?= e($consumos['consumo_estimado_cremolada_6oz_litros'] ?? '0.18') ?>" required>
                        </div>
                        <div class="form-group mb-0">
                            <label for="c_c12" class="form-label font-bold">Consumo Cremolada 12 oz (Litros):</label>
                            <input type="number" step="0.01" min="0.05" class="form-control" id="c_c12" 
                                   name="consumo_estimado_cremolada_12oz_litros" value="<?= e($consumos['consumo_estimado_cremolada_12oz_litros'] ?? '0.35') ?>" required>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="diametro_cuchara" class="form-label font-bold">Diámetro de Cuchara Automática (mm):</label>
                        <input type="number" step="1" min="30" max="100" class="form-control" id="diametro_cuchara" 
                               name="diametro_cuchara_mm" value="<?= e($consumos['diametro_cuchara_mm'] ?? '57') ?>">
                        <span class="text-xs text-muted">Valor de referencia física para calibración de porciones.</span>
                    </div>

                    <div class="form-row grid-2-cols gap-3 mb-4">
                        <div class="form-group mb-0">
                            <label for="litros_bandeja" class="form-label font-bold">Capacidad Bandeja Helado (L):</label>
                            <input type="number" step="0.5" min="1" class="form-control" id="litros_bandeja" 
                                   name="litros_bandeja_helado" value="<?= e($consumos['litros_bandeja_helado'] ?? '5') ?>" required>
                        </div>
                        <div class="form-group mb-0">
                            <label for="litros_balde" class="form-label font-bold">Capacidad Balde Cremolada (L):</label>
                            <input type="number" step="0.5" min="1" class="form-control" id="litros_balde" 
                                   name="litros_balde_cremolada" value="<?= e($consumos['litros_balde_cremolada'] ?? '7') ?>" required>
                        </div>
                    </div>

                    <div class="form-actions text-right border-top pt-3">
                        <button type="submit" class="btn btn-primary">
                            <span>💾 Guardar Parámetros de Consumo</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- PESTAÑA 3: SABORES -->
    <div class="tab-pane hidden" id="tabSabores">
        <div class="grid-2-cols gap-4">
            <!-- Formulario para Agregar Sabor -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">➕ Agregar Nuevo Sabor</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= url('config/sabores') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="accion" value="agregar">

                        <div class="form-group mb-3">
                            <label for="nombre_sabor" class="form-label font-bold">Nombre del Sabor:</label>
                            <input type="text" class="form-control" id="nombre_sabor" name="nombre" 
                                   placeholder="Ej: Pistacho, Maracuyá Sour, Lúcuma con chispas..." required>
                        </div>

                        <div class="form-group mb-3">
                            <label for="tipo_sabor" class="form-label font-bold">Tipo de Producto:</label>
                            <select class="form-control" id="tipo_sabor" name="tipo" required>
                                <option value="helado" selected>🍨 Helado Artesanal</option>
                                <option value="cremolada">🍧 Cremolada de Fruta</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">
                            <span>Agregar Sabor al Menú</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Listado y Conmutador de Sabores -->
            <div class="card">
                <div class="card-header flex flex-between">
                    <h3 class="card-title">Catálogo de Sabores</h3>
                    <span class="badge badge-outline"><?= count($sabores) ?> registrados</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive max-h-500 overflow-y-auto">
                        <table class="table table-compact mb-0">
                            <thead>
                                <tr>
                                    <th>Sabor</th>
                                    <th>Tipo</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-right">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sabores as $s): ?>
                                    <tr>
                                        <td><strong><?= e($s['nombre']) ?></strong></td>
                                        <td>
                                            <?php if ($s['tipo'] === 'helado'): ?>
                                                <span class="badge badge-primary">Helado</span>
                                            <?php else: ?>
                                                <span class="badge badge-info">Cremolada</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($s['activo']): ?>
                                                <span class="badge badge-success">Activo</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-right">
                                            <form method="POST" action="<?= url('config/sabores') ?>" style="display:inline;">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="accion" value="toggle">
                                                <input type="hidden" name="id" value="<?= e($s['id']) ?>">
                                                <button type="submit" class="btn btn-xs <?= $s['activo'] ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                                                    <?= $s['activo'] ? 'Desactivar' : 'Activar' ?>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- PESTAÑA 4: CATEGORÍAS DE GASTO -->
    <div class="tab-pane hidden" id="tabCategorias">
        <div class="grid-2-cols gap-4">
            <!-- Formulario Agregar Categoría -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">➕ Nueva Categoría de Egreso</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= url('config/categorias') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="accion" value="agregar">

                        <div class="form-group mb-3">
                            <label for="nombre_categoria" class="form-label font-bold">Nombre de la Categoría:</label>
                            <input type="text" class="form-control" id="nombre_categoria" name="nombre" 
                                   placeholder="Ej: Envases y Vasos, Hielo, Publicidad..." required>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">
                            <span>Crear Categoría</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Listado y Conmutador de Categorías -->
            <div class="card">
                <div class="card-header flex flex-between">
                    <h3 class="card-title">Categorías Operativas</h3>
                    <span class="badge badge-outline"><?= count($categorias) ?> registradas</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive max-h-500 overflow-y-auto">
                        <table class="table table-compact mb-0">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-right">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categorias as $cat): ?>
                                    <tr>
                                        <td><strong><?= e($cat['nombre']) ?></strong></td>
                                        <td class="text-center">
                                            <?php if ($cat['activo']): ?>
                                                <span class="badge badge-success">Activa</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Inactiva</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-right">
                                            <form method="POST" action="<?= url('config/categorias') ?>" style="display:inline;">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="accion" value="toggle">
                                                <input type="hidden" name="id" value="<?= e($cat['id']) ?>">
                                                <button type="submit" class="btn btn-xs <?= $cat['activo'] ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                                                    <?= $cat['activo'] ? 'Desactivar' : 'Activar' ?>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Lógica de navegación entre pestañas -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabPanes = document.querySelectorAll('.tab-pane');

    tabButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const targetId = this.dataset.tab;

            tabButtons.forEach(b => b.classList.remove('active'));
            tabPanes.forEach(p => p.classList.add('hidden'));

            this.classList.add('active');
            const targetPane = document.getElementById(targetId);
            if (targetPane) {
                targetPane.classList.remove('hidden');
            }
        });
    });
});
</script>
