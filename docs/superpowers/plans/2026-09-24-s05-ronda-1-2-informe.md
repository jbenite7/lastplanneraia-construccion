---
capa: fuente
tipo: plan
areas: [proceso]
fuente: docs/superpowers/plans/2026-09-24-s05-ronda-1-2-informe.md
resumen: Informe de sprint S05 ronda 1.2
fecha: 2026-09-26
sprint: S05 ronda 1.2
ejecutor: codex
rama: codex/s05-ronda-1-2
estado: abierto
---

# Informe de sprint S05 ronda 1.2

## Partida

- Worktree aislado: `.claude/worktrees/s05-ronda-1-2`, creado desde `8ad3ca3f` e integrado con `origin/main` antes de implementar. `.env` disponible mediante enlace local.
- Se comprobó el montaje de `app` sobre este worktree para la validación viva. El cierre devuelve el montaje a la raíz compartida.
- La sesión del 24 de septiembre se interrumpió. No se conserva una transcripción verificable de los códigos de salida de las tres pruebas de línea de partida; este informe no les atribuye un resultado. La verificación final posterior sí se midió y consta abajo.
- El 25 de septiembre se verificó por lectura del API que Da Porto solo tenía las semanas 1 y 2, con la 2 actual. La semana 3 creada en la sesión anterior ya no estaba presente; no se hizo otra mutación en la base compartida.
- Se integró en esta rama la spec de R1.2-5 desde `origin/docs/s05-ronda-1-2` mediante `cb04e46e`. El rechazo previo de Felipe a las capturas de referencia se conservó.

## Implementación por tarea

| Tarea | Resultado | Commits principales |
| --- | --- | --- |
| 1 · Puerta de desarrollo | `p` vuelve a resolverse por clave real de proyecto; se retiró la posición numérica. | `fc8fd993` |
| 2 · Conteos | Los totales visibles cuentan actividades, sin filas de capítulo. | `39de5283` |
| 3 · Tokens | Programa General consume las capas de tema y tokens globales. | `ed0b0b60` |
| 4 · Tabla | Ocho y trece columnas dentro de 1180 px; identificadores, fechas y estados legibles. | `07524cf1`, `c1b03a9e`, `71bce5a4` |
| 5 · Scroll | Scroll vertical interno hasta la última actividad con encabezado fijo. | `f9b4c2a2` |
| 6–7 · Shell | Riel, iconos, contraste, estados y selector de semana en la barra de contexto, en ambos temas; se quitó una carga CSS duplicada. | `32b15bbb`, `e77540a2`, `c68330f6` |
| R1.2-5 · Toolbar | Leyenda, actualización de ejecución, recarga y BI conectados a permisos y API; se corrigieron carreras de carga y avisos obsoletos. | `a559d7f6`, `26bfed75`, `2c919385`, `5e3e42a1` |
| 8 · Pruebas | Persistencia React UI→API→BD, restauración, RBAC real, presupuesto de runtime y flujo móvil con el disparador real. | `d8459b2b`, `c9ebacfa`, `784f846f`, `a87c8824`, `f942d76e`, `f9391db6` |
| 9 · Goldens | Felipe aprobó el 2026-09-26 las candidatas tomadas desde `ad01da32`; Claude reemplazó las dos referencias oscuras de macOS y sus hashes en el manifiesto, por encargo de Felipe. Las referencias de Linux quedan pendientes del artefacto de CI. | Ver «Cierre de la tarea 9». |
| 10 · Evidencia | Catorce capturas finales en dos temas; revisión funcional y de seguridad independiente. | `b02bc36a`, `131e158a`, `073f8b17` |

## Decisiones de código

- La tabla usa `table-layout: fixed` y anchos de columnas en `rem`; «Actividad» ocupa el espacio restante y envuelve el texto. La caja de tabla usa `overflow-y: auto` y `overflow-x: hidden`, y el encabezado usa `position: sticky`. No se añadieron celdas ni instancias ficticias de Handsontable.
- El riel y el selector de semana usan el marcado y los estados del shell React compartido. Los iconos son SVG visibles y el contraste del ítem activo proviene del token canónico.
- La recarga cancela la petición anterior, incluida la carga inicial, y solo reconcilia la respuesta vigente. Mantiene datos y filtros visibles si falla. El lote de ejecución exige confirmación y usa el endpoint real con CSRF.
- La prueba de persistencia usa un stack CI aislado para Da Porto, JMC y PC; cada escenario restaura datos y archivos y compara sus huellas antes y después. La denegación de Viewer se comprueba contra las respuestas API 403 de guardado y lote.
- La corrida integral reveló que `maxWeek: 6` de JMC no existe en el fixture CI (el endpoint respondió `WEEK_NOT_FOUND` 404). El escenario ahora cambia a la semana 4, sembrada y distinta de la operativa 5; el caso enfocado pasó con restauración idéntica. No se cambió el límite del API ni el fixture SQL.
- La primera corrida de presupuesto llegó a la semana 2 de Da Porto, vacía deliberadamente en el fixture, y no encontró `row-activity`. La medición selecciona ahora la semana operativa 1 mediante `/context/week` con CSRF antes de iniciar la navegación cronometrada, sin alterar datos ni calentar previamente Programa General.
- La siguiente medición reveló dos solicitudes de `theme-claro.css`: el HTML React la enlazaba y `aia-design-system.css` ya la importaba. Se retiró el enlace redundante. La prueba de tema comprueba que el HTML no lo vuelva a añadir y el contrato estático conserva la importación canónica; la nueva medición tiene cero solicitudes duplicadas en las tres muestras, sin subir el umbral.
- `css-minify.mjs` resuelve la ruta real del propio archivo, de modo que el comando funcione en un checkout cuyo nombre contiene espacios (`7850aa0e`).

## Verificación local

Resultados de la corrección del 26 de septiembre sobre `ad01da321af32e619905bb70f6d5a32c6a50aff1`.

| Comando o gate | Resultado |
| --- | --- |
| `npm --prefix frontend run typecheck` | RC 0 |
| `npm --prefix frontend run test` | RC 0 · 73 archivos, 942 pruebas |
| `npm run test:design-system:static` | RC 0 · ocho secciones |
| `npm --prefix frontend run build` | RC 0 |
| `npm run css:minify:check` | RC 0 |
| `docker compose exec -T app php scripts/run-php-tests.php --nivel=http` en stack CI aislado | RC 0 · 117 seleccionadas, 115 aprobadas, 2 omisiones propias; PHPUnit 299 pruebas, 752 aserciones |
| `docker compose exec -T app vendor/bin/phpstan analyse src admin/src --memory-limit=1G` en stack CI aislado | RC 0 · 221 rutas, cero errores |
| Playwright PG, shell, visualizador y runtime a11y | RC 0 · 34/34; incluye ocho casos de layout y dos del punto Terminada |
| `npm run test:a11y:pilot` | RC 0 · 2/2 |
| `npm run test:hue:pilot` | RC 0 · 1/1 |
| E2E `pg-interactions.spec.mjs` en stack CI aislado | RC 0 · 4/4, restauración verificada |
| `full-app-flow.spec.mjs` en stack CI aislado | RC 0 · 30/30; treinta recibos con huellas de base y archivos idénticas antes/después |
| `npm run test:runtime-budget:measure` y comprobación con `design-system-runtime-budget.mjs check` | RC 0 ambos · tres muestras ligadas a `ad01da32`; CSS gzip 128965 B, JS gzip 135001 B, solicitudes duplicadas 0, flashes 0, inicialización 134,6 ms, interacción 242,6 ms; baseline 0.5.0 → medición 1.1.0 |
| `programa-general.visual.mjs` contra las referencias aprobadas (macOS, código de `ad01da32`) | RC 0 · 2/2 (corrida de Claude, 2026-09-26) |
| `npm run test:wiki` | RC 1 · sus pruebas unitarias pasaron; el lint estricto detecta 14 metadatos inválidos en cuatro documentos ajenos a esta ronda y en el propio plan sellado. El informe nuevo ya pasó su validación de frontmatter. No se alteraron esos contratos. |

La suite PHP y los E2E de mutación se ejecutaron sobre base CI aislada. No se alteraron datos de la base de desarrollo compartida en esta reanudación.

## Rutas React y capturas

Se recorrieron `/programa-general` y `/app/programa-general`, en claro y oscuro y con riel colapsado y expandido: **8/8 combinaciones** a 1180×820 sin desbordamiento horizontal del documento, con el chip de semana visible. Son las rutas que montan `AppShell` en `frontend/src/shell/rutas.tsx`; las demás rutas de ese archivo son acceso o selección de proyecto.

Las catorce capturas finales de `docs/superpowers/evidence/s05-ronda-1-2/` cubren, en ambos temas: ocho columnas, trece columnas, última fila, riel colapsado, riel expandido, menú de semana y Drawer. Se regeneraron tras el último cambio de código `ad01da321af32e619905bb70f6d5a32c6a50aff1`. Las dos rutas React volvieron a pasar sus ocho combinaciones, sin errores JavaScript.

## Corrección de la ronda 3 · 26 de septiembre

- Se añadió el glifo SVG exacto del legado para `user`, `contract`, `integration`, `tasks`, `sync` y `clipboard`. La prueba unitaria dio RED con el círculo de respaldo en Profesionales y GREEN con el glifo propio. La prueba de navegador también dio RED con el build anterior y GREEN 4/4: compara por destino la ruta, el nombre y cada elemento/atributo SVG contra el legado vivo, y rechaza el respaldo genérico.
- Las fechas tienen columnas de 5 rem y padding horizontal real de 5 px. Se redistribuyeron 24 px de columnas auxiliares para conservar Actividad con al menos 160 px en 13 columnas y riel abierto. La matriz de ocho casos verifica cero overflow, cero recortes, Actividad ≥280/160 px y separación entre textos de fecha vecinos ≥8 px; en 13 columnas mide Inicio→Sem→Fin, en 8 Inicio→Fin. La primera ampliación de fechas produjo RED por Actividad de 139 px; tras redistribuir, GREEN 8/8.
- Terminada usaba el token inexistente `--ds-text-muted`; ahora usa el neutral canónico `--ds-state-solid-neutral` en señal y Estado. Prueba unitaria RED/GREEN y prueba de navegador GREEN 2/2 con puntos de tamaño positivo y color opaco en ambos temas.
- Las candidatas se tomaron en una salida nueva tras el commit `ad01da32`. Se comprobaron los hashes de JS/CSS servidos contra los archivos del commit antes y después. `goldens-candidatas/manifest.json` registra SHA, fecha y huellas anteriores/nuevas: candidata 1180×820 `0edee1430380d645d5d82d1dee9da1327fedf2afe42ed5d397332f5c28ef40ad`; 1440×900 `3173fb85a137991dbb10377fc34930d6c81687e05a6e24655311c09567c5c111`. Ambas son distintas de la tanda rechazada.
- El mismo manifiesto enumera las catorce capturas vivas, con SHA-256, fecha de captura y `sourceRef`; sus archivos en el repo se compararon con las salidas originales de la captura antes de registrar la huella.
- La revisión independiente de código no encontró otros hallazgos. Su hallazgo sobre evidencia antigua quedó resuelto tras comprobar el manifiesto, assets y ambas candidatas, más 13 columnas: glifos propios, fechas separadas y puntos Terminada visibles. Veredicto final de esta corrección: sin hallazgos accionables; aprobación de referencias pendiente.
- Docker Desktop se recuperó mediante su comando de reinicio tras un error de arranque. Para las capturas vivas se montó temporalmente el `.env` local de solo lectura: el enlace absoluto del worktree no se resolvía dentro del contenedor. Se devolvió el montaje de `app` a la raíz compartida y se retiró ese bind temporal. Los E2E de mutación usaron exclusivamente la base CI aislada.

## Revisión y pendientes

- Revisión independiente funcional: se corrigieron el orden del hook y contraste del riel, recargas obsoletas, reconciliación de avisos, leyenda y conteos del lote. El último veredicto quedó sin hallazgos accionables.
- Revisión adicional del diff de los dos ajustes finales de prueba: «sin hallazgos».
- Revisión adicional del selector de semana para runtime y de la deduplicación CSS: «sin hallazgos».
- Revisión independiente de seguridad: se amplió la prueba a la negación real del lote y a restauración en `finally`; último veredicto sin hallazgos accionables.
- El lint de wiki estricto queda rojo por metadatos anteriores a este informe en planes y specs fuera del alcance. Cambiar el frontmatter del plan S05 sellado invalidaría su huella; se deja registrado para el cierre con Felipe.
- ~~BLOCKED · tarea 9~~ → cerrada en macOS el 2026-09-26: ver «Cierre de la tarea 9». Queda abierto el gemelo de Linux.
- No se hizo push, merge a `main` ni despliegue, según el alcance aprobado del sprint.

## Condición de hecho para un PR futuro

Antes de correr CI, el cuerpo del PR deberá declarar esta condición: **todas las variables `G_*` del paso «Summarize gate results» deben estar en verde en ambos temas**, además de las capturas de 1180×820 en claro y oscuro. El PR no se abrió en este sprint. El gate visual local está pendiente del visto de Felipe sobre los goldens.

## Costo

El costo real de esta sesión no está disponible en el arnés; no se estima.


## Cierre de la tarea 9 (2026-09-26)

- **Aprobación:** Felipe aprobó en el chat de Claude, el 2026-09-26, reemplazar las dos referencias oscuras (1180×820 y 1440×900) con las candidatas tomadas desde `ad01da321af32e619905bb70f6d5a32c6a50aff1` (manifiesto de candidatas: assets servidos iguales a los del commit).
- **Quién lo ejecutó:** Claude, por encargo explícito de Felipe (§2b), porque la app de Codex no aceptaba mensajes sin control de pantalla y a Codex le quedaba 5 % de uso.
- **Qué cambió:** `tests/browser/__screenshots__/programa-general.visual.mjs/programa-general-dark-{1180x820,1440x900}.png` (macOS) y sus `sha256` en `docs/design-system/manifests/programa-general.json` (`0edee143…` y `3173fb85…`, iguales a las candidatas aprobadas).
- **Verificación:** `npm run test:design-system:static` RC 0; `npx playwright test tests/browser/programa-general.visual.mjs` RC 0 · 2/2, contra `localhost:8081` sirviendo este worktree; después el contenedor volvió a la raíz del repo.
- **Pendiente — referencias de Linux (CI):** `goldenPlatforms.linux` sigue con las referencias anteriores. Por el procedimiento documentado (`docs/decisiones-pendientes.md`, D-GAC-4), se toman del artefacto que sube la corrida de CI en Linux al fallar el piloto visual; se copian a `…/linux/` y se actualiza su `sha256`. Requiere publicar la rama y correr el CI: paso 08, con Felipe.