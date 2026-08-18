<!-- cas:cita-textual — registro del frente: cita salidas y comandos tal como se midieron -->
# Frente: contrato-estados-modulo-fantasma

**Estado: CERRADO Y PUBLICADO** · `c25ac63d` en `origin/main` · 2026-08-11 · ejecutor `69ee4d31`

## Objetivo

Retirar de `docs/design-system/state-semantics.json` —la autoridad del vocabulario de estados— el
módulo `programa-general-actualizar`, que declaraba seis estados que ninguna pantalla pinta.

## Condición de hecho

`npm run test:design-system:static` → **`RC=0`, 8/8**, sobre `c25ac63d`, con el código de salida
leído sin tubería y **después** de integrar `origin/main` (`3cdfcd36`). Antes de integrar, `RC=0`
sobre `d58ca9d6`.

**Mutación ejecutada, en dos pasos:**

1. Reinsertado el módulo → `AssertionError: 13 !== 12` en `tests/design-system/states-feedback.test.mjs:61`,
   una sola prueba y exactamente la aserción endurecida. El cambio muerde.
2. Reinsertado el módulo **con el suelo antiguo** (`assert.ok(length >= 13)` y el nombre de vuelta
   en la lista literal) → **`RC=0`, verde**. Lo anterior no mordía: el contrato admitía módulos
   fantasma sin que nada lo notara, y lo hizo durante un mes.

## Lo medido (sobre `44917bc1`)

- `grep -c "estado\|Estado" views/programa-general-actualizar/*.php` → `0`
- Las seis etiquetas en `public/js/modules/programa_actualizar/` → 0 coincidencias
- Única «Bloqueado» de la vista: `programaGeneralActualizar.view.php:303`, título de modal
- `hot_actualizar.js:717` (`Estado_Restricciones`): `type: "numeric"` con `pgPercentRenderer` — porcentaje
- Origen: `3a139499` (2026-07-15); ese mismo día la vista ya solo tenía el modal. Inventado, no caducado.

**Censo: 29 → 28**, un solo término (`Bloqueado`). Las otras cinco ya se contaban por
`programa-general`; el censo cuenta cadenas distintas, no entradas de contrato. Ninguna desaparece
de una pantalla: nunca estuvieron en una. El «seis» del encargo contaba entradas creyendo contar
términos, y queda corregido en el spec.

## Archivos tocados

`docs/design-system/state-semantics.json` · `docs/design-system/decisions.md` (DS-036) ·
`tests/design-system/states-feedback.test.mjs` · `docs/decisiones-pendientes.md` (`D-CEF-1`) ·
`docs/superpowers/specs/2026-08-11-contrato-estados-modulo-fantasma-design.md` ·
`docs/superpowers/plans/2026-08-11-contrato-estados-modulo-fantasma.md`

## Lo que este frente NO arregla

**Un módulo fantasma con el número correcto seguiría entrando.** Lo que ahora caza la suma no es el
contrato, es un censo escrito a mano en un test. La corrección estructural —exigir que cada módulo
declare su superficie— es `D-CEF-1` en `docs/decisiones-pendientes.md`, es del usuario, y no se
implementó ni se insinuó en el código.

## Decisiones

- Bloqueante, escalada y resuelta por la coordinadora: ajustar las dos aserciones de censo. Resolvió
  **igualdad, no suelo**. Medido antes de aplicarla: el suelo era redundante con la lista literal
  por un lado y ciego por el otro. El porqué queda en un comentario sobre la aserción.
- Encolada sin decidir: `D-CEF-1`.
- Observación fuera de alcance, informada y no tocada: `decisiones/` está en `.gitignore:404`; la
  coordinadora confirmó que es deliberado y que la cola que sobrevive es `docs/decisiones-pendientes.md`.

## Archivos de este goal

- [goal.md](goal.md) — este archivo
- [Estado de los goals](../../memoria/goals/estado.md)
