---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-08-12
areas: [arquitectura, deploy, design-system]
resumen: bajo mantenimiento los CSS servidos por PHP (/runtime/css/*) devolvían el HTML del cartel con 503 donde el navegador esperaba CSS, mientras los estáticos (/css/*) cargaban; la pantalla de entrada salía a medio estilizar y parecía un despliegue roto sin serlo. Desde el 2026-09-01 esa pantalla es el shell React y su exención es el prefijo /app/assets/
fuente: sesion-ejecucion
---
> **Nota del 2026-09-17, precisada el 2026-09-21.** La pantalla de la ruta oculta que describe esta
> página ya no es la vista PHP: desde el 2026-09-01 (`4b3c891c`, S01) la sirve
> `MaintenanceLoginController` con el shell React. Su HTML (`public/app/index.html:17-23`) enlaza
> **cinco** hojas estáticas `/css/*` —subió de cuatro con `project-selector-react.css` en S04
> (`bc1013ac`)— y el CSS del bundle en `/app/assets/`, ninguna por `/runtime/css/`, y
> `MaintenanceMode::isExemptRoute()` añadió para ello la exención del prefijo `/app/assets/`
> (`src/Core/MaintenanceMode.php:29-31`). Las «cinco hojas, tres y dos» de abajo son el estado medido
> el 2026-08-12, sobre la vista PHP retirada — coincide en número con las estáticas de hoy por
> casualidad, no por ser la misma lista. El criterio —pedir las rutas exentas y mirar el
> `content-type`, no el 503— sigue valiendo igual, ahora también para `/app/assets/*`.

**El modo mantenimiento tapaba sus propios estilos, y el síntoma parece un despliegue roto.**
Medido en producción el 2026-08-12, con el sitio cerrado tras el release de 1.763 commits.

Una página enlaza sus hojas de estilo por **dos caminos que no se parecen en nada** aunque en el
HTML se vean iguales:

| Ruta enlazada | Quién la sirve | Bajo mantenimiento |
|---|---|---|
| `/runtime/css/…` | PHP (`DesignSystemAssetController`) | **503 con el HTML del cartel** |
| `/css/…` | Apache, estático | 200 `text/css` |

La pantalla de entrada de la ruta oculta enlaza **cinco** hojas, tres del segundo tipo y dos del
primero — y una de las dos es `core.css`, el núcleo del design system. De ahí la forma exacta del
síntoma, que es lo que despista: **no se ve sin estilo, se ve a medio vestir**, que es justo como
se ve un despliegue a medias. Los 36 archivos importados existían y respondían 200; no faltaba
nada.

**Lo que lo hizo posible fue que la misma lista de rutas vivía copiada en tres sitios**
—`$publicRoutes` de `public/index.php`, el registro del router, y `MaintenanceMode::isExemptRoute()`—
y solo dos se mantuvieron al día. La exención del cartel se quedó con dos entradas
(`/runtime/frontend-config.js` y la ruta oculta) desde el commit que creó la puerta (`b00cdaf6`):
**los CSS no se cayeron de la lista, nunca estuvieron**. No lo trajo el release; el release lo
destapó, porque hasta entonces producción no servía CSS por PHP.

**El arreglo no fue añadir una cuarta copia.** `DesignSystemAssetController::publicRoutePaths()`
deriva las rutas de su propio `ENTRYPOINTS`, y `isExemptRoute()` se las pide en vez de repetirlas,
así que un entrypoint nuevo queda exento sin que nadie se acuerde. Lo cubre
`tests/test_maintenance_asset_exemption.php`, que comprueba la coherencia entre las listas en
lugar de comparar contra un catálogo escrito a mano — y verifica también que la exención **no
abra** el sitio: `/`, `/login`, `/plan-compras` y `/programacion-intermedia` siguen en 503.

**El criterio que deja:** al poner un sitio en mantenimiento, comprobar que responde 503 **no es
la comprobación**. Bajo el cartel todo devuelve 503, así que ese código no distingue «cerrado» de
«roto». Lo que discrimina es pedir las rutas **exentas** y mirar el `content-type`: un `text/html`
donde esperabas `text/css` es el fallo, y sale a la vista con
`curl -s -o /dev/null -w "%{http_code} %{content_type}"`, nunca con el código a secas.

**Desplegado y confirmado en producción** el 2026-08-12 (`9ae7cb19`), con el cartel todavía puesto:
las **cinco** hojas de la pantalla de entrada responden `200 text/css` —eran tres de cinco— y `/`,
`/login`, `/plan-compras` y `/programacion-intermedia` siguen en `503`. La prueba corre también
allí con PHP 8.3: 25/25. No hizo falta `composer install`, porque el cambio no trae clases nuevas
y ambas ya estaban en el classmap.

Ver [[produccion-deploy]] y [[el-archivo-que-tocas-puede-tener-un-contrato]].
