---
capa: wiki
tipo: decision
estado: vigente
fecha: 2026-08-06
areas: [design-system, qa]
fuente: sesion
resumen: "AdminLTE deja VIEW_OWNED_VENDORS y pasa a attach-adminlte.css con layer(vendor); las tres vistas de views/auth/ quedan sin una sola petición externa"
---
Las tres vistas de `views/auth/` (`/login`, `/password/forgot`, `/password/reset`) cargaban
AdminLTE 3.2 desde jsDelivr con un `<link>` **sin capa**. Una hoja de autor sin capa gana a
**todas** las capas en declaraciones normales (DS-006), así que el vendor derrotaba al design
system en toda la superficie de acceso. Medido: su reboot de Bootstrap declara
`button { border-radius: 0 }` y dejaba en radio 0 el botón del diálogo de sesión caducada; su
`.h1..h6 { color: inherit }` ganaba al adaptador de SweetAlert2.

**La decisión:** capar el vendor en vez de seguir parcheando el síntoma. Se sirve la copia local
desde `public/css/design-system/entrypoints/attach-adminlte.css` con
`@import url(...) layer(vendor)`, y lo emite `renderForModule('auth')`. Con eso desapareció el
único `!important` que le quedaba a `adapters/sweetalert2.css`.

**Lo que hubo que mover en el registro de vendors, y por qué no era opcional.** `adminlte` estaba en
`DesignSystemHeadComponent::VIEW_OWNED_VENDORS`, cuya definición es «vendor cuya hoja enlaza la
propia vista». Era el contraejemplo de esa categoría: enlazar desde la vista es exactamente lo que
lo dejaba sin capa. Un `attach-*` y la pertenencia a esa lista son **mutuamente excluyentes** por
candado (`view-owned-with-attachment` en `scripts/design-system-entrypoint-partition.mjs`), así que
salió de ahí y entró en `VENDOR_ATTACHMENTS` + `STANDALONE_ATTACHMENTS`. Es *standalone* y no
miembro de la partición porque su CSS nunca estuvo dentro de `aia-design-system.css`: meterlo ahí lo
cargaría en las ~14 vistas que siguen en `render()`, un cambio visual global que nadie pidió. Va
primero entre los adjuntos por ser el único que entrega un framework completo —AdminLTE 3.2 trae
Bootstrap 4.6.1 dentro— y dentro de `layer(vendor)` gana el último declarado.

**Segunda tanda, misma sesión: cero red externa.** Retirados también el `<link>` a
`fonts.googleapis.com/css2` y el del CDN de Font Awesome 5.15.4 —redundantes: `core.css` ya trae
`design-system/fonts.css` (Inter y Montserrat locales, DS-007) y
`/public/vendor/font-awesome/css/all.css` capado—; jQuery pasa a la copia local, que es la **misma**
3.6.0 que servía cdnjs; y el bundle de Bootstrap 4.6.1 se retiró **sin sustituir** por ser carga
muerta (cero `data-toggle`, modal, tooltip, popover o collapse en las tres vistas; el único `$()` es
jQuery puro). Sustituirlo por la copia local habría sido peor: `public/vendor/bootstrap/` es 4.3.1,
versión distinta y sin Popper. El **CSS** de Bootstrap sí hace falta y sigue llegando capado desde
`core.css`.

Casos `6-adminlte-login` y `10-cdn-externo-auth` de
`docs/design-system/unlayered-delivery-inventory.json`, ambos CERRADOS. Auth pasa de tres hojas sin
capa a una: `login-brand-unified.css`, que es el caso 7, censado aparte y todavía abierto.

**El golden, aparte.** `auth/login-dark-1180x820.png` ya estaba desactualizado **antes** de esta
tanda —retrataba el logo viejo y el botón «Modo linen», ambos retirados en `4437fcfa`— y capar el
vendor no mueve la página (A/B con y sin el `<link>` del CDN: mismo ancho de botón, mismo
`font-size` y mismo salto de línea del título; solo cambia el `offsetHeight` de la tarjeta,
736→603 px, invisible). Con **aprobación visual explícita del usuario** se rehízo el mismo día,
como paso separado: `d932f02b`→`0d58ad67` en `scenarios[0].sha256` de `auth.json`.

Se regeneró con el **mismo mecanismo que lo fijó** en su día —`DRYRUN_SURFACE=auth
DRYRUN_PHASE=… npx playwright test tests/browser/entrypoint-segmentation-dryrun.mjs`— y no con una
captura ad hoc: es lo único que garantiza mismo viewport, mismo `fullPage: false` y mismo estado de
carga que el golden que sustituye. La fase se nombró `golden-2026-08-06` en vez de reusar `after`
para no pisar la evidencia histórica del goal [[goals/segmentacion-entrypoint-css/goal|segmentacion-entrypoint-css]].
La corrida deja además su propia evidencia: consola vacía y **cero violaciones de axe** en las tres
rutas, donde el dry-run de 2026-07-22 registraba `landmark-one-main` y `region`.

Relacionado: [[admin-adminlte-adaptador]] (el precedente que fija el patrón, en `admin/`),
[[css-layer-cascade]], [[panel-browser-no-anima]] (la trampa que se destapó al verificar el diálogo).
