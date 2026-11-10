<div class="ventas-page">
    <!-- Encabezado de Página -->
    <div class="page-header flex flex-between flex-wrap gap-2 mb-3">
        <div>
            <h1 class="page-title">📋 Ventas del Día</h1>
            <p class="text-muted">Registro detallado de transacciones del <?= date('d/m/Y') ?></p>
        </div>
        <div class="header-actions">
            <?php if ($cajaAbierta): ?>
                <a href="<?= url('ventas/crear') ?>" class="btn btn-primary btn-lg">
                    <span>🛒 Nueva Venta</span>
                </a>
            <?php else: ?>
                <a href="<?= url('caja/abrir') ?>" class="btn btn-success">
                    <span>🔓 Abrir Caja para Vender</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Advertencia de Caja Cerrada -->
    <?php if (!$cajaAbierta): ?>
        <div class="alert alert-warning mb-3">
            <div class="alert-icon">⚠️</div>
            <div class="alert-content">
                <strong>Caja no disponible:</strong> 
                <?php if ($cajaHoy && $cajaHoy['estado'] === 'cerrada'): ?>
                    La caja del día de hoy ya fue cerrada formalmente. No se admiten nuevas ventas.
                <?php else: ?>
                    Aún no se ha realizado la apertura de caja para hoy. Para registrar transacciones debe abrir caja.
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Tarjetas de Resumen Rápido -->
    <div class="stat-grid mb-3">
        <div class="stat-card stat-primary">
            <div class="stat-icon">💰</div>
            <div class="stat-details">
                <span class="stat-label">Total Recaudado</span>
                <h2 class="stat-value"><?= formatMoney($totalGeneral) ?></h2>
                <span class="stat-subtext"><?= e($conteoVentas) ?> ventas activas</span>
            </div>
        </div>
        <div class="stat-card stat-success">
            <div class="stat-icon">💵</div>
            <div class="stat-details">
                <span class="stat-label">Total Efectivo</span>
                <h2 class="stat-value"><?= formatMoney($totalEfectivo) ?></h2>
                <span class="stat-subtext">Caja física</span>
            </div>
        </div>
        <div class="stat-card stat-info">
            <div class="stat-icon">📱</div>
            <div class="stat-details">
                <span class="stat-label">Total Yape</span>
                <h2 class="stat-value"><?= formatMoney($totalYape) ?></h2>
                <span class="stat-subtext">Transferencias</span>
            </div>
        </div>
        <div class="stat-card stat-accent">
            <div class="stat-icon">🏷️</div>
            <div class="stat-details">
                <span class="stat-label">Comprobantes</span>
                <h2 class="stat-value"><?= e($conteoVentas + $conteoAnuladas) ?></h2>
                <span class="stat-subtext"><?= e($conteoAnuladas) ?> anuladas</span>
            </div>
        </div>
    </div>

    <!-- Grilla de Ventas -->
    <div class="card">
        <div class="card-header flex flex-between">
            <h3 class="card-title">Listado de Ventas de Hoy</h3>
            <span class="badge badge-outline"><?= count($ventas) ?> transacciones</span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($ventas)): ?>
                <div class="empty-state text-center py-5">
                    <span class="empty-icon" style="font-size: 3rem;">🧾</span>
                    <h3 class="mt-2">No hay ventas registradas</h3>
                    <p class="text-muted">Aún no se han generado comprobantes en la caja de hoy.</p>
                    <?php if ($cajaAbierta): ?>
                        <a href="<?= url('ventas/crear') ?>" class="btn btn-primary mt-3">
                            <span>🛒 Iniciar Primera Venta</span>
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th style="width: 70px;">#ID</th>
                                <th style="width: 100px;">Hora</th>
                                <th>Detalle de Productos</th>
                                <th>Medio de Pago</th>
                                <th class="text-right">Total</th>
                                <th class="text-center" style="width: 110px;">Estado</th>
                                <th class="text-center" style="width: 90px;">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ventas as $v): ?>
                                <tr class="<?= $v['anulada'] ? 'row-anulada opacity-60' : '' ?>">
                                    <td>
                                        <span class="font-bold text-muted">#<?= e($v['id']) ?></span>
                                    </td>
                                    <td>
                                        <span class="font-medium"><?= date('H:i:s', strtotime($v['fecha_hora'])) ?></span>
                                    </td>
                                    <td>
                                        <div class="items-summary font-medium">
                                            <?= e($v['resumen_items'] ?: 'Sin descripción detallada') ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($v['medio_pago'] === 'efectivo'): ?>
                                            <span class="badge badge-success">💵 Efectivo</span>
                                        <?php elseif ($v['medio_pago'] === 'yape'): ?>
                                            <span class="badge badge-info">📱 Yape</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning" title="Efectivo: <?= formatMoney($v['monto_efectivo']) ?> + Yape: <?= formatMoney($v['monto_yape']) ?>">
                                                ⚡ Combinado
                                            </span>
                                            <div class="text-xs text-muted">
                                                Ef: <?= formatMoney($v['monto_efectivo']) ?> / Yp: <?= formatMoney($v['monto_yape']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-right font-bold text-lg">
                                        <?= formatMoney($v['total']) ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($v['anulada']): ?>
                                            <span class="badge badge-danger">ANULADA</span>
                                        <?php else: ?>
                                            <span class="badge badge-success">COMPLETADA</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= url('ventas/detalle/' . $v['id']) ?>" class="btn btn-sm btn-outline" title="Ver Comprobante">
                                            👁️ Ver
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-total-row">
                                <td colspan="4" class="text-right font-bold">TOTAL GENERAL (Activas):</td>
                                <td class="text-right font-bold text-primary text-xl"><?= formatMoney($totalGeneral) ?></td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
