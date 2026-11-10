<div class="venta-detalle-page">
    <div class="page-header flex flex-between flex-wrap gap-2 mb-3">
        <div>
            <div class="flex flex-center gap-2">
                <h1 class="page-title">Comprobante de Venta #<?= e($venta['id']) ?></h1>
                <?php if ($venta['anulada']): ?>
                    <span class="badge badge-danger text-base">ANULADA</span>
                <?php else: ?>
                    <span class="badge badge-success text-base">VÁLIDA</span>
                <?php endif; ?>
            </div>
            <p class="text-muted">
                Emitida el <?= formatDateTime($venta['fecha_hora']) ?> · Caja del día <?= formatDate($venta['fecha_caja']) ?>
            </p>
        </div>
        <div class="header-actions flex gap-2">
            <a href="<?= url('ventas') ?>" class="btn btn-outline">
                <span>← Volver a Ventas</span>
            </a>
            <button type="button" class="btn btn-secondary" onclick="window.print()">
                <span>🖨️ Imprimir Ticket</span>
            </button>
            <?php if (!$venta['anulada']): ?>
                <form method="POST" action="<?= url('ventas/anular/' . $venta['id']) ?>" style="display:inline;" onsubmit="return confirm('¿Está seguro de anular esta venta? Esta acción no se puede deshacer.');">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-danger">
                        <span>🗑️ Anular Venta</span>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Comprobante tipo Ticket / Tarjeta elegante -->
    <div class="card ticket-card max-w-700 mx-auto mb-4">
        <div class="ticket-header text-center pb-3 border-bottom">
            <span class="brand-emoji" style="font-size: 2.5rem;">🍦</span>
            <h2 class="mt-1">SIVE 2.0 - Helados & Cremoladas</h2>
            <p class="text-sm text-muted">Venta Artesanal Familiar</p>
            <div class="ticket-meta mt-2 text-sm">
                <span><strong>Ticket:</strong> #<?= str_pad((string)$venta['id'], 6, '0', STR_PAD_LEFT) ?></span>
                <span class="mx-2">|</span>
                <span><strong>Fecha:</strong> <?= formatDateTime($venta['fecha_hora']) ?></span>
            </div>
        </div>

        <div class="ticket-body py-3">
            <h4 class="font-bold mb-2">Detalle de Productos</h4>
            <div class="table-responsive">
                <table class="table table-compact mb-0">
                    <thead>
                        <tr>
                            <th>Cant.</th>
                            <th>Descripción</th>
                            <th class="text-right">P. Unit</th>
                            <th class="text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detalles as $det): ?>
                            <tr>
                                <td class="font-bold text-center"><?= e($det['cantidad']) ?></td>
                                <td>
                                    <div class="font-bold">
                                        <?= ucfirst(e($det['tipo_producto'])) ?> - <?= e($det['tamano_vaso']) ?>
                                        <?php if ($det['num_bolas'] > 0): ?>
                                            <span class="text-xs text-muted">(<?= e($det['num_bolas']) ?> bola<?= $det['num_bolas'] > 1 ? 's' : '' ?>)</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($saboresPorDetalle[$det['id']])): ?>
                                        <div class="text-sm text-muted">
                                            Sabores: <em><?= implode(', ', array_map('e', $saboresPorDetalle[$det['id']])) ?></em>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-xs text-muted italic">Sabores no especificados o estándar</div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-right"><?= formatMoney($det['precio_unitario']) ?></td>
                                <td class="text-right font-bold"><?= formatMoney($det['subtotal']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Desglose de Pago -->
            <div class="ticket-payment-details mt-4 pt-3 border-top">
                <div class="flex flex-between py-1 text-sm">
                    <span class="text-muted">Medio de Pago:</span>
                    <span class="font-bold uppercase"><?= e($venta['medio_pago']) ?></span>
                </div>
                <?php if ($venta['medio_pago'] === 'combinado' || $venta['monto_efectivo'] > 0): ?>
                    <div class="flex flex-between py-1 text-sm">
                        <span class="text-muted">Monto en Efectivo:</span>
                        <span class="font-medium"><?= formatMoney($venta['monto_efectivo']) ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($venta['medio_pago'] === 'combinado' || $venta['monto_yape'] > 0): ?>
                    <div class="flex flex-between py-1 text-sm">
                        <span class="text-muted">Monto en Yape:</span>
                        <span class="font-medium"><?= formatMoney($venta['monto_yape']) ?></span>
                    </div>
                <?php endif; ?>
                <div class="flex flex-between py-2 border-top mt-2">
                    <span class="font-bold text-lg">TOTAL COBRADO:</span>
                    <span class="font-bold text-2xl text-primary"><?= formatMoney($venta['total']) ?></span>
                </div>
            </div>
        </div>

        <div class="ticket-footer text-center pt-3 border-top text-xs text-muted">
            <p>¡Gracias por su preferencia!</p>
            <p class="mt-1">Conservación de precios históricos garantizada por SIVE 2.0</p>
        </div>
    </div>
</div>
