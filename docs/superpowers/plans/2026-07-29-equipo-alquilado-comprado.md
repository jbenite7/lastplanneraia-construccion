# Equipo alquilado vs equipo comprado — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Partir el tipo de recurso «Equipo» del maestro global de insumos en **alquilado** y **comprado**, dejando los 167 equipos existentes en un estado de tránsito **sin clasificar** que se resuelve en masa desde la cola de pendientes del maestro que ya existe.

**Architecture:** `general_maestro_insumos.tipo_recurso` **no es un enum**: es `varchar(60)` sembrado por el importador SINCO (`MaestroSincoParser` lee la columna `TIPO DESCRIPCION`). Por eso no hay DDL de enum que ampliar: el cambio es de **datos + reglas de lectura**, no de esquema, salvo dos columnas nuevas de auditoría (`clasificado_por`, `clasificado_at`) que son las que hacen cumplible el punto 5. La migración reetiqueta los equipos a `EQUIPO (SIN CLASIFICAR)`; una tercera sección en la pantalla del maestro (que ya tiene pestañas) los clasifica en lote; y el importador SINCO aprende a **no pisar una clasificación humana**. El motor de sugerencias no recibe código nuevo, pero sí las tres entradas nuevas en `tiposCompatibles()` — sin ellas caerían al `default` (= no filtrar) y eso es exactamente la regresión silenciosa de A3.2.

**Tech Stack:** PHP 8.3 (`src/Services/Pdc/`, sin framework), MySQL 8.0 (migraciones `database/migrations/`, backfills `.php` con dry-run → `--apply`), SPA React + Vite + AG Grid (`pdc-app/`), Vitest, tests PHP autoejecutables (no PHPUnit), Playwright e2e.

## Global Constraints

- **Runtime:** todo PHP y MySQL corre dentro de Docker Compose. Nunca PHP del host. Este trabajo usa un stack **propio**: `COMPOSE_PROJECT_NAME=pdc-equipo`, app `8094`, db `3313`, adminer `8095`. Los stacks `last-planner-aia` (3307), `pdc-ola2` (3312) y `lps-aia-pdc` (3308) son **de otras sesiones**: no levantarlos, no recrearlos, no escribirles.
- **Aislamiento por proyecto:** toda consulta operativa se aísla por `project_id`. `general_maestro_insumos` es catálogo **global** (`general_*`, sin `project_id`) — clasificar ahí afecta a todas las obras, y de ahí sale el RBAC.
- **RBAC:** clasificar exige **`lps.pdc.maestro`** (descripción literal en `RbacCatalog.php:119`: «Administrar el maestro global de insumos del plan de compras v2»). **No** `lps.paquetes_contratacion.reglas`, que gobierna reglas y overrides del motor. Lectura con `lps.pdc.ver`. CSRF form key `plan_compras_v2` en toda mutación.
- **Valores canónicos exactos** (decisión del usuario: adoptar lo que SINCO ya emite):
  - `ALQUILER EQUIPOS` — equipo alquilado. **Ya existe en el maestro** (2 filas). No se inventa nombre nuevo.
  - `EQUIPO COMPRADO` — equipo comprado. SINCO **no** emite ningún valor para esto (los 53 «compra» llegan como `EQUIPO` con `agrupacion` `COMPRA ELEMENTOS-…`), así que aquí no hay nada que adoptar y el valor es nuevo.
  - `EQUIPO (SIN CLASIFICAR)` — estado de tránsito.
  - El valor viejo `EQUIPO` queda **sin uso** tras la migración, pero el código sigue reconociéndolo: el importador SINCO lo recibirá en cada carga futura.
- **Prohibido adivinar por el nombre del insumo.** Descartado en el grilleo. La cola **muestra** el `agrupacion` de SINCO como evidencia y preordena por él, pero **nunca escribe** una clasificación sin confirmación humana.
- **UI:** solo desktop ≥1180 px (viewport canónico 1180×820) y solo dark mode. Prohibido producir cambios, pruebas o evidencia para mobile, tablet o tema `linen`. Tokens y primitivas `aia-*` / clases `pdc-*` existentes; sin hex ni estilos inline.
- **Sin commits, push ni deploy** salvo petición explícita del usuario. Los pasos «Commit» del plan quedan **pendientes de esa autorización**: ejecútalos solo si el usuario la da.
- **Idioma:** dominio y comentarios en español; identificadores y rutas en su idioma original.

---

## Enmiendas tras integrar `origin/main` (2026-07-29, antes de ejecutar)

El plan se escribió sobre `1a75b19`. Al liberarse la tarea se integró `origin/main`, que trajo la fila 2, la curva de caja, el re-matching, los subpaquetes y un cambio **en la zona exacta de este trabajo**. Tres enmiendas:

1. **`tipo_recurso` sigue siendo `varchar(60)`.** Verificado tras el merge: la premisa central del plan se sostiene, no hay enum que ampliar.
2. **No hace falta re-enganchar la cola de vínculos.** `main` añadió `MaestroInsumosService::reengancharPendientes()` (línea 459) porque cargar el maestro SINCO no volvía a mirar la cola. **No aplica a esta migración:** ese UPDATE empareja por `descripcion_norm` + `unidad`, y ninguna consulta de `pdc_insumo_vinculos` lee `tipo_recurso`. Reetiquetar equipos no puede alterar un vínculo. Se añade como **aserción** en Task 3 (demostrar la independencia) en vez de como llamada — llamar a `reengancharPendientes()` aquí sería carga muerta que además tocaría filas de otras obras sin motivo.
3. **Los tests siembran sus propios equipos, no mutan los 167 reales.** `test_pdc_v2_reenganche_pendientes.php` y `test_pdc_v2_maestro_gobernado.php` fijaron el patrón del repo: un `project_id` dedicado, una marca en `creado_por`, y limpieza al entrar y al salir. Se adopta. **Consecuencia:** el Task 6 Step 5 del plan (revertir y reaplicar la migración para restaurar la cola tras clasificar 20 filas reales) **se elimina** — con equipos sembrados no hay nada que restaurar, y aquel `UPDATE … WHERE clasificado_por LIKE '%@aia'` era un borrado por patrón sobre una tabla global, exactamente el tipo de operación que no debe estar en un test. El test RBAC se basa en `test_pdc_v2_maestro_gobernado.php`, que resuelve contra `RbacService` y la BD real (no contra el catálogo en código, porque `getPermissionMap()` lee primero `rbac_role_permissions` y un entorno sembrado puede contradecir al catálogo).

**Límite de alcance de esta sesión:** se entrega migración, código y pruebas en la rama `worktree-pdc-ola2-equipo-alq-comp`. **No se aplica nada al servidor** — el despliegue de esta migración es de la sesión de despliegue, con su respaldo y su orden escrito. Permisos de git: commitear en la rama propia y traer `origin/main`, sí; empujar o escribir en `main`, no.

---

## Hechos medidos (no re-derivar)

Censo del maestro real, tomado en solo lectura del stack `last-planner-aia` (3307) el 2026-07-29:

| Dato | Valor |
|---|---|
| `tipo_recurso = 'EQUIPO'` | **167** filas, **todas** con `codigo_sinco` (ninguna huérfana) |
| De esos, vinculados a algún presupuesto | 36 |
| `tipo_recurso = 'ALQUILER EQUIPOS'` | 2 (uno es `ALQUILER DE LAMINA CUBRE BRECHA`, otro `ALQUILER LICENCIAS DE COMPUTO`) |
| `agrupacion` de los 167, por prefijo | `ALQUILER` 89 · `COMPRA` 53 · `COMPRAS` 3 · `MTTO` 17 · `COMPRAS` 3 · `MAT-HERRAMIENTA` 3 · `GASTOS` 2 |
| Otros `tipo_recurso` | MATERIAL 1636 · SUBCONTRATO 849 · MANO DE OBRA 206 · NOMINA 91 · CONSUMIBLES 58 · TRANSPORTE 47 · HONORARIOS 21 · INSUM EXCLUSIVO PPTO 2 |

**El tapón son 167 filas, no cientos.** 145 traen la respuesta escrita en `agrupacion` por el equipo de presupuestos.

### Gastos generales — el hecho aparte que pedía el spec

**El presupuesto no trae categorías de gastos generales que el maestro pierda.** Los capítulos de nivel 1 de DAPORTO son exactamente dos: `COSTO DIRECTO` y `COSTO INDIRECTO`. Lo que Tomás llama «unas categorías de gastos generales que le creé a mi código» no viene por el presupuesto: viene por el **`agrupacion` de SINCO**, que el maestro **sí guarda ya** (`GASTOS MEDICOS Y DROGAS PERSONAL OBRA`, `COMPRA ELEMENTOS- EQUIPO DE OFICINA`, …). Conclusión: **no hay dato perdido, hay dato no explotado** — el maestro guarda el `agrupacion` pero ninguna pantalla lo agrupa ni filtra por él. Eso se registra en la Task 8 como hecho documentado, y **no se implementa aquí**: es un entregable distinto (una vista del maestro por agrupación) que merece su propio grilleo con Tomás para no duplicar lo que él ya tiene en su código.

### La trampa de A3.2, en su versión nueva

Cuando el bucket de indirectos se partió en A3.2 aparecieron paquetes arrastrando el tipo viejo, y la lección quedó escrita en `docs/pdc-v2.md`: **reetiquetar por regla, no por lista de nombres**, y **la constante PHP y el `types.ts` de la SPA tienen que coincidir exactamente** o falla en silencio hasta que alguien intenta guardar. Aquí la trampa reaparece en un sitio nuevo y peor:

`PaquetesService::tiposCompatibles()` (línea 1858) es un `match` sobre el string, con `default => self::TIPOS` = **no filtrar nada**. Hoy `'EQUIPO', 'TRANSPORTE' => ['suministro','a_todo_costo','consumibles']`. Si la migración renombra los equipos y nadie toca ese `match`, los 167 caen al `default` y el motor **deja de filtrarlos**: pasan a ser candidatos de cualquier paquete, incluido mano de obra. Es una regresión silenciosa que ningún test de hoy atrapa. La Task 4 existe solo para esto.

### Por qué el punto 5 tiene dos caras

«Reimportar un presupuesto no devuelve a sin clasificar un insumo ya clasificado.»

1. **Presupuesto: ya está a salvo, y hay que dejarlo probado.** `MaestroInsumosService` crea filas desde el presupuesto **sin** `tipo_recurso` (los INSERT de las líneas 295 y 347 no incluyen la columna) y sus UPDATE solo tocan `activo`. Un re-import de presupuesto no puede pisar la clasificación. No hay que arreglar nada; hay que **fijarlo con un test** para que nadie lo rompa después.
2. **SINCO: aquí sí está roto, y es el riesgo real.** `MaestroSincoImportService` líneas 84-90 y 104-110 hacen `SET … tipo_recurso = ?` a ciegas por `codigo_sinco`. Como los 167 equipos **todos tienen `codigo_sinco`**, la próxima carga del maestro SINCO los devolvería a `EQUIPO` y borraría el trabajo humano. Eso lo arregla la Task 5.

---

## File Structure

| Archivo | Responsabilidad |
|---|---|
| `database/migrations/20260729_pdc_v2_equipo_alquilado_comprado.sql` | DDL: `clasificado_por`, `clasificado_at`. Reversible con `DROP COLUMN`. |
| `database/migrations/20260729_pdc_v2_equipo_sin_clasificar.php` | Backfill dry-run → `--apply` → `--revertir`. Reetiqueta por regla. Idempotente. |
| `src/Services/Pdc/TipoRecursoEquipo.php` | **Nuevo.** Única fuente de verdad de los tres valores, la pista de `agrupacion` y qué cuenta como equipo. Sin dependencias — testeable en seco. |
| `src/Services/Pdc/MaestroInsumosService.php` | Métodos nuevos: `equiposSinClasificar()`, `clasificarEquipos()`. |
| `src/Services/Pdc/MaestroSincoImportService.php` | La clasificación humana gana sobre el genérico entrante. |
| `src/Services/Pdc/PaquetesService.php` | Tres entradas en `tiposCompatibles()`. Nada más. |
| `src/Controllers/Api/PlanComprasMaestroController.php` | `equipos()` (GET, lectura) y `clasificarEquipos()` (POST, escritura). |
| `public/index.php` | Dos rutas. |
| `pdc-app/src/lib/tipoRecurso.ts` | **Nuevo.** Espejo exacto de los valores PHP + etiquetas de UI. |
| `pdc-app/src/lib/tipoRecurso.test.ts` | **Nuevo.** Fija los valores para que la divergencia con PHP no se repita en silencio. |
| `pdc-app/src/lib/types.ts` | Tipo `EquipoSinClasificar`. |
| `pdc-app/src/pages/MaestroInsumos.tsx` | Sección nueva en las pestañas que ya existen. Sin pantalla nueva. |
| `tests/test_pdc_v2_equipo_clasificacion.php` | **Nuevo.** Puntos 1, 2, 3, 5, 6 de la condición de hecho. |
| `tests/test_pdc_v2_paquetes_motor.php` | Punto 4: alquilado no es candidato de un paquete de compra. |
| `docs/pdc-v2.md` | Deuda saldada + el hecho de gastos generales. |

---

## Task 0: Levantar el stack propio y la línea base

Sin esto, cualquier `docker compose` desde este worktree recrea el stack de otra sesión (el `name:` del compose es fijo) y le escribe en la base.

**Files:** solo `.env` local (no versionado).

- [ ] **Step 1: Copiar el `.env`** — el worktree no tiene uno.

```bash
cp "/Volumes/Crucial X6/Developer/lps-aia/.env" .env
```

- [ ] **Step 2: Levantar stack propio con nombre y puertos que nadie usa**

```bash
COMPOSE_PROJECT_NAME=pdc-equipo DOCKER_DB_PORT=3313 DOCKER_ADMINER_PORT=8095 docker compose up -d db adminer
```

- [ ] **Step 3: Comprobar que no se pisó ningún stack ajeno**

Run: `docker ps --format '{{.Names}}\t{{.Ports}}'`
Expected: aparecen `pdc-equipo-db-1` en `3313` **y siguen vivos e intactos** `last-planner-aia-db-1` (3307), `pdc-ola2-db-1` (3312). Si alguno de esos dos desapareció o cambió de puerto, **para y avisa**: se recreó un stack ajeno.

- [ ] **Step 4: Sembrar la base propia con un volcado de la de referencia**

```bash
docker exec last-planner-aia-db-1 mysqldump -uroot -p"$DB_PASS" --single-transaction --routines lastplanneraia_dev > /tmp/base-equipo.sql
docker exec -i pdc-equipo-db-1 mysql -uroot -p"$DB_PASS" -e "CREATE DATABASE IF NOT EXISTS lastplanneraia_dev"
docker exec -i pdc-equipo-db-1 mysql -uroot -p"$DB_PASS" lastplanneraia_dev < /tmp/base-equipo.sql
```

- [ ] **Step 5: Verificar el censo en la base propia** — es la línea base contra la que se mide la migración.

```bash
docker exec pdc-equipo-db-1 mysql -uroot -p"$DB_PASS" lastplanneraia_dev -N -e \
 "SELECT tipo_recurso, COUNT(*) FROM general_maestro_insumos GROUP BY tipo_recurso ORDER BY 2 DESC"
```

Expected: `EQUIPO 167`, `ALQUILER EQUIPOS 2`. Si `EQUIPO` no es 167, **anota el número real** y úsalo como línea base en las tasks siguientes en lugar de 167.

- [ ] **Step 6: Levantar `app` y correr la línea base de tests**

```bash
COMPOSE_PROJECT_NAME=pdc-equipo docker compose up -d app
COMPOSE_PROJECT_NAME=pdc-equipo docker compose exec app composer install
for t in test_global_table_safety test_pdc_v2_paquetes_motor test_pdc_v2_maestro_sinco_import test_pdc_v2_paquetes; do
  echo "== $t"; COMPOSE_PROJECT_NAME=pdc-equipo docker compose exec -T app php "tests/$t.php" >/dev/null 2>&1; echo "rc=$?"
done
```

Expected: `rc=0` en los cuatro. **Mide por `rc`, no por grep de «FAIL»** (el grep miente). Si alguno viene rojo, anótalo como **rojo preexistente** antes de tocar código — hay 16 tests PHP que fallan solos, y confundir uno de ellos con una regresión propia cuesta horas.

---

## Task 1: La fuente de verdad de los valores (PHP, en seco)

Un solo sitio decide qué strings existen y qué cuenta como equipo. Sin BD, sin HTTP: testeable en seco y barato de razonar.

**Files:**
- Create: `src/Services/Pdc/TipoRecursoEquipo.php`
- Create: `tests/test_pdc_v2_equipo_clasificacion.php`

**Interfaces:**
- Produces:
  - `TipoRecursoEquipo::SIN_CLASIFICAR = 'EQUIPO (SIN CLASIFICAR)'`
  - `TipoRecursoEquipo::ALQUILADO = 'ALQUILER EQUIPOS'`
  - `TipoRecursoEquipo::COMPRADO = 'EQUIPO COMPRADO'`
  - `TipoRecursoEquipo::GENERICO = 'EQUIPO'`
  - `TipoRecursoEquipo::CLASIFICADOS: list<string>` — los dos destinos válidos
  - `TipoRecursoEquipo::esEquipo(?string $t): bool`
  - `TipoRecursoEquipo::esClasificado(?string $t): bool`
  - `TipoRecursoEquipo::esDestinoValido(string $t): bool`
  - `TipoRecursoEquipo::pistaSinco(?string $agrupacion): ?string` — devuelve `ALQUILADO`, `COMPRADO` o `null`

- [ ] **Step 1: Write the failing test**

Crea `tests/test_pdc_v2_equipo_clasificacion.php`. Sigue el patrón de los tests PHP del repo: script autoejecutable, sin runner, `$assert` propio, exit code 1 si algo falla.

```php
<?php
// Clasificación de equipos (alquilado / comprado / sin clasificar) — Ola 2.
// Cubre los puntos 1, 2, 3, 5 y 6 de la condición de hecho del spec
// docs/superpowers/specs/2026-07-29-equipo-alquilado-comprado-design.md
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\Pdc\TipoRecursoEquipo;

$fallos = 0;
$assert = static function (bool $ok, string $que) use (&$fallos): void {
    echo ($ok ? '  ok   ' : '  FAIL ') . $que . PHP_EOL;
    if (!$ok) { $fallos++; }
};

echo "== TipoRecursoEquipo (en seco)" . PHP_EOL;

// Los valores exactos. Si alguien los cambia, este test lo dice antes que la base de datos.
$assert(TipoRecursoEquipo::SIN_CLASIFICAR === 'EQUIPO (SIN CLASIFICAR)', 'SIN_CLASIFICAR es el string exacto.');
$assert(TipoRecursoEquipo::ALQUILADO === 'ALQUILER EQUIPOS', 'ALQUILADO adopta el valor que SINCO ya emite.');
$assert(TipoRecursoEquipo::COMPRADO === 'EQUIPO COMPRADO', 'COMPRADO es el valor nuevo.');
$assert(TipoRecursoEquipo::GENERICO === 'EQUIPO', 'GENERICO conserva el valor viejo de SINCO.');

// Qué cuenta como equipo: los cuatro, y nada más.
$assert(TipoRecursoEquipo::esEquipo('EQUIPO') === true, 'El genérico es equipo.');
$assert(TipoRecursoEquipo::esEquipo('EQUIPO (SIN CLASIFICAR)') === true, 'El de tránsito es equipo.');
$assert(TipoRecursoEquipo::esEquipo('ALQUILER EQUIPOS') === true, 'El alquilado es equipo.');
$assert(TipoRecursoEquipo::esEquipo('EQUIPO COMPRADO') === true, 'El comprado es equipo.');
$assert(TipoRecursoEquipo::esEquipo('MATERIAL') === false, 'Un material no es equipo.');
$assert(TipoRecursoEquipo::esEquipo('TRANSPORTE') === false, 'Transporte NO es equipo: tiene su propio tipo y no entra a esta cola.');
$assert(TipoRecursoEquipo::esEquipo(null) === false, 'NULL no es equipo (los insumos nacidos del presupuesto llegan sin tipo).');

// Insensible a mayúsculas y espacios: el dato viene de un Excel.
$assert(TipoRecursoEquipo::esEquipo('  equipo  ') === true, 'esEquipo normaliza caja y espacios.');

// Clasificado = ya tiene respuesta humana. El genérico y el de tránsito NO lo están.
$assert(TipoRecursoEquipo::esClasificado('ALQUILER EQUIPOS') === true, 'Alquilado está clasificado.');
$assert(TipoRecursoEquipo::esClasificado('EQUIPO COMPRADO') === true, 'Comprado está clasificado.');
$assert(TipoRecursoEquipo::esClasificado('EQUIPO') === false, 'El genérico no está clasificado.');
$assert(TipoRecursoEquipo::esClasificado('EQUIPO (SIN CLASIFICAR)') === false, 'El de tránsito no está clasificado.');
$assert(TipoRecursoEquipo::esClasificado('MATERIAL') === false, 'Un material no es un equipo clasificado.');

// Sólo se puede clasificar HACIA los dos destinos. Nadie vuelve a «sin clasificar» por la API.
$assert(TipoRecursoEquipo::esDestinoValido('ALQUILER EQUIPOS') === true, 'Alquilado es destino válido.');
$assert(TipoRecursoEquipo::esDestinoValido('EQUIPO COMPRADO') === true, 'Comprado es destino válido.');
$assert(TipoRecursoEquipo::esDestinoValido('EQUIPO (SIN CLASIFICAR)') === false, 'El tránsito NO es un destino: clasificar es avanzar.');
$assert(TipoRecursoEquipo::esDestinoValido('MATERIAL') === false, 'No se cambia un equipo a material por esta puerta.');

// La pista de SINCO: sugiere, no decide. Es el `agrupacion` que escribió presupuestos.
$assert(TipoRecursoEquipo::pistaSinco('ALQUILER MAQUINARIA Y EQUIPOS') === TipoRecursoEquipo::ALQUILADO, 'Prefijo ALQUILER sugiere alquilado.');
$assert(TipoRecursoEquipo::pistaSinco('ALQUILER BIENES MUEBLES') === TipoRecursoEquipo::ALQUILADO, 'Otro ALQUILER sugiere alquilado.');
$assert(TipoRecursoEquipo::pistaSinco('COMPRA ELEMENTOS- MAQUINARIA Y EQUIPO') === TipoRecursoEquipo::COMPRADO, 'Prefijo COMPRA sugiere comprado.');
$assert(TipoRecursoEquipo::pistaSinco('COMPRAS DE INSUMOS MENORES') === TipoRecursoEquipo::COMPRADO, 'El plural COMPRAS también.');
$assert(TipoRecursoEquipo::pistaSinco('MTTO COMPRA MAQUINARIA Y EQUIPO') === null, 'MTTO no sugiere: mantenimiento no dice si el equipo es propio o alquilado.');
$assert(TipoRecursoEquipo::pistaSinco('MAT-HERRAMIENTA EQUIPO MENOR Y CONSUMIBLES') === null, 'Sin prefijo reconocible, no se sugiere.');
$assert(TipoRecursoEquipo::pistaSinco('GASTOS MEDICOS Y DROGAS PERSONAL OBRA') === null, 'GASTOS no sugiere nada.');
$assert(TipoRecursoEquipo::pistaSinco(null) === null, 'Sin agrupación no hay pista.');
$assert(TipoRecursoEquipo::pistaSinco('') === null, 'Agrupación vacía no da pista.');

// La pista NO mira la descripción del insumo. Eso está prohibido por el grilleo.
$assert(TipoRecursoEquipo::pistaSinco('ALQUILER DE LAMINA') === TipoRecursoEquipo::ALQUILADO, 'La pista sólo lee agrupacion; la firma no acepta descripción.');

echo PHP_EOL . ($fallos === 0 ? "TODO OK" : "{$fallos} FALLOS") . PHP_EOL;
exit($fallos === 0 ? 0 : 1);
```

- [ ] **Step 2: Run test to verify it fails**

Run: `COMPOSE_PROJECT_NAME=pdc-equipo docker compose exec -T app php tests/test_pdc_v2_equipo_clasificacion.php; echo "rc=$?"`
Expected: FAIL — `Class "App\Services\Pdc\TipoRecursoEquipo" not found`, `rc` distinto de 0.

- [ ] **Step 3: Write minimal implementation**

Crea `src/Services/Pdc/TipoRecursoEquipo.php`:

```php
<?php

declare(strict_types=1);

namespace App\Services\Pdc;

/**
 * Los valores de `general_maestro_insumos.tipo_recurso` que designan equipo, y la pista de SINCO.
 *
 * `tipo_recurso` es varchar(60), no un enum: lo siembra el importador SINCO desde la columna
 * «TIPO DESCRIPCION». Esta clase es el único sitio donde viven los strings, para que partir
 * «Equipo» no se convierta en literales sueltos por el código — el error que ya se pagó al partir
 * el bucket de indirectos en A3.2 (ver docs/pdc-v2.md §deudas de datos saldadas).
 *
 * OJO: el espejo de estos valores en la SPA es `pdc-app/src/lib/tipoRecurso.ts`, y tienen que
 * coincidir EXACTAMENTE. Cada lado tiene un test que fija los strings, por lo mismo que se fijaron
 * los cinco de TIPOS_NEGOCIACION: una divergencia no rompe nada visible hasta que alguien guarda.
 */
final class TipoRecursoEquipo
{
    /** El valor que SINCO viene emitiendo y que ya no clasifica nada. */
    public const GENERICO = 'EQUIPO';

    /** Estado de tránsito: sabemos que es equipo, no sabemos si se alquila o se compra. */
    public const SIN_CLASIFICAR = 'EQUIPO (SIN CLASIFICAR)';

    /** Adopta el valor que SINCO ya emite (2 filas en el maestro), en vez de inventar un sinónimo. */
    public const ALQUILADO = 'ALQUILER EQUIPOS';

    /** Valor nuevo: SINCO no emite ninguno para esto (los «compra» le llegan como EQUIPO). */
    public const COMPRADO = 'EQUIPO COMPRADO';

    /** Los dos destinos a los que un humano puede llevar un equipo. */
    public const CLASIFICADOS = [self::ALQUILADO, self::COMPRADO];

    /** Prefijos de `agrupacion` que sugieren un destino. Deliberadamente cortos y sin ambigüedad. */
    private const PISTAS = [
        'ALQUILER' => self::ALQUILADO,
        'ARRIENDO' => self::ALQUILADO,
        'COMPRA' => self::COMPRADO,
        'COMPRAS' => self::COMPRADO,
    ];

    private static function norm(?string $t): string
    {
        return mb_strtoupper(trim((string) $t));
    }

    /** ¿Este tipo de recurso designa equipo, en cualquiera de sus cuatro formas? */
    public static function esEquipo(?string $tipo): bool
    {
        return in_array(
            self::norm($tipo),
            [self::GENERICO, self::SIN_CLASIFICAR, self::ALQUILADO, self::COMPRADO],
            true,
        );
    }

    /** ¿Un humano ya dijo si se alquila o se compra? */
    public static function esClasificado(?string $tipo): bool
    {
        return in_array(self::norm($tipo), self::CLASIFICADOS, true);
    }

    /** ¿Es un destino al que la API permite mover un equipo? «Sin clasificar» no lo es: clasificar avanza. */
    public static function esDestinoValido(string $tipo): bool
    {
        return in_array(self::norm($tipo), self::CLASIFICADOS, true);
    }

    /**
     * Destino que sugiere la agrupación SINCO, o null si no dice nada.
     *
     * Es una SUGERENCIA para mostrar en la cola con su evidencia, nunca una escritura automática:
     * adivinar sin confirmación humana está descartado por el grilleo. Sólo lee `agrupacion` —un
     * campo que el equipo de presupuestos escribió a propósito—, jamás la descripción del insumo.
     *
     * `MTTO …` no sugiere nada a propósito: mantener un equipo no dice de quién es.
     */
    public static function pistaSinco(?string $agrupacion): ?string
    {
        $primera = explode(' ', self::norm($agrupacion))[0] ?? '';
        if ($primera === '') {
            return null;
        }
        return self::PISTAS[$primera] ?? null;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `COMPOSE_PROJECT_NAME=pdc-equipo docker compose exec -T app php tests/test_pdc_v2_equipo_clasificacion.php; echo "rc=$?"`
Expected: `TODO OK`, `rc=0`.

- [ ] **Step 5: Commit** *(solo si el usuario autorizó publicar)*

```bash
git add src/Services/Pdc/TipoRecursoEquipo.php tests/test_pdc_v2_equipo_clasificacion.php
git commit -m "feat(pdc): un solo sitio decide qué string es un equipo alquilado o comprado"
```

---

## Task 2: Auditoría de la clasificación (DDL)

Dos columnas. Son lo que hace **verificable** el punto 5: sin ellas, «este equipo lo clasificó un humano» y «este equipo lo trajo así el Excel» son indistinguibles, y el importador SINCO no puede saber a quién respetar. Es la misma lección de B1: un NULL mudo no se distingue de un cálculo que nunca corrió.

**Files:**
- Create: `database/migrations/20260729_pdc_v2_equipo_alquilado_comprado.sql`

- [ ] **Step 1: Escribir el DDL**

```sql
-- 20260729_pdc_v2_equipo_alquilado_comprado.sql
-- Ola 2 — Equipo alquilado vs comprado.
--
-- NO amplía ningún enum: `tipo_recurso` es varchar(60) y admite los valores nuevos sin DDL.
-- Lo que falta es la AUDITORÍA: quién clasificó y cuándo. Sin ese par, el importador SINCO no
-- puede distinguir «lo dijo un humano» de «lo trajo el Excel», y el punto 5 de la condición de
-- hecho (reimportar no borra la clasificación) no sería verificable, sólo afirmable.
--
-- Vuelta atrás: DROP de las dos columnas (ver el bloque --revertir del backfill .php).

ALTER TABLE `general_maestro_insumos`
  ADD COLUMN `clasificado_por` varchar(120) DEFAULT NULL AFTER `tipo_recurso`,
  ADD COLUMN `clasificado_at` datetime DEFAULT NULL AFTER `clasificado_por`;
```

- [ ] **Step 2: Comprobar que las columnas no existen ya** — la migración no es idempotente por sí sola.

```bash
docker exec pdc-equipo-db-1 mysql -uroot -p"$DB_PASS" lastplanneraia_dev -N -e \
 "SELECT COLUMN_NAME FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA='lastplanneraia_dev' AND TABLE_NAME='general_maestro_insumos'
    AND COLUMN_NAME IN ('clasificado_por','clasificado_at')"
```

Expected: salida vacía. Si ya están, la migración ya corrió: salta al Step 3 de verificación.

- [ ] **Step 3: Aplicar**

```bash
docker exec -i pdc-equipo-db-1 mysql -uroot -p"$DB_PASS" lastplanneraia_dev \
  < database/migrations/20260729_pdc_v2_equipo_alquilado_comprado.sql
```

- [ ] **Step 4: Verificar**

```bash
docker exec pdc-equipo-db-1 mysql -uroot -p"$DB_PASS" lastplanneraia_dev -e \
 "SHOW COLUMNS FROM general_maestro_insumos LIKE 'clasificado%'"
```

Expected: dos filas, ambas `YES` en Null y `NULL` en Default.

- [ ] **Step 5: Probar la vuelta atrás AHORA, no al final**

```bash
docker exec pdc-equipo-db-1 mysql -uroot -p"$DB_PASS" lastplanneraia_dev -e \
 "ALTER TABLE general_maestro_insumos DROP COLUMN clasificado_por, DROP COLUMN clasificado_at"
docker exec pdc-equipo-db-1 mysql -uroot -p"$DB_PASS" lastplanneraia_dev -e \
 "SHOW COLUMNS FROM general_maestro_insumos LIKE 'clasificado%'"
```

Expected: la segunda salida vacía. Luego **vuelve a aplicar** el Step 3 para seguir trabajando. Esto es lo que convierte «reversible» en un hecho medido.

- [ ] **Step 6: Commit** *(solo si el usuario autorizó publicar)*

```bash
git add database/migrations/20260729_pdc_v2_equipo_alquilado_comprado.sql
git commit -m "feat(pdc): el maestro registra quién clasificó un equipo y cuándo"
```

---

## Task 3: La migración de los 167 (backfill reversible)

Reetiqueta **por regla** (`tipo_recurso` genérico), nunca por lista de nombres — la lección literal de A3.2.

**Files:**
- Create: `database/migrations/20260729_pdc_v2_equipo_sin_clasificar.php`
- Modify: `tests/test_pdc_v2_equipo_clasificacion.php` (añadir el bloque de BD)

**Interfaces:**
- Consumes: `TipoRecursoEquipo::GENERICO`, `::SIN_CLASIFICAR` (Task 1); columnas de Task 2.
- Produces: un script con tres modos — sin flags = dry-run; `--apply`; `--revertir`.

- [ ] **Step 1: Escribir el backfill**

Sigue el patrón de `20260728_pdc_v2_tipo_no_aplica.php` (dry-run → `--apply`, idempotente).

```php
<?php
/**
 * 20260729_pdc_v2_equipo_sin_clasificar.php
 *
 * Ola 2 — los equipos que ya existen pasan a «EQUIPO (SIN CLASIFICAR)».
 *
 * Decisión deliberada del usuario, contra la opción barata de mandarlos todos a «comprado»: nadie
 * afirma lo que no sabe. Genera un tapón de decisiones humanas, y se asume — «sin clasificar» se
 * comporta exactamente como el «EQUIPO» de hoy, ni mejor ni peor (el filtro del motor es el mismo,
 * ver Task 4).
 *
 * REGLA, NO LISTA DE NOMBRES: mueve las filas cuyo `tipo_recurso` es el genérico `EQUIPO`. Cuando el
 * bucket de indirectos se partió en A3.2 se reetiquetó por lista y aparecieron paquetes arrastrando
 * el tipo viejo (docs/pdc-v2.md §deudas de datos saldadas).
 *
 * NO TOCA las filas ya clasificadas: las 2 que traen `ALQUILER EQUIPOS` de SINCO se quedan como
 * están — ya tienen la respuesta, degradarlas sería perder dato.
 *
 * Uso:
 *   php database/migrations/20260729_pdc_v2_equipo_sin_clasificar.php              # dry-run
 *   php database/migrations/20260729_pdc_v2_equipo_sin_clasificar.php --apply
 *   php database/migrations/20260729_pdc_v2_equipo_sin_clasificar.php --revertir   # vuelta atrás
 *
 * Idempotente: la segunda corrida de --apply escribe 0 filas.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Services\Pdc\TipoRecursoEquipo;

$apply = in_array('--apply', $argv, true);
$revertir = in_array('--revertir', $argv, true);

if ($apply && $revertir) {
    fwrite(STDERR, "--apply y --revertir son excluyentes.\n");
    exit(1);
}

$db = \Database::getInstance();

$GEN = TipoRecursoEquipo::GENERICO;
$SIN = TipoRecursoEquipo::SIN_CLASIFICAR;

if ($revertir) {
    // Vuelta atrás: los «sin clasificar» regresan a «EQUIPO». Los que un humano YA clasificó se
    // quedan clasificados a propósito: revertir la migración no es borrar trabajo humano.
    $n = (int) $db->query(
        'SELECT COUNT(*) FROM general_maestro_insumos WHERE tipo_recurso = ?',
        [$SIN],
    )->fetchColumn();
    $clasificados = (int) $db->query(
        'SELECT COUNT(*) FROM general_maestro_insumos WHERE tipo_recurso IN (?, ?) AND clasificado_at IS NOT NULL',
        TipoRecursoEquipo::CLASIFICADOS,
    )->fetchColumn();

    echo "REVERTIR: {$n} filas «{$SIN}» → «{$GEN}».\n";
    echo "Se CONSERVAN {$clasificados} clasificadas por un humano (revertir no borra su trabajo).\n";

    if (!$apply && !$revertir) { exit(0); }

    $db->query(
        'UPDATE general_maestro_insumos SET tipo_recurso = ?, updated_at = NOW() WHERE tipo_recurso = ?',
        [$GEN, $SIN],
    );
    echo "Hecho.\n";
    exit(0);
}

// Dry-run / apply.
$aMover = (int) $db->query(
    'SELECT COUNT(*) FROM general_maestro_insumos WHERE UPPER(TRIM(tipo_recurso)) = ?',
    [$GEN],
)->fetchColumn();

$yaEnTransito = (int) $db->query(
    'SELECT COUNT(*) FROM general_maestro_insumos WHERE tipo_recurso = ?',
    [$SIN],
)->fetchColumn();

$yaClasificados = (int) $db->query(
    'SELECT COUNT(*) FROM general_maestro_insumos WHERE tipo_recurso IN (?, ?)',
    TipoRecursoEquipo::CLASIFICADOS,
)->fetchColumn();

// Cuánto del tapón trae la respuesta escrita en `agrupacion`. NO se aplica: se informa, para que
// quien mire la cola sepa cuánto trabajo es de verdad ciego.
$conPista = 0;
$filas = $db->query(
    'SELECT agrupacion FROM general_maestro_insumos WHERE UPPER(TRIM(tipo_recurso)) = ?',
    [$GEN],
)->fetchAll(\PDO::FETCH_COLUMN);
foreach ($filas as $agr) {
    if (TipoRecursoEquipo::pistaSinco(is_string($agr) ? $agr : null) !== null) { $conPista++; }
}

echo ($apply ? "APLICANDO" : "DRY-RUN (usa --apply para escribir)") . "\n";
echo "  A mover a «{$SIN}»: {$aMover}\n";
echo "  Ya en tránsito (no se tocan): {$yaEnTransito}\n";
echo "  Ya clasificados (no se tocan): {$yaClasificados}\n";
echo "  Del tapón, con pista SINCO en `agrupacion`: {$conPista} de {$aMover} (se muestra en la cola, NO se escribe)\n";

if (!$apply) {
    exit(0);
}

$db->beginTransaction();
try {
    // clasificado_por/at quedan NULL a propósito: nadie ha clasificado nada todavía. Eso es
    // justo lo que hace que el importador SINCO sepa que puede pisar esta fila (Task 5).
    $db->query(
        'UPDATE general_maestro_insumos SET tipo_recurso = ?, updated_at = NOW()
         WHERE UPPER(TRIM(tipo_recurso)) = ?',
        [$SIN, $GEN],
    );
    $db->commit();
} catch (\Throwable $t) {
    $db->rollBack();
    throw $t;
}

$quedan = (int) $db->query(
    'SELECT COUNT(*) FROM general_maestro_insumos WHERE UPPER(TRIM(tipo_recurso)) = ?',
    [$GEN],
)->fetchColumn();
$enTransito = (int) $db->query(
    'SELECT COUNT(*) FROM general_maestro_insumos WHERE tipo_recurso = ?',
    [$SIN],
)->fetchColumn();

echo "Hecho. Genéricos restantes: {$quedan} (debe ser 0). En tránsito: {$enTransito}.\n";
exit($quedan === 0 ? 0 : 1);
```

- [ ] **Step 2: Correr el dry-run y verificar que no escribe**

```bash
COMPOSE_PROJECT_NAME=pdc-equipo docker compose exec -T app php database/migrations/20260729_pdc_v2_equipo_sin_clasificar.php
docker exec pdc-equipo-db-1 mysql -uroot -p"$DB_PASS" lastplanneraia_dev -N -e \
 "SELECT tipo_recurso, COUNT(*) FROM general_maestro_insumos WHERE UPPER(tipo_recurso) LIKE '%EQUIPO%' GROUP BY tipo_recurso"
```
Expected: el dry-run dice `A mover: 167` y `con pista SINCO: 145 de 167`. **La segunda consulta sigue mostrando `EQUIPO 167`** — un dry-run que escribe no es un dry-run.

- [ ] **Step 3: Aplicar y verificar el censo**

```bash
COMPOSE_PROJECT_NAME=pdc-equipo docker compose exec -T app php database/migrations/20260729_pdc_v2_equipo_sin_clasificar.php --apply
docker exec pdc-equipo-db-1 mysql -uroot -p"$DB_PASS" lastplanneraia_dev -N -e \
 "SELECT tipo_recurso, COUNT(*) FROM general_maestro_insumos WHERE UPPER(tipo_recurso) LIKE '%EQUIPO%' GROUP BY tipo_recurso"
```
Expected: `EQUIPO (SIN CLASIFICAR) 167` · `ALQUILER EQUIPOS 2`. **Cero filas `EQUIPO`.** Las 2 de `ALQUILER EQUIPOS` intactas.

- [ ] **Step 4: Correr `--apply` otra vez (idempotencia)**

Run: `COMPOSE_PROJECT_NAME=pdc-equipo docker compose exec -T app php database/migrations/20260729_pdc_v2_equipo_sin_clasificar.php --apply`
Expected: `A mover a «EQUIPO (SIN CLASIFICAR)»: 0` y `Ya en tránsito (no se tocan): 167`.

- [ ] **Step 5: Probar la vuelta atrás y volver a aplicar** — punto 6 de la condición de hecho.

```bash
COMPOSE_PROJECT_NAME=pdc-equipo docker compose exec -T app php database/migrations/20260729_pdc_v2_equipo_sin_clasificar.php --revertir
docker exec pdc-equipo-db-1 mysql -uroot -p"$DB_PASS" lastplanneraia_dev -N -e \
 "SELECT tipo_recurso, COUNT(*) FROM general_maestro_insumos WHERE UPPER(tipo_recurso) LIKE '%EQUIPO%' GROUP BY tipo_recurso"
COMPOSE_PROJECT_NAME=pdc-equipo docker compose exec -T app php database/migrations/20260729_pdc_v2_equipo_sin_clasificar.php --apply
```
Expected: tras `--revertir`, `EQUIPO 167` + `ALQUILER EQUIPOS 2` — **el censo exacto de la línea base del Task 0**. Tras el `--apply` final, otra vez 167 en tránsito. **Pega estas tres salidas en la bitácora de validación**: son la evidencia del punto 6.

- [ ] **Step 6: Añadir el bloque de BD al test** — punto 2 de la condición de hecho.

Añade al final de `tests/test_pdc_v2_equipo_clasificacion.php`, **antes** del `echo` de cierre:

```php
echo PHP_EOL . "== Estado del maestro tras la migración" . PHP_EOL;

$db = \Database::getInstance();

$genericos = (int) $db->query(
    'SELECT COUNT(*) FROM general_maestro_insumos WHERE UPPER(TRIM(tipo_recurso)) = ?',
    [TipoRecursoEquipo::GENERICO],
)->fetchColumn();
$assert($genericos === 0, "No queda ningún insumo con el tipo genérico «EQUIPO» (hay {$genericos}).");

$transito = (int) $db->query(
    'SELECT COUNT(*) FROM general_maestro_insumos WHERE tipo_recurso = ?',
    [TipoRecursoEquipo::SIN_CLASIFICAR],
)->fetchColumn();
$assert($transito > 0, "Los equipos preexistentes están en la cola de sin clasificar ({$transito}).");

// Punto 5, cara «presupuesto»: el importador de presupuestos NUNCA escribe tipo_recurso, así que
// un re-import no puede degradar una clasificación. Se fija aquí para que nadie lo rompa después.
$fuente = file_get_contents(__DIR__ . '/../src/Services/Pdc/MaestroInsumosService.php');
$assert(
    $fuente !== false && !str_contains($fuente, 'tipo_recurso'),
    'MaestroInsumosService (la vía del presupuesto) no menciona tipo_recurso: un re-import no puede pisar la clasificación.',
);

// Las columnas de auditoría existen y arrancan vacías para lo migrado.
$col = $db->query(
    "SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'general_maestro_insumos'
       AND COLUMN_NAME IN ('clasificado_por','clasificado_at')",
)->fetchColumn();
$assert((int) $col === 2, 'Las dos columnas de auditoría de clasificación existen.');

$migradosConAutor = (int) $db->query(
    'SELECT COUNT(*) FROM general_maestro_insumos WHERE tipo_recurso = ? AND clasificado_at IS NOT NULL',
    [TipoRecursoEquipo::SIN_CLASIFICAR],
)->fetchColumn();
$assert($migradosConAutor === 0, 'Lo migrado no finge tener autor: clasificado_at sigue NULL.');
```

- [ ] **Step 7: Run test**

Run: `COMPOSE_PROJECT_NAME=pdc-equipo docker compose exec -T app php tests/test_pdc_v2_equipo_clasificacion.php; echo "rc=$?"`
Expected: `TODO OK`, `rc=0`.

- [ ] **Step 8: Commit** *(solo si el usuario autorizó publicar)*

```bash
git add database/migrations/20260729_pdc_v2_equipo_sin_clasificar.php tests/test_pdc_v2_equipo_clasificacion.php
git commit -m "feat(pdc): los equipos que ya existían entran a la cola de sin clasificar"
```

---

## Task 4: El motor sigue filtrando (la regresión silenciosa)

Sin esta task los 167 equipos caen al `default => self::TIPOS` de `tiposCompatibles()` y **dejan de filtrarse**. Esto es la trampa de A3.2 en su forma nueva. También entrega el punto 4 de la condición de hecho.

**Files:**
- Modify: `src/Services/Pdc/PaquetesService.php:1858-1868` (`tiposCompatibles`)
- Modify: `tests/test_pdc_v2_paquetes_motor.php`

**Interfaces:**
- Consumes: `TipoRecursoEquipo` (Task 1).
- Produces: `tiposCompatibles()` reconoce los tres valores nuevos. Firma sin cambios.

- [ ] **Step 1: Write the failing test**

Añade a `tests/test_pdc_v2_paquetes_motor.php`, junto a los asserts de `tipoRecursoAdmitido` (hacia la línea 380). `tiposCompatibles` es privada, así que se prueba **por su efecto** a través de `tipoRecursoAdmitido()`, que es pública.

```php
// --- Equipo alquilado vs comprado (Ola 2) -----------------------------------------------------
// Partir «EQUIPO» sin tocar tiposCompatibles() los mandaría al `default` = no filtrar nada, y
// pasarían a ser candidatos de cualquier paquete. Es la regresión de A3.2 en forma nueva.
echo PHP_EOL . "== Equipo alquilado / comprado / sin clasificar" . PHP_EOL;

$suministroId = (int) $db->query(
    "SELECT id FROM general_paquetes_contratacion WHERE tipo_negociacion = 'suministro' AND activo = 1 LIMIT 1",
)->fetchColumn();
$manoObraId = (int) $db->query(
    "SELECT id FROM general_paquetes_contratacion WHERE tipo_negociacion = 'mano_obra' AND activo = 1 LIMIT 1",
)->fetchColumn();
$assert($suministroId > 0 && $manoObraId > 0, 'Hay un paquete de suministro y uno de mano de obra en el catálogo.');

// Punto 4 de la condición de hecho: un equipo ALQUILADO no es candidato de un paquete de COMPRA.
// Alquilar no es comprar: es un contrato de servicio, no un suministro.
$assert(
    $svc->tipoRecursoAdmitido(\App\Services\Pdc\TipoRecursoEquipo::ALQUILADO, $suministroId) === false,
    'Un equipo ALQUILADO no es admisible en un paquete de suministro (no se compra lo que se alquila).',
);
$assert(
    $svc->tipoRecursoAdmitido(\App\Services\Pdc\TipoRecursoEquipo::COMPRADO, $suministroId) === true,
    'Un equipo COMPRADO sí es admisible en un paquete de suministro.',
);

// «Sin clasificar» se comporta exactamente como el «EQUIPO» de hoy: ni mejor ni peor. Es lo que
// permite usar el módulo con el tapón puesto.
$assert(
    $svc->tipoRecursoAdmitido(\App\Services\Pdc\TipoRecursoEquipo::SIN_CLASIFICAR, $suministroId) === true,
    'Sin clasificar se comporta como el EQUIPO de hoy: admisible en suministro.',
);
$assert(
    $svc->tipoRecursoAdmitido(\App\Services\Pdc\TipoRecursoEquipo::SIN_CLASIFICAR, $manoObraId) === false,
    'Sin clasificar NO cae en mano de obra: sigue filtrando, no cayó al default.',
);
$assert(
    $svc->tipoRecursoAdmitido(\App\Services\Pdc\TipoRecursoEquipo::ALQUILADO, $manoObraId) === false,
    'Un equipo alquilado tampoco cae en mano de obra.',
);
$assert(
    $svc->tipoRecursoAdmitido(\App\Services\Pdc\TipoRecursoEquipo::COMPRADO, $manoObraId) === false,
    'Un equipo comprado tampoco cae en mano de obra.',
);
```

- [ ] **Step 2: Run test to verify it fails**

Run: `COMPOSE_PROJECT_NAME=pdc-equipo docker compose exec -T app php tests/test_pdc_v2_paquetes_motor.php 2>&1 | grep -A2 "Equipo alquilado"; echo "rc=${PIPESTATUS[0]}"`
Expected: FAIL en «Un equipo ALQUILADO no es admisible en un paquete de suministro» y en «Sin clasificar NO cae en mano de obra» — los tres valores caen al `default` y todo les vale.

- [ ] **Step 3: Write minimal implementation**

En `src/Services/Pdc/PaquetesService.php`, sustituye la rama `'EQUIPO', 'TRANSPORTE'` del `match` de `tiposCompatibles()`:

```php
            // Equipo (A3.2 → Ola 2). Partir este valor sin nombrar los nuevos aquí los mandaría al
            // `default` = no filtrar, y un equipo pasaría a ser candidato de cualquier paquete. Es
            // exactamente la regresión que dejó el corte del bucket de indirectos.
            //
            // ALQUILADO no lleva `suministro` a propósito: alquilar no es comprar. Un alquiler se
            // contrata (a todo costo) o se consume (consumibles); si además figurara como candidato
            // de un paquete de compra, contabilidad volvería a tener las dos cosas en la misma bolsa,
            // que es el problema que este trabajo viene a resolver.
            TipoRecursoEquipo::ALQUILADO => ['a_todo_costo', 'consumibles'],
            // COMPRADO y SIN_CLASIFICAR conservan el cuadro del viejo «EQUIPO»: el de tránsito se
            // comporta como hoy —ni mejor ni peor— para que el módulo se pueda usar con el tapón puesto.
            TipoRecursoEquipo::COMPRADO,
            TipoRecursoEquipo::SIN_CLASIFICAR,
            TipoRecursoEquipo::GENERICO,
            'TRANSPORTE' => ['suministro', 'a_todo_costo', 'consumibles'],
```

Deja el resto del `match` intacto. `TipoRecursoEquipo::GENERICO` sigue nombrado a propósito: SINCO lo va a seguir emitiendo en cada carga, y entre la carga y la clasificación el motor tiene que seguir filtrándolo.

- [ ] **Step 4: Run tests to verify they pass**

```bash
COMPOSE_PROJECT_NAME=pdc-equipo docker compose exec -T app php tests/test_pdc_v2_paquetes_motor.php >/dev/null 2>&1; echo "motor rc=$?"
COMPOSE_PROJECT_NAME=pdc-equipo docker compose exec -T app php tests/test_pdc_v2_paquetes.php >/dev/null 2>&1; echo "paquetes rc=$?"
```
Expected: `rc=0` en ambos. Si `paquetes` ya venía rojo en el Task 0, compara contra ese rojo preexistente, no contra verde.

- [ ] **Step 5: Commit** *(solo si el usuario autorizó publicar)*

```bash
git add src/Services/Pdc/PaquetesService.php tests/test_pdc_v2_paquetes_motor.php
git commit -m "fix(pdc): partir «Equipo» no deja al motor sin filtro de tipo de recurso"
```

---

## Task 5: El importador SINCO respeta la clasificación humana (punto 5)

`MaestroSincoImportService` hace `SET … tipo_recurso = ?` a ciegas, y los 167 equipos **todos tienen `codigo_sinco`**: la próxima carga del maestro los devolvería a `EQUIPO` y de ahí a la cola. Este es el punto delicado del spec.

**Files:**
- Modify: `src/Services/Pdc/MaestroSincoImportService.php:82-117`
- Modify: `tests/test_pdc_v2_equipo_clasificacion.php`

**Interfaces:**
- Consumes: `TipoRecursoEquipo` (Task 1); columnas de Task 2.
- Produces: método privado `resolverTipoRecurso(?string $entrante, ?string $guardado, ?string $clasificadoAt): ?string`.

- [ ] **Step 1: Write the failing test**

Añade a `tests/test_pdc_v2_equipo_clasificacion.php`, antes del `echo` de cierre. Se prueba contra la BD real: se siembra un insumo con `codigo_sinco`, se clasifica, y se simula el UPDATE del importador.

```php
echo PHP_EOL . "== Punto 5: reimportar SINCO no borra la clasificación humana" . PHP_EOL;

$svcSinco = new \App\Services\Pdc\MaestroSincoImportService($db);
$reflex = new \ReflectionMethod($svcSinco, 'resolverTipoRecurso');
$reflex->setAccessible(true);
$resolver = static fn (?string $entrante, ?string $guardado, ?string $at): ?string
    => $reflex->invoke($svcSinco, $entrante, $guardado, $at);

$ALQ = TipoRecursoEquipo::ALQUILADO;
$COM = TipoRecursoEquipo::COMPRADO;
$SIN = TipoRecursoEquipo::SIN_CLASIFICAR;
$GEN = TipoRecursoEquipo::GENERICO;

// El caso que rompe el punto 5: un humano dijo «comprado», SINCO vuelve a mandar el genérico.
$assert($resolver($GEN, $COM, '2026-07-29 10:00:00') === $COM,
    'SINCO manda EQUIPO sobre un equipo clasificado a mano: gana la persona.');
$assert($resolver($GEN, $ALQ, '2026-07-29 10:00:00') === $ALQ,
    'Igual con alquilado: gana la persona.');
$assert($resolver($SIN, $COM, '2026-07-29 10:00:00') === $COM,
    'SINCO mandando «sin clasificar» tampoco degrada una clasificación.');

// Sin autor humano, SINCO manda: la migración dejó clasificado_at NULL a propósito.
$assert($resolver($GEN, $SIN, null) === $GEN,
    'Sobre una fila migrada (sin autor), SINCO escribe con normalidad.');

// Si SINCO se pone MÁS específico que lo guardado, gana SINCO: es dato nuevo, no una degradación.
$assert($resolver($ALQ, $SIN, null) === $ALQ,
    'SINCO trayendo ALQUILER EQUIPOS sobre un sin clasificar sí escribe: gana precisión.');
$assert($resolver($ALQ, $COM, '2026-07-29 10:00:00') === $ALQ,
    'Si SINCO trae un valor YA clasificado, gana SINCO aunque hubiera autor: es una corrección de la fuente, no una degradación.');

// Fuera de los equipos, nada cambia: este blindaje es sólo para el tapón.
$assert($resolver('MATERIAL', 'SUBCONTRATO', '2026-07-29 10:00:00') === 'MATERIAL',
    'En tipos que no son equipo el importador sigue mandando, como siempre.');
$assert($resolver('MATERIAL', $COM, '2026-07-29 10:00:00') === 'MATERIAL',
    'Si SINCO reclasifica un equipo a material, se respeta: dejó de ser equipo.');
```

- [ ] **Step 2: Run test to verify it fails**

Run: `COMPOSE_PROJECT_NAME=pdc-equipo docker compose exec -T app php tests/test_pdc_v2_equipo_clasificacion.php; echo "rc=$?"`
Expected: FAIL con `ReflectionException: Method … resolverTipoRecurso() does not exist`.

- [ ] **Step 3: Write minimal implementation**

En `src/Services/Pdc/MaestroSincoImportService.php`, añade el método privado:

```php
    /**
     * Qué `tipo_recurso` queda tras un re-import, cuando el entrante y el guardado no coinciden.
     *
     * Punto 5 de la condición de hecho de la Ola 2: reimportar el maestro NO puede devolver a «sin
     * clasificar» un equipo que un humano ya clasificó. El importador escribía `tipo_recurso` a
     * ciegas por `codigo_sinco`, y los 167 equipos migrados TODOS tienen código: sin esto, la
     * siguiente carga de SINCO borraba el trabajo entero.
     *
     * La regla es estrecha a propósito — sólo protege equipos, y sólo contra una DEGRADACIÓN:
     * - Entrante genérico o de tránsito, guardado clasificado, con autor humano → gana la persona.
     * - Entrante ya clasificado → gana SINCO: la fuente se puso más precisa, eso es dato nuevo.
     * - Entrante que no es equipo → gana SINCO: dejó de ser un equipo, no es asunto de esta regla.
     */
    private function resolverTipoRecurso(?string $entrante, ?string $guardado, ?string $clasificadoAt): ?string
    {
        // Sólo se protege un equipo clasificado por una persona.
        if ($clasificadoAt === null || !TipoRecursoEquipo::esClasificado($guardado)) {
            return $entrante;
        }
        // Si lo entrante ya es una clasificación, es una corrección de la fuente: pasa.
        if (TipoRecursoEquipo::esClasificado($entrante)) {
            return $entrante;
        }
        // Si lo entrante dejó de ser equipo, SINCO reclasificó de verdad: pasa.
        if (!TipoRecursoEquipo::esEquipo($entrante)) {
            return $entrante;
        }
        // Queda el único caso peligroso: SINCO manda el genérico o el de tránsito sobre una
        // clasificación humana. Se conserva lo que dijo la persona.
        return $guardado;
    }
```

Añade el `use` al principio del archivo si no está:

```php
use App\Services\Pdc\TipoRecursoEquipo;
```

*(Si la clase ya está en el namespace `App\Services\Pdc`, el `use` es innecesario — comprueba la cabecera del archivo y omítelo en ese caso.)*

Ahora cablea el método en los dos UPDATE. Sustituye el `SELECT id FROM …` de la línea 77 para traer también lo que hay que respetar:

```php
                $porCodigo = $this->db->query(
                    'SELECT id, tipo_recurso, clasificado_at FROM general_maestro_insumos WHERE codigo_sinco = ?',
                    [$ins['codigoSinco']],
                )->fetch(\PDO::FETCH_ASSOC);

                if ($porCodigo !== false) {
                    $tipoRecurso = $this->resolverTipoRecurso(
                        $ins['tipoRecurso'],
                        $porCodigo['tipo_recurso'],
                        $porCodigo['clasificado_at'],
                    );
                    $this->db->query(
                        'UPDATE general_maestro_insumos
                         SET descripcion = ?, descripcion_norm = ?, unidad = ?, tipo_insumo = ?, agrupacion = ?,
                             tipo_recurso = ?, valor_unitario = ?, iva = ?, activo = 1, actualizado_por = ?, updated_at = NOW()
                         WHERE id = ?',
                        [$ins['descripcion'], $ins['descripcionNorm'], $ins['unidad'], $tipoInsumo, $ins['agrupacion'],
                         $tipoRecurso, $ins['valorUnitario'], $ins['iva'], $usuario, (int) $porCodigo['id']],
                    );
                    $actualizados++;
                    continue;
                }
```

Y en la rama de la huérfana, trae los dos campos en el `SELECT` y aplica lo mismo:

```php
                $huerfana = $this->db->query(
                    'SELECT id, codigo_sinco, tipo_recurso, clasificado_at FROM general_maestro_insumos WHERE descripcion_norm = ? AND unidad = ?',
                    [$ins['descripcionNorm'], $ins['unidad']],
                )->fetch(\PDO::FETCH_ASSOC);

                if ($huerfana !== false) {
                    if ($huerfana['codigo_sinco'] === null || $huerfana['codigo_sinco'] === '') {
                        $tipoRecurso = $this->resolverTipoRecurso(
                            $ins['tipoRecurso'],
                            $huerfana['tipo_recurso'],
                            $huerfana['clasificado_at'],
                        );
                        $this->db->query(
                            'UPDATE general_maestro_insumos
                             SET codigo_sinco = ?, descripcion = ?, tipo_insumo = ?, agrupacion = ?, tipo_recurso = ?,
                                 valor_unitario = ?, iva = ?, activo = 1, actualizado_por = ?, updated_at = NOW()
                             WHERE id = ?',
                            [$ins['codigoSinco'], $ins['descripcion'], $tipoInsumo, $ins['agrupacion'], $tipoRecurso,
                             $ins['valorUnitario'], $ins['iva'], $usuario, (int) $huerfana['id']],
                        );
                        $enriquecidos++;
                    } else {
                        $conflictos[] = ['codigoSinco' => $ins['codigoSinco'], 'descripcion' => $ins['descripcion'], 'chocaCon' => $huerfana['codigo_sinco']];
                    }
                    continue;
                }
```

El INSERT de la línea 119 no cambia: una fila nueva no tiene clasificación humana que respetar.

- [ ] **Step 4: Run tests to verify they pass**

```bash
COMPOSE_PROJECT_NAME=pdc-equipo docker compose exec -T app php tests/test_pdc_v2_equipo_clasificacion.php; echo "clasificacion rc=$?"
COMPOSE_PROJECT_NAME=pdc-equipo docker compose exec -T app php tests/test_pdc_v2_maestro_sinco_import.php >/dev/null 2>&1; echo "sinco rc=$?"
COMPOSE_PROJECT_NAME=pdc-equipo docker compose exec -T app php tests/test_pdc_v2_maestro_sinco_parser.php >/dev/null 2>&1; echo "parser rc=$?"
```
Expected: `TODO OK` y `rc=0` en los tres.

- [ ] **Step 5: Commit** *(solo si el usuario autorizó publicar)*

```bash
git add src/Services/Pdc/MaestroSincoImportService.php tests/test_pdc_v2_equipo_clasificacion.php
git commit -m "fix(pdc): reimportar el maestro SINCO ya no borra una clasificación hecha a mano"
```

---

## Task 6: La cola y la clasificación en lote (backend)

**Files:**
- Modify: `src/Services/Pdc/MaestroInsumosService.php`
- Modify: `src/Controllers/Api/PlanComprasMaestroController.php`
- Modify: `public/index.php:194-205`
- Modify: `tests/test_pdc_v2_equipo_clasificacion.php`

**Interfaces:**
- Consumes: `TipoRecursoEquipo` (Task 1); `guardLectura()` / `guardEscritura()` del controlador, que ya existen (líneas 177 y 191) y ya usan `lps.pdc.ver` y `lps.pdc.maestro`.
- Produces:
  - `MaestroInsumosService::equiposSinClasificar(?string $busqueda = null, int $limite = 2000): array{total:int, items:list<array{id:int, descripcion:string, unidad:string, agrupacion:?string, codigoSinco:?string, pista:?string}>}` — ordenado por pista (los que la tienen primero, agrupados por destino sugerido) y luego por descripción.
  - `MaestroInsumosService::clasificarEquipos(array $ids, string $destino, string $usuario): array{ok:bool, code?:string, clasificados:int}`
  - `GET /plan-compras/api/maestro/equipos` → `{ok, data:{total, items}}`
  - `POST /plan-compras/api/maestro/equipos/clasificar` con body `{ids:int[], destino:string}` → `{ok, data:{clasificados}}`

- [ ] **Step 1: Write the failing test**

Añade a `tests/test_pdc_v2_equipo_clasificacion.php` antes del cierre:

```php
echo PHP_EOL . "== La cola y la clasificación en lote" . PHP_EOL;

$maestro = new \App\Services\Pdc\MaestroInsumosService($db);

$cola = $maestro->equiposSinClasificar();
$assert($cola['total'] > 0, "La cola trae los equipos sin clasificar ({$cola['total']}).");
$assert(count($cola['items']) > 0, 'La cola trae items.');

// Punto 2: están CONTADOS, y el total cuadra con la BD.
$enBd = (int) $db->query(
    'SELECT COUNT(*) FROM general_maestro_insumos WHERE tipo_recurso = ? AND activo = 1',
    [TipoRecursoEquipo::SIN_CLASIFICAR],
)->fetchColumn();
$assert($cola['total'] === $enBd, "El total de la cola ({$cola['total']}) cuadra con la BD ({$enBd}).");

// La pista viaja para mostrarse como evidencia, y NO está escrita en tipo_recurso.
$conPista = array_values(array_filter($cola['items'], static fn (array $i): bool => $i['pista'] !== null));
$assert(count($conPista) > 0, 'Algunos items traen la pista de SINCO para mostrar como evidencia.');
$assert(
    $conPista[0]['agrupacion'] !== null && $conPista[0]['agrupacion'] !== '',
    'El item con pista trae también la agrupación que la justifica: la evidencia se muestra, no se esconde.',
);

// Preordenada: los que traen pista van primero (ese es el lote que se resuelve de golpe).
$assert($cola['items'][0]['pista'] !== null, 'La cola viene preordenada: lo que tiene pista primero.');

// Punto 3: clasificar 20 de golpe, y la cola baja en 20.
$antes = $cola['total'];
$lote = array_slice(array_column($cola['items'], 'id'), 0, 20);
$assert(count($lote) === 20, 'Hay al menos 20 equipos en la cola para probar el lote.');

$res = $maestro->clasificarEquipos($lote, TipoRecursoEquipo::COMPRADO, 'test@aia');
$assert($res['ok'] === true, 'Clasificar 20 de golpe funciona.');
$assert($res['clasificados'] === 20, "Se clasificaron 20 (dice {$res['clasificados']}).");

$despues = $maestro->equiposSinClasificar()['total'];
$assert($despues === $antes - 20, "La cola bajó en 20: {$antes} → {$despues}.");

// Punto 1, cara «sobrevive a recargar»: el valor está en la BD, no en memoria.
$fila = $db->query(
    'SELECT tipo_recurso, clasificado_por, clasificado_at FROM general_maestro_insumos WHERE id = ?',
    [$lote[0]],
)->fetch(\PDO::FETCH_ASSOC);
$assert($fila['tipo_recurso'] === TipoRecursoEquipo::COMPRADO, 'El tipo quedó persistido en la BD.');
$assert($fila['clasificado_por'] === 'test@aia', 'Quedó registrado quién clasificó.');
$assert($fila['clasificado_at'] !== null, 'Quedó registrado cuándo: es lo que hace que SINCO lo respete.');

// Destinos inválidos se rechazan. No se puede devolver algo a «sin clasificar» por esta puerta.
$r = $maestro->clasificarEquipos([$lote[0]], TipoRecursoEquipo::SIN_CLASIFICAR, 'test@aia');
$assert($r['ok'] === false && $r['code'] === 'DESTINO_INVALIDO', 'No se puede clasificar HACIA sin clasificar.');
$r = $maestro->clasificarEquipos([$lote[0]], 'MATERIAL', 'test@aia');
$assert($r['ok'] === false && $r['code'] === 'DESTINO_INVALIDO', 'No se convierte un equipo en material por esta puerta.');
$r = $maestro->clasificarEquipos([], TipoRecursoEquipo::COMPRADO, 'test@aia');
$assert($r['ok'] === false && $r['code'] === 'SIN_IDS', 'Un lote vacío se rechaza.');

// Reclasificar uno ya clasificado sí se permite (corregir un error humano), y re-sella la auditoría.
$r = $maestro->clasificarEquipos([$lote[0]], TipoRecursoEquipo::ALQUILADO, 'otro@aia');
$assert($r['ok'] === true && $r['clasificados'] === 1, 'Se puede corregir una clasificación equivocada.');
$fila2 = $db->query('SELECT tipo_recurso, clasificado_por FROM general_maestro_insumos WHERE id = ?', [$lote[0]])
    ->fetch(\PDO::FETCH_ASSOC);
$assert($fila2['tipo_recurso'] === TipoRecursoEquipo::ALQUILADO && $fila2['clasificado_por'] === 'otro@aia',
    'La corrección queda con su nuevo autor.');

// Un insumo que NO es equipo no se toca ni por error.
$materialId = (int) $db->query("SELECT id FROM general_maestro_insumos WHERE tipo_recurso = 'MATERIAL' LIMIT 1")->fetchColumn();
$r = $maestro->clasificarEquipos([$materialId], TipoRecursoEquipo::COMPRADO, 'test@aia');
$assert($r['clasificados'] === 0, 'Un MATERIAL no se clasifica como equipo: el filtro es por tipo, no por id suelto.');
$sigue = $db->query('SELECT tipo_recurso FROM general_maestro_insumos WHERE id = ?', [$materialId])->fetchColumn();
$assert($sigue === 'MATERIAL', 'Y sigue siendo MATERIAL.');
```

- [ ] **Step 2: Run test to verify it fails**

Run: `COMPOSE_PROJECT_NAME=pdc-equipo docker compose exec -T app php tests/test_pdc_v2_equipo_clasificacion.php; echo "rc=$?"`
Expected: FAIL con `Call to undefined method … equiposSinClasificar()`.

- [ ] **Step 3: Write minimal implementation**

Añade a `src/Services/Pdc/MaestroInsumosService.php` (dentro de la clase; el `use` de `TipoRecursoEquipo` no hace falta, comparten namespace):

```php
    /**
     * Los equipos que esperan que alguien diga si se alquilan o se compran.
     *
     * Es una cola del catálogo GLOBAL, no de una versión de presupuesto: por eso no lleva
     * `project_id` ni `version_id`, al contrario que la cola de vínculos. Vive en la misma pantalla
     * (una sección más de las pestañas que ya tiene el maestro), no en una pantalla nueva.
     *
     * `pista` es lo que SUGIERE la agrupación que escribió el equipo de presupuestos, y viaja junto
     * al `agrupacion` que la justifica para que se muestre como evidencia. NO está escrita en
     * `tipo_recurso`: adivinar sin confirmación humana está descartado por el grilleo. Lo único que
     * hace es ordenar la cola, de modo que las decisiones fáciles se resuelvan en un lote.
     *
     * @return array{total: int, items: list<array{id: int, descripcion: string, unidad: string,
     *                agrupacion: ?string, codigoSinco: ?string, pista: ?string}>}
     */
    public function equiposSinClasificar(?string $busqueda = null, int $limite = 2000): array
    {
        $sql = 'SELECT id, descripcion, unidad, agrupacion, codigo_sinco
                FROM general_maestro_insumos
                WHERE tipo_recurso = ? AND activo = 1';
        $args = [TipoRecursoEquipo::SIN_CLASIFICAR];

        if ($busqueda !== null && trim($busqueda) !== '') {
            // Comodines LIKE escapados, como en el resto del maestro (follow-up del review de A2).
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], trim($busqueda)) . '%';
            $sql .= ' AND (descripcion LIKE ? OR agrupacion LIKE ?)';
            $args[] = $like;
            $args[] = $like;
        }

        $sql .= ' ORDER BY descripcion ASC LIMIT ' . max(1, min($limite, 5000));

        $filas = $this->db->query($sql, $args)->fetchAll(\PDO::FETCH_ASSOC);

        $items = [];
        foreach ($filas as $f) {
            $items[] = [
                'id' => (int) $f['id'],
                'descripcion' => (string) $f['descripcion'],
                'unidad' => (string) $f['unidad'],
                'agrupacion' => $f['agrupacion'],
                'codigoSinco' => $f['codigo_sinco'],
                'pista' => TipoRecursoEquipo::pistaSinco($f['agrupacion']),
            ];
        }

        // Preorden: primero lo que trae pista, agrupado por destino sugerido, para que el lote se
        // arme solo. La pista NO decide el valor; sólo decide el orden de la fila en la pantalla.
        usort($items, static function (array $a, array $b): int {
            $pa = $a['pista'] ?? 'ZZZ';
            $pb = $b['pista'] ?? 'ZZZ';
            return [$pa, $a['agrupacion'] ?? '', $a['descripcion']]
               <=> [$pb, $b['agrupacion'] ?? '', $b['descripcion']];
        });

        $total = (int) $this->db->query(
            'SELECT COUNT(*) FROM general_maestro_insumos WHERE tipo_recurso = ? AND activo = 1',
            [TipoRecursoEquipo::SIN_CLASIFICAR],
        )->fetchColumn();

        return ['total' => $total, 'items' => $items];
    }

    /**
     * Clasifica en lote. Es la operación de la cola: selección múltiple, un solo destino.
     *
     * El WHERE filtra por tipo de recurso además de por id: un id que no sea de un equipo no se
     * toca ni por error de la SPA. Reclasificar uno ya clasificado SÍ se permite —corregir una
     * equivocación humana es parte del trabajo— y re-sella la auditoría con el nuevo autor.
     *
     * @param list<int> $ids
     *
     * @return array{ok: bool, code?: string, clasificados: int}
     */
    public function clasificarEquipos(array $ids, string $destino, string $usuario): array
    {
        if (!TipoRecursoEquipo::esDestinoValido($destino)) {
            return ['ok' => false, 'code' => 'DESTINO_INVALIDO', 'clasificados' => 0];
        }
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $i): bool => $i > 0)));
        if ($ids === []) {
            return ['ok' => false, 'code' => 'SIN_IDS', 'clasificados' => 0];
        }

        $marcadores = implode(', ', array_fill(0, count($ids), '?'));
        $tipos = implode(', ', array_fill(0, 4, '?'));

        $this->db->beginTransaction();
        try {
            $st = $this->db->query(
                "UPDATE general_maestro_insumos
                 SET tipo_recurso = ?, clasificado_por = ?, clasificado_at = NOW(),
                     actualizado_por = ?, updated_at = NOW()
                 WHERE id IN ({$marcadores}) AND UPPER(TRIM(tipo_recurso)) IN ({$tipos})",
                array_merge(
                    [mb_strtoupper($destino), $usuario, $usuario],
                    $ids,
                    [
                        TipoRecursoEquipo::SIN_CLASIFICAR,
                        TipoRecursoEquipo::GENERICO,
                        TipoRecursoEquipo::ALQUILADO,
                        TipoRecursoEquipo::COMPRADO,
                    ],
                ),
            );
            $clasificados = $st->rowCount();
            $this->db->commit();
        } catch (\Throwable $t) {
            $this->db->rollBack();
            throw $t;
        }

        return ['ok' => true, 'clasificados' => $clasificados];
    }
```

Añade al controlador `src/Controllers/Api/PlanComprasMaestroController.php`:

```php
    /** GET /plan-compras/api/maestro/equipos */
    public function equipos(): void
    {
        if ($this->guardLectura() === null) {
            return;
        }
        $busqueda = isset($_GET['q']) && is_string($_GET['q']) ? $_GET['q'] : null;
        $this->json(['ok' => true, 'data' => $this->service->equiposSinClasificar($busqueda)]);
    }

    /** POST /plan-compras/api/maestro/equipos/clasificar */
    public function clasificarEquipos(): void
    {
        // Escritura sobre el maestro GLOBAL: exige `lps.pdc.maestro` (administración), no una
        // capacidad de obra. Clasificar aquí cambia el dato para todos los proyectos de AIA.
        if ($this->guardEscritura() === null) {
            return;
        }
        $body = $this->body();
        $ids = is_array($body['ids'] ?? null) ? $body['ids'] : [];
        $destino = is_string($body['destino'] ?? null) ? $body['destino'] : '';

        $r = $this->service->clasificarEquipos($ids, $destino, $this->usuario());
        if ($r['ok'] !== true) {
            http_response_code(422);
            $this->json(['ok' => false, 'error' => $r['code'] ?? 'ERROR']);
            return;
        }
        $this->json(['ok' => true, 'data' => ['clasificados' => $r['clasificados']]]);
    }
```

*(Ajusta `$this->json(...)` y `$this->service` al nombre exacto que usan los demás métodos del controlador — cópialo de `catalogo()` o `vinculos()`, que ya están escritos.)*

Y las dos rutas en `public/index.php`, junto a las del maestro (después de la línea 205):

```php
$router->get('/plan-compras/api/maestro/equipos', [\App\Controllers\Api\PlanComprasMaestroController::class, 'equipos']);
$router->post('/plan-compras/api/maestro/equipos/clasificar', [\App\Controllers\Api\PlanComprasMaestroController::class, 'clasificarEquipos']);
```

- [ ] **Step 4: Run test to verify it passes**

Run: `COMPOSE_PROJECT_NAME=pdc-equipo docker compose exec -T app php tests/test_pdc_v2_equipo_clasificacion.php; echo "rc=$?"`

Expected: `TODO OK`, `rc=0`.

- [ ] **Step 5: Restaurar la cola a 167 antes de seguir** — el test acaba de clasificar 21 filas y las tasks siguientes esperan el tapón completo.

```bash
COMPOSE_PROJECT_NAME=pdc-equipo docker compose exec -T app php database/migrations/20260729_pdc_v2_equipo_sin_clasificar.php --revertir
docker exec pdc-equipo-db-1 mysql -uroot -p"$DB_PASS" lastplanneraia_dev -e \
 "UPDATE general_maestro_insumos SET clasificado_por = NULL, clasificado_at = NULL WHERE clasificado_por LIKE '%@aia'"
COMPOSE_PROJECT_NAME=pdc-equipo docker compose exec -T app php database/migrations/20260729_pdc_v2_equipo_sin_clasificar.php --apply
```
Expected: `En tránsito: 167`. **Ojo:** ese `UPDATE` de limpieza toca solo los autores de prueba (`%@aia` del test). Si un usuario real tuviera ese sufijo, acótalo más — revísalo antes de correrlo.

- [ ] **Step 6: Verificar RBAC — un rol permitido y uno denegado**

Es un cambio de ruta protegida, así que AGENTS.md lo exige explícitamente. Sigue el patrón de `tests/test_pdc_v2_rbac_pasos.php`, que ya prueba exactamente esto para otra capacidad: cópialo a `tests/test_pdc_v2_rbac_equipos.php`, cambia la capacidad a `lps.pdc.maestro` y las rutas a las dos nuevas. Debe afirmar:
- un rol con `lps.pdc.maestro` (Admin) puede `POST /plan-compras/api/maestro/equipos/clasificar`;
- un rol sin ella (Visualizador `V`) recibe 403 en el POST **y sí** puede el GET si tiene `lps.pdc.ver`.

Run: `COMPOSE_PROJECT_NAME=pdc-equipo docker compose exec -T app php tests/test_pdc_v2_rbac_equipos.php; echo "rc=$?"`
Expected: `rc=0`, con el permitido y el denegado nombrados en la salida.

- [ ] **Step 7: Commit** *(solo si el usuario autorizó publicar)*

```bash
git add src/Services/Pdc/MaestroInsumosService.php src/Controllers/Api/PlanComprasMaestroController.php public/index.php tests/test_pdc_v2_equipo_clasificacion.php tests/test_pdc_v2_rbac_equipos.php
git commit -m "feat(pdc): la cola del maestro clasifica equipos en lote, con la pista de SINCO como evidencia"
```

---

## Task 7: La sección en la pantalla del maestro (SPA)

Sin pantalla nueva: `MaestroInsumos.tsx` ya tiene pestañas (`seccion`: `pendientes` / `catalogo` / importar SINCO) con el componente `Pestanas`. Se añade una.

**Files:**
- Create: `pdc-app/src/lib/tipoRecurso.ts`
- Create: `pdc-app/src/lib/tipoRecurso.test.ts`
- Modify: `pdc-app/src/lib/types.ts`
- Modify: `pdc-app/src/pages/MaestroInsumos.tsx`

**Interfaces:**
- Consumes: `GET /plan-compras/api/maestro/equipos`, `POST /plan-compras/api/maestro/equipos/clasificar` (Task 6).
- Produces:
  - `TIPO_RECURSO_EQUIPO = { SIN_CLASIFICAR: 'EQUIPO (SIN CLASIFICAR)', ALQUILADO: 'ALQUILER EQUIPOS', COMPRADO: 'EQUIPO COMPRADO' } as const`
  - `etiquetaTipoRecurso(valor: string): string`
  - tipo `EquipoSinClasificar = { id: number; descripcion: string; unidad: string; agrupacion: string | null; codigoSinco: string | null; pista: string | null }`

- [ ] **Step 1: Write the failing test**

Crea `pdc-app/src/lib/tipoRecurso.test.ts`:

```ts
import { describe, expect, it } from 'vitest'
import { TIPO_RECURSO_EQUIPO, etiquetaTipoRecurso } from './tipoRecurso'

describe('tipoRecurso', () => {
  // Espejo exacto de App\Services\Pdc\TipoRecursoEquipo. La divergencia PHP↔SPA no rompe nada
  // visible hasta que alguien intenta guardar, y entonces falla sin explicar por qué — ya pasó al
  // agregar `no_aplica` a TIPOS_NEGOCIACION (docs/pdc-v2.md §deudas de datos saldadas).
  it('fija los strings canónicos que comparte con el PHP', () => {
    expect(TIPO_RECURSO_EQUIPO.SIN_CLASIFICAR).toBe('EQUIPO (SIN CLASIFICAR)')
    expect(TIPO_RECURSO_EQUIPO.ALQUILADO).toBe('ALQUILER EQUIPOS')
    expect(TIPO_RECURSO_EQUIPO.COMPRADO).toBe('EQUIPO COMPRADO')
  })

  it('traduce el valor guardado a lo que lee una persona', () => {
    // El dato guarda el valor canónico de SINCO; la pantalla dice algo legible.
    expect(etiquetaTipoRecurso('ALQUILER EQUIPOS')).toBe('Equipo alquilado')
    expect(etiquetaTipoRecurso('EQUIPO COMPRADO')).toBe('Equipo comprado')
    expect(etiquetaTipoRecurso('EQUIPO (SIN CLASIFICAR)')).toBe('Equipo (sin clasificar)')
  })

  it('deja pasar cualquier otro tipo de recurso sin tocarlo', () => {
    expect(etiquetaTipoRecurso('MATERIAL')).toBe('MATERIAL')
    expect(etiquetaTipoRecurso('MANO DE OBRA')).toBe('MANO DE OBRA')
  })
})
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd pdc-app && npx vitest run src/lib/tipoRecurso.test.ts`
Expected: FAIL — no se resuelve `./tipoRecurso`.

- [ ] **Step 3: Write minimal implementation**

Crea `pdc-app/src/lib/tipoRecurso.ts`:

```ts
/**
 * Espejo de `App\Services\Pdc\TipoRecursoEquipo`. Los strings tienen que coincidir EXACTAMENTE.
 *
 * `ALQUILADO` vale `ALQUILER EQUIPOS` porque es el valor que SINCO ya emite: adoptarlo evita tener
 * dos nombres para la misma cosa cada vez que se recarga el maestro. Lo que la persona lee sale de
 * `etiquetaTipoRecurso`, no del dato.
 */
export const TIPO_RECURSO_EQUIPO = {
  SIN_CLASIFICAR: 'EQUIPO (SIN CLASIFICAR)',
  ALQUILADO: 'ALQUILER EQUIPOS',
  COMPRADO: 'EQUIPO COMPRADO',
} as const

const ETIQUETAS: Record<string, string> = {
  [TIPO_RECURSO_EQUIPO.SIN_CLASIFICAR]: 'Equipo (sin clasificar)',
  [TIPO_RECURSO_EQUIPO.ALQUILADO]: 'Equipo alquilado',
  [TIPO_RECURSO_EQUIPO.COMPRADO]: 'Equipo comprado',
}

/** Nombre legible de un tipo de recurso. Los que no son equipo pasan tal cual. */
export function etiquetaTipoRecurso(valor: string): string {
  return ETIQUETAS[valor] ?? valor
}
```

Añade a `pdc-app/src/lib/types.ts`:

```ts
export type EquipoSinClasificar = {
  id: number
  descripcion: string
  unidad: string
  agrupacion: string | null
  /** Código SINCO: la fila viene del maestro de la empresa, no de un presupuesto. */
  codigoSinco: string | null
  /** Destino que SUGIERE la agrupación de SINCO. Nunca se guarda sin que una persona lo confirme. */
  pista: string | null
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `cd pdc-app && npx vitest run src/lib/tipoRecurso.test.ts`
Expected: 3 tests PASS.

- [ ] **Step 5: Añadir la sección a `MaestroInsumos.tsx`**

Reusa lo que la pantalla ya tiene, sin inventar patrones: el `Pestanas` / `PanelPestana`, el `maestroReducer` (`TOGGLE_SEL`, `SEL_TODOS`, `LIMPIAR_SEL`, `OCUPADO`, `LISTO`, `FALLO`) para la selección múltiple, `apiGet` / `apiPost`, `AgGridReact` con `defaultColDef` / `pdcTheme` / `vacioTabla`, y las clases `pdc-*`. Requisitos concretos:

1. **Pestaña nueva** rotulada `Clasificar equipos` con el contador entre paréntesis, junto a `Pendientes por vincular` y `Catálogo`. Si `total` es 0, la pestaña **no se muestra**: el tapón se vacía y la pantalla vuelve a como estaba.
2. **Carga:** `apiGet<{ total: number; items: EquipoSinClasificar[] }>('/maestro/equipos')` al abrir la sección.
3. **Columnas:** casilla de selección · Descripción · Unidad · **`SINCO dice`** (el `agrupacion` crudo) · **`Sugerencia`** (`etiquetaTipoRecurso(pista)`, o `—`). La columna «Sugerencia» es **informativa**: no hay ninguna celda editable que la escriba sola.
4. **Dos botones de acción**, activos solo con selección: `Marcar como alquilado` y `Marcar como comprado`. Cada uno hace `apiPost('/maestro/equipos/clasificar', { ids: [...seleccion], destino })`, y al volver recarga la cola y muestra `Clasificados N equipos como …` con `plural()`.
5. **Un botón de lote asistido:** `Seleccionar los N que SINCO marca como alquiler` (y su gemelo para compra), que solo hace `SEL_TODOS` con los ids cuya `pista` casa. **Selecciona, no guarda**: la persona sigue apretando el botón de clasificar. Esto es lo que convierte 89 decisiones en dos clics sin afirmar nada por ella.
6. **Texto de la sección**, una línea, explicando por qué existe el tapón:
   `Estos equipos venían en una sola categoría. Contabilidad maneja distinto un alquiler que una compra, así que hay que decirlo una vez por insumo. La columna «SINCO dice» es lo que escribió presupuestos: úsala como pista, no como respuesta.`
7. **Sin permiso de escritura**, los botones no se renderizan (mira cómo la pantalla ya oculta las acciones de retirar/reactivar y haz lo mismo).

- [ ] **Step 6: Build y suite de la SPA**

```bash
cd pdc-app && npm run build && npm run test
```
Expected: build sin errores de `tsc`, y la suite Vitest en verde (los 3 tests nuevos incluidos). El build escribe `public/pdc-app/assets/{pdc.js,pdc.css}` — **hay que commitearlos** con el resto.

- [ ] **Step 7: Lint del frontend**

Run: `npm run check:frontend`
Expected: sin errores nuevos. `biome.json` no cubre `pdc-app/`, así que esto solo confirma que no se tocó `public/js|css`; si sale rojo, es preexistente.

- [ ] **Step 8: Commit** *(solo si el usuario autorizó publicar)*

```bash
git add pdc-app/src/lib/tipoRecurso.ts pdc-app/src/lib/tipoRecurso.test.ts pdc-app/src/lib/types.ts pdc-app/src/pages/MaestroInsumos.tsx public/pdc-app/assets
git commit -m "feat(pdc): el maestro gana una sección para clasificar equipos en lote"
```

---

## Task 8: Verificación en navegador y cierre documental

**Files:**
- Modify: `docs/pdc-v2.md`
- Create: `goals/pdc-preparar-b1/validation-log.md` (o añadir al que exista)

- [ ] **Step 1: Abrir la ruta afectada en el navegador integrado**

Desktop dark, viewport **1180×820**. No la home: la pantalla del maestro.

```bash
COMPOSE_PROJECT_NAME=pdc-equipo docker compose ps
```

Luego `mcp__Claude_Browser__preview_start` con `{url: "http://localhost:8094/plan-compras#/ensamble/maestro"}`, `resize_window` a 1180×820 con `colorScheme: 'dark'`, y `read_page`.

Verifica y guarda evidencia de:
- la pestaña `Clasificar equipos (167)` existe y abre;
- la tabla muestra `SINCO dice` y `Sugerencia`, con los `ALQUILER …` arriba;
- seleccionar el lote sugerido de alquiler y pulsar `Marcar como alquilado` baja el contador en ese número;
- **consola sin errores** (`read_console_messages`) y **sin overflow horizontal** a 1180 px;
- una captura (`computer` → `screenshot`) de la sección con el lote seleccionado.

**La sesión del panel se cae a los ~60-90 s** (el panel Electron pierde la cookie entre turnos; es del panel, no de la app). Si te saca, vuelve a entrar y sigue — no lo diagnostiques como bug.

- [ ] **Step 2: Suite de regresión completa** — punto 7 de la condición de hecho.

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia/.claude/worktrees/pdc-ola2-equipo-alq-comp"
for t in test_global_table_safety test_global_table_reconciliation \
         test_pdc_v2_equipo_clasificacion test_pdc_v2_rbac_equipos \
         test_pdc_v2_paquetes_motor test_pdc_v2_paquetes \
         test_pdc_v2_maestro_sinco_import test_pdc_v2_maestro_sinco_parser; do
  COMPOSE_PROJECT_NAME=pdc-equipo docker compose exec -T app php "tests/$t.php" >/dev/null 2>&1
  echo "$t rc=$?"
done
COMPOSE_PROJECT_NAME=pdc-equipo docker compose exec -T app vendor/bin/phpstan analyse src admin/src --memory-limit=1G
cd pdc-app && npm run test && npm run build
```

Expected: `rc=0` en los ocho, PHPStan sin errores nuevos, Vitest verde, build limpio. **Compara contra los rojos preexistentes anotados en el Task 0** — no contra un verde imaginario.

- [ ] **Step 3: Registrar la deuda saldada en `docs/pdc-v2.md`**

Añade una subsección tras «Dos deudas de datos saldadas (2026-07-28)»:

```markdown
### Equipo alquilado vs comprado (2026-07-29, Ola 2)

`tipo_recurso` **no es un enum**: es `varchar(60)` que siembra el importador SINCO desde la columna
«TIPO DESCRIPCION». Por eso partir «Equipo» no llevó DDL de enum, sino datos + reglas de lectura.

- **Los tres valores** viven en un solo sitio, `App\Services\Pdc\TipoRecursoEquipo`, con espejo en
  `pdc-app/src/lib/tipoRecurso.ts` y un test a cada lado que fija los strings — la misma disciplina
  que fijó los cinco de `TIPOS_NEGOCIACION`.
- **`ALQUILER EQUIPOS` no es un nombre nuevo: SINCO ya lo emitía** (2 filas en el maestro). Adoptarlo
  en vez de inventar «EQUIPO ALQUILADO» es lo que evita que cada carga de SINCO reabra la deuda con
  un sinónimo. Para «comprado» no había nada que adoptar: los 53 de compra llegan como `EQUIPO` con
  `agrupacion` `COMPRA ELEMENTOS-…`, así que `EQUIPO COMPRADO` sí es valor nuevo.
- **Los 167 preexistentes quedaron en `EQUIPO (SIN CLASIFICAR)`** por decisión explícita del usuario,
  contra la opción barata de mandarlos a «comprado»: nadie afirma lo que no sabe. El tapón se asume —
  «sin clasificar» hereda el cuadro de compatibilidad del viejo `EQUIPO`, así que el módulo se usa
  igual con el tapón puesto. Migración `20260729_pdc_v2_equipo_sin_clasificar.php`, reglada por
  `tipo_recurso` (no por lista de nombres, la lección de A3.2), con `--revertir` probado.
- **La trampa de A3.2, en sitio nuevo.** `tiposCompatibles()` es un `match` cuyo `default` es *no
  filtrar*. Renombrar los equipos sin nombrar los valores nuevos ahí los habría vuelto candidatos de
  cualquier paquete, incluido mano de obra, sin que ningún test lo dijera. `ALQUILADO` sale además de
  `suministro` a propósito: alquilar no es comprar.
- **El punto delicado era el importador SINCO, no el de presupuestos.** El de presupuestos nunca
  escribe `tipo_recurso` (un test lo fija ahora). El de SINCO sí, a ciegas por `codigo_sinco`, y los
  167 equipos **todos** tienen código: la siguiente carga habría borrado el trabajo humano.
  `resolverTipoRecurso()` lo acota — la persona gana solo contra una *degradación* (genérico o
  tránsito sobre una clasificación con autor); si SINCO se pone más preciso, gana SINCO. Lo hace
  verificable el par `clasificado_por` / `clasificado_at`: sin él, «lo dijo un humano» y «lo trajo el
  Excel» son indistinguibles, el mismo problema del NULL mudo de B1.
- **La pista sugiere, no escribe.** 145 de los 167 traen la respuesta en `agrupacion` (89 `ALQUILER…`,
  53 `COMPRA…`). La cola la muestra como evidencia y preordena por ella, y un botón *selecciona* el
  lote — pero la escritura siempre la dispara una persona. Adivinar por la **descripción** del insumo
  sigue descartado.
- **Sin pantalla nueva:** una sección más en las pestañas que el maestro ya tenía. Desaparece cuando
  la cola llega a 0. RBAC `lps.pdc.maestro` (el maestro es global; clasificar no es una capacidad de obra).

**Hecho aparte, no implementado — gastos generales.** El comité pidió revisar si el presupuesto trae
categorías de gastos generales que el maestro no distinga. **No las trae:** los capítulos de nivel 1
son exactamente `COSTO DIRECTO` y `COSTO INDIRECTO`. Lo que Tomás llama «categorías de gastos
generales» llega por el **`agrupacion` de SINCO**, que el maestro **ya guarda** (`GASTOS MEDICOS Y
DROGAS PERSONAL OBRA`, `COMPRA ELEMENTOS- EQUIPO DE OFICINA`, …). No hay dato perdido: hay dato **no
explotado** — ninguna pantalla agrupa ni filtra por `agrupacion`. Eso es un entregable distinto (una
vista del maestro por agrupación) y necesita grilleo con Tomás para no duplicar lo que él ya tiene en
su código.
```

- [ ] **Step 4: Bitácora de validación**

Escribe en `goals/pdc-preparar-b1/validation-log.md` los siete puntos de la condición de hecho, cada uno con **la salida real del comando** que lo respalda (no una afirmación): el censo antes/después, las tres salidas del ciclo revertir→aplicar, los `rc=` de los ocho tests, y la captura del navegador. Cualquier punto sin salida pegada se reporta como **no verificado**.

- [ ] **Step 5: Commit** *(solo si el usuario autorizó publicar)*

```bash
git add docs/pdc-v2.md goals/pdc-preparar-b1/validation-log.md
git commit -m "docs(pdc): queda escrito que partir «Equipo» tenía trampa en el match del motor"
```

---

## Condición de hecho → dónde se cumple

| # | Spec | Task |
|---|---|---|
| 1 | Un insumo nuevo se puede crear como comprado o alquilado, y el valor sobrevive a recargar | 6 (persistencia verificada en BD) + 7 (UI) |
| 2 | Todos los equipos preexistentes en «sin clasificar» y en la cola, contados | 3 (censo) + 6 (`total` cuadra con la BD) |
| 3 | Clasificar 20 de golpe funciona y la cola baja en 20 | 6 (test explícito de 20) + 7 (selección múltiple) |
| 4 | El motor no ofrece un alquilado como candidato de un paquete de compra | 4 |
| 5 | Reimportar un presupuesto no devuelve a «sin clasificar» un insumo ya clasificado | 5 (SINCO, la cara rota) + 3 Step 6 (presupuesto, la cara ya sana, fijada con test) |
| 6 | La migración tiene vuelta atrás probada | 2 Step 5 (DDL) + 3 Step 5 (datos) |
| 7 | Regresión: maestro, importador SINCO y motor en verde | 8 Step 2 |
| — | Gastos generales revisadas y anotadas como hecho aparte | 8 Step 3 |

## Riesgos que este plan asume

- **El tapón son 167 decisiones humanas.** Se asume por decisión explícita del usuario. Mitigado a dos clics para 145 de ellas mediante preorden + selección por lote, sin escribir nada sin confirmación. «Sin clasificar» hereda el comportamiento del viejo `EQUIPO`, así que el módulo se puede usar con el tapón puesto.
- **`resolverTipoRecurso()` decide que «SINCO más preciso gana».** Si el equipo de presupuestos clasifica mal en SINCO, sobrescribirá una clasificación humana correcta. Es deliberado —SINCO es la fuente contable— y queda auditado en `clasificado_por`/`clasificado_at`. Si en obra molesta, la vuelta es invertir esa rama, no ampliar el blindaje.
- **La base de este trabajo es un volcado, no la de producción.** Los números (167 / 89 / 53) son del volcado del 2026-07-29. Antes de aplicar en cualquier otro entorno hay que **volver a correr el dry-run**, que recalcula por regla y no lleva ids fijos.
