<div class="inventario-page">
    <div class="page-header flex flex-between flex-wrap gap-2 mb-3">
        <div>
            <h1 class="page-title">📦 Inventario Estimado por Sabor</h1>
            <p class="text-muted">Balance teórico basado en reposiciones, ventas y ajustes manuales</p>
        </div>
        <div class="header-actions">
            <a href="<?= url('reposiciones/crear') ?>" class="btn btn-primary">
                <span>🔄 Nueva Reposición</span>
            </a>
        </div>
    </div>

    <!-- Gran Banner de Inventario Estimado -->
    <div class="alert alert-warning mb-4 py-3">
        <div class="alert-icon" style="font-size: 1.8rem;">⚠️</div>
        <div class="alert-content">
            <h3 class="font-bold text-lg mb-1">Inventario Estimado (No Exacto)</h3>
            <p class="mb-0 text-sm">
                Los valores mostrados son aproximaciones matemáticas basadas en las bandejas/baldes repuestos menos el consumo estimado por vaso servido. 
                Las ventas sin sabores específicos no descuentan de un sabor en particular. Use el botón <strong>"Ajustar"</strong> para corregir el stock según la inspección visual.
            </p>
        </div>
    </div>

    <!-- Leyenda de Niveles de Stock -->
    <div class="flex gap-3 mb-3 flex-wrap text-sm">
        <span class="flex flex-center gap-1">
            <span class="legend-dot bg-success"></span>
            <strong>Óptimo:</strong> Más de 3.0 Litros
        </span>
        <span class="flex flex-center gap-1">
            <span class="legend-dot bg-warning"></span>
            <strong>Medio:</strong> Entre 1.0 y 3.0 Litros
        </span>
        <span class="flex flex-center gap-1">
            <span class="legend-dot bg-danger"></span>
            <strong>Bajo / Crítico:</strong> Menos de 1.0 Litro
        </span>
    </div>

    <!-- SECCIÓN 1: HELADOS ARTESANALES -->
    <div class="card mb-4">
        <div class="card-header flex flex-between">
            <div class="flex flex-center gap-2">
                <span style="font-size: 1.5rem;">🍦</span>
                <h3 class="card-title">Sabores de Helado (Bandejas de 5L)</h3>
            </div>
            <span class="badge badge-primary"><?= count($helados) ?> sabores activos</span>
        </div>
        <div class="card-body">
            <?php if (empty($helados)): ?>
                <p class="text-muted text-center py-3">No hay sabores de helado registrados.</p>
            <?php else: ?>
                <div class="grid-2-cols gap-3">
                    <?php foreach ($helados as $h): 
                        $stock = (float)$h['stock_estimado'];
                        $capacidadMax = 5.0; // Referencia de bandeja
                        $porcentaje = max(0, min(100, round(($stock / $capacidadMax) * 100)));
                        
                        $nivelClase = 'stock-success';
                        $badgeClase = 'badge-success';
                        if ($stock < 1.0) {
                            $nivelClase = 'stock-danger';
                            $badgeClase = 'badge-danger';
                        } elseif ($stock <= 3.0) {
                            $nivelClase = 'stock-warning';
                            $badgeClase = 'badge-warning';
                        }
                    ?>
                        <div class="inventory-card p-3 border rounded">
                            <div class="flex flex-between mb-2">
                                <h4 class="font-bold mb-0 text-base"><?= e($h['nombre']) ?></h4>
                                <span class="badge <?= $badgeClase ?>">
                                    <?= number_format($stock, 2) ?> L
                                </span>
                            </div>

                            <!-- Barra de Progreso Visual de Stock -->
                            <div class="stock-progress-bar mb-2">
                                <div class="stock-progress-fill <?= $nivelClase ?>" style="width: <?= $porcentaje ?>%;"></div>
                            </div>

                            <div class="inventory-details text-xs text-muted mb-3 flex flex-between">
                                <span>Repuesto: <?= number_format((float)($h['litros_repuestos'] ?? 0), 1) ?>L</span>
                                <span>Consumo: <?= number_format((float)($h['litros_consumidos'] ?? 0), 1) ?>L</span>
                                <span>Ajustes: <?= number_format((float)($h['litros_ajustados'] ?? 0), 1) ?>L</span>
                            </div>

                            <div class="flex flex-between flex-center text-xs border-top pt-2">
                                <span class="text-muted">
                                    Última reposición: 
                                    <strong><?= !empty($h['ultima_reposicion']) ? formatDate($h['ultima_reposicion']) : 'Sin registros' ?></strong>
                                </span>
                                <button type="button" class="btn btn-sm btn-outline" 
                                        onclick="abrirModalAjuste(<?= e($h['sabor_id']) ?>, '<?= e(addslashes($h['nombre'])) ?>', <?= $stock ?>)">
                                    ✏️ Ajustar
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- SECCIÓN 2: CREMOLADAS DE FRUTA -->
    <div class="card mb-4">
        <div class="card-header flex flex-between">
            <div class="flex flex-center gap-2">
                <span style="font-size: 1.5rem;">🍧</span>
                <h3 class="card-title">Sabores de Cremolada (Baldes de 7L)</h3>
            </div>
            <span class="badge badge-info"><?= count($cremoladas) ?> sabores activos</span>
        </div>
        <div class="card-body">
            <?php if (empty($cremoladas)): ?>
                <p class="text-muted text-center py-3">No hay sabores de cremolada registrados.</p>
            <?php else: ?>
                <div class="grid-2-cols gap-3">
                    <?php foreach ($cremoladas as $c): 
                        $stock = (float)$c['stock_estimado'];
                        $capacidadMax = 7.0; // Referencia de balde
                        $porcentaje = max(0, min(100, round(($stock / $capacidadMax) * 100)));
                        
                        $nivelClase = 'stock-success';
                        $badgeClase = 'badge-success';
                        if ($stock < 1.0) {
                            $nivelClase = 'stock-danger';
                            $badgeClase = 'badge-danger';
                        } elseif ($stock <= 3.0) {
                            $nivelClase = 'stock-warning';
                            $badgeClase = 'badge-warning';
                        }
                    ?>
                        <div class="inventory-card p-3 border rounded">
                            <div class="flex flex-between mb-2">
                                <h4 class="font-bold mb-0 text-base"><?= e($c['nombre']) ?></h4>
                                <span class="badge <?= $badgeClase ?>">
                                    <?= number_format($stock, 2) ?> L
                                </span>
                            </div>

                            <!-- Barra de Progreso Visual de Stock -->
                            <div class="stock-progress-bar mb-2">
                                <div class="stock-progress-fill <?= $nivelClase ?>" style="width: <?= $porcentaje ?>%;"></div>
                            </div>

                            <div class="inventory-details text-xs text-muted mb-3 flex flex-between">
                                <span>Repuesto: <?= number_format((float)($c['litros_repuestos'] ?? 0), 1) ?>L</span>
                                <span>Consumo: <?= number_format((float)($c['litros_consumidos'] ?? 0), 1) ?>L</span>
                                <span>Ajustes: <?= number_format((float)($c['litros_ajustados'] ?? 0), 1) ?>L</span>
                            </div>

                            <div class="flex flex-between flex-center text-xs border-top pt-2">
                                <span class="text-muted">
                                    Última reposición: 
                                    <strong><?= !empty($c['ultima_reposicion']) ? formatDate($c['ultima_reposicion']) : 'Sin registros' ?></strong>
                                </span>
                                <button type="button" class="btn btn-sm btn-outline" 
                                        onclick="abrirModalAjuste(<?= e($c['sabor_id']) ?>, '<?= e(addslashes($c['nombre'])) ?>', <?= $stock ?>)">
                                    ✏️ Ajustar
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal para Ajuste Manual de Inventario -->
<div class="modal-overlay hidden" id="modalAjusteOverlay">
    <div class="modal max-w-500">
        <div class="modal-header flex flex-between">
            <h3 class="modal-title" id="modalAjusteTitulo">Ajustar Inventario</h3>
            <button type="button" class="btn-close" onclick="cerrarModalAjuste()" aria-label="Cerrar">&times;</button>
        </div>
        <form method="POST" action="<?= url('inventario/ajustar') ?>" id="formAjusteInventario">
            <?= csrf_field() ?>
            <input type="hidden" name="sabor_id" id="ajusteSaborId" value="">

            <div class="modal-body p-3">
                <p class="text-sm text-muted mb-3" id="modalAjusteInfo">
                    Stock actual estimado: <strong id="modalStockActual">0.00 L</strong>
                </p>

                <div class="form-group mb-3">
                    <label for="litros_ajuste" class="form-label font-bold">
                        Variación de Litros (+ para añadir, - para restar):
                    </label>
                    <input type="number" step="0.10" class="form-control" id="litros_ajuste" name="litros_ajuste" 
                           placeholder="Ej: -0.5 para merma o +2.0 para adición" required>
                    <span class="text-xs text-muted">Use valores negativos si hubo descongelamiento o merma.</span>
                </div>

                <div class="form-group mb-0">
                    <label for="motivo" class="form-label font-bold">Motivo del Ajuste:</label>
                    <input type="text" class="form-control" id="motivo" name="motivo" 
                           placeholder="Ej: Calibración física, degustaciones, merma por corte eléctrico..." required>
                </div>
            </div>

            <div class="modal-footer flex flex-between p-3 border-top">
                <button type="button" class="btn btn-outline" onclick="cerrarModalAjuste()">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Ajuste</button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalAjuste(id, nombre, stock) {
    document.getElementById('ajusteSaborId').value = id;
    document.getElementById('modalAjusteTitulo').textContent = 'Ajustar Inventario: ' + nombre;
    document.getElementById('modalStockActual').textContent = stock.toFixed(2) + ' L';
    document.getElementById('litros_ajuste').value = '';
    document.getElementById('motivo').value = '';
    document.getElementById('modalAjusteOverlay').classList.remove('hidden');
}

function cerrarModalAjuste() {
    document.getElementById('modalAjusteOverlay').classList.add('hidden');
}
</script>
