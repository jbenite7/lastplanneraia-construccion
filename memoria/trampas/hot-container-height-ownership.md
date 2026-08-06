---
tipo: trampa
estado: vigente
fecha: 2026-07-25
areas: [design-system]
fuente: memoria-claude
origen: lps-aia-hot-container-height-ownership
resumen: Quién resuelve la altura de #hot-container en los módulos HOT y por qué calc(100vh - Npx) sobre ese contenedor es siempre incorrecto
---
La altura de `#hot-container` la resuelve **JS**, no CSS: `syncContainerHeight()` mide
`getBoundingClientRect().top` y `getContainerAvailableHeight()` lee el `clientHeight` para
alimentar `hot.updateSettings({ height })`. Es el patrón compartido por los **4** módulos HOT
vigentes (programacion_semanal, programacion_intermedia, programa_general, programa_actualizar);
eran 6 hasta que `contratos` y `listado_actividades` se borraron con el PDC v1 el 2026-08-04
(verificado el 2026-08-06: `syncContainerHeight` sólo aparece en esos cuatro
`public/js/modules/*/hot*.js`). Los `calc(100vh - Npx)` de las hojas de módulo son
sólo fallback pre-JS.

**Ningún `calc(100vh - Npx)` sobre `#hot-container` puede ser correcto** en las vistas con
toolbar: el contenedor va dentro de `.hot-full-bleed`, detrás de `.header-actions`, cuyo alto
refluye en runtime (leyenda, `#mensajeActualizacion`, botones sujetos a permisos/estado de
semana). Medido a 1180x820 dark en /programacion-semanal: context-bar 49px + `.header-actions`
131px + márgenes → `rect.top` 184, altura correcta **632px**.

Ojo con el **drift de sujeto**: `calc(100vh - 49px)` es correcto sobre **`.hot-full-bleed`**
(el 49px es la context-bar sticky, que sí es lo único encima de ese wrapper) y así se aplicó en
`public/css/programa-general-actualizar.css:63`. Copiarlo a
`#hot-container` lo vuelve falso por el alto de la toolbar (resolvía 771px). Esa vista tiene
además el patrón CSS completo y sin números mágicos por módulo, si algún día se migra la
geometría a CSS: `.hot-full-bleed { display:flex; flex-direction:column; height:calc(100vh - 49px) }`
+ `#hot-container { flex:1 1 auto; min-height:0 }` — el `flex: 1 1 auto` ya existe en
`handsontable-module.css:79` pero está inerte porque `.hot-full-bleed` es `display:block` en
el resto de módulos.

Contrato de geometría verificado en `tests/browser/programacion-semanal-hot-height.mjs`.
Ver [[css-layer-cascade]].
