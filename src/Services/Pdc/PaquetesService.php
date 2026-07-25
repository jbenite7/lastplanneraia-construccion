<?php

namespace App\Services\Pdc;

/**
 * Paquetes de contratación (PDC v2 / Fase A3): catálogo global reutilizable +
 * asignación insumo→paquete por proyecto clavada por (norma, unidad) — el
 * re-import hereda gratis. Cada insumo tiene un único destino: asignado a un
 * paquete (paquete_id NOT NULL, omitido=0) u omitido (paquete_id NULL, omitido=1).
 * El motor de sugerencias agrega sobre la propia asignación entre proyectos
 * (sin tabla nueva), siempre con confirmación humana.
 */
final class PaquetesService
{
    public const TIPOS = ['a_todo_costo', 'mano_obra', 'suministro', 'consumibles'];

    /** Paquete bucket para insumos no empaquetables (A3.1). */
    public const PAQUETE_INDIRECTOS = 'Indirectos / Administración';

    /** Keywords (ya normalizadas) que marcan un insumo como indirecto/administrativo. */
    private const KEYWORDS_INDIRECTOS = [
        'IMPREVISTO', 'NOMINA', 'DOTACION', 'PAPELERIA', 'FOTOCOPIA', 'UTILES', 'CAFETERIA',
        'ASEO', 'VIGILANCIA', 'HONORARIO', 'ADMINISTRA', 'GASTOS MEDICOS', 'DROGAS',
        'ELEMENTOS DE ASEO', 'EQUIPO DE OFICINA', 'EQ DE COMPUTO', 'COMUNICACIONES',
    ];

    /** Veto: la regla declara que NO debe proponerse nada (queda pendiente para decisión humana). */
    public const SIN_PROPUESTA = '__SIN_PROPUESTA__';

    /**
     * Reglas de dominio para el sembrado (A3.1) — taxonomía por OFICIO CONTRATABLE.
     *
     * PRINCIPIO RECTOR (usuario, 2026-07-25): un paquete es el alcance que ejecuta UN contratista
     * especializado. La pregunta de corte es siempre «¿lo haría el mismo gremio, con la misma
     * expertise y herramienta?». Si no, son paquetes distintos (piso cerámico ≠ piso en madera ≠
     * deck; mediacaña de cubierta ≠ instalación de pisos). Un paquete que mezcla oficios es
     * incontratable: nadie puede cotizarlo completo.
     *
     * Estructura de una regla:
     *   kw          keywords (normalizadas, SIN Ñ ni tildes: normalizar() las elimina) que disparan la regla.
     *   ctx         contexto OBLIGATORIO: además del kw, el heno debe contener alguno de estos (separa
     *               «porcelanato en piso» de «porcelanato en muro» sin duplicar diccionarios).
     *   paq         paquete destino, o self::SIN_PROPUESTA para vetar (dejar pendiente).
     *   tipos       tipo_recurso admitidos (vacío = cualquiera).
     *   soloDesc    la regla solo mira la descripción del insumo (materiales: el nombre identifica el producto).
     *   descPrimero la regla se evalúa en la pasada 1 sobre la descripción: cuando el propio nombre
     *               nombra el oficio/material, manda sobre la actividad padre.
     *
     * Orden = prioridad: gana la PRIMERA regla que casa.
     */
    private const REGLAS_SEMBRADO = [
        // ── Objetos físicos inequívocos: van primero para que no los capturen reglas de instalación ──
        ['kw' => ['CORTA FUEGO', 'CORTAFUEGO'], 'paq' => 'Sum + Inst PUERTAS CORTAFUEGO', 'tipos' => ['SUBCONTRATO', 'MATERIAL'], 'descPrimero' => true],
        ['kw' => ['PASAMANO', 'BARANDA', 'RODAMANOS'], 'paq' => 'Sum + Inst BARANDAS Y PASAMANOS', 'tipos' => ['SUBCONTRATO', 'MATERIAL', 'MANO DE OBRA'], 'descPrimero' => true],
        ['kw' => ['PERGOLA', 'CERCHA', 'ESTRUCTURA METALICA'], 'paq' => 'Sum + Inst ESTRUCTURA METÁLICA', 'tipos' => ['SUBCONTRATO'], 'descPrimero' => true],
        // Seguridad en alturas: certificador con matrícula, no el instalador de anclaje epóxico.
        ['kw' => ['LINEA DE VIDA', 'ANTIPENDULO', 'ANCLAJE CERTIFICADO', 'ANCLAJES CERTIFICADOS'], 'paq' => 'Sum + Inst SEGURIDAD HUMANA (LINEAS LONGITUDINALES Y TRANSVERSALES, PUNTOS ANTIPÉNDULO)', 'tipos' => ['SUBCONTRATO', 'MATERIAL'], 'descPrimero' => true],

        // ── Trámites y servicios: tienen su propio proceso de contratación, no son «indirectos» ──
        ['kw' => ['RETILAP', 'CERTIFICACION RETIE', 'INSPECCION RETIE'], 'paq' => 'Sum + Inst CERTIFICACION RETILAP Y RETIE', 'tipos' => ['SUBCONTRATO'], 'descPrimero' => true],
        ['kw' => ['VIGILANCIA'], 'paq' => 'Sum + Inst SERVICIO DE VIGILANCIA', 'tipos' => ['SUBCONTRATO'], 'descPrimero' => true],
        ['kw' => ['ASEO'], 'paq' => 'Sum + Inst ASEO FINAL DE OBRA', 'tipos' => ['SUBCONTRATO'], 'descPrimero' => true],
        ['kw' => ['LAVADA', 'HIDROFUG'], 'ctx' => ['FACHADA'], 'paq' => 'Sum + Inst LAVADA E HIDROFUGADA DE FACHADAS', 'tipos' => ['SUBCONTRATO'], 'descPrimero' => true],
        ['kw' => ['DEMARCACION', 'NUMERACION'], 'ctx' => ['PARQUEADERO', 'VIAL', 'CELDA'], 'paq' => 'Sum + Inst PINTURA DEMARCACIÓN, NUMERACIÓN Y FLECHAS DE PARQUEADEROS', 'tipos' => ['SUBCONTRATO', 'MANO DE OBRA'], 'descPrimero' => true],
        ['kw' => ['NOMENCLATURA', 'SENALIZACION', 'AVISO'], 'paq' => 'Sum + Inst SEÑALIZACIÓN', 'tipos' => ['SUBCONTRATO', 'MATERIAL', 'MANO DE OBRA'], 'descPrimero' => true],
        ['kw' => ['POLISOMBRA', 'INVERNADERO', 'PROTECCION TEMPORAL', 'PROTECCION CON PLASTICO'], 'paq' => self::PAQUETE_INDIRECTOS, 'tipos' => ['SUBCONTRATO', 'MATERIAL'], 'descPrimero' => true],

        // ── Instalaciones (subcontrato / a todo costo) ──
        // Impermeabilización antes que ascensores: el ascensorista no impermeabiliza el foso.
        ['kw' => ['IMPERMEABILIZ'], 'ctx' => ['FOSO', 'ASCENSOR'], 'paq' => 'Sum + Inst IMPERMEABILIZACIÓN FOSO DE ASCENSOR', 'tipos' => ['SUBCONTRATO', 'MANO DE OBRA', 'MATERIAL']],
        ['kw' => ['IMPERMEABILIZ'], 'paq' => 'Sum + Inst IMPERMEABILIZACIONES', 'tipos' => ['SUBCONTRATO', 'MANO DE OBRA', 'MATERIAL']],
        // Aparatos sanitarios en M.O. antes que la red hidrosanitaria: es el instalador de aparatos.
        ['kw' => ['REJILLA DE PISO', 'REJILLA PARA DUCHA', 'SIFON', 'CROMAD', 'GRIFERIA', 'LAVAMANOS', 'LAVAPLATOS', 'ORINAL', 'APARATO SANITARIO'], 'paq' => 'M. de O APARATOS SANITARIOS Y GRIFERÍA', 'tipos' => ['MANO DE OBRA'], 'descPrimero' => true],
        ['kw' => ['INSTALACION ELECTRIC', 'ELECTRIC', 'ILUMINACION', 'VOZ Y DATOS'], 'paq' => 'Sum + Inst INSTALACIONES ELÉCTRICAS, VOZ Y DATOS (INTERIORES)', 'tipos' => ['SUBCONTRATO', 'MANO DE OBRA']],
        ['kw' => ['HIDROSANITARI', 'HIDRAULIC', 'SANITARIA', 'DESAGUE', 'TUBERIA PVC', 'RED DE AGUA', 'GARGOLA'], 'paq' => 'Sum + Inst INSTALACIONES HIDROSANITARIAS', 'tipos' => ['SUBCONTRATO', 'MANO DE OBRA', 'MATERIAL']],
        ['kw' => ['RED DE GAS', 'GAS DOMICILIAR', 'INSTALACION DE GAS', 'GAS NATURAL'], 'paq' => 'Sum + Inst RED DE GAS', 'tipos' => ['SUBCONTRATO', 'MANO DE OBRA']],
        ['kw' => ['RED CONTRA INCENDIO', 'DETECCION', 'EXTINCION', 'ROCIADOR'], 'paq' => 'Sum + Inst RED CONTRA INCENDIO, DETECCIÓN Y EXTINCIÓN', 'tipos' => ['SUBCONTRATO']],
        ['kw' => ['AIRE ACONDICIONADO', 'EXTRACCION', 'VENTILACION MECANIC'], 'paq' => 'Sum + Inst RED DE AIRE ACONDICIONADO Y EQUIPOS DE EXTRACCIÓN', 'tipos' => ['SUBCONTRATO']],
        ['kw' => ['ASCENSOR'], 'paq' => 'Sum + Inst ASCENSORES', 'tipos' => ['SUBCONTRATO']],
        // Cielos rasos ANTES de pintura y ventanería: el drywallero entrega el cielo pintado.
        ['kw' => ['CIELO', 'DRYWALL', 'SUPERBOARD', 'CIELORRASO', 'FALSO'], 'paq' => 'Sum + Inst CIELOS RASOS', 'tipos' => ['SUBCONTRATO', 'MANO DE OBRA']],
        ['kw' => ['CLOSET', 'MUEBLE', 'COCINA INTEGRAL', 'MOBILIARIO', 'VESTIER', 'ALACENA', 'LINO'], 'paq' => 'Sum + Inst CARPINTERÍA DE MADERA', 'tipos' => ['SUBCONTRATO']],
        // La puerta de madera es producto de catálogo; el closet es medida y despiece: gremios distintos.
        ['kw' => ['PUERTA EN MADERA', 'PUERTA MADERA', 'PUERTA CORREDIZA MADERA'], 'paq' => 'Sum + Inst PUERTAS EN MADERA', 'tipos' => ['SUBCONTRATO']],
        ['kw' => ['CARPINTERIA MADERA'], 'paq' => 'Sum + Inst CARPINTERÍA DE MADERA', 'tipos' => ['SUBCONTRATO']],
        ['kw' => ['CABINA', 'DIVISION DE DUCHA', 'DIVISIONES DE BANO', 'ESPEJO'], 'ctx' => ['BANO', 'DUCHA', 'APTO', 'VIDRIO TEMPLADO'], 'paq' => 'Sum + Inst CABINAS Y ESPEJOS DE BAÑOS', 'tipos' => ['SUBCONTRATO', 'MATERIAL'], 'descPrimero' => true],
        ['kw' => ['VENTAN', 'VIDRIO', 'VIDRIER', 'FACHADA FLOTANTE', 'ALUMINIO'], 'paq' => 'Sum + Inst VENTANERÍA', 'tipos' => ['SUBCONTRATO']],
        ['kw' => ['CARPINTERIA METAL', 'PUERTA METAL', 'PUERTA ACCESO', 'PUERTA BATIENTE', 'PUERTA', 'REJA'], 'paq' => 'Sum + Inst CARPINTERÍA METÁLICA', 'tipos' => ['SUBCONTRATO']],

        // ── Mano de obra por OFICIO ──
        ['kw' => ['MAMPOSTERIA', 'MURO EN LADRILLO', 'MURO EN BLOQUE', 'MURO EN CATALAN', 'MURO EN CONCRETO', 'MURO LADRILLO'], 'paq' => 'M. de O MAMPOSTERÍA', 'tipos' => ['MANO DE OBRA', 'SUBCONTRATO']],
        // Estuco ANTES que revoque: «ESTUCO SOBRE REVOQUE» es del estucador, no del revocador.
        // Los subcontratos de estuco/pintura incluyen material → a todo costo, no mano de obra.
        ['kw' => ['ESTUCO'], 'paq' => 'Sum + Inst ESTUCO', 'tipos' => ['SUBCONTRATO'], 'descPrimero' => true],
        ['kw' => ['ESTUCO'], 'paq' => 'M. de O ESTUCO Y PINTURA', 'tipos' => ['MANO DE OBRA'], 'descPrimero' => true],
        ['kw' => ['PINTURA', 'VINILO', 'KORAZA', 'ESMALTE', 'ANTICORROSIV'], 'ctx' => ['FACHADA', 'EXTERIOR', 'KORAZA', 'POSTE', 'PISCINA'], 'paq' => 'Sum + Inst PINTURA FACHADA', 'tipos' => ['SUBCONTRATO'], 'descPrimero' => true],
        ['kw' => ['PINTURA', 'VINILO', 'ESMALTE', 'ACRILIC'], 'paq' => 'Sum + Inst PINTURA INTERIOR', 'tipos' => ['SUBCONTRATO'], 'descPrimero' => true],
        ['kw' => ['PINTURA', 'VINILO'], 'paq' => 'M. de O ESTUCO Y PINTURA', 'tipos' => ['MANO DE OBRA'], 'descPrimero' => true],
        // Revoque de fachada: otra cuadrilla, con andamio y rendimientos distintos.
        ['kw' => ['REVOQUE', 'PANETE', 'REPELLO', 'DILATACION'], 'ctx' => ['FACHADA', 'EXTERIOR'], 'paq' => 'M. de O REVOQUE DE FACHADA', 'tipos' => ['MANO DE OBRA', 'SUBCONTRATO'], 'descPrimero' => true],
        ['kw' => ['REVOQUE', 'PANETE', 'REPELLO'], 'paq' => 'M. de O REVOQUE INTERIOR', 'tipos' => ['MANO DE OBRA', 'SUBCONTRATO'], 'descPrimero' => true],
        ['kw' => ['ACERO', 'REFUERZO', 'FIGURAD', 'AMARRE Y COLOCACION', 'MALLA ELECTROSOLDADA'], 'paq' => 'M. de O ESTRUCTURA EN CONCRETO', 'tipos' => ['MANO DE OBRA'], 'descPrimero' => true],
        // Pilotaje: equipo de perforación y lodos; el descabece es otra cuadrilla.
        ['kw' => ['DESCABECE'], 'paq' => 'M. de O DESCABECE DE PILOTES', 'tipos' => ['MANO DE OBRA', 'SUBCONTRATO']],
        ['kw' => ['PILOTE', 'CAISSON', 'PILOTAJE', 'INCLUSION'], 'paq' => 'M. de O CIMENTACIÓN PROFUNDA EN CONCRETO', 'tipos' => ['MANO DE OBRA', 'SUBCONTRATO']],
        ['kw' => ['CIMENTACION', 'ZAPATA', 'DADO', 'VIGA DE FUNDACION'], 'paq' => 'M. de O CIMENTACIÓN SUPERFICIAL EN CONCRETO', 'tipos' => ['MANO DE OBRA', 'SUBCONTRATO']],
        ['kw' => ['EXCAVACION', 'RELLENO', 'MOVIMIENTO DE TIERRA', 'DESCAPOTE', 'MOVIMIENTOS DE TIERRA', 'LLENO'], 'paq' => 'M. de O MOVIMIENTOS DE TIERRA (EXCAVACIONES Y RELLENOS)', 'tipos' => ['MANO DE OBRA', 'SUBCONTRATO', 'EQUIPO']],
        ['kw' => ['DEMOLICION', 'DEMOLER'], 'paq' => 'M. de O DEMOLICIONES', 'tipos' => ['MANO DE OBRA', 'SUBCONTRATO']],
        ['kw' => ['LOSA', 'PLACA', 'COLUMNA', 'VIGA', 'ESTRUCTURA EN CONCRETO', 'PANTALLA', 'ENTREPISO', 'ESCALERA EN CONCRETO', 'FUNDIDA', 'CONCRETO ALIGERAD'], 'paq' => 'M. de O ESTRUCTURA EN CONCRETO', 'tipos' => ['MANO DE OBRA', 'SUBCONTRATO']],
        // Piso industrial: cuadrilla de allanadora y corte, no la de losa aérea.
        ['kw' => ['PISO INDUSTRIAL', 'PISO EN CONCRETO', 'ENDURECEDOR', 'CORTE DE PISO'], 'paq' => 'M. de O PISOS INDUSTRIALES EN CONCRETO', 'tipos' => ['MANO DE OBRA', 'SUBCONTRATO'], 'descPrimero' => true],
        ['kw' => ['MORTERO DE PISO', 'ALISTADO', 'MORTERO DE NIVELACION', 'AFINADO DE PISO'], 'paq' => 'M. de O MORTEROS DE PISO', 'tipos' => ['MANO DE OBRA', 'SUBCONTRATO']],
        // Mediacaña de CUBIERTA: obra civil previa a impermeabilización (no es instalador de pisos).
        ['kw' => ['MEDIA CANA', 'MEDIACANA', 'REGATA'], 'ctx' => ['CUBIERTA', 'IMPERMEABILIZ', 'TERRAZA'], 'paq' => 'Sum + Inst IMPERMEABILIZACIÓN DE CUBIERTA', 'tipos' => ['MANO DE OBRA', 'SUBCONTRATO']],

        // Acabado de piso SEPARADO POR OFICIO: el instalador de cerámico no es el de laminado ni el
        // de deck. El zócalo viaja con el piso de SU material (mismo gremio). Sin material → veto.
        ['kw' => ['DECK'], 'paq' => 'M. de O INSTALACIÓN DE PISO PARA DECK', 'tipos' => ['MANO DE OBRA', 'SUBCONTRATO'], 'descPrimero' => true],
        ['kw' => ['MADERA', 'LAMINAD'], 'ctx' => ['PISO', 'ZOCALO', 'GUARDAESCOBA', 'PIRLAN'], 'paq' => 'M. de O INSTALACIÓN DE PISOS EN MADERA', 'tipos' => ['MANO DE OBRA', 'SUBCONTRATO'], 'descPrimero' => true],
        ['kw' => ['PORCELANATO', 'CERAMIC', 'GRES', 'TABLETA', 'BALDOSA', 'ADOQUIN'], 'ctx' => ['PISO', 'ZOCALO', 'GUARDAESCOBA', 'PIRLAN', 'ADOQUIN'], 'paq' => 'M. de O INSTALACIÓN DE PISOS CERÁMICOS', 'tipos' => ['MANO DE OBRA', 'SUBCONTRATO'], 'descPrimero' => true],
        // Mediacaña sin contexto de cubierta: pendiente explícito (no la rellenan tokens/agrupación).
        ['kw' => ['MEDIA CANA', 'MEDIACANA'], 'paq' => self::SIN_PROPUESTA, 'tipos' => ['MANO DE OBRA', 'SUBCONTRATO']],
        // Enchape de MURO (el de piso ya se resolvió arriba).
        ['kw' => ['ENCHAPE', 'CERAMIC', 'PORCELANATO', 'BALDOSA', 'GRES', 'PUENTE ADHERENCIA'], 'paq' => 'M. de O ENCHAPES CERÁMICOS', 'tipos' => ['MANO DE OBRA', 'SUBCONTRATO']],
        ['kw' => ['PREPARACION MEZCLA', 'TRANSPORTE INTERNO', 'MEZCLA', 'PREPARACION DE CONCRETO'], 'paq' => 'M. de O ESTRUCTURA EN CONCRETO', 'tipos' => ['MANO DE OBRA']],

        // ── Materiales (suministro) — SOLO por la descripción: el nombre identifica el producto ──
        // Prefabricados antes que CONCRETO: su negociación no es volumen de mixer.
        ['kw' => ['CORDON', 'BORDILLO', 'SARDINEL'], 'paq' => 'Suministro BORDILLOS', 'tipos' => ['MATERIAL'], 'soloDesc' => true],
        ['kw' => ['LAGRIMAL', 'SILLAR', 'PREFABRICADO'], 'paq' => 'Suministro PREFABRICADOS URBANISMO Y EXTERIORES', 'tipos' => ['MATERIAL'], 'soloDesc' => true],
        ['kw' => ['ALIGERANTE', 'PORON'], 'paq' => 'Suministro ALIGERANTES LOSAS', 'tipos' => ['MATERIAL', 'SUBCONTRATO'], 'descPrimero' => true],
        ['kw' => ['MORTERO', 'GROUTING'], 'paq' => 'Suministro MORTEROS', 'tipos' => ['MATERIAL'], 'soloDesc' => true],
        ['kw' => ['LADRILLO', 'BLOQUE', 'ADOBE', 'CATALAN'], 'paq' => 'Suministro LADRILLO', 'tipos' => ['MATERIAL'], 'soloDesc' => true],
        ['kw' => ['ACERO', 'REFUERZO', 'ALAMBRE', 'MALLA ELECTROSOLDADA', 'FLEJE', 'VARILLA', 'FIGURAD'], 'paq' => 'Suministro ACERO DE REFUERZO', 'tipos' => ['MATERIAL'], 'soloDesc' => true],
        ['kw' => ['CEMENTO'], 'paq' => 'Suministro CEMENTO', 'tipos' => ['MATERIAL'], 'soloDesc' => true],
        ['kw' => ['ARENA', 'GRAVA', 'TRITURADO', 'AGREGADO', 'RECEBO', 'GRANULAR', 'SUBBASE'], 'paq' => 'Suministro AGREGADOS', 'tipos' => ['MATERIAL'], 'soloDesc' => true],
        ['kw' => ['ZOCALO', 'GUARDAESCOBA', 'PIRLAN'], 'paq' => 'Suministro GUARDAESCOBAS (cerámico/madera/otros)', 'tipos' => ['MATERIAL'], 'soloDesc' => true],
        ['kw' => ['PORCELANATO', 'CERAMIC', 'BALDOSA', 'GRES', 'TABLETA', 'ENCHAPE'], 'paq' => 'Suministro PISOS Y ENCHAPES CERÁMICOS/PORCELANATO', 'tipos' => ['MATERIAL'], 'soloDesc' => true],
        ['kw' => ['SANITARIO', 'LAVAMANOS', 'ORINAL', 'GRIFERIA', 'LAVAPLATOS', 'DUCHA', 'SIFON'], 'paq' => 'Suministro APARATOS SANITARIOS Y GRIFERÍA', 'tipos' => ['MATERIAL'], 'soloDesc' => true],
        ['kw' => ['FORMALETA', 'ENCOFRADO', 'OBRA FALSA', 'TABLERO FENOLIC'], 'paq' => 'Suministro FORMALETA MUROS, LOSAS Y CONTENCIÓN', 'tipos' => ['MATERIAL'], 'soloDesc' => true],
        ['kw' => ['CONCRETO', 'HORMIGON'], 'paq' => 'Suministro CONCRETO', 'tipos' => ['MATERIAL'], 'soloDesc' => true],
    ];

    public function __construct(private readonly \Database $db)
    {
    }

    /** Resuelve la versión (activa por defecto) del proyecto, o null. */
    private function versionDe(int $projectId, ?int $versionId): ?array
    {
        $sql = $versionId === null
            ? 'SELECT id, version_label, activa FROM pdc_presupuesto_versiones WHERE project_id = ? AND activa = 1'
            : 'SELECT id, version_label, activa FROM pdc_presupuesto_versiones WHERE project_id = ? AND id = ?';
        $params = $versionId === null ? [$projectId] : [$projectId, $versionId];
        $row = $this->db->query($sql, $params)->fetch(\PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    /** Paquetes globales activos con su nº de asignaciones (a paquete) en todos los proyectos. */
    public function catalogo(?string $busqueda = null): array
    {
        $where = 'p.activo = 1';
        $params = [];
        if ($busqueda !== null && trim($busqueda) !== '') {
            $where .= ' AND p.nombre_norm LIKE ?';
            $params[] = '%' . addcslashes(MaestroInsumosService::normalizar($busqueda), '\\%_') . '%';
        }
        $rows = $this->db->query(
            "SELECT p.id, p.nombre, p.tipo_negociacion, COUNT(a.id) AS insumos_global
             FROM general_paquetes_contratacion p
             LEFT JOIN pdc_insumo_paquete a ON a.paquete_id = p.id
             WHERE {$where}
             GROUP BY p.id, p.nombre, p.tipo_negociacion
             ORDER BY p.nombre ASC",
            $params,
        )->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(static fn (array $r): array => [
            'id' => (int) $r['id'],
            'nombre' => $r['nombre'],
            'tipoNegociacion' => $r['tipo_negociacion'],
            'insumosGlobal' => (int) $r['insumos_global'],
        ], $rows);
    }

    /** Crea un paquete global; duplicado por nombre_norm devuelve el existente (reactivado si estaba inactivo). */
    public function crearPaquete(string $nombre, string $tipo, string $usuario): array
    {
        $nombre = trim($nombre);
        if ($nombre === '' || !in_array($tipo, self::TIPOS, true)) {
            return ['ok' => false, 'code' => 'PAQUETE_INVALIDO'];
        }
        $norm = mb_substr(MaestroInsumosService::normalizar($nombre), 0, 200);

        $existente = $this->db->query(
            'SELECT id, nombre, tipo_negociacion, activo FROM general_paquetes_contratacion WHERE nombre_norm = ?',
            [$norm],
        )->fetch(\PDO::FETCH_ASSOC);
        if ($existente !== false) {
            if ((int) $existente['activo'] === 0) {
                $this->db->query(
                    'UPDATE general_paquetes_contratacion SET activo = 1, updated_at = NOW() WHERE id = ?',
                    [(int) $existente['id']],
                );
            }
            return ['ok' => true, 'paquete' => [
                'id' => (int) $existente['id'],
                'nombre' => $existente['nombre'],
                'tipoNegociacion' => $existente['tipo_negociacion'],
                'existente' => 1,
            ]];
        }

        try {
            $this->db->query(
                'INSERT INTO general_paquetes_contratacion (nombre, nombre_norm, tipo_negociacion, activo, creado_por, created_at)
                 VALUES (?, ?, ?, 1, ?, NOW())',
                [mb_substr($nombre, 0, 200), $norm, $tipo, $usuario],
            );
        } catch (\PDOException $e) {
            // Carrera: otro proceso lo creó entre el SELECT y el INSERT (errno 1062) → devolver el existente.
            if ((int) ($e->errorInfo[1] ?? 0) !== 1062) {
                throw $e;
            }
            $row = $this->db->query(
                'SELECT id, nombre, tipo_negociacion FROM general_paquetes_contratacion WHERE nombre_norm = ?',
                [$norm],
            )->fetch(\PDO::FETCH_ASSOC);
            return ['ok' => true, 'paquete' => [
                'id' => (int) $row['id'], 'nombre' => $row['nombre'],
                'tipoNegociacion' => $row['tipo_negociacion'], 'existente' => 1,
            ]];
        }
        return ['ok' => true, 'paquete' => [
            'id' => (int) $this->db->lastInsertId(),
            'nombre' => mb_substr($nombre, 0, 200),
            'tipoNegociacion' => $tipo,
            'existente' => 0,
        ]];
    }

    /** Filtra y normaliza la lista de insumos {descripcionNorm, unidad}; descarta elementos malformados. */
    private static function insumosValidos(array $insumos): array
    {
        $out = [];
        foreach ($insumos as $i) {
            if (!is_array($i) || !is_string($i['descripcionNorm'] ?? null) || !is_string($i['unidad'] ?? null)) {
                continue;
            }
            $norm = trim($i['descripcionNorm']);
            $unidad = trim($i['unidad']);
            if ($norm === '' || $unidad === '') {
                continue;
            }
            $out[] = ['norm' => mb_substr($norm, 0, 500), 'unidad' => mb_substr($unidad, 0, 20)];
        }
        return $out;
    }

    /** Asignación masiva insumo→paquete (upsert: reasignar mueve, no duplica; limpia omisión). */
    public function asignar(int $projectId, array $insumos, int $paqueteId, string $usuario): array
    {
        $paquete = $this->db->query(
            'SELECT id FROM general_paquetes_contratacion WHERE id = ? AND activo = 1',
            [$paqueteId],
        )->fetchColumn();
        if ($paquete === false) {
            return ['ok' => false, 'code' => 'PAQUETE_INVALIDO'];
        }
        $validos = self::insumosValidos($insumos);
        // Lotes multi-fila (patrón generarVinculos): evita un round-trip por insumo.
        foreach (array_chunk($validos, 200) as $lote) {
            $valores = implode(', ', array_fill(0, count($lote), '(?, ?, ?, ?, 0, ?, NOW())'));
            $params = [];
            foreach ($lote as $u) {
                array_push($params, $projectId, $u['norm'], $u['unidad'], $paqueteId, $usuario);
            }
            $this->db->query(
                "INSERT INTO pdc_insumo_paquete (project_id, descripcion_norm, unidad, paquete_id, omitido, asignado_por, updated_at)
                 VALUES {$valores}
                 ON DUPLICATE KEY UPDATE paquete_id = VALUES(paquete_id), omitido = 0, asignado_por = VALUES(asignado_por), updated_at = NOW()",
                $params,
            );
        }
        return ['ok' => true, 'asignados' => count($validos)];
    }

    /** Marca insumos como omitidos (no van al plan de compras): paquete_id NULL, omitido=1. */
    public function omitir(int $projectId, array $insumos, string $usuario): array
    {
        $validos = self::insumosValidos($insumos);
        foreach (array_chunk($validos, 200) as $lote) {
            $valores = implode(', ', array_fill(0, count($lote), '(?, ?, ?, NULL, 1, ?, NOW())'));
            $params = [];
            foreach ($lote as $u) {
                array_push($params, $projectId, $u['norm'], $u['unidad'], $usuario);
            }
            $this->db->query(
                "INSERT INTO pdc_insumo_paquete (project_id, descripcion_norm, unidad, paquete_id, omitido, asignado_por, updated_at)
                 VALUES {$valores}
                 ON DUPLICATE KEY UPDATE paquete_id = NULL, omitido = 1, asignado_por = VALUES(asignado_por), updated_at = NOW()",
                $params,
            );
        }
        return ['ok' => true, 'omitidos' => count($validos)];
    }

    /** Quita la asignación u omisión (el insumo vuelve a "sin asignar"). */
    public function desasignar(int $projectId, array $insumos): array
    {
        $validos = self::insumosValidos($insumos);
        $total = 0;
        foreach (array_chunk($validos, 200) as $lote) {
            $tuplas = implode(', ', array_fill(0, count($lote), '(?, ?)'));
            $params = [$projectId];
            foreach ($lote as $u) {
                array_push($params, $u['norm'], $u['unidad']);
            }
            $stmt = $this->db->query(
                "DELETE FROM pdc_insumo_paquete WHERE project_id = ? AND (descripcion_norm, unidad) IN ({$tuplas})",
                $params,
            );
            $total += $stmt->rowCount();
        }
        return ['ok' => true, 'desasignados' => $total];
    }

    /** Insumos únicos de la versión (activa por defecto) con su asignación/omisión, agrupación y tipo de recurso. */
    public function insumosDeVersion(int $projectId, string $filtro = 'todos', ?int $versionId = null): ?array
    {
        $version = $this->versionDe($projectId, $versionId);
        if ($version === null) {
            return null;
        }
        $vid = (int) $version['id'];
        $extra = match ($filtro) {
            'sin_asignar' => ' AND a.id IS NULL',
            'asignados' => ' AND a.paquete_id IS NOT NULL',
            'omitidos' => ' AND a.omitido = 1',
            default => '',
        };
        $rows = $this->db->query(
            "SELECT v.descripcion_norm, v.unidad, v.descripcion_original, v.tipo_insumo,
                    v.cantidad_total, v.valor_total,
                    m.agrupacion, m.tipo_recurso,
                    a.paquete_id, a.omitido, p.nombre AS paquete_nombre
             FROM pdc_insumo_vinculos v
             LEFT JOIN general_maestro_insumos m ON m.id = v.maestro_id
             LEFT JOIN pdc_insumo_paquete a
                    ON a.project_id = v.project_id AND a.descripcion_norm = v.descripcion_norm AND a.unidad = v.unidad
             LEFT JOIN general_paquetes_contratacion p ON p.id = a.paquete_id
             WHERE v.project_id = ? AND v.version_id = ?{$extra}
             ORDER BY v.valor_total DESC",
            [$projectId, $vid],
        )->fetchAll(\PDO::FETCH_ASSOC);
        return [
            'version' => ['id' => $vid, 'label' => $version['version_label']],
            'insumos' => array_map(static fn (array $r): array => [
                'descripcionNorm' => $r['descripcion_norm'],
                'unidad' => $r['unidad'],
                'descripcion' => $r['descripcion_original'],
                'tipoInsumo' => $r['tipo_insumo'],
                'agrupacion' => $r['agrupacion'],
                'tipoRecurso' => $r['tipo_recurso'],
                'cantidadTotal' => (float) $r['cantidad_total'],
                'valorTotal' => (float) $r['valor_total'],
                'paqueteId' => $r['paquete_id'] === null ? null : (int) $r['paquete_id'],
                'paqueteNombre' => $r['paquete_nombre'],
                'omitido' => (int) $r['omitido'],
            ], $rows),
        ];
    }

    /** Cobertura de la meta 100% (asignados + omitidos) + subtotales por paquete sobre la versión activa. */
    public function resumen(int $projectId, ?int $versionId = null): ?array
    {
        $version = $this->versionDe($projectId, $versionId);
        if ($version === null) {
            return null;
        }
        $vid = (int) $version['id'];
        $tot = $this->db->query(
            'SELECT COUNT(*) AS total,
                    SUM(CASE WHEN a.paquete_id IS NOT NULL THEN 1 ELSE 0 END) AS asignados,
                    SUM(CASE WHEN a.omitido = 1 THEN 1 ELSE 0 END) AS omitidos
             FROM pdc_insumo_vinculos v
             LEFT JOIN pdc_insumo_paquete a
                    ON a.project_id = v.project_id AND a.descripcion_norm = v.descripcion_norm AND a.unidad = v.unidad
             WHERE v.project_id = ? AND v.version_id = ?',
            [$projectId, $vid],
        )->fetch(\PDO::FETCH_ASSOC);
        $total = (int) $tot['total'];
        $asignados = (int) $tot['asignados'];
        $omitidos = (int) $tot['omitidos'];
        $porPaquete = $this->db->query(
            'SELECT p.id, p.nombre, p.tipo_negociacion, COUNT(*) AS insumos, SUM(v.valor_total) AS subtotal
             FROM pdc_insumo_vinculos v
             JOIN pdc_insumo_paquete a
                   ON a.project_id = v.project_id AND a.descripcion_norm = v.descripcion_norm AND a.unidad = v.unidad
             JOIN general_paquetes_contratacion p ON p.id = a.paquete_id
             WHERE v.project_id = ? AND v.version_id = ?
             GROUP BY p.id, p.nombre, p.tipo_negociacion
             ORDER BY subtotal DESC',
            [$projectId, $vid],
        )->fetchAll(\PDO::FETCH_ASSOC);
        return [
            'version' => ['id' => $vid, 'label' => $version['version_label']],
            'total' => $total,
            'asignados' => $asignados,
            'omitidos' => $omitidos,
            'cobertura' => $total === 0 ? 0.0 : round(($asignados + $omitidos) * 100 / $total, 1),
            'porPaquete' => array_map(static fn (array $r): array => [
                'paqueteId' => (int) $r['id'],
                'nombre' => $r['nombre'],
                'tipoNegociacion' => $r['tipo_negociacion'],
                'insumos' => (int) $r['insumos'],
                'subtotal' => (float) $r['subtotal'],
            ], $porPaquete),
        ];
    }

    /**
     * Motor de sugerencias para los insumos SIN asignar de la versión (activa por defecto).
     * 3 capas en cascada (la N solo si la N-1 no dio): exacta (alta) → tokens (media) → agrupación (baja).
     * Sin tabla propia: la memoria es pdc_insumo_paquete agregada entre proyectos. Nada se aplica
     * sin confirmación humana (esto solo PRE-marca). La 4ª señal (tipo_recurso) se aplica en el
     * asistente vía candidatosParaPaquete(), donde el usuario ya fijó el tipo de negociación.
     */
    public function sugerencias(int $projectId, ?int $versionId = null): ?array
    {
        $r = $this->proponerSembrado($projectId, $versionId, 'sin_asignar');
        if ($r === null) {
            return null;
        }
        $sugerencias = [];
        foreach ($r['propuestas'] as $p) {
            if ($p['propuesta'] !== null) {
                $sugerencias[] = array_merge(
                    ['descripcionNorm' => $p['descripcionNorm'], 'unidad' => $p['unidad']],
                    $p['propuesta'],
                );
            }
        }
        return ['version' => $r['version'], 'sugerencias' => $sugerencias];
    }

    /**
     * Propuesta de sembrado por insumo (A3.1). Devuelve CADA insumo del filtro con su propuesta
     * (paquete + capa + confianza + evidencia) o null si nada aplicó — útil para explicar el "porqué"
     * incluso de los ya asignados (filtro 'todos') y de los que quedan sin propuesta.
     * Cascada de fuentes (la primera que acierta gana): IA → exacta → reglas → tokens → indirectos → agrupación.
     */
    public function proponerSembrado(int $projectId, ?int $versionId = null, string $filtro = 'todos'): ?array
    {
        $ins = $this->insumosDeVersion($projectId, $filtro, $versionId);
        if ($ins === null) {
            return null;
        }
        $catalogo = $this->catalogoActivoPorNombre();
        $overrides = $this->overridesIA();
        $actMap = $this->actividadDominantePorInsumo($projectId, $versionId);

        $propuestas = [];
        foreach ($ins['insumos'] as $insumo) {
            $clave = $insumo['descripcionNorm'] . '@@' . mb_strtoupper((string) $insumo['unidad']);
            $actividad = $actMap[$clave] ?? '';
            // El veto de reglas corta la cascada: ni tokens ni agrupacion deben rellenar un pendiente.
            $porReglas = $this->sugerirOverrideIA($insumo, $overrides, $catalogo)
                ?? $this->sugerirExacta($projectId, $insumo)
                ?? $this->sugerirPorReglas($insumo, $actividad, $catalogo);
            if (($porReglas['veto'] ?? false) === true) {
                $p = null;
            } else {
                $p = $porReglas
                    ?? $this->sugerirPorTokens($projectId, $insumo)
                    ?? $this->sugerirIndirectos($insumo, $catalogo)
                    ?? $this->sugerirPorAgrupacion($insumo);
            }
            $propuestas[] = [
                'descripcionNorm' => $insumo['descripcionNorm'],
                'unidad' => $insumo['unidad'],
                'descripcion' => $insumo['descripcion'],
                'tipoRecurso' => $insumo['tipoRecurso'],
                'agrupacion' => $insumo['agrupacion'],
                'valorTotal' => $insumo['valorTotal'],
                'actividad' => $actividad,
                'propuesta' => $p,
            ];
        }
        return ['version' => $ins['version'], 'propuestas' => $propuestas];
    }

    /** Capa 1: mismo (norma, unidad) asignado en OTROS proyectos. Consenso = más proyectos. */
    private function sugerirExacta(int $projectId, array $insumo): ?array
    {
        $row = $this->db->query(
            'SELECT a.paquete_id, p.nombre, COUNT(DISTINCT a.project_id) AS proyectos
             FROM pdc_insumo_paquete a
             JOIN general_paquetes_contratacion p ON p.id = a.paquete_id AND p.activo = 1
             WHERE a.descripcion_norm = ? AND a.unidad = ? AND a.project_id <> ? AND a.paquete_id IS NOT NULL
             GROUP BY a.paquete_id, p.nombre
             ORDER BY proyectos DESC, p.nombre ASC
             LIMIT 1',
            [$insumo['descripcionNorm'], $insumo['unidad'], $projectId],
        )->fetch(\PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }
        return [
            'paqueteId' => (int) $row['paquete_id'],
            'paqueteNombre' => $row['nombre'],
            'capa' => 'exacta',
            'confianza' => 'alta',
            'evidencia' => "Mismo insumo asignado en {$row['proyectos']} proyecto(s).",
        ];
    }

    /** Capa 2: similitud por tokens (>=4 chars, comodines escapados) contra asignaciones de otros proyectos. */
    private function sugerirPorTokens(int $projectId, array $insumo): ?array
    {
        $tokens = self::tokens($insumo['descripcionNorm']);
        if ($tokens === []) {
            return null;
        }
        $condiciones = implode(' + ', array_fill(0, count($tokens), '(a.descripcion_norm LIKE ?)'));
        $params = array_map(static fn ($t) => '%' . addcslashes($t, '\\%_') . '%', $tokens);
        $params[] = $projectId;
        $row = $this->db->query(
            "SELECT a.paquete_id, p.nombre,
                    SUM({$condiciones}) AS score, COUNT(DISTINCT a.project_id) AS proyectos
             FROM pdc_insumo_paquete a
             JOIN general_paquetes_contratacion p ON p.id = a.paquete_id AND p.activo = 1
             WHERE a.project_id <> ? AND a.paquete_id IS NOT NULL
             GROUP BY a.paquete_id, p.nombre
             HAVING score > 0
             ORDER BY score DESC, proyectos DESC, p.nombre ASC
             LIMIT 1",
            $params,
        )->fetch(\PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }
        return [
            'paqueteId' => (int) $row['paquete_id'],
            'paqueteNombre' => $row['nombre'],
            'capa' => 'tokens',
            'confianza' => 'media',
            'evidencia' => 'Insumos similares asignados a este paquete en otros proyectos.',
        ];
    }

    /** Capa 3 (respaldo): paquete más frecuente entre insumos ya asignados de la misma agrupación SINCO. */
    private function sugerirPorAgrupacion(array $insumo): ?array
    {
        if (($insumo['agrupacion'] ?? null) === null || $insumo['agrupacion'] === '') {
            return null;
        }
        $row = $this->db->query(
            'SELECT a.paquete_id, p.nombre, COUNT(*) AS usos
             FROM pdc_insumo_paquete a
             JOIN general_maestro_insumos m
                   ON m.descripcion_norm = a.descripcion_norm AND m.unidad = a.unidad
             JOIN general_paquetes_contratacion p ON p.id = a.paquete_id AND p.activo = 1
             WHERE m.agrupacion = ? AND a.paquete_id IS NOT NULL
             GROUP BY a.paquete_id, p.nombre
             ORDER BY usos DESC, p.nombre ASC
             LIMIT 1',
            [$insumo['agrupacion']],
        )->fetch(\PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }
        return [
            'paqueteId' => (int) $row['paquete_id'],
            'paqueteNombre' => $row['nombre'],
            'capa' => 'agrupacion',
            'confianza' => 'baja',
            'evidencia' => "Agrupación «{$insumo['agrupacion']}» suele ir a este paquete.",
        ];
    }

    /**
     * Candidatos para engrosar un paquete desde el asistente: insumos SIN asignar de la versión activa
     * similares (tokens/agrupación) a los que ya están en el paquete (en cualquier proyecto), opcionalmente
     * filtrados por tipo_recurso (4ª señal — replica el filtro del asistente de Tomás). null sin versión.
     */
    public function candidatosParaPaquete(int $projectId, int $paqueteId, ?string $tipoRecurso = null, ?int $versionId = null): ?array
    {
        $sin = $this->insumosDeVersion($projectId, 'sin_asignar', $versionId);
        if ($sin === null) {
            return null;
        }
        // "Huella" del paquete: tokens y agrupaciones de sus insumos ya asignados (todos los proyectos).
        $miembros = $this->db->query(
            'SELECT a.descripcion_norm, m.agrupacion
             FROM pdc_insumo_paquete a
             LEFT JOIN general_maestro_insumos m ON m.descripcion_norm = a.descripcion_norm AND m.unidad = a.unidad
             WHERE a.paquete_id = ?',
            [$paqueteId],
        )->fetchAll(\PDO::FETCH_ASSOC);
        $tokensPaquete = [];
        $agrupaciones = [];
        foreach ($miembros as $m) {
            foreach (self::tokens($m['descripcion_norm']) as $t) {
                $tokensPaquete[$t] = true;
            }
            if (($m['agrupacion'] ?? null) !== null && $m['agrupacion'] !== '') {
                $agrupaciones[$m['agrupacion']] = true;
            }
        }
        $candidatos = [];
        foreach ($sin['insumos'] as $insumo) {
            if ($tipoRecurso !== null && $tipoRecurso !== '' && ($insumo['tipoRecurso'] ?? null) !== $tipoRecurso) {
                continue;
            }
            $agrupMatch = ($insumo['agrupacion'] ?? null) !== null && isset($agrupaciones[$insumo['agrupacion']]);
            $tokenMatch = false;
            foreach (self::tokens($insumo['descripcionNorm']) as $t) {
                if (isset($tokensPaquete[$t])) { $tokenMatch = true; break; }
            }
            if (!$agrupMatch && !$tokenMatch) {
                continue;
            }
            $candidatos[] = [
                'descripcionNorm' => $insumo['descripcionNorm'],
                'unidad' => $insumo['unidad'],
                'descripcion' => $insumo['descripcion'],
                'agrupacion' => $insumo['agrupacion'],
                'tipoRecurso' => $insumo['tipoRecurso'],
                'valorTotal' => $insumo['valorTotal'],
            ];
        }
        return ['version' => $sin['version'], 'candidatos' => $candidatos];
    }

    /**
     * Para cada insumo único de la versión, las actividades del presupuesto que lo requieren
     * (vía el APU) — código, descripción, cantidad y valor. La clave del mapa es "NORMA@@UNIDAD"
     * (misma que usa la SPA). Los códigos son el futuro amarre con el cronograma (A4).
     * Devuelve top-`$tope` actividades por valor por insumo + el total. null sin versión.
     */
    public function actividadesPorInsumo(int $projectId, ?int $versionId = null, int $tope = 15): ?array
    {
        $version = $this->versionDe($projectId, $versionId);
        if ($version === null) {
            return null;
        }
        $vid = (int) $version['id'];
        $rows = $this->db->query(
            "SELECT ai.descripcion, ai.unidad, ai.cantidad_total, ai.valor_total,
                    it.codigo, it.descripcion AS actividad
             FROM pdc_presupuesto_apu_insumos ai
             JOIN pdc_presupuesto_items it ON it.id = ai.item_id
             WHERE ai.project_id = ? AND ai.version_id = ?
             ORDER BY ai.valor_total DESC",
            [$projectId, $vid],
        )->fetchAll(\PDO::FETCH_ASSOC);

        $mapa = [];
        foreach ($rows as $r) {
            $clave = MaestroInsumosService::normalizar((string) $r['descripcion']) . '@@' . mb_strtoupper(trim((string) $r['unidad']));
            if (!isset($mapa[$clave])) {
                $mapa[$clave] = ['total' => 0, 'items' => []];
            }
            $mapa[$clave]['total']++;
            if (count($mapa[$clave]['items']) < $tope) {
                $mapa[$clave]['items'][] = [
                    'codigo' => (string) $r['codigo'],
                    'actividad' => (string) $r['actividad'],
                    'cantidad' => (float) $r['cantidad_total'],
                    'valor' => (float) $r['valor_total'],
                ];
            }
        }
        return ['version' => ['id' => $vid, 'label' => $version['version_label']], 'mapa' => $mapa];
    }

    /** Catálogo activo indexado por nombre_norm → {id, nombre, tipoNegociacion} (una consulta). */
    private function catalogoActivoPorNombre(): array
    {
        $rows = $this->db->query(
            'SELECT id, nombre, nombre_norm, tipo_negociacion FROM general_paquetes_contratacion WHERE activo = 1',
        )->fetchAll(\PDO::FETCH_ASSOC);
        $mapa = [];
        foreach ($rows as $r) {
            $mapa[$r['nombre_norm']] = ['id' => (int) $r['id'], 'nombre' => $r['nombre'], 'tipoNegociacion' => $r['tipo_negociacion']];
        }
        return $mapa;
    }

    /** Overrides expertos (pasada semántica IA) desde el JSON versionado: NORMA@@UNIDAD → nombre de paquete. */
    private function overridesIA(): array
    {
        $ruta = __DIR__ . '/../../../database/seeds/sembrado_ia_overrides.json';
        if (!is_file($ruta)) {
            return [];
        }
        $data = json_decode((string) file_get_contents($ruta), true);
        return is_array($data['overrides'] ?? null) ? $data['overrides'] : [];
    }

    /** Actividad dominante (mayor valor) por insumo de la versión: NORMA@@UNIDAD → texto de la actividad. */
    private function actividadDominantePorInsumo(int $projectId, ?int $versionId): array
    {
        $act = $this->actividadesPorInsumo($projectId, $versionId, 1);
        if ($act === null) {
            return [];
        }
        $mapa = [];
        foreach ($act['mapa'] as $clave => $info) {
            $mapa[$clave] = (string) ($info['items'][0]['actividad'] ?? '');
        }
        return $mapa;
    }

    /** tipo_negociacion compatibles con un tipo_recurso SINCO (evita ubicar material en paquete de mano de obra). */
    private static function tiposCompatibles(?string $tipoRecurso): array
    {
        return match (mb_strtoupper((string) $tipoRecurso)) {
            'MATERIAL' => ['suministro', 'a_todo_costo', 'consumibles'],
            'MANO DE OBRA' => ['mano_obra', 'a_todo_costo'],
            'NOMINA' => ['mano_obra', 'consumibles'],
            'SUBCONTRATO' => ['a_todo_costo', 'mano_obra', 'suministro'],
            'EQUIPO', 'TRANSPORTE' => ['suministro', 'a_todo_costo', 'consumibles'],
            'HONORARIOS', 'CONSUMIBLES' => ['consumibles', 'a_todo_costo'],
            default => self::TIPOS,
        };
    }

    /** Resuelve un paquete del catálogo por nombre (normalizado), respetando compatibilidad de tipo. */
    private function resolverPaquete(string $nombre, ?string $tipoRecurso, array $catalogo, string $descNorm = ''): ?array
    {
        $norm = mb_substr(MaestroInsumosService::normalizar($nombre), 0, 200);
        $paq = $catalogo[$norm] ?? null;
        if ($paq === null) {
            return null;
        }
        // El bucket de indirectos no es una negociacion: exento del cuadro de compatibilidad.
        if ($norm === mb_substr(MaestroInsumosService::normalizar(self::PAQUETE_INDIRECTOS), 0, 200)) {
            return $paq;
        }
        if (!in_array($paq['tipoNegociacion'], self::tiposCompatibles($tipoRecurso), true)) {
            return null;
        }
        // El prefijo del NOMBRE del insumo manda: un suministro nunca cae en un paquete de mano de
        // obra (y viceversa), aunque su tipo_recurso lo permitiria.
        if ($descNorm !== '' && self::pareceSuministro($descNorm) && $paq['tipoNegociacion'] === 'mano_obra') {
            return null;
        }
        if ($descNorm !== '' && self::pareceManoDeObra($descNorm) && $paq['tipoNegociacion'] === 'suministro') {
            return null;
        }
        return $paq;
    }

    /** Capa IA (alta): override experto por (norma, unidad). */
    private function sugerirOverrideIA(array $insumo, array $overrides, array $catalogo): ?array
    {
        $clave = $insumo['descripcionNorm'] . '@@' . mb_strtoupper((string) $insumo['unidad']);
        $nombre = $overrides[$clave] ?? null;
        if (!is_string($nombre) || $nombre === '') {
            return null;
        }
        $paq = $this->resolverPaquete($nombre, $insumo['tipoRecurso'] ?? null, $catalogo, $insumo['descripcionNorm']);
        if ($paq === null) {
            return null;
        }
        return [
            'paqueteId' => $paq['id'], 'paqueteNombre' => $paq['nombre'],
            'capa' => 'ia', 'confianza' => 'alta',
            'evidencia' => 'Mapeo experto (pasada semántica de la primera iteración).',
        ];
    }

    /** Capa reglas (media): diccionario de dominio sobre descripción + actividad dominante, filtrado por tipo_recurso. */
    private function sugerirPorReglas(array $insumo, string $actividad, array $catalogo): ?array
    {
        $desc = self::henoParaReglas($insumo['descripcionNorm']);
        $act = $actividad !== '' ? self::henoParaReglas(MaestroInsumosService::normalizar($actividad)) : '';
        $tipoRecurso = mb_strtoupper((string) ($insumo['tipoRecurso'] ?? ''));

        // Doctrina de precedencia (feedback del usuario 2026-07-25):
        //  1) Si la propia DESCRIPCION nombra el oficio/material, manda (reglas `descPrimero`) — p.ej.
        //     «M.O. ZOCALO EN PORCELANATO» es del enchapador aunque su actividad dominante sea de madera.
        //  2) Si la descripcion calla, el material/trabajo lo aporta la ACTIVIDAD PADRE dominante.
        //  3) Respaldo: el resto de reglas sobre la descripcion.
        // Las reglas de material (`soloDesc`) nunca miran la actividad: el nombre identifica el producto.
        $pasadas = [
            ['heno' => $desc, 'origen' => 'descripcion', 'soloDescPrimero' => true],
            ['heno' => $act, 'origen' => 'actividad', 'soloDescPrimero' => false],
            ['heno' => $desc, 'origen' => 'descripcion', 'soloDescPrimero' => false],
        ];

        foreach ($pasadas as $pasada) {
            if (trim($pasada['heno']) === '') {
                continue;
            }
            foreach (self::REGLAS_SEMBRADO as $regla) {
                if ($regla['tipos'] !== [] && !in_array($tipoRecurso, $regla['tipos'], true)) {
                    continue;
                }
                if ($pasada['soloDescPrimero'] && !($regla['descPrimero'] ?? false)) {
                    continue;
                }
                if (($regla['soloDesc'] ?? false) && $pasada['origen'] !== 'descripcion') {
                    continue;
                }
                foreach ($regla['kw'] as $kw) {
                    if (!self::casaKeyword($pasada['heno'], $kw)) {
                        continue;
                    }
                    // Contexto obligatorio: la regla exige que el heno hable tambien del alcance correcto.
                    if (($regla['ctx'] ?? []) !== []) {
                        $hayCtx = false;
                        foreach ($regla['ctx'] as $c) {
                            if (self::casaKeyword($pasada['heno'], $c)) {
                                $hayCtx = true;
                                break;
                            }
                        }
                        if (!$hayCtx) {
                            continue;
                        }
                    }
                    $donde = $pasada['origen'] === 'actividad'
                        ? "en su actividad padre «{$actividad}»"
                        : 'en la descripcion del insumo';
                    // Veto explicito: sin senal suficiente es preferible dejarlo pendiente que inventar.
                    if ($regla['paq'] === self::SIN_PROPUESTA) {
                        return [
                            'veto' => true, 'capa' => 'reglas', 'confianza' => 'media',
                            'paqueteId' => 0, 'paqueteNombre' => '',
                            'evidencia' => "Regla de veto: «{$kw}» {$donde} sin contexto suficiente → queda pendiente.",
                        ];
                    }
                    $paq = $this->resolverPaquete($regla['paq'], $insumo['tipoRecurso'] ?? null, $catalogo, $insumo['descripcionNorm']);
                    if ($paq !== null) {
                        return [
                            'paqueteId' => $paq['id'], 'paqueteNombre' => $paq['nombre'],
                            'capa' => 'reglas', 'confianza' => 'media',
                            'evidencia' => "Regla de dominio: «{$kw}» {$donde} (recurso {$tipoRecurso}) → {$paq['nombre']}.",
                        ];
                    }
                    break; // regla caso pero el paquete no resolvio: siguiente regla
                }
            }
        }
        return null;
    }

    /**
     * ¿El keyword aparece como INICIO de palabra en el heno normalizado?
     * normalizar() no tokeniza, asi que str_contains casaba «PISO» dentro de APISONADOR/CONTRAPISO,
     * «GRES» dentro de INGRESO y «COMUNICACIONES» dentro de TELECOMUNICACIONES. La frontera izquierda
     * conserva plurales y compuestos legitimos (PISOS, CERAMICA sobre CERAMIC) y elimina esos falsos.
     */
    private static function casaKeyword(string $heno, string $kw): bool
    {
        return preg_match('/(?<![A-Z0-9])' . preg_quote($kw, '/') . '/u', $heno) === 1;
    }

    /** Prepara el heno (descripcion o actividad ya normalizada) para el matching de reglas. */
    private static function henoParaReglas(string $norm): string
    {
        // El texto entre parentesis es nota constructiva, no alcance contratable.
        $h = (string) preg_replace('/\([^)]*\)/u', ' ', $norm);
        // «PISO 1», «PISOS 4», «PISO N 2» = NIVEL del edificio, nunca acabado de piso.
        $h = (string) preg_replace('/(?<![A-Z0-9])PISOS?\s*(N[O°]?\s*)?\d+/u', ' ', $h);
        return ' ' . trim((string) preg_replace('/\s+/u', ' ', $h)) . ' ';
    }

    /** ¿La descripcion declara que el insumo es un suministro puro (no incluye instalacion)? */
    private static function pareceSuministro(string $d): bool
    {
        return (bool) preg_match('/^SUMINISTRO(?! E INSTALACION| Y (COLOCACION|INSTALACION))/u', trim($d));
    }

    /** ¿La descripcion declara que el insumo es mano de obra pura? */
    private static function pareceManoDeObra(string $d): bool
    {
        return (bool) preg_match('/^(M\.?O\.?[ .]|M\. DE O|MANO DE OBRA)/u', trim($d));
    }

    /** Capa indirectos (media): admin/nómina/dotación → paquete «Indirectos / Administración». */
    private function sugerirIndirectos(array $insumo, array $catalogo): ?array
    {
        $tipoRecurso = mb_strtoupper((string) ($insumo['tipoRecurso'] ?? ''));
        $motivo = null;
        if (in_array($tipoRecurso, ['NOMINA', 'HONORARIOS', 'CONSUMIBLES'], true)) {
            $motivo = "tipo de recurso {$tipoRecurso}";
        } else {
            foreach (self::KEYWORDS_INDIRECTOS as $kw) {
                if (self::casaKeyword(' ' . $insumo['descripcionNorm'] . ' ', $kw)) {
                    $motivo = "«{$kw}» en la descripción";
                    break;
                }
            }
        }
        if ($motivo === null) {
            return null;
        }
        $paq = $catalogo[mb_substr(MaestroInsumosService::normalizar(self::PAQUETE_INDIRECTOS), 0, 200)] ?? null;
        if ($paq === null) {
            return null;
        }
        return [
            'paqueteId' => $paq['id'], 'paqueteNombre' => $paq['nombre'],
            'capa' => 'indirectos', 'confianza' => 'media',
            'evidencia' => "Administrativo/no empaquetable ({$motivo}) → Indirectos.",
        ];
    }

    /** Tokens significativos (>=4 chars) de una descripción normalizada. */
    private static function tokens(string $norm): array
    {
        return array_values(array_filter(
            explode(' ', $norm),
            static fn ($t) => mb_strlen($t) >= 4,
        ));
    }
}
