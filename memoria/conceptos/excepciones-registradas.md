---
tipo: concepto
estado: vigente
fecha: 2026-08-04
areas: [design-system]
fuente: tests/design-system/state-token-pairing.test.mjs, tests/design-system/accessibility.test.mjs, scripts/design-system-contracts.mjs
resumen: "Una excepción registrada es una desviación tolerada con dueño, motivo y caducidad; los cuatro inventarios cierran la lista — lo que no está inventariado, falla"
---
# Excepciones registradas: la desviación con nombre y caducidad

Cuando una regla del design system no se puede cumplir todavía, el sistema no elige entre «romper
el gate» y «apagar la regla». Hay una tercera vía: **registrar la excepción**, con dueño, motivo y
condición de salida. La lista es cerrada: lo que no está inventariado, falla.

Los cuatro inventarios, cada uno con su gate verificado:

| Archivo | Qué tolera | Quién lo exige |
|---|---|---|
| `a11y-exceptions.json` | Un hallazgo axe concreto, identificado por fingerprint, con `owner`, `reason`, `milestone` y `expiresAt` | `tests/design-system/accessibility.test.mjs:176-222` |
| `state-token-exceptions.json` | Un «medio par» de tokens de estado (`-bg` sin `-text` o viceversa), con `kind` y `reason` de al menos 80 caracteres | `tests/design-system/state-token-pairing.test.mjs:17,37` |
| `state-tint-exceptions.json` | Un fondo `--ds-state-tint-*` sin color de texto en el mismo selector | `tests/design-system/state-tint-pairing.test.mjs:8` |
| `legacy-aliases.json` | Un selector legado vivo, mapeado a su componente canónico (`legacySelector` → `catalogId`) | `scripts/design-system-contracts.mjs:232-238` |

**Para qué existe el mecanismo.** Deja avanzar sin mentir: el gate sigue en verde, pero la deuda
queda contada, con un responsable y una fecha u versión en que revisitarla. Una excepción sin
`reason` sustancial o con el motivo genérico no pasa — el mínimo de 80 caracteres de
`state-token-pairing` existe justo para eso.

**La asimetría que hay que conocer:** `state-token-exceptions.json` ancla sus entradas por
**firma** (selector + token + `occurrence`), pero su hermano `state-tint-exceptions.json` sigue
anclado por **número de línea**, que se corre con cualquier inserción encima. Mismo dato, dos
robusteces distintas.

## Dónde se rompe esto en la práctica

- [[occurrence-no-resiste-insercion-entre-duplicados]] — el ancla por firma resiste inserciones,
  salvo insertar entre dos copias duplicadas del mismo selector+token.
- [[axe-incomplete-cuenta-como-violacion]] — el arnés aplana `incomplete` junto a `violations`, y
  en superficies translúcidas axe siempre devuelve `incomplete`: por eso hay excepciones con la
  medición real (compuesta con alfa) escrita dentro.

Mapa del área: [[design-system]].
