---
capa: fuente
tipo: referencia
estado: vigente
fecha: 2026-08-03
areas: [proceso]
tags: [proyecto, generado]
fuente: docs/superpowers
resumen: "Catálogo del trabajo fechado: cada spec de diseño con el plan que la ejecutó, por mes, incluido lo archivado"
project: lps-aia
type: plan-inventory
status: activo
updated: 2026-08-24
---
# Registro de trabajo

Cada cambio de peso de este repositorio deja dos papeles: una **spec** que decide qué se hace y por
qué, y un **plan** que dice cómo. Viven en `docs/superpowers/` y, cuando el trabajo terminó y nadie
los cita, en `docs/archive/superpowers/`. Son historia, no contrato: para saber qué manda hoy,
[[index|el índice]] y los mapas por área.

Esta página existe porque esos papeles estaban en el disco y no había forma de saber qué existía
sin listar un directorio. Aquí se ve la genealogía completa —qué se decidió, con qué plan se
ejecutó, y si ya está archivado— sin escribir a mano un enlace por documento.

**Cómo se lee.** «Archivado: sí» significa que el trabajo terminó y sus dos mitades se movieron a
`docs/archive/superpowers/`; se mueven solo los que ningún otro archivo cita, así que un trabajo sin
archivar puede estar igual de cerrado y seguir donde estaba porque alguien lo nombra. Un trabajo con
una sola mitad es normal: no todo lo que se diseña se ejecuta, y algunos planes son anteriores a la
costumbre de escribir la spec.

## Catálogo

Lo de abajo lo genera `scripts/wiki-registro.mjs` emparejando spec y plan por su slug. **No lo
edites a mano:** se sobrescribe en cada regeneración. Todo lo de fuera de los marcadores sí es tuyo.

```bash
node scripts/wiki-registro.mjs              # comprueba si la zona quedó desfasada
node scripts/wiki-registro.mjs --escribir   # la actualiza
```

<!-- generado:inicio -->

_131 trabajos · 52 con spec y plan emparejados · 20 archivados en `docs/archive/superpowers/`. Generado por `scripts/wiki-registro.mjs`._

### agosto de 2026

| Trabajo | Documentos | Archivado |
|---|---|---|
| Estado consolidado del repositorio — la spec única | [[docs/superpowers/specs/2026-08-24-estado-consolidado-del-repo-design|spec]] | — |
| P1 · Desagüe y consolidación de ramas | [[docs/superpowers/plans/2026-08-24-p1-desague-y-consolidacion|plan]] | — |
| P2 · El CI en verde y los presupuestos | [[docs/superpowers/plans/2026-08-24-p2-ci-en-verde-y-presupuestos|plan]] | — |
| P3 · Programa Design System · DS-F1 → DS-F3 | [[docs/superpowers/plans/2026-08-24-p3-design-system-contrato-y-control|plan]] | — |
| P4 · Móvil y tema claro · MO-F2b → MO-F3 | [[docs/superpowers/plans/2026-08-24-p4-movil-y-tema-claro|plan]] | — |
| P5 · Cierre hasta producción · CP-F-C → CP-F-E | [[docs/superpowers/plans/2026-08-24-p5-cierre-hasta-produccion|plan]] | — |
| P6 · Higiene documental y de coordinación | [[docs/superpowers/plans/2026-08-24-p6-higiene-documental-y-coordinacion|plan]] | — |
| Pendientes del frente de tablas | [[docs/superpowers/specs/2026-08-24-pendientes-frente-tablas-design|spec]] · [[docs/superpowers/plans/2026-08-24-pendientes-frente-tablas|plan]] | — |
| Reparto de lienzos de la Torre de Control por rol | [[docs/superpowers/specs/2026-08-24-reparto-lienzos-por-rol-design|spec]] · [[docs/superpowers/plans/2026-08-24-reparto-lienzos-por-rol|plan]] | — |
| Control Tower · Fase 0 — Higiene de datos | [[docs/superpowers/plans/2026-08-20-control-tower-f0-higiene-datos|plan]] | — |
| Deuda del CI — diseño de eliminación | [[docs/superpowers/specs/2026-08-20-deuda-ci-design|spec]] | — |
| Deuda del CI · Frente 1 (G1+G3+G5) | [[docs/superpowers/plans/2026-08-20-deuda-ci-frente-1|plan]] | — |
| Deuda del CI · Frente 2 (G2 mínimo, cache de capa base) | [[docs/superpowers/plans/2026-08-20-deuda-ci-frente-2|plan]] | — |
| Habilitación en una columna — spec v2 | [[docs/superpowers/specs/2026-08-20-habilitacion-en-una-columna-design|spec]] · [[docs/superpowers/plans/2026-08-21-habilitacion-en-una-columna|plan]] | — |
| Interruptor del Control Tower desde /admin | [[docs/superpowers/specs/2026-08-20-interruptor-control-tower-admin-design|spec]] · [[docs/superpowers/plans/2026-08-20-interruptor-control-tower-admin|plan]] | — |
| Replanteo de coloreado de estados (dirección B) | [[docs/superpowers/plans/2026-08-20-replanteo-coloreado-estados|plan]] | — |
| Replanteo de la Control Tower — Diseño | [[docs/superpowers/specs/2026-08-20-replanteo-control-tower-design|spec]] | — |
| Apply del recálculo de estados — plan de ejecución | [[docs/superpowers/plans/2026-08-19-apply-recalculo-estados|plan]] | — |
| El coloreado en cascada por severidad — diseño del diagnóstico | [[docs/superpowers/specs/2026-08-19-bug-coloreado-severidad-design|spec]] · [[docs/superpowers/plans/2026-08-19-bug-coloreado-severidad|plan]] | — |
| DS-F0 · Auditoría total del design system — diseño | [[docs/superpowers/specs/2026-08-19-ds-f0-auditoria-total-design|spec]] · [[docs/superpowers/plans/2026-08-19-ds-f0-auditoria-total|plan]] | — |
| DS-F1a · La escala de estado: vocabulario y lectura — diseño | [[docs/superpowers/specs/2026-08-19-ds-f1a-estado-design|spec]] · [[docs/superpowers/plans/2026-08-19-ds-f1a-estado|plan]] | — |
| «Fuera de Ventana» en los dos calculadores | [[docs/superpowers/plans/2026-08-19-estados-fuera-de-ventana|plan]] | — |
| Estados, severidad y color — el contrato — diseño | [[docs/superpowers/specs/2026-08-19-estados-severidad-contrato-design|spec]] · [[docs/superpowers/plans/2026-08-19-estados-severidad-contrato|plan]] | — |
| La línea base contractual deja de deducirse — diseño | [[docs/superpowers/specs/2026-08-19-linea-base-contractual-design|spec]] · [[docs/superpowers/plans/2026-08-19-linea-base-contractual|plan]] | — |
| Migración de la columna Estado | [[docs/superpowers/plans/2026-08-19-migracion-estados|plan]] | — |
| Organizar la casa — el repo y sus sesiones | [[docs/superpowers/specs/2026-08-19-organizar-la-casa-design|spec]] | — |
| publicar.sh: el invariante es el montaje, no el nombre del proyecto — diseño | [[docs/superpowers/specs/2026-08-19-publicar-sh-invariante-de-montaje-design|spec]] | — |
| runtime-budgets al CI, recortado a andamio — diseño | [[docs/superpowers/specs/2026-08-19-runtime-budgets-al-ci-design|spec]] · [[docs/superpowers/plans/2026-08-19-runtime-budgets-al-ci|plan]] | — |
| Programación Semanal: el fondo pasa a matiz | [[docs/superpowers/plans/2026-08-19-semanal-fondo-por-matiz|plan]] | — |
| Espacio de la cuenta de SiteGround: dejar de guardar lo que git ya guarda | [[docs/superpowers/specs/2026-08-18-espacio-cuenta-siteground-design|spec]] · [[docs/superpowers/plans/2026-08-18-espacio-cuenta-siteground|plan]] | — |
| Wiki v2 — visual, etiquetada, misma metodología | [[docs/superpowers/specs/2026-08-18-wiki-v2-visual-design|spec]] · [[docs/superpowers/plans/2026-08-18-wiki-v2-visual|plan]] | — |
| Diseño: el fixture aislado alcanza para programacion-semanal-roles-phases | [[docs/superpowers/specs/2026-08-14-fixture-ci-semanal-roles-design|spec]] · [[docs/superpowers/plans/2026-08-14-fixture-ci-semanal-roles|plan]] | — |
| Menú flotante del shell por debajo de 1180 px | [[docs/superpowers/specs/2026-08-14-shell-menu-flotante-responsive-design|spec]] · [[docs/superpowers/plans/2026-08-14-shell-menu-flotante-responsive|plan]] | — |
| Tarjeta móvil E2-bis: plan de implementación | [[docs/superpowers/plans/2026-08-14-tarjeta-movil-e2bis|plan]] | — |
| F2a-2b-2 — Extracción de reglas, umbral único y montaje condicional: plan de implementación | [[docs/superpowers/plans/2026-08-13-f2a-2b-2-extraccion-umbral-y-montaje|plan]] | — |
| Ocultar Control Tower de la navegación, dejándolo accesible a Admin | [[docs/superpowers/specs/2026-08-13-ocultar-control-tower-design|spec]] · [[docs/superpowers/plans/2026-08-13-ocultar-control-tower|plan]] | — |
| Espejo de producción → local → pruebas (2026-08-12) | [[docs/superpowers/specs/2026-08-12-espejo-produccion-a-pruebas-design|spec]] | — |
| Los !important de .pdc-legend-item en buttons.css — spec | [[docs/superpowers/specs/2026-08-11-buttons-important-leyenda-design|spec]] · [[docs/superpowers/plans/2026-08-11-buttons-important-leyenda|plan]] | — |
| Plan de cierre hasta producción | [[docs/superpowers/plans/2026-08-11-cierre-hasta-produccion|plan]] | — |
| Ocultar las etiquetas contadoras que marcan cero — diseño | [[docs/superpowers/specs/2026-08-11-contadores-cero-design|spec]] · [[docs/superpowers/plans/2026-08-11-contadores-cero|plan]] | — |
| Retirar del contrato de estados el módulo fantasma programa-general-actualizar — spec | [[docs/superpowers/specs/2026-08-11-contrato-estados-modulo-fantasma-design|spec]] · [[docs/superpowers/plans/2026-08-11-contrato-estados-modulo-fantasma|plan]] | — |
| Frente 1 · Tanda 1C — Pulido visual, accesibilidad y texto: plan de implementación | [[docs/superpowers/plans/2026-08-11-frente-1c-pulido-a11y-y-texto|plan]] | — |
| La aserción de la marca del carril comprueba que se vea, no que declare un filtro | [[docs/archive/superpowers/specs/2026-08-11-marca-carril-visible-design|spec]] · [[docs/archive/superpowers/plans/2026-08-11-marca-carril-visible|plan]] | sí |
| PHPUnit incremental, conviviendo con la suite de scripts | [[docs/archive/superpowers/specs/2026-08-11-phpunit-incremental-design|spec]] · [[docs/archive/superpowers/plans/2026-08-11-phpunit-incremental|plan]] | sí |
| Plan de cierre hasta producción — diseño | [[docs/superpowers/specs/2026-08-11-plan-cierre-hasta-produccion-design|spec]] | — |
| Fijar la semana en la prueba visual de Programación Intermedia — diseño | [[docs/superpowers/specs/2026-08-11-semana-fija-visual-design|spec]] · [[docs/superpowers/plans/2026-08-11-semana-fija-visual|plan]] | — |
| Unificar los vocabularios de estado de la cascada — spec | [[docs/superpowers/specs/2026-08-11-vocabulario-estados-cascada-design|spec]] · [[docs/superpowers/plans/2026-08-11-vocabulario-estados-cascada|plan]] | — |
| Frente 0 — Higiene y decisiones: plan de implementación | [[docs/superpowers/plans/2026-08-10-frente-0-higiene-y-decisiones|plan]] | — |
| Frente 1 · Tanda 1A — Seguridad y permisos: plan de implementación | [[docs/superpowers/plans/2026-08-10-frente-1a-seguridad-y-permisos|plan]] | — |
| Frente 1 · Tanda 1B — La cascada LPS: plan de implementación | [[docs/superpowers/plans/2026-08-10-frente-1b-cascada-lps|plan]] | — |
| Programa de cierre de pendientes — diseño | [[docs/superpowers/specs/2026-08-10-programa-cierre-pendientes-design|spec]] | — |
| Runner para los tests PHP y su conexión al CI | [[docs/superpowers/specs/2026-08-10-runner-tests-php-design|spec]] · [[docs/archive/superpowers/plans/2026-08-10-runner-tests-php|plan]] | — |
| F2a-2b-1 — Red de pruebas sobre las reglas de habilitación: plan de implementación | [[docs/superpowers/plans/2026-08-08-f2a-2b-1-red-de-pruebas-habilitacion|plan]] | — |
| F1 — Destrabar el viewport móvil: plan de implementación | [[docs/superpowers/plans/2026-08-07-f1-destrabar-viewport-movil|plan]] | — |
| F2a-1 — Precondiciones de la evidencia móvil: plan de implementación | [[docs/superpowers/plans/2026-08-07-f2a-1-precondiciones-evidencia-movil|plan]] | — |
| F2a-2a — Deudas de arranque: plan de implementación | [[docs/superpowers/plans/2026-08-07-f2a-2a-deudas-de-arranque|plan]] | — |
| F2a — Precondiciones y piloto móvil (Programación Intermedia y Semanal) | [[docs/superpowers/specs/2026-08-07-f2a-piloto-movil-programacion-design|spec]] | — |
| Reapertura de móvil/tablet y tema claro — diseño | [[docs/superpowers/specs/2026-08-07-reapertura-movil-y-tema-claro-design|spec]] | — |
| Adopción del logo «Last Planner · línea Construcción» — Diseño | [[docs/superpowers/specs/2026-08-06-adopcion-logo-construccion-design|spec]] · [[docs/superpowers/plans/2026-08-06-adopcion-logo-construccion|plan]] | — |
| Cierre de los hallazgos de seguridad de la biblia de flujos — diseño | [[docs/superpowers/specs/2026-08-06-cierre-hallazgos-seguridad-biblia-design|spec]] · [[docs/superpowers/plans/2026-08-06-cierre-hallazgos-seguridad-biblia|plan]] | — |
| Plan de Compras: filtros de columna, buscadores rápidos y selects buscables | [[docs/superpowers/specs/2026-08-06-pdc-filtros-y-buscadores-design|spec]] · [[docs/superpowers/plans/2026-08-06-pdc-filtros-y-buscadores|plan]] | — |
| La biblia de flujos: describir, verificar y auditar el comportamiento de la app | [[docs/superpowers/specs/2026-08-04-biblia-de-flujos-design|spec]] | — |
| Biblia de flujos · Tanda T1 (transversal) | [[docs/superpowers/plans/2026-08-04-biblia-t1-transversal|plan]] | — |
| Biblia de flujos · Tanda T2 (cascada LPS) | [[docs/superpowers/plans/2026-08-04-biblia-t2-cascada-lps|plan]] | — |
| Biblia de flujos · Tanda T3 (PDC — Plan de Compras v2) | [[docs/superpowers/plans/2026-08-04-biblia-t3-pdc|plan]] | — |
| Biblia de flujos · Tanda T4 (soporte) | [[docs/superpowers/plans/2026-08-04-biblia-t4-soporte|plan]] | — |
| Biblia de flujos · Tanda T5 (lectura) | [[docs/superpowers/plans/2026-08-04-biblia-t5-lectura|plan]] | — |
| Spec — Campaña de cierre de dark mode: las 54 decisiones convertidas en trabajo | [[docs/superpowers/specs/2026-08-04-cierre-dark-mode-campana-decisiones-design|spec]] · [[docs/superpowers/plans/2026-08-04-cierre-dark-mode-campana-decisiones|plan]] | — |
| Cierre de la versión 1.1.0 del design system — Diseño | [[docs/superpowers/specs/2026-08-04-cierre-version-1-1-0-design-system-design|spec]] · [[docs/superpowers/plans/2026-08-04-cierre-version-1-1-0-design-system|plan]] | — |
| Sembrar los conceptos del design system en la wiki | [[docs/archive/superpowers/specs/2026-08-04-conceptos-design-system-en-la-wiki-design|spec]] | sí |
| La semana en sesión solo la escribe una navegación | [[docs/superpowers/plans/2026-08-04-semana-en-sesion-solo-por-navegacion|plan]] | — |
| Puerta de servicio de desarrollo para admin/ | [[docs/superpowers/specs/2026-08-03-admin-dev-door-design|spec]] | — |
| Arquitectura del proyecto en la wiki, generada desde el código | [[docs/superpowers/specs/2026-08-03-arquitectura-en-la-wiki-design|spec]] · [[docs/archive/superpowers/plans/2026-08-03-arquitectura-en-la-wiki|plan]] | — |
| Cierre de dark mode — diseño validado | [[docs/superpowers/specs/2026-08-03-cierre-dark-mode-design|spec]] | — |
| Cierre de dark mode | [[docs/superpowers/plans/2026-08-03-cierre-dark-mode-fases-0-3|plan]] | — |
| Pasada de lint sobre la wiki memoria/ | [[docs/superpowers/specs/2026-08-03-lint-wiki-memoria-design|spec]] · [[docs/archive/superpowers/plans/2026-08-03-lint-wiki-memoria|plan]] | — |
| Chip de estado de Programa General | [[docs/superpowers/plans/2026-08-03-pg-chip-de-estado|plan]] | — |
| Reparto del trabajo pendiente tras el saneamiento del goal de tablas | [[docs/superpowers/specs/2026-08-03-reparto-trabajo-pendiente-design|spec]] | — |
| Saneamiento de las deudas abiertas del goal de usabilidad | [[docs/superpowers/specs/2026-08-03-saneamiento-deudas-usabilidad-design|spec]] · [[docs/superpowers/plans/2026-08-03-saneamiento-deudas-usabilidad|plan]] | — |
| Usabilidad: altas y medias | [[docs/superpowers/plans/2026-08-03-usabilidad-altas-y-medias|plan]] | — |
| Cierre de los tres pendientes de la wiki memoria/ | [[docs/archive/superpowers/specs/2026-08-03-wiki-veracidad-y-grafo-design|spec]] · [[docs/archive/superpowers/plans/2026-08-03-wiki-veracidad-y-grafo|plan]] | sí |
| Wiki de proyecto en Obsidian (patrón LLM Wiki) | [[docs/archive/superpowers/specs/2026-08-02-obsidian-memoria-proyecto-design|spec]] | sí |
| Impeccable Audit & Refactor Design: Core LPS & Ops | [[docs/superpowers/specs/2026-08-01-ui-audit-core-lps-ops-design|spec]] | — |
| LPS Core & Ops UI Refactor Implementation Plan | [[docs/superpowers/plans/2026-08-01-ui-audit-core-lps-ops-plan|plan]] | — |

### julio de 2026

| Trabajo | Documentos | Archivado |
|---|---|---|
| Plan de Cierre de Diseño e Integración Impeccable | [[docs/superpowers/plans/2026-07-31-cierre-de-diseno-impeccable|plan]] | — |
| Auditoría Visual Canónica y Plan de Reparación End-to-End (DESIGN.md + *Refactoring UI*) | [[docs/superpowers/specs/2026-07-31-ui-audit-and-repair-plan-design|spec]] | — |
| Plan de Reparación UI End-to-End (Auditoría 10/10) | [[docs/archive/superpowers/plans/2026-07-31-ui-audit-repair-plan|plan]] | sí |
| Puerta de servicio de desarrollo (DevDoor) | [[docs/superpowers/specs/2026-07-30-dev-door-design|spec]] | — |
| Diseñó — Unificación de Shell, Layout y Design System | [[docs/superpowers/specs/2026-07-30-shell-layout-design-system-design|spec]] · [[docs/archive/superpowers/plans/2026-07-30-shell-layout-design-system|plan]] | — |
| PDC v2 — Los cuatro diferidos de A4.1 (configuración de pasos) — Design | [[docs/superpowers/specs/2026-07-29-a41-diferidos-configuracion-pasos-design|spec]] · [[docs/archive/superpowers/plans/2026-07-29-a41-diferidos-configuracion-pasos|plan]] | — |
| PDC v2 — Ayuda dentro de la aplicación — Design | [[docs/superpowers/specs/2026-07-29-ayuda-in-app-pdc-design|spec]] · [[docs/archive/superpowers/plans/2026-07-29-ayuda-in-app-pdc|plan]] | — |
| PDC v2 · Fase B2 (primera mitad) — Semáforos y look-ahead de contratación — Design | [[docs/superpowers/specs/2026-07-29-b2-semaforos-lookahead-design|spec]] | — |
| PDC v2 · B2 (primera mitad) — Vencimientos y semáforo del plan | [[docs/archive/superpowers/plans/2026-07-29-b2-vencimientos-lookahead|plan]] | sí |
| PDC v2 · Fase B3 — El plan de compras en la Torre de Control — Design | [[docs/superpowers/specs/2026-07-29-b3-torre-control-pdc-design|spec]] · [[docs/archive/superpowers/plans/2026-07-30-b3-torre-control-pdc|plan]] | — |
| PDC · Fase C1 — Retirar el PDC viejo, y qué hacer con su dark a medias — Design | [[docs/superpowers/specs/2026-07-29-c1-retiro-pdc-viejo-design|spec]] · [[docs/superpowers/plans/2026-08-04-c1-retiro-pdc-viejo|plan]] | — |
| PDC v2 — Cierre pre-lanzamiento: los pendientes que bloquean decir «verificado» — Design | [[docs/superpowers/specs/2026-07-29-cierre-prelanzamiento-pdc-design|spec]] | — |
| PDC v2 — Despliegue a producción — Design | [[docs/superpowers/specs/2026-07-29-despliegue-pdc-v2-produccion-design|spec]] | — |
| PDC v2 — Equipo alquilado vs equipo comprado — Design | [[docs/superpowers/specs/2026-07-29-equipo-alquilado-comprado-design|spec]] · [[docs/superpowers/plans/2026-07-29-equipo-alquilado-comprado|plan]] | — |
| PDC v2 — Flujo de caja: curva de desembolsos por mes — Design | [[docs/superpowers/specs/2026-07-29-flujo-caja-desembolsos-design|spec]] | — |
| PDC v2 — Informe de impacto al recargar el presupuesto — Design | [[docs/superpowers/specs/2026-07-29-impacto-reimport-presupuesto-design|spec]] | — |
| Impacto al recargar el presupuesto + tamiz y cifras honestas | [[docs/superpowers/plans/2026-07-29-impacto-reimport-y-tamiz-presupuesto|plan]] | — |
| PDC v2 · Fase B1 — Seguimiento al Plan de Compras — Design | [[docs/superpowers/specs/2026-07-29-pdc-b1-seguimiento-design|spec]] · [[docs/archive/superpowers/plans/2026-07-29-pdc-b1-seguimiento|plan]] | — |
| PDC v2 · Fase B2 (segunda mitad) — Re-matching al reprogramar — Design | [[docs/superpowers/specs/2026-07-29-rematching-reprogramacion-design|spec]] · [[docs/archive/superpowers/plans/2026-07-29-rematching-reprogramacion|plan]] | — |
| Retiro del modo legacy (USE_GLOBAL_TABLES=false / tablas zleg_*) | [[docs/archive/superpowers/specs/2026-07-29-retiro-modo-legacy-design|spec]] | sí |
| PDC v2 — Subpaquetes: del paquete de preconstrucción al contrato real de la obra — Design | [[docs/superpowers/specs/2026-07-29-subpaquetes-obra-design|spec]] | — |
| PDC v2 — El presupuesto se explica solo: tamiz y cifras honestas — Design | [[docs/superpowers/specs/2026-07-29-tamiz-presupuesto-design|spec]] | — |
| Unificar plan-de-compras dentro de lastplanneraia-construccion — diseño | [[docs/superpowers/specs/2026-07-29-unificacion-repos-design|spec]] · [[docs/archive/superpowers/plans/2026-07-29-unificacion-repos|plan]] | — |
| A4.1 — Pasos del proceso de contratación configurables por proyecto | [[docs/archive/superpowers/specs/2026-07-28-a41-pasos-configurables-design|spec]] · [[docs/archive/superpowers/plans/2026-07-28-a41-pasos-configurables|plan]] | sí |
| Adoptar los tonos de PDC y el punto de nivel en todos los chips | [[docs/superpowers/plans/2026-07-28-chips-tonos-pdc-y-punto-de-nivel|plan]] | — |
| Invertir la paleta de estado del design system a oscuro | [[docs/archive/superpowers/specs/2026-07-28-paleta-estado-oscura-design|spec]] · [[docs/superpowers/plans/2026-07-28-paleta-estado-oscura|plan]] | — |
| PDC A4 → preparar B1: upsert de pasos y responsable como usuario | [[docs/archive/superpowers/plans/2026-07-28-pdc-preparar-b1|plan]] | sí |
| Responsable de paquete como usuario | [[docs/archive/superpowers/plans/2026-07-28-pdc-responsable-usuario|plan]] | sí |
| Responsable de paquete: de texto libre a usuario del proyecto | [[docs/superpowers/specs/2026-07-28-responsable-usuario-proyecto-design|spec]] · [[docs/archive/superpowers/plans/2026-07-28-responsable-usuario-proyecto|plan]] | — |
| A4 · El plan de compras con fechas — diseño | [[docs/archive/superpowers/specs/2026-07-27-a4-plan-fechas-design|spec]] · [[docs/archive/superpowers/plans/2026-07-27-a4-plan-fechas|plan]] | sí |
| Control Tower en el shell dark — diseño | [[docs/archive/superpowers/specs/2026-07-24-control-tower-shell-dark-design|spec]] · [[docs/superpowers/plans/2026-07-24-control-tower-shell-dark|plan]] | — |
| Fase A1.5: Visor del Presupuesto | [[docs/archive/superpowers/plans/2026-07-23-a15-visor-presupuesto|plan]] | sí |
| Diseño: Fase A1.6 — Comparativo de versiones del presupuesto | [[docs/superpowers/specs/2026-07-23-a16-comparativo-versiones-design|spec]] · [[docs/archive/superpowers/plans/2026-07-23-a16-comparativo-versiones|plan]] | — |
| Diseño: Fase A1.7 — Versionamiento inteligente del importador | [[docs/superpowers/specs/2026-07-23-a17-versionamiento-inteligente-design|spec]] · [[docs/archive/superpowers/plans/2026-07-23-a17-versionamiento-inteligente|plan]] | — |
| Fase A2: Maestro de Insumos | [[docs/archive/superpowers/plans/2026-07-23-a2-maestro-insumos|plan]] | sí |
| Diseño: Fase A2.5 — Importador del maestro SINCO | [[docs/superpowers/specs/2026-07-23-a25-importador-maestro-sinco-design|spec]] · [[docs/archive/superpowers/plans/2026-07-23-a25-importador-maestro-sinco|plan]] | — |
| Diseño: Fase A3 — Paquetes de contratación + asistente de empaquetamiento | [[docs/superpowers/specs/2026-07-23-a3-paquetes-contratacion-design|spec]] · [[docs/archive/superpowers/plans/2026-07-23-a3-paquetes-contratacion|plan]] | — |
| CSRF en endpoints legacy de mutación de semanas | [[docs/archive/superpowers/plans/2026-07-23-csrf-endpoints-semanas|plan]] | sí |
| Follow-ups del review final A2 (maestro de insumos) | [[docs/archive/superpowers/plans/2026-07-23-pdc-a2-followups|plan]] | sí |
| Diseño: Fase A1 — Importador de presupuesto | [[docs/archive/superpowers/specs/2026-07-22-a1-importador-presupuesto-design|spec]] · [[docs/archive/superpowers/plans/2026-07-22-a1-importador-presupuesto|plan]] | sí |
| Fundación PDC v2 (isla React en lps-aia) | [[docs/archive/superpowers/plans/2026-07-22-fundacion-pdc-v2|plan]] | sí |
| Colapsado del sidebar como primitiva canónica adoptada en el laboratorio | [[docs/superpowers/specs/2026-07-22-lab-colapsado-primitiva|spec]] · [[docs/superpowers/plans/2026-07-22-lab-colapsado-primitiva|plan]] | — |
| Roadmap PDC v2 — producto en 2 submódulos y fases de desarrollo | [[docs/superpowers/plans/2026-07-22-roadmap-pdc-v2|plan]] | — |
| Semanas del Proyecto en el sidebar canónico: flyout de gestión con crear/eliminar | [[docs/archive/superpowers/specs/2026-07-22-semanas-sidebar-design|spec]] | sí |
| Flyout "Semanas del Proyecto" con crear/eliminar | [[docs/archive/superpowers/plans/2026-07-22-semanas-sidebar-flyout|plan]] | sí |
| Diseño: stack del módulo Plan de Compras (PDC v2) | [[docs/superpowers/specs/2026-07-21-stack-plan-de-compras-design|spec]] | — |
| Sidebar canónico del laboratorio | [[docs/superpowers/plans/2026-07-20-sidebar-canonico-laboratorio|plan]] | — |

<!-- generado:fin -->

## Estado real de las specs — auditoría del 2026-08-20, con delta al 2026-08-24

El catálogo de arriba dice qué existe; esto dice **qué está hecho de verdad**. Las 61 specs
vigentes de entonces se verificaron contra el código y los documentos actuales — **no contra
casillas de verificación, que en este repo no miden avance** (la regla y su medición, en
[[TASKS]] y en `AGENTS.md` §Verificación). El detalle completo —evidencia por spec y cada
pendiente con el plan que lo cierra— está en
[[docs/superpowers/reports/2026-08-20-auditoria-estado-specs|el informe de la auditoría]].

**Corte del 2026-08-20:** 44 ejecutadas · 16 parciales · 1 pendiente · 0 derogadas · 12 cerradas
(archivadas).

**Corte del 2026-08-24:** **50 ejecutadas · 11 parciales · 0 pendientes · 0 derogadas · 12
cerradas.** Las mismas 61 specs, seis movidas de casilla en dos pasadas del mismo día; ninguna se
archivó, así que el bloque de cerradas no cambia. Esto es un **delta verificado, no una
re-auditoría**: las parciales que siguen así conservan el veredicto del 20 de agosto, comprobado
ítem por ítem contra `goals/*/goal.md`, `TASKS.md` y los cierres de P1 y P2.

Las tres primeras, con la evidencia que lo prueba:

| Spec | Antes | Ahora | Evidencia |
|---|---|---|---|
| `organizar-la-casa` | **pendiente** | ejecutada | Existe `goals/organizar-la-casa/`, los vistos ya no están en `.claude/vistos/` sino versionados en `decisiones/vistos/`, y `docs/coordinacion-sesiones.md` es la reescritura del 2026-08-20 con las siete reglas. Los **tres** criterios con que el informe la declaró «sin rastro de ejecución» están hoy invertidos |
| `runtime-budgets-al-ci` | parcial (`initializationMs` rojo, D-11) | ejecutada | `docs/design-system/closeout-evidence.json` pasa de 8/9 con un `blocked` a **9/9 `passed`**, con procedencia de corrida real. Cerrada vía P2 |
| `estados-severidad-contrato` | parcial (sin publicar; chocaba con `ds-f1a-estado`) | ejecutada | La colisión de 3 vs 4 niveles la resolvió Felipe a favor del contrato de 3 niveles; el frente se adaptó y publicó (`8418449a`), verificado en pantalla |

Tres más, movidas en la sesión de cierre de las specs huérfanas de la auditoría del 20 de agosto
(las tres decisiones de bajo esfuerzo que ningún plan P3-P6 recogía):

| Spec | Antes | Ahora | Evidencia |
|---|---|---|---|
| `stack-plan-de-compras` | parcial (brecha solo documental) | ejecutada | La brecha era por qué el módulo se unificó en `lps-aia` en vez del repo separado que la spec proponía. Ya estaba respondido en [[docs/superpowers/specs/2026-07-29-unificacion-repos-design]] (2026-07-29); solo faltaba citarlo en el `## Cierre` de la spec |
| `vocabulario-estados-cascada` | parcial (unificación en replanteo) | ejecutada | El trabajo mecánico (35→29 términos en Intermedia) está en el código, verificado línea por línea en `hot.js`, la vista y el test. Las cuatro decisiones encoladas (D-VOC-1..4) están resueltas desde el 2026-08-11 en `docs/decisiones-pendientes.md`, la cola canónica — el archivo `decisiones/vocabulario-estados-cascada.md` que sugería "en replanteo" es una copia del 2026-08-18 que nunca se sincronizó con ese cierre. Pendiente real fuera de este frente: ejecutar D-VOC-4 (separar `Capítulo`) en frente propio |
| `wiki-v2-visual` | parcial (plugins por decisión) | ejecutada | Los plugins de comunidad se instalaron y verificaron en pantalla el 2026-08-20 (`2888ab77`), ya en `TASKS.md`; la pregunta que dejaba la spec abierta ya no aplica. Único pendiente real: grupos de color del grafo (`.obsidian/graph.json`, `colorGroups: []` verificado), que no se puede comprobar sin abrir Obsidian — Felipe decidió el 2026-08-24 dejarlo pendiente en vez de escribirlo sin verificación visual |

**Ya no hay ninguna spec pendiente.** Ese dato —«la única pendiente sin rastro»— era el más
llamativo del corte anterior y caducó a los cuatro días.

**Aviso de alcance:** hoy hay **69 specs vigentes**, no 61. Las 8 nuevas —entre ellas
`estado-consolidado-del-repo`, `pendientes-frente-tablas` y `reparto-lienzos-por-rol`— se
escribieron después del corte y **no están auditadas**. Auditarlas es trabajo propio, no un
renglón de este delta.

Las 11 parciales que quedan: `cierre-prelanzamiento-pdc`,
`despliegue-pdc-v2-produccion` (producción sin tocar, CP-F-E), `ui-audit-and-repair-plan` y
`ui-audit-core-lps-ops` (sin cierre formal; `/indicadores` sin evidencia), `cierre-dark-mode`
(fase 6 sustituida por DS-F0..F3), `reparto-trabajo-pendiente` (línea E sin cierre),
`f2a-piloto-movil-programacion` (manifiestos sin escenario móvil), `reapertura-movil-y-tema-claro`
(F2b/F3/F4), `programa-cierre-pendientes` (frentes 3–5), `plan-cierre-hasta-produccion`
(F-AB pausado, F-E) y `espacio-cuenta-siteground` (frentes C/D de servidor).

`stack-plan-de-compras`, `vocabulario-estados-cascada` y `wiki-v2-visual` se movieron a ejecutada
en esta misma pasada (ver tabla arriba); `estados-severidad-contrato` y `runtime-budgets-al-ci` ya
se habían movido en la primera tabla del corte y quedaron fuera de esta lista por error hasta
ahora — corregido aquí.

La única **pendiente** sin rastro de ejecución: `organizar-la-casa` (2026-08-19).

El estado de ejecución vive aquí y en el informe; el frontmatter de las specs conserva su
vocabulario documental (`vigente`/`cerrado`) a propósito.
