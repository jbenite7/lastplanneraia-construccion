---
tipo: trampa
estado: vigente
fecha: 2026-08-04
areas: [design-system]
fuente: scripts/design-system-audit.mjs, docs/design-system/exceptions.json, scripts/design-system-contracts.mjs, scripts/design-system-activation-git.mjs
resumen: "Subir version.json no es editar un número: 38 excepciones de exceptions.json vencen en 1.1.0, los manifiestos exigen designSystemVersion sincronizado y dos gates comprueban 1.0.0 literal"
---
# Subir la versión del design system cobra deudas, no solo cambia un número

Se intentó subir `version.json` de `1.0.0` a `1.1.0` para acompañar una entrada nueva del
changelog, y la suite estática se puso en rojo por tres frentes a la vez:

1. **Excepciones con vencimiento por versión.** `scripts/design-system-audit.mjs:167-170` compara
   `expiresAtVersion` de cada excepción de `docs/design-system/exceptions.json` contra la versión
   viva con `compareSemVer(...) >= 0`. Hay **38 excepciones que vencen en `1.1.0`**: declarar esa
   versión las hace exigibles y el gate `audit` falla. (Ojo: no es `a11y-exceptions.json`, que usa
   `expiresAt` por fecha; el vencimiento por versión vive en `exceptions.json`.)
2. **Manifiestos sincronizados.** `design-system-contracts.mjs` exige que `designSystemVersion`
   de ~10 JSON (`component-catalog`, `stable-api-1.0.0`, `ui-groups-inventory`, `state-semantics`,
   `vendors`, `legacy-aliases`, `manifests/inventory`, `closeout-evidence`, …) sea **igual** al de
   `version.json`.
3. **Gates con `1.0.0` literal.** `design-system-closeout-contract.mjs:121-124` y
   `design-system-activation-git.mjs:55` comprueban `version === '1.0.0'` para decidir si la
   activación existe; con otra versión declaran el sistema no activado.

**Cómo no caer.** Un bump de versión es un cierre de ciclo completo: pagar (o re-vencer a
conciencia) las excepciones que expiran, sincronizar los manifiestos y decidir qué significan los
gates de activación para versiones > 1.0.0. Mientras eso no se haga, los cambios contractuales se
anotan en el changelog bajo «Sin publicar (candidato a 1.1.0)», como quedó el 2026-08-04.

Vecina: [[changelog-ds-encabeza-version-vieja]] — la misma pareja changelog/version.json, en la
dirección contraria. Mapa del área: [[design-system]].
