---
capa: fuente
tipo: plan
estado: cerrado
fecha: 2026-08-11
areas: [proceso]
fuente: docs/superpowers/plans/2026-08-11-vocabulario-estados-cascada.md
resumen: Cola de decisiones: decisiones/vocabulario-estados-cascada.md (D-VOC-1 … D-VOC-4)
---

# Plan — unificar los vocabularios de estado de la cascada

Spec: [2026-08-11-vocabulario-estados-cascada-design.md](../specs/2026-08-11-vocabulario-estados-cascada-design.md)
Cola de decisiones: `decisiones/vocabulario-estados-cascada.md` (D-VOC-1 … D-VOC-4)

## Alcance de esta pasada

El censo dejó **35 términos** para el mismo ciclo. De esos, 26 son términos aprobados en el
contrato y **no se tocan sin criterio del usuario** (D-VOC-1 a D-VOC-4, encoladas). Lo que sí se
ejecuta ahora es la única resta que no elige vocabulario: **cerrar la desviación entre el código y
el contrato en Programación Intermedia**, que hoy produce seis variantes duplicadas y dos
contradicciones dentro de la misma pantalla.

**Recuento objetivo de esta pasada: 35 → 29.**

## Por qué esto no es una decisión encolada

- El nombre ya está decidido: lo declara `docs/design-system/state-semantics.json`, que según
  `AGENTS.md` es contrato y gana sobre el módulo. El código no lo está proyectando.
- No altera lo que mide ninguna prueba: `ops-state-contract.test.mjs` une código y contrato **por
  `key`, no por etiqueta**, y su propio comentario documenta la desviación como tolerada.
- No toca datos guardados: `stateLabels` es presentación pura; el estado viaja por `key`.
- No cambia lo que la obra tiene aprendido: en los dos casos con contradicción visible
  (`Inicio Vencido`, `Ejecución Pendiente`) la leyenda de la propia pantalla **ya muestra la forma
  del contrato**; lo que cambia es el chip de la fila, que hoy la contradice.

## Tareas

### T1 — Proyectar las etiquetas del contrato en Intermedia

`public/js/modules/programacion_intermedia/hot.js`, tabla `var stateLabels` (línea ~497). Seis
entradas pasan a la cadena del contrato:

| `key` | Antes (código) | Después (contrato) |
|---|---|---|
| `blocked-overdue` | Inicio Vencido | Inicio vencido |
| `alert-1-week` | Alistamiento urgente | Alistamiento Urgente |
| `alert-2-3-weeks` | Alistamiento en riesgo | Alistamiento en Riesgo |
| `alert-4-6-weeks` | Alistamiento pendiente | Alistamiento Pendiente |
| `execution-blocked` | Ejecución Pendiente | En Ejecución Pendiente |
| `liberated-control` | Listo para comprometer | Listo para Comprometer |

`neutral` (`Control`) y `header` (`Capítulo`) **no se tocan**: son D-VOC-4.

### T2 — Alinear la leyenda de la vista

`views/programacion-intermedia/programacion_intermedia.view.php`. Comprobar cada `pdc-legend-item`
contra el contrato y corregir las que difieran (medido: `Inicio Vencido` en la línea 71). No se
añaden ni se quitan ítems de la leyenda: eso cambiaría lo que se puede filtrar.

### T3 — Guard que impide la reincidencia

Extender `tests/design-system/ops-state-contract.test.mjs` para que, además de unir por `key`,
compare **también la etiqueta** de `stateLabels` con el `label` del contrato, y retirar el
comentario que documenta la desviación tolerada.

Entrega obligatoria: **una mutación que lo pone rojo, ejecutada**. Se revierte una etiqueta, se
corre el test, se enseña el fallo, se restaura.

> Este guard endurece un test existente. Endurecer no es reescribir lo que mide: el test ya
> verifica que el módulo proyecte el contrato, y esto cierra el hueco que su propio comentario
> declara. Si en la revisión se considera que sí altera lo que mide, T3 se retira y T1–T2 quedan
> igual de válidas.

### T4 — Verificación

1. `npm run test:design-system:static`
2. `docker compose exec app vendor/bin/phpstan analyse src admin/src --memory-limit=1G`
3. Pruebas del área tocadas por la leyenda de Intermedia (Playwright de Intermedia si existe).
4. Antes/después a 1180×820 dark de `/programacion-intermedia`, sesión por
   `http://localhost:8081/dev/entrar?u=test.A`, **contra un stack que sirva este worktree** — el
   `docker compose` del repo sirve el árbol principal (`memoria/trampas/aislar-stack-docker-por-worktree.md`).

### T5 — Cierre

Recuento final medido, entrega a la coordinadora con sha, visto, publicación en comando aparte.

## Lo que este plan deliberadamente NO hace

- No renombra ningún estado de Programa General, Actualizar ni Semanal.
- No toca `docs/design-system/state-semantics.json`.
- No toca la columna persistida `Estado`.
- No añade un mapa de equivalencias: sería el cuarto vocabulario.

---

## Estado verificado — cerrado

Verificado contra el código el 2026-08-25. **`estado: cerrado` es una afirmación deliberada**, no el valor por defecto del backfill.

**Evidencia:** programacion_intermedia/hot.js:734-744 (stateLabels) tiene las seis cadenas del contrato, ya no las variantes viejas

Criterio y método: [[docs/superpowers/plans/2026-08-25-estado-real-de-planes-y-specs]].
