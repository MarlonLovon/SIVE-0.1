<div class="pos-container">
    <div class="page-header flex flex-between flex-wrap gap-2 mb-3">
        <div>
            <h1 class="page-title">🛒 Nueva Venta (Punto de Venta)</h1>
            <p class="text-muted">Seleccione el producto, tamaño, sabores y medio de pago</p>
        </div>
        <div>
            <a href="<?= url('ventas') ?>" class="btn btn-outline">
                <span>← Volver al listado</span>
            </a>
        </div>
    </div>

    <div class="pos-grid">
        <!-- COLUMNA IZQUIERDA: CONFIGURADOR DE PRODUCTO -->
        <div class="pos-configurator">
            <!-- 1. Selector de Tipo de Producto -->
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">1. Seleccione Producto</h3>
                </div>
                <div class="card-body">
                    <div class="product-type-grid">
                        <button type="button" class="btn-product-type active" id="btnTipoHelado" data-tipo="helado">
                            <span class="type-icon">🍦</span>
                            <span class="type-name">Helado Artesanal</span>
                            <span class="type-desc">Por vaso o por litro</span>
                        </button>
                        <button type="button" class="btn-product-type" id="btnTipoCremolada" data-tipo="cremolada">
                            <span class="type-icon">🍧</span>
                            <span class="type-name">Cremolada de Fruta</span>
                            <span class="type-desc">Vasos 6oz y 12oz</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 2. Selector de Tamaño y Porciones -->
            <div class="card mb-3" id="seccionTamano">
                <div class="card-header flex flex-between">
                    <h3 class="card-title">2. Tamaño y Porción</h3>
                    <span class="text-muted text-sm" id="tamanoHint">Seleccione tamaño</span>
                </div>
                <div class="card-body">
                    <!-- Tamaños para Helado -->
                    <div class="vaso-selector-grid" id="tamanosHelado">
                        <button type="button" class="vaso-btn active" data-tamano="4oz" data-bolas="1" data-precio="<?= e($precios['precio_helado_1bola'] ?? '4.00') ?>">
                            <span class="vaso-size">4 oz</span>
                            <span class="vaso-bolas">1 bola</span>
                            <span class="vaso-price"><?= formatMoney($precios['precio_helado_1bola'] ?? 4) ?></span>
                        </button>
                        <button type="button" class="vaso-btn" data-tamano="6oz" data-bolas="2" data-precio="<?= e($precios['precio_helado_2bolas'] ?? '7.00') ?>">
                            <span class="vaso-size">6 oz</span>
                            <span class="vaso-bolas">2 bolas</span>
                            <span class="vaso-price"><?= formatMoney($precios['precio_helado_2bolas'] ?? 7) ?></span>
                        </button>
                        <button type="button" class="vaso-btn" data-tamano="8oz" data-bolas="3" data-precio="<?= e($precios['precio_helado_3bolas'] ?? '9.00') ?>">
                            <span class="vaso-size">8 oz</span>
                            <span class="vaso-bolas">3 bolas</span>
                            <span class="vaso-price"><?= formatMoney($precios['precio_helado_3bolas'] ?? 9) ?></span>
                        </button>
                        <button type="button" class="vaso-btn" data-tamano="litro" data-bolas="0" data-precio="<?= e($precios['precio_litro_helado'] ?? '28.00') ?>">
                            <span class="vaso-size">1 Litro</span>
                            <span class="vaso-bolas">Pote para llevar</span>
                            <span class="vaso-price"><?= formatMoney($precios['precio_litro_helado'] ?? 28) ?></span>
                        </button>
                    </div>

                    <!-- Tamaños para Cremolada -->
                    <div class="vaso-selector-grid hidden" id="tamanosCremolada">
                        <button type="button" class="vaso-btn active" data-tamano="6oz" data-bolas="0" 
                                data-precio-base="<?= e($precios['precio_cremolada_1_2_sabores'] ?? '6.00') ?>"
                                data-precio-3="<?= e($precios['precio_cremolada_3_sabores'] ?? '8.00') ?>"
                                data-precio="<?= e($precios['precio_cremolada_1_2_sabores'] ?? '6.00') ?>">
                            <span class="vaso-size">6 oz</span>
                            <span class="vaso-bolas">Vaso Estándar</span>
                            <span class="vaso-price"><?= formatMoney($precios['precio_cremolada_1_2_sabores'] ?? 6) ?></span>
                        </button>
                        <button type="button" class="vaso-btn" data-tamano="12oz" data-bolas="0" 
                                data-precio-base="<?= e($precios['precio_cremolada_1_2_sabores'] ?? '6.00') ?>"
                                data-precio-3="<?= e($precios['precio_cremolada_3_sabores'] ?? '8.00') ?>"
                                data-precio="<?= e($precios['precio_cremolada_1_2_sabores'] ?? '6.00') ?>">
                            <span class="vaso-size">12 oz</span>
                            <span class="vaso-bolas">Vaso Grande</span>
                            <span class="vaso-price"><?= formatMoney($precios['precio_cremolada_1_2_sabores'] ?? 6) ?></span>
                        </button>
                    </div>

                    <div id="cremoladaReglaInfo" class="alert alert-info py-2 mt-2 hidden text-xs">
                        ℹ️ <strong>Regla de precio cremolada:</strong> 1 o 2 sabores = <?= formatMoney($precios['precio_cremolada_1_2_sabores'] ?? 6) ?> · 3 sabores = <?= formatMoney($precios['precio_cremolada_3_sabores'] ?? 8) ?>
                    </div>
                </div>
            </div>

            <!-- 3. Selector de Sabores -->
            <div class="card mb-3" id="seccionSabores">
                <div class="card-header flex flex-between">
                    <h3 class="card-title">3. Seleccione Sabores</h3>
                    <span class="badge badge-primary" id="contadorSabores">Seleccione hasta 1 sabor</span>
                </div>
                <div class="card-body">
                    <!-- Grilla de Sabores de Helado -->
                    <div class="sabor-chips-grid" id="gridSaboresHelado">
                        <?php foreach ($saboresHelado as $sh): ?>
                            <button type="button" class="sabor-chip" data-id="<?= e($sh['id']) ?>" data-nombre="<?= e($sh['nombre']) ?>" data-tipo="helado">
                                <span class="sabor-dot"></span>
                                <span class="sabor-text"><?= e($sh['nombre']) ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <!-- Grilla de Sabores de Cremolada -->
                    <div class="sabor-chips-grid hidden" id="gridSaboresCremolada">
                        <?php foreach ($saboresCremolada as $sc): ?>
                            <button type="button" class="sabor-chip" data-id="<?= e($sc['id']) ?>" data-nombre="<?= e($sc['nombre']) ?>" data-tipo="cremolada">
                                <span class="sabor-dot cremolada-dot"></span>
                                <span class="sabor-text"><?= e($sc['nombre']) ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <div id="litroSinSaboresMsg" class="alert alert-info hidden mt-2">
                        💡 Para venta de litro se puede empacar directo o seleccionar sabores surtidos.
                    </div>
                </div>
            </div>

            <!-- 4. Cantidad y Botón Agregar -->
            <div class="card mb-3">
                <div class="card-body flex flex-between flex-wrap gap-2">
                    <div class="flex flex-center gap-2">
                        <label class="font-bold text-muted">Cantidad:</label>
                        <div class="cantidad-control">
                            <button type="button" class="btn-qty" id="btnQtyMinus" aria-label="Restar">-</button>
                            <input type="number" id="inputItemCantidad" value="1" min="1" max="99" readonly>
                            <button type="button" class="btn-qty" id="btnQtyPlus" aria-label="Sumar">+</button>
                        </div>
                    </div>
                    <div class="flex flex-center gap-3">
                        <div class="text-right">
                            <span class="text-xs text-muted d-block">Subtotal Ítem</span>
                            <span class="font-bold text-lg text-primary" id="lblSubtotalItem">S/ 4.00</span>
                        </div>
                        <button type="button" class="btn btn-primary btn-lg" id="btnAgregarItem">
                            <span>➕ Agregar al Pedido</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- COLUMNA DERECHA: RESUMEN DE LA VENTA Y MEDIO DE PAGO -->
        <div class="pos-summary">
            <div class="card pos-sticky-summary">
                <div class="card-header flex flex-between">
                    <h3 class="card-title">🧾 Resumen de Venta</h3>
                    <span class="badge badge-outline" id="badgeItemCount">0 ítems</span>
                </div>
                <div class="card-body p-0">
                    <!-- Tabla de Ítems del Pedido -->
                    <div class="table-responsive pos-items-wrapper">
                        <table class="table table-compact mb-0" id="tablaPedido">
                            <thead>
                                <tr>
                                    <th>Ítem</th>
                                    <th class="text-center">Cant.</th>
                                    <th class="text-right">Precio</th>
                                    <th class="text-right">Subtotal</th>
                                    <th style="width: 40px;"></th>
                                </tr>
                            </thead>
                            <tbody id="tbodyPedido">
                                <tr id="emptyPedidoRow">
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <div class="empty-cart-icon">🛒</div>
                                        <p class="mt-1">Agregue productos para comenzar la venta</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Desglose de Totales -->
                    <div class="pos-totals-box p-3 border-top">
                        <div class="flex flex-between mb-2">
                            <span class="text-muted">Total a Pagar:</span>
                            <span class="font-bold text-2xl text-primary" id="lblGranTotal">S/ 0.00</span>
                        </div>
                    </div>

                    <!-- 5. Sección de Pago -->
                    <div class="pos-payment-box p-3 border-top">
                        <h4 class="font-bold mb-2">Medio de Pago</h4>
                        
                        <div class="payment-method-grid mb-3">
                            <button type="button" class="btn-payment active" data-metodo="efectivo" id="btnPagoEfectivo">
                                <span class="pay-icon">💵</span>
                                <span class="pay-name">Efectivo</span>
                            </button>
                            <button type="button" class="btn-payment" data-metodo="yape" id="btnPagoYape">
                                <span class="pay-icon">📱</span>
                                <span class="pay-name">Yape</span>
                            </button>
                            <button type="button" class="btn-payment" data-metodo="combinado" id="btnPagoCombinado">
                                <span class="pay-icon">⚡</span>
                                <span class="pay-name">Combinado</span>
                            </button>
                        </div>

                        <!-- Campos para pago combinado -->
                        <div class="combinado-inputs-box hidden p-2 mb-3 bg-light rounded" id="boxPagoCombinado">
                            <div class="form-group mb-2">
                                <label class="text-xs font-bold text-muted">Monto en Efectivo (S/):</label>
                                <input type="number" step="0.50" min="0" class="form-control" id="inputMontoEfectivo" placeholder="0.00">
                            </div>
                            <div class="form-group mb-1">
                                <label class="text-xs font-bold text-muted">Monto en Yape (S/):</label>
                                <input type="number" step="0.50" min="0" class="form-control" id="inputMontoYape" placeholder="0.00">
                            </div>
                            <div class="combinado-check text-xs mt-1" id="lblCombinadoCheck"></div>
                        </div>

                        <!-- Formulario de Envío (con token CSRF) -->
                        <form id="formRegistrarVenta" method="POST" action="<?= url('ventas/guardar') ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="items_json" id="hiddenItemsJson" value="[]">
                            <input type="hidden" name="medio_pago" id="hiddenMedioPago" value="efectivo">
                            <input type="hidden" name="monto_efectivo" id="hiddenMontoEfectivo" value="0.00">
                            <input type="hidden" name="monto_yape" id="hiddenMontoYape" value="0.00">
                            <input type="hidden" name="total" id="hiddenTotal" value="0.00">

                            <button type="button" class="btn btn-success btn-block btn-lg" id="btnRegistrarVenta" disabled>
                                <span>✅ Confirmar y Registrar Venta</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Configuración de precios y catálogo inyectados para ventas.js -->
<script>
    const CONFIG_PRECIOS = <?= json_encode($precios, JSON_UNESCAPED_UNICODE) ?>;
    const SABORES_HELADO = <?= json_encode($saboresHelado, JSON_UNESCAPED_UNICODE) ?>;
    const SABORES_CREMOLADA = <?= json_encode($saboresCremolada, JSON_UNESCAPED_UNICODE) ?>;
</script>
