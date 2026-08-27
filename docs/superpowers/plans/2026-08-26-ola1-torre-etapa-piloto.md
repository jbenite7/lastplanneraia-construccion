---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-08-26
areas: [bi, design-system, rbac, datos]
fuente: docs/superpowers/plans/2026-08-26-ola1-torre-etapa-piloto.md
resumen: "Plan de la Ola 1, etapa piloto: del cimiento del catálogo ejecutable a la hoja de Intermedia completa en React — escritura, linaje, móvil y tema claro. Produce el número que dimensiona todo lo demás"
---

# Ola 1 — La Torre escribe · Etapa piloto · Plan de implementación

> **Para trabajadores agénticos:** SUB-SKILL REQUERIDA: usa `superpowers:subagent-driven-development`
> (recomendado) o `superpowers:executing-plans`, tarea por tarea. Las casillas (`- [ ]`) son
> seguimiento de ejecución — **en este repo no son evidencia de avance** (`AGENTS.md` §Verificación).

**Goal:** que un director asigne responsable y fecha a una restricción sin salir de la hoja de
Intermedia — en escritorio y celular, en oscuro y claro — y que cada cifra responda «¿de dónde
salgo?» con un clic. Al cerrar, **existe el número del piloto** (paradas + decisiones consumidas)
que dimensiona el resto de la Torre y de la v0.

**Architecture:** tres capas que avanzan en este orden. (1) El **cimiento PHP**: `MetricExecutor`
nuevo que ejecuta las declaraciones del catálogo con prueba de paridad métrica por métrica; el SQL
duplicado muere solo cuando la paridad pasa. (2) La **migración de restricciones**, tarea propia con
gate completo — nunca dentro de una tarea de interfaz. (3) La **hoja piloto en React + TypeScript**
(`ct-app/`, patrón de `pdc-app/`), consumiendo los tokens CSS del design system y entrando a los
gates como cualquier pantalla (decisión N11 de la spec).

**Tech Stack:** PHP 8.3 (catálogo, ejecutor, endpoint, RBAC) · MySQL 8 · React 18 + TypeScript +
Vite (`ct-app/`, espejo de `pdc-app/`) · Vitest · Playwright · node test runner para gates.

**Spec:** `docs/superpowers/specs/2026-08-26-v0-del-producto-design.md` (v1, aprobada). La Parte II
(CT-1 a CT-20) manda en el cómo; las decisiones 1–12 y N1–N11 no se re-litigan.

**Alcance de ESTA etapa y qué queda fuera:** cubre lo que el piloto necesita — restos de F0, el
cimiento F1 y la F2 completa sobre la hoja de Intermedia. **F3 (narrativa de las demás hojas), F4
(salir del escondite) y F5 (jubilación de Power BI + retiro de tablas del PDC v1, medido hoy: las
cuatro tablas SIGUEN en la base de dev) se planean en la etapa siguiente, re-priorizadas con el
número del piloto en la mano** — es la protección que la propia spec exige en sus riesgos.

## Global Constraints

- **La migración de esquema exige autorización explícita de Felipe, respaldo verificable probado,
  dry-run y reversa escrita** (`docs/global-tables-architecture.md`). La base de dev se congela
  entera para terceros durante la ventana (regla 6 de `docs/coordinacion-sesiones.md`).
- **Paso 0 antes de leer cualquier RC del contenedor:**
  `docker inspect last-planner-aia-app-1 --format '{{range .Mounts}}{{.Source}} -> {{.Destination}}{{"\n"}}{{end}}' | grep html`
- **RC siempre en su propia línea** — nunca tras tubería ni encadenado (en zsh, `$pipestatus[1]`).
- **Sesión local solo por la puerta de servicio**: `http://localhost:8081/dev/entrar?u=test.A&p=...`
  (roles: `test.A` permitido, `test.V` denegado). Jamás credenciales en `/login`.
- **TDD con roles separados**: quien escribe la prueba de una tarea no escribe su implementación en
  la misma tarea (regla del repo, medida: la detección cae de 25% a 14% si es el mismo).
- **Rojos preexistentes**: leer `memoria/trampas/suite-php-rojos-preexistentes.md` antes de atribuirse
  un fallo. `/tmp` es de solo lectura en el contenedor (dos rc=255 perpetuos que no son de nadie).
- **Cierre por Pull Request con CI en verde** (`AGENTS.md` §Publicación, política del 2026-08-26).
  La rama nace al arrancar: `git checkout -b ola1-torre-piloto`.
- **Ninguna baseline ni deuda se regenera para poner algo verde.**
- **UI**: dark por defecto, 1180×820 canónico; móvil 390×844; tema claro anclado al manual AIA con
  visto visual de Felipe antes de propagar (decisión 10). Sin hex fuera de tokens.
- **El contador del piloto corre desde la Tarea 1**: cada vez que lo escrito no coincida con el
  código y haya que parar, se anota una **parada** en la sección `## Bitácora del piloto` de este
  plan; cada pregunta que suba a Felipe, una **decisión consumida**. Es el entregable, no un adorno.

---

### Task 1: Los restos de F0 que el piloto necesita

**Files:**
- Modify: `src/Services/Bi/MetricDictionaryService.php` (retiro funcional)
- Test: `tests/unit/MetricCatalogHygieneTest.php` (PHPUnit, `#[Group('puro')]`)

**Interfaces:**
- Produces: catálogo sin referencias a campos muertos; la línea base del criterio de muerte medida.

- [ ] **Paso 1 (rol A): escribir la prueba de higiene, que debe fallar**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Bi\MetricDictionaryService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('puro')]
final class MetricCatalogHygieneTest extends TestCase
{
    public function testNingunaMetricaReferenciaCamposRetirados(): void
    {
        // N8: reprogramaciones_semanales salió por retiro funcional; los demás
        // «muertos» (Categoria_CP, CP, alerta_crisis) están VIVOS y se quedan.
        $texto = json_encode((new MetricDictionaryService())->exportDictionary());
        self::assertStringNotContainsString('reprogramaciones_semanales', $texto);
    }

    public function testCicAprobacionHeredaLaInhabilitacionDelIntegral(): void
    {
        // CT-20.3: clasificar un número que no se publica sería publicarlo por
        // la puerta de atrás. El estado de aprobación declara su dependencia.
        $def = (new MetricDictionaryService())->getDefinition('cic_aprobacion_status');
        self::assertSame('cic_cal_integral', $def['completeness_inherits_from'] ?? null);
    }
}
```

- [ ] **Paso 2: correr y ver el fallo** — `docker compose exec -T app vendor/bin/phpunit --group puro --filter MetricCatalogHygiene` → FAIL (la clave `completeness_inherits_from` no existe).
- [ ] **Paso 3 (rol B): implementar** — añadir `'completeness_inherits_from' => 'cic_cal_integral'` a `cic_aprobacion_status` en el catálogo; verificar con `grep -n reprogramaciones_semanales src/Services/Bi/` que no hay referencia (hoy no la hay: el retiro funcional es que ninguna entre).
- [ ] **Paso 4: correr y ver el verde**, RC en su línea.
- [ ] **Paso 5: medir y anotar la línea base del criterio de muerte** (CT-15) — correr contra la base de dev la consulta de actividades-semana con las cinco restricciones intactas y escribir el porcentaje y la fecha en la `## Bitácora del piloto`. La línea que manda se re-mide el día que F2 publique.
- [ ] **Paso 6: commit** — `test(bi): higiene del catalogo — herencia de inhabilitacion y retiro funcional`.

---

### Task 2: `MetricExecutor` — el catálogo pasa de describir a ejecutar

**Files:**
- Create: `src/Services/Bi/MetricExecutor.php`
- Create: `src/Services/Bi/MetricResult.php`
- Test: `tests/unit/MetricExecutorTest.php` (`#[Group('puro')]`, con PDO en memoria no hay: usar grupo `db`)

**Interfaces:**
- Consumes: `MetricDictionaryService::getDefinition()`.
- Produces: `MetricExecutor::execute(string $metricKey, MetricScope $scope): MetricResult` donde
  `MetricResult` expone `value()`, `basis(): array{obras_incluidas:int, obras_esperadas:int, corte:string, filas_usadas:int}`,
  `completeness(): string` (`completa|parcial|insuficiente`) y `missing(): array`. Las tareas 3, 5 y 7
  dependen de estas firmas exactas.

- [ ] **Paso 1 (rol A): la prueba del contrato** — dado un catálogo con `execution_source`,
  `filters`, `aggregation_policy`, el ejecutor arma la consulta con sentencias preparadas, aísla por
  `project_id`, y devuelve `MetricResult` con `basis` siempre poblado. Caso obligatorio: una métrica
  cuyo denominador es cero devuelve `insuficiente`, nunca división por cero ni `null` mudo.
- [ ] **Paso 2: FAIL** (la clase no existe).
- [ ] **Paso 3 (rol B): implementación mínima** — un solo camino de construcción de SQL: `SELECT
  {select} FROM {source} WHERE project_id IN (?) AND {filters}` con los filtros del catálogo como
  pares columna/operador/valor. **Regla dura de CT-6:** si una métrica se puede calcular por dos
  caminos, la spec está mal — el ejecutor no acepta SQL libre, solo declaraciones.
- [ ] **Paso 4: verde.** `vendor/bin/phpstan analyse src --memory-limit=1G` también en RC=0.
- [ ] **Paso 5: commit.**

---

### Task 3: Paridad métrica por métrica — el patrón, con la primera completa

**Files:**
- Create: `tests/test_bi_paridad_metricas.php` (`// @requiere: db`)
- Modify: `src/Services/Bi/MetricDictionaryService.php` (campo `estado_ejecucion`)

**Interfaces:**
- Consumes: `MetricExecutor::execute()` de Task 2 y el SQL viejo de `ControlTowerService`.
- Produces: el trinquete de paridad; cada métrica con `estado_ejecucion: descriptiva|en_paridad|ejecutable`.

- [ ] **Paso 1: declarar los tres estados en el catálogo** — todas nacen `descriptiva`.
- [ ] **Paso 2 (rol A): el test de paridad como trinquete** — para cada métrica `en_paridad`, correr
  los dos caminos sobre **cuatro semanas reales de al menos dos obras** (Da Porto 73 y una más,
  contra la base de dev sembrada) y fallar ante cualquier discrepancia, imprimiendo ambos valores y
  la semana. Para las `ejecutable`, verificar que el SQL viejo **ya no existe** (grep del método en
  `ControlTowerService`).
- [ ] **Paso 3: pasar `ps_weekly_fulfillment` a `en_paridad`** — es la más simple (`SUM(PAC=1)/COUNT(*)`)
  y su fuente (`bi_ps_compromisos`) está sana (92,3% de captura). Correr el test: si discrepa, **la
  discrepancia se documenta y se decide cuál es la correcta ANTES de migrar** — es una ganancia, no
  un problema (CT-16).
- [ ] **Paso 4: con paridad verde en 4 semanas × 2 obras, pasarla a `ejecutable`** y borrar su SQL
  de `ControlTowerService`. Re-correr todo el nivel `db`.
- [ ] **Paso 5: repetir el patrón para las 18 restantes en tandas de 3–5 por commit**, en este orden
  de riesgo creciente: las de `bi_pg_semana` y `programacion_semanal` (simples), luego radares,
  luego `pg_finish_variance_days_p50` (Monte Carlo — la paridad admite tolerancia declarada por la
  semilla, documentada en el propio test), y de última `cic_cal_integral` con su herencia. **Cada
  tanda es un commit; una métrica que no logra paridad se queda `en_paridad` con su discrepancia
  documentada y NO bloquea a las demás.**
- [ ] **Paso 6: commit final de la task** con el conteo: cuántas `ejecutable`, cuántas `en_paridad`
  con causa, cuántas `descriptiva`.

---

### Task 4: La migración de restricciones — tarea propia, con gate

> **FRENO. Esta tarea no arranca sin la autorización explícita de Felipe en el chat, aunque el resto
> del plan esté aprobado.** Y durante su ventana, la base de dev se congela para terceros.

**Files:**
- Create: `database/migrations/20260827_pi_shared_constraints_gestion.sql`
- Create: `scripts/dry-run-constraints-gestion.php`
- Test: `tests/test_constraints_gestion_schema.php` (`// @requiere: db`)

**Interfaces:**
- Produces: columnas `ResponsableAsignado` (varchar nulo), `FechaCompromiso` (date nulo),
  `EstadoLiberacion` (enum `sin_gestionar|en_gestion|liberada|no_aplica`), `AsignadoPor`, `AsignadoEn`
  en `pi_shared_constraints` — las firmas exactas de CT-7.3 que las tareas 5 y 7 consumen.

- [ ] **Paso 1: respaldo verificable** — dump de `pi_shared_constraints` y `pi_shared_constraint_links`,
  **restaurado en una base de prueba y contado** (filas antes = filas después). Un respaldo no
  probado no es respaldo.
- [ ] **Paso 2: dry-run** — el script imprime, sin escribir: cuántas filas recibirían cada
  `EstadoLiberacion` según el relleno de compatibilidad (`0 → sin_gestionar`, intermedio →
  `en_gestion`, `>=1.0 → liberada`), y el total reconstruido **comparado contra el que hoy muestra
  Power BI**. Si no coinciden, la migración NO se aplica y la discrepancia sube a Felipe.
- [ ] **Paso 3: autorización explícita de Felipe sobre la salida del dry-run.** Una decisión consumida, bien gastada.
- [ ] **Paso 4: aplicar, re-contar, y correr el test de esquema** (columnas existen, enum exacto,
  nulos permitidos donde CT-7.3 los declara).
- [ ] **Paso 5: la reversa, escrita y probada en la base de prueba** — `ALTER TABLE ... DROP COLUMN`
  de las cinco, verificando que el valor numérico original quedó intacto (la migración no lo tocó).
- [ ] **Paso 6: commit** con la salida real del dry-run y el conteo en el cuerpo.

---

### Task 5: El endpoint de escritura — la Torre escribe

**Files:**
- Create: `src/Controllers/Api/BiConstraintWriteController.php`
- Modify: `public/index.php` (una ruta POST)
- Modify: `src/Security/RbacCatalog.php` + `src/Security/RbacManager.php` (capacidad, por el catálogo — nunca inventada aparte)
- Test: `tests/test_bi_constraint_write.php` (`// @requiere: http`)

**Interfaces:**
- Consumes: columnas de Task 4.
- Produces: `POST /api/bi/control-tower/restricciones/{id}/gestion` con cuerpo
  `{responsable: string, fechaCompromiso: "YYYY-MM-DD", estado: "en_gestion"|...}` y CSRF. Respuesta
  `{ok: true, restriccion: {...}}`. La Task 7 lo consume tal cual.

- [ ] **Paso 1 (rol A): la prueba HTTP completa antes del código** — por la puerta de servicio:
  `test.A` escribe y recibe 200 con la fila actualizada (incluidos `AsignadoPor`/`AsignadoEn`);
  `test.V` recibe **403**; sin token CSRF, **403**; con `project_id` ajeno en el id, **404** (el
  aislamiento no revela existencia). Escribir → recargar → recuperar: el estado persiste.
- [ ] **Paso 2: FAIL completo** (ruta no existe).
- [ ] **Paso 3 (rol B): implementar** — capacidad resuelta con `RbacManager` +
  `RbacService::normalizeRole()`, mutación con sentencias preparadas vía `Database`, aislada por
  `project_id` de la sesión. Nada más se puede escribir: responsable, fecha, estado — CT-10 es
  taxativo.
- [ ] **Paso 4: verde en las cinco aserciones**, `phpstan` RC=0, y **anotar en la bitácora del plan
  el par rol permitido/denegado con su salida** — es exigencia del routing de RBAC de `AGENTS.md`.
- [ ] **Paso 5: commit.**

---

### Task 6: `ct-app/` — el andamiaje React de la Torre, con los tokens dentro

**Files:**
- Create: `ct-app/` (espejo de `pdc-app/`: `package.json`, `vite.config.ts`, `tsconfig.json`, `index.html`, `src/`)
- Create: `ct-app/src/lib/api.ts`, `ct-app/src/lib/tokens.css` (importa, no copia)
- Modify: `src/Controllers/Bi/` (la vista de Intermedia nueva se sirve tras bandera `CT_PILOTO=1` en `.env` — la hoja vieja sigue siendo la default hasta que D55 se cumpla)
- Test: `ct-app/src/lib/api.test.ts` (Vitest)

**Interfaces:**
- Produces: build a `public/ct-app/` (mismo patrón de `pdc-app`: `vite build` y sin `index.html`
  suelto), cliente `api.ts` tipado contra el endpoint de Task 5 y el GET existente.

- [ ] **Paso 1: replicar el esqueleto de `pdc-app/`** — mismo `vite.config.ts` adaptado (`outDir: '../public/ct-app'`), mismas versiones de dependencias (leerlas de `pdc-app/package.json`, no elegir nuevas).
- [ ] **Paso 2: los tokens del design system entran por `@import` del CSS compartido** — cero
  copias, cero hex. Condición 1 de N11. Verificable: `grep -rn "#[0-9a-fA-F]\{3,6\}" ct-app/src --include=*.css --include=*.tsx | grep -v tokens` debe dar cero.
- [ ] **Paso 3 (rol A): test del cliente API** — `api.ts` tipa `MetricResult` (espejo del PHP:
  `value`, `basis`, `completeness`, `missing`) y el POST de gestión; un mock verifica que el CSRF
  viaja y que un 403 se propaga como error tipado, no como catch mudo.
- [ ] **Paso 4 (rol B): implementar `api.ts`**, verde en Vitest.
- [ ] **Paso 5: la bandera** — con `CT_PILOTO=1`, `/bi/control-tower` sirve el bundle nuevo para la
  hoja de Intermedia y las demás hojas siguen en `bi-spa.js`. Sin bandera, todo como hoy. (D55:
  primero se reconstruye, solo después se retira.)
- [ ] **Paso 6: commit.**

---

### Task 7: La hoja de Intermedia — el piloto, completo

**Files:**
- Create: `ct-app/src/pages/Intermedia.tsx` + componentes (`ListaRestricciones.tsx`, `AlarmaHuerfanas.tsx`, `Titular.tsx`, `PanelGestion.tsx`, `Linaje.tsx`)
- Create: `ct-app/src/lib/titulares.ts` (las plantillas N1) + `titulares.test.ts`
- Create: `ct-app/src/lib/urgencia.ts` (el orden N4) + `urgencia.test.ts`
- Test: `tests/browser/ct-intermedia.spec.mjs` (Playwright, sandbox — no Da Porto: los e2e sobre la obra real alternan con el dato, hallazgo H1)

**Interfaces:**
- Consumes: `api.ts` de Task 6, endpoint de Task 5, columnas de Task 4.
- Produces: la pantalla del criterio de corte de la v0.

El lienzo, en el orden que la Parte II fijó (lista arriba — resuelto en CT-18.3): **alarma de
huérfanas · lista accionable · titular · semáforo · pareto**, con:

- [ ] **Paso 1 (rol A): tests de las dos librerías puras primero.** `urgencia.test.ts`: el orden N4
  (semana de inicio de la actividad bloqueada; desempate por encadenamiento y ruta crítica) con
  casos borde — sin fecha = alarma arriba del todo, como las huérfanas del PDC. `titulares.test.ts`:
  cada plantilla N1 con su condición disparadora y sus huecos llenos; **una condición sin plantilla
  produce el titular neutro declarado, jamás cadena vacía**.
- [ ] **Paso 2: FAIL → (rol B) implementar ambas → verde.**
- [ ] **Paso 3: la lista y el panel de gestión** — asignar responsable y fecha **sin salir de la
  hoja** (D33): el panel abre sobre la fila, escribe por `api.ts`, y la fila refleja el nuevo estado
  sin recargar. Los tres estados se ven (D87); cada alerta trae su acción sugerida y a quién acudir
  (D89, con `ActionRecommendationService` detrás del GET).
- [ ] **Paso 3-bis: las métricas nuevas del lienzo se declaran antes de pintarse** — el semáforo
  por semanas (0–6) y las demás de D58 que la hoja use entran al catálogo con su contrato completo
  (fuente, filtros, grano, completitud) y su prueba de paridad si reconstruyen una cifra de Power
  BI. **Ninguna cifra se pinta sin declaración** — es la regla que la Torre existe para instaurar
  (CT-20.3).
- [ ] **Paso 4: el linaje pintado** (CT-6.3) — el control «de dónde sale esto» en cada cifra,
  alcanzable por teclado (`tabindex`, `aria-expanded`), abriendo definición, fórmula en lenguaje de
  negocio, fuente, corte, versión y el `basis` del cálculo concreto. **Nada valioso en hover.**
- [ ] **Paso 5: las dos lecturas del cero, separadas y rotuladas** (D59) — adherencia como cifra
  dura; la señal predictiva marcada «estimación», nunca «estas van a fallar».
- [ ] **Paso 6: e2e en el sandbox** — sembrar restricciones de prueba, recorrer: la alarma lista las
  huérfanas → asignar responsable y fecha → la fila cambia a `en_gestion` → recargar → persiste →
  el conteo de huérfanas bajó. Un rol denegado ve la hoja sin el panel de gestión.
- [ ] **Paso 7: commit por paso mayor** (librerías, lista+panel, linaje, e2e).

---

### Task 8: El tema claro nace aquí — y el móvil, en la misma pasada

**Files:**
- Create: `public/css/design-system/theme-claro.css` (tokens del tema claro, solo los que la hoja usa)
- Modify: `ct-app/src/` (conmutador respetando `prefers-color-scheme` + toggle persistido)
- Test: `tests/design-system/theme-claro-tokens.test.mjs` + escenarios móvil en el manifiesto

**Interfaces:**
- Consumes: la hoja de Task 7.
- Produces: los tokens claros que las demás pantallas heredarán — **tras el visto visual de Felipe**.

- [ ] **Paso 1: derivar los tokens claros del manual AIA v1.0** (skill `aia-brand-system`), solo el
  conjunto que la hoja consume — no la paleta entera especulativa (YAGNI). Cada par fondo/texto con
  su contraste WCAG medido en el propio test.
- [ ] **Paso 2: el móvil a 390×844** — decisión 9: misma decisión, otra forma. La lista pasa a
  tarjetas; el panel de gestión ocupa la pantalla; ver, asignar y confirmar funcionan con el pulgar.
  La tabla densa queda declarada de escritorio en el manifiesto.
- [ ] **Paso 3: el manifiesto de la hoja** declara `layouts: ["desktop", "mobile"]` y sus escenarios
  en ambos temas; el gate de cobertura y `censo-fichas-coherencia` en verde.
- [ ] **Paso 4 — FRENO de diseño: capturas de la hoja en claro y oscuro, escritorio y móvil, a
  Felipe.** El tema claro **no se propaga a ninguna otra pantalla sin su visto** (decisión 10). Una
  decisión consumida, presupuestada.
- [ ] **Paso 5: `impeccable:audit` sobre la pantalla cerrada** (regla de cierre de pantalla del
  proceso), arreglos que salgan, commit.

---

### Task 9: Cierre del piloto — el número, los gates y el PR

**Files:**
- Modify: este plan (sección `## Cierre` + `## Bitácora del piloto`)
- Modify: `docs/superpowers/specs/2026-08-26-v0-del-producto-design.md` (condición 7: el cierre actualiza la spec)
- Create: `.github/pull_request_template.md` (el checklist que fuerza la condición 7 — decisión técnica ya anotada en la spec)

- [ ] **Paso 1: suites completas** — `run-php-tests.php` niveles `puro`, `db` y `http`; Vitest de
  `ct-app`; `test:design-system:static`; Playwright del piloto. Cada RC en su línea. Rojos
  preexistentes citados con su ficha, no adoptados.
- [ ] **Paso 2: el número** — cerrar la bitácora: paradas totales con su causa en una línea cada
  una, y decisiones de Felipe consumidas. **Escribirlo en la spec (condición 6) y dimensionar con él
  las 7 hojas restantes y la re-priorización de F3–F5** — esa re-priorización es la primera entrada
  del plan de la etapa siguiente, no de esta.
- [ ] **Paso 3: el template de PR** con las tres casillas: verificación citada · spec actualizada ·
  bitácora al día.
- [ ] **Paso 4: `git push -u origin ola1-torre-piloto` + `gh pr create`** con el resumen de
  verificación. **CI en verde es el gate; un PR rojo se arregla o se cierra, nunca se fuerza.**
  Merge → confirmación en `main`.

---

## Bitácora del piloto

*(se llena durante la ejecución — es el entregable de la decisión 5/N y la condición 6 de la spec)*

| # | Tipo | Qué pasó | Costo |
|---|---|---|---|
| 1 | Parada | Task 2: el catálogo real no trae `select` como campo propio, y `filters` son strings `'Columna=Valor'`, no pares estructurados — CT-6 describe la forma destino, no la forma actual. Ruling: `MetricExecutor` parsea `filters[]` partiendo cada string por el primer operador (`=`,`>=`,`<=`,`!=`,`>`,`<`) y usa `formula` como fuente de la expresión `select` cuando es SQL-válida; una métrica cuya `formula` no sea parseable se documenta al migrarla en Task 3 (que ya es incremental por diseño) en vez de bloquear el ejecutor. | Si la regla de parseo falla en un caso real, el costo es ajustar el parser en Task 3 al toparlo — no hay dato ni escritura de por medio |
| 2 | Parada | Task 2: el test del rol A (`MetricExecutorTest.php`) usaba una `const` `private` de la clase de test, leída desde una clase anónima interna — PHP no concede ese acceso aunque estén anidadas léxicamente (confirmado con repro mínimo en `/tmp` del contenedor). El rol B verificó su implementación correcta contra las 3 aserciones antes de reportar BLOCKED, sin tocar el test. Ruling: se resuelve como corrección de test (rol A la aplica, no rompe separación de roles), no como defecto de `MetricExecutor`. | Ninguno — el bug era de alcance de PHP en el arnés de prueba, no de lógica de negocio ni de dato |
| 3 | Ganancia (CT-16) | Task 3, dry-run temporal del rol A antes del paso 3: el catálogo declara el filtro de `ps_weekly_fulfillment` como `Activa=Si` / `Es_TNP=0`, pero la columna real en `bi_ps_compromisos` es `is_TNP` (no `Es_TNP`) y los valores reales son `'1'/'0'/'NA'`, nunca la cadena `'Si'`. El filtro nunca calzaba. Ruling: se corrige como parte del propio paso 3 de la tarea — es exactamente la discrepancia que CT-16 pide documentar y decidir antes de migrar, no un defecto de `MetricExecutor` de Task 2. | Si la corrección del filtro está mal, el costo es que `ps_weekly_fulfillment` se queda `en_paridad` con la discrepancia documentada — no bloquea nada, no toca datos |
| 4 | Parada | Task 3 paso 3 (rol B), con el filtro de la entrada 3 ya corregido: el trinquete SÍ corrió sobre las 4 semanas × 2 obras y encontró una discrepancia que no es de catálogo — `MetricExecutor::execute()` nunca acota por `Semana`. `MetricScope` solo lleva `project_id`(s) y un `startDate`/`endDate` opcional que `MetricExecutor::buildWhereClause()` nunca lee; no hay ningún parámetro de "semana" en toda la cadena. El "camino nuevo" agrega entonces TODAS las semanas históricas del proyecto (678 filas para la obra 65) en vez de solo la semana pedida (24-80 filas por semana), dando el mismo valor sin importar qué semana se compare — confirmado reproduciendo la agregación a mano contra `bi_ps_compromisos` (evidencia completa en `task-3-report.md`). Es un vacío de alcance de `MetricExecutor`/`MetricScope` (Task 2), fuera de lo que el catálogo o el arnés de prueba de Task 3 pueden resolver. `ps_weekly_fulfillment` se queda `descriptiva` (no `en_paridad`, no `ejecutable`) con el hallazgo documentado en su `known_limitations`; no se fuerza paridad. Ruling del controlador: se corrige como fix acotado sobre los archivos de Task 2 (ya cerrada y revisada) antes de reabrir el Paso 3 — `MetricScope` gana un campo de semana/corte, y `MetricExecutor::buildWhereClause()` lo aplica cuando la métrica declara `cutoff_policy` de tipo "semana seleccionada". Va con su propia pareja de roles TDD, acotada a esa sola brecha, no una reapertura general de Task 2. No sube a Felipe: es reversible, no toca dato ni escritura, y el propio arnés de paridad de Task 3 ya demostró que atrapa el error si el fix queda mal. | Ninguno sobre datos — el filtro corregido de la entrada 3 se conserva documentado, aunque sin efecto mientras la métrica siga `descriptiva`. El costo es que Task 3 no cierra su "primera métrica completa" hasta resolver el alcance de `MetricExecutor`, y Paso 4 (borrar el SQL viejo) tampoco corrió: `ControlTowerService::scorecardPS()` sigue siendo la única fuente de otros 3 KPIs en vivo (`Compromisos activos`, `En riesgo`, `CNC esta semana`) que ni siquiera tienen entrada en el catálogo — borrarlo entero habría roto el reporte `semanal` real (`BiControlTowerApiController`), así que no se tocó `ControlTowerService.php` |
| 5 | Parada | Task 3 paso 3 (rol B), retomando tras el fix de la entrada 4 (`MetricScope::week()`, commit def23b0b): con el arnés construyendo el scope por semana, el trinquete corrió de verdad y encontró dos cosas distintas. (a) Los 4 pares de la obra 65 discrepaban por un patrón consistente: `scorecardPS()` publica `round(ratio*100)` (entero) antes de mostrarlo; se declaró una tolerancia de 0.005 en el arnés — la cota matemática del propio redondeo, no un número para forzar el verde — y calzaron los 4. (b) El resolver del camino viejo leía el KPI `'PAC'` ya coalescido por `scorecardPS()` (`$r['PAC'] ?? 0`), que publica "0%" cuando ningún compromiso activo tiene PAC registrado; la consulta cruda con la misma población de `fetchSemanal()` da `SUM(PAC=1) = NULL` en ese caso — el 0% es un default de presentación, no lo que "el SQL viejo" calcula. Se corrigió el resolver (no `ControlTowerService.php`) para reproducir esa guarda. Con eso, obra 73 semana 1 y los 4 pares de la obra 65 pasan limpio (5/6). El sexto (obra 73, semana 2) sigue sin poder verificarse: esa semana empezó el 2026-08-25 (`semanas_activas`: `Semanal_Confirmada=0`, `fechaCierreCompromisos=NULL`, hoy es 2026-08-26) y no tiene ningún PAC registrado — los dos caminos coinciden en que el valor es indefinido (null), pero el arnés de rol A trata "no se puede comparar" como fallo siempre, incluso cuando ambos lados concuerdan en null (diseño deliberado: preferir fallo ruidoso a un acuerdo por casualidad). No se tocó esa lógica del arnés — está fuera de lo que el rol B puede decidir sin permiso. `ps_weekly_fulfillment` se queda `descriptiva` una tercera vez; no se fuerza el sexto par. Ruling del controlador: (ii) — cuando ambos caminos coincidan en "sin dato" (null=null) para un par obra/semana, el arnés lo cuenta como paridad, no como fallo, y lo anota aparte como "sin datos, ambos caminos concuerdan". Sigue fallando fuerte el caso asimétrico (un lado null, el otro con valor), que es donde de verdad hay riesgo de discrepancia oculta. Esperar a que la semana 2 de la obra 73 cierre ataría la tarea a un reloj externo sin bajar ningún riesgo real — CT-6.2 ya modela "sin dato suficiente" como estado propio (`insuficiente`), y que los dos caminos coincidan en eso es la forma más fuerte de acuerdo, no una comparación pendiente. | Ninguno sobre datos — el fix de alcance de la entrada 4 y la tolerancia declarada aquí quedan documentados y en verde para 5/6 combinaciones reales, listos para cuando el sexto par se resuelva. El costo es el mismo de la entrada 4: Task 3 sigue sin cerrar su "primera métrica completa", y Paso 4 (borrar SQL viejo) sigue sin correr por la misma razón ya documentada (`scorecardPS()` alimenta 3 KPIs en vivo sin cobertura en el catálogo) |
| 6 | Parada | Task 3 paso 5, tanda 2: de 6 candidatas "simples" (`bi_pg_semana`/`programacion_semanal`), solo `pg_radar_desempeno` migró — las otras 5 (`pg_activities_to_do`, `pg_cnp_activity_count`, `pg_cnc_activity_count`, `pg_radar_eficiencia`, `pg_radar_productividad`) chocan con un vacío de **modelo de ejecución** en `MetricExecutor`: solo sabe `SUM(expr_simple)/COUNT(*)`, nunca conteo puro (sin denominador), nunca razón promediada por fila (`AVG(A/B)` ≠ `SUM(A)/SUM(B)`, confirmado con datos reales que `pg_radar_eficiencia` es lo primero), nunca valor capado antes de agregar (`pg_radar_productividad` usa `MIN(P_Completado,1)`, con delta real de 0,03-0,08 en obra 65 — no ruido de redondeo, no hay tolerancia razonable que lo tape). Investigado con evidencia, no forzado (CT-16). Ruling del controlador (alcance, no fix acotado): extender `MetricExecutor` con modos de agregación (conteo puro, razón-por-fila, valor capado) es arquitectura real — no algo del tamaño del fix de `MetricScope::week()` — y **no lo pide la condición de hecho de esta etapa**, que exige "al menos `ps_weekly_fulfillment` y las métricas que la hoja [de Intermedia] consume" (punto 3), no las 19. Contra CT-20.2: del catálogo existente, Intermedia (8.3) solo necesita `pi_hard_restrictions_ready_rate` (ya ejecutable) y `pi_restriction_pareto`; las 15 métricas restantes pertenecen a hojas de otras olas (8.2, 8.4-8.8), ya declaradas fuera de esta etapa en el propio preámbulo del plan ("F3... se planean en la etapa siguiente, re-priorizadas con el número del piloto en la mano"). Task 3 cierra su paso 5 verificando si `pi_restriction_pareto` es migrable con el motor actual (siguiente parada/entrada); las 15 restantes quedan `descriptiva` con el hallazgo documentado — insumo directo para la re-priorización de F3-F5 en la Task 9. No sube a Felipe: es una lectura literal de la condición de hecho ya escrita, no una rebaja de alcance. | Ninguno sobre datos. El costo es que F1 (CT-13, "las 19 métricas ejecutable") no cierra completo en esta etapa — exactamente lo que el riesgo declarado en la spec (CT y el preámbulo del plan) ya anticipa y protege. Si la lectura de la condición de hecho está mal, el remedio es retomar el paso 5 con las 15 métricas restantes en la etapa siguiente, con el motor ya extendido |
| 7 | Ruling | Revisión completa de Task 3 (agente revisor, modelo opus): señaló que ninguna de las 3 métricas `ejecutable` (`ps_weekly_fulfillment`, `pi_hard_restrictions_ready_rate`, `pg_radar_desempeno`) borró su SQL viejo — las 3 quedaron en `$oldMethodRetainedByMetric` con motivo documentado (el método viejo sigue alimentando otros KPIs en vivo sin cobertura de catálogo, per entradas 4-5), y esa decisión reinterpreta la condición de hecho 3 de esta etapa ("ninguna métrica quedó con dos caminos de cálculo vivos") sin haber quedado registrada como ruling explícito — vivía repartida en reportes de agentes y comentarios de código. Ruling del controlador, ahora explícito: la condición de hecho 3 se lee por **métrica**, no por **método** — para cada una de las 3, el catálogo/`MetricExecutor` es la única fuente de la CIFRA declarada (el "camino de cálculo" de esa métrica), y lo que se retiene es un método que además calcula otras cifras sin entrada en el catálogo (2-3 KPIs cada vez). Borrar el método completo para cumplir la letra habría roto esos KPIs en producción, que es peor que la letra incumplida. La condición 3 se da por satisfecha en el sentido de "ninguna cifra catalogada se calcula por dos caminos", no en el sentido literal de "ningún método viejo sigue vivo". **Task 9 debe re-verificar esta lectura explícitamente al cerrar el número del piloto** — si Felipe la lee distinto, el costo es revisar las 3 migraciones para separar el cálculo de la cifra catalogada del resto del método (partirlos), no rehacer la paridad. | Ninguno sobre datos ni sobre lo ya publicado — es una aclaración retroactiva de una decisión ya tomada y ya verificada por paridad. El costo si la lectura está mal es de alcance (partir 3 métodos en Task 9 o la etapa siguiente), no de corrección: las 3 cifras siguen siendo las que `MetricExecutor` calcula, correctas por paridad probada |
| 8 | Parada | Task 4, gate 1 (respaldo + dry-run, autorizado por Felipe): `ValorObjetivo` en `pi_shared_constraints` es `varchar(20)`, no numérico como asumía el brief — 191 filas: `'1'`=131, `'0'`=34, `'N/A'`=21 (11%), `'0.66'`=5. La regla de compatibilidad de CT-7.3 (prosa) solo describe 3 casos numéricos; el enum declarado tiene un cuarto valor (`no_aplica`) sin instrucción de cuándo usarlo. Y la reconciliación contra "lo que hoy muestra Power BI" que pide el paso 2 **no tiene proxy posible**: las métricas de restricciones ejecutables hoy (`pi_hard_restrictions_ready_rate`, `pi_restriction_pareto`) filtran `is_hard=1` y excluyen por diseño esta tabla entera; la única vista que sí la incluye (`bi_pi_restricciones`, rama `is_hard=0`) mide algo distinto a otro grano (4005 links contra 191 filas; "¿lo aplicado alcanzó la meta?" contra "¿qué declara la meta?") y da 100% listo — artefacto del cast de MySQL de `'N/A'`/`'0'` a decimal, no dato real. Presentado a Felipe en panel de decisión (gate 2), con recomendación de aplicar de todas formas: la migración es mecánica y reversible (clasifica un valor de texto ya existente, no calcula nada), el respaldo está probado por checksum, y el riesgo que la reconciliación existe para atrapar —cifra inventada— no aplica porque no se publica ningún número nuevo. **Decisión consumida — respuesta de Felipe en el chat: «Sí a ambas — aplicar con N/A→no_aplica, y sin reconciliación contra Power BI, dejando la razón documentada en el commit».** | Ninguno sobre datos — dry-run de solo lectura, sesión `TRANSACTION READ ONLY`. Con la autorización, Task 4 sigue a pasos 4-6 (aplicar, reversa probada, commit) |
| 9 | Parada | Task 6, paso 2: el `@import url('/css/tokens.css')` de `ct-app/src/lib/tokens.css` — la forma que el brief describe para que los tokens entren "cero copias, cero hex" — rompía `vite build` en seco (postcss-import resuelve una ruta absoluta como filesystem, `ENOENT: /css/tokens.css`), no solo en un caso borde. Ruling: mismo patrón ya usado por `pdc-app` (que el propio Task 6 dice espejear) — el shell PHP enlaza `/css/tokens.css` con `<link>` antes que el bundle, y `ct-app` no reimporta nada; cero copias se mantiene, solo cambia el mecanismo de entrega (link del shell, no @import del bundle). No sube a Felipe: el brief describe la intención (tokens reales, no copiados), no el mecanismo CSS exacto, y pdc-app ya prueba que el patrón funciona en producción. | Ninguno sobre datos. El costo si el ruling está mal es volver a intentar el @import con otra configuración de Vite (`css.postcss` con filtro), no rehacer el andamiaje |
| 10 | Ruling | Revisión completa de Task 6 (agente revisor, modelo opus): la bandera `CT_PILOTO` sirve el bundle nuevo en carga directa de `/bi/intermedia`, pero la navegación SPA de `bi-spa.js` (`switchView()`) nunca dispara una carga de página — solo alterna visibilidad y pide `/api/bi/report/intermedia` — así que alguien que hace clic en la pestaña Intermedia desde `/bi/control-tower` sigue viendo la hoja vieja aunque `CT_PILOTO=1`. El paso 5 del brief ("con CT_PILOTO=1, /bi/control-tower sirve el bundle nuevo para la hoja de Intermedia") queda cumplido solo para carga directa por URL, no desde la navegación interna. Ruling del controlador: se documenta como límite conocido, no se arregla en Task 6 — integrar la bandera con la navegación SPA toca `bi-spa.js`, compartido por las 8 hojas, y es exactamente el tipo de trabajo que corresponde a Task 7 cuando construya la pantalla real y decida cómo se llega a ella. Task 7 debe resolver esto como parte de dejar la hoja usable, no asumir que ya funciona. Además: el CSRF de la Torre usa la clave `'ct_piloto'` (`CsrfTokenManager::generate('ct_piloto')`) — Task 5 debe validar contra esa misma clave, no una genérica. | Ninguno sobre datos. El costo si el ruling está mal es que Task 7 encuentra el límite de todas formas al construir la pantalla — no se pierde nada por no anticiparlo en Task 6 |
| 11 | Parada | Task 6, ronda de fix: el par rol permitido/rol denegado con `CT_PILOTO=1` (exigido por el routing de RBAC de AGENTS.md) se verificó por reflexión y consulta directa a `BiPreviewAccessPolicy::canOpen()` con los roles reales de `test.A`/`test.V` desde la base de datos, en un contenedor efímero — no por una petición HTTP completa contra Apache. Editar `.env` para prender la bandera fue denegado dos veces por el sistema de permisos de la sesión, y recrear el contenedor compartido para probarla exige ventana coordinada (`docs/coordinacion-sesiones.md` regla 4) que esta sesión no tiene ni amerita pedir para una bandera que queda apagada. El gate de acceso (`assertBiPreviewAccessible()`) es el MISMO código ya probado en vivo por las otras 7 hojas del BI — no es lógica nueva sin probar, solo el enrutamiento hacia ella es nuevo. Ruling del controlador: la verificación queda aceptada en este nivel — la prueba HTTP de extremo a extremo con la bandera prendida de verdad queda pendiente para cuando Task 7 la active para construir/probar la pantalla real, momento en que sí se justifica coordinar la ventana. No se finge la prueba completa; se documenta el límite. | Ninguno sobre datos ni producción — `CT_PILOTO` sigue ausente de `.env`, confirmado antes/después. El costo si el ruling está mal es descubrir un problema de acceso al activar la bandera en Task 7, en vez de ahora — mismo momento en que de todas formas hay que probarla en vivo |
| 12 | Ruling | Task 5, revisión completa (opus): encontró un bypass de RBAC real, confirmado con un usuario real de la base de dev — `resolveRole()` resolvía el rol vía `RbacService::resolveRoleForUser($username)` sin acotar por proyecto, que por diseño devuelve el rol MÁS PRIVILEGIADO del usuario en CUALQUIER proyecto (`ORDER BY FIELD(pm.role,'A','D','R',...)`), no su rol en el proyecto de la sesión activa. Un usuario con rol `V` en el proyecto donde intenta escribir pero `A` en otro proyecto habría pasado la compuerta. El test original no lo detectaba porque `test.V` es `V` en los 4 proyectos donde es miembro — el par permitido/denegado se cumplía en la letra sin probar la propiedad real. Etiquetado `plan-mandated` porque mi ruling previo (dispatch de rol B) había nombrado `resolveRoleForUser()` para evitar el gate equivocado (`BiPreviewAccessPolicy::canOpen()`, que da 404 en vez de 403 a V) — pero ese ruling nunca pedía omitir el proyecto. Ruling del controlador: el fix (usar `RbacService::resolveCurrentRole()`, acotado por `$_SESSION['proyecto']`/`$_SESSION['db']` — la convención real de escritura del repo, ya usada por `CicApiController`, `SemanalApiController`, `GeneralApiController`, `LpsWeekEditPolicy`, `CommitmentLockGuard`) no contradice el ruling previo, es un fix de seguridad legítimo. La misma revisión encontró dos hallazgos Important adicionales, ambos corregidos en la misma ronda: el test declaraba nivel `http` (rompía CI porque las columnas de Task 4 no están en el fixture de CI) — re-etiquetado a `datos-proyecto`; y los envelopes de error 403/404 usaban un formato distinto al 422, incompatible con `ct-app/src/lib/api.ts` ya commiteado — unificados los 4 sitios de error. Re-revisión acotada: los 3 hallazgos ADDRESSED, sin rotura nueva. **Par rol permitido/denegado, salida real (post-fix, `tests/test_bi_constraint_write.php`):** `test.A` (permitido) → `HTTP 200`, `{"ok":true,"restriccion":{...,"AsignadoPor":"test.A","AsignadoEn":"2026-08-26 17:05:07",...}}`; `test.V` (denegado) → `HTTP 403`, `{"ok":false,"error":{"code":"FORBIDDEN","message":"Tu rol no tiene permiso para gestionar restricciones."}}`. No sube a Felipe: es un bug de seguridad real ya corregido y re-verificado, dentro del umbral técnico normal. | Ninguno sobre datos — el bug nunca llegó a producción (la rama no está mergeada); el costo si el fix está mal es que la re-revisión ya lo habría detectado, y el propio Caso 6 del test (a nivel `RbacService`, documentado su límite: no hay forma legítima de autenticar por la puerta de dev al usuario que expone el bug) queda como regresión permanente contra ese patrón específico |
| 13 | Parada | Task 7, previo al paso 3-bis: D58 pide «cuatro métricas nuevas al catálogo» para el semáforo por semanas del lienzo de Intermedia (posición 4), pero ninguna spec escrita (ni esta ni la de origen, `2026-08-20-replanteo-control-tower-design.md`) detalla qué mide cada una — solo dicen «semáforo por semanas para iniciar (0 a 6), reconstruido desde Power BI (D55, D58)». Investigado en código (`ControlTowerService.php`, `bi-spa.js`) y en `memoria/`/`goals/`: sin rastro de una fórmula ya implementada. Es decisión de producto (qué comunica el semáforo a los directores de obra), no técnica — supera el umbral. **Decisión consumida — respuesta de Felipe en el chat: cuatro franjas por urgencia según `Semanas_Inicio`** (semana 0 = listas ya, 1-2, 3-4, 5-6), cada una contando actividades del lookahead en esa franja, coloreadas verde a rojo por cercanía. Reutiliza `Semanas_Inicio` (ya disponible en `bi_pi_restricciones`/`programa_consolidado`), sin dato nuevo que capturar. | Ninguno sobre datos — decisión de forma sobre un dato ya existente. El costo si la lectura de las franjas está mal (límites distintos, agrupación distinta) es redefinir 4 filtros de catálogo, no rehacer el modelo de datos |
| 14 | Parada + Decisión | Task 7 paso 6 (e2e): el rol A del e2e destapó dos brechas al escribir la prueba completa. **(a) `CT_PILOTO` sigue bloqueada** — confirmado exhaustivamente que no hay vía de activarla sin editar `.env` compartido o recrear el contenedor (`docker compose exec -e` solo afecta al proceso exec'd, no a Apache corriendo; `docker-compose.yml` no la inyecta), tal como anticiparon las entradas 10/11. **(b) Hallazgo de RBAC real**: `PERM_INTERNAL_BI_PREVIEW` (quién abre el módulo BI: A/D/R) es subconjunto estricto de `canEditConstraints` (quién gestiona restricciones: A/D/R/DCV/S/G/SG/OT) — verificado en vivo con `test.V` (404 antes de llegar a la hoja). Ningún rol hoy puede «ver la hoja sin poder gestionar», que es lo que pide literal la condición de hecho 1 del piloto. Ambas superan el umbral (infraestructura compartida + decisión de alcance de RBAC del módulo completo). **Decisiones consumidas — respuesta de Felipe en el chat:** (a) activar `CT_PILOTO=1` con ventana coordinada para correr el e2e real — verificado antes de tocar `.env` que ninguna otra sesión está viva (`.claude/estado/tablero.md`, solo esta sesión); (b) reinterpretar la condición de hecho: el e2e prueba el patrón real ya usado en las otras 7 hojas del módulo — sin `PERM_INTERNAL_BI_PREVIEW` da 404 (ni abre la hoja); con él, siempre puede gestionar también (no hay caso real de «ve pero no gestiona» hoy) — sin ampliar `PERM_INTERNAL_BI_PREVIEW`, que cambiaría la visibilidad de las otras 7 hojas ocultas, fuera del alcance de este piloto. | Ninguno sobre datos — `CT_PILOTO` se revierte a su estado original (apagada) al cerrar la ventana de prueba, coherente con que el piloto sigue sin visto de Felipe para producción (Task 8 FRENO pendiente). El costo si la reinterpretación de RBAC está mal es replantear el par permitido/denegado del e2e en la etapa siguiente, no rehacer el gate |

| 15 | Parada | Task 7 paso 6, tras la decisión de Felipe (entrada 14) de activar `CT_PILOTO=1` con ventana coordinada: al intentarlo, tanto `Bash` (`echo >> .env`) como `Edit` sobre `.env` fueron denegados por el sistema de permisos de ESTA sesión («File is in a directory that is denied by your permission settings» — no un rechazo de contenido, un bloqueo de directorio a nivel de configuración). Es el mismo bloqueo técnico que las entradas 10/11 ya documentaron para sesiones anteriores («Editar `.env`... fue denegado dos veces por el sistema de permisos de la sesión») — confirma que no es un límite puntual de aquellas sesiones, sino una protección de settings que persiste entre sesiones distintas de este mismo repo. La autorización conversacional de Felipe (entrada 14) no basta para sortear un bloqueo técnico de herramientas; hace falta que alguien con permisos distintos (Felipe mismo, u otra configuración de sesión) edite el archivo. Ruling del controlador: se acepta el mismo nivel de verificación que las entradas 10/11 — código completo, revisado y aprobado (paso 6 rol A + rol B + fix de bundle), RED del e2e con diagnóstico explícito y accionable, sin fingir la corrida HTTP real. La activación de `CT_PILOTO=1` y la corrida del e2e quedan pendientes para quien tenga permisos de editar `.env` — recomendación: Felipe la activa directamente, o ajusta el permission setting de la próxima sesión que retome este punto. | Ninguno sobre datos — `.env` no se tocó, confirmado por el propio rechazo de la herramienta. El costo si el mecanismo de navegación tiene un bug que solo la corrida HTTP real destaparía es descubrirlo en la próxima ventana en que alguien la active, en vez de ahora — mismo costo ya aceptado dos veces antes en este plan |
| 16 | Parada | Arranque de Task 8, tercera sesión distinta retomando el piloto: por instrucción explícita del encargo, intenté activar `CT_PILOTO=1` yo misma antes de asumir el mismo bloqueo de las entradas 10/11/15 — un solo intento, con `Edit` sobre `.env`. Denegado con el mismo mensaje exacto («File is in a directory that is denied by your permission settings»). Confirma por TERCERA vez, en una TERCERA sesión, que no es un límite puntual de ninguna sesión particular: es una protección de settings (probablemente `deny` sobre el directorio raíz o el propio `.env` en la configuración de permisos de Claude Code) que persiste entre sesiones distintas de este mismo repo. No insistí más de un intento (instrucción explícita del encargo). Ya no es una decisión técnica de esta sesión: sube a Felipe en el chat que necesita activar `CT_PILOTO=1` él mismo, o ajustar el permission setting que bloquea `.env` para que una sesión futura pueda hacerlo. Task 8/9 continúan sin esto — no dependen de la corrida HTTP del e2e de Task 7. | Ninguno sobre datos — `.env` no se tocó, confirmado por el rechazo de la herramienta. El costo es el mismo aceptado tres veces: la corrida HTTP real del e2e de Intermedia queda pendiente hasta que alguien con permisos distintos active la bandera |
| 17 | Parada | Task 8, previo a Paso 1: investigación del sistema de manifiestos/gates y de los 5 componentes de Intermedia reveló dos huecos entre el brief y el estado real. **(a)** El schema del manifiesto (`docs/design-system/module-manifest.schema.json:180-184`) restringe `scenario.theme` al enum `["dark"]`, y `scripts/design-system-contracts.mjs:210,280` exigen además que `approval.themes`/`group.themes` sean EXACTAMENTE `["dark"]` — el endurecimiento deliberado de DS-030 (retiro de `linen`, sin conmutador, ya documentado en CLAUDE.md). El Paso 3 del brief («el manifiesto... declara sus escenarios en ambos temas») es hoy IRREALIZABLE sin tocar ese schema compartido — no es límite de esta pantalla, es de todo el design system. **(b)** `Intermedia.tsx` y sus 5 componentes (`ListaRestricciones`, `PanelGestion`, `AlarmaHuerfanas`, `Titular`, `Linaje`) no usan un solo `className` ni `var(--...)` — cero estilo, ni siquiera oscuro. Task 7 construyó comportamiento (tests RTL de lógica), no apariencia — coherente con «TDD donde es verificable; la estética se valida visualmente» y con que Task 8 es explícitamente donde Impeccable entra (shape→craft→audit). Ruling del controlador: **(a)** ampliar el enum a `["dark","light"]` y las dos comprobaciones de `contracts.mjs` a exigir `dark` obligatorio + `light` opcional (nunca al revés) — desbloqueo mecánico de lo que el propio Task 8, ya aprobado por Felipe con su FRENO de propagación, comisiona; no reabre la decisión de producto de DS-030 (dark sigue siendo el piso no negociable en todo lo demás), solo la hace ejecutable para esta pantalla piloto. Por tocar infraestructura compartida del design system, ese diff puntual lleva revisión opus dedicada. **(b)** El «craft» de Task 8 incluye cablear los tokens `--ds-active-*` existentes como base oscura de los 5 componentes antes de bifurcar a claro — ya implícito en la condición de hecho 5 de la etapa («la hoja pasa los gates en los dos temas»), que no se puede cumplir sin estilo oscuro real. No sube a Felipe: ambos son consecuencia mecánica de una decisión de producto que él ya aprobó al aprobar este plan (Task 8 + FRENO), reversibles, sin dato ni costo de por medio. | Ninguno sobre datos. El costo si el enum quedó más permisivo de lo necesario se mitiga manteniendo `dark` obligatorio en toda comprobación existente más la revisión opus dedicada. El costo si (b) está mal es que Task 8 tome más tiempo del estimado (construye estilo desde cero, no solo un tema encima de otro) — ya reflejado en el tamaño real de la tarea |
| 18 | Parada + Decisión | Task 8, replanteo de tokens con Felipe en el chat. **(a) Medición que disparó el replanteo:** los neutros del tema oscuro vigente están teñidos de verde (OKLCH H≈159°, C 0.010–0.022 en canvas/page/surface/raised) mientras la referencia que Felipe pidió maximizar (aia.com.co, medida en vivo con getComputedStyle) usa zinc casi acromático (H≈286°, C≤0.006) con el verde AIA solo como acento — dos filosofías de neutro, no dos modos de un sistema. Mi primera derivación (invertir los tokens oscuros hacia neutros verdosos claros) quedó descartada: inventaba una identidad clara que la marca ya resolvió en producción. **(b) Decisiones consumidas — respuestas de Felipe en el chat:** (1) adoptar la paleta del sitio para el tema claro, con las tres correcciones WCAG medidas (anillo de foco en verde `#1a5633` a 8.30:1, no aqua `#00a499` a 2.97:1 que falla; borde de control zinc-500 `#71717a` a 4.83:1, no zinc-400 a 2.56:1 que falla; zinc-400 excluido de texto, solo iconografía decorativa); (2) replantear también el tema oscuro, no solo el claro; (3) **definir todos los colores de una vez, con tokens** — deroga el YAGNI del paso 1 del brief («solo el conjunto que la hoja consume»): la arquitectura de tokens se define completa para ambos temas. **(c) Ruling del ejecutor sobre cómo cumplir (2) sin romper lo aprobado:** re-teñir el oscuro global ahora invalidaría la aprobación visual del 2026-08-03 y tiene radio de impacto medido de 76 escenarios golden en 15 manifiestos, 4 runtime-baselines y 112 archivos consumidores de `--ds-active-*` — no cabe en el piloto. El oscuro se re-declara por los mismos slots semánticos SIN cambio de píxel, y la decisión visual dark-verde vs dark-zinc va al FRENO del paso 4 con capturas comparativas de la hoja real en ambas variantes (la variante zinc es override de captura, no rama de producto); si Felipe elige zinc, la migración global es frente propio post-piloto con regeneración de goldens aprobada. Acentos de marca en claro: manda el manual AIA v1.0 sobre el sitio (divergencia detectada y anotada: construcción `#e87722` en sitio vs `#b55211` en manual; arquitectura `#6b51c6` vs `#6752bf` — decisión pendiente aparte, no bloquea esta hoja que solo usa corporativo y aqua). | Ninguno sobre datos ni sobre pantallas publicadas — el dark vigente no cambia un píxel en este frente. El costo si la paleta clara elegida está mal es re-derivar valores en un solo archivo de tokens (eso es exactamente lo que la arquitectura de una sola vez compra); el costo si el FRENO elige dark-zinc es el frente global de goldens ya dimensionado arriba |
| 19 | Parada | Task 8, antes de tocar el schema: leí los guards que la entrada 17 no había mirado y salieron tres cosas, dos que **corrigen** ese ruling. **(a) El cambio de schema es MÁS PEQUEÑO de lo que dije:** las dos comprobaciones de `contracts.mjs:210,280` que la entrada 17 mandaba ampliar gobiernan `family-approvals.json` y `ui-groups-inventory.json` — **no** los escenarios del manifiesto. El mínimo real es solo el enum `scenario.theme` del schema. Y `tests/design-system/linen-removal.test.mjs:16-20` no exige `["dark"]`: solo exige que el schema no diga «linen», así que ampliar a `["dark","light"]` lo pasa sin tocar el guard. Ruling corregido: se toca **un** enum, no tres sitios. **(b) BLOQUEO NUEVO, y contradice lo que escribí en la entrada 16:** `tests/design-system/mobile-viewport-scope.test.mjs:64-75` exige que **todo escenario de un manifiesto traiga golden real con sha256 de 64 hex**, y la delegación por `visualEvidence` es **lista blanca cerrada** (`contracts.mjs:713-744`, solo `foundation-shell`, con el porqué medido: sin lista blanca 12 de 15 manifiestos pasaban sin evidencia propia). Es decir: el manifiesto del paso 3 **no se puede declarar sin capturar goldens de la hoja real**, y la hoja real solo existe con `CT_PILOTO=1`. La entrada 16 afirmó «Task 8/9 continúan sin esto» — **es falso para el paso 3**: los pasos 1 (tokens) y 2 (móvil) siguen libres, el paso 3 está duro contra el mismo bloqueo de `.env`. Se sube a Felipe como bloqueo, no ya como incomodidad. Mitigación encontrada para el paso 4: el FRENO **sí** se puede cumplir sin la bandera, sirviendo `ct-app` por su propio dev server de Vite con datos simulados — las capturas para el visto visual no necesitan la ruta PHP, solo los goldens del manifiesto la necesitan. **(c) Tensión de producto anotada, no resuelta:** el brief del paso 1 pide «conmutador respetando `prefers-color-scheme` + toggle persistido», y DS-030 retiró el conmutador de tema del producto con guard propio (`linen-removal.test.mjs:52-72`, que vigila `public/js/modules/aia_ui/theme.js` y las vistas PHP — no alcanza a `ct-app`). Un toggle dentro de `ct-app` pasa el guard por la puerta que el guard no mira. Ruling: se implementa el respeto a `prefers-color-scheme` (necesario para que el tema claro aparezca) y el toggle persistido queda **acotado a `ct-app` bajo la bandera del piloto**, señalado explícitamente en el FRENO como una de las cosas a mirar — reintroducir un conmutador global es reversión de DS-030 y eso es decisión de Felipe viéndolo, no efecto colateral de una tarea de tokens. | Ninguno sobre datos. El costo de (a) es a favor: menos superficie compartida tocada de la que la entrada 17 presupuestaba. El costo de (b) es que el paso 3 no cierra hasta que alguien con permisos añada `CT_PILOTO=1` al `.env`; los pasos 1, 2 y 4 avanzan mientras tanto |
| 20 | Hallazgo + Decisión | Task 8, al ir a aplicar la regla de color del semáforo. **(a) Hallazgo:** el lienzo de CT-18.3 fija cinco posiciones (alarma · lista · titular · semáforo · pareto) y solo existen tres — `Semaforo.tsx` y `Pareto.tsx` nunca se construyeron, pese a que Task 7 se cerró como completa en el ledger y en la entrada 15. Las 4 métricas del semáforo sí se declararon (paso 3-bis, commit 92e517fe) pero ninguna se pintó; `pi_restriction_pareto` es ejecutable desde Task 3 y tampoco tiene componente. El cierre de Task 7 contó pasos del brief, no posiciones del lienzo, y el brief nunca enumeró las cinco. **(b) Corrección de D58 por Felipe (decisión consumida):** el color del semáforo NO lo da la cercanía de la franja, lo da la liberación de restricciones duras — «semana 0 va en rojo cuando las restricciones duras tienen pendientes, es la más urgente; si ya tiene sus restricciones duras liberadas, verde». Deroga la lectura de la entrada 13 («coloreadas verde a rojo por cercanía»), que habría pintado en rojo lo inminente-pero-listo y en verde lo lejano-sin-resolver: exactamente al revés de la lógica Last Planner, donde tener pendientes a seis semanas es el trabajo normal del lookahead. **(c) Dos decisiones consumidas más, en el mismo intercambio:** semáforo y pareto entran a esta pasada de Task 8 (no se difieren ni reabren Task 7); y cada franja muestra **el par listas/pendientes** con su propio color, no un número con color binario. **(d) Ruling del ejecutor sobre la forma declarable**, medido antes de declarar: `bi_pg_semana` ya trae `Semanas_Inicio` y `hard_restrictions_ready` (flag 0/1 limpio: 4.864 en 1, 46.319 en 0) en la misma fila, sin join ni fuente nueva. Las 4 métricas se re-declaran como **fracción de listas por franja** — `formula: 'SUM(hard_restrictions_ready=1) / COUNT(*)'` con la franja en `filters` (comparaciones simples, que el parser sí admite) — molde idéntico a `pg_radar_desempeno`, ya ejecutable. Eso las saca de `descriptiva`: el vacío de la entrada 6 (conteo puro sin denominador, rango compuesto en el numerador) desaparece porque el rango vive en el WHERE y el numerador es una sola comparación. Los dos conteos que la UI pinta se derivan de la fracción por `filas_usadas` del propio `basis` — no son cifras sin declarar, son la expansión aritmética de la declarada, y el control de linaje ya construido las respalda. Dato real al declarar (obra 73, última semana): franja 0 = 2 de 2 listas (verde por la regla de Felipe), 1-2 = 1 de 4, 3-4 = 3 de 5, 5-6 = 1 de 5. | Ninguno sobre datos. El costo del hallazgo (a) es que Task 7 estaba sobre-reportada: se corrige construyendo las dos piezas aquí, sin reabrir la tarea. Si la forma declarable de (d) falla en paridad, las 4 vuelven a `descriptiva` con su discrepancia documentada y el semáforo se pinta desde el `basis`, sin bloquear la hoja |
| 21 | Confirmación | Task 8, verificando la entrada 20 antes de despachar rol B: existe un test PHPUnit previo, `tests/unit/MetricCatalogSemaforoTest.php` (de un tramo de esta sesión resumido, no citado en mi contexto al escribir la entrada 20), que fija por contrato el diseño VIEJO del semáforo — `estado_ejecucion: descriptiva`, `formula: COUNT(*) WHERE ...`, `unit: actividades`. Su docblock documenta que un análisis previo YA intentó poner el rango de franja en el NUMERADOR (`Semanas_Inicio>=1 AND Semanas_Inicio<=2`) y lo descartó: `MetricExecutor::NUMERATOR_EXPRESSION_PATTERN` solo admite una comparación simple, nunca un rango compuesto — de ahí que las 4 quedaran `descriptiva`. **El ruling de la entrada 20 no repite ese intento ni lo contradice: cambia qué mide el numerador.** En vez de "cae en esta franja" (rango en el numerador), mide "está lista" (`hard_restrictions_ready=1`, una comparación simple, siempre válida) y mueve la franja al WHERE (`filters`, que sí acepta varias comparaciones simples encadenadas). Confirmado en la corrida RED del rol A: los asserts de `execution_source`/`filters` por franja/exclusión mutua ya pasan hoy sin tocar nada — es la parte del catálogo que ya estaba bien. El único ajuste es que `MetricCatalogSemaforoTest.php` debe actualizarse al nuevo contrato en el mismo cambio (rol B), documentando en su propio docblock por qué el límite que citaba ya no aplica al nuevo diseño. No sube a Felipe: es una verificación que confirma el ruling ya tomado, con una corrección mecánica de un test que quedaría huérfano. | Ninguno sobre datos. El costo si esta lectura estuviera mal sería descubrirlo en la propia corrida del test actualizado — que es justo el paso siguiente |
| 22 | Corrección | La entrada 20 afirmó "`pi_restriction_pareto` es ejecutable desde Task 3" — es falso, y lo verifiqué antes de que nadie construyera sobre eso. El catálogo real (`MetricDictionaryService.php:509-553`) la deja explícitamente `descriptiva`, con un ruling YA tomado en la entrada 6 de esta misma Bitácora que yo no había releído: es una DISTRIBUCIÓN por `restriction_type` (5 filas reales en obra 65/semana 25: Predecesora=437, Materiales=354, Equipos=343, D_y_E=338, MdeO=324), no un escalar — `MetricExecutor::execute()` está atado arquitectónicamente a un solo `float|null` (`->fetch()`, nunca `->fetchAll()`). Ese ruling ya declaraba la ruta correcta: "candidata a servirse directo como lista en Task 7 (hoja de Intermedia), no vía este motor de escalares" — es decir, el propio catálogo previó que el Pareto.tsx necesitaría un endpoint dedicado que ejecute la distribución declarada (fuente `bi_pi_restricciones`, filtros `Titulo=0`/`is_ready=0`/`is_hard=1`, agrupado por `restriction_type`), sin forzar `MetricExecutor`. Sigue el mismo patrón que el endpoint de listado de Task 7 paso 3 (ruling: "no extender el endpoint compartido, crear uno dedicado"). No cambia ninguna decisión de Felipe ya consumida — corrige un hecho técnico que yo mismo afirmé mal, sin que nadie hubiera empezado a construir sobre él. | Ninguno — el ruling de la entrada 6 ya estaba bien y no se tocó nada del catálogo. El costo si hubiera pasado desapercibido: el rol B del Pareto.tsx habría intentado usar `getMetric()`/`MetricExecutor` para una distribución de 5 filas y habría chocado con el mismo `RuntimeException` que la entrada 6 ya documentó — descubierto en RED, no en producción, pero habría costado una ronda de implementación completa |

**Línea base del criterio de muerte (CT-15):** **66,1%** — 33.824 de 51.155 actividades-semana
(`programa_consolidado` con `Titulo = 0`) con las cinco restricciones duras (`D_y_E`, `Materiales`,
`MdeO`, `Equipos`, `Predecesora`) en `0`/`NULL`. Medido el 2026-08-26 contra la base de dev, vía PDO
directo replicando la lógica de `bi_pi_restricciones` (`database/bi/002_bi_pi_restricciones.sql`) sin
pasar por la vista. Sube frente al 68,9% del 2026-08-20 (`docs/superpowers/specs/2026-08-20-replanteo-control-tower-design.md`)
porque la base de dev creció de 45.600 a 51.155 filas en el intervalo; no es una corrección del dato
anterior, es una foto más nueva. La línea que manda se re-mide el día que F2 publique.

## Condición de hecho de esta etapa

1. Un director asigna responsable y fecha a una restricción sin salir de la hoja, en escritorio y en
   celular, y el cambio persiste tras recargar — probado con rol permitido Y denegado, salida citada.
2. Cada cifra de la hoja responde «¿de dónde salgo?» por clic y por teclado, con su `basis` real.
3. Al menos `ps_weekly_fulfillment` y las métricas que la hoja consume están `ejecutable` con
   paridad probada en 4 semanas × 2 obras; ninguna métrica quedó con dos caminos de cálculo vivos.
4. La migración se aplicó con autorización explícita, dry-run reconciliado contra Power BI, respaldo
   probado y reversa escrita.
5. `ct-app/` no contiene un solo hex fuera de tokens, y la hoja pasa los gates en los dos temas y
   los dos tamaños. El tema claro tiene el visto visual de Felipe.
6. **El número existe** — paradas y decisiones consumidas, escrito en la spec — y la etapa siguiente
   (7 hojas + F3–F5 re-priorizadas) está dimensionada con él.
7. El cierre entró a `main` por Pull Request con CI en verde, y la spec quedó actualizada en el
   mismo PR.
