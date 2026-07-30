<?php
// tests/test_pdc_v2_torre_control_rbac.php — Punto 2 de la condición de hecho del spec B3:
// ninguna obra ve datos de contratación de otra sin permiso.
//
// No prueba código nuevo: prueba que la regla que ya existe (BiProjectScope) cubre también el
// informe de compras ahora que ese informe trae datos del PDC v2. Es la comprobación que separa
// esto de un incidente, y por eso se verifica con un rol permitido Y uno denegado.
declare(strict_types=1);

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
$fila = $db->query(
    "SELECT u.usuario, pm.project_id, pm.role
     FROM project_members pm
     INNER JOIN general_usuarios u ON u.id = pm.user_id
     INNER JOIN general_proyectos_procesos p ON p.ID = pm.project_id
     WHERE p.Area IN ('Construccion', 'Pre-Construccion')
       AND p.Activo = 1
       AND (p.Acceso = 1 OR pm.role IN ('A', 'D', 'P'))
     LIMIT 1",
)->fetch(\PDO::FETCH_ASSOC);

if (!$fila) {
    fwrite(STDERR, "FAIL: no hay ningún usuario con obra visible en la Torre; sin eso no se puede probar nada.\n");
    exit(1);
}

$usuario   = (string) $fila['usuario'];
$permitido = (int) $fila['project_id'];
$session   = ['usuario' => $usuario, 'project_id' => $permitido];

// --- Rol permitido ----------------------------------------------------------------------------
$autorizados = array_map(
    static fn(array $p): int => (int) $p['project_id'],
    $scope->authorizedProjects($session),
);
$assert(in_array($permitido, $autorizados, true), 'rol permitido: su obra está entre las autorizadas');

$resuelto = $scope->resolve([$permitido], $session);
$assert($resuelto === [$permitido], 'rol permitido: el usuario resuelve su propia obra sin error');

// --- Rol denegado -----------------------------------------------------------------------------
$ph = $autorizados === [] ? '0' : implode(',', array_map('intval', $autorizados));
$ajena = (int) $db->query(
    "SELECT ID FROM general_proyectos_procesos WHERE ID NOT IN ({$ph}) LIMIT 1",
)->fetchColumn();

if ($ajena <= 0) {
    fwrite(STDERR, "FAIL: no hay ninguna obra ajena a este usuario; la mitad denegada no se pudo probar.\n");
    $failures[] = 'no se pudo probar el caso denegado';
} else {
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
// OJO: con una instancia NUEVA, que es como funciona en producción (una por petición).
// BiProjectScope memoiza $this->projects sin tener en cuenta la sesión, así que reusar la misma
// instancia con dos sesiones distintas devuelve los permisos de la primera. No es explotable por
// HTTP —una petición es una sesión—, pero sí lo sería en un proceso que recorra varios usuarios.
// Está reportado aparte; este test fija el contrato real y no lo tapa.
$scopeLimpio = new BiProjectScope($db);
$lanzoAnonimo = false;
try {
    $scopeLimpio->resolve([$permitido], ['usuario' => '']);
} catch (\DomainException) {
    $lanzoAnonimo = true;
}
$assert($lanzoAnonimo, 'sesión sin usuario: no resuelve ninguna obra');

// Y se deja constancia ejecutable del matiz, para que si alguien arregla la caché este test
// se lo diga en vez de quedarse callado.
$scopeSucio = new BiProjectScope($db);
$scopeSucio->resolve([$permitido], $session);
$cacheFiltra = false;
try {
    $scopeSucio->resolve([$permitido], ['usuario' => '']);
    $cacheFiltra = true;
} catch (\DomainException) {
    $cacheFiltra = false;
}
$assert(
    $cacheFiltra,
    'DOCUMENTADO (no deseable): la caché por instancia ignora la sesión. Si esto falla, es que se arregló: quita este bloque',
);

fwrite(STDOUT, $failures === [] ? "\nOK\n" : "\n" . count($failures) . " fallos\n");
exit($failures === [] ? 0 : 1);
