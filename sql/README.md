# SQL migrations (DexaLai)

## Orden sugerido de ejecución
1. Ejecutar el script de acuerdo a la fecha en el nombre del archivo.
2. Respaldar la base de datos antes de correr cada migración.
3. Verificar estructura final con `SHOW CREATE TABLE <tabla>;`.

## Migraciones agregadas
- `2026-02-25_create_pagos.sql`
  - Crea la tabla `pagos` como cabecera de pagos, reutilizable para cualquier zona (incluyendo SUR).
  - Incluye soporte de factura posterior con `factura_pago`, `fecha_factura` y `factura_actualizada`.

- `2026-02-25_create_pagos_detalle.sql`
  - Crea la tabla `pagos_detalle` para relacionar cada pago con tickets/productos provenientes de `captacion_detalle`.
  - Guarda snapshot de `numero_ticket`, `total_kilos`, `precio_unitario` e `importe` al momento del pago.

- `2026-02-24_create_venta_servicio.sql`
  - Crea la tabla `venta_servicio` para separar el cobro de servicio de almacenaje del módulo de flete.
  - Incluye campos de factura/CR solicitados: `doc_factura_ser`, `com_factura_ser`, `aliasser`, `folioser`.
