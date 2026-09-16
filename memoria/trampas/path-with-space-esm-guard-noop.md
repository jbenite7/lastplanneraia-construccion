---
capa: wiki
tipo: trampa
estado: derogada
fecha: 2026-07-21
areas: [proceso]
fuente: memoria-claude
origen: path-with-space-esm-guard-noop
resumen: "Derogada: el repo ya no vive en una ruta con espacio. Con espacio, el guard ESM file://${argv[1]} es no-op; usar pathToFileURL"
---
> **Derogada el 2026-09-16 (pase de veracidad):** desde la mudanza del 2026-08-18 el repo vive en `/Users/felipebenitez/Developer/lps-aia`, sin espacios, así que en esta máquina la trampa ya no se dispara. El patrón sigue siendo frágil en cualquier ruta con espacio o caracteres codificables; `pathToFileURL` sigue siendo la forma correcta. Lo de abajo se conserva como historia.

El repositorio estaba en `/Volumes/Crucial X6/Developer/lps-aia` — la ruta contenía un **espacio** (`Crucial X6`).

Cualquier script `.mjs` que use el patrón `if (import.meta.url === \`file://${process.argv[1]}\`)` para detectar ejecución directa **falla silenciosamente como no-op** en esta máquina: `import.meta.url` viene percent-encoded (`file:///Volumes/Crucial%20X6/...`) pero `process.argv[1]` no. El bloque main nunca corre y el script sale con exit 0 sin hacer nada.

**Caso real (2026-07-21):** `scripts/design-system-consumer-contract.mjs` tenía ese guard → el gate estático del design system (`npm run test:design-system:static`) invocaba el contrato de consumidor pero éste no ejecutaba nada localmente. Corregido usando `import.meta.url === pathToFileURL(process.argv[1]).href` (import `{ pathToFileURL }` de `node:url`).

**Why:** en CI (Ubuntu, ruta sin espacio) el bug no se manifiesta, así que un gate puede parecer verde y en realidad no estar corriendo local. **How to apply:** en scripts `.mjs` nuevos o al revisar existentes, usar siempre `pathToFileURL(process.argv[1]).href` para el guard de ejecución directa, nunca la interpolación `file://${...}`. Ver también [[branch-preexisting-red-gates]].

**El mismo espacio muerde al importar Playwright desde un worktree:** Playwright vive solo en el
`node_modules` del checkout principal, así que hay que importarlo con URL absoluta —
`file:///Volumes/Crucial%20X6/Developer/lps-aia/node_modules/playwright/index.mjs` — con el espacio
codificado como `%20`.
