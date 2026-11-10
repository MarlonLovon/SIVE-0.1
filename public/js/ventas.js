/**
 * SIVE 2.0 - Sistema de Ventas y Estadísticas
 * ventas.js - Lógica completa del registro de ventas (punto de venta / TPV)
 */

'use strict';

// =============================================================================
// 1. ESTADO GLOBAL DE LA VENTA (STATE MANAGEMENT)
// =============================================================================

/**
 * Array de ítems agregados a la venta actual
 * Cada elemento: {
 *   tipo_producto: 'helado' | 'cremolada',
 *   tamano_vaso: '4oz' | '6oz' | '8oz' | '12oz' | 'litro',
 *   num_bolas: number,
 *   sabores: Array<{ id: number|string, nombre: string }>,
 *   precio_unitario: number,
 *   cantidad: number,
 *   subtotal: number
 * }
 */
let ventaItems = [];

/**
 * Ítem que el usuario está configurando actualmente antes de agregarlo
 */
let currentItem = null;

/**
 * Catálogo de precios vigentes (cargados desde /api/precios o fallback)
 */
let preciosConfig = {
    helado_4oz: 4.00,
    helado_6oz: 7.00,
    helado_8oz: 9.00,
    helado_litro: 28.00,
    cremolada_6oz_1_2: 6.00,
    cremolada_6oz_3: 8.00,
    cremolada_12oz_1_2: 6.00,
    cremolada_12oz_3: 8.00,
    cremolada_1_2_sabores: 6.00,
    cremolada_3_sabores: 8.00
};

/**
 * Catálogo de sabores activos agrupados por tipo
 */
let saboresCatalogo = {
    helado: [],
    cremolada: []
};

/**
 * Medio de pago seleccionado ('efectivo', 'yape', 'combinado')
 */
let selectedMedioPago = 'efectivo';

// =============================================================================
// 2. INICIALIZACIÓN (initVentaForm)
// =============================================================================

/**
 * Inicializa el formulario de ventas, carga datos del API y enlaza eventos
 */
const initVentaForm = async () => {
    const ventaContainer = document.getElementById('venta-container') ||
                           document.getElementById('form-venta') ||
                           document.querySelector('.venta-page-container');

    // Si no estamos en la página de ventas, no ejecutar
    if (!ventaContainer && !document.getElementById('btn-tipo-helado')) {
        return;
    }

    try {
        // Cargar precios y sabores en paralelo desde la API
        await Promise.all([
            cargarPrecios(),
            cargarSabores('helado'),
            cargarSabores('cremolada')
        ]);
    } catch (err) {
        console.warn('Aviso: No se pudieron cargar datos remotos, usando valores locales:', err);
    }

    // Configurar listeners del DOM
    setupEventListeners();

    // Render inicial del estado de venta vacía
    renderItems();
    selectMedioPago('efectivo');

    // Seleccionar 'helado' por defecto para guiar al usuario
    const btnHelado = document.getElementById('btn-tipo-helado') || document.querySelector('[data-tipo="helado"]');
    if (btnHelado) {
        btnHelado.click();
    }
};

// =============================================================================
// 3. CARGA DE DATOS DESDE LA API
// =============================================================================

/**
 * Carga los precios vigentes desde /api/precios
 */
const cargarPrecios = async () => {
    try {
        const fetcher = window.api || (async (u) => (await fetch(u)).json());
        const data = await fetcher('/api/precios');

        if (data) {
            // Normalizar formato si viene como array clave-valor o como objeto directo
            if (Array.isArray(data)) {
                data.forEach(item => {
                    if (item.clave && item.valor !== undefined) {
                        preciosConfig[item.clave] = parseFloat(item.valor);
                    }
                });
            } else if (typeof data === 'object') {
                for (const [k, v] of Object.entries(data)) {
                    preciosConfig[k] = parseFloat(v);
                }
            }
        }
    } catch (error) {
        console.error('Error al cargar precios desde API:', error);
    }
};

/**
 * Carga los sabores activos según tipo ('helado' o 'cremolada')
 * @param {'helado'|'cremolada'} tipo
 */
const cargarSabores = async (tipo) => {
    try {
        const fetcher = window.api || (async (u) => (await fetch(u)).json());
        const data = await fetcher(`/api/sabores/${tipo}`);

        if (Array.isArray(data)) {
            saboresCatalogo[tipo] = data.map(s => ({
                id: s.id || s.sabor_id,
                nombre: s.nombre || s.nombre_sabor,
                tipo: s.tipo || tipo,
                activo: s.activo !== undefined ? Boolean(Number(s.activo)) : true
            })).filter(s => s.activo);
        }
    } catch (error) {
        console.error(`Error al cargar sabores de ${tipo}:`, error);
        // Fallback inicial con sabores populares peruanos
        if (tipo === 'helado' && saboresCatalogo.helado.length === 0) {
            saboresCatalogo.helado = [
                { id: 1, nombre: 'Lúcuma' },
                { id: 2, nombre: 'Vainilla' },
                { id: 3, nombre: 'Chocolate' },
                { id: 4, nombre: 'Fresa' },
                { id: 5, nombre: 'Mango' },
                { id: 6, nombre: 'Maracuyá' },
                { id: 7, nombre: 'Café' },
                { id: 8, nombre: 'Menta' }
            ];
        } else if (tipo === 'cremolada' && saboresCatalogo.cremolada.length === 0) {
            saboresCatalogo.cremolada = [
                { id: 101, nombre: 'Fresa' },
                { id: 102, nombre: 'Mango' },
                { id: 103, nombre: 'Maracuyá' },
                { id: 104, nombre: 'Piña' },
                { id: 105, nombre: 'Chicha Morada' },
                { id: 106, nombre: 'Limonada Frozen' }
            ];
        }
    }
};

// =============================================================================
// 4. FLUJO PASO A PASO: TIPO, TAMAÑO, SABORES Y CANTIDAD
// =============================================================================

/**
 * Paso 1: Selección de Tipo de Producto ('helado' o 'cremolada')
 * @param {'helado'|'cremolada'} tipo
 */
const selectTipoProducto = (tipo) => {
    // Resetear configuración actual
    currentItem = null;

    // Actualizar botones de tipo
    document.querySelectorAll('.btn-tipo-producto, [data-tipo]').forEach(btn => {
        const isMatch = btn.getAttribute('data-tipo') === tipo ||
                        btn.id === `btn-tipo-${tipo}` ||
                        btn.id === `btnTipo${tipo.charAt(0).toUpperCase() + tipo.slice(1)}`;
        btn.classList.toggle('active', isMatch);
        btn.classList.toggle('btn-primary', isMatch);
        btn.classList.toggle('btn-outline-primary', !isMatch);
    });

    // Mostrar el contenedor de tamaños correspondiente
    const tamanosHelado = document.getElementById('selector-tamanos-helado') || document.querySelector('.tamanos-helado');
    const tamanosCremolada = document.getElementById('selector-tamanos-cremolada') || document.querySelector('.tamanos-cremolada');

    if (tamanosHelado) {
        tamanosHelado.style.display = tipo === 'helado' ? 'flex' : 'none';
        tamanosHelado.classList.toggle('active', tipo === 'helado');
    }
    if (tamanosCremolada) {
        tamanosCremolada.style.display = tipo === 'cremolada' ? 'flex' : 'none';
        tamanosCremolada.classList.toggle('active', tipo === 'cremolada');
    }

    // Ocultar selectores de pasos posteriores hasta que se elija tamaño
    const secSabores = document.getElementById('seccion-sabores') || document.querySelector('.seccion-sabores');
    if (secSabores) secSabores.style.display = 'none';

    const secCantidad = document.getElementById('seccion-cantidad') || document.querySelector('.seccion-cantidad');
    if (secCantidad) secCantidad.style.display = 'none';

    // Deseleccionar tamaños previos
    document.querySelectorAll('.btn-tamano, [data-tamano]').forEach(btn => btn.classList.remove('active', 'btn-primary'));

    updateUIItemPreview();
};

/**
 * Paso 2: Selección de Tamaño de Vaso
 * @param {'4oz'|'6oz'|'8oz'|'12oz'|'litro'} tamano
 * @param {'helado'|'cremolada'} tipo
 */
const selectTamano = (tamano, tipo) => {
    // Definir bolas y precio base según tipo y tamaño
    let numBolas = 0;
    let basePrecio = 0;

    if (tipo === 'helado') {
        switch (tamano) {
            case '4oz':
                numBolas = 1;
                basePrecio = preciosConfig.helado_4oz || 4.00;
                break;
            case '6oz':
                numBolas = 2;
                basePrecio = preciosConfig.helado_6oz || 7.00;
                break;
            case '8oz':
                numBolas = 3;
                basePrecio = preciosConfig.helado_8oz || 9.00;
                break;
            case 'litro':
                numBolas = 0; // Envase completo
                basePrecio = preciosConfig.helado_litro || 28.00;
                break;
            default:
                numBolas = 1;
                basePrecio = 4.00;
        }
    } else {
        // Cremolada: 6oz y 12oz (por defecto 1-2 sabores = S/6)
        numBolas = 3; // Cremoladas permiten hasta 3 sabores
        basePrecio = preciosConfig.cremolada_1_2_sabores || 6.00;
    }

    // Inicializar ítem actual
    currentItem = {
        tipo_producto: tipo,
        tamano_vaso: tamano,
        num_bolas: numBolas,
        sabores: [],
        precio_unitario: basePrecio,
        cantidad: 1,
        subtotal: basePrecio
    };

    // Resaltar botón de tamaño seleccionado
    document.querySelectorAll('.btn-tamano, [data-tamano]').forEach(btn => {
        const isMatch = btn.getAttribute('data-tamano') === tamano &&
                        (!btn.getAttribute('data-tipo') || btn.getAttribute('data-tipo') === tipo);
        btn.classList.toggle('active', isMatch);
        btn.classList.toggle('btn-primary', isMatch);
    });

    const secSabores = document.getElementById('seccion-sabores') || document.querySelector('.seccion-sabores');
    const secCantidad = document.getElementById('seccion-cantidad') || document.querySelector('.seccion-cantidad');

    // Regla de Negocio: Si es 'litro', se omite la selección de sabores y va directo a cantidad
    if (tamano === 'litro') {
        if (secSabores) secSabores.style.display = 'none';
        if (secCantidad) secCantidad.style.display = 'block';
    } else {
        // Mostrar selector de sabores
        if (secSabores) secSabores.style.display = 'block';
        if (secCantidad) secCantidad.style.display = 'block';
        renderSaboresSelector(tipo, numBolas);
    }

    // Resetear cantidad a 1
    const cantInput = document.getElementById('item-cantidad-input') || document.querySelector('.item-cantidad-input');
    if (cantInput) cantInput.value = 1;

    updatePrecio();
    updateUIItemPreview();
};

/**
 * Paso 3: Renderizado y selección de chips de sabores
 * @param {'helado'|'cremolada'} tipo
 * @param {number} maxSabores
 */
const renderSaboresSelector = (tipo, maxSabores) => {
    const grid = document.getElementById('sabores-grid') ||
                 document.getElementById('saboresGrid') ||
                 document.querySelector('.sabores-grid');

    const hint = document.getElementById('sabores-hint') || document.querySelector('.sabores-hint');

    if (!grid) return;

    grid.innerHTML = '';
    const sabores = saboresCatalogo[tipo] || [];

    if (sabores.length === 0) {
        grid.innerHTML = `<p class="text-muted">No hay sabores activos de ${tipo} disponibles.</p>`;
        return;
    }

    sabores.forEach(sabor => {
        const chip = document.createElement('button');
        chip.type = 'button';
        chip.className = 'sabor-chip';
        chip.setAttribute('data-id', sabor.id);
        chip.setAttribute('data-nombre', sabor.nombre);
        chip.textContent = sabor.nombre;

        // Comprobar si ya estaba seleccionado en currentItem
        if (currentItem && currentItem.sabores.some(s => String(s.id) === String(sabor.id))) {
            chip.classList.add('selected', 'active');
        }

        chip.addEventListener('click', (e) => {
            e.preventDefault();
            toggleSabor(sabor, maxSabores, chip);
        });

        grid.appendChild(chip);
    });

    actualizarTextoHintSabores(maxSabores);
};

/**
 * Alterna la selección de un sabor respetando el límite máximo
 * @param {Object} sabor
 * @param {number} maxSabores
 * @param {HTMLElement} chipElement
 */
const toggleSabor = (sabor, maxSabores, chipElement) => {
    if (!currentItem) return;

    const index = currentItem.sabores.findIndex(s => String(s.id) === String(sabor.id));

    if (index > -1) {
        // Deseleccionar sabor
        currentItem.sabores.splice(index, 1);
        chipElement.classList.remove('selected', 'active');
    } else {
        // Validar límite máximo de sabores
        // Para helado: maxSabores = num_bolas (4oz=1, 6oz=2, 8oz=3)
        // Para cremolada: máximo 3 sabores
        if (currentItem.sabores.length >= maxSabores) {
            const msg = currentItem.tipo_producto === 'helado'
                ? `El vaso de ${currentItem.tamano_vaso} solo permite hasta ${maxSabores} bola(s) / sabor(es).`
                : 'Las cremoladas permiten un máximo de 3 sabores.';
            if (window.showToast) window.showToast(msg, 'warning', 2500);
            return;
        }

        // Agregar sabor
        currentItem.sabores.push({
            id: sabor.id,
            nombre: sabor.nombre
        });
        chipElement.classList.add('selected', 'active');
    }

    actualizarTextoHintSabores(maxSabores);
    updatePrecio();
    updateUIItemPreview();
};

/**
 * Actualiza el texto guía que indica cuántos sabores faltan o se pueden elegir
 * @param {number} maxSabores
 */
const actualizarTextoHintSabores = (maxSabores) => {
    const hint = document.getElementById('sabores-hint') || document.querySelector('.sabores-hint');
    if (!hint || !currentItem) return;

    const count = currentItem.sabores.length;

    if (currentItem.tipo_producto === 'helado') {
        hint.textContent = `Seleccione hasta ${maxSabores} sabor(es): (${count}/${maxSabores} seleccionados)`;
    } else {
        // Cremolada: mostrar regla de precio (1-2 sabores = S/6, 3 sabores = S/8)
        const precioActual = count === 3 ? (preciosConfig.cremolada_3_sabores || 8.00) : (preciosConfig.cremolada_1_2_sabores || 6.00);
        hint.innerHTML = `Seleccione de 1 a 3 sabores: (<strong>${count}/3</strong>) &bull; Precio: <strong>${window.formatMoney ? window.formatMoney(precioActual) : 'S/ ' + precioActual.toFixed(2)}</strong> ${count === 3 ? '<span class="badge badge-info">(Tarifa 3 sabores)</span>' : '<span class="badge badge-light">(1-2 sabores)</span>'}`;
    }
};

// =============================================================================
// 5. CÁLCULO DE PRECIOS Y ACTUALIZACIÓN EN TIEMPO REAL (updatePrecio)
// =============================================================================

/**
 * Actualiza el precio unitario y subtotal de currentItem aplicando las reglas de negocio
 */
const updatePrecio = () => {
    if (!currentItem) return;

    let unitPrice = 0;

    if (currentItem.tipo_producto === 'helado') {
        // Precios fijos por tamaño de helado
        switch (currentItem.tamano_vaso) {
            case '4oz':
                unitPrice = preciosConfig.helado_4oz || 4.00;
                break;
            case '6oz':
                unitPrice = preciosConfig.helado_6oz || 7.00;
                break;
            case '8oz':
                unitPrice = preciosConfig.helado_8oz || 9.00;
                break;
            case 'litro':
                unitPrice = preciosConfig.helado_litro || 28.00;
                break;
            default:
                unitPrice = 4.00;
        }
    } else if (currentItem.tipo_producto === 'cremolada') {
        // Regla de Negocio: 1-2 sabores = S/ 6, 3 sabores = S/ 8
        const numSabores = currentItem.sabores.length;
        if (numSabores >= 3) {
            unitPrice = preciosConfig.cremolada_3_sabores || 8.00;
        } else {
            unitPrice = preciosConfig.cremolada_1_2_sabores || 6.00;
        }
    }

    currentItem.precio_unitario = unitPrice;
    currentItem.subtotal = currentItem.precio_unitario * (currentItem.cantidad || 1);
};

/**
 * Actualiza la vista previa del ítem en el panel de configuración
 */
const updateUIItemPreview = () => {
    const previewContainer = document.getElementById('item-preview-resumen') || document.querySelector('.item-preview-resumen');
    const btnAgregar = document.getElementById('btn-agregar-item') ||
                        document.getElementById('btnAgregarItem') ||
                        document.querySelector('.btn-agregar-item');

    if (!currentItem) {
        if (btnAgregar) btnAgregar.disabled = true;
        if (previewContainer) previewContainer.textContent = 'Seleccione producto y tamaño';
        return;
    }

    const fmt = window.formatMoney || ((m) => `S/ ${parseFloat(m).toFixed(2)}`);

    // Habilitar botón agregar:
    // - Si es litro: no requiere sabores
    // - En otro caso: requiere al menos 1 sabor seleccionado
    const canAdd = currentItem.tamano_vaso === 'litro' || currentItem.sabores.length > 0;
    if (btnAgregar) {
        btnAgregar.disabled = !canAdd;
        btnAgregar.innerHTML = `Agregar al pedido &bull; <strong>${fmt(currentItem.subtotal)}</strong>`;
    }

    // Actualizar etiquetas de precio si existen
    const unitPriceEl = document.getElementById('item-precio-unitario');
    const subtotalEl = document.getElementById('item-subtotal-preview');
    if (unitPriceEl) unitPriceEl.textContent = fmt(currentItem.precio_unitario);
    if (subtotalEl) subtotalEl.textContent = fmt(currentItem.subtotal);
};

// =============================================================================
// 6. GESTIÓN DE LA LISTA DE ÍTEMS (addItem, removeItem, renderItems)
// =============================================================================

/**
 * Agrega el ítem configurado a la venta actual
 */
const addItem = () => {
    if (!currentItem) {
        if (window.showToast) window.showToast('Por favor configure un producto primero.', 'warning');
        return;
    }

    // Validar sabores requeridos
    if (currentItem.tamano_vaso !== 'litro' && currentItem.sabores.length === 0) {
        if (window.showToast) window.showToast('Debe seleccionar al menos 1 sabor.', 'warning');
        return;
    }

    // Leer cantidad actual
    const cantInput = document.getElementById('item-cantidad-input') || document.querySelector('.item-cantidad-input');
    const cantidad = cantInput ? parseInt(cantInput.value, 10) || 1 : 1;
    currentItem.cantidad = Math.max(1, cantidad);
    currentItem.subtotal = currentItem.precio_unitario * currentItem.cantidad;

    // Clonar ítem para evitar referencias mutables
    const itemToAdd = {
        ...currentItem,
        sabores: [...currentItem.sabores]
    };

    ventaItems.push(itemToAdd);

    if (window.showToast) {
        window.showToast(`Agregado: ${itemToAdd.tipo_producto.toUpperCase()} ${itemToAdd.tamano_vaso}`, 'success', 1800);
    }

    // Renderizar tabla y recalcular totales
    renderItems();

    // Resetear UI para el siguiente ítem manteniendo el tipo actual para agilidad
    resetConfigurador();
};

/**
 * Elimina un ítem de la venta por su índice
 * @param {number} index
 */
const removeItem = (index) => {
    if (index >= 0 && index < ventaItems.length) {
        const removed = ventaItems.splice(index, 1)[0];
        renderItems();
        if (window.showToast && removed) {
            window.showToast(`Eliminado: ${removed.tipo_producto} ${removed.tamano_vaso}`, 'info', 1500);
        }
    }
};

/**
 * Resetea el configurador de ítem para permitir agregar otro producto rápidamente
 */
const resetConfigurador = () => {
    const tipo = currentItem ? currentItem.tipo_producto : 'helado';
    currentItem = null;

    // Limpiar selección de sabores visual
    document.querySelectorAll('.sabor-chip').forEach(c => c.classList.remove('selected', 'active'));

    // Deseleccionar tamaños
    document.querySelectorAll('.btn-tamano, [data-tamano]').forEach(btn => btn.classList.remove('active', 'btn-primary'));

    // Ocultar sabores y cantidad hasta elegir nuevo tamaño
    const secSabores = document.getElementById('seccion-sabores') || document.querySelector('.seccion-sabores');
    if (secSabores) secSabores.style.display = 'none';

    const secCantidad = document.getElementById('seccion-cantidad') || document.querySelector('.seccion-cantidad');
    if (secCantidad) secCantidad.style.display = 'none';

    updateUIItemPreview();
};

/**
 * Renderiza la lista o tabla de ítems de la venta y actualiza los totales
 */
const renderItems = () => {
    const tbody = document.getElementById('items-table-body') ||
                  document.querySelector('#tabla-items tbody') ||
                  document.querySelector('.items-table-body');

    const emptyMessage = document.getElementById('empty-items-message') || document.querySelector('.empty-items-message');
    const btnSubmit = document.getElementById('btn-guardar-venta') ||
                      document.getElementById('btnSubmitVenta') ||
                      document.querySelector('.btn-guardar-venta');

    const fmt = window.formatMoney || ((m) => `S/ ${parseFloat(m).toFixed(2)}`);

    if (tbody) {
        tbody.innerHTML = '';

        if (ventaItems.length === 0) {
            if (emptyMessage) emptyMessage.style.display = 'block';
            tbody.innerHTML = `
                <tr class="empty-row">
                    <td colspan="6" class="text-center text-muted py-4">
                        <em>No hay ítems en el pedido. Seleccione un helado o cremolada para comenzar.</em>
                    </td>
                </tr>
            `;
        } else {
            if (emptyMessage) emptyMessage.style.display = 'none';

            ventaItems.forEach((item, index) => {
                const tr = document.createElement('tr');

                // Formatear sabores
                let saboresText = '';
                if (item.tamano_vaso === 'litro') {
                    saboresText = '<span class="badge badge-secondary">Envase cerrado (1L)</span>';
                } else if (item.sabores && item.sabores.length > 0) {
                    saboresText = item.sabores.map(s => `<span class="badge badge-info">${s.nombre}</span>`).join(' ');
                } else {
                    saboresText = '<span class="text-muted">Sin sabores</span>';
                }

                const badgeTipo = item.tipo_producto === 'helado'
                    ? '<span class="badge badge-helado">Helado</span>'
                    : '<span class="badge badge-cremolada">Cremolada</span>';

                tr.innerHTML = `
                    <td>${badgeTipo}</td>
                    <td><strong>${item.tamano_vaso}</strong></td>
                    <td>${saboresText}</td>
                    <td class="text-right">${fmt(item.precio_unitario)}</td>
                    <td class="text-center"><strong>${item.cantidad}</strong></td>
                    <td class="text-right font-weight-bold">${fmt(item.subtotal)}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-item" data-index="${index}" title="Eliminar ítem" aria-label="Eliminar ítem">
                            &times;
                        </button>
                    </td>
                `;

                tbody.appendChild(tr);
            });
        }
    }

    // Calcular gran total
    const grandTotal = calcularTotal();

    // Actualizar elementos de total en el DOM
    document.querySelectorAll('.total-venta-display, #total-venta, #totalVenta, .resumen-gran-total').forEach(el => {
        el.textContent = fmt(grandTotal);
    });

    // Actualizar campos de medio de pago
    updatePaymentInputs(grandTotal);

    // Habilitar o deshabilitar botón de guardar venta
    if (btnSubmit) {
        btnSubmit.disabled = ventaItems.length === 0;
    }
};

/**
 * Calcula la suma de todos los subtotales de la venta
 * @returns {number} Gran total
 */
const calcularTotal = () => {
    return ventaItems.reduce((acc, item) => acc + (parseFloat(item.subtotal) || 0), 0);
};

// =============================================================================
// 7. SELECCIÓN DE MEDIO DE PAGO (EFECTIVO, YAPE, COMBINADO)
// =============================================================================

/**
 * Cambia el medio de pago seleccionado
 * @param {'efectivo'|'yape'|'combinado'} metodo
 */
const selectMedioPago = (metodo) => {
    selectedMedioPago = metodo;

    // Actualizar botones de medio de pago
    document.querySelectorAll('.btn-medio-pago, [data-medio-pago], [data-pago]').forEach(btn => {
        const val = btn.getAttribute('data-medio-pago') || btn.getAttribute('data-pago');
        const isMatch = val === metodo;
        btn.classList.toggle('active', isMatch);
        btn.classList.toggle('btn-primary', isMatch);
        btn.classList.toggle('btn-outline-primary', !isMatch);
    });

    const contenedorCombinado = document.getElementById('seccion-pago-combinado') ||
                                 document.getElementById('contenedor-combinado') ||
                                 document.querySelector('.seccion-pago-combinado');

    if (contenedorCombinado) {
        contenedorCombinado.style.display = metodo === 'combinado' ? 'block' : 'none';
    }

    const total = calcularTotal();
    updatePaymentInputs(total);
};

/**
 * Sincroniza los inputs de efectivo y yape según el total y medio de pago
 * @param {number} total
 */
const updatePaymentInputs = (total) => {
    const inputEfectivo = document.getElementById('monto_efectivo') || document.querySelector('input[name="monto_efectivo"]');
    const inputYape = document.getElementById('monto_yape') || document.querySelector('input[name="monto_yape"]');
    const difContainer = document.getElementById('combinado-diferencia') || document.querySelector('.combinado-diferencia');

    if (selectedMedioPago === 'efectivo') {
        if (inputEfectivo) inputEfectivo.value = total.toFixed(2);
        if (inputYape) inputYape.value = '0.00';
    } else if (selectedMedioPago === 'yape') {
        if (inputEfectivo) inputEfectivo.value = '0.00';
        if (inputYape) inputYape.value = total.toFixed(2);
    } else if (selectedMedioPago === 'combinado') {
        // En combinado, si ambos están en cero o suman distinto de total, distribuir o validar
        let ef = parseFloat(inputEfectivo ? inputEfectivo.value : 0) || 0;
        let yp = parseFloat(inputYape ? inputYape.value : 0) || 0;

        if (ef === 0 && yp === 0 && total > 0) {
            // Dividir equitativamente por defecto para facilitar
            ef = Math.round((total / 2) * 10) / 10;
            yp = parseFloat((total - ef).toFixed(2));
            if (inputEfectivo) inputEfectivo.value = ef.toFixed(2);
            if (inputYape) inputYape.value = yp.toFixed(2);
        }

        const suma = ef + yp;
        const diff = parseFloat((suma - total).toFixed(2));

        if (difContainer) {
            if (diff === 0) {
                difContainer.innerHTML = '<span class="text-success">✓ La suma coincide con el total exacto.</span>';
            } else if (diff > 0) {
                difContainer.innerHTML = `<span class="text-warning">⚠ Excede el total por S/ ${diff.toFixed(2)}</span>`;
            } else {
                difContainer.innerHTML = `<span class="text-danger">✕ Falta S/ ${Math.abs(diff).toFixed(2)} para completar el total.</span>`;
            }
        }
    }
};

// =============================================================================
// 8. REGISTRO Y ENVÍO DE LA VENTA (submitVenta)
// =============================================================================

/**
 * Valida y envía la venta al endpoint /ventas/guardar
 */
const submitVenta = async () => {
    const btnSubmit = document.getElementById('btn-guardar-venta') ||
                      document.getElementById('btnSubmitVenta') ||
                      document.querySelector('.btn-guardar-venta');

    // 1. Validar que haya al menos un ítem
    if (ventaItems.length === 0) {
        if (window.showToast) window.showToast('Debe agregar al menos un ítem a la venta.', 'warning');
        return;
    }

    const total = calcularTotal();
    if (total <= 0) {
        if (window.showToast) window.showToast('El total de la venta debe ser mayor a 0.', 'error');
        return;
    }

    // 2. Validar montos según medio de pago
    let montoEfectivo = 0;
    let montoYape = 0;

    const inputEfectivo = document.getElementById('monto_efectivo') || document.querySelector('input[name="monto_efectivo"]');
    const inputYape = document.getElementById('monto_yape') || document.querySelector('input[name="monto_yape"]');

    if (selectedMedioPago === 'efectivo') {
        montoEfectivo = total;
        montoYape = 0;
    } else if (selectedMedioPago === 'yape') {
        montoEfectivo = 0;
        montoYape = total;
    } else if (selectedMedioPago === 'combinado') {
        montoEfectivo = parseFloat(inputEfectivo ? inputEfectivo.value : 0) || 0;
        montoYape = parseFloat(inputYape ? inputYape.value : 0) || 0;

        const suma = parseFloat((montoEfectivo + montoYape).toFixed(2));
        if (Math.abs(suma - total) > 0.05) {
            const difMsg = `La suma de Efectivo (S/ ${montoEfectivo.toFixed(2)}) y Yape (S/ ${montoYape.toFixed(2)}) debe ser exactamente igual al total S/ ${total.toFixed(2)}.`;
            if (window.showToast) window.showToast(difMsg, 'error', 4000);
            return;
        }
    }

    // Preparar payload estructurado para el controlador PHP
    const payload = {
        medio_pago: selectedMedioPago,
        monto_efectivo: montoEfectivo,
        monto_yape: montoYape,
        total: total,
        items: ventaItems.map(item => ({
            tipo_producto: item.tipo_producto,
            tamano_vaso: item.tamano_vaso,
            num_bolas: item.num_bolas,
            precio_unitario: item.precio_unitario,
            cantidad: item.cantidad,
            subtotal: item.subtotal,
            sabores: item.sabores.map(s => s.id)
        }))
    };

    // Deshabilitar botón para prevenir doble submit
    if (window.disableButton) {
        window.disableButton(btnSubmit, 'Registrando venta...');
    } else if (btnSubmit) {
        btnSubmit.disabled = true;
    }

    try {
        const fetcher = window.api || (async (u, opt) => {
            const res = await fetch(u, opt);
            return res.json();
        });

        const response = await fetcher('/ventas/guardar', {
            method: 'POST',
            body: payload
        });

        if (window.showToast) {
            window.showToast('¡Venta registrada con éxito!', 'success', 2500);
        }

        // Limpiar formulario y redireccionar o imprimir
        ventaItems = [];
        renderItems();

        const base = typeof BASE_URL !== 'undefined' ? BASE_URL : '';
        const redirectUrl = (response && response.redirect) || `${base}/ventas`;

        setTimeout(() => {
            window.location.href = redirectUrl;
        }, 800);

    } catch (error) {
        console.error('Error al guardar venta:', error);
        const errMsg = error.message || 'Ocurrió un error al guardar la venta. Verifique que la caja esté abierta.';
        if (window.showToast) {
            window.showToast(errMsg, 'error', 5000);
        }

        // Re-habilitar botón
        if (window.enableButton) {
            window.enableButton(btnSubmit);
        } else if (btnSubmit) {
            btnSubmit.disabled = false;
        }
    }
};

// =============================================================================
// 9. CONFIGURACIÓN DE LISTENERS (EVENT BINDING)
// =============================================================================

/**
 * Enlaza todos los eventos del DOM para el flujo de ventas
 */
const setupEventListeners = () => {
    // 1. Botones de Tipo de Producto ('Helado' / 'Cremolada')
    document.querySelectorAll('.btn-tipo-producto, [data-tipo]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const tipo = btn.getAttribute('data-tipo') || (btn.id.includes('cremolada') ? 'cremolada' : 'helado');
            selectTipoProducto(tipo);
        });
    });

    // 2. Botones de Tamaño
    document.querySelectorAll('.btn-tamano, [data-tamano]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const tamano = btn.getAttribute('data-tamano');
            const tipo = btn.getAttribute('data-tipo') || (currentItem ? currentItem.tipo_producto : 'helado');
            selectTamano(tamano, tipo);
        });
    });

    // 3. Cantidad de Ítem (input o stepper)
    const cantInput = document.getElementById('item-cantidad-input') || document.querySelector('.item-cantidad-input');
    if (cantInput) {
        const handleQtyChange = () => {
            if (currentItem) {
                const val = parseInt(cantInput.value, 10);
                currentItem.cantidad = Math.max(1, isNaN(val) ? 1 : val);
                currentItem.subtotal = currentItem.precio_unitario * currentItem.cantidad;
                updateUIItemPreview();
            }
        };
        cantInput.addEventListener('input', handleQtyChange);
        cantInput.addEventListener('change', handleQtyChange);
    }

    // 4. Botón Agregar Ítem
    const btnAgregar = document.getElementById('btn-agregar-item') ||
                       document.getElementById('btnAgregarItem') ||
                       document.querySelector('.btn-agregar-item');
    if (btnAgregar) {
        btnAgregar.addEventListener('click', (e) => {
            e.preventDefault();
            addItem();
        });
    }

    // 5. Delegación para eliminar ítem de la tabla
    const tbody = document.getElementById('items-table-body') ||
                  document.querySelector('#tabla-items tbody') ||
                  document.querySelector('.items-table-body');
    if (tbody) {
        tbody.addEventListener('click', (e) => {
            const btnRemove = e.target.closest('.btn-remove-item, [data-remove-index]');
            if (btnRemove) {
                e.preventDefault();
                const idx = parseInt(btnRemove.getAttribute('data-index') || btnRemove.getAttribute('data-remove-index'), 10);
                if (!isNaN(idx)) removeItem(idx);
            }
        });
    }

    // 6. Botones de Medio de Pago
    document.querySelectorAll('.btn-medio-pago, [data-medio-pago], [data-pago]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const metodo = btn.getAttribute('data-medio-pago') || btn.getAttribute('data-pago');
            if (metodo) selectMedioPago(metodo);
        });
    });

    // 7. Inputs de pago combinado en tiempo real
    const inputEf = document.getElementById('monto_efectivo');
    const inputYp = document.getElementById('monto_yape');
    if (inputEf) {
        inputEf.addEventListener('input', () => updatePaymentInputs(calcularTotal()));
    }
    if (inputYp) {
        inputYp.addEventListener('input', () => updatePaymentInputs(calcularTotal()));
    }

    // 8. Botón Guardar / Finalizar Venta
    const btnSubmit = document.getElementById('btn-guardar-venta') ||
                      document.getElementById('btnSubmitVenta') ||
                      document.querySelector('.btn-guardar-venta');
    if (btnSubmit) {
        btnSubmit.addEventListener('click', (e) => {
            e.preventDefault();
            submitVenta();
        });
    }
};

// =============================================================================
// 10. INICIALIZACIÓN AUTOMÁTICA
// =============================================================================

document.addEventListener('DOMContentLoaded', () => {
    initVentaForm();
});

// Exponer funciones necesarias al ámbito global
window.initVentaForm = initVentaForm;
window.selectTipoProducto = selectTipoProducto;
window.selectTamano = selectTamano;
window.addItem = addItem;
window.removeItem = removeItem;
window.renderItems = renderItems;
window.calcularTotal = calcularTotal;
window.selectMedioPago = selectMedioPago;
window.submitVenta = submitVenta;
window.ventaItems = ventaItems;
