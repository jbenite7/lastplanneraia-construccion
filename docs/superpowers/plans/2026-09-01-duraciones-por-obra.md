# Duraciones de contratación por obra — plan de implementación

> **Para trabajadores agénticos:** SUB-SKILL REQUERIDA: usar `superpowers:subagent-driven-development` (recomendado) o `superpowers:executing-plans` para ejecutar este plan tarea por tarea. Los pasos usan casillas (`- [ ]`) para seguimiento.

**Objetivo:** que una obra pueda corregir la duración de un paso de contratación de un paquete sin mover las fechas de las demás obras.

**Arquitectura:** el catálogo global `general_dias_procesos_contratacion` pasa a ser el valor por defecto. Una tabla nueva, `pdc_proyecto_duraciones`, guarda solo las correcciones de cada obra. La resolución «excepción si existe, catálogo si no» ocurre en un único punto —`PlanFechasService::proyectar()`— y el resto del cálculo no se entera.

**Stack:** PHP 8.3 sin framework (`src/Services/Pdc/`, `src/Controllers/Api/`), MySQL 8.0, React + TypeScript + Vite + AG Grid en `pdc-app/`, pruebas con scripts PHP autoejecutables, Vitest y Playwright.

**Spec:** `docs/superpowers/specs/2026-09-01-duraciones-por-obra-design.md`

## Restricciones globales

- **Rama y base.** Todo el trabajo va en `fix/pdc-duraciones-pasos`, worktree `.claude/worktrees/produccion`, anclada al commit **desplegado en producción** `6fa3cff10b7011ec1cb0001dbd00f4bbd2a8cb0b`. **No mezclar con `main`**, que va 457 commits por delante.
- **TODO se corre en el stack del espejo, nunca en el compartido.** Desde el 2026-09-01 este frente tiene su propio entorno completo: `docker-compose.espejo.yml`, proyecto `lps-espejo-prod`, app en **8083**, base en **3308**, con una **copia verificada de la base de producción** (104 tablas, ocho conteos comparados exactos contra el origen). El prefijo de cualquier comando es:

  ```bash
  docker compose -f docker-compose.espejo.yml --env-file .env.espejo exec -T app php <lo que sea>
  ```

  **Nunca `docker compose exec app`.** Ese es el stack compartido: sirve `main`, tiene aplicadas todas sus migraciones, y lo está usando otra sesión. El código de este frente es 457 commits anterior y no conoce ese esquema, así que probar ahí produce verdes y rojos que no significan nada — y de paso le mueve el suelo a la otra sesión. **Donde el texto de una tarea diga `docker compose exec app`, léase el prefijo de arriba.** Tampoco hace falta ya `LPS_CODE_ROOT`: el compose del espejo monta este worktree por ruta absoluta, así que no existe forma de verificar contra el árbol equivocado.
- **`.env.espejo` guarda las credenciales del espejo** y no se versiona (`.gitignore: .env*`). Sin él el stack no levanta. `.env` a secas sigue siendo el enlace al de la raíz y apunta a la base **compartida**; por eso el compose del espejo inyecta `DB_NAME`, `DB_USER` y `DB_PASS` como variables de contenedor, que `Dotenv::createImmutable()` no puede sobrescribir.
- **La base del espejo tiene datos reales de obra.** No se publica, no sale del disco, y su volumen se borra con `down -v` al cerrar el frente.
- **Las siete columnas legacy**, lista blanca de todo el plan, salen de `PasosContratacionService::columnasLegacy()`, derivada de `PlanFechasService::PASOS`: `diasElaboracionPliegos`, `diasEntregaPliegos`, `diasReciboPropuestas`, `diasCuadrosComparativos`, `diasLegalizacionContrato`, `diasFabricacion`, `diasInsumosObra`.
- **Nombre de columna nunca va como parámetro PDO.** Se interpola, y por eso se valida contra esa lista blanca antes. Sin el filtro es una inyección.
- **`project_id` sale de la sesión**, jamás del cliente.
- **CSRF ámbito `plan_compras_v2`** en toda mutación.
- **Permiso de escritura: `lps.paquetes_contratacion.editar`.** `.reglas` sigue protegiendo el catálogo global y no se toca.
- **Proyecto de pruebas: `999906`.** El `999905` ya lo usa `test_pdc_v2_pasos_configurables.php`; compartirlo cruzaría dos suites.
- **Ninguna tarea aplica la migración en producción ni despliega.** Eso es puerta aparte, con autorización explícita de Felipe. El entorno local de Docker y el servidor de producción son de solo lectura para SQL sin ese visto.

## Estructura de archivos

| Archivo | Responsabilidad |
|---|---|
| `database/migrations/20260901_pdc_v2_duraciones_por_obra.php` | Crea la tabla. No siembra datos. |
| `src/Services/Pdc/DuracionesObraService.php` | Leer, guardar y borrar las excepciones de una obra. Única puerta a la tabla. |
| `src/Services/Pdc/PlanFechasService.php` | Modificar: `proyectar()` acepta las excepciones y las antepone al catálogo. |
| `src/Controllers/Api/PlanComprasPlanController.php` | Modificar: dos verbos nuevos y el guard de `.editar`. |
| `public/index.php` | Modificar: registrar las dos rutas. |
| `pdc-app/src/lib/duracionesObra.ts` | Lógica pura: validar el número y distinguir el origen. |
| `pdc-app/src/pages/PlanFechas.tsx` | Modificar: la columna Días del panel pasa a editable. |

`pdc-app` **no tiene pruebas de componente** —sus 20 pruebas son de funciones puras en `lib/`— así que la lógica de pantalla se extrae a `duracionesObra.ts` y se prueba ahí; el render se cubre con Playwright. No se introduce un patrón nuevo de pruebas.

---

### Tarea 1: La tabla y su migración

**Archivos:**
- Crear: `database/migrations/20260901_pdc_v2_duraciones_por_obra.php`
- Crear: `tests/test_pdc_v2_duraciones_por_obra.php`

**Interfaces:**
- Consume: nada.
- Produce: la tabla `pdc_proyecto_duraciones` con columnas `project_id INT`, `duracion_ref INT`, `columna VARCHAR(64)`, `dias INT`, `actualizado_por INT NULL`, `updated_at TIMESTAMP`, y clave única `uq_ppd_obra_ref_col (project_id, duracion_ref, columna)`.

- [ ] **Paso 1: Escribir la prueba que falla**

Crear `tests/test_pdc_v2_duraciones_por_obra.php`:

```php
<?php
// tests/test_pdc_v2_duraciones_por_obra.php — duraciones por obra: tabla, servicio y resolución.
declare(strict_types=1);
// @requiere: datos-proyecto

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

$failures = [];
$assert = static function (bool $c, string $m) use (&$failures): void {
    if ($c) { fwrite(STDOUT, "PASS: {$m}\n"); return; }
    $failures[] = $m; fwrite(STDERR, "FAIL: {$m}\n");
};

$db = Database::getInstance();

echo "=== la tabla existe con su clave única ===\n";
$tabla = (int) $db->query(
    'SELECT COUNT(*) FROM information_schema.TABLES
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
    ['pdc_proyecto_duraciones'],
)->fetchColumn();
$assert($tabla === 1, 'Existe la tabla pdc_proyecto_duraciones.');

$unica = (int) $db->query(
    'SELECT COUNT(*) FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? AND NON_UNIQUE = 0',
    ['pdc_proyecto_duraciones', 'uq_ppd_obra_ref_col'],
)->fetchColumn();
$assert($unica === 3, 'La clave única cubre las tres columnas (project_id, duracion_ref, columna). Dio ' . $unica);

echo $failures === [] ? "\nOK\n" : "\n" . count($failures) . " fallo(s)\n";
exit($failures === [] ? 0 : 1);
```

- [ ] **Paso 2: Correrla y ver que falla**

```bash
docker compose exec app php tests/test_pdc_v2_duraciones_por_obra.php
```

Esperado: FAIL en las dos afirmaciones — la tabla no existe. Código de salida `1`.

- [ ] **Paso 3: Escribir la migración**

Crear `database/migrations/20260901_pdc_v2_duraciones_por_obra.php`. Sigue la forma de `20260728_pdc_v2_pasos_configurables.php`: dry-run por defecto, `--apply` para aplicar, y salida temprana si la tabla ya existe.

Cabecera de comentario obligatoria, porque explica las dos decisiones que un lector futuro va a cuestionar:

```php
<?php

// 20260901_pdc_v2_duraciones_por_obra.php
// Las duraciones de los pasos de contratación dejan de ser solo de la empresa.
//
// pdc_proyecto_duraciones guarda UNA FILA POR NÚMERO CORREGIDO, no por paquete: la corrección es
// parcial por naturaleza —se ajusta Fabricación y nada más— y con siete columnas espejo habría que
// distinguir «no corregido» de «corregido a NULL». Además, si el catálogo gana un paso, esta tabla
// no necesita migración.
//
// CERO FILAS PARA UNA OBRA = manda el catálogo global, exactamente como hoy. Por eso esta migración
// NO siembra nada: aplicarla no puede mover ni una fecha.
//
// Sin clave foránea a general_dias_procesos_contratacion, por la misma razón que A4.1 aceptó para
// paso_id: el catálogo es global y su ciclo de vida no lo gobierna esta tabla. La integridad la
// sostienen la validación de escritura y una lectura que solo mira las filas que la obra usa.
//
// Uso:  php database/migrations/20260901_pdc_v2_duraciones_por_obra.php [--apply]

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Core/Database.php';

$apply = in_array('--apply', $argv, true);
$db = Database::getInstance();

$existeTabla = static fn (Database $db, string $t): bool => (int) $db->query(
    'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
    [$t],
)->fetchColumn() > 0;

if ($existeTabla($db, 'pdc_proyecto_duraciones')) {
    echo "pdc_proyecto_duraciones ya existe: nada que hacer.\n";
    exit(0);
}

echo "A crear: tabla pdc_proyecto_duraciones con clave única uq_ppd_obra_ref_col.\n";
if (!$apply) {
    echo "Simulacro. Repite con --apply para aplicar.\n";
    exit(0);
}
```

Y a continuación la sentencia de creación, con esta forma exacta:

- tabla `pdc_proyecto_duraciones`, motor `InnoDB`, charset `utf8mb4`;
- `id INT AUTO_INCREMENT PRIMARY KEY`;
- `project_id INT NOT NULL`;
- `duracion_ref INT NOT NULL`;
- `columna VARCHAR(64) NOT NULL`;
- `dias INT NOT NULL`;
- `actualizado_por INT NULL`;
- `updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`;
- clave única `uq_ppd_obra_ref_col (project_id, duracion_ref, columna)`;
- índice `ix_ppd_obra (project_id)`.

Cerrar con `echo "Creada.\n";` y `exit(0);`.

- [ ] **Paso 4: Correr el simulacro y leer su salida**

```bash
docker compose exec app php database/migrations/20260901_pdc_v2_duraciones_por_obra.php
```

Esperado: «A crear: tabla pdc_proyecto_duraciones…» y «Simulacro». **No sigas si dice otra cosa.**

- [ ] **Paso 5: Aplicar en el entorno local**

Aplicar cambia el esquema, así que es una escritura y necesita el visto de Felipe en el chat. Con ese visto, y anteponiendo la marca de auditoría que exige el repo:

```bash
AUTORIZADO_POR_FELIPE=1 docker compose exec app php database/migrations/20260901_pdc_v2_duraciones_por_obra.php --apply
```

Esperado: «Creada.» **Sin el visto, la tarea se detiene aquí y se le pide.**

- [ ] **Paso 6: Comprobar que es idempotente**

Repetir el mismo comando del paso 5.

Esperado: «pdc_proyecto_duraciones ya existe: nada que hacer.» Salida `0`. Una migración que no se puede repetir sin daño no sirve para un deploy.

- [ ] **Paso 7: Correr la prueba y verla pasar**

```bash
docker compose exec app php tests/test_pdc_v2_duraciones_por_obra.php
```

Esperado: dos `PASS` y `OK`. Salida `0`.

- [ ] **Paso 8: Commit**

```bash
git add database/migrations/20260901_pdc_v2_duraciones_por_obra.php tests/test_pdc_v2_duraciones_por_obra.php
git commit -m "feat(pdc): tabla de duraciones de contratación por obra"
```

---

### Tarea 2: El servicio que lee y escribe las excepciones

**Archivos:**
- Crear: `src/Services/Pdc/DuracionesObraService.php`
- Modificar: `tests/test_pdc_v2_duraciones_por_obra.php` (añadir antes del resumen final)

**Interfaces:**
- Consume: la tabla de la Tarea 1; `PasosContratacionService::columnasLegacy()`.
- Produce:
  - `DuracionesObraService::__construct(\Database $db)`
  - `deProyecto(int $projectId): array` → `array<int, array<string,int>>`, mapa `duracionRef => [columna => dias]`
  - `guardar(int $projectId, int $duracionRef, array $dias, ?int $usuario): array{ok:bool, code?:string, mensaje?:string}` — `$dias` es `columna => días`
  - `borrar(int $projectId, int $duracionRef, array $columnas): array{ok:bool, code?:string, mensaje?:string}`

- [ ] **Paso 1: Escribir las pruebas que fallan**

Añadir a `tests/test_pdc_v2_duraciones_por_obra.php`, justo antes de la línea `echo $failures === [] ? ...`:

```php
use App\Services\Pdc\DuracionesObraService;

echo "=== el servicio guarda, lee y borra ===\n";
$P = 999906;
$REF = 1;
$svcObra = new DuracionesObraService($db);
$limpiar = static function () use ($db, $P): void {
    $db->query('DELETE FROM pdc_proyecto_duraciones WHERE project_id = ?', [$P]);
};
$limpiar();

$assert($svcObra->deProyecto($P) === [], 'Una obra sin correcciones devuelve un mapa vacío.');

$r = $svcObra->guardar($P, $REF, ['diasFabricacion' => 120], null);
$assert($r['ok'] === true, 'Guardar una corrección válida responde ok.');
$assert($svcObra->deProyecto($P) === [$REF => ['diasFabricacion' => 120]],
    'La corrección se lee indexada por duracionRef y columna.');

$r = $svcObra->guardar($P, $REF, ['diasFabricacion' => 90], null);
$assert($r['ok'] === true && $svcObra->deProyecto($P)[$REF]['diasFabricacion'] === 90,
    'Guardar dos veces la misma columna actualiza en vez de duplicar.');

$r = $svcObra->guardar($P, $REF, ['columnaInventada' => 5], null);
$assert($r['ok'] === false && $r['code'] === 'COLUMNA_INVALIDA',
    'Una columna fuera de la lista blanca se rechaza.');

$r = $svcObra->guardar($P, $REF, ['diasFabricacion' => -1], null);
$assert($r['ok'] === false && $r['code'] === 'DIAS_INVALIDOS',
    'Un número de días negativo se rechaza.');

$assert($svcObra->deProyecto($P)[$REF]['diasFabricacion'] === 90,
    'Un rechazo no deja el dato a medias: sigue valiendo 90.');

$r = $svcObra->borrar($P, $REF, ['diasFabricacion']);
$assert($r['ok'] === true && $svcObra->deProyecto($P) === [],
    'Borrar la corrección devuelve la obra al catálogo de la empresa.');

$limpiar();
```

- [ ] **Paso 2: Correrlas y ver que fallan**

```bash
docker compose exec app php tests/test_pdc_v2_duraciones_por_obra.php
```

Esperado: error fatal `Class "App\Services\Pdc\DuracionesObraService" not found`.

- [ ] **Paso 3: Escribir el servicio**

Crear `src/Services/Pdc/DuracionesObraService.php`:

```php
<?php

namespace App\Services\Pdc;

/**
 * Las correcciones de duración que UNA OBRA hace sobre el catálogo de la empresa.
 *
 * `general_dias_procesos_contratacion` es de la empresa y lo comparten todas las obras: cambiar un
 * número allí mueve las fechas de todas. Esta tabla guarda la excepción de una obra, y la lectura
 * del plan la antepone al catálogo. Cero filas para una obra = manda el catálogo, igual que antes
 * de que esto existiera.
 *
 * El nombre de columna se interpola en el SQL porque es un nombre de columna y no puede ir como
 * parámetro. Por eso se valida antes contra `PasosContratacionService::columnasLegacy()`, que se
 * deriva de `PlanFechasService::PASOS` y no de la base: sin ese filtro esto sería una inyección.
 */
class DuracionesObraService
{
    public function __construct(private readonly \Database $db)
    {
    }

    /**
     * Las correcciones de una obra, listas para consultar por paquete.
     *
     * Se carga UNA VEZ POR OBRA y no dentro del bucle de paquetes: la consulta de `proyectar()`
     * corre por paquete, así que meterlo ahí convertiría un plan de cien paquetes en doscientas
     * consultas.
     *
     * @return array<int, array<string,int>> duracionRef => [columna => días]
     */
    public function deProyecto(int $projectId): array
    {
        $rows = $this->db->query(
            'SELECT duracion_ref, columna, dias FROM pdc_proyecto_duraciones WHERE project_id = ?',
            [$projectId],
        )->fetchAll(\PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r['duracion_ref']][(string) $r['columna']] = (int) $r['dias'];
        }
        return $out;
    }

    /**
     * @param array<string, mixed> $dias columna legacy => días
     * @return array{ok:bool, code?:string, mensaje?:string}
     */
    public function guardar(int $projectId, int $duracionRef, array $dias, ?int $usuario): array
    {
        $legales = PasosContratacionService::columnasLegacy();
        // Se valida TODO antes de escribir nada: una corrección a medias dejaría la obra con unos
        // pasos movidos y otros no, y sin forma de saber cuáles.
        foreach ($dias as $col => $v) {
            if (!in_array($col, $legales, true)) {
                return ['ok' => false, 'code' => 'COLUMNA_INVALIDA', 'mensaje' => "«{$col}» no es un paso del proceso."];
            }
            $n = filter_var($v, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
            if ($n === false) {
                return ['ok' => false, 'code' => 'DIAS_INVALIDOS', 'mensaje' => 'Los días deben ser un entero de cero o más.'];
            }
        }
        foreach ($dias as $col => $v) {
            $this->db->query(
                'INSERT INTO pdc_proyecto_duraciones (project_id, duracion_ref, columna, dias, actualizado_por)
                 VALUES (?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE dias = VALUES(dias), actualizado_por = VALUES(actualizado_por)',
                [$projectId, $duracionRef, (string) $col, (int) $v, $usuario],
            );
        }
        return ['ok' => true];
    }

    /**
     * @param list<string> $columnas
     * @return array{ok:bool, code?:string, mensaje?:string}
     */
    public function borrar(int $projectId, int $duracionRef, array $columnas): array
    {
        $legales = PasosContratacionService::columnasLegacy();
        foreach ($columnas as $col) {
            if (!in_array($col, $legales, true)) {
                return ['ok' => false, 'code' => 'COLUMNA_INVALIDA', 'mensaje' => "«{$col}» no es un paso del proceso."];
            }
        }
        if ($columnas === []) {
            return ['ok' => true];
        }
        $marcas = implode(', ', array_fill(0, count($columnas), '?'));
        $this->db->query(
            "DELETE FROM pdc_proyecto_duraciones
             WHERE project_id = ? AND duracion_ref = ? AND columna IN ({$marcas})",
            array_merge([$projectId, $duracionRef], array_values($columnas)),
        );
        return ['ok' => true];
    }
}
```

- [ ] **Paso 4: Correr las pruebas y verlas pasar**

```bash
docker compose exec app php tests/test_pdc_v2_duraciones_por_obra.php
```

Esperado: todas en `PASS` y `OK`. Salida `0`.

- [ ] **Paso 5: Commit**

```bash
git add src/Services/Pdc/DuracionesObraService.php tests/test_pdc_v2_duraciones_por_obra.php
git commit -m "feat(pdc): servicio de correcciones de duración por obra"
```

---

### Tarea 3: La resolución — la obra manda sobre la empresa

**Archivos:**
- Modificar: `src/Services/Pdc/PlanFechasService.php:1307-1313` (firma de `proyectar()`), `:1337` (tras el fetch), `:1406-1407` y `:1428-1435` (`calcular()`), `:2059-2060` y `:2084` (`simularReprogramacion()`)
- Modificar: `tests/test_pdc_v2_duraciones_por_obra.php`

**Interfaces:**
- Consume: `DuracionesObraService::deProyecto()` de la Tarea 2.
- Produce: `proyectar()` con un parámetro nuevo `array $excepciones = []` en sexta posición, antes de `?string $modalidadDestino = null`.

- [ ] **Paso 1: Escribir la prueba que falla**

Añadir a `tests/test_pdc_v2_duraciones_por_obra.php`, antes del resumen:

```php
use App\Services\Pdc\PlanFechasService;

echo "=== la obra manda sobre la empresa ===\n";
$refl = new \ReflectionMethod(PlanFechasService::class, 'proyectar');
$assert($refl->getNumberOfParameters() === 7,
    'proyectar() acepta las excepciones. Tiene ' . $refl->getNumberOfParameters() . ' parámetros.');
$assert($refl->getParameters()[5]->getName() === 'excepciones',
    'El sexto parámetro de proyectar() se llama $excepciones.');

$fuentePlan = file_get_contents(__DIR__ . '/../src/Services/Pdc/PlanFechasService.php');
$assert(substr_count($fuentePlan, 'DuracionesObraService($this->db))->deProyecto($projectId)') === 2,
    'calcular() y simularReprogramacion() cargan las excepciones. Si solo una lo hace, la '
    . 'simulación promete fechas distintas de las que el cálculo escribe.');

// La resolución en sí es aritmética sobre dos arrays y se prueba sin base.
$catalogo = ['diasFabricacion' => 180, 'diasLegalizacionContrato' => 30];
$resolver = static function (array $paq, array $exc): array {
    foreach ($exc as $col => $d) { if (array_key_exists($col, $paq)) { $paq[$col] = $d; } }
    return $paq;
};
$assert($resolver($catalogo, [])['diasFabricacion'] === 180,
    'Sin excepción manda el número de la empresa.');
$assert($resolver($catalogo, ['diasFabricacion' => 120])['diasFabricacion'] === 120,
    'Con excepción manda el número de la obra.');
$assert($resolver($catalogo, ['diasFabricacion' => 120])['diasLegalizacionContrato'] === 30,
    'La excepción de un paso no toca los demás pasos.');
```

- [ ] **Paso 2: Correrla y ver que falla**

```bash
docker compose exec app php tests/test_pdc_v2_duraciones_por_obra.php
```

Esperado: FAIL en «proyectar() acepta las excepciones» — hoy tiene 6 parámetros, no 7.

- [ ] **Paso 3: Añadir el parámetro a `proyectar()`**

En `src/Services/Pdc/PlanFechasService.php`, cambiar la firma (línea 1307):

```php
    private function proyectar(
        int $paqueteId,
        string $fechaAncla,
        array $pasos,
        array $medianas,
        string $selectCols,
        array $excepciones = [],
        ?string $modalidadDestino = null,
    ): ?array {
```

Y añadir al docblock:

```php
     * @param array<int, array<string,int>> $excepciones duracionRef => [columna => días], las
     *        correcciones de ESTA obra. Se anteponen al catálogo global.
```

- [ ] **Paso 4: Aplicar la excepción justo después del fetch**

En la misma función, después del bloque `if ($paq === false) { return null; }` (línea ~1336) y **antes** del cálculo de `$desgloseCompleto`:

```php
        // La obra corrige; la empresa es el valor por defecto. Va ANTES del cálculo de
        // `$desgloseCompleto` a propósito: una obra debe poder dar un número donde el catálogo tiene
        // NULL, y ese es justamente uno de los casos útiles. Aplicada después, el paquete ya se
        // habría marcado provisional y la corrección no serviría de nada.
        $ref = $paq['duracion_ref'] === null ? null : (int) $paq['duracion_ref'];
        if ($ref !== null && isset($excepciones[$ref])) {
            foreach ($excepciones[$ref] as $col => $dias) {
                // Solo columnas que esta consulta trajo: `$selectCols` pide únicamente las que la
                // obra usa, y escribir una clave que no vino inventaría una columna en el array.
                if (array_key_exists($col, $paq)) {
                    $paq[$col] = $dias;
                }
            }
        }
```

- [ ] **Paso 5: Cargar las excepciones en `calcular()` y pasarlas**

Después de `$pasos = $this->pasos->deProyecto($projectId);` (línea ~1407):

```php
        // Una sola consulta por obra, fuera del bucle: `proyectar()` consulta por paquete.
        $excepciones = (new DuracionesObraService($this->db))->deProyecto($projectId);
```

Y en la llamada a `proyectar()` (línea ~1428), insertar `$excepciones` antes de la modalidad:

```php
            $pr = $this->proyectar(
                $paqueteId,
                $a['fechaAncla'],
                $pasos,
                $medianas,
                $selectCols,
                $excepciones,
                $modalidadPorLote[$subpaqueteId] ?? null,
            );
```

- [ ] **Paso 6: Lo mismo en `simularReprogramacion()`**

Después de `$pasos = $this->pasos->deProyecto($projectId);` (línea ~2060):

```php
        $excepciones = (new DuracionesObraService($this->db))->deProyecto($projectId);
```

Y en la llamada (línea ~2084):

```php
            $pr = $this->proyectar($d['paqueteId'], $d['fechaActual'], $pasos, $medianas, $selectCols, $excepciones);
```

Sin esto, la simulación de reprogramación prometería fechas distintas de las que `calcular()` va a escribir.

- [ ] **Paso 7: Correr las pruebas y verlas pasar**

```bash
docker compose exec app php tests/test_pdc_v2_duraciones_por_obra.php
```

Esperado: todas en `PASS`.

- [ ] **Paso 8: Comprobar que el PDC no se rompió**

```bash
docker compose exec app php tests/test_pdc_v2_plan_fechas.php
docker compose exec app php tests/test_pdc_v2_pasos_configurables.php
```

Esperado: las dos en `OK`, salida `0`. **Si alguna falla, para**: significa que la resolución cambió un cálculo que debía quedar igual. Sin excepciones en la base, el resultado tiene que ser idéntico al de antes de esta tarea.

- [ ] **Paso 9: Escribir la prueba del aislamiento entre obras**

Es **la afirmación central de todo el frente** y la spec la exige por nombre (§9). Prueba que la
misma fila del catálogo, con el mismo paquete, da resultados distintos según la obra — que es
exactamente lo que hoy no se puede hacer.

Añadir a `tests/test_pdc_v2_duraciones_por_obra.php`:

```php
use App\Services\Pdc\PasosContratacionService;

echo "=== la misma fila del catálogo, dos obras, resultados distintos ===\n";
$cols = PasosContratacionService::columnasLegacy();
$selectCols = ', ' . implode(', ', array_map(static fn (string $c): string => 'd.' . $c, $cols));
$pasosSint = [];
foreach (PlanFechasService::PASOS as $i => $p) {
    $pasosSint[] = [
        'pasoId' => $i + 1, 'clave' => $p['clave'], 'nombre' => $p['paso'],
        'colLegacy' => $p['col'], 'diasFijos' => null, 'peso' => null,
    ];
}

// Una fila del catálogo y un paquete que la usa. Las DOS obras comparten ambos: si el aislamiento
// fallara, la corrección de una se vería en la otra. Los siete números suman 33.
$db->query(
    "INSERT INTO general_dias_procesos_contratacion (paqueteContratacion, tipoPaquete,
        diasElaboracionPliegos, diasEntregaPliegos, diasReciboPropuestas, diasCuadrosComparativos,
        diasLegalizacionContrato, diasFabricacion, diasInsumosObra)
     VALUES ('ZZTEST OBRA DUR', 'a_todo_costo', 3, 2, 7, 4, 5, 10, 2)",
);
$refSint = (int) $db->lastInsertId();
$db->query(
    "INSERT INTO general_paquetes_contratacion (nombre, nombre_norm, tipo_negociacion,
        modalidad_contratacion, duracion_ref, activo, creado_por, created_at)
     VALUES ('ZZTEST OBRA DUR', 'zztest obra dur', 'a_todo_costo', 'contrato', ?, 1, 'test-dur-obra', NOW())",
    [$refSint],
);
$paqSint = (int) $db->lastInsertId();

// `proyectar()` es privado a propósito: lo que se prueba es su contrato, no su visibilidad.
$proyectar = new \ReflectionMethod(PlanFechasService::class, 'proyectar');
$proyectar->setAccessible(true);
$plan = new PlanFechasService($db);
$correr = static fn (array $exc): int => (int) $proyectar->invoke(
    $plan, $paqSint, '2026-12-31', $pasosSint, [], $selectCols, $exc,
)['total'];

try {
    $sinNada = $correr([]);
    $assert($sinNada === 33, 'Sin correcciones el proceso dura lo del catálogo: 33 días. Dio ' . $sinNada);

    $obraQueCorrige = $correr([$refSint => ['diasFabricacion' => 15]]);
    $assert($obraQueCorrige === 38,
        'La obra que corrigió Fabricación de 10 a 15 pasa a 38 días. Dio ' . $obraQueCorrige);

    $obraQueNoCorrige = $correr([]);
    $assert($obraQueNoCorrige === 33,
        'Y la otra obra, con el MISMO paquete y la MISMA fila del catálogo, sigue en 33. Dio '
        . $obraQueNoCorrige);

    $otraFila = $correr([$refSint + 100000 => ['diasFabricacion' => 15]]);
    $assert($otraFila === 33,
        'Una corrección sobre otra fila del catálogo no afecta a este paquete. Dio ' . $otraFila);
} finally {
    $db->query('DELETE FROM general_paquetes_contratacion WHERE creado_por = ?', ['test-dur-obra']);
    $db->query("DELETE FROM general_dias_procesos_contratacion WHERE paqueteContratacion = 'ZZTEST OBRA DUR'");
}
```

- [ ] **Paso 10: Correrla y verla pasar**

```bash
docker compose exec app php tests/test_pdc_v2_duraciones_por_obra.php
```

Esperado: las cuatro afirmaciones en `PASS`. **Si «la otra obra sigue en 33» falla, para el frente
entero**: significa que la corrección se está filtrando entre obras, que es el daño que este diseño
existe para evitar.

Comprobar además que el montaje se limpió, porque un `ZZTEST` olvidado contamina el catálogo real:

```bash
docker compose exec app php -r 'require "vendor/autoload.php"; require "src/Core/Database.php"; echo (int) Database::getInstance()->query("SELECT COUNT(*) FROM general_dias_procesos_contratacion WHERE paqueteContratacion LIKE \"ZZTEST%\"")->fetchColumn(), "\n";'
```

Esperado: `0`.

- [ ] **Paso 11: Commit**

```bash
git add src/Services/Pdc/PlanFechasService.php tests/test_pdc_v2_duraciones_por_obra.php
git commit -m "feat(pdc): el plan resuelve la duración de la obra antes que la de la empresa"
```

---

### Tarea 4: El plan le dice a la pantalla qué corregir

**Archivos:**
- Modificar: `src/Services/Pdc/PlanFechasService.php:1790-1820` (consulta de cabeceras), `:1823-1855` (armado de pasos), `:1889` (armado de la fila)
- Modificar: `tests/test_pdc_v2_duraciones_por_obra.php`

**Interfaces:**
- Consume: `DuracionesObraService::deProyecto()`; `PlanFechasService::PASOS`.
- Produce: cada fila del plan gana `duracionRef: int|null`; cada paso gana `colLegacy: string|null` y `origen: 'empresa'|'obra'`.

Hoy `duracion_ref` **se guarda** en `pdc_plan_paquete` (`PlanFechasService:1473`) pero **no se selecciona** en la consulta del plan, así que no llega al cliente. Sin él, la pantalla no sabe qué fila del catálogo corregir.

- [ ] **Paso 1: Escribir la prueba que falla**

Añadir a `tests/test_pdc_v2_duraciones_por_obra.php`:

```php
echo "=== el plan expone lo que la pantalla necesita para corregir ===\n";
$fuente = file_get_contents(__DIR__ . '/../src/Services/Pdc/PlanFechasService.php');
$assert(str_contains($fuente, 'pp.duracion_ref'),
    'La consulta del plan selecciona pp.duracion_ref.');
$assert(str_contains($fuente, "'duracionRef' => \$r['duracion_ref']"),
    'La fila del plan expone duracionRef al cliente.');
$assert(str_contains($fuente, "'origen' =>") && str_contains($fuente, "'colLegacy' =>"),
    'Cada paso del plan dice su columna legacy y si su duración es de la empresa o de la obra.');
```

- [ ] **Paso 2: Correrla y ver que falla**

```bash
docker compose exec app php tests/test_pdc_v2_duraciones_por_obra.php
```

Esperado: FAIL en las tres.

- [ ] **Paso 3: Seleccionar `duracion_ref` en la consulta de cabeceras**

En la consulta que empieza en la línea ~1790, añadir `pp.duracion_ref,` a la lista de columnas de `pp`.

- [ ] **Paso 4: Cargar excepciones y mapas antes de armar los pasos**

Junto a `$hoyStr` (línea ~1823):

```php
        $excepciones = (new DuracionesObraService($this->db))->deProyecto($projectId);
        // paquete_id:subpaquete_id → duracion_ref, para saber qué excepción mira cada paso.
        $refPorDestino = [];
        foreach ($rows as $r) {
            $refPorDestino[(int) $r['paquete_id'] . ':' . (int) $r['subpaquete_id']]
                = $r['duracion_ref'] === null ? null : (int) $r['duracion_ref'];
        }
        // clave del paso → columna legacy. Se casa por CLAVE y no por nombre porque la obra puede
        // haber renombrado el paso con su alias.
        $colPorClave = [];
        foreach (self::PASOS as $pp) {
            $colPorClave[$pp['clave']] = $pp['col'];
        }
```

- [ ] **Paso 5: Marcar cada paso con su columna y su origen**

Dentro del `foreach` que arma cada paso (línea ~1837), añadir al array del paso:

```php
                'colLegacy' => $colPorClave[(string) ($p['clave'] ?? '')] ?? null,
                // De dónde sale el número. La pantalla lo muestra para que nadie corrija el estándar
                // de la empresa creyendo que corrige solo su obra, ni al revés.
                'origen' => (function () use ($p, $colPorClave, $refPorDestino, $excepciones): string {
                    $col = $colPorClave[(string) ($p['clave'] ?? '')] ?? null;
                    $ref = $refPorDestino[(int) $p['paquete_id'] . ':' . (int) $p['subpaquete_id']] ?? null;
                    return $col !== null && $ref !== null && isset($excepciones[$ref][$col]) ? 'obra' : 'empresa';
                })(),
```

- [ ] **Paso 6: Exponer `duracionRef` en la fila**

En el `foreach ($rows as $r)` que arma la salida (línea ~1889), añadir a cada fila:

```php
                'duracionRef' => $r['duracion_ref'] === null ? null : (int) $r['duracion_ref'],
```

- [ ] **Paso 7: Correr las pruebas y verlas pasar**

```bash
docker compose exec app php tests/test_pdc_v2_duraciones_por_obra.php
docker compose exec app php tests/test_pdc_v2_plan_fechas.php
```

Esperado: las dos en `OK`.

- [ ] **Paso 8: Commit**

```bash
git add src/Services/Pdc/PlanFechasService.php tests/test_pdc_v2_duraciones_por_obra.php
git commit -m "feat(pdc): el plan expone duracionRef y el origen de cada duración"
```

---

### Tarea 5: Los dos verbos

**Archivos:**
- Modificar: `src/Controllers/Api/PlanComprasPlanController.php` (guard nuevo y dos métodos, junto a `guardarDuracion()` en la línea 511)
- Modificar: `public/index.php:224`
- Crear: `tests/test_pdc_v2_duraciones_obra_contrato.php`

**Interfaces:**
- Consume: `DuracionesObraService` de la Tarea 2.
- Produce:
  - `POST /plan-compras/api/plan/duraciones/obra` — cuerpo `{duracionRef:int, dias:{columna:int}}`
  - `POST /plan-compras/api/plan/duraciones/obra/borrar` — cuerpo `{duracionRef:int, columnas:string[]}`
  - Ambos responden el plan recalculado, igual que `guardarDuracion()`.

> **Resuelto en el barrido previo (C2).** `src/Core/Router.php` declara únicamente `get()` y
> `post()`, y `pdc-app/src/lib/api.ts` exporta únicamente `apiGet`, `apiPost` y `apiUpload`. No hay
> `DELETE` sin ampliar dos capas compartidas por todo el módulo, y eso queda fuera del alcance.
> **Los dos verbos son POST**, y el de restablecer vive en `…/duraciones/obra/borrar`.

- [ ] **Paso 1: Escribir la prueba de contrato que falla**

Crear `tests/test_pdc_v2_duraciones_obra_contrato.php`:

```php
<?php
// tests/test_pdc_v2_duraciones_obra_contrato.php — contrato de las duraciones por obra.
declare(strict_types=1);
// @requiere: puro

require_once __DIR__ . '/../vendor/autoload.php';

$failures = [];
$assert = static function (bool $c, string $m) use (&$failures): void {
    if ($c) { fwrite(STDOUT, "PASS: {$m}\n"); return; }
    $failures[] = $m; fwrite(STDERR, "FAIL: {$m}\n");
};

$rutas = file_get_contents(__DIR__ . '/../public/index.php');
$assert(str_contains($rutas, '/plan-compras/api/plan/duraciones/obra'),
    'Las rutas de duraciones por obra están registradas.');
$assert(substr_count($rutas, '/plan-compras/api/plan/duraciones/obra') >= 2,
    'Están registrados los dos verbos: guardar y restablecer.');

$ctrl = file_get_contents(__DIR__ . '/../src/Controllers/Api/PlanComprasPlanController.php');
$assert(str_contains($ctrl, "can('lps.paquetes_contratacion.editar')"),
    'El guard de la excepción de obra exige .editar, no .reglas.');
$assert(str_contains($ctrl, 'guardEditarObra'),
    'Existe un guard propio para la excepción de obra.');
$assert(substr_count($ctrl, "'plan_compras_v2'") >= 2,
    'Las mutaciones nuevas validan CSRF del ámbito plan_compras_v2.');
$assert(str_contains($ctrl, 'DURACION_NO_DISPONIBLE'),
    'Se conserva el 403 cuando la fila no la usa esta obra.');
$assert(!str_contains($ctrl, "\$_POST['project_id']") && !str_contains($ctrl, "\$body['projectId']"),
    'El proyecto sale de la sesión y nunca del cliente.');

echo $failures === [] ? "\nOK\n" : "\n" . count($failures) . " fallo(s)\n";
exit($failures === [] ? 0 : 1);
```

- [ ] **Paso 2: Correrla y ver que falla**

```bash
docker compose exec app php tests/test_pdc_v2_duraciones_obra_contrato.php
```

Esperado: FAIL en las rutas y en el guard.

- [ ] **Paso 3: Escribir el guard de `.editar` y el validador de la fila**

En `PlanComprasPlanController.php`, junto a `guardReglas()` (línea 632):

```php
    /**
     * Guard de la EXCEPCIÓN DE OBRA, que no es la misma puerta que el catálogo global.
     *
     * `.reglas` protege `general_dias_procesos_contratacion`, el estándar de toda la empresa.
     * Corregir la duración de una obra no lo toca, así que exige `.editar`. Hoy los dos permisos
     * viajan juntos en `$allWrite` y no cambia quién puede; cambiará el día que el catálogo se
     * gobierne desde /admin/ y los dos alcances dejen de coincidir.
     */
    private function guardEditarObra(): ?int
    {
        if (!(new RbacService($this->db))->can('lps.paquetes_contratacion.editar')) {
            $this->fail('FORBIDDEN', 'No autorizado para cambiar las duraciones de esta obra.', 403);
            return null;
        }
        $projectId = (int) ($_SESSION['project_id'] ?? 0);
        if ($projectId <= 0) {
            $this->fail('NO_PROJECT', 'No hay proyecto activo. Selecciona un proyecto.', 409);
            return null;
        }
        $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['_csrf_token'] ?? '';
        if (!CsrfTokenManager::validate(is_string($csrf) ? $csrf : '', 'plan_compras_v2')) {
            $this->fail('CSRF_INVALID', 'Token CSRF inválido o ausente.', 403);
            return null;
        }
        return $projectId;
    }

    /**
     * La fila tiene que ser una que ESTA obra use: `duracionRef` llega del cliente, y sin esta
     * comprobación la pantalla de una obra podría corregir filas que no le tocan.
     */
    private function refDeEstaObra(int $projectId, mixed $bruto): ?int
    {
        $ref = filter_var($bruto, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($ref === false) {
            $this->fail('DURACION_INVALIDA', 'Falta la fila del catálogo que se quiere corregir.', 422);
            return null;
        }
        $svc = new DuracionesCatalogoService($this->db);
        if (!in_array($ref, array_column($svc->deProyecto($projectId), 'duracionRef'), true)) {
            $this->fail('DURACION_NO_DISPONIBLE', 'Esa duración no la usa ningún paquete de esta obra.', 403);
            return null;
        }
        return $ref;
    }
```

- [ ] **Paso 4: Escribir los dos métodos**

Después de `guardarDuracion()`:

```php
    /**
     * POST /plan-compras/api/plan/duraciones/obra  {duracionRef, dias:{columna: dias}}
     *
     * Corrige la duración PARA ESTA OBRA. El catálogo de la empresa no se toca, y por eso ninguna
     * otra obra cambia: las demás siguen leyendo el catálogo, que sigue diciendo lo mismo.
     * Recalcula esta obra al terminar, igual que `guardarDuracion()`.
     */
    public function guardarDuracionObra(): void
    {
        $projectId = $this->guardEditarObra();
        if ($projectId === null) {
            return;
        }
        $body = $this->body();
        $ref = $this->refDeEstaObra($projectId, $body['duracionRef'] ?? null);
        if ($ref === null) {
            return;
        }
        $dias = is_array($body['dias'] ?? null) ? $body['dias'] : null;
        if ($dias === null) {
            $this->fail('DIAS_INVALIDOS', 'Falta el detalle de días.', 422);
            return;
        }
        $r = (new DuracionesObraService($this->db))
            ->guardar($projectId, $ref, $dias, SesionUsuario::resolverId($this->db));
        if (!$r['ok']) {
            $this->fail($r['code'] ?? 'DIAS_INVALIDOS', $r['mensaje'] ?? 'No se pudo guardar.', 422);
            return;
        }
        $this->ok($this->service->calcular($projectId, $this->usuario()));
    }

    /**
     * POST /plan-compras/api/plan/duraciones/obra/borrar  {duracionRef, columnas:[…]}
     *
     * Quita la corrección y la obra vuelve al número de la empresa. No hay migración de vuelta:
     * borrar la fila ES la vuelta atrás.
     */
    public function borrarDuracionObra(): void
    {
        $projectId = $this->guardEditarObra();
        if ($projectId === null) {
            return;
        }
        $body = $this->body();
        $ref = $this->refDeEstaObra($projectId, $body['duracionRef'] ?? null);
        if ($ref === null) {
            return;
        }
        $columnas = is_array($body['columnas'] ?? null) ? array_values($body['columnas']) : null;
        if ($columnas === null) {
            $this->fail('COLUMNAS_INVALIDAS', 'Falta la lista de pasos que se quieren restablecer.', 422);
            return;
        }
        $r = (new DuracionesObraService($this->db))->borrar($projectId, $ref, $columnas);
        if (!$r['ok']) {
            $this->fail($r['code'] ?? 'COLUMNA_INVALIDA', $r['mensaje'] ?? 'No se pudo restablecer.', 422);
            return;
        }
        $this->ok($this->service->calcular($projectId, $this->usuario()));
    }
```

Añadir `use App\Services\Pdc\DuracionesObraService;` en la cabecera si falta.

- [ ] **Paso 5: Registrar las rutas**

En `public/index.php`, junto a la línea 224:

```php
$router->post('/plan-compras/api/plan/duraciones/obra', [\App\Controllers\Api\PlanComprasPlanController::class, 'guardarDuracionObra']);
$router->post('/plan-compras/api/plan/duraciones/obra/borrar', [\App\Controllers\Api\PlanComprasPlanController::class, 'borrarDuracionObra']);
```

- [ ] **Paso 6: Correr las pruebas y verlas pasar**

```bash
docker compose exec app php tests/test_pdc_v2_duraciones_obra_contrato.php
```

```bash
docker compose exec app php scripts/run-php-tests.php --nivel=puro
```

Esperado: las dos en verde. Leer el código de salida de cada una **en su propia línea**, sin encadenar.

- [ ] **Paso 7: Commit**

```bash
git add src/Controllers/Api/PlanComprasPlanController.php public/index.php tests/test_pdc_v2_duraciones_obra_contrato.php
git commit -m "feat(pdc): verbos para corregir y restablecer la duración de una obra"
```

---

### Tarea 6: La pantalla

**Archivos:**
- Crear: `pdc-app/src/lib/duracionesObra.ts`
- Crear: `pdc-app/src/lib/duracionesObra.test.ts`
- Modificar: `pdc-app/src/pages/PlanFechas.tsx:851-873`
- Crear: `tests/browser/pdc-v2-duraciones-obra.spec.mjs`

**Interfaces:**
- Consume: `duracionRef` de la fila y `colLegacy` / `origen` de cada paso (Tarea 4); los dos verbos de la Tarea 5.
- Produce: `validarDias(bruto: string): Validacion` y `esCorregido(origen: string): boolean`.

- [ ] **Paso 1: Escribir las pruebas puras que fallan**

Crear `pdc-app/src/lib/duracionesObra.test.ts`:

```ts
import { describe, it, expect } from 'vitest'
import { validarDias, esCorregido } from './duracionesObra'

describe('validarDias', () => {
  it('acepta un entero de cero o más', () => {
    expect(validarDias('120')).toEqual({ ok: true, dias: 120 })
    expect(validarDias('0')).toEqual({ ok: true, dias: 0 })
  })

  it('rechaza el vacío, porque un paso sin días no es un paso de cero días', () => {
    expect(validarDias('')).toEqual({ ok: false, motivo: 'Escribe cuántos días dura el paso.' })
  })

  it('rechaza negativos y decimales', () => {
    expect(validarDias('-1').ok).toBe(false)
    expect(validarDias('1.5').ok).toBe(false)
  })

  it('rechaza lo que no es un número', () => {
    expect(validarDias('doce').ok).toBe(false)
  })
})

describe('esCorregido', () => {
  it('distingue el número de la obra del de la empresa', () => {
    expect(esCorregido('obra')).toBe(true)
    expect(esCorregido('empresa')).toBe(false)
  })
})
```

- [ ] **Paso 2: Correrlas y ver que fallan**

```bash
cd pdc-app && npm test
```

Esperado: FAIL, no resuelve `./duracionesObra`.

- [ ] **Paso 3: Escribir la lógica pura**

Crear `pdc-app/src/lib/duracionesObra.ts`:

```ts
/**
 * Reglas de la corrección de duración por obra.
 *
 * Vive en `lib/` y no dentro del componente porque en este proyecto las pruebas son de funciones
 * puras: `pdc-app` no tiene pruebas de componente, y la pantalla se cubre con Playwright.
 */

export type Validacion = { ok: true; dias: number } | { ok: false; motivo: string }

export function validarDias(bruto: string): Validacion {
  const t = bruto.trim()
  // El vacío no es cero: un paso sin días escritos es un campo a medio llenar, y guardarlo como
  // cero movería las fechas de la obra sin que nadie lo haya pedido.
  if (t === '') return { ok: false, motivo: 'Escribe cuántos días dura el paso.' }
  if (!/^\d+$/.test(t)) return { ok: false, motivo: 'Los días son un número entero de cero o más.' }
  return { ok: true, dias: Number(t) }
}

export function esCorregido(origen: string): boolean {
  return origen === 'obra'
}
```

- [ ] **Paso 4: Correrlas y verlas pasar**

```bash
cd pdc-app && npm test
```

Esperado: las seis en verde.

- [ ] **Paso 5: Los tipos y los dos manejadores**

En `pdc-app/src/lib/types.ts`, añadir al tipo del paso del plan (el que tiene `orden`, `paso`,
`dias`, `fechaInicio`, `fechaFin`, `clave`, `vencimiento`):

```ts
  colLegacy: string | null
  origen: 'empresa' | 'obra'
```

Y al tipo de la fila del plan:

```ts
  duracionRef: number | null
```

En `pdc-app/src/pages/PlanFechas.tsx`, junto a `onRecalcular` (línea ~338), los dos manejadores.
Siguen el patrón exacto del archivo —`dispatch` de ocupado/listo/fallo y `cargar()` al terminar—;
no se introduce estado nuevo:

```tsx
  /**
   * A4.2 — la obra corrige la duración de un paso.
   *
   * Recarga entera y no solo la fila: el servidor recalcula TODO el plan de la obra, así que las
   * fechas de otros paquetes que compartan esa fila del catálogo también se movieron. Refrescar
   * solo la fila dejaría el resto de la pantalla mostrando fechas que ya no son.
   */
  const onGuardarDuracionObra = async (duracionRef: number, columna: string, dias: number) => {
    dispatch({ type: 'OCUPADO' })
    try {
      await apiPost('/plan-compras/api/plan/duraciones/obra', { duracionRef, dias: { [columna]: dias } })
      dispatch({ type: 'LISTO', mensaje: 'Duración guardada para esta obra.' })
      cargar()
    } catch (e) {
      dispatch({ type: 'FALLO', mensaje: mensajeError(e) })
      cargar()   // el campo vuelve a mostrar lo que el servidor tiene, no lo que se tecleó
    }
  }

  const onRestablecerDuracionObra = async (duracionRef: number, columna: string) => {
    dispatch({ type: 'OCUPADO' })
    try {
      await apiPost('/plan-compras/api/plan/duraciones/obra/borrar', { duracionRef, columnas: [columna] })
      dispatch({ type: 'LISTO', mensaje: 'Este paso vuelve a la duración de la empresa.' })
      cargar()
    } catch (e) {
      dispatch({ type: 'FALLO', mensaje: mensajeError(e) })
    }
  }
```

Los dos son `apiPost` por el fallo C2 del barrido previo: el `Router` no tiene `delete()` y el
cliente tampoco. No hay condicional que resolver.

- [ ] **Paso 6: Hacer editable la columna Días**

En `pdc-app/src/pages/PlanFechas.tsx`, reemplazar la celda de días del panel (línea 863). Conservar el resto de la fila tal cual:

```tsx
                  <td>{p.paso}</td>
                  <td className={esCorregido(p.origen) ? 'pdc-dias-obra' : undefined}>
                    <input
                      type="number"
                      min={0}
                      className="pdc-dias-input"
                      data-testid={`pdc-plan-paso-dias-${p.orden}`}
                      defaultValue={p.dias}
                      disabled={filaExpandida.duracionRef === null || p.colLegacy === null}
                      aria-label={`Días de «${p.paso}»${esCorregido(p.origen) ? ', corregido por esta obra' : ', valor de la empresa'}`}
                      onBlur={(e) => {
                        const v = validarDias(e.target.value)
                        if (!v.ok) {
                          dispatch({ type: 'FALLO', mensaje: v.motivo })
                          e.target.value = String(p.dias)
                          return
                        }
                        if (v.dias === p.dias) return
                        void onGuardarDuracionObra(filaExpandida.duracionRef as number, p.colLegacy as string, v.dias)
                      }}
                    />
                    {esCorregido(p.origen) && (
                      <button
                        type="button"
                        className="pdc-paq-secundario"
                        data-testid={`pdc-plan-paso-restablecer-${p.orden}`}
                        onClick={() => void onRestablecerDuracionObra(filaExpandida.duracionRef as number, p.colLegacy as string)}
                      >
                        Volver al de la empresa
                      </button>
                    )}
                  </td>
```

Un paso **sin `colLegacy`** —días fijos, que ya se configuran por obra en la pantalla de Pasos— queda deshabilitado: sería un segundo camino al mismo dato.

El origen **no puede distinguirse solo por color** (contrato de accesibilidad del repo): el `aria-label` lo dice, y la marca visual va acompañada del botón «Volver al de la empresa», que solo aparece cuando el valor es de la obra.

Y en el mismo paso, encima de la tabla del panel, el aviso de alcance **diciendo lo contrario que el
del catálogo**:

```tsx
          <p className="pdc-sub" data-testid="pdc-plan-pasos-alcance">
            Estos días son de esta obra: cambiarlos mueve las fechas de este paquete aquí, y no
            toca a las demás obras.
          </p>
```

- [ ] **Paso 7: Escribir el e2e**

Crear `tests/browser/pdc-v2-duraciones-obra.spec.mjs`. El montaje es el del test de duraciones del
catálogo que ya existe en `tests/browser/pdc-v2-pasos.spec.mjs:56` — se copia a propósito en vez de
inventar otro, porque ese ya resuelve el sandbox, el amarre y la limpieza:

```js
// tests/browser/pdc-v2-duraciones-obra.spec.mjs — la obra corrige la duración de un paso.
//
// Contra el sandbox sacrificable, no contra Da Porto: corregir una duración mueve fechas reales.
// `usarSandboxPdc()` lo resetea antes de cada test.
import { test, expect } from '@playwright/test';
import { loginAndSelectProject, logout } from './support/session.mjs';
import { PDC_SANDBOX_PROJECT, sqlEnApp, usarSandboxPdc } from './support/pdc-sandbox.mjs';

const project = PDC_SANDBOX_PROJECT;

usarSandboxPdc();

test('la obra corrige un paso, la fecha se mueve, y vuelve al número de la empresa', async ({ page }) => {
  // Un paquete con su fila de catálogo propia: 3+2+7+4+5+10+2 = 33 días.
  const montaje = JSON.parse(sqlEnApp(
    `$db->query("INSERT INTO general_dias_procesos_contratacion (paqueteContratacion, tipoPaquete, `
    + `diasElaboracionPliegos, diasEntregaPliegos, diasReciboPropuestas, diasCuadrosComparativos, `
    + `diasLegalizacionContrato, diasFabricacion, diasInsumosObra) `
    + `VALUES ('ZZTEST DUROBRA', 'a_todo_costo', 3, 2, 7, 4, 5, 10, 2)"); `
    + `$ref = (int) $db->lastInsertId(); `
    + `$db->query("INSERT INTO general_paquetes_contratacion (nombre, nombre_norm, tipo_negociacion, `
    + `modalidad_contratacion, duracion_ref, activo, creado_por, created_at) `
    + `VALUES ('ZZTEST DUROBRA', 'zztest durobra', 'a_todo_costo', 'contrato', ?, 1, 'e2e-durobra', NOW())", [$ref]); `
    + `$paq = (int) $db->lastInsertId(); `
    + `$uid = (int) $db->query('SELECT unique_id FROM programa WHERE project_id = ? AND Titulo = 1 ORDER BY Consecutivo LIMIT 1', `
    + `[${project.projectId}])->fetchColumn(); `
    + `$s = new App\\Services\\Pdc\\PlanFechasService($db); `
    + `$s->amarrar(${project.projectId}, $paq, $uid, 'e2e-durobra'); `
    + `$s->calcular(${project.projectId}, 'e2e-durobra'); `
    + `echo json_encode(['paquete' => $paq, 'ref' => $ref]);`,
  ));

  const total = () => Number(sqlEnApp(
    `echo (int) $db->query('SELECT dias_totales FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?', `
    + `[${project.projectId}, ${montaje.paquete}])->fetchColumn();`,
  ));

  try {
    expect(total(), 'punto de partida: lo que dice el catálogo').toBe(33);

    await loginAndSelectProject(page, project);
    await page.goto('/plan-compras#/ensamble/plan');

    // Desplegar el paquete abre el panel de pasos.
    await page.getByRole('cell', { name: 'ZZTEST DUROBRA' }).first().click();
    const panel = page.getByTestId('pdc-plan-detalle');
    await expect(panel).toBeVisible({ timeout: 15000 });

    // El aviso dice lo CONTRARIO que el del catálogo. Es la diferencia que evita corregir el
    // estándar de la empresa creyendo que se corrige solo esta obra.
    await expect(page.getByTestId('pdc-plan-pasos-alcance')).toContainText('son de esta obra');

    // Fabricación es el sexto paso del proceso por defecto: orden 6.
    const dias = page.getByTestId('pdc-plan-paso-dias-6');
    await expect(dias).toHaveValue('10', { timeout: 15000 });
    const fechaFinAntes = await panel.locator('tbody tr').nth(5).locator('td').nth(3).innerText();

    await dias.fill('15');
    await dias.blur();

    // Escribir, RECARGAR y recuperar: sin la recarga esto solo probaría que React repintó.
    await page.reload();
    await page.getByRole('cell', { name: 'ZZTEST DUROBRA' }).first().click();
    await expect(page.getByTestId('pdc-plan-paso-dias-6')).toHaveValue('15', { timeout: 15000 });

    expect(total(), 'el proceso se alargó los cinco días de la corrección').toBe(38);

    const fechaFinDespues = await page.getByTestId('pdc-plan-detalle')
      .locator('tbody tr').nth(5).locator('td').nth(3).innerText();
    expect(fechaFinDespues, 'la fecha del paso se movió de verdad').not.toBe(fechaFinAntes);

    // Y la vuelta atrás, que es lo que hace segura la corrección.
    await page.getByTestId('pdc-plan-paso-restablecer-6').click();
    await expect(page.getByTestId('pdc-plan-paso-dias-6')).toHaveValue('10', { timeout: 15000 });
    expect(total(), 'restablecer devuelve el número de la empresa').toBe(33);

    expect(await page.locator('body').innerText()).not.toContain('Fatal error');
  } finally {
    sqlEnApp(
      `$db->query("DELETE FROM pdc_proyecto_duraciones WHERE project_id = ${project.projectId}"); `
      + `$db->query("DELETE FROM pdc_plan_paso WHERE project_id = ${project.projectId}"); `
      + `$db->query("DELETE FROM pdc_plan_paquete WHERE project_id = ${project.projectId}"); `
      + `$db->query("DELETE FROM pdc_paquete_frente WHERE project_id = ${project.projectId}"); `
      + `$db->query("DELETE FROM general_paquetes_contratacion WHERE creado_por = 'e2e-durobra'"); `
      + `$db->query("DELETE FROM general_dias_procesos_contratacion WHERE paqueteContratacion = 'ZZTEST DUROBRA'"); echo 'ok';`,
    );
    await logout(page).catch(() => {});
  }
});
```

**El `finally` borra primero `pdc_proyecto_duraciones`**: si la prueba muere después de corregir y
antes de restablecer, la excepción sobreviviría al sandbox y envenenaría la corrida siguiente.

- [ ] **Paso 8: Correr el e2e contra ESTE código**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose up -d app
```

```bash
cd pdc-app && npm run build && cd ..
```

```bash
npx playwright test tests/browser/pdc-v2-duraciones-obra.spec.mjs --workers=1
```

Esperado: verde. **El `LPS_CODE_ROOT` no es opcional**: sin él el contenedor sirve la raíz del repo y la prueba mide otro código.

- [ ] **Paso 9: Comprobar que el plan sigue funcionando**

```bash
npx playwright test tests/browser/pdc-v2-plan.spec.mjs --workers=1
```

- [ ] **Paso 10: Devolver el contenedor a la raíz**

```bash
cd /Users/felipebenitez/Developer/lps-aia && docker compose up -d app
```

- [ ] **Paso 11: Commit**

```bash
git add pdc-app/src/lib/duracionesObra.ts pdc-app/src/lib/duracionesObra.test.ts pdc-app/src/pages/PlanFechas.tsx tests/browser/pdc-v2-duraciones-obra.spec.mjs public/pdc-app/
git commit -m "feat(pdc): corregir la duración de un paso desde el panel del plan"
```

---

## Cierre del frente

No es una tarea del plan: es el gate, y va con salida real de comandos.

- [ ] Suite PHP del nivel que toca, con el código de salida leído **en su propia línea**, nunca encadenado con `&&` a un push. Un gate encadenado ya se ejecutó: no puede impedir nada.
- [ ] `cd pdc-app && npm test` en verde.
- [ ] El e2e nuevo y `tests/browser/pdc-v2-plan.spec.mjs` en verde, con `LPS_CODE_ROOT` apuntando a este worktree.
- [ ] `docker compose exec app vendor/bin/phpstan analyse src --memory-limit=1G` sin hallazgos nuevos.
- [ ] `CHANGELOG.md` y `TASKS.md` actualizados en el mismo turno.
- [ ] **La migración en producción y el deploy NO forman parte de este cierre.** Necesitan autorización explícita de Felipe, respaldo previo y la rutina de `docs/siteground-deploy-routine.md`, con el esquema aplicado ANTES que el código — con el código publicado y la migración sin correr, toda la pestaña Plan responde 500, que ya pasó con `20260728_pdc_v2_responsable_usuario.sql`.
- [ ] El bloqueante de `TASKS.md` —replicar esto a `main`— sigue abierto y se le informa a Felipe al cerrar.
