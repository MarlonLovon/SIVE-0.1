<?php
/**
 * SIVE 2.0 - ConfigController
 *
 * Módulo de configuración administrativa del sistema.
 * Permite actualizar precios vigentes, parámetros de consumo para el cálculo
 * de inventario estimado, catálogo de sabores (altas y estado activo/inactivo)
 * y categorías de egresos de caja.
 */

class ConfigController extends Controller {

    /**
     * Muestra la interfaz de configuración general por pestañas
     */
    public function index(): void {
        $configModel = new Configuracion();
        $saborModel = new Sabor();
        $catModel = new CategoriaGasto();

        $precios = $configModel->obtenerPrecios();
        $consumos = $configModel->obtenerConsumos();
        $sabores = $saborModel->findAll('tipo ASC, nombre ASC');
        $categorias = $catModel->findAll('nombre ASC');

        $this->view('config/index', [
            'pageTitle'  => 'Configuración del Sistema',
            'precios'    => $precios,
            'consumos'   => $consumos,
            'sabores'    => $sabores,
            'categorias' => $categorias
        ]);
    }

    /**
     * Actualiza la tabla de precios del negocio
     */
    public function guardarPrecios(): void {
        if (!$this->isPost()) {
            $this->redirect('config');
            return;
        }

        if (!verify_csrf()) {
            $this->flash('danger', 'Error de seguridad CSRF.');
            $this->redirect('config');
            return;
        }

        $configModel = new Configuracion();

        $claves = [
            'precio_helado_1bola',
            'precio_helado_2bolas',
            'precio_helado_3bolas',
            'precio_litro_helado',
            'precio_cremolada_1_2_sabores',
            'precio_cremolada_3_sabores'
        ];

        foreach ($claves as $clave) {
            $valor = $this->post($clave);
            if ($valor !== null && is_numeric($valor)) {
                $configModel->actualizarValor($clave, (string)round((float)$valor, 2));
            }
        }

        $this->flash('success', 'Precios del negocio actualizados correctamente.');
        $this->redirect('config');
    }

    /**
     * Actualiza los parámetros de consumo estimados para el inventario
     */
    public function guardarConsumos(): void {
        if (!$this->isPost()) {
            $this->redirect('config');
            return;
        }

        if (!verify_csrf()) {
            $this->flash('danger', 'Error de seguridad CSRF.');
            $this->redirect('config');
            return;
        }

        $configModel = new Configuracion();

        $claves = [
            'consumo_estimado_bola_litros',
            'consumo_estimado_cremolada_6oz_litros',
            'consumo_estimado_cremolada_12oz_litros',
            'diametro_cuchara_mm',
            'litros_bandeja_helado',
            'litros_balde_cremolada'
        ];

        foreach ($claves as $clave) {
            $valor = $this->post($clave);
            if ($valor !== null && is_numeric($valor)) {
                $configModel->actualizarValor($clave, (string)$valor);
            }
        }

        $this->flash('success', 'Parámetros de consumo de inventario actualizados con éxito.');
        $this->redirect('config');
    }

    /**
     * Agrega un nuevo sabor o conmuta su estado activo/inactivo
     */
    public function sabores(): void {
        if (!$this->isPost()) {
            $this->redirect('config');
            return;
        }

        if (!verify_csrf()) {
            $this->flash('danger', 'Error de seguridad CSRF.');
            $this->redirect('config');
            return;
        }

        $accion = $this->post('accion');
        $saborModel = new Sabor();

        if ($accion === 'agregar') {
            $nombre = trim($this->post('nombre', ''));
            $tipo = $this->post('tipo', 'helado');

            if (empty($nombre)) {
                $this->flash('warning', 'Debe especificar el nombre del sabor.');
            } else {
                $saborModel->crear($nombre, $tipo);
                $this->flash('success', "Sabor '{$nombre}' agregado exitosamente al catálogo.");
            }
        } elseif ($accion === 'toggle') {
            $id = (int)$this->post('id', 0);
            if ($id > 0) {
                $saborModel->toggleActivo($id);
                $this->flash('info', 'Estado del sabor actualizado.');
            }
        }

        $this->redirect('config');
    }

    /**
     * Agrega una nueva categoría de gasto o conmuta su estado activo/inactivo
     */
    public function categorias(): void {
        if (!$this->isPost()) {
            $this->redirect('config');
            return;
        }

        if (!verify_csrf()) {
            $this->flash('danger', 'Error de seguridad CSRF.');
            $this->redirect('config');
            return;
        }

        $accion = $this->post('accion');
        $catModel = new CategoriaGasto();

        if ($accion === 'agregar') {
            $nombre = trim($this->post('nombre', ''));

            if (empty($nombre)) {
                $this->flash('warning', 'Debe ingresar el nombre de la categoría de gasto.');
            } else {
                $catModel->crear($nombre);
                $this->flash('success', "Categoría '{$nombre}' creada correctamente.");
            }
        } elseif ($accion === 'toggle') {
            $id = (int)$this->post('id', 0);
            if ($id > 0) {
                $catModel->toggleActivo($id);
                $this->flash('info', 'Estado de la categoría actualizado.');
            }
        }

        $this->redirect('config');
    }
}
