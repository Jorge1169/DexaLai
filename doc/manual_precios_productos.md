# Manual de uso: precios de productos (pantalla V_producto)

Esta guía explica cómo consultar, agregar y mantener precios de compra y venta para un producto desde la pantalla única [mod/V_producto.php](mod/V_producto.php).

## 1. Cómo llegar
- Menú → Productos → lista de productos.
- Da clic en el código/ID del producto para abrir su detalle.
- En la tarjeta "Precios" verás pestañas de "Precios Activos" e "Histórico" y el botón **Agregar**.

## 2. Qué ves en la tarjeta de precios
- Badges de color: Verde = vigente, Amarillo = no vigente (fuera de rango), Rojo = eliminado.
- Dos tablas en la pestaña activa:
  - Precios de compra (tipo "c"): sin cliente asociado.
  - Precios de venta (tipo "v"): ligados a un cliente/dirección.
- Acciones rápidas por fila: extender vigencia, actualizar fecha fin, eliminar/reactivar.
- Botón **Extender Todos**: suma días a todos los precios activos del producto.

## 3. Alta de un precio (modal "Nuevo precio")
1) Presiona **Agregar**.
2) Completa los campos:
   - **Tipo**: "Compra" (c) o "Venta" (v).
   - **Precio $**: monto con dos decimales.
   - **Fechas**: inicio y fin de vigencia. Usa formato calendario.
   - **Cliente** (solo para venta): obligatorio. Selecciona de la lista; si lo dejas vacío el formulario no se envía.
3) Guarda. Si todo es válido, verás el nuevo precio en la tabla con estado "Vigente" si las fechas cubren hoy.

## 4. Reglas y validaciones clave
- Fechas deben ser válidas (`YYYY-MM-DD`).
- Para precio de venta, el cliente/dirección es requerido.
- Si ya existe un precio activo con mismo tipo, monto y destino, el sistema actualiza las fechas en lugar de crear otro.
- Eliminar no borra: cambia `status` a inactivo; puedes reactivar después.

## 5. Extender o ajustar vigencias
- **Extender vigencia** (ícono calendario con +): ingresa días; el sistema calcula la nueva fecha fin.
- **Actualizar fecha**: define una nueva fecha fin específica.
- **Extender Todos**: aplica los días indicados a todos los precios activos (compra y venta).

## 6. Eliminar o reactivar
- **Eliminar**: marca el precio como inactivo (fila roja). No se usa en cálculos.
- **Reactivar**: devuelve el precio a activo con sus mismas fechas.

## 7. Precios históricos
- Pestaña "Histórico": muestra precios inactivos o vencidos.
- Desde ahí puedes reactivar precios eliminados o volver a eliminarlos si estaban vigentes pero fuera de rango.

## 8. Consejos rápidos
- Verifica la zona seleccionada antes de cargar clientes (afecta la lista disponible).
- Si un precio no aparece vigente, revisa el rango de fechas y el color del badge.
- Usa "Extender Todos" para renovar vigencias en bloque y evitar caducidad masiva.
- Cada acción registra actividad.

## 9. Errores comunes y cómo evitarlos
- No seleccionar cliente en un precio de venta: el formulario se bloquea; elige uno para continuar.
- Fechas invertidas: asegúrate que inicio ≤ fin.
- Duplicar precios idénticos: el sistema los consolida actualizando vigencia; confirma que el mensaje de éxito indica "actualizado".
