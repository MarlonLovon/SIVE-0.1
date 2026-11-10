<?php
/**
 * SIVE 2.0 - ReposicionController
 *
 * Gestión del reabastecimiento de bandejas de helado (5L) y baldes de cremolada (7L).
 * Incrementa el inventario estimado y, si la reposición involucró un costo pagado
 * con fondos de la heladería, genera un egreso en la caja registradora sin doble conteo.
 */

class ReposicionController extends Controller {

    /**
     * Listado histórico de reposiciones de inventario
     */
    public function index(): void {
        $repoModel = new Reposicion();
        $reposiciones = $repoModel->obtenerTodas(100);

        $this->view('reposiciones/index', [
            'pageTitle'    => 'Reposiciones de Inventario',
            'reposiciones' => $reposiciones
        ]);
    }

    /**
     * Muestra el formulario para registrar el ingreso de una nueva cubeta/balde
     */
    public function crear(): void {
        $saborModel = new Sabor();
        $sabores = $saborModel->obtenerActivos();

        $cajaModel = new Caja();
        $cajaHoy = $cajaModel->obtenerHoy();
        $cajaAbierta = $cajaHoy && ($cajaHoy['estado'] === 'abierta');

        $this->view('reposiciones/crear', [
            'pageTitle'   => 'Nueva Reposición',
            'sabores'     => $sabores,
            'cajaAbierta' => $cajaAbierta
        ]);
    }

    /**
     * Procesa y guarda la reposición y el movimiento contable si tuvo costo
     */
    public function guardar(): void {
        if (!$this->isPost()) {
            $this->redirect('reposiciones/crear');
            return;
        }

        if (!verify_csrf()) {
            $this->flash('danger', 'Error de seguridad CSRF.');
            $this->redirect('reposiciones/crear');
            return;
        }

        $saborId = (int)$this->post('sabor_id', 0);
        $tipo = $this->post('tipo', 'bandeja_helado'); // 'bandeja_helado' o 'balde_cremolada'
        $litros = (float)$this->post('litros', 0.0);
        $fecha = $this->post('fecha', date('Y-m-d'));
        $costo = (float)$this->post('costo', 0.0);
        $medioPago = $this->post('medio_pago', 'efectivo');
        $observaciones = trim($this->post('observaciones', ''));

        // Valores por defecto de litros si no se especificaron
        if ($litros <= 0) {
            $litros = ($tipo === 'bandeja_helado') ? 5.0 : 7.0;
        }

        if ($saborId <= 0) {
            $this->flash('danger', 'Debe seleccionar un sabor válido para reponer.');
            $this->redirect('reposiciones/crear');
            return;
        }

        $repoModel = new Reposicion();
        $repoId = $repoModel->crear([
            'sabor_id'      => $saborId,
            'tipo'          => $tipo,
            'litros'        => $litros,
            'fecha'         => $fecha,
            'costo'         => $costo,
            'medio_pago'    => $costo > 0 ? $medioPago : null,
            'observaciones' => $observaciones
        ]);

        if ($repoId) {
            $costoMsg = $costo > 0 ? " y se registró un egreso de " . formatMoney($costo) : "";
            $this->flash('success', "¡Reposición de {$litros}L registrada con éxito{$costoMsg}!");
            $this->redirect('reposiciones');
        } else {
            $this->flash('danger', 'Error al procesar la reposición de inventario.');
            $this->redirect('reposiciones/crear');
        }
    }
}
