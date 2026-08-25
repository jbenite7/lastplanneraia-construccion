---
capa: fuente
tipo: plan
estado: cerrado
fecha: 2026-08-19
areas: [proceso]
fuente: docs/superpowers/plans/2026-08-19-estados-fuera-de-ventana.md
resumen: que Fuera de Ventana deje de borrarse sola — los dos calculadores de estado la producen con la regla de 7+ semanas — y que por primera vez existan pruebas que…
---

# «Fuera de Ventana» en los dos calculadores — plan de implementación

> **Para trabajadores agénticos:** SUB-SKILL REQUERIDA: usá `superpowers:subagent-driven-development`
> o `superpowers:executing-plans` para ejecutar este plan tarea a tarea. Los pasos usan casillas
> (`- [ ]`) para seguimiento.

**Goal:** que `Fuera de Ventana` deje de borrarse sola — los dos calculadores de estado la producen
con la regla de 7+ semanas — y que por primera vez existan pruebas que fijen su comportamiento.

**Architecture:** hay **dos** implementaciones de la misma clasificación: `pg_calculate_status()`
en `src/Legacy/estado_programa_general.php` (con constantes `PG_STATUS_*`) y
`LpsService::calculateGeneralStatus()` en `src/Core/Lps/LpsService.php:124` (con literales). **Hoy
no las cubre ninguna prueba.** El plan escribe primero la caracterización del comportamiento actual
sobre ambas, después añade el octavo estado, y deja una prueba de paridad que falla si vuelven a
divergir.

**Tech Stack:** PHP 8.3, PHPUnit 12 (`tests/unit/*Test.php` con `#[Group]`), y el runner
`scripts/run-php-tests.php`, que corre las dos suites en una pasada.

**Spec:** decisiones del usuario del 2026-08-19 en `decisiones/estados-consolidado-coordinadora.md`;
contrato en `docs/design-system/ds-f1a-escala-estado.md`.

## Paso 0 de TODA tarea que ejecute PHP

**Antes de aceptar cualquier `RC=0`, comprobar qué árbol monta el contenedor:**

```bash
docker inspect $(docker compose ps -q app) \
  --format '{{range .Mounts}}{{if eq .Destination "/var/www/html"}}{{.Source}}{{end}}{{end}}'
```

Si no devuelve **este** worktree, el resultado no mide este trabajo y **no cuenta como
verificación**, ni en verde ni en rojo.

Existe porque pasó el 2026-08-19, ejecutando este mismo plan: `run-php-tests.php --nivel=puro`
devolvió `RC=0` y `OK (18 tests, 41 assertions)` mientras el contenedor servía el worktree
`reverent-golick-aaf932`. La prueba de caracterización **no se ejecutó ninguna de las dos veces**;
los 18 tests eran de la única clase que existía en aquel árbol. Lo delató que el conteo siguiera
en 18 al añadir un caso que debía subirlo a 21 — es decir, por casualidad. **Un verde que no
distingue «pasó» de «no se ejecutó» no es un verde.**

## Global Constraints

- **Ni un `UPDATE`.** Este frente no migra ni corrige datos: eso es el frente (B).
- **No tocar `Con Alerta Restricciones`** en ningún archivo. La orden de retirarla quedó derogada.
- **No tocar `state-semantics.json`.**
- **La regla es el offset de semanas ≥ 7**, ratificada por el usuario **conociendo** que reclasifica
  26 084 actividades y no 12 338, y que deja `Fuera de Ventana` en ~51%.
- **Los ocho estados y sus etiquetas exactas** salen de `docs/design-system/ds-f1a-escala-estado.json`.
- **Sin dependencias nuevas.**
- **El «goteo» es una decisión diferida al deploy, no de este plan.** A partir del despliegue, cada
  actividad que se guarde a 7+ semanas pasará sola a `Fuera de Ventana`, migrando la base por uso
  antes del frente (B). **Publicar en `main` no despliega**, y el deploy a producción exige
  autorización expresa del usuario siempre; goteo-vs-atómico se decide en ese momento y con él
  delante. Aprobado así por la coordinadora el 2026-08-19.
- **Nivel de las pruebas: `puro`.** Ninguna de las dos funciones consulta la base.

---

### Task 1: Caracterizar lo que los dos calculadores hacen HOY

Antes de cambiar nada. Si esta tarea encuentra que los dos ya discrepan en algo más que el umbral
conocido, **se para y se reporta**: sería un hallazgo, no un detalle.

**Files:**
- Create: `tests/unit/EstadoProgramaGeneralTest.php`

**Interfaces:**
- Consumes: nada.
- Produces: la clase `Tests\Unit\EstadoProgramaGeneralTest` y su proveedor de datos
  `casosDeEstado()`, que la Task 2 amplía con los casos de `Fuera de Ventana`.

- [x] **Step 1: Escribir la prueba de caracterización**

Crear `tests/unit/EstadoProgramaGeneralTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Lps\LpsService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Caracteriza los DOS calculadores de estado de Programa General, que hasta hoy no tenian
 * ninguna prueba: `pg_calculate_status()` (legacy, con constantes PG_STATUS_*) y
 * `LpsService::calculateGeneralStatus()` (con literales). Son la misma clasificacion escrita
 * dos veces, y de ellas depende el `Estado` de 65.549 filas.
 *
 * Nivel `puro`: ninguna de las dos consulta la base.
 */
#[Group('puro')]
final class EstadoProgramaGeneralTest extends TestCase
{
    protected function setUp(): void
    {
        require_once dirname(__DIR__, 2) . '/src/Legacy/estado_programa_general.php';
    }

    /** @return array<string, array{0: array<string, mixed>, 1: string}> */
    public static function casosDeEstado(): array
    {
        // titulo, ejecutado, inicioActividad, finActividad, inicioSemana, finSemana
        return [
            'un capitulo es capitulo antes de mirar fechas' => [
                ['titulo' => 1, 'ej' => 0.0, 'fi' => '2026-09-01', 'ff' => '2026-09-30',
                 'fs' => '2026-08-17', 'fe' => '2026-08-23'], 'Capítulo'],
            'ejecutado completo es terminada' => [
                ['titulo' => 0, 'ej' => 1.0, 'fi' => '2026-08-01', 'ff' => '2026-08-10',
                 'fs' => '2026-08-17', 'fe' => '2026-08-23'], 'Terminada'],
            'sin fechas y sin avance es sin datos' => [
                ['titulo' => 0, 'ej' => 0.0, 'fi' => null, 'ff' => null,
                 'fs' => '2026-08-17', 'fe' => '2026-08-23'], 'Sin Datos'],
            'empieza antes de la semana y no ha arrancado: atrasada' => [
                ['titulo' => 0, 'ej' => 0.0, 'fi' => '2026-08-03', 'ff' => '2026-08-14',
                 'fs' => '2026-08-17', 'fe' => '2026-08-23'], 'Atrasada'],
            'empieza dentro de la semana: debe iniciar' => [
                ['titulo' => 0, 'ej' => 0.0, 'fi' => '2026-08-19', 'ff' => '2026-08-28',
                 'fs' => '2026-08-17', 'fe' => '2026-08-23'], 'Debe Iniciar'],
            'empieza en tres semanas: actividad futura' => [
                ['titulo' => 0, 'ej' => 0.0, 'fi' => '2026-09-07', 'ff' => '2026-09-18',
                 'fs' => '2026-08-17', 'fe' => '2026-08-23'], 'Actividad Futura'],
        ];
    }

    /** @param array<string, mixed> $c */
    #[DataProvider('casosDeEstado')]
    public function testElCalculadorLegacyClasifica(array $c, string $esperado): void
    {
        $this->assertSame($esperado, pg_calculate_status(
            $c['titulo'], $c['ej'], $c['fi'], $c['ff'], $c['fs'], $c['fe'],
        ));
    }

    /** @param array<string, mixed> $c */
    #[DataProvider('casosDeEstado')]
    public function testElCalculadorDeLpsServiceClasificaIgual(array $c, string $esperado): void
    {
        $this->assertSame($esperado, (new LpsService())->calculateGeneralStatus(
            $c['titulo'], $c['ej'], $c['fi'], $c['ff'], $c['fs'], $c['fe'],
        ));
    }

    /**
     * La paridad es el invariante: dos implementaciones de la misma clasificacion tienen que
     * responder lo mismo. Esta prueba es la que se pondra roja si alguien toca una y olvida la
     * otra, que es como llegaron a divergir en el umbral de la rama sin fechas.
     *
     * @param array<string, mixed> $c
     */
    #[DataProvider('casosDeEstado')]
    public function testLosDosCalculadoresCoinciden(array $c, string $ignorado): void
    {
        $this->assertSame(
            pg_calculate_status($c['titulo'], $c['ej'], $c['fi'], $c['ff'], $c['fs'], $c['fe']),
            (new LpsService())->calculateGeneralStatus(
                $c['titulo'], $c['ej'], $c['fi'], $c['ff'], $c['fs'], $c['fe'],
            ),
        );
    }
}
```

- [x] **Step 2: Correr y ver qué dice del estado actual**

```bash
docker compose exec -T app php scripts/run-php-tests.php --nivel=puro
```

Esperado: **todo en verde.** Es caracterización, no TDD: describe lo que ya hace el código.

**Si algún caso falla, PARAR y reportar** — significaría que los dos calculadores divergen en algo
que no habíamos medido, y eso cambia el alcance.

- [x] **Step 3: Añadir el caso que expone la divergencia conocida**

Añadir a `casosDeEstado()`:

```php
            'sin fechas con avance del 5%: aqui los dos discrepan hoy' => [
                ['titulo' => 0, 'ej' => 0.05, 'fi' => null, 'ff' => null,
                 'fs' => '2026-08-17', 'fe' => '2026-08-23'], 'En Curso'],
```

- [x] **Step 4: Correr y confirmar que la paridad falla**

```bash
docker compose exec -T app php scripts/run-php-tests.php --nivel=puro
```

Esperado: **falla `testLosDosCalculadoresCoinciden`** para ese caso, y falla
`testElCalculadorDeLpsServiceClasificaIgual`. El legacy dice `En Curso` (su `PG_STATUS_EPS` es
0.001, y 0.05 > 0.001); `LpsService` dice `Sin Datos` (su literal es 0.1, y 0.05 < 0.1). Es la
divergencia medida, ahora con una prueba que la nombra.

- [x] **Step 5: Commit**

```bash
git add tests/unit/EstadoProgramaGeneralTest.php
git commit -m "test(estados): caracteriza los dos calculadores, que no tenian ninguna prueba"
```

---

### Task 2: Unificar el umbral divergente

**Files:**
- Modify: `src/Core/Lps/LpsService.php:147-148`

**Interfaces:**
- Consumes: de Task 1, la clase de prueba y su proveedor.
- Produces: `LpsService::calculateGeneralStatus()` con el mismo umbral que el legacy.

- [x] **Step 1: Elegir el canónico y dejarlo escrito**

El canónico es **`PG_STATUS_EPS = 0.001`, el del legacy**, por dos razones que van en el comentario
del código: es el que está declarado como constante con nombre en vez de un literal suelto, y es el
que se aplica en el resto de las ramas de **ambas** implementaciones — el `0.1` de `LpsService`
aparece **solo** en la rama de fechas nulas, así que es la excepción, no la regla.

- [x] **Step 2: Cambiar el literal por el umbral canónico**

En `src/Core/Lps/LpsService.php`, sustituir:

```php
            return ($ej > 0.1) ? 'En Curso' : 'Sin Datos';
```

por:

```php
            // 0.001 es PG_STATUS_EPS, el umbral canonico que usa el resto de las ramas de las
            // DOS implementaciones. Aqui habia un 0.1 suelto -la unica rama donde diferian-, asi
            // que una actividad sin fechas con avance entre 0,1% y 10% salia `En Curso` por el
            // calculador legacy y `Sin Datos` por este. Medido el 2026-08-19: cero filas caian en
            // esa ventana, asi que era deuda latente y no un fallo activo.
            return ($ej > 0.001) ? 'En Curso' : 'Sin Datos';
```

- [x] **Step 3: Correr y confirmar que la paridad vuelve al verde**

```bash
docker compose exec -T app php scripts/run-php-tests.php --nivel=puro
```

Esperado: **todo verde**, incluido el caso del 5% que fallaba en la Task 1.

- [x] **Step 4: Commit**

```bash
git add src/Core/Lps/LpsService.php
git commit -m "fix(estados): los dos calculadores comparten el umbral de la rama sin fechas"
```

---

### Task 3: `Fuera de Ventana` en los dos calculadores

**Files:**
- Modify: `src/Legacy/estado_programa_general.php` (la rama final de `pg_calculate_status`)
- Modify: `src/Core/Lps/LpsService.php` (la rama final de `calculateGeneralStatus`)
- Modify: `tests/unit/EstadoProgramaGeneralTest.php`

**Interfaces:**
- Consumes: de Task 1 y 2, la prueba en verde y los umbrales unificados.
- Produces: los dos calculadores devolviendo un octavo estado, `'Fuera de Ventana'`.

- [x] **Step 1: Escribir los casos que fallan**

Añadir a `casosDeEstado()`:

```php
            'empieza en 7 semanas justas: fuera de ventana' => [
                ['titulo' => 0, 'ej' => 0.0, 'fi' => '2026-10-05', 'ff' => '2026-10-16',
                 'fs' => '2026-08-17', 'fe' => '2026-08-23'], 'Fuera de Ventana'],
            'empieza en 6 semanas: sigue siendo actividad futura' => [
                ['titulo' => 0, 'ej' => 0.0, 'fi' => '2026-09-28', 'ff' => '2026-10-09',
                 'fs' => '2026-08-17', 'fe' => '2026-08-23'], 'Actividad Futura'],
```

El primero cae a 49 días de la semana, o sea offset 7. El segundo a 42 días, offset 6: **el borde
exacto de la regla**, y por eso están los dos.

- [x] **Step 2: Correr y verlos fallar**

```bash
docker compose exec -T app php scripts/run-php-tests.php --nivel=puro
```

Esperado: los dos casos nuevos fallan en las tres pruebas, con `Actividad Futura` donde se espera
`Fuera de Ventana`.

- [x] **Step 3: Añadir la regla al calculador legacy**

En `src/Legacy/estado_programa_general.php`, sustituir la última línea de `pg_calculate_status`:

```php
    return 'Actividad Futura';
```

por:

```php
    // Fuera del lookahead: la actividad no entra todavia en la ventana de planificacion. Es una
    // posicion en el tiempo, no un grado de urgencia, y por eso el contrato la declara sin nivel
    // de gravedad (docs/design-system/ds-f1a-escala-estado.json). El umbral son las 6 semanas de
    // PG_LOOKAHEAD_DAYS = 42; a partir de la septima, fuera.
    $offset = pg_calculate_week_offset($fechaInicioActividad, $fechaInicioSemana);
    if ($offset !== null && $offset >= 7) {
        return 'Fuera de Ventana';
    }

    return 'Actividad Futura';
```

- [x] **Step 4: Añadir la misma regla a `LpsService`**

En `src/Core/Lps/LpsService.php`, sustituir la última línea de `calculateGeneralStatus`:

```php
        return 'Actividad Futura';
```

por:

```php
        // Misma regla que `pg_calculate_status`, y la prueba de paridad de
        // `EstadoProgramaGeneralTest` existe para que no vuelvan a separarse.
        $dias = (int) floor(($fiTs - $fsTs) / 86400);
        if ((int) floor($dias / 7) >= 7) {
            return 'Fuera de Ventana';
        }

        return 'Actividad Futura';
```

- [x] **Step 5: Correr y confirmar el verde**

```bash
docker compose exec -T app php scripts/run-php-tests.php --nivel=puro
```

Esperado: **todo verde**, los ocho estados en las dos implementaciones y la paridad intacta.

- [x] **Step 6: Comprobar que no rompe el resto de la suite PHP**

```bash
docker compose exec -T app php scripts/run-php-tests.php --nivel=http
```

Esperado: RC=0. Si algo se pone rojo aquí, es un consumidor que asumía siete estados: **parar y
reportar** antes de tocarlo.

- [x] **Step 7: Commit**

```bash
git add src/Legacy/estado_programa_general.php src/Core/Lps/LpsService.php tests/unit/EstadoProgramaGeneralTest.php
git commit -m "feat(estados): los dos calculadores producen Fuera de Ventana a partir de la septima semana"
```

---

### Task 4: Cerrar el «pendiente» del contrato

**Files:**
- Modify: `docs/design-system/ds-f1a-escala-estado.json`
- Modify: `docs/design-system/ds-f1a-escala-estado.md`

**Interfaces:**
- Consumes: de Task 3, el hecho de que los calculadores ya lo producen.
- Produces: el contrato sin pregunta abierta.

- [x] **Step 1: Cerrar la pregunta en el JSON**

En el estado `fuera-de-ventana`, sustituir la clave `pendiente` por:

```json
      "persistencia": "valor persistido — decision del usuario del 2026-08-19, tomada conociendo que la regla alcanza a 26084 actividades y no a 12338, y que deja Fuera de Ventana en ~51%",
      "producido_desde": "2026-08-19 por pg_calculate_status y LpsService::calculateGeneralStatus",
```

y cambiar su `origen` de `"legacy-sin-productor"` a `"pg_calculate_status"`.

- [x] **Step 2: Correr la prueba del contrato**

```bash
node --test tests/design-system/ds-f1a-escala-estado.test.mjs
```

Esperado: **9 pass**. El caso `cada estado declara quien lo produce` acepta el valor nuevo porque
`pg_calculate_status` ya está en su lista de orígenes válidos.

- [x] **Step 3: Actualizar el contrato legible**

En `docs/design-system/ds-f1a-escala-estado.md`:
1. Sustituir la sección «Pendiente de decisión del usuario» por una que diga la decisión tomada,
   su fecha, y que se tomó **conociendo el 51%**.
2. En la tabla de los trece estados, cambiar la columna «Quién lo produce» de `Fuera de Ventana`
   a `pg_calculate_status`.
3. Añadir un aviso: **los porcentajes de la tabla son del reparto anterior a la migración**; el
   frente (B) los actualizará cuando los datos se recalculen.

- [x] **Step 4: Verificar el lint de la wiki**

```bash
npm run test:wiki
```

Esperado: RC=0.

- [x] **Step 5: Commit**

```bash
git add docs/design-system/ds-f1a-escala-estado.json docs/design-system/ds-f1a-escala-estado.md
git commit -m "docs(estados): el contrato cierra su pregunta abierta con la decision del usuario"
```

---

### Task 5: El diagnóstico de las 113 contradictorias

Se **diagnostican, no se corrigen**. Corregirlas es del frente (B).

**Files:**
- Create: `goals/estados-fuera-de-ventana/diagnostico-113-contradictorias.md`

**Interfaces:**
- Consumes: nada de las tareas anteriores.
- Produces: el informe. No lo consume ninguna tarea de este plan.

- [x] **Step 1: Medir las 113 y clasificarlas**

```bash
docker compose exec -T app php -r '
$pdo = new PDO("mysql:host=db;dbname=".getenv("DB_NAME").";charset=utf8mb4", getenv("DB_USER"), getenv("DB_PASS"));
foreach ($pdo->query("SELECT Estado, COUNT(*) n, MIN(Semanas_Inicio) mn, MAX(Semanas_Inicio) mx, ROUND(AVG(COALESCE(Ejecutado,0)),3) ej, COUNT(DISTINCT project_id) p
  FROM programa_consolidado
  WHERE COALESCE(Titulo,0)<>1 AND Semanas_Inicio>=7 AND Estado NOT IN (\"Actividad Futura\",\"No Requerida\")
  GROUP BY Estado ORDER BY n DESC") as $r)
  printf("%-20s n=%-5s semanas=%s..%s ejecutado_medio=%s proyectos=%s\n", $r["Estado"], $r["n"], $r["mn"], $r["mx"], $r["ej"], $r["p"]);
'
```

- [x] **Step 2: Escribir el informe**

Crear `goals/estados-fuera-de-ventana/diagnostico-113-contradictorias.md` con: la consulta exacta y
su salida literal; qué hace contradictoria a cada familia (una actividad `Terminada` que empieza
dentro de siete semanas o más no puede estar terminada); si se concentran en pocos proyectos o
están repartidas; y **tres hipótesis con lo que cada una implicaría para el frente (B)** — fechas
mal importadas desde Project, `Semanas_Inicio` calculada contra una semana activa distinta de la
actual, o estados escritos a mano. **Sin elegir una**: eso exige datos que este frente no tiene.

- [x] **Step 3: Commit**

```bash
git add goals/estados-fuera-de-ventana/diagnostico-113-contradictorias.md
git commit -m "docs(estados): el diagnostico de las 113 filas contradictorias, sin corregirlas"
```

---

### Task 6: La nota de wiki del contenedor compartido

**Files:**
- Create: `memoria/trampas/contenedor-compartido-durante-verificacion.md`
- Modify: `memoria/log.md`

- [x] **Step 1: Escribir la nota**

Crear `memoria/trampas/contenedor-compartido-durante-verificacion.md` con el molde de
`memoria/templates/trampa.md` y el **vocabulario cerrado** de `scripts/wiki-esquema.mjs` —`tags`
solo de `['dashboard','plantilla','pendiente','trampa','leer-antes-de-tocar','generado','archivo']`
y `areas` de la lista cerrada—. Contenido: el síntoma es una suite que falla con
`service "app" is not running` o con resultados que no cuadran; lo que parece es que rompiste algo;
lo que es, que el contenedor `app` es **uno solo para todo el repo** y otra sesión puede estar
recreándolo o usándolo con otro worktree montado; cómo se sale, mirando `docker compose ps` (si
lleva segundos arriba, era una carrera) y `docker inspect` para ver qué monta; y cuánto costó: el
2026-08-19, una re-verificación en rojo que no era del código.

- [x] **Step 2: Verificar el lint**

```bash
npm run test:wiki
```

Esperado: RC=0, sin hallazgos. **Si sale «tag fuera del vocabulario cerrado», corregir el
frontmatter** — es el error que ya costó una vuelta el 2026-08-19.

- [x] **Step 3: Añadir la línea de bitácora y commitear**

```bash
git add memoria/trampas/contenedor-compartido-durante-verificacion.md memoria/log.md
git commit -m "docs(memoria): la trampa del contenedor compartido durante una verificacion"
```

---

## Cierre del frente

- [x] Verificar con salida real: `--nivel=puro`, `--nivel=http`, `node --test` del contrato y
      `npm run test:wiki`.
- [x] `git status` limpio. **Comprobar que no hay ni un `UPDATE` en el diff.**
- [x] `git fetch origin` y mirar la divergencia.
- [x] Integrar si la hay, resolviendo a la vista.
- [x] **Re-verificar después de integrar, no antes.** Anotar el sha.
- [x] Pedir el visto a la coordinadora con el sha medido.
- [x] Publicar el sha exacto visado. `5759b13d` viaja en este cierre.
- [x] Confirmar que `origin/main` coincide con el sha anotado.
- [x] Anotar el cierre en `goals/estados-fuera-de-ventana/goal.md`.

---

## Estado verificado — cerrado

Verificado contra el código el 2026-08-25. **`estado: cerrado` es una afirmación deliberada**, no el valor por defecto del backfill.

**Evidencia:** tests/unit/EstadoProgramaGeneralTest.php; «Fuera de Ventana» en src/Legacy/estado_programa_general.php y src/Core/Lps/LpsService.php; publicado 5759b13d

Criterio y método: [[docs/superpowers/plans/2026-08-25-estado-real-de-planes-y-specs]].
