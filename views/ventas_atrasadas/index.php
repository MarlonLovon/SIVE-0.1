<div class="ventas-atrasadas-page">
    <div class="page-header flex flex-between flex-wrap gap-2 mb-3">
        <div>
            <h1 class="page-title">📝 Ventas Atrasadas (Resumen Diario)</h1>
            <p class="text-muted">Registro consolidado de días anteriores o ventas anotadas en papel</p>
        </div>
        <div class="header-actions">
            <a href="<?= url('ventas-atrasadas/crear') ?>" class="btn btn-primary">
                <span>➕ Registrar Resumen de Ventas</span>
            </a>
        </div>
    </div>

    <!-- Banner Explicativo -->
    <div class="alert alert-info mb-3">
        <div class="alert-icon">ℹ️</div>
        <div class="alert-content">
            <strong>Acerca de las ventas atrasadas:</strong>
            Estos registros permiten mantener las estadísticas al día cuando no hubo internet o no se usó el sistema en el momento.
            El inventario se calcula de forma estimada y no se altera el arqueo de caja del día de hoy.
        </div>
    </div>

    <!-- Tabla de Resúmenes -->
    <div class="card">
        <div class="card-header flex flex-between">
            <h3 class="card-title">Historial de Resúmenes Ingresados</h3>
            <span class="badge badge-outline"><?= count($ventasAtrasadas) ?> registros</span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($ventasAtrasadas)): ?>
                <div class="empty-state text-center py-5">
                    <span class="empty-icon" style="font-size: 3rem;">📑</span>
                    <h3 class="mt-2">No hay ventas atrasadas registradas</h3>
                    <p class="text-muted">Todos los registros se encuentran al día en el sistema.</p>
                    <a href="<?= url('ventas-atrasadas/crear') ?>" class="btn btn-primary mt-3">
                        Ingresar un resumen pasado
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>Fecha Venta</th>
                                <th>Fecha Registro</th>
                                <th>Helados (4oz / 6oz / 8oz / 1L)</th>
                                <th>Cremoladas (6oz / 12oz)</th>
                                <th class="text-right">Total Calc.</th>
                                <th class="text-right">Total Anotado</th>
                                <th class="text-right">Diferencia</th>
                                <th>Notas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ventasAtrasadas as $va): 
                                $dif = null;
                                if ($va['total_anotado'] !== null) {
                                    $dif = round((float)$va['total_anotado'] - (float)$va['total_calculado'], 2);
                                }
                            ?>
                                <tr>
                                    <td>
                                        <span class="font-bold text-primary"><?= formatDate($va['fecha_venta']) ?></span>
                                    </td>
                                    <td>
                                        <span class="text-sm text-muted"><?= formatDateTime($va['fecha_registro']) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge badge-light" title="4oz"><?= e($va['helados_4oz']) ?> x 4oz</span>
                                        <span class="badge badge-light" title="6oz"><?= e($va['helados_6oz']) ?> x 6oz</span>
                                        <span class="badge badge-light" title="8oz"><?= e($va['helados_8oz']) ?> x 8oz</span>
                                        <?php if (!empty($va['litros_helado'])): ?>
                                            <span class="badge badge-primary" title="Litros"><?= e($va['litros_helado']) ?> L</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-light" title="6oz"><?= e($va['cremoladas_6oz']) ?> x 6oz</span>
                                        <span class="badge badge-light" title="12oz"><?= e($va['cremoladas_12oz']) ?> x 12oz</span>
                                    </td>
                                    <td class="text-right font-bold">
                                        <?= formatMoney($va['total_calculado']) ?>
                                    </td>
                                    <td class="text-right">
                                        <?= $va['total_anotado'] !== null ? formatMoney($va['total_anotado']) : '<span class="text-muted">-</span>' ?>
                                    </td>
                                    <td class="text-right font-medium">
                                        <?php if ($dif === null): ?>
                                            <span class="text-muted">-</span>
                                        <?php elseif (abs($dif) < 0.01): ?>
                                            <span class="text-success font-bold">Exacto (S/ 0)</span>
                                        <?php elseif ($dif > 0): ?>
                                            <span class="text-info">+<?= formatMoney($dif) ?></span>
                                        <?php else: ?>
                                            <span class="text-danger"><?= formatMoney($dif) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="text-sm text-muted"><?= e($va['notas'] ?? '') ?></span>
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
