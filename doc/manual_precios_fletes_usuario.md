# Guía rápida para usuarios (precios de flete)

Esta guía explica, cómo dar de alta precios de flete, extender vigencias y hacer cargas masivas. Pantallas involucradas: lista de transportes, detalle de un fletero y carga masiva.

## 1. Dónde empezar
- Menú principal → Transporte.
- En la tabla verás tus fleteros. El ID del fletero es un enlace al detalle.
- El botón Carga Masiva de Precios está en la parte superior de la lista.
- El selector de zona (si existe en la cabecera) define qué datos se muestran y qué opciones de origen/destino verás.

## 2. Ver el estado de los precios de un fletero
- En la tabla, la columna Estado Precios muestra un badge:
  - Verde: precios vigentes.
  - Amarillo: por caducar (menos de 3 días).
  - Rojo: sin precios o todos caducados.
- Haz clic en el ID del fletero para abrir el detalle del fletero.

## 3. Alta rápida de un precio individual
En el detalle del fletero (pantalla de precios):
- Presiona "Nuevo precio".
- Completa cada campo así:
  - Tipo: selecciona el que ves en pantalla: "Por viaje" (importe fijo) o "Por tonelada" (se multiplica por el peso registrado). En zona MEOQUI verás las variantes "Por viaje (MEOQUI)" y "Por tonelada (MEOQUI)".
  - Origen y destino: elige de las listas desplegables; usa el buscador del combo para localizar rápido.
  - Precio: captura el monto en pesos (usa dos decimales si aplica).
  - Peso mínimo (opcional): deja en 0 si no aplica mínimo; si hay mínimo, captura las toneladas mínimas para cobrar.
  - Fechas: inicio y fin de vigencia; hoy y una fecha futura suele ser lo usual.
  - Zona MEOQUI: antes de elegir origen/destino, selecciona el tipo de movimiento:
    - CAP: movimiento proveedor → almacén; el sistema carga proveedores como origen y almacenes como destino.
    - VEN: movimiento almacén → cliente; el sistema carga almacenes como origen y clientes como destino.
- Guarda y verifica que el precio aparezca con estado "Vigente" si las fechas cubren hoy.

## 4. Extender o ajustar vigencias
En la tabla de precios del fletero verás botones para cada precio:
- Extender: agrega días a la fecha fin. Al abrir el modal, ingresa días y confirma; el sistema calcula la nueva fecha.
- Actualizar fecha: fija directamente una nueva fecha fin para ese precio.
- Extender todos: suma los días que indiques a todos los precios activos de ese fletero (por viaje o por tonelada).

## 5. Pausar o reactivar un precio
- Eliminar: marca el precio como inactivo (no se usa). Verás la fila en rojo.
- Reactivar: devuelve el precio a activo; la fila volverá a verde/amarillo según vigencia.

## 6. Carga masiva de precios (varios fleteros a la vez)
- Desde la lista de transportes, pulsa "Carga Masiva de Precios".
- Paso 1: selección de fleteros.
  - Marca "Aplicar a TODOS los fleteros activos de la zona" si quieres incluirlos a todos; si no, selecciona con checkboxes.
- Paso 2: datos del precio.
  - Ingresa tipo ("Por viaje" o "Por tonelada"; en MEOQUI verás las versiones con etiqueta MEOQUI), precio, origen, destino, peso mínimo (opcional) y fechas.
  - Zona MEOQUI: elige CAP (proveedor → almacén) o VEN (almacén → cliente). Las listas se llenan automáticamente.
  - Recuerda: precio por viaje = fijo; precio por tonelada = se multiplicará por el peso que se registre.
- Enviar: el sistema inserta nuevos precios o, si ya había uno con las mismas condiciones, actualiza su vigencia y valor.
- Al final se muestra un mensaje con cuántos precios se insertaron y cuántos se actualizaron.

## 7. Consejos rápidos
- Revisa siempre la zona seleccionada antes de capturar precios (afecta listas de origen/destino y qué fleteros ves).
- Usa Extender todos cuando veas varios precios a punto de caducar.
- Si no ves un fletero, habilita "Mostrar Inactivos" y revisa su estado.
- No necesitas tocar bases de datos: todo se hace desde las pantallas descritas.

## 8. Rutas directas
- Lista y estado de fleteros: Transporte → tabla de transportes.
- Precios de un fletero: clic en el ID del fletero en la tabla de transportes.
- Carga masiva: botón "Carga Masiva de Precios" en la lista de transportes.
