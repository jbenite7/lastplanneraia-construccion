# Los cuatro diferidos de A4.1 (configuración de pasos) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) o superpowers:executing-plans para ejecutar tarea por tarea. Los pasos usan checkbox (`- [ ]`).

**Goal:** Que montar la segunda obra no obligue a reconfigurar los pasos a mano ni a entrar a la base de datos para cambiar un número de días — sin que una obra sin configurar deje de tener sus siete pasos de siempre.

**Architecture:** Todo cuelga de `PasosContratacionService` y de la pantalla «Pasos de contratación». Copiar es una **copia explícita y puntual** (leer los `pdc_proyecto_pasos` del origen y reescribir los del destino con el `guardar()` que ya existe), no un vínculo vivo. Las duraciones se editan sobre `general_dias_procesos_contratacion`, la misma tabla que ya edita `/contratos`, pero desde el PDC v2 y con el permiso de reglas. El historial es una tabla nueva de solo-anexar.

**Tech Stack:** PHP 8.3 sin framework, MySQL 8 vía `Database` (PDO), React + Vite en `pdc-app/`, tests PHP autoejecutables en `tests/`.

## Global Constraints

- **Cero regresión:** una obra sin configurar sigue teniendo los siete pasos de siempre — el contrato que A4.1 demostró contra las 11 filas y 77 pasos de Da Porto. `PasosContratacionService::deProyecto()` debe seguir cayendo en `porDefecto()` cuando no hay filas propias.
- **Cada diferido se ejecuta por separado.** Comparten pantalla, no son un solo trabajo.
- **Copiar es puntual, nunca un vínculo vivo:** copiar y luego editar el destino sin que el origen se entere.
- **La pantalla muestra qué se va a copiar antes de copiarlo.** Si el origen está a medias, la copia hereda la basura; el usuario tiene que poder verlo antes.
- **RBAC:** copiar y duraciones exigen `lps.paquetes_contratacion.reglas` (el mismo `guardReglas()` que ya protege `guardarPasos()`), más CSRF `plan_compras_v2`. Toda consulta aislada por `project_id`.
- **UI: solo desktop ≥1180 px y solo dark mode.** Viewport canónico 1180×820. Nada de mobile, tablet ni tema `linen`.
- **No hacer commit, push ni deploy** salvo petición explícita del usuario.

## Orden y estado

| # | Diferido | Prioridad | Tarea |
|---|---|---|---|
| 2 | Copiar la configuración entre obras | Alta — la pide el aeropuerto | Tasks 1-2 |
| 4 | Duraciones del catálogo legacy editables | Media | Tasks 3-4 |
| 1 | Listas de pasos por modalidad | Media | **Task 5 — NO SE CONSTRUYE**, falta la precondición |
| 3 | Historial de versiones de la configuración | Baja | Task 6 |

## Recorte de alcance ya identificado (diferido nº 4)

**El camino de escritura sobre `general_dias_procesos_contratacion` ya existe**, pero en otro módulo: `ContratosApiController::guardarDuracionesContratacion()` (`src/Controllers/Api/ContratosApiController.php:625-690`) hace el upsert de las siete columnas, guardado por `lps.contratos.editar` y **con clave `(paqueteContratacion, tipoPaquete)`**.

Lo que falta no es escribir: es (a) llegar ahí desde la pantalla de pasos del PDC v2, donde el paquete apunta a una fila por `duracion_ref` (un id, no un par de nombres), (b) con el permiso de reglas y no el de contratos, y (c) recalculando el plan después, porque cambiar un número mueve las fechas de toda la obra. Las Tasks 3-4 hacen exactamente eso y **no duplican el upsert**: lo aíslan en un servicio del PDC.

## File Structure

| Archivo | Responsabilidad |
|---|---|
| `src/Services/Pdc/PasosContratacionService.php` (modificar) | `origenesDisponibles()`, `previsualizarCopia()`, `copiarDesde()` |
| `src/Services/Pdc/DuracionesCatalogoService.php` (crear) | Leer y actualizar las siete columnas de `general_dias_procesos_contratacion` por `duracion_ref` |
| `src/Controllers/Api/PlanComprasPlanController.php` (modificar) | Endpoints de copia y de duraciones |
| `public/index.php` (modificar) | Registrar las rutas nuevas |
| `pdc-app/src/lib/types.ts` (modificar) | `OrigenCopia`, `PreviewCopia`, `DuracionCatalogo` |
| `pdc-app/src/lib/pasosState.ts` (modificar) | Lógica pura del panel de copia |
| `pdc-app/src/pages/PasosContratacion.tsx` (modificar) | Panel «Copiar de otra obra» y edición de duraciones |
| `tests/test_pdc_v2_pasos_copiar.php` (crear) | Copia + no-vínculo-vivo + cero regresión |
| `tests/test_pdc_v2_duraciones_editables.php` (crear) | Cambiar un día mueve solo la fecha que dependía de él; rol sin permiso → 403 |
| `database/migrations/20260730_pdc_pasos_historial.sql` (crear) | Solo en la Task 6 |
| `goals/pdc-preparar-b1/evidence/listas-por-modalidad-no-se-construye.md` (crear) | Task 5 |

---

### Task 1: Copiar la configuración de pasos — el servicio

**Files:**
- Modify: `src/Services/Pdc/PasosContratacionService.php`
- Test: `tests/test_pdc_v2_pasos_copiar.php` (crear)

**Interfaces:**
- Produces:
  - `public function origenesDisponibles(int $projectIdActual, int $userId): list<array{projectId:int,nombre:string,pasos:int}>` — las obras que ese usuario puede ver y que **sí** tienen configuración propia. La obra actual nunca aparece.
  - `public function previsualizarCopia(int $origenId): array{pasos: list<array{clave:string,nombre:string,alias:string,diasFijos:?int,tieneCatalogo:bool}>, incompleta: bool}` — qué se copiaría. `incompleta` es true si algún paso sin respaldo en el catálogo se quedó sin días fijos.
  - `public function copiarDesde(int $origenId, int $destinoId, string $usuario): array{ok:bool,code?:string,mensaje?:string,pasos?:int}`

- [ ] **Step 1: Escribir los tests que fallan**

Crear `tests/test_pdc_v2_pasos_copiar.php` (patrón de `tests/test_pdc_v2_pasos_configurables.php`: proyectos sintéticos 999960 origen y 999961 destino, `$limpiar()` al principio y al final):

```php
$svc = new PasosContratacionService($db);
$A = 999960; $B = 999961;

// El origen se configura con cinco pasos, uno de ellos con alias y días fijos.
$svc->guardar($A, [
    ['clave' => 'elaboracion_pliegos'],
    ['clave' => 'recibo_propuestas'],
    ['clave' => 'cuadros_comparativos'],
    ['clave' => 'legalizacion'],
    ['clave' => 'insumos_obra', 'alias' => 'Llegada a obra'],
], 'test-copia');

// --- previsualizar: se ve QUÉ se va a copiar antes de copiarlo ---
$prev = $svc->previsualizarCopia($A);
$assert(count($prev['pasos']) === 5, 'La vista previa muestra los cinco pasos del origen: ' . count($prev['pasos']));
$assert($prev['pasos'][4]['alias'] === 'Llegada a obra', 'La vista previa muestra el alias, no solo la clave.');
$assert($prev['incompleta'] === false, 'Una configuración completa no se marca como incompleta.');

// --- cero regresión: el destino, sin configurar, tiene los siete de siempre ---
$assert(count($svc->deProyecto($B)) === 7, 'Antes de copiar, el destino tiene los siete por defecto.');
$assert($svc->configurado($B) === false, 'Y no figura como configurado.');

// --- copiar ---
$r = $svc->copiarDesde($A, $B, 'test-copia');
$assert($r['ok'] === true && $r['pasos'] === 5, 'Se copiaron cinco pasos: ' . json_encode($r));

$pa = $svc->deProyecto($A);
$pb = $svc->deProyecto($B);
$assert(array_column($pa, 'clave') === array_column($pb, 'clave'), 'B queda con la misma lista y el mismo orden que A.');
$assert(array_column($pa, 'nombre') === array_column($pb, 'nombre'), 'Y con los mismos alias.');

// --- copia puntual, NO vínculo vivo ---
$svc->guardar($B, [['clave' => 'elaboracion_pliegos'], ['clave' => 'legalizacion']], 'test-copia');
$assert(count($svc->deProyecto($B)) === 2, 'B se puede editar después de copiar: ' . count($svc->deProyecto($B)));
$assert(count($svc->deProyecto($A)) === 5, 'Y editar B no cambia A: ' . count($svc->deProyecto($A)));

// --- un origen sin configurar no se puede copiar: copiaría «los siete por defecto» disfrazados ---
$r2 = $svc->copiarDesde(999962, $B, 'test-copia');
$assert($r2['ok'] === false && $r2['code'] === 'ORIGEN_SIN_CONFIGURAR',
    'Copiar de una obra sin configuración propia se rechaza: ' . json_encode($r2));

// --- copiarse a sí misma no tiene sentido y no se permite ---
$assert($svc->copiarDesde($A, $A, 'test-copia')['code'] === 'ORIGEN_ES_DESTINO', 'Una obra no se copia a sí misma.');

// --- orígenes disponibles: solo obras que el usuario ve y que están configuradas ---
$or = $svc->origenesDisponibles($B, $userIdConAmbas);
$assert(in_array($A, array_column($or, 'projectId'), true), 'A es un origen disponible para quien lo ve.');
$assert(!in_array($B, array_column($or, 'projectId'), true), 'La obra actual nunca se ofrece como origen.');
$assert(!in_array(999962, array_column($or, 'projectId'), true), 'Una obra sin configurar no se ofrece como origen.');
$assert($svc->origenesDisponibles($B, $userIdSinAcceso) === [], 'Quien no es miembro de A no lo ve como origen.');
```

- [ ] **Step 2: Correr y verificar que falla**

```bash
docker compose exec app php tests/test_pdc_v2_pasos_copiar.php
```

Expected: FAIL — `Call to undefined method ...::origenesDisponibles()`.

- [ ] **Step 3: Implementar los tres métodos**

En `src/Services/Pdc/PasosContratacionService.php`:

```php
    /**
     * Obras de las que se puede copiar: las que este usuario ve Y tienen configuración propia.
     *
     * Se excluyen las obras sin filas en `pdc_proyecto_pasos` a propósito. Ofrecerlas copiaría «los
     * siete por defecto» como si fueran una decisión de esa obra, y a partir de ahí el destino
     * dejaría de seguir el proceso por defecto de la empresa aunque nadie lo hubiera elegido.
     *
     * @return list<array{projectId:int,nombre:string,pasos:int}>
     */
    public function origenesDisponibles(int $projectIdActual, int $userId): array
    {
        $rows = $this->db->query(
            'SELECT p.Id AS project_id, p.Proyecto_Proceso AS nombre, COUNT(pp.id) AS pasos
             FROM project_members pm
             JOIN general_proyectos_procesos p ON p.Id = pm.project_id
             JOIN pdc_proyecto_pasos pp ON pp.project_id = p.Id
             WHERE pm.user_id = ? AND p.Id <> ? AND p.Activo = 1
             GROUP BY p.Id, p.Proyecto_Proceso
             ORDER BY p.Proyecto_Proceso',
            [$userId, $projectIdActual],
        )->fetchAll(\PDO::FETCH_ASSOC);

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'projectId' => (int) $r['project_id'],
                'nombre' => (string) $r['nombre'],
                'pasos' => (int) $r['pasos'],
            ];
        }
        return $out;
    }

    /**
     * Qué se copiaría, para poder enseñarlo antes de copiarlo.
     *
     * `incompleta` marca el riesgo que registró el diseño: si la obra origen quedó a medias, la
     * copia hereda ese estado. Se calcula igual que la validación de `guardar()` —un paso sin
     * respaldo en el catálogo necesita días fijos—, así que lo que aquí se marca en amarillo es
     * exactamente lo que allí sería un error.
     *
     * @return array{pasos: list<array{clave:string,nombre:string,alias:string,diasFijos:?int,tieneCatalogo:bool}>, incompleta: bool}
     */
    public function previsualizarCopia(int $origenId): array
    {
        $rows = $this->db->query(
            'SELECT c.clave, c.nombre, c.col_legacy, p.alias, p.dias_fijos
             FROM pdc_proyecto_pasos p
             JOIN general_pasos_contratacion c ON c.id = p.paso_id
             WHERE p.project_id = ? AND c.activo = 1
             ORDER BY p.orden, p.id',
            [$origenId],
        )->fetchAll(\PDO::FETCH_ASSOC);

        $legales = self::columnasLegacy();
        $pasos = [];
        $incompleta = false;
        foreach ($rows as $r) {
            $col = $r['col_legacy'] === null ? null : (string) $r['col_legacy'];
            $tieneCatalogo = $col !== null && in_array($col, $legales, true);
            $dias = $r['dias_fijos'] === null ? null : (int) $r['dias_fijos'];
            if (!$tieneCatalogo && $dias === null) {
                $incompleta = true;
            }
            $pasos[] = [
                'clave' => (string) $r['clave'],
                'nombre' => (string) $r['nombre'],
                'alias' => trim((string) $r['alias']),
                'diasFijos' => $dias,
                'tieneCatalogo' => $tieneCatalogo,
            ];
        }
        return ['pasos' => $pasos, 'incompleta' => $incompleta];
    }

    /**
     * Copia la configuración de una obra a otra. Puntual, no un vínculo vivo.
     *
     * Se reutiliza `guardar()` en vez de un INSERT ... SELECT: así la copia pasa por exactamente las
     * mismas validaciones que una configuración escrita a mano (paso desconocido, repetido, días
     * fijos obligatorios), y no hay forma de meter por la puerta de atrás una configuración que la
     * pantalla habría rechazado. Después de esto los dos proyectos son independientes: editar el
     * destino no toca el origen, porque no queda ninguna referencia entre ellos.
     *
     * @return array{ok:bool,code?:string,mensaje?:string,pasos?:int}
     */
    public function copiarDesde(int $origenId, int $destinoId, string $usuario): array
    {
        if ($origenId === $destinoId) {
            return ['ok' => false, 'code' => 'ORIGEN_ES_DESTINO', 'mensaje' => 'Una obra no puede copiarse a sí misma.'];
        }
        if (!$this->configurado($origenId)) {
            return [
                'ok' => false, 'code' => 'ORIGEN_SIN_CONFIGURAR',
                'mensaje' => 'Esa obra no tiene un proceso propio: usa el proceso por defecto de la empresa, que esta obra ya tiene.',
            ];
        }
        $pasos = [];
        foreach ($this->previsualizarCopia($origenId)['pasos'] as $p) {
            $pasos[] = ['clave' => $p['clave'], 'alias' => $p['alias'], 'diasFijos' => $p['diasFijos']];
        }
        return $this->guardar($destinoId, $pasos, $usuario);
    }
```

- [ ] **Step 4: Correr y verificar que pasa**

```bash
docker compose exec app php tests/test_pdc_v2_pasos_copiar.php
docker compose exec app php tests/test_pdc_v2_pasos_configurables.php
docker compose exec app vendor/bin/phpstan analyse src admin/src --memory-limit=1G
```

Expected: los dos tests verdes, phpstan sin errores nuevos.

- [ ] **Step 5: Commit** *(condicionado a autorización)*

```bash
git add src/Services/Pdc/PasosContratacionService.php tests/test_pdc_v2_pasos_copiar.php
git commit -m "feat(pdc): copiar la configuracion de pasos de una obra a otra"
```

---

### Task 2: Copiar — endpoints y pantalla

**Files:**
- Modify: `src/Controllers/Api/PlanComprasPlanController.php`
- Modify: `public/index.php`
- Modify: `pdc-app/src/lib/types.ts`, `pdc-app/src/pages/PasosContratacion.tsx`

**Interfaces:**
- Consumes: `origenesDisponibles()`, `previsualizarCopia()`, `copiarDesde()`.
- Produces:
  - `GET /plan-compras/api/plan/pasos/origenes` → `{ok:true, origenes:[{projectId,nombre,pasos}]}`
  - `GET /plan-compras/api/plan/pasos/copia-preview?origenId=N` → `{ok:true, pasos:[...], incompleta:bool}`
  - `POST /plan-compras/api/plan/pasos/copiar` `{origenId:int}` → `{ok:true, pasos:int, calculados:int, sinDuracion:int}`

- [ ] **Step 1: Añadir los tres métodos al controlador**

```php
    /** GET /plan-compras/api/plan/pasos/origenes — de qué obras puede copiar QUIEN pregunta. */
    public function origenesPasos(): void
    {
        $projectId = $this->guardReglas();
        if ($projectId === null) {
            return;
        }
        $userId = SesionUsuario::resolverId($this->db);
        if ($userId === null) {
            $this->fail('SIN_USUARIO', 'No se pudo identificar al usuario de la sesión.', 409);
            return;
        }
        $this->ok(['origenes' => (new PasosContratacionService($this->db))->origenesDisponibles($projectId, $userId)]);
    }

    /**
     * GET /plan-compras/api/plan/pasos/copia-preview?origenId=N — qué se copiaría.
     *
     * Se comprueba que el usuario sea miembro de la obra origen ANTES de enseñar su configuración:
     * un `origenId` llega del cliente y sin este filtro la pantalla sería una forma de leer cómo
     * trabaja una obra a la que no se tiene acceso.
     */
    public function previewCopiaPasos(): void
    {
        $projectId = $this->guardReglas();
        if ($projectId === null) {
            return;
        }
        $origenId = filter_var($_GET['origenId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($origenId === false) {
            $this->fail('ORIGEN_INVALIDO', 'origenId inválido.', 422);
            return;
        }
        $userId = SesionUsuario::resolverId($this->db);
        $svc = new PasosContratacionService($this->db);
        $permitidos = array_column($svc->origenesDisponibles($projectId, (int) $userId), 'projectId');
        if (!in_array($origenId, $permitidos, true)) {
            $this->fail('ORIGEN_NO_DISPONIBLE', 'No tienes acceso a esa obra o no tiene un proceso propio.', 403);
            return;
        }
        $this->ok($svc->previsualizarCopia($origenId));
    }

    /**
     * POST /plan-compras/api/plan/pasos/copiar  {origenId}
     *
     * Recalcula después de copiar, por la misma razón que `guardarPasos()`: la configuración nueva
     * conviviendo con el plan viejo pondría en pantalla unas fechas que ya no son las que produce
     * esa configuración.
     */
    public function copiarPasos(): void
    {
        $projectId = $this->guardReglas();
        if ($projectId === null) {
            return;
        }
        $origenId = filter_var($this->body()['origenId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($origenId === false) {
            $this->fail('ORIGEN_INVALIDO', 'Falta la obra de la que copiar.', 422);
            return;
        }
        $userId = SesionUsuario::resolverId($this->db);
        $svc = new PasosContratacionService($this->db);
        $permitidos = array_column($svc->origenesDisponibles($projectId, (int) $userId), 'projectId');
        if (!in_array($origenId, $permitidos, true)) {
            $this->fail('ORIGEN_NO_DISPONIBLE', 'No tienes acceso a esa obra o no tiene un proceso propio.', 403);
            return;
        }
        $r = $svc->copiarDesde($origenId, $projectId, $this->usuario());
        if (!$r['ok']) {
            $this->fail($r['code'] ?? 'COPIA_INVALIDA', $r['mensaje'] ?? 'No se pudo copiar.', 422);
            return;
        }
        $this->ok(array_merge($r, $this->service->calcular($projectId, $this->usuario())));
    }
```

Añadir `use App\Support\SesionUsuario;` (o el namespace real que use `PlanComprasApiController`) si no está importado.

- [ ] **Step 2: Registrar las rutas**

En `public/index.php`, en el bloque A4.1, **antes** de `$router->post('/plan-compras/api/plan/pasos', ...)`:

```php
$router->get('/plan-compras/api/plan/pasos/origenes', [\App\Controllers\Api\PlanComprasPlanController::class, 'origenesPasos']);
$router->get('/plan-compras/api/plan/pasos/copia-preview', [\App\Controllers\Api\PlanComprasPlanController::class, 'previewCopiaPasos']);
$router->post('/plan-compras/api/plan/pasos/copiar', [\App\Controllers\Api\PlanComprasPlanController::class, 'copiarPasos']);
```

- [ ] **Step 3: Añadir los tipos**

En `pdc-app/src/lib/types.ts`:

```ts
export type OrigenCopia = { projectId: number; nombre: string; pasos: number }

export type PasoPreviewCopia = {
  clave: string
  nombre: string
  alias: string
  diasFijos: number | null
  tieneCatalogo: boolean
}

export type PreviewCopia = { pasos: PasoPreviewCopia[]; incompleta: boolean }
```

- [ ] **Step 4: Añadir el panel a la pantalla**

En `pdc-app/src/pages/PasosContratacion.tsx`, un bloque «Copiar de otra obra» con: un `<select>` de `origenes` (cada opción dice `nombre (N pasos)`), un botón «Ver qué se copiaría» que llama a `copia-preview`, la lista resultante con alias y días fijos, el aviso cuando `incompleta` es true, y **solo entonces** el botón «Copiar a esta obra», más «Cancelar» que cierra el panel sin escribir.

```tsx
{preview !== null && (
  <div className="pdc-panel" data-testid="pdc-pasos-preview-copia">
    <p>Se copiarían estos {preview.pasos.length} pasos, reemplazando el proceso actual de esta obra:</p>
    <ol data-testid="pdc-pasos-preview-lista">
      {preview.pasos.map((p) => (
        <li key={p.clave}>
          {p.alias !== '' ? `${p.alias} (${p.nombre})` : p.nombre}
          {p.diasFijos !== null && <span className="pdc-paq-meta">{p.diasFijos} día(s) fijos</span>}
        </li>
      ))}
    </ol>
    {preview.incompleta && (
      <p data-testid="pdc-pasos-preview-incompleta">
        Ojo: esa obra tiene algún paso sin duración definida. Al copiarla, esta obra hereda ese hueco.
      </p>
    )}
    <button type="button" data-testid="pdc-pasos-copiar-confirmar" disabled={ui.ocupado} onClick={() => void onCopiar()}>
      Copiar a esta obra
    </button>
    <button type="button" data-testid="pdc-pasos-copiar-cancelar" onClick={() => setPreview(null)}>
      Cancelar
    </button>
  </div>
)}
```

- [ ] **Step 5: Verificar**

```bash
cd pdc-app && npx tsc --noEmit && npx vitest run
npm run check:frontend
docker compose exec app php tests/test_pdc_v2_rbac_pasos.php
```

Expected: verde. Ampliar `test_pdc_v2_rbac_pasos.php` con las tres rutas nuevas: un rol con `lps.paquetes_contratacion.reglas` → 200; un `V` → 403.

- [ ] **Step 6: Verificar en el navegador** (1180×820, dark)

Configurar la obra A, entrar a la obra B → «Pasos de contratación» → copiar de A, y comprobar sobre la pantalla los tres hechos: B queda igual que A, editar B no cambia A, y recalcular el plan de B usa los pasos copiados. Revisar consola y red.

- [ ] **Step 7: Commit** *(condicionado a autorización)*

```bash
git add src/Controllers/Api/PlanComprasPlanController.php public/index.php pdc-app/src/lib/types.ts pdc-app/src/pages/PasosContratacion.tsx tests/test_pdc_v2_rbac_pasos.php
git commit -m "feat(pdc): la pantalla de pasos deja copiar de otra obra viendo antes que copia"
```

---

### Task 3: Duraciones del catálogo legacy editables — el servicio

**Files:**
- Create: `src/Services/Pdc/DuracionesCatalogoService.php`
- Test: `tests/test_pdc_v2_duraciones_editables.php` (crear)

**Interfaces:**
- Produces:
  - `public function deProyecto(int $projectId): list<array{duracionRef:int,paqueteContratacion:string,tipoPaquete:string,dias:array<string,?int>,paquetesQueLaUsan:int}>` — las filas del catálogo que los paquetes de esta obra usan realmente.
  - `public function actualizar(int $duracionRef, array $dias, string $usuario): array{ok:bool,code?:string,mensaje?:string}`

- [ ] **Step 1: Escribir los tests que fallan**

Crear `tests/test_pdc_v2_duraciones_editables.php` (proyecto 999970, fixture del mismo patrón que `tests/medicion_rematching_reprogramacion.php`: un paquete con desglose completo, amarrado y calculado):

```php
$svc = new DuracionesCatalogoService($db);
$plan = new PlanFechasService($db);

// Solo se ofrecen las filas que esta obra usa de verdad.
$lista = $svc->deProyecto($P);
$assert(in_array($durRef, array_column($lista, 'duracionRef'), true), 'La duración que usa el paquete se ofrece.');
$assert(!in_array($durRefAjeno, array_column($lista, 'duracionRef'), true),
    'Una fila del catálogo que ningún paquete de esta obra usa no se ofrece.');

$antes = $db->query(
    'SELECT fecha_arranque, dias_totales FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?',
    [$P, $paq],
)->fetch(PDO::FETCH_ASSOC);
$assert((int) $antes['dias_totales'] === 33, 'Punto de partida: 33 días.');

// Cambiar un día mueve la fecha que dependía de él.
$r = $svc->actualizar($durRef, ['diasFabricacion' => 15], 'test-dur');
$assert($r['ok'] === true, 'Se actualizó: ' . json_encode($r));
$plan->calcular($P, 'test-dur');

$despues = $db->query(
    'SELECT fecha_arranque, dias_totales FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?',
    [$P, $paq],
)->fetch(PDO::FETCH_ASSOC);
$assert((int) $despues['dias_totales'] === 38, 'El total subió cinco días: ' . $despues['dias_totales']);
$assert($despues['fecha_arranque'] === '2026-07-25', 'Y el arranque se adelantó cinco días: ' . $despues['fecha_arranque']);

// «y solo esa»: el paquete que NO usa esa duración no se movió.
$otro = $db->query(
    'SELECT dias_totales FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?',
    [$P, $paqOtro],
)->fetchColumn();
$assert((int) $otro === $diasTotalesOtroAntes, 'El paquete con otra duración no se movió: ' . $otro);

// Un día negativo no entra.
$assert($svc->actualizar($durRef, ['diasFabricacion' => -1], 'test-dur')['code'] === 'DIAS_INVALIDOS',
    'Un número negativo de días se rechaza.');

// Una columna que no es del catálogo no entra (la lista blanca es la misma de A4.1).
$assert($svc->actualizar($durRef, ['diasInventados' => 3], 'test-dur')['code'] === 'COLUMNA_DESCONOCIDA',
    'Una columna fuera de la lista blanca se rechaza.');
```

- [ ] **Step 2: Correr y verificar que falla**

```bash
docker compose exec app php tests/test_pdc_v2_duraciones_editables.php
```

Expected: FAIL — clase inexistente.

- [ ] **Step 3: Implementar**

Crear `src/Services/Pdc/DuracionesCatalogoService.php`:

```php
<?php

namespace App\Services\Pdc;

/**
 * A4.1 · diferido nº 4 — las duraciones del catálogo legacy, editables desde el PDC v2.
 *
 * `general_dias_procesos_contratacion` es de la EMPRESA, no de una obra: cambiar un número aquí
 * mueve las fechas de todas las obras cuyos paquetes apunten a esa fila. Por eso el permiso que lo
 * protege es `lps.paquetes_contratacion.reglas` (el de reglas globales) y no el de editar el plan,
 * y por eso `deProyecto()` solo ofrece las filas que la obra usa de verdad: no se edita a ciegas un
 * catálogo entero desde la pantalla de una obra.
 *
 * El módulo `/contratos` ya escribe en esta tabla por su cuenta
 * (`ContratosApiController::guardarDuracionesContratacion()`), con clave `(paqueteContratacion,
 * tipoPaquete)` y el permiso `lps.contratos.editar`. Aquí se escribe por `id` —que es como el PDC
 * v2 la referencia, vía `general_paquetes_contratacion.duracion_ref`— y no se toca aquel camino.
 */
class DuracionesCatalogoService
{
    public function __construct(private readonly \Database $db)
    {
    }

    /**
     * Las filas del catálogo que los paquetes de ESTA obra usan, con cuántos las usan.
     *
     * @return list<array{duracionRef:int,paqueteContratacion:string,tipoPaquete:string,dias:array<string,?int>,paquetesQueLaUsan:int}>
     */
    public function deProyecto(int $projectId): array
    {
        $cols = PasosContratacionService::columnasLegacy();
        $select = implode(', ', array_map(static fn (string $c): string => 'd.' . $c, $cols));
        $rows = $this->db->query(
            "SELECT d.id, d.paqueteContratacion, d.tipoPaquete, {$select}, COUNT(DISTINCT p.id) AS usos
             FROM pdc_paquete_frente f
             JOIN general_paquetes_contratacion p ON p.id = f.paquete_id
             JOIN general_dias_procesos_contratacion d ON d.id = p.duracion_ref
             WHERE f.project_id = ?
             GROUP BY d.id, d.paqueteContratacion, d.tipoPaquete, {$select}
             ORDER BY d.paqueteContratacion",
            [$projectId],
        )->fetchAll(\PDO::FETCH_ASSOC);

        $out = [];
        foreach ($rows as $r) {
            $dias = [];
            foreach ($cols as $c) {
                $dias[$c] = $r[$c] === null ? null : (int) $r[$c];
            }
            $out[] = [
                'duracionRef' => (int) $r['id'],
                'paqueteContratacion' => (string) $r['paqueteContratacion'],
                'tipoPaquete' => (string) $r['tipoPaquete'],
                'dias' => $dias,
                'paquetesQueLaUsan' => (int) $r['usos'],
            ];
        }
        return $out;
    }

    /**
     * Cambia una o varias de las siete duraciones de una fila del catálogo.
     *
     * Los nombres de columna se validan contra `PasosContratacionService::columnasLegacy()` —la
     * misma lista blanca que A4.1— porque van interpolados en el SQL: son nombres de columna y no
     * pueden ir como parámetro. Sin ese filtro esto sería una inyección.
     *
     * @param array<string, mixed> $dias columna legacy → días
     * @return array{ok:bool,code?:string,mensaje?:string}
     */
    public function actualizar(int $duracionRef, array $dias, string $usuario): array
    {
        if ($dias === []) {
            return ['ok' => false, 'code' => 'SIN_CAMBIOS', 'mensaje' => 'No se recibió ninguna duración que cambiar.'];
        }
        $legales = PasosContratacionService::columnasLegacy();
        $sets = [];
        $args = [];
        foreach ($dias as $col => $valor) {
            if (!in_array((string) $col, $legales, true)) {
                return ['ok' => false, 'code' => 'COLUMNA_DESCONOCIDA', 'mensaje' => "«{$col}» no es una duración del catálogo."];
            }
            $n = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
            if ($n === false) {
                return ['ok' => false, 'code' => 'DIAS_INVALIDOS', 'mensaje' => 'Los días tienen que ser un número entero de cero para arriba.'];
            }
            $sets[] = "{$col} = ?";
            $args[] = $n;
        }
        $args[] = $duracionRef;
        $stmt = $this->db->query(
            'UPDATE general_dias_procesos_contratacion SET ' . implode(', ', $sets) . ' WHERE id = ?',
            $args,
        );
        // No se usa rowCount() para decidir si la fila existe: este repo no activa
        // PDO::MYSQL_ATTR_FOUND_ROWS, así que guardar el mismo número daría 0 y parecería que la
        // fila no está. Se comprueba aparte, como ya hace asignarResponsable().
        unset($stmt);
        $existe = (int) $this->db->query(
            'SELECT COUNT(*) FROM general_dias_procesos_contratacion WHERE id = ?',
            [$duracionRef],
        )->fetchColumn();
        if ($existe === 0) {
            return ['ok' => false, 'code' => 'DURACION_NO_EXISTE', 'mensaje' => 'Esa fila del catálogo ya no existe.'];
        }
        return ['ok' => true];
    }
}
```

- [ ] **Step 4: Correr y verificar que pasa**

```bash
docker compose exec app php tests/test_pdc_v2_duraciones_editables.php
docker compose exec app php tests/test_pdc_duration_catalog.php
docker compose exec app vendor/bin/phpstan analyse src admin/src --memory-limit=1G
```

Expected: verde, incluido el test del catálogo que ya existía (no se rompe el camino de `/contratos`).

- [ ] **Step 5: Commit** *(condicionado a autorización)*

```bash
git add src/Services/Pdc/DuracionesCatalogoService.php tests/test_pdc_v2_duraciones_editables.php
git commit -m "feat(pdc): las duraciones del catalogo se pueden cambiar sin entrar a la base"
```

---

### Task 4: Duraciones — endpoints y pantalla

**Files:**
- Modify: `src/Controllers/Api/PlanComprasPlanController.php`, `public/index.php`
- Modify: `pdc-app/src/lib/types.ts`, `pdc-app/src/pages/PasosContratacion.tsx`
- Test: `tests/test_pdc_v2_rbac_pasos.php`

**Interfaces:**
- Produces: `GET /plan-compras/api/plan/duraciones` → `{ok:true, duraciones:[...]}`;
  `POST /plan-compras/api/plan/duraciones` `{duracionRef:int, dias:{columna:int}}` → `{ok:true, calculados:int, sinDuracion:int}`.

- [ ] **Step 1: Añadir los métodos al controlador**

```php
    /** GET /plan-compras/api/plan/duraciones — las duraciones del catálogo que esta obra usa. */
    public function duraciones(): void
    {
        $projectId = $this->guardReglas();
        if ($projectId === null) {
            return;
        }
        $this->ok(['duraciones' => (new DuracionesCatalogoService($this->db))->deProyecto($projectId)]);
    }

    /**
     * POST /plan-compras/api/plan/duraciones  {duracionRef, dias:{columna: dias}}
     *
     * Recalcula el plan de ESTA obra después de guardar, por la misma razón que `guardarPasos()`.
     * Las demás obras que usen la misma fila del catálogo verán el cambio cuando recalculen: no se
     * recalculan aquí porque un cambio hecho desde una obra no debe reescribir el plan de otras
     * a sus espaldas — para eso están sus desfases y su «Recalcular».
     */
    public function guardarDuracion(): void
    {
        $projectId = $this->guardReglas();
        if ($projectId === null) {
            return;
        }
        $body = $this->body();
        $ref = filter_var($body['duracionRef'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($ref === false) {
            $this->fail('DURACION_INVALIDA', 'Falta la fila del catálogo que se quiere cambiar.', 422);
            return;
        }
        $dias = is_array($body['dias'] ?? null) ? $body['dias'] : null;
        if ($dias === null) {
            $this->fail('DIAS_INVALIDOS', 'Falta el detalle de días.', 422);
            return;
        }
        // La fila tiene que ser una de las que esta obra usa: `duracionRef` llega del cliente y sin
        // esta comprobación la pantalla de una obra podría reescribir duraciones que no le tocan.
        $svc = new DuracionesCatalogoService($this->db);
        if (!in_array($ref, array_column($svc->deProyecto($projectId), 'duracionRef'), true)) {
            $this->fail('DURACION_NO_DISPONIBLE', 'Esa duración no la usa ningún paquete de esta obra.', 403);
            return;
        }
        $r = $svc->actualizar($ref, $dias, $this->usuario());
        if (!$r['ok']) {
            $this->fail($r['code'] ?? 'DIAS_INVALIDOS', $r['mensaje'] ?? 'No se pudo guardar.', 422);
            return;
        }
        $this->ok($this->service->calcular($projectId, $this->usuario()));
    }
```

Añadir `use App\Services\Pdc\DuracionesCatalogoService;`.

- [ ] **Step 2: Registrar las rutas**

```php
$router->get('/plan-compras/api/plan/duraciones', [\App\Controllers\Api\PlanComprasPlanController::class, 'duraciones']);
$router->post('/plan-compras/api/plan/duraciones', [\App\Controllers\Api\PlanComprasPlanController::class, 'guardarDuracion']);
```

- [ ] **Step 3: Añadir el tipo y el bloque de pantalla**

```ts
export type DuracionCatalogo = {
  duracionRef: number
  paqueteContratacion: string
  tipoPaquete: string
  dias: Record<string, number | null>
  paquetesQueLaUsan: number
}
```

En `PasosContratacion.tsx`, una sección «Duraciones del catálogo» con una fila por duración, sus siete campos numéricos y un aviso permanente:

```tsx
<p className="pdc-sub" data-testid="pdc-duraciones-aviso">
  Estas duraciones son de la empresa, no de esta obra: cambiarlas mueve las fechas de todas las
  obras cuyos paquetes las usen. Aquí se ven {duraciones.length} porque son las que usan los
  paquetes de esta obra.
</p>
```

Cada fila muestra `{d.paquetesQueLaUsan} paquete(s) de esta obra la usan` y, al guardar, informa de cuántos paquetes se recalcularon.

- [ ] **Step 4: Verificar RBAC — un rol permitido y uno denegado**

Ampliar `tests/test_pdc_v2_rbac_pasos.php`: rol con `lps.paquetes_contratacion.reglas` → 200 en `GET` y `POST /plan-compras/api/plan/duraciones`; rol `V` → 403 en ambas.

```bash
docker compose exec app php tests/test_pdc_v2_rbac_pasos.php
cd pdc-app && npx tsc --noEmit && npx vitest run
npm run check:frontend
```

Expected: verde.

- [ ] **Step 5: Verificar en el navegador** (1180×820, dark) que cambiar un día mueve la fecha del plan que dependía de él, y solo esa.

- [ ] **Step 6: Commit** *(condicionado a autorización)*

```bash
git add src/Controllers/Api/PlanComprasPlanController.php public/index.php pdc-app/src/lib/types.ts pdc-app/src/pages/PasosContratacion.tsx tests/test_pdc_v2_rbac_pasos.php
git commit -m "feat(pdc): editar las duraciones del catalogo desde la pantalla de pasos"
```

---

### Task 5: Listas de pasos por modalidad — NO SE CONSTRUYE

El spec fija una **precondición**: evidencia escrita de al menos una obra que necesite dos listas distintas. Esa evidencia no existe hoy y no la puede producir esta sesión: hay que preguntárselo a las dos obras. El spec es explícito — «sin esa evidencia, no se construye y se anota el porqué».

**Files:**
- Create: `goals/pdc-preparar-b1/evidence/listas-por-modalidad-no-se-construye.md`

- [ ] **Step 1: Escribir la anotación**

```markdown
# Listas de pasos por modalidad — no se construye (2026-07-29)

**Decisión:** no se implementa el diferido nº 1 de A4.1.

**Por qué:** el spec `2026-07-29-a41-diferidos-configuracion-pasos-design.md` lo condiciona a una
precondición que hoy no se cumple: *«evidencia escrita de al menos una obra que necesite dos listas
distintas»*. Nadie lo ha pedido desde que se registró el 2026-07-28; está en la lista porque se
registró, no porque haya demanda.

**Lo que costaría construirlo a ciegas:** es el más caro de los cuatro. La configuración dejaría de
ser *por obra* y pasaría a ser *por obra × modalidad*, lo que cambia la forma de `pdc_proyecto_pasos`
y de todo lo que la lee (`deProyecto()`, `calcular()`, la pantalla de pasos, la copia entre obras).

**Qué desbloquea esto:** preguntar a las dos obras si sus cuatro modalidades de contratación siguen
procesos distintos. Si alguna dice que sí, se anota aquí la respuesta y **se abre grilleo propio**
antes de tocar nada — el spec lo exige explícitamente porque cambia la forma del modelo de datos.

**Pendiente de:** el dueño del producto, que es quien tiene el canal con las dos obras.
```

- [ ] **Step 2: Anotarlo también en el goal**

En `goals/pdc-preparar-b1/estado-olas.md`, marcar este diferido como *no construido por precondición no cumplida*, con el enlace al archivo.

---

### Task 6: Historial de versiones de la configuración

Prioridad baja, y el propio spec lo dice: «Hoy no hay a quién responderle». Se implementa al final, como tabla de solo-anexar.

**Files:**
- Create: `database/migrations/20260730_pdc_pasos_historial.sql`
- Modify: `src/Services/Pdc/PasosContratacionService.php`
- Modify: `src/Controllers/Api/PlanComprasPlanController.php`, `public/index.php`
- Modify: `pdc-app/src/pages/PasosContratacion.tsx`
- Test: `tests/test_pdc_v2_pasos_historial.php` (crear)

**Interfaces:**
- Produces: `PasosContratacionService::historial(int $projectId): list<array{id:int,usuario:string,cuando:string,pasos:list<array{clave:string,alias:string,diasFijos:?int}>}>`.

- [ ] **Step 1: Escribir la migración**

```sql
-- 20260730_pdc_pasos_historial.sql
-- A4.1 · diferido nº 3 — quién cambió la configuración de pasos de una obra, cuándo y a qué.
--
-- Tabla de SOLO ANEXAR: una fila por guardado, con la configuración completa en JSON. Se guarda
-- entera y no un diff porque la lista es corta (siete pasos) y un diff obliga a reconstruir el
-- estado leyendo toda la cadena para responder «¿cómo estaba en mayo?».
--
-- Tabla global aislada por project_id, como el resto del módulo.
CREATE TABLE IF NOT EXISTS pdc_proyecto_pasos_historial (
    id BIGINT NOT NULL AUTO_INCREMENT,
    project_id INT NOT NULL,
    configuracion JSON NOT NULL,
    pasos SMALLINT NOT NULL,
    actualizado_por VARCHAR(100) NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_ppph_proyecto (project_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

- [ ] **Step 2: Escribir el test que falla**

Crear `tests/test_pdc_v2_pasos_historial.php` (proyecto 999980):

```php
$svc->guardar($P, [['clave' => 'elaboracion_pliegos'], ['clave' => 'legalizacion']], 'ana');
$svc->guardar($P, [['clave' => 'elaboracion_pliegos']], 'beto');

$h = $svc->historial($P);
$assert(count($h) === 2, 'Dos guardados dejan dos entradas: ' . count($h));
$assert($h[0]['usuario'] === 'beto', 'La más reciente va primero: ' . $h[0]['usuario']);
$assert(count($h[1]['pasos']) === 2, 'La entrada vieja conserva los dos pasos que tenía entonces.');
$assert(count($h[0]['pasos']) === 1, 'La nueva conserva el único que quedó.');

// Restablecer también deja rastro: volver al proceso por defecto es un cambio.
$svc->restablecer($P);
$assert(count($svc->historial($P)) === 3, 'Restablecer también se registra.');
$assert($svc->historial($P)[0]['pasos'] === [], 'Restablecer se guarda como «sin configuración propia».');

// Cero regresión: el historial no cambia lo que la obra usa.
$assert(count($svc->deProyecto($P)) === 7, 'Tras restablecer, los siete por defecto.');
```

- [ ] **Step 3: Correr y verificar que falla**

```bash
docker compose exec app php tests/test_pdc_v2_pasos_historial.php
```

Expected: FAIL — tabla y método inexistentes.

- [ ] **Step 4: Aplicar la migración y implementar**

```bash
docker compose exec -T db mysql -uroot -p"$DB_PASS" "$DB_NAME" < database/migrations/20260730_pdc_pasos_historial.sql
```

Y en `PasosContratacionService`, dentro de la transacción de `guardar()` (después del bucle de INSERT) y al final de `restablecer()`:

```php
    /**
     * Anota la configuración que acaba de quedar vigente. Solo anexa: nunca actualiza ni borra.
     *
     * Va DENTRO de la transacción de `guardar()` para que no pueda quedar una configuración
     * guardada sin su entrada de historial, ni al revés.
     *
     * @param list<array{clave:string,alias:string,diasFijos:?int}> $pasos
     */
    private function anotarHistorial(int $projectId, array $pasos, string $usuario): void
    {
        $this->db->query(
            'INSERT INTO pdc_proyecto_pasos_historial (project_id, configuracion, pasos, actualizado_por, created_at)
             VALUES (?, ?, ?, ?, NOW())',
            [$projectId, json_encode($pasos, JSON_UNESCAPED_UNICODE), count($pasos), mb_substr($usuario, 0, 100)],
        );
    }

    /**
     * @return list<array{id:int,usuario:string,cuando:string,pasos:list<array{clave:string,alias:string,diasFijos:?int}>}>
     */
    public function historial(int $projectId): array
    {
        $rows = $this->db->query(
            'SELECT id, configuracion, actualizado_por, created_at
             FROM pdc_proyecto_pasos_historial WHERE project_id = ? ORDER BY created_at DESC, id DESC',
            [$projectId],
        )->fetchAll(\PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'id' => (int) $r['id'],
                'usuario' => (string) $r['actualizado_por'],
                'cuando' => (string) $r['created_at'],
                'pasos' => json_decode((string) $r['configuracion'], true) ?: [],
            ];
        }
        return $out;
    }
```

`restablecer()` pasa a recibir `string $usuario` y anota una entrada con la lista vacía. Actualizar sus dos llamadores (`PlanComprasPlanController::restablecerPasos()` y el test de A4.1).

- [ ] **Step 5: Exponerlo**

`GET /plan-compras/api/plan/pasos/historial` con `guardLectura()` (leer quién cambió qué no necesita el permiso de reglas), y un `<details>` «Historial de cambios» en `PasosContratacion.tsx` con `usuario`, `cuando` y los pasos de cada entrada.

- [ ] **Step 6: Verificar**

```bash
docker compose exec app php tests/test_pdc_v2_pasos_historial.php
docker compose exec app php tests/test_pdc_v2_pasos_configurables.php
docker compose exec app php tests/test_pdc_v2_pasos_copiar.php
docker compose exec app php tests/test_global_table_safety.php
docker compose exec app vendor/bin/phpstan analyse src admin/src --memory-limit=1G
cd pdc-app && npx tsc --noEmit && npx vitest run
```

Expected: todo verde. `test_global_table_safety.php` porque la migración añade una tabla global.

- [ ] **Step 7: Commit** *(condicionado a autorización)*

```bash
git add database/migrations/20260730_pdc_pasos_historial.sql src/Services/Pdc/PasosContratacionService.php src/Controllers/Api/PlanComprasPlanController.php public/index.php pdc-app/src/pages/PasosContratacion.tsx tests/test_pdc_v2_pasos_historial.php
git commit -m "feat(pdc): quien cambio la configuracion de pasos, cuando y a que"
```

---

### Task 7: Cierre — cero regresión demostrada

- [ ] **Step 1: Demostrar el contrato de A4.1 sobre datos reales**

Sobre Da Porto (obra sin configurar): comprobar que sigue teniendo **11 filas y 77 pasos**, exactamente como A4.1 demostró.

```bash
docker compose exec app php tests/test_pdc_v2_pasos_configurables.php
docker compose exec app php tests/test_pdc_v2_plan_fechas.php
docker compose exec app php tests/test_pdc_v2_seguimiento.php
docker compose exec app php tests/test_pdc_v2_amarre_cronograma.php
docker compose exec app php tests/test_pdc_duration_catalog.php
docker compose exec app php tests/test_pdc_projected_dates_reflow.php
docker compose exec app vendor/bin/phpstan analyse src admin/src --memory-limit=1G
npx playwright test e2e/pdc-v2-plan.spec.mjs --config e2e/playwright.config.mjs --workers=1
```

- [ ] **Step 2: Anotar el cierre** en `goals/pdc-preparar-b1/estado-olas.md`: qué se construyó, qué no (Task 5) y por qué.

- [ ] **Step 3: `superpowers:verification-before-completion`** antes de decir que está hecho.

---

## Self-Review

**Cobertura del spec:**

| Hecho del spec | Tarea |
|---|---|
| 1 — copiar A→B, B queda igual, editar B no cambia A, recalcular B usa los pasos copiados | Tasks 1-2 |
| 2 — cambiar un día mueve la fecha que dependía de él y solo esa; rol sin capacidad → 403 | Tasks 3-4 |
| 3 — listas por modalidad: sin evidencia no se construye y se anota el porqué | Task 5 |
| 4 — el historial dice quién, cuándo y qué cambió, y se puede ver | Task 6 |
| 5 — cero regresión: una obra sin configurar sigue con sus siete pasos | Task 7, y asserts en Tasks 1 y 6 |
| «Copiar puede arrastrar basura → la pantalla muestra qué se va a copiar» | Task 1 (`previsualizarCopia`, `incompleta`) y Task 2 (panel de vista previa) |
| «Copia explícita y puntual, no vínculo vivo» | Task 1 Step 1 (assert de que editar B no cambia A) |
| «Requiere elegir obra origen entre las que el usuario puede ver» | Task 1 (`origenesDisponibles` filtra por `project_members`) y Task 2 (revalidado en el servidor) |
| «Cada uno se escribe y se ejecuta por separado» | Cuatro bloques de tareas independientes, cada uno con su commit |

**Consistencia de tipos:** `previsualizarCopia()` devuelve las claves que `PasoPreviewCopia` declara; `copiarDesde()` devuelve la misma forma que `guardar()` (`ok`/`code`/`mensaje`/`pasos`), que es lo que el controlador ya sabe manejar; `DuracionesCatalogoService::deProyecto()` produce las claves de `DuracionCatalogo`.

**Riesgo residual:** la Task 6 cambia la firma de `restablecer()`, que tiene dos llamadores; el Step 4 los nombra a los dos. Y la Task 4 escribe en una tabla compartida por todas las obras: por eso el endpoint exige que la fila sea una de las que la obra usa, y la pantalla lo dice en texto.
