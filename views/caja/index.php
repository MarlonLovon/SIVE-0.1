<div class="caja-page">
    <div class="page-header flex flex-between flex-wrap gap-2 mb-3">
        <div>
            <h1 class="page-title">💰 Control Diario de Caja</h1>
            <p class="text-muted">Apertura, arqueo, movimientos de gastos y cierre diario</p>
        </div>
        <div class="header-actions">
            <?php if ($cajaEstado === 'abierta'): ?>
                <a href="<?= url('caja/cerrar') ?>" class="btn btn-danger btn-lg">
                    <span>🔒 Cerrar Caja de Hoy</span>
                </a>
            <?php elseif ($cajaEstado === 'sin_abrir'): ?>
                <a href="<?= url('caja/abrir') ?>" class="btn btn-success btn-lg">
                    <span>🔓 Abrir Caja de Hoy</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Alerta: Caja anterior sin cerrar -->
    <?php if ($cajaAnteriorSinCerrar): ?>
        <div class="alert alert-warning mb-3">
            <div class="alert-icon">⚠️</div>
            <div class="alert-content">
                <strong>Advertencia importante de Arqueo:</strong>
                La caja del día <strong><?= formatDate($cajaAnteriorSinCerrar['fecha']) ?></strong> quedó en estado <strong>ABIERTA</strong>.
                Se recomienda verificar el dinero físico de esa jornada y cerrar la caja pendiente para mantener la correlación contable.
            </div>
        </div>
    <?php endif; ?>

    <!-- CASO 1: CAJA SIN ABRIR HOY -->
    <?php if ($cajaEstado === 'sin_abrir'): ?>
        <div class="card text-center py-5 mb-4">
            <div class="card-body">
                <span style="font-size: 3.5rem;">🔒</span>
                <h2 class="mt-2">La caja de hoy aún no ha sido abierta</h2>
                <p class="text-muted max-w-500 mx-auto mt-2">
                    Para comenzar a registrar ventas del día <?= date('d/m/Y') ?>, establezca el fondo inicial en efectivo y aperture la caja registradora.
                </p>
                <div class="mt-4">
                    <a href="<?= url('caja/abrir') ?>" class="btn btn-success btn-lg">
                        <span>🔓 Iniciar Apertura con Fondo Inicial</span>
                    </a>
                </div>
            </div>
        </div>

    <!-- CASO 2: CAJA ABIERTA HOY -->
    <?php elseif ($cajaEstado === 'abierta'): ?>
        <!-- Estado Prominente de Caja Abierta -->
        <div class="card card-glass mb-3 border-success">
            <div class="card-body flex flex-between flex-wrap gap-2 py-3">
                <div class="flex flex-center gap-2">
                    <span class="status-indicator status-open"></span>
                    <div>
                        <h3 class="font-bold text-success mb-0">CAJA ABIERTA</h3>
                        <span class="text-xs text-muted">
                            Apertura: <?= formatDateTime($cajaHoy['apertura']) ?> · Fecha: <?= formatDate($cajaHoy['fecha']) ?>
                        </span>
                    </div>
                </div>
                <div class="flex flex-center gap-2">
                    <a href="<?= url('ventas/crear') ?>" class="btn btn-sm btn-primary">🛒 Nueva Venta</a>
                    <a href="<?= url('caja/cerrar') ?>" class="btn btn-sm btn-outline-danger">🔒 Arqueo y Cierre</a>
                </div>
            </div>
        </div>

        <!-- Tarjetas de Arqueo en Tiempo Real -->
        <div class="stat-grid mb-3">
            <div class="stat-card">
                <div class="stat-icon">🪙</div>
                <div class="stat-details">
                    <span class="stat-label">Fondo Inicial</span>
                    <h2 class="stat-value"><?= formatMoney($resumen['fondo_inicial'] ?? 0) ?></h2>
                    <span class="stat-subtext">Caja chica apertura</span>
                </div>
            </div>

            <div class="stat-card stat-success">
                <div class="stat-icon">💵</div>
                <div class="stat-details">
                    <span class="stat-label">(+) Ventas Efectivo</span>
                    <h2 class="stat-value"><?= formatMoney($resumen['ventas_efectivo'] ?? 0) ?></h2>
                    <span class="stat-subtext">Ingreso físico directo</span>
                </div>
            </div>

            <div class="stat-card stat-danger">
                <div class="stat-icon">📤</div>
                <div class="stat-details">
                    <span class="stat-label">(-) Gastos / Retiros</span>
                    <h2 class="stat-value"><?= formatMoney(($resumen['gastos_efectivo'] ?? 0) + ($resumen['retiros_efectivo'] ?? 0)) ?></h2>
                    <span class="stat-subtext">Salidas en efectivo</span>
                </div>
            </div>

            <div class="stat-card stat-primary">
                <div class="stat-icon">🎯</div>
                <div class="stat-details">
                    <span class="stat-label">(=) Efectivo Esperado</span>
                    <h2 class="stat-value text-primary"><?= formatMoney($resumen['efectivo_esperado'] ?? 0) ?></h2>
                    <span class="stat-subtext">Debe haber en gaveta</span>
                </div>
            </div>
        </div>

        <!-- Panel Secundario: Total Yape Digital -->
        <div class="card mb-3 p-3 bg-light">
            <div class="flex flex-between flex-wrap gap-2">
                <div class="flex flex-center gap-2">
                    <span style="font-size: 1.8rem;">📱</span>
                    <div>
                        <strong class="d-block">Ventas Cobradas por Yape:</strong>
                        <span class="text-xs text-muted">Este dinero está en la cuenta bancaria, no en el efectivo físico de la caja.</span>
                    </div>
                </div>
                <div class="text-right">
                    <span class="font-bold text-xl text-info"><?= formatMoney($resumen['ventas_yape'] ?? 0) ?></span>
                </div>
            </div>
        </div>

        <!-- Formulario Rápido para Egresos y Tabla de Movimientos -->
        <div class="grid-2-cols mb-3">
            <!-- Formulario para Registrar Gasto o Retiro -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">💸 Registrar Egreso de Caja</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= url('caja/registrar-gasto') ?>">
                        <?= csrf_field() ?>

                        <div class="form-group mb-2">
                            <label class="form-label font-bold">Tipo de Egreso:</label>
                            <div class="flex gap-2">
                                <label class="radio-label flex flex-center gap-1">
                                    <input type="radio" name="tipo" value="gasto" checked>
                                    <span>Gasto Operativo</span>
                                </label>
                                <label class="radio-label flex flex-center gap-1">
                                    <input type="radio" name="tipo" value="retiro">
                                    <span>Retiro de Caja</span>
                                </label>
                            </div>
                        </div>

                        <div class="form-row grid-2-cols gap-2 mb-2">
                            <div class="form-group mb-0">
                                <label for="monto" class="form-label font-bold">Monto (S/):</label>
                                <input type="number" step="0.50" min="0.10" class="form-control" id="monto" name="monto" placeholder="0.00" required>
                            </div>
                            <div class="form-group mb-0">
                                <label for="medio_pago" class="form-label font-bold">Medio de Pago:</label>
                                <select class="form-control" id="medio_pago" name="medio_pago">
                                    <option value="efectivo" selected>💵 Efectivo (Gaveta)</option>
                                    <option value="yape">📱 Yape (Digital)</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group mb-2">
                            <label for="categoria_gasto_id" class="form-label font-bold">Categoría de Gasto:</label>
                            <select class="form-control" id="categoria_gasto_id" name="categoria_gasto_id">
                                <option value="">-- Sin categoría o Retiro --</option>
                                <?php foreach ($categoriasGasto as $cat): ?>
                                    <option value="<?= e($cat['id']) ?>"><?= e($cat['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label for="descripcion" class="form-label font-bold">Motivo / Descripción:</label>
                            <input type="text" class="form-control" id="descripcion" name="descripcion" 
                                   placeholder="Ej: Bolsas de plástico, hielo, almuerzo..." required>
                        </div>

                        <button type="submit" class="btn btn-warning btn-block">
                            <span>💸 Registrar Salida de Dinero</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Movimientos Registrados en el Día -->
            <div class="card">
                <div class="card-header flex flex-between">
                    <h3 class="card-title">📜 Movimientos de Caja Hoy</h3>
                    <span class="badge badge-outline"><?= count($movimientos) ?> registros</span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($movimientos)): ?>
                        <div class="empty-state text-center py-4">
                            <span class="empty-icon">🪙</span>
                            <p class="text-muted">No hay gastos ni retiros registrados en esta caja.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-compact mb-0">
                                <thead>
                                    <tr>
                                        <th>Hora</th>
                                        <th>Tipo</th>
                                        <th>Descripción</th>
                                        <th>Pago</th>
                                        <th class="text-right">Monto</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($movimientos as $m): ?>
                                        <tr>
                                            <td><?= date('H:i', strtotime($m['fecha_hora'])) ?></td>
                                            <td>
                                                <?php if ($m['tipo'] === 'retiro'): ?>
                                                    <span class="badge badge-danger">Retiro</span>
                                                <?php elseif ($m['tipo'] === 'reposicion'): ?>
                                                    <span class="badge badge-info">Reposición</span>
                                                <?php else: ?>
                                                    <span class="badge badge-warning">Gasto</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="font-medium"><?= e($m['descripcion']) ?></div>
                                                <?php if (!empty($m['categoria_nombre'])): ?>
                                                    <span class="text-xs text-muted"><?= e($m['categoria_nombre']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="text-xs"><?= e($m['medio_pago']) ?></span>
                                            </td>
                                            <td class="text-right font-bold text-danger">
                                                -<?= formatMoney($m['monto']) ?>
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

    <!-- CASO 3: CAJA YA CERRADA HOY -->
    <?php else: ?>
        <div class="card card-glass mb-4 border-muted">
            <div class="card-header flex flex-between">
                <div>
                    <h2 class="card-title text-muted">🔒 Caja del Día Cerrada</h2>
                    <p class="text-xs text-muted">Apertura: <?= formatDateTime($cajaHoy['apertura']) ?> · Cierre: <?= formatDateTime($cajaHoy['cierre']) ?></p>
                </div>
                <span class="badge badge-pill badge-secondary text-base">CERRADA</span>
            </div>
            <div class="card-body">
                <div class="stat-grid mb-3">
                    <div class="stat-card">
                        <span class="stat-label">Fondo Inicial</span>
                        <h3 class="stat-value"><?= formatMoney($cajaHoy['fondo_inicial']) ?></h3>
                    </div>
                    <div class="stat-card">
                        <span class="stat-label">Efectivo Esperado</span>
                        <h3 class="stat-value"><?= formatMoney($cajaHoy['efectivo_esperado']) ?></h3>
                    </div>
                    <div class="stat-card">
                        <span class="stat-label">Efectivo Contado</span>
                        <h3 class="stat-value"><?= formatMoney($cajaHoy['efectivo_contado']) ?></h3>
                    </div>
                    <div class="stat-card">
                        <span class="stat-label">Diferencia Final</span>
                        <?php 
                            $dif = (float)$cajaHoy['efectivo_contado'] - (float)$cajaHoy['efectivo_esperado'];
                        ?>
                        <h3 class="stat-value <?= $dif == 0 ? 'text-success' : ($dif > 0 ? 'text-info' : 'text-danger') ?>">
                            <?= formatMoney($dif) ?>
                        </h3>
                    </div>
                </div>

                <?php if (!empty($cajaHoy['observaciones'])): ?>
                    <div class="mt-3 p-3 bg-light rounded">
                        <strong>Observaciones de Cierre:</strong>
                        <p class="mb-0 mt-1 text-muted"><?= nl2br(e($cajaHoy['observaciones'])) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
