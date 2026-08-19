---
capa: wiki
tipo: goal
estado: vigente
fecha: 2026-08-19
areas: [proceso]
tags: [dashboard]
fuente: los 56 `goals/<slug>/goal.md` leídos el 2026-08-19 contra `origin/main`, más `git ls-files` y el lint de la wiki
resumen: "Estado de los 56 goals del repo, leído de la sección `## Cierre` de cada goal.md: 34 cerrados, 12 abiertos y 10 que son plantilla sin objetivo redactado"
---
# Estado de los goals

**Corte del 2026-08-19**, releído de cada `goals/<slug>/goal.md` contra `origin/main`. El anterior
era del 2026-08-10 y describía 26 carpetas cuando ya había 56: nueve días y treinta goals de
desfase, en la página que existe justo para responder «¿dónde quedó X?».

La fuente sigue siendo `goals/<slug>/`; esta página solo lo resume. **La regla de lectura es una:
un goal está cerrado si su `goal.md` tiene una sección `## Cierre` con contenido** —salida de
comandos, no prosa—. No se deduce de que lleve días quieto ni de que su plan tenga casillas
marcadas: **las casillas de los planes no miden nada** —96 marcadas de 1.680, medido el 2026-08-19—.

Para las **fases de los cuatro programas y los pendientes vivos**, [[TASKS]], que es la fuente
única desde que la cola de pendientes migró a la raíz.

## El reparto

| | Cuántos | Qué significa |
|---|---|---|
| **Cerrados** | 34 | `## Cierre` con evidencia. Incluye 1 retirado y 1 descartado |
| **Abiertos** | 12 | objetivo redactado, sin cierre |
| **Plantilla** | 10 | `goal.md` es el andamiaje con el objetivo sin escribir |

## Abiertos o bloqueados

| Goal | Estado |
|---|---|
| [[goals/bi-control-tower-gemini/goal\|bi-control-tower-gemini]] | **Bloqueado por dependencia, no por olvido.** Su condición de hecho pide aprobar seis modos, tres del tema `linen`, retirado el 2026-07-25 (DS-030): nadie podía cumplirla. El usuario decidió esperar al tema claro nuevo (MO-F3) y aprobar los seis de verdad, en vez de recortar el alcance. Ver [[condicion-de-hecho-caduca-sin-aviso]] |
| [[goals/design-system-nucleo-gobernanza/goal\|design-system-nucleo-gobernanza]] | **No se cierra**, medido el 2026-08-10: de sus 15 gates solo 2 pasaban de verdad, 4 fallaban con evidencia, 8 no eran ejecutables y 1 era un recibo sin comando detrás. Los 14 artefactos de `docs/design-system/evidence/` eran stubs de dos claves. **Superado por el programa DS**: los 15 gates se reemplazan en DS-F3, no se arreglan. Ver [[gate-solo-cuenta-elementos-no-los-lee]] |
| [[goals/reapertura-movil-y-tema-claro/goal\|reapertura-movil-y-tema-claro]] | **Abierto**, 4 de 7 fases cerradas (MO-F1, F2a-1, F2a-2a, F2a-2b). Quedan F2b —los 13 módulos restantes— y F3 —tema claro—; F4 se absorbió en DS-F3 |
| [[goals/runtime-budgets-al-ci/goal\|runtime-budgets-al-ci]] | **Activo.** Persigue el único gate `blocked` de los nueve de `closeout-evidence.json`. Andamio declarado: DS-F3 lo reemplaza |
| [[goals/gates-al-ci/goal\|gates-al-ci]] | **Pausado** con sus dos decisiones ya confirmadas y sin ejecutar (`test.C` en `docker-compose.ci.yml`, baseline 0.3.4) |
| [[goals/contadores-cero/goal\|contadores-cero]] | Visto concedido; falta localizar la rama, re-verificar y publicar |
| [[goals/vocabulario-estados-cascada/goal\|vocabulario-estados-cascada]] | **En replanteo** por pedido del usuario (D-VOC-1). Su aclaración clave: [[programa-general-actualizar-es-otra-herramienta]] |
| [[goals/contrato-estados-modulo-fantasma/goal\|contrato-estados-modulo-fantasma]] | Su D-1 se ajusta al censo que salga del replanteo anterior, en un solo movimiento |
| [[goals/semana-fija-visual/goal\|semana-fija-visual]] | Abierto |
| [[goals/repaso-usabilidad-no-tablas/goal\|repaso-usabilidad-no-tablas]] | Reabierto para ejecutar el hallazgo H-08 que `cierre-dark-mode-y-tablas` dejó diferido |
| [[goals/pdc-tanda2-plan-verdad/goal\|pdc-tanda2-plan-verdad]] | Condición de hecho cumplida el 2026-07-29 **sin sección de cierre**: por la regla de lectura cuenta como abierto, aunque su trabajo esté hecho. Se cierra escribiendo el cierre, no re-ejecutando |
| [[goals/adopcion-logo-construccion/goal\|adopcion-logo-construccion]] | Igual que el anterior: ejecutado (`4437fcfa`, `6b618964`) y sin cierre escrito |

## Cerrados

**Programa design system y estados (2026-08-19)** — [[goals/ds-f0-auditoria/goal|ds-f0-auditoria]] ·
[[goals/ds-f1a-estado/goal|ds-f1a-estado]] · [[goals/estados-fuera-de-ventana/goal|estados-fuera-de-ventana]] ·
[[goals/migracion-estados/goal|migracion-estados]] · [[goals/bug-coloreado-severidad/goal|bug-coloreado-severidad]]

**Wiki v2, las seis tandas (2026-08-19)** — [[goals/wiki-t1/goal|wiki-t1]] · [[goals/wiki-t2/goal|wiki-t2]] ·
[[goals/wiki-t3/goal|wiki-t3]] · [[goals/wiki-t4/goal|wiki-t4]] · [[goals/wiki-t5/goal|wiki-t5]] ·
[[goals/wiki-t6/goal|wiki-t6]]

**Biblia de flujos (2026-08-06)** — [[goals/biblia-t1-transversal/goal|t1 transversal]] ·
[[goals/biblia-t2-cascada-lps/goal|t2 cascada LPS]] · [[goals/biblia-t3-pdc/goal|t3 PDC]] ·
[[goals/biblia-t4-soporte/goal|t4 soporte]] · [[goals/biblia-t5-lectura/goal|t5 lectura]]

**PDC** — [[goals/pdc-a41-pasos-configurables/goal|a41 pasos configurables]] ·
[[goals/pdc-a42-frentes-cobertura/goal|a42 frentes]] · [[goals/pdc-preparar-b1/goal|preparar b1]] ·
[[goals/pdc-revision-ux/goal|revisión UX]] · [[goals/pdc-tanda34-pulido/goal|tanda 3-4 pulido]] ·
[[goals/retiro-listado-contratos/goal|retiro listado y contratos]]

**Design system y UI** — [[goals/cierre-version-1-1-0-design-system/goal|cierre 1.1.0]] ·
[[goals/cierre-dark-mode-y-tablas/goal|dark mode y tablas]] ·
[[goals/shell-layout-design-system/goal|shell layout]] ·
[[goals/sidebar-todos-modulos/goal|sidebar en 11 módulos]] ·
[[goals/segmentacion-entrypoint-css/goal|segmentación CSS]] ·
[[goals/css-presupuesto-57kb/goal|presupuesto CSS]] · [[goals/pg-chip-de-estado/goal|chip de estado PG]] ·
[[goals/ci-en-verde/goal|CI en verde]]

**Cerrados sin ejecución propia** — [[goals/dark-mode-todos-los-modulos/goal|dark-mode-todos-los-modulos]]
y [[goals/pdc-responsable-usuario/goal|pdc-responsable-usuario]] fueron absorbidos por otro goal;
sus decisiones siguen vigentes aunque ninguna fase corriera bajo su nombre. Ver
[[goal-dark-mode-todos-modulos]].

**Dos que no son cierres normales:**

- [[goals/validar-migracion-handsontable/goal|validar-migracion-handsontable]] — **descartado**:
  quedó sin objeto al retirarse las superficies que iba a validar.
- [[goals/ds-f1a-estados-severidad/goal|ds-f1a-estados-severidad]] — **retirado el mismo día que se
  escribió**, al aparecer al integrar que un frente hermano había publicado la misma escala con un
  número de niveles distinto. Su `## Cierre` se sustituyó en vez de borrarse, a propósito: el mapa
  de estado deriva de la presencia de esa sección, y quitarla haría mentir a dos sitios a la vez.

## Los diez que son plantilla

`goal.md` existe pero el objetivo sigue siendo `<!-- 1-3 frases -->`. **Nueve de ellos no se sabe si
siguen vivos** y el triaje es criterio del usuario, no deducción: `a187ccda`,
`buttons-important-leyenda`, `contador-no-mide-el-archivo`, `focus-visible-verde`,
`forma-quitar-pasos`, `reserva-redundante-green-dark`, `reservas-contradictorias-var`,
`severidad-runtime`, `veracidad-8`. Todos tienen su `decisiones/<slug>.md`.

El décimo, [[goals/apply-recalculo-estados/goal|apply-recalculo-estados]], **sí está vivo y es el de
mayor riesgo del repo**: Felipe autorizó el apply completo del recálculo de la columna `Estado`
—40.664 filas, 16 proyectos— con el informe del dry-run delante. Espera ventana de base exclusiva,
y **antes hay que capturar las 24 filas terminadas con fecha futura**: el recálculo las mandaría a
`Fuera de Ventana` y se perdería el dato de que estaban terminadas.

## Qué viaja en git — resuelto el 2026-08-18

**Esta sección decía lo contrario hasta hoy, y prescribía una acción que ahora sobra.** Durante meses
`.gitignore` excluía `goals/` entero y lo iba reabriendo con una lista blanca escrita a mano, cinco
líneas por goal. Nadie se acordaba, y así hubo **19 `goal.md` sin versionar**: se creaban, se
trabajaban y desaparecían en un clon fresco.

`9711ae3f` lo cerró con una regla general al final del archivo —`!goals/**/`, `!goals/**/*.md`,
`!goals/**/*.json`—, colocada al final a propósito porque en `.gitignore` gana la última regla que
casa. Medido el 2026-08-19: **146 de 148 `.md` versionados**, y los dos que faltan son de frentes
nacidos hoy, no huérfanos.

Sigue fuera a propósito: los PNG de `evidence/` (`goals/**/*.png`), porque 14 MB de capturas en la
historia de git serían irreversibles y no es la pérdida que esta decisión existe para evitar.

**Ya no hay que añadir nada al `.gitignore` al crear un goal.** Si esta línea vuelve a aparecer en
alguna nota, está caducada.
