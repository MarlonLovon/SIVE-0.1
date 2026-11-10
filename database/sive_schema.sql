-- ============================================================================
-- SIVE 2.0 - Sistema Integrado de Ventas para Heladería y Cremoladas
-- Esquema de Base de Datos MySQL / MariaDB
-- Motor: InnoDB | Cotejamiento: utf8mb4_unicode_ci
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `sive_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `sive_db`;

-- Desactivar temporalmente revisión de claves foráneas para recreación limpia
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `ajustes_inventario`;
DROP TABLE IF EXISTS `ventas_atrasadas`;
DROP TABLE IF EXISTS `reposiciones`;
DROP TABLE IF EXISTS `movimientos_caja`;
DROP TABLE IF EXISTS `detalle_venta_sabores`;
DROP TABLE IF EXISTS `detalle_ventas`;
DROP TABLE IF EXISTS `ventas`;
DROP TABLE IF EXISTS `cajas`;
DROP TABLE IF EXISTS `categorias_gasto`;
DROP TABLE IF EXISTS `sabores`;
DROP TABLE IF EXISTS `configuracion`;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- 1. TABLA: configuracion
-- Parámetros del sistema: precios base por vaso/producto, capacidades de
-- bandejas y baldes, porciones estimadas en litros y calibración de cuchara.
-- ============================================================================
CREATE TABLE `configuracion` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `clave` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Clave identificadora única del parámetro',
  `valor` TEXT NOT NULL COMMENT 'Valor de configuración',
  `descripcion` VARCHAR(255) NULL COMMENT 'Descripción explicativa para administración',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Parámetros globales y precios del negocio';

-- ============================================================================
-- 2. TABLA: sabores
-- Catálogo de sabores disponibles tanto para helados artesanales como
-- para cremoladas frutadas. Permite activar/desactivar según temporada o stock.
-- ============================================================================
CREATE TABLE `sabores` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(100) NOT NULL COMMENT 'Nombre del sabor (ej. Chocolate, Lúcuma)',
  `tipo` ENUM('helado', 'cremolada') NOT NULL COMMENT 'Tipo de producto al que pertenece',
  `activo` TINYINT(1) DEFAULT 1 COMMENT '1 = Disponible para venta, 0 = Inactivo/Agotado',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_sabores_tipo_activo` (`tipo`, `activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Catálogo de sabores de helados y cremoladas';

-- ============================================================================
-- 3. TABLA: categorias_gasto
-- Clasificación de los egresos diarios de caja (Insumos, Envases, etc.)
-- para análisis de costos y control financiero.
-- ============================================================================
CREATE TABLE `categorias_gasto` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Nombre de la categoría (ej. Insumos, Envases)',
  `activo` TINYINT(1) DEFAULT 1 COMMENT '1 = Activa, 0 = Deshabilitada'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Categorías de gastos operativos';

-- ============================================================================
-- 4. TABLA: cajas
-- Control de caja diaria. Permite una sola caja abierta por fecha (UNIQUE).
-- Registra fondo inicial, hora de apertura/cierre, balance de efectivo y Yape.
-- ============================================================================
CREATE TABLE `cajas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `fecha` DATE NOT NULL UNIQUE COMMENT 'Fecha operativa de la caja (única por día)',
  `fondo_inicial` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Monto de apertura en efectivo (sencillo/cambio)',
  `apertura` DATETIME NOT NULL COMMENT 'Fecha y hora exacta de apertura',
  `cierre` DATETIME NULL COMMENT 'Fecha y hora exacta de cierre de turno/día',
  `efectivo_esperado` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Fondo + ventas efectivo - gastos/retiros efectivo',
  `efectivo_contado` DECIMAL(10,2) NULL COMMENT 'Efectivo real contado físicamente al cerrar',
  `total_yape` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Total acumulado de ventas/movimientos por Yape',
  `estado` ENUM('abierta', 'cerrada') DEFAULT 'abierta' COMMENT 'Estado del turno de caja',
  `observaciones` TEXT NULL COMMENT 'Notas, descuadres o incidencias del turno',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_cajas_estado_fecha` (`estado`, `fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Apertura, arqueo y cierre diario de caja';

-- ============================================================================
-- 5. TABLA: ventas
-- Encabezado de transacciones de venta. Soporta pagos en Efectivo, Yape
-- o Pago Combinado (parte efectivo, parte Yape).
-- ============================================================================
CREATE TABLE `ventas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `caja_id` INT NOT NULL COMMENT 'ID de la caja activa donde se registró la venta',
  `fecha_hora` DATETIME NOT NULL COMMENT 'Momento exacto de la venta',
  `medio_pago` ENUM('efectivo', 'yape', 'combinado') NOT NULL COMMENT 'Método de pago utilizado',
  `monto_efectivo` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Monto cubierto en efectivo (S/)',
  `monto_yape` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Monto cubierto mediante Yape/transferencia (S/)',
  `total` DECIMAL(10,2) NOT NULL COMMENT 'Importe total de la venta (S/)',
  `anulada` TINYINT(1) DEFAULT 0 COMMENT '0 = Válida, 1 = Anulada (no suma a caja)',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`caja_id`) REFERENCES `cajas` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX `idx_ventas_caja` (`caja_id`),
  INDEX `idx_ventas_fecha_hora` (`fecha_hora`),
  INDEX `idx_ventas_anulada` (`anulada`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro maestro de ventas';

-- ============================================================================
-- 6. TABLA: detalle_ventas
-- Líneas de detalle por cada venta: tipo de producto (helado/cremolada),
-- tamaño del vaso (4oz, 6oz, 8oz, 12oz, litro), número de bolas y subtotal.
-- ============================================================================
CREATE TABLE `detalle_ventas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `venta_id` INT NOT NULL COMMENT 'ID de la venta a la que pertenece',
  `tipo_producto` ENUM('helado', 'cremolada') NOT NULL COMMENT 'Tipo de producto vendido',
  `tamano_vaso` VARCHAR(20) NOT NULL COMMENT 'Presentación: 4oz, 6oz, 8oz, 12oz, litro',
  `num_bolas` INT DEFAULT 1 COMMENT 'Número de bolas (para helados: 1, 2, 3)',
  `cantidad` INT DEFAULT 1 COMMENT 'Cantidad de unidades de este ítem',
  `precio_unitario` DECIMAL(10,2) NOT NULL COMMENT 'Precio unitario al momento de la venta',
  `subtotal` DECIMAL(10,2) NOT NULL COMMENT 'Cantidad * Precio unitario',
  FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX `idx_detalle_venta` (`venta_id`),
  INDEX `idx_detalle_producto` (`tipo_producto`, `tamano_vaso`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Líneas individuales de productos por venta';

-- ============================================================================
-- 7. TABLA: detalle_venta_sabores
-- Asociación N:M entre una línea de venta y los sabores elegidos por el cliente
-- (ej. helado de 2 bolas con Chocolate y Vainilla, cremolada de 3 sabores).
-- Permite calcular el consumo exacto por sabor para control de inventario.
-- ============================================================================
CREATE TABLE `detalle_venta_sabores` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `detalle_venta_id` INT NOT NULL COMMENT 'ID del detalle de venta',
  `sabor_id` INT NOT NULL COMMENT 'ID del sabor elegido',
  FOREIGN KEY (`detalle_venta_id`) REFERENCES `detalle_ventas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`sabor_id`) REFERENCES `sabores` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX `idx_dvs_detalle` (`detalle_venta_id`),
  INDEX `idx_dvs_sabor` (`sabor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sabores seleccionados para cada vaso o presentación';

-- ============================================================================
-- 8. TABLA: movimientos_caja
-- Egresos o movimientos que afectan el flujo de dinero de la caja activa:
-- gastos de operación, retiros de dinero del dueño o reposición pagada de caja.
-- ============================================================================
CREATE TABLE `movimientos_caja` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `caja_id` INT NOT NULL COMMENT 'Caja activa en la que ocurre el movimiento',
  `tipo` ENUM('gasto', 'retiro', 'reposicion') NOT NULL COMMENT 'Tipo de movimiento',
  `monto` DECIMAL(10,2) NOT NULL COMMENT 'Importe del movimiento (S/)',
  `medio_pago` ENUM('efectivo', 'yape') NOT NULL COMMENT 'De qué fondo sale el dinero (efectivo o cuenta Yape)',
  `categoria_gasto_id` INT NULL COMMENT 'Categoría de gasto (opcional para retiros)',
  `descripcion` VARCHAR(255) NULL COMMENT 'Detalle o justificativo del movimiento',
  `fecha_hora` DATETIME NOT NULL COMMENT 'Fecha y hora del registro',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`caja_id`) REFERENCES `cajas` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  FOREIGN KEY (`categoria_gasto_id`) REFERENCES `categorias_gasto` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX `idx_movimientos_caja` (`caja_id`),
  INDEX `idx_movimientos_tipo` (`tipo`),
  INDEX `idx_movimientos_fecha` (`fecha_hora`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Gastos menores, retiros y egresos de caja';

-- ============================================================================
-- 9. TABLA: reposiciones
-- Ingresos de producto al negocio: bandejas de helado (5 litros) o baldes
-- de cremolada (7 litros). Opcionalmente vinculado a un movimiento_caja_id
-- único para evitar doble contabilidad si fue pagado con dinero de la caja.
-- ============================================================================
CREATE TABLE `reposiciones` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sabor_id` INT NOT NULL COMMENT 'Sabor del producto repuesto',
  `tipo` ENUM('bandeja_helado', 'balde_cremolada') NOT NULL COMMENT 'Tipo de envase/recipiente',
  `litros` DECIMAL(10,2) NOT NULL COMMENT 'Volumen ingresado (5L para helado, 7L para cremolada)',
  `fecha` DATE NOT NULL COMMENT 'Fecha de producción o recepción',
  `costo` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Costo de elaboración o compra',
  `medio_pago` ENUM('efectivo', 'yape') NULL COMMENT 'Medio con el que se canceló (si aplica)',
  `movimiento_caja_id` INT NULL UNIQUE COMMENT 'Vínculo único con movimiento_caja para evitar doble conteo',
  `observaciones` TEXT NULL COMMENT 'Lote, calidad o notas del fabricante',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`sabor_id`) REFERENCES `sabores` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  FOREIGN KEY (`movimiento_caja_id`) REFERENCES `movimientos_caja` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX `idx_reposiciones_sabor` (`sabor_id`),
  INDEX `idx_reposiciones_fecha` (`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Ingresos de bandejas de helado y baldes de cremolada';

-- ============================================================================
-- 10. TABLA: ventas_atrasadas
-- Registro consolidado o diferido de ventas pasadas (por ejemplo días en los
-- que no se usó el sistema digital y se anotó en papel o libretas).
-- ============================================================================
CREATE TABLE `ventas_atrasadas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `fecha_venta` DATE NOT NULL COMMENT 'Fecha real en que ocurrieron las ventas',
  `fecha_registro` DATETIME NOT NULL COMMENT 'Fecha y hora en que se ingresó al sistema',
  `helados_4oz` INT DEFAULT 0 COMMENT 'Total de vasos 4oz de helado vendidos',
  `helados_6oz` INT DEFAULT 0 COMMENT 'Total de vasos 6oz de helado vendidos',
  `helados_8oz` INT DEFAULT 0 COMMENT 'Total de vasos 8oz de helado vendidos',
  `cremoladas_6oz` INT DEFAULT 0 COMMENT 'Total de vasos 6oz de cremolada vendidos',
  `cremoladas_12oz` INT DEFAULT 0 COMMENT 'Total de vasos 12oz de cremolada vendidos',
  `litros_helado` INT DEFAULT 0 COMMENT 'Total de potes de 1L de helado vendidos',
  `total_calculado` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Total calculado según tarifas configuradas',
  `total_anotado` DECIMAL(10,2) NULL COMMENT 'Total real anotado en papel (para comparar diferencias)',
  `notas` TEXT NULL COMMENT 'Explicaciones o aclaraciones del registro histórico',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_ventas_atrasadas_fecha` (`fecha_venta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Carga diferida o histórica de ventas no registradas en vivo';

-- ============================================================================
-- 11. TABLA: ajustes_inventario
-- Ajustes manuales sobre el stock de litros de cada sabor (mermas por deshielo,
-- degustaciones a clientes, calibración física o pérdidas accidentales).
-- ============================================================================
CREATE TABLE `ajustes_inventario` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sabor_id` INT NOT NULL COMMENT 'Sabor afectado por el ajuste',
  `litros_ajuste` DECIMAL(10,3) NOT NULL COMMENT 'Variación en litros: positivo suma, negativo resta',
  `motivo` VARCHAR(255) NOT NULL COMMENT 'Motivo (merma, prueba de degustación, corrección de inventario)',
  `fecha` DATETIME NOT NULL COMMENT 'Fecha y hora de aplicación del ajuste',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`sabor_id`) REFERENCES `sabores` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX `idx_ajustes_sabor` (`sabor_id`),
  INDEX `idx_ajustes_fecha` (`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Mermas, degustaciones y ajustes de inventario de sabores';


-- ============================================================================
-- DATOS INICIALES (SEEDS)
-- ============================================================================

-- ----------------------------------------------------------------------------
-- Configuración básica del sistema y precios por defecto
-- ----------------------------------------------------------------------------
INSERT INTO `configuracion` (`clave`, `valor`, `descripcion`) VALUES
  ('precio_helado_1bola', '4', 'Precio helado simple (1 bola en vaso 4oz)'),
  ('precio_helado_2bolas', '7', 'Precio helado doble (2 bolas en vaso 6oz)'),
  ('precio_helado_3bolas', '9', 'Precio helado triple (3 bolas en vaso 8oz)'),
  ('precio_litro_helado', '28', 'Precio helado pote de 1 litro para llevar'),
  ('precio_cremolada_1_2_sabores', '6', 'Precio cremolada vaso chico 6oz (1 a 2 sabores)'),
  ('precio_cremolada_3_sabores', '8', 'Precio cremolada vaso grande 12oz (hasta 3 sabores)'),
  ('litros_bandeja_helado', '5', 'Litros estándar contenidos en una bandeja de helado'),
  ('litros_balde_cremolada', '7', 'Litros estándar contenidos en un balde de cremolada'),
  ('bolas_4oz', '1', 'Número de bolas asignadas al vaso de 4oz'),
  ('bolas_6oz', '2', 'Número de bolas asignadas al vaso de 6oz'),
  ('bolas_8oz', '3', 'Número de bolas asignadas al vaso de 8oz'),
  ('diametro_cuchara_mm', '57', 'Diámetro estándar de la cuchara porcionadora en milímetros (#16)'),
  ('consumo_estimado_bola_litros', '0.05', 'Consumo estimado de helado por bola en litros (50 ml aprox.)'),
  ('consumo_estimado_cremolada_6oz_litros', '0.18', 'Consumo estimado de cremolada vaso 6oz en litros'),
  ('consumo_estimado_cremolada_12oz_litros', '0.35', 'Consumo estimado de cremolada vaso 12oz en litros')
ON DUPLICATE KEY UPDATE `valor` = VALUES(`valor`), `descripcion` = VALUES(`descripcion`);

-- ----------------------------------------------------------------------------
-- Catálogo de Sabores Iniciales
-- ----------------------------------------------------------------------------
-- Helados (10 sabores)
INSERT INTO `sabores` (`nombre`, `tipo`, `activo`) VALUES
  ('Chocolate', 'helado', 1),
  ('Vainilla', 'helado', 1),
  ('Fresa', 'helado', 1),
  ('Lúcuma', 'helado', 1),
  ('Mango', 'helado', 1),
  ('Maracuyá', 'helado', 1),
  ('Coco', 'helado', 1),
  ('Chicle', 'helado', 1),
  ('Ron con pasas', 'helado', 1),
  ('Oreo', 'helado', 1);

-- Cremoladas (6 sabores)
INSERT INTO `sabores` (`nombre`, `tipo`, `activo`) VALUES
  ('Mango', 'cremolada', 1),
  ('Maracuyá', 'cremolada', 1),
  ('Fresa', 'cremolada', 1),
  ('Lúcuma', 'cremolada', 1),
  ('Guanábana', 'cremolada', 1),
  ('Camu camu', 'cremolada', 1);

-- ----------------------------------------------------------------------------
-- Categorías de Gastos Operativos
-- ----------------------------------------------------------------------------
INSERT INTO `categorias_gasto` (`nombre`, `activo`) VALUES
  ('Insumos', 1),
  ('Transporte', 1),
  ('Limpieza', 1),
  ('Servicios', 1),
  ('Envases', 1),
  ('Varios', 1);
