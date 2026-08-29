<?php
// tests/test_pdc_v2_torre_control_rbac.php — Punto 2 de la condición de hecho del spec B3:
// ninguna obra ve datos de contratación de otra sin permiso.
//
// No prueba código nuevo: prueba que la regla que ya existe (BiProjectScope) cubre también el
// informe de compras ahora que ese informe trae datos del PDC v2. Es la comprobación que separa
// esto de un incidente, y por eso se verifica con un rol permitido Y uno denegado.
declare(strict_types=1);
// @requiere: db


require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Support\BiProjectScope;

$failures = [];
$assert = static function (bool $c, string $m) use (&$failures): void {
    if ($c) {
        fwrite(STDOUT, "PASS: {$m}\n");
        return;
    }
    $failures[] = $m;
    fwrite(STDERR, "FAIL: {$m}\n");
};

$db = Database::getInstance();
$scope = new BiProjectScope($db);

// Un usuario real que tenga al menos una obra visible en la Torre. Se replican las mismas
// condiciones que authorizedProjects(): área, activo y acceso.
//
// El test necesita un par completo: un usuario con una obra propia Y al menos una obra ajena con
// la que probar la mitad denegada. Hasta el 2026-08-10 tomaba el primer candidato que devolviera
// `LIMIT 1` sin `ORDER BY` y daba por hecho que serviría; cuando le tocaba un usuario que las veía
// todas, no había obra ajena y el test fallaba. En lote pasaba una de cada cuatro veces, porque el
// estado de la base cambia qué fila sale primero.
//
// Ahora se recorren los candidatos en un orden fijo y se elige el primero que cumple las dos
// condiciones. Lo que el test comprueba no cambia: cambia cómo elige los datos con los que
// comprobarlo, y deja de depender de qué fila quiera devolver MySQL.
$candidatos = $db->query(
    "SELECT DISTINCT u.usuario, pm.project_id
     FROM project_members pm
     INNER JOIN general_usuarios u ON u.id = pm.user_id
     INNER JOIN general_proyectos_procesos p ON p.ID = pm.project_id
     WHERE p.Area IN ('Construccion', 'Pre-Construccion')
       AND p.Activo = 1
       AND (p.Acceso = 1 OR pm.role IN ('A', 'D', 'P'))
     ORDER BY u.usuario ASC, pm.project_id ASC",
)->fetchAll(\PDO::FETCH_ASSOC);

if ($candidatos === []) {
    fwrite(STDERR, "FAIL: no hay ningún usuario con obra visible en la Torre; sin eso no se puede probar nada.\n");
    exit(1);
}

$usuario     = null;
$permitido   = 0;
$autorizados = [];
$ajena       = 0;

foreach ($candidatos as $candidato) {
    $suSesion = [
        'usuario' => (string) $candidato['usuario'],
        'project_id' => (int) $candidato['project_id'],
    ];
    $suyas = array_map(
        static fn(array $p): int => (int) $p['project_id'],
        $scope->authorizedProjects($suSesion),
    );

    if (!in_array((int) $candidato['project_id'], $suyas, true)) {
        continue;
    }

    $lista = $suyas === [] ? '0' : implode(',', array_map('intval', $suyas));
    $obraAjena = (int) $db->query(
        "SELECT ID FROM general_proyectos_procesos WHERE ID NOT IN ({$lista}) ORDER BY ID ASC LIMIT 1",
    )->fetchColumn();

    if ($obraAjena <= 0) {
        continue;
    }

    $usuario     = (string) $candidato['usuario'];
    $permitido   = (int) $candidato['project_id'];
    $autorizados = $suyas;
    $ajena       = $obraAjena;
    break;
}

if ($usuario === null) {
    fwrite(STDERR, "FAIL: ningún usuario tiene a la vez una obra propia y una ajena; la mitad denegada no se pudo probar.\n");
    exit(1);
}

$session = ['usuario' => $usuario, 'project_id' => $permitido];

// --- Rol permitido ----------------------------------------------------------------------------
$assert(in_array($permitido, $autorizados, true), 'rol permitido: su obra está entre las autorizadas');

$resuelto = $scope->resolve([$permitido], $session);
$assert($resuelto === [$permitido], 'rol permitido: el usuario resuelve su propia obra sin error');

// --- Rol denegado -----------------------------------------------------------------------------
// $ajena ya quedó resuelta al elegir el candidato: es condición para haberlo elegido.
{
    $lanzo = false;
    try {
        $scope->resolve([$ajena], $session);
    } catch (\DomainException) {
        $lanzo = true;
    }
    $assert($lanzo, 'rol denegado: pedir una obra ajena lanza DomainException');

    // Y no se cuela mezclándola con una propia, que es como se escapan estas cosas.
    $lanzoMixto = false;
    try {
        $scope->resolve([$permitido, $ajena], $session);
    } catch (\DomainException) {
        $lanzoMixto = true;
    }
    $assert($lanzoMixto, 'rol denegado: una obra ajena mezclada con una propia también lanza');
}

// --- Sesión sin usuario -------------------------------------------------------------------------
$scopeLimpio = new BiProjectScope($db);
$sessionAnonima = ['usuario' => ''];
$assert($scopeLimpio->resolve([$permitido], $sessionAnonima) === [], 'sesión sin usuario: resolve conserva el adaptador vacío');
$lanzoScopeAnonimo = false;
try {
    $scopeLimpio->scope([$permitido], $sessionAnonima, 'test:test_pdc_v2_torre_control_rbac:anonimo');
} catch (\DomainException) {
    $lanzoScopeAnonimo = true;
}
$assert($lanzoScopeAnonimo, 'sesión sin usuario: scope rechaza el conjunto vacío');

// --- La caché no mezcla sesiones ---------------------------------------------------------------
// Fue un fallo real: authorizedProjects() memoizaba en una sola $this->projects, así que una
// instancia reutilizada le respondía al segundo usuario con los permisos del primero. Arreglado
// cacheando por usuario; este test es lo que impide que vuelva.
$scopeReusado = new BiProjectScope($db);
$scopeReusado->resolve([$permitido], $session);

$assert(
    $scopeReusado->resolve([$permitido], $sessionAnonima) === [],
    'la misma instancia, con una sesión anónima después de una válida, devuelve el adaptador vacío',
);
$lanzoScopeTrasReuso = false;
try {
    $scopeReusado->scope([$permitido], $sessionAnonima, 'test:test_pdc_v2_torre_control_rbac:reuso-anonimo');
} catch (\DomainException) {
    $lanzoScopeTrasReuso = true;
}
$assert($lanzoScopeTrasReuso, 'la misma instancia no convierte una sesión anónima en autoridad');

// Y el usuario legítimo sigue viendo lo suyo después de que otra sesión haya pasado por la
// instancia: la caché por usuario tiene que aislar en los dos sentidos, no solo denegar.
$assert(
    $scopeReusado->resolve([$permitido], $session) === [$permitido],
    'la misma instancia sigue resolviendo bien al usuario legítimo tras atender a otra sesión',
);

fwrite(STDOUT, $failures === [] ? "\nOK\n" : "\n" . count($failures) . " fallos\n");
exit($failures === [] ? 0 : 1);
