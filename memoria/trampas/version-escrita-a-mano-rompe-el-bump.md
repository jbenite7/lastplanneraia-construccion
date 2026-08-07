---
tipo: trampa
estado: vigente
fecha: 2026-08-07
areas: [design-system, qa]
fuente: tests/design-system/foundation.test.mjs, tests/design-system/release-governance.test.mjs, tests/design-system/accessibility.test.mjs, public/css/aia-design-system.css
resumen: "Al publicar 1.1.0 aparecieron tres deudas que ninguna lista censaba, todas la misma: la versión del design system escrita a mano en sitios que deberían leerla de version.json"
---
# La versión escrita a mano es lo que rompe el bump

[[subir-la-version-del-ds-cobra-deudas]] enumeraba tres frentes. Al publicar `1.1.0` el 2026-08-07
saltaron **tres más**, y los tres eran el mismo defecto: **la versión escrita como literal donde
debería leerse de `version.json`**.

- **El sello `?v=` de los `@import` locales.** `foundation.test.mjs` («local entrypoint imports share
  the published design-system version») exige que iguale la versión publicada. Son **85** entre
  `public/css` y `admin/public/css`, y hay que cambiarlos **todos a la vez**: el gate
  `entrypoint-partition` compara los imports del agregador con los de la partición, así que dejar
  unos en la versión vieja rompe la identidad que exige. (En runtime no se ve: el sello real lo
  reescribe `DesignSystemHeadComponent` con `filemtime`. El literal solo existe para el gate.)
- **Un tercer gate con `1.0.0` literal**, en `release-governance.test.mjs`, además de los dos que la
  otra trampa ya citaba.
- **Tres asserts de `accessibility.test.mjs`** que fijaban `'1.0.0'` a mano para comprobar que los
  contratos de a11y iban versionados.

**La cura, ya aplicada en los tres.** El patrón de activación vive una sola vez en
`ACTIVATED_VERSION_PATTERN` (`scripts/design-system-activation-git.mjs`) y lo importan el gate y sus
tests; los asserts de a11y comparan contra `version.json`. Ese cambio no es solo comodidad: un
assert contra `version.json` **detecta una desincronización real**, mientras que el literal solo se
rompía en cada bump sin comprobar nada. `entrypoint-partition.test.mjs` también se desancló, porque
construía su fixture buscando el import con el literal `?v=1.0.0`.

**Cómo no caer en 1.2.0.** Antes de tocar `version.json`, busca el literal de la versión viva en
`tests/` y `scripts/`, no solo en `docs/`. Lo que encuentres o se convierte en lectura de
`version.json`, o pasa a reusar `ACTIVATED_VERSION_PATTERN`. Un test que afirma una versión concreta
casi nunca quiere decir eso: quiere decir «sincronizada».

Mapa del área: [[design-system]]. Vecinas: [[subir-la-version-del-ds-cobra-deudas]],
[[changelog-ds-encabeza-version-vieja]], [[qa-y-gates]].
