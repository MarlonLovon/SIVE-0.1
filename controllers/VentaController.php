<?php
/**
 * SIVE 2.0 - VentaController
 *
 * Gestión del proceso comercial: listado diario de ventas, punto de venta (POS),
 * registro transaccional de ventas con desglose de ítems y sabores, visualización
 * de detalle y anulación controlada de comprobantes.
 */

class VentaController extends Controller {

    /**
     * Listado de ventas registradas en la caja del día actual
     */
    public function index(): void {
        $cajaModel = new Caja();
        $ventaModel = new Venta();

        $cajaHoy = $cajaModel->obtenerHoy();
        $cajaAbierta = $cajaHoy && ($cajaHoy['estado'] === 'abierta');

        $ventas = [];
        $totalGeneral = 0.0;
        $totalEfectivo = 0.0;
        $totalYape = 0.0;
        $conteoVentas = 0;
        $conteoAnuladas = 0;

        if ($cajaHoy) {
            $cajaId = $cajaHoy['id'];
            $db = Database::getConnection();

            // Consulta completa con ítems concatenados para la grilla
            $stmt = $db->prepare("
                SELECT v.*,
                    (SELECT COUNT(*) FROM detalle_ventas dv WHERE dv.venta_id = v.id) as total_items,
                    (SELECT GROUP_CONCAT(CONCAT(dv.cantidad, 'x ', dv.tipo_producto, ' (', dv.tamano_vaso, ')') SEPARATOR ', ')
                     FROM detalle_ventas dv WHERE dv.venta_id = v.id) as resumen_items
                FROM ventas v
                WHERE v.caja_id = :caja_id
                ORDER BY v.fecha_hora DESC
            ");
            $stmt->execute([':caja_id' => $cajaId]);
            $ventas = $stmt->fetchAll();

            foreach ($ventas as $v) {
                if (!$v['anulada']) {
                    $totalGeneral += (float)$v['total'];
                    $totalEfectivo += (float)$v['monto_efectivo'];
                    $totalYape += (float)$v['monto_yape'];
                    $conteoVentas++;
                } else {
                    $conteoAnuladas++;
                }
            }
        }

        $this->view('ventas/index', [
            'pageTitle'      => 'Ventas del Día',
            'cajaHoy'        => $cajaHoy,
            'cajaAbierta'    => $cajaAbierta,
            'ventas'         => $ventas,
            'totalGeneral'   => $totalGeneral,
            'totalEfectivo'  => $totalEfectivo,
            'totalYape'      => $totalYape,
            'conteoVentas'   => $conteoVentas,
            'conteoAnuladas' => $conteoAnuladas
        ]);
    }

    /**
     * Muestra la interfaz táctil del Punto de Venta (POS) para construir un nuevo pedido
     */
    public function crear(): void {
        $cajaModel = new Caja();
        $cajaHoy = $cajaModel->obtenerHoy();

        // Validar que la caja del día esté formalmente abierta
        if (!$cajaHoy || $cajaHoy['estado'] !== 'abierta') {
            $this->flash('warning', 'Para registrar ventas debe abrir la caja del día primero.');
            $this->redirect('caja/abrir');
            return;
        }

        $saborModel = new Sabor();
        $configModel = new Configuracion();

        $saboresHelado = $saborModel->obtenerActivos('helado');
        $saboresCremolada = $saborModel->obtenerActivos('cremolada');
        $precios = $configModel->obtenerPrecios();

        $this->view('ventas/crear', [
            'pageTitle'        => 'Nueva Venta',
            'pageScript'       => 'ventas.js',
            'cajaHoy'          => $cajaHoy,
            'saboresHelado'    => $saboresHelado,
            'saboresCremolada' => $saboresCremolada,
            'precios'          => $precios
        ]);
    }

    /**
     * Procesa y persiste una venta en la base de datos de manera transaccional
     */
    public function guardar(): void {
        if (!$this->isPost()) {
            $this->redirect('ventas/crear');
            return;
        }

        // Detección de petición AJAX JSON
        $isJsonRequest = (
            str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') ||
            str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')
        );

        // Si los datos vienen en el payload JSON crudo
        $inputData = [];
        if ($isJsonRequest) {
            $rawPayload = file_get_contents('php://input');
            $inputData = json_decode($rawPayload, true) ?: [];
        } else {
            $inputData = $_POST;
        }

        // Verificación de CSRF token
        $csrfToken = $inputData['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
        if (!verify_csrf($csrfToken)) {
            if ($isJsonRequest) {
                $this->json(['success' => false, 'error' => 'Token de seguridad inválido o sesión expirada.'], 403);
            }
            $this->flash('danger', 'Error de seguridad: Token CSRF inválido.');
            $this->redirect('ventas/crear');
            return;
        }

        // Verificar que la caja esté abierta
        $cajaModel = new Caja();
        $cajaHoy = $cajaModel->obtenerHoy();
        if (!$cajaHoy || $cajaHoy['estado'] !== 'abierta') {
            if ($isJsonRequest) {
                $this->json(['success' => false, 'error' => 'La caja del día está cerrada o no existe.'], 400);
            }
            $this->flash('danger', 'La caja registradora debe estar abierta para registrar ventas.');
            $this->redirect('caja');
            return;
        }

        // Obtener ítems del pedido
        $items = $inputData['items'] ?? [];
        if (is_string($items)) {
            $items = json_decode($items, true) ?: [];
        }

        if (empty($items) || !is_array($items)) {
            if ($isJsonRequest) {
                $this->json(['success' => false, 'error' => 'El pedido debe incluir al menos un producto.'], 422);
            }
            $this->flash('warning', 'Debe agregar al menos un ítem al pedido antes de registrar la venta.');
            $this->redirect('ventas/crear');
            return;
        }

        // Validar importes y medio de pago
        $medioPago = $inputData['medio_pago'] ?? 'efectivo';
        $montoEfectivo = (float)($inputData['monto_efectivo'] ?? 0.0);
        $montoYape = (float)($inputData['monto_yape'] ?? 0.0);
        $total = (float)($inputData['total'] ?? 0.0);

        // Recalcular el total en servidor para asegurar integridad
        $totalCalculado = 0.0;
        foreach ($items as $item) {
            $sub = (float)($item['subtotal'] ?? ((float)($item['precio_unitario'] ?? 0) * (int)($item['cantidad'] ?? 1)));
            $totalCalculado += $sub;
        }

        // Redondear a 2 decimales para evitar problemas de precisión flotante
        $totalCalculado = round($totalCalculado, 2);
        if ($total <= 0 || abs($total - $totalCalculado) > 0.05) {
            $total = $totalCalculado;
        }

        if ($total <= 0) {
            if ($isJsonRequest) {
                $this->json(['success' => false, 'error' => 'El monto total de la venta no es válido.'], 422);
            }
            $this->flash('danger', 'El monto total de la venta debe ser mayor a cero.');
            $this->redirect('ventas/crear');
            return;
        }

        // Conciliar montos según el medio de pago
        if ($medioPago === 'efectivo') {
            $montoEfectivo = $total;
            $montoYape = 0.0;
        } elseif ($medioPago === 'yape') {
            $montoYape = $total;
            $montoEfectivo = 0.0;
        } elseif ($medioPago === 'combinado') {
            $sumaPagos = round($montoEfectivo + $montoYape, 2);
            if (abs($sumaPagos - $total) > 0.05) {
                $msg = "La suma de Efectivo (S/ {$montoEfectivo}) y Yape (S/ {$montoYape}) debe ser exactamente igual al total (S/ {$total}).";
                if ($isJsonRequest) {
                    $this->json(['success' => false, 'error' => $msg], 422);
                }
                $this->flash('danger', $msg);
                $this->redirect('ventas/crear');
                return;
            }
        } else {
            if ($isJsonRequest) {
                $this->json(['success' => false, 'error' => 'Medio de pago no reconocido.'], 422);
            }
            $this->flash('danger', 'Medio de pago inválido.');
            $this->redirect('ventas/crear');
            return;
        }

        // Persistencia transaccional
        $db = Database::getConnection();
        try {
            $db->beginTransaction();

            // 1. Insertar encabezado de venta
            $stmtVenta = $db->prepare("
                INSERT INTO ventas (caja_id, fecha_hora, medio_pago, monto_efectivo, monto_yape, total, anulada)
                VALUES (:caja_id, NOW(), :medio_pago, :monto_efectivo, :monto_yape, :total, 0)
            ");
            $stmtVenta->execute([
                ':caja_id'        => $cajaHoy['id'],
                ':medio_pago'     => $medioPago,
                ':monto_efectivo' => $montoEfectivo,
                ':monto_yape'     => $montoYape,
                ':total'          => $total
            ]);
            $ventaId = (int)$db->lastInsertId();

            // 2. Insertar líneas de detalle y sabores
            $stmtDetalle = $db->prepare("
                INSERT INTO detalle_ventas (venta_id, tipo_producto, tamano_vaso, num_bolas, cantidad, precio_unitario, subtotal)
                VALUES (:venta_id, :tipo_producto, :tamano_vaso, :num_bolas, :cantidad, :precio_unitario, :subtotal)
            ");

            $stmtSabor = $db->prepare("
                INSERT INTO detalle_venta_sabores (detalle_venta_id, sabor_id)
                VALUES (:detalle_venta_id, :sabor_id)
            ");

            foreach ($items as $item) {
                $tipoProd = $item['tipo_producto'] ?? 'helado';
                $tamano = $item['tamano_vaso'] ?? '4oz';
                $numBolas = (int)($item['num_bolas'] ?? 1);
                $cant = max(1, (int)($item['cantidad'] ?? 1));
                $precioUnit = (float)($item['precio_unitario'] ?? 0.0);
                $subtotal = round($precioUnit * $cant, 2);

                $stmtDetalle->execute([
                    ':venta_id'        => $ventaId,
                    ':tipo_producto'   => $tipoProd,
                    ':tamano_vaso'     => $tamano,
                    ':num_bolas'       => $numBolas,
                    ':cantidad'        => $cant,
                    ':precio_unitario' => $precioUnit,
                    ':subtotal'        => $subtotal
                ]);
                $detalleId = (int)$db->lastInsertId();

                // Asociar sabores elegidos (si aplica)
                $saboresIds = $item['sabores'] ?? [];
                if (is_array($saboresIds)) {
                    foreach ($saboresIds as $sid) {
                        $saborIdInt = (int)$sid;
                        if ($saborIdInt > 0) {
                            $stmtSabor->execute([
                                ':detalle_venta_id' => $detalleId,
                                ':sabor_id'         => $saborIdInt
                            ]);
                        }
                    }
                }
            }

            $db->commit();

            if ($isJsonRequest) {
                $this->json([
                    'success'  => true,
                    'message'  => '¡Venta registrada con éxito!',
                    'venta_id' => $ventaId,
                    'redirect' => url('ventas/detalle/' . $ventaId)
                ]);
            }

            $this->flash('success', "Venta #{$ventaId} registrada exitosamente por " . formatMoney($total));
            $this->redirect('ventas/detalle/' . $ventaId);

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Error al registrar venta: " . $e->getMessage());

            if ($isJsonRequest) {
                $this->json(['success' => false, 'error' => 'Error de base de datos al guardar la venta: ' . $e->getMessage()], 500);
            }

            $this->flash('danger', 'Ocurrió un error al guardar la venta. Inténtelo nuevamente.');
            $this->redirect('ventas/crear');
        }
    }

    /**
     * Muestra el comprobante y desglose detallado de una venta
     *
     * @param int|string $id ID de la venta
     */
    public function detalle(int|string $id): void {
        $ventaId = (int)$id;
        $db = Database::getConnection();

        // 1. Obtener encabezado de venta
        $stmtVenta = $db->prepare("
            SELECT v.*, c.fecha as fecha_caja, c.estado as estado_caja
            FROM ventas v
            JOIN cajas c ON v.caja_id = c.id
            WHERE v.id = :id
            LIMIT 1
        ");
        $stmtVenta->execute([':id' => $ventaId]);
        $venta = $stmtVenta->fetch();

        if (!$venta) {
            $this->flash('danger', "La venta #{$ventaId} no fue encontrada.");
            $this->redirect('ventas');
            return;
        }

        // 2. Obtener líneas de detalle
        $stmtDetalles = $db->prepare("
            SELECT dv.*
            FROM detalle_ventas dv
            WHERE dv.venta_id = :id
            ORDER BY dv.id ASC
        ");
        $stmtDetalles->execute([':id' => $ventaId]);
        $detalles = $stmtDetalles->fetchAll();

        // 3. Obtener sabores por cada detalle
        $stmtSabores = $db->prepare("
            SELECT dvs.detalle_venta_id, s.id, s.nombre, s.tipo
            FROM detalle_venta_sabores dvs
            JOIN sabores s ON dvs.sabor_id = s.id
            WHERE dvs.detalle_venta_id IN (
                SELECT id FROM detalle_ventas WHERE venta_id = :id
            )
        ");
        $stmtSabores->execute([':id' => $ventaId]);
        $saboresRows = $stmtSabores->fetchAll();

        $saboresPorDetalle = [];
        foreach ($saboresRows as $sr) {
            $saboresPorDetalle[$sr['detalle_venta_id']][] = $sr['nombre'];
        }

        $this->view('ventas/detalle', [
            'pageTitle'         => "Detalle de Venta #{$ventaId}",
            'venta'             => $venta,
            'detalles'          => $detalles,
            'saboresPorDetalle' => $saboresPorDetalle
        ]);
    }

    /**
     * Anula una venta previamente registrada
     *
     * @param int|string $id ID de la venta a anular
     */
    public function anular(int|string $id): void {
        if (!$this->isPost()) {
            $this->redirect('ventas/detalle/' . $id);
            return;
        }

        if (!verify_csrf()) {
            $this->flash('danger', 'Error de seguridad CSRF.');
            $this->redirect('ventas/detalle/' . $id);
            return;
        }

        $ventaId = (int)$id;
        $db = Database::getConnection();

        $stmt = $db->prepare("SELECT * FROM ventas WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $ventaId]);
        $venta = $stmt->fetch();

        if (!$venta) {
            $this->flash('danger', 'Venta no encontrada.');
            $this->redirect('ventas');
            return;
        }

        if ($venta['anulada'] == 1) {
            $this->flash('warning', 'Esta venta ya se encuentra anulada.');
            $this->redirect('ventas/detalle/' . $ventaId);
            return;
        }

        $updateStmt = $db->prepare("UPDATE ventas SET anulada = 1 WHERE id = :id");
        $updateStmt->execute([':id' => $ventaId]);

        $this->flash('success', "La venta #{$ventaId} ha sido anulada exitosamente.");
        $this->redirect('ventas/detalle/' . $ventaId);
    }
}
