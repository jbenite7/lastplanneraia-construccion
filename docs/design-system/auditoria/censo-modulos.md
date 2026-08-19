---
capa: fuente
tipo: guia
estado: vigente
fecha: 2026-08-18
areas: [design-system]
fuente: docs/design-system/auditoria/censo-modulos.md
resumen: Generado por herramientas/censo.mjs y verificado contra public/index.php y admin/public/index.php (ver README.md §Por qué el censo se verifica y no se hereda)…
---

# Censo de módulos — el universo a recorrer

Generado por `herramientas/censo.mjs` y verificado contra `public/index.php` y
`admin/public/index.php` (ver `README.md` §Por qué el censo se verifica y no se hereda).
El dato de máquina está en `censo-modulos.json`.

**257 rutas · 41 pantallas HTML distintas · 22 módulos.**

## Qué cuenta como pantalla, y por qué el número baja de 52 a 41

*Pantalla* = ruta `GET` que rinde HTML **propio**. Excluidas, cada una con su motivo leído en el
controlador y anotado en `rutasQueNoRindenPantalla` del JSON:

- **APIs** — devuelven JSON o CSV. Por eso `plan-de-compras` tiene 70 rutas y **una** pantalla: es
  una SPA que sirve un shell y 69 endpoints.
- **Assets de `/runtime/`** — CSS y JS servidos por ruta, no pantallas.
- **Ocho rutas `GET` que mutan sesión y redirigen**: `/logout`, `/login/cancelar`, `/dev/entrar`,
  `/programa-general/set-filtro`, `/programacion-intermedia/set-filtro`,
  `/programacion-intermedia/set-view-all`, `/reportes/{tipo}` (responde `json_encode`) y
  `/legacy/cambiar_pagina.php` (solo `header(Location)`).
- **Tres alias que rinden una pantalla ya contada**, listados en `alias` para no perderlos:
  `/` y `/_aia/operacion/7f3c9b` rinden ambos `views/auth/login.view.php` —el segundo es la misma
  vista en modo mantenimiento, con otro `formAction`— y `/admin/dashboard` es `/admin`.

Contarlas habría inflado el denominador de cobertura con rutas que ningún golden puede capturar.

| Módulo | Rutas | Pantallas | Estado en el design system | Manifiesto |
|---|---:|---:|---|---|
| `autenticacion` | 13 | 3 | pilot (`auth`) | auth.json |
| `selector-de-proyectos` | 2 | 1 | pilot (`projects`) | project-selector.json |
| `programa-general` | 15 | 1 | pilot (`programa-general`) | programa-general.json |
| `cronograma` | 1 | 1 | pilot (`programa-general-actualizar`) | programa-general-actualizar.json |
| `programacion-intermedia` | 8 | 1 | pilot (`programacion-intermedia`) | programacion-intermedia.json |
| `programacion-semanal` | 9 | 1 | pilot (`programacion-semanal`) | programacion-semanal.json |
| `submodulo-cnp` | 4 | 1 | ausente-del-inventario | — |
| `submodulo-cnc` | 4 | 1 | ausente-del-inventario | — |
| `submodulo-cic` | 3 | 1 | ausente-del-inventario | — |
| `plan-de-compras` | 70 | 1 | pilot (`plan-compras-v2`) | plan-compras-v2.json |
| `profesionales` | 4 | 1 | pilot (`profesionales`) | profesionales.json |
| `subcontratistas` | 4 | 1 | pilot (`subcontratistas`) | subcontratistas.json |
| `control-de-cambios` | 3 | 1 | pilot (`control-cambios`) | control-cambios.json |
| `indicadores` | 2 | 1 | pilot (`indicadores`) | indicadores.json |
| `torre-de-control-bi` | 27 | 8 | pilot (`bi-runtime`) | bi-runtime.json |
| `integracion` | 2 | 0 | ausente-del-inventario | — |
| `escalamientos-y-crisis` | 10 | 2 | pilot (`escalamientos`) | escalamientos.json |
| `nucleo-y-runtime` | 12 | 0 | ausente-del-inventario | — |
| `legado` | 7 | 0 | ausente-del-inventario | — |
| `panel-admin` | 56 | 14 | inventory-only (`admin`) | — |
| `laboratorio-design-system` | 1 | 1 | ausente-del-inventario (`laboratory`) | laboratory.json |

## La cobertura real de las 41 pantallas

Cruce de cada pantalla contra los manifiestos (`docs/design-system/manifests/*.json`): qué
manifiesto la declara en `routes[]`, y si algún `scenarios[]` la apunta.

| | Pantallas | Qué significa |
|---|---:|---|
| Con escenario declarado | **16** (39%) | El manifiesto la declara y tiene al menos un escenario apuntándola |
| Declarada, sin escenario | **10** (24%) | El manifiesto la lista en `routes[]` y ningún escenario la cubre |
| Sin manifiesto | **15** (37%) | Ningún manifiesto la nombra |

**Las 10 declaradas sin escenario:** `/password/forgot` y `/password/reset` (`auth.json`);
`/programacion-semanal/{cnp,cnc,cic}` (`programacion-semanal.json` las declara en `routes[]`, y sus
dos escenarios apuntan solo a `/programacion-semanal`); y cinco de las ocho de BI —
`/bi/contratistas`, `/bi/intermedia`, `/bi/pdc`, `/bi/programa-general`, `/bi/responsables` —
donde `bi-runtime.json` tiene escenarios solo para `control-tower`, `curva-s` y `semanal`.

**Las 15 sin manifiesto:** las 14 de `admin/` y `/dashboard`, que es la **home autenticada** de la
aplicación — la primera pantalla que ve cualquiera que entra, y no la nombra ningún manifiesto.

## Lo que el cruce revela

Cuatro hechos, todos entrada del inventario y ninguno de la reparación:

1. **Seis módulos con pantalla no figuran en `modules[]` de `inventory.json`:** `submodulo-cnp`,
   `submodulo-cnc`, `submodulo-cic`, `integracion`, `legado` y `laboratorio-design-system`. Los tres
   submódulos **sí** están cubiertos por el manifiesto del padre (`programacion-semanal.json` los
   declara en `routes[]`), así que no están fuera del sistema: están declarados y sin escenario. Los
   otros tres no aparecen en ninguna parte. → `F0-001`

2. **`laboratory.json` y `foundation-shell.json` son manifiestos sin fila en `modules[]`.** El
   índice `manifests[]` los lista; el arreglo `modules[]` —el único que declara `status`— no. Un
   módulo con manifiesto y sin estado no lo alcanza ninguna consulta que parta de `modules[]`, que
   es como está escrito el propio `README.md` del design system. → `F0-002`

3. **`foundation-shell.json` declara 20 rutas y cero escenarios.** Es el manifiesto de mayor alcance
   del repositorio y el único sin ninguna evidencia visual atada. → `F0-003`

4. **`/dashboard` no la nombra ningún manifiesto.** Es la home autenticada. → `F0-004`

## Los dos front controllers no se mezclan

`admin/` es una mini-app aparte: su propio front controller (`admin/public/index.php`, 56 rutas),
su propio router, sus propios modelos y vistas, y su propio entrypoint CSS
(`admin/public/css/admin-entrypoint.css`, sobre AdminLTE). No las ve `scripts/wiki-arquitectura.mjs`
porque ese script lee `public/index.php` y nada más.

Su estado `inventory-only` es deliberado y vinculante: por decisión explícita del usuario, AdminLTE
permanece como framework de `admin/` y sus 14 vistas no se reescriben sobre el shell canónico. Se
audita igual —está en el censo— pero **su deuda se lee contra esa decisión**, no contra el contrato
de los módulos `pilot`. Un `.btn` de Bootstrap en `admin/` no es la misma clase de hallazgo que un
`.btn` de Bootstrap en Programa General.
