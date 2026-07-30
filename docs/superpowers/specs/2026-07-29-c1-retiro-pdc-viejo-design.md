# PDC · Fase C1 — Retirar el PDC viejo, y qué hacer con su dark a medias — Design

- **Fecha:** 2026-07-29
- **Ola:** 3 (lo grande), pero **con una decisión que aplica hoy**
- **Goal:** `goals/pdc-preparar-b1`
- **Origen:** roadmap maestro (fase C1) + un conflicto detectado al reunir los pendientes.
- **Estado (2026-07-30):** trabajo previo **cerrado** — censo, medición, decisión del dueño y manifiesto del
  v2 (puntos 1, 2 y 4). El retiro en sí sigue **bloqueado por la precondición**, y el alcance quedó
  reescrito: es más pequeño y más seguro de lo que decía el diseño original. Ver «Alcance de C1».

## El conflicto que se resolvió solo — y por qué queda escrito igual

Al reunir los pendientes se detectó una contradicción: **C1** retira el PDC viejo (`/pdc`,
`/api/pdc/*`, `OperationalFamilyPolicy`, la vista Handsontable), y al mismo tiempo se estaba invirtiendo
esfuerzo en migrarlo a dark. Trabajo sobre una pantalla condenada.

La decisión del usuario fue **parar el dark y acelerar C1**.

**Lo que se encontró al ir a aplicarla:** la decisión llegó tarde. El trabajo ya estaba **terminado y
commiteado** el mismo 2026-07-29:

- `a3d59e8` — `/pdc` cierra F2: `public/css/pdc.css` entra en `@layer module`, 22 de 23 `rgba()` pasan a
  tokens, y el defecto real (`.pdc-message-neutral`, tinta casi negra sobre canvas oscuro) queda
  corregido. Ese commit además **corrige la premisa con la que se abrió la tarea**: `/pdc` no se pintaba
  en claro — el body ya estaba en `rgb(11,16,13)` porque el grueso de la hoja ya usaba tokens. Lo que
  faltaba eran sombras, bordes y acentos.
- `c5af102` — el panel de limpieza del Plan de Compras (`PdcResetService`, `PdcMaintenanceController`,
  el seed y su test) también está commiteado, con sus cuatro salvaguardas antes de borrar.

O sea: **no hay nada que triar ni que congelar**. No quedó trabajo sin versionar y no hay archivos en
riesgo de morir con una rama.

### Qué queda de esa decisión

- **No se abre más trabajo de diseño sobre `/pdc`.** Lo hecho, hecho está; lo que venga se invierte en
  retirarla, no en pulirla.
- **`PdcResetService` y su panel de mantenimiento sobreviven al retiro.** No son del PDC viejo: sirven
  para vaciar el PDC **v2** de un proyecto y rehacer el flujo desde la carga del presupuesto. C1 no los
  toca.
- **La lección:** la contradicción se detectó al inventariar pendientes, no al planificar la tarea. Si el
  inventario se hubiera hecho antes, esas horas se habrían ahorrado. No es un reproche al trabajo hecho
  —que además corrigió una premisa falsa y arregló un defecto real de contraste— sino el motivo por el
  que este goal empieza por reunir los pendientes de todos los frentes.

## Alcance de C1 — corregido el 2026-07-30 por medición, no por opinión

> **Este apartado se reescribió.** El alcance original daba por ciertas dos cosas que el censo desmintió,
> y las dos habrían roto módulos sanos. La evidencia está en
> [`goals/pdc-preparar-b1/evidence/censo-consumidores-pdc-v1.md`](../../../goals/pdc-preparar-b1/evidence/censo-consumidores-pdc-v1.md).
> Lo que sigue es el alcance real; el original queda descrito al final para que se entienda el cambio.

### Se retira

**Rutas y controladores** — ruta por ruta, **nunca por prefijo**:

- `/pdc` (`public/index.php:127`) y `App\Controllers\Gestion\PdcController`.
- `/api/pdc/list`, `/api/pdc/save`, `/api/pdc/update-cell`, `/api/pdc/duracion-sugerida`
  (`index.php:180-183`) y `App\Controllers\Api\PdcApiController`.
- `/api/pdc/plantillas`, `/api/pdc/plantillas/{id}`, `/api/pdc/plantillas/{id}/items`,
  `/api/pdc/categorias-recurso` (`index.php:272-275`) y `PdcPlantillaController`. **Ya están muertas hoy:**
  cero consumidores, y sus tablas ni existen en la base.

**Vista y assets** — caen juntos o no cae ninguno:

- `views/pdc/pdc.view.php`, `public/css/pdc.css`, `public/js/modules/pdc/hot.js`.

**Navegación:**

- `views/design-system/families/shell-navigation.php:45` y `public/js/modules/info_general_nav.js:23,42`.

**Sistema de diseño:**

- `docs/design-system/manifests/pdc.json` y su golden, más las entradas de `pdc` en `inventory.json`,
  `exceptions.json`, `state-token-exceptions.json`, `ui-groups-inventory.json` y
  `unlayered-delivery-inventory.json`.

**Pruebas:** las específicas del v1, y la poda de `/pdc` en los fixtures compartidos (`operationalCycle.mjs`,
`moduleFlows.mjs`, `apiPayloads.mjs`, `routes.spec.mjs`, `shell-sidebar-rollout.mjs` y las suites de diseño
que iteran rutas). Son 35 archivos en total; el censo los lista.

### NO se retira — y esto es lo que corrigió la medición

- **`OperationalFamilyPolicy` y el modelo de «familias».** El alcance original lo condicionaba a «si no lo
  consume nada más». **Lo consumen:** `src/Support/ActivityMatcher.php:13,18` y
  `src/Services/SemiAutoService.php:9,27,57,60`, ambos transversales, más tres tests. El prefijo
  `general_pdc_*` de sus tablas engaña sobre el dueño real, que es el motor semi-automático compartido.
  Retirarlo rompe Listado de Actividades y Contratos, que no están en retiro. **Fuera de alcance.**
- **Los trece `/api/pdc/auto/*`** (`index.php:276-288`). Doce apuntan a `SemiAutoController`, el mismo
  controlador que sirve a `/contratos` y `/listado-actividades` bajo los contratos `auto/*` que `AGENTS.md`
  declara compartidos. Y `/api/pdc/auto/apply-from-contratos` es la **sucesora moderna** de un legado ya
  retirado (`test_lacp_legacy_cleanup_readiness.php:37`): retirarla resucita esa deuda. Renombrar ese
  namespace es una decisión de producto aparte, no parte de C1.
- **`PdcResetService` y `PdcMaintenanceController`.** Son del **v2** — vacían el plan de compras nuevo de
  un proyecto. Punto 5 de la condición de hecho.
- **Las tablas.** Ver el apartado siguiente.

### Los datos históricos: decidido, ya no está abierto

**Decisión de Felipe del 2026-07-30: opción A — conservar, y el retiro no toca las tablas.**

Se tomó sobre medición, no sobre criterio técnico. De las 370 filas de `pdc` en 4 obras: las 370 tienen
nombre de paquete y 269 tienen estado, pero **cero** tienen valor adjudicado, anticipo o vencimiento de
pólizas, y solo 8 tienen alguna fecha real. Es la *definición* de los paquetes, no su *ejecución*. Y el
dato que inclinó la decisión: **tres de las cuatro obras (27, 68, 74) no tienen ni una versión de
presupuesto en v2**, así que para ellas borrar no sería migrar, sería perder.

**Consecuencia para quien ejecute C1:** no hay borrado, no hace falta gate de Plannotator por este lado, ni
respaldo con estrategia de restauración. `pdc`, `papelera_pdc` y las tablas de familias se quedan quietas,
sin lectores una vez retiradas las rutas. **C1 es hoy un retiro de código, no una operación sobre datos.**

Queda escrito qué haría falta para revisar la decisión: que se confirme que las obras 27, 68 y 74 están
cerradas o son de prueba, y que la 73 ya tiene en v2 lo que necesita. Ahí la opción B (exportar y borrar)
volvería a ser razonable, con su gate y su respaldo.

### El alcance original, y por qué cambió

Decía: «la ruta `/pdc` y las `/api/pdc/*` del front controller · la vista Handsontable y su CSS ·
`OperationalFamilyPolicy` y el modelo de familias, si no lo consume nada más — hay que comprobarlo antes»,
más los datos históricos como decisión abierta.

Dos de esas afirmaciones eran falsas, y el propio spec mandaba comprobarlas antes de actuar. Se comprobó y
no lo eran. Que la regla estuviera escrita es lo que evitó romper dos módulos sanos: **si esto se hubiera
ejecutado como estaba redactado, `/contratos` y `/listado-actividades` habrían caído con el PDC viejo.**

### Precondición innegociable

C1 **no empieza** hasta que el PDC v2 esté validado en producción con una obra trabajando de verdad. Es la
condición que ya fijaba el roadmap («cuando A+B estén validados en producción») y el comité no la cambió.

## La lección que hay que dejar escrita

Antes de borrar un asset o una vista: **grepear `public/js`, `views` y `src` ignorando comentarios, y
buscar los tests que lo leen**. Ya pasó una vez en este repositorio — un CSS declarado «solo comentarios
históricos» tenía un consumidor vivo en JavaScript. Esa comprobación es parte del trabajo, no una
precaución opcional.

**Volvió a pagar el 2026-07-30, y van dos.** `public/js/modules/pdc/hot.js` llama a `/api/pdc/list`
(líneas 651 y 743) y a `/api/pdc/save` (951 y 1071), cargado desde `views/pdc/pdc.view.php:1374`, que
además llama a `/api/pdc/save` en cuatro sitios más. Y el mismo grep destapó las dos correcciones de
alcance del apartado anterior. Medir antes de tocar no fue una formalidad: evitó tumbar dos módulos que no
estaban en retiro.

## Además: la deuda de diseño del PDC nuevo

El módulo v2 entró al inventario del sistema de diseño como **`inventory-only`**: consume el shell y pasa
su gate, pero no tiene manifiesto de piloto con escenarios y evidencia. Declararlo migrado sin haberlo
hecho sería falso, y así se registró.

Cerrar esa deuda es trabajo de la migración de diseño propiamente dicha. Va en esta ola porque es el
momento coherente: el PDC viejo se retira y el nuevo pasa a ser **el** plan de compras de la empresa —
merece estar en el sistema de diseño como tal, no como inventario.

Ojo con la trampa ya medida: un manifiesto no se puede crear en seco. Exige al menos un escenario con su
golden real y su `sha256` coincidente.

### Cerrada el 2026-07-30

`plan-compras-v2` pasa de `inventory-only` a `pilot` con
[`docs/design-system/manifests/plan-compras-v2.json`](../../design-system/manifests/plan-compras-v2.json),
escenario y golden real. Tres decisiones que conviene no deshacer sin leer por qué se tomaron:

- **`vendors: []`** no es un olvido: la vista v2 no carga ninguna librería por CDN — tokens, design system
  e isla React empaquetada salen todos del propio dominio. Es la diferencia de fondo con `pdc.json`, que
  conserva nueve librerías externas más Google Fonts y por eso no puede declarar `consumerContract v1`.
- **`state: "empty"`, no `"normal"`.** El golden es la pantalla de importar presupuesto todavía vacía, del
  sandbox 990100. Deliberado: es la primera pantalla de un proyecto nuevo y es el único contenido
  **estable**, porque la única obra con datos v2 la escriben otras sesiones y su golden cambiaría solo.
- **La trampa se confirmó, y hay una segunda:** además del golden real,
  `tests/design-system/contracts.test.mjs:249` es un `deepEqual` contra una lista fija de manifiestos. Un
  manifiesto nuevo obliga a ampliarla **en el mismo commit**. Y el gate exige árbol limpio: hay que
  commitear antes de correrlo.

Verificado: `node scripts/design-system-contracts.mjs` → PASS; `npm run test:design-system:static` →
358/359, con el único rojo (`foundation.test.mjs:273`) ambiental del worktree — ese test corre PHP dentro
del contenedor, que sirve el árbol principal, y lo compara contra el `tokens.css` del worktree propio.

**Consecuencia para el retiro:** cuando C1 se ejecute habrá que retirar también `pdc.json` y su golden. Por
eso esta deuda se cerró **antes** del retiro y no después: el v2 ya tiene su sitio en el sistema de diseño
cuando el v1 deje el suyo.

## Condición de hecho

1. **HECHO (2026-07-30).** Está medido y escrito quién consume todavía el modelo viejo, con el grep
   completo como evidencia → `goals/pdc-preparar-b1/evidence/censo-consumidores-pdc-v1.md`.
2. **HECHO (2026-07-30).** Hay decisión escrita del dueño del producto sobre los datos históricos del v1:
   **opción A, conservar; el retiro no toca las tablas.** Ver el apartado «Los datos históricos».
3. **Bloqueado por la precondición.** Retiradas las rutas, la aplicación arranca, la suite pasa y ninguna
   pantalla queda enlazando a `/pdc`.
4. **HECHO (2026-07-30).** El PDC v2 tiene manifiesto de piloto con escenarios y evidencia, y deja de ser
   `inventory-only` → `docs/design-system/manifests/plan-compras-v2.json`, con golden real
   (`sha256: cd5523bd…`) y `contracts.test` ampliado. Gate en verde.
5. **Solo verificable después del retiro.** `PdcResetService` y el panel de mantenimiento siguen
   funcionando: no eran del PDC viejo y no deben caer con él.

## Riesgos

- **Retirar antes de tiempo deja a una obra sin herramienta.** La precondición está para eso y no se
  negocia por comodidad de calendario. **Al 2026-07-30 sigue incumplida, y no por poco:** lo desplegado es
  `prueba-lps`; la producción real sigue en `1aa7c69` del 2026-07-16, con **cero tablas `pdc_*`**. No es
  que falte una obra usando el v2 — es que el v2 no existe ahí.
- **Retirar por prefijo en vez de ruta por ruta rompe módulos sanos.** Trece `/api/pdc/auto/*` sirven a
  `/contratos` y `/listado-actividades`. Es el error más fácil de cometer al ejecutar esto con prisa.
- **~~Los datos históricos son irreversibles.~~** Neutralizado por la decisión del 2026-07-30: no hay
  borrado. Si alguna vez se revisa a favor de borrar, este riesgo vuelve entero y con él el gate de
  Plannotator, el respaldo verificable y la estrategia de restauración.
