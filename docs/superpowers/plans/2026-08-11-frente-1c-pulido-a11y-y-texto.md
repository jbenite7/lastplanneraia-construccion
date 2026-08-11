# Frente 1 · Tanda 1C — Pulido visual, accesibilidad y texto: plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Cerrar los 16 hallazgos abiertos de pulido visual, accesibilidad y texto — y con ellos el Frente 1 entero.

**Architecture:** **Siete de los dieciséis se cierran sin tocar código**, porque al medir sus premisas contra el repo de hoy resultaron falsas, ya arregladas, o diferidas por el usuario. Eso no es un atajo: es el entregable. Los nueve restantes se agrupan por la pieza que gobiernan, y **dos de ellos cambian comportamiento** —el botón bloqueado que pasa a focalizable y la tecla `Esc` en los modales—, así que van con su medición previa y su verificación de los dos roles.

**Tech Stack:** PHP 8.3 y JavaScript ES5 (jQuery + Handsontable + DataTables), React/TypeScript en `pdc-app/`, CSS por tokens del design system, Playwright para lo que solo se ve en navegador.

**Spec:** [`2026-08-10-programa-cierre-pendientes-design.md`](../specs/2026-08-10-programa-cierre-pendientes-design.md), Frente 1, tanda 1C.

**Tanda anterior:** [`2026-08-10-frente-1b-cascada-lps.md`](2026-08-10-frente-1b-cascada-lps.md). Este plan se escribió **al terminar aquella** y no antes, a propósito: el de la 1A se quedó obsoleto a mitad porque el repo se movió mientras se ejecutaba, y en la 1B tres premisas caducaron en horas.

## Global Constraints

- **Docker Compose es el runtime.** Exporta `COMPOSE_FILE=docker-compose.wt.yml` **antes de cualquier comando**, incluidos los `npm run test:*`: sin eso `docker compose exec app` resuelve al contenedor del árbol principal y mides el árbol vecino. App de este worktree en **`http://localhost:8091`**.
- **La sesión se abre por la puerta de servicio**, nunca por `/login`: `http://localhost:8091/dev/entrar?u=test.A&p=PDC%20Sandbox%20E2E`. Roles: `test.A`, `test.R`, `test.V`.
- **Dark a 1180×820.** No se valida en claro ni en móvil.
- **Ningún hex suelto, ningún estilo en línea nuevo, ninguna variante local** en módulo migrado: se consume token (`DESIGN.md`).
- **CSRF en toda mutación autenticada.** Prepared statements a través de `Database`.
- **Empieza cada tarea comprobando su premisa contra el código de hoy.** Los números de línea envejecen en horas en este repo. Si la premisa cambió, **corrige el plan en vez de arrastrarlo, y escribe el resultado en la ficha aunque contradiga lo registrado.**
- **No repitas lógica en varios módulos.** Si un arreglo cae en varias vistas, va a una pieza compartida.
- **Las decisiones del usuario se acumulan y esta sesión no para nunca.** Si un paso necesita su criterio: anótalo en `docs/decisiones-pendientes.md` con id `D-F1-<n>` (ya existen `D-F1-1` y `D-F1-2`) midiendo antes, **sáltate ese hallazgo** y sigue. **No lo resuelvas con el supuesto más conservador.**
  - **Única excepción:** un `200` donde se espera `403` no se encola — se avisa de inmediato.
- **Todo gate se entrega con una mutación que lo pone rojo, ejecutada.**
- Si añades pruebas PHP, **etiquétalas** `// @requiere: <nivel>` o `scripts/run-php-tests.php` sale con código 2.
- Commits atómicos, uno por tarea. Nunca `.env`, nunca `docker-compose.wt.yml`.

## Los 16 hallazgos: qué midió este plan antes de escribirse

**Siete se cierran sin código.** Medidos contra `b726d400` el 2026-08-11:

| ICE | Hallazgo | Qué se midió | Tarea |
|---|---|---|---|
| 300 | «La frase de Control de Cambios es provisional» | **Falso.** `grep -niE "provisional\|TODO\|ratific" views/control-cambios/controlCambios.view.php` no devuelve ninguna marca. El texto ya es definitivo (`:729`). Lo que la ficha pedía —quitar la marca— no existe | **10** |
| 280 | «Los chips de PI y PS envuelven a dos líneas por un ancho fijo de 155 px» | **Falso, ya arreglado.** Los `155px` solo sobreviven como **comentario histórico** en `public/css/design-system/adapters/legacy-bridge.css:468`. La regla vigente (`:462-481`) da `width: auto` a los tres módulos a la vez | **10** |
| 252 | «`/control-cambios` repite doce ids, y diez son buscadores de columna» | **Falso.** `grep -oiE "id=['\"][^'\"]+" … \| sort \| uniq -d` devuelve **vacío**: cero ids duplicados hoy, ni siquiera ignorando mayúsculas | **10** |
| 160 | El tabulador acolchado de la barra lateral | **Diferido por el usuario**, con motivo escrito en su propia ficha: cambia cómo navega con teclado quien ya está acostumbrado, y lo importante (anillo de foco en las 20 paradas) ya está bien | **10** |
| 140 | «La fila 1 del sandbox trae contenido de HOMECENTER CALI que no coincide con el seed» | **No es un bug de importación.** «HOMECENTER CALI» aparece cientos de veces en `database/seeds/biblioteca_maestra_pdc_source_of_truth_v1_0.json` (p. ej. `:11475`): es dato real de la biblioteca maestra del PDC, no residuo de una importación manual | **10** |
| 125 | Los tres textos de dominio | El **único** aprobado —(1), el vacío de PS al filtrar— **ya se hizo** en la tanda 1B (`e9c74933`). Los otros dos ya estaban cerrados como descartados | **10** |
| 98 | Fase 6 del design system | Es **solo una decisión** (aprobar el inventario de excepciones), y la campaña de reducción está declarada **fuera** de este programa por el propio spec | **10** → cola |

**Nueve llevan código:**

| ICE | Hallazgo | Tarea |
|---|---|---|
| 324 | El único estado vacío malo está en la pantalla que se le enseña al cliente | 1 |
| 320 | El chip «Auto-Guardado» no se oculta nunca | 2 |
| 252 | La densidad no gana filas: la columna «Actividad» gobierna el alto | 6 |
| 216 | El botón flotante tapa datos | 5 |
| 216 | El historial del PDC dice qué no pasó y no dice qué hacer | 1 |
| 192 | El motivo del botón bloqueado solo llega con el ratón — **cambia comportamiento** | 7 |
| 180 | `Esc` no cierra los modales — **cambia comportamiento** | 8 |
| 180 | Programación Intermedia no tiene acción primaria | 4 |
| 162 | La asimetría de reserva de color | 3 |

### La premisa que hay que corregir antes de arreglarla, porque el arreglo cambia

**ICE 320 — el chip «Auto-Guardado».** Su síntoma es **cierto** y su explicación es **falsa**, y la diferencia decide dónde va el arreglo.

La ficha dice que la clase `badge-badge-hidden` «solo existe bajo `.pg-page .pg-status-badges` y Actualizar Cronograma no está bajo ninguno de los dos ámbitos». Medido en vivo el 2026-08-11 a 1180×820 en dark, con la página cargada:

```js
document.body.className                                   // "aia-shell aia-shell--sidebar pg-page"
document.getElementById('save-status').parentElement.className  // "pg-status-badges"
el.matches('.pg-page .pg-status-badges .badge-badge-hidden')    // true   ← el selector SÍ casa
getComputedStyle(el).display                              // "inline-flex"  ← y aun así se ve
el.getBoundingClientRect()                                // {w: 114, h: 28}
```

**El selector casa perfectamente.** La causa real es otra: recorriendo `document.styleSheets` en esa página, **ninguna hoja cargada contiene la regla**. Las hojas que llegan son `core.css`, `tokens.css` y **`programa-general-actualizar.css`** — `programa-general.css`, que es donde vive la regla (`:231`), **no se carga en esta pantalla**.

Es decir: la regla existe en el repo, el elemento la reclama, y nunca se encuentran. Arreglarlo cambiando el ámbito del selector —que es lo que la ficha sugiere— no habría servido de nada.

## File Structure

| Archivo | Responsabilidad | Tarea |
|---|---|---|
| `views/indicadores/indicadores.view.php:272` | `sEmptyTable` de DataTables que dice «=(» | 1 |
| `pdc-app/src/pages/ImportarPresupuesto.tsx:436` | Estado vacío del historial de presupuesto | 1 |
| `public/css/programa-general-actualizar.css` | La regla de ocultamiento que a esa página no le llega | 2 |
| `public/js/modules/programa_actualizar/hot_actualizar.js:1381,1384` | La reserva de color asimétrica | 3 |
| `views/programacion-intermedia/programacion_intermedia.view.php:50` | Acción primaria de la toolbar | 4 |
| `public/css/handsontable-module.css:684-710` y el contenedor de scroll | El botón flotante y el hueco al final | 5 |
| `public/css/handsontable-module.css:198-204` | `.force-wrap` de la columna «Actividad» | 6 |
| `views/profesionales/profesionales.view.php:313` | Botón bloqueado focalizable con su motivo | 7 |
| `public/js/design-system/modal-escape.js` | **Nuevo.** `Esc` cierra los modales de backdrop estático, para todas las vistas | 8 |
| `docs/EXPERIMENTS.md`, `docs/decisiones-pendientes.md` | Disposición escrita de los 16 | 10 |

---

### Task 1: Los dos estados vacíos que no dicen cómo se llenan

Junta dos hallazgos porque son **el mismo defecto de escritura** en dos pantallas: decir qué no hay, sin decir qué hacer.

- **ICE 324** — `/indicadores` dice «Ningún dato disponible en esta tabla =(». No dice qué falta, no dice qué hacer, y el emoticono le quita credibilidad a la vista que la gerencia usa para sostener cifras ante la junta.
- **ICE 216** — el historial del Plan de Compras dice «Todavía no se ha importado ningún presupuesto en este proyecto.»: cierto, pasivo y sin salida, justo debajo del cargador de Excel que sí la tiene.

**Premisas verificadas el 2026-08-11 sobre `b726d400`:** las dos ciertas, con los sitios exactos.
- `views/indicadores/indicadores.view.php:272`, dentro del objeto `idioma_espanol` de DataTables declarado en la propia vista (`:267`). **No es un literal en el HTML.**
- `pdc-app/src/pages/ImportarPresupuesto.tsx:436`: `overlayNoRowsTemplate={vacioTabla("Todavía no se ha importado ningún presupuesto en este proyecto.")}`. Única ocurrencia.

**El patrón bueno ya existe en el repo tres veces** y siempre explica **cómo se llena** la tabla: `views/programacion-semanal/CNC.view.php:547` («Sin causas de no cumplimiento esta semana. Se registran al justificar un avance menor al compromiso en Programación Semanal.»), y sus hermanos `CNP.view.php:714` y `CIC.view.php:1505`. Es paridad con lo que la propia app ya hace bien, no invención.

**Files:**
- Modify: `views/indicadores/indicadores.view.php:272`, `pdc-app/src/pages/ImportarPresupuesto.tsx:436`

**Interfaces:** Consumes: nada. Produces: nada.

- [ ] **Step 1: Comprobar las dos premisas y leer el patrón bueno**

```bash
export COMPOSE_FILE=docker-compose.wt.yml
sed -n '265,275p' views/indicadores/indicadores.view.php
sed -n '430,440p' pdc-app/src/pages/ImportarPresupuesto.tsx
sed -n '545,549p' views/programacion-semanal/CNC.view.php
```

Si alguna ya está reescrita, **para y dilo**: se cierra como ya hecha en vez de tocarla.

- [ ] **Step 2: Reescribir el de `/indicadores`**

Sin emoticono, diciendo de dónde salen los datos:

```php
"sEmptyTable": "Sin datos para el filtro seleccionado. Los indicadores se calculan a partir de los compromisos confirmados en Programación Semanal: si la semana aún no se ha cerrado, todavía no hay nada que mostrar.",
```

> **Antes de escribirlo, verifica que la frase es cierta.** Lee de dónde salen realmente los datos de esa tabla (`IndicadoresController` y la consulta que la alimenta). Si no vienen de los compromisos confirmados, **escribe la procedencia real**: un estado vacío que explica mal es peor que uno que no explica. Si no consigues determinarla con seguridad, deja el texto sin la segunda frase (solo «Sin datos para el filtro seleccionado.») y dilo en el informe.

- [ ] **Step 3: Reescribir el del historial del PDC**

La reescritura está propuesta en la propia ficha y apunta a la acción que está a diez centímetros:

```tsx
overlayNoRowsTemplate={vacioTabla("Todavía no se ha importado ningún presupuesto. Sube el Excel aquí arriba y esta lista guardará cada versión para que puedas compararlas.")}
```

- [ ] **Step 4: Reconstruir la SPA del PDC**

`pdc-app/` es React + Vite y publica su bundle en `public/pdc-app/`. Un cambio en el `.tsx` **no se ve** hasta reconstruir:

```bash
cd pdc-app && npm run build && cd ..
git status --porcelain public/pdc-app | head
```

Esperado: el bundle de `public/pdc-app/` aparece modificado. **Si el build genera un diff enorme de archivos con hash**, dilo en el informe y **commitea el bundle igualmente** si es lo que el repo ya versiona — compruébalo con `git log --oneline -3 -- public/pdc-app`. Si el repo **no** versiona el bundle, no lo añadas.

- [ ] **Step 5: Verificar los dos en navegador**

A 1180×820 en dark, como `test.A`:
1. `/indicadores` con un filtro que no devuelva datos → el nuevo texto, **sin** `=(`.
2. `/plan-compras`, historial de versiones, en un proyecto **sin** presupuesto importado → el nuevo texto.

Consola sin errores nuevos.

- [ ] **Step 6: Commit**

```bash
git add views/indicadores/indicadores.view.php pdc-app/src/pages/ImportarPresupuesto.tsx
git commit -m "fix(estados-vacios): las dos tablas vacias dicen como se llenan, no solo que estan vacias

/indicadores decia «Ningun dato disponible en esta tabla =(» en la pantalla
que la gerencia usa para sostener cifras ante la junta, y el historial del
PDC decia que no habia presupuesto sin senalar el cargador que tiene justo
encima. Paridad con CNC/CNP/CIC, que ya explican de donde salen sus datos."
```

---

### Task 2: El chip «Auto-Guardado» se oculta, y por el motivo correcto

**ICE 320.** La causa registrada en la ficha es falsa y la verdadera está medida arriba: **la hoja que lleva la regla no se carga en esa página.**

**Files:**
- Modify: `public/css/programa-general-actualizar.css`
- Read: `public/css/programa-general.css:231`

**Interfaces:** Consumes: nada. Produces: nada.

- [ ] **Step 1: Confirmar la causa real antes de arreglar**

En el navegador, en `/programa-general-actualizar` a 1180×820 en dark:

```js
const el = document.getElementById('save-status');
JSON.stringify({
  casa: el.matches('.pg-page .pg-status-badges .badge-badge-hidden'),
  display: getComputedStyle(el).display,
  hojas: [...document.styleSheets].map(s => (s.href||'inline').split('/').pop().split('?')[0]),
})
```

Esperado: `casa: true`, `display: "inline-flex"`, y **`programa-general.css` ausente** de la lista. Si `programa-general.css` **sí** aparece, la causa cambió: para y vuelve a medir.

- [ ] **Step 2: Dar a esa página la regla que reclama**

En `public/css/programa-general-actualizar.css`, que es la hoja que esa pantalla **sí** carga:

```css
/* El chip nace con `badge-badge-hidden` y el selector de `programa-general.css:231`
   casa perfectamente con el —el body lleva `pg-page` y el padre es
   `.pg-status-badges`—, pero esa hoja NO se carga en Actualizar Cronograma:
   aqui llega `programa-general-actualizar.css`. La regla existia en el repo y
   nunca se encontraba con su elemento, asi que el chip anunciaba un guardado
   que no habia ocurrido desde que la pantalla existe. Medido el 2026-08-11. */
.pg-page .pg-status-badges .badge-badge-hidden {
    display: none;
}
```

- [ ] **Step 3: Verificar que se oculta al cargar y aparece al guardar**

Las dos mitades, porque ocultarlo para siempre sería el defecto contrario:

1. Cargar `/programa-general-actualizar` → el chip **no** se ve (`display: none`, `rect.width === 0`).
2. Editar una celda → aparece «Guardando... (1)» y luego «Auto-Guardado». Es el chip que la tanda 1B cableó a la pieza compartida: si no aparece, has roto la Task 2 de 1B.
3. **Restaura la celda** y verifica la restauración con una consulta.

- [ ] **Step 4: El golden congelado**

La ficha dice que este defecto quedó **congelado en la evidencia** del golden del módulo: «cuando se arregle, el golden fallará y pedirá recaptura, que es lo correcto».

```bash
export COMPOSE_FILE=docker-compose.wt.yml
npm run test:design-system:static
```

Si un carril pide recaptura, **no la regeneres por tu cuenta**: `AGENTS.md` exige aprobación explícita para cambios visuales. Anótalo en `docs/decisiones-pendientes.md` con id `D-F1-<n>`, di exactamente qué golden y por qué, y **deja el arreglo puesto**. Este es el caso donde la recaptura está *anticipada por la propia ficha*, pero anticiparla no es aprobarla.

- [ ] **Step 5: Commit**

```bash
git add public/css/programa-general-actualizar.css
git commit -m "fix(actualizar-cronograma): el chip «Auto-Guardado» se oculta, y no era lo que decia la ficha

El chip anunciaba un guardado que no habia ocurrido desde que existe la
pantalla. La ficha lo atribuia a que la pagina no esta bajo el ambito del
selector, y es falso: el body lleva pg-page, el padre es .pg-status-badges
y `el.matches(...)` da true. La causa real es que programa-general.css NO se
carga aqui — llega programa-general-actualizar.css—, asi que la regla existia
en el repo y nunca se encontraba con su elemento."
```

---

### Task 3: La reserva de color asimétrica

**ICE 162.** Dos líneas contiguas: una sobrevive a que el token falte y la de al lado no, sin motivo. Lo destapó el hook de `impeccable` y se aplazó dos veces por ser refactor de cortesía en medio de otra tanda. Aquí es el trabajo.

**Premisa verificada:** cierta, con las líneas corregidas. **No** está en `:1395`/`:1398` como decía la ficha, sino en `public/js/modules/programa_actualizar/hot_actualizar.js:1381` (con reserva `var(--aia-text-muted, #6c757d)`) y `:1384` (sin reserva, `var(--aia-green-primary)`). El archivo lo movió la tanda 1B, así que **vuelve a localizarlas**.

**La política dominante del repo está medida, y decide el arreglo:** en `public/js/` hay **12** ocurrencias de `var(--token)` sin reserva frente a **5** con ella. Las cinco con reserva son `programacion_intermedia/hot.js:2084` y `:2824` (dos en la misma línea), y `hot_actualizar.js:1273` y `:1381`. **Gana quitar la reserva**, que es lo que hace el resto del repo — no añadirla a la vecina.

**Files:**
- Modify: `public/js/modules/programa_actualizar/hot_actualizar.js`, y las otras cuatro ocurrencias con reserva

**Interfaces:** Consumes: nada. Produces: nada.

- [ ] **Step 1: Recontar antes de tocar**

```bash
grep -rn "var(--[a-z-]*, *#" public/js/ | grep -v node_modules
grep -rcn "var(--" public/js/modules/*/hot*.js | head
```

Anota las cifras reales de hoy. **Si la mayoría hubiera cambiado de bando**, el arreglo se invierte: dilo y cambia de dirección.

- [ ] **Step 2: Quitar la reserva en las cinco ocurrencias**

Deja el consumo del token limpio, como el resto del repo:

```javascript
label = '<i class="fas fa-history" style="color: var(--aia-text-muted);"></i> ' +
```

- [ ] **Step 3: Comprobar que los tokens existen de verdad**

Quitar la reserva solo es seguro si el token está definido. Si alguno **no** existe, la reserva era lo único que pintaba algo y quitarla dejaría el color por defecto:

```bash
for t in aia-text-muted aia-green-primary; do echo -n "$t: "; grep -rc -- "--$t:" public/css/ | grep -v ':0' | head -1 || echo "NO DEFINIDO"; done
```

**Si alguno no está definido, para y anótalo como decisión** (`D-F1-<n>`): significa que ese consumo lleva tiempo cayendo en la reserva, y eso es un hallazgo, no un formateo.

- [ ] **Step 4: Verificar en navegador que los colores no cambiaron**

En `/programa-general-actualizar`, con la lista de asociaciones visible, comprueba que los iconos de historial y de aceptado **conservan su color**. El cambio debe ser invisible: si algo cambia de color, el token no estaba definido y la Step 3 se te escapó.

- [ ] **Step 5: Commit**

```bash
git add public/js/modules/programa_actualizar/hot_actualizar.js public/js/modules/programacion_intermedia/hot.js
git commit -m "refactor(tokens): el consumo de tokens en JS deja de mezclar dos politicas

Dos lineas contiguas de hot_actualizar.js consumian el mismo tipo de token,
una con reserva en hex y la vecina sin ella, sin motivo. Medido: en public/js
hay 12 consumos sin reserva frente a 5 con ella, asi que gana la mayoria.
Verificado que los cinco tokens afectados estan definidos, para que quitar la
reserva no cambie ningun color."
```

---

### Task 4: Programación Intermedia gana su acción primaria

**ICE 180.** La ficha está aprobada con la respuesta ya dada por el usuario: **la acción primaria es «Restricción Compartida»**.

**Premisa verificada el 2026-08-11, con un matiz que cambia el alcance:** los 8 botones de la toolbar de PI son `aia-btn--secondary`, incluido `#btn-shared-constraint` (`programacion_intermedia.view.php:50`). Pero **PI no es la excepción**: Programa General (`:63-68`) y Programación Semanal (`:70-110`) tampoco marcan primaria — sus toolbars son enteras `secondary`. **La única que jerarquiza es Actualizar Cronograma**, y lo hace con `aia-btn-primary` (guion simple), distinto de la variante BEM `aia-btn--primary` que usan los modales.

**Qué significa eso para esta tarea:** la ficha dice «en las otras tres se pudo señalar cuál es la principal», y **eso es falso hoy**. Aun así, el usuario aprobó cuál debe destacar en PI, así que **se aplica solo a PI** y no se extiende a las otras dos por iniciativa propia: eso sería decidir su acción primaria sin preguntarle. La discrepancia se escribe en la ficha, y **la pregunta de si PG y PS también deberían tener primaria se encola** como decisión.

- [ ] **Step 1: Comprobar la premisa y cuál clase usar**

```bash
grep -n "aia-btn--primary\|aia-btn-primary" views/programacion-intermedia/programacion_intermedia.view.php views/programa-general-actualizar/programaGeneralActualizar.view.php public/css/design-system/components/buttons.css | head -20
```

**Decide con el resultado, no de antemano:** usa la variante que el design system declare canónica en `buttons.css`. Si conviven las dos (`aia-btn-primary` y `aia-btn--primary`), usa la BEM —es la del design system— y **anota la convivencia como hallazgo** en la Task 10; no unifiques la otra aquí.

- [ ] **Step 2: Marcar «Restricción Compartida» como primaria**

```php
<button id="btn-shared-constraint" class="aia-btn aia-btn--primary">Restricción Compartida</button>
```

- [ ] **Step 3: Encolar la pregunta que esto destapa**

En `docs/decisiones-pendientes.md`, id `D-F1-<n>`: Programa General y Programación Semanal tampoco tienen acción primaria, y la ficha del ICE 180 daba por hecho que sí. **Mide antes de escribirla**: cuántos botones tiene cada toolbar y cuáles son candidatos. No decidas cuál debería ser.

- [ ] **Step 4: Verificar en navegador**

`/programacion-intermedia` a 1180×820 en dark: «Restricción Compartida» destaca sobre los otros siete, y el contraste del botón primario sobre el fondo cumple el piso del design system. Comprueba también que **sigue funcionando** (abre su modal).

```bash
export COMPOSE_FILE=docker-compose.wt.yml
npm run test:design-system:static
```

Esperado 8/8. Recaptura de golden → **no la hagas**, anótala.

- [ ] **Step 5: Commit**

```bash
git add views/programacion-intermedia/programacion_intermedia.view.php
git commit -m "feat(programacion-intermedia): la toolbar declara su accion primaria

«Restriccion Compartida», por decision del usuario. Medido al aplicarlo: la
ficha decia que las otras tres toolbars si senalaban su principal, y es falso
—PG y PS son enteras secondary; solo Actualizar jerarquiza—. No se extiende a
las otras dos por iniciativa propia: eso es decidir por el usuario. Encolado."
```

---

### Task 5: El botón flotante deja de tapar la última fila

**ICE 216.** Medido y confirmado en la ficha: en `/programa-general` con 29 filas, el círculo de 50 px se posa sobre la última y oculta parcialmente el valor de «Lib. Restr.». **No se arregla desplazándolo**: la tabla virtualiza y siempre hay una fila al fondo, así que el tapado es permanente y solo cambia a qué fila afecta.

**Premisa verificada el 2026-08-11:** cierta. El botón es `.lps-sidebar-trigger` (`public/css/handsontable-module.css:684-710`): `position: fixed; bottom: 20px; right: 20px; 50×50; border-radius: 50%`, marcado en `views/partials/drawer_unificado.php:2` (partial **global**, no solo de programa-general). El único `padding-bottom` compensatorio que existe es para la vista de tarjetas **móvil** (`handsontable-module.css:317`, `programacion-intermedia.css:1075`): **`#hot-container` y `.wtHolder` no tienen ninguno**.

**La salida que la ficha recomienda:** reservar hueco al final del área de scroll, **con verificación propia de que la tabla no pierde alto ni filas**. Esa verificación es la mitad del trabajo, no un extra.

- [ ] **Step 1: Medir el estado de partida, para poder comparar**

En `/programa-general` a 1180×820 en dark, con datos:

```js
const holder = document.querySelector('#hot-container .wtHolder');
const hot = window.hot || null;
JSON.stringify({
  alturaHolder: holder.clientHeight,
  scrollHeight: holder.scrollHeight,
  filasRenderizadas: document.querySelectorAll('#hot-container .ht_master tbody tr').length,
  filasTotales: hot && hot.countRows(),
})
```

**Anota los cuatro números.** Son la línea base contra la que se comprueba que no se perdió alto ni filas.

- [ ] **Step 2: Reservar el hueco**

El botón mide 50 px y se posa a 20 px del borde: el hueco tiene que cubrir los dos. Consume token de espacio, no un número suelto:

```css
/* El boton flotante (.lps-sidebar-trigger, 50px a 20px del borde) se posa sobre
   la ultima fila y tapa parte de su valor. Desplazarlo no sirve: la tabla
   virtualiza y siempre hay una fila al fondo, asi que el tapado es permanente
   y solo cambia a que fila afecta. Se reserva hueco al final del area de
   scroll para que ninguna fila quede debajo. */
#hot-container .wtHolder::after {
    content: '';
    display: block;
    block-size: var(--ds-space-16);
}
```

> **Comprueba el token antes de escribirlo** (`grep -n "ds-space-16" public/css/tokens.css`) y usa uno que exista y que sume al menos 70 px. Si ninguno llega, **elige el mayor que exista y dilo**, o anótalo como decisión — no inventes un valor en píxeles.
>
> **Y comprueba que el `::after` funciona con Handsontable.** `.wtHolder` es un contenedor de scroll virtualizado: si el pseudo-elemento no aumenta `scrollHeight`, prueba con `padding-block-end` sobre el elemento correcto. **Lo que decide es la medición de la Step 3, no el gusto.**

- [ ] **Step 3: Verificar que se ganó el hueco sin perder tabla — es el punto de la tarea**

Repite la medición de la Step 1 y compara:

- `scrollHeight` debe **aumentar** en el tamaño del hueco.
- `alturaHolder` **no** debe disminuir.
- `filasTotales` debe ser **idéntico**.
- Con la tabla desplazada hasta abajo del todo, la **última fila debe quedar completamente por encima** del botón. Compruébalo con `getBoundingClientRect()` de la última fila y del botón: no deben solaparse.

- [ ] **Step 4: Comprobar la otra pantalla que la ficha no pudo medir**

La ficha dice que en Programación Semanal la columna «Acciones» (copiar, eliminar) está en ese mismo extremo y **no se pudo medir por falta de filas**. Como el partial es global, el arreglo llega también ahí: verifica `/programacion-semanal` con filas suficientes y **di si el solape existía**, cerrando el hueco que la ficha dejó abierto.

- [ ] **Step 5: Suite y commit**

```bash
export COMPOSE_FILE=docker-compose.wt.yml
npm run test:design-system:static   # 8/8; recaptura de golden -> anotar, no regenerar
git add public/css/handsontable-module.css
git commit -m "fix(tablas): el boton flotante deja de taparse con la ultima fila

Medido: el circulo de 50px se posa sobre la ultima fila y oculta parte de su
valor, y como la tabla virtualiza el tapado es permanente. Se reserva hueco al
final del area de scroll. Verificado que scrollHeight crece, que el alto del
contenedor y el numero de filas no cambian, y que la ultima fila ya no solapa
con el boton."
```

---

### Task 6: La columna «Actividad» deja de gobernar el alto de fila

**ICE 252**, y su disposición aprobada es concreta: **dos líneas máximo en «Actividad», con el detalle al pasar el cursor.**

**Premisa verificada el 2026-08-11, y es más aguda que la registrada.** La ficha dice que el token de alto de fila vale 24 px y la fila mediana mide 111. Medido: los tokens `--ds-density-compact-*` existen (`public/css/tokens.css:517-530`) pero **no se aplican a Handsontable en absoluto** — su única referencia fuera de `tokens.css` es `public/css/design-system/lab.css:1806`, y `handsontable-module.css` no consume ningún token de densidad ni fija alto de fila. Y no hay truncado: hay **lo contrario**, `.force-wrap` (`handsontable-module.css:198-204`) con `white-space: pre-wrap !important; word-wrap: normal !important; word-break: normal !important`, aplicado a la columna «Actividad» de PI (`programacion_intermedia/hot.js:325`).

O sea: la densidad no gana filas **porque nada la aplica**, y el envoltorio es deliberado — su comentario documenta el caso «HOMECENTE R CALI», que es un nombre que se partía mal.

- [ ] **Step 1: Medir el alto real antes de tocar**

En `/programacion-intermedia` a 1180×820 en dark, con datos:

```js
const filas = [...document.querySelectorAll('#hot-container .ht_master tbody tr')];
const altos = filas.map(f => Math.round(f.getBoundingClientRect().height)).sort((a,b)=>a-b);
JSON.stringify({n: altos.length, mediana: altos[Math.floor(altos.length/2)], max: altos[altos.length-1], min: altos[0]});
```

**Anota mediana y máximo.** La ficha dice 111 y 216 sobre 26 filas. Si tus números difieren mucho, dilo: la ficha se midió hace días.

- [ ] **Step 2: Limitar a dos líneas conservando el detalle**

`.force-wrap` existe por un motivo real (no partir palabras largas), así que **no lo quites**: acótalo a dos líneas.

```css
/* Aprobado por el usuario: dos lineas maximo en «Actividad», con el detalle al
   pasar el cursor. `.force-wrap` se conserva —existe para no partir nombres
   largos como «HOMECENTER CALI»— y solo se acota su alto. Medido antes: la fila
   mediana media 111px sobre un token de densidad de 24px, y lo que gobernaba el
   alto era este texto envuelto, no la densidad. */
#hot-container td.force-wrap {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
```

- [ ] **Step 3: Dar el detalle al pasar el cursor, y que no dependa solo del ratón**

El texto completo tiene que seguir siendo alcanzable. En el renderer de la columna «Actividad» (`programacion_intermedia/hot.js`, `piActividadRenderer`), pon el texto completo en `td.title`:

```javascript
td.title = valorCompleto;
```

> **Y aquí un límite que no debes cruzar:** `title` **solo llega con el ratón**, que es exactamente el defecto del ICE 192 (Task 7). Ponerlo es mejor que nada y es lo aprobado, pero **no lo declares resuelto para teclado**. Si al medirlo ves que la celda no es alcanzable por teclado con su texto completo, **anótalo como hallazgo nuevo** en la Task 10 en vez de inventar un widget.

- [ ] **Step 4: Medir la ganancia, que es la razón de la tarea**

Repite la medición de la Step 1 y **da las dos cifras, antes y después**. Cuenta además cuántas filas caben en el viewport antes y después:

```js
document.querySelectorAll('#hot-container .ht_master tbody tr').length
```

Si el alto mediano **no baja**, el arreglo no sirvió: dilo en vez de darlo por bueno.

- [ ] **Step 5: Comprobar que no se rompió lo que `.force-wrap` protegía**

Busca una fila con un nombre largo tipo «HOMECENTER CALI» y comprueba que **sigue sin partirse a mitad de palabra**, solo recortado a dos líneas con puntos suspensivos.

- [ ] **Step 6: Suite y commit**

```bash
export COMPOSE_FILE=docker-compose.wt.yml
npm run test:design-system:static   # 8/8; recaptura -> anotar, no regenerar
git add public/css/handsontable-module.css public/js/modules/programacion_intermedia/hot.js
git commit -m "feat(tablas): la columna «Actividad» se acota a dos lineas y deja de gobernar el alto"
```

---

### Task 7: El motivo del botón bloqueado llega también con teclado

**ICE 192. Cambia comportamiento**, y la ficha lo dice en mayúsculas. Está aprobada con alcance acotado: **empezar por los medidos en `/profesionales`**.

**Premisa verificada el 2026-08-11, con una corrección de conteo:** el mecanismo es cierto, pero **los «13 botones» no están en la vista**: se generan desde JS, en el `cells()` de Handsontable (`views/profesionales/profesionales.view.php:313`), y **cuántos hay depende de los datos** (`!rowData.can_delete`), no de un número codificado.

```js
td.innerHTML = `<button class="aia-btn aia-btn--secondary aia-btn--sm" disabled title="${reason}"><i class="fas fa-lock"></i></button>`;
```

Con `disabled` nativo el botón **no es focalizable**, así que con teclado o lector de pantalla no existe y su motivo (`reason`) es inalcanzable.

**El remedio estándar exige las dos mitades, y la segunda es la que hace que no sea un fallo:** `aria-disabled` devuelve el botón a pulsable, así que **el JS tiene que bloquear la acción**. Un `aria-disabled` sin ese bloqueo convierte un botón inerte en un botón que borra.

- [ ] **Step 1: Censar cuántos son y con qué motivos, hoy**

En `/profesionales` a 1180×820 en dark como `test.A`:

```js
const bs = [...document.querySelectorAll('#hot-container button[disabled]')];
JSON.stringify({n: bs.length, motivos: [...new Set(bs.map(b => b.title))]});
```

**Anota el número real y los motivos distintos.** Si es 0, no hay nada que arreglar en este proyecto: prueba con otro o dilo.

- [ ] **Step 2: Escribir la prueba del bloqueo antes del cambio**

Lo que puede salir mal es que el botón pase a pulsable y **borre**. Añade a `tests/design-system/cascada-lps-a11y.test.mjs` un candado estático que exija que, donde haya `aria-disabled`, exista el guard en el manejador:

```javascript
test('el boton bloqueado de /profesionales es alcanzable pero no actua', async () => {
  const vista = await read('views/profesionales/profesionales.view.php');
  // aria-disabled devuelve el boton a pulsable: sin guard en el manejador, un
  // boton inerte se convierte en un boton que borra.
  if (!/aria-disabled/.test(vista)) return; // aun no migrado
  assert.match(vista, /aria-disabled=["']true["']/);
  assert.match(vista, /getAttribute\(['"]aria-disabled['"]\)|\.ariaDisabled/,
    'hay aria-disabled pero ningun guard que lo compruebe en el manejador');
});
```

Ejecútalo y **pega la salida**. Con el código de hoy pasa (por el `return` temprano); tras la Step 3 debe seguir pasando **por la rama real**, no por el atajo. Comprueba esa diferencia.

- [ ] **Step 3: Hacer el botón alcanzable y bloquear la acción**

```javascript
td.innerHTML = `<button class="aia-btn aia-btn--secondary aia-btn--sm" aria-disabled="true" title="${reason}" aria-label="Eliminar. Bloqueado: ${reason}"><i class="fas fa-lock" aria-hidden="true"></i></button>`;
```

Y en el manejador del clic de eliminar, **antes de cualquier otra cosa**:

```javascript
if (boton.getAttribute('aria-disabled') === 'true') {
  // El boton es focalizable a proposito para que su motivo llegue con teclado y
  // lector de pantalla; el bloqueo real vive aqui, no en el atributo `disabled`.
  showFeedback('error', boton.title);
  return;
}
```

Además, el CSS tiene que seguir distinguiéndolo del activo. La ficha midió que hoy se distingue por **cuatro canales independientes** y con `disabled` nativo: al pasar a `aria-disabled` esos estilos dejan de aplicar solos. Añade la regla equivalente consumiendo los mismos tokens:

```css
.aia-btn[aria-disabled="true"] {
    opacity: var(--ds-opacity-disabled);
    cursor: not-allowed;
}
```

> Comprueba el nombre real del token (`grep -n "opacity-disabled\|disabled" public/css/tokens.css`) y **copia los cuatro canales que hoy usa `:disabled`** en `buttons.css`, no solo la opacidad. Si el token no existe, anótalo como decisión.

- [ ] **Step 4: Verificar las tres cosas que importan, y la tercera es la crítica**

1. **Teclado:** con `Tab` se llega al botón, y el lector anuncia «Eliminar. Bloqueado: …». Compruébalo en el árbol de accesibilidad.
2. **Visual:** sigue distinguiéndose del botón activo por los mismos canales que antes.
3. **No borra.** Púlsalo con ratón y con `Enter`. **Comprueba en la pestaña de red que NO sale ninguna petición de borrado**, y con una consulta que la fila **sigue existiendo**. Si borra, es una regresión destructiva: revierte y dilo.

Y el rol denegado: entra como `test.V` y comprueba que el servidor sigue respondiendo **403** al borrado. Si responde 200, **avisa de inmediato**, no lo encoles.

- [ ] **Step 5: Commit**

```bash
git add views/profesionales/profesionales.view.php public/css/design-system/components/buttons.css tests/design-system/cascada-lps-a11y.test.mjs
git commit -m "fix(a11y): el motivo del boton bloqueado llega tambien con teclado

Con `disabled` nativo el boton no era focalizable, asi que con teclado o
lector de pantalla no existia y su motivo vivia solo en el `title`. Pasa a
aria-disabled, y el bloqueo real se mueve al manejador: sin ese guard, un
boton inerte se habria convertido en un boton que borra. Verificado que no
sale ninguna peticion de borrado al pulsarlo con raton y con Enter."
```

---

### Task 8: `Esc` cierra los modales, en todas las pantallas

**ICE 180. Cambia comportamiento**, y su ficha exige algo antes de arreglar: **medir los 12 modales, porque solo se probó 1.**

**Premisa verificada el 2026-08-11, y ha cambiado desde que se escribió la ficha:** hay **11** modales con `data-backdrop="static"` (ninguno con `data-bs-backdrop`), y **ya existe un handler**, pero solo para dos de ellos: `programacion_intermedia/hot.js:4666-4677` (`keydown.piModalEscape`), acotado a `#modal_shared_constraint.show, #modal_leyenda_colores.show`, con el comentario «Escape cierra los modales PI: el backdrop estático los dejaba sin salida de teclado». **Quedan 9 sin salida por teclado.** No existe ningún `keydown`/`keyup` global que haga `preventDefault` sobre Escape en `public/js/`.

Los 11: `programacion_intermedia.view.php:87,99` · `programa_general.view.php:104` · `programacion_semanal.view.php:135,147,163,301,373` · `partials/_changeMonitorModal.php:2` · `programaGeneralActualizar.view.php:226,321` · generado en JS: `programa_general/hot.js:1598`.

**El arreglo no es copiar el handler de PI nueve veces** —eso es el defecto que este frente arranca—: se extrae a una pieza compartida y **PI pasa a consumirla**, igual que se hizo con el chip de guardado en la tanda 1B.

- [ ] **Step 1: Medir los 11, uno a uno, antes de tocar nada**

Esto es lo que la ficha exige y lo que no se hizo en su día. Para cada modal, ábrelo en el navegador y comprueba **si `Esc` lo cierra hoy**:

```js
const m = document.querySelector('#<id_del_modal>');
m.dispatchEvent(new KeyboardEvent('keydown', {key:'Escape', bubbles:true}));
setTimeout(() => console.log('sigue abierto:', m.classList.contains('show')), 400);
```

**Escribe en el informe una tabla de 11 filas** con el id, la pantalla y si cierra o no. Sin esa tabla la ficha sigue sin cumplirse, aunque el código funcione: su defecto registrado era precisamente «alcance sin medir».

- [ ] **Step 2: Extraer la pieza compartida**

`public/js/design-system/modal-escape.js`:

```javascript
/**
 * `Esc` cierra los modales de backdrop estatico.
 *
 * Con `data-backdrop="static"` Bootstrap no cierra al pulsar fuera, y aunque la
 * instancia reporta `keyboard: true`, la tecla no cierra: la unica salida es la
 * «×». No es trampa de teclado (WCAG 2.1.2) porque la «×» es alcanzable con Tab,
 * pero rompe la convencion que todo usuario intenta primero.
 *
 * Vivia solo en `programacion_intermedia/hot.js` y cubria 2 de los 11 modales
 * del repo. Se extrae en vez de copiarse nueve veces.
 */
export function activarEscapeEnModales() {
  document.addEventListener('keydown', (ev) => {
    if (ev.key !== 'Escape') return;
    const abierto = document.querySelector('.modal.show');
    if (!abierto) return;
    // `data-aia-escape="off"` deja la puerta abierta a un modal que de verdad
    // no deba cerrarse asi (una confirmacion destructiva a medias, por ejemplo).
    if (abierto.dataset.aiaEscape === 'off') return;
    window.jQuery(abierto).modal('hide');
  });
}
```

- [ ] **Step 3: Cablearla una vez, y retirar la copia de PI**

Cárgala desde el punto común que ya comparten las vistas —**localízalo**: `views/partials/` o el cargador común de JS— y **borra el `keydown.piModalEscape` de `programacion_intermedia/hot.js:4666-4677`**, para que no queden dos handlers compitiendo. Si al quitarlo PI deja de cerrar, la pieza compartida no está llegando a esa vista: arréglalo ahí, no devolviendo la copia.

- [ ] **Step 4: Repetir la medición de la Step 1 en los 11**

Misma tabla, columna «después». Esperado: los 11 cierran. **Si alguno no debe cerrarse con `Esc`** —porque está a mitad de una operación destructiva—, no lo fuerces: márcalo con `data-aia-escape="off"`, **explica por qué en el informe**, y anótalo.

- [ ] **Step 5: Comprobar que `Esc` no rompe otra cosa**

Con un modal abierto **sobre** una celda en edición de Handsontable, `Esc` podría cancelar la edición además de cerrar el modal. Pruébalo y dilo. Y comprueba que `Esc` **sin** ningún modal abierto sigue haciendo lo de siempre en las rejillas (cancelar la edición de celda).

- [ ] **Step 6: Commit**

```bash
git add public/js/design-system/modal-escape.js public/js/modules/programacion_intermedia/hot.js views/
git commit -m "fix(a11y): Esc cierra los 11 modales de backdrop estatico, no 2

El handler existia solo en Programacion Intermedia y cubria 2 de los 11
modales del repo; los otros 9 tenian como unica salida la «×». Se extrae a una
pieza compartida y PI pasa a consumirla, en vez de copiarlo nueve veces.
Medidos los 11 antes y despues, uno a uno — la ficha registraba «alcance sin
medir: probado 1 de 12» y eso era la mitad del hallazgo."
```

---

### Task 11: Aplicar las cinco decisiones que el usuario resolvió

**Añadida el 2026-08-11**, después de escribirse el plan. Las cinco entradas de la cola volvieron resueltas por el usuario a través de la sesión coordinadora, así que dejan de ser «anotadas y saltadas» y pasan a ser trabajo. **Va antes de la Task 9**, para que la verificación de conjunto mida el estado final.

**Files:**
- Modify: `public/css/tokens.css` (familia nueva de estado de celda, y el token de espacio)
- Modify: `public/css/programacion-semanal.css`, `public/css/handsontable-module.css`
- Modify: `public/js/modules/programa_actualizar/hot_actualizar.js`, `public/js/modules/programacion_intermedia/hot.js`
- Modify: `views/programacion-semanal/programacion_semanal.view.php`, `views/programa-general/programa_general.view.php`
- Modify: `docs/decisiones-pendientes.md` (las cinco a `resuelta`)

- [ ] **Step 1: `D-F1-1` — escribir por qué las dos severidades son distintas**

Es la única de las cinco que **no toca CSS**. Deja constancia en `programacion-semanal.css` y en `programacion-intermedia.css`, junto a cada regla, de que la diferencia es deliberada:

```css
/* Deliberado, decidido el 2026-08-11 (D-F1-1): esta marca es `critical` y la
   hermana de Programacion Intermedia es `warning`, para la MISMA falta. No es
   una inconsistencia: aqui la falta frena el cierre de la semana entera, alli
   solo bloquea unas celdas. No unificar sin volver a preguntar. */
```

- [ ] **Step 2: `D-F1-2` — familia nueva de tokens de estado para celdas**

Los cuatro tokens de fondo de estado están calibrados para teñir superficies grandes y **ninguno llega a 3:1** contra el fondo de tabla (medido: 1,02 · 1,36 · 1,31 · 1,54). Se crea una familia **nueva** con ese trabajo declarado; los actuales **no se tocan**.

En `public/css/tokens.css`, junto a los de estado:

```css
/* Familia de destaque de CELDA, decidida el 2026-08-11 (D-F1-2). Distinta de
   `--ds-color-state-*-bg`, que sigue existiendo para tenir superficies grandes:
   aquellos estan calibrados para eso y ninguno llega al 3:1 que WCAG 1.4.11
   exige a un componente pequeno contra lo que lo rodea (medido 1,02 el peor).
   Estos se calibran para ESE trabajo: 3:1 contra `--ds-active-surface`. */
--ds-color-cell-critical-bg: <valor>;
--ds-color-cell-warning-bg: <valor>;
--ds-color-cell-success-bg: <valor>;
--ds-color-cell-info-bg: <valor>;
```

**Los valores no están en este plan a propósito: se calculan.** Para cada uno, parte del tono del token de estado correspondiente y sube su luminancia hasta que el contraste contra `rgb(28,36,31)` (el fondo de tabla, ya medido) **alcance o supere 3:1**. Usa la fórmula de WCAG y **da las cuatro cifras finales en el informe**. Si algún tono no puede llegar a 3:1 sin dejar de parecerse a su estado, **dilo con su número** y usa el que más se acerque conservando la identidad del color.

Después, apunta `.ps-cell-empty-alert` al nuevo `--ds-color-cell-critical-bg` (hoy usa `--ds-color-state-critical-bg`) y **vuelve a medir fondo-alerta contra fondo-vecino**: esa cifra es la que cierra del todo el ICE 448.

- [ ] **Step 3: `D-F1-3` — los cuatro `--aia-*` apuntan a un token real**

Los cuatro no existen: `--aia-text-muted`, `--aia-warning-soft-bg`, `--aia-warning-border`, `--aia-red-primary`. Sustitúyelos por el token del design system que corresponda y **retira la reserva en hexadecimal**.

**Condición explícita de la coordinadora, y es bloqueante por línea:** antes de dar por buena cada sustitución, **comprueba en el navegador que el color computado no cambia**. Da el `getComputedStyle(...).color` antes y después de cada una. **Si alguno no tiene equivalente claro en el sistema, no lo fuerces**: anótalo, salta esa línea, y sigue con las demás.

Las cinco ocurrencias están en `hot_actualizar.js` y `programacion_intermedia/hot.js`; **localízalas por contenido**, los números de línea se han movido tres veces.

- [ ] **Step 4: `D-F1-4` — dos acciones primarias más**

Aplícalas sin darle vueltas; el usuario dijo que las corregirá al verlas en pantalla:

- Programación Semanal → **«Confirmar Compromisos»**. Es el momento firma, que él mismo eligió.
- Programa General → **«Actualizar Ejecución»**. Es la única de las diecisiete que escribe.

Usa la misma variante BEM que la Task 4 (`aia-btn--primary`), quitando `aia-btn--secondary`. **Comprueba que las dos siguen funcionando** tras el cambio, y mide su contraste igual que en la Task 4: texto sobre botón (**1.4.3**, piso 4.5) y botón contra fondo (**1.4.11**, piso 3). Da las cuatro cifras.

- [ ] **Step 5: `D-F1-5` — el token de espacio que faltaba, y el hueco cierra**

Lee la escala actual (`grep -n "ds-space-" public/css/tokens.css`) y **respeta su progresión**. El objetivo es ≥70 px: 72 px si encaja, y si rompe la escala, **el siguiente que la respete y llegue a 70**. Di cuál elegiste y por qué encaja.

Luego cambia el `padding-block-end` de `#hot-container .wtHolder` a ese token y **vuelve a medir el solape** de la Task 5: última fila contra `.lps-sidebar-trigger`, con `getBoundingClientRect()` de las dos, en `/programa-general` **y** en `/programacion-semanal`. Esperado: **cero solape**. Esa cifra es la que cierra del todo el ICE 216.

- [ ] **Step 6: Marcar las cinco como resueltas**

En `docs/decisiones-pendientes.md`, mueve las cinco a la sección «Resueltas» con el formato del propio archivo: `resuelta 2026-08-11: <decisión>`. **No borres su medición**: es lo que hace auditable la decisión.

- [ ] **Step 7: Gates y commit**

```bash
export COMPOSE_FILE=docker-compose.wt.yml
npm run test:design-system:static
```

Esperado 8/8. **Este paso toca tokens del design system, así que es el más probable de toda la tanda para pedir recaptura de golden.** Si la pide, **no la regeneres**: anótalo, deja el cambio, y dilo — la aprobación de un cambio visual no es la aprobación de recapturar su evidencia.

---

### Task 9: Verificación de conjunto del Frente 1

No añade código: comprueba que las dos tandas juntas no se pisaron.

- [ ] **Step 1: Los cuatro gates, con su salida real**

```bash
export COMPOSE_FILE=docker-compose.wt.yml
npm run test:rbac-parity
npm run test:design-system:static
docker compose exec app vendor/bin/phpstan analyse src admin/src --memory-limit=1G
npm run test:wiki
```

**Pega la salida y el sha sobre el que se midió.** Si `test:wiki` sale rojo **solo** por la alarma de veracidad, dilo: no es un fallo de forma y **no se arregla escribiendo en `memoria/` para bajar el contador**.

- [ ] **Step 2: El recorrido completo en navegador**

Como `test.A` y luego como `test.V`, a 1180×820 en dark, recorre `/programa-general` → `/programa-general-actualizar` → `/programacion-intermedia` → `/programacion-semanal` → `/indicadores` → `/plan-compras` → `/control-cambios` → `/profesionales`. En cada una: **consola sin errores** y la pantalla renderiza.

Es la comprobación que ninguna tarea individual hace y que caza lo que se rompe entre dos tareas.

- [ ] **Step 3: Las pruebas PHP que tocó el frente**

```bash
docker compose exec app php tests/test_global_table_safety.php
docker compose exec app php tests/test_dev_door_guard.php
```

---

### Task 10: Los 16 quedan con disposición escrita, y el Frente 1 cierra

**Files:** Modify: `docs/EXPERIMENTS.md`, `docs/decisiones-pendientes.md`, `docs/IMPROVE-APP-PLAN.md`

- [ ] **Step 1: Escribir los siete cierres sin código**

Con la misma exigencia que pidió la coordinadora para la tanda 1B: **cada ficha dice qué se midió y contra qué commit**, no solo que ya no aplica. «Ya está arreglado» sin evidencia se convierte en la próxima ficha que nadie se cree.

Los siete, con lo medido en la tabla de arriba: ICE 300, 280, 252 (ids), 160, 140, 125 y 98.

- [ ] **Step 2: Escribir los nueve cierres con código**, con sus hashes reales (`git log --oneline`), diciendo dónde la medición contradijo la ficha. Al menos tres lo hacen: el ICE 320 (la causa era otra), el 180 de PI (las otras toolbars tampoco tenían primaria) y el 192 (los «13 botones» no están en la vista).

- [ ] **Step 3: Comprobar que no queda ninguno mudo**

```bash
grep -c "| abierto" docs/EXPERIMENTS.md
```

Esperado: solo los hallazgos **nuevos** que abrieron las tandas 1B y 1C. Los 28 de partida, cerrados.

- [ ] **Step 4: La fase 9 de `improve-app`**

Se ejecuta **al cerrar el frente, en frío**, sobre la cascada PG → PI → PS **ya arreglada** — ese es su momento natural, y revisarla antes habría sido revisar defectos ya censados. Usa `steve-jobs-design-review`. `docs/PRODUCT.md` **ya existe**: se **refresca**, no se crea. Marca la fase 9 como `done` en `docs/IMPROVE-APP-PLAN.md` solo cuando la revisión esté hecha.

- [ ] **Step 5: Commit y gate de cierre de frente**

El gate completo de `AGENTS.md` §Publicación, en orden y sin saltarse el quinto paso: verificar, commitear, `git fetch`, integrar, **re-verificar después de integrar**, publicar, confirmar sin divergencia, anotar. Y avisar a la coordinadora, que abre el frente siguiente.

---

## Self-Review

**Cobertura de los 16:** 324→T1, 320→T2, 300→T10, 280→T10, 252(ids)→T10, 252(densidad)→T6, 216(FAB)→T5, 216(PDC)→T1, 192→T7, 180(Esc)→T8, 180(PI)→T4, 162→T3, 160→T10, 140→T10, 125→T10, 98→T10+cola. Los dieciséis tienen destino, y los dieciséis reciben disposición escrita en la T10.

**Los dos cambios de comportamiento van con red:** el T7 lleva prueba de que el botón alcanzable **no borra** (la regresión destructiva posible), y el T8 mide los 11 modales antes y después, que es lo que su ficha exigía y nunca se hizo.

**Puntos que se encolan y se saltan:** cualquier recaptura de golden (T2, T4, T5, T6), un token de espacio o de opacidad que no exista (T5, T7), un token de color no definido al quitar su reserva (T3), y la acción primaria de PG y PS (T4). **Excepción que no se encola:** un `200` donde se espera `403` (T7).

**Lo que este plan no cubre:** la campaña de reducción de la fase 6 del design system, declarada fuera del programa por el propio spec; y los hallazgos nuevos que abrieron las tandas 1B y 1C, que quedan abiertos con su medición para un frente posterior.
