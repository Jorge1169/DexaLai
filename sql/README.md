# SQL migrations (DexaLai)

## Orden sugerido de ejecución
1. Ejecutar el script de acuerdo a la fecha en el nombre del archivo.
2. Respaldar la base de datos antes de correr cada migración.
3. Verificar estructura final con `SHOW CREATE TABLE <tabla>;`.

## Migraciones agregadas
- `2026-02-24_create_venta_servicio.sql`
  - Crea la tabla `venta_servicio` para separar el cobro de servicio de almacenaje del módulo de flete.
  - Incluye campos de factura/CR solicitados: `doc_factura_ser`, `com_factura_ser`, `aliasser`, `folioser`.
