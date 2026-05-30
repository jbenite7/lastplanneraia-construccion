<?php

require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/../src/Services/ProgramChangeDetector.php';

$db = Database::getInstance();
$detector = new App\Services\ProgramChangeDetector();

echo "=== INICIANDO PRUEBAS DE INTEGRACIÓN: CASOS DE GOBERNANZA ===\n\n";

// --- PREPARACIÓN INICIAL ---
// Limpiar la base de datos de pruebas anteriores
$db->query("DELETE FROM optimizacionJMC_programacion_semanal WHERE Consecutivo_En_Programa = 5 AND Semana = 2");

// Insertar la actividad base en la programación semanal de la Semana 2
$db->query("INSERT INTO optimizacionJMC_programacion_semanal (
    Semana, Consecutivo_En_Programa, Id, Actividad, Activa, Empresa, Ejecutado
) VALUES (2, 5, '2.4', 'Actas de vecindad frentes de obra iniciales', '0', 'AIA', 0.5)");

// ==========================================
// CASO 1: Reactivación Automática
// Activa = 0 con CNP genérica de restricciones -> Liberar restricciones en consolidado -> Debe pasar a Activa = 1 y limpiar CNP.
// ==========================================
echo "--- CASO 1: Probando Reactivación Automática ---\n";

// 1. Configurar CNP genérica de restricciones en la programación semanal
$db->query("UPDATE optimizacionJMC_programacion_semanal 
            SET Activa = '0', Categoria_CNP = 'Programación', CNP = 'Restricciones habilitantes no cumplidas', Ejecutado = 0.0
            WHERE Consecutivo_En_Programa = 5 AND Semana = 2");

// 2. Liberar todas las restricciones duras (al 100%) en el Programa Consolidado
$db->query("UPDATE optimizacionJMC_programa_consolidado 
            SET D_y_E = '1', Materiales = '1', MdeO = '1', Equipos = '1', Predecesora = '1', Ejecutado = 0.0
            WHERE Id = '2.4' AND Semana = 2");

// 3. Correr el detector de cambios
$log1 = $detector->run('optimizacionJMC', 2);
echo "Log del detector (Caso 1):\n";
print_r($log1);

// 4. Validar el estado resultante en la base de datos
$record1 = $db->query("SELECT Activa, Categoria_CNP, CNP FROM optimizacionJMC_programacion_semanal WHERE Consecutivo_En_Programa = 5 AND Semana = 2")->fetch();

$c1_activa_ok = ($record1['Activa'] === '1');
$c1_cnp_ok = ($record1['CNP'] === null || $record1['CNP'] === '');

echo "Resultado Caso 1 en DB:\n";
print_r($record1);

if ($c1_activa_ok && $c1_cnp_ok) {
    echo "✅ CASO 1 EXITOSO: La actividad fue reactivada automáticamente a Activa=1 y se limpió la causa genérica de restricciones.\n\n";
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
$db->query("UPDATE optimizacionJMC_programacion_semanal 
            SET Activa = '0', Categoria_CNP = 'Mano de Obra', CNP = 'Causa Manual del Usuario', Ejecutado = 0.0
            WHERE Consecutivo_En_Programa = 5 AND Semana = 2");

// 2. Correr el detector de cambios
$log2 = $detector->run('optimizacionJMC', 2);
echo "Log del detector (Caso 2):\n";
print_r($log2);

// 3. Validar el estado resultante en la base de datos
$record2 = $db->query("SELECT Activa, Categoria_CNP, CNP FROM optimizacionJMC_programacion_semanal WHERE Consecutivo_En_Programa = 5 AND Semana = 2")->fetch();

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
// Activa = 0 con CNP genérica -> Simular reprogramación del usuario (Activa = 1, limpia CNP)
// -> Romper restricciones en consolidado (NO OK) -> Simular el DELETE de limpieza física de Autoprogramar
// -> Correr detector -> Debe continuar existiendo, con Activa = 1 y no ser inhabilitada.
// ==========================================
echo "--- CASO 3: Probando Reprogramación Manual e Inmunidad ante Saneamiento ---\n";

// 1. Configurar inicialmente como inactiva por restricciones genéricas
$db->query("UPDATE optimizacionJMC_programacion_semanal 
            SET Activa = '0', Categoria_CNP = 'Programación', CNP = 'Restricciones habilitantes no cumplidas', Ejecutado = 0.0
            WHERE Consecutivo_En_Programa = 5 AND Semana = 2");

// 2. Simular reprogramación manual del usuario desde el módulo de CNP (Activa = 1)
$db->query("UPDATE optimizacionJMC_programacion_semanal 
            SET Activa = '1', Categoria_CNP = NULL, CNP = NULL, Observaciones_CNP = NULL
            WHERE Consecutivo_En_Programa = 5 AND Semana = 2");

// 3. Romper las restricciones duras en el Programa Consolidado (Predecesora = 0 y Ejecutado = 0)
$db->query("UPDATE optimizacionJMC_programa_consolidado 
            SET D_y_E = '1', Materiales = '1', MdeO = '1', Equipos = '1', Predecesora = '0', Ejecutado = 0.0, Estado = 'Atrasada'
            WHERE Id = '2.4' AND Semana = 2");

// 4. Simular la limpieza física del botón "Autoprogramar" en SemanalApiController (con la nueva consulta corregida)
$eligibleSubSql = "SELECT Consecutivo_en_Programa FROM optimizacionJMC_programa_consolidado 
    WHERE Semana = 2 AND Titulo = 0 
      AND (Estado='En Curso' OR Estado='Atrasada' OR Estado='Debe Iniciar'
        OR Estado='A Tiempo' OR Estado='Ya Debió Iniciar y Restricciones Pendientes')";

$db->query("
    DELETE FROM optimizacionJMC_programacion_semanal 
    WHERE Semana = 2 AND Activa = '1'
      AND (Ejecutado_Real IS NULL OR Ejecutado_Real <= 0)
      AND (Compromiso IS NULL OR Compromiso <= 0)
      AND Consecutivo_En_Programa NOT IN ({$eligibleSubSql})
");

// 5. Validar que la actividad NO haya sido borrada físicamente
$existeFisico = (int) $db->query("SELECT COUNT(*) FROM optimizacionJMC_programacion_semanal WHERE Consecutivo_En_Programa = 5 AND Semana = 2")->fetchColumn();
if ($existeFisico > 0) {
    echo "✅ Sub-verificación: La actividad reprogramada sobrevivió a la limpieza física del botón Autoprogramar.\n";
} else {
    echo "❌ Sub-verificación FALLIDA: La actividad fue eliminada físicamente por la limpieza de Autoprogramar.\n";
    exit(1);
}

// 6. Correr el detector de cambios
$log3 = $detector->run('optimizacionJMC', 2);
echo "Log del detector (Caso 3):\n";
print_r($log3);

// 7. Validar el estado resultante en la base de datos
$record3 = $db->query("SELECT Activa, Categoria_CNP, CNP FROM optimizacionJMC_programacion_semanal WHERE Consecutivo_En_Programa = 5 AND Semana = 2")->fetch();

$c3_activa_ok = ($record3['Activa'] === '1');
$c3_cnp_ok = ($record3['CNP'] === null || $record3['CNP'] === '');

echo "Resultado Caso 3 en DB:\n";
print_r($record3);

if ($c3_activa_ok && $c3_cnp_ok) {
    echo "✅ CASO 3 EXITOSO: Se garantizó la inmunidad ante el saneamiento. La actividad reprogramada manualmente sobrevivió a la limpieza del botón Autoprogramar y se mantuvo Activa=1 a pesar de tener restricciones pendientes.\n\n";
} else {
    echo "❌ CASO 3 FALLIDO: El detector de cambios desactivó o borró la actividad reprogramada voluntariamente.\n\n";
    exit(1);
}

// ==========================================
// CASO 4: Inmunidad de Actividades Futuras Reprogramadas
// Activa = 0 con CNP genérica -> Simular reprogramación del usuario (Activa = 1, limpia CNP)
// -> Configurar estado consolidado como 'En Liberación de Restricciones' (actividad futura) con restricciones rotas
// -> Simular el DELETE de limpieza física de Autoprogramar (excluyendo terminadas y sin datos)
// -> Correr detector -> Debe continuar existiendo, con Activa = 1 y no ser desprogramada.
// ==========================================
echo "--- CASO 4: Probando Inmunidad de Actividades Futuras Reprogramadas ---\n";

// 1. Configurar inicialmente como inactiva por restricciones genéricas
$db->query("UPDATE optimizacionJMC_programacion_semanal 
            SET Activa = '0', Categoria_CNP = 'Programación', CNP = 'Restricciones habilitantes no cumplidas', Ejecutado = 0.0
            WHERE Consecutivo_En_Programa = 5 AND Semana = 2");

// 2. Simular reprogramación manual del usuario desde el módulo de CNP (Activa = 1)
$db->query("UPDATE optimizacionJMC_programacion_semanal 
            SET Activa = '1', Categoria_CNP = NULL, CNP = NULL, Observaciones_CNP = NULL
            WHERE Consecutivo_En_Programa = 5 AND Semana = 2");

// 3. Establecer estado en consolidado como 'En Liberación de Restricciones' y restricciones pendientes (Predecesora = 0, Ejecutado = 0.0)
$db->query("UPDATE optimizacionJMC_programa_consolidado 
            SET D_y_E = '1', Materiales = '1', MdeO = '1', Equipos = '1', Predecesora = '0', Ejecutado = 0.0, Estado = 'En Liberación de Restricciones'
            WHERE Id = '2.4' AND Semana = 2");

// 4. Simular la limpieza física de Autoprogramar con la nueva consulta (Estado NOT IN ('Terminada', 'Terminada Antes', 'Sin Datos'))
$eligibleSubSql = "SELECT Consecutivo_en_Programa FROM optimizacionJMC_programa_consolidado 
    WHERE Semana = 2 AND Titulo = 0 
      AND Estado NOT IN ('Terminada', 'Terminada Antes', 'Sin Datos')";

$db->query("
    DELETE FROM optimizacionJMC_programacion_semanal 
    WHERE Semana = 2 AND Activa = '1'
      AND (Ejecutado_Real IS NULL OR Ejecutado_Real <= 0)
      AND (Compromiso IS NULL OR Compromiso <= 0)
      AND Consecutivo_En_Programa NOT IN ({$eligibleSubSql})
");

// 5. Validar que la actividad futura reprogramada NO haya sido borrada físicamente
$existeFisico4 = (int) $db->query("SELECT COUNT(*) FROM optimizacionJMC_programacion_semanal WHERE Consecutivo_En_Programa = 5 AND Semana = 2")->fetchColumn();
if ($existeFisico4 > 0) {
    echo "✅ Sub-verificación: La actividad futura reprogramada sobrevivió a la limpieza física.\n";
} else {
    echo "❌ Sub-verificación FALLIDA: La actividad futura fue eliminada físicamente de la base de datos.\n";
    exit(1);
}

// 6. Correr el detector de cambios
$log4 = $detector->run('optimizacionJMC', 2);
echo "Log del detector (Caso 4):\n";
print_r($log4);

// 7. Validar el estado resultante en la base de datos (debe seguir Activa = 1 y limpia)
$record4 = $db->query("SELECT Activa, Categoria_CNP, CNP FROM optimizacionJMC_programacion_semanal WHERE Consecutivo_En_Programa = 5 AND Semana = 2")->fetch();

$c4_activa_ok = ($record4['Activa'] === '1');
$c4_cnp_ok = ($record4['CNP'] === null || $record4['CNP'] === '');

echo "Resultado Caso 4 en DB:\n";
print_r($record4);

if ($c4_activa_ok && $c4_cnp_ok) {
    echo "✅ CASO 4 EXITOSO: Actividad futura reprogramada voluntariamente inmunizada con éxito.\n\n";
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
$db->query("DELETE FROM optimizacionJMC_auto_program_log WHERE semana = 2");

// 2. Configurar inicialmente como inactiva por restricciones genéricas
$db->query("UPDATE optimizacionJMC_programacion_semanal 
            SET Activa = '0', Categoria_CNP = 'Programación', CNP = 'Restricciones habilitantes no cumplidas', Ejecutado = 0.0
            WHERE Consecutivo_En_Programa = 5 AND Semana = 2");

// 3. Liberar todas las restricciones duras (al 100%) en el consolidado para forzar reactivación
$db->query("UPDATE optimizacionJMC_programa_consolidado 
            SET D_y_E = '1', Materiales = '1', MdeO = '1', Equipos = '1', Predecesora = '1', Ejecutado = 0.0, Estado = 'Debe Iniciar'
            WHERE Id = '2.4' AND Semana = 2");

// 4. Correr la primera ejecución (que realiza cambios)
$log5_1 = $detector->run('optimizacionJMC', 2);
$logs_consultados_1 = $detector->getLog('optimizacionJMC', 2);

echo "Corrida 1 - Cambios devueltos por run():\n";
print_r($log5_1);
echo "Corrida 1 - Cambios obtenidos de getLog():\n";
print_r($logs_consultados_1);

$has_changes_1 = count($logs_consultados_1) > 0;

// 5. Correr una segunda ejecución de inmediato (sin cambios nuevos)
sleep(1); // Forzar cambio de segundo en el timestamp del lote
$log5_2 = $detector->run('optimizacionJMC', 2);
$logs_consultados_2 = $detector->getLog('optimizacionJMC', 2);

echo "Corrida 2 - Cambios devueltos por run():\n";
print_r($log5_2);
echo "Corrida 2 - Cambios obtenidos de getLog():\n";
print_r($logs_consultados_2);

$has_changes_2 = count($logs_consultados_2) > 0;

if ($has_changes_1 && !$has_changes_2) {
    echo "✅ CASO 5 EXITOSO: Los logs muestran de forma exclusiva los cambios ejecutados en la última corrida de la función (Corrida 1 con cambios, Corrida 2 vacía).\n\n";
} else {
    echo "❌ CASO 5 FALLIDO: No se aisló correctamente el log de la última corrida.\n\n";
    exit(1);
}

// --- LIMPIEZA FINAL ---
// Dejar el registro en su estado original correcto (Activa = 1, Ejecutado = 0.5 y restricciones al 100%)
$db->query("UPDATE optimizacionJMC_programacion_semanal 
            SET Activa = '1', Categoria_CNP = NULL, CNP = NULL, Observaciones_CNP = NULL, Ejecutado = 0.5
            WHERE Consecutivo_En_Programa = 5 AND Semana = 2");

$db->query("UPDATE optimizacionJMC_programa_consolidado 
            SET D_y_E = '1', Materiales = '1', MdeO = '1', Equipos = '1', Predecesora = '0', Ejecutado = 0.5
            WHERE Id = '2.4' AND Semana = 2");

echo "=== PRUEBAS FINALIZADAS CON ÉXITO ABSOLUTO ===\n";
exit(0);
