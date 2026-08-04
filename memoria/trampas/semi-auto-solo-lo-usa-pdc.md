---
tipo: trampa
estado: vigente
fecha: 2026-08-04
areas: [pdc, arquitectura, lps]
fuente: public/index.php, src/Controllers/Api/SemiAutoController.php, AGENTS.md
resumen: "AGENTS.md dice que Listado, Contratos y PDC comparten los contratos auto/*, pero las 13 rutas son todas /api/pdc/ y los 12 métodos *Listado del controlador no tienen ninguna ruta"
---
# El semi-automático lo usa solo el PDC, aunque el contrato diga tres

[[AGENTS]] `:23` afirma:

> «Listado de Actividades, Contratos y PDC comparten los contratos `auto/preview`, `auto/apply`,
> `auto/undo`, `auto/feedback` y `auto/metrics`. Reutiliza `public/js/modules/semi_auto_review.js`
> y los servicios existentes.»

**Medido el 2026-08-04, eso hoy no es cierto.** Tres comprobaciones independientes:

1. Las **13 rutas** `auto/*` de `public/index.php:256-268` son **todas** `/api/pdc/…` y apuntan a
   los métodos `previewPdc`, `applyPdc`, `undoPdc`, `feedbackPdc`, `metricsPdc` y los de asistente
   y aprendizaje. Ninguna es de contratos ni de listado.
2. `src/Controllers/Api/SemiAutoController.php` tiene **12 métodos `*Listado`**
   (`previewListado`, `statusListado`, `applyListado`, `undoListado`, `feedbackListado`,
   `metricsListado`, los tres de asistente y los tres de aprendizaje) y **ninguna ruta los invoca**:
   `grep "Listado']" public/index.php` devuelve **0**.
3. `public/js/modules/semi_auto_review.js` lo carga **un solo archivo**:
   `views/pdc/pdc.view.php:601`.

## Por qué importa

No es una imprecisión de redacción. La instrucción de `AGENTS.md` es **operativa**: le dice a quien
trabaje que reutilice un flujo compartido en vez de crear uno paralelo. Quien la siga creyendo que
Listado ya está cableado buscará durante un rato un cableado que no existe; quien la contradiga sin
comprobar se salta un contrato.

Hay doce métodos de servicio escritos y esperando rutas. Eso admite dos lecturas y **la wiki no
elige por su cuenta**: o el contrato describe una intención que se quedó a medias, o el cableado de
Listado existió y se perdió. Registrado para decisión del usuario en
`docs/EXPERIMENTS.md`.

## Cómo no caer

Antes de citar ese punto de `AGENTS.md` como estado actual, corre:

```bash
grep -nE "'/api/[a-z-]+/auto/" public/index.php
grep -c "Listado'\]" public/index.php
```

Si el segundo sigue devolviendo `0`, el semi-automático sigue siendo solo del PDC.

Vecinas: [[comentario-de-token-afirma-uso-inexistente]] y
[[guard-valida-declaracion-contra-si-misma]] — el mismo patrón de una fuente que afirma más de lo
que el repositorio respalda. Mapas: [[pdc]] · [[arquitectura]].
