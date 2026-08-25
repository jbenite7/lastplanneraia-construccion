---
capa: fuente
tipo: reporte
estado: vigente
fecha: 2026-08-18
areas: [design-system]
fuente: docs/design-system/auditoria/inventario.md
resumen: Generado por herramientas/inventario-md.mjs desde hallazgos.json. No se edita a mano. La regla con la que se clasifica está en escala-severidad.md; cada…
---

# Inventario por severidad

**Generado por `herramientas/inventario-md.mjs` desde `hallazgos.json`. No se edita a mano.**
La regla con la que se clasifica está en `escala-severidad.md`; cada entrada lleva su `porQue`
en el JSON, que es lo que permite reinterpretarla si DS-F1 fija otra escala.

| Severidad | Hallazgos |
|---|---:|
| Crítico | 7 |
| Mayor | 31 |
| Menor | 13 |
| Cosmético | 6 |
| Sin problema | 11 |
| **Total** | **68** |

**10** esperan al frente `runtime-budgets-al-ci` para poder medirse; **2** llevan severidad estimada
y lo dicen. **21** vienen de la semilla, con su identificador original intacto.

## Crítico — 7

*Rompe una regla innegociable en superficie `pilot`, deja al sistema sin poder detectarlo, o rompe accesibilidad nivel A.*

| id | Módulo | Hallazgo | Dónde | Origen |
|---|---|---|---|---|
| `F0-030` | transversal | El baseline de deuda tolera 7161 hallazgos y la deuda real es 3896: el gate no puede detectar una regresion menor del 84% | `docs/design-system/audit-baseline.json:1` +1 | DS-F0 |
| `F0-031` | transversal | Una regla ausente del presupuesto de un modulo no se evalua nunca para ese modulo | `scripts/design-system-audit.mjs:369` +1 | DS-F0 |
| `F0-032` | programacion-intermedia | Cinco hex de la paleta clara, fijados con !important, en un modulo pilot de un producto dark ⏳ | `public/css/programacion-intermedia.css:1323` +4 | C-2 |
| `F0-051` | plan-de-compras | El gate estatico no escanea pdc-app/: ninguno de los 3896 hallazgos viene del Plan de Compras | `scripts/design-system-audit.mjs:25` | DS-F0 |
| `F0-052` | plan-de-compras | El gate propio del PDC comprueba dos condiciones y no lo ejecuta ningun script ni CI | `scripts/design-system-plan-compras-gate.mjs:30` +2 | DS-F0 |
| `F0-200` | vendor:handsontable | 63 de los 85 selectores de Handsontable no los alcanza ninguna hoja nuestra | `public/vendor/handsontable/handsontable.full.min.css` +1 | DS-F0 |
| `F0-201` | vendor:handsontable | El vendor trae su propia paleta clara y pinta con ella todo lo que ningun token alcanza ⏳ | `public/vendor/handsontable/handsontable.full.min.css` | DS-F0 |

## Mayor — 31

*Rompe una regla innegociable fuera de `pilot`, o es deuda estructural que impide migrar.*

| id | Módulo | Hallazgo | Dónde | Origen |
|---|---|---|---|---|
| `F0-001` | transversal | Tres modulos con pantalla no aparecen en ninguna parte del inventario del design system | `docs/design-system/manifests/inventory.json` | DS-F0 |
| `F0-002` | transversal | laboratory.json y foundation-shell.json son manifiestos sin fila en modules[] | `docs/design-system/manifests/inventory.json` +2 | DS-F0 |
| `F0-003` | transversal | foundation-shell.json declara 20 rutas y cero escenarios | `docs/design-system/manifests/foundation-shell.json` | DS-F0 |
| `F0-004` | escalamientos-y-crisis | /dashboard, la home autenticada, no la nombra ningun manifiesto | `docs/design-system/manifests/escalamientos.json` +1 | DS-F0 |
| `F0-005` | transversal | 24 de las 41 pantallas (59%) no tienen escenario que las cubra | `docs/design-system/auditoria/censo-modulos.md` | DS-F0 |
| `F0-020` | programacion-semanal | 167 de los 435 !important de Programacion Semanal apuntan a selectores propios del modulo | `public/css/programacion-semanal.css:399` +2 | C-2 |
| `F0-022` | programacion-semanal | Las tres pantallas de CNP, CNC y CIC estan declaradas en el manifiesto y ningun escenario las cubre | `docs/design-system/manifests/programacion-semanal.json` | DS-F0 |
| `F0-023` | programacion-semanal | 18 !important de Programacion Semanal apuntan a primitivas aia-* | `public/css/programacion-semanal.css` | DS-F0 |
| `F0-040` | cronograma | tom-select-premium-aia.css concentra 99 !important en 230 lineas y su presupuesto no declara la regla | `public/css/tom-select-premium-aia.css` +1 | DS-F0 |
| `F0-042` | transversal | Hay HTML con estilo en linea y color literal inyectado desde JavaScript, fuera del alcance de cualquier lectura de vistas | `public/js/funcionesGenerales6.js:11` +1 | DS-F0 |
| `F0-070` | autenticacion | login-brand-unified.css no entra en ninguna capa y llega por <link> directo | `public/css/login-brand-unified.css:1` +2 | C-2 |
| `F0-072` | autenticacion | login-brand-unified.css:148 consume --ds-active-text-tertiary, que no existe | `public/css/login-brand-unified.css:148` | DS-F0 |
| `F0-073` | autenticacion | /password/forgot y /password/reset estan declaradas en auth.json y ningun escenario las cubre | `docs/design-system/manifests/auth.json` | DS-F0 |
| `F0-090` | torre-de-control-bi | 38 de los 51 !important de BI apuntan a selectores propios y 10 a primitivas aia-* | `public/css/bi-control-tower.css` | DS-F0 |
| `F0-091` | torre-de-control-bi | BI escribe su markup con dos vocabularios a la vez: 88 utilidades tipo Tailwind y el catalogo aia-* | `public/css/design-system/adapters/bi-utilities.css` +1 | DS-F0 |
| `F0-093` | torre-de-control-bi | Tres de las ocho pestanas de BI quedan fuera de vista a 1180x820 ⏳ ≈ | `views/bi/_nav.php:6` +1 | C-23 |
| `F0-095` | torre-de-control-bi | Los graficos de BI se nombran y no se leen ⏳ | `public/js/modules/bi_chart_theme.js` | C-32 |
| `F0-097` | torre-de-control-bi | Cinco de las ocho pantallas de BI no tienen escenario | `docs/design-system/manifests/bi-runtime.json` | DS-F0 |
| `F0-101` | panel-admin | Trece de las catorce vistas de admin/ no declaran <main> y once no declaran <h1> | `admin/views/layouts/main.php` +1 | C-30 |
| `F0-102` | panel-admin | Catorce pantallas, el 34% del producto, fuera del catalogo de componentes | `admin/views/pages/` | DS-F0 |
| `F0-112` | transversal | El catalogo de componentes esta limpio y probado; lo que falta es que los modulos lo consuman | `views/dashboard/escalamientos.php` +2 | DS-F0 |
| `F0-120` | transversal | El 41% de los !important del repositorio no pelea contra ningun proveedor | `public/css/programacion-semanal.css` +3 | C-2 |
| `F0-122` | transversal | Cuatro archivos quedan en un sub-escalon de capa que nadie decidio | `public/css/styles.css:1` +3 | C-15 |
| `F0-123` | transversal | handsontable-header-global.css afirma tres veces estar fuera de capa y el agregador lo encapsula | `public/css/handsontable-header-global.css:6` +2 | DS-F0 |
| `F0-124` | transversal | Cinco tokens se consumen y nadie los define; tres no tienen fallback | `public/css/login-brand-unified.css:148` +6 | DS-F0 |
| `F0-126` | transversal | Los 31 hex de buttons.css son 31 veces color: #ffffff | `public/css/buttons.css:197` | C-2 |
| `F0-128` | transversal | Cuatro primitivas aia-* estan coloreadas con la paleta literal de Bootstrap 4 | `public/css/styles.css:1486` +3 | DS-F0 |
| `F0-202` | vendor:handsontable | 1545 lineas y 247 !important para gobernar 22 selectores de los 85 | `public/css/handsontable-module.css` +3 | DS-F0 |
| `F0-210` | vendor:datatables | 26 de los 48 selectores de DataTables no los alcanza ninguna hoja nuestra | `public/vendor/datatables/` +1 | DS-F0 |
| `F0-211` | vendor:datatables | .dataTables_processing, el indicador de cargando de toda tabla con carga por servidor, no lo alcanza ningun token ⏳ | `public/vendor/datatables/jquery.dataTables.css` | DS-F0 |
| `F0-214` | vendor:datatables | La deuda de DataTables no esta en su adaptador: esta en el modulo que lo esquiva | `public/css/programacion-semanal.css` +1 | DS-F0 |

## Menor — 13

*Desviación local con equivalente ya existente, o accesibilidad AA de geometría.*

| id | Módulo | Hallazgo | Dónde | Origen |
|---|---|---|---|---|
| `F0-012` | programa-general | Las dos ramas de la leyenda nombran los mismos siete estados con dos vocabularios distintos | `views/programa-general/programa_general.view.php:78` +1 | DS-F0 |
| `F0-021` | submodulo-cic | /programacion-semanal/cic repite siete ids entre dos modales que coexisten en el DOM | `views/programacion-semanal/CIC.view.php:154` +1 | F-5 |
| `F0-033` | programacion-intermedia | rgba() crudo dentro de un box-shadow, en modulo migrado, donde el token ya existe | `public/css/programacion-intermedia.css:262` | F-9 |
| `F0-034` | programacion-intermedia | Segunda sombra con rgb() crudo, esta no registrada en ninguna fuente anterior | `public/css/programacion-intermedia.css:378` | DS-F0 |
| `F0-071` | autenticacion | Catorce !important en una hoja que ya gana por estar fuera de capa | `public/css/login-brand-unified.css` | DS-F0 |
| `F0-081` | escalamientos-y-crisis | Escalamientos consume los tokens y casi no consume el catalogo de componentes | `views/dashboard/escalamientos.php` | DS-F0 |
| `F0-094` | torre-de-control-bi | El boton «Quitar filtro» de los chips mide 28x20 px en las ocho vistas ⏳ | `public/css/bi-control-tower.css` | F-4 |
| `F0-103` | panel-admin | La marca «AIA» de admin/ se queda a 0,04 del minimo de contraste ⏳ | `admin/views/layouts/main.php` | C-25 |
| `F0-127` | transversal | Tres iconos de estado en hex crudo dentro de la hoja compartida | `public/css/styles.css:398` +2 | C-2 |
| `F0-129` | transversal | Nueve literales white en la hoja compartida | `public/css/styles.css` | DS-F0 |
| `F0-203` | vendor:handsontable | La casilla nativa mide 13x13 px, la mitad del piso de 24 ⏳ | `public/vendor/handsontable/handsontable.full.min.css` | F-7 |
| `F0-204` | vendor:handsontable | La caja interna de la cabecera desperdicia 23 px por columna ⏳ | `public/css/handsontable-header-global.css` | C-16 |
| `F0-213` | vendor:datatables | La paleta del vendor mezcla grises casi negros con grises muy claros | `public/vendor/datatables/` | DS-F0 |

## Cosmético — 6

*Inconsistencia sin efecto funcional ni de contraste.*

| id | Módulo | Hallazgo | Dónde | Origen |
|---|---|---|---|---|
| `F0-041` | cronograma | El id duplicado de F-8 sigue en pie, y ademas el duplicado lo inyecta JavaScript | `views/programa-general-actualizar/programaGeneralActualizar.view.php:204` +1 | F-8 |
| `F0-053` | plan-de-compras | El shell de la SPA declara <main> y no declara <h1> ⏳ ≈ | `views/plan-compras/app.view.php` | DS-F0 |
| `F0-061` | control-de-cambios | Dos estados vacios apilados en /control-cambios, el segundo le quita fuerza al primero | `views/control-cambios/controlCambios.view.php:729` +1 | F-6 |
| `F0-111` | laboratorio-design-system | El unico style= del laboratorio esta en la familia que concentra las excepciones del contrato de evidencia | `views/design-system/families/states-feedback.php` | DS-F0 |
| `F0-205` | vendor:handsontable | La mitad de los gatillos decorativos de Programa General no estan marcados como decorativos | `public/js/modules/programa_general/hot.js` | C-37 |
| `F0-212` | vendor:datatables | Los cuatro estados de ordenacion deshabilitada estan escritos y nadie los ha visto en accion | `public/vendor/datatables/jquery.dataTables.css` | C-7 |

## Sin problema — 11

*Medido y conforme. Se registra a propósito: el inventario también dice dónde NO hay deuda.*

| id | Módulo | Hallazgo | Dónde | Origen |
|---|---|---|---|---|
| `F0-011` | programa-general | Los 8 ids duplicados de Programa General son falso positivo del escaneo estatico | `views/programa-general/programa_general.view.php:76` | DS-F0 |
| `F0-050` | plan-de-compras | Los 26 hex y el @layer ausente del PDC son decisiones escritas, no deuda | `pdc-app/src/styles.css:22` | DS-F0 |
| `F0-060` | control-de-cambios | Profesionales, Subcontratistas y Control de Cambios cumplen el contrato entero | `public/css/control-cambios.css` +2 | DS-F0 |
| `F0-080` | selector-de-proyectos | project-selector.css: cero !important en 342 lineas y 48 tokens consumidos | `public/css/project-selector.css` | DS-F0 |
| `F0-082` | indicadores | El «sin deuda» de /indicadores no significa lo mismo que en los demas modulos | `public/css/indicadores.css` +1 | C-22 |
| `F0-092` | torre-de-control-bi | Cuatro de las cinco vistas de BI no declaran <main> ni <h1>, y es correcto | `views/bi/_layout.php` | DS-F0 |
| `F0-096` | torre-de-control-bi | La paleta de los graficos es el mejor caso de color del repositorio | `public/js/modules/bi_chart_theme.js:1` +1 | DS-F0 |
| `F0-100` | panel-admin | admin/ cumple lo que promete: dark y tokenizado, sin migrar | `public/css/design-system/adapters/admin-lte.css` +1 | DS-F0 |
| `F0-110` | laboratorio-design-system | El laboratorio es el unico conjunto de hojas con cero !important, cero hex y cero rgb() crudo | `public/css/design-system/lab.css` | DS-F0 |
| `F0-121` | transversal | Quince de los 74 !important contra aia-* son el patron canonico correcto | `public/css/design-system/core.css:373` +1 | DS-F0 |
| `F0-125` | transversal | --hot-table-width parece roto en CSS y lo escribe JavaScript en tiempo de ejecucion | `public/js/modules/aia_ui/hot_table_width.js:66` +1 | DS-F0 |

⏳ = no medible hasta que `runtime-budgets-al-ci` deje un carril de referencia sano.
≈ = severidad estimada, no medida.
