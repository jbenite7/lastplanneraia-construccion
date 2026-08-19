---
capa: fuente
tipo: spec
estado: vigente
fecha: 2026-07-29
areas: [admin]
fuente: goals/dark-mode-todos-los-modulos/specs/F4-admin.md
resumen: F4 · Panel admin
---

# F4 · Panel admin

**Depende de:** F0. Puede correr en paralelo con F1 — `admin/` no carga `styles.css`.
**Riesgo:** medio — 14 vistas, pero sin reescritura estructural.

## Alcance acotado por decisión del usuario

Decisión 11 del grilleo, respuesta libre: **«Mejor no lo toquemos en este goal»**, referida a
AdminLTE.

Lectura confirmada en chat el 2026-07-25 y vinculante para este spec:

> AdminLTE permanece como framework de `admin/`. **No** se reescriben las 14 vistas sobre el
> shell canónico ni se migran a primitivas `aia-*`. Sí se sirve local en vez de por CDN, sí se
> unifica a los tokens canónicos, y sí recibe un adaptador dark.

**Consecuencia asumida:** al cerrar el goal, `admin/` quedará en dark y tokenizado **pero no
migrado al design system**. Es la única desviación deliberada respecto al criterio
«migración completa al DS» elegido para el resto del alcance. Queda registrada para que nadie
la lea después como un olvido.

## Estado

Catorce vistas bajo `admin/views/`:

```
layouts/main.php
pages/{dashboard,login,password-forgot,password-reset}.php
pages/matching/{config,family_catalog}.php
pages/projects/{index,create,edit,members}.php
pages/users/{index,create,edit}.php
```

`admin/index.php` es un front controller propio, con su `admin/src/Core/Router.php`,
`RoleManager.php`, `Security.php` y sus modelos. Comparte el autoloader de Composer y el
esquema MySQL con la app principal, pero es arquitectónicamente independiente (AGENTS.md).

### Por qué está en claro

`admin/views/layouts/main.php` no carga `theme-bootstrap.js`, no pone `data-aia-theme`, y su
`<body>` usa `navbar-white navbar-light`. Su hoja de tokens
(`admin/public/css/tokens.css`, 126 líneas, 37 hex) no tiene ninguna relación con `--ds-*`.

**Corrección medida el 2026-07-25 (tras ejecutar F0/Task 5):** la primera redacción decía que
`admin/` «mejoraría parcialmente por herencia» al invertir `:root` a dark. **Es falso.**
`admin/views/layouts/main.php` no enlaza ninguna hoja del design system y ningún CSS de `admin/`
consume `--ds-active-*`, así que esas variables resuelven a cadena vacía y el `body` sigue en
`rgb(255,255,255)`. Verificado forzando la cascada previa a la inversión: números idénticos en
las cuatro rutas medidas.

Consecuencia para el alcance: **F0 no mejora `admin/` en absoluto**. Sigue en claro puro hasta
que F4 le enlace los tokens (T4.2) y el adaptador (T4.4). No hay estado intermedio «mezclado».

Medición de contraste al abrir F4 (Playwright, `1180x820`, WCAG con `oklch()` resuelto):

| Ruta | Peor ratio | Fallos / medidos |
|---|---|---|
| `/admin/` | **1.63:1** | 22 / 366 |
| `/admin/usuarios` | 2.13:1 | 99 / 1131 |
| `/admin/proyectos` | 3.13:1 | 15 / 267 |
| `/admin/login` | 5.74:1 | 0 / 6 |

Peor elemento: `h3.card-title.text-warning` («Integridad») en `/admin/`, `rgb(255,193,7)` sobre
`rgb(255,255,255)`. Las superficies de texto principales están bien (cuerpo y tarjetas 15.43:1,
filas de tabla 13.81–15.43:1, cabeceras 6.9:1, etiquetas 15.43:1, sidebar 12.59:1). **Todos los
fallos son de la paleta Bootstrap/AdminLTE** —`.text-warning`, `.badge bg-teal`,
`.badge-success`— sobre blanco. Es deuda de contraste preexistente y ajena al tema, que T4.4
debe resolver junto con el adaptador.

Deuda menor detectada de paso: `public/css/login-brand-unified.css` consume `--ds-active-*` en
`/admin/login`, pero esa página no carga ninguna hoja que los defina; esas declaraciones son
inválidas en tiempo de cómputo hoy.

### Dependencias externas

`main.php` carga desde CDN:

| Recurso | Origen |
|---|---|
| Inter | `fonts.googleapis.com` |
| Font Awesome 5.15.4 | `cdnjs.cloudflare.com` |
| Toastr | `cdnjs.cloudflare.com` (**sin versión**: `/latest/`) |
| DataTables 1.10.21 + responsive + buttons | `cdnjs.cloudflare.com` |
| jQuery 3.6.0, Bootstrap 4.6.1 | `cdnjs.cloudflare.com` |
| AdminLTE 3.2 | `cdn.jsdelivr.net` |

Ya servidos localmente y reutilizables: `/public/vendor/font-awesome/`,
`/public/vendor/bootstrap/`, `/public/vendor/sweetalert2.min.css`.

Las fuentes por CDN además dejan `admin/` fuera del contrato DS-007 (Inter v20 y Montserrat
v31 servidas locales con hash y licencia OFL).

## Alcance

### T4.1 — Vendorizar

Decisión 13. Todo a `/public/vendor`, reutilizando lo existente en lugar de duplicar:

- Font Awesome, Bootstrap y SweetAlert2: apuntar a los que ya están servidos.
- Inter: apuntar a `public/css/design-system/fonts.css`, que ya sirve las fuentes locales.
- Toastr y DataTables: descargar con **versión fija** y añadir a `/public/vendor`. El `/latest/`
  de Toastr desaparece.
- AdminLTE 3.2: descargar con versión fija a `/public/vendor/admin-lte/`.

Criterio: cargar cualquier vista de `admin/` con la red externa bloqueada produce la página
completa y funcional.

### T4.2 — Unificar tokens

Decisión 12. `admin/views/layouts/main.php` carga `public/css/tokens.css`.
`admin/public/css/tokens.css` se elimina, tras mapear sus 37 hex a los `--ds-*` equivalentes y
reescribir sus consumidores en `admin-custom.css` y `utilities.css`.

**Cabo suelto que F0 dejó y esta tarea cierra.** `admin/public/css/tokens.css:66` sigue
declarando `--aia-bg-linen`. F0/Task 7 renombró ese mismo token a `--aia-bg-parchment` en
`public/css/tokens.css` con el argumento de que **un token llamado como el tema retirado es en sí
mismo una referencia viva**, y el criterio 4 de cierre de F0 las prohíbe. Task 10 metió `admin/`
bajo el audit y no aplicó la misma regla aquí, así que la norma quedó aplicada en un archivo y no
en el otro.

Hoy es inocuo —ese token no tiene ningún consumidor en `admin/`— y por eso se aceptó como deuda
en vez de tocarlo desde otra fase. Al unificar tokens desaparece por construcción. Si esta tarea
se replantea y `admin/` conserva su propio archivo, hay que renombrarlo igualmente.

El aislamiento que AGENTS.md exige para `admin/` es de PHP —routing, RBAC, modelos—, no de
design system; `DESIGN.md` declara los tokens contractuales para toda la aplicación.

### T4.3 — Aplicar el tema

`main.php`, `login.php`, `password-forgot.php` y `password-reset.php` cargan
`theme-bootstrap.js` mediante `DesignSystemHeadComponent::renderScript`. Las cuatro vistas con
`<html>` propio quedan cubiertas; las diez restantes heredan de `layouts/main.php`.

Sustituir `navbar-white navbar-light` por el equivalente oscuro de AdminLTE.

### T4.4 — Adaptador dark de AdminLTE

Crear `public/css/design-system/adapters/admin-lte.css`, en la capa `vendor`, siguiendo el
patrón de los adaptadores existentes (`select2.css`, `sweetalert2.css`, `handsontable.css`).
Reasigna las variables y superficies de AdminLTE —sidebar, navbar, cards, tablas, formularios,
modales— a `--ds-active-*`.

Es un adaptador de vendor, no una migración: no introduce primitivas `aia-*` en las vistas.

### T4.5 — Limpiar las vistas

Eliminar los `<style>` y `style="…"` que el audit encuentre: 4 bloques `<style>` y 43
atributos inline, concentrados en `users/index.php` (11 inline, 11 hex), `dashboard.php`
(11 inline), `users/create.php`, `users/edit.php`, `projects/*` y `matching/*`.

### T4.6 — Bajar el baseline

F0 congela el baseline de `admin/` con gate monotónico. F4 lo baja. Al cerrar, `admin/`
declara presupuesto de ruta con cero en `hardcoded-hex`, `hardcoded-color-function`,
`inline-style`, `embedded-style-block` y `forbidden-font-roboto`.

`hardcoded-radius` **no** entra en cero: AdminLTE trae los suyos y no se reescribe el vendor.
Se registra la excepción en `exceptions.json`.

## Fuera de alcance

- Reescribir las vistas sobre el shell canónico o migrarlas a primitivas `aia-*`.
- Absorber `admin/` dentro de `src/`.
- Actualizar AdminLTE de versión.
- Cambiar routing, RBAC, modelos ni comportamiento de `admin/`.

## Verificación

```bash
node scripts/design-system-audit.mjs
docker compose exec app vendor/bin/phpstan analyse src admin/src --memory-limit=1G
docker compose exec app php tests/test_global_table_safety.php
```

En navegador, `1180×820` dark, contra el contenedor servido: las 14 vistas cargan en oscuro,
sin peticiones a dominios externos, consola limpia, foco visible y contraste AA en texto,
tablas y formularios. Al menos un rol permitido y uno denegado sobre una vista con
restricción, porque `admin/` tiene su propio `RoleManager`.

## Riesgos

| Riesgo | Mitigación |
|---|---|
| Vendorizar AdminLTE rompe estilos que dependían de rutas del CDN | Copiar el paquete completo, incluidos assets referenciados por `url()` |
| Unificar tokens rompe `admin-custom.css` | Mapeo explícito de los 37 hex antes de borrar; el archivo son 280 líneas, revisable entero |
| El adaptador de AdminLTE crece hasta ser una reescritura encubierta | Límite declarado: sólo reasignación de variables y superficies. Si hace falta más, se escala al usuario en vez de ampliarlo en silencio |
| `admin/` queda en dark pero fuera del DS y alguien lo lee como cerrado | Registrado en `facts.md`, en este spec y en el criterio de cierre del goal |

## Criterio de cierre

1. Cero peticiones externas en las 14 vistas.
2. `admin/public/css/tokens.css` no existe; `admin/` consume el canónico.
3. Las 14 vistas renderizan en dark coherente, con AA verificado.
4. Ninguna vista conserva `<style>` ni `style="…"`.
5. Presupuesto de ruta declarado, con la excepción de `hardcoded-radius` justificada.
6. Evidencia visual de las 14 en `evidence/F4/`.
