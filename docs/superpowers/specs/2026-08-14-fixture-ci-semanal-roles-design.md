---
capa: fuente
tipo: spec
estado: vigente
fecha: 2026-08-14
areas: [rbac, lps]
fuente: docs/superpowers/specs/2026-08-14-fixture-ci-semanal-roles-design.md
resumen: Cuatro casos de tests/browser/programacion-semanal-roles-phases.mjs quedaron con test.skip el 2026-08-13 porque no existe entorno donde puedan correr:
---

# Diseño: el fixture aislado alcanza para `programacion-semanal-roles-phases`

Fecha: 2026-08-14 · Estado: aprobado en sesión · Origen: cierre del 2026-08-13 (`8359fb36`)

## Problema

Cuatro casos de `tests/browser/programacion-semanal-roles-phases.mjs` quedaron con `test.skip` el
2026-08-13 porque **no existe entorno donde puedan correr**:

| | Dato que necesitan | Permiso de escritura |
|---|---|---|
| Stack de desarrollo | sí | **no** — `runSql` lo bloquea |
| Stack aislado de CI | **no** — fixture mínimo | sí |

`runSql` (`tests/browser/support/dbSnapshot.mjs`) enruta todo comando de base por
`isolatedComposeArgs` → `assertIsolatedComposeEnvironment`, que exige el stack aislado. Ese candado
existe para que un e2e no escriba sobre la base compartida y **no se relaja**. La salida es que el
fixture del stack que sí tiene permiso traiga el dato.

Los cuatro casos son: «avance móvil y API rechazan una actividad sin responsables», «API semanal
rechaza fase, CNC incompleta y semana suplantada», «rol R histórico solo puede calificar el
compromiso confirmado» y «API CNP no reprograma una semana confirmada».

## Alcance

Al inventariar el fixture apareció que el hueco es más ancho que esos cuatro casos: **el spec
completo no puede correr hoy en el stack aislado**. Y como el trabajo termina en un gate propio del
CI, «verde» tiene que significar los catorce. Lo que falta, medido contra
`database/fixtures/design-system-ci.sql`:

| Falta | Quién lo necesita |
|---|---|
| Semanas 1-4 de JMC (68) con `Semanal_Confirmada = 1`; la 5 sigue abierta | los tres casos de API: semana histórica (`Max_Semana - 2`) y fase de calificación |
| Filas de `programacion_semanal` en esas semanas con `Sub_Contratista`, `Responsable_AIA` y `Compromiso > 0` | los mismos tres, por precondición de `pickWeeklyRow` |
| Una fila con `Activa = 0` en una semana confirmada de JMC | el caso de CNP: `CnpApiController::list` filtra por `Activa = 0` |
| `test.R` como miembro de JMC — hoy solo está `test.A` (`project_members` id 9) | el caso histórico, que califica como rol R |
| El usuario `test.D` y su membresía en Da Porto (73) | uno de los cuatro casos de roles: `ROLE_CASES` incluye el rol D |
| Una semana de Da Porto **sin** actividades | «semana sin actividades no fabrica filas ni tarjetas»: el fixture siembra una fila en la semana 1, justo la que ese caso exige vacía |

**Fuera de alcance:** relajar el candado de mutación; tocar los datos que ya consumen los gates de
design-system o `full-app-flow`; y el proyecto 27 («Prueba»), que desaparece de este spec por la
decisión de abajo.

## Decisiones tomadas

1. **El caso de CNP se muda al proyecto 68.** Solo necesita una semana confirmada con una fila de
   CNP, y JMC va a tener semanas confirmadas de todos modos. Sembrar «Prueba» entero añadiría un
   cuarto proyecto que ninguna otra prueba usa.
2. **El fixture crece hacia atrás, nunca hacia adelante.** `tests/browser/full-operational-cycle.spec.mjs`
   corre un ciclo completo sobre JMC dando por buena la semana 5 como la abierta; subir `Max_Semana`
   le cambiaría la fase bajo los pies. Con las semanas 1-4 añadidas, `Max_Semana` sigue siendo 5, la
   histórica es la 3 y la primera de calificación es la 4.
3. **La semana vacía se añade, no se muda** (opción (a) de tres):
   - (a) **elegida** — una semana nueva de Da Porto sin filas, y el caso la deriva («la primera
     semana cuya lista viene vacía»). Funciona en los dos entornos y no toca la fila existente.
   - (b) mover la fila del fixture a una semana 2, como en desarrollo: más barato, pero cambia el
     dato que ya consumen otros gates — arreglar un caso rompiendo otros.
   - (c) dejar ese caso saltado en el aislado: barato, pero el gate nuevo nacería cubriendo 13 de 14
     con una excepción que nadie volverá a mirar.
4. **El spec se cablea a un paso propio del workflow.** Ampliar el fixture lo vuelve ejecutable, pero
   nada lo ejecutaría: sin gate, la cobertura queda disponible y no vigente.

## Forma del trabajo

Cinco unidades, en orden, cada una verificable sola:

1. **Sembrar JMC** en `design-system-ci.sql`: semanas 1-4 confirmadas, filas con responsables y
   compromiso, y una fila con `Activa = 0` en semana confirmada.
2. **Sembrar los accesos que faltan**: `test.R` en JMC; `test.D` con usuario y membresía en Da Porto.
3. **Semana vacía de Da Porto** por la opción (a), con el ajuste del caso para derivarla en vez de
   fijar la semana 1.
4. **Quitar los cuatro `test.skip`** y el andamio que sobre, conservando el candado intacto.
5. **Cablear el paso** en `.github/workflows/design-system.yml`, después de que la app aislada esté
   arriba, con las cuatro variables de consentimiento y recibo vía `scripts/design-system/gate-receipt.mjs`.

## Verificación

- Por unidad: los casos que esa unidad desbloquea, corridos en el stack aislado.
- Condición de hecho: `npx playwright test tests/browser/programacion-semanal-roles-phases.mjs
  --workers=1` con los 14 casos en verde **y sin ningún `skip`** dentro del stack aislado; en
  desarrollo, los cuatro que escriben siguen saltándose por el candado.
- `npm run test:design-system:static` verde después de **cada** unidad que toque el fixture, no solo
  al final.
- Sin regresión en `full-operational-cycle.spec.mjs` ni en los gates que ya consumen el fixture.

## Riesgos

- **El fixture es contrato versionado.** Está anclado por `CI_FIXTURE_SHA256` y vigilado por
  `scripts/design-system-ci-compose-contract.mjs`, `scripts/design-system-ci-preflight.mjs` y
  `tests/design-system/ci-preflight.test.mjs`. De ahí que la suite estática se corra por unidad.
- **Un gate más que puede ponerse rojo.** Es el precio de que la cobertura sea vigente; el spec
  tarda unos 25 s.
- **Efecto lateral en JMC.** Cualquier prueba que hoy dé por hecho que JMC tiene una sola semana
  cambia de terreno. Se revisa antes de sembrar, no después.

## Referencias

- `memoria/trampas/el-dato-esta-en-desarrollo-y-el-permiso-en-el-stack-aislado.md`
- `memoria/trampas/fijar-un-dato-de-la-base-en-un-test-lo-podre.md`
- `AGENTS.md` §Verificación y §Publicación
