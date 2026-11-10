/**
 * SIVE 2.0 - Sistema de Ventas y Estadísticas
 * caja.js - Gestión de apertura, cierre, arqueo y movimientos de caja
 */

'use strict';

// =============================================================================
// 1. ESTADO Y VARIABLES DE CAJA
// =============================================================================

let cajaEstadoActual = null;
let efectivoEsperadoCalculado = 0;

// =============================================================================
// 2. INICIALIZACIÓN (initCaja)
// =============================================================================

/**
 * Inicializa la lógica de caja en las vistas correspondientes
 */
const initCaja = () => {
    // 1. Detectar si estamos en formulario de apertura
    const formAbrir = document.getElementById('form-abrir-caja') || document.querySelector('form.form-abrir-caja');
    if (formAbrir) {
        initFormAbrirCaja(formAbrir);
    }

    // 2. Detectar si estamos en formulario de cierre
    const formCerrar = document.getElementById('form-cerrar-caja') || document.querySelector('form.form-cerrar-caja');
    if (formCerrar) {
        initFormCerrarCaja(formCerrar);
    }

    // 3. Detectar formulario de gastos o retiros
    const formMovimiento = document.getElementById('form-movimiento-caja') ||
                           document.getElementById('form-gasto') ||
                           document.querySelector('form.form-movimiento');
    if (formMovimiento) {
        initFormMovimiento(formMovimiento);
    }

    // 4. Si hay panel o tarjetas de resumen de caja, sincronizar con API
    const resumenContainer = document.getElementById('caja-resumen') || document.querySelector('.caja-resumen-card');
    if (resumenContainer) {
        actualizarResumenCaja();
    }
};

// =============================================================================
// 3. FORMULARIO DE APERTURA DE CAJA
// =============================================================================

/**
 * Configura las validaciones y confirmación al abrir caja diaria
 * @param {HTMLFormElement} form
 */
const initFormAbrirCaja = (form) => {
    const inputFondo = form.querySelector('#fondo_inicial') ||
                       form.querySelector('input[name="fondo_inicial"]');
    const btnSubmit = form.querySelector('button[type="submit"]');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const fondo = parseFloat(inputFondo ? inputFondo.value : 0);

        if (isNaN(fondo) || fondo < 0) {
            if (window.showToast) {
                window.showToast('El fondo inicial debe ser un número válido mayor o igual a S/ 0.00.', 'error');
            } else {
                alert('El fondo inicial debe ser un número válido.');
            }
            if (inputFondo) inputFondo.focus();
            return;
        }

        const fmt = window.formatMoney || ((m) => `S/ ${parseFloat(m).toFixed(2)}`);
        const confirmMsg = `¿Está seguro de abrir la caja con un fondo inicial de <strong>${fmt(fondo)}</strong>?`;

        const proceed = window.confirmModal
            ? await window.confirmModal(confirmMsg, null, null, { title: 'Apertura de Caja Diaria' })
            : window.confirm(`¿Está seguro de abrir la caja con un fondo de ${fmt(fondo)}?`);

        if (proceed) {
            if (window.disableButton && btnSubmit) {
                window.disableButton(btnSubmit, 'Abriendo caja...');
            } else if (btnSubmit) {
                btnSubmit.disabled = true;
            }
            form.submit();
        }
    });
};

// =============================================================================
// 4. FORMULARIO DE CIERRE DE CAJA Y ARQUEO
// =============================================================================

/**
 * Calcula el efectivo esperado en caja:
 * expected = fondo_inicial + ventas_efectivo - gastos_efectivo - retiros_efectivo
 * @param {HTMLFormElement} [form]
 * @returns {number}
 */
const calcularEfectivoEsperado = (form) => {
    const container = form || document.getElementById('form-cerrar-caja') || document.body;

    // Intentar leer de data attributes
    const fondoInicial = parseFloat(container.dataset.fondoInicial || container.getAttribute('data-fondo-inicial') || 0);
    const ventasEfectivo = parseFloat(container.dataset.ventasEfectivo || container.getAttribute('data-ventas-efectivo') || 0);
    const gastosEfectivo = parseFloat(container.dataset.gastosEfectivo || container.getAttribute('data-gastos-efectivo') || 0);
    const retirosEfectivo = parseFloat(container.dataset.retirosEfectivo || container.getAttribute('data-retiros-efectivo') || 0);

    // Si los atributos no están en el contenedor, buscar inputs ocultos o spans
    let fondo = fondoInicial;
    let ventas = ventasEfectivo;
    let gastos = gastosEfectivo;
    let retiros = retirosEfectivo;

    const elFondo = document.getElementById('val-fondo-inicial') || document.querySelector('[data-val="fondo-inicial"]');
    const elVentas = document.getElementById('val-ventas-efectivo') || document.querySelector('[data-val="ventas-efectivo"]');
    const elGastos = document.getElementById('val-gastos-efectivo') || document.querySelector('[data-val="gastos-efectivo"]');
    const elRetiros = document.getElementById('val-retiros-efectivo') || document.querySelector('[data-val="retiros-efectivo"]');

    if (elFondo && elFondo.dataset.monto) fondo = parseFloat(elFondo.dataset.monto);
    if (elVentas && elVentas.dataset.monto) ventas = parseFloat(elVentas.dataset.monto);
    if (elGastos && elGastos.dataset.monto) gastos = parseFloat(elGastos.dataset.monto);
    if (elRetiros && elRetiros.dataset.monto) retiros = parseFloat(elRetiros.dataset.monto);

    const esperado = fondo + ventas - gastos - retiros;
    efectivoEsperadoCalculado = Math.round(esperado * 100) / 100;

    // Actualizar visualmente el efectivo esperado si hay elemento para ello
    const displayEsperado = document.getElementById('efectivo-esperado-display') ||
                            document.querySelector('.efectivo-esperado-display');
    if (displayEsperado) {
        const fmt = window.formatMoney || ((m) => `S/ ${parseFloat(m).toFixed(2)}`);
        displayEsperado.textContent = fmt(efectivoEsperadoCalculado);
    }

    return efectivoEsperadoCalculado;
};

/**
 * Configura la lógica interactiva de conteo y cálculo de diferencias en el cierre
 * @param {HTMLFormElement} form
 */
const initFormCerrarCaja = (form) => {
    const inputContado = form.querySelector('#efectivo_contado') ||
                         form.querySelector('input[name="efectivo_contado"]');
    const displayDiferencia = form.querySelector('#diferencia-caja-display') ||
                              form.querySelector('.diferencia-caja-display') ||
                              document.getElementById('diferencia-caja-display');
    const warningBox = form.querySelector('#caja-warning-box') ||
                       document.getElementById('caja-warning-box');
    const btnSubmit = form.querySelector('button[type="submit"]');

    // Calcular esperado inicial
    const esperado = calcularEfectivoEsperado(form);

    const actualizarDiferencia = () => {
        if (!inputContado) return 0;

        const contado = parseFloat(inputContado.value);
        if (isNaN(contado)) {
            if (displayDiferencia) {
                displayDiferencia.innerHTML = '<span class="text-muted">Ingrese el efectivo contado</span>';
                displayDiferencia.className = 'diferencia-caja-display text-muted';
            }
            if (warningBox) warningBox.style.display = 'none';
            return 0;
        }

        const diferencia = Math.round((contado - esperado) * 100) / 100;
        const fmt = window.formatMoney || ((m) => `S/ ${parseFloat(m).toFixed(2)}`);

        if (displayDiferencia) {
            displayDiferencia.classList.remove('text-success', 'text-danger', 'text-warning', 'badge-success', 'badge-danger', 'badge-warning');

            if (Math.abs(diferencia) < 0.01) {
                // Cuadre perfecto
                displayDiferencia.innerHTML = `<strong>${fmt(0)}</strong> &bull; <span class="badge badge-success">✓ Caja Cuadrada Exacta</span>`;
                displayDiferencia.classList.add('text-success');
                if (warningBox) warningBox.style.display = 'none';
            } else if (diferencia < 0) {
                // Faltante de dinero
                displayDiferencia.innerHTML = `<strong>- ${fmt(Math.abs(diferencia))}</strong> &bull; <span class="badge badge-danger">✕ Faltante</span>`;
                displayDiferencia.classList.add('text-danger');

                if (warningBox) {
                    warningBox.style.display = 'block';
                    warningBox.className = 'alert alert-danger';
                    warningBox.innerHTML = `<strong>Atención:</strong> Hay un faltante de dinero de <strong>${fmt(Math.abs(diferencia))}</strong> respecto al sistema. Por favor re-cuente el efectivo físico en gaveta.`;
                }
            } else {
                // Sobrante de dinero
                displayDiferencia.innerHTML = `<strong>+ ${fmt(diferencia)}</strong> &bull; <span class="badge badge-warning">⚠ Sobrante</span>`;
                displayDiferencia.classList.add('text-warning');

                if (warningBox) {
                    warningBox.style.display = 'block';
                    warningBox.className = 'alert alert-warning';
                    warningBox.innerHTML = `<strong>Aviso:</strong> Se ha detectado un sobrante de <strong>${fmt(diferencia)}</strong> respecto a las ventas registradas.`;
                }
            }
        }

        return diferencia;
    };

    if (inputContado) {
        inputContado.addEventListener('input', actualizarDiferencia);
        inputContado.addEventListener('change', actualizarDiferencia);
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const contado = parseFloat(inputContado ? inputContado.value : NaN);
        if (isNaN(contado) || contado < 0) {
            if (window.showToast) {
                window.showToast('Debe ingresar el efectivo contado físicamente en caja.', 'error');
            } else {
                alert('Debe ingresar el monto contado.');
            }
            if (inputContado) inputContado.focus();
            return;
        }

        const diferencia = Math.round((contado - esperado) * 100) / 100;
        const fmt = window.formatMoney || ((m) => `S/ ${parseFloat(m).toFixed(2)}`);

        let mensajeConfirmacion = `Efectivo contado: <strong>${fmt(contado)}</strong>.<br>Efectivo esperado: <strong>${fmt(esperado)}</strong>.`;

        if (Math.abs(diferencia) >= 0.01) {
            const tipoDif = diferencia < 0 ? 'FALTANTE' : 'SOBRANTE';
            mensajeConfirmacion += `<br><br><span class="${diferencia < 0 ? 'text-danger' : 'text-warning'} font-weight-bold">Existe una diferencia (${tipoDif}) de ${fmt(Math.abs(diferencia))}.</span><br>¿Desea cerrar la caja de todos modos?`;
        } else {
            mensajeConfirmacion += '<br><br>¿Confirmar el cierre definitivo de caja por hoy?';
        }

        const proceed = window.confirmModal
            ? await window.confirmModal(mensajeConfirmacion, null, null, {
                title: 'Confirmar Cierre de Caja',
                danger: Math.abs(diferencia) >= 1.00
            })
            : window.confirm(mensajeConfirmacion.replace(/<[^>]+>/g, ''));

        if (proceed) {
            if (window.disableButton && btnSubmit) {
                window.disableButton(btnSubmit, 'Cerrando caja...');
            } else if (btnSubmit) {
                btnSubmit.disabled = true;
            }
            form.submit();
        }
    });
};

// =============================================================================
// 5. MOVIMIENTOS DE CAJA (GASTOS Y RETIROS)
// =============================================================================

/**
 * Inicializa validaciones y comportamiento dinámico de categorías para gastos y retiros
 * @param {HTMLFormElement} form
 */
const initFormMovimiento = (form) => {
    const selectTipo = form.querySelector('select[name="tipo"]') || document.getElementById('tipo_movimiento');
    const selectCategoria = form.querySelector('select[name="categoria_gasto_id"]') || document.getElementById('categoria_gasto_id');
    const inputMonto = form.querySelector('input[name="monto"]') || document.getElementById('monto_movimiento');
    const btnSubmit = form.querySelector('button[type="submit"]');

    // Cambiar visualización de categoría según el tipo ('gasto' vs 'retiro')
    const actualizarCategorias = () => {
        if (!selectTipo || !selectCategoria) return;

        const tipo = selectTipo.value;
        const categoriaContainer = selectCategoria.closest('.form-group, .campo-categoria');

        if (tipo === 'retiro') {
            if (categoriaContainer) {
                categoriaContainer.style.display = 'none';
            }
            selectCategoria.required = false;
        } else {
            if (categoriaContainer) {
                categoriaContainer.style.display = 'block';
            }
            selectCategoria.required = true;
        }
    };

    if (selectTipo) {
        selectTipo.addEventListener('change', actualizarCategorias);
        actualizarCategorias();
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const monto = parseFloat(inputMonto ? inputMonto.value : 0);
        if (isNaN(monto) || monto <= 0) {
            if (window.showToast) {
                window.showToast('El monto debe ser mayor a S/ 0.00.', 'error');
            } else {
                alert('El monto debe ser mayor a 0.');
            }
            if (inputMonto) inputMonto.focus();
            return;
        }

        const tipoTexto = selectTipo && selectTipo.value === 'retiro' ? 'retiro' : 'gasto';
        const fmt = window.formatMoney || ((m) => `S/ ${parseFloat(m).toFixed(2)}`);
        const confirmMsg = `¿Registrar este <strong>${tipoTexto}</strong> por un monto de <strong>${fmt(monto)}</strong>?`;

        const proceed = window.confirmModal
            ? await window.confirmModal(confirmMsg, null, null, { title: `Confirmar ${tipoTexto.toUpperCase()}` })
            : window.confirm(`¿Registrar este ${tipoTexto} por ${fmt(monto)}?`);

        if (proceed) {
            if (window.disableButton && btnSubmit) {
                window.disableButton(btnSubmit, 'Guardando...');
            } else if (btnSubmit) {
                btnSubmit.disabled = true;
            }
            form.submit();
        }
    });
};

// =============================================================================
// 6. ACTUALIZACIÓN EN TIEMPO REAL VÍA AJAX (/api/caja/estado)
// =============================================================================

/**
 * Consulta el estado actual de caja vía AJAX y refresca los indicadores en pantalla
 */
const actualizarResumenCaja = async () => {
    try {
        const fetcher = window.api || (async (u) => (await fetch(u)).json());
        const data = await fetcher('/api/caja/estado');

        if (!data) return;

        cajaEstadoActual = data;
        const fmt = window.formatMoney || ((m) => `S/ ${parseFloat(m).toFixed(2)}`);

        // Actualizar badges de estado (Abierta / Cerrada)
        document.querySelectorAll('.caja-estado-badge').forEach(badge => {
            const abierta = data.estado === 'abierta' || data.abierta;
            badge.textContent = abierta ? 'Caja Abierta' : 'Caja Cerrada';
            badge.className = `badge ${abierta ? 'badge-success' : 'badge-secondary'} caja-estado-badge`;
        });

        // Actualizar valores numéricos
        const bindMonto = (id, valor) => {
            document.querySelectorAll(`#${id}, .${id}`).forEach(el => {
                el.textContent = fmt(valor || 0);
            });
        };

        bindMonto('resumen-fondo-inicial', data.fondo_inicial);
        bindMonto('resumen-ventas-efectivo', data.ventas_efectivo);
        bindMonto('resumen-ventas-yape', data.ventas_yape);
        bindMonto('resumen-gastos-efectivo', data.gastos_efectivo);
        bindMonto('resumen-retiros-efectivo', data.retiros_efectivo);

        // Efectivo disponible en gaveta
        const efectivoEnCaja = (parseFloat(data.fondo_inicial) || 0) +
                               (parseFloat(data.ventas_efectivo) || 0) -
                               (parseFloat(data.gastos_efectivo) || 0) -
                               (parseFloat(data.retiros_efectivo) || 0);

        bindMonto('resumen-efectivo-disponible', efectivoEnCaja);

        // Si la página tiene el formulario de cierre, actualizar atributos del form
        const formCerrar = document.getElementById('form-cerrar-caja');
        if (formCerrar) {
            formCerrar.dataset.fondoInicial = data.fondo_inicial || 0;
            formCerrar.dataset.ventasEfectivo = data.ventas_efectivo || 0;
            formCerrar.dataset.gastosEfectivo = data.gastos_efectivo || 0;
            formCerrar.dataset.retirosEfectivo = data.retiros_efectivo || 0;
            calcularEfectivoEsperado(formCerrar);
        }

    } catch (error) {
        console.warn('No se pudo actualizar el resumen de caja en tiempo real:', error);
    }
};

// =============================================================================
// 7. INICIALIZACIÓN AUTOMÁTICA EN DOM READY
// =============================================================================

document.addEventListener('DOMContentLoaded', () => {
    initCaja();
});

// Exponer funciones necesarias al ámbito global
window.initCaja = initCaja;
window.calcularEfectivoEsperado = calcularEfectivoEsperado;
window.actualizarResumenCaja = actualizarResumenCaja;
