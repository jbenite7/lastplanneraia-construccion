---
capa: fuente
tipo: plan
estado: cerrado
fecha: 2026-08-04
areas: [lps]
fuente: docs/superpowers/plans/2026-08-04-biblia-t2-cascada-lps.md
resumen: Que el ciclo Last Planner —Programa General, actualizar cronograma, Programación Intermedia, Programación Semanal y los tres submódulos de aprendizaje— tenga…
---

# Biblia de flujos · Tanda T2 (cascada LPS) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Que el ciclo Last Planner —Programa General, actualizar cronograma, Programación Intermedia, Programación Semanal y los tres submódulos de aprendizaje— tenga cada escenario descrito, verificado contra el código con cita, y los que tocan permisos, mutan datos o cierran periodo cubiertos por prueba ejecutable.

**Architecture:** Cinco documentos en `docs/flujos/`, uno por eslabón, más uno para las invariantes que cruzan la cascada (la herencia de actividades y el candado de semana confirmada). Cada escenario lleva `id` estable citado desde `e2e/`. Los hallazgos van al backlog único, sin arreglar.

**Tech Stack:** Markdown versionado · Playwright (`e2e/`, config propia) · Handsontable en las grillas operativas · PHP 8.3 en Docker · la puerta de servicio para abrir sesión con rol real.

## Global Constraints

- **Cláusula de autoridad:** si la biblia y el código divergen, **es un bug de uno de los dos y hay que resolverlo**; no se corrige la biblia en silencio.
- **Verificar, no sospechar:** cita `archivo:línea` leída en la sesión. Lo no comprobable en lectura se declara así; nunca se da por bueno.
- **Los hallazgos se registran y la pasada continúa.** Si la duda es *cuál es la conducta correcta*, decide el usuario.
- **T1 es prerrequisito.** Cada escenario de T2 empieza con «rol X, proyecto Y en sesión», estado que T1 define. No dupliques aquí los escenarios de sesión: cítalos por su `id` `AUTH`/`PROY`.
- **Sesión local solo por la puerta de servicio:** `/dev/entrar?u=test.R&p=<Proyecto_Proceso>`. Nunca `/login`.
- **Viewport 1180×820, dark only.** Sin evidencia de móvil, tablet ni `linen`.
- **Rol permitido y rol denegado** en todo escenario de capacidad.
- **Aislamiento por `project_id`** en toda consulta operativa: comprobarlo es escenario, no supuesto.
- **Prefijos de `id`:** `PG` (programa general), `CRO` (actualizar cronograma), `PI` (intermedia), `PS` (semanal), `APR` (CIC/CNC/CNP), `CAS` (invariantes de cascada).
- **Vocabulario:** el que fija `GLOSARIO.md`. Un escenario que invente un término para algo que ya tiene nombre en LPS es un escenario mal escrito.
- **No se hace commit sin petición explícita del usuario.**

---

## File Structure

| Archivo | Responsabilidad |
|---|---|
| `docs/flujos/lps-programa-general.md` (nuevo) | `PG-*` y `CRO-*`: la línea base y su actualización. 15 rutas. |
| `docs/flujos/lps-intermedia.md` (nuevo) | `PI-*`: ventana de medio plazo y levantamiento de restricciones. 8 rutas. |
| `docs/flujos/lps-semanal.md` (nuevo) | `PS-*`: el compromiso semanal y su confirmación. 9 rutas. |
| `docs/flujos/lps-aprendizaje.md` (nuevo) | `APR-*`: CNP, CNC y CIC — por qué no se programó, por qué no se cumplió, cuánto se cumplió. |
| `docs/flujos/lps-cascada.md` (nuevo) | `CAS-*`: lo que cruza eslabones — herencia de actividades, candado de semana, coherencia de fechas. |
| `e2e/tests/biblia/cascada-lps.spec.mjs` (nuevo) | Pruebas de los críticos, cada `test()` titulado con su `id`. |
| `docs/EXPERIMENTS.md` (modificar) | Backlog único; lo crea T1. |

---

### Task 1: El candado de semana, primero que todo

**Files:**
- Create: `docs/flujos/lps-cascada.md`
- Read: `src/Security/LpsWeekEditPolicy.php`, `src/Core/CommitmentLockGuard.php`, `src/Security/RbacCatalog.php`

**Interfaces:**
- Consumes: el formato del escenario de `docs/flujos/README.md` (T1).
- Produces: los `id` `CAS-001`…`CAS-0NN`, que los demás documentos de T2 citan en vez de repetir.

Va primero porque es la regla que atraviesa toda la cascada: qué semana se puede editar y quién.

- [ ] **Step 1: Desmenuza `LpsWeekEditPolicy::allows()` en escenarios**

Ya verificado el 2026-08-04, `src/Security/LpsWeekEditPolicy.php:16-47`. La función tiene **cinco salidas distintas** y cada una es un escenario:

1. `dbPrefix` vacío o `week <= 0` → `false` (entrada inválida).
2. Prefijo sin `project_id` resoluble → `false`.
3. `RbacCatalog::canEditLpsWeek($role, $week, $maxWeek)` → `true` (camino normal, depende de la semana máxima existente).
4. No es edición normal y **no** es calificación, o el rol no puede calificar → `false`.
5. Es calificación, el rol puede calificar y `Semanal_Confirmada = 1` → `true`.

La quinta es la interesante y la que hay que describir con más cuidado: **una semana confirmada sigue siendo editable para calificar compromisos**, que es justo lo contrario de lo que sugiere la palabra «confirmada». Descríbelo como escenario nombrando esa contraintuición.

- [ ] **Step 2: Lee `canEditLpsWeek` y vuelca su regla real**

```bash
grep -n -A 15 "function canEditLpsWeek" src/Security/RbacCatalog.php
grep -n -A 8 "function canQualifyWeeklyCommitment" src/Security/RbacCatalog.php
```

Escribe en el escenario la regla exacta que devuelvan esas dos funciones —qué roles, y qué relación entre `$week` y `$maxWeek`—, con su cita. No la deduzcas del nombre.

- [ ] **Step 3: Describe el guardián de mutaciones**

`src/Core/CommitmentLockGuard.php:25`:
`guard(string $dbPrefix, int $semana, string $operacion, bool $allowIfConfirmed = false): void`

Escenarios obligatorios: una mutación bloqueada por el guardián (qué recibe el cliente: código HTTP y cuerpo), una permitida, y **una con `allowIfConfirmed = true`**, que es la puerta para calificar. Lee qué hace exactamente al bloquear —¿lanza, responde, redirige?— y descríbelo; de eso depende que la grilla muestre un error entendible o se quede muda.

- [ ] **Step 4: Marca lo no comprobable en lectura**

El efecto real sobre la grilla (si el usuario ve un mensaje o la celda revierte en silencio) exige navegador. Decláralo «no comprobable en lectura» y anótalo como candidato a prueba ejecutable en la Task 6.

- [ ] **Step 5: Registra hallazgos y sigue**

Fila en `docs/EXPERIMENTS.md` con el `id` en `Origen`. Sin tocar `src/`.

---

### Task 2: Programa General y la actualización del cronograma

**Files:**
- Create: `docs/flujos/lps-programa-general.md`
- Read: `memoria/arquitectura/programa-general.md`, `memoria/arquitectura/cronograma.md`, los controladores que ambas citan

**Interfaces:**
- Consumes: `CAS-*` de la Task 1.
- Produces: `PG-*` y `CRO-*`.

- [ ] **Step 1: Parte del inventario ya generado**

```bash
sed -n '/generado:inicio/,/generado:fin/p' memoria/arquitectura/programa-general.md
```

Son 15 rutas. **No escribas un escenario por ruta**: agrupa por camino de negocio (crear la línea base, editarla, eliminar filas, crear semanas, exportar) y deja explícito qué ruta sirve a cada camino.

- [ ] **Step 2: Describe la edición del pasado como escenario propio**

`canEditPastGeneralProgram` solo la tienen `A` y `D` (verificado el 2026-08-04 en `RbacManager`), mientras `canEditGeneralProgram` la tienen `A`, `D`, `R` y `DCV`. Es decir: **un Residente puede editar el programa pero no su pasado.** Escenario obligatorio con su rol permitido y su denegado.

- [ ] **Step 3: Describe el borrado de filas**

`canDeleteRows` solo `A` y `D`. Un borrado es irreversible desde la interfaz: el escenario debe decir qué pasa con los datos dependientes (¿la actividad borrada estaba en una semana? ¿en intermedia?). Si el código no lo resuelve, es hallazgo, no una nota.

- [ ] **Step 4: Actualizar cronograma, con su trampa medida**

Antes de describir la geometría de esa vista lee `memoria/trampas/hot-container-height-ownership.md`: el patrón `calc(100vh - 49px)` es correcto sobre `.hot-full-bleed` y falso sobre `#hot-container`. No repitas el dato en la biblia —es implementación, no comportamiento—, pero sí describe **qué debe ver el usuario**: la grilla ocupando el alto disponible sin scroll doble.

- [ ] **Step 5: Verifica el aislamiento por proyecto en una consulta real**

Elige una consulta de listado del controlador y comprueba que filtra por `project_id`. Si alguna no lo hace, es hallazgo de severidad alta: fuga entre proyectos.

- [ ] **Step 6: Registra hallazgos y sigue**

---

### Task 3: Programación Intermedia

**Files:**
- Create: `docs/flujos/lps-intermedia.md`
- Read: `memoria/arquitectura/programacion-intermedia.md` y sus controladores

**Interfaces:**
- Consumes: `CAS-*`, y los `PG-*` de los que hereda actividades.
- Produces: `PI-*`.

- [ ] **Step 1: Describe la herencia desde Programa General**

Es el paso que da sentido al módulo: las actividades bajan del programa general a la ventana intermedia. Averigua leyendo el código **cómo** bajan (¿copia, referencia, selección manual?) y qué pasa si la actividad de origen cambia después. Ese «qué pasa después» es el escenario que más vale de toda la tanda, porque es donde el dato se desincroniza.

- [ ] **Step 2: Describe el levantamiento de restricciones**

`canEditConstraints` es capacidad propia. Escenarios: añadir restricción, levantarla, e **intentar comprometer en semanal una actividad con restricción viva** — que es justo la subentrega funcional que `docs/CUSTOMER.md` señala para el Residente. Si el código no impide eso, es hallazgo de producto, no de código: regístralo y que lo decida el usuario.

- [ ] **Step 3: Los ocho estados operativos**

`docs/design-system/CHANGELOG.md` menciona que Programación Intermedia muestra ocho estados operativos sobre el mapa compartido de urgencia. Enuméralos leyendo el código y describe **qué condición de datos produce cada uno**. Un estado que no se pueda alcanzar con ninguna combinación de datos es hallazgo.

- [ ] **Step 4: Verifica aislamiento y rol denegado**

`canEditMediumTerm` / `canManageMediumTermProgram`: lee sus listas de roles y cubre uno permitido y uno denegado.

- [ ] **Step 5: Registra hallazgos y sigue**

---

### Task 4: Programación Semanal

**Files:**
- Create: `docs/flujos/lps-semanal.md`
- Read: `memoria/arquitectura/programacion-semanal.md`, `public/js/modules/programacion_semanal/hot.js`, `changeMonitor.js`

**Interfaces:**
- Consumes: `CAS-*` (candado de semana), `PI-*` (de dónde vienen las actividades).
- Produces: `PS-*`.

- [ ] **Step 1: Describe qué dispara la carga de la vista**

Lee `memoria/trampas/semanal-auto-dispara-mutaciones.md` y **verifícala otra vez** contra el código: la nota dice que `save` exige `canManageToolbarActions()` (`hot.js:2097`) y `auto-program` exige `semana > 0` (`changeMonitor.js:36`). Que abrir una pantalla pueda escribir datos es comportamiento, no implementación: merece escenario explícito con sus dos condiciones.

- [ ] **Step 2: El compromiso, paso a paso**

Crear compromiso, editarlo, eliminarlo, y **confirmar la semana**. La confirmación es el punto de no retorno del ciclo: describe qué cambia en datos (`Semanal_Confirmada`) y qué deja de poderse hacer, citando `CAS-*` en vez de repetir la regla.

- [ ] **Step 3: La calificación tras la confirmación**

Enlaza con el escenario contraintuitivo de la Task 1: tras confirmar, ciertos roles siguen pudiendo calificar. Describe qué campos concretos siguen editables y cuáles no. Si en la práctica la grilla deja editar más de lo que la política permite, es hallazgo de seguridad.

- [ ] **Step 4: Los tres submódulos como salida**

CIC, CNC y CNP cuelgan de esta pantalla. Aquí solo describe **cuándo se alimentan** desde semanal; el detalle va en la Task 5.

- [ ] **Step 5: Registra hallazgos y sigue**

---

### Task 5: Los tres submódulos de aprendizaje

**Files:**
- Create: `docs/flujos/lps-aprendizaje.md`
- Read: `memoria/arquitectura/submodulo-cic.md`, `submodulo-cnc.md`, `submodulo-cnp.md`, `GLOSARIO.md`

**Interfaces:**
- Consumes: `PS-*`.
- Produces: `APR-*`.

- [ ] **Step 1: Fija el vocabulario antes de describir**

```bash
grep -n -i "CNP\|CNC\|CIC\|PPC" GLOSARIO.md
```

Usa esos términos exactos. Confundir CNC con CNP en la biblia sería un error caro: son causas de no cumplimiento y de no programación respectivamente.

- [ ] **Step 2: Describe el registro de causa como el escenario crítico del producto**

`docs/CUSTOMER.md` lo dice: la alternativa más peligrosa a la app es **no registrar la causa**, porque es más fácil. Así que estos escenarios llevan una pregunta extra: cuántos pasos y cuánta escritura exige registrar una causa. Ese número es evidencia para la fase 5 de `improve-app`; anótalo aunque el comportamiento sea correcto.

- [ ] **Step 3: Verifica el cálculo del PPC**

CIC mide cumplimiento. Lee la fórmula en el código y descríbela con sus casos borde: semana sin compromisos, compromisos parcialmente cumplidos, actividades añadidas después de confirmar. Un divisor cero mal tratado es hallazgo.

- [ ] **Step 4: Comprueba la coherencia con indicadores**

Las cifras que estos submódulos producen alimentan `/indicadores` y BI (tanda T5). Anota la dependencia como escenario `APR-*` que T5 citará, en vez de duplicarla.

- [ ] **Step 5: Registra hallazgos y sigue**

---

### Task 6: Pruebas ejecutables de la cascada

**Files:**
- Create: `e2e/tests/biblia/cascada-lps.spec.mjs`
- Modify: los cinco documentos de `docs/flujos/` para anotar la prueba en cada escenario cubierto

**Interfaces:**
- Consumes: los `id` de las tareas 1-5.
- Produces: pruebas tituladas con su `id`.

- [ ] **Step 1: Elige los críticos y di por qué**

Suben, como mínimo: el candado de semana confirmada (`CAS-*`), la calificación permitida tras confirmar (`PS-*`), la edición del pasado permitida a `D` y denegada a `R` (`PG-*`), y el disparo de mutaciones al cargar semanal (`PS-*`). Escribe también **qué no sube y por qué** — un recorte callado se lee como cobertura total.

- [ ] **Step 2: Prepara datos sin ensuciar el proyecto de nadie**

Usa el proyecto sandbox de las fixtures de `e2e/support/`. Antes de escribir, lee cómo se siembra y se restaura:

```bash
ls e2e/support/; grep -rn "sandbox\|seed\|restore" e2e/support/ | head
```

Si una prueba muta datos, debe restaurarlos. Una prueba que deja el sandbox sucio hace fallar a la siguiente y se diagnostica como bug inexistente.

- [ ] **Step 3: Escribe las pruebas**

```javascript
import { test, expect } from '@playwright/test';

test.use({ viewport: { width: 1180, height: 820 }, colorScheme: 'dark' });

test('PG-007 · Director edita el pasado del programa general y Residente no', async ({ page }) => {
  await page.goto('/dev/entrar?u=test.R&p=PDC%20Sandbox%20E2E');
  await page.goto('/programa-general');
  // El residente no debe poder editar una fila con fecha anterior a hoy.
  // Ajusta el selector tras inspeccionar la vista real.
  await expect(page.locator('[data-past-row] .htCore td').first()).toHaveAttribute('aria-readonly', 'true');
});
```

Los selectores de arriba son el patrón, no una promesa: inspecciona la vista y ajústalos. Si la vista no expone nada que distinga una fila del pasado, eso mismo es un hallazgo de accesibilidad y de testabilidad.

- [ ] **Step 4: Corre la suite**

```bash
npx playwright test e2e/tests/biblia/cascada-lps.spec.mjs --config=e2e/playwright.config.mjs --workers=1
```

**Si algo falla, no ajustes la prueba para que pase.** O la biblia describe mal el comportamiento (corrígela) o el código incumple (hallazgo al backlog). Esa bifurcación es el motivo del proyecto entero.

- [ ] **Step 5: Anota la prueba en cada escenario cubierto**

---

### Task 7: Cierre de la tanda

**Files:**
- Modify: `docs/EXPERIMENTS.md`, `docs/flujos/README.md`, `memoria/mapas/lps-dominio.md`, `memoria/flujos/flujo-lps.md`, `memoria/log.md`, `docs/IMPROVE-APP-PLAN.md`

- [ ] **Step 1: Prioriza el backlog por ICE**

Marca aparte los hallazgos donde la duda sea *cuál es la conducta correcta*: esos son decisión del usuario, no bugs con solución obvia.

- [ ] **Step 2: Teje la biblia en la wiki**

`memoria/flujos/flujo-lps.md` pasa a ser el resumen de entrada que **enlaza** los cinco documentos de biblia; `memoria/mapas/lps-dominio.md` los cita en «Qué manda». Explicita la diferencia de capas: la wiki cuenta el porqué, la biblia el qué debe pasar.

- [ ] **Step 3: Vuelca a `improve-app`**

Los escenarios de T2 son el insumo de la fase 3 (gulfs de Norman sobre PG→PI→PS) y de la fase 9. Anota en `docs/IMPROVE-APP-PLAN.md` `## Next Actions` que la fase 3 ya tiene su material.

- [ ] **Step 4: Lint y bitácora**

```bash
npm run test:wiki
```

Esperado: `Sin hallazgos`. Después, una línea `ingest` en `memoria/log.md` con los números medidos: escenarios descritos, verificados, ejecutables y hallazgos.

---

## Verificación final de T2

```bash
npx playwright test e2e/tests/biblia/cascada-lps.spec.mjs --config=e2e/playwright.config.mjs --workers=1
npm run test:wiki
```

Y las condiciones de hecho del spec que aplican a T2. La validación en navegador que exige `AGENTS.md` la cubren las pruebas de la Task 6, que corren contra el contenedor en el viewport canónico.

---

## Estado verificado — cerrado

Verificado contra el código el 2026-08-25. **`estado: cerrado` es una afirmación deliberada**, no el valor por defecto del backfill.

**Evidencia:** docs/flujos/lps-{programa-general,intermedia,semanal,aprendizaje,cascada}.md; e2e/tests/biblia/cascada-lps.spec.mjs; README.md:94

Criterio y método: [[docs/superpowers/plans/2026-08-25-estado-real-de-planes-y-specs]].
