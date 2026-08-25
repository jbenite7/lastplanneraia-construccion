---
capa: fuente
tipo: reporte
estado: vigente
fecha: 2026-08-03
areas: [proceso]
fuente: docs/superpowers/matriz-dark-mode-2026-08-03.md
resumen: Matriz de hallazgos — estado real del dark mode
---

# Matriz de hallazgos — estado real del dark mode

**Fecha:** 2026-08-03 · **Alcance:** repositorio `lps-aia` únicamente.
**Método:** todo lo marcado *medido* sale de comandos ejecutados en esta sesión contra `main` en
`4c46825`. Lo marcado *declarado* sale de un `goal.md` o de un diagnóstico y **no se ha
comprobado**. Donde ambos discrepan, gana lo medido (`código > AGENTS.md > memoria/`).

---

## 1. El titular

El dark mode **no está a medias por falta de trabajo, sino por falta de un cierre**: hay tres
registros distintos (dos goals y un reparto) que se solapan, se contradicen y ninguno dice la
verdad completa. Medido hoy:

| Métrica | Al abrir (2026-07-25, `8a13ad4`) | Hoy (`4c46825`) | Δ |
|---|---|---|---|
| **Hallazgos vivos del audit** | 7 230 | **4 511** | **−38 %** |
| `unauthorized-important` | 2 245 | 1 697 | −24 % |
| `css-outside-layer` | 846 | 504 | −40 % |
| `hardcoded-hex` | 806 | 161 | −80 % |
| `raw-token-in-module` | 730 | 483 | −34 % |
| `hardcoded-color-function` | 567 | 229 | −60 % |
| Presupuestos de ruta declarados | 9 | **15** | +6 |
| `admin/` dentro de `scanRoots` | **no** | **sí** | resuelto |

**Se avanzó mucho más de lo que el registro admite, y queda más de lo que el registro admite.**
Ambas cosas a la vez: por eso se siente como migajas.

---

## 2. Matriz maestra — las tres numeraciones, reconciliadas

Tres registros usan tres nomenclaturas para el mismo trabajo. Esta tabla las cruza por primera vez.

| # | Línea | Registro de origen | Estado **declarado** | Estado **medido hoy** | Evidencia de la medición |
|---|---|---|---|---|---|
| **F0** | Fundación de tema | `dark-mode-todos-los-modulos` | «sin ejecutar» | ✅ **HECHA** | `theme-bootstrap.js` fuerza `dark` incondicionalmente; `dark-mode.css`, `navbar.css` y `NavbarComponent.php` ya no existen; `views/bi/_layout.php` sin script inline de tema |
| **F1** | Desmantelar `styles.css` | `dark-mode-todos-los-modulos` | «sin ejecutar» | ✅ **HECHA en lo esencial** | 4 380 líneas (eran 6 802) · **10 hex** (eran 483) · capas `theme, layout, components` (era `module`) |
| **F2** | 9 superficies del agregador | `dark-mode-todos-los-modulos` | «sin ejecutar» | 🔧 **PARCIAL** | 15 presupuestos de ruta declarados (eran 9), pero **sin** `programacion-intermedia`, CIC, CNC ni CNP |
| **F3** | BI (8 rutas) | `dark-mode-todos-los-modulos` | «sin ejecutar» | 🔧 **PARCIAL** | Existe `adapters/bi-utilities.css` con su gate propio; **ninguna de las 8 rutas `/bi/*` tiene presupuesto** |
| **F4** | Panel `admin/` (14 vistas) | `dark-mode-todos-los-modulos` | «sin ejecutar» | 🔧 **PARCIAL** | `admin/` ya entra en `scanRoots`; los 4 layouts llevan `data-aia-theme="dark"`; existe `adapters/admin-lte.css`. **Cero presupuestos y cero evidencia visual: las 14 pantallas son inauditables** (ver D) |
| **F5** | `plan-compras` | `dark-mode-todos-los-modulos` | absorbida en G4 | ✅ **HECHA** | `design-system-plan-compras-gate.mjs` **PASS**; `agGrid.ts` consume 28 tokens `--ds-*`; cero bloques sin capa |
| **F6** | Vendors / Tom Select | `dark-mode-todos-los-modulos` | absorbida en G5 | ✅ **HECHA** | `adapters/tom-select.css` existe bajo `@layer vendor` |
| **G0–G6** | Cierre de tablas | `cierre-dark-mode-y-tablas` | HECHO (2026-07-31) | ✅ **HECHA, con saneamiento posterior** | `design-system-table-contract.mjs` **PASS**. El saneamiento del 2026-08-03 halló 3 de 8 condiciones incumplidas + 1 token roto |
| **A** | Consolidar `--ds-state-tint-*` | Reparto 2026-08-03 | Cerrada | ✅ Cerrada | `95a1827`, `08fe26c` (declarado; no re-medido) |
| **B** | Que las pruebas digan la verdad | Reparto 2026-08-03 | Cerrada | ⚠️ **Cerrada pero la trampa sigue viva** | Ver §5.1: la cadena `&&` volvió a ocultar el audit **hoy, en esta sesión** |
| **C** | `bloqueado` al matiz azul | Reparto 2026-08-03 | «puede sobrar» | ⏸️ **Sin decidir** | Decisión de dominio pendiente del usuario |
| **D** | Puerta de servicio para `admin/` | Reparto 2026-08-03 | Sin empezar | ⬜ **Sin empezar — bloqueo duro** | `/dev/entrar` solo abre la app principal; `admin/` valida contra `/admin/login` y el repo prohíbe teclear credenciales |
| **E** | Usabilidad (26 hallazgos) | Reparto 2026-08-03 | 1 de 5 fases | 🔧 **En curso** | Worktree `usabilidad-altas-y-medias`: **11 commits por delante, 14 por detrás de `main`**, con 4 archivos sin commitear |
| **F** | Panel de inicio | Reparto 2026-08-03 | Sin empezar | ⬜ **Sin empezar — es producto** | `/dashboard` es hoy un redirect a `/programacion-semanal` |
| **F-bis** | Autoguardado al entrar | Reparto 2026-08-03 | Sin empezar | ⬜ **Sin empezar — integridad de datos** | Condicional, no universal: exige `canManageToolbarActions()` y `semana > 0` |
| **G** | Chip de estado de PG | Reparto 2026-08-03 | Cerrada | ✅ **Cerrada y verificada** | Los tres módulos usan `data-aia-hue`: `programa_general/hot.js`, `programacion_intermedia/hot.js`, `programacion_semanal/hot.js` |

---

## 3. Los cuatro módulos priorizados

### PG · Programa General — ✅ el más terminado

- Chip de estado con los 7 matices, todos ≥AA (11,15–14,82:1), con guard que mide el píxel.
- Presupuesto de ruta declarado (`programa-general` y `programa-general-actualizar`).
- Escala de celda derivada de la de estado; contraste subió en los 4 peldaños (5,63→8,88 · 5,21→9,31 · 5,58→10,07 · 7,19→10,99).
- **Pendiente:** goldens sin recapturar tras el cambio de color (requiere aprobación visual).

### PI · Programación Intermedia — 🔧 dos deudas concretas

- Usa `data-aia-hue` correctamente (verificado: chip `green` resuelve a `#173d26`).
- ❌ **Sin presupuesto de ruta declarado** — puede regresar a claro sin que nada falle.
- ❌ **Copia local del chip:** `.pi-page .ops-state-chip` en `programacion-intermedia.css:249` y `:278`, redundante con el componente compartido.
- ⏸️ **Mapeo que no se sostiene:** `pi-state-execution-blocked → OK`. Decisión de dominio, no de diseño.

### PS · Programación Semanal — 🔧 la más contaminada

- Usa `data-aia-hue` correctamente.
- ❌ **9 bloques de copia local del chip** en `programacion-semanal.css` (líneas 454, 495, 499, 503–505, 2393, 2405), más `buttons.css:51`. Es la mayor concentración de overrides locales del sistema.
- ❌ **Sus tres tablas satélite sin presupuesto:** CIC, CNC, CNP.
- ⚠️ Mismo tono ámbar en `ps-alert-high` y `ps-alert-medium` desorienta la prioridad (hallazgo de G0, sin disponer).

### PDC v2 — ✅ cerrado en dark, ⬜ abierto en producto

- Gate propio **PASS**; `agGrid.ts` consume 28 tokens del DS; cero bloques sin capa.
- Presupuesto `pdc` declarado.
- ❌ `pdc.css:178-183` conserva 3 `!important` y 2 `local-vendor-override` sobre cabeceras Handsontable.
- ⬜ Tareas H-28 a H-30 del PDC seguían abiertas en su sesión al momento de parar.

---

## 4. Hallazgos vivos, con severidad y coste

| ID | Hallazgo | Severidad | Coste | Dónde |
|---|---|---|---|---|
| **M-01** | La cadena `&&` de `test:design-system:static` corta en el primer fallo: `contracts`, `consumer-contract` y `audit` **no llegan a correr** | 🔴 **Alta** — un verde parcial se lee como verde total | Bajo (separar en pasos o usar `;`) | `package.json` |
| **M-02** | El gate `activation` falla si el árbol está sucio, y eso **enmascara los tres gates siguientes** | 🔴 Alta | Bajo | `tests/design-system/contracts.test.mjs:55` |
| **M-03** | `design-system-audit.mjs` en rojo por 1 hex en `profesionales` y 1 en `subcontratistas` | 🟠 Media | **2 líneas** — es un hex de reserva dentro de un `var()` | `72093c6` |
| **M-04** | Las 8 rutas `/bi/*` sin presupuesto de ruta | 🟠 Media — pueden regresar a claro en silencio | Medio | F3 |
| **M-05** | PI, CIC, CNC y CNP sin presupuesto de ruta | 🟠 Media | Medio | F2 |
| **M-06** | Las 14 vistas de `admin/` son **invisibles para toda revisión automatizada** | 🟠 Media, creciente | Alto — exige vía de autenticación nueva con spec propio | Línea D |
| **M-07** | 11 copias locales de `.ops-state-chip` en `.pi-page` y `.ps-page` | 🟡 Baja | Bajo — el componente compartido ya existe | PI/PS |
| **M-08** | Paridad rota del chip: el componente compartido no declara `box-shadow` y usa radio `--ds-radius-md` | 🟡 Baja | Bajo | `components/ops-state-chip.css:17` |
| **M-09** | `cell-state-vocabulary.mjs` es código muerto: nadie lo importa salvo su propio gate | 🟡 Baja | Bajo (borrarlo) o Medio (cablearlo) | G1 |
| **M-10** | Goldens de tabla sin recapturar tras el cambio de color | 🟠 Media | Requiere **aprobación visual explícita** | G6 |
| **M-11** | `axe` cuenta `incomplete` como `violation`; las superficies son translúcidas por diseño → rojos falsos garantizados | 🔴 **Alta y creciente** — la gente aprende a ignorar los rojos | Bajo | `tests/browser/support/accessibility.mjs:36` |
| **M-12** | `--ds-sidebar-width-expanded` valió `17.5rem` hasta `72093c6`, que lo bajó a `15rem` sin razón, sin test y sin golden | 🟡 Baja (ya resuelto a favor del token) | — | B2 |
| **M-13** | 1 697 `unauthorized-important` y 504 `css-outside-layer` vivos | 🟠 Media, estructural | Alto — es el grueso de los 4 511 | Todo el árbol |

---

## 5. Lo que el registro dice y no es cierto

### 5.1 «B está cerrada: las pruebas ya dicen la verdad» — **falso, y lo comprobé hoy**

Corrí `npm run test:design-system:static` en esta sesión. Salida: `364 pass, 1 fail`. El fallo es
`activation: worktree and index must be clean` — un artefacto de tener el árbol sucio, no un
defecto del sistema. Pero por el `&&`, **`contracts`, `consumer-contract` y `audit` nunca
corrieron**. Y como canalicé la salida por `tail`, el código de salida que vi fue `0`.

La trampa que el diagnóstico documentó el 3 de agosto **me capturó a mí el 3 de agosto**. No basta
con documentarla: hay que romper la cadena.

### 5.2 «Ninguna de las fases F0–F6 se ejecutó» — **falso**

Es la frase de cierre de `goals/dark-mode-todos-los-modulos/goal.md`. Medido: F0 y F1 están hechas,
F2, F3 y F4 están a medias. El trabajo se hizo bajo otros paraguas (el goal de segmentación, el de
gobernanza del núcleo, el de sidebar) y nadie volvió a actualizar la frase.

**Consecuencia práctica:** cualquiera que lea ese goal para retomar el trabajo empezaría por
rehacer F0 y F1, que ya están hechas.

### 5.3 «El goal de tablas está HECHO» — **cierto ahora, falso cuando se declaró**

Se cerró el 2026-07-31 con **3 de sus 8 condiciones incumplidas** y un token (`--ds-table-empty-fg`)
apuntando a una variable que nunca existió. Lo corrigió el saneamiento del 2026-08-03. El patrón a
retener: *el cierre se declaró contra la intención, no contra la condición escrita.*

---

## 6. Estado del árbol al momento de parar

| Worktree / rama | Adelante | Atrás | Sin commitear |
|---|---|---|---|
| `main` | — | — | 2 archivos |
| `claude/admiring-bose-b4ef3c` | 3 | 0 | 5 archivos |
| `claude/competent-jepsen-dec1c4` | 3 | 0 | limpio |
| `claude/nostalgic-austin-50d4aa` | 1 | 0 | limpio |
| `claude/nostalgic-thompson-dceb00` | 3 | 0 | 9 archivos |
| `worktree-usabilidad-altas-y-medias` | 11 | **14** | 5 archivos |

Más **14 ramas sin worktree**, varias de ellas entre 339 y 399 commits por detrás de `main`.
