---
capa: wiki
tipo: decision
estado: vigente
fecha: 2026-07-23
areas: [bi]
fuente: memoria-claude
origen: lps-aia-powerbi-indicadores
resumen: /indicadores dejó Data Studio y embebe Power BI (publish-to-web); limitaciones y el ajuste de ancho dinámico por altura
---
`views/indicadores/indicadores.view.php` (informes de Last Planner) **deprecó los 6 reportes de Google Data Studio** y ahora embebe un único reporte de **Power BI** (`app.powerbi.com/view?r=…`, publish-to-web).

Limitaciones aceptadas del `publish-to-web` (Opción C del usuario): es **público por link** y **NO** admite filtrado por proyecto vía URL ni control por la JS API de Power BI (verificado en Microsoft Learn) → todos los proyectos ven el mismo reporte. El filtrado real por proyecto exigiría **Power BI Embedded** (capacidad Azure + service principal + endpoint de embed-token) — pendiente. Los roles restringidos `["G","S","SG","C"]` no ven el dashboard (se preservó ese límite RBAC).

Presentación final aprobada: reporte a **ancho completo** (full-bleed `100vw` centrado) con función JS `ajustarInformePowerBI()` que **dimensiona por la ALTURA libre visible** (reporte de forma fija ~980×600: alto = espacio disponible, ancho = alto×proporción, tope 95% de holgura), reajustando en `resize`/`onload`. Sin la fila de botones (en `main` eso quitó el enlace "BI Curva S").

Entregado en `main`: PR #4 (hotfix base, `3ad3ab5`) + PR #5 (ancho completo, `35cfa1e`), ambos mergeados; y como parche quirúrgico ya vivo en producción. Ver [[produccion-deploy]].
