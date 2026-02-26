-- Migración: crear tabla de pagos (genérica para todas las zonas)
-- Fecha: 2026-02-25
-- Nota: Esta tabla es cabecera de pagos y está pensada para escalar a cualquier tipo de zona.

CREATE TABLE IF NOT EXISTS `pagos` (
  `id_pago` int NOT NULL AUTO_INCREMENT,
  `folio` int NOT NULL COMMENT 'Consecutivo interno por zona/periodo según lógica de aplicación',
  `fecha_pago` date NOT NULL,
  `zona` int NOT NULL COMMENT 'zonas.id_zone',
  `id_prov` int DEFAULT NULL COMMENT 'Proveedor relacionado (cuando el pago provenga de captaciones)',
  `concepto` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `subtotal` decimal(14,2) DEFAULT NULL,
  `impuesto_traslado` decimal(14,2) DEFAULT NULL,
  `impuesto_retenido` decimal(14,2) DEFAULT NULL,
  `total` decimal(14,2) DEFAULT NULL,
  `factura_pago` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Se captura después de crear el pago',
  `fecha_factura` date DEFAULT NULL,
  `factura_actualizada` datetime DEFAULT NULL,
  `id_user` int NOT NULL,
  `status` tinyint NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id_pago`),
  KEY `idx_pagos_zona` (`zona`),
  KEY `idx_pagos_proveedor` (`id_prov`),
  KEY `idx_pagos_fecha` (`fecha_pago`),
  KEY `idx_pagos_status` (`status`),
  KEY `idx_pagos_factura` (`factura_pago`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;