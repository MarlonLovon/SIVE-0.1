<?php
/**
 * SIVE 2.0 - CajaController
 *
 * Gestión del ciclo diario de caja registradora: apertura con fondo inicial,
 * control de caja previa sin cerrar, registro de egresos (gastos operativos y retiros),
 * arqueo físico de efectivo y cierre diario con cálculo de descuadres.
 */

class CajaController extends Controller {

    /**
     * Pantalla principal del estado de caja y movimientos
     */
    public function index(): void {
        $cajaModel = new Caja();
        $cajaHoy = $cajaModel->obtenerHoy();
        $cajaAnteriorSinCerrar = $cajaModel->cajaAnteriorSinCerrar();

        $cajaEstado = $cajaHoy ? $cajaHoy['estado'] : 'sin_abrir';
        $resumen = [];
        $movimientos = [];
        $categoriasGasto = [];

        if ($cajaHoy) {
            $cajaId = $cajaHoy['id'];
            $resumen = $cajaModel->obtenerResumen($cajaId);

            $movModel = new MovimientoCaja();
            $movimientos = $movModel->obtenerPorCaja($cajaId);

            $catModel = new CategoriaGasto();
            $categoriasGasto = $catModel->obtenerActivas();
        }

        $this->view('caja/index', [
            'pageTitle'             => 'Control de Caja',
            'cajaHoy'               => $cajaHoy,
            'cajaEstado'            => $cajaEstado,
            'cajaAnteriorSinCerrar' => $cajaAnteriorSinCerrar,
            'resumen'               => $resumen,
            'movimientos'           => $movimientos,
            'categoriasGasto'       => $categoriasGasto
        ]);
    }

    /**
     * Formulario y procesamiento de apertura de caja
     */
    public function abrir(): void {
        $cajaModel = new Caja();
        $cajaHoy = $cajaModel->obtenerHoy();

        // Si ya está abierta hoy, redirigir
        if ($cajaHoy && $cajaHoy['estado'] === 'abierta') {
            $this->flash('info', 'La caja de hoy ya se encuentra abierta.');
            $this->redirect('caja');
            return;
        }

        // Si ya está cerrada hoy
        if ($cajaHoy && $cajaHoy['estado'] === 'cerrada') {
            $this->flash('warning', 'La caja de hoy ya fue cerrada previamente. No se permite reabrir en la misma fecha.');
            $this->redirect('caja');
            return;
        }

        // Procesar POST de apertura
        if ($this->isPost()) {
            if (!verify_csrf()) {
                $this->flash('danger', 'Error de seguridad CSRF.');
                $this->redirect('caja/abrir');
                return;
            }

            $fondoInicial = (float)$this->post('fondo_inicial', 0.0);
            if ($fondoInicial < 0) {
                $this->flash('danger', 'El fondo inicial no puede ser un valor negativo.');
                $this->redirect('caja/abrir');
                return;
            }

            $cajaId = $cajaModel->abrir($fondoInicial);
            if ($cajaId) {
                $this->flash('success', '¡Caja del día abierta exitosamente con fondo inicial de ' . formatMoney($fondoInicial) . '!');
                $this->redirect('caja');
            } else {
                $this->flash('danger', 'No se pudo abrir la caja registradora.');
                $this->redirect('caja/abrir');
            }
            return;
        }

        // GET: Mostrar vista de apertura
        $cajaAnteriorSinCerrar = $cajaModel->cajaAnteriorSinCerrar();

        $this->view('caja/abrir', [
            'pageTitle'             => 'Abrir Caja del Día',
            'cajaAnteriorSinCerrar' => $cajaAnteriorSinCerrar
        ]);
    }

    /**
     * Formulario y procesamiento de arqueo y cierre de caja
     */
    public function cerrar(): void {
        $cajaModel = new Caja();
        $cajaHoy = $cajaModel->obtenerHoy();

        if (!$cajaHoy || $cajaHoy['estado'] !== 'abierta') {
            $this->flash('warning', 'No hay ninguna caja abierta hoy para cerrar.');
            $this->redirect('caja');
            return;
        }

        $cajaId = $cajaHoy['id'];

        // Procesar POST de cierre
        if ($this->isPost()) {
            if (!verify_csrf()) {
                $this->flash('danger', 'Error de seguridad CSRF.');
                $this->redirect('caja/cerrar');
                return;
            }

            $efectivoContado = $this->post('efectivo_contado');
            if ($efectivoContado === null || $efectivoContado === '' || (float)$efectivoContado < 0) {
                $this->flash('danger', 'Debe ingresar el efectivo físico contado en caja.');
                $this->redirect('caja/cerrar');
                return;
            }

            $efectivoContado = (float)$efectivoContado;
            $observaciones = $this->post('observaciones', '');

            $cerrado = $cajaModel->cerrar($efectivoContado, $observaciones);
            if ($cerrado) {
                $esperado = (float)$cajaModel->calcularEfectivoEsperado($cajaId);
                $diferencia = round($efectivoContado - $esperado, 2);

                $msgExtra = ($diferencia == 0) 
                    ? ' ¡Caja cuadrada perfectamente!' 
                    : ' Diferencia detectada: ' . formatMoney($diferencia);

                $this->flash('success', 'Caja del día cerrada formalmente.' . $msgExtra);
                $this->redirect('caja');
            } else {
                $this->flash('danger', 'Error al cerrar la caja registradora.');
                $this->redirect('caja/cerrar');
            }
            return;
        }

        // GET: Mostrar vista de cierre con arqueo
        $resumen = $cajaModel->obtenerResumen($cajaId);

        $this->view('caja/cerrar', [
            'pageTitle'  => 'Cerrar Caja del Día',
            'pageScript' => 'caja.js',
            'cajaHoy'    => $cajaHoy,
            'resumen'    => $resumen
        ]);
    }

    /**
     * Registra un egreso de dinero (gasto operativo o retiro de efectivo)
     */
    public function registrarGasto(): void {
        if (!$this->isPost()) {
            $this->redirect('caja');
            return;
        }

        if (!verify_csrf()) {
            $this->flash('danger', 'Error de seguridad CSRF.');
            $this->redirect('caja');
            return;
        }

        $cajaModel = new Caja();
        $cajaHoy = $cajaModel->obtenerHoy();

        if (!$cajaHoy || $cajaHoy['estado'] !== 'abierta') {
            $this->flash('danger', 'La caja debe estar abierta para registrar egresos.');
            $this->redirect('caja');
            return;
        }

        $tipo = $this->post('tipo', 'gasto'); // 'gasto' o 'retiro'
        $monto = (float)$this->post('monto', 0.0);
        $medioPago = $this->post('medio_pago', 'efectivo');
        $categoriaId = $this->post('categoria_gasto_id');
        $descripcion = trim($this->post('descripcion', ''));

        if ($monto <= 0) {
            $this->flash('danger', 'El monto del egreso debe ser mayor a cero.');
            $this->redirect('caja');
            return;
        }

        if (empty($descripcion)) {
            $this->flash('warning', 'Debe ingresar una breve descripción del egreso.');
            $this->redirect('caja');
            return;
        }

        $movModel = new MovimientoCaja();
        $movId = $movModel->crear([
            'caja_id'            => $cajaHoy['id'],
            'tipo'               => $tipo,
            'monto'              => $monto,
            'medio_pago'         => $medioPago,
            'categoria_gasto_id' => !empty($categoriaId) ? (int)$categoriaId : null,
            'descripcion'        => $descripcion,
            'fecha_hora'         => date('Y-m-d H:i:s')
        ]);

        if ($movId) {
            $tipoLabel = $tipo === 'retiro' ? 'Retiro' : 'Gasto operativo';
            $this->flash('success', "{$tipoLabel} de " . formatMoney($monto) . " registrado con éxito.");
        } else {
            $this->flash('danger', 'No se pudo guardar el movimiento de caja.');
        }

        $this->redirect('caja');
    }
}
