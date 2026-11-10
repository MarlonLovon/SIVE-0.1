<div class="dashboard-page">
    <!-- Header del Dashboard con Bienvenida y Acciones Rápidas -->
    <div class="page-header flex flex-between flex-wrap gap-2 mb-3">
        <div>
            <h1 class="page-title">📊 Panel de Control</h1>
            <p class="text-muted">Resumen operativo y ventas del <?= date('d/m/Y') ?></p>
        </div>
        <div class="header-actions flex gap-2">
            <?php if ($cajaEstado === 'abierta'): ?>
                <a href="<?= url('ventas/crear') ?>" class="btn btn-primary btn-lg">
                    <span>🛒 Nueva Venta</span>
                </a>
                <a href="<?= url('caja') ?>" class="btn btn-outline">
                    <span>💰 Ver Caja</span>
                </a>
            <?php else: ?>
                <a href="<?= url('caja/abrir') ?>" class="btn btn-success btn-lg">
                    <span>🔓 Abrir Caja de Hoy</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Alertas y Estado de la Caja -->
    <?php if ($cajaAnteriorSinCerrar): ?>
        <div class="alert alert-warning alert-dismissible mb-3" role="alert">
            <div class="alert-icon">⚠️</div>
            <div class="alert-content">
                <strong>¡Atención! Caja anterior sin cerrar:</strong>
                La caja del día <strong><?= formatDate($cajaAnteriorSinCerrar['fecha']) ?></strong> quedó abierta.
                <a href="<?= url('caja') ?>" class="alert-link">Haz clic aquí para revisar y regularizar</a>.
            </div>
        </div>
    <?php endif; ?>

    <!-- Banner de Estado de Caja Registradora -->
    <div class="card card-glass mb-3 caja-status-banner">
        <div class="caja-status-wrapper flex flex-between flex-wrap gap-2">
            <div class="flex flex-center gap-2">
                <span class="status-indicator <?= $cajaEstado === 'abierta' ? 'status-open' : ($cajaEstado === 'cerrada' ? 'status-closed' : 'status-warning') ?>"></span>
                <div>
                    <h3 class="status-title">
                        Estado de Caja: 
                        <?php if ($cajaEstado === 'abierta'): ?>
                            <span class="text-success font-bold">ABIERTA</span>
                        <?php elseif ($cajaEstado === 'cerrada'): ?>
                            <span class="text-muted font-bold">CERRADA</span>
                        <?php else: ?>
                            <span class="text-warning font-bold">PENDIENTE DE APERTURA</span>
                        <?php endif; ?>
                    </h3>
                    <p class="text-muted text-sm">
                        <?php if ($cajaHoy): ?>
                            Apertura: <?= formatDateTime($cajaHoy['apertura']) ?> · Fondo Inicial: <?= formatMoney($cajaHoy['fondo_inicial']) ?>
                        <?php else: ?>
                            Aún no se ha registrado apertura de caja para el día de hoy.
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <div class="caja-quick-btns">
                <?php if ($cajaEstado === 'abierta'): ?>
                    <a href="<?= url('caja/cerrar') ?>" class="btn btn-sm btn-outline-danger">Cerrar Caja</a>
                <?php elseif ($cajaEstado === 'sin_abrir'): ?>
                    <a href="<?= url('caja/abrir') ?>" class="btn btn-sm btn-success">Abrir Ahora</a>
                <?php else: ?>
                    <a href="<?= url('caja') ?>" class="btn btn-sm btn-outline">Resumen de Caja</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Grid de Tarjetas de Métricas (Stat Cards) -->
    <div class="stat-grid mb-3">
        <!-- Tarjeta 1: Total Ventas Hoy -->
        <div class="stat-card stat-primary">
            <div class="stat-icon">💰</div>
            <div class="stat-details">
                <span class="stat-label">Total Recaudado Hoy</span>
                <h2 class="stat-value"><?= formatMoney($totalHoy) ?></h2>
                <span class="stat-subtext"><?= e($ventasCount) ?> ventas registradas</span>
            </div>
        </div>

        <!-- Tarjeta 2: Efectivo en Ventas -->
        <div class="stat-card stat-success">
            <div class="stat-icon">💵</div>
            <div class="stat-details">
                <span class="stat-label">Ventas en Efectivo</span>
                <h2 class="stat-value"><?= formatMoney($efectivoHoy) ?></h2>
                <span class="stat-subtext">Disponible físico</span>
            </div>
        </div>

        <!-- Tarjeta 3: Ventas Yape -->
        <div class="stat-card stat-info">
            <div class="stat-icon">📱</div>
            <div class="stat-details">
                <span class="stat-label">Ventas vía Yape</span>
                <h2 class="stat-value"><?= formatMoney($yapeHoy) ?></h2>
                <span class="stat-subtext">Transferencia digital</span>
            </div>
        </div>

        <!-- Tarjeta 4: Volumen de Vasos -->
        <div class="stat-card stat-accent">
            <div class="stat-icon">🍨</div>
            <div class="stat-details">
                <span class="stat-label">Vasos Servidos Hoy</span>
                <h2 class="stat-value"><?= e($totalHeladosHoy + $totalCremoladasHoy) ?></h2>
                <span class="stat-subtext"><?= e($totalHeladosHoy) ?> Helados · <?= e($totalCremoladasHoy) ?> Cremoladas</span>
            </div>
        </div>
    </div>

    <!-- Sección de Gráficos Estadísticos -->
    <div class="grid-2-cols mb-3">
        <!-- Gráfico 1: Ventas por Tipo de Producto -->
        <div class="card">
            <div class="card-header flex flex-between">
                <h3 class="card-title">🍧 Distribución del Día</h3>
                <span class="badge badge-info">Hoy</span>
            </div>
            <div class="card-body">
                <div class="chart-container" style="position: relative; height: 260px;">
                    <canvas id="chartProductosHoy"></canvas>
                </div>
            </div>
        </div>

        <!-- Gráfico 2: Evolución Semanal de Ventas -->
        <div class="card">
            <div class="card-header flex flex-between">
                <h3 class="card-title">📈 Ventas de los Últimos 7 Días</h3>
                <span class="badge badge-primary">Efectivo vs Yape</span>
            </div>
            <div class="card-body">
                <div class="chart-container" style="position: relative; height: 260px;">
                    <canvas id="chartVentasSemana"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Sección Inferior: Sabores Populares y Últimas Ventas -->
    <div class="grid-2-cols mb-3">
        <!-- Sabores Populares -->
        <div class="card">
            <div class="card-header flex flex-between">
                <h3 class="card-title">⭐ Sabores Más Pedidos</h3>
                <span class="badge badge-outline">Últimos 30 días</span>
            </div>
            <div class="card-body">
                <?php if (empty($saboresPopulares)): ?>
                    <div class="empty-state text-center py-4">
                        <span class="empty-icon">🍦</span>
                        <p class="text-muted">Aún no hay suficientes registros de sabores vendidos.</p>
                    </div>
                <?php else: ?>
                    <div class="chart-container" style="position: relative; height: 260px;">
                        <canvas id="chartSaboresPopulares"></canvas>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tabla de Últimas Ventas del Día -->
        <div class="card">
            <div class="card-header flex flex-between">
                <h3 class="card-title">🕒 Últimas Ventas Registradas</h3>
                <a href="<?= url('ventas') ?>" class="btn btn-sm btn-outline">Ver todas</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($ventasRecientes)): ?>
                    <div class="empty-state text-center py-4">
                        <span class="empty-icon">🛒</span>
                        <p class="text-muted">No se han registrado ventas el día de hoy.</p>
                        <?php if ($cajaEstado === 'abierta'): ?>
                            <a href="<?= url('ventas/crear') ?>" class="btn btn-sm btn-primary mt-2">Registrar primera venta</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-compact">
                            <thead>
                                <tr>
                                    <th>Hora</th>
                                    <th>Detalle</th>
                                    <th>Pago</th>
                                    <th class="text-right">Total</th>
                                    <th class="text-center">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ventasRecientes as $v): ?>
                                    <tr class="<?= $v['anulada'] ? 'row-anulada' : '' ?>">
                                        <td>
                                            <span class="font-medium"><?= date('H:i', strtotime($v['fecha_hora'])) ?></span>
                                        </td>
                                        <td>
                                            <span class="text-sm truncate d-block" style="max-width: 180px;" title="<?= e($v['descripcion_items'] ?? '') ?>">
                                                <?= e($v['descripcion_items'] ?: 'Sin detalle') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($v['medio_pago'] === 'efectivo'): ?>
                                                <span class="badge badge-success">💵 Efectivo</span>
                                            <?php elseif ($v['medio_pago'] === 'yape'): ?>
                                                <span class="badge badge-info">📱 Yape</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">⚡ Combinado</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-right font-bold">
                                            <?= formatMoney($v['total']) ?>
                                            <?php if ($v['anulada']): ?>
                                                <span class="badge badge-danger text-xs ml-1">Anulada</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <a href="<?= url('ventas/detalle/' . $v['id']) ?>" class="btn btn-sm btn-icon" title="Ver Detalle">
                                                👁️
                                            </a>
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
</div>

<!-- Inyección de datos serializados para Chart.js en public/js/dashboard.js -->
<script>
    const chartData = <?= json_encode($chartData, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
</script>
