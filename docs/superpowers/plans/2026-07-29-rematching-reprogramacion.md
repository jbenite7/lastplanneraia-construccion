# Re-matching al reprogramar (PDC v2 · fase B2, segunda mitad) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Que mover un frente en el cronograma se pueda *aplicar* al plan de compras — viendo antes qué paquetes se mueven y cuántos días — sin que se borre nunca lo que ya ocurrió.

**Architecture:** `PlanFechasService::calcular()` hoy proyecta y escribe en el mismo bucle, y proyecta contra la `fecha_ancla` **congelada** en `pdc_paquete_frente`, que nunca se refresca. Se parte en dos: una proyección pura reutilizable y la escritura. Sobre la proyección pura se monta `simularReprogramacion()` (no escribe nada) y `aplicarReprogramacion()` (refresca el ancla de los paquetes que el usuario confirmó y recalcula). Sin tablas nuevas: el desfase se sigue deduciendo comparando `fecha_ancla` contra el cronograma en vivo.

**Tech Stack:** PHP 8.3 (sin framework), MySQL 8 vía `Database` (PDO, prepared statements), React + Vite + AG Grid en `pdc-app/`, tests PHP autoejecutables en `tests/`, Playwright en `tests/browser/` y `e2e/`.

## Global Constraints

- **Lo programado se recalcula; lo ocurrido nunca se borra.** Un paso con `fecha_real` conserva su fecha real aunque su fecha programada se mueva. Regla heredada de B1, no negociable.
- **Prohibido reamarrar paquetes solos.** Si un frente desaparece del cronograma, el paquete queda sin frente y se pide confirmación humana. Nunca se elige otro frente por él.
- **Simular y pedir confirmación, nunca aplicar y avisar.** Recalcular es la operación que más daño puede hacer del módulo. Cancelar no escribe absolutamente nada.
- **Cero regresión:** una obra sin configurar sigue teniendo los siete pasos de siempre (contrato de A4.1 contra las 11 filas y 77 pasos de Da Porto).
- **UI: solo desktop ≥1180 px y solo dark mode.** Viewport canónico 1180×820. Nada de mobile, tablet ni tema `linen` (AGENTS.md).
- **Sin tablas nuevas.** El estado «desactualizado» se deduce comparando fechas, no se persiste en una columna que haya que mantener al día.
- **RBAC:** lectura `lps.paquetes_contratacion.ver`; escritura `lps.paquetes_contratacion.editar` + CSRF `plan_compras_v2`. Toda consulta aislada por `project_id`.
- **No hacer commit, push ni deploy** salvo petición explícita del usuario.

## Qué se retira del alcance (medido, no supuesto)

`goals/pdc-preparar-b1/evidence/medicion-rematching-2026-07-29.md` demuestra sobre datos que **ya existen** y NO se reimplementan:

- **Detectar el desfase** — `PlanFechasService::desfases()` + `GET /plan-compras/api/plan/desfases`.
- **Avisar en la pantalla del plan** — pestaña «Desfases» y estado `desfasado` por fila.
- **No recalcular solo** — medido: el plan no cambia por su cuenta.
- **Conservar `fecha_real`** — medido: sobrevive al recálculo.
- **No reamarrar solo un frente borrado** — medido: queda huérfano y se reporta.

Y demuestra el bug que reordena el trabajo: **«Recalcular» no recoge la fecha nueva del frente**, porque `amarres()` (`src/Services/Pdc/PlanFechasService.php:1015`) lee la `fecha_ancla` congelada. Tras recalcular, el desfase sigue reportándose. Hoy no hay forma de aplicar un desfase salvo desamarrar y volver a amarrar.

## File Structure

| Archivo | Responsabilidad |
|---|---|
| `src/Services/Pdc/PlanFechasService.php` (modificar) | Extraer `proyectar()` (puro), añadir `simularReprogramacion()` y `aplicarReprogramacion()` |
| `src/Services/Pdc/SeguimientoService.php` (modificar) | Añadir `cronogramaDesactualizado()`: cuántos paquetes del tablero se calcularon contra un cronograma viejo |
| `src/Controllers/Api/PlanComprasPlanController.php` (modificar) | Endpoints `reprogramacion/simular` y `reprogramacion/aplicar` |
| `src/Controllers/Api/PlanComprasSeguimientoController.php` (modificar) | Exponer el aviso en el payload de `resumen` |
| `public/index.php` (modificar) | Registrar las dos rutas nuevas |
| `pdc-app/src/lib/types.ts` (modificar) | Tipos `SimulacionReprogramacion`, `DeltaPaquete` |
| `pdc-app/src/lib/reprogramacion.ts` (crear) | Lógica pura de presentación del delta (texto y agrupación), testeable sin DOM |
| `pdc-app/src/lib/reprogramacion.test.ts` (crear) | Tests de esa lógica |
| `pdc-app/src/pages/PlanFechas.tsx` (modificar) | Pestaña «Desfases»: «Ver qué cambia» → panel de delta → «Aplicar» / «Cancelar» |
| `pdc-app/src/pages/Seguimiento.tsx` (modificar) | Banner «esto se calculó contra un cronograma que ya cambió» |
| `tests/test_pdc_v2_reprogramacion.php` (crear) | Test PHP sobre MySQL real |
| `tests/browser/pdc-v2-plan.spec.mjs` (modificar) | Cobertura del flujo simular → confirmar → cancelar |

---

### Task 1: Extraer la proyección de `calcular()` sin cambiar su comportamiento

Refactor puro. Hoy `calcular()` (línea 1078) decide días, provisionalidad y fechas *y* escribe, todo en el mismo bucle. Para poder simular hace falta la primera mitad sin la segunda.

**Files:**
- Modify: `src/Services/Pdc/PlanFechasService.php:1078-1274`
- Test: `tests/test_pdc_v2_plan_fechas.php` (existente — debe seguir verde sin tocarlo)

**Interfaces:**
- Produces: `private function proyectar(int $paqueteId, string $fechaAncla, array $pasos, array $medianas, string $selectCols): ?array` que devuelve
  `['arranque' => 'Y-m-d', 'total' => int, 'dias' => list<int>, 'provisional' => bool, 'duracionRef' => ?int]`
  o `null` si el paquete está inactivo o su modalidad ya no genera proceso (el `continue` actual de la línea 1116).

- [ ] **Step 1: Ejecutar los tests que hoy protegen esta zona, para tener la línea base**

```bash
docker compose exec app php tests/test_pdc_v2_plan_fechas.php
docker compose exec app php tests/test_pdc_v2_seguimiento.php
docker compose exec app php tests/test_pdc_v2_pasos_configurables.php
```

Expected: los tres terminan en `=== OK ===` / `OK`, rc 0.

- [ ] **Step 2: Extraer el método `proyectar()`**

En `src/Services/Pdc/PlanFechasService.php`, añadir este método privado justo antes de `calcular()`. El cuerpo es **literalmente** el bloque de las líneas 1104-1167 actuales, sin cambiar una coma de la aritmética:

```php
    /**
     * Qué fechas produciría este paquete anclado a `$fechaAncla`. NO escribe nada.
     *
     * Se separó de `calcular()` en B2 porque simular la reprogramación necesita exactamente este
     * cálculo sin la escritura. La aritmética no cambió al extraerla: el contrato de fronteras
     * medio abiertas `[fecha_inicio, fecha_fin)` documentado en `calcular()` sigue siendo el mismo.
     *
     * @param list<array{pasoId:?int,clave:string,nombre:string,colLegacy:?string,diasFijos:?int,peso:?float}> $pasos
     * @param array<string,int> $medianas
     * @return array{arranque:string,total:int,dias:list<int>,provisional:bool,duracionRef:?int}|null
     *         null si el paquete está inactivo o su modalidad ya no genera proceso de contratación
     */
    private function proyectar(int $paqueteId, string $fechaAncla, array $pasos, array $medianas, string $selectCols): ?array
    {
        $paq = $this->db->query(
            "SELECT p.id, p.tipo_negociacion, p.duracion_ref{$selectCols}
             FROM general_paquetes_contratacion p
             LEFT JOIN general_dias_procesos_contratacion d ON d.id = p.duracion_ref
             WHERE p.id = ? AND p.activo = 1
               AND p.modalidad_contratacion IN (" . self::modalidadesConProcesoSql() . ')',
            [$paqueteId],
        )->fetch(\PDO::FETCH_ASSOC);
        if ($paq === false) {
            return null;
        }

        $desgloseCompleto = true;
        foreach ($pasos as $p) {
            if ($p['colLegacy'] !== null && ($paq[$p['colLegacy']] ?? null) === null) {
                $desgloseCompleto = false;
                break;
            }
        }
        $provisional = !$desgloseCompleto;

        if ($provisional) {
            $mediana = $medianas[$paq['tipo_negociacion']] ?? self::DURACION_FALLBACK_DIAS;
            $fijos = 0;
            $pesos = [];
            foreach ($pasos as $p) {
                $esFijo = $p['colLegacy'] === null;
                $fijos += $esFijo ? (int) ($p['diasFijos'] ?? 0) : 0;
                $pesos[] = $esFijo ? 0.0 : ($p['peso'] ?? 0.0);
            }
            $reparto = self::repartirMediana(max(0, $mediana - $fijos), $pesos);
            $dias = [];
            foreach ($pasos as $i => $p) {
                $dias[] = $p['colLegacy'] === null ? (int) ($p['diasFijos'] ?? 0) : $reparto[$i];
            }
        } else {
            $dias = [];
            foreach ($pasos as $p) {
                $dias[] = $p['colLegacy'] === null
                    ? (int) ($p['diasFijos'] ?? 0)
                    : (int) $paq[$p['colLegacy']];
            }
        }
        $total = array_sum($dias);

        return [
            'arranque' => (new \DateTimeImmutable($fechaAncla))->modify(sprintf('-%d days', $total))->format('Y-m-d'),
            'total' => $total,
            'dias' => $dias,
            'provisional' => $provisional,
            'duracionRef' => $paq['duracion_ref'] === null ? null : (int) $paq['duracion_ref'],
        ];
    }
```

- [ ] **Step 3: Hacer que `calcular()` consuma `proyectar()`**

Sustituir en `calcular()` el bloque que va desde `$paq = $this->db->query(` hasta la línea `$arranque = $cursor->format('Y-m-d');` por:

```php
            $pr = $this->proyectar($paqueteId, $a['fechaAncla'], $pasos, $medianas, $selectCols);
            if ($pr === null) {
                // Paquete inactivo, o cuya modalidad ya no genera proceso de contratación (cambió
                // después de amarrarlo): no se calcula plan para él. Su cabecera vieja, si existe,
                // queda huérfana en pdc_plan_paquete — plan() la filtra por su cuenta.
                continue;
            }
            $provisional = $pr['provisional'];
            $dias = $pr['dias'];
            $total = $pr['total'];
            $arranque = $pr['arranque'];
            $cursor = new \DateTimeImmutable($arranque);
            if ($provisional) {
                $sinDuracion++;
            }
```

y en el `INSERT` de la cabecera cambiar `$paq['duracion_ref']` por `$pr['duracionRef']`.

- [ ] **Step 4: Verificar que no cambió nada**

```bash
docker compose exec app php tests/test_pdc_v2_plan_fechas.php
docker compose exec app php tests/test_pdc_v2_seguimiento.php
docker compose exec app php tests/test_pdc_v2_pasos_configurables.php
docker compose exec app php tests/test_pdc_projected_dates_reflow.php
```

Expected: los cuatro en verde, exactamente igual que en el Step 1. Si alguno cambia, el refactor no fue puro: revertir y rehacer, no ajustar el test.

- [ ] **Step 5: Análisis estático**

```bash
docker compose exec app vendor/bin/phpstan analyse src admin/src --memory-limit=1G
```

Expected: sin errores nuevos respecto a la línea base de la rama.

- [ ] **Step 6: Commit** *(solo si el usuario autorizó commits; si no, dejarlo en el worktree)*

```bash
git add src/Services/Pdc/PlanFechasService.php
git commit -m "refactor(pdc): separar la proyeccion de fechas de su escritura"
```

---

### Task 2: `simularReprogramacion()` — el delta, sin escribir nada

**Files:**
- Modify: `src/Services/Pdc/PlanFechasService.php`
- Test: `tests/test_pdc_v2_reprogramacion.php` (crear)

**Interfaces:**
- Consumes: `proyectar()` de la Task 1.
- Produces:

```php
/**
 * @return array{
 *   movidos: list<array{
 *     paqueteId:int, nombre:string, frenteNombre:string,
 *     anclaActual:string, anclaNueva:string, diasMovidos:int,
 *     arranqueActual:?string, arranqueNuevo:string,
 *     pasosQueSeMueven:int, pasosConFechaReal:int
 *   }>,
 *   huerfanos: list<array{paqueteId:int, nombre:string, frenteNombre:string, anclaActual:string}>
 * }
 */
public function simularReprogramacion(int $projectId): array
```

`movidos` son los paquetes cuyo frente sigue en el cronograma pero cambió de fecha. `huerfanos` son los que apuntan a un frente que ya no está: **no se simulan y no se aplican**; se listan para que un humano decida.

- [ ] **Step 1: Escribir el test que falla**

Crear `tests/test_pdc_v2_reprogramacion.php` con el mismo patrón de fixture que `tests/medicion_rematching_reprogramacion.php` (proyecto 999952, `$limpiar()` al principio y al final), y estos asserts:

```php
// Fixture: paquete de 33 días amarrado a ESTRUCTURA (2026-09-01), calculado.
// Paso de orden 1 con fecha_real = 2026-08-05. Frente movido a 2026-09-22 (+21).

$sim = $svc->simularReprogramacion($P);

$assert(count($sim['movidos']) === 1, 'Un solo paquete movido: ' . count($sim['movidos']));
$m = $sim['movidos'][0];
$assert($m['diasMovidos'] === 21, 'El frente se atrasó 21 días: ' . $m['diasMovidos']);
$assert($m['anclaActual'] === '2026-09-01', 'Ancla actual: ' . $m['anclaActual']);
$assert($m['anclaNueva'] === '2026-09-22', 'Ancla nueva: ' . $m['anclaNueva']);
$assert($m['arranqueActual'] === '2026-07-30', 'Arranque actual: ' . (string) $m['arranqueActual']);
$assert($m['arranqueNuevo'] === '2026-08-20', 'Arranque nuevo, 21 días después: ' . $m['arranqueNuevo']);
$assert($m['pasosQueSeMueven'] === 7, 'Se mueven los siete pasos: ' . $m['pasosQueSeMueven']);
$assert($m['pasosConFechaReal'] === 1, 'Uno ya ocurrió y conserva su fecha real: ' . $m['pasosConFechaReal']);
$assert($sim['huerfanos'] === [], 'Sin huérfanos mientras el frente exista.');

// Simular NO escribe: el plan sigue exactamente como estaba.
$cabDespues = $db->query(
    'SELECT fecha_ancla, fecha_arranque FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?',
    [$P, $paq],
)->fetch(\PDO::FETCH_ASSOC);
$assert($cabDespues['fecha_ancla'] === '2026-09-01', 'Simular no tocó el ancla guardada.');
$assert($cabDespues['fecha_arranque'] === '2026-07-30', 'Simular no tocó el arranque guardado.');

// Un frente que no se movió no aparece en la simulación.
$assert(!in_array($paqQuieto, array_column($sim['movidos'], 'paqueteId'), true),
    'Un paquete cuyo frente no se movió no entra en el delta.');

// Frente borrado del cronograma → huérfano, nunca reamarrado.
$db->query('DELETE FROM programa_consolidado WHERE project_id = ? AND unique_id = 8801', [$P]);
$db->query('DELETE FROM programa WHERE project_id = ? AND unique_id = 8801', [$P]);
$sim2 = $svc->simularReprogramacion($P);
$assert($sim2['movidos'] === [], 'Un frente que ya no existe no produce un delta que aplicar.');
$assert(count($sim2['huerfanos']) === 1, 'Se lista como huérfano para que lo decida un humano.');
```

- [ ] **Step 2: Correr el test y verificar que falla**

```bash
docker compose exec app php tests/test_pdc_v2_reprogramacion.php
```

Expected: FAIL — `Call to undefined method App\Services\Pdc\PlanFechasService::simularReprogramacion()`.

- [ ] **Step 3: Implementar `simularReprogramacion()`**

```php
    /**
     * El antes/después de aplicar la reprogramación del cronograma al plan. NO escribe nada.
     *
     * Se calcula sobre lo que produciría `calcular()` con el ancla NUEVA, y se compara contra lo
     * que hay guardado. Es la mitad «mirar» de la operación que más daño puede hacer del módulo;
     * la mitad «escribir» es `aplicarReprogramacion()`, y solo corre si el usuario confirma.
     *
     * Los huérfanos —amarres a un frente que ya no está en el cronograma— van en su propia lista y
     * NUNCA en `movidos`: no hay fecha nueva contra la que proyectar, y reamarrarlos a otro frente
     * es una decisión humana que este módulo no toma por nadie.
     *
     * @return array{movidos: list<array{paqueteId:int,nombre:string,frenteNombre:string,anclaActual:string,anclaNueva:string,diasMovidos:int,arranqueActual:?string,arranqueNuevo:string,pasosQueSeMueven:int,pasosConFechaReal:int}>, huerfanos: list<array{paqueteId:int,nombre:string,frenteNombre:string,anclaActual:string}>}
     */
    public function simularReprogramacion(int $projectId): array
    {
        $movidos = [];
        $huerfanos = [];
        $desfases = $this->desfases($projectId);
        if ($desfases === []) {
            return ['movidos' => [], 'huerfanos' => []];
        }

        $medianas = $this->medianasPorTipo();
        $pasos = $this->pasos->deProyecto($projectId);
        self::exigirIdentidad($pasos);
        $cols = [];
        foreach ($pasos as $p) {
            if ($p['colLegacy'] !== null && in_array($p['colLegacy'], PasosContratacionService::columnasLegacy(), true)) {
                $cols[$p['colLegacy']] = true;
            }
        }
        $selectCols = $cols === []
            ? ''
            : ', ' . implode(', ', array_map(static fn (string $c): string => 'd.' . $c, array_keys($cols)));

        foreach ($desfases as $d) {
            if ($d['fechaActual'] === null) {
                $huerfanos[] = [
                    'paqueteId' => $d['paqueteId'],
                    'nombre' => $d['nombre'],
                    'frenteNombre' => $d['frenteNombre'],
                    'anclaActual' => $d['fechaGuardada'],
                ];
                continue;
            }
            $pr = $this->proyectar($d['paqueteId'], $d['fechaActual'], $pasos, $medianas, $selectCols);
            if ($pr === null) {
                continue; // inactivo o sin proceso de contratación: `calcular()` tampoco lo tocaría
            }
            $cab = $this->db->query(
                'SELECT fecha_arranque FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?',
                [$projectId, $d['paqueteId']],
            )->fetch(\PDO::FETCH_ASSOC);
            // Los pasos que ya ocurrieron se cuentan aparte y se dicen en pantalla: son justo los
            // que NO se van a mover, y es la garantía que el usuario necesita ver antes de aplicar.
            $conReal = (int) $this->db->query(
                'SELECT COUNT(*) FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ? AND fecha_real IS NOT NULL',
                [$projectId, $d['paqueteId']],
            )->fetchColumn();

            $movidos[] = [
                'paqueteId' => $d['paqueteId'],
                'nombre' => $d['nombre'],
                'frenteNombre' => $d['frenteNombre'],
                'anclaActual' => $d['fechaGuardada'],
                'anclaNueva' => $d['fechaActual'],
                'diasMovidos' => (int) $d['diasMovidos'],
                'arranqueActual' => $cab === false ? null : (string) $cab['fecha_arranque'],
                'arranqueNuevo' => $pr['arranque'],
                'pasosQueSeMueven' => count($pasos),
                'pasosConFechaReal' => $conReal,
            ];
        }
        return ['movidos' => $movidos, 'huerfanos' => $huerfanos];
    }
```

- [ ] **Step 4: Correr el test y verificar que pasa**

```bash
docker compose exec app php tests/test_pdc_v2_reprogramacion.php
```

Expected: `=== OK ===`, rc 0.

- [ ] **Step 5: Commit** *(condicionado a autorización)*

```bash
git add src/Services/Pdc/PlanFechasService.php tests/test_pdc_v2_reprogramacion.php
git commit -m "feat(pdc): simular que le pasa al plan si se aplica la reprogramacion"
```

---

### Task 3: `aplicarReprogramacion()` — escribir solo lo confirmado

**Files:**
- Modify: `src/Services/Pdc/PlanFechasService.php`
- Test: `tests/test_pdc_v2_reprogramacion.php` (ampliar)

**Interfaces:**
- Consumes: `simularReprogramacion()`, `calcular()`.
- Produces: `public function aplicarReprogramacion(int $projectId, array $paqueteIds, string $usuario): array` → `['ok' => true, 'aplicados' => int, 'ignorados' => int]`.

- [ ] **Step 1: Escribir los tests que fallan**

Añadir al final de `tests/test_pdc_v2_reprogramacion.php`:

```php
// Aplicar SOLO el paquete confirmado.
$r = $svc->aplicarReprogramacion($P, [$paq], 'test-b2');
$assert($r['aplicados'] === 1, 'Se aplicó un paquete: ' . $r['aplicados']);

$cab = $db->query(
    'SELECT fecha_ancla, fecha_arranque FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?',
    [$P, $paq],
)->fetch(\PDO::FETCH_ASSOC);
$assert($cab['fecha_ancla'] === '2026-09-22', 'El ancla se refrescó al cronograma nuevo: ' . $cab['fecha_ancla']);
$assert($cab['fecha_arranque'] === '2026-08-20', 'El arranque se corrió 21 días: ' . $cab['fecha_arranque']);

$ancla = $db->query(
    'SELECT fecha_ancla FROM pdc_paquete_frente WHERE project_id = ? AND paquete_id = ?',
    [$P, $paq],
)->fetchColumn();
$assert($ancla === '2026-09-22', 'El amarre también quedó al día: ' . (string) $ancla);

// Y el desfase deja de reportarse: este es el bug que la medición encontró.
$assert($svc->desfases($P) === [], 'Aplicado el delta, ya no queda desfase que avisar.');

// Lo ocurrido no se borró.
$real = $db->query(
    'SELECT fecha_real FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ? AND orden = 1',
    [$P, $paq],
)->fetchColumn();
$assert($real === '2026-08-05', 'El paso que ya ocurrió conserva su fecha real: ' . var_export($real, true));

// Un paquete NO confirmado no se toca.
$assert($svc->aplicarReprogramacion($P, [], 'test-b2')['aplicados'] === 0,
    'Sin paquetes confirmados no se escribe nada.');

// Un huérfano no se puede aplicar ni aunque lo pidan: no se reamarra solo.
$db->query('DELETE FROM programa_consolidado WHERE project_id = ? AND unique_id = 8802', [$P]);
$db->query('DELETE FROM programa WHERE project_id = ? AND unique_id = 8802', [$P]);
$rh = $svc->aplicarReprogramacion($P, [$paqQuieto], 'test-b2');
$assert($rh['aplicados'] === 0 && $rh['ignorados'] === 1,
    'Un amarre huérfano se ignora y se cuenta, no se reamarra: ' . json_encode($rh));
```

- [ ] **Step 2: Correr y verificar que falla**

```bash
docker compose exec app php tests/test_pdc_v2_reprogramacion.php
```

Expected: FAIL — `Call to undefined method ...::aplicarReprogramacion()`.

- [ ] **Step 3: Implementar**

```php
    /**
     * Aplica la reprogramación del cronograma SOLO a los paquetes que el usuario confirmó.
     *
     * Es la mitad «escribir» de `simularReprogramacion()`. Refresca la `fecha_ancla` del amarre
     * desde el cronograma en vivo y recalcula: sin ese refresco, `calcular()` vuelve a proyectar
     * contra la copia congelada del ancla y el desfase no se va nunca (el bug que midió B2, ver
     * `goals/pdc-preparar-b1/evidence/medicion-rematching-2026-07-29.md`).
     *
     * Un amarre cuyo frente ya no está en el cronograma se IGNORA y se cuenta: no hay fecha nueva
     * a la que moverlo, y elegirle otro frente es una decisión humana. Se devuelve en `ignorados`
     * para que la pantalla pueda decir cuántos quedaron pendientes de decisión en vez de callarlo.
     *
     * `fecha_real` no corre peligro aquí: `calcular()` hace upsert de `pdc_plan_paso` listando solo
     * las columnas programadas, y lo que no se lista MySQL lo conserva.
     *
     * @param list<int> $paqueteIds los confirmados en pantalla; una lista vacía no escribe nada
     * @return array{ok:true,aplicados:int,ignorados:int}
     */
    public function aplicarReprogramacion(int $projectId, array $paqueteIds, string $usuario): array
    {
        $pedidos = array_values(array_unique(array_map('intval', $paqueteIds)));
        if ($pedidos === []) {
            return ['ok' => true, 'aplicados' => 0, 'ignorados' => 0];
        }

        $frentes = [];
        foreach ($this->frentesDisponibles($projectId) as $f) {
            $frentes[$f['uniqueId']] = $f;
        }

        $aplicados = 0;
        $ignorados = 0;
        $this->db->beginTransaction();
        try {
            foreach ($pedidos as $paqueteId) {
                $r = $this->db->query(
                    'SELECT unique_id, fecha_ancla FROM pdc_paquete_frente WHERE project_id = ? AND paquete_id = ?',
                    [$projectId, $paqueteId],
                )->fetch(\PDO::FETCH_ASSOC);
                if ($r === false) {
                    $ignorados++;
                    continue;
                }
                $f = $frentes[(int) $r['unique_id']] ?? null;
                if ($f === null) {
                    $ignorados++; // huérfano: sin frente vivo no hay reprogramación que aplicar
                    continue;
                }
                if ($f['fechaInicio'] === (string) $r['fecha_ancla']) {
                    $ignorados++; // ya estaba al día; nada que mover
                    continue;
                }
                $this->db->query(
                    'UPDATE pdc_paquete_frente SET fecha_ancla = ? WHERE project_id = ? AND paquete_id = ?',
                    [$f['fechaInicio'], $projectId, $paqueteId],
                );
                $aplicados++;
            }
            $this->db->commit();
        } catch (\Throwable $t) {
            $this->db->rollBack();
            throw $t;
        }

        if ($aplicados > 0) {
            // `calcular()` abre su propia transacción por paquete, así que corre FUERA de la de
            // arriba: anidar transacciones con PDO no es lo que parece y el rollback interno se
            // llevaría por delante el refresco de las anclas ya confirmadas.
            $this->calcular($projectId, $usuario);
        }
        return ['ok' => true, 'aplicados' => $aplicados, 'ignorados' => $ignorados];
    }
```

- [ ] **Step 4: Correr y verificar que pasa**

```bash
docker compose exec app php tests/test_pdc_v2_reprogramacion.php
```

Expected: `=== OK ===`, rc 0.

- [ ] **Step 5: Regresión de la zona**

```bash
docker compose exec app php tests/test_pdc_v2_plan_fechas.php
docker compose exec app php tests/test_pdc_v2_seguimiento.php
docker compose exec app php tests/test_pdc_v2_amarre_cronograma.php
docker compose exec app vendor/bin/phpstan analyse src admin/src --memory-limit=1G
```

Expected: todo en verde, sin errores nuevos de phpstan.

- [ ] **Step 6: Commit** *(condicionado a autorización)*

```bash
git add src/Services/Pdc/PlanFechasService.php tests/test_pdc_v2_reprogramacion.php
git commit -m "fix(pdc): aplicar la reprogramacion refresca el ancla, que era lo que faltaba"
```

---

### Task 4: Endpoints `reprogramacion/simular` y `reprogramacion/aplicar`

**Files:**
- Modify: `src/Controllers/Api/PlanComprasPlanController.php`
- Modify: `public/index.php:230` (bloque A4, junto a `/plan/calcular`)
- Test: `tests/test_pdc_v2_rbac_paquetes.php` (ampliar con un rol permitido y uno denegado)

**Interfaces:**
- Consumes: `simularReprogramacion()`, `aplicarReprogramacion()`.
- Produces: `GET /plan-compras/api/plan/reprogramacion/simular` → `{ok:true, movidos:[...], huerfanos:[...]}`;
  `POST /plan-compras/api/plan/reprogramacion/aplicar` con cuerpo `{paqueteIds:[int]}` → `{ok:true, aplicados:int, ignorados:int}`.

- [ ] **Step 1: Añadir los dos métodos al controlador**

Después de `calcular()` en `src/Controllers/Api/PlanComprasPlanController.php`:

```php
    /**
     * GET /plan-compras/api/plan/reprogramacion/simular — el antes/después, sin escribir nada.
     *
     * Va con el guard de ESCRITURA aunque no escriba: simular es el primer paso de aplicar, y
     * enseñarle el delta completo a quien no puede aplicarlo solo produce una pantalla que promete
     * un botón que le va a decir 403.
     */
    public function simularReprogramacion(): void
    {
        $projectId = $this->guardEscritura();
        if ($projectId === null) {
            return;
        }
        $this->ok($this->service->simularReprogramacion($projectId));
    }

    /** POST /plan-compras/api/plan/reprogramacion/aplicar  {paqueteIds:[int]} */
    public function aplicarReprogramacion(): void
    {
        $projectId = $this->guardEscritura();
        if ($projectId === null) {
            return;
        }
        $body = $this->body();
        $ids = $body['paqueteIds'] ?? null;
        if (!is_array($ids)) {
            $this->fail('PAQUETES_INVALIDOS', 'Falta la lista de paquetes a reprogramar.', 422);
            return;
        }
        $limpios = [];
        foreach ($ids as $id) {
            $n = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($n === false) {
                $this->fail('PAQUETES_INVALIDOS', 'Hay un paquete inválido en la lista.', 422);
                return;
            }
            $limpios[] = $n;
        }
        $this->ok($this->service->aplicarReprogramacion($projectId, $limpios, $this->usuario()));
    }
```

- [ ] **Step 2: Registrar las rutas**

En `public/index.php`, en el bloque A4, **antes** de `$router->get('/plan-compras/api/plan', ...)`:

```php
$router->get('/plan-compras/api/plan/reprogramacion/simular', [\App\Controllers\Api\PlanComprasPlanController::class, 'simularReprogramacion']);
$router->post('/plan-compras/api/plan/reprogramacion/aplicar', [\App\Controllers\Api\PlanComprasPlanController::class, 'aplicarReprogramacion']);
```

- [ ] **Step 3: Verificar RBAC con un rol permitido y uno denegado**

```bash
docker compose exec app php tests/test_pdc_v2_rbac_paquetes.php
```

Expected: verde. Ampliarlo siguiendo el patrón que ya usa para `/plan/calcular`: un rol con `lps.paquetes_contratacion.editar` recibe 200; un `V` (Visualizador) recibe 403 en ambas rutas.

- [ ] **Step 4: Comprobar contra el contenedor servido**

```bash
curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8081/plan-compras/api/plan/reprogramacion/simular
```

Expected: 401/403 sin sesión (nunca 404 — un 404 significa que la ruta no quedó registrada).

- [ ] **Step 5: Commit** *(condicionado a autorización)*

```bash
git add src/Controllers/Api/PlanComprasPlanController.php public/index.php tests/test_pdc_v2_rbac_paquetes.php
git commit -m "feat(pdc): exponer simular y aplicar la reprogramacion"
```

---

### Task 5: La pestaña «Desfases» muestra el delta y pide confirmación

Hoy esa pestaña ofrece «Recalcular todo el plan», que —medido— no arregla el desfase que denuncia. Pasa a: **«Ver qué cambia»** → panel con el delta → **«Aplicar a los N paquetes»** / **«Cancelar»**.

**Files:**
- Create: `pdc-app/src/lib/reprogramacion.ts`
- Create: `pdc-app/src/lib/reprogramacion.test.ts`
- Modify: `pdc-app/src/lib/types.ts`
- Modify: `pdc-app/src/pages/PlanFechas.tsx:972-988` (bloque `seccion === 'desfases'`)

**Interfaces:**
- Consumes: `GET /plan-compras/api/plan/reprogramacion/simular`, `POST .../aplicar`.
- Produces: `resumenDelta(movidos): {paquetes:number, pasosProtegidos:number, atrasan:number, adelantan:number}` y `etiquetaMovimiento(m): string`.

- [ ] **Step 1: Añadir los tipos**

En `pdc-app/src/lib/types.ts`, junto a `Desfase`:

```ts
export type DeltaPaquete = {
  paqueteId: number
  nombre: string
  frenteNombre: string
  anclaActual: string
  anclaNueva: string
  diasMovidos: number
  arranqueActual: string | null
  arranqueNuevo: string
  pasosQueSeMueven: number
  pasosConFechaReal: number
}

export type HuerfanoReprogramacion = {
  paqueteId: number
  nombre: string
  frenteNombre: string
  anclaActual: string
}

export type SimulacionReprogramacion = {
  movidos: DeltaPaquete[]
  huerfanos: HuerfanoReprogramacion[]
}
```

- [ ] **Step 2: Escribir los tests que fallan**

Crear `pdc-app/src/lib/reprogramacion.test.ts`:

```ts
import { describe, expect, it } from 'vitest'
import { etiquetaMovimiento, resumenDelta } from './reprogramacion'
import type { DeltaPaquete } from './types'

const m = (over: Partial<DeltaPaquete> = {}): DeltaPaquete => ({
  paqueteId: 1, nombre: 'CONCRETO', frenteNombre: 'ESTRUCTURA',
  anclaActual: '2026-09-01', anclaNueva: '2026-09-22', diasMovidos: 21,
  arranqueActual: '2026-07-30', arranqueNuevo: '2026-08-20',
  pasosQueSeMueven: 7, pasosConFechaReal: 1, ...over,
})

describe('etiquetaMovimiento', () => {
  it('un frente atrasado dice que se atrasa y cuántos días', () => {
    expect(etiquetaMovimiento(m())).toBe('se atrasa 21 días: arranque 2026-07-30 → 2026-08-20')
  })
  it('un frente adelantado no dice «-9 días»', () => {
    expect(etiquetaMovimiento(m({ diasMovidos: -9, arranqueNuevo: '2026-07-21' })))
      .toBe('se adelanta 9 días: arranque 2026-07-30 → 2026-07-21')
  })
  it('un solo día va en singular', () => {
    expect(etiquetaMovimiento(m({ diasMovidos: 1, arranqueNuevo: '2026-07-31' })))
      .toBe('se atrasa 1 día: arranque 2026-07-30 → 2026-07-31')
  })
  it('sin arranque previo no se inventa un «desde»', () => {
    expect(etiquetaMovimiento(m({ arranqueActual: null })))
      .toBe('se atrasa 21 días: arranque 2026-08-20')
  })
})

describe('resumenDelta', () => {
  it('cuenta paquetes, pasos ya ocurridos y en qué dirección se mueven', () => {
    expect(resumenDelta([m(), m({ paqueteId: 2, diasMovidos: -3, pasosConFechaReal: 2 })]))
      .toEqual({ paquetes: 2, pasosProtegidos: 3, atrasan: 1, adelantan: 1 })
  })
  it('sin nada que mover, todo en cero', () => {
    expect(resumenDelta([])).toEqual({ paquetes: 0, pasosProtegidos: 0, atrasan: 0, adelantan: 0 })
  })
})
```

- [ ] **Step 3: Correr y verificar que falla**

```bash
cd pdc-app && npx vitest run src/lib/reprogramacion.test.ts
```

Expected: FAIL — no existe `./reprogramacion`.

- [ ] **Step 4: Implementar**

Crear `pdc-app/src/lib/reprogramacion.ts`:

```ts
import type { DeltaPaquete } from './types'

/**
 * El movimiento de un paquete en palabras. El signo de `diasMovidos` no se enseña nunca crudo:
 * «-9 días» se lee como un error, y la dirección es justo lo que hay que entender antes de aplicar.
 */
export function etiquetaMovimiento(m: DeltaPaquete): string {
  const dias = Math.abs(m.diasMovidos)
  const direccion = m.diasMovidos >= 0 ? 'se atrasa' : 'se adelanta'
  const unidad = dias === 1 ? 'día' : 'días'
  const arranque = m.arranqueActual === null
    ? `arranque ${m.arranqueNuevo}`
    : `arranque ${m.arranqueActual} → ${m.arranqueNuevo}`
  return `${direccion} ${dias} ${unidad}: ${arranque}`
}

/**
 * El titular del panel. `pasosProtegidos` son los pasos con fecha real: los que NO se van a mover.
 * Se cuentan y se dicen porque es la garantía que hace segura esta operación.
 */
export function resumenDelta(movidos: DeltaPaquete[]): {
  paquetes: number
  pasosProtegidos: number
  atrasan: number
  adelantan: number
} {
  return {
    paquetes: movidos.length,
    pasosProtegidos: movidos.reduce((n, m) => n + m.pasosConFechaReal, 0),
    atrasan: movidos.filter((m) => m.diasMovidos >= 0).length,
    adelantan: movidos.filter((m) => m.diasMovidos < 0).length,
  }
}
```

- [ ] **Step 5: Correr y verificar que pasa**

```bash
cd pdc-app && npx vitest run src/lib/reprogramacion.test.ts
```

Expected: 6 tests en verde.

- [ ] **Step 6: Reemplazar el cuerpo de la pestaña «Desfases»**

En `pdc-app/src/pages/PlanFechas.tsx`, añadir el estado y los manejadores junto a `onRecalcular`:

```tsx
  const [simulacion, setSimulacion] = useState<SimulacionReprogramacion | null>(null)

  const onSimularReprogramacion = async () => {
    dispatch({ type: 'OCUPADO' })
    try {
      const s = await apiGet<SimulacionReprogramacion>('/plan-compras/api/plan/reprogramacion/simular')
      setSimulacion(s)
      dispatch({ type: 'LISTO', mensaje: '' })
    } catch (e) {
      dispatch({ type: 'FALLO', mensaje: mensajeError(e) })
    }
  }

  // Cancelar no escribe nada: solo tira la simulación, que nunca tocó la base.
  const onCancelarReprogramacion = () => setSimulacion(null)

  const onAplicarReprogramacion = async () => {
    if (simulacion === null) return
    const ids = simulacion.movidos.map((m) => m.paqueteId)
    dispatch({ type: 'OCUPADO' })
    try {
      const r = await apiPost<{ aplicados: number; ignorados: number }>(
        '/plan-compras/api/plan/reprogramacion/aplicar',
        { paqueteIds: ids },
      )
      setSimulacion(null)
      dispatch({
        type: 'LISTO',
        mensaje: r.ignorados > 0
          ? `Reprogramados ${r.aplicados} paquete(s). ${r.ignorados} quedaron sin aplicar por no tener frente vivo.`
          : `Reprogramados ${r.aplicados} paquete(s).`,
      })
      cargar()
    } catch (e) {
      dispatch({ type: 'FALLO', mensaje: mensajeError(e) })
    }
  }
```

Y sustituir el bloque `seccion === 'desfases'` (líneas 972-988) por:

```tsx
      {seccion === 'desfases' && (
      <PanelPestana idBase="pdc-plan" id="desfases">
      <p className="pdc-sub">
        El cronograma se reprogramó después de amarrar estos paquetes. No se aplica solo: primero
        mira qué cambia y luego decides.
      </p>
      <ul className="pdc-paq-lista" data-testid="pdc-plan-desfases">
        {desfases.map((d) => (
          <li key={d.paqueteId}>
            <strong>{d.nombre}</strong>
            <span className="pdc-paq-meta">{etiquetaDesfase(d)}</span>
          </li>
        ))}
        {desfases.length === 0 && <li className="pdc-vacio">Ningún amarre quedó desactualizado.</li>}
      </ul>

      {desfases.length > 0 && simulacion === null && (
        <button
          type="button"
          className="pdc-paq-primario"
          data-testid="pdc-plan-simular-reprogramacion"
          disabled={ui.ocupado}
          onClick={() => void onSimularReprogramacion()}
        >
          Ver qué cambia
        </button>
      )}

      {simulacion !== null && (
        <div className="pdc-panel" data-testid="pdc-plan-delta-reprogramacion">
          {(() => {
            const r = resumenDelta(simulacion.movidos)
            return (
              <p data-testid="pdc-plan-delta-resumen">
                Se moverían <strong>{r.paquetes}</strong> paquete(s) — {r.atrasan} se atrasan y{' '}
                {r.adelantan} se adelantan. <strong>{r.pasosProtegidos}</strong> paso(s) ya
                ocurrieron y conservan su fecha real.
              </p>
            )
          })()}
          <ul className="pdc-paq-lista">
            {simulacion.movidos.map((m) => (
              <li key={m.paqueteId}>
                <strong>{m.nombre}</strong>
                <span className="pdc-paq-meta">{etiquetaMovimiento(m)}</span>
              </li>
            ))}
          </ul>
          {simulacion.huerfanos.length > 0 && (
            <p data-testid="pdc-plan-delta-huerfanos">
              {simulacion.huerfanos.length} paquete(s) apuntan a un frente que ya no está en el
              cronograma. No se reamarran solos: amárralos a mano desde la grilla.
            </p>
          )}
          <button
            type="button"
            className="pdc-paq-primario"
            data-testid="pdc-plan-aplicar-reprogramacion"
            disabled={ui.ocupado || simulacion.movidos.length === 0}
            onClick={() => void onAplicarReprogramacion()}
          >
            Aplicar a {simulacion.movidos.length} paquete(s)
          </button>
          <button
            type="button"
            data-testid="pdc-plan-cancelar-reprogramacion"
            onClick={onCancelarReprogramacion}
          >
            Cancelar
          </button>
        </div>
      )}
      </PanelPestana>
      )}
```

Añadir a los imports de la cabecera del archivo:

```tsx
import { etiquetaMovimiento, resumenDelta } from '../lib/reprogramacion'
```

y `SimulacionReprogramacion` al `import type { ... } from '../lib/types'`.

- [ ] **Step 7: Comprobar tipos y estilo**

```bash
cd pdc-app && npx tsc --noEmit && npx vitest run
npm run check:frontend
```

Expected: sin errores de tipos, toda la suite de vitest verde, biome limpio.

- [ ] **Step 8: Verificar en el navegador** (desktop 1180×820, dark)

Levantar el preview, entrar a `/plan-compras` → pestaña «Desfases» con un frente movido, y comprobar: «Ver qué cambia» pinta el delta sin escribir; «Cancelar» lo cierra y el plan sigue igual; «Aplicar» mueve las fechas y el aviso desaparece. Revisar consola y red.

- [ ] **Step 9: Commit** *(condicionado a autorización)*

```bash
git add pdc-app/src/lib/reprogramacion.ts pdc-app/src/lib/reprogramacion.test.ts pdc-app/src/lib/types.ts pdc-app/src/pages/PlanFechas.tsx
git commit -m "feat(pdc): el desfase se mira antes de aplicarse"
```

---

### Task 6: El tablero de vencimientos avisa de que el cronograma cambió

**Files:**
- Modify: `src/Services/Pdc/SeguimientoService.php`
- Modify: `src/Controllers/Api/PlanComprasSeguimientoController.php`
- Modify: `pdc-app/src/pages/Seguimiento.tsx`
- Test: `tests/test_pdc_v2_seguimiento.php` (ampliar)

**Interfaces:**
- Produces: `SeguimientoService::paquetesDesactualizados(int $projectId): list<int>` — los `paqueteId` cuyo amarre no coincide con el cronograma. El controlador lo expone como `desactualizados` junto a `resumen`.

- [ ] **Step 1: Escribir el test que falla**

Añadir a `tests/test_pdc_v2_seguimiento.php`, tras mover el frente de un paquete ya calculado:

```php
$desact = $seg->paquetesDesactualizados($P);
$assert(in_array($paq, $desact, true),
    'El tablero sabe que este paquete se calculó contra un cronograma que ya cambió.');
$assert(!in_array($paqQuieto, $desact, true),
    'Un paquete cuyo frente no se movió no aparece como desactualizado.');
```

- [ ] **Step 2: Correr y verificar que falla**

```bash
docker compose exec app php tests/test_pdc_v2_seguimiento.php
```

Expected: FAIL — método inexistente.

- [ ] **Step 3: Implementar**

En `src/Services/Pdc/SeguimientoService.php`:

```php
    /**
     * Qué paquetes del tablero se calcularon contra un cronograma que ya cambió.
     *
     * Se delega en `PlanFechasService::desfases()` en vez de repetir la comparación aquí: si algún
     * día cambia qué cuenta como desfase, el tablero y la pantalla del plan no pueden discrepar.
     * Sin columna de estado que mantener al día — se deduce comparando, como decidió el diseño B2.
     *
     * @return list<int>
     */
    public function paquetesDesactualizados(int $projectId): array
    {
        return array_values(array_map(
            static fn (array $d): int => $d['paqueteId'],
            (new PlanFechasService($this->db))->desfases($projectId),
        ));
    }
```

Añadir `use App\Services\Pdc\PlanFechasService;` si no está — están en el mismo namespace, así que basta con referenciarla.

- [ ] **Step 4: Exponerlo en el endpoint**

En `src/Controllers/Api/PlanComprasSeguimientoController.php::resumen()`:

```php
        $this->ok([
            'resumen' => $this->service->resumen($projectId),
            // El tablero necesita poder decir «esto se calculó contra un cronograma viejo». Va como
            // lista aparte y no como columna del resumen: es una propiedad del amarre, no del avance.
            'desactualizados' => $this->service->paquetesDesactualizados($projectId),
        ]);
```

- [ ] **Step 5: Pintar el aviso**

En `pdc-app/src/pages/Seguimiento.tsx`, guardar `desactualizados` en estado al cargar y, encima de la tabla:

```tsx
      {desactualizados.length > 0 && (
        <p className="pdc-plan-aviso-recalcular" data-testid="pdc-seg-aviso-cronograma" role="status">
          {desactualizados.length === 1
            ? '1 paquete se calculó contra un cronograma que ya cambió: sus fechas de aquí abajo pueden estar viejas. Revísalo en «Plan» → «Desfases».'
            : `${desactualizados.length} paquetes se calcularon contra un cronograma que ya cambió: sus fechas de aquí abajo pueden estar viejas. Revísalos en «Plan» → «Desfases».`}
        </p>
      )}
```

- [ ] **Step 6: Verificar**

```bash
docker compose exec app php tests/test_pdc_v2_seguimiento.php
cd pdc-app && npx tsc --noEmit && npx vitest run
npm run check:frontend
```

Expected: todo verde.

- [ ] **Step 7: Commit** *(condicionado a autorización)*

```bash
git add src/Services/Pdc/SeguimientoService.php src/Controllers/Api/PlanComprasSeguimientoController.php pdc-app/src/pages/Seguimiento.tsx tests/test_pdc_v2_seguimiento.php
git commit -m "feat(pdc): el tablero avisa cuando sus fechas son de un cronograma viejo"
```

---

### Task 7: Cerrar con regresión y evidencia

**Files:**
- Modify: `tests/browser/pdc-v2-plan.spec.mjs`
- Modify: `goals/pdc-preparar-b1/estado-olas.md`

- [ ] **Step 1: Añadir el e2e del flujo completo**

En `tests/browser/pdc-v2-plan.spec.mjs`, un caso que: mueva un frente, entre a «Desfases», pulse `pdc-plan-simular-reprogramacion`, compruebe que `pdc-plan-delta-resumen` dice cuántos paquetes y cuántos pasos protegidos, pulse `pdc-plan-cancelar-reprogramacion` y verifique que las fechas de la grilla **no** cambiaron; luego repita y pulse `pdc-plan-aplicar-reprogramacion` y verifique que sí cambiaron y que el conteo de la pestaña «Desfases» bajó a 0.

*(Recordatorio: un test nuevo bajo `tests/browser/` no se commitea si no se le añade su `!` en `.gitignore`.)*

- [ ] **Step 2: Correr toda la regresión**

```bash
docker compose exec app php tests/test_pdc_v2_plan_fechas.php
docker compose exec app php tests/test_pdc_v2_seguimiento.php
docker compose exec app php tests/test_pdc_v2_reprogramacion.php
docker compose exec app php tests/test_pdc_v2_amarre_cronograma.php
docker compose exec app php tests/test_pdc_v2_pasos_configurables.php
docker compose exec app vendor/bin/phpstan analyse src admin/src --memory-limit=1G
cd pdc-app && npx vitest run && npx tsc --noEmit
npx playwright test tests/browser/pdc-v2-plan.spec.mjs --workers=1
npx playwright test e2e/pdc-v2-plan.spec.mjs --config e2e/playwright.config.mjs --workers=1
```

Expected: todo verde. Cualquier rojo se investiga con `superpowers:systematic-debugging`; no se ajusta un test para taparlo.

- [ ] **Step 3: Anotar el cierre en el goal**

En `goals/pdc-preparar-b1/estado-olas.md`, marcar el entregable «Re-matching al reprogramar» con: el recorte de alcance que produjo la medición, el bug encontrado y corregido, y el enlace a `evidence/medicion-rematching-2026-07-29.md`.

- [ ] **Step 4: `superpowers:verification-before-completion`** antes de decir que está hecho.

---

## Self-Review

**Cobertura del spec:**

| Requisito del spec | Tarea |
|---|---|
| Hecho 1 — está escrito qué hace hoy el sistema, con evidencia | **Ya entregado** — `evidence/medicion-rematching-2026-07-29.md` |
| Hecho 2 — mover un frente hace que el plan lo diga | Ya existía (pestaña «Desfases» + estado `desfasado`); Task 6 lo extiende al tablero de vencimientos |
| Hecho 3 — el delta antes de aplicar; cancelar no escribe | Tasks 2, 4, 5 |
| Hecho 4 — un paso con fecha real la conserva, verificado sobre datos | Task 3 Step 1 (assert sobre la base) |
| Hecho 5 — frente eliminado deja el paquete sin frente y pide confirmación | Tasks 2 y 3 (`huerfanos` / `ignorados`) |
| Hecho 6 — regresión verde | Task 7 |
| «No entra: reamarrar solo / correo / historial» | No hay ninguna tarea que los toque |
| «Sin tablas nuevas» | Ninguna migración en el plan |

**Consistencia de tipos:** `proyectar()` (Task 1) devuelve `duracionRef` y así lo consume `calcular()`; `simularReprogramacion()` (Task 2) produce las claves que `DeltaPaquete` (Task 5) declara una a una; `aplicarReprogramacion()` (Task 3) devuelve `aplicados`/`ignorados` y así los lee el controlador (Task 4) y la pantalla (Task 5).

**Riesgo residual:** la Task 1 es un refactor sobre el método más delicado del módulo. Su única red son los tests existentes, por eso el Step 1 captura la línea base antes de tocar nada y el Step 4 exige que no se mueva ni un assert.
