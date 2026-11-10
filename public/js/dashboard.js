/**
 * SIVE 2.0 - Sistema de Ventas y Estadísticas
 * dashboard.js - Gráficos interactivos con Chart.js y animación de estadísticas
 */

'use strict';

// =============================================================================
// 1. ESTADO Y REFERENCIAS DE GRÁFICOS
// =============================================================================

let chartVentasDia = null;
let chartVentasSemana = null;
let chartSaboresPopulares = null;

// =============================================================================
// 2. CONFIGURACIÓN GLOBAL DE CHART.JS
// =============================================================================

/**
 * Aplica estilos globales y paleta de colores a Chart.js acordes al diseño de SIVE
 */
const setupChartDefaults = () => {
    if (typeof Chart === 'undefined') {
        console.warn('Chart.js no está cargado en el documento.');
        return;
    }

    const isDark = document.body.classList.contains('dark-mode');
    const textColor = isDark ? '#cbd5e1' : '#64748b';
    const gridColor = isDark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.05)';

    Chart.defaults.font.family = "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif";
    Chart.defaults.font.size = 12;
    Chart.defaults.color = textColor;
    Chart.defaults.responsive = true;
    Chart.defaults.maintainAspectRatio = false;

    // Tooltips estilizados
    Chart.defaults.plugins.tooltip.backgroundColor = isDark ? 'rgba(15, 23, 42, 0.95)' : 'rgba(30, 41, 59, 0.95)';
    Chart.defaults.plugins.tooltip.titleColor = '#ffffff';
    Chart.defaults.plugins.tooltip.bodyColor = '#f1f5f9';
    Chart.defaults.plugins.tooltip.padding = 10;
    Chart.defaults.plugins.tooltip.cornerRadius = 8;
    Chart.defaults.plugins.tooltip.boxPadding = 4;
    Chart.defaults.plugins.tooltip.usePointStyle = true;

    // Leyendas limpias
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.legend.labels.boxWidth = 8;
    Chart.defaults.plugins.legend.labels.boxHeight = 8;
    Chart.defaults.plugins.legend.labels.padding = 16;
};

// =============================================================================
// 3. INICIALIZACIÓN DEL DASHBOARD (initDashboard)
// =============================================================================

/**
 * Inicializa los gráficos y animaciones numéricas en la página del Dashboard
 */
const initDashboard = () => {
    const dashboardContainer = document.getElementById('dashboard-container') ||
                               document.querySelector('.dashboard-grid') ||
                               document.getElementById('ventasDelDiaChart');

    if (!dashboardContainer) {
        return;
    }

    setupChartDefaults();

    // Obtener datos globales pasados desde PHP
    // <script>const chartData = <?= json_encode($chartData) ?>;</script>
    const data = (typeof chartData !== 'undefined' && chartData) ? chartData : {};

    // 1. Renderizar gráfico de ventas del día (Doughnut)
    renderVentasDelDiaChart(data.ventas_dia || data.ventasDelDia);

    // 2. Renderizar gráfico de ventas de la semana (Barras agrupadas Efectivo + Yape)
    renderVentasSemanaChart(data.ventas_semana || data.ventasSemana);

    // 3. Renderizar gráfico de sabores más populares (Horizontal Bar)
    renderSaboresPopularesChart(data.sabores_populares || data.saboresPopulares);

    // 4. Animar los números de las tarjetas de métricas
    animateStatNumbers();

    // Reaccionar a cambios en modo oscuro
    const observer = new MutationObserver(() => {
        setupChartDefaults();
        if (chartVentasDia) chartVentasDia.update();
        if (chartVentasSemana) chartVentasSemana.update();
        if (chartSaboresPopulares) chartSaboresPopulares.update();
    });

    observer.observe(document.body, { attributes: true, attributeFilter: ['class'] });
};

// =============================================================================
// 4. GRÁFICO 1: VENTAS DEL DÍA (DOUGHNUT)
// =============================================================================

/**
 * Gráfico tipo dona con distribución de ventas del día: Helados vs Cremoladas vs Litros
 * @param {Object} [data]
 */
const renderVentasDelDiaChart = (data) => {
    const canvas = document.getElementById('ventasDelDiaChart') ||
                   document.getElementById('chart-ventas-dia') ||
                   document.querySelector('[data-chart="ventas-dia"]');

    if (!canvas || typeof Chart === 'undefined') return;

    // Destruir instancia previa si existe
    if (chartVentasDia) {
        chartVentasDia.destroy();
    }

    // Datos por defecto si no vienen desde backend
    const labels = (data && data.labels) || ['Helados', 'Cremoladas', 'Litros'];
    const values = (data && data.values) || (data && [data.helados || 0, data.cremoladas || 0, data.litros || 0]) || [0, 0, 0];

    const ctx = canvas.getContext('2d');

    chartVentasDia = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: [
                    '#ff6b81', // Helado (Rosa suave / fresa)
                    '#ffa502', // Cremolada (Naranja tropical / mango)
                    '#a55eea'  // Litro (Púrpura / mora)
                ],
                hoverBackgroundColor: [
                    '#ee5253',
                    '#ff9f43',
                    '#8854d0'
                ],
                borderWidth: 2,
                borderColor: document.body.classList.contains('dark-mode') ? '#1e293b' : '#ffffff'
            }]
        },
        options: {
            cutout: '70%',
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            const label = context.label || '';
                            const val = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + (parseFloat(b) || 0), 0);
                            const pct = total > 0 ? Math.round((val / total) * 100) : 0;
                            return ` ${label}: ${val} unidades (${pct}%)`;
                        }
                    }
                }
            }
        }
    });
};

// =============================================================================
// 5. GRÁFICO 2: VENTAS DE LA SEMANA (BARRAS DOBLES: EFECTIVO + YAPE)
// =============================================================================

/**
 * Gráfico de barras comparativo de ventas de los últimos 7 días con Efectivo y Yape
 * @param {Object} [data]
 */
const renderVentasSemanaChart = (data) => {
    const canvas = document.getElementById('ventasSemanaChart') ||
                   document.getElementById('chart-ventas-semana') ||
                   document.querySelector('[data-chart="ventas-semana"]');

    if (!canvas || typeof Chart === 'undefined') return;

    if (chartVentasSemana) {
        chartVentasSemana.destroy();
    }

    // Preparar labels de los últimos 7 días si no vienen dados
    const diasSemanaDefault = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
    const labels = (data && data.labels) || diasSemanaDefault;
    const efectivoData = (data && data.efectivo) || [0, 0, 0, 0, 0, 0, 0];
    const yapeData = (data && data.yape) || [0, 0, 0, 0, 0, 0, 0];

    const ctx = canvas.getContext('2d');

    // Gradiente sutil para Efectivo (Verde / Menta)
    const gradientEfectivo = ctx.createLinearGradient(0, 0, 0, 300);
    gradientEfectivo.addColorStop(0, 'rgba(46, 213, 115, 0.9)');
    gradientEfectivo.addColorStop(1, 'rgba(46, 213, 115, 0.5)');

    // Gradiente sutil para Yape (Púrpura Yape)
    const gradientYape = ctx.createLinearGradient(0, 0, 0, 300);
    gradientYape.addColorStop(0, 'rgba(116, 45, 221, 0.9)');
    gradientYape.addColorStop(1, 'rgba(142, 68, 173, 0.5)');

    chartVentasSemana = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Efectivo',
                    data: efectivoData,
                    backgroundColor: gradientEfectivo,
                    borderRadius: 6,
                    borderSkipped: false
                },
                {
                    label: 'Yape',
                    data: yapeData,
                    backgroundColor: gradientYape,
                    borderRadius: 6,
                    borderSkipped: false
                }
            ]
        },
        options: {
            scales: {
                x: {
                    grid: {
                        display: false
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: document.body.classList.contains('dark-mode') ? 'rgba(255, 255, 255, 0.06)' : 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        callback: function (value) {
                            return `S/ ${value}`;
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            const val = parseFloat(context.parsed.y) || 0;
                            const fmt = window.formatMoney ? window.formatMoney(val) : `S/ ${val.toFixed(2)}`;
                            return ` ${context.dataset.label}: ${fmt}`;
                        }
                    }
                }
            }
        }
    });
};

// =============================================================================
// 6. GRÁFICO 3: TOP 10 SABORES POPULARES (HORIZONTAL BAR)
// =============================================================================

/**
 * Gráfico horizontal con el ranking de los 10 sabores más vendidos
 * @param {Object|Array} [data]
 */
const renderSaboresPopularesChart = (data) => {
    const canvas = document.getElementById('saboresPopularesChart') ||
                   document.getElementById('chart-sabores-populares') ||
                   document.querySelector('[data-chart="sabores-populares"]');

    if (!canvas || typeof Chart === 'undefined') return;

    if (chartSaboresPopulares) {
        chartSaboresPopulares.destroy();
    }

    let labels = [];
    let values = [];
    let backgroundColors = [];

    if (Array.isArray(data)) {
        // Formato array de objetos: [{ nombre: 'Lúcuma', tipo: 'helado', total: 42 }, ...]
        labels = data.map(item => item.nombre || item.sabor);
        values = data.map(item => parseFloat(item.total || item.cantidad || 0));
        backgroundColors = data.map(item => {
            return (item.tipo === 'helado') ? '#ff6b81' : '#ffa502';
        });
    } else if (data && data.labels) {
        labels = data.labels;
        values = data.values || [];
        const tipos = data.tipos || [];
        backgroundColors = tipos.map(t => t === 'helado' ? '#ff6b81' : '#ffa502');
    }

    // Si aún está vacío, no fallar
    if (labels.length === 0) {
        labels = ['Sin datos registrados'];
        values = [0];
        backgroundColors = ['#cbd5e1'];
    }

    const ctx = canvas.getContext('2d');

    chartSaboresPopulares = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Porciones vendidas',
                data: values,
                backgroundColor: backgroundColors,
                borderRadius: 5,
                borderSkipped: false
            }]
        },
        options: {
            indexAxis: 'y', // Barra horizontal para lectura clara de nombres de sabores
            scales: {
                x: {
                    beginAtZero: true,
                    grid: {
                        color: document.body.classList.contains('dark-mode') ? 'rgba(255, 255, 255, 0.06)' : 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        precision: 0
                    }
                },
                y: {
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    display: false // No es necesaria leyenda para ranking horizontal
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            return ` Ventas: ${context.parsed.x} porciones`;
                        }
                    }
                }
            }
        }
    });
};

// =============================================================================
// 7. ANIMACIÓN DE NÚMEROS EN TARJETAS DE MÉTRICAS (COUNT-UP ANIMATION)
// =============================================================================

/**
 * Anima los números de las tarjetas del dashboard con efecto de conteo suave
 */
const animateStatNumbers = () => {
    const statCards = document.querySelectorAll('.stat-number, .stat-value, [data-counter], .metric-number');

    statCards.forEach(el => {
        const rawText = el.getAttribute('data-value') || el.textContent.trim();
        const isMoney = rawText.includes('S/') || el.classList.contains('is-money') || el.dataset.money === 'true';

        // Extraer número objetivo
        const cleanNumber = rawText.replace(/[^0-9.-]+/g, '');
        const targetValue = parseFloat(cleanNumber);

        if (isNaN(targetValue) || targetValue === 0) return;

        const duration = 900; // ms
        const startTime = performance.now();

        // Función de aceleración suave (ease-out cubic)
        const easeOutCubic = (t) => (--t) * t * t + 1;

        const step = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const currentVal = targetValue * easeOutCubic(progress);

            if (isMoney) {
                const fmt = window.formatMoney ? window.formatMoney(currentVal) : `S/ ${currentVal.toFixed(2)}`;
                el.textContent = fmt;
            } else {
                el.textContent = Math.round(currentVal).toLocaleString('es-PE');
            }

            if (progress < 1) {
                requestAnimationFrame(step);
            } else {
                // Asegurar valor final exacto
                if (isMoney) {
                    const fmt = window.formatMoney ? window.formatMoney(targetValue) : `S/ ${targetValue.toFixed(2)}`;
                    el.textContent = fmt;
                } else {
                    el.textContent = targetValue.toLocaleString('es-PE');
                }
            }
        };

        requestAnimationFrame(step);
    });
};

// =============================================================================
// 8. INICIALIZACIÓN AUTOMÁTICA EN DOM READY
// =============================================================================

document.addEventListener('DOMContentLoaded', () => {
    initDashboard();
});

// Exponer funciones al ámbito global
window.initDashboard = initDashboard;
window.updateStats = animateStatNumbers;
window.renderVentasDelDiaChart = renderVentasDelDiaChart;
window.renderVentasSemanaChart = renderVentasSemanaChart;
window.renderSaboresPopularesChart = renderSaboresPopularesChart;
