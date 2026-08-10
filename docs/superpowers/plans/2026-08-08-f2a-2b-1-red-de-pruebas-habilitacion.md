# F2a-2b-1 — Red de pruebas sobre las reglas de habilitación: plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fijar por escrito, con pruebas que fallen si algo cambia, el comportamiento actual de las 22 reglas que deciden si una celda es editable en Programación Semanal e Intermedia — **antes** de extraerlas.

**Architecture:** No se cambia ni una regla. Se caracteriza lo que hoy hacen, leyendo la decisión donde el propio código ya la deja visible: las clases CSS que `cells()` pone en cada celda (`ps-cell-readonly`, `pi-cell-readonly`, `pi-cell-locked-resp`, `pi-cell-editable`, `pi-cell-dropdown`). El rol, la fase y la semana se inyectan en los inputs ocultos que las reglas ya leen, así que la matriz se recorre sin sembrar datos ni cuentas nuevas.

**Tech Stack:** Playwright (`tests/browser/`), puerta de desarrollo para la sesión, Docker Compose para servir la app.

**Spec:** [`2026-08-07-f2a-piloto-movil-programacion-design.md`](../specs/2026-08-07-f2a-piloto-movil-programacion-design.md), decisiones E7 y E8 y la corrección de premisa del 2026-08-08.

## Global Constraints

- **No se cambia ninguna regla de habilitación.** Si una prueba caracteriza algo que parece un bug, se caracteriza **el comportamiento real** y se anota; corregirlo es otra tarea.
- **Cero cambio visual.** No se toca `public/css`, ni ningún golden, ni los manifiestos salvo lo que la Task 4 declare.
- La sesión se abre **siempre por la puerta de desarrollo** (`/dev/entrar?u=test.R&p=...`), nunca por `/login`, y nunca se teclean credenciales. `AGENTS.md` §Seguridad.
- Cuentas realmente habilitadas: **`test.A`, `test.R`, `test.V`**. `test.C` y `test.D` existen pero no están habilitadas, y **`DCV` no tiene cuenta**. Por eso la matriz de roles se recorre inyectando `#permiso_canonico`, no abriendo seis sesiones.
- **Lo que esta red cubre y lo que no, dicho sin adornos:** cubre la **decisión del cliente** sobre si una celda es editable. **No** cubre la autorización del servidor, que ya tienen `tests/test_semanal_rbac_solo_lectura.php` y `tests/test_weekly_governance.php`. Una regla del cliente puede coincidir o no con la del servidor; caracterizar la del cliente no afirma nada sobre la otra.
- **Commits:** autorizados, uno por tarea.

## Inventario a cubrir (medido el 2026-08-08)

**Semanal** (`public/js/modules/programacion_semanal/hot.js`, 4775 líneas):

| # | Regla | Entrada | Dónde |
|---|---|---|---|
| S1 | Whitelist `editableProps` (9 columnas) | constante | `:26-36` |
| S2 | `isUserAllowedToEdit`: semana histórica (`Max_Semana - 2 >= semana`) solo A/D; si no A/D/R/DCV | rol, semana | `:355-365` |
| S3 | `Ejecutado_Real` editable **solo** en fase calificación (`Semanal_Confirmada === 1`) | fase, rol | `:376-378` |
| S4 | `Compromiso`, `Sub_Contratista`, `Responsable_AIA` bloqueados si `Semanal_Confirmada === 1` | fase | `:384-386` |
| S5 | Columna de acciones siempre readOnly | meta | `:2742-2746` |
| S6 | ~15 columnas readOnly fijas | — | `:2596-2623` |
| S11 | El dropdown solo auto-abre si la celda no es readOnly | S1–S4 | `:1251-1276` |
| S12 | `canManageToolbarActions` = S2 más veto al rol `C` | rol, fase | `:367-369` |
| S13 | La card móvil edita solo si `editableProps[prop] && !isPropReadOnly(prop)` | S1–S4 | `:3287-3301` |

**Intermedia** (`public/js/modules/programacion_intermedia/hot.js`, 4806 líneas):

| # | Regla | Entrada | Dónde |
|---|---|---|---|
| I1 | `editableProps` **dinámico**, construido desde `/api/general/restriction-config` | API | `:198-208` |
| I2 | `isUserAllowedToEdit`: `false` si `Semanal_Confirmada === 1`; histórica solo A/D | fase, rol, semana | `:591-606` |
| I3 | Filas cabecera (`state === 'header'`) no editables | máquina de estados | `:837-861`, `:933` |
| I4 | **Candado por Responsable AIA**: restricciones readOnly si la fila no tiene responsable | dato de fila | `:859`, `:929-934` |
| I5 | `__shared_selected` editable en toda fila no cabecera, ignorando rol y fase | estado de fila | `:931-932` |
| I6 | 6 columnas readOnly fijas | — | `:323-346` |
| I7 | La apertura del dropdown re-verifica I1–I3 | varias | `:3842-3855` |

**Fuera de esta red, y por qué:** S7, S8, S9, S10, I8 e I9 no son reglas de habilitación sino **guards de valor** que corren en `beforeChange`/`afterChange` y tienen efectos (revertir, borrar la actividad, encolar un modal CNC). Caracterizarlos exige provocar cambios reales de datos, que es otra clase de prueba y otro riesgo. Se declaran fuera de alcance en la Task 4, no se omiten en silencio.

## File Structure

| Archivo | Responsabilidad |
|---|---|
| `tests/browser/support/enablement-probe.mjs` | **Nuevo.** El arnés: inyecta rol/fase/semana en los inputs ocultos, fuerza la re-evaluación, y lee la decisión de cada celda por sus clases. |
| `tests/browser/programacion-semanal-enablement.mjs` | **Nuevo.** Caracteriza S1–S6, S11–S13. |
| `tests/browser/programacion-intermedia-enablement.mjs` | **Nuevo.** Caracteriza I1–I7. |
| `docs/design-system/manifests/programacion-semanal.json`, `programacion-intermedia.json` | Declaran las dos pruebas nuevas en `tests`. |

---

### Task 1: El arnés, y probarlo contra dos reglas conocidas

Sin arnés no hay red: las reglas leen el estado del DOM en cada llamada y Handsontable cachea `cellMeta`, así que cambiar un input no basta para que la grilla vuelva a decidir.

**Files:**
- Create: `tests/browser/support/enablement-probe.mjs`
- Test: el propio arnés se valida contra dos reglas de comportamiento conocido

**Interfaces:**
- Produces: `setEnablementContext(page, { permiso, semana, maxSemana, semanalConfirmada })` y `readCellDecisions(page, { columns })`, que devuelve por columna `{ readOnly: boolean, classes: string[] }`. Las tareas 2 y 3 las consumen.

- [ ] **Step 1: Abrir sesión y llegar a la grilla**

Usa el patrón que ya siguen las specs existentes de estos módulos (mira `tests/browser/programacion-semanal-roles-phases.mjs` para el arranque de sesión y selección de proyecto). No inventes un arranque nuevo.

- [ ] **Step 2: Escribir el inyector de contexto**

Los cuatro inputs que las reglas leen son `#permiso_canonico`, `#semana` (o `#semana_PHP`), `#Max_Semana` y `#Semanal_Confirmada` (`hot.js:231-260` en Semanal, `:560-589` en Intermedia). El inyector los fija y después **fuerza a la grilla a re-decidir**.

Ese «después» es la parte delicada: `cells()` no se re-evalúa sobre `cellMeta` ya cacheado, y en Intermedia `_canEditGlobal` se fija en `buildRowClassCache` y se lee más tarde. Averigua qué llamada usa la propia app para refrescar tras un cambio de contexto y usa **esa**, no un `location.reload()` que perdería el contexto inyectado. Si no encuentras una y acabas recargando, dilo en el informe: significaría que el inyector tiene que reinyectar tras cada recarga.

- [ ] **Step 3: Escribir el lector de decisiones**

Lee las clases que `cells()` ya pone en cada `<td>`: `ps-cell-readonly` en Semanal; `pi-cell-editable`, `pi-cell-readonly`, `pi-cell-locked-resp`, `pi-cell-dropdown` en Intermedia. Devuelve la decisión por columna para una fila dada.

**Comprueba que el lector no miente:** que una celda que el lector dice editable **acepta** de verdad una edición, y una que dice readOnly la rechaza, al menos en un caso de cada. Un lector que se limite a leer clases sin comprobar esa correspondencia caracterizaría el CSS, no la regla.

- [ ] **Step 4: Validar el arnés contra dos reglas conocidas**

Dos casos de comportamiento no discutible:
1. Una de las columnas readOnly fijas (S6, por ejemplo `Actividad`) debe salir readOnly con **cualquier** rol y fase.
2. `Compromiso` con rol `V` debe salir readOnly, y con rol `A` en semana corriente y fase no confirmada, editable.

Si el arnés no distingue esos dos casos, no sirve para los otros veinte. **Párate y repórtalo antes de escribir más pruebas.**

- [ ] **Step 5: Commit**

```bash
git add tests/browser/support/enablement-probe.mjs
git commit -m "test(programacion): arnes para caracterizar las reglas de habilitacion"
```

---

### Task 2: Caracterizar Semanal (S1–S6, S11–S13)

**Files:**
- Create: `tests/browser/programacion-semanal-enablement.mjs`

**Interfaces:**
- Consumes: `setEnablementContext` y `readCellDecisions` de la Task 1.

- [ ] **Step 1: La matriz**

Recorre los seis roles (`A`, `D`, `R`, `DCV`, `C`, `V`) × dos fases (`Semanal_Confirmada` 0 y 1) × dos posiciones de semana (corriente e histórica, es decir `Max_Semana - 2 >= semana`), y para cada combinación registra la decisión de las nueve columnas de `editableProps` más una readOnly fija de control.

- [ ] **Step 2: Escribir las aserciones como caracterización, no como deseo**

Para cada celda de la matriz, la aserción fija **lo que hoy ocurre**. Si al correrlo descubres que una combinación se comporta de forma que te parece equivocada —por ejemplo un rol que edita algo que no debería—, **no la corrijas ni la ajustes**: escribe la aserción con el comportamiento real y anótala en el informe bajo «comportamiento a revisar». Esta tarea fija la línea base, no la mejora.

- [ ] **Step 3: Cubrir S11 y S13, que no se ven en las clases de celda**

S11 es el auto-abrir del dropdown y S13 es la card móvil. Ninguna se lee de `ps-cell-readonly`:
- S11: comprueba que al hacer clic en una celda de dropdown editable el editor se abre, y en una readOnly no.
- S13: en viewport móvil, comprueba que la card ofrece campo editable para `Compromiso`/`Ejecutado_Real` exactamente cuando la regla dice que la celda es editable, y texto plano cuando no. Es la prueba que **ata la card a la misma regla que la grilla**, y por eso es la más valiosa de la tarea para lo que viene después.

- [ ] **Step 4: Correr y estabilizar**

```bash
npx playwright test tests/browser/programacion-semanal-enablement.mjs --workers=1
```

Córrelo **tres veces**. Si alguna combinación es intermitente, no la marques como `skip`: averigua por qué y, si la causa es que la grilla necesita más tiempo para re-decidir, arregla la espera en el arnés. Una prueba intermitente en una red de caracterización es peor que no tenerla, porque enseña a ignorar el rojo.

- [ ] **Step 5: Commit**

```bash
git add tests/browser/programacion-semanal-enablement.mjs
git commit -m "test(programacion-semanal): caracteriza las nueve reglas de habilitacion de la grilla"
```

---

### Task 3: Caracterizar Intermedia (I1–I7)

**Files:**
- Create: `tests/browser/programacion-intermedia-enablement.mjs`

**Interfaces:**
- Consumes: `setEnablementContext` y `readCellDecisions` de la Task 1.

- [ ] **Step 1: La matriz, con las dos particularidades de Intermedia**

Misma matriz de roles, fases y semanas que en Semanal, más dos cosas que Semanal no tiene:
- **I3**, filas cabecera: incluye al menos una fila con `state === 'header'` y comprueba que ninguna columna es editable en ella.
- **I4**, candado por Responsable AIA: una fila **con** responsable y otra **sin** él, y comprueba que las columnas de restricción cambian de decisión entre las dos.

- [ ] **Step 2: I1, que depende de una respuesta de la API**

`editableProps` en Intermedia se construye desde `/api/general/restriction-config` y **se muta en caliente**. Caracteriza que las columnas de restricción que la config declara salen editables y las que no, no. Si la config del proyecto sembrado no tiene variedad suficiente para distinguirlo, **dilo en el informe** en vez de forzar el dato: sería una limitación de la red, no un fallo.

- [ ] **Step 3: I5, la excepción que ignora rol y fase**

`__shared_selected` es editable en toda fila no cabecera **sin mirar rol ni fase**. Compruébalo con el rol `V` y fase confirmada, que es donde todo lo demás está bloqueado. Es la regla más fácil de romper sin querer al extraer, porque contradice a las otras.

- [ ] **Step 4: I7, la re-verificación del dropdown**

Comprueba que al hacer clic en una celda de dropdown el editor solo se abre cuando I1–I3 lo permiten.

- [ ] **Step 5: Correr y estabilizar**

```bash
npx playwright test tests/browser/programacion-intermedia-enablement.mjs --workers=1
```

Tres corridas, mismo criterio que en la Task 2. Ojo especialmente aquí: Intermedia escribe `cellMeta` a mano y limpia clases del DOM (`syncRestrictionLockForVisualRow`, `refreshCellMetaForVisualRow`), así que la ventana entre inyectar contexto y leer decisiones es más frágil.

- [ ] **Step 6: Commit**

```bash
git add tests/browser/programacion-intermedia-enablement.mjs
git commit -m "test(programacion-intermedia): caracteriza las siete reglas de habilitacion de la grilla"
```

---

### Task 4: Declarar la red y lo que no cubre

Una red que no dice dónde tiene agujeros invita a confiar de más. Esta fase ya vio a una prueba venderse como completa cubriendo 1 regla de 107.

**Files:**
- Modify: `docs/design-system/manifests/programacion-semanal.json`, `programacion-intermedia.json` (declarar las pruebas nuevas en `tests`)
- Create: una sección en el informe con la tabla de cobertura

- [ ] **Step 1: Declarar las pruebas en los dos manifiestos**

Añade los dos archivos nuevos al array `tests` de su manifiesto. El gate comprueba que los archivos declarados existan, así que esto los ata al contrato.

- [ ] **Step 2: Escribir la tabla de cobertura, honesta**

Por cada una de las 22 reglas del inventario: cubierta, parcialmente cubierta o no cubierta, y en los dos últimos casos **por qué**. Los guards de valor (S7–S10, I8, I9) van como «fuera de alcance» con su motivo. Si alguna regla del inventario resultó no existir o comportarse distinto de lo que decía el censo, dilo: el censo también puede equivocarse.

- [ ] **Step 3: Anotar los comportamientos a revisar**

Todo lo que hayas caracterizado y te parezca cuestionable va aquí, con su combinación exacta. No se corrige en este plan; se deja escrito para decidir después.

- [ ] **Step 4: Cierre**

```bash
npm run test:design-system:static
```

Esperado: ocho puertas en verde, con los dos manifiestos declarando las pruebas nuevas.

- [ ] **Step 5: Commit**

```bash
git add docs/design-system/manifests/
git commit -m "docs(design-system): los dos manifiestos declaran la red de habilitacion"
```

---

## Condición de hecho

1. Las dos suites nuevas pasan **tres veces seguidas** sin intermitencias.
2. Cada una de las 22 reglas del inventario está clasificada como cubierta, parcial o fuera de alcance, **con motivo escrito** para las dos últimas.
3. La prueba de S13 ata la card móvil a la misma regla que la grilla — es la que sostiene que extraer no las desincronice.
4. `npm run test:design-system:static` en verde, con los dos manifiestos declarando las pruebas.
5. Ninguna regla de habilitación fue modificada: `git diff` sobre los dos `hot.js` vacío.

## Fuera de alcance

Extraer las reglas, cambiar el umbral, dejar de montar Handsontable, hacer que Intermedia edite, y la evidencia móvil. Todo eso viene después, con esta red puesta.
