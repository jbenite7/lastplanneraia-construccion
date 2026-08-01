# Goal — Dark mode en todos los módulos

**Slug:** `dark-mode-todos-los-modulos`
**Fecha de apertura:** 2026-07-25
**Estado:** specs escritos, sin ejecutar

## Objetivo

Que las 31 superficies HTML de la aplicación adopten el tema dark del design system,
eliminando los mecanismos de tema paralelos y las fuentes de fuga a claro, con gates que
impidan la regresión.

## Alcance

Las 31 superficies servidas por la app, en cuatro grupos:

- **A — migradas con manifiesto y presupuesto** (6): auth, project-selector, programa-general,
  programacion-intermedia, programacion-semanal (+CIC/CNC/CNP), laboratorio.
- **B — consumidores del agregador sin manifiesto** (7): pdc, profesionales, subcontratistas,
  control-cambios, programa-general-actualizar, indicadores, dashboard/escalamientos.
- **C — fuera del contrato del head** (9 rutas): las 8 rutas `/bi/*` y `/plan-compras`.
- **D — sin design system** (14 vistas): el mini-app `admin/`.

## Fuera de alcance

- **`/listado-actividades` y `/contratos`** — deprecadas. Decisión del usuario del 2026-07-29:
  salen del plan entero, no se les hace manifiesto, presupuesto ni evidencia. Son la interfaz del
  PDC viejo, y el mismo día `views/partials/shell_sidebar.php` retiró sus entradas del rail. El
  grupo B pasa de 9 superficies a 7. Consecuencias abiertas anotadas en `specs/F6-vendors.md`.
- Mobile, tablet y cualquier viewport bajo 1180 px (AGENTS.md).
- Reescribir las 14 vistas de `admin/` sobre el shell canónico. Decisión explícita del
  usuario: AdminLTE permanece como framework de `admin/` en este goal.
- Funcionalidad, datos, RBAC y rutas: este goal es exclusivamente de capa visual y de
  arquitectura de estilos.

## Estado medido al abrir (2026-07-25)

Medición con `node scripts/design-system-audit.mjs` sobre `main` en `8a13ad4`.

| Métrica | Valor |
|---|---|
| Hallazgos vivos del audit | 7 230 |
| `hardcoded-hex` | 806 (483 en `public/css/styles.css`) |
| `hardcoded-color-function` | 567 |
| `unauthorized-important` | 2 245 |
| `css-outside-layer` | 846 |
| `raw-token-in-module` | 730 |
| Presupuestos de ruta | 9 declarados, **`programacion-semanal` en rojo** |
| Raíces escaneadas | `views`, `public/js`, `public/css`, `src/View/Components` — **`admin/` no** |

## Debilidades estructurales identificadas

1. **`:root` es linen.** El default de la cascada es claro; el dark llega sólo por
   `[data-aia-theme="dark"]`. Lo que no carga `theme-bootstrap.js` cae en claro.
2. **Tres mecanismos de aplicación de tema**: `theme-bootstrap.js` (canónico), script inline
   en `views/bi/_layout.php`, atributo escrito a mano en `<html>` (plan-compras, laboratorio).
3. **`styles.css` en `layer(module)`**: 6 802 líneas, 483 hex, cero `--ds-active-*`, y `module`
   gana a `components` en la cascada. Emisor principal de la fuga a claro.
4. **`admin/` es punto ciego**, no faltante conocido: fuera de `scanRoots`, de `inventory.json`
   y de todo presupuesto.
5. **Sistema de tema muerto en paralelo**: `NavbarComponent.php` huérfano arrastra
   `dark-mode.css` (`body.dark-mode`, `--surface-bg`, `--text-main`) y `navbar.css`.
6. **Cobertura de gates dispareja**: 9 superficies del grupo B y las 9 rutas del grupo C
   pueden regresar a claro sin que nada falle.
7. **Vendors sin adaptador dark**: `tom-select-premium-aia.css`, `change-monitor.css`,
   `handsontable-module.css`.

## Decisiones tomadas

Diecisiete decisiones transversales, respondidas por el usuario en el grilleo de Plannotator.
Bundle en `interview.json`, respuestas en `interview-result.json`. Resumen en `facts.md`.

## Fases

| Fase | Spec | Depende de |
|---|---|---|
| F0 · Fundación de tema | `specs/F0-fundacion-tema.md` | — |
| F1 · Desmantelar `styles.css` | `specs/F1-styles-css.md` | F0 |
| F2 · Nueve superficies del agregador | `specs/F2-superficies-agregador.md` | F1 |
| F3 · BI | `specs/F3-bi.md` | F1 |
| F4 · Panel admin | `specs/F4-admin.md` | F0 |
| F5 · plan-compras | `specs/F5-plan-compras.md` | F0 |
| F6 · Vendors | `specs/F6-vendors.md` | F0; **T6.3 requiere F1 cerrado** |

**Orden de ejecución:** F0 → (F1 ∥ F4) → (F2 ∥ F3) → F6. F5 en cualquier momento tras F0.

Las tareas T6.1 y T6.2 de F6 pueden adelantarse en cualquier hueco tras F0. La consolidación de
selects (T6.3) espera a que F1 haya cerrado: `styles.css` pisa hoy tanto Select2 como Tom
Select, y migrar sobre esa cascada sería trabajar dos veces.

Cada fase tiene su propio ciclo spec → plan (`superpowers:writing-plans`) → gate de
Plannotator → ejecución subagent-driven + TDD.

## Enlaces

- `DESIGN.md` — contrato de consumo.
- `docs/design-system/README.md` — autoridad ejecutable.
- `AGENTS.md` — alcance visual (desktop ≥1180 px, dark).
- `goals/segmentacion-entrypoint-css/` — antecedente de la segmentación del entrypoint.
- `goals/design-system-nucleo-gobernanza/` — antecedente de gobernanza del núcleo.

---

## Cierre formal

**Estado:** CERRADO — absorbido por `cierre-dark-mode-y-tablas`
**Fecha de cierre:** 2026-07-31

### Justificación

Este goal fue reemplazado por [`cierre-dark-mode-y-tablas`](../cierre-dark-mode-y-tablas/goal.md),
que absorbió todas sus especificaciones, fases y deudas pendientes. El `validation-log.md`
(1 638 líneas) queda como historia consultable, no como trabajo abierto. Ninguna de las fases
F0–F6 se ejecutó bajo este goal; el diseño y las decisiones del grilleo siguen vigentes en
el goal sucesor.
