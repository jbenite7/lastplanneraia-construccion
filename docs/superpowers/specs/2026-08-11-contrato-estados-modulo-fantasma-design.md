# Retirar del contrato de estados el módulo fantasma `programa-general-actualizar` — spec

- Frente: `contrato-estados-modulo-fantasma` · sesión ejecutora `69ee4d31`
- Fecha: 2026-08-11
- Plan: [2026-08-11-contrato-estados-modulo-fantasma.md](../plans/2026-08-11-contrato-estados-modulo-fantasma.md)
- Cola de decisiones: `decisiones/contrato-estados-modulo-fantasma-ejecutor.md`
- Antecedente: [2026-08-11-vocabulario-estados-cascada-design.md](2026-08-11-vocabulario-estados-cascada-design.md)

## El problema, en una frase

`docs/design-system/state-semantics.json` es la autoridad del vocabulario de estados, y declara
para el módulo `programa-general-actualizar` **seis estados que ninguna pantalla pinta**.

## Cómo se midió (sobre `44917bc1`, salida real de esta sesión)

| Comprobación | Comando | Resultado |
|---|---|---|
| El contrato los declara | lectura de `moduleMappings` con `node -e` | 6 estados: `Actividad Futura`, `En Curso`, `Terminada`, `Atrasada`, `Bloqueado`, `Sin Datos` |
| La vista no habla de estados | `grep -c "estado\|Estado" views/programa-general-actualizar/*.php` | **0** |
| El JS no trae las etiquetas | `grep -rn` de las seis sobre `public/js/modules/programa_actualizar/` | **0 coincidencias** |
| La única «Bloqueado» de la vista | `grep -rn "Bloqueado" views/programa-general-actualizar/` | `:303` → `<h5 class="aia-modal__title">Programa General Bloqueado</h5>` — **título de modal**, no estado |
| La única columna parecida | `hot_actualizar.js:717` | `data: 'Estado_Restricciones'`, `type: 'numeric'`, `renderer: pgPercentRenderer` → **es un porcentaje** |
| Nunca existieron | `git log -S'programa-general-actualizar' -- docs/design-system/state-semantics.json` | entran en `3a139499` (2026-07-15); `git show 3a139499:…view.php` ese mismo día solo tenía el título de modal |

**No es una entrada caducada: es inventada.** Cinco de las seis etiquetas son literalmente las de
`programa-general`, lo que apunta a copia.

## Por qué nada lo detectó

El contrato **no declara superficie**. Las claves de una entrada de `moduleMappings` son solo
`module` y `states` (`state-semantics.schema.json`, `additionalProperties: false`): ni ruta, ni
vista, ni selector. Sin superficie declarada no hay nada contra lo que contrastar, así que un
módulo puede declarar estados que no pinta y ningún gate puede notarlo.

## Qué se hace

Retirar la entrada `programa-general-actualizar` de `moduleMappings`, con el motivo escrito en
`docs/design-system/decisions.md` (el esquema no admite campos libres en el JSON, así que el
motivo no cabe dentro del contrato).

## Qué se lleva por delante

`tests/design-system/states-feedback.test.mjs` fija el censo a mano y **falla al retirar el módulo**:

- línea 55: `assert.ok(semantics.moduleMappings.length >= 13)` → pasaría a 12
- línea 57: la lista literal de módulos incluye `'programa-general-actualizar'`

Ningún otro consumidor lo nombra: `ops-state-contract.test.mjs` solo consulta
`programacion-intermedia`, `programacion-semanal` y `programa-general`;
`state-tint-ladder.test.mjs` y `ops-state-chip-hue.mjs` recorren lo que haya. Los demás usos del
literal `programa-general-actualizar` en `tests/`, `docs/design-system/manifests/` y
`state-tint-exceptions.json` se refieren a la **ruta, la vista y el CSS** del módulo —que siguen
existiendo y no se tocan—, no a su entrada en el contrato de estados.

Ajustar esas dos aserciones es alterar lo que una prueba mide: **decisión bloqueante**, escalada a
la coordinadora antes de tocar nada.

## El recuento, con su matiz

El censo de la cascada estaba en **29 términos**. La resta **no es seis**: cinco de las seis
etiquetas (`Actividad Futura`, `En Curso`, `Terminada`, `Atrasada`, `Sin Datos`) ya se contaban
una vez por `programa-general`, y el censo cuenta **cadenas distintas**, no entradas de contrato.
El único término que solo aportaba este módulo era **`Bloqueado`**.

**29 → 28.** Y con un matiz que conviene no perder: este término **no desaparece de ninguna
pantalla, porque nunca estuvo en una**. Desaparece del contrato. El vocabulario que la obra ve no
cambia en absoluto con este frente.

> **De dónde salía el «seis».** El encargo de este frente decía «seis de los 29», y la coordinadora
> reconoció el error al recibir la medición: **contó entradas del contrato creyendo que contaba
> términos distintos**. Se deja escrito porque el 6 ya viajó dentro del encargo y alguien podría
> heredarlo. Es el mismo defecto que este programa lleva días desmontando —una cifra declarada que
> no se corresponde con lo que nombra— y aquí apareció en el propio enunciado del arreglo.

## Qué NO se decide aquí

**Si el contrato debe exigir que cada módulo declare su superficie** —ruta, vista o selector— para
que un módulo inventado no pueda entrar. Es la corrección de fondo, cambia el esquema para los
trece módulos, y es del usuario. Queda anotada en `docs/decisiones-pendientes.md` con lo medido.
Este frente **no la implementa ni la insinúa en el código**.

## Condición de hecho

1. Este spec y su plan, con el gate del plan dado antes de editar el JSON.
2. `programa-general-actualizar` fuera de `moduleMappings`, con el motivo en `decisions.md`.
3. Recuento reportado: 29 → 28, con la aritmética a la vista.
4. **Mutación ejecutada**: volver a meter el módulo y mirar qué aserción cae —o reportar que no
   cae ninguna, que sería el hallazgo mayor.
5. Verde con salida real y código de salida leído sin tubería:
   `npm run test:design-system:static`.
6. Decisión de fondo encolada en `docs/decisiones-pendientes.md`.
