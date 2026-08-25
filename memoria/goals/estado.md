---
capa: wiki
tipo: goal
estado: vigente
fecha: 2026-08-25
areas: [proceso]
tags: [dashboard]
fuente: los 63 `goals/<slug>/goal.md` recontados el 2026-08-25 en el pase de veracidad, más `git ls-files` y el lint de la wiki
resumen: "Estado de los 63 goals del repo, leído de la sección `## Cierre` de cada goal.md: 58 cerrados y 5 abiertos"
---
# Estado de los goals

**Corte del 2026-08-25**, recontado goal por goal en el pase de veracidad. El del 2026-08-19 daba
42 cerrados y 13 abiertos sobre 56 carpetas; hoy son **58 y 5 sobre 63**. Y el del 2026-08-10
describía 26 carpetas cuando ya había 56 — nueve días y treinta goals de desfase, en la página que
existe justo para responder «¿dónde quedó X?». **Es la tercera vez que esta página envejece por el
mismo motivo**, así que conviene decirlo aquí: su cifra caduca en días, no en semanas, y el pase de
veracidad es lo único que la mantiene honesta.

La fuente sigue siendo `goals/<slug>/`; esta página solo lo resume. **La regla de lectura es una:
un goal está cerrado si su `goal.md` tiene una sección `## Cierre` con contenido** —salida de
comandos, no prosa—. No se deduce de que lleve días quieto ni de que su plan tenga casillas
marcadas: **las casillas de los planes no miden nada** —96 marcadas de 1.680, medido el 2026-08-19—.

Para las **fases de los cuatro programas y los pendientes vivos**, [[TASKS]], que es la fuente
única desde que la cola de pendientes migró a la raíz.

## El reparto

| | Cuántos | Qué significa |
|---|---|---|
| **Cerrados** | 58 | `## Cierre` con evidencia. Incluye 1 retirado y 1 descartado |
| **Abiertos** | 5 | objetivo redactado, sin cierre |
| **Plantilla** | 0 | ya no queda ninguna |

*(Recuento del **2026-08-25**, pase de veracidad, con
`for d in goals/*/; do grep -q '^## Cierre' $d/goal.md; done`: **63 carpetas, 58 con cierre, 5 sin
él, 0 sin `goal.md`**. La tabla anterior decía 42/13/1, cifras del corte del 2026-08-19 que
envejecieron seis días — y sumaban 56, no 63.)*

**Eran 56 goals y 10 plantillas hasta el 2026-08-19.** Ocho de esas plantillas recibieron su
objetivo y su cierre con evidencia re-medida ese día, y una carpeta —`a187ccda`— se borró por no ser
un frente. **La última que quedaba, `apply-recalculo-estados`, ya tiene su `## Cierre`**
(comprobado el 2026-08-25): no queda ninguna plantilla.

## Abiertos o bloqueados

> **Recorte del 2026-08-24.** De los 12 que esta tabla listaba, **seis ya tienen `## Cierre`** y
> salieron: `contadores-cero`, `contrato-estados-modulo-fantasma`, `semana-fija-visual`,
> `repaso-usabilidad-no-tablas`, `pdc-tanda2-plan-verdad` y `adopcion-logo-construccion` — los dos
> últimos eran justamente los que esta página señalaba como «ejecutados sin cierre escrito», y el
> cierre se escribió. Entró `organizar-la-casa`, en esa misma situación. Medido con
> `grep -rl '^## Cierre' goals --include=goal.md`: **56 de 63**.
>
> **Segundo recorte, 2026-08-25 (pase de veracidad): ahora son 58 de 63.** Salieron
> `runtime-budgets-al-ci` y `gates-al-ci`, que cerraron el 2026-08-24 vía Plan P2 y esta tabla
> seguía dando por abiertos — uno como «activo» y el otro como «pausado». **Ninguno de los cinco que
> quedan está esperando trabajo ajeno: a tres les falta solo escribir su cierre.**


| Goal | Estado |
|---|---|
| [[goals/bi-control-tower-gemini/goal\|bi-control-tower-gemini]] | **Desbloqueado desde el 2026-08-20, y esta página decía lo contrario.** Su condición pedía aprobar seis modos, tres del tema `linen` retirado el 2026-07-25 (DS-030): nadie podía cumplirla. **Felipe resolvió D-7 con la opción (a): recortar la condición a los tres modos dark** (`DECISIONES_PENDIENTES.md:385`), y P4 lo confirma — «no bloquea a `bi-control-tower-gemini`, que cierra en dark por decisión propia». Lo que falta es **escribir su cierre**, no esperar nada. Ver [[condicion-de-hecho-caduca-sin-aviso]] |
| [[goals/design-system-nucleo-gobernanza/goal\|design-system-nucleo-gobernanza]] | **No se cierra**, medido el 2026-08-10: de sus 15 gates solo 2 pasaban de verdad, 4 fallaban con evidencia, 8 no eran ejecutables y 1 era un recibo sin comando detrás. Los 14 artefactos de `docs/design-system/evidence/` eran stubs de dos claves. **Superado por el programa DS**: los 15 gates se reemplazan en DS-F3, no se arreglan. Ver [[gate-solo-cuenta-elementos-no-los-lee]] |
| [[goals/reapertura-movil-y-tema-claro/goal\|reapertura-movil-y-tema-claro]] | **Abierto**, 4 de 7 fases cerradas (MO-F1, F2a-1, F2a-2a, F2a-2b). Quedan F2b —los 13 módulos restantes— y F3 —tema claro—; F4 se absorbió en DS-F3. **Su spec se derogó el 2026-08-25** a favor de P4, que ejecuta la decisión D-9 de Felipe: el trabajo vive ahí, no aquí |
| [[goals/vocabulario-estados-cascada/goal\|vocabulario-estados-cascada]] | **Ya no está «en replanteo»: sus cuatro decisiones se resolvieron el 2026-08-11** (D-VOC-1 a D-VOC-4, `docs/decisiones-pendientes.md`), y su trabajo mecánico —35→29 términos en Intermedia— está en el código. Lo que decía «en replanteo» era una copia de la cola sin sincronizar. Falta **escribir su cierre**, y ejecutar D-VOC-4 (separar `Capítulo`) en frente propio, porque toca datos persistidos. Su aclaración clave sigue valiendo: [[programa-general-actualizar-es-otra-herramienta]] |
| [[goals/organizar-la-casa/goal\|organizar-la-casa]] | **Ejecutado y sin cierre escrito** (2026-08-20): las siete reglas viven en [[docs/coordinacion-sesiones]] y `AGENTS.md` las referencia. Por la regla de lectura cuenta como abierto |

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

## Los nueve que parecían muertos y estaban solo sin firmar

Hasta el 2026-08-19 esta página decía que de los goals en plantilla **«nueve no se sabe si siguen
vivos»**. Se fue a buscar su rastro en `docs/decisiones-pendientes.md` y `memoria/log.md`, y **los
nueve habían corrido**. Ocho recibieron ese día su objetivo y su cierre, con la evidencia re-medida
contra el código; el noveno resultó no ser un frente:

| Frente | Rastro |
|---|---|
| `buttons-important-leyenda` | 41 `!important` → 16 (11-ago). Medido hoy: `buttons.css` en 138, el número que declaró |
| `focus-visible-verde` | `D-F1-3` resuelta: el foco lo señala el anillo, no el relleno. `:777` sin reserva |
| `reservas-contradictorias-var` | `D-RES-1`, misma resolución, aplicada dentro del frente anterior |
| `reserva-redundante-green-dark` | La reserva de `:758` retirada; hoy es `var(--aia-green-dark)` limpio |
| `contador-no-mide-el-archivo` | Produjo [[el-contador-no-mide-el-archivo]], «el hallazgo más caro del día» |
| `veracidad-8` | El octavo pase está en `log.md`; van once |
| `forma-quitar-pasos` | `D-FORMA-1` resuelta: cerró como «medir y repartir» y engendró tres frentes hijos |
| `a187ccda` | **No es un frente: es un id de sesión.** Aparece como autora de dos frentes distintos |
| `severidad-runtime` | Corrió; dejó la trampa del reescalado de capturas que `bug-coloreado-severidad` hereda |

**La lección no es sobre estos nueve, es sobre la regla de lectura:** un `goal.md` en plantilla no
dice «frente muerto», dice «nadie rellenó el papel al terminar». Deducir muerte del silencio habría
tirado nueve entregas reales.

El décimo, [[goals/apply-recalculo-estados/goal|apply-recalculo-estados]], **ya se ejecutó** sobre
la base de **desarrollo** (`aa965bf5`, 13:40): 40.664 filas migradas, ventana exclusiva,
reconciliación exacta, acta en `goals/apply-recalculo-estados/acta-del-apply.md`. **Producción
sigue sin tocar** y necesita autorización propia.

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
