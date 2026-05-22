<?php
/**
 * Test de Integridad y Simulación Transaccional en Servidor
 * Valida los métodos core del Ecosistema de Escalamientos y Drawers Contextuales LPS.
 * Ejecución: `docker-compose exec app php tests/test_escalamiento_drawers.php`
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/../src/Services/LpsService.php';

use App\Services\LpsService;

echo "\e[1;35m=====================================================================\e[0m\n";
echo "\e[1;35m      TEST DE INTEGRIDAD EN SERVIDOR: ESCALAMIENTOS & DRAWERS LPS    \e[0m\n";
echo "\e[1;35m=====================================================================\e[0m\n\n";

$db = Database::getInstance();
$lpsService = new LpsService();

// 1. Obtener proyecto de prueba "Prueba"
$proyectoResult = $db->query("SELECT Id, Base_de_Datos FROM general_proyectos_procesos WHERE Proyecto_Proceso = 'Prueba' LIMIT 1")->fetch();

if (!$proyectoResult) {
    echo "\e[1;31m[ERROR]\e[0m No se encontró el proyecto de prueba 'Prueba' en la base de datos.\n";
    echo "Por favor ejecute primero: `docker-compose exec app php seed_test_users.php` para configurar el entorno.\n";
    exit(1);
}

$proyectoId = (int)$proyectoResult['Id'];
$dbPrefix = $proyectoResult['Base_de_Datos'];

echo "\e[1;36m[ENTORNO]\e[0m Proyecto de prueba: 'Prueba' | ID: {$proyectoId} | Prefijo DB: '{$dbPrefix}'\n\n";

// 2. Obtener un usuario de prueba para asociar a los logs/comentarios
$usuarioResult = $db->query("SELECT id, nombre, cargo FROM general_usuarios WHERE email LIKE 'test.%' LIMIT 1")->fetch();
if (!$usuarioResult) {
    // Fallback a cualquier usuario existente si no se han sembrado los de prueba
    $usuarioResult = $db->query("SELECT id, nombre, cargo FROM general_usuarios LIMIT 1")->fetch();
}

if (!$usuarioResult) {
    echo "\e[1;31m[ERROR]\e[0m No existen usuarios en `general_usuarios` para simular las interacciones.\n";
    exit(1);
}

$usuarioId = (int)$usuarioResult['id'];
$usuarioNombre = $usuarioResult['nombre'];
echo "\e[1;36m[SIMULACIÓN]\e[0m Usuario de QA: '{$usuarioNombre}' (ID: {$usuarioId})\n\n";

try {
    // 3. Crear una actividad simulada en consolidado para asociar la crisis
    $consecutivoSimulado = 99999;
    $semanaSimulada = 99;
    
    // Eliminar previos de prueba en caso de residuos
    $db->query("DELETE FROM `{$dbPrefix}_programa_consolidado` WHERE Consecutivo_en_Programa = ? AND Semana = ?", [$consecutivoSimulado, $semanaSimulada]);
    
    // Insertar actividad base simulada
    $db->query("
        INSERT INTO `{$dbPrefix}_programa_consolidado` 
        (Consecutivo_en_Programa, Semana, Actividad, Sub_Contratista, Responsable_AIA, D_y_E, Materiales, MdeO, Equipos, Predecesora, Pdto_Cons, Modelo, alerta_crisis) 
        VALUES (?, ?, 'Actividad de QA Automatizada', 'Contratista QA', 'Responsable QA', '1.00', '1.00', '1.00', '1.00', '1.00', '1.00', '1.00', 0)
    ", [$consecutivoSimulado, $semanaSimulada]);
    
    echo "\e[1;32m[OK]\e[0m Actividad de simulación en grilla consolidada creada con éxito.\n";

    // 4. Test de Registro de Alerta de Crisis
    echo "\n\e[1;34m[PASO 1] Registrando Alerta de Crisis en LPS...\e[0m\n";
    $motivo = "Atraso significativo continuo en la liberación de la restricción de Diseños por más de 3 semanas.";
    $crisisExitoso = $lpsService->registerCrisisAlert(
        $dbPrefix,
        $proyectoId,
        $semanaSimulada,
        $consecutivoSimulado,
        'PI',
        'PI-1'
    );
    
    if (!$crisisExitoso) {
        throw new Exception("Falló la creación de la alerta de crisis en `LpsService::registerCrisisAlert`.");
    }
    
    // Obtener el ID de la crisis activa insertada
    $crisisResult = $db->query("
        SELECT id FROM `{$dbPrefix}_lps_escalamientos` 
        WHERE proyecto_id = ? AND semana = ? AND consecutivo_en_programa = ? AND estado = 'Activo' 
        LIMIT 1
    ", [$proyectoId, $semanaSimulada, $consecutivoSimulado])->fetch();
    
    $crisisId = (int)$crisisResult['id'];
    
    echo "  -> Alerta de crisis registrada en la tabla `{$dbPrefix}_lps_escalamientos` con ID: \e[1;33m{$crisisId}\e[0m\n";
    echo "  -> \e[1;32m[OK]\e[0m Registro e inserción estructural correctos.\n";

    // 5. Test de simulación de bitácora y comentarios Slack-style
    echo "\n\e[1;34m[PASO 2] Añadiendo Comentarios en Hilos (Slack-Style) al Drawer...\e[0m\n";
    
    // Comentario Principal
    $comentarioId = $lpsService->addActivityComment(
        $dbPrefix,
        $proyectoId,
        $consecutivoSimulado,
        $semanaSimulada,
        $usuarioId,
        "Estamos revisando el atraso del subcontratista con el Director de Obra.",
        null, // parent_id
        $crisisId // escalamiento_id
    );
    
    if (!$comentarioId) {
        throw new Exception("Falló la inserción del comentario de bitácora en `LpsService::addActivityComment`.");
    }
    echo "  -> Comentario principal insertado en `{$dbPrefix}_lps_drawer_comentarios` con ID: \e[1;33m{$comentarioId}\e[0m\n";

    // Respuesta en hilo anidado (parent_id)
    $respuestaId = $lpsService->addActivityComment(
        $dbPrefix,
        $proyectoId,
        $consecutivoSimulado,
        $semanaSimulada,
        $usuarioId,
        "Entendido. Haremos seguimiento inmediato al entregable de ingeniería estructural.",
        $comentarioId, // parent_id
        $crisisId // escalamiento_id
    );
    
    if (!$respuestaId) {
        throw new Exception("Falló la inserción de la respuesta en hilo.");
    }
    echo "  -> Respuesta anidada en hilo insertada con ID: \e[1;33m{$respuestaId}\e[0m\n";
    echo "  -> \e[1;32m[OK]\e[0m Bitácora Slack-style e integridad relacional validadas.\n";

    // 6. Test de Consulta y Parser de Bitácora
    echo "\n\e[1;34m[PASO 3] Consultando Bitácora de Comentarios del Drawer...\e[0m\n";
    $comentarios = $lpsService->getActivityComments($dbPrefix, $consecutivoSimulado, $semanaSimulada, $crisisId);
    
    if (count($comentarios) !== 1) {
        throw new Exception("Error: Se esperaba 1 hilo de conversación principal en la raíz, se obtuvieron: " . count($comentarios));
    }
    if (count($comentarios[0]['respuestas']) !== 1) {
        throw new Exception("Error: Se esperaba 1 respuesta anidada en el hilo principal, se obtuvieron: " . count($comentarios[0]['respuestas']));
    }
    
    echo "  -> Cantidad de hilos principales recuperados: \e[1;33m" . count($comentarios) . "\e[0m\n";
    foreach ($comentarios as $c) {
        echo "  └── [Hilo Principal] '{$c['autor_nombre']}' ({$c['autor_cargo']}): \"{$c['comentario']}\"\n";
        foreach ($c['respuestas'] as $resp) {
            echo "       └── [Respuesta] '{$resp['autor_nombre']}' ({$resp['autor_cargo']}): \"{$resp['comentario']}\"\n";
        }
    }
    echo "  -> \e[1;32m[OK]\e[0m Recuperación e hidratación de comentarios correctas.\n";

    // 7. Test de Escalamiento Semanal Automático (Anti-Spam)
    echo "\n\e[1;34m[PASO 4] Simulando Ciclo de Escalamiento Automático...\e[0m\n";
    
    // Forzar actualización de fecha anterior en la crisis (8 días) y nivel_actual a 2 para que sea elegible para escalamiento al nivel 3 (Coordinador de Integración)
    $db->query("UPDATE `{$dbPrefix}_lps_escalamientos` SET fecha_detonacion = DATE_SUB(NOW(), INTERVAL 8 DAY), fecha_ultimo_escalamiento = DATE_SUB(NOW(), INTERVAL 8 DAY), nivel_actual = 2 WHERE id = ?", [$crisisId]);
    
    $escalados = $lpsService->escalarAlertasActivas($dbPrefix, $proyectoId);
    echo "  -> Alertas escaladas de nivel jerárquico automáticamente: \e[1;33m{$escalados}\e[0m\n";
    
    // Verificar si el nivel se actualizó
    $crisisVerif = $db->query("SELECT nivel_actual FROM `{$dbPrefix}_lps_escalamientos` WHERE id = ?", [$crisisId])->fetch();
    echo "  -> Nivel actual verificado tras escalamiento: \e[1;33m" . ($crisisVerif['nivel_actual'] ?? 'N/A') . "\e[0m (Esperado: 3 - Coordinador de Integración)\n";
    
    if ((int)$crisisVerif['nivel_actual'] !== 3) {
        throw new Exception("Error en la escala de niveles en `LpsService::escalarAlertasActivas`.");
    }
    echo "  -> \e[1;32m[OK]\e[0m Motor de escalamiento y anti-spam operando con exactitud.\n";

    // 8. Test de Consulta Agregada del Dashboard Kanban Directivo
    echo "\n\e[1;34m[PASO 5] Consultando Crisis Activas para el Dashboard Kanban...\e[0m\n";
    $crisisKanban = $lpsService->getActiveCrisisByProject($dbPrefix, $proyectoId);
    
    $crisisEncontrada = false;
    foreach ($crisisKanban as $k) {
        if ((int)$k['id'] === $crisisId) {
            $crisisEncontrada = true;
            echo "  -> Crisis detectada en Kanban: ID {$k['id']} | Nivel Jerárquico: {$k['nivel_actual']} | Actividad: '{$k['actividad_nombre']}'\n";
            break;
        }
    }
    
    if (!$crisisEncontrada) {
        throw new Exception("La crisis registrada no fue retornada por el agregador del Dashboard Kanban.");
    }
    echo "  -> \e[1;32m[OK]\e[0m Vista e hidratación del Dashboard Directivo validadas.\n";

    // 9. Test de Mitigación/Cierre de Alerta de Crisis (Justificación Obligatoria >= 100 caracteres)
    echo "\n\e[1;34m[PASO 6] Cerrando Alerta de Crisis con Mitigación y Justificación Técnica...\e[0m\n";
    
    // Intento con justificación corta (debe fallar la regla de negocio)
    $justificacionCorta = "Cierre rápido.";
    $cierreCorto = $lpsService->closeCrisisAlert($dbPrefix, $crisisId, $usuarioId, $justificacionCorta);
    echo "  -> Intento con justificación corta (<100 caracteres): " . ($cierreCorto ? "\e[1;31mFALLÓ (Se permitió)\e[0m" : "\e[1;32mÉXITO (Se bloqueó por regla de negocio)\e[0m") . "\n";
    
    if ($cierreCorto) {
        throw new Exception("La regla de negocio de longitud mínima de justificación técnica (>100 caracteres) no se aplicó en el backend.");
    }

    // Intento con justificación válida
    $justificacionValida = "Se concretó el plano estructural visado el día de hoy mediante comité extraordinario con el diseñador principal, liberando el frente de cimentación para iniciar labores sin riesgo técnico.";
    $cierreExitoso = $lpsService->closeCrisisAlert($dbPrefix, $crisisId, $usuarioId, $justificacionValida);
    echo "  -> Intento con justificación técnica válida (188 caracteres): " . ($cierreExitoso ? "\e[1;32mÉXITO (Se procesó el cierre transaccional)\e[0m" : "\e[1;31mFALLÓ (No se procesó)\e[0m") . "\n";
    
    if (!$cierreExitoso) {
        throw new Exception("Falló la ejecución de `LpsService::closeCrisisAlert` con justificación válida.");
    }
    
    // Verificar estado cerrado en DB
    $crisisCerrada = $db->query("SELECT estado, justificacion_cierre, usuario_cierre_id FROM `{$dbPrefix}_lps_escalamientos` WHERE id = ?", [$crisisId])->fetch();
    echo "  -> Estado en Base de Datos: \e[1;33m{$crisisCerrada['estado']}\e[0m (Esperado: Cerrado)\n";
    
    if ($crisisCerrada['estado'] !== 'Cerrado') {
        throw new Exception("El estado final de la crisis no fue actualizado en la base de datos.");
    }
    echo "  -> \e[1;32m[OK]\e[0m Regla de longitud y guardado de mitigación operan perfectamente.\n";

    echo "\n\e[1;32m=====================================================================\e[0m\n";
    echo "\e[1;32m         ¡TEST DE INTEGRIDAD COMPLETADO CON ÉXITO! (100% PASS)       \e[0m\n";
    echo "\e[1;32m=====================================================================\e[0m\n";

} catch (Throwable $e) {
    echo "\n\e[1;31m=====================================================================\e[0m\n";
    echo "\e[1;31m        ¡EL TEST DE INTEGRIDAD EN SERVIDOR HA DETECTADO UN FALLO!    \e[0m\n";
    echo "\e[1;31m=====================================================================\e[0m\n";
    echo "\e[1;31mError:\e[0m " . $e->getMessage() . "\n";
    echo "Línea: " . $e->getLine() . " en el archivo: " . $e->getFile() . "\n";
} finally {
    // Cleanup físico quirúrgico de todos los registros creados durante el test de simulación
    $db->query("DELETE FROM `{$dbPrefix}_lps_drawer_comentarios` WHERE consecutivo_en_programa = ? AND semana = ?", [$consecutivoSimulado, $semanaSimulada]);
    $db->query("DELETE FROM `{$dbPrefix}_lps_escalamientos` WHERE consecutivo_en_programa = ? AND semana = ?", [$consecutivoSimulado, $semanaSimulada]);
    $db->query("DELETE FROM `{$dbPrefix}_programa_consolidado` WHERE Consecutivo_en_Programa = ? AND Semana = ?", [$consecutivoSimulado, $semanaSimulada]);
    
    echo "\n\e[1;36m[CLEANUP]\e[0m Registros simulados eliminados. Base de datos limpia y sin residuos.\n\n";
}
