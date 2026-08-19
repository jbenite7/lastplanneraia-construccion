---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-07-28
areas: [pdc]
fuente: goals/pdc-revision-ux/plan-3-navegacion.md
resumen: Que el módulo viva dentro del shell del sistema de diseño y que sus tablas dejen de estar apiladas una debajo de otra. Cumple f26–f29.
---

# Plan 3 — Rediseño de navegación — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) o superpowers:executing-plans. Steps use checkbox (`- [ ]`) syntax.
>
> **Este plan es de interfaz.** Antes de la Task 3, invocar `impeccable:impeccable` (comando `shape` para el diseño de las pestañas, `craft` para construirlas, `audit` + `critique` al cerrar). La estética se battle-testea con capturas, no con TDD; lo verificable —qué pestañas existen, cuál está activa, que ninguna tabla quede oculta— sí va con test primero.

**Goal:** Que el módulo viva dentro del shell del sistema de diseño y que sus tablas dejen de estar apiladas una debajo de otra. Cumple **f26–f29**.

**Architecture:** Dos capas. En PHP, la vista de la SPA pasa a usar el shell con barra lateral y se registra un item propio que hoy no existe. En la SPA, la barra horizontal se rediseña como pestañas con los tokens del sistema, y Maestro, Paquetes y Plan organizan sus tablas internas en pestañas.

## Lo que la exploración dejó claro, y que cambia el encargo original

El hecho f26 se pidió como «que el módulo deje de tener su propia barra». **No es implementable así**, por dos razones que ninguna cantidad de esfuerzo salva:

1. **La barra lateral no admite anidamiento.** Su estructura es estrictamente grupo → enlace (`DesignSystemComponent::sidebarNavigation()`). No hay tercer nivel en el componente, ni en su CSS, ni en `sidebar_navigation.js`.
2. **Las rutas con almohadilla no llegan al servidor.** La SPA usa `HashRouter`; el navegador recorta el `#...` antes de enviar la petición. `PlanComprasController::index()` es un único método para una única ruta y **no puede saber en qué pantalla está el usuario**, así que no puede fijar un `$shellActive` distinto por pantalla.

**Lo que sí se puede, y es el patrón que ya usa Control Tower:** la barra lateral aporta **una** entrada al módulo (`views/bi/_layout.php` requiere `shell_sidebar.php`) y la navegación entre sub-pantallas vive dentro (`views/bi/_nav.php`, un `role="tablist"` con ocho pestañas). Este plan copia ese patrón.

**Además, un hallazgo que nadie había visto:** el item «Plan de Compras» de la barra lateral (`shell_sidebar.php:92`) apunta a **`/pdc`, el módulo viejo de Handsontable**. A la SPA nueva no llega ningún enlace desde la barra — solo se entra escribiendo la dirección. Eso hay que arreglarlo aquí.

## Global Constraints

- **Dos repos:** PHP en `/Volumes/Crucial X6/Developer/lps-aia-pdc`, SPA en `/Volumes/Crucial X6/Developer/plan-de-compras`. **NUNCA** tocar `/Volumes/Crucial X6/Developer/lps-aia`. **NUNCA** `npm run sync`.
- **Invariante duro del shell:** si `$shellActive` no coincide con ningún id declarado, `sidebarNavigation()` lanza `InvalidArgumentException` y **la página truena**. Declarar el item antes de usarlo.
- **Gate del rollout:** `tests/browser/shell-sidebar-rollout.mjs` tiene `ALL_ROUTES` (21 rutas) y `MIGRATED`. `/plan-compras` no está en ninguna. Se añade primero a `ALL_ROUTES` (queda en PENDING, no rompe) y a `MIGRATED` solo cuando el shell esté cableado.
- **Contrato de migración:** `docs/design-system/contracts/module-migration.md` exige CSS local solo de composición, manifiesto declarado, y gates de oscuro en tres tamaños, teclado/foco y Axe sin críticos. El módulo **no está en ningún manifiesto** (`inventory.json` no tiene entrada para `plan-compras`): hay que crearla.
- `npm run test` y `npm run build` en verde; suites PHP en 0 FAIL.

---

### Task 1: Registrar el módulo en la barra lateral (f26, parte PHP)

**Files:**
- Modify: `lps-aia-pdc/views/partials/shell_sidebar.php`
- Modify: `lps-aia-pdc/src/Controllers/Gestion/PlanComprasController.php`
- Modify: `lps-aia-pdc/views/plan-compras/app.view.php`
- Modify: `lps-aia-pdc/tests/browser/shell-sidebar-rollout.mjs`
- Modify: `lps-aia-pdc/docs/design-system/manifests/inventory.json`

- [ ] **Step 1: Escribir el test que falla**

En `tests/test_shell_sidebar_partial.php` (que ya renderiza el parcial y hace asserts de substring):

```php
// El módulo nuevo tiene que tener su propia entrada. Hasta ahora el único enlace «Plan de Compras»
// llevaba al módulo viejo, y a la SPA no se llegaba desde ninguna parte de la interfaz.
$assert(str_contains($html, '/plan-compras'),
    'Sidebar: existe un enlace hacia el módulo nuevo de plan de compras.');
```

- [ ] **Step 2: Ejecutar para verlo fallar**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && docker compose exec -T app php tests/test_shell_sidebar_partial.php 2>&1 | tail -5
```

- [ ] **Step 3: Declarar el item**

En `shell_sidebar.php`, dentro del grupo `compras` (junto al `$shellItem('plan-compras', ...)` que ya existe apuntando a `/pdc`). **Decisión a tomar y documentar:** ¿el item nuevo convive con el viejo o lo reemplaza? Mientras el módulo viejo siga en producción, conviven; hay que distinguirlos por etiqueta sin que el usuario tenga que adivinar cuál es cuál.

Respetar el filtrado por rol de `$shellHiddenByRole` y usar un id nuevo, distinto de `plan-compras`, para no romper el `$shellActive` del controlador viejo.

- [ ] **Step 4: Que la vista de la SPA use el shell**

En `app.view.php`: requerir `shell_sidebar.php`, poner las clases `aia-shell aia-shell--sidebar` en el `body`, declarar `window.__AIA_SHELL_SIDEBAR__ = true` y cargar `sidebar_navigation.js` — exactamente como hace `views/pdc/pdc.view.php`. En el controlador, fijar `$shellActive` al id nuevo.

- [ ] **Step 5: Comprobar que la página no truena**

```bash
curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8091/plan-compras
```

Esperado: `200` o redirección al login, **nunca 500**. Un 500 aquí significa que `$shellActive` no casa con ningún id declarado.

- [ ] **Step 6: Sumar la ruta al gate**

Añadir `/plan-compras` a `ALL_ROUTES` en `shell-sidebar-rollout.mjs`. **Todavía no a `MIGRATED`** — eso va en la Task 4, cuando el shell esté verificado.

- [ ] **Step 7: Commit**

---

### Task 2: La barra del módulo, alineada al sistema (f26, parte SPA)

**Files:** `plan-de-compras/src/App.tsx`, `src/styles.css`.

**Contexto:** `.pdc-nav` usa colores sueltos (`#2c2c2e`, `#f4f1ea`, `#69b578`), no tokens del sistema. Además de duplicar navegación, hoy tampoco combina visualmente con el shell que la va a rodear.

- [ ] **Step 1: Invocar `impeccable:impeccable` (`shape`)** para el diseño de las pestañas dentro del shell. Producir el brief antes de escribir CSS.

- [ ] **Step 2: Escribir el test que falla** — la barra pasa a ser una lista de pestañas accesible:

```ts
it('la navegación del módulo se anuncia como pestañas', () => {
  // role="tablist" con aria-selected es el patrón que ya usa Control Tower.
})
```

- [ ] **Step 3: Implementar** — `role="tablist"`, `aria-selected` según la ruta activa, y los colores sustituidos por tokens `--ds-*`. Sin hex sueltos.

- [ ] **Step 4: Renombrar la pantalla del cargue (f27)**

«Ensamble» → **«Cargar presupuesto»** en la barra. La palabra «Ensamble» queda para nombrar la etapa que agrupa las seis pantallas, no para una de ellas.

- [ ] **Step 5: Tests, build, bundle a mano, commit**

---

### Task 3: Pestañas dentro de Maestro, Paquetes y Plan (f28, f29)

**Files:** `MaestroInsumos.tsx`, `PaquetesContratacion.tsx`, `PlanFechas.tsx`, `src/styles.css`, y la lógica pura de pestañas en `src/lib/`.

**Las tres cascadas confirmadas:**
- **Maestro:** Importar SINCO · Pendientes por vincular · Catálogo global (3.079 insumos).
- **Paquetes:** la grilla masiva y el resto de bloques.
- **Plan:** la tabla principal y «Sin frente» con sus sugerencias.

- [ ] **Step 1: Invocar `impeccable:impeccable` (`craft`)** para construir el componente de pestañas una sola vez y reutilizarlo en las tres pantallas. **Un solo componente**: si se implementan tres veces, volvemos al problema del Plan 1 con el tema duplicado.

- [ ] **Step 2: Escribir los tests que fallan** — lógica pura: qué pestañas tiene cada pantalla, cuál abre por defecto, y que el contador de pendientes siga visible desde cualquier pestaña.

Ojo con un detalle que ya se decidió en el Plan 1: **Paquetes abre en «Sin asignar» si queda algo pendiente**. Esa regla no puede perderse al meter pestañas.

- [ ] **Step 3: Verlo fallar, implementar**

**Cuidado con AG Grid:** una tabla montada dentro de una pestaña oculta puede medir mal su ancho al mostrarse. Verifica que al cambiar de pestaña las columnas se dimensionan bien; si no, hay que forzar el redimensionado al activarla.

- [ ] **Step 4: Verificación visual en tres tamaños**

El contrato de migración exige oscuro en tres viewports, teclado/foco y Axe sin críticos ni serios. Usar el navegador integrado. **El login lo hace el usuario.**

- [ ] **Step 5: `audit` + `critique` de Impeccable**, aplicar lo P0/P1.

- [ ] **Step 6: Tests, build, bundle, commit**

---

### Task 4: Cerrar la migración y heredar el gate

- [ ] **Step 1: Declarar el módulo en el manifiesto**

Crear la entrada de `plan-compras` en `docs/design-system/manifests/inventory.json` con sus rutas, componentes y excepciones, según `contracts/module-migration.md`.

- [ ] **Step 2: Sumar la ruta a `MIGRATED`**

En `shell-sidebar-rollout.mjs`. A partir de aquí el módulo hereda gratis la verificación completa: colapsado por defecto, el conmutador expande y colapsa, sin scroll interno del nav, sin desbordamiento horizontal, e item activo con `aria-current`.

- [ ] **Step 3: Correr el gate**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc"
npx playwright test tests/browser/shell-sidebar-rollout.mjs --workers=1 2>&1 | tail -15
```

Esperado: la ruta pasa de `PENDING` a verificada, sin fallos.

- [ ] **Step 4: Verificación final y commit en ambos repos**

- [ ] **Step 5: Repasar `facts.md` f26–f29** y marcar los cumplidos. Recordar que f26 quedó **reescrito** respecto a lo aprobado inicialmente: la barra propia no desaparece, se convierte en pestañas dentro del shell.
