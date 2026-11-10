<?php
/**
 * SIVE 2.0 - DashboardController
 *
 * Controlador principal del panel de administración. Provee métricas del día,
 * estado de la caja registradora, estadísticas de ventas por categoría,
 * evolución semanal y catálogo de sabores con mayor demanda.
 */

class DashboardController extends Controller {

    /**
     * Muestra el resumen principal del negocio y estadísticas
     */
    public function index(): void {
        $cajaModel = new Caja();
        $ventaModel = new Venta();
        $db = Database::getConnection();

        // 1. Estado de la caja del día
        $cajaHoy = $cajaModel->obtenerHoy();
        $cajaAnteriorSinCerrar = $cajaModel->cajaAnteriorSinCerrar();

        $cajaEstado = 'sin_abrir';
        if ($cajaHoy) {
            $cajaEstado = $cajaHoy['estado'] ?? 'abierta';
        }

        $cajaId = $cajaHoy['id'] ?? null;

        // 2. Métricas de ventas de hoy
        $totalHoy = 0.0;
        $ventasCount = 0;
        $efectivoHoy = 0.0;
        $yapeHoy = 0.0;
        $ventasRecientes = [];

        if ($cajaId) {
            // Totales de venta
            $totalHoy = (float)$ventaModel->totalDelDia($cajaId);
            
            // Ventas por medio de pago
            $pagos = $ventaModel->totalPorMedioPago($cajaId);
            $efectivoHoy = (float)($pagos['efectivo'] ?? 0.0);
            $yapeHoy = (float)($pagos['yape'] ?? 0.0);

            // Cantidad de ventas no anuladas
            $stmtCount = $db->prepare("SELECT COUNT(*) as total FROM ventas WHERE caja_id = :caja_id AND anulada = 0");
            $stmtCount->execute([':caja_id' => $cajaId]);
            $ventasCount = (int)($stmtCount->fetch()['total'] ?? 0);

            // Últimas 10 ventas del día
            $stmtRecientes = $db->prepare("
                SELECT v.*, 
                    (SELECT COUNT(*) FROM detalle_ventas dv WHERE dv.venta_id = v.id) as items_count,
                    (SELECT GROUP_CONCAT(CONCAT(dv.cantidad, 'x ', dv.tipo_producto, ' (', dv.tamano_vaso, ')') SEPARATOR ', ')
                     FROM detalle_ventas dv WHERE dv.venta_id = v.id) as descripcion_items
                FROM ventas v
                WHERE v.caja_id = :caja_id
                ORDER BY v.fecha_hora DESC
                LIMIT 10
            ");
            $stmtRecientes->execute([':caja_id' => $cajaId]);
            $ventasRecientes = $stmtRecientes->fetchAll();
        }

        // 3. Distribución por tipo de producto (Helados vs Cremoladas) hoy
        $totalHeladosHoy = 0;
        $totalCremoladasHoy = 0;
        $ingresosHelados = 0.0;
        $ingresosCremoladas = 0.0;

        if ($cajaId) {
            $stmtProd = $db->prepare("
                SELECT dv.tipo_producto, 
                       SUM(dv.cantidad) as total_cantidad,
                       SUM(dv.subtotal) as total_ingreso
                FROM detalle_ventas dv
                JOIN ventas v ON dv.venta_id = v.id
                WHERE v.caja_id = :caja_id AND v.anulada = 0
                GROUP BY dv.tipo_producto
            ");
            $stmtProd->execute([':caja_id' => $cajaId]);
            $productosData = $stmtProd->fetchAll();

            foreach ($productosData as $prod) {
                if ($prod['tipo_producto'] === 'helado') {
                    $totalHeladosHoy = (int)$prod['total_cantidad'];
                    $ingresosHelados = (float)$prod['total_ingreso'];
                } elseif ($prod['tipo_producto'] === 'cremolada') {
                    $totalCremoladasHoy = (int)$prod['total_cantidad'];
                    $ingresosCremoladas = (float)$prod['total_ingreso'];
                }
            }
        }

        // 4. Ventas de la última semana (últimos 7 días)
        $diasSemana = [];
        $diasLabels = [];
        $ventasEfectivoSemana = [];
        $ventasYapeSemana = [];
        $totalesSemana = [];

        for ($i = 6; $i >= 0; $i--) {
            $fechaIter = date('Y-m-d', strtotime("-$i days"));
            $diaNombre = match((int)date('w', strtotime($fechaIter))) {
                0 => 'Dom', 1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb'
            };
            $diasLabels[] = $diaNombre . ' ' . date('d/m', strtotime($fechaIter));

            $stmtDia = $db->prepare("
                SELECT 
                    COALESCE(SUM(v.monto_efectivo), 0) as efectivo,
                    COALESCE(SUM(v.monto_yape), 0) as yape,
                    COALESCE(SUM(v.total), 0) as total
                FROM ventas v
                JOIN cajas c ON v.caja_id = c.id
                WHERE c.fecha = :fecha AND v.anulada = 0
            ");
            $stmtDia->execute([':fecha' => $fechaIter]);
            $rowDia = $stmtDia->fetch();

            $ventasEfectivoSemana[] = (float)($rowDia['efectivo'] ?? 0);
            $ventasYapeSemana[] = (float)($rowDia['yape'] ?? 0);
            $totalesSemana[] = (float)($rowDia['total'] ?? 0);
        }

        // 5. Sabores más populares de los últimos 30 días
        $stmtSabores = $db->query("
            SELECT s.nombre, s.tipo, COUNT(dvs.id) as veces_pedido
            FROM detalle_venta_sabores dvs
            JOIN sabores s ON dvs.sabor_id = s.id
            JOIN detalle_ventas dv ON dvs.detalle_venta_id = dv.id
            JOIN ventas v ON dv.venta_id = v.id
            WHERE v.anulada = 0 AND v.fecha_hora >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY s.id, s.nombre, s.tipo
            ORDER BY veces_pedido DESC
            LIMIT 7
        ");
        $saboresPopulares = $stmtSabores ? $stmtSabores->fetchAll() : [];

        // Estructura de datos para los gráficos de Chart.js (dashboard.js)
        $chartData = [
            'productosHoy' => [
                'labels' => ['Helados', 'Cremoladas'],
                'cantidades' => [$totalHeladosHoy, $totalCremoladasHoy],
                'ingresos' => [$ingresosHelados, $ingresosCremoladas]
            ],
            'semana' => [
                'labels'   => $diasLabels,
                'efectivo' => $ventasEfectivoSemana,
                'yape'     => $ventasYapeSemana,
                'totales'  => $totalesSemana
            ],
            'sabores' => [
                'labels' => array_column($saboresPopulares, 'nombre'),
                'valores' => array_map('intval', array_column($saboresPopulares, 'veces_pedido')),
                'tipos' => array_column($saboresPopulares, 'tipo')
            ]
        ];

        // Renderizado de la vista
        $this->view('dashboard/index', [
            'pageTitle'              => 'Panel de Control',
            'pageScript'             => 'dashboard.js',
            'cajaHoy'                => $cajaHoy,
            'cajaEstado'             => $cajaEstado,
            'cajaAnteriorSinCerrar'  => $cajaAnteriorSinCerrar,
            'ventasCount'            => $ventasCount,
            'totalHoy'               => $totalHoy,
            'efectivoHoy'            => $efectivoHoy,
            'yapeHoy'                => $yapeHoy,
            'ventasRecientes'        => $ventasRecientes,
            'totalHeladosHoy'        => $totalHeladosHoy,
            'totalCremoladasHoy'     => $totalCremoladasHoy,
            'saboresPopulares'       => $saboresPopulares,
            'chartData'              => $chartData
        ]);
    }
}
