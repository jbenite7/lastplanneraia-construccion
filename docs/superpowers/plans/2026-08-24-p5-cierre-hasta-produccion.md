---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-08-24
areas: [proceso, lps, pdc]
fuente: docs/superpowers/plans/2026-08-24-p5-cierre-hasta-produccion.md
resumen: "P5 · Lo que falta del programa de cierre hasta producción: CP-F-C (cada módulo declara dónde pinta sus estados) y la fase terminal CP-F-E, que no se ejecuta sin autorización explícita de Felipe"
---

# P5 · Cierre hasta producción · CP-F-C → CP-F-E

> **For agentic workers:** REQUIRED SUB-SKILL: `superpowers:executing-plans`.
> **La última fase de este plan NO se ejecuta.** Se prepara y se detiene.

**Spec:** [[docs/superpowers/specs/2026-08-24-estado-consolidado-del-repo-design]]
**Depende de:** P2 completo.
**Plan padre:** `docs/superpowers/plans/2026-08-11-cierre-hasta-produccion.md`.

**Goal:** dejar el repositorio en condición de desplegar, y **detenerse ahí**.

**Estado de las fases del programa:**

| Fase | Frente | Estado |
|---|---|---|
| CP-F0 · CI en verde | `ci-en-verde` | Lo ejecuta **P2** |
| CP-F-AB · Cablear los dos gates | `gates-al-ci` | Lo ejecuta **P2**, recortado |
| CP-F-C · Cada módulo declara dónde pinta sus estados | `superficie-de-estados` | **Aquí, Tarea 1** |
| CP-F-D | — | **RETIRADA** el 2026-08-12: su premisa estaba caducada, ya estaba hecha |
| CP-F-E · Despliegue | `despliegue` | **Aquí, Tarea 3 — no se ejecuta** |

---

## Tarea 1 — CP-F-C · La superficie de estados

**YA EJECUTADA el 2026-08-12 (D-CEF-1), antes de que esta tarea se escribiera.** Corregido
2026-08-24 al cerrar la spec [[docs/superpowers/specs/2026-08-11-plan-cierre-hasta-produccion-design]]:
`state-semantics.schema.json` exige `surface` en cada `moduleMappings` (`required: [module, surface,
states]`), los 10 módulos vivos la declaran, y `states-feedback.test.mjs:252-258` comprueba que la
ruta existe de verdad en `public/index.php`. Esta tarea no se ejecuta: ya está hecha. Ver el `##
Cierre` de esa spec para la evidencia completa.

~~Decisión de Felipe ya tomada: **opción (a), obligatoria**. Cada módulo declara dónde pinta sus
estados; no es opt-in.~~

~~- [ ] Censar los módulos contra la declaración existente~~
~~- [ ] Hacer la declaración obligatoria, con un guard que **falle por el efecto, no por la
      declaración**~~

**Trampa a evitar, medida tres veces en este repo:** «el guard que valida su declaración, no su
efecto». Es hermana de [[memoria/trampas/guard-de-texto-no-ve-el-parseo]] y de
[[memoria/trampas/guard-valida-declaracion-contra-si-misma]], y el caso nuevo está en el `## Cierre`
de [[goals/semanal-fondo-por-matiz/goal]]: la sonda de la fase Calificación **no forzaba la fase** y
la declaraba igual, porque comprobaba su propia sustitución de texto.

- [ ] **Escribir la ficha de esa trampa en `memoria/trampas/`** — le falta ficha propia y este es el
      frente que la usa

## Tarea 2 — Preparar el despliegue, sin desplegarlo

`CP-F-E` arrastra **~1.255 commits de retraso**. Prepararlo no es autorizarlo.

- [ ] Seguir `docs/siteground-deploy-routine.md` hasta el paso previo al `pull`: pruebas antes que
      producción, respaldo previo verificable, plan de restauración escrito
- [ ] Composer se ejecuta con **PHP 8.3**
- [ ] Dejar listo el smoke funcional del flujo afectado, sin correrlo contra producción
- [x] ~~Resolver el **drift residual** ya inventariado: el stash `pre-deploy-20260820-185447`
      (SmtpMailer, superado por `21243c7e` versionado) y **7 `.bak` de `indicadores.view.php`** del
      2026-07-23 en `public_html`. **Confirmar con Felipe antes de borrar** — una publicación
      aprobada no autoriza limpiar drift~~ — **YA RESUELTO, verificado en el servidor el
      2026-08-24:** no queda ningún `.bak` en el webroot de producción (`find` da 0) y
      `git status --porcelain` sale **vacío**: el árbol está limpio. **Quién los retiró y cuándo no
      se determinó** — se dice en vez de suponerlo; el candidato natural es el despliegue del
      2026-08-20. No se ejecutó nada bajo esta tarea: se midió y ya estaba hecha

## Tarea 3 — CP-F-E · Despliegue · NO EJECUTAR

**Esta tarea existe para estar nombrada, no para hacerse.** El despliegue a producción necesita
autorización propia y explícita de Felipe, siempre. Publicar en `main` no la concede.

Lo mismo aplica al **apply de `recalculo-estados` en producción**: el apply sobre desarrollo ya
corrió (`aa965bf5`, 2026-08-19 13:40) con 40.664 filas migradas y reconciliación exacta, acta en
`goals/apply-recalculo-estados/acta-del-apply.md`. Producción sigue sin tocar.

**Y cuando llegue la autorización, la lección del apply de desarrollo manda:** el respaldo probado
horas antes **ya no cubría la base** — 8 filas nuevas sin respaldo. El respaldo se rehace y se
prueba la restauración **inmediatamente antes**, no la víspera. Durante la migración la base de dev
se congela entera para terceros: ni escrituras ni mediciones (regla 6 de
[[docs/coordinacion-sesiones]]).

---

## Condición de hecho

CP-F-C cerrada con su guard midiendo el efecto; el despliegue preparado, con respaldo probado y plan
de restauración escrito; y **CP-F-E sin ejecutar**, esperando la palabra de Felipe.
