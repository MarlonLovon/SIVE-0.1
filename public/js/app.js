/**
 * SIVE 2.0 - Sistema de Ventas y Estadísticas
 * app.js - Lógica global de la aplicación, utilidades, notificaciones y navegación
 */

'use strict';

// =============================================================================
// 1. CONFIGURACIÓN GLOBAL Y BASE URL
// =============================================================================

/**
 * Obtiene la URL base de la aplicación.
 * Si BASE_URL no está definida en la vista PHP, detecta el origen actual.
 */
const getBaseUrl = () => {
    if (typeof BASE_URL !== 'undefined' && BASE_URL) {
        return BASE_URL.endsWith('/') ? BASE_URL.slice(0, -1) : BASE_URL;
    }
    return window.location.origin;
};

// =============================================================================
// 2. HELPER PARA FORMATEO DE MONEDA Y NÚMEROS
// =============================================================================

/**
 * Formatea un monto numérico a formato moneda peruana: 'S/ X.XX'
 * @param {number|string} amount - Monto a formatear
 * @returns {string} Texto formateado en Soles
 */
const formatMoney = (amount) => {
    const num = parseFloat(amount);
    if (isNaN(num)) return 'S/ 0.00';
    return `S/ ${num.toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
};

/**
 * Convierte un texto con formato de dinero a número flotante
 * @param {string|number} value 
 * @returns {number}
 */
const parseMoney = (value) => {
    if (typeof value === 'number') return isNaN(value) ? 0 : value;
    if (!value) return 0;
    const clean = String(value).replace(/[^0-9.-]+/g, '');
    const num = parseFloat(clean);
    return isNaN(num) ? 0 : num;
};

// =============================================================================
// 3. SISTEMA DE TOAST NOTIFICATIONS
// =============================================================================

/**
 * Muestra una notificación emergente tipo toast
 * @param {string} message - Mensaje a mostrar
 * @param {'success'|'error'|'warning'|'info'} [type='success'] - Tipo de notificación
 * @param {number} [duration=3000] - Tiempo en ms antes de auto-descartar
 */
const showToast = (message, type = 'success', duration = 3000) => {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');

    // Iconos según el tipo
    const icons = {
        success: '✓',
        error: '✕',
        warning: '⚠',
        info: 'ℹ'
    };

    const iconSpan = document.createElement('span');
    iconSpan.className = 'toast-icon';
    iconSpan.textContent = icons[type] || 'ℹ';

    const textSpan = document.createElement('span');
    textSpan.className = 'toast-message';
    textSpan.textContent = message;

    const closeBtn = document.createElement('button');
    closeBtn.className = 'toast-close';
    closeBtn.setAttribute('aria-label', 'Cerrar');
    closeBtn.innerHTML = '&times;';

    toast.appendChild(iconSpan);
    toast.appendChild(textSpan);
    toast.appendChild(closeBtn);

    container.appendChild(toast);

    // Animación de entrada
    requestAnimationFrame(() => {
        toast.classList.add('toast-visible');
    });

    const removeToast = () => {
        toast.classList.remove('toast-visible');
        toast.classList.add('toast-hiding');
        setTimeout(() => {
            if (toast.parentElement) {
                toast.parentElement.removeChild(toast);
            }
        }, 300);
    };

    let timer = setTimeout(removeToast, duration);

    closeBtn.addEventListener('click', () => {
        clearTimeout(timer);
        removeToast();
    });
};

// =============================================================================
// 4. MODAL DE CONFIRMACIÓN (CONFIRM PERSONALIZADO)
// =============================================================================

/**
 * Muestra un diálogo modal de confirmación con opciones Sí/No
 * Soporta tanto callbacks (onConfirm, onCancel) como sintaxis Promise
 * @param {string} message - Mensaje o pregunta de confirmación
 * @param {Function} [onConfirm] - Callback si el usuario confirma
 * @param {Function} [onCancel] - Callback si el usuario cancela
 * @param {Object} [options] - Opciones adicionales (título, texto de botones, color)
 * @returns {Promise<boolean>}
 */
const confirm = (message, onConfirm = null, onCancel = null, options = {}) => {
    return new Promise((resolve) => {
        const title = options.title || '¿Está seguro?';
        const confirmText = options.confirmText || 'Sí, continuar';
        const cancelText = options.cancelText || 'Cancelar';
        const isDanger = options.danger || false;

        // Crear overlay modal
        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay modal-confirm-overlay';
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');

        overlay.innerHTML = `
            <div class="modal-dialog modal-confirm-dialog">
                <div class="modal-header">
                    <h3 class="modal-title">${title}</h3>
                </div>
                <div class="modal-body">
                    <p class="modal-message">${message}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-cancel-confirm">${cancelText}</button>
                    <button type="button" class="btn ${isDanger ? 'btn-danger' : 'btn-primary'} btn-accept-confirm">${confirmText}</button>
                </div>
            </div>
        `;

        document.body.appendChild(overlay);

        // Bloquear scroll
        const prevOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';

        // Animación de entrada
        requestAnimationFrame(() => {
            overlay.classList.add('modal-visible');
            overlay.querySelector('.btn-accept-confirm').focus();
        });

        const cleanup = (confirmed) => {
            overlay.classList.remove('modal-visible');
            overlay.classList.add('modal-hiding');
            document.body.style.overflow = prevOverflow;

            setTimeout(() => {
                if (overlay.parentElement) {
                    overlay.parentElement.removeChild(overlay);
                }
            }, 250);

            if (confirmed) {
                if (typeof onConfirm === 'function') onConfirm();
                resolve(true);
            } else {
                if (typeof onCancel === 'function') onCancel();
                resolve(false);
            }
        };

        overlay.querySelector('.btn-accept-confirm').addEventListener('click', () => cleanup(true));
        overlay.querySelector('.btn-cancel-confirm').addEventListener('click', () => cleanup(false));

        // Cerrar con tecla Escape o clic fuera
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) cleanup(false);
        });

        const keyHandler = (e) => {
            if (e.key === 'Escape') {
                document.removeEventListener('keydown', keyHandler);
                cleanup(false);
            }
        };
        document.addEventListener('keydown', keyHandler);
    });
};

// =============================================================================
// 5. WRAPPER PARA PETICIONES HTTP (FETCH API)
// =============================================================================

/**
 * Wrapper de Fetch con manejo de JSON, cabeceras automáticas y control de errores
 * @param {string} url - Ruta relativa o absoluta
 * @param {RequestInit} [options={}] - Opciones de la petición fetch
 * @returns {Promise<any>} Datos JSON devueltos por el servidor
 */
const api = async (url, options = {}) => {
    const base = getBaseUrl();
    let finalUrl = url;

    // Si es relativa y no empieza con http, prepend base
    if (!url.startsWith('http://') && !url.startsWith('https://')) {
        const cleanUrl = url.startsWith('/') ? url : `/${url}`;
        finalUrl = `${base}${cleanUrl}`;
    }

    const defaultHeaders = {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    };

    // Si enviamos body y es objeto o string, asignar Content-Type
    let body = options.body;
    if (body && typeof body === 'object' && !(body instanceof FormData)) {
        defaultHeaders['Content-Type'] = 'application/json';
        body = JSON.stringify(body);
    }

    const config = {
        ...options,
        body,
        headers: {
            ...defaultHeaders,
            ...(options.headers || {})
        }
    };

    try {
        const response = await fetch(finalUrl, config);
        const contentType = response.headers.get('content-type') || '';

        let data = null;
        if (contentType.includes('application/json')) {
            data = await response.json();
        } else {
            data = await response.text();
        }

        if (!response.ok) {
            const errorMsg = (data && data.mensaje) || (data && data.error) || `Error ${response.status}: ${response.statusText}`;
            const err = new Error(errorMsg);
            err.status = response.status;
            err.data = data;
            throw err;
        }

        return data;
    } catch (error) {
        console.error(`[API Error] ${options.method || 'GET'} ${finalUrl}:`, error);
        throw error;
    }
};

// =============================================================================
// 6. HELPERS PARA FORMULARIOS Y BOTONES (PREVENIR DOBLE SUBMIT)
// =============================================================================

/**
 * Serializa los campos de un formulario a un objeto JavaScript simple
 * @param {HTMLFormElement} form
 * @returns {Object}
 */
const serializeForm = (form) => {
    if (!(form instanceof HTMLFormElement)) {
        console.warn('serializeForm: El argumento no es un HTMLFormElement');
        return {};
    }

    const formData = new FormData(form);
    const data = {};

    for (const [key, value] of formData.entries()) {
        if (key in data) {
            if (!Array.isArray(data[key])) {
                data[key] = [data[key]];
            }
            data[key].push(value);
        } else {
            data[key] = value;
        }
    }

    return data;
};

/**
 * Deshabilita un botón para evitar doble clic y muestra spinner opcional
 * @param {HTMLButtonElement|HTMLElement} btn
 * @param {string} [loadingText='Procesando...']
 */
const disableButton = (btn, loadingText = '') => {
    if (!btn) return;
    btn.dataset.originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.classList.add('btn-loading');

    if (loadingText) {
        btn.innerHTML = `<span class="spinner"></span> ${loadingText}`;
    }
};

/**
 * Restaura el estado original de un botón previamente deshabilitado
 * @param {HTMLButtonElement|HTMLElement} btn
 * @param {string} [originalText='']
 */
const enableButton = (btn, originalText = '') => {
    if (!btn) return;
    btn.disabled = false;
    btn.classList.remove('btn-loading');

    if (originalText) {
        btn.innerHTML = originalText;
    } else if (btn.dataset.originalHtml) {
        btn.innerHTML = btn.dataset.originalHtml;
    }
};

// =============================================================================
// 7. MODO OSCURO (DARK MODE)
// =============================================================================

const DARK_MODE_STORAGE_KEY = 'sive_dark_mode';

/**
 * Inicializa y controla el alternador de modo oscuro
 */
const initDarkMode = () => {
    const isSavedDark = localStorage.getItem(DARK_MODE_STORAGE_KEY) === 'true';
    const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    const shouldBeDark = isSavedDark || (localStorage.getItem(DARK_MODE_STORAGE_KEY) === null && prefersDark);

    if (shouldBeDark) {
        document.body.classList.add('dark-mode');
    } else {
        document.body.classList.remove('dark-mode');
    }

    updateDarkModeIcons(shouldBeDark);

    // Event listener para botones de modo oscuro
    document.querySelectorAll('.dark-mode-toggle, #darkModeToggle').forEach(toggleBtn => {
        toggleBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const isDark = document.body.classList.toggle('dark-mode');
            localStorage.setItem(DARK_MODE_STORAGE_KEY, isDark);
            updateDarkModeIcons(isDark);
            showToast(isDark ? 'Modo oscuro activado' : 'Modo claro activado', 'info', 1500);
        });
    });
};

/**
 * Actualiza el icono y aria-label del botón de tema
 * @param {boolean} isDark 
 */
const updateDarkModeIcons = (isDark) => {
    document.querySelectorAll('.dark-mode-toggle, #darkModeToggle').forEach(btn => {
        const icon = btn.querySelector('.theme-icon') || btn;
        if (isDark) {
            btn.setAttribute('aria-label', 'Cambiar a modo claro');
            btn.setAttribute('title', 'Cambiar a modo claro');
            if (icon.classList.contains('theme-icon')) {
                icon.textContent = '☀️';
            }
        } else {
            btn.setAttribute('aria-label', 'Cambiar a modo oscuro');
            btn.setAttribute('title', 'Cambiar a modo oscuro');
            if (icon.classList.contains('theme-icon')) {
                icon.textContent = '🌙';
            }
        }
    });
};

// =============================================================================
// 8. SIDEBAR RESPONSIVE (MENÚ HAMBURGUESA MÓVIL)
// =============================================================================

/**
 * Inicializa el menú lateral para dispositivos móviles
 */
const initSidebar = () => {
    const sidebar = document.getElementById('sidebar') || document.querySelector('.sidebar');
    const togglers = document.querySelectorAll('.sidebar-toggle, #sidebarToggle, [data-toggle="sidebar"]');
    let overlay = document.getElementById('sidebar-overlay') || document.querySelector('.sidebar-overlay');

    if (!sidebar) return;

    // Crear overlay si no existe
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'sidebar-overlay';
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);
    }

    const openSidebar = () => {
        sidebar.classList.add('open', 'active');
        overlay.classList.add('active');
        document.body.classList.add('sidebar-open');
    };

    const closeSidebar = () => {
        sidebar.classList.remove('open', 'active');
        overlay.classList.remove('active');
        document.body.classList.remove('sidebar-open');
    };

    togglers.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            if (sidebar.classList.contains('open') || sidebar.classList.contains('active')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
    });

    overlay.addEventListener('click', closeSidebar);

    // Cerrar sidebar al hacer clic en enlaces de navegación en móvil
    sidebar.querySelectorAll('a.nav-link, a.sidebar-link').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 992) {
                closeSidebar();
            }
        });
    });

    // Cerrar con Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && sidebar.classList.contains('open')) {
            closeSidebar();
        }
    });
};

// =============================================================================
// 9. RESALTADO DE NAVEGACIÓN ACTIVA (ACTIVE NAV)
// =============================================================================

/**
 * Marca el enlace de navegación activo según la URL actual
 */
const highlightActiveNav = () => {
    const currentPath = window.location.pathname.toLowerCase();
    const navLinks = document.querySelectorAll('.nav-link, .sidebar-link, .nav-item a');

    let bestMatch = null;
    let maxMatchLen = 0;

    navLinks.forEach(link => {
        link.classList.remove('active');
        const href = link.getAttribute('href');
        if (!href || href === '#' || href.startsWith('javascript:')) return;

        try {
            const linkUrl = new URL(href, window.location.origin);
            const linkPath = linkUrl.pathname.toLowerCase();

            // Coincidencia exacta o de prefijo
            if (currentPath === linkPath) {
                bestMatch = link;
                maxMatchLen = 9999;
            } else if (linkPath !== '/' && linkPath !== getBaseUrl().toLowerCase() && currentPath.startsWith(linkPath)) {
                if (linkPath.length > maxMatchLen) {
                    bestMatch = link;
                    maxMatchLen = linkPath.length;
                }
            }
        } catch (e) {
            // Ignorar URLs inválidas
        }
    });

    if (bestMatch) {
        bestMatch.classList.add('active');
        // También resaltar el item padre si aplica
        const parentItem = bestMatch.closest('.nav-item');
        if (parentItem) parentItem.classList.add('active');
    }
};

// =============================================================================
// 10. MENSAJES FLASH DE PHP (.alert AUTO-DISMISS)
// =============================================================================

/**
 * Detecta alertas flash renderizadas por PHP y las descarta automáticamente tras 5s
 */
const initFlashMessages = () => {
    const alerts = document.querySelectorAll('.alert, .flash-message');

    alerts.forEach(alert => {
        // Añadir botón de cerrar si no lo tiene
        if (!alert.querySelector('.alert-close, .close, [data-dismiss="alert"]')) {
            const closeBtn = document.createElement('button');
            closeBtn.type = 'button';
            closeBtn.className = 'alert-close';
            closeBtn.setAttribute('aria-label', 'Cerrar');
            closeBtn.innerHTML = '&times;';
            closeBtn.style.cssText = 'float:right; background:none; border:none; font-size:1.25rem; line-height:1; cursor:pointer; opacity:0.7;';
            closeBtn.addEventListener('click', () => dismissAlert(alert));
            alert.prepend(closeBtn);
        } else {
            const btn = alert.querySelector('.alert-close, .close, [data-dismiss="alert"]');
            btn.addEventListener('click', () => dismissAlert(alert));
        }

        // Auto-dismiss tras 5 segundos
        setTimeout(() => {
            dismissAlert(alert);
        }, 5000);
    });
};

/**
 * Transición suave para remover alerta
 * @param {HTMLElement} alert
 */
const dismissAlert = (alert) => {
    if (!alert || !alert.parentElement) return;
    alert.style.transition = 'opacity 0.4s ease, transform 0.4s ease, max-height 0.4s ease, margin 0.4s ease';
    alert.style.opacity = '0';
    alert.style.transform = 'translateY(-10px)';
    alert.style.maxHeight = '0';
    alert.style.overflow = 'hidden';
    alert.style.marginBottom = '0';
    alert.style.paddingTop = '0';
    alert.style.paddingBottom = '0';

    setTimeout(() => {
        if (alert.parentElement) {
            alert.parentElement.removeChild(alert);
        }
    }, 450);
};

// =============================================================================
// 11. CONTROLES STEPPER DE CANTIDAD (.cantidad-control)
// =============================================================================

/**
 * Inicializa los controles de incremento/decremento de cantidad (+ / -)
 */
const initQuantitySteppers = () => {
    // Delegación de eventos en el documento para soportar elementos dinámicos
    document.addEventListener('click', (e) => {
        const btnMinus = e.target.closest('.btn-cantidad-minus, .btn-minus, [data-step="minus"]');
        const btnPlus = e.target.closest('.btn-cantidad-plus, .btn-plus, [data-step="plus"]');

        if (!btnMinus && !btnPlus) return;

        const isPlus = Boolean(btnPlus);
        const container = (btnMinus || btnPlus).closest('.cantidad-control, .quantity-control, .input-group-stepper');
        if (!container) return;

        const input = container.querySelector('input[type="number"], input.cantidad-input');
        if (!input || input.disabled) return;

        const min = input.hasAttribute('min') ? parseInt(input.getAttribute('min'), 10) : 1;
        const max = input.hasAttribute('max') ? parseInt(input.getAttribute('max'), 10) : 999;
        const step = input.hasAttribute('step') ? parseInt(input.getAttribute('step'), 10) : 1;

        let currentVal = parseInt(input.value, 10);
        if (isNaN(currentVal)) currentVal = min;

        if (isPlus) {
            if (currentVal + step <= max) {
                input.value = currentVal + step;
            }
        } else {
            if (currentVal - step >= min) {
                input.value = currentVal - step;
            }
        }

        // Disparar eventos nativos para que cualquier listener reaccione
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
    });
};

// =============================================================================
// 12. INICIALIZACIÓN GLOBAL EN DOM READY
// =============================================================================

document.addEventListener('DOMContentLoaded', () => {
    initDarkMode();
    initSidebar();
    highlightActiveNav();
    initFlashMessages();
    initQuantitySteppers();
});

// =============================================================================
// 13. EXPOSICIÓN AL ÁMBITO GLOBAL
// =============================================================================

// Exportar helpers principales a window y namespace App
window.formatMoney = formatMoney;
parseMoney && (window.parseMoney = parseMoney);
window.showToast = showToast;
window.confirmModal = confirm;
window.siveConfirm = confirm;
window.api = api;
window.serializeForm = serializeForm;
window.disableButton = disableButton;
window.enableButton = enableButton;

window.App = {
    getBaseUrl,
    formatMoney,
    parseMoney,
    showToast,
    confirm,
    api,
    serializeForm,
    disableButton,
    enableButton,
    initDarkMode,
    initSidebar,
    highlightActiveNav
};
