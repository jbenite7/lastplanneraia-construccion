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
| 4 | Parada | Task 3 paso 3 (rol B), con el filtro de la entrada 3 ya corregido: el trinquete SÍ corrió sobre las 4 semanas × 2 obras y encontró una discrepancia que no es de catálogo — `MetricExecutor::execute()` nunca acota por `Semana`. `MetricScope` solo lleva `project_id`(s) y un `startDate`/`endDate` opcional que `MetricExecutor::buildWhereClause()` nunca lee; no hay ningún parámetro de "semana" en toda la cadena. El "camino nuevo" agrega entonces TODAS las semanas históricas del proyecto (678 filas para la obra 65) en vez de solo la semana pedida (24-80 filas por semana), dando el mismo valor sin importar qué semana se compare — confirmado reproduciendo la agregación a mano contra `bi_ps_compromisos` (evidencia completa en `task-3-report.md`). Es un vacío de alcance de `MetricExecutor`/`MetricScope` (Task 2), fuera de lo que el catálogo o el arnés de prueba de Task 3 pueden resolver. `ps_weekly_fulfillment` se queda `descriptiva` (no `en_paridad`, no `ejecutable`) con el hallazgo documentado en su `known_limitations`; no se fuerza paridad. Ruling: **pendiente — decisión del controlador antes de abrir el Paso 5**, porque el mismo vacío de alcance probablemente golpea a la mayoría de las 18 métricas restantes (todas con `grain` semanal). | Ninguno sobre datos — el filtro corregido de la entrada 3 se conserva documentado, aunque sin efecto mientras la métrica siga `descriptiva`. El costo es que Task 3 no cierra su "primera métrica completa" hasta resolver el alcance de `MetricExecutor`, y Paso 4 (borrar el SQL viejo) tampoco corrió: `ControlTowerService::scorecardPS()` sigue siendo la única fuente de otros 3 KPIs en vivo (`Compromisos activos`, `En riesgo`, `CNC esta semana`) que ni siquiera tienen entrada en el catálogo — borrarlo entero habría roto el reporte `semanal` real (`BiControlTowerApiController`), así que no se tocó `ControlTowerService.php` |

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
