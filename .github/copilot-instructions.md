# Copilot / AI agent instructions for DexaLai

Breve: instrucciones prácticas y ejemplos para que un agente IA sea productivo inmediatamente en este proyecto PHP (WAMP, Windows).

1) Objetivo rápido
- Aplicación PHP hospedada en WAMP (ruta de trabajo: c:\\wamp64\\www\\DexaLai). El punto de entrada web principal es `index.php` en la raíz.

2) Arranque local (rápido)
- Ejecutar en Windows con WAMP: colocar el proyecto en `c:\\wamp64\\www\\DexaLai` y abrir `http://localhost/DexaLai/`.
- Revisar conexiones de DB en `config/conexiones.php` y `config/database_context.php` antes de ejecutar.

3) Arquitectura y 'big picture'
- Estructura dominante: scripts PHP monolíticos + módulos en `mod/` (cada `mod/*.php` suele ser una página/handler). Muchos ficheros raíz actúan como endpoints AJAX o páginas.
- `config/` guarda conexiones, contexto y constantes (ej.: `BusinessContext.php`, `conexiones.php`, `database_context.php`).
- `AJAX/` contiene endpoints invocados desde el frontend por llamadas XHR/Fetch. En la raíz también hay endpoints AJAX (`ajax_*.php`).
- Entidades/DAOs: clases prefijadas `E_` dentro de `mod/` (por ejemplo `E_producto.php`, `E_compra.php`) que representan modelos ligeros.
- Integraciones: `PHPMailer/` para envío de correos (ver `PHPMailer/README.md` y `externo/` para scripts de envío). `sql/` contiene dumps o scripts SQL.

4) Flujos de datos importantes
- Frontend → AJAX endpoints (`AJAX/` o `ajax_*.php`) → lógica en `mod/` y `config/` → DB (credenciales en `config/conexiones.php`).
- Envío de correos desde scripts en `externo/` o `correo.php` que usan `PHPMailer`.
- Subidas/descargas: revisar `uploads/` y `descargas/PlantillaParaSubirCompraVentas.csv` para formatos esperados.

5) Convenciones del proyecto (observadas)
- Páginas / controladores mezclados: los archivos en `mod/` y raíz contienen tanto lógica como presentación. Evitar suposiciones de MVC limpias.
- Prefijo `E_` para clases de entidad; agregar propiedades allí si extiendes el esquema.
- Endpoints AJAX devuelven JSON o fragmentos HTML según el archivo; inspeccionar archivos concretos (`get_productos.php`, `get_clientes.php`) para ejemplo.

6) Archivos clave a inspeccionar (ejemplos)
- `config/conexiones.php` — configuración y funciones de conexión a la DB.
- `config/database_context.php`, `config/BusinessContext.php` — constantes y contexto global.
- `mod/inicio.php` — ejemplo de módulo de entrada (punto de referencia para UI/permiso/session).
- `AJAX/guardar_factura_flete.php` — ejemplo de endpoint AJAX con lógica de negocio.
- `get_productos.php`, `get_clientes.php` — endpoints de consulta simples.
- `PHPMailer/README.md` y `externo/correo_proveedores.php` — patrón de envío de correo.

7) Cambios en la base de datos y modelos
- Si actualizas tablas, sincroniza las propiedades de la clase `E_*` correspondiente (`mod/E_producto.php`, etc.).
- Actualiza los scripts SQL en `sql/` y documenta el cambio en un nuevo archivo `sql/README.md` si añades migraciones.

8) Pruebas y depuración (qué funciona hoy)
- No hay suite de tests automatizados detectable. Pruebas manuales: usar el navegador y las herramientas de red para invocar endpoints AJAX.
- Para depurar: habilitar `display_errors` en php.ini o añadir temporalmente `ini_set('display_errors',1); error_reporting(E_ALL);` en el script a testar.

9) Reglas prácticas para generar código
- Cuando propongas cambios, adapta al estilo actual: modificar archivos individuales (no reestructurar en grandes refactors) y respetar la mezcla lógica/HTML.
- Evita introducir frameworks nuevos sin consenso (no se detecta `composer.json` ni PSR-4 aquí).
- Para nuevos endpoints AJAX, seguir patrón existente: validar inputs, llamar a funciones en `mod/` o clases `E_*`, devolver JSON o HTML según la convención del endpoint similar.

10) Integraciones externas y credenciales
- Variables sensibles y claves aparecen en `config/` (revisar `groq_key.php` y archivos de conexión). No subir credenciales en commits.

11) Sugerencias rápidas para PRs de IA
- Incluir un ejemplo de prueba manual en la descripción del PR (URL local + payload de ejemplo para el endpoint AJAX).
- Indicar claramente los archivos modificados y las dependencias externas afectadas (correo, subida de archivos, SQL).

12) Dónde mirar cuando algo falla
- Revisar logs de Apache/PHP en WAMP y `error_reporting`.
- Consultar `config/conexiones.php` para fallos de DB y `PHPMailer` logs para errores de correo.

Feedback
- Si falta alguna zona crítica o conoces convenciones internas adicionales (nombres de ramas, proceso de despliegue), indícame y ajusto este archivo.
