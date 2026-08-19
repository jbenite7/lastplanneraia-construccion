---
capa: fuente
tipo: referencia
estado: vigente
fecha: 2026-08-19
areas: [proceso]
fuente: IMPLEMENTATION_PLAN_INVENTORY.md
resumen: Índice con estado de los planes y specs de docs/superpowers/ — propuesto, en curso, ejecutado o abandonado. No duplica contenido.
---

# Inventario de planes

Índice de los 88 planes/specs de `docs/superpowers/specs/` y `docs/superpowers/plans/` (patrón
`YYYY-MM-DD-slug[-design].md`; spec = diseño, plan = ejecución del mismo slug). No duplica
contenido: solo estado y enlace. Trabajo del enjambre activo hoy: ver [[TASKS]].

**Método de inferencia (2026-08-19):** el estado de los ítems anteriores al 18-ago se infirió por
fecha y por el hecho de que [[ROADMAP]]/[[CHANGELOG]] documentan una historia continua de entregas
sin huecos hasta hoy — **no se abrió cada archivo uno por uno**. `ejecutado (inferido)` significa
eso: alta confianza, no verificación línea a línea. `revisar` marca los que mencionan textualmente
alguna forma de cancelación/no-ejecución (`grep -l "abandonad\|se descarta\|no se hizo\|cancelad"`)
y necesitan una lectura real antes de confiar en su estado. `en curso` y el `ejecutado` sin
calificar son los 6 frentes de hoy (2026-08-19), verificados contra `goal.md`/git log en este mismo
bootstrap.

| Plan | Estado | Spec / Plan |
|---|---|---|
| 2026-07-20-sidebar-canonico-laboratorio | ejecutado (inferido) | [[docs/superpowers/plans/2026-07-20-sidebar-canonico-laboratorio]] |
| 2026-07-21-stack-plan-de-compras | ejecutado (inferido) | [[docs/superpowers/specs/2026-07-21-stack-plan-de-compras-design]] |
| 2026-07-22-lab-colapsado-primitiva | ejecutado (inferido) | [[docs/superpowers/specs/2026-07-22-lab-colapsado-primitiva]] · [[docs/superpowers/plans/2026-07-22-lab-colapsado-primitiva]] |
| 2026-07-22-roadmap-pdc-v2 | ejecutado (inferido) | [[docs/superpowers/plans/2026-07-22-roadmap-pdc-v2]] |
| 2026-07-23-a16-comparativo-versiones | ejecutado (inferido) | [[docs/superpowers/specs/2026-07-23-a16-comparativo-versiones-design]] |
| 2026-07-23-a17-versionamiento-inteligente | ejecutado (inferido) | [[docs/superpowers/specs/2026-07-23-a17-versionamiento-inteligente-design]] |
| 2026-07-23-a25-importador-maestro-sinco | ejecutado (inferido) | [[docs/superpowers/specs/2026-07-23-a25-importador-maestro-sinco-design]] |
| 2026-07-23-a3-paquetes-contratacion | ejecutado (inferido) | [[docs/superpowers/specs/2026-07-23-a3-paquetes-contratacion-design]] |
| 2026-07-24-control-tower-shell-dark | ejecutado (inferido) | [[docs/superpowers/plans/2026-07-24-control-tower-shell-dark]] |
| 2026-07-28-chips-tonos-pdc-y-punto-de-nivel | ejecutado (inferido) | [[docs/superpowers/plans/2026-07-28-chips-tonos-pdc-y-punto-de-nivel]] |
| 2026-07-28-paleta-estado-oscura | ejecutado (inferido) | [[docs/superpowers/plans/2026-07-28-paleta-estado-oscura]] |
| 2026-07-28-responsable-usuario-proyecto | ejecutado (inferido) | [[docs/superpowers/specs/2026-07-28-responsable-usuario-proyecto-design]] |
| 2026-07-29-a41-diferidos-configuracion-pasos | ejecutado (inferido) | [[docs/superpowers/specs/2026-07-29-a41-diferidos-configuracion-pasos-design]] |
| 2026-07-29-ayuda-in-app-pdc | ejecutado (inferido) | [[docs/superpowers/specs/2026-07-29-ayuda-in-app-pdc-design]] |
| 2026-07-29-b2-semaforos-lookahead | ejecutado (inferido) | [[docs/superpowers/specs/2026-07-29-b2-semaforos-lookahead-design]] |
| 2026-07-29-b3-torre-control-pdc | ejecutado (inferido) | [[docs/superpowers/specs/2026-07-29-b3-torre-control-pdc-design]] |
| 2026-07-29-c1-retiro-pdc-viejo | ejecutado (inferido) | [[docs/superpowers/specs/2026-07-29-c1-retiro-pdc-viejo-design]] |
| 2026-07-29-cierre-prelanzamiento-pdc | revisar | [[docs/superpowers/specs/2026-07-29-cierre-prelanzamiento-pdc-design]] |
| 2026-07-29-despliegue-pdc-v2-produccion | ejecutado (inferido) | [[docs/superpowers/specs/2026-07-29-despliegue-pdc-v2-produccion-design]] |
| 2026-07-29-equipo-alquilado-comprado | ejecutado (inferido) | [[docs/superpowers/specs/2026-07-29-equipo-alquilado-comprado-design]] · [[docs/superpowers/plans/2026-07-29-equipo-alquilado-comprado]] |
| 2026-07-29-flujo-caja-desembolsos | ejecutado (inferido) | [[docs/superpowers/specs/2026-07-29-flujo-caja-desembolsos-design]] |
| 2026-07-29-impacto-reimport-presupuesto | revisar | [[docs/superpowers/specs/2026-07-29-impacto-reimport-presupuesto-design]] |
| 2026-07-29-impacto-reimport-y-tamiz-presupuesto | revisar | [[docs/superpowers/plans/2026-07-29-impacto-reimport-y-tamiz-presupuesto]] |
| 2026-07-29-pdc-b1-seguimiento | ejecutado (inferido) | [[docs/superpowers/specs/2026-07-29-pdc-b1-seguimiento-design]] |
| 2026-07-29-rematching-reprogramacion | ejecutado (inferido) | [[docs/superpowers/specs/2026-07-29-rematching-reprogramacion-design]] |
| 2026-07-29-subpaquetes-obra | ejecutado (inferido) | [[docs/superpowers/specs/2026-07-29-subpaquetes-obra-design]] |
| 2026-07-29-tamiz-presupuesto | ejecutado (inferido) | [[docs/superpowers/specs/2026-07-29-tamiz-presupuesto-design]] |
| 2026-07-29-unificacion-repos | ejecutado (inferido) | [[docs/superpowers/specs/2026-07-29-unificacion-repos-design]] |
| 2026-07-30-dev-door | ejecutado (inferido) | [[docs/superpowers/specs/2026-07-30-dev-door-design]] |
| 2026-07-30-shell-layout-design-system | ejecutado (inferido) | [[docs/superpowers/specs/2026-07-30-shell-layout-design-system-design]] |
| 2026-07-31-cierre-de-diseno-impeccable | ejecutado (inferido) | [[docs/superpowers/plans/2026-07-31-cierre-de-diseno-impeccable]] |
| 2026-07-31-ui-audit-and-repair-plan | ejecutado (inferido) | [[docs/superpowers/specs/2026-07-31-ui-audit-and-repair-plan-design]] |
| 2026-08-01-ui-audit-core-lps-ops | ejecutado (inferido) | [[docs/superpowers/specs/2026-08-01-ui-audit-core-lps-ops-design]] |
| 2026-08-01-ui-audit-core-lps-ops-plan | ejecutado (inferido) | [[docs/superpowers/plans/2026-08-01-ui-audit-core-lps-ops-plan]] |
| 2026-08-03-admin-dev-door | ejecutado (inferido) | [[docs/superpowers/specs/2026-08-03-admin-dev-door-design]] |
| 2026-08-03-arquitectura-en-la-wiki | ejecutado (inferido) | [[docs/superpowers/specs/2026-08-03-arquitectura-en-la-wiki-design]] |
| 2026-08-03-cierre-dark-mode | ejecutado (inferido) | [[docs/superpowers/specs/2026-08-03-cierre-dark-mode-design]] |
| 2026-08-03-cierre-dark-mode-fases-0-3 | ejecutado (inferido) | [[docs/superpowers/plans/2026-08-03-cierre-dark-mode-fases-0-3]] |
| 2026-08-03-lint-wiki-memoria | ejecutado (inferido) | [[docs/superpowers/specs/2026-08-03-lint-wiki-memoria-design]] |
| 2026-08-03-pg-chip-de-estado | ejecutado (inferido) | [[docs/superpowers/plans/2026-08-03-pg-chip-de-estado]] |
| 2026-08-03-reparto-trabajo-pendiente | ejecutado (inferido) | [[docs/superpowers/specs/2026-08-03-reparto-trabajo-pendiente-design]] |
| 2026-08-03-saneamiento-deudas-usabilidad | ejecutado (inferido) | [[docs/superpowers/specs/2026-08-03-saneamiento-deudas-usabilidad-design]] · [[docs/superpowers/plans/2026-08-03-saneamiento-deudas-usabilidad]] |
| 2026-08-03-usabilidad-altas-y-medias | ejecutado (inferido) | [[docs/superpowers/plans/2026-08-03-usabilidad-altas-y-medias]] |
| 2026-08-04-biblia-de-flujos | ejecutado (inferido) | [[docs/superpowers/specs/2026-08-04-biblia-de-flujos-design]] |
| 2026-08-04-biblia-t1-transversal | ejecutado (inferido) | [[docs/superpowers/plans/2026-08-04-biblia-t1-transversal]] |
| 2026-08-04-biblia-t2-cascada-lps | ejecutado (inferido) | [[docs/superpowers/plans/2026-08-04-biblia-t2-cascada-lps]] |
| 2026-08-04-biblia-t3-pdc | ejecutado (inferido) | [[docs/superpowers/plans/2026-08-04-biblia-t3-pdc]] |
| 2026-08-04-biblia-t4-soporte | ejecutado (inferido) | [[docs/superpowers/plans/2026-08-04-biblia-t4-soporte]] |
| 2026-08-04-biblia-t5-lectura | ejecutado (inferido) | [[docs/superpowers/plans/2026-08-04-biblia-t5-lectura]] |
| 2026-08-04-c1-retiro-pdc-viejo | ejecutado (inferido) | [[docs/superpowers/plans/2026-08-04-c1-retiro-pdc-viejo]] |
| 2026-08-04-cierre-dark-mode-campana-decisiones | ejecutado (inferido) | [[docs/superpowers/specs/2026-08-04-cierre-dark-mode-campana-decisiones-design]] · [[docs/superpowers/plans/2026-08-04-cierre-dark-mode-campana-decisiones]] |
| 2026-08-04-cierre-version-1-1-0-design-system | ejecutado (inferido) | [[docs/superpowers/specs/2026-08-04-cierre-version-1-1-0-design-system-design]] · [[docs/superpowers/plans/2026-08-04-cierre-version-1-1-0-design-system]] |
| 2026-08-04-semana-en-sesion-solo-por-navegacion | ejecutado (inferido) | [[docs/superpowers/plans/2026-08-04-semana-en-sesion-solo-por-navegacion]] |
| 2026-08-06-adopcion-logo-construccion | ejecutado (inferido) | [[docs/superpowers/specs/2026-08-06-adopcion-logo-construccion-design]] · [[docs/superpowers/plans/2026-08-06-adopcion-logo-construccion]] |
| 2026-08-06-cierre-hallazgos-seguridad-biblia | ejecutado (inferido) | [[docs/superpowers/specs/2026-08-06-cierre-hallazgos-seguridad-biblia-design]] · [[docs/superpowers/plans/2026-08-06-cierre-hallazgos-seguridad-biblia]] |
| 2026-08-06-pdc-filtros-y-buscadores | ejecutado (inferido) | [[docs/superpowers/specs/2026-08-06-pdc-filtros-y-buscadores-design]] · [[docs/superpowers/plans/2026-08-06-pdc-filtros-y-buscadores]] |
| 2026-08-07-f1-destrabar-viewport-movil | ejecutado (inferido) | [[docs/superpowers/plans/2026-08-07-f1-destrabar-viewport-movil]] |
| 2026-08-07-f2a-1-precondiciones-evidencia-movil | ejecutado (inferido) | [[docs/superpowers/plans/2026-08-07-f2a-1-precondiciones-evidencia-movil]] |
| 2026-08-07-f2a-2a-deudas-de-arranque | ejecutado (inferido) | [[docs/superpowers/plans/2026-08-07-f2a-2a-deudas-de-arranque]] |
| 2026-08-07-f2a-piloto-movil-programacion | ejecutado (inferido) | [[docs/superpowers/specs/2026-08-07-f2a-piloto-movil-programacion-design]] |
| 2026-08-07-reapertura-movil-y-tema-claro | ejecutado (inferido) | [[docs/superpowers/specs/2026-08-07-reapertura-movil-y-tema-claro-design]] |
| 2026-08-08-f2a-2b-1-red-de-pruebas-habilitacion | ejecutado (inferido) | [[docs/superpowers/plans/2026-08-08-f2a-2b-1-red-de-pruebas-habilitacion]] |
| 2026-08-10-frente-0-higiene-y-decisiones | revisar | [[docs/superpowers/plans/2026-08-10-frente-0-higiene-y-decisiones]] |
| 2026-08-10-frente-1a-seguridad-y-permisos | ejecutado (inferido) | [[docs/superpowers/plans/2026-08-10-frente-1a-seguridad-y-permisos]] |
| 2026-08-10-frente-1b-cascada-lps | ejecutado (inferido) | [[docs/superpowers/plans/2026-08-10-frente-1b-cascada-lps]] |
| 2026-08-10-programa-cierre-pendientes | ejecutado (inferido) | [[docs/superpowers/specs/2026-08-10-programa-cierre-pendientes-design]] |
| 2026-08-10-runner-tests-php | ejecutado (inferido) | [[docs/superpowers/specs/2026-08-10-runner-tests-php-design]] |
| 2026-08-11-buttons-important-leyenda | ejecutado (inferido) | [[docs/superpowers/specs/2026-08-11-buttons-important-leyenda-design]] · [[docs/superpowers/plans/2026-08-11-buttons-important-leyenda]] |
| 2026-08-11-cierre-hasta-produccion | revisar | [[docs/superpowers/plans/2026-08-11-cierre-hasta-produccion]] |
| 2026-08-11-contadores-cero | ejecutado (inferido) | [[docs/superpowers/specs/2026-08-11-contadores-cero-design]] · [[docs/superpowers/plans/2026-08-11-contadores-cero]] |
| 2026-08-11-contrato-estados-modulo-fantasma | ejecutado (inferido) | [[docs/superpowers/specs/2026-08-11-contrato-estados-modulo-fantasma-design]] · [[docs/superpowers/plans/2026-08-11-contrato-estados-modulo-fantasma]] |
| 2026-08-11-frente-1c-pulido-a11y-y-texto | revisar | [[docs/superpowers/plans/2026-08-11-frente-1c-pulido-a11y-y-texto]] |
| 2026-08-11-plan-cierre-hasta-produccion | revisar | [[docs/superpowers/specs/2026-08-11-plan-cierre-hasta-produccion-design]] |
| 2026-08-11-semana-fija-visual | ejecutado (inferido) | [[docs/superpowers/specs/2026-08-11-semana-fija-visual-design]] · [[docs/superpowers/plans/2026-08-11-semana-fija-visual]] |
| 2026-08-11-vocabulario-estados-cascada | ejecutado (inferido) | [[docs/superpowers/specs/2026-08-11-vocabulario-estados-cascada-design]] · [[docs/superpowers/plans/2026-08-11-vocabulario-estados-cascada]] |
| 2026-08-12-espejo-produccion-a-pruebas | ejecutado (inferido) | [[docs/superpowers/specs/2026-08-12-espejo-produccion-a-pruebas-design]] |
| 2026-08-13-f2a-2b-2-extraccion-umbral-y-montaje | ejecutado (inferido) | [[docs/superpowers/plans/2026-08-13-f2a-2b-2-extraccion-umbral-y-montaje]] |
| 2026-08-13-ocultar-control-tower | ejecutado (inferido) | [[docs/superpowers/specs/2026-08-13-ocultar-control-tower-design]] · [[docs/superpowers/plans/2026-08-13-ocultar-control-tower]] |
| 2026-08-14-fixture-ci-semanal-roles | revisar | [[docs/superpowers/specs/2026-08-14-fixture-ci-semanal-roles-design]] · [[docs/superpowers/plans/2026-08-14-fixture-ci-semanal-roles]] |
| 2026-08-14-shell-menu-flotante-responsive | ejecutado (inferido) | [[docs/superpowers/specs/2026-08-14-shell-menu-flotante-responsive-design]] · [[docs/superpowers/plans/2026-08-14-shell-menu-flotante-responsive]] |
| 2026-08-14-tarjeta-movil-e2bis | ejecutado (inferido) | [[docs/superpowers/plans/2026-08-14-tarjeta-movil-e2bis]] |
| 2026-08-18-espacio-cuenta-siteground | revisar | [[docs/superpowers/specs/2026-08-18-espacio-cuenta-siteground-design]] · [[docs/superpowers/plans/2026-08-18-espacio-cuenta-siteground]] |
| 2026-08-18-wiki-v2-visual | en curso | [[docs/superpowers/specs/2026-08-18-wiki-v2-visual-design]] · [[docs/superpowers/plans/2026-08-18-wiki-v2-visual]] |
| 2026-08-19-bug-coloreado-severidad | en curso | [[docs/superpowers/specs/2026-08-19-bug-coloreado-severidad-design]] · [[docs/superpowers/plans/2026-08-19-bug-coloreado-severidad]] |
| 2026-08-19-ds-f0-auditoria-total | en curso | [[docs/superpowers/specs/2026-08-19-ds-f0-auditoria-total-design]] · [[docs/superpowers/plans/2026-08-19-ds-f0-auditoria-total]] |
| 2026-08-19-organizar-la-casa | en curso | [[docs/superpowers/specs/2026-08-19-organizar-la-casa-design]] |
| 2026-08-19-publicar-sh-invariante-de-montaje | ejecutado | [[docs/superpowers/specs/2026-08-19-publicar-sh-invariante-de-montaje-design]] |
| 2026-08-19-runtime-budgets-al-ci | en curso | [[docs/superpowers/specs/2026-08-19-runtime-budgets-al-ci-design]] · [[docs/superpowers/plans/2026-08-19-runtime-budgets-al-ci]] |

## Notas

- `2026-08-19-organizar-la-casa`: spec sin commitear todavía (`docs/superpowers/specs/2026-08-19-organizar-la-casa-design.md`
  está `??` en `git status`); su paso 2 (`docs/coordinacion-sesiones.md`) ya existe en el repo desde
  el 2026-08-10, su paso 1 (registros versionados en `decisiones/`) sigue untracked.
- `2026-08-19-publicar-sh-invariante-de-montaje` es el único de hoy confirmado `ejecutado` sin
  calificar: el commit `b334604e` en `main` coincide exactamente con lo que pide el spec.
- Los 9 marcados `revisar` no tienen evidencia de haberse completado y su texto menciona
  cancelación/no-ejecución en algún punto — antes de asumir `ejecutado`, ábrelos.
