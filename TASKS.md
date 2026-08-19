---
capa: fuente
tipo: goal
estado: vigente
fecha: 2026-08-18
areas: [proceso]
tags: [proyecto]
fuente: sesión de coordinación 2026-08-18 (inventario de planes, specs y sesiones + 22 decisiones del usuario); consolidación de fases 2026-08-18
resumen: "Fuente única de pendientes: las 22 fases de los cuatro programas, su orden y su estado"
project: lps-aia
type: tasks
status: activo
updated: 2026-08-19
---

# Tareas

**Fuente única de pendientes del proyecto.** Las cuatro secciones de abajo son la vista operativa;
el detalle por bloques y fases, más abajo, es el mismo trabajo visto por programa. Si algo no está
aquí, no está pendiente.

## Bloqueantes

- [ ] **Decidir los plugins de comunidad de Obsidian.** La Tanda 4 se hizo solo con lo nativo por
  decisión del usuario (2026-08-19). Mientras no se decida, quedan fuera el Kanban de esta cola,
  el arranque automático del dashboard (Homepage), los iconos por carpeta y el tema Minimal.
  Versionarlos mete dependencias de terceros en el árbol: por eso no se hizo de paso.

## Ahora

- [ ] **Ocho `goal.md` son andamiajes con el objetivo sin escribir** — `a187ccda`,
  `buttons-important-leyenda`, `contador-no-mide-el-archivo`, `focus-visible-verde`,
  `forma-quitar-pasos`, `reserva-redundante-green-dark`, `reservas-contradictorias-var`,
  `severidad-runtime`, `veracidad-8`. Salen en el catálogo con un resumen que dice exactamente eso.
  Hay que decidir cuáles siguen vivos y cuáles se descartan.
- [ ] **El módulo CAS no existe en el plugin instalado** (`loop-engineering/0.3.0`), y el caché de
  `0.2.0` desapareció a mitad de jornada. **Los gates de rutas, presupuesto y push no están
  activos**: el cumplimiento es disciplina, no mecánica. Con varias sesiones en paralelo, cuesta.
- [ ] **Ordenar `CHANGELOG.md`.** No está en orden cronológico inverso: `[1.1.1]` y `[1.1.0]`
  aparecen antes que `[Sin publicar]` y que `[1.2.0]`. Se detectó el 2026-08-19 y **no se corrigió
  en el mismo turno a propósito**: reordenar 400 líneas de historia ajena a mano arriesga perder
  contenido, y eso pide su propia pasada con verificación.

## Diferibles

- [ ] **Grupos de color del grafo** (`.obsidian/graph.json`). Se difirió porque no hay forma de
  comprobar que la consulta de color hace lo que dice sin abrir Obsidian, y el criterio de la
  Tanda 4 fue que sin verificación no se escribe.
- [ ] **Rediseñar el proxy de la alarma de veracidad.** Hoy cuenta commits y **no sabe de qué habla
  la wiki**: pesa igual un commit en un área con quince páginas que uno en un área sin ninguna.
  Ahora sería afinable —las 13 áreas tienen mapa y las fuentes declaran su `areas`—, pero es
  cambiar el proxy entero, no recortarlo. Los tres descuentos de 2026-08-19 ya exprimieron el atajo.
- [ ] **Versionar el estado de coordinación.** `.claude/vistos/` está en `.gitignore:219` y
  `decisiones/gobierno-relato-de-autorizaciones.md` está sin commitear, así que ninguna sesión que
  trabaje en un worktree los ve. Precedente medido el 2026-08-11: un archivo de estado compartido
  sin versionar se llevó doce hallazgos sin diff y sin rastro. Lo lleva la coordinadora.

## Hechas (últimas 10)

- [x] 2026-08-19 — **Fase 0b completa**: las seis tandas de la wiki v2, publicadas por separado.
- [x] 2026-08-19 — El gate de publicación bloquea por la forma de la wiki y solo avisa por la alarma.
- [x] 2026-08-19 — La alarma deja de contar frontmatter, merges que solo unen y documentos de intención.
- [x] 2026-08-19 — Los cinco archivos de la wiki del proyecto, en la raíz y con frontmatter fusionado.
- [x] 2026-08-18 — **Fase 0**: mudanza del repositorio a `~/Developer/lps-aia`, verificada antes del cutover.

## El detalle, por bloques

**Esta página manda.** Es el único sitio donde se mira qué está pendiente y en qué orden. El
detalle de cada decisión sigue en `decisiones/<frente>.md` y en cada `goals/<slug>/goal.md`, pero
el **estado y la prioridad** se leen aquí y no se deducen de ningún otro lado.

Se actualiza al cerrar o reordenar, no se deja derivar. Nada de lo que hay aquí es contrato:
precedencia **código > `AGENTS.md` > `memoria/`**.

## Por qué existe esta consolidación

El proyecto tenía sus fases repartidas en cuatro planes que **numeran igual sin ser lo mismo**: hay
tres cosas distintas llamadas «F0» y dos llamadas «F1». Nadie podía responder «¿dónde quedó la
fase X?» sin abrir cuatro archivos y adivinar a cuál se refería. Consolidado el 2026-08-18.

Segundo hallazgo de esa consolidación, que vale por sí solo: **las casillas de los planes no miden
nada.** De 435 casillas repartidas en 17 planes, hay **0 marcadas** — incluidos planes cuyo trabajo
está en producción. Es el mismo defecto que `coordinating-agent-sessions` tiene medido en su propio
plan. Para saber si algo está hecho, se verifica **contra el código**, no contra su casilla.

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
| 8 `goal.md` que son andamiajes sin objetivo escrito | Salen ahora en el catálogo con un resumen que lo dice. Hay que decidir cuáles siguen vivos |

## Bloque 1 — Programa Design System (cuatro fases)

Decisión del usuario del 2026-08-18, en [[programa-design-system-en-cuatro-fases]]: «el design
system no está bien definido, ni bien implementado, ni bien controlado». **Es el programa que
manda sobre los gates.**

| Fase | Qué es | Estado |
|---|---|---|
| **DS-F0 · Auditoría total** | Toda la app: módulo, objeto, variable y escenario. Absorbe como semilla las 48 decisiones del 3-ago y F-4…F-9 de `docs/DESIGN-AUDIT.md`. Entregable: inventario por severidad «Crítico → Sin problema», verificando de paso el bug de coloreado que el usuario sospecha | No empezada |
| **DS-F1 · Redefinición del contrato** | Tokens, primitivas `aia-*`, escalas de estado/severidad y escala de stacking (z-index). Arranca con brainstorming con el usuario: el contrato es decisión de negocio | No empezada |
| **DS-F2 · Reimplementación por adaptadores** | Primero Handsontable y DataTables, que concentran la deuda; luego módulo a módulo según DS-F0 | No empezada |
| **DS-F3 · Control** | Gates nuevos derivados del contrato. **Los 15 actuales se reemplazan, no se arreglan.** Cinco principios: pocos y atados a contratos que duelan; nunca bloquean el flujo local, solo el merge; actualizar un baseline cuesta un comando con diff visible; todo rojo dice qué archivo y qué hacer; cuarentena explícita para gates ruidosos | No empezada |

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

## Frentes en espera (no arrancan hasta cerrar el bloque 0)

- [[goals/gates-al-ci/goal|gates-al-ci]] — CP-F-AB recortado: `test.C` en CI + baseline, re-medir 8/8, publicar.
- [[goals/contadores-cero/goal|contadores-cero]] — visto concedido; localizar rama, re-verificar, publicar.
- **Plan espacio SiteGround** — tareas 1–5 de `docs/superpowers/plans/2026-08-18-espacio-cuenta-siteground.md`.
- **Dropdown PS sobre selector de semana** — diagnóstico (`systematic-debugging`) del stacking en `/programacion-semanal`.
- **Higiene de coordinación** — sesiones zombi, `cas-log.*` de la raíz, triaje de goals.

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
