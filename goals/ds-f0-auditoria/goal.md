<!-- cas:cita-textual — registro del frente: cita salidas y comandos tal como se midieron -->
# Frente: ds-f0-auditoria

## Fase del plan
Plan: ?
Fase: ?
Sha verificado: ?
Presupuesto: ?

Nota: el plan `docs/superpowers/plans/2026-08-19-ds-f0-auditoria-total.md` usa encabezados
`## Tanda N`, y `cas-frente.sh` solo reconoce `Task N` o `Fase N`, así que rechaza el enlace.
Se dejan `?` (ignorancia declarada) en vez de `-` (afirmar que no es fase de ningún plan, que
sería falso). Decisión encolada en `decisiones/ds-f0-auditoria-ejecutor.md`.

## Objetivo
Producir el inventario total del design system —módulo por módulo, objeto por objeto, variable por
variable, escenario por escenario— en `docs/design-system/auditoria/`, clasificado por severidad y
consultable por máquina. Es la fase que convierte «el DS no está bien definido, ni implementado, ni
controlado» en un inventario con el que DS-F1 pueda decidir el contrato.

## Condición de hecho
`docs/design-system/auditoria/` con el inventario completo por módulo y por severidad; cada hallazgo
con archivo, línea y por qué se clasificó así; los huecos que dependen del CI marcados como tales y
no rellenados. Cero cambios en código de producto.
Verificación: npm run test:design-system:static

## Posture
- **No arreglar nada.** Ni un hex, ni un `!important`, ni un token. Lo trivial se anota con su
  severidad y se sigue.
- **No tocar `docs/design-system/closeout-evidence.json`** ni ningún baseline: son de otro frente.
- **No decidir vocabulario ni escalas del producto.** Eso es DS-F1 y arranca con brainstorming.
- **Sin dependencias nuevas.**
- **No rellenar cifras que salgan de ejecutar los gates**: esperan a `runtime-budgets-al-ci`.

## Leer primero
- `DESIGN.md` y `docs/design-system/README.md` — el contrato vigente, que es lo que se audita.
- `docs/DESIGN-AUDIT.md` — F-4…F-9, la semilla.
- `docs/superpowers/decisiones-pendientes-2026-08-03.md` — las 48.
- `memoria/mapas/design-system.md` — las trampas ya puestas.
- `AGENTS.md` §Routing por tipo de cambio.

## Archivos declarados
docs/design-system/auditoria/**

## Contención
<!-- archivo · commits hoy · quién más lo declara -->

## Cadena de herramientas
<!-- ids del arsenal, máx 8, una línea de porqué cada uno -->
