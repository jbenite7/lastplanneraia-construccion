---
capa: fuente
tipo: reporte
estado: vigente
fecha: 2026-09-28
areas: [lps, design-system]
fuente: docs/superpowers/plans/2026-09-28-s05-rediseno-mockup-informe.md
resumen: "Mockup verificable del rediseño de Programa General S05 con 60 actividades ficticias, ambos temas y ocho capturas."
---

# Informe del mockup S05 · Programa General

## Pieza entregada

`public/mockups/s05-rediseno-2026-09-28.html` es una pantalla estática de revisión visual para el proyecto ficticio **Edificio Mirador del Río**. Contiene cuatro capítulos y 60 actividades, en orden de cronograma. Los nombres describen trabajos de obra; aparecen ocho estados, fechas vencidas, avance real y teórico y Δ positivo y negativo. No consume datos reales ni cambia el módulo productivo.

La cabecera ocupa 56 px y reúne semana, búsqueda con atajo `/`, Resumen/Detalle, Actualizar ejecución con hora del último lote, Exportar y acciones secundarias. La franja de señales ocupa 36 px, agrupa Actuar, Seguir y Contexto, y permite operar los chips con un punto de Tab y flechas. La leyenda `?` abre un popover no modal. La tabla tiene capítulos colapsables, lectura con flechas/Enter, estado junto al nombre, bandera en columna independiente, avance comparado y fin marcado cuando vence. Las columnas opcionales de Detalle son las únicas que se agregan al cambiar de vista; no aparece una columna completamente vacía.

El cajón inicia **cerrado** y se superpone a la tabla al elegir una actividad. Muestra campos editables de avance real, inicio, fin y responsable, avance teórico de solo lectura y Guardar/Cancelar, siguiendo los campos pertinentes de `ProgramaDrawer.tsx` en `origin/main`. Guardar actualiza el estado local del mockup; no persiste al recargar. Recursos aparece solo en actividades que tienen dato. No hay campo de observación. En móvil se monta la vista de tarjetas de tres líneas, cada una con bandera visible cuando aplica, nombre, chip de estado, avance, Δ y fin; la tabla se desmonta del DOM. Los filtros están en hoja inferior.

## Decisiones aplicadas

- **Felipe, 2026-09-28:** “Fuera de ventana” conserva su lugar cronológico y se atenúa por texto/contenido, sin bandera ni tinte. Su chip de estado mantiene el matiz semántico teal. El chip neutro de Contexto lo oculta con un clic.
- **Felipe, 2026-09-28:** el modo proyector queda para una ronda posterior.
- **Claude (sesión que planea), auditoría de las 10:50 del 2026-09-28:** bandera en columna propia; tabla de ancho completo; tintes diferenciados por gravedad; cajón editable basado en campos reales, cerrado al cargar.
- **Claude (sesión que planea), auditoría de las 10:56:** los tres tintes mezclan al 45 % `--ds-active-state-tint-red/orange/amber` con `--ds-active-surface` en ambos temas. Las capturas finales muestran rojo, naranja y ámbar distinguibles a simple vista en claro y oscuro; axe dio cero hallazgos en ambos temas.

## Medición y evidencia visual

| Escenario | Inicio tabla | Actividades completas visibles | Alto fila | Actividades/tarjetas montadas | Axe |
|---|---:|---:|---:|---|---|
| Claro, 1180×820 | y=92 px | 24 | 26 px | 60 / 0 | 0 hallazgos |
| Oscuro, 1180×820 | y=92 px | 24 | 26 px | 60 / 0 | 0 hallazgos |
| Claro, 390×844 | — | — | — | 0 / 60 | 0 hallazgos |
| Oscuro, 390×844 | — | — | — | 0 / 60 | 0 hallazgos |

A 390×844, `documentElement.scrollWidth - innerWidth = 0`; el menor control visible mide 44 px en su dimensión mínima y no hay objetivos menores de 44×44. No hubo errores de JavaScript en los cuatro recorridos. La tabla ocupa 1180 px de ancho sin cajón. La revisión independiente detectó y se corrigió un segundo punto de Tab en `?`: ahora inicia fuera del orden de Tab y entra al ciclo por flechas; el roving mantiene un solo `tabindex="0"`. Una comprobación adicional abrió la actividad 001, guardó avance real de 47 %, observó 47 % y Δ +8 pp en su fila, y comprobó que al pasar a 390 px el DOM contiene 0 tablas y 60 tarjetas.

Las ocho capturas de página completa, copiadas inmediatamente desde `test-output/s05-rediseno-mockup/`, están en `docs/superpowers/plans/2026-09-28-s05-rediseno-capturas/`:

1. `light-1180-base.png`
2. `dark-1180-base.png`
3. `light-390-base.png`
4. `dark-390-base.png`
5. `light-1180-drawer.png`
6. `dark-1180-drawer.png`
7. `light-1180-signal-selected-focused.png`
8. `light-1180-context-hidden.png`

## Comprobaciones

El chequeo Playwright de contrato se escribió primero en `.superpowers/sdd/2026-09-28-s05-rediseno-encargo-mockup/contract.mjs` (ignorado). **RED:** la ruta ausente devolvió 404/500 por el front controller frente al 200 esperado; Claude (sesión que planea) indicó que esto no afecta archivos estáticos y pidió no investigarlo ni tocar `.env`. **GREEN:** con el archivo estático, respondió 200 y pasó la presencia de 60 actividades, el tratamiento sin bandera de “Fuera de ventana”, su ocultamiento mediante Contexto y el cambio de tema.

El contenedor aislado `codex-mockup` montó este worktree en `/var/www/html`. Su Apache efímero se configuró con `DocumentRoot /var/www/html/public` para servir los enlaces `/css/...`; no se tocó `app` compartido. No se instalaron dependencias. El primer `npm run test:design-system:static` dio RC=1 únicamente porque el worktree aislado carecía de `node_modules` y diez pruebas no encontraban `@axe-core/playwright`; se enlazó temporalmente el `node_modules` local existente, ignorado por Git, y el mismo comando dio RC=0 (8/8 secciones PASS, 0 fallos). `npm run test:wiki` dio RC=0 (98/98 pruebas y lint estricto sin hallazgos). Tras la última corrección de tintes se repitieron ambos comandos, por separado: `test:design-system:static` RC=0 con 8/8 secciones PASS y `test:wiki` RC=0 con 98/98 pruebas y lint estricto sin hallazgos.

## Auto-revisión

Se revisó el HTML final contra el encargo: solo se añadió el archivo nuevo en `public/mockups/`, sin tocar producción, plan, spec ni mockups previos. Las hojas requeridas están enlazadas; el CSS local usa tokens existentes y no contiene hex, `!important` ni estilos inline. Los 60 nombres miden 46–56 caracteres. La bandera no tapa el código, el cajón comienza cerrado, el chip teal de “Fuera de ventana” conserva contraste y el filtro Contexto elimina esas filas sin dejar capítulos vacíos. Las ocho capturas corresponden a la última versión del HTML.

## Asuntos no resueltos

La pieza es un mockup: las opciones de semana y el guardado no se conectan al servidor, y no hay persistencia tras recarga. El modo proyector queda fuera por decisión de Felipe. No se cambiaron baselines ni código de producción.
