<?php
/**
 * SIVE 2.0 - InventarioController
 *
 * Módulo de inventario estimado por sabor. Calcula el balance teórico
 * (reposiciones acumuladas - consumo estimado en ventas + ajustes manuales)
 * y permite registrar correcciones por mermas, degustaciones o calibraciones.
 */

class InventarioController extends Controller {

    /**
     * Muestra el stock estimado por cada sabor activo de helado y cremolada
     */
    public function index(): void {
        $invModel = new Inventario();
        $stockGeneral = $invModel->estimarStock();

        // Separar sabores por categoría
        $helados = [];
        $cremoladas = [];

        foreach ($stockGeneral as $item) {
            if ($item['tipo'] === 'helado') {
                $helados[] = $item;
            } else {
                $cremoladas[] = $item;
            }
        }

        $this->view('inventario/index', [
            'pageTitle'  => 'Inventario Estimado',
            'helados'    => $helados,
            'cremoladas' => $cremoladas
        ]);
    }

    /**
     * Procesa un ajuste manual de inventario para un sabor específico
     */
    public function ajustar(): void {
        if (!$this->isPost()) {
            $this->redirect('inventario');
            return;
        }

        if (!verify_csrf()) {
            $this->flash('danger', 'Error de validación CSRF.');
            $this->redirect('inventario');
            return;
        }

        $saborId = (int)$this->post('sabor_id', 0);
        $litrosAjuste = (float)$this->post('litros_ajuste', 0.0);
        $motivo = trim($this->post('motivo', ''));

        if ($saborId <= 0) {
            $this->flash('danger', 'Debe seleccionar un sabor válido para ajustar.');
            $this->redirect('inventario');
            return;
        }

        if ($litrosAjuste == 0) {
            $this->flash('warning', 'La cantidad de ajuste en litros no puede ser cero.');
            $this->redirect('inventario');
            return;
        }

        if (empty($motivo)) {
            $this->flash('warning', 'Debe detallar el motivo del ajuste (ej: Descongelamiento, degustación, corrección física).');
            $this->redirect('inventario');
            return;
        }

        $ajusteModel = new AjusteInventario();
        $idAjuste = $ajusteModel->crear([
            'sabor_id'      => $saborId,
            'litros_ajuste' => $litrosAjuste,
            'motivo'        => $motivo,
            'fecha'         => date('Y-m-d H:i:s')
        ]);

        if ($idAjuste) {
            $signo = $litrosAjuste > 0 ? "+{$litrosAjuste}" : "{$litrosAjuste}";
            $this->flash('success', "Ajuste de inventario de {$signo} L registrado exitosamente.");
        } else {
            $this->flash('danger', 'Ocurrió un error al guardar el ajuste de inventario.');
        }

        $this->redirect('inventario');
    }
}
