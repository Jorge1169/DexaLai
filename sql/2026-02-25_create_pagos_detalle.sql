-- Migración: crear tabla detalle de pagos
-- Fecha: 2026-02-25
-- Nota: Relaciona cada pago con los tickets/productos de captación pagados.

CREATE TABLE IF NOT EXISTS `pagos_detalle` (
  `id_pago_detalle` int NOT NULL AUTO_INCREMENT,
  `id_pago` int NOT NULL,
  `id_detalle` int NOT NULL COMMENT 'captacion_detalle.id_detalle',
  `id_captacion` int NOT NULL,
  `id_prod` int NOT NULL,
  `numero_ticket` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_kilos` decimal(14,2) NOT NULL DEFAULT '0.00',
  `precio_unitario` decimal(14,4) NOT NULL DEFAULT '0.0000',
  `importe` decimal(14,2) NOT NULL DEFAULT '0.00',
  `status` tinyint NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pago_detalle`),
  UNIQUE KEY `uniq_pago_detalle` (`id_pago`,`id_detalle`),
  KEY `idx_pagos_detalle_pago` (`id_pago`),
  KEY `idx_pagos_detalle_detalle` (`id_detalle`),
  KEY `idx_pagos_detalle_ticket` (`numero_ticket`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;