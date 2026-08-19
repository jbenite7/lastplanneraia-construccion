---
capa: wiki
tipo: goal
estado: abierto
fecha: 2026-08-19
areas: [proceso]
tags: [pendiente]
fuente: sesión de coordinación 2026-08-18 (inventario de planes, specs y sesiones + 22 decisiones del usuario); consolidación de fases 2026-08-18; repaso contra `origin/main` del 2026-08-19
resumen: "Ledger de fases: los cuatro programas, sus 22 fases, el orden entre ellas y qué bloquea a qué"
---
# Cola de fases de los cuatro programas

**Esta página responde una pregunta y solo una: ¿en qué fase va cada programa y qué bloquea a
qué?** El orden entre bloques se lee aquí y no se deduce de ningún otro lado.

**Los pendientes sueltos no viven aquí, viven en [[TASKS]]**, que es la fuente única de pendientes
del proyecto. Hasta el 2026-08-19 las dos páginas decían ser «la fuente única», que es una
contradicción con coste: dos listas divergen, y entonces no manda ninguna. El reparto es por
pregunta — fases y su orden, aquí; frentes vivos y tareas, en `TASKS.md`.

Se actualiza al cerrar o reordenar, no se deja derivar. Nada de lo que hay aquí es contrato:
precedencia **código > `AGENTS.md` > `memoria/`**.

## Por qué existe esta consolidación

El proyecto tenía sus fases repartidas en cuatro planes que **numeran igual sin ser lo mismo**: hay
tres cosas distintas llamadas «F0» y dos llamadas «F1». Nadie podía responder «¿dónde quedó la
fase X?» sin abrir cuatro archivos y adivinar a cuál se refería. Consolidado el 2026-08-18.

Segundo hallazgo de esa consolidación, que vale por sí solo: **las casillas de los planes no miden
nada.** El 2026-08-18 eran 0 marcadas de 435 en 17 planes, incluidos planes cuyo trabajo estaba en
producción. Re-medido el 2026-08-19 sobre los 54 planes: **96 marcadas de 1.680, un 5,7%** — sigue
sin medir nada, solo que ahora con más muestra. Es el mismo defecto que `coordinating-agent-sessions`
tiene medido en su propio plan. Para saber si algo está hecho, se verifica **contra el código**, no
contra su casilla.

## Bloque 0 — Arranque (bloquea todo lo demás)

Orden del usuario, 2026-08-18: los frentes y chips no arrancan hasta cerrar estas dos.

| Fase | Qué es | Estado |
|---|---|---|
| **Fase 0** | Mudanza del repositorio a `~/Developer/lps-aia` | **HECHA** (2026-08-18). Copia verificada (fsck limpio, 2.7G), 6 worktrees reparados, montaje Docker actualizado, web 200, PHP 24/24. Respaldo en `/Volumes/Crucial X6/Developer/lps-aia.pre-mudanza-2026-08-18`; borrarlo es decisión aparte. La BD no se movió: vive en el volumen Docker `htdocs_db_data` |
| **Fase 0b** | Replanteo completo de la wiki: metodología Karpathy intacta, Obsidian visual, vault entero etiquetado y frontmatter en todas las fuentes (solo metadato; el cuerpo sigue intocable) | **HECHA** (2026-08-19), las seis tandas, publicadas. `wiki-lint.mjs --estricto` verde sobre 156 páginas y 414 de 417 fuentes. **Con dos salvedades declaradas:** los plugins de comunidad quedaron fuera por decisión del usuario, y los grupos de color del grafo quedaron pendientes por no poder verificarse sin abrir Obsidian |

Las seis tandas de la 0b, en `docs/superpowers/plans/2026-08-18-wiki-v2-visual.md` (~2 jornadas;
cada tanda cierra en verde antes de la siguiente):

| Tanda | Qué hizo | Cerrada en |
|---|---|---|
| **1 · Esquema y herramientas** | `wiki-operacion.md` a v2, lint v2, `wiki-frontmatter.mjs`, 5 moldes | `7208edf9` |
| **2 · Frontmatter a las fuentes** | 413 archivos por lotes, con revisión entre uno y otro. **Cero borrados**: solo se añadió metadato | `e5c540c3` |
| **3 · Retag fino** | `capa: wiki` en las 151 páginas, `generado` en 26, `trampa` en 4 | `26a8fe80` |
| **5 · MOCs completos** | 5 mapas nuevos; las 13 áreas tienen MOC. `moc` sale del vocabulario | `58240c2c` |
| **4 · Capa visual** | 13 vistas Bases, 3 canvas, dashboard, snippet de severidad. **Sin plugins** | `66012929` |
| **6 · Cierre** | Regeneración, línea `ingest`, esta tabla | esta tanda |

La 5 se cerró antes que la 4 porque el usuario reordenó: la 4 tocaba plugins de terceros y quedó
esperando su decisión.

**Lo que la Fase 0b deja pendiente**, para que no se pierda al marcarla hecha:

| Pendiente | Por qué quedó fuera |
|---|---|
| Plugins de comunidad (Dataview, Tasks, Kanban, Excalidraw, Iconize, Homepage, tema Minimal) | Decisión del usuario: los decide aparte. Con ellos quedan fuera el Kanban de esta cola y el arranque automático del dashboard |
| Grupos de color del grafo (`.obsidian/graph.json`) | No hay forma de comprobar que la consulta hace lo que dice sin abrir Obsidian, y el criterio de la tanda fue que sin verificación no se escribe |
| Enchufar `--estricto` a `npm run test:wiki` | Es decisión de contrato: a partir de ahí toda fuente nueva nace con frontmatter o el gate se pone rojo. **Ya se midió el hueco**: una fuente entró sin declarar por un merge y el gate no lo detuvo |
| 3 archivos del design system sin frontmatter | Están congelados por sha256 en `goal-provenance.json`. Ratificado por el usuario |
| 9 `goal.md` que son andamiajes sin objetivo escrito | Salen en el catálogo con un resumen que lo dice. Recontados el 2026-08-19: son nueve, no ocho. Hay que decidir cuáles siguen vivos — ver «El triaje que espera criterio de Felipe» |

## Bloque 1 — Programa Design System (cuatro fases)

Decisión del usuario del 2026-08-18, en [[programa-design-system-en-cuatro-fases]]: «el design
system no está bien definido, ni bien implementado, ni bien controlado». **Es el programa que
manda sobre los gates.**

| Fase | Qué es | Estado |
|---|---|---|
| **DS-F0 · Auditoría total** | Toda la app: módulo, objeto, variable y escenario. Absorbe como semilla las 48 decisiones del 3-ago y F-4…F-9 de `docs/DESIGN-AUDIT.md` | **CERRADA y publicada** (2026-08-19, `567e566e`). `docs/design-system/auditoria/` con **68 hallazgos** —7 críticos, 31 mayores, 13 menores, 6 cosméticos, 11 sin problema— sobre un censo de **257 rutas**. Cero cambios en código de producto, como exigía su posture |
| **DS-F1 · Redefinición del contrato** | Tokens, primitivas `aia-*`, escalas de estado/severidad y escala de stacking (z-index). El contrato es decisión de negocio, así que arranca con brainstorming | **EN CURSO.** La escala de estado (F1a) cerró; queda el resto: tokens, primitivas, severidad y z-index. Ver «Dónde va DS-F1» abajo |
| **DS-F2 · Reimplementación por adaptadores** | Primero Handsontable y DataTables, que concentran la deuda; luego módulo a módulo según DS-F0 | No empezada. Ya tiene de qué partir: los 68 hallazgos de DS-F0 |
| **DS-F3 · Control** | Gates nuevos derivados del contrato. **Los 15 actuales se reemplazan, no se arreglan.** Cinco principios: pocos y atados a contratos que duelan; nunca bloquean el flujo local, solo el merge; actualizar un baseline cuesta un comando con diff visible; todo rojo dice qué archivo y qué hacer; cuarentena explícita para gates ruidosos | No empezada |

### Dónde va DS-F1

Cuatro frentes corrieron el 2026-08-19 sobre la escala de estado. Tres cerraron y están publicados;
el cuarto espera una ventana de base, no una decisión.

| Frente | Estado |
|---|---|
| [[goals/ds-f1a-estado/goal\|ds-f1a-estado]] | **Cerrado** sobre `4a152a54`. Fijó la escala de estado del contrato midiendo contra 50.966 actividades reales |
| [[goals/ds-f1a-estados-severidad/goal\|ds-f1a-estados-severidad]] | **RETIRADO** el mismo día que se escribió: publicaba una escala de cuatro niveles que contradecía la de tres de su frente hermano. Se conserva con su `## Cierre` sustituido, no borrado, porque el mapa de estado deriva de esa sección |
| [[goals/estados-fuera-de-ventana/goal\|estados-fuera-de-ventana]] | **Cerrado** sobre `aeaa7a77`. Los dos calculadores producen `Fuera de Ventana` desde la séptima semana, y por primera vez tienen pruebas — de ellos depende el `Estado` de 65.549 filas |
| [[goals/migracion-estados/goal\|migracion-estados]] | **Cerrado.** Prepara y ensaya la migración; **no la aplica, y su guarda deniega el `--apply` con `RC=1`**. Dry-run: 40.664 filas cambiarían. Respaldo probado restaurando 2.024 filas estropeadas, no declarado |
| [[goals/apply-recalculo-estados/goal\|apply-recalculo-estados]] | **AUTORIZADO Y SIN EJECUTAR.** Felipe autorizó el apply completo, las tres familias, con el informe del dry-run delante. Su `goal.md` sigue en plantilla |

**El riesgo que hereda el apply, y hay que correrlo antes:** 24 de las 113 filas contradictorias
están al 100% de avance con fecha de inicio futura. Un recálculo masivo las manda a
`Fuera de Ventana` y **se pierde el dato de que estaban terminadas**. La consulta que las captura
vive en `goals/estados-fuera-de-ventana/diagnostico-113-contradictorias.md` y hay que correrla
**antes** de migrar: después ya no hay forma de saber cuáles eran. Solo cubre la base de
DESARROLLO — producción es deploy y va aparte.

Consecuencia de secuencia ya decidida: **la Torre de Control BI no se recaptura**, se reconstruye
con enfoque data storytelling sobre el contrato de DS-F1; hacerlo antes sería construirla dos veces.

## Bloque 2 — Cierre hasta producción (cinco fases)

`docs/superpowers/plans/2026-08-11-cierre-hasta-produccion.md`.

| Fase | Frente | Estado |
|---|---|---|
| **CP-F0 · Poner el CI en verde** | `ci-en-verde` | Añadida el 2026-08-12 delante de todo (`6d82f723`), porque `design-system-runtime` lleva `needs: design-system-static` y el static llevaba rojo desde el 2026-07-17 |
| **CP-F-AB · Cablear los dos gates al CI** | `gates-al-ci` | **PAUSADO.** Sus dos decisiones ya confirmadas por el usuario (añadir `test.C` a `DEV_DOOR_USERS` en `docker-compose.ci.yml`, y el baseline 0.3.4), sin ejecutar |
| **CP-F-C · Cada módulo declara dónde pinta sus estados** | `superficie-de-estados` | Pendiente. Decisión del usuario: opción (a), obligatoria |
| **CP-F-D** | — | **RETIRADA** el 2026-08-12: su premisa estaba caducada, ya estaba hecha |
| **CP-F-E · Despliegue a producción** | `despliegue` | Pendiente. ~1.255 commits de retraso. **Necesita autorización propia y explícita, siempre** |

## Bloque 3 — Móvil, tablet y tema claro (siete fases)

`goals/reapertura-movil-y-tema-claro/goal.md`.

| Fase | Qué es | Estado |
|---|---|---|
| **MO-F1 · Destrabar** | `390x844` vuelve a ser soportado y no requerido | **CERRADA** (2026-08-07, DS-032) |
| **MO-F2a-1 · Precondiciones** | El gate valida los 15 manifiestos (miraba 4) y ata cada golden a su tema, viewport y contenido | **CERRADA** (2026-08-07) |
| **MO-F2a-2a · Deudas de arranque** | El golden mide exactamente su viewport salvo recorte declarado; los 17 manifiestos en `1.1.0` | **CERRADA** (2026-08-07, DS-033) |
| **MO-F2a-2b · Piloto móvil** | Handsontable deja de instanciarse bajo el umbral (0 nodos en 390×844); el sidebar pasa a menú flotante — era la causa raíz de que móvil fuera inusable: se comía 240 de 390 px y nunca colapsaba | **CERRADA** (2026-08-14) |
| **MO-F2b · Resto de módulos** | Los 13 restantes, con el coste ya medido en el piloto | Pendiente |
| **MO-F3 · Tema claro** | Paleta clara nueva y conmutador con preferencia guardada. Ojo: `linen` se retiró del producto el 2026-07-25 (DS-030), así que es reconstruir, no reactivar | Pendiente |
| **MO-F4 · Matriz diagonal** | Los gates adoptan la matriz de D6 y los candados se reinstalan | Pendiente — **absorbida por DS-F3**, ver «El solape de los gates» |

## El solape de los gates, y cómo se resuelve

Tres bloques empujaban la misma pieza: **DS-F3** dice que los 15 gates se reemplazan, **CP-F-AB**
está cableando dos de esos mismos gates, y **MO-F4** quiere cambiarles la matriz.

**Resolución (2026-08-18): manda DS-F3.** Los otros dos se subordinan:

- **MO-F4 se retira como fase propia** y entra como requisito dentro de DS-F3: la matriz de D6 es
  una entrada del contrato nuevo, no un trabajo aparte sobre los gates viejos.
- **CP-F-AB se recorta a lo mínimo que desbloquea el CI** y no se amplía. Cablear dos gates que
  DS-F3 va a reemplazar solo se justifica porque sin CI verde no hay forma de medir nada de DS-F0.
  Es andamio declarado, no inversión.

  **Matiz del 2026-08-19:** DS-F0 cerró **sin** que CP-F-AB llegara a ejecutarse, así que esa
  justificación ya no aplica tal cual. Lo que queda vivo es el gate `runtime-budgets`, el único
  `blocked` de los nueve de `closeout-evidence.json`, que persigue el frente
  [[goals/runtime-budgets-al-ci/goal|runtime-budgets-al-ci]]. El resto del andamio se revisa antes
  de invertirle nada: no se cablea un gate que DS-F3 va a tirar salvo que desbloquee una medida
  concreta que alguien necesite hoy.

## Frentes en espera

El bloque 0 cerró el 2026-08-19, así que estos ya no están bloqueados por él. El orden lo manda el
bloque 1.

- [[goals/apply-recalculo-estados/goal|apply-recalculo-estados]] — autorizado; espera ventana de base exclusiva y la captura previa de las 24 filas en riesgo.
- [[goals/gates-al-ci/goal|gates-al-ci]] — CP-F-AB recortado: `test.C` en CI + baseline, re-medir 8/8, publicar.
- [[goals/contadores-cero/goal|contadores-cero]] — visto concedido; localizar rama, re-verificar, publicar.
- **Plan espacio SiteGround** — tareas 1–5 de `docs/superpowers/plans/2026-08-18-espacio-cuenta-siteground.md`.
- **Dropdown PS sobre selector de semana** — diagnóstico (`systematic-debugging`) del stacking en `/programacion-semanal`.
- **Higiene de coordinación** — sesiones zombi, `cas-log.*` de la raíz, y el triaje de abajo.

## El triaje que espera criterio de Felipe

**Nueve `goal.md` son andamiajes con el objetivo sin redactar** y nadie sabe cuáles siguen vivos:
`a187ccda`, `buttons-important-leyenda`, `contador-no-mide-el-archivo`, `focus-visible-verde`,
`forma-quitar-pasos`, `reserva-redundante-green-dark`, `reservas-contradictorias-var`,
`severidad-runtime` y `veracidad-8`. Todos tienen su `decisiones/<slug>.md`, ninguno tiene cierre.
No se cierran ni se borran por cuenta propia: decidir que un frente murió es criterio del usuario,
no deducción de que lleve días quieto.

## Replanteo antes de ejecutar

- [[goals/vocabulario-estados-cascada/goal|vocabulario-estados-cascada]] — el usuario pidió
  replantear D-VOC-1; su aclaración clave está en
  [[programa-general-actualizar-es-otra-herramienta]]. D-VOC-4 exige análisis profundo. D-1 de
  `contrato-estados-modulo-fantasma` se ajusta al censo que salga del replanteo, en un solo
  movimiento.

## Apuestas planificadas (tras lo anterior)

Torre de Control reconstruida con data storytelling (tras DS-F1 y el tema claro) · semana fija en
el resto de módulos con corte semanal · extensión de contadores-cero a todos los módulos · backlog
del 3-ago (48 decisiones; accesibilidad primero, absorbido por DS-F0).
