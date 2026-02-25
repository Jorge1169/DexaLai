-- Migración: crear tabla de servicios de almacenaje por venta
-- Fecha: 2026-02-24
-- Nota: Tabla separada de venta_flete para no mezclar conceptos de flete y servicio.

CREATE TABLE IF NOT EXISTS `venta_servicio` (
  `id_venta_servicio` int NOT NULL AUTO_INCREMENT,
  `id_venta` int NOT NULL,
  `id_pre_servicio` int NOT NULL COMMENT 'Referencia a precios.id_precio (tipos SVT/SVV)',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `factura_servicio` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `doc_factura_ser` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `com_factura_ser` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `impuestoTraslado_ser` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `impuestoRetenido_ser` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subtotal_ser` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_ser` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `aliasser` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `folioser` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_actualizacion` datetime DEFAULT NULL,
  PRIMARY KEY (`id_venta_servicio`),
  KEY `id_venta` (`id_venta`),
  KEY `id_pre_servicio` (`id_pre_servicio`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;