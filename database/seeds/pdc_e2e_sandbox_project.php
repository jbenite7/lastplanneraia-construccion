<?php

/**
 * Proyecto sacrificable para los e2e del Plan de Compras v2.
 *
 * Por qué existe: los specs `tests/browser/pdc-v2-*.spec.mjs` importan presupuestos de juguete y
 * los dejan como versión activa. Contra un proyecto real (Da Porto, project_id=73) eso desactiva
 * el presupuesto de producción sin restaurarlo, así que los specs vivían apagados detrás de
 * `PDC_E2E_DESTRUCTIVO=1` y la cobertura e2e del módulo era cero. Con un proyecto propio, escribir
 * a gusto deja de tener consecuencias y los specs vuelven a correr por defecto.
 *
 * Este script es **idempotente y reseteador**: cada ejecución deja el sandbox en el mismo estado
 * inicial conocido (sin presupuestos, sin vínculos, sin paquetes asignados, sin plan) y vuelve a
 * sembrar el cronograma mínimo. Se ejecuta desde los specs antes de cada test.
 *
 *   docker compose exec -T app php /var/www/html/database/seeds/pdc_e2e_sandbox_project.php
 *
 * Con `--purge` además borra el proyecto entero (para desmontar el sandbox de un entorno).
 *
 * Ojo — lo que este sandbox NO aísla: `general_maestro_insumos` y `general_paquetes_contratacion`
 * son catálogos GLOBALES sin project_id. Ver la nota de residuos al final del archivo.
 */

require __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Core/Database.php';

const PDC_SANDBOX_PROJECT_ID = 990100;
const PDC_SANDBOX_NOMBRE = 'PDC Sandbox E2E';
const PDC_SANDBOX_PREFIJO = 'pdc_sandbox_e2e';
const PDC_SANDBOX_SEMANA = 1;

/** Prefijo reservado para todo lo que los specs crean en catálogos globales. */
const PDC_SANDBOX_MARCA = 'ZZTEST';

$db = Database::getInstance();
$purge = in_array('--purge', $argv, true);

// El prefijo es la llave con la que TableResolver resuelve el proyecto: no puede colisionar.
$colision = $db->query(
    'SELECT Id FROM general_proyectos_procesos WHERE Base_de_Datos = ? AND Id <> ? LIMIT 1',
    [PDC_SANDBOX_PREFIJO, PDC_SANDBOX_PROJECT_ID],
)->fetchColumn();
if ($colision !== false) {
    throw new RuntimeException(sprintf(
        'El prefijo %s ya pertenece al proyecto %s; el sandbox no puede montarse encima.',
        PDC_SANDBOX_PREFIJO,
        $colision,
    ));
}

/**
 * Borra todo lo que los specs escriben con `project_id` del sandbox, en orden de dependencia.
 * `pdc_presupuesto_versiones` arrastra en cascada items, insumos de APU y vínculos (ver las FK
 * `fk_pdcpi_version`, `fk_pdcpai_version` y `fk_piv_version`), así que basta con borrar la cabecera.
 */
function pdcSandboxLimpiarDatos(Database $db, int $projectId): void
{
    foreach ([
        'pdc_plan_paso',
        'pdc_plan_paquete',
        'pdc_paquete_frente',
        'pdc_insumo_paquete',
        'pdc_insumo_actividades',
        'pdc_correcciones_motor',
        'pdc_presupuesto_versiones',
        // A4.1: si la obra queda con un proceso propio, el siguiente test la encuentra con la
        // configuración del anterior y no con los siete por defecto. No se notaba mientras el único
        // test que configuraba terminaba pulsando «Restablecer»; el de copiar entre obras no puede.
        'pdc_proyecto_pasos',
        // Y su historial: es de solo anexar, así que sin esto cada corrida hereda las entradas de
        // todas las anteriores y la pantalla nunca vuelve al estado inicial que los tests asumen.
        'pdc_proyecto_pasos_historial',
        // Las correcciones de duración por obra. Sin esto, el spec de duraciones deja su excepción
        // puesta y el siguiente test lee 15 días donde el catálogo dice 10: el plan del sandbox
        // arranca movido y ningún test lo explica.
        'pdc_proyecto_duraciones',
    ] as $tabla) {
        $db->query("DELETE FROM {$tabla} WHERE project_id = ?", [$projectId]);
    }

    // El cronograma se resiembra completo abajo; `programa_consolidado` y `semanas_activas`
    // caen en cascada desde `programa`, pero se borran explícitamente por claridad y por si el
    // entorno viene de un esquema sin esas FK.
    $db->query('DELETE FROM programa_consolidado WHERE project_id = ?', [$projectId]);
    $db->query('DELETE FROM programa WHERE project_id = ?', [$projectId]);
    $db->query('DELETE FROM semanas_activas WHERE project_id = ?', [$projectId]);
}

/**
 * Residuos de los specs en los catálogos globales. Se borran solo si nadie más los referencia:
 * un paquete que ya tenga insumos asignados en otro proyecto NO se toca (la FK es RESTRICT y,
 * sobre todo, sería dato ajeno). Cubre `E2E ...` (nombre que usa el spec de paquetes) y cualquier
 * cosa marcada con el prefijo ZZTEST.
 */
function pdcSandboxLimpiarGlobales(Database $db): void
{
    $db->query(
        "DELETE p FROM general_paquetes_contratacion p
         LEFT JOIN pdc_insumo_paquete ip ON ip.paquete_id = p.id
         LEFT JOIN pdc_paquete_frente pf ON pf.paquete_id = p.id
         LEFT JOIN pdc_plan_paquete pp ON pp.paquete_id = p.id
         LEFT JOIN pdc_plan_paso ps ON ps.paquete_id = p.id
         WHERE (p.nombre_norm LIKE 'E2E %' OR p.nombre_norm LIKE ?)
           AND ip.id IS NULL AND pf.id IS NULL AND pp.id IS NULL AND ps.id IS NULL",
        [PDC_SANDBOX_MARCA . '%'],
    );

    // Las filas del catálogo de duraciones que siembran los specs. Va DESPUÉS del borrado de
    // paquetes: `general_paquetes_contratacion.duracion_ref` apunta aquí, y borrar primero dejaría
    // paquetes ZZTEST colgando de una fila que ya no existe (o la FK lo impediría). Se limita a las
    // marcadas: el catálogo real es dato de la empresa y no se toca.
    $db->query(
        "DELETE d FROM general_dias_procesos_contratacion d
         LEFT JOIN general_paquetes_contratacion p ON p.duracion_ref = d.id
         WHERE d.paqueteContratacion LIKE ? AND p.id IS NULL",
        [PDC_SANDBOX_MARCA . '%'],
    );

    // Insumos SINCO de juguete: solo los que ningún presupuesto vincula (FK fk_piv_maestro RESTRICT).
    $db->query(
        "DELETE m FROM general_maestro_insumos m
         LEFT JOIN pdc_insumo_vinculos v ON v.maestro_id = m.id
         WHERE (m.codigo_sinco LIKE ? OR m.descripcion_norm LIKE ?) AND v.id IS NULL",
        [PDC_SANDBOX_MARCA . '-%', PDC_SANDBOX_MARCA . ' %'],
    );
}

if ($purge) {
    pdcSandboxLimpiarDatos($db, PDC_SANDBOX_PROJECT_ID);
    pdcSandboxLimpiarGlobales($db);
    $db->query('DELETE FROM project_members WHERE project_id = ?', [PDC_SANDBOX_PROJECT_ID]);
    $db->query('DELETE FROM general_proyectos_procesos WHERE Id = ?', [PDC_SANDBOX_PROJECT_ID]);
    echo "Sandbox PDC eliminado.\n";
    exit(0);
}

// ---------------------------------------------------------------------------
// 1) El proyecto
// ---------------------------------------------------------------------------
$db->query(
    'INSERT INTO general_proyectos_procesos
        (Id, Proyecto_Proceso, Base_de_Datos, Area, Activo, Acceso, pdcActivo, fechaInicioLineaBase, fechaFinLineaBase)
     VALUES (?, ?, ?, ?, 1, 1, 1, ?, ?)
     ON DUPLICATE KEY UPDATE
        Proyecto_Proceso = VALUES(Proyecto_Proceso),
        Base_de_Datos = VALUES(Base_de_Datos),
        Area = VALUES(Area),
        Activo = 1, Acceso = 1, pdcActivo = 1,
        fechaInicioLineaBase = VALUES(fechaInicioLineaBase),
        fechaFinLineaBase = VALUES(fechaFinLineaBase)',
    [PDC_SANDBOX_PROJECT_ID, PDC_SANDBOX_NOMBRE, PDC_SANDBOX_PREFIJO, 'Construccion', '2026-01-05', '2027-12-31'],
);

// ---------------------------------------------------------------------------
// 2) Los usuarios de prueba (mismos roles que database/seeds/dev_test_users.php)
// ---------------------------------------------------------------------------
foreach (['test.A' => 'A', 'test.D' => 'D', 'test.R' => 'R', 'test.C' => 'C', 'test.V' => 'V'] as $usuario => $rol) {
    $userId = $db->query('SELECT id FROM general_usuarios WHERE usuario = ? LIMIT 1', [$usuario])->fetchColumn();
    if ($userId === false) {
        continue;
    }
    $db->query(
        'INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE role = VALUES(role)',
        [PDC_SANDBOX_PROJECT_ID, (int) $userId, $rol],
    );
}

// ---------------------------------------------------------------------------
// 3) Estado inicial limpio
// ---------------------------------------------------------------------------
pdcSandboxLimpiarDatos($db, PDC_SANDBOX_PROJECT_ID);
pdcSandboxLimpiarGlobales($db);

// ---------------------------------------------------------------------------
// 4) Cronograma mínimo: una semana activa y dos frentes (encabezados, Titulo = 1).
//
// `PlanFechasService::frentesDisponibles()` lee los encabezados de la ÚLTIMA semana activa; sin
// ellos la pestaña Plan no puede proponer ningún amarre y el spec A4 se quedaría sin caso.
// ---------------------------------------------------------------------------
$db->query(
    'INSERT INTO semanas_activas (project_id, Id, Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem, Semanal_Confirmada, fechaCreacionSemana)
     VALUES (?, 1, ?, ?, ?, 0, ?)',
    [PDC_SANDBOX_PROJECT_ID, PDC_SANDBOX_SEMANA, '2026-01-05', '2026-01-11', '2026-01-05'],
);

/**
 * El frente principal se llama igual que el paquete que crea `pdc-v2-plan.spec.mjs`: así el motor
 * de propuestas (`sugerirFrentes()`, Jaccard sobre los tokens del nombre) acierta con confianza
 * alta y el spec tiene una propuesta que aceptar. Si cambias uno, cambia el otro.
 */
const PDC_SANDBOX_FRENTE_PLAN = 'ZZTEST PAQUETE PLAN';

$frentes = [
    [1, PDC_SANDBOX_FRENTE_PLAN, '2026-02-02', '2026-04-30'],
    [2, 'ZZTEST FRENTE SECUNDARIO', '2026-05-04', '2026-07-31'],
];

foreach ($frentes as [$consecutivo, $actividad, $inicio, $fin]) {
    $db->query(
        'INSERT INTO programa (project_id, unique_id, Consecutivo, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin)
         VALUES (?, ?, ?, ?, ?, 1, ?, ?)',
        [PDC_SANDBOX_PROJECT_ID, $consecutivo, $consecutivo, (string) $consecutivo, $actividad, $inicio, $fin],
    );
    $db->query(
        'INSERT INTO programa_consolidado
            (project_id, Consecutivo, Semana, unique_id, Consecutivo_en_Programa, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin, Activa)
         VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?, 1)',
        [PDC_SANDBOX_PROJECT_ID, $consecutivo, PDC_SANDBOX_SEMANA, $consecutivo, $consecutivo,
         (string) $consecutivo, $actividad, $inicio, $fin],
    );
}

// El paquete del proyecto NO se siembra aquí: `pdc-v2-plan.spec.mjs` lo construye por la interfaz
// (importar → maestro → paquetes) porque el bloque «Sin frente» se alimenta del resumen de
// paquetes, que solo cuenta insumos de la versión activa. Una asignación suelta en
// `pdc_insumo_paquete` no aparecería allí.

printf(
    "Sandbox PDC listo: project_id=%d «%s» (frente principal: %s).\n",
    PDC_SANDBOX_PROJECT_ID,
    PDC_SANDBOX_NOMBRE,
    PDC_SANDBOX_FRENTE_PLAN,
);

/*
 * Residuos conocidos que este seed NO puede evitar (catálogos globales, sin project_id):
 *
 * - `general_maestro_insumos`: el spec `pdc-v2-maestro` pulsa «crear masivo», que da de alta en el
 *   catálogo global los insumos del presupuesto de juguete (TEJA DE ZINC, AYUDANTE, CONCRETO
 *   4000PSI, SERVICIO BOMBEO). Son altas aditivas e idempotentes —el upsert es por
 *   (descripcion_norm, unidad)— y hoy ninguna choca con un insumo real, pero quedan en el catálogo
 *   de toda la empresa. `pdcSandboxLimpiarGlobales()` no las borra porque el presupuesto del
 *   sandbox las referencia mientras la versión exista; se limpian al resetear (las versiones caen
 *   primero) solo si además llevan la marca ZZTEST, que estas no llevan.
 * - `general_paquetes_contratacion`: el spec de paquetes crea «E2E Paquete Pisos». Ese sí lo borra
 *   `pdcSandboxLimpiarGlobales()` en cada reset, siempre que nadie más lo referencie.
 */
