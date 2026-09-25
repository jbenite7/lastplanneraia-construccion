---
fecha: 2026-09-25
sprint: S05 ronda 1.2
ejecutor: codex
rama: codex/s05-ronda-1-2
estado: verificacion-final
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
| 9 · Goldens | Comparaciones candidatas preparadas; sustitución de referencias bloqueada por el visto de Felipe. | Sin commit de referencias. |
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

| Comando o gate | Resultado |
| --- | --- |
| `npm --prefix frontend run typecheck` | RC 0 |
| `npm --prefix frontend run test` | RC 0 · 73 archivos, 941 pruebas |
| `npm run test:design-system:static` | RC 0 · ocho secciones |
| `npm --prefix frontend run build` | RC 0 |
| `npm run css:minify:check` | RC 0 |
| `docker compose exec -T app php scripts/run-php-tests.php --nivel=http` en stack CI aislado | RC 0 · 117 seleccionadas, 115 aprobadas, 2 omisiones propias; PHPUnit 299 pruebas, 752 aserciones |
| `docker compose exec -T app vendor/bin/phpstan analyse src admin/src --memory-limit=1G` en stack CI aislado | RC 0 · 221 rutas, cero errores |
| Playwright PG, shell, visualizador y runtime a11y | RC 0 · 32/32 |
| `npm run test:a11y:pilot` | RC 0 · 2/2 |
| `npm run test:hue:pilot` | RC 0 · 1/1 |
| E2E `pg-interactions.spec.mjs` en stack CI aislado | RC 0 · 4/4, restauración verificada |
| `full-app-flow.spec.mjs` en stack CI aislado | RC 0 · 30/30; cada escenario emitió recibo de restauración de base y archivos |
| `npm run test:runtime-budget:measure` y `npm run test:runtime-budget:check` | RC 0 ambos · tres muestras ligadas a `c68330f6`; CSS gzip 128963 B, JS gzip 134709 B, solicitudes duplicadas 0, flashes 0, inicialización 320,4 ms, interacción 135,5 ms; baseline 0.5.0 → medición 1.1.0 |
| `programa-general.visual.mjs` contra referencias previas | RC 1 esperado · 78187 px distintos a 1180×820 y 86953 px a 1440×900; referencia nueva sin aprobación |

La suite PHP y los E2E de mutación se ejecutaron sobre base CI aislada. No se alteraron datos de la base de desarrollo compartida en esta reanudación.

## Rutas React y capturas

Se recorrieron `/programa-general` y `/app/programa-general`, en claro y oscuro y con riel colapsado y expandido: **8/8 combinaciones** a 1180×820 sin desbordamiento horizontal del documento, con el chip de semana visible. Son las rutas que montan `AppShell` en `frontend/src/shell/rutas.tsx`; las demás rutas de ese archivo son acceso o selección de proyecto.

Las catorce capturas finales de `docs/superpowers/evidence/s05-ronda-1-2/` cubren, en ambos temas: ocho columnas, trece columnas, última fila, riel colapsado, riel expandido, menú de semana y Drawer. Se regeneraron tras el último cambio de código `c68330f6`; las diferencias con la tanda anterior son de 6 a 457 píxeles por imagen.

## Revisión y pendientes

- Revisión independiente funcional: se corrigieron el orden del hook y contraste del riel, recargas obsoletas, reconciliación de avisos, leyenda y conteos del lote. El último veredicto quedó sin hallazgos accionables.
- Revisión adicional del diff de los dos ajustes finales de prueba: «sin hallazgos».
- Revisión adicional del selector de semana para runtime y de la deduplicación CSS: «sin hallazgos».
- Revisión independiente de seguridad: se amplió la prueba a la negación real del lote y a restauración en `finally`; último veredicto sin hallazgos accionables.
- **BLOCKED · tarea 9:** Felipe rechazó las capturas iniciales. Las seis imágenes de referencia, candidata y diferencia en dos tamaños están en `docs/superpowers/evidence/s05-ronda-1-2/goldens-candidatas/`, regeneradas desde `c68330f6`. Se solicitó un nuevo visto sobre las candidatas posteriores a R1.2-5. Hasta obtenerlo, no se modifican los goldens ni se considera verde el gate visual.
- No se hizo push, merge a `main` ni despliegue, según el alcance aprobado del sprint.

## Condición de hecho para un PR futuro

Antes de correr CI, el cuerpo del PR deberá declarar esta condición: **todas las variables `G_*` del paso «Summarize gate results» deben estar en verde en ambos temas**, además de las capturas de 1180×820 en claro y oscuro. El PR no se abrió en este sprint. El gate visual local está pendiente del visto de Felipe sobre los goldens.

## Costo

El costo real de esta sesión no está disponible en el arnés; no se estima.
