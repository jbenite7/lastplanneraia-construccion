<?php
// @requiere: db

// El 2026-08-29 el gate de scope (ProjectSqlGuard) empezo a rechazar cualquier consulta con una
// tabla calificada por schema, y `information_schema.tables` lo esta. RbacService preguntaba por
// ahi si existia `project_members`, se tragaba la excepcion en su catch y concluia que la tabla no
// existe: la resolucion de rol devolvia null y `normalizeRole(null)` la convertia en
// RbacCatalog::DEFAULT_ROLE, que es 'C'. Resultado medido: un administrador entraba al laboratorio
// interno con rol de subcontratista y recibia 403, con 24 pruebas del CI en rojo.
//
// La prueba mide el mecanismo, no la semilla: busca en la base un usuario que de verdad tenga una
// membresia 'A' y comprueba que la resolucion se la devuelve. Si no hay ninguno, no hay nada que
// medir y se salta — fallar ahi seria reportar un bug de codigo cuando lo que falta son datos.

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Security\DesignSystemLabAccessPolicy;
use App\Security\RbacService;

$failures = [];

function expectSame($expected, $actual, string $message): void
{
    global $failures;
    if ($expected !== $actual) {
        $failures[] = $message . ': esperaba ' . var_export($expected, true)
            . ', obtuvo ' . var_export($actual, true);
    }
}

$db = Database::getInstance();

// 1. Los metadatos se leen por la puerta de Database, que no pasa por el gate de scope.
expectSame(true, $db->tableExists('project_members'), 'project_members debe existir para Database');
expectSame(false, $db->tableExists('tabla_que_no_existe_jamas'), 'una tabla inexistente da false');
expectSame(true, $db->columnExists('project_members', 'role'), 'project_members.role debe existir');
expectSame(
    false,
    $db->columnExists('project_members', 'columna_que_no_existe_jamas'),
    'una columna inexistente da false',
);

// 2. La resolucion de rol llega hasta las membresias en vez de caer al rol por defecto.
$pdoReflection = new ReflectionClass($db);
$pdoProperty = $pdoReflection->getProperty('pdo');
$pdoProperty->setAccessible(true);
$pdo = $pdoProperty->getValue($db);

$statement = $pdo->prepare(
    'SELECT u.usuario
       FROM project_members pm
 INNER JOIN general_usuarios u ON u.id = pm.user_id
      WHERE pm.role = ?
   ORDER BY u.usuario ASC
      LIMIT 1'
);
$statement->execute(['A']);
$administrator = $statement->fetchColumn();

if ($administrator === false) {
    echo "RBAC metadatos sin gate: SKIP (no hay ninguna membresia 'A' en la base)\n";
    exit(0);
}

$service = new RbacService();
expectSame(
    'A',
    $service->resolveRoleForUser((string) $administrator),
    "el rol global de {$administrator} sale de sus membresias, no de DEFAULT_ROLE",
);

// 3. Y por eso el laboratorio interno vuelve a abrirle la puerta al administrador.
expectSame(
    200,
    DesignSystemLabAccessPolicy::status(['usuario' => $administrator], 'testing'),
    'el laboratorio interno responde 200 a un administrador identificado por su usuario',
);

// 4. La denegacion sigue en pie para quien no es administrador: el arreglo no ablanda la politica.
$statement = $pdo->prepare(
    'SELECT u.usuario
       FROM general_usuarios u
      WHERE NOT EXISTS (
            SELECT 1 FROM project_members pm WHERE pm.user_id = u.id AND pm.role = ?
      )
   ORDER BY u.usuario ASC
      LIMIT 1'
);
$statement->execute(['A']);
$nonAdministrator = $statement->fetchColumn();

if ($nonAdministrator !== false) {
    expectSame(
        403,
        DesignSystemLabAccessPolicy::status(['usuario' => $nonAdministrator], 'testing'),
        "el laboratorio interno le sigue negando la entrada a {$nonAdministrator}",
    );
}

if ($failures !== []) {
    foreach ($failures as $failure) {
        fwrite(STDERR, "FAIL: {$failure}\n");
    }
    exit(1);
}

echo "RBAC metadatos sin gate: PASS\n";
