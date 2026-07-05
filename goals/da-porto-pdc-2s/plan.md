# Plan — Da Porto PDC 2 semanas de control end-to-end

## Solution approach

Automatización 100% via Playwright MCP contra la app Docker corriendo en `http://localhost:8081`. Cada hito se valida con triple evidencia: captura de pantalla, aserción DOM (`playwright_browser_snapshot` / `verify_*`) y aserción SQL (`docker compose exec db mysql`). Al final, escribir `tests/browser/da-porto-pdc-full-cycle.mjs` que reproduce el flujo completo para CI.

El flujo recorre los tres módulos encadenados (Listado → Contratos → PDC) usando la semi-auto del backend, luego crea dos semanas nuevas validando el auto-update del PDC en cada una, y finalmente prueba la propagación de un cambio en Familias hacia el PDC con recálculo de fechas.

## Precondition

- Docker compose corriendo: `docker compose ps` muestra `db`, `app`, `adminer` Up.
- App responde en `http://localhost:8081/login`.
- Credenciales `jbenitez` / `Jbe#1106z` válidas.

## Ordered steps

### Step 0 — Verificar preconditions y patch de familias

**Touches:** `database/patches/20260701_da_porto_feedback_semi_auto.sql`, `general_pdc_familias`, `general_pdc_activity_rules`, `general_pdc_family_contract_options`

**Actions:**
1. `bash`: `docker compose ps` → confirmar `db` y `app` Up.
2. `bash`: Verificar patch aplicado:
   ```bash
   docker compose exec db mysql -uroot -proot lps_aia -e "SELECT COUNT(*) AS total FROM general_pdc_familias;"
   ```
   Esperado: ≥ 90.
3. `bash`: Verificar familias Da Porto específicas:
   ```bash
   docker compose exec db mysql -uroot -proot lps_aia -e "SELECT codigo FROM general_pdc_familias WHERE codigo IN ('REVOQUE_HUMEDO','REVOQUE_SECO','CABINAS_BANO','PINTURAS','PISO_LAMINADO') AND deleted_at IS NULL;"
   ```
   Esperado: ≥ 3 filas. Si 0 → aplicar patch:
   ```bash
   docker compose exec -T db mysql -uroot -proot lps_aia < database/patches/20260701_da_porto_feedback_semi_auto.sql
   ```
   Re-verificar.

**Verification:** `fact-seed-patch` — COUNT ≥ 90 y familias Da Porto presentes.

**Risks:** Si el patch usa `INSERT IGNORE` o `ON DUPLICATE KEY UPDATE` es idempotente y seguro re-aplicar. Si no, re-aplicar puede generar duplicados — verificar antes de aplicar a ciegas.

---

### Step 1 — Login y selección de proyecto Da Porto

**Touches:** `/login`, `/proyectos`, sesión PHP

**Actions:**
1. `playwright_browser_navigate` → `http://localhost:8081/login`
2. `playwright_browser_snapshot` → identificar campos `#usuario`, `#password`, botón submit.
3. `playwright_browser_fill_form` → `jbenitez` / `Jbe#1106z`.
4. Click submit → esperar redirección a `/proyectos`.
5. `playwright_browser_snapshot` → buscar card con texto "Da Porto".
6. Click en botón dentro del card de Da Porto.
7. Esperar redirección al shell de módulos.
8. `playwright_browser_take_screenshot` → guardar como `tests/browser/evidence/da-porto-pdc-2s/01-post-login.png`.
9. Detectar `startingWeek`:
   ```bash
   docker compose exec db mysql -uroot -proot lps_aia -N -e "SELECT MAX(semana) FROM semanas_activas WHERE project_id=73;"
   ```
   Guardar valor en variable de seguimiento.

**Verification:** `fact-login`, `fact-start-week`. Snapshot muestra navbar con Da Porto y switcher visible. SQL devuelve startingWeek.

---

### Step 2 — Listado de actividades: validar familias

**Touches:** `/listado-actividades`, `general_pdc_familias`, `general_pdc_activity_rules`, `actividades`

**Actions:**
1. `playwright_browser_navigate` → `http://localhost:8081/listado-actividades`
2. `playwright_browser_snapshot` → esperar `#dt_cliente` visible.
3. Verificar que la tabla tenga filas (al menos 5).
4. `playwright_browser_take_screenshot` → `02-post-listado.png`.
5. SQL: verificar que las actividades tienen familieas asignadas vía rules:
   ```bash
   docker compose exec db mysql -uroot -proot lps_aia -N -e "
     SELECT a.actividad, f.codigo, r.patron_regex
     FROM actividades a
     JOIN general_pdc_activity_rules r ON a.actividad REGEXP r.patron_regex
     JOIN general_pdc_familias f ON r.familia_id = f.id
     WHERE a.project_id = 73
     LIMIT 10;
   "
   ```
   Esperado: ≥ 5 filas con match regex.

**Verification:** `fact-listado-families`. Snapshot + SQL confirman familias detectadas.

---

### Step 3 — Reset "desde cero absoluto": limpiar actividades de Da Porto

**Touches:** `actividades` (project_id=73), `pdc` (project_id=73)

**Actions:**
1. Capturar snapshot de estado actual (para reporte, no para restore):
   ```bash
   docker compose exec db mysql -uroot -proot lps_aia -N -e "
     SELECT 'actividades' AS tbl, COUNT(*) FROM actividades WHERE project_id=73
     UNION ALL
     SELECT 'pdc', COUNT(*) FROM pdc WHERE project_id=73;
   "
   ```
   Registrar counts en `goals/da-porto-pdc-2s/pre-reset-counts.txt`.
2. DELETE controlado:
   ```bash
   docker compose exec db mysql -uroot -proot lps_aia -e "
     DELETE FROM pdc WHERE project_id=73;
     DELETE FROM actividades WHERE project_id=73;
   "
   ```
3. Verificar counts = 0.
4. `playwright_browser_navigate` → `http://localhost:8081/contratos` → snapshot → tabla vacía.
5. Screenshot `03-post-reset.png`.

**Verification:** `fact-contratos-reset`. SQL confirma counts=0; DOM muestra tabla vacía.

**Risks:** Esto borra datos reales de Da Porto. El usuario eligió "persist_as_new_seed" → no hay rollback. Aceptar que el estado final será el generado por la automation.

---

### Step 4 — Re-crear actividades desde el Programa General (si es necesario)

**Touches:** `actividades` (origen de Da Porto), `programa-general`

**Actions:**
1. Verificar si el Programa General de Da Porto tiene datos (la fuente original de actividades):
   ```bash
   docker compose exec db mysql -uroot -proot lps_aia -N -e "
     SELECT COUNT(*) FROM programa_general WHERE project_id=73;
   "
   ```
   (o la tabla equivalente — puede ser `pi_programacion_intermedia` o similar; verificar nombre real).
2. Si hay datos en PG/PI, navegar a `/programa-general` y usar el flujo de "Listado de actividades" para regenerar desde el master program. Si no hay master program, insertar actividades de prueba directamente vía SQL desde el patch original de Da Porto (si existe un seed de actividades).
3. **Alternativa pragmática**: Re-aplicar el seed de actividades de Da Porto si existe un patch con `INSERT INTO actividades ... project_id=73`. Buscar en `database/patches/` y `database/migrations/` un seed de Da Porto.

**Verification:** `actividades` tiene ≥ 5 filas para project_id=73.

**Risks/Open questions:** ¿Existe un seed de actividades para Da Porto, o las 30 filas venían de un dump manual? Si no hay seed, necesito re-crear actividades manualmente desde el Programa General. Esto puede requerir exploración adicional del esquema de PG.

---

### Step 5 — Contratos: semi-auto preview + apply (modalidades y paquetes)

**Touches:** `/contratos`, `/api/contratos/auto/preview`, `/api/contratos/auto/apply`, `actividades` (modalidades, paquetes, cantidades), `semi_auto_runs`, `semi_auto_suggestions`, `semi_auto_decisions`

**Actions:**
1. `playwright_browser_navigate` → `http://localhost:8081/contratos`
2. Esperar `window.SemiAutoReview` cargado:
   ```js
   // via playwright_browser_evaluate
   () => !!(window.jQuery && window.SemiAutoReview)
   ```
3. Disparar preview:
   ```js
   () => window.SemiAutoReview.open('contratos')
   ```
4. Esperar respuesta de `/api/contratos/auto/preview` via `playwright_browser_evaluate` con fetch, o esperar panel `#semiAutoReview-contratos` visible.
5. `playwright_browser_snapshot` del panel → capturar sugerencias.
6. Seleccionar todas las sugerencias con `sar-check-all` y click `sar-btn-apply`.
7. Esperar recarga de página.
8. `playwright_browser_take_screenshot` → `04-post-contratos-apply.png`.
9. SQL validar:
   ```bash
   docker compose exec db mysql -uroot -proot lps_aia -N -e "
     SELECT COUNT(*) FROM actividades WHERE project_id=73 AND (paqueteSI1 IS NOT NULL OR paqueteMO1 IS NOT NULL OR paqueteS1 IS NOT NULL OR paqueteOC1 IS NOT NULL);
   "
   ```
   Esperado: > 0.
10. SQL validar run registrado:
    ```bash
    docker compose exec db mysql -uroot -proot lps_aia -N -e "
      SELECT id, module, status, created_at FROM semi_auto_runs WHERE project_id=73 AND module='contratos' ORDER BY id DESC LIMIT 1;
    "
    ```

**Verification:** `fact-semiauto-preview-contratos`, `fact-modalidades-assigned`, `fact-semi-auto-run-log`.

---

### Step 6 — Contratos: validar paquetes, cantidades e insumos/recursos

**Touches:** `actividades` (paqueteSIx, cantidadSIx, etc.), modal de contratos, `general_pdc_family_contract_option_items`

**Actions:**
1. En `/contratos`, click en botón `editar` de la primera fila → abre `#modalEditarContratos`.
2. `playwright_browser_snapshot` del modal → verificar secciones de modalidades y slots de paquetes.
3. Verificar que al menos un slot `paqueteSI1` (o equivalente) tiene texto no vacío.
4. Verificar `cantidadSI1` (o equivalente) tiene valor numérico >0.
5. Verificar sección "Insumos y recursos requeridos" visible en el modal.
6. `playwright_browser_take_screenshot` → `05-contratos-modal.png`.
7. Cerrar modal.
8. SQL validar paquetes poblados:
   ```bash
   docker compose exec db mysql -uroot -proot lps_aia -N -e "
     SELECT codigo, paqueteSI1, cantidadSI1, paqueteMO1, cantidadMO1
     FROM actividades WHERE project_id=73
     AND (paqueteSI1 IS NOT NULL AND paqueteSI1 != '')
     LIMIT 5;
   "
   ```

**Verification:** `fact-paquetes-populated`, `fact-cantidad-contratos`, `fact-insumos-recursos`.

---

### Step 7 — PDC: generar plan de compras con "Actualizar"

**Touches:** `/pdc`, `/api/pdc/auto/apply-from-contratos`, tabla `pdc`

**Actions:**
1. `playwright_browser_navigate` → `http://localhost:8081/pdc`
2. `playwright_browser_snapshot` → esperar `#dt_cliente` y toolbar visible.
3. Localizar botón `#btn_actualizarPDC` → click.
4. Esperar SweetAlert de confirmación si aparece → aceptar.
5. Esperar recarga de página o respuesta de `/api/pdc/auto/apply-from-contratos`.
6. `playwright_browser_snapshot` → verificar filas en `#dt_cliente`.
7. `playwright_browser_take_screenshot` → `06-post-pdc-generate.png`.
8. SQL validar PDC generado:
   ```bash
   docker compose exec db mysql -uroot -proot lps_aia -N -e "
     SELECT COUNT(*) FROM pdc WHERE project_id=73 AND semana=<startingWeek>;
   "
   ```
   Esperado: > 0.
9. SQL validar fechas calculadas:
   ```bash
   docker compose exec db mysql -uroot -proot lps_aia -N -e "
     SELECT consecutivo, paqueteContratacion, tipoPaquete,
            fechaElaboracionPliegos, fechaEntregaPliegos, fechaReciboPliegos, fechaInicioProyectada
     FROM pdc WHERE project_id=73 AND semana=<startingWeek>
     AND fechaElaboracionPliegos IS NOT NULL
     LIMIT 5;
   "
   ```
10. SQL validar estados:
    ```bash
    docker compose exec db mysql -uroot -proot lps_aia -N -e "
      SELECT DISTINCT estado FROM pdc WHERE project_id=73 AND semana=<startingWeek>;
    "
    ```
    Verificar que cada estado está en el set de 7 legend states.

**Verification:** `fact-pdc-generated`, `fact-pdc-calculated-dates`, `fact-pdc-states`, `fact-semi-auto-run-log`.

---

### Step 8 — Crear semana startingWeek+1 y validar auto-update del PDC

**Touches:** `/legacy/funciones_generales/php/nueva_semana.php`, `semanas_activas`, `pdc`, `src/Legacy/nueva_semana.php`, `src/Legacy/_pdc_functions.php`

**Actions:**
1. Capturar count de pdc en semana startingWeek (baseline):
   ```bash
   docker compose exec db mysql -uroot -proot lps_aia -N -e "
     SELECT COUNT(*) FROM pdc WHERE project_id=73 AND semana=<startingWeek>;
   "
   ```
2. Capturar filas con `numeroSubcontratos > 1` (para validar duplicación):
   ```bash
   docker compose exec db mysql -uroot -proot lps_aia -N -e "
     SELECT consecutivo, numeroSubcontratos, subcontratoPaquete
     FROM pdc WHERE project_id=73 AND semana=<startingWeek> AND numeroSubcontratos > 1;
   "
   ```
3. Crear nueva semana via `playwright_browser_evaluate` (fetch POST):
   ```js
   async () => {
     const form = new FormData();
     form.append('db', 'da_porto');
     form.append('semana_crear', '<startingWeek+1>');
     const res = await fetch('/legacy/funciones_generales/php/nueva_semana.php', {
       method: 'POST', body: form, credentials: 'same-origin'
     });
     return { status: res.status, text: await res.text() };
   }
   ```
   Alternativa: si el endpoint requiere params diferentes, usar `postFormJson` pattern del session.mjs.
4. Verificar semana creada:
   ```bash
   docker compose exec db mysql -uroot -proot lps_aia -N -e "
     SELECT semana FROM semanas_activas WHERE project_id=73 AND semana=<startingWeek+1>;
   "
   ```
5. Validar copia de PDC:
   ```bash
   docker compose exec db mysql -uroot -proot lps_aia -N -e "
     SELECT COUNT(*) FROM pdc WHERE project_id=73 AND semana=<startingWeek+1>;
   "
   ```
   Esperado: ≥ count de startingWeek.
6. Validar recompute de paquetes (al menos una fila nueva no es copia literal):
   ```bash
   docker compose exec db mysql -uroot -proot lps_aia -N -e "
     SELECT consecutivo, paqueteContratacion, tipoPaquete
     FROM pdc WHERE project_id=73 AND semana=<startingWeek+1>
     ORDER BY consecutivo LIMIT 10;
   "
   ```
   Comparar con startingWeek — si hay filas nuevas o consecutivos diferentes, recompute funcionó.
7. Validar recompute de estados:
   ```bash
   docker compose exec db mysql -uroot -proot lps_aia -N -e "
     SELECT estado, COUNT(*) FROM pdc WHERE project_id=73 AND semana=<startingWeek+1> GROUP BY estado;
   "
   ```
   Comparar con distribución de estados de startingWeek.
8. Validar duplicación de subcontratos:
   ```bash
   docker compose exec db mysql -uroot -proot lps_aia -N -e "
     SELECT consecutivo, subcontratoPaquete, numeroSubcontratos
     FROM pdc WHERE project_id=73 AND semana=<startingWeek+1> AND numeroSubcontratos > 1;
   "
   ```
   Verificar que hay filas con `subcontratoPaquete` diferentes para el mismo consecutivo.
9. Cambiar contexto a semana nueva:
   ```js
   async () => {
     const res = await fetch('/context/week', {
       method: 'POST', headers: {'Content-Type':'application/json'},
       body: JSON.stringify({ semana: <startingWeek+1> }), credentials: 'same-origin'
     });
     return res.status;
   }
   ```
10. `playwright_browser_navigate` → `http://localhost:8081/pdc` → snapshot → verificar filas.
11. `playwright_browser_take_screenshot` → `07-post-semana-1.png`.

**Verification:** `fact-new-week-1-created`, `fact-pdc-copy-prev-week-1`, `fact-pdc-recompute-packages-1`, `fact-pdc-recompute-states-1`, `fact-duplicate-subcontracts-1`.

**Risks:** El endpoint `nueva_semana.php` puede requerir parámetros adicionales (project_id, db, etc.) que no conozco. Si falla, inspeccionar el form del botón "Nueva semana" en la UI real para capturar los campos exactos.

---

### Step 9 — Propagación: cambiar familia en Listado → reflejar en PDC con recálculo de fechas

**Touches:** `/listado-actividades`, `actividades` (cambio de actividad asociada), `/pdc` (re-update), `pdc` (recálculo de fechas)

**Actions:**
1. `playwright_browser_navigate` → `http://localhost:8081/listado-actividades`
2. Seleccionar una actividad/familia existente.
3. Antes de cambiar, capturar la fila PDC asociada:
   ```bash
   docker compose exec db mysql -uroot -proot lps_aia -N -e "
     SELECT consecutivo, paqueteContratacion, fechaInicioProyectada, fechaElaboracionPliegos
     FROM pdc WHERE project_id=73 AND semana=<startingWeek+1>
     AND paqueteContratacion LIKE '%<actividad_original>%'
     LIMIT 1;
   "
   ```
4. Editar la actividad en el listado (cambiar nombre o asociación de familia):
   - Click editar → modal → cambiar actividad asociada → guardar.
   - Alternativa: SQL UPDATE directo para simular el cambio:
     ```bash
     docker compose exec db mysql -uroot -proot lps_aia -e "
       UPDATE actividades SET actividad='<nuevo_nombre>' WHERE project_id=73 AND Id=<id_elegido>;
     "
     ```
5. Navegar a `/pdc` → click `#btn_actualizarPDC`.
6. Esperar recarga.
7. Verificar que el PDC refleja el cambio:
   ```bash
   docker compose exec db mysql -uroot -proot lps_aia -N -e "
     SELECT consecutivo, paqueteContratacion, fechaInicioProyectada, fechaElaboracionPliegos
     FROM pdc WHERE project_id=73 AND semana=<startingWeek+1>
     AND paqueteContratacion LIKE '%<nuevo_nombre>%'
     LIMIT 1;
   "
   ```
8. Verificar que las fechas se recalcularon (diferentes a las anteriores).
9. `playwright_browser_take_screenshot` → `08-post-propagacion.png`.

**Verification:** `fact-propagate-family-change`.

**Risks:** El formato exacto de `paqueteContratacion` puede no incluir el nombre de la actividad literal — puede usar el código de familia. Necesito inspeccionar el contenido real de `paqueteContratacion` antes de asumir que contiene el nombre de la actividad.

---

### Step 10 — Crear semana startingWeek+2 y repetir validación de auto-update

**Touches:** mismos que Step 8 pero para startingWeek+2

**Actions:**
1. Repetir Steps 8.1-8.11 con `semana_crear=<startingWeek+2>`.
2. Validar todas las aserciones (copia, recompute paquetes, recompute estados, duplicación subcontratos) para semana startingWeek+2.
3. `playwright_browser_take_screenshot` → `09-post-semana-2.png`.

**Verification:** `fact-new-week-2-created`, `fact-pdc-auto-update-cycle-2`.

---

### Step 11 — Validar persistencia final (sin rollback)

**Touches:** todas las tablas afectadas

**Actions:**
1. SQL recuento final:
   ```bash
   docker compose exec db mysql -uroot -proot lps_aia -N -e "
     SELECT 'actividades' AS tbl, COUNT(*) FROM actividades WHERE project_id=73
     UNION ALL SELECT 'pdc_startingWeek', COUNT(*) FROM pdc WHERE project_id=73 AND semana=<startingWeek>
     UNION ALL SELECT 'pdc_startingWeek+1', COUNT(*) FROM pdc WHERE project_id=73 AND semana=<startingWeek+1>
     UNION ALL SELECT 'pdc_startingWeek+2', COUNT(*) FROM pdc WHERE project_id=73 AND semana=<startingWeek+2>
     UNION ALL SELECT 'semanas_activas', COUNT(*) FROM semanas_activas WHERE project_id=73;
   "
   ```
2. Verificar todos > 0.
3. `playwright_browser_take_screenshot` → `10-final-state.png`.

**Verification:** `fact-persisted-result`.

---

### Step 12 — Generar mini-reporte de métricas

**Touches:** `semi_auto_runs`, `semi_auto_suggestions`, `semi_auto_decisions`, `goals/da-porto-pdc-2s/metrics-report.md`

**Actions:**
1. SQL métricas:
   ```bash
   docker compose exec db mysql -uroot -proot lps_aia -N -e "
     SELECT module, COUNT(*) AS runs, MIN(created_at) AS first, MAX(created_at) AS last
     FROM semi_auto_runs WHERE project_id=73 GROUP BY module;
   "
   ```
2. SQL confidence distribution:
   ```bash
   docker compose exec db mysql -uroot -proot lps_aia -N -e "
     SELECT
       CASE WHEN confidence >= 80 THEN 'ready (80-100)'
            WHEN confidence >= 50 THEN 'review (50-79)'
            ELSE 'not-recommended (<50)' END AS bucket,
       COUNT(*) AS total
     FROM semi_auto_suggestions WHERE project_id=73 GROUP BY bucket;
   "
   ```
3. SQL decisiones:
   ```bash
   docker compose exec db mysql -uroot -proot lps_aia -N -e "
     SELECT action, COUNT(*) FROM semi_auto_decisions WHERE project_id=73 GROUP BY action;
   "
   ```
4. Escribir `goals/da-porto-pdc-2s/metrics-report.md` con los resultados en formato markdown.

**Verification:** `fact-metrics-report`, `fact-semi-auto-run-log`.

---

### Step 13 — Escribir `tests/browser/da-porto-pdc-full-cycle.mjs`

**Touches:** `tests/browser/da-porto-pdc-full-cycle.mjs`, `tests/browser/evidence/da-porto-pdc-2s/`

**Actions:**
1. Delegar a `task(category="unspecified-high", load_skills=["frontend"])` con prompt detallado que reproduzca todos los steps anteriores como test Playwright `.mjs` reutilizable.
2. El test debe:
   - Importar `PROJECTS`, `loginAndSelectProject`, `changeWeek`, `ProjectDbSnapshot`, `installErrorCollectors`, `assertNoRuntimeErrors` de los support files existentes.
   - Reproducir Steps 0-12 como tests Playwright con `page.evaluate` para fetch POST y `playwright_browser_evaluate` equivalentes.
   - Guardar capturas en `tests/browser/evidence/da-porto-pdc-2s/`.
   - Usar `beforeAll` para login + selectProject, `beforeEach` para snapshot, `afterEach` para assertions.
   - **No** hacer rollback (el usuario eligió persist_as_new_seed).
3. Validar que el test compile (`npx playwright test tests/browser/da-porto-pdc-full-cycle.mjs --list`).

**Verification:** `fact-regression-mjs`, `fact-visual-evidence`.

---

## Verification summary per fact

| Fact | Step | Verification method |
|---|---|---|
| fact-login | 1 | Snapshot DOM + screenshot |
| fact-seed-patch | 0 | SQL COUNT |
| fact-start-week | 1 | SQL MAX(semana) |
| fact-listado-families | 2 | SQL regex match + snapshot |
| fact-contratos-reset | 3 | SQL COUNT=0 + snapshot |
| fact-semiauto-preview-contratos | 5 | HTTP 200 + SQL run_id |
| fact-modalidades-assigned | 5 | SQL COUNT modalidad != null |
| fact-paquetes-populated | 6 | Modal snapshot + SQL |
| fact-cantidad-contratos | 6 | Modal snapshot + SQL numérico >0 |
| fact-insumos-recursos | 6 | Modal snapshot sección visible |
| fact-pdc-generated | 7 | SQL COUNT >0 |
| fact-pdc-calculated-dates | 7 | SQL fechas pobladas + consistencia |
| fact-pdc-states | 7 | SQL estado en set de 7 |
| fact-new-week-1-created | 8 | SQL semana en semanas_activas |
| fact-pdc-copy-prev-week-1 | 8 | SQL COUNT new ≥ old |
| fact-pdc-recompute-packages-1 | 8 | SQL comparar consecutivos |
| fact-pdc-recompute-states-1 | 8 | SQL distribución estados |
| fact-duplicate-subcontracts-1 | 8 | SQL subcontratoPaquete diferentes |
| fact-propagate-family-change | 9 | SQL paqueteContratacion + fechas |
| fact-new-week-2-created | 10 | SQL semana en semanas_activas |
| fact-pdc-auto-update-cycle-2 | 10 | Repetir aserciones 15-18 |
| fact-persisted-result | 11 | SQL COUNT >0 post-ejecución |
| fact-semi-auto-run-log | 12 | SQL semi_auto_runs |
| fact-metrics-report | 12 | Archivo metrics-report.md |
| fact-regression-mjs | 13 | tests/browser/da-porto-pdc-full-cycle.mjs |
| fact-visual-evidence | 1-13 | Screenshots en evidence/ |

## Risks and open questions

1. **Seed de actividades Da Porto**: Las 30 filas originales de `actividades` para Da Porto — ¿vienen de un patch SQL, un dump, o del Programa General? Si no hay seed reproducible, el "desde cero absoluto" pierde las actividades y no puedo regenerar familias sin master program. **Mitigación**: Antes del DELETE del Step 3, hacer `mysqldump` de las actividades actuales y guardarlo como `goals/da-porto-pdc-2s/da-porto-actividades-seed.sql` para poder re-aplicar si la semi-auto no las regenera.

2. **Parámetros de `nueva_semana.php`**: El endpoint legacy puede requerir campos que no conozco (project_id vs db vs semana_crear). Si falla, inspeccionar el form del botón "Nueva semana" en la UI real para capturar los campos exactos.

3. **Formato de `paqueteContratacion` en tabla pdc**: Para la propagación (Step 9), asumo que `paqueteContratacion` contiene el nombre de la actividad. Puede usar código de familia o un formato compuesto. Necesito inspeccionar el contenido real antes de asumir.

4. **`pdc_insertarPaquetes` vs copia literal**: Diferenciar entre filas copiadas de la semana anterior y filas recalculadas por `pdc_insertarPaquetes` puede ser sutil. Si todas las filas son copias idénticas, la aserción de "recompute" fallará. **Mitigación**: Antes de crear la semana, insertar una actividad nueva en `actividades` que no existía en la semana anterior — así `pdc_insertarPaquetes` debe generar una fila nueva que no es copia.

5. **Idempotencia del patch**: Re-aplicar `20260701_da_porto_feedback_semi_auto.sql` si no está applied — verificar que es idempotente (INSERT IGNORE o ON DUPLICATE KEY UPDATE) antes de aplicar a ciegas.

6. **Tensión persist_as_new_seed + regression_mjs**: El test `.mjs` reproduce el flujo, pero si persiste datos, cada corrida del test en CI dejaría basura acumulada. **Resolución**: El test `.mjs` debe hacer snapshot+restore (como hacen los demás tests), pero la corrida viva via MCP persiste (como pidió el usuario). Documentar esta diferencia en el header del `.mjs`.