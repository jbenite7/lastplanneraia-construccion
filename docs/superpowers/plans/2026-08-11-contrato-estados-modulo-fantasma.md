# Retirar el módulo fantasma del contrato de estados — plan

- Frente: `contrato-estados-modulo-fantasma` · sesión ejecutora `69ee4d31` · sha de arranque `44917bc1`
- Spec: [2026-08-11-contrato-estados-modulo-fantasma-design.md](../specs/2026-08-11-contrato-estados-modulo-fantasma-design.md)

**Recuento objetivo de esta pasada: 29 → 28** (un solo término, `Bloqueado`; ver el matiz en el spec).

## Paso 0 — Gate bloqueante antes de editar

Escalar a la coordinadora, en un mensaje:

- el gate de este plan (capa contractual), y
- la decisión bloqueante: ajustar `states-feedback.test.mjs:55` (`>= 13` → `>= 12`) y `:57`
  (quitar `'programa-general-actualizar'` de la lista) es **alterar lo que una prueba mide**.

Mientras se espera, se adelanta el paso 4 (que no depende de la respuesta). No se toca el JSON.

## Paso 1 — Retirar la entrada del contrato

Archivo: `docs/design-system/state-semantics.json`. Quitar el objeto
`{"module": "programa-general-actualizar", "states": [...]}` completo de `moduleMappings`.
13 → 12 entradas. No se toca nada más del JSON.

## Paso 2 — Escribir el motivo donde se lea

Archivo: `docs/design-system/decisions.md`. Entrada fechada que registre: qué se quitó, la
medición que lo respalda (los seis comandos del spec, con sha), que entraron en `3a139499`
inventados, y que la corrección de fondo —exigir superficie declarada— queda encolada, no hecha.

El motivo **no cabe en el JSON**: el esquema es `additionalProperties: false` y solo admite `note`
dentro de un estado, no a nivel de módulo. Registrarlo en `decisions.md` no es un rodeo, es el
único sitio.

## Paso 3 — Ajustar los dos consumidores (solo si el paso 0 lo autoriza)

`tests/design-system/states-feedback.test.mjs`, líneas 55 y 57. Nada más: ningún otro test
consulta ese módulo en el contrato de estados.

## Paso 4 — Encolar la decisión de fondo

`docs/decisiones-pendientes.md`: si el contrato debe exigir superficie declarada por módulo, con
lo medido (el esquema actual, y que por eso nada podía detectarlo). Sin implementarla.

## Paso 5 — Mutación ejecutada

Volver a insertar la entrada retirada y correr `npm run test:design-system:static`, leyendo el
código de salida **sin tubería**. Se espera rojo en `states-feedback.test.mjs`. Se reporta **qué
aserción cae**; si cae otra distinta de la esperada, la esperada no vigilaba esto. Si no cae
ninguna, se reporta como hallazgo mayor: el contrato admite módulos fantasma sin que nada lo note.
Después, revertir la mutación y volver a verde.

## Paso 6 — Verificación y cierre

`npm run test:design-system:static`, con `$?` leído sin tubería. Luego el gate de cierre de nueve
pasos: commit atómico, `git fetch`, integrar, **re-verificar después de integrar**, pedir el visto
con el sha, publicar **ese** sha en comando aparte, confirmar sin `ahead`/`behind`, anotar.

## Lo que este plan no hace

No toca la vista, el CSS, el manifiesto ni las excepciones de `programa-general-actualizar`: el
módulo sigue existiendo como pantalla. Solo deja de declarar estados que no pinta.
