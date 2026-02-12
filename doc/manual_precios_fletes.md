# Manual rapido: precios de fletes

Este manual resume como usar las pantallas de transportes para alta, edicion y carga masiva de precios de flete. Referencias de codigo: [mod/transportes.php](mod/transportes.php), [mod/V_transporte.php](mod/V_transporte.php) y [mod/subir_precios_masivo.php](mod/subir_precios_masivo.php).

## 1. Panorama general
- Un precio de flete vive en la tabla `precios` y se liga a un fletero (`id_prod = id_transp`).
- Tipos principales: `FT` y `FV`. En zona MEO existen `MFT`/`MFV` mas el campo `cap_ven` (`CAP` o `VEN`).
- Un precio tiene vigencia (`fecha_ini`, `fecha_fin`) y estado (`status`: 1 activo, 0 eliminado).

## 2. Pantalla de lista de transportes
Ubicacion: [mod/transportes.php](mod/transportes.php)
- Botones principales: "Nuevo Transporte", "Carga Masiva de Precios" y toggle "Mostrar Inactivos".
- Columna "Estado Precios" indica la situacion actual para cada fletero (vigentes, por caducar en <=3 dias, caducados, sin precios). Esto se calcula solo con precios activos (`status=1`) y tipos `FT`/`FV`.
- Click en las placas abre el detalle del fletero (pantalla V_transporte) para administrar precios individuales.

## 3. Gestion individual por fletero
Ubicacion: [mod/V_transporte.php](mod/V_transporte.php)
- Alta de precio (modal "Nuevo precio"):
  - Campos basicos: tipo (`FT`/`FV` o `MFT`/`MFV` en MEO), precio, origen, destino, peso minimo (`conmin`), fechas de inicio/fin.
  - Zona MEO: selecciona `cap_ven` para definir si el movimiento es CAP (proveedor -> almacen) o VEN (almacen -> cliente). Al cambiarlo, los combos origen/destino se rellenan segun la eleccion.
- Extender o actualizar vigencia (modales dedicados):
  - Extender dias a un precio puntual: ingresa dias y se calcula la nueva fecha fin.
  - Actualizar fecha fin exacta de un precio puntual.
  - Extender en bloque todos los precios activos del fletero (tipos FT/FV): suma los dias indicados a `fecha_fin`.
- Eliminar / reactivar precio:
  - Eliminar marca `status=0` (no borra registro). Reactivar lo devuelve a `status=1`.
- Marcas visuales de estado por precio: "vigente" (dentro de rango), "no vigente" (fuera de rango) o "eliminado" (`status=0`). La tabla colorea cada fila segun esto.

## 4. Carga masiva de precios
Ubicacion: [mod/subir_precios_masivo.php](mod/subir_precios_masivo.php)
- Paso 1: elegir fleteros
  - Marca "Aplicar a TODOS los fleteros activos de la zona" para no seleccionar uno por uno. Si no, marca checkboxes individuales.
  - El filtro de zona usa `$_SESSION['selected_zone']`; si vale `0` lista todos.
- Paso 2: capturar datos del precio
  - Campos obligatorios: tipo, precio, origen, destino; opcional `conmin`.
  - Fechas: por defecto inicio hoy, fin un mes despues.
  - Zona MEO: selecciona `cap_ven` (CAP o VEN) y tipo `MFT`/`MFV`. Los combos origen/destino se adaptan al tipo (CAP usa proveedores->almacenes, VEN usa almacenes->clientes).
- Logica interna por fletero seleccionado:
  - Si ya existe un precio activo con igual origen, destino, tipo (y `cap_ven` si aplica), se actualiza `fecha_fin`, `precio`, `conmin`, `usuario`.
  - Si no existe, se inserta un nuevo registro con `status=1`.
- Al final muestra cuantos precios se insertaron y cuantos se actualizaron.

## 5. Buenas practicas y validaciones rapidas
- Fechas: el codigo valida formato `YYYY-MM-DD`. Verifica que inicio <= fin antes de guardar.
- Revisar zona actual en la cabecera de cada pantalla para cargar origen/destino correctos.
- Para MEO, no olvides `cap_ven`; sin ese valor la carga masiva no agrega la columna y la coincidencia para actualizar no se cumple.
- Eliminacion no borra datos: reactivar es posible desde el mismo detalle del fletero.
- Usa los botones de extensiones para mantener vigencias en bloque y evitar caducidad.

## 6. Pruebas manuales sugeridas
1) Alta individual: en un fletero, abre "Nuevo precio", captura tipo FT, origen A, destino B, precio 100, fechas hoy->+15 dias. Confirma que aparece "Vigente" en la tabla.
2) Extender puntual: en el mismo precio abre "Extender vigencia", suma 7 dias y valida nueva `fecha_fin` y badge actualizado.
3) Extender todos: ejecuta "Extender todos" con 5 dias y revisa cualquier precio activo del fletero.
4) Eliminar y reactivar: elimina un precio, verifica que la fila queda en rojo y luego reactiva para volver a verde/amarillo.
5) Carga masiva: en "Carga Masiva de Precios" elige 2 fleteros, tipo FT, mismo origen/destino, precio 200, fechas futuras. Repite con otro precio para validar que actualiza en lugar de duplicar.
6) MEO: con zona MEO seleccionada, en carga masiva prueba `cap_ven=CAP` + `MFT` y luego `cap_ven=VEN` + `MFV`; verifica que combos origen/destino cambian segun el tipo.

## 7. Notas de seguridad y auditoria
- Cada accion registra `usuario` en inserciones/actualizaciones y usa `logActivity` para traza basica.
- No se expone borrado fisico; usar eliminacion logica.
- Si ocurre un error en carga masiva, la transaccion se revierte y se muestra alerta.

## 8. Rutas rapidas
- Lista de transportes y estado de precios: menu Transporte -> columna Estado Precios.
- Gestion por fletero: click en placas en la lista.
- Carga masiva: boton "Carga Masiva de Precios" en la lista.
