---
tipo: mapa
estado: vigente
fecha: 2026-08-02
areas: [design-system]
fuente: sesion
resumen: "Tokens, capas CSS, gates y baselines del design system — y las trampas que cuestan una vuelta entera"
---
# Mapa · Design system

## Qué manda

- **[[inspiracion-apple-en-dark-aia]] — premisa de diseño de alta prioridad (2026-08-03).**
  Principios de Apple (deferencia al contenido, jerarquía sin cromo, un solo acento por vista,
  controles discretos) expresados con los tokens y primitivas de AIA en dark. No se copia el
  aspecto de macOS/iOS. Su límite duro: libertad de forma, nunca de funcionalidad.
- [[DESIGN]] (raíz) — contrato de **consumo**: qué tokens y primitivas `aia-*` usar. Se lee antes
  de tocar cualquier superficie migrada.
- [[docs/design-system/README]] — la autoridad ejecutable. Junto a `contracts/`, `manifests/` y
  `decisions.md` forma la capa contractual, distinta de `docs/brand/`, que solo aporta insumos
  visuales.
- [[AGENTS]] — fija el alcance: dark es el tema por defecto y el viewport canónico de validación
  es 1180×820. El 2026-08-07 cayeron las prohibiciones de móvil, tablet y tema claro; lo que queda
  es un hecho del código, no un veto: `linen` se retiró en DS-030 y habría que reconstruirlo.

Los tres contratos que cuelgan de esa autoridad, cada uno con su ámbito:
[[docs/design-system/contracts/governance|gobierno global]] (el repositorio es la autoridad del
sistema), [[docs/design-system/contracts/module-migration|migración por módulo]] (un módulo por
sprint, sin primitivas locales) y
[[docs/design-system/contracts/sprint-review-close|revisión y cierre de sprint]] (el cierre es
verificable, y la aprobación humana bloquea).

Y las cuatro referencias que se consultan al implementar: [[docs/design-system/tokens|tokens]]
canónicos en sus dos capas, [[docs/design-system/components|componentes]] `aia-*`,
[[docs/design-system/decisions|decisiones DS-000…]] —una variante sigue `candidate` hasta que el
laboratorio la aprueba— y la [[docs/design-system/dark-palette|paleta oscura]], aprobada el
2026-07-12. El orden de migración de los módulos vive en
[[docs/design-system/migration|migration.md]], la accesibilidad revisada a mano en
[[docs/design-system/manual-accessibility-review|manual-accessibility-review.md]], y qué entró en
cada ciclo en el [[docs/design-system/CHANGELOG|changelog]] — **la versión viva la manda
`version.json`, no el encabezado del changelog**: ver
[[changelog-ds-encabeza-version-vieja]].

Insumo visual, **no contrato**: [[docs/STITCH]] explica cómo se conecta con Stitch y
[[docs/brand/aia_design_system_web_apple_inspired|el brief de marca]] aporta dirección; ninguno de
los dos manda sobre los tokens.

## Conceptos: el terreno antes que las minas

Siete fichas que explican **para qué existe** cada pieza del gobierno del sistema, verificadas
contra sus consumidores reales el 2026-08-04:

- [[dos-capas-de-tokens]] — por qué `--aia-*` (marca) y `--ds-*` (semántica) son capas distintas y
  qué consume un módulo.
- [[madurez-y-api-estable]] — qué separa `candidate` de `stable` y qué garantiza de verdad la
  versión estable. **La versión viva es `1.1.0` desde el 2026-08-07**; la activación fue un hito
  único cumplido en `1.0.0` y los gates ya no la vuelven a exigir en cada bump.
- [[baselines-y-presupuestos]] — congelar el desorden viejo vs. acotar lo nuevo, y qué gate vigila
  cada archivo.
- [[excepciones-registradas]] — la desviación tolerada con dueño, motivo y caducidad; lista
  cerrada.
- [[homologacion-y-familias]] — cómo una familia visual gana su aspecto por candidatos y
  aprobación humana trazable.
- [[manifiesto-de-modulo]] — la declaración jurada de cada módulo, y los dos selladores del cierre.
- [[inventarios-del-sistema]] — los cuatro censos y por qué dos son normativos y dos descriptivos.

## Restricción de alcance

El 2026-08-07 se retiraron las tres prohibiciones (móvil, tablet y tema claro) de los `.md`
normativos. Lo que queda es descriptivo: el viewport canónico de validación sigue siendo 1180×820
y dark sigue siendo el tema por defecto y único implementado.

El mismo día, **DS-032** (`docs/design-system/decisions.md:39`) llevó esa reapertura a los gates:
`390x844` vuelve a ser un viewport **soportado pero no requerido** —la cobertura obligatoria sigue
siendo `1180x820` y `1440x900`—, `design-system-contracts.mjs` distingue `SUPPORTED_VIEWPORTS` de
`REQUIRED_VIEWPORTS` y, por primera vez, valida los viewports declarados en `homologation.json`.
El candado de DS-031 se renombró a `tests/design-system/mobile-viewport-scope.test.mjs` y cambió de
intención: ya no prohíbe el ancho, exige evidencia para todo escenario declarado. **Evidencia móvil
todavía no hay** (es la fase F2 del goal).

## La cascada, que es donde duele

El orden de capas es `reset, vendor, theme, base, layout, components, utilities, module,
legacy-overrides`. Dos consecuencias que ya costaron horas:

- `styles.css` entra como `layer(module)` pero anida `@layer components` dentro, así que sus
  reglas quedan en `module.components` y ganan a reglas planas de `module` — ver
  [[css-layer-cascade]].
- Con `!important` el orden **se invierte**: solo una capa anterior puede ganar. Por eso los
  remapeos contra Bootstrap viven en `@layer reset` — ver [[admin-adminlte-adaptador]].

Y el reset legado pisa adaptadores: el spacing de adaptadores va en `@layer legacy-overrides`.

## Gates

Antes de dar nada por verde, lee [[branch-preexisting-red-gates]]: hay rojos preexistentes que no
son tuyos. Otras trampas del carril: [[audit-ve-color-en-comentarios]] (el audit lee texto crudo,
así que un hex citado en un comentario rompe el presupuesto),
[[manifiesto-ds-exige-golden]] (un manifiesto no se crea en seco),
[[visual-baselines-estado-real]] (las baselines del lab están rojas; mide el delta antes de
culparte), [[gate-estatico-no-ve-tokens-rotos]] (un gate que lee archivos da verde con un token
que apunta a una variable inexistente: los valores resueltos solo se ven en navegador) y
[[occurrence-no-resiste-insercion-entre-duplicados]] (el ancla por firma de
`state-token-exceptions.json` resiste inserciones salvo una: meter una regla nueva entre dos
copias duplicadas del mismo selector+token corre el `occurrence` declarado).

Y antes de citar una fuente como contrato, tres que afirman lo que el código no cumple:
[[comentario-de-token-afirma-uso-inexistente]] (ocho tokens rotulados para un uso que su
consumidor real nunca cableó), [[guard-valida-declaracion-contra-si-misma]] (el guard de «un
matiz por estado» comprueba el JSON contra el JSON y nunca abre el CSS: Programa General pinta
dos estados del mismo color con el test en verde) y
[[gate-solo-cuenta-elementos-no-los-lee]] (el gate de gobernanza de release solo comprueba
`evidence.length > 0`, nunca su contenido: 14 recibos de release resultaron ser stubs de dos
claves, medido el 2026-08-10).

Del laboratorio: [[lab-sticky-body-overflow]], [[lab-header-offset-medido]],
[[lab-desktop-layout-suite]].

Antes de borrar una hoja: [[navbar-css-consumidor-vivo]]. Antes de migrar un head a
`renderForModule`: [[drawer-en-handsontable-module]].

## Decisiones vigentes

[[goal-dark-mode-todos-modulos]] (F0–F6: `linen` se retira, `:root` pasa a dark, AdminLTE no se
migra) · [[sidebar-default-collapsed]] · [[compras-migrado-shell-sidebar]].

Estado vivo del dark mode: [[artefacto-estado-dark-mode]].

## Goals que trabajaron esta área

- [[goals/design-system-nucleo-gobernanza/goal|design-system-nucleo-gobernanza]] — el núcleo: fuente de verdad global, cascada determinista, gates continuos, con Programa General de piloto. **Sigue abierto**: medido el 2026-08-10 que sus 15 gates de cierre NO están sustancialmente verificados — solo 2 pasan de verdad. Ver [[gate-solo-cuenta-elementos-no-los-lee]] y [[condicion-de-hecho-caduca-sin-aviso]].
- [[goals/segmentacion-entrypoint-css/goal|segmentacion-entrypoint-css]] — núcleo sin vendors más adjuntos por vendor, para dejar de servir ~190 KB de CSS de grilla a superficies ligeras.
- [[goals/shell-layout-design-system/goal|shell-layout-design-system]] — el paraguas que agrupó shell, layout y sistema.
- [[goals/sidebar-todos-modulos/goal|sidebar-todos-modulos]] — el rollout del shell sidebar; sus `reports/` explican módulo por módulo.
- [[goals/cierre-dark-mode-y-tablas/goal|cierre-dark-mode-y-tablas]] y [[goals/dark-mode-todos-los-modulos/goal|dark-mode-todos-los-modulos]] — el dark mode, absorbido el segundo en el primero.
- [[goals/bi-control-tower-gemini/goal|bi-control-tower-gemini]] — dashboard de BI, **bloqueado por dependencia**: su condición de hecho pedía aprobar un tema (`linen`) retirado el 2026-07-25; corregida el 2026-08-10 para esperar el tema claro nuevo de F3 de `reapertura-movil-y-tema-claro`. Ver [[condicion-de-hecho-caduca-sin-aviso]].

Estado de todos en [[estado|Estado de los goals]].

## Vecinos

[[qa-y-gates]] para las suites · [[lps-dominio]] para las superficies que consumen todo esto.
