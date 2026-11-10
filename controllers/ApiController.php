<?php
/**
 * SIVE 2.0 - ApiController
 *
 * Endpoints JSON para consumo AJAX desde el frontend.
 * No renderiza vistas HTML; todas las respuestas son application/json.
 */

class ApiController extends Controller {

    /**
     * GET /api/precios
     * Retorna los precios vigentes del sistema.
     */
    public function precios(): void {
        $config = new Configuracion();
        $precios = $config->obtenerPrecios();
        $this->json([
            'success' => true,
            'data'    => $precios
        ]);
    }

    /**
     * GET /api/sabores  o  GET /api/sabores/{tipo}
     * Retorna los sabores activos, opcionalmente filtrados por tipo.
     */
    public function sabores($tipo = null): void {
        $saborModel = new Sabor();
        $sabores = $saborModel->obtenerActivos($tipo);
        $this->json([
            'success' => true,
            'data'    => $sabores
        ]);
    }

    /**
     * GET /api/caja/estado  →  mapeado como cajaEstado()
     * Retorna el estado actual de la caja del día.
     */
    public function cajaEstado(): void {
        $cajaModel = new Caja();
        $caja = $cajaModel->obtenerHoy();

        if (!$caja) {
            $anterior = $cajaModel->cajaAnteriorSinCerrar();
            $this->json([
                'success' => true,
                'data'    => [
                    'estado'              => 'sin_abrir',
                    'caja'                => null,
                    'anterior_sin_cerrar' => $anterior
                ]
            ]);
            return;
        }

        // Calcular resumen financiero
        $resumen = $cajaModel->obtenerResumen($caja['id']);

        $this->json([
            'success' => true,
            'data'    => [
                'estado'  => $caja['estado'],
                'caja'    => $caja,
                'resumen' => $resumen
            ]
        ]);
    }

    /**
     * GET /api/caja  →  alias amigable de cajaEstado
     */
    public function caja(): void {
        $this->cajaEstado();
    }

    /**
     * POST /api/venta/calcular  →  mapeado como ventaCalcular()
     * Recibe un arreglo de ítems y calcula el total de la venta.
     *
     * Body JSON esperado:
     * {
     *   "items": [
     *     {
     *       "tipo_producto": "helado",
     *       "tamano_vaso": "6oz",
     *       "num_bolas": 2,
     *       "sabores": [1, 3],
     *       "cantidad": 1
     *     }
     *   ]
     * }
     */
    public function ventaCalcular(): void {
        if (!$this->isPost()) {
            $this->json(['success' => false, 'error' => 'Método no permitido'], 405);
            return;
        }

        // Leer JSON del body
        $input = json_decode(file_get_contents('php://input'), true);
        $items = $input['items'] ?? [];

        if (empty($items)) {
            $this->json(['success' => false, 'error' => 'No se recibieron ítems'], 400);
            return;
        }

        $config = new Configuracion();
        $precios = $config->obtenerPrecios();

        $detalles = [];
        $total = 0;

        foreach ($items as $item) {
            $tipo     = $item['tipo_producto'] ?? '';
            $tamano   = $item['tamano_vaso'] ?? '';
            $numBolas = (int)($item['num_bolas'] ?? 1);
            $sabores  = $item['sabores'] ?? [];
            $cantidad = max(1, (int)($item['cantidad'] ?? 1));

            $precio = $this->obtenerPrecioItem($tipo, $tamano, $numBolas, count($sabores), $precios);

            $subtotal = $precio * $cantidad;
            $total += $subtotal;

            $detalles[] = [
                'tipo_producto'  => $tipo,
                'tamano_vaso'    => $tamano,
                'num_bolas'      => $numBolas,
                'sabores'        => $sabores,
                'cantidad'       => $cantidad,
                'precio_unitario'=> $precio,
                'subtotal'       => $subtotal
            ];
        }

        $this->json([
            'success'  => true,
            'data'     => [
                'detalles' => $detalles,
                'total'    => round($total, 2)
            ]
        ]);
    }

    /**
     * Alias: POST /api/calcular  →  redirige a ventaCalcular
     */
    public function calcular(): void {
        $this->ventaCalcular();
    }

    /**
     * Determina el precio unitario de un ítem según las reglas de negocio.
     *
     * Helados:   precio fijo por número de bolas (1=S/4, 2=S/7, 3=S/9) o litro (S/28)
     * Cremoladas: precio depende de cantidad de sabores (1-2=S/6, 3=S/8)
     */
    private function obtenerPrecioItem(string $tipo, string $tamano, int $numBolas, int $numSabores, array $precios): float {
        if ($tipo === 'helado') {
            if ($tamano === 'litro') {
                return (float)($precios['precio_litro_helado'] ?? 28);
            }
            return match ($numBolas) {
                1       => (float)($precios['precio_helado_1bola'] ?? 4),
                2       => (float)($precios['precio_helado_2bolas'] ?? 7),
                3       => (float)($precios['precio_helado_3bolas'] ?? 9),
                default => (float)($precios['precio_helado_1bola'] ?? 4),
            };
        }

        if ($tipo === 'cremolada') {
            if ($numSabores >= 3) {
                return (float)($precios['precio_cremolada_3_sabores'] ?? 8);
            }
            return (float)($precios['precio_cremolada_1_2_sabores'] ?? 6);
        }

        return 0;
    }

    /**
     * GET /api/inventario
     * Retorna el inventario estimado por sabor.
     */
    public function inventario(): void {
        $invModel = new Inventario();
        $stock = $invModel->estimarStock();
        $this->json([
            'success' => true,
            'data'    => $stock
        ]);
    }
}
