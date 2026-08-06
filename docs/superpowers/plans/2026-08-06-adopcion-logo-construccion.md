# Adopción del logo «Last Planner · línea Construcción» — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Adoptar el ícono nuevo en favicon, sidebar del shell, login y Admin, sin tocar la paleta del design system.

**Architecture:** Assets estáticos en `public/img/brand/` + `public/favicon.ico`; el sidebar consume el ícono a color como `<img>`, el login consume el glifo monocromo como máscara CSS vía el token existente; Admin (mini-app aislada) recibe sus propios `<link>` y `<img>`.

**Tech Stack:** PHP plano (vistas + `DesignSystemComponent`), CSS tokens, sin build.

**Spec:** `docs/superpowers/specs/2026-08-06-adopcion-logo-construccion-design.md`

## Global Constraints

- Solo dark desktop ≥1180 px; viewport canónico de validación **1180×820** (AGENTS.md). Nada de mobile/tablet/`linen`.
- Sesión local **solo** por dev door: `http://localhost:8081/dev/entrar?u=test.R&p=PDC%20Sandbox%20E2E`. Nunca por `/login` con credenciales.
- Runtime en Docker (`app` en `http://localhost:8081`); nada de PHP del host.
- **Sin commits**: AGENTS.md exige petición explícita. Los pasos de commit se ejecutan solo si el usuario lo autoriza; si no, se dejan los cambios en el worktree y se reporta.
- Origen de assets: `/Users/felipebenitez/Downloads/last-planner-aia-construction/last-planner-aia/exports/construction/` (en adelante `$KIT`).
- No introducir hex nuevos en CSS de módulos migrados; aquí solo cambian URLs de assets y un filtro.

---

### Task 1: Assets de marca en runtime

**Files:**
- Create: `public/img/brand/icon.svg` (desde `$KIT/last-planner-construction-icon.svg`)
- Create: `public/img/brand/glyph-mono.svg` (desde `$KIT/last-planner-construction-glyph-mono.svg`)
- Create: `public/img/brand/icon-192.png` (desde `$KIT/last-planner-construction-icon-192.png`)
- Create: `public/favicon.ico` (desde `$KIT/favicon.ico`)

**Interfaces:**
- Produces: URLs `/img/brand/icon.svg`, `/img/brand/glyph-mono.svg`, `/img/brand/icon-192.png`, `/favicon.ico` servidas por el contenedor. Tasks 2–5 dependen de ellas.

- [ ] **Step 1: Copiar los assets**

```bash
KIT="/Users/felipebenitez/Downloads/last-planner-aia-construction/last-planner-aia/exports/construction"
mkdir -p "public/img/brand"
cp "$KIT/last-planner-construction-icon.svg" public/img/brand/icon.svg
cp "$KIT/last-planner-construction-glyph-mono.svg" public/img/brand/glyph-mono.svg
cp "$KIT/last-planner-construction-icon-192.png" public/img/brand/icon-192.png
cp "$KIT/favicon.ico" public/favicon.ico
```

- [ ] **Step 2: Verificar que el contenedor los sirve**

Run: `for u in favicon.ico img/brand/icon.svg img/brand/glyph-mono.svg img/brand/icon-192.png; do curl -s -o /dev/null -w "%{http_code} $u\n" "http://localhost:8081/$u"; done`
Expected: cuatro líneas `200 …` (el override de compose monta `public/` en vivo; no requiere rebuild).

- [ ] **Step 3: Commit (solo con autorización explícita)**

```bash
git add public/img/brand public/favicon.ico
git commit -m "feat(marca): añade el ícono Construcción y favicon al runtime"
```

---

### Task 2: Partial de favicon en las vistas de la app

**Files:**
- Create: `views/partials/head_brand.php`
- Modify (añadir una línea dentro de `<head>`, junto a los `<meta>` iniciales) en:
  `views/auth/login.view.php`, `views/auth/password-forgot.view.php`, `views/auth/password-reset.view.php`,
  `views/core/project_selector.view.php`, `views/bi/_layout.php`, `views/bi/control-tower.php`,
  `views/dashboard/escalamientos.php`, `views/indicadores/indicadores.view.php`,
  `views/control-cambios/controlCambios.view.php`, `views/plan-compras/app.view.php`,
  `views/profesionales/profesionales.view.php`, `views/subcontratistas/subcontratistas.view.php`,
  `views/programa-general/programa_general.view.php`, `views/programa-general-actualizar/programaGeneralActualizar.view.php`,
  `views/programacion-intermedia/programacion_intermedia.view.php`,
  `views/programacion-semanal/programacion_semanal.view.php`, `views/programacion-semanal/CIC.view.php`,
  `views/programacion-semanal/CNC.view.php`, `views/programacion-semanal/CNP.view.php`
  (las vistas de `views/design-system/` quedan fuera: laboratorio interno; `/favicon.ico` les llega igual por resolución automática del navegador)

**Interfaces:**
- Consumes: URLs de Task 1.
- Produces: partial `views/partials/head_brand.php` incluible con `require`.

- [ ] **Step 1: Crear el partial**

```php
<?php /* Favicon y touch icon de la marca Construcción (spec 2026-08-06). */ ?>
<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" type="image/png" sizes="192x192" href="/img/brand/icon-192.png">
<link rel="apple-touch-icon" href="/img/brand/icon-192.png">
```

- [ ] **Step 2: Incluirlo en cada vista listada**

Insertar tras el `<meta charset…>` (o el primer `<meta>`) de cada `<head>`:

```php
<?php require dirname(__DIR__) . '/partials/head_brand.php'; ?>
```

Ojo con la profundidad: todas las vistas listadas están a un nivel bajo `views/` salvo ninguna — `dirname(__DIR__)` resuelve a `views/`. En `views/plan-compras/app.view.php` ya existe `<link rel="icon" href="/favicon.ico">`: reemplazar esa línea por el require (no duplicar).

- [ ] **Step 3: Verificar cobertura e inclusión**

Run: `grep -rl "head_brand.php" views | wc -l` → Expected: `19`.
Run: `php -l views/partials/head_brand.php` dentro del contenedor (`docker compose exec app php -l …`) → sin errores.
Run: `curl -s "http://localhost:8081/login" | grep -c "img/brand/icon-192.png"` → Expected: `2`.

- [ ] **Step 4: Commit (solo con autorización explícita)**

```bash
git add views/partials/head_brand.php views
git commit -m "feat(marca): favicon de Construcción en las vistas de la app"
```

---

### Task 3: Ícono a color en el sidebar del shell

**Files:**
- Modify: `src/View/Components/DesignSystemComponent.php:431` y `:490` (los dos `<img src="/public/img/aia-last-planner-mark.svg">`)
- Modify: `public/css/design-system/components/navigation.css:50` (el `filter` del img del lockup)

**Interfaces:**
- Consumes: `/img/brand/icon.svg` (Task 1).
- Produces: lockup del sidebar con el ícono a color; el token `--ds-nav-brand-mark-image` queda intacto para el login (Task 4).

- [ ] **Step 1: Cambiar las dos rutas del `<img>`**

En ambas líneas (431 y 490) reemplazar
`/public/img/aia-last-planner-mark.svg` → `/public/img/brand/icon.svg`
(se conserva el prefijo `/public` que ya usa el componente).

- [ ] **Step 2: Neutralizar el filtro de tinte**

El ícono nuevo trae sus colores; el filtro que blanqueaba el SVG negro lo arruinaría. En
`navigation.css`, `.aia-brand-lockup img` cambia:

```css
filter: var(--ds-active-nav-mark-filter);
```

por:

```css
/* El ícono Construcción es a color; no se tiñe con el tema. */
filter: none;
```

Antes de editar, confirmar con `grep -rn "ds-active-nav-mark-filter" public/css/` que ese `filter`
no tiene otros consumidores que dependan del tinte; si los hay, limitar el cambio al selector del lockup.

- [ ] **Step 3: Verificar en navegador**

Con sesión por dev door, abrir `http://localhost:8081/programa-general` a 1180×820 dark:
el header del sidebar muestra el ícono naranja nítido + «Last Planner AIA» en texto, en estados
colapsado y expandido. Consola sin errores nuevos.

- [ ] **Step 4: Lint PHP**

Run: `docker compose exec app php -l src/View/Components/DesignSystemComponent.php` → sin errores.

- [ ] **Step 5: Commit (solo con autorización explícita)**

```bash
git add src/View/Components/DesignSystemComponent.php public/css/design-system/components/navigation.css
git commit -m "feat(marca): ícono Construcción a color en el lockup del sidebar"
```

---

### Task 4: Glifo monocromo en el login y retiro del SVG legado

**Files:**
- Modify: `public/css/tokens.css:543` (`--ds-nav-brand-mark-image`)
- Delete: `public/img/aia-last-planner-mark.svg`

**Interfaces:**
- Consumes: `/img/brand/glyph-mono.svg` (Task 1); Task 3 ya retiró los otros consumidores del SVG legado.

- [ ] **Step 1: Apuntar el token al glifo nuevo**

```css
--ds-nav-brand-mark-image: url("/img/brand/glyph-mono.svg");
```

- [ ] **Step 2: Confirmar que el SVG legado quedó huérfano y retirarlo**

Run: `grep -rn "aia-last-planner-mark" --include="*.php" --include="*.css" --include="*.js" . | grep -v vendor | grep -v node_modules`
Expected: sin resultados. Solo entonces:

```bash
git rm public/img/aia-last-planner-mark.svg
```

(Si aparece algún consumidor no previsto, actualizarlo o conservar el archivo y reportarlo.)

- [ ] **Step 3: Verificar el login en navegador**

Abrir `http://localhost:8081/login` a 1180×820 dark: el pill muestra el glifo nuevo teñido con el
color del texto + wordmark; favicon visible en la pestaña. Consola limpia.

- [ ] **Step 4: Commit (solo con autorización explícita)**

```bash
git add public/css/tokens.css
git commit -m "feat(marca): glifo Construcción como máscara del login y retiro del mark legado"
```

---

### Task 5: Marca y favicon en Admin

**Files:**
- Modify: `admin/views/layouts/main.php:70` (mark) y su `<head>` (favicons)
- Modify: `admin/views/pages/login.php`, `admin/views/pages/password-forgot.php`, `admin/views/pages/password-reset.php` (favicons en `<head>`)
- Modify (si hace falta tamaño): `admin/public/css/` — regla existente `admin-brand-mark`

**Interfaces:**
- Consumes: `/public/img/brand/icon.svg` y `/favicon.ico` (Task 1). Admin no comparte partials con `views/`, por eso los `<link>` van directos.

- [ ] **Step 1: Reemplazar el mark del layout**

`admin/views/layouts/main.php:70` cambia:

```html
<img src="/public/img/florAIA.png" alt="AIA Logo" class="brand-image img-circle elevation-3 admin-brand-mark">
```

por:

```html
<img src="/public/img/brand/icon.svg" alt="" aria-hidden="true" class="admin-brand-mark">
```

(`img-circle` recortaría el contenedor redondeado; `elevation-3` y `brand-image` pertenecían al
tratamiento circular. El texto adyacente del brand sigue dando el nombre accesible; si el `<a>`
padre no tiene texto visible, conservar `alt="AIA Last Planner"` en lugar de vaciarlo.)

- [ ] **Step 2: Añadir favicons en los cuatro `<head>` de Admin**

En `main.php`, `login.php`, `password-forgot.php`, `password-reset.php`, tras el primer `<meta>`:

```html
<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" type="image/png" sizes="192x192" href="/public/img/brand/icon-192.png">
```

- [ ] **Step 3: Ajustar tamaño del mark si hace falta**

Buscar la regla: `grep -rn "admin-brand-mark" admin/public/css/`. El ícono debe quedar en un
cuadrado ≈33 px (tamaño del brand de AdminLTE) sin deformarse: si la regla existente no fija
`width/height`, añadir en esa hoja:

```css
.admin-brand-mark { width: 33px; height: 33px; object-fit: contain; }
```

- [ ] **Step 4: Verificar Admin en navegador**

Con sesión de admin por dev door (`u=test.A`), abrir el panel Admin a 1180×820: mark nuevo sin
recorte circular ni deformación; favicon en pestaña; consola limpia.
Run: `docker compose exec app php -l admin/views/layouts/main.php` → sin errores.

- [ ] **Step 5: Commit (solo con autorización explícita)**

```bash
git add admin/views admin/public/css
git commit -m "feat(marca): ícono Construcción y favicon en el panel Admin"
```

---

### Task 6: Verificación de cierre

**Files:** ninguno (solo verificación).

- [ ] **Step 1: Pase de navegador (spec §Verificación)**

A 1180×820 dark, con dev door: `/login`, `/programa-general` (sidebar colapsado/expandido) y Admin.
Capturar una imagen de cada una como evidencia. Consola y red sin errores nuevos
(las peticiones a `/favicon.ico` y `/img/brand/*` responden 200).

- [ ] **Step 2: Frontend lint de lo tocado**

Run: `npm run check:frontend`
Expected: sin errores nuevos en `public/css` (biome no cubre `admin/views` ni `views/`).

- [ ] **Step 3: Reporte final**

Reportar: superficies verificadas, comandos y resultados, y el estado de los commits (hechos o
pendientes de autorización). Pendientes conocidos fuera de alcance: lockups PNG para
Control de Cambios/impresión y PWA manifest (no solicitados).
