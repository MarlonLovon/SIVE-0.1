<div class="reposiciones-page">
    <div class="page-header flex flex-between flex-wrap gap-2 mb-3">
        <div>
            <h1 class="page-title">🔄 Reposiciones de Stock</h1>
            <p class="text-muted">Registro de bandejas de helado (5L) y baldes de cremolada (7L) agregados al inventario</p>
        </div>
        <div class="header-actions">
            <a href="<?= url('reposiciones/crear') ?>" class="btn btn-primary">
                <span>➕ Nueva Reposición</span>
            </a>
        </div>
    </div>

    <!-- Tabla de Reposiciones -->
    <div class="card">
        <div class="card-header flex flex-between">
            <h3 class="card-title">Historial de Reposiciones</h3>
            <span class="badge badge-outline"><?= count($reposiciones) ?> registros</span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($reposiciones)): ?>
                <div class="empty-state text-center py-5">
                    <span class="empty-icon" style="font-size: 3rem;">🍨</span>
                    <h3 class="mt-2">No hay reposiciones registradas</h3>
                    <p class="text-muted">Cuando elabore o compre nuevas cubetas de helado o baldes de cremolada, regístrelos aquí.</p>
                    <a href="<?= url('reposiciones/crear') ?>" class="btn btn-primary mt-3">
                        Registrar primera reposición
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo de Recipiente</th>
                                <th>Sabor</th>
                                <th class="text-right">Volumen</th>
                                <th class="text-right">Costo</th>
                                <th>Pago / Caja</th>
                                <th>Observaciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reposiciones as $r): ?>
                                <tr>
                                    <td>
                                        <span class="font-bold"><?= formatDate($r['fecha']) ?></span>
                                    </td>
                                    <td>
                                        <?php if ($r['tipo'] === 'bandeja_helado'): ?>
                                            <span class="badge badge-primary">🍨 Bandeja Helado</span>
                                        <?php else: ?>
                                            <span class="badge badge-info">🍧 Balde Cremolada</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong class="text-base"><?= e($r['sabor_nombre'] ?? 'Sabor #' . $r['sabor_id']) ?></strong>
                                    </td>
                                    <td class="text-right font-bold text-primary">
                                        +<?= number_format((float)$r['litros'], 1) ?> L
                                    </td>
                                    <td class="text-right">
                                        <?php if ($r['costo'] > 0): ?>
                                            <span class="font-bold text-danger"><?= formatMoney($r['costo']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">S/ 0.00</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($r['costo'] > 0): ?>
                                            <span class="badge badge-outline">
                                                <?= e(ucfirst($r['medio_pago'] ?? 'efectivo')) ?>
                                            </span>
                                            <?php if (!empty($r['movimiento_caja_id'])): ?>
                                                <span class="text-xs text-success d-block" title="Egreso contabilizado en caja">✓ En caja</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-xs text-muted">Sin egreso</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="text-sm text-muted"><?= e($r['observaciones'] ?? '') ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
