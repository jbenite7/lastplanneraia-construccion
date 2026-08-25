---
capa: fuente
tipo: plan
estado: cerrado
fecha: 2026-08-20
areas: [bi, datos, pdc]
fuente: docs/superpowers/specs/2026-08-20-replanteo-control-tower-design.md
resumen: "Fase 0 del replanteo de la Control Tower: que ninguna cifra venga de una fuente que no es la que declara, y que ningún texto de causa se corte donde dice de quién es la culpa"
project: lps-aia
---

# Control Tower · Fase 0 — Higiene de datos — Plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Que ninguna cifra de la Control Tower venga de una fuente distinta de la que declara, que ningún texto de causa se trunque donde dice de quién es la culpa, y que las tablas muertas del PDC v1 salgan de producción.

**Architecture:** Cinco arreglos independientes sobre el código y los datos existentes, más un retiro de tablas con gate propio. No se toca la interfaz de la Torre ni se construye ninguna hoja nueva: F0 solo deja el terreno en condiciones de que F1 pueda prometer trazabilidad sin mentir.

**Tech Stack:** PHP 8.3 en Docker · MySQL 8.0 · vistas SQL en `database/bi/` · pruebas sueltas `tests/test_*.php` y PHPUnit 12 en `tests/unit/` · `scripts/run-php-tests.php` como entrada única.

**Spec:** `docs/superpowers/specs/2026-08-20-replanteo-control-tower-design.md` (sección 13, fila F0)

## Global Constraints

- **Solo lectura en producción.** Producción se consulta por `ssh siteground-produccion-lastplanner` con `SELECT` y `SHOW` únicamente. Ninguna escritura, ningún volcado a disco.
- **Toda consulta operativa se aísla por `project_id`.** Regla del repositorio, sin excepciones.
- **Sentencias preparadas siempre**, a través de la capa `App\Core\Database`. Nunca SQL construido con datos de usuario.
- **Las pruebas declaran su nivel.** Los scripts sueltos con `// @requiere: <nivel>`; las clases PHPUnit con `#[Group(...)]`. Sin eso, `scripts/run-php-tests.php` aborta.
- **Comandos dentro del contenedor:** `docker compose exec app ...`. Nunca un PHP del host.
- **El `.env` del worktree es un enlace**, no una copia: `ln -s ~/Developer/lps-aia/.env .env`.
- **Ningún cambio de esquema en esta fase.** F0 no altera tablas; el único DDL autorizado es el `CREATE OR REPLACE VIEW` de la Tarea 1 y el retiro de la Tarea 6, que tiene gate propio.
- **Nada de regenerar baselines** para forzar un verde.

---

## Estructura de archivos

| Archivo | Responsabilidad | Tarea |
|---|---|---|
| `src/Services/ReportProcessor.php` | Poblar `cip` junto con `cic` al procesar el reporte semanal | 1 |
| `tests/test_cip_poblado.php` | Probar que procesar una semana con responsables deja filas en `cip` | 1 |
| `src/Services/ControlTowerService.php` | Renombrar el eje del radar y su fórmula declarada | 2 |
| `src/Services/Bi/MetricDictionaryService.php` | Alinear el catálogo con el nombre real de la métrica | 2 |
| `tests/unit/RadarAxisNamingTest.php` | Probar que el eje declara la fuente que de verdad usa | 2 |
| `public/js/modules/bi-spa.js` | Dejar de truncar la atribución en las gráficas de causas | 3 |
| `tests/test_causa_atribucion.php` | Probar que la atribución sobrevive al pintado | 3 |
| `scripts/higiene/reparar-mojibake-causas.php` | Reparar los textos de causa mal codificados | 4 |
| `tests/test_causas_codificacion.php` | Probar que no quedan secuencias mal codificadas | 4 |
| `docs/superpowers/notas/2026-08-20-campos-muertos.md` | Registrar el veredicto de cada campo sin uso | 5 |
| `docs/superpowers/notas/2026-08-20-retiro-pdc-v1.md` | Evidencia y bitácora del retiro | 6 |

---

## Task 1: Responsables — `cip` no se puebla

**Contexto que el ejecutor necesita.** La hoja de Responsables lee la vista `bi_cip_responsables`, que hace `FROM cip`. Medido el 2026-08-20: **`cip` tiene 1 fila en desarrollo y 1 en producción**, y esa fila es de fixture (proyecto 76, profesional `@ci.invalid`). Su gemela `cic` tiene 323 en desarrollo y 340 en producción. La materia prima existe: **200 profesionales y 41 responsables distintos** con compromisos en `programacion_semanal`. `ReportProcessor` tiene código para poblar las dos (`cic` alrededor de la línea 642, `cip` alrededor de la 888). **La vista no está mal construida: el origen está vacío.**

**Files:**
- Modify: `src/Services/ReportProcessor.php` (bloque de `cip`, desde ~888 hasta ~959)
- Test: `tests/test_cip_poblado.php`

**Interfaces:**
- Consumes: `App\Services\ReportProcessor::procesarReporte()` tal como lo invoca hoy `ReportController`.
- Produces: nada nuevo para otras tareas. El efecto es que `cip` queda con una fila por profesional y semana.

- [ ] **Step 1: Reproducir el hueco con una consulta, antes de tocar código**

```bash
docker compose exec -T app php -r '
$pdo = new PDO("mysql:host=db;dbname=lastplanneraia_dev", getenv("DB_USER"), getenv("DB_PASS"));
printf("cip=%s  cic=%s  profesionales=%s  responsables=%s\n",
  $pdo->query("SELECT COUNT(*) FROM cip")->fetchColumn(),
  $pdo->query("SELECT COUNT(*) FROM cic")->fetchColumn(),
  $pdo->query("SELECT COUNT(*) FROM profesionales")->fetchColumn(),
  $pdo->query("SELECT COUNT(DISTINCT Responsable_AIA) FROM programacion_semanal WHERE Responsable_AIA<>\"\"")->fetchColumn());'
```

Esperado: `cip=1` con `cic` y `profesionales` en cientos. Ese contraste es el defecto.

- [ ] **Step 2: Escribir la prueba que falla**

```php
<?php
// @requiere: db
// Prueba: procesar una semana con responsables deja filas en `cip`.
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

$db = Database::getInstance();
$projectId = (int) ($argv[1] ?? 68);

$responsables = (int) $db->query(
    "SELECT COUNT(DISTINCT Responsable_AIA) FROM programacion_semanal
     WHERE project_id = ? AND Responsable_AIA <> ''", [$projectId]
)->fetchColumn();

$enCip = (int) $db->query(
    "SELECT COUNT(DISTINCT profesional) FROM cip WHERE project_id = ?", [$projectId]
)->fetchColumn();

if ($responsables === 0) {
    echo "OMITIDA: el proyecto $projectId no tiene responsables\n";
    exit(0);
}
if ($enCip === 0) {
    echo "FALLA: $responsables responsables con compromisos y 0 en cip (proyecto $projectId)\n";
    exit(1);
}
echo "PASA: $enCip de $responsables responsables presentes en cip\n";
exit(0);
```

- [ ] **Step 3: Correr la prueba y verificar que falla**

Run: `docker compose exec app php tests/test_cip_poblado.php 68`
Esperado: `FALLA: 12 responsables con compromisos y 0 en cip (proyecto 68)`

- [ ] **Step 4: Encontrar por qué el bloque de `cip` no produce filas**

Leer `src/Services/ReportProcessor.php` desde la línea 880 hasta la 960 y responder por escrito, en el commit, cuál de estas tres es la causa:

1. El bloque de `cip` nunca se invoca (está detrás de una condición que no se cumple).
2. Se invoca y su consulta de origen devuelve cero filas (el `WHERE` no empata con `programacion_semanal`).
3. Se invoca, produce filas y algo las borra después — mirar `deleteRowsNotInProcessedEntities` en la línea ~959.

Comando de apoyo para distinguir la 2 de la 3:

```bash
docker compose exec -T app php -r '
$pdo = new PDO("mysql:host=db;dbname=lastplanneraia_dev", getenv("DB_USER"), getenv("DB_PASS"));
foreach ($pdo->query("SELECT DISTINCT Responsable_AIA FROM programacion_semanal WHERE project_id=68 AND Responsable_AIA<>\"\" LIMIT 5") as $r) {
  $n = $pdo->query("SELECT COUNT(*) FROM profesionales WHERE project_id=68 AND nombre=" . $pdo->quote($r[0]))->fetchColumn();
  printf("  %-40s en profesionales: %s\n", $r[0], $n);
}'
```

Si los responsables no aparecen en `profesionales`, la causa es la 2 y el arreglo va en el empate de nombres.

- [ ] **Step 5: Aplicar el arreglo mínimo**

Corregir **solo** la causa encontrada. No reescribir el bloque, no cambiar el de `cic`, no tocar la vista. Si la causa es el empate de nombres, normalizar por el mismo criterio que ya usa `ProjectProfessionalsSyncService` (línea ~623) en vez de inventar uno nuevo.

- [ ] **Step 6: Correr la prueba y verificar que pasa**

Run: `docker compose exec app php tests/test_cip_poblado.php 68`
Esperado: `PASA: 12 de 12 responsables presentes en cip`

- [ ] **Step 7: Comprobar que la hoja de Responsables ya tiene qué mostrar**

```bash
docker compose exec -T app php -r '
$pdo = new PDO("mysql:host=db;dbname=lastplanneraia_dev", getenv("DB_USER"), getenv("DB_PASS"));
echo "bi_cip_responsables: " . $pdo->query("SELECT COUNT(*) FROM bi_cip_responsables")->fetchColumn() . " filas\n";'
```

Esperado: decenas de filas, no 1.

- [ ] **Step 8: Correr la suite y commitear**

```bash
docker compose exec app php scripts/run-php-tests.php --nivel=db
git add src/Services/ReportProcessor.php tests/test_cip_poblado.php
git commit -m "fix(bi): poblar cip para que la hoja de Responsables tenga origen"
```

---

## Task 2: El eje del radar dice «Productividad» y mide avance

**Contexto que el ejecutor necesita.** El radar de Programa General tiene un eje llamado **Productividad**. Medido el 2026-08-20: **no usa la columna `medir_productividad`** —que está al 0% de llenado y es una bandera de `LpsService`, otra cosa— sino `P_Completado`. Su propia etiqueta interna ya lo dice: «Avance promedio válido». **El catálogo no miente sobre la tabla de origen; el eje está mal bautizado.** Es el segundo caso del mismo patrón, después de `ps_pac_expected`. La spec exige en su sección 6 que ninguna métrica se llame distinto de lo que mide.

**Files:**
- Modify: `src/Services/ControlTowerService.php:3321-3324` (definición del eje) y `:1035` (etiquetas del gráfico)
- Modify: `src/Services/Bi/MetricDictionaryService.php` (entrada `pg_radar_productividad`)
- Test: `tests/unit/RadarAxisNamingTest.php`

**Interfaces:**
- Consumes: `ControlTowerService::programaRadar(array $rows): array`, que devuelve ejes con las claves `name`, `label`, `formula`, `value`.
- Produces: la clave interna del eje sigue siendo `productividad` — **no se renombra la clave**, porque `LineageService::REPORT_METRICS` y la interfaz la referencian. Solo cambian `name` y el nombre visible.

- [ ] **Step 1: Escribir la prueba que falla**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Bi\MetricDictionaryService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('puro')]
final class RadarAxisNamingTest extends TestCase
{
    public function testElEjeNoSeLlamaProductividadPorqueMideAvance(): void
    {
        $definicion = (new MetricDictionaryService())->getDefinition('pg_radar_productividad');

        $this->assertNotSame(
            'Radar: Productividad',
            $definicion['metric_name'],
            'El eje mide P_Completado (avance), no productividad. El nombre debe decir lo que mide.'
        );
        $this->assertStringContainsString('P_Completado', $definicion['formula']);
    }
}
```

- [ ] **Step 2: Correr la prueba y verificar que falla**

Run: `docker compose exec app vendor/bin/phpunit tests/unit/RadarAxisNamingTest.php`
Esperado: FALLA — el nombre actual es exactamente `Radar: Productividad`.

- [ ] **Step 3: Renombrar el eje en el servicio**

En `src/Services/ControlTowerService.php`, dentro de `programaRadar()`:

```php
'productividad' => [
    'name' => 'Avance promedio',
    'label' => 'Avance promedio válido',
    'formula' => 'PROMEDIO(MIN(P_Completado válido, 1)) × 100; válido cuando P_Completado es mayor o igual que 0.',
    'value' => fn(array $row): ?float => $this->radarProgressValue($row),
],
```

Y en la línea ~1035, las etiquetas del gráfico:

```php
'programa-radar-productividad' => $this->chart('radar', ['Avance promedio', 'Eficiencia', 'Desempeño'], [
```

- [ ] **Step 4: Alinear el catálogo**

En `MetricDictionaryService`, en la entrada `pg_radar_productividad`, cambiar `metric_name` a `'Radar: Avance promedio'` y subir `version` a `'2.1'`. **No cambiar `metric_key`**: la clave viaja en `LineageService` y en la interfaz.

- [ ] **Step 5: Correr la prueba y verificar que pasa**

Run: `docker compose exec app vendor/bin/phpunit tests/unit/RadarAxisNamingTest.php`
Esperado: PASS

- [ ] **Step 6: Comprobar que nada más referenciaba el nombre visible**

```bash
grep -rn "Radar: Productividad" src/ public/js/ views/ tests/
```

Esperado: sin resultados fuera de los archivos ya modificados.

- [ ] **Step 7: Correr la suite y commitear**

```bash
docker compose exec app php scripts/run-php-tests.php --nivel=puro
git add src/Services/ControlTowerService.php src/Services/Bi/MetricDictionaryService.php tests/unit/RadarAxisNamingTest.php
git commit -m "fix(bi): el eje del radar se llama Avance promedio, que es lo que mide"
```

---

## Task 3: La gráfica trunca la atribución de la causa

**Contexto que el ejecutor necesita.** El catálogo de causas distingue de quién es la culpa con un sufijo. Medido el 2026-08-20 en `programacion_semanal`: «Actividad predecesora incompleta / no ejecutada **(obra)**» 502 veces · «**(subcontratista)**» 297 · sin atribuir 224. En el informe de Power BI esas tres se ven como dos entradas idénticas porque el texto se corta antes del paréntesis. **Es el dato más político del tablero y la visualización lo borra.** La spec lo exige en 8.3 (D65).

**Files:**
- Modify: `public/js/modules/bi-spa.js` (donde se arman las leyendas y etiquetas de las donas de causas)
- Test: `tests/test_causa_atribucion.php`

**Interfaces:**
- Consumes: el bloque `chart` de causas que arma `ControlTowerService`, con la etiqueta completa en cada punto.
- Produces: nada para otras tareas.

- [ ] **Step 1: Escribir la prueba que falla**

```php
<?php
// @requiere: puro
// Prueba: la interfaz no recorta la atribución de la causa.
$spa = file_get_contents(__DIR__ . '/../public/js/modules/bi-spa.js');

$sospechas = [];
foreach (explode("\n", $spa) as $n => $linea) {
    if (preg_match('/(substring|substr|slice)\s*\(\s*0\s*,\s*\d{1,2}\s*\)/', $linea)) {
        $sospechas[] = ($n + 1) . ': ' . trim($linea);
    }
}
if ($sospechas !== []) {
    echo "FALLA: hay recortes de texto que pueden borrar la atribución:\n  " . implode("\n  ", $sospechas) . "\n";
    exit(1);
}
echo "PASA: ningún recorte de texto corto en la interfaz\n";
exit(0);
```

- [ ] **Step 2: Correr la prueba y verificar que falla**

Run: `docker compose exec app php tests/test_causa_atribucion.php`
Esperado: FALLA, con las líneas exactas donde se recorta.

- [ ] **Step 3: Quitar el recorte en las causas**

En cada línea que la prueba señale y que pertenezca a las gráficas de causas: entregar la etiqueta completa al gráfico y dejar que el ancho lo resuelva el CSS —salto de línea o `title`—, no el JavaScript. Si una etiqueta no cabe, se acorta **por el principio**, conservando el sufijo entre paréntesis, que es lo que distingue las tres causas.

- [ ] **Step 4: Correr la prueba y verificar que pasa**

Run: `docker compose exec app php tests/test_causa_atribucion.php`
Esperado: PASA

- [ ] **Step 5: Verificar en el navegador**

Abrir `http://localhost:8081/dev/entrar?u=test.A` y luego `/bi/programa-general`, con un proyecto que tenga causas. Comprobar en la dona de No Programación que se distinguen «(obra)» y «(subcontratista)». Revisar la consola.

- [ ] **Step 6: Commitear**

```bash
git add public/js/modules/bi-spa.js tests/test_causa_atribucion.php
git commit -m "fix(bi): no truncar la atribución de la causa en las graficas"
```

---

## Task 4: Textos de causa mal codificados

**Contexto que el ejecutor necesita.** En el catálogo de causas aparecen textos con la codificación rota: «Diseńos en revisión» en vez de «Diseños», y «Programación» mal formada. Un ranking de causas con dos variantes del mismo texto suma mal.

**Files:**
- Create: `scripts/higiene/reparar-mojibake-causas.php`
- Test: `tests/test_causas_codificacion.php`

**Interfaces:**
- Produces: el script se ejecuta con `--dry-run` por defecto y solo aplica con `--aplicar`.

- [ ] **Step 1: Escribir la prueba que falla**

```php
<?php
// @requiere: db
// Prueba: no hay textos de causa con codificación rota.
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

$db = Database::getInstance();
$malas = [];
foreach (['CNC', 'CNP', 'Categoria_CNC', 'Categoria_CNP'] as $col) {
    $filas = $db->query(
        "SELECT DISTINCT `$col` AS t FROM programacion_semanal WHERE `$col` <> ''"
    )->fetchAll(PDO::FETCH_COLUMN);
    foreach ($filas as $t) {
        if ($t !== mb_convert_encoding(mb_convert_encoding($t, 'UTF-8', 'UTF-8'), 'UTF-8', 'UTF-8')
            || preg_match('/[\x{FFFD}]|ń|Ã|Â/u', $t)) {
            $malas[] = "$col: $t";
        }
    }
}
if ($malas !== []) {
    echo "FALLA: " . count($malas) . " textos con codificación rota:\n  " . implode("\n  ", array_slice($malas, 0, 10)) . "\n";
    exit(1);
}
echo "PASA: catálogo de causas sin textos rotos\n";
exit(0);
```

- [ ] **Step 2: Correr la prueba y verificar que falla**

Run: `docker compose exec app php tests/test_causas_codificacion.php`
Esperado: FALLA, listando «Diseńos» entre otros.

- [ ] **Step 3: Escribir el script de reparación**

```php
<?php

declare(strict_types=1);

// Repara textos de causa con codificación rota. Dry-run por defecto.
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Core\Database;

$aplicar = in_array('--aplicar', $argv, true);
$db = Database::getInstance();

$reemplazos = [
    'Diseńos' => 'Diseños',
    'Diseńo'  => 'Diseño',
];

foreach (['CNC', 'CNP', 'Categoria_CNC', 'Categoria_CNP'] as $col) {
    foreach ($reemplazos as $malo => $bueno) {
        $n = (int) $db->query(
            "SELECT COUNT(*) FROM programacion_semanal WHERE `$col` LIKE ?", ["%$malo%"]
        )->fetchColumn();
        if ($n === 0) {
            continue;
        }
        printf("%s: %d filas con «%s» → «%s»%s\n", $col, $n, $malo, $bueno, $aplicar ? '' : ' (dry-run)');
        if ($aplicar) {
            $db->query(
                "UPDATE programacion_semanal SET `$col` = REPLACE(`$col`, ?, ?) WHERE `$col` LIKE ?",
                [$malo, $bueno, "%$malo%"]
            );
        }
    }
}
echo $aplicar ? "Aplicado.\n" : "Dry-run. Repetir con --aplicar para escribir.\n";
```

- [ ] **Step 4: Correr en dry-run y revisar el listado**

Run: `docker compose exec app php scripts/higiene/reparar-mojibake-causas.php`
Esperado: el conteo por columna, sin escribir nada. **Si aparece algún texto que no esté en la lista de reemplazos, añadirlo antes de aplicar.**

- [ ] **Step 5: Pedir autorización y aplicar**

Esto escribe en la base. **Requiere el visto explícito de Felipe en el chat**, y entonces:

```bash
AUTORIZADO_POR_FELIPE=1 docker compose exec app php scripts/higiene/reparar-mojibake-causas.php --aplicar
```

- [ ] **Step 6: Correr la prueba y verificar que pasa**

Run: `docker compose exec app php tests/test_causas_codificacion.php`
Esperado: PASA

- [ ] **Step 7: Commitear**

```bash
git add scripts/higiene/reparar-mojibake-causas.php tests/test_causas_codificacion.php
git commit -m "fix(datos): reparar la codificacion de los textos de causa"
```

---

## Task 5: Veredicto de los campos muertos

**Contexto que el ejecutor necesita.** Medido el 2026-08-20 sobre 5.713 filas de `programacion_semanal`, cuatro campos están al **0% de llenado**: `Categoria_CP`, `CP`, `alerta_crisis`, `reprogramaciones_semanales`. La spec (sección 18, punto 4) los deja como decisión de proceso: o se retiran, o alguien debería estar llenándolos. **Esta tarea no borra nada**: documenta el veredicto para que la decisión exista por escrito antes de que F1 construya encima.

**Files:**
- Create: `docs/superpowers/notas/2026-08-20-campos-muertos.md`

- [ ] **Step 1: Medir en producción, no solo en desarrollo**

```bash
ssh siteground-produccion-lastplanner 'cd ~/www/lastplanneraia.com/public_html && php -r "
\$l=parse_ini_file(\".env\");
\$p=new PDO(\"mysql:host=\".\$l[\"DB_HOST\"].\";dbname=\".\$l[\"DB_NAME\"], \$l[\"DB_USER\"], \$l[\"DB_PASS\"]);
\$t=\$p->query(\"SELECT COUNT(*) FROM programacion_semanal\")->fetchColumn();
foreach([\"Categoria_CP\",\"CP\",\"alerta_crisis\",\"reprogramaciones_semanales\"] as \$c){
 \$n=\$p->query(\"SELECT COUNT(*) FROM programacion_semanal WHERE \`\$c\` IS NOT NULL AND \`\$c\`<>0 AND \`\$c\`<>0\")->fetchColumn();
 printf(\"  %-30s %s de %s\n\",\$c,\$n,\$t); }"'
```

Un campo vacío en desarrollo pero lleno en producción **no está muerto**.

- [ ] **Step 2: Buscar quién los lee o los escribe**

```bash
grep -rn "Categoria_CP\|alerta_crisis\|reprogramaciones_semanales" src/ public/js/ views/ admin/ | grep -v "^Binary"
```

- [ ] **Step 3: Escribir el veredicto**

Un archivo con frontmatter del esquema de la wiki (`capa: fuente`, `tipo: reporte`, `estado: abierto`, `fecha`, `areas: [datos, lps]`, `fuente`, `resumen`) y una tabla por campo: llenado en desarrollo · llenado en producción · quién lo lee · quién lo escribe · **veredicto propuesto** (retirar / empezar a llenar / dejar quieto) · **quién decide**.

- [ ] **Step 4: Validar el frontmatter y commitear**

```bash
npm run test:wiki
git add docs/superpowers/notas/2026-08-20-campos-muertos.md
git commit -m "docs(datos): veredicto de los cuatro campos sin uso"
```

---

## Task 6: Retiro de las tablas del PDC v1

**Contexto que el ejecutor necesita.** La tabla `pdc` guarda 409 filas que **son plantilla, no historial**: 273 sin una sola fecha, valor ni proveedor; 126 con fecha planeada y solo 4 con fecha real; las seis columnas de valor vacías en las 409. Sus hermanas —`general_informe_pdc`, `bi_pdc_general`, `papelera_pdc`, `backup_licify_general_informe_pdc_20260612`— repiten el mismo esqueleto. El código del PDC v1 se eliminó del repositorio el 2026-08-04, así que están vivas y huérfanas. Decisión D64 y D83.

> **Esta tarea NO se ejecuta sin el visto explícito de Felipe en el chat.** Borrar es lo único que no tiene vuelta atrás.

**Files:**
- Create: `docs/superpowers/notas/2026-08-20-retiro-pdc-v1.md` (evidencia y bitácora)

- [ ] **Step 1: Verificar que ningún informe vivo las lee** *(prerrequisito, D84)*

Comprobar con Felipe qué informes de Power BI siguen publicados y de qué tablas leen. **Si alguna hermana alimenta un informe vivo, esa tabla queda fuera del retiro** hasta que el informe se jubile en F5. Registrar la comprobación con su fecha y quién la hizo.

- [ ] **Step 2: Extraer el archivo histórico**

Dos piezas, y solo dos:
1. La estructura: `SHOW CREATE TABLE` de las cinco tablas.
2. El CSV de las **126 filas planeadas de la obra «Prueba»** (proyecto 27), que Felipe pidió conservar por ser el borrador de un plan real (D83).

Guardar fuera del repositorio —los datos son de obras de clientes— y anotar la ruta en la bitácora, no el contenido.

- [ ] **Step 3: Comprobar que el archivo se lee fuera de producción**

Cargar el CSV y la estructura en la base de desarrollo y confirmar que se abren y se cuentan. **Un archivo que nadie ha abierto no es un respaldo.**

- [ ] **Step 4: Respaldo verificable de producción**

Seguir `docs/siteground-deploy-routine.md` en su parte de respaldo, con marca de tiempo y manifiesto, y comprobar que el respaldo se puede restaurar.

- [ ] **Step 5: Pedir el visto y ejecutar el retiro**

Con el visto de Felipe en el chat, y no antes. Registrar en la bitácora: qué se retiró, cuándo, con qué respaldo, y el conteo de filas antes y después.

- [ ] **Step 6: Anotar el cierre**

Actualizar `docs/superpowers/notas/2026-08-20-retiro-pdc-v1.md` y commitear.

---

## Condición de hecho de F0

- `bi_cip_responsables` devuelve decenas de filas y la hoja de Responsables tiene qué mostrar.
- Ningún eje del radar se llama distinto de lo que mide.
- Las tres variantes de «Actividad predecesora incompleta» se distinguen en pantalla.
- El catálogo de causas no tiene textos con codificación rota.
- Los cuatro campos sin uso tienen veredicto escrito y dueño de la decisión.
- Las tablas del PDC v1 están retiradas, o hay constancia escrita de por qué no.
- `docker compose exec app php scripts/run-php-tests.php --nivel=puro` y `--nivel=db` en verde.
- `npm run test:wiki` sin hallazgos.

---

## Estado verificado — cerrado

Verificado contra el código el 2026-08-25. **`estado: cerrado` es una afirmación deliberada**, no el valor por defecto del backfill.

**Evidencia:** Tareas 1-5 con artefactos (test_cip_poblado.php, RadarAxisNamingTest.php, test_causa_atribucion.php, scripts/higiene/reparar-mojibake-causas.php, notas/2026-08-20-campos-muertos.md). La Task 6 no se ejecuto pero el plan admite esa salida en su linea 526 («o hay constancia escrita de por que no») y la constancia esta en notas/2026-08-20-retiro-pdc-v1.md:127-131 con decision de Felipe del 2026-08-24

Criterio y método: [[docs/superpowers/plans/2026-08-25-estado-real-de-planes-y-specs]].
