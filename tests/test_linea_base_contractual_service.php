<?php
// @requiere: db
require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\LineaBaseContractualService;

/**
 * Doble de prueba de \Database para el caso 4: simula que el UPDATE de sembrarSiFalta llega a
 * ejecutarse pero afecta cero filas, como pasaría si otra consolidación concurrente ya escribió la
 * línea base entre la guarda en PHP y el UPDATE. No abre conexión real (el constructor del padre no
 * se llama a propósito), así que no toca la base — puede correr aunque haya un congelamiento vigente
 * sobre `general_proyectos_procesos` o `programa_consolidado`.
 */
final class DatabaseFalsaConCarrera extends \Database
{
    private int $llamada = 0;

    public function __construct()
    {
        // Sin llamar a parent::__construct(): este doble nunca abre PDO ni toca la red.
    }

    public function query($sql, $params = [])
    {
        $this->llamada++;

        return match ($this->llamada) {
            // 1: declaradaDe() dentro de la guarda — nadie había declarado nada todavía.
            1 => new class {
                public function fetch($modo = null)
                {
                    return false;
                }
            },
            // 2: deducidaDelPrimerCorte() — MIN(Semana).
            2 => new class {
                public function fetchColumn()
                {
                    return 1;
                }
            },
            // 3: deducidaDelPrimerCorte() — MIN/MAX de fechas de esa semana.
            3 => new class {
                public function fetch($modo = null)
                {
                    return ['inicio' => '2020-01-01', 'fin' => '2020-01-07'];
                }
            },
            // 4: el UPDATE de sembrarSiFalta — llega tarde, otra escritura ya ganó la carrera.
            4 => new class {
                public function rowCount()
                {
                    return 0;
                }
            },
            default => throw new \RuntimeException('DatabaseFalsaConCarrera: llamada inesperada #' . $this->llamada),
        };
    }

}

$fallos = [];
$svc = new LineaBaseContractualService();
$db = \Database::getInstance();

// La base de desarrollo es COMPARTIDA con otras sesiones. Se guarda el estado del proyecto 68 y se
// restaura pase lo que pase — incluso si la prueba muere a mitad.
$original = $db->query(
    'SELECT fechaInicioLineaBase AS inicio, fechaFinLineaBase AS fin
       FROM general_proyectos_procesos WHERE Id = 68',
)->fetch(\PDO::FETCH_ASSOC) ?: ['inicio' => null, 'fin' => null];

register_shutdown_function(static function () use ($db, $original): void {
    $db->query(
        'UPDATE general_proyectos_procesos
            SET fechaInicioLineaBase = ?, fechaFinLineaBase = ? WHERE Id = 68',
        [$original['inicio'], $original['fin']],
    );
});

// El servicio consulta `programa_consolidado`, tabla de proyecto: ProjectSqlGuard exige un alcance
// declarado además del filtro por `project_id`. En una petición real lo enlaza SessionMiddleware
// desde la sesión; aquí el test opera sobre el proyecto 68 sin sesión, así que lo declara él. El
// alcance es del mismo proyecto que el test dice estar mirando: no ensancha nada.
$db->dataScope()->clear();
$db->dataScope()->bind(new \App\Security\DataScope\ProjectScope(68, 'test-linea-base', 'A'));

// 1. Un proyecto con línea base declarada la devuelve tal cual.
$db->query("UPDATE general_proyectos_procesos
            SET fechaInicioLineaBase = '2020-01-01', fechaFinLineaBase = '2020-12-31'
            WHERE Id = 68");
$lb = $svc->declaradaDe(68);
if (($lb['inicio'] ?? null) !== '2020-01-01' || ($lb['fin'] ?? null) !== '2020-12-31') {
    $fallos[] = 'declaradaDe no devuelve las fechas declaradas';
}

// 2. sembrarSiFalta NO sobrescribe una línea base existente.
if ($svc->sembrarSiFalta(68) !== false) {
    $fallos[] = 'sembrarSiFalta sobrescribió una línea base ya declarada';
}
$lb = $svc->declaradaDe(68);
if (($lb['inicio'] ?? null) !== '2020-01-01') {
    $fallos[] = 'sembrarSiFalta pisó la fecha declarada';
}

// 3. Sin línea base declarada, declaradaDe devuelve null y sembrarSiFalta escribe.
$db->query("UPDATE general_proyectos_procesos
            SET fechaInicioLineaBase = NULL, fechaFinLineaBase = NULL WHERE Id = 68");
if ($svc->declaradaDe(68) !== null) {
    $fallos[] = 'declaradaDe debería devolver null sin fechas declaradas';
}
$deducida = $svc->deducidaDelPrimerCorte(68);
if ($deducida === null) {
    $fallos[] = 'deducidaDelPrimerCorte no encontró el primer corte del proyecto 68';
}
if ($svc->sembrarSiFalta(68) !== true) {
    $fallos[] = 'sembrarSiFalta no escribió cuando faltaba la línea base';
}
if ($svc->declaradaDe(68) != $deducida) {
    $fallos[] = 'lo sembrado no coincide con lo deducido del primer corte';
}

// 4. Race concurrente simulada con un doble de \Database, sin tocar la base real: la guarda en PHP
// pasa (nadie había declarado todavía cuando se leyó), pero el UPDATE llega después de que otra
// consolidación concurrente ya escribió, así que afecta cero filas. sembrarSiFalta tiene que
// reportar false — "true solo si escribió", no "true si se intentó" — aunque la guarda haya dado
// luz verde. Esto no lo cubre el caso 2 de arriba: ahí la guarda ya detecta la declarada y ni
// siquiera llega a ejecutar el UPDATE; acá la guarda no detecta nada porque la lee de un mock, no
// hay forma de que lo haga, y lo único que decide el resultado es el rowCount() del UPDATE.
$svcConCarrera = new LineaBaseContractualService(new DatabaseFalsaConCarrera());
if ($svcConCarrera->sembrarSiFalta(999) !== false) {
    $fallos[] = 'sembrarSiFalta devolvió true aunque el UPDATE afectó cero filas (carrera perdida)';
}

if ($fallos) {
    foreach ($fallos as $f) { echo "FAIL: $f\n"; }
    exit(1);
}
echo "OK: linea base contractual — declarada, deducida y sembrado write-once\n";
