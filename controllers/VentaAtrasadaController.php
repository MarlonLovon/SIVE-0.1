<?php
/**
 * SIVE 2.0 - VentaAtrasadaController
 *
 * Módulo para regularizar ventas pasadas mediante resúmenes diarios acumulados
 * (cuando se atendió en papel o sin conexión). Permite registrar fecha real de venta,
 * cálculo automático por cantidades y comparación con el total anotado.
 */

class VentaAtrasadaController extends Controller {

    /**
     * Listado histórico de resúmenes de ventas atrasadas
     */
    public function index(): void {
        $vaModel = new VentaAtrasada();
        $ventasAtrasadas = $vaModel->obtenerTodas(100);

        $this->view('ventas_atrasadas/index', [
            'pageTitle'        => 'Ventas Atrasadas',
            'ventasAtrasadas'  => $ventasAtrasadas
        ]);
    }

    /**
     * Muestra el formulario para ingresar un nuevo resumen de ventas atrasadas
     */
    public function crear(): void {
        $configModel = new Configuracion();
        $precios = $configModel->obtenerPrecios();

        $this->view('ventas_atrasadas/crear', [
            'pageTitle' => 'Registrar Ventas Atrasadas',
            'precios'   => $precios
        ]);
    }

    /**
     * Procesa y guarda el resumen de ventas atrasadas
     */
    public function guardar(): void {
        if (!$this->isPost()) {
            $this->redirect('ventas-atrasadas/crear');
            return;
        }

        if (!verify_csrf()) {
            $this->flash('danger', 'Error de seguridad CSRF.');
            $this->redirect('ventas-atrasadas/crear');
            return;
        }

        $fechaVenta = $this->post('fecha_venta');
        if (empty($fechaVenta)) {
            $this->flash('danger', 'Debe indicar la fecha real en que ocurrieron las ventas.');
            $this->redirect('ventas-atrasadas/crear');
            return;
        }

        // Cantidades ingresadas por tamaño
        $h4 = max(0, (int)$this->post('helados_4oz', 0));
        $h6 = max(0, (int)$this->post('helados_6oz', 0));
        $h8 = max(0, (int)$this->post('helados_8oz', 0));
        $litros = max(0, (int)$this->post('litros_helado', 0));
        $c6 = max(0, (int)$this->post('cremoladas_6oz', 0));
        $c12 = max(0, (int)$this->post('cremoladas_12oz', 0));

        $sumaCantidades = $h4 + $h6 + $h8 + $litros + $c6 + $c12;
        if ($sumaCantidades <= 0) {
            $this->flash('warning', 'Debe ingresar al menos una unidad vendida en el resumen.');
            $this->redirect('ventas-atrasadas/crear');
            return;
        }

        // Obtener precios vigentes
        $configModel = new Configuracion();
        $precios = $configModel->obtenerPrecios();

        $pH4 = (float)($precios['precio_helado_1bola'] ?? 4.0);
        $pH6 = (float)($precios['precio_helado_2bolas'] ?? 7.0);
        $pH8 = (float)($precios['precio_helado_3bolas'] ?? 9.0);
        $pLitro = (float)($precios['precio_litro_helado'] ?? 28.0);
        
        // Para cremoladas atrasadas se usa la tarifa base (S/6) por defecto
        $pCremBase = (float)($precios['precio_cremolada_1_2_sabores'] ?? 6.0);

        $totalCalculado = round(
            ($h4 * $pH4) +
            ($h6 * $pH6) +
            ($h8 * $pH8) +
            ($litros * $pLitro) +
            ($c6 * $pCremBase) +
            ($c12 * $pCremBase),
            2
        );

        $totalAnotadoInput = $this->post('total_anotado');
        $totalAnotado = ($totalAnotadoInput !== null && $totalAnotadoInput !== '') ? (float)$totalAnotadoInput : null;
        $notas = $this->post('notas', '');

        $vaModel = new VentaAtrasada();
        $registroId = $vaModel->crear([
            'fecha_venta'      => $fechaVenta,
            'fecha_registro'   => date('Y-m-d H:i:s'),
            'helados_4oz'      => $h4,
            'helados_6oz'      => $h6,
            'helados_8oz'      => $h8,
            'cremoladas_6oz'   => $c6,
            'cremoladas_12oz'  => $c12,
            'litros_helado'    => $litros,
            'total_calculado'  => $totalCalculado,
            'total_anotado'    => $totalAnotado,
            'notas'            => $notas
        ]);

        if ($registroId) {
            $diferenciaMsg = '';
            if ($totalAnotado !== null) {
                $dif = round($totalAnotado - $totalCalculado, 2);
                if (abs($dif) > 0.01) {
                    $diferenciaMsg = " (Diferencia con anotado: " . formatMoney($dif) . ")";
                }
            }
            $this->flash('success', "Resumen de ventas del {$fechaVenta} guardado correctamente por " . formatMoney($totalCalculado) . $diferenciaMsg);
        } else {
            $this->flash('danger', 'No se pudo guardar el registro de ventas atrasadas.');
        }

        $this->redirect('ventas-atrasadas');
    }
}
