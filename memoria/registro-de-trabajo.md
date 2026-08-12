---
tipo: mapa
estado: vigente
fecha: 2026-08-03
areas: [proceso]
fuente: docs/superpowers
resumen: "Catálogo del trabajo fechado: cada spec de diseño con el plan que la ejecutó, por mes, incluido lo archivado"
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

_94 trabajos · 37 con spec y plan emparejados · 14 archivados en `docs/archive/superpowers/`. Generado por `scripts/wiki-registro.mjs`._

### agosto de 2026

| Trabajo | Documentos | Archivado |
|---|---|---|
| Los !important de .pdc-legend-item en buttons.css — spec | [[docs/superpowers/specs/2026-08-11-buttons-important-leyenda-design|spec]] · [[docs/superpowers/plans/2026-08-11-buttons-important-leyenda|plan]] | — |
| Plan de cierre hasta producción | [[docs/superpowers/plans/2026-08-11-cierre-hasta-produccion|plan]] | — |
| Ocultar las etiquetas contadoras que marcan cero — diseño | [[docs/superpowers/specs/2026-08-11-contadores-cero-design|spec]] · [[docs/superpowers/plans/2026-08-11-contadores-cero|plan]] | — |
| Retirar del contrato de estados el módulo fantasma programa-general-actualizar — spec | [[docs/superpowers/specs/2026-08-11-contrato-estados-modulo-fantasma-design|spec]] · [[docs/superpowers/plans/2026-08-11-contrato-estados-modulo-fantasma|plan]] | — |
| Frente 1 · Tanda 1C — Pulido visual, accesibilidad y texto: plan de implementación | [[docs/superpowers/plans/2026-08-11-frente-1c-pulido-a11y-y-texto|plan]] | — |
| La aserción de la marca del carril comprueba que se vea, no que declare un filtro | [[docs/superpowers/specs/2026-08-11-marca-carril-visible-design|spec]] · [[docs/superpowers/plans/2026-08-11-marca-carril-visible|plan]] | — |
| PHPUnit incremental, conviviendo con la suite de scripts | [[docs/superpowers/specs/2026-08-11-phpunit-incremental-design|spec]] · [[docs/superpowers/plans/2026-08-11-phpunit-incremental|plan]] | — |
| Plan de cierre hasta producción — diseño | [[docs/superpowers/specs/2026-08-11-plan-cierre-hasta-produccion-design|spec]] | — |
| Fijar la semana en la prueba visual de Programación Intermedia — diseño | [[docs/superpowers/specs/2026-08-11-semana-fija-visual-design|spec]] · [[docs/superpowers/plans/2026-08-11-semana-fija-visual|plan]] | — |
| Unificar los vocabularios de estado de la cascada — spec | [[docs/superpowers/specs/2026-08-11-vocabulario-estados-cascada-design|spec]] · [[docs/superpowers/plans/2026-08-11-vocabulario-estados-cascada|plan]] | — |
| Frente 0 — Higiene y decisiones: plan de implementación | [[docs/superpowers/plans/2026-08-10-frente-0-higiene-y-decisiones|plan]] | — |
| Frente 1 · Tanda 1A — Seguridad y permisos: plan de implementación | [[docs/superpowers/plans/2026-08-10-frente-1a-seguridad-y-permisos|plan]] | — |
| Frente 1 · Tanda 1B — La cascada LPS: plan de implementación | [[docs/superpowers/plans/2026-08-10-frente-1b-cascada-lps|plan]] | — |
| Programa de cierre de pendientes — diseño | [[docs/superpowers/specs/2026-08-10-programa-cierre-pendientes-design|spec]] | — |
| Runner para los tests PHP y su conexión al CI | [[docs/superpowers/specs/2026-08-10-runner-tests-php-design|spec]] · [[docs/superpowers/plans/2026-08-10-runner-tests-php|plan]] | — |
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
| Sembrar los conceptos del design system en la wiki | [[docs/superpowers/specs/2026-08-04-conceptos-design-system-en-la-wiki-design|spec]] | — |
| La semana en sesión solo la escribe una navegación | [[docs/superpowers/plans/2026-08-04-semana-en-sesion-solo-por-navegacion|plan]] | — |
| Puerta de servicio de desarrollo para admin/ | [[docs/superpowers/specs/2026-08-03-admin-dev-door-design|spec]] | — |
| Arquitectura del proyecto en la wiki, generada desde el código | [[docs/superpowers/specs/2026-08-03-arquitectura-en-la-wiki-design|spec]] · [[docs/superpowers/plans/2026-08-03-arquitectura-en-la-wiki|plan]] | — |
| Cierre de dark mode — diseño validado | [[docs/superpowers/specs/2026-08-03-cierre-dark-mode-design|spec]] | — |
| Cierre de dark mode | [[docs/superpowers/plans/2026-08-03-cierre-dark-mode-fases-0-3|plan]] | — |
| Pasada de lint sobre la wiki memoria/ | [[docs/superpowers/specs/2026-08-03-lint-wiki-memoria-design|spec]] · [[docs/superpowers/plans/2026-08-03-lint-wiki-memoria|plan]] | — |
| Chip de estado de Programa General | [[docs/superpowers/plans/2026-08-03-pg-chip-de-estado|plan]] | — |
| Reparto del trabajo pendiente tras el saneamiento del goal de tablas | [[docs/superpowers/specs/2026-08-03-reparto-trabajo-pendiente-design|spec]] | — |
| Saneamiento de las deudas abiertas del goal de usabilidad | [[docs/superpowers/specs/2026-08-03-saneamiento-deudas-usabilidad-design|spec]] · [[docs/superpowers/plans/2026-08-03-saneamiento-deudas-usabilidad|plan]] | — |
| Usabilidad: altas y medias | [[docs/superpowers/plans/2026-08-03-usabilidad-altas-y-medias|plan]] | — |
| Cierre de los tres pendientes de la wiki memoria/ | [[docs/superpowers/specs/2026-08-03-wiki-veracidad-y-grafo-design|spec]] · [[docs/superpowers/plans/2026-08-03-wiki-veracidad-y-grafo|plan]] | — |
| Wiki de proyecto en Obsidian (patrón LLM Wiki) | [[docs/superpowers/specs/2026-08-02-obsidian-memoria-proyecto-design|spec]] | — |
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
| Unificar plan-de-compras dentro de lastplanneraia-construccion — diseño | [[docs/superpowers/specs/2026-07-29-unificacion-repos-design|spec]] · [[docs/superpowers/plans/2026-07-29-unificacion-repos|plan]] | — |
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
| Diseño: Fase A1 — Importador de presupuesto | [[docs/superpowers/specs/2026-07-22-a1-importador-presupuesto-design|spec]] · [[docs/archive/superpowers/plans/2026-07-22-a1-importador-presupuesto|plan]] | — |
| Fundación PDC v2 (isla React en lps-aia) | [[docs/archive/superpowers/plans/2026-07-22-fundacion-pdc-v2|plan]] | sí |
| Colapsado del sidebar como primitiva canónica adoptada en el laboratorio | [[docs/superpowers/specs/2026-07-22-lab-colapsado-primitiva|spec]] · [[docs/superpowers/plans/2026-07-22-lab-colapsado-primitiva|plan]] | — |
| Roadmap PDC v2 — producto en 2 submódulos y fases de desarrollo | [[docs/superpowers/plans/2026-07-22-roadmap-pdc-v2|plan]] | — |
| Semanas del Proyecto en el sidebar canónico: flyout de gestión con crear/eliminar | [[docs/archive/superpowers/specs/2026-07-22-semanas-sidebar-design|spec]] | sí |
| Flyout "Semanas del Proyecto" con crear/eliminar | [[docs/archive/superpowers/plans/2026-07-22-semanas-sidebar-flyout|plan]] | sí |
| Diseño: stack del módulo Plan de Compras (PDC v2) | [[docs/superpowers/specs/2026-07-21-stack-plan-de-compras-design|spec]] | — |
| Sidebar canónico del laboratorio | [[docs/superpowers/plans/2026-07-20-sidebar-canonico-laboratorio|plan]] | — |

<!-- generado:fin -->
