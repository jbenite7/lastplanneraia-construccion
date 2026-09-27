---
capa: fuente
tipo: spec
estado: abierto
id: S05-DEUDA
fecha: 2026-09-26
superficie: programa-general
rutas: ["/programa-general"]
depende_de: [S05]
version: 1.0
aprobado_por: felipe
aprobado_el: 2026-09-26
sello: "ninguno — aprobada en el chat de Claude; /aprobar no está instalado en esa sesión"
areas: [lps, design-system, qa, proceso]
fuente: "cierre de la ronda 1.2 de S05 (PR #61, merge d6995213), informe docs/superpowers/plans/2026-09-24-s05-ronda-1-2-informe.md y auditoría de Claude, 2026-09-26"
resumen: "Tarea corta de limpieza tras la ronda 1.2 de S05: cinco deudas del cierre, a ejecutar por Codex después del reinicio de su uso el 2026-09-30."
---

# S05 — Deuda del cierre de la ronda 1.2

> **Estado:** **aprobada por Felipe en el chat el 2026-09-26**, sin sello (`/aprobar` no está
> instalado en la sesión donde se escribió). Las cinco deudas van juntas en una tarea corta que
> ejecuta Codex después del reinicio semanal de su uso (2026-09-30). Sigue el plan (paso 03).

## Por qué existe

La ronda 1.2 de S05 entró a `main` el 2026-09-26 (PR #61, `d6995213`) con las 13 variables `G_*`
en verde en ambos temas. La auditoría del cierre dejó cinco pendientes que no bloqueaban esa
condición, pero que no deben perderse.

## Las cinco deudas

1. **Prueba del tinte crítico de «Atrasada» sin valor fijo.** `tests/browser/design-system-body-canvas-dark.mjs`
   compara hoy el color del chip React con lo que resuelva `--ds-state-tint-red` en ese momento. La
   versión anterior fijaba el valor literal en oscuro (`#431414`) para detectar que una regla
   perdiera su condición de tema. Si el token mismo quedara con el valor claro en oscuro, la prueba
   actual no lo vería. **Arreglo:** añadir la aserción del valor esperado en oscuro
   (`rgb(67, 20, 20)`), sin quitar la comparación actual.
2. **Íconos inactivos del riel poco visibles en tema claro.** En las capturas finales de la ronda
   1.2, los íconos no activos del riel React apenas se distinguen sobre el verde. **Arreglo:**
   comparar primero con el riel del legado en claro; si el legado se ve igual, es herencia del
   design system y va a su propio frente; si no, igualar al legado. Medir contraste (3:1 para íconos
   no textuales, WCAG 1.4.11).
3. **Semanas negativas en la vista de 13 columnas.** La columna «Sem. ini.» muestra valores como
   «Sem -9» para actividades que empiezan antes de la semana 1 del proyecto. **Decidido por Felipe
   (2026-09-26):** se muestra «Antes de S1», con la fecha real de inicio en el título (al pasar el
   cursor). No se inventa un número de semana. El CSV conserva el número crudo (por ejemplo `-9`),
   porque es un archivo para análisis (Felipe, 2026-09-26).
4. **Áreas inválidas del frente TNP en la wiki.** `docs/superpowers/specs/2026-09-24-calificacion-tnp-semana-confirmada-design.md`
   y `docs/superpowers/plans/2026-09-24-calificacion-tnp-semana-confirmada.md` declaran áreas fuera
   de la lista cerrada (`programacion_semanal`, `tnp`, `backend`, `frontend`). **Arreglo:** usar
   áreas de `scripts/wiki-esquema.mjs` (`lps`, `datos`, `qa`, …) para que `npm run test:wiki` quede
   en verde en esa parte.
5. **Pase de veracidad de la wiki vencido.** `npm run test:wiki` avisa 42 commits de código desde el
   último pase (2026-09-21), por encima del umbral de 40. **Arreglo:** el pase de veracidad descrito
   en `docs/wiki-operacion.md`, verificando contra el código las páginas de las áreas que cambió la
   ronda 1.2 (lps, design-system, qa, arquitectura del shell).

## Hallazgos de proceso para la wiki (ingest)

Van a `memoria/` en la misma tarea, como `ingest`:

- Las pruebas del grupo del laboratorio (`G_LABORATORY_GATES`) no corrieron en local durante el
  sprint porque nadie las pidió; la primera corrida del CI las encontró en rojo. La verificación
  local de un cambio de shell o de módulo migrado debe incluir el grupo completo de CI de ambos
  temas.
- Las capturas de referencia de Linux no se regeneran en local: salen del artefacto de una corrida
  de CI fallida (D-GAC-4). Planearlo desde el principio evita una vuelta extra.
- La medición de interacción del presupuesto de runtime contaba tiempo de Playwright; ahora mide
  del `pointerdown` real al pintado. Un número que baja de ~260 ms a ~30 ms al cambiar el método
  debe documentarse con el porqué, como se hizo.
- La app de Codex no acepta mensajes sin control de pantalla, y a varias sesiones compartiendo la
  app se les cruzan las ventanas. Para supervisión, la transcripción de `~/.codex/sessions` es la
  fuente fiable.

## Condición de hecho propuesta

`npm run test:wiki` en `RC=0`, las pruebas tocadas en verde en local en ambos temas y, si la tarea
toca código, las 13 variables `G_*` en verde en ambos temas en el PR.

## Decisiones abiertas

- Fecha de ejecución: no antes del 2026-09-30.
