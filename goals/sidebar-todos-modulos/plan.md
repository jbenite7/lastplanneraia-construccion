---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-08-02
areas: [proceso]
fuente: goals/sidebar-todos-modulos/plan.md
resumen: Plan — Rollout del shell sidebar a todos los módulos
---

# Plan — Rollout del shell sidebar a todos los módulos

## Enfoque

El shell sidebar ya es una unidad reutilizable (`views/partials/shell_sidebar.php` + `public/css/design-system/adapters/shell-sidebar.css` + `public/js/modules/aia_ui/sidebar_navigation.js`), probada en `/programacion-intermedia`. El rollout es **repetir la "receta PI"** en cada módulo, con un **test data-driven** que se escribe primero (falla para los no migrados) y se vuelve verde módulo a módulo. Orden por riesgo creciente. Cada ruta migrada se declara en `docs/design-system/manifests/foundation-shell.json` y debe dejar los gates del foundation-shell verdes.

### La "receta PI" (por módulo)
En el `.view.php` del módulo:
1. `body class="aia-shell aia-shell--sidebar …"`.
2. `require __DIR__ . '/../partials/shell_sidebar.php';` al inicio del `<body>`.
3. Definir antes del wiring: `$shellActive` = id del módulo (destino válido del nav), `$shellModuleLabel` = nombre para la context-bar, y `$shellWeeks` (semanas del proyecto) cuando aplique — el partial cae a sesión/vacío si no se pasan.
4. `window.__AIA_SHELL_SIDEBAR__ = true;` y cargar `/js/modules/aia_ui/sidebar_navigation.js` vía `DesignSystemHeadComponent::renderScript`.
5. **Quitar la navbar superior legacy** (markup Bootstrap `#navbarSupportedContent`/`.navbar-*` y su `nav_drawer.js`), conservando el **cajón LPS derecho** donde exista.
6. Ajustar el layout del contenido para convivir con la sidebar fija (padding-left del body ya lo aporta `aia-shell--sidebar`) sin overflow horizontal.
7. Añadir la ruta a `foundation-shell.json → routes`.

## Pasos

### Paso 0 — Mapa/inventario (fact-mapa)
- Recorrer los 12 módulos y registrar por cada uno: ¿incluye `shell_sidebar.php`? ¿está en `foundation-shell.json → routes`? ¿pasa gates? ¿usa Handsontable / cajón LPS? ¿navbar legacy a retirar? ¿es week-scoped?
- Entregable: `goals/sidebar-todos-modulos/evidence/mapa-sidebar.md` (tabla estado + brechas).
- **Verificación:** revisión del doc; grep confirmando include del partial y presencia en `routes`.

### Paso 1 — Harness de verificación data-driven (fact-test-datadriven)
- Nuevo `tests/browser/shell-sidebar-rollout.mjs`: lista de las 12 rutas (con `$shellActive` esperado y rol de prueba). Para cada ruta: login → goto → asserts: sidebar presente (`[data-shell-pattern="sidebar"]`), `data-sidebar-state="collapsed"` por defecto, toggle alterna a expanded y de vuelta, cero-scroll del nav en ambos estados, sin overflow horizontal del documento, `aria-current="page"` en el ítem del módulo. Reutiliza el patrón de `shell-week-admin.mjs` (login, intercept de mutaciones PS).
- Se escribe **antes** de migrar: falla para los 11 no migrados (rojo esperado), verde para PI.
- **Verificación:** `node tests/browser/shell-sidebar-rollout.mjs` (PI verde, resto rojo documentado).

### Paso 2 — Grupo A: páginas simples (profesionales, subcontratistas, control-cambios)
- Aplicar la receta PI a cada `.view.php`. Retirar su navbar Bootstrap. Estos no son week-scoped: `$shellWeeks` puede ir vacío (chip de semana oculto; flyouts de semana solo cuelgan de PG/PI/PS, no de estos ítems).
- Registrar `/profesionales`, `/subcontratistas`, `/control-cambios` en `foundation-shell.json`.
- **Verificación por módulo:** `php -l` de la vista; `node tests/browser/shell-sidebar-rollout.mjs` (ruta pasa a verde); biome si se toca CSS; spot-check visual 1180×820 dark en ambos estados.

### Paso 3 — Grupo B: grids operativos con cajón LPS (programa-general, programacion-semanal, actualizar-cronograma, listado-actividades, contratos, pdc)
- Misma receta; estos ya se parecen a PI (Handsontable + cajón LPS + week-scoped). Confirmar coexistencia sidebar fija + rail LPS derecho (padding-left del shell + padding-right del handsontable-module) sin overflow ni solape.
- `programacion-semanal` incluye subvistas (CIC/CNC/CNP): verificar que todas hereden el shell.
- Registrar sus rutas en `foundation-shell.json`.
- **Verificación:** por módulo como Paso 2 + confirmar que el cajón LPS sigue operativo (fact-cajon-lps).

### Paso 4 — Grupo C: layouts especiales
- **Indicadores** (fact-indicadores): aplicar shell; ajustar `ajustarInformePowerBI()` / ancho del embed para el nuevo ancho disponible (body con padding-left del sidebar) sin overflow horizontal en ambos estados.
- **Control Tower** (fact-control-tower): el shell reemplaza la navbar de app superior pero **conserva la sub-nav BI intra-sección** (tabs overview/PG/PI/PS de `bi/_nav.php`); decidir si la sub-nav va en la context-bar o bajo ella. Mayor riesgo por su layout propio (`bi/_layout.php`).
- Registrar `/indicadores` y las rutas de control-tower en `foundation-shell.json`.
- **Verificación:** rollout test verde para ambas + spot-check visual del embed/sub-nav sin overflow.

### Paso 5 — Cierre y gates globales (fact-manifest-gates, fact-paridad)
- `foundation-shell.json → routes` contiene las 12 rutas.
- Gates verdes: `node --test tests/design-system/shell-navigation.test.mjs`, `docker compose exec -T app php tests/test_shell_sidebar_partial.php`, `node tests/browser/shell-week-admin.mjs`, `node tests/test_foundation_shell_contract.mjs`, `node tests/browser/shell-sidebar-rollout.mjs` (12/12), biome sobre CSS tocado, `php -l` de vistas tocadas.
- Verificar paridad de comportamiento (default colapsado, toggle compacto, marca sin truncar, paneles opacos, sin contexto duplicado) — cubierto por el rollout test + spot-check.

## Riesgos / preguntas abiertas
- **Control Tower**: convivencia de la sub-nav BI con la context-bar del shell es el punto de mayor incertidumbre; puede requerir diseño puntual. Candidato a hacerse al final o como sub-goal si se complica.
- **Indicadores embed**: el ancho del Power BI depende de JS de dimensionado; validar que no reintroduzca overflow al cambiar de estado colapsado/expandido.
- **`$shellActive` inválido**: el componente lanza excepción si el id activo no es un destino conocido del nav → cada módulo debe pasar un id válido (o vacío).
- **Módulos no week-scoped** (profesionales/subcontratistas/control-cambios/indicadores): el chip de semana se oculta solo (`$shellSemana > 0`), pero confirmar que ningún JS legacy dependa de la navbar retirada.
- **Dos "foundation shells"**: el navbar-drawer legacy (`nav_drawer.js`, `test_foundation_shell_contract.mjs`) es distinto del sidebar; al retirar la navbar de un módulo, confirmar que no rompe assets/tests compartidos que aún la asuman.
- **RBAC** (fact-activo-rbac): verificar al menos un rol permitido y uno denegado por módulo donde la visibilidad varíe (transcrita en `$shellHiddenByRole` del partial).
- **Tamaño**: 11 módulos es grande; los grupos A/B/C permiten avanzar y verificar por lotes. Se puede ejecutar en fases/PRs por grupo.
