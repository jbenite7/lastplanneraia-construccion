---
capa: fuente
tipo: reporte
estado: vigente
fecha: 2026-09-28
areas: [lps, design-system]
fuente: docs/superpowers/plans/2026-09-28-s05-rediseno-mockup-informe.md
resumen: "Mockup verificable del rediseño de Programa General S05 con shell real, 60 actividades ficticias, ambos temas y 32 capturas."
---

# Informe del mockup S05 · Programa General

## Pieza entregada

`public/mockups/s05-rediseno-2026-09-28.html` es una pantalla estática de revisión visual para el proyecto ficticio **Edificio Mirador del Río**. Contiene cuatro capítulos y 60 actividades, en orden de cronograma. Los nombres describen trabajos de obra; aparecen ocho estados, fechas vencidas, avance real y teórico y Δ positivo y negativo. No consume datos reales ni cambia el módulo productivo.

El mockup incluye la barra lateral del shell real, con sus clases, marca, iconografía y tokens. «Programa General» es el ítem activo; el tema claro muestra el riel verde. La barra mide 15 rem abierta y 4 rem plegada. El cambio de proyecto está en el menú de cuenta y el selector de semana queda solo en la cabecera. A 390 px, el botón del shell abre la barra sobre el contenido con velo; cerrada, permanece oculta e inerte.

La cabecera ocupa 56 px y reúne semana, búsqueda con atajo `/`, Resumen/Detalle, Actualizar ejecución con hora del último lote, Exportar y acciones secundarias. La franja de señales agrupa Actuar, Seguir y Contexto, conserva rótulos completos a 1180 px con la barra abierta y permite operar los chips con un punto de Tab y flechas. La leyenda `?` abre un popover no modal. La tabla tiene capítulos colapsables, lectura con flechas/Enter, estado junto al nombre, bandera en columna independiente, avance comparado y fin marcado cuando vence. Las columnas opcionales de Detalle son las únicas que se agregan al cambiar de vista; no aparece una columna completamente vacía.

El cajón inicia **cerrado** y se superpone a la tabla al elegir una actividad. Muestra campos editables de avance real, inicio, fin y responsable, avance teórico de solo lectura y Guardar/Cancelar, siguiendo los campos pertinentes de `ProgramaDrawer.tsx` en `origin/main`. Guardar actualiza el estado local del mockup; no persiste al recargar. Recursos aparece solo en actividades que tienen dato. No hay campo de observación. En móvil se monta la vista de tarjetas de tres líneas, cada una con bandera visible cuando aplica, nombre, chip de estado, avance, Δ y fin; la tabla se desmonta del DOM. Los filtros están en hoja inferior.

## Decisiones aplicadas

- **Felipe, 2026-09-28:** “Fuera de ventana” conserva su lugar cronológico y se atenúa por texto/contenido, sin bandera ni tinte. Su chip de estado mantiene el matiz semántico teal. El chip neutro de Contexto lo oculta con un clic.
- **Felipe, 2026-09-28:** el modo proyector queda para una ronda posterior.
- **Claude (sesión que planea), auditoría de las 10:50 del 2026-09-28:** bandera en columna propia; tabla de ancho completo; tintes diferenciados por gravedad; cajón editable basado en campos reales, cerrado al cargar.
- **Claude (sesión que planea), auditoría de las 10:56:** los tres tintes mezclan al 45 % `--ds-active-state-tint-red/orange/amber` con `--ds-active-surface` en ambos temas. Las capturas finales muestran rojo, naranja y ámbar distinguibles a simple vista en claro y oscuro; axe dio cero hallazgos en ambos temas.
- **Codex, decisión de implementación:** en el ancho restante con la barra abierta, la franja de señales se adapta al contenedor para mantener visibles sus rótulos; el riel verde claro usa el token de marca `--aia-green-light`.

## Medición y evidencia visual

| Tema | Barra | Inicio tabla | Actividades completas visibles | Alto fila | Ancho útil tabla | Overflow de tabla/página | Nombre + chip + avance en una línea | Estado completo visible |
|---|---|---:|---:|---:|---:|---|---|---|
| Claro | Plegada | y=93 px | 24 | 26 px | 1116 px | No / No | Sí | Sí; el nombre se acorta con elipsis |
| Claro | Abierta | y=93 px | 24 | 26 px | 940 px | No / No | Sí | Sí; el nombre se acorta con elipsis |
| Oscuro | Plegada | y=93 px | 24 | 26 px | 1116 px | No / No | Sí | Sí; el nombre se acorta con elipsis |
| Oscuro | Abierta | y=93 px | 24 | 26 px | 940 px | No / No | Sí | Sí; el nombre se acorta con elipsis |

La etiqueta más larga, «Con alerta de restricciones», queda completa en ambos estados de barra y temas. Se trunca parte del nombre de actividades en la columna estrecha, pero el chip de estado y el avance permanecen visibles en su línea; ningún estado queda oculto. Los rótulos de los filtros también quedan completos a 1180 px con la barra abierta.

A 390×844, `documentElement.scrollWidth - innerWidth = 0`; la barra cerrada queda fuera de vista y la abierta se superpone al contenido. El DOM monta 60 tarjetas y 0 tablas. Con el cajón móvil abierto, avance, inicio, fin, responsable y Guardar miden 44 px cada uno. Axe no encuentra hallazgos en las 32 combinaciones de vista, tema, dispositivo y barra; tampoco hubo errores de JavaScript. La tabla útil ocupa 1116 px plegada y 940 px abierta a 1180×820.

La revisión independiente detectó y se corrigieron cinco detalles del mockup: la tabla no tenía fila de entrada por teclado y podía perder el punto de Tab al filtrar o al colapsar su capítulo; los controles editables del cajón móvil no llegaban a 44 px; los chips positivos de Actuar carecían de relleno sólido; y las barras no seguían el matiz de su estado. El contrato ahora comprueba la entrada y el movimiento por flechas de las filas, la restauración del punto de Tab al ocultar la fila activa por filtro o al colapsar/expandir un capítulo, los rellenos de Actuar, los colores de las barras y los tamaños del cajón móvil. La revisión independiente anterior también encontró un segundo punto de Tab en `?`: ahora inicia fuera del orden de Tab y entra al ciclo por flechas; el roving mantiene un solo `tabindex="0"`. Una comprobación adicional abrió la actividad 001, guardó avance real de 47 %, observó 47 % y Δ +8 pp en su fila, y comprobó que al pasar a 390 px el DOM contiene 0 tablas y 60 tarjetas.

Las 32 capturas de página completa, copiadas desde `test-output/s05-rediseno-mockup/`, están en `docs/superpowers/plans/2026-09-28-s05-rediseno-capturas/`. Cubren claro/oscuro × 1180×820/390×844 × `base`/`cajon-abierto`/`contexto-oculto`/`franja-seleccion-foco` × barra `plegada`/`abierta`, con el patrón `<tema>-<ancho>-<vista>-barra-<estado>.png`. Se retiraron las ocho capturas anteriores. No hay hallazgos axe críticos, serios, moderados ni menores en esta matriz.

## Comprobaciones

El chequeo Playwright de contrato vive en `.superpowers/sdd/2026-09-28-s05-rediseno-encargo-mockup/contract.mjs` (ignorado). **RED:** la ruta ausente devolvió 404/500 por el front controller frente al 200 esperado; Claude (sesión que planea) indicó que esto no afecta archivos estáticos y pidió no investigarlo ni tocar `.env`. **GREEN:** con el archivo estático respondió 200 y pasó 60 actividades, el tratamiento sin bandera de “Fuera de ventana”, su ocultamiento mediante Contexto, la navegación de teclado, la barra real del shell en 15 rem/4 rem, el riel verde, filtros sin truncar, los chips sólidos, el matiz de las barras, el cambio de tema y los objetivos editables móviles de 44 px.

El contenedor aislado `codex-mockup` montó este worktree en `/var/www/html` y sirvió en 8094. Su Apache efímero usa `DocumentRoot /var/www/html/public` para los enlaces `/css/...`; no se cambió el montaje ni el estado del `app` compartido. No se tocó `.env`. Docker rechazó crear un segundo contenedor porque su almacén estaba en solo lectura; para el gate estático se usó un adaptador temporal local que redirigió las llamadas PHP de `docker compose exec app` al contenedor propio `codex-mockup`. El primer intento sin ese adaptador ejecutó solo llamadas PHP de lectura contra el `app` compartido, que monta otro worktree, falló por rutas ausentes (RC=1) y no cambió datos ni configuración.

`npm run test:design-system:static` RC=0 (8/8 secciones PASS, incluidas las pruebas de Node). `npm run test:wiki` RC=0 (98/98 pruebas y lint estricto sin hallazgos). El contrato Playwright y la captura de las 32 combinaciones dieron RC=0; axe reportó cero hallazgos en cada combinación.

## Auto-revisión

Se revisó el HTML final contra el encargo: el único archivo de código modificado es `public/mockups/s05-rediseno-2026-09-28.html`; también se actualizaron el informe y la matriz de capturas. No se tocó código de producción, el plan, la spec ni otros mockups. Las hojas requeridas y la hoja de primitivas del selector de proyecto están enlazadas; el CSS local usa tokens existentes y no contiene hex, `!important` ni estilos inline. Los 60 nombres miden 46–56 caracteres. La bandera no tapa el código, el cajón comienza cerrado, el chip teal de “Fuera de ventana” conserva contraste y el filtro Contexto elimina esas filas sin dejar capítulos vacíos. Las 32 capturas corresponden a la última versión del HTML.

## Asuntos no resueltos

La pieza es un mockup: las opciones de semana y el guardado no se conectan al servidor, y no hay persistencia tras recarga. Con la barra abierta y plegada se ven al menos 24 actividades, sin overflow ni estado oculto. Parte del nombre puede abreviarse con elipsis; el estado y el avance permanecen completos y visibles. El modo proyector queda fuera por decisión de Felipe. No se cambiaron baselines ni código de producción.

Claude (software, 11:41 del 2026-09-28) cerró el caso de colapso al verificar este arreglo y acotó la revisión de teclado del mockup: cualquier otra arista de `roving tabindex` que aparezca durante la implementación se anotará como requisito para ese plan y no bloqueará el visto de diseño.
