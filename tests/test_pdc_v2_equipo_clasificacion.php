<?php

declare(strict_types=1);

/**
 * Clasificación de equipos: alquilado / comprado / sin clasificar — PDC v2, Ola 2.
 *
 * Cubre los puntos 1, 2, 3, 5 y 6 de la condición de hecho del spec
 * `docs/superpowers/specs/2026-07-29-equipo-alquilado-comprado-design.md`.
 *
 * Origen: comité del 2026-07-29, petición de Tomás Trujillo con motivo contable — «el del alquiler
 * es distinto al de la compra, y contabilidad no sabe el manejo». El tipo de recurso «Equipo» metía
 * las dos cosas en la misma bolsa.
 *
 * Siembra sus propios insumos con una marca en `creado_por` y limpia al entrar y al salir: los
 * equipos reales del maestro no se tocan. Mismo patrón que `test_pdc_v2_reenganche_pendientes.php`.
 *
 * Uso:  docker compose exec app php tests/test_pdc_v2_equipo_clasificacion.php
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Services\Pdc\MaestroInsumosService;
use App\Services\Pdc\TipoRecursoEquipo;

const PDC_EQ_MARCA = 'test-equipo-clasificacion';

$fallos = 0;
$assert = static function (bool $ok, string $que) use (&$fallos): void {
    if ($ok) {
        fwrite(STDOUT, "PASS: {$que}\n");
        return;
    }
    $fallos++;
    fwrite(STDERR, "FAIL: {$que}\n");
};

// ---------------------------------------------------------------------------------------------
// 1) Los valores y la pista, en seco (sin BD).
// ---------------------------------------------------------------------------------------------

fwrite(STDOUT, "\n== TipoRecursoEquipo (en seco)\n");

$assert(TipoRecursoEquipo::SIN_CLASIFICAR === 'EQUIPO (SIN CLASIFICAR)', 'SIN_CLASIFICAR es el string exacto.');
$assert(TipoRecursoEquipo::ALQUILADO === 'ALQUILER EQUIPOS', 'ALQUILADO adopta el valor que SINCO ya emite.');
$assert(TipoRecursoEquipo::COMPRADO === 'EQUIPO COMPRADO', 'COMPRADO es el valor nuevo.');
$assert(TipoRecursoEquipo::GENERICO === 'EQUIPO', 'GENERICO conserva el valor viejo de SINCO.');

$assert(TipoRecursoEquipo::esEquipo('EQUIPO') === true, 'El genérico es equipo.');
$assert(TipoRecursoEquipo::esEquipo('EQUIPO (SIN CLASIFICAR)') === true, 'El de tránsito es equipo.');
$assert(TipoRecursoEquipo::esEquipo('ALQUILER EQUIPOS') === true, 'El alquilado es equipo.');
$assert(TipoRecursoEquipo::esEquipo('EQUIPO COMPRADO') === true, 'El comprado es equipo.');
$assert(TipoRecursoEquipo::esEquipo('MATERIAL') === false, 'Un material no es equipo.');
$assert(TipoRecursoEquipo::esEquipo('TRANSPORTE') === false, 'TRANSPORTE no es equipo: tiene su propio tipo y no entra a esta cola.');
$assert(TipoRecursoEquipo::esEquipo(null) === false, 'NULL no es equipo (los insumos nacidos del presupuesto llegan sin tipo).');
$assert(TipoRecursoEquipo::esEquipo('  equipo  ') === true, 'esEquipo normaliza caja y espacios: el dato viene de un Excel.');

$assert(TipoRecursoEquipo::esClasificado('ALQUILER EQUIPOS') === true, 'Alquilado está clasificado.');
$assert(TipoRecursoEquipo::esClasificado('EQUIPO COMPRADO') === true, 'Comprado está clasificado.');
$assert(TipoRecursoEquipo::esClasificado('EQUIPO') === false, 'El genérico no está clasificado.');
$assert(TipoRecursoEquipo::esClasificado('EQUIPO (SIN CLASIFICAR)') === false, 'El de tránsito no está clasificado.');
$assert(TipoRecursoEquipo::esClasificado('MATERIAL') === false, 'Un material no es un equipo clasificado.');

$assert(TipoRecursoEquipo::esDestinoValido('ALQUILER EQUIPOS') === true, 'Alquilado es destino válido.');
$assert(TipoRecursoEquipo::esDestinoValido('EQUIPO COMPRADO') === true, 'Comprado es destino válido.');
$assert(TipoRecursoEquipo::esDestinoValido('EQUIPO (SIN CLASIFICAR)') === false, 'El tránsito NO es un destino: clasificar es avanzar.');
$assert(TipoRecursoEquipo::esDestinoValido('MATERIAL') === false, 'No se cambia un equipo a material por esta puerta.');

// La pista sugiere, no decide. Lee `agrupacion` —campo que escribió presupuestos—, nunca la descripción.
$assert(TipoRecursoEquipo::pistaSinco('ALQUILER MAQUINARIA Y EQUIPOS') === TipoRecursoEquipo::ALQUILADO, 'Prefijo ALQUILER sugiere alquilado.');
$assert(TipoRecursoEquipo::pistaSinco('ALQUILER BIENES MUEBLES') === TipoRecursoEquipo::ALQUILADO, 'Otro ALQUILER sugiere alquilado.');
$assert(TipoRecursoEquipo::pistaSinco('COMPRA ELEMENTOS- MAQUINARIA Y EQUIPO') === TipoRecursoEquipo::COMPRADO, 'Prefijo COMPRA sugiere comprado.');
$assert(TipoRecursoEquipo::pistaSinco('COMPRAS DE INSUMOS MENORES') === TipoRecursoEquipo::COMPRADO, 'El plural COMPRAS también.');
$assert(TipoRecursoEquipo::pistaSinco('MTTO COMPRA MAQUINARIA Y EQUIPO') === null, 'MTTO no sugiere: mantener un equipo no dice de quién es.');
$assert(TipoRecursoEquipo::pistaSinco('MAT-HERRAMIENTA EQUIPO MENOR Y CONSUMIBLES') === null, 'Sin prefijo reconocible, no se sugiere.');
$assert(TipoRecursoEquipo::pistaSinco('GASTOS MEDICOS Y DROGAS PERSONAL OBRA') === null, 'GASTOS no sugiere nada.');
$assert(TipoRecursoEquipo::pistaSinco(null) === null, 'Sin agrupación no hay pista.');
$assert(TipoRecursoEquipo::pistaSinco('') === null, 'Agrupación vacía no da pista.');

// ---------------------------------------------------------------------------------------------
// 2) Estado del maestro tras la migración (punto 2 de la condición de hecho).
// ---------------------------------------------------------------------------------------------

fwrite(STDOUT, "\n== Estado del maestro tras la migración\n");

$db = Database::getInstance();

$limpiar = static function () use ($db): void {
    $db->query('DELETE FROM general_maestro_insumos WHERE creado_por = ?', [PDC_EQ_MARCA]);
};
$limpiar();

$genericos = (int) $db->query(
    'SELECT COUNT(*) FROM general_maestro_insumos WHERE UPPER(TRIM(tipo_recurso)) = ?',
    [TipoRecursoEquipo::GENERICO],
)->fetchColumn();
$assert($genericos === 0, "No queda ningún insumo con el tipo genérico «EQUIPO» (hay {$genericos}).");

$transito = (int) $db->query(
    'SELECT COUNT(*) FROM general_maestro_insumos WHERE tipo_recurso = ?',
    [TipoRecursoEquipo::SIN_CLASIFICAR],
)->fetchColumn();
$assert($transito > 0, "Los equipos preexistentes están en la cola de sin clasificar ({$transito}).");

// Las dos columnas de auditoría existen.
$cols = (int) $db->query(
    "SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'general_maestro_insumos'
       AND COLUMN_NAME IN ('clasificado_por','clasificado_at')",
)->fetchColumn();
$assert($cols === 2, 'Las dos columnas de auditoría de clasificación existen.');

// Lo migrado no finge tener autor: es lo que permite al importador SINCO saber que puede pisarlo.
$migradosConAutor = (int) $db->query(
    'SELECT COUNT(*) FROM general_maestro_insumos WHERE tipo_recurso = ? AND clasificado_at IS NOT NULL',
    [TipoRecursoEquipo::SIN_CLASIFICAR],
)->fetchColumn();
$assert($migradosConAutor === 0, 'Lo migrado no finge tener autor: clasificado_at sigue NULL.');

// Punto 5, cara «presupuesto»: la vía del presupuesto crea filas del maestro SIN tipo_recurso, así
// que un re-import no puede degradar una clasificación. Se comprueba sobre los INSERT reales, no
// sobre la presencia del string en el archivo — desde la Ola 2 este servicio SÍ escribe
// `tipo_recurso`, pero sólo en `clasificarEquipos()`, que es una decisión humana explícita.
$fuente = file_get_contents(__DIR__ . '/../src/Services/Pdc/MaestroInsumosService.php');
$assert($fuente !== false, 'Se puede leer MaestroInsumosService para inspeccionar sus INSERT.');
preg_match_all('/INSERT INTO general_maestro_insumos\s*\(([^)]*)\)/i', (string) $fuente, $m);
$assert($m[1] !== [], 'Se encontraron los INSERT al maestro (' . count($m[1]) . ').');
$insertConTipo = array_filter($m[1], static fn (string $cols): bool => stripos($cols, 'tipo_recurso') !== false);
$assert(
    $insertConTipo === [],
    'Ningún INSERT de la vía del presupuesto escribe tipo_recurso: un re-import no puede pisar la clasificación.',
);
// Y sólo un método escribe la columna: el de clasificar a mano.
$escrituras = preg_match_all('/SET\s+tipo_recurso\s*=/i', (string) $fuente);
$assert($escrituras === 1, "Sólo una escritura de tipo_recurso en todo el servicio, la de clasificar a mano (hay {$escrituras}).");

// Reetiquetar el tipo de recurso NO puede alterar la cola de vínculos: `reengancharPendientes()` y
// el auto-match emparejan por `descripcion_norm` + `unidad`. Sin esta independencia, la migración
// tendría que re-enganchar después de tocar el catálogo; con ella, no hay nada que re-enganchar.
$assert(
    $fuente !== false && str_contains((string) $fuente, 'reengancharPendientes'),
    'reengancharPendientes() sigue existiendo (llegó con main el 2026-07-29).',
);
$vinculosPorTipo = (int) $db->query(
    "SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pdc_insumo_vinculos'
       AND COLUMN_NAME = 'tipo_recurso'",
)->fetchColumn();
$assert($vinculosPorTipo === 0, 'pdc_insumo_vinculos no tiene tipo_recurso: reetiquetar equipos no puede tocar un vínculo.');

// ---------------------------------------------------------------------------------------------
// 3) La cola y la clasificación en lote (puntos 1, 2 y 3).
// ---------------------------------------------------------------------------------------------

fwrite(STDOUT, "\n== La cola y la clasificación en lote\n");

$maestro = new MaestroInsumosService($db);

/** Siembra un equipo de prueba y devuelve su id. */
$sembrar = static function (string $desc, string $agrupacion, string $tipo) use ($db): int {
    $db->query(
        'INSERT INTO general_maestro_insumos
            (descripcion, descripcion_norm, unidad, tipo_insumo, agrupacion, tipo_recurso, activo, creado_por, created_at)
         VALUES (?, ?, ?, ?, ?, ?, 1, ?, NOW())',
        [$desc, MaestroInsumosService::normalizar($desc), 'UN', $agrupacion, $agrupacion, $tipo, PDC_EQ_MARCA],
    );
    return (int) $db->query('SELECT LAST_INSERT_ID()')->fetchColumn();
};

$colaAntes = $maestro->equiposSinClasificar()['total'];

// 25 sembrados: 12 con pista de alquiler, 8 con pista de compra, 5 sin pista.
$sembrados = [];
for ($i = 1; $i <= 12; $i++) {
    $sembrados[] = $sembrar("EQ PRUEBA ALQ {$i}", 'ALQUILER MAQUINARIA Y EQUIPOS', TipoRecursoEquipo::SIN_CLASIFICAR);
}
for ($i = 1; $i <= 8; $i++) {
    $sembrados[] = $sembrar("EQ PRUEBA COMP {$i}", 'COMPRA ELEMENTOS- MAQUINARIA Y EQUIPO', TipoRecursoEquipo::SIN_CLASIFICAR);
}
for ($i = 1; $i <= 5; $i++) {
    $sembrados[] = $sembrar("EQ PRUEBA MUDO {$i}", 'MTTO COMPRA MAQUINARIA Y EQUIPO', TipoRecursoEquipo::SIN_CLASIFICAR);
}

$cola = $maestro->equiposSinClasificar();
$assert($cola['total'] === $colaAntes + 25, "La cola creció en los 25 sembrados: {$colaAntes} → {$cola['total']}.");

// Punto 2: el total cuadra con la BD, no es un conteo de la página.
$enBd = (int) $db->query(
    'SELECT COUNT(*) FROM general_maestro_insumos WHERE tipo_recurso = ? AND activo = 1',
    [TipoRecursoEquipo::SIN_CLASIFICAR],
)->fetchColumn();
$assert($cola['total'] === $enBd, "El total de la cola ({$cola['total']}) cuadra con la BD ({$enBd}).");

// La pista viaja con la evidencia que la justifica, y NO está escrita en tipo_recurso.
$porId = [];
foreach ($cola['items'] as $it) {
    $porId[$it['id']] = $it;
}
$unAlq = $porId[$sembrados[0]] ?? null;
$assert($unAlq !== null, 'Un equipo sembrado aparece en la cola.');
$assert($unAlq !== null && $unAlq['pista'] === TipoRecursoEquipo::ALQUILADO, 'El sembrado con agrupación de alquiler trae la pista de alquilado.');
$assert($unAlq !== null && $unAlq['agrupacion'] === 'ALQUILER MAQUINARIA Y EQUIPOS', 'Trae también la agrupación que justifica la pista: la evidencia se muestra, no se esconde.');

$unMudo = $porId[$sembrados[20]] ?? null;
$assert($unMudo !== null && $unMudo['pista'] === null, 'El sembrado con MTTO no trae pista: nadie adivina por él.');

// La pista NO se escribió en la BD: sigue en tránsito.
$tipoReal = $db->query('SELECT tipo_recurso FROM general_maestro_insumos WHERE id = ?', [$sembrados[0]])->fetchColumn();
$assert($tipoReal === TipoRecursoEquipo::SIN_CLASIFICAR, 'La pista sugiere pero NO escribe: el insumo sigue sin clasificar en la BD.');

// Preordenada: lo que trae pista va primero (ese es el lote que se resuelve de golpe).
$assert($cola['items'][0]['pista'] !== null, 'La cola viene preordenada: lo que tiene pista primero.');

// Punto 3: clasificar 20 de golpe, y la cola baja en 20.
$antes = $cola['total'];
$lote = array_slice($sembrados, 0, 20);
$res = $maestro->clasificarEquipos($lote, TipoRecursoEquipo::COMPRADO, 'test@equipo');
$assert($res['ok'] === true, 'Clasificar 20 de golpe funciona.');
$assert($res['clasificados'] === 20, "Se clasificaron 20 (dice {$res['clasificados']}).");

$despues = $maestro->equiposSinClasificar()['total'];
$assert($despues === $antes - 20, "La cola bajó en 20: {$antes} → {$despues}.");

// Punto 1, cara «sobrevive a recargar»: está en la BD, no en memoria.
$fila = $db->query(
    'SELECT tipo_recurso, clasificado_por, clasificado_at FROM general_maestro_insumos WHERE id = ?',
    [$lote[0]],
)->fetch(PDO::FETCH_ASSOC);
$assert($fila['tipo_recurso'] === TipoRecursoEquipo::COMPRADO, 'El tipo quedó persistido en la BD.');
$assert($fila['clasificado_por'] === 'test@equipo', 'Quedó registrado quién clasificó.');
$assert($fila['clasificado_at'] !== null, 'Quedó registrado cuándo: es lo que hace que SINCO lo respete.');

// Destinos inválidos se rechazan.
$r = $maestro->clasificarEquipos([$lote[0]], TipoRecursoEquipo::SIN_CLASIFICAR, 'test@equipo');
$assert($r['ok'] === false && ($r['code'] ?? '') === 'DESTINO_INVALIDO', 'No se puede clasificar HACIA sin clasificar.');
$r = $maestro->clasificarEquipos([$lote[0]], 'MATERIAL', 'test@equipo');
$assert($r['ok'] === false && ($r['code'] ?? '') === 'DESTINO_INVALIDO', 'No se convierte un equipo en material por esta puerta.');
$r = $maestro->clasificarEquipos([], TipoRecursoEquipo::COMPRADO, 'test@equipo');
$assert($r['ok'] === false && ($r['code'] ?? '') === 'SIN_IDS', 'Un lote vacío se rechaza.');

// Corregir una clasificación equivocada sí se permite, y re-sella la auditoría.
$r = $maestro->clasificarEquipos([$lote[0]], TipoRecursoEquipo::ALQUILADO, 'otro@equipo');
$assert($r['ok'] === true && $r['clasificados'] === 1, 'Se puede corregir una clasificación equivocada.');
$fila2 = $db->query('SELECT tipo_recurso, clasificado_por FROM general_maestro_insumos WHERE id = ?', [$lote[0]])
    ->fetch(PDO::FETCH_ASSOC);
$assert(
    $fila2['tipo_recurso'] === TipoRecursoEquipo::ALQUILADO && $fila2['clasificado_por'] === 'otro@equipo',
    'La corrección queda con su nuevo autor.',
);

// Un insumo que NO es equipo no se toca ni por error de la SPA.
$materialId = $sembrar('MAT PRUEBA NO EQUIPO', 'MAT-ACABADOS', 'MATERIAL');
$r = $maestro->clasificarEquipos([$materialId], TipoRecursoEquipo::COMPRADO, 'test@equipo');
$assert($r['clasificados'] === 0, 'Un MATERIAL no se clasifica como equipo: el filtro es por tipo, no por id suelto.');
$sigue = $db->query('SELECT tipo_recurso FROM general_maestro_insumos WHERE id = ?', [$materialId])->fetchColumn();
$assert($sigue === 'MATERIAL', 'Y sigue siendo MATERIAL.');

// ---------------------------------------------------------------------------------------------
// 4) Punto 5: reimportar SINCO no borra la clasificación humana.
// ---------------------------------------------------------------------------------------------

fwrite(STDOUT, "\n== Punto 5: reimportar SINCO no borra la clasificación humana\n");

// `resolverTipoRecurso()` es una función pura: no toca BD ni el store, así que se instancia sin
// constructor en vez de fabricar un PresupuestoImportStore y un parser que no se van a usar.
$svcSinco = (new ReflectionClass(\App\Services\Pdc\MaestroSincoImportService::class))->newInstanceWithoutConstructor();
$reflex = new ReflectionMethod($svcSinco, 'resolverTipoRecurso');
$reflex->setAccessible(true);
$resolver = static fn (?string $entrante, ?string $guardado, ?string $at): ?string
    => $reflex->invoke($svcSinco, $entrante, $guardado, $at);

$ALQ = TipoRecursoEquipo::ALQUILADO;
$COM = TipoRecursoEquipo::COMPRADO;
$SIN = TipoRecursoEquipo::SIN_CLASIFICAR;
$GEN = TipoRecursoEquipo::GENERICO;

// El caso que rompe el punto 5: un humano dijo «comprado», SINCO vuelve a mandar el genérico.
$assert($resolver($GEN, $COM, '2026-07-29 10:00:00') === $COM, 'SINCO manda EQUIPO sobre un equipo clasificado a mano: gana la persona.');
$assert($resolver($GEN, $ALQ, '2026-07-29 10:00:00') === $ALQ, 'Igual con alquilado: gana la persona.');
$assert($resolver($SIN, $COM, '2026-07-29 10:00:00') === $COM, 'SINCO mandando «sin clasificar» tampoco degrada una clasificación.');

// Sin autor humano, SINCO manda: la migración dejó clasificado_at NULL a propósito.
$assert($resolver($GEN, $SIN, null) === $GEN, 'Sobre una fila migrada (sin autor), SINCO escribe con normalidad.');

// Si SINCO se pone MÁS específico, gana SINCO: es dato nuevo, no una degradación.
$assert($resolver($ALQ, $SIN, null) === $ALQ, 'SINCO trayendo ALQUILER EQUIPOS sobre un sin clasificar sí escribe: gana precisión.');
$assert($resolver($ALQ, $COM, '2026-07-29 10:00:00') === $ALQ, 'Si SINCO trae un valor YA clasificado, gana SINCO: es una corrección de la fuente.');

// Fuera de los equipos, nada cambia.
$assert($resolver('MATERIAL', 'SUBCONTRATO', '2026-07-29 10:00:00') === 'MATERIAL', 'En tipos que no son equipo el importador sigue mandando, como siempre.');
$assert($resolver('MATERIAL', $COM, '2026-07-29 10:00:00') === 'MATERIAL', 'Si SINCO reclasifica un equipo a material, se respeta: dejó de ser equipo.');

// Y el blindaje de verdad, sobre la BD: fila clasificada a mano + UPDATE del importador por código.
$conCodigo = $sembrar('EQ PRUEBA BLINDAJE', 'ALQUILER MAQUINARIA Y EQUIPOS', TipoRecursoEquipo::SIN_CLASIFICAR);
$db->query('UPDATE general_maestro_insumos SET codigo_sinco = ? WHERE id = ?', ['TEST-EQ-9001', $conCodigo]);
$maestro->clasificarEquipos([$conCodigo], TipoRecursoEquipo::COMPRADO, 'humano@equipo');

$guardada = $db->query('SELECT tipo_recurso, clasificado_at FROM general_maestro_insumos WHERE id = ?', [$conCodigo])
    ->fetch(PDO::FETCH_ASSOC);
$decidido = $resolver($GEN, $guardada['tipo_recurso'], $guardada['clasificado_at']);
$assert(
    $decidido === TipoRecursoEquipo::COMPRADO,
    'Con la fila real en BD: el importador resuelve conservar la clasificación humana, no degradarla.',
);

// Y en comportamiento, no sólo en el código: re-enganchar la cola de vínculos —lo que corre tras
// cargar el maestro SINCO— no altera el tipo de recurso de una fila ya clasificada.
$maestro->reengancharPendientes();
$trasReenganche = $db->query('SELECT tipo_recurso, clasificado_por FROM general_maestro_insumos WHERE id = ?', [$conCodigo])
    ->fetch(PDO::FETCH_ASSOC);
$assert(
    $trasReenganche['tipo_recurso'] === TipoRecursoEquipo::COMPRADO && $trasReenganche['clasificado_por'] === 'humano@equipo',
    'reengancharPendientes() no toca la clasificación: opera sobre vínculos, no sobre el tipo de recurso.',
);

// ---------------------------------------------------------------------------------------------

$limpiar();

fwrite(STDOUT, "\n" . ($fallos === 0 ? "TODO OK\n" : "{$fallos} FALLOS\n"));
exit($fallos === 0 ? 0 : 1);
