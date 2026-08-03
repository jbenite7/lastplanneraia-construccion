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

- [[DESIGN]] (raíz) — contrato de **consumo**: qué tokens y primitivas `aia-*` usar. Se lee antes
  de tocar cualquier superficie migrada.
- [[docs/design-system/README]] — la autoridad ejecutable. Junto a `contracts/`, `manifests/` y
  `decisions.md` forma la capa contractual, distinta de `docs/brand/`, que solo aporta insumos
  visuales.
- [[AGENTS]] — fija el alcance: **desktop ≥1180 px y dark mode, nada más**. El viewport canónico
  de validación es 1180×820. No se trabaja móvil, tablet ni el tema `linen`, ni siquiera para
  generar evidencia.

## Restricción de alcance

Esto no es una preferencia, es una prohibición del repo. Si una petición pide móvil, tablet o
`linen`, hay que decirlo explícitamente y no hacer esa parte.

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
culparte) y [[gate-estatico-no-ve-tokens-rotos]] (un gate que lee archivos da verde con un token
que apunta a una variable inexistente: los valores resueltos solo se ven en navegador).

Y antes de citar un comentario de `tokens.css` como contrato:
[[comentario-de-token-afirma-uso-inexistente]] — ocho tokens llevan rotulado un uso que ningún
archivo del repositorio ejerce.

Del laboratorio: [[lab-sticky-body-overflow]], [[lab-header-offset-medido]],
[[lab-desktop-layout-suite]].

Antes de borrar una hoja: [[navbar-css-consumidor-vivo]]. Antes de migrar un head a
`renderForModule`: [[drawer-en-handsontable-module]].

## Decisiones vigentes

[[goal-dark-mode-todos-modulos]] (F0–F6: `linen` se retira, `:root` pasa a dark, AdminLTE no se
migra) · [[sidebar-default-collapsed]] · [[compras-migrado-shell-sidebar]].

Estado vivo del dark mode: [[artefacto-estado-dark-mode]].

## Goals que trabajaron esta área

- [[goals/design-system-nucleo-gobernanza/goal|design-system-nucleo-gobernanza]] — el núcleo: fuente de verdad global, cascada determinista, gates continuos, con Programa General de piloto. **Sigue abierto.**
- [[goals/segmentacion-entrypoint-css/goal|segmentacion-entrypoint-css]] — núcleo sin vendors más adjuntos por vendor, para dejar de servir ~190 KB de CSS de grilla a superficies ligeras.
- [[goals/shell-layout-design-system/goal|shell-layout-design-system]] — el paraguas que agrupó shell, layout y sistema.
- [[goals/sidebar-todos-modulos/goal|sidebar-todos-modulos]] — el rollout del shell sidebar; sus `reports/` explican módulo por módulo.
- [[goals/cierre-dark-mode-y-tablas/goal|cierre-dark-mode-y-tablas]] y [[goals/dark-mode-todos-los-modulos/goal|dark-mode-todos-los-modulos]] — el dark mode, absorbido el segundo en el primero.
- [[goals/bi-control-tower-gemini/goal|bi-control-tower-gemini]] — dashboard de BI, **bloqueado** esperando aprobación visual de sus 6 modos.

Estado de todos en [[estado|Estado de los goals]].

## Vecinos

[[qa-y-gates]] para las suites · [[lps-dominio]] para las superficies que consumen todo esto.
