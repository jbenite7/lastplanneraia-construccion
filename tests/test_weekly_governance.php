<?php
// @requiere: datos-proyecto


require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/../src/Core/TableResolver.php';
require_once __DIR__ . '/../src/Services/ProgramChangeDetector.php';

$db = Database::getInstance();
$detector = new App\Services\ProgramChangeDetector();

// Resolve table names via TableResolver so tests work in BOTH flag modes
$tPS  = TableResolver::resolveByPrefix('optimizacionJMC', 'programacion_semanal');
$tPC  = TableResolver::resolveByPrefix('optimizacionJMC', 'programa_consolidado');
$tP   = TableResolver::resolveByPrefix('optimizacionJMC', 'programa');
$tLog = TableResolver::resolveByPrefix('optimizacionJMC', 'auto_program_log');
$tWeeks = TableResolver::resolveByPrefix('optimizacionJMC', 'semanas_activas');
$pid  = 68; // optimizacionJMC project ID

/**
 * Query helper — delegates to queryWithProject for project-scoped operations.
 */
function qp(string $sql, array $params = []): \PDOStatement {
    global $db, $pid;
    return $db->queryWithProject($sql, $params, $pid);
}

function cleanupWeeklyGovernanceFixture(): void
{
    global $db, $pid, $tPS, $tPC, $tP, $tLog, $tWeeks;
    $db->queryWithProject("DELETE FROM {$tLog} WHERE semana = ?", [9992], $pid);
    $db->queryWithProject("DELETE FROM {$tPS} WHERE Consecutivo_En_Programa = ? AND Semana = ?", [9900005, 9992], $pid);
    $db->queryWithProject("DELETE FROM {$tPC} WHERE Id = ? AND Semana = ?", ['2.4', 9992], $pid);
    $db->queryWithProject("DELETE FROM {$tP} WHERE Consecutivo = ?", [9900005], $pid);
    $db->queryWithProject("DELETE FROM {$tWeeks} WHERE Semana = ?", [9992], $pid);
}

function assertWeeklyGovernanceReservationVacant(): void
{
    global $db, $pid, $tPS, $tPC, $tP, $tLog, $tWeeks;
    $checks = [
        ["SELECT COUNT(*) FROM {$tLog} WHERE project_id = ? AND semana = ?", [$pid, 9992]],
        ["SELECT COUNT(*) FROM {$tPS} WHERE project_id = ? AND Semana = ?", [$pid, 9992]],
        ["SELECT COUNT(*) FROM {$tPC} WHERE project_id = ? AND Semana = ?", [$pid, 9992]],
        ["SELECT COUNT(*) FROM {$tP} WHERE project_id = ? AND Consecutivo = ?", [$pid, 9900005]],
        ["SELECT COUNT(*) FROM {$tWeeks} WHERE project_id = ? AND Semana = ?", [$pid, 9992]],
    ];
    foreach ($checks as [$sql, $params]) {
        if ((int) $db->query($sql, $params)->fetchColumn() !== 0) {
            throw new RuntimeException('La reserva sintetica de gobernanza no esta vacia.');
        }
    }
}

echo "=== INICIANDO PRUEBAS DE INTEGRACIÓN: CASOS DE GOBERNANZA ===\n\n";

// --- PREPARACIÓN INICIAL ---
// Set project context so all $db->query() calls auto-scope via queryWithProject
$db->setProjectContext($pid);
assertWeeklyGovernanceReservationVacant();
register_shutdown_function('cleanupWeeklyGovernanceFixture');

$sql = "INSERT INTO {$tWeeks} (Id, Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem) VALUES (?, ?, ?, ?)";
$params = [9992, 9992, '2099-01-05', '2099-01-11'];
[$sql, $params] = $db->insertProjectId($sql, $pid, $params);
$db->query($sql, $params);

// La fila semanal y el consolidado comparten la llave tecnica del programa padre.
$sql = "INSERT INTO {$tP} (Consecutivo, unique_id, Id, Actividad, Titulo) VALUES (?, ?, ?, ?, ?)";
$params = [9900005, 9900005, '2.4', 'Actas de vecindad frentes de obra iniciales', 0];
[$sql, $params] = $db->insertProjectId($sql, $pid, $params);
$db->query($sql, $params);

// Insertar la actividad base en la programación semanal de la Semana sintetica 9992
$pid = 68; // optimizacionJMC project ID
$sql = "INSERT INTO {$tPS} (
    Consecutivo, Semana, Consecutivo_En_Programa, Id, Actividad, Activa, Empresa, Ejecutado
) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$params = [9900005, 9992, 9900005, '2.4', 'Actas de vecindad frentes de obra iniciales', '0', 'AIA', 0.5];
[$sql, $params] = $db->insertProjectId($sql, $pid, $params);
$db->query($sql, $params);

// Insertar la actividad base en el Programa Consolidado (necesario para que el cascade la vea)
$sql = "INSERT INTO {$tPC} (
    Consecutivo, Consecutivo_en_Programa, Id, Semana, Titulo, Estado, D_y_E, Materiales, MdeO, Equipos, Predecesora, Ejecutado, Activa
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$params = [9900005, 9900005, '2.4', 9992, 0, 'Atrasada', '1', '1', '1', '1', '0', 0.0, 1];
[$sql, $params] = $db->insertProjectId($sql, $pid, $params);
$db->query($sql, $params);

// ==========================================
// CASO 1: Reactivación Automática
// Activa = 0 con CNP genérica de restricciones -> Liberar restricciones en consolidado -> Debe pasar a Activa = 1 y limpiar CNP.
// ==========================================
echo "--- CASO 1: Probando Reactivación Automática ---\n";

// 1. Configurar CNP genérica de restricciones en la programación semanal
qp("UPDATE {$tPS}
            SET Activa = '0', Categoria_CNP = 'Programación', CNP = 'Restricciones habilitantes no cumplidas', Ejecutado = 0.0, Reprogramada_Por_Usuario = 0
            WHERE Consecutivo_En_Programa = 9900005 AND Semana = 9992");

// 2. Liberar todas las restricciones duras (al 100%) en el Programa Consolidado
qp("UPDATE {$tPC}
            SET D_y_E = '1', Materiales = '1', MdeO = '1', Equipos = '1', Predecesora = '1', Ejecutado = 0.0
            WHERE Id = '2.4' AND Semana = 9992");

// 3. Correr el detector de cambios
$log1 = $detector->run('optimizacionJMC', 9992);
echo "Log del detector (Caso 1):\n";
print_r($log1);

// 4. Validar el estado resultante en la base de datos
$record1 = qp("SELECT Activa, Categoria_CNP, CNP, Reprogramada_Por_Usuario FROM {$tPS} WHERE Consecutivo_En_Programa = 9900005 AND Semana = 9992")->fetch();

$c1_activa_ok = ($record1['Activa'] === '1');
$c1_cnp_ok = ($record1['CNP'] === null || $record1['CNP'] === '');
$c1_flag_ok = ((int) $record1['Reprogramada_Por_Usuario'] === 0);

echo "Resultado Caso 1 en DB:\n";
print_r($record1);

if ($c1_activa_ok && $c1_cnp_ok && $c1_flag_ok) {
    echo "✅ CASO 1 EXITOSO: La actividad fue reactivada automáticamente a Activa=1, se limpió la causa genérica y el flag se reseteó a 0.\n\n";
} else {
    echo "❌ CASO 1 FALLIDO: La actividad no fue reactivada correctamente.\n\n";
    exit(1);
}

// ==========================================
// CASO 2: Soberanía de Desprogramación
// Activa = 0 con CNP manual propia -> Mantener restricciones liberadas (OK) -> Debe continuar Activa = 0 con CNP propia intacta.
// ==========================================
echo "--- CASO 2: Probando Soberanía de Desprogramación ---\n";

// 1. Configurar una CNP manual propia en la programación semanal
qp("UPDATE {$tPS}
            SET Activa = '0', Categoria_CNP = 'Mano de Obra', CNP = 'Causa Manual del Usuario', Ejecutado = 0.0, Reprogramada_Por_Usuario = 0
            WHERE Consecutivo_En_Programa = 9900005 AND Semana = 9992");

// 2. Correr el detector de cambios
$log2 = $detector->run('optimizacionJMC', 9992);
echo "Log del detector (Caso 2):\n";
print_r($log2);

// 3. Validar el estado resultante en la base de datos
$record2 = qp("SELECT Activa, Categoria_CNP, CNP FROM {$tPS} WHERE Consecutivo_En_Programa = 9900005 AND Semana = 9992")->fetch();

$c2_activa_ok = ($record2['Activa'] === '0');
$c2_cnp_ok = ($record2['CNP'] === 'Causa Manual del Usuario');

echo "Resultado Caso 2 en DB:\n";
print_r($record2);

if ($c2_activa_ok && $c2_cnp_ok) {
    echo "✅ CASO 2 EXITOSO: El detector respetó la soberanía del usuario (Activa=0 y la causa CNP manual intactas).\n\n";
} else {
    echo "❌ CASO 2 FALLIDO: El detector modificó o reactivó indebidamente la actividad del usuario.\n\n";
    exit(1);
}

// ==========================================
// CASO 3: Reprogramación Manual e Inmunidad ante Saneamiento del Botón Autoprogramar
// Activa = 0 con CNP genérica -> Simular reprogramación del usuario (Activa = 1, limpia CNP, flag=1)
// -> Romper restricciones en consolidado (NO OK) -> Simular el DELETE de limpieza física de Autoprogramar
// -> Correr detector -> Debe continuar existiendo, con Activa = 1 y no ser inhabilitada.
// ==========================================
echo "--- CASO 3: Probando Reprogramación Manual e Inmunidad ante Saneamiento ---\n";

// 1. Configurar inicialmente como inactiva por restricciones genéricas
qp("UPDATE {$tPS}
            SET Activa = '0', Categoria_CNP = 'Programación', CNP = 'Restricciones habilitantes no cumplidas', Ejecutado = 0.0, Reprogramada_Por_Usuario = 0
            WHERE Consecutivo_En_Programa = 9900005 AND Semana = 9992");

// 2. Simular reprogramación manual del usuario desde el módulo de CNP (Activa = 1, flag = 1)
qp("UPDATE {$tPS}
            SET Activa = '1', Categoria_CNP = NULL, CNP = NULL, Observaciones_CNP = NULL, Reprogramada_Por_Usuario = 1
            WHERE Consecutivo_En_Programa = 9900005 AND Semana = 9992");

// 3. Romper las restricciones duras en el Programa Consolidado (Predecesora = 0 y Ejecutado = 0)
qp("UPDATE {$tPC}
            SET D_y_E = '1', Materiales = '1', MdeO = '1', Equipos = '1', Predecesora = '0', Ejecutado = 0.0, Estado = 'Atrasada'
            WHERE Id = '2.4' AND Semana = 9992");

// 4. Simular la limpieza física del botón "Autoprogramar" en SemanalApiController (con la nueva consulta corregida)
$eligibleSubSql = "SELECT unique_id FROM {$tPC}
    WHERE project_id = ? AND Semana = 9992 AND Titulo = 0
      AND (Estado='En Curso' OR Estado='Atrasada' OR Estado='Debe Iniciar'
        OR Estado='A Tiempo' OR Estado='Ya Debió Iniciar y Restricciones Pendientes')";

qp("
    DELETE FROM {$tPS}
    WHERE project_id = ? AND Semana = 9992 AND Activa = '1'
      AND (Ejecutado_Real IS NULL OR Ejecutado_Real <= 0)
      AND (Compromiso IS NULL OR Compromiso <= 0)
      AND unique_id NOT IN ({$eligibleSubSql})
", [$pid, $pid]);
// 5. Validar que la actividad NO haya sido borrada físicamente
$existeFisico = (int) qp("SELECT COUNT(*) FROM {$tPS} WHERE Consecutivo_En_Programa = 9900005 AND Semana = 9992")->fetchColumn();
if ($existeFisico > 0) {
    echo "✅ Sub-verificación: La actividad reprogramada sobrevivió a la limpieza física del botón Autoprogramar.\n";
} else {
    echo "❌ Sub-verificación FALLIDA: La actividad fue eliminada físicamente por la limpieza de Autoprogramar.\n";
    exit(1);
}

// 6. Correr el detector de cambios
$log3 = $detector->run('optimizacionJMC', 9992);
echo "Log del detector (Caso 3):\n";
print_r($log3);

// 7. Validar el estado resultante en la base de datos
$record3 = qp("SELECT Activa, Categoria_CNP, CNP, Reprogramada_Por_Usuario FROM {$tPS} WHERE Consecutivo_En_Programa = 9900005 AND Semana = 9992")->fetch();

$c3_activa_ok = ($record3['Activa'] === '1');
$c3_cnp_ok = ($record3['CNP'] === null || $record3['CNP'] === '');
$c3_flag_ok = ((int) $record3['Reprogramada_Por_Usuario'] === 1);

echo "Resultado Caso 3 en DB:\n";
print_r($record3);

if ($c3_activa_ok && $c3_cnp_ok && $c3_flag_ok) {
    echo "✅ CASO 3 EXITOSO: Se garantizó la inmunidad ante el saneamiento. La actividad reprogramada manualmente (flag=1) sobrevivió a la limpieza del botón Autoprogramar y se mantuvo Activa=1 a pesar de tener restricciones pendientes.\n\n";
} else {
    echo "❌ CASO 3 FALLIDO: El detector de cambios desactivó o borró la actividad reprogramada voluntariamente.\n\n";
    exit(1);
}

// ==========================================
// CASO 4: Inmunidad de Actividades Futuras Reprogramadas
// Activa = 0 con CNP genérica -> Simular reprogramación del usuario (Activa = 1, limpia CNP, flag=1)
// -> Configurar estado consolidado como 'En Liberación de Restricciones' (actividad futura) con restricciones rotas
// -> Simular el DELETE de limpieza física de Autoprogramar (excluyendo terminadas y sin datos)
// -> Correr detector -> Debe continuar existiendo, con Activa = 1 y no ser desprogramada.
// ==========================================
echo "--- CASO 4: Probando Inmunidad de Actividades Futuras Reprogramadas ---\n";

// 1. Configurar inicialmente como inactiva por restricciones genéricas
qp("UPDATE {$tPS}
            SET Activa = '0', Categoria_CNP = 'Programación', CNP = 'Restricciones habilitantes no cumplidas', Ejecutado = 0.0, Reprogramada_Por_Usuario = 0
            WHERE Consecutivo_En_Programa = 9900005 AND Semana = 9992");

// 2. Simular reprogramación manual del usuario desde el módulo de CNP (Activa = 1, flag = 1)
qp("UPDATE {$tPS}
            SET Activa = '1', Categoria_CNP = NULL, CNP = NULL, Observaciones_CNP = NULL, Reprogramada_Por_Usuario = 1
            WHERE Consecutivo_En_Programa = 9900005 AND Semana = 9992");

// 3. Establecer estado en consolidado como 'En Liberación de Restricciones' y restricciones pendientes (Predecesora = 0, Ejecutado = 0.0)
qp("UPDATE {$tPC}
            SET D_y_E = '1', Materiales = '1', MdeO = '1', Equipos = '1', Predecesora = '0', Ejecutado = 0.0, Estado = 'En Liberación de Restricciones'
            WHERE Id = '2.4' AND Semana = 9992");

// 4. Simular la limpieza física de Autoprogramar con la nueva consulta (Estado NOT IN ('Terminada', 'Terminada Antes', 'Sin Datos'))
$eligibleSubSql = "SELECT unique_id FROM {$tPC}
    WHERE project_id = ? AND Semana = 9992 AND Titulo = 0
      AND Estado NOT IN ('Terminada', 'Terminada Antes', 'Sin Datos')";

qp("
    DELETE FROM {$tPS}
    WHERE project_id = ? AND Semana = 9992 AND Activa = '1'
      AND (Ejecutado_Real IS NULL OR Ejecutado_Real <= 0)
      AND (Compromiso IS NULL OR Compromiso <= 0)
      AND unique_id NOT IN ({$eligibleSubSql})
", [$pid, $pid]);

// 5. Validar que la actividad futura reprogramada NO haya sido borrada físicamente
$existeFisico4 = (int) qp("SELECT COUNT(*) FROM {$tPS} WHERE Consecutivo_En_Programa = 9900005 AND Semana = 9992")->fetchColumn();
if ($existeFisico4 > 0) {
    echo "✅ Sub-verificación: La actividad futura reprogramada sobrevivió a la limpieza física.\n";
} else {
    echo "❌ Sub-verificación FALLIDA: La actividad futura fue eliminada físicamente de la base de datos.\n";
    exit(1);
}

// 6. Correr el detector de cambios
$log4 = $detector->run('optimizacionJMC', 9992);
echo "Log del detector (Caso 4):\n";
print_r($log4);

// 7. Validar el estado resultante en la base de datos (debe seguir Activa = 1, flag=1, limpia)
$record4 = qp("SELECT Activa, Categoria_CNP, CNP, Reprogramada_Por_Usuario FROM {$tPS} WHERE Consecutivo_En_Programa = 9900005 AND Semana = 9992")->fetch();

$c4_activa_ok = ($record4['Activa'] === '1');
$c4_cnp_ok = ($record4['CNP'] === null || $record4['CNP'] === '');
$c4_flag_ok = ((int) $record4['Reprogramada_Por_Usuario'] === 1);

echo "Resultado Caso 4 en DB:\n";
print_r($record4);

if ($c4_activa_ok && $c4_cnp_ok && $c4_flag_ok) {
    echo "✅ CASO 4 EXITOSO: Actividad futura reprogramada voluntariamente (flag=1) inmunizada con éxito.\n\n";
} else {
    echo "❌ CASO 4 FALLIDO: La actividad futura fue desprogramada automáticamente.\n\n";
    exit(1);
}

// ==========================================
// CASO 5: Aislamiento de Logs por Corrida Única (Lotes Independientes)
// 1. Limpiar logs anteriores
// 2. Correr detector con cambios (Caso 1) -> getLog() debe retornar los cambios
// 3. Correr detector sin cambios adicionales -> getLog() debe retornar array vacío
// ==========================================
echo "--- CASO 5: Probando Aislamiento de Logs por Corrida Única ---\n";

// 1. Limpiar logs anteriores
qp("DELETE FROM {$tLog} WHERE semana = 9992");

// 2. Configurar inicialmente como inactiva por restricciones genéricas
qp("UPDATE {$tPS}
            SET Activa = '0', Categoria_CNP = 'Programación', CNP = 'Restricciones habilitantes no cumplidas', Ejecutado = 0.0, Reprogramada_Por_Usuario = 0
            WHERE Consecutivo_En_Programa = 9900005 AND Semana = 9992");

// 3. Liberar todas las restricciones duras (al 100%) en el consolidado para forzar reactivación
qp("UPDATE {$tPC}
            SET D_y_E = '1', Materiales = '1', MdeO = '1', Equipos = '1', Predecesora = '1', Ejecutado = 0.0, Estado = 'Debe Iniciar'
            WHERE Id = '2.4' AND Semana = 9992");

// 4. Correr la primera ejecución (que realiza cambios)
$log5_1 = $detector->run('optimizacionJMC', 9992);
$logs_consultados_1 = $detector->getLog('optimizacionJMC', 9992);

echo "Corrida 1 - Cambios devueltos por run():\n";
print_r($log5_1);
echo "Corrida 1 - Cambios obtenidos de getLog():\n";
print_r($logs_consultados_1);

// Filtrar por consecutivo 5 (la actividad controlada del test) para evitar ruido de datos residuales
$logs_filtrados_1 = array_values(array_filter($logs_consultados_1, fn($e) => (int) ($e['consecutivo'] ?? 0) === 9900005));
$has_changes_1 = count($logs_filtrados_1) > 0;

// 5. Correr una segunda ejecución de inmediato (sin cambios nuevos en la actividad 5)
sleep(1); // Forzar cambio de segundo en el timestamp del lote
$log5_2 = $detector->run('optimizacionJMC', 9992);
$logs_consultados_2 = $detector->getLog('optimizacionJMC', 9992);

echo "Corrida 2 - Cambios devueltos por run():\n";
print_r($log5_2);
echo "Corrida 2 - Cambios obtenidos de getLog():\n";
print_r($logs_consultados_2);

$logs_filtrados_2 = array_values(array_filter($logs_consultados_2, fn($e) => (int) ($e['consecutivo'] ?? 0) === 9900005));
$has_changes_2 = count($logs_filtrados_2) > 0;

if ($has_changes_1 && !$has_changes_2) {
    echo "✅ CASO 5 EXITOSO: Los logs aíslan correctamente los cambios de la actividad 5 en cada corrida (Corrida 1 con cambios, Corrida 2 sin cambios sobre la actividad controlada).\n\n";
} else {
    echo "❌ CASO 5 FALLIDO: No se aisló correctamente el log de la última corrida para la actividad controlada.\n\n";
    exit(1);
}

// ==========================================
// CASO 6: Autodescompromiso por restricciones rotas (Caso A)
// Activa = 1 sin compromiso, flag = 0, restricciones pendientes -> El cascade la descompromete con CNP genérica
// ==========================================
echo "--- CASO 6: Probando Autodescompromiso por Restricciones Rotas ---\n";

// 1. Limpiar logs anteriores
qp("DELETE FROM {$tLog} WHERE semana = 9992");

// 2. Estado: actividad autoprogramada (Activa=1, flag=0) con restricciones rotas en consolidado
qp("UPDATE {$tPS}
            SET Activa = '1', Categoria_CNP = NULL, CNP = NULL, Observaciones_CNP = NULL, Reprogramada_Por_Usuario = 0, Ejecutado = 0.0
            WHERE Consecutivo_En_Programa = 9900005 AND Semana = 9992");

qp("UPDATE {$tPC}
            SET D_y_E = '1', Materiales = '1', MdeO = '1', Equipos = '1', Predecesora = '0', Ejecutado = 0.0, Estado = 'Atrasada'
            WHERE Id = '2.4' AND Semana = 9992");

// 3. Correr el detector
$log6 = $detector->run('optimizacionJMC', 9992);
echo "Log del detector (Caso 6):\n";
print_r($log6);

// 4. Validar el estado resultante
$record6 = qp("SELECT Activa, Categoria_CNP, CNP, Reprogramada_Por_Usuario FROM {$tPS} WHERE Consecutivo_En_Programa = 9900005 AND Semana = 9992")->fetch();

$c6_activa_ok = ($record6['Activa'] === '0');
$c6_categoria_ok = ($record6['Categoria_CNP'] === 'Programación');
$c6_cnp_ok = ($record6['CNP'] === 'Restricciones habilitantes no cumplidas');
$c6_flag_ok = ((int) $record6['Reprogramada_Por_Usuario'] === 0);

// 5. Validar que el log retornado incluya la acción descomprometer con categoria_cnp
$log6_tiene_restriccion = false;
foreach ($log6 as $entry) {
    if ($entry['accion'] === 'descomprometer'
        && ($entry['categoria_cnp'] ?? null) === 'Programación'
        && ($entry['cnp'] ?? null) === 'Restricciones habilitantes no cumplidas') {
        $log6_tiene_restriccion = true;
        break;
    }
}

echo "Resultado Caso 6 en DB:\n";
print_r($record6);

if ($c6_activa_ok && $c6_categoria_ok && $c6_cnp_ok && $c6_flag_ok && $log6_tiene_restriccion) {
    echo "✅ CASO 6 EXITOSO: Actividad autoprogramada con restricciones rotas fue autodescomprometida con CNP genérica y flag reseteado a 0. El log incluye la acción con categoria_cnp=Programación.\n\n";
} else {
    echo "❌ CASO 6 FALLIDO: La actividad no fue autodescomprometida correctamente.\n\n";
    exit(1);
}

// ==========================================
// CASO 7: Inmunidad del usuario reprogramador
// Activa = 1 sin compromiso, flag = 1 (reprogra manual), restricciones pendientes -> El cascade la mantiene Activa = 1
// ==========================================
echo "--- CASO 7: Probando Inmunidad por Reprogramación Manual del Usuario ---\n";

// 1. Estado: actividad autodescomprometida con CNP genérica, luego usuario la reprogra desde CNP
qp("UPDATE {$tPS}
            SET Activa = '1', Categoria_CNP = NULL, CNP = NULL, Observaciones_CNP = NULL, Reprogramada_Por_Usuario = 1, Ejecutado = 0.0
            WHERE Consecutivo_En_Programa = 9900005 AND Semana = 9992");

qp("UPDATE {$tPC}
            SET D_y_E = '1', Materiales = '1', MdeO = '1', Equipos = '1', Predecesora = '0', Ejecutado = 0.0, Estado = 'Atrasada'
            WHERE Id = '2.4' AND Semana = 9992");

// 2. Correr el detector
$log7 = $detector->run('optimizacionJMC', 9992);
echo "Log del detector (Caso 7):\n";
print_r($log7);

// 3. Validar el estado resultante
$record7 = qp("SELECT Activa, Categoria_CNP, CNP, Reprogramada_Por_Usuario FROM {$tPS} WHERE Consecutivo_En_Programa = 9900005 AND Semana = 9992")->fetch();

$c7_activa_ok = ($record7['Activa'] === '1');
$c7_cnp_ok = ($record7['CNP'] === null || $record7['CNP'] === '');
$c7_flag_ok = ((int) $record7['Reprogramada_Por_Usuario'] === 1);

// 4. Validar que el log NO incluya descomprometer para el consecutivo 5 (porque tiene flag=1)
$log7_sin_descompromiso = true;
foreach ($log7 as $entry) {
    if ((int) ($entry['consecutivo'] ?? 0) === 9900005 && $entry['accion'] === 'descomprometer') {
        $log7_sin_descompromiso = false;
        break;
    }
}

echo "Resultado Caso 7 en DB:\n";
print_r($record7);

if ($c7_activa_ok && $c7_cnp_ok && $c7_flag_ok && $log7_sin_descompromiso) {
    echo "✅ CASO 7 EXITOSO: La actividad con flag=1 (reprograda por usuario) se mantuvo Activa=1 a pesar de las restricciones pendientes. El log no incluye descompromiso.\n\n";
} else {
    echo "❌ CASO 7 FALLIDO: La actividad no mantuvo la inmunidad del usuario.\n\n";
    exit(1);
}

// ==========================================
// CASO 8: Reset del flag al reactivar (cascade limpia flag al comprometer)
// Activa = 0 con CNP genérica, flag = 1, restricciones OK -> El cascade la reactiva, limpia CNP y resetea flag a 0
// ==========================================
echo "--- CASO 8: Probando Reset del Flag al Reactivar ---\n";

// 1. Estado: actividad desprogramada con CNP genérica, flag = 1 (estado anómalo de prueba), restricciones OK
qp("UPDATE {$tPS}
            SET Activa = '0', Categoria_CNP = 'Programación', CNP = 'Restricciones habilitantes no cumplidas', Ejecutado = 0.0, Reprogramada_Por_Usuario = 1
            WHERE Consecutivo_En_Programa = 9900005 AND Semana = 9992");

qp("UPDATE {$tPC}
            SET D_y_E = '1', Materiales = '1', MdeO = '1', Equipos = '1', Predecesora = '1', Ejecutado = 0.0, Estado = 'Debe Iniciar'
            WHERE Id = '2.4' AND Semana = 9992");

// 2. Correr el detector
$log8 = $detector->run('optimizacionJMC', 9992);
echo "Log del detector (Caso 8):\n";
print_r($log8);

// 3. Validar el estado resultante: Activa=1, CNP NULL, flag=0
$record8 = qp("SELECT Activa, Categoria_CNP, CNP, Reprogramada_Por_Usuario FROM {$tPS} WHERE Consecutivo_En_Programa = 9900005 AND Semana = 9992")->fetch();

$c8_activa_ok = ($record8['Activa'] === '1');
$c8_cnp_ok = ($record8['CNP'] === null || $record8['CNP'] === '');
$c8_flag_ok = ((int) $record8['Reprogramada_Por_Usuario'] === 0);

echo "Resultado Caso 8 en DB:\n";
print_r($record8);

if ($c8_activa_ok && $c8_cnp_ok && $c8_flag_ok) {
    echo "✅ CASO 8 EXITOSO: Al reactivar (cascade), se limpió CNP y se reseteó el flag a 0. El sistema asume control cuando las restricciones se cumplen.\n\n";
} else {
    echo "❌ CASO 8 FALLIDO: El flag no se reseteó al reactivar.\n\n";
    exit(1);
}

// ==========================================
// CASO 9: Idempotencia del Cascade (sin cambio real no se registra log)
// Tras autodescomprometer con Caso A, una 2da corrida del cascade con
// la misma actividad (ya desprogramada, misma CNP, mismas restricciones rotas)
// NO debe agregar entrada de log. Esto evita que el modal Capa 1 se muestre
// en cada F5 sin cambios reales.
// ==========================================
echo "--- CASO 9: Probando Idempotencia del Cascade ---\n";

// 1. Limpiar logs anteriores
qp("DELETE FROM {$tLog} WHERE semana = 9992");

// 2. Estado: actividad autodescomprometida con CNP genérica, flag=0, restricciones rotas
qp("UPDATE {$tPS}
            SET Activa = '0', Categoria_CNP = 'Programación', CNP = 'Restricciones habilitantes no cumplidas', Ejecutado = 0.0, Reprogramada_Por_Usuario = 0
            WHERE Consecutivo_En_Programa = 9900005 AND Semana = 9992");

qp("UPDATE {$tPC}
            SET D_y_E = '1', Materiales = '1', MdeO = '1', Equipos = '1', Predecesora = '0', Ejecutado = 0.0, Estado = 'Atrasada'
            WHERE Id = '2.4' AND Semana = 9992");

// 3. Primera corrida: dado que la actividad YA está desprogramada con la CNP genérica,
// el cascade debe saltarla (idempotencia). El log no debe tener entrada para esta actividad.
$log9_1 = $detector->run('optimizacionJMC', 9992);

$log9_tiene_actividad_5 = false;
foreach ($log9_1 as $entry) {
    if ((int) ($entry['consecutivo'] ?? 0) === 9900005) {
        $log9_tiene_actividad_5 = true;
        break;
    }
}

$log9_global = $detector->getLog('optimizacionJMC', 9992);
$log9_global_tiene_actividad_5 = false;
foreach ($log9_global as $entry) {
    if ((int) ($entry['consecutivo'] ?? 0) === 9900005) {
        $log9_global_tiene_actividad_5 = true;
        break;
    }
}

echo "Corrida 1 - log retornado por run() para actividad 5: " . ($log9_tiene_actividad_5 ? 'SÍ (FALLO)' : 'NO (OK)') . "\n";
echo "Corrida 1 - log persistido para actividad 5: " . ($log9_global_tiene_actividad_5 ? 'SÍ (FALLO)' : 'NO (OK)') . "\n";

// 4. Validar estado: la actividad sigue Activa=0, CNP genérica intacta
$record9 = qp("SELECT Activa, Categoria_CNP, CNP, Reprogramada_Por_Usuario FROM {$tPS} WHERE Consecutivo_En_Programa = 9900005 AND Semana = 9992")->fetch();

$c9_activa_ok = ($record9['Activa'] === '0');
$c9_categoria_ok = ($record9['Categoria_CNP'] === 'Programación');
$c9_cnp_ok = ($record9['CNP'] === 'Restricciones habilitantes no cumplidas');
$c9_flag_ok = ((int) $record9['Reprogramada_Por_Usuario'] === 0);

if (!$log9_tiene_actividad_5 && !$log9_global_tiene_actividad_5 && $c9_activa_ok && $c9_categoria_ok && $c9_cnp_ok && $c9_flag_ok) {
    echo "✅ CASO 9 EXITOSO: El cascade es idempotente. Una actividad ya desprogramada con CNP genérica y mismas restricciones rotas NO se vuelve a procesar, evitando ruido en el log y en el modal Capa 1.\n\n";
} else {
    echo "❌ CASO 9 FALLIDO: La actividad fue procesada nuevamente a pesar de estar en estado objetivo.\n\n";
    print_r($record9);
    exit(1);
}

// --- LIMPIEZA FINAL ---
// Dejar el registro en su estado original correcto (Activa = 1, Ejecutado = 0.5 y restricciones al 100%)
qp("UPDATE {$tPS}
            SET Activa = '1', Categoria_CNP = NULL, CNP = NULL, Observaciones_CNP = NULL, Ejecutado = 0.5, Reprogramada_Por_Usuario = 0
            WHERE Consecutivo_En_Programa = 9900005 AND Semana = 9992");

qp("UPDATE {$tPC}
            SET D_y_E = '1', Materiales = '1', MdeO = '1', Equipos = '1', Predecesora = '0', Ejecutado = 0.5
            WHERE Id = '2.4' AND Semana = 9992");

echo "=== PRUEBAS FINALIZADAS CON ÉXITO ABSOLUTO ===\n";
exit(0);
