# Bitácora de Desarrollo — Minisite Moderno & Tailwind

> [!IMPORTANT]
> **Vinculación con el Cerebro Digital (Obsidian):** Este proyecto está documentado en el vault centralizado de NOAX bajo el código **PROY-2026-006** (Ficha física: [PROY-2026-006_mini-site-w2026.md](file:///D:/cerebro/cerebro-noax/02_Productos_Proyectos/Proyectos/PROY-2026-006_mini-site-w2026.md)).

Diario de ingeniería del desarrollo de la landing page de alto impacto para la reestructuración del proyecto, utilizando Vite y Tailwind CSS.

---

## 📅 Registro de Pasos Realizados

| Fecha / Hora | Fase / Paso | Descripción Técnica | Archivos Creados/Modificados |
| :--- | :--- | :--- | :--- |
| 01-Jun-2026 11:30 | Inicialización | Creación del archivo de bitácora `LOG_DESARROLLO.md` para control de calidad. | `LOG_DESARROLLO.md` |
| 01-Jun-2026 11:32 | Limpieza | Eliminación del archivo obsoleto del mundial de fútbol `api-proxy.php` del directorio. | `api-proxy.php` (Eliminado) |
| 01-Jun-2026 11:35 | Configuración | Creación de archivos de configuración (`package.json`, `tailwind.config.js`, `postcss.config.js`, `src/input.css`) e instalación exitosa de dependencias con `npm install`. | `package.json`, `tailwind.config.js`, `postcss.config.js`, `src/input.css` |
| 01-Jun-2026 11:49 | Estructura HTML | Creación de la estructura del minisite del Mundial 2026 para Miravos País en `index.html` (Navbar con logo, Módulo Live, Calendario, Ruta de la Albiceleste, Grid de Goleadores y Vista de Detalles). | `index.html` |
| 01-Jun-2026 11:51 | Interactividad JS | Programación en `app.js` (datos mock, clase `MiniSiteApp` para renderizado y ruteo, EventSource para eventos SSE y notificación Toast de gol en vivo). Se exportó `app` de forma global (`window.app = app`) para mantener compatibilidad con manejadores inline en Vite. | `app.js` |
| 01-Jun-2026 11:55 | Verificación | Ejecución del servidor local de desarrollo con `vite` y verificación en tiempo real (HMR) del minisite del Mundial 2026 funcionando en el navegador. | Ninguno (Servidor iniciado) |
| 01-Jun-2026 12:13 | Logo Oficial | Reemplazo del logotipo generado por el logotipo real provisto por el usuario (`cropped-mv-favicon-1_2.jpg`). | `cropped-mv-favicon-1_2.jpg` (Actualizado) |
| 01-Jun-2026 18:15 | Rediseño Premium & Interactividad | Actualización de la cabecera original, inserción de las 3 tarjetas estadísticas superiores, diseño horizontal compacto para en vivo (segunda imagen), restricción de altura en grillas, configuración horaria de Tucumán (UTC-3), banderas integradas en vez de abreviaturas e interactividad de detalles y observaciones tácticas con modales glassmórficos para países y candidatos de Bota de Oro. | `index.html`, `app.js` |
| 01-Jun-2026 18:22 | Ajustes API y Texto | Modificación de etiqueta "Live API" a "En Vivo" en index.html. Verificación de la API Zafronix y constatación de que no devuelve fotos de jugadores en el perfil ni en las plantillas convocadas. | `index.html` |
| 01-Jun-2026 18:26 | Corrección de Banderas API | Se detectó que la API real de Zafronix devuelve "homeTeam" y "awayTeam" como cadenas de texto plano, perdiendo la estructura de objetos con banderas y códigos de selección de los datos mock. Se implementó una base de datos local de mapeo de metadatos (teamMetaData) y la función normalizeMatch para resolver de forma dinámica las banderas nacionales y códigos de país de cada selección. | `app.js` |
| 01-Jun-2026 18:46 | Solución Banderas Windows (FlagCDN) | Se identificó que el sistema operativo Windows no renderiza nativamente emojis de banderas en navegadores Chromium (Chrome/Edge), mostrándolas como texto (placeholder). Se solucionó migrando el renderizado a imágenes de banderas SVG/PNG consumidas a través de `flagcdn.com`. | `app.js` |
| 01-Jun-2026 22:00 | Consolidación y Limpieza General | Integración total en un único archivo (`index.html`), remoción completa del archivo externo obsoleto `app.js`. Adaptación de colores y contraste (opacidad de tarjetas al 65% y contenedor base oscuro al 70%) para incrustación dentro del recuadro lila de la web padre. Remoción de avatares con iniciales (placeholders de futbolistas) y adición de lógica `onerror` para remover dinámicamente imágenes de bandera fallidas (evitando iconos rotos en modo local/offline). | `index.html`, `app.js` (Eliminado) |
| 02-Jun-2026 10:55 | Integración de Paleta de Marca | Configuración de los colores corporativos (brand-indigo, brand-slate, brand-bone, brand-platinum, brand-snow y brand-dark) y actualización de las hojas de estilo y clases dinámicas de index.html. | `index.html` |
| 02-Jun-2026 12:54 | Integración de Logo SVG | Reemplazo del logo antiguo por el nuevo logo vectorizado logo.svg con dimensiones adaptadas (w-8 h-12) para mantener el formato vertical original y optimizar la velocidad del sitio. | `index.html`, `logo.svg` |
| 02-Jun-2026 16:12 | Fondo Sólido y Fusión Sin Bordes | Modificación de estilos y clases en index.html para establecer el fondo del body en #4E31A0, eliminar bordes redondeados y márgenes del contenedor base (fusión total), y redefinir las tarjetas glass-card con base oscura para contraste óptimo. | `index.html` |
| 02-Jun-2026 16:27 | Preparación de Despliegue | Creación de la carpeta dedicada wc2026/ y generación de los archivos optimizados listos para FTP (index.html, logo.svg y api-proxy.php con caché resiliente de fallback). | `wc2026/index.html`, `wc2026/logo.svg`, `wc2026/api-proxy.php` |
| 02-Jun-2026 19:15 | Solución Cuelgue en Móviles | Desactivación de EventSource (SSE) en agentes móviles/pantallas chicas para evitar saturar sockets HTTP/1.1 y colas de procesos PHP, y adición de try-catch con UX de error en modales. | `index.html`, `wc2026/index.html` |
| 02-Jun-2026 21:23 | Altura de Iframe Auto-adaptativa | Integración de window.postMessage en index.html y un script oyente en el widget de Elementor para ajustar dinámicamente la altura del iframe de manera automática y evitar recortes visuales o scrolls internos. | `index.html`, `wc2026/index.html`, `guia_despliegue.md` |
| 03-Jun-2026 13:28 | Restructuración de Widgets y Traducción | Remoción del widget Campeón, reubicación de los widgets de Goles debajo del countdown, expansión de teamMetaData a 100 países y mejora en getTeamMeta con normalización unicode. | `index.html`, `wc2026/index.html` |
| 03-Jun-2026 14:00 | Alineación Inferior de Columnas | Ajuste de altura de `fixturesContainer` de `max-h-[550px]` a `max-h-[594px]` en `index.html`, `wc2026/index.html` y compilación en `dist/` para lograr alineación pixel-perfect (diferencia de 0px) con la barra lateral en resoluciones de escritorio. | `index.html`, `wc2026/index.html`, `dist/index.html` |
| 03-Jun-2026 14:10 | Datos Reales del Fixture 2026 | Reemplazo de los 7 partidos mock ficticios por los 25 primeros partidos reales del fixture oficial del Mundial 2026 obtenidos del WC Explorer de Zafronix. Argentina corregida al Grupo J vs Argelia (Jun 17, Kansas City). Agregados mapeos de Noruega, Bosnia y Herzegovina, Cabo Verde, Curazao y Korea Republic a `teamMetaData`. | `index.html`, `wc2026/index.html` |
| 29-Jun-2026 | Chequeo de integridad y sincronía | Verificación de datos reales vía MCP contra datos mostrados en el sitio. Confirmado: Argentina 3-0 Algeria, 2-0 Austria, 1-3 Jordan (finalizado); próximo vs Cabo Verde 03/07. Detectadas y corregidas diferencias entre `index.html` raíz y `wc2026/index.html`: función `normalizePlayerApiData`, referee robusto (string o objeto), label "Mundiales" en ficha de jugador, fallback de edad `N/D`. | `index.html`, `wc2026/index.html` |
| 29-Jun-2026 | Fix orden "Terminados" | El filtro "Terminados" ahora muestra los partidos con el más reciente arriba. Corregido: `.reverse()` mutaba `this.matches` causando inconsistencias. Reemplazado por `.sort((a,b) => new Date(b.date) - new Date(a.date))` que no muta el array original. | `index.html`, `wc2026/index.html` |
| 29-Jun-2026 | Fix ficha de jugador (undefined) | Panel de jugador mostraba todo "undefined" al hacer click en goleadores. Dos causas: (1) lookup fallaba porque la API devuelve apellido solo ("Messi") pero el playerDb tiene claves con nombre completo — resuelto con búsqueda tolerante por apellido. (2) `normalizePlayerApiData` pisaba datos buenos del playerDb con campos vacíos de la API — resuelto con merge inteligente que solo aplica campos con valor real. | `index.html`, `wc2026/index.html` |

---

## 🐞 Historial de Errores y Soluciones

Esta sección documentará cualquier error encontrado durante el desarrollo (terminal, compilación de Tailwind, consola del navegador o fallas visuales de responsividad), detallando su causa raíz y la solución implementada.

### [ERR-001] Banderas Nacionales No Visibles (Inconsistencia de API)
* **Fase/Hora**: Fase de Integración de API - 01-Jun-2026 18:24
* **Error Encontrado**:
  ```text
  Las banderas nacionales no se renderizan al consumir los datos reales de la API. Los campos home_team.flag_emoji y away_team.flag_emoji devuelven undefined en consola.
  ```
* **Causa Raíz**:
  La API real de Zafronix devuelve una estructura plana donde `homeTeam` y `awayTeam` son simples cadenas de texto (ej. `"Mexico"`), y no objetos con banderas y códigos como los datos simulados en la maqueta inicial. Además, las propiedades usan camelCase (como `homeScore` y `awayScore`) en vez de snake_case.
* **Solución Aplicada**:
  Se introdujo un diccionario de metadatos de selecciones (`teamMetaData`) en `app.js` (luego unificado en `index.html`) y se desarrolló el método `normalizeMatch` para traducir las cadenas de texto del API real y mapear automáticamente el nombre traducido al español, el emoji de bandera correspondiente y el código de selección de 3 letras (ej. `"Mexico"` -> `{ name: "México", flag_emoji: "🇲🇽", code: "MEX" }`), unificando la salida para ambos modos (API y Mock).

### [ERR-002] Emojis de Banderas Renderizados como Código de Texto en Windows
* **Fase/Hora**: Fase de Verificación Visual - 01-Jun-25 18:44
* **Error Encontrado**:
  ```text
  Las banderas siguen apareciendo como abreviaturas de texto de dos letras (ej. "MX", "PL") en la pantalla del usuario.
  ```
* **Causa Raíz**:
  Limitación nativa del sistema operativo Windows: Microsoft no da soporte nativo a emojis de banderas de países en su tipografía estándar de emojis (Segoe UI Emoji). En navegadores basados en Chromium (como Google Chrome y Microsoft Edge en Windows), los emojis de bandera se descomponen y muestran como dos letras del código ISO de país, pareciendo placeholders de texto.
* **Solución Aplicada**:
  Se migró el sistema de renderizado de banderas de emojis a imágenes reales. Se implementó una función helper `getFlagImgHTML` que consume las banderas nacionales en formato PNG/SVG desde la CDN gratuita de alto rendimiento `flagcdn.com`, y se adaptaron todos los componentes visuales del sitio (calendario, en vivo, spotlight de Argentina y goleadores) para inyectar etiquetas `<img>` estilizadas con sombras y bordes finos.

### [ERR-003] Marcadores de Posición Rotos en Modo Offline/Local
* **Fase/Hora**: Fase de Consolidación - 01-Jun-2026 22:00
* **Error Encontrado**:
  ```text
  Al correr en modo local/offline o cuando las peticiones a flagcdn.com fallan, el navegador muestra iconos de imagen rota (broken image) en los espacios de las banderas. Además, las iniciales de futbolistas simulan placeholders vacíos de fotos.
  ```
* **Causa Raíz**:
  Falta de controladores de errores en las etiquetas `<img>` dinámicas de banderas, dejando el marcador de posición roto visible. Para los futbolistas, las iniciales hacían obvia la ausencia de fotos.
* **Solución Aplicada**:
  Se añadió un manejador `onerror="this.remove()"` dinámico en la función `getFlagImgHTML` para descartar por completo cualquier imagen de bandera que falle en su carga en lugar de desplegar el icono de error. Se quitaron los bordes de los contenedores envolventes `span` para que desaparezcan sin dejar marcos vacíos. En la lista de candidatos a la Bota de Oro, se eliminó la burbuja redonda de las iniciales por completo, presentando una lista compacta y alineada directamente por nombre.

### [ERR-004] Ventanas Modales Congeladas con Spinner en Móviles (Bloqueo de Conexiones por SSE)
* **Fase/Hora**: Fase de Despliegue / Integración - 02-Jun-2026 19:10
* **Error Encontrado**:
  ```text
  Al abrir los detalles de un equipo o un jugador desde el móvil (tanto en producción directa como embebido en el iframe), la ventana modal se queda colgada permanentemente mostrando "Cargando plantel... Obteniendo datos de la API..." y el spinner dando vueltas, mientras que en desktop funciona perfectamente.
  ```
* **Causa Raíz**:
  El stream de datos en tiempo real SSE (Server-Sent Events) mantiene un socket HTTP abierto de manera indefinida. Debido a las limitaciones de los protocolos HTTP/1.1 en navegadores de dispositivos móviles y WebView (como los navegadores dentro de redes sociales o Chrome Móvil), existe un límite estricto y muy bajo de conexiones simultáneas por dominio (usualmente 2 a 4). La conexión SSE consume una de estas vías de manera permanente. Al realizar peticiones `fetch()` adicionales para cargar plantillas o perfiles en el modal, estas se encolan indefinidamente en el navegador del móvil esperando a que se libere el socket ocupado por el SSE, congelando la interactividad del minisite.
* **Solución Aplicada**:
  Se optimizó la inicialización del stream SSE en `index.html`. Ahora se detecta mediante `navigator.userAgent` y ancho de pantalla si el cliente está accediendo desde un dispositivo móvil. En caso afirmativo, la conexión SSE se omite por completo, liberando la cola de red y permitiendo que los `fetch()` del modal se resuelvan instantáneamente. Además, se envolvieron las funciones `showTeamDetails` and `showPlayerDetails` en bloques `try/catch` globales con renderizado UX alternativo de error y botón de "Reintentar" para evitar que cualquier otra falla futura deje la interfaz en un estado colgado sin respuesta visual.

### [ERR-005] Recorte de Contenido en el Iframe (Altura Fija Insuficiente en Elementor)
* **Fase/Hora**: Fase de Integración en Elementor - 02-Jun-2026 21:21
* **Error Encontrado**:
  ```text
  Al integrar el minisite mediante un iframe en Elementor con una altura fija (ej. 950px), el contenido de los partidos, grillas y modales se corta verticalmente en móviles y en ciertas resoluciones de escritorio, impidiendo al usuario ver la totalidad de los datos y navegar fluidamente debido a la directiva scrolling="no".
  ```
* **Causa Raíz**:
  El minisite tiene una altura variable que cambia dinámicamente según la cantidad de partidos cargados, el filtro seleccionado (Todos, En Vivo, Programados) y si se abre o cierra la vista detallada de un encuentro. Configurar una altura estática en la etiqueta iframe de Elementor provoca recortes inevitables cuando el contenido del minisite supera esa altura fija.
* **Solución Aplicada**:
  Se implementó un mecanismo de comunicación entre ventanas usando `window.parent.postMessage()`. Dentro del minisite (`index.html`), se programó el método `sendHeight()` que calcula la altura real de scroll (`document.body.scrollHeight`) y la despacha al padre tras cada renderizado de datos, carga de modales o cambio de vistas. En la guía de integración (`guia_despliegue.md`), se actualizó el widget HTML de Elementor agregando un script receptor `window.addEventListener('message', ...)` que reacciona a este mensaje y ajusta dinámicamente y con suavidad la propiedad `style.height` del iframe a la medida exacta de su contenido.

### [ERR-006] Nombres de Selecciones en Inglés y Banderas Faltantes (Consistencia de API)
* **Fase/Hora**: Fase de Pruebas con Datos Reales - 03-Jun-2026 13:27
* **Error Encontrado**:
  ```text
  Algunos nombres de países que devuelve la API real de Zafronix aparecen en inglés en el fixture de la web (por ejemplo, "Switzerland", "Morocco", "Saudi Arabia"), y en consecuencia sus banderas aparecen rotas o ausentes (con un icono por defecto) debido a la falta de mapeos correctos en el diccionario de metadatos del cliente.
  ```
* **Causa Raíz**:
  El diccionario `teamMetaData` era limitado (solo cubría las 20 selecciones del mock inicial) y la búsqueda dependía de una coincidencia estricta y exacta de cadenas de texto en JavaScript. Si la API devolvía un nombre en minúsculas, con espacios adicionales, o variaciones de tildes (como "mexico" o "MÉXICO"), el mapeo de metadatos fallaba de inmediato, dejando el nombre en inglés e impidiendo la asignación del código de bandera ISO.
* **Solución Aplicada**:
  1. Se expandió drásticamente el diccionario `teamMetaData` en `index.html` para incluir **100 mapeos de claves** correspondientes a todas las selecciones nacionales clasificadas, participantes y potenciales del Mundial 2026 de la FIFA (Concacaf, UEFA, Conmebol, CAF, AFC y OFC).
  2. Se optimizó la función `getTeamMeta` en JavaScript: si la clave exacta no coincide, el script realiza una búsqueda insensible a mayúsculas/minúsculas y tildes mediante normalización unicode (`normalize("NFD")`) y remoción de diacríticos (`replace(/[\u0300-\u036f]/g, "")`), asegurando la traducción y bandera correctas para cualquier variación del nombre devuelta por la API.



