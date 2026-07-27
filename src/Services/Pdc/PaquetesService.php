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

    /**
     * Modalidad de contratación — dimensión ORTOGONAL a tipo_negociacion: `tipo_negociacion` dice QUÉ
     * se compra; la modalidad dice CÓMO y con qué cadencia, que es lo que decide si el paquete entra
     * al plan de fechas (A4) y cómo se le hace seguimiento (B1/B2).
     *  · contrato        alcance cerrado, un proveedor, se firma una vez → proceso completo con fechas.
     *  · orden_compra    commodity recurrente (concreto, acero): entra al plan pero solo se programa el
     *                    PRIMER hito; las reposiciones son historial, no filas del plan.
     *  · consumo_directo ferretería a demanda: SIN proceso ni fecha; se controla el gasto.
     *  · no_contratable  nómina e imprevistos: no se le compran a nadie; fuera de cobertura y semáforos.
     */
    public const MODALIDADES = ['contrato', 'orden_compra', 'consumo_directo', 'no_contratable'];

    /** Modalidades que NO generan proceso de contratación (quedan fuera del plan de fechas de A4). */
    public const MODALIDADES_SIN_PROCESO = ['consumo_directo', 'no_contratable'];

    /**
     * Capas del motor que pueden originar una asignación (A3.3). Lo que no viene de una de estas es
     * 'humano': se guarda como decisión confirmada y el re-sembrado no la toca.
     */
    public const ORIGENES_MOTOR = ['ia', 'exacta', 'reglas', 'tokens', 'indirectos', 'agrupacion'];

    /**
     * Peso mínimo de la actividad dominante para fiarse de ella. Por debajo, el insumo se reparte
     * demasiado y elegir «su» actividad es azar: la sugerencia baja a confianza baja (A3.3).
     */
    public const DOMINANCIA_MINIMA = 0.60;

    /**
     * Tipos de recurso cuyo destino depende del frente de obra. Un material se compra por lo que es;
     * la mano de obra y el subcontrato se contratan por dónde se ejecutan.
     */
    private static function mandaLaActividad(?string $tipoRecurso): bool
    {
        return in_array(mb_strtoupper((string) $tipoRecurso), ['MANO DE OBRA', 'SUBCONTRATO', 'NOMINA'], true);
    }

    /** Paquete bucket para insumos no empaquetables (A3.1). */
    public const PAQUETE_INDIRECTOS = 'Indirectos / Administración';

    /** Buckets sin proceso de contratación (modalidades consumo_directo / no_contratable). */
    public const PAQUETE_FERRETERIA = 'Ferretería y consumibles de obra';
    public const PAQUETE_NOMINA = 'Nómina de obra';
    public const PAQUETE_IMPREVISTOS = 'Imprevistos y provisiones';

    /**
     * Ferretería y consumibles: se piden a necesidad contra almacén/caja menor, sin proceso de
     * contratación. ANTI-CAJÓN DE SASTRE: este bucket es el ÚLTIMO recurso — cualquier paquete de
     * oficio del catálogo gana primero (las reglas corren antes), y su peso total debe vigilarse
     * (techo sano ≈2% del presupuesto; por encima es error de clasificación, no motivo para ampliarlo).
     */
    private const KEYWORDS_FERRETERIA = [
        'PUNTILLA', 'TORNILLO', 'CHAZO', 'LIJA', 'BROCHA', 'RODILLO', 'DISCO', 'SEGUETA', 'CINTA',
        'MANGUERA', 'HERRAMIENTA MENOR', 'EQUIPO MENOR', 'CONSUMIBLE', 'AMARRE', 'GUANTE', 'CASCO',
        'SEGURIDAD INDUSTRIAL', 'DOTACION DE PERSONAL', 'MADERA PROVISIONALES', 'ELEMENTO DE PROTECCION',
    ];

    /** Keywords (ya normalizadas) que marcan un insumo como indirecto/administrativo. */
    private const KEYWORDS_INDIRECTOS = [
        'IMPREVISTO', 'NOMINA', 'DOTACION', 'PAPELERIA', 'FOTOCOPIA', 'UTILES', 'CAFETERIA',
        'ASEO', 'VIGILANCIA', 'HONORARIO', 'ADMINISTRA', 'GASTOS MEDICOS', 'DROGAS',
        'ELEMENTOS DE ASEO', 'EQUIPO DE OFICINA', 'EQ DE COMPUTO', 'COMUNICACIONES',
        // SST y bioseguridad: gasto de obra que no se le compra a un contratista de alcance.
        'BIOSEGURIDAD', 'PROTOCOLOS DE', 'COPIAS Y PLANOS',
        // Personal indirecto y provisiones de gestión: sin esto el motor los mandaba a estructura.
        'OFICIALES', 'PRESUPUESTO AMBIENTAL', 'AMBIENTAL',
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
        // ── A3.4 · Oficios que AIA contrata partidos: el suministro y la instalación van a paquetes
        // distintos, así que el TIPO DE RECURSO del insumo decide cuál de los dos le toca. Doctrina
        // de la dirección de obra: «tengo 2 contratos, uno por fabricación y suministro y otro por
        // mano de obra… ellos tienen 2 razones sociales». Van primero porque son más específicas que
        // las reglas de alcance a todo costo que vienen después.
        ['kw' => ['PUERTA MADERA', 'PUERTA EN MADERA', 'PUERTA CORREDIZA MADERA'], 'paq' => 'Suministro PUERTAS EN MADERA', 'tipos' => ['MATERIAL'], 'soloDesc' => true, 'descPrimero' => true],
        ['kw' => ['PUERTA MADERA', 'PUERTA EN MADERA', 'CARPINTERIA MADERA', 'CARPINTERIA EN MADERA', 'CLOSET', 'ALACENA', 'VESTIER'], 'paq' => 'M. de O CARPINTERÍA DE MADERA', 'tipos' => ['MANO DE OBRA'], 'descPrimero' => true],
        ['kw' => ['PUERTA CORTAFUEGO', 'PUERTA CORTA FUEGO'], 'paq' => 'Suministro PUERTAS CORTAFUEGO', 'tipos' => ['MATERIAL'], 'soloDesc' => true, 'descPrimero' => true],
        ['kw' => ['CORTAFUEGO', 'CORTA FUEGO'], 'paq' => 'M. de O PUERTAS CORTAFUEGO', 'tipos' => ['MANO DE OBRA'], 'descPrimero' => true],
        ['kw' => ['PUERTA METALICA', 'PUERTA METALIVA', 'PUERTA EN LAMINA'], 'paq' => 'Suministro PUERTAS METÁLICAS', 'tipos' => ['MATERIAL'], 'soloDesc' => true, 'descPrimero' => true],
        ['kw' => ['PUERTA METALICA', 'PUERTA METALIVA'], 'paq' => 'M. de O PUERTAS METÁLICAS', 'tipos' => ['MANO DE OBRA'], 'descPrimero' => true],
        // El epóxico es el consumible del anclaje químico, no un aditivo de concreto.
        ['kw' => ['EPOXICO', 'ANCLAJE QUIMICO', 'ANCLAJES QUIMICOS'], 'paq' => 'Suministro ANCLAJES', 'tipos' => ['MATERIAL'], 'soloDesc' => true, 'descPrimero' => true],
        // «Suministro solo. La MO la ejecutan los de la carpintería de madera.»
        ['kw' => ['CAMPANA EXTRACTORA', 'ASADOR', 'ESTUFA', 'HORNO EMPOTRA', 'CUBIERTA A GAS'], 'paq' => 'Suministro DOTACIÓN COCINAS Y LAVADEROS', 'tipos' => ['MATERIAL'], 'soloDesc' => true, 'descPrimero' => true],
        // «Aparte del urbanismo, y el suministro por aparte también.»
        ['kw' => ['TOPELLANTA'], 'paq' => 'M. de O TOPELLANTAS', 'tipos' => ['MANO DE OBRA', 'SUBCONTRATO'], 'descPrimero' => true],
        // El transporte de personal es un contrato distinto del acarreo de materiales.
        ['kw' => ['BUSETA', 'BUS DE PERSONAL', 'TRANSPORTE DE PERSONAL'], 'paq' => 'Alquiler de transporte de personal', 'tipos' => [], 'soloDesc' => true, 'descPrimero' => true],

        // ── A3.3 · Overrides destilados a conocimiento generalizable ────────────────────────────
        // Estas reglas nacen de revisar las 158 entradas curadas a mano para DAPORTO: 89 eran
        // redundantes (la regla ya acertaba) y las otras 69 tapaban huecos que, sin ellas, el motor
        // resolvía con disparates de la capa de agrupación contable — FORMALETA COLUMNAS acababa en
        // Indirectos, EXCAVACIÓN MECÁNICA en cielos rasos y el geodrén en ventanería. Al escribirlas
        // como reglas el conocimiento deja de ser memoria de un proyecto y sirve para el siguiente.

        // Equipos de izaje y transporte vertical: contrato propio, con montaje y desmontaje.
        ['kw' => ['TORRE GRUA'], 'paq' => 'Sum + Inst TORRE GRUA', 'tipos' => [], 'descPrimero' => true],
        ['kw' => ['MALACATE', 'PLUMA GRUA', 'MONTACARGA', 'ASCENSOR DE OBRA'], 'paq' => 'Sum + Inst EQUIPOS TRANSPORTE VERTICAL', 'tipos' => [], 'descPrimero' => true],
        // Movimiento de tierra contratado a todo costo (máquina + operador + botada), distinto de la
        // cuadrilla propia que hace excavación manual.
        ['kw' => ['EXCAVACION MECANICA', 'BOTADA DE MATERIAL', 'BOTADA ESCOMBROS', 'BOTADA DE ESCOMBROS', 'BOBCAT', 'VIBROCOMPACTADOR', 'RETROEXCAVADORA'], 'paq' => 'Sum + Inst MOVIMIENTOS DE TIERRA', 'tipos' => [], 'descPrimero' => true],
        ['kw' => ['BASE GRANULAR', 'SUB BASE', 'SUBBASE GRANULAR'], 'paq' => 'Suministro BASE Y SUB BASE GRANULAR', 'tipos' => [], 'soloDesc' => true, 'descPrimero' => true],
        // Formaleta y obra falsa: el alquiler de encofrado no es un indirecto de administración.
        ['kw' => ['FORMALETA', 'ENCOFRADO', 'OBRA FALSA', 'MADERA ESTRUCTURA', 'TABLERO FENOLIC'], 'paq' => 'Suministro FORMALETA MUROS, LOSAS Y CONTENCIÓN', 'tipos' => [], 'descPrimero' => true],
        // Anclajes químicos y líneas de vida: el epóxico va con el anclaje, no con los aditivos.
        ['kw' => ['EPOXICO', 'ANCLAJE QUIMICO', 'ANCLAJES PARA', 'ANCLAJES CERTIFICADOS PARA MANTENIMIENTO'], 'paq' => 'Sum + Inst ANCLAJES', 'tipos' => [], 'descPrimero' => true],
        // Cubiertas, asfalto y otros oficios con paquete propio que el motor no alcanzaba.
        ['kw' => ['CUBIERTA LIVIANA', 'CUBIERTA METALICA', 'TEJA'], 'paq' => 'Sum + Inst CUBIERTAS METÁLICAS', 'tipos' => [], 'descPrimero' => true],
        ['kw' => ['PAVIMENTO ASFALTICO', 'IMPRIMACION ASFALTICA', 'CARPETA ASFALTICA', 'ASFALTO'], 'paq' => 'Sum + Inst CARPETA ASFALTICA', 'tipos' => [], 'descPrimero' => true],
        ['kw' => ['ENSAYOS DE LABORATORIO', 'LABORATORIO DE MATERIALES', 'ENSAYO DE'], 'paq' => 'Sum + Inst LABORATORIO DE MATERIALES', 'tipos' => [], 'descPrimero' => true],
        ['kw' => ['CAMPAMENTO'], 'paq' => 'Sum + Inst CAMPAMENTO - ALMACEN', 'tipos' => [], 'descPrimero' => true],
        ['kw' => ['REVOQUE SECO'], 'paq' => 'Sum + Inst REVOQUE SECO', 'tipos' => [], 'descPrimero' => true],
        ['kw' => ['GEODREN', 'ALVEODREN'], 'paq' => 'Suministro GEODREN', 'tipos' => ['MATERIAL'], 'soloDesc' => true],
        ['kw' => ['GEODREN', 'ALVEODREN', 'LECHO FILTRANTE'], 'paq' => 'M. de O FILTROS', 'tipos' => ['MANO DE OBRA', 'SUBCONTRATO'], 'descPrimero' => true],
        ['kw' => ['MESON', 'MESONES'], 'paq' => 'Sum + Inst MESONES', 'tipos' => [], 'descPrimero' => true],
        ['kw' => ['ESPEJO'], 'paq' => 'Sum + Inst CABINAS Y ESPEJOS DE BAÑOS', 'tipos' => [], 'descPrimero' => true],
        ['kw' => ['CALENTADOR', 'CALENTAMIENTO DE AGUA'], 'paq' => 'Sum + Inst SISTEMA DE CALENTAMIENTO AGUA', 'tipos' => [], 'descPrimero' => true],
        ['kw' => ['JACUZZI', 'HIDROMASAJE', 'EQUIPO DE PRESION', 'EQUIPOS DE PRESION', 'HIDRONEUMATIC'], 'paq' => 'Suministro EQUIPOS HIDRONEUMÁTICOS', 'tipos' => [], 'descPrimero' => true],
        ['kw' => ['REJILLA'], 'paq' => 'Sum + Inst INSTALACIONES HIDROSANITARIAS', 'tipos' => ['MATERIAL', 'SUBCONTRATO'], 'descPrimero' => true],
        ['kw' => ['LAVADERO PREFABRICADO', 'LAVAESCOBAS'], 'paq' => 'Sum + Inst DOTACIÓN COCINAS Y LAVADEROS', 'tipos' => [], 'descPrimero' => true],
        // Redes provisionales de obra: son instalación temporal, no un imprevisto.
        ['kw' => ['RED PROVISIONAL', 'PROVISIONAL DE ENERGIA', 'PROVISIONAL DE AGUA'], 'paq' => 'Sum + Inst PROVISIONAL ELÉCTRICA', 'tipos' => [], 'descPrimero' => true],
        // Bombeo de concreto: servicio con equipo y operador, contrato propio (no un indirecto).
        ['kw' => ['BOMBEO DE CONCRETO', 'SERVICIO BOMBEO'], 'paq' => 'Sum + Inst BOMBEO DE CONCRETO', 'tipos' => [], 'descPrimero' => true],
        // Obra de urbanismo llamada por su nombre: sin esto acababa en estructura por tokens.
        ['kw' => ['URBANISMO', 'OBRAS CIVILES GUARDERIA', 'OBRAS EXTERIORES'], 'paq' => 'M. de O URBANISMO (MUROS, ANDENES, ESCALAS, GRAMA, ETC)', 'tipos' => ['MANO DE OBRA', 'SUBCONTRATO'], 'descPrimero' => true],
        // Accesorios de baño: el juego de incrustaciones es dotación, no un aparato sanitario.
        ['kw' => ['INCRUSTACION', 'ACCESORIOS DE BANO', 'TOALLERO', 'JABONERA'], 'paq' => 'Suministro DOTACIÓN Y ACCESORIOS DE BAÑOS', 'tipos' => ['MATERIAL'], 'soloDesc' => true, 'descPrimero' => true],
        // El prefabricado se suministra; su instalación es otro paquete (regla de más abajo).
        ['kw' => ['LAVAESCOBAS PREFABRICADO', 'LAVADERO PREFABRICADO'], 'paq' => 'Suministro LAVADERO PREFABRICADO', 'tipos' => ['MATERIAL'], 'soloDesc' => true, 'descPrimero' => true],
        // Cimentación: el vaciado y el descabece de pilotes son de la cuadrilla de cimentación
        // profunda, y la losa de cimentación de la superficial — no de la estructura aérea.
        ['kw' => ['VACIADO DE PILOTES', 'DESCABECE PILOTES', 'VACIADO PILOTE'], 'paq' => 'M. de O CIMENTACIÓN PROFUNDA EN CONCRETO', 'tipos' => ['MANO DE OBRA', 'SUBCONTRATO'], 'descPrimero' => true],

        // ── A3.3 · Cola larga: familias que el motor no sabía colocar (71 insumos, $653M en DAPORTO).
        // Van primero porque son las más específicas: nombran un objeto o un servicio concreto y no
        // deben caer en las reglas de oficio de más abajo.

        // Equipo y maquinaria de obra: compra, alquiler y reposición. Se distingue de los alquileres
        // que SÍ pertenecen a un frente (una retro alquilada es movimiento de tierra, y eso lo
        // resuelven los overrides antes que esta regla).
        ['kw' => ['VIBRADOR', 'CANGURO', 'APISONADOR', 'PLANCHA VIBRATORIA', 'BOMBA SUMERGIBLE', 'COMPRA EQUIPO', 'MARTILLO DEMOLEDOR', 'COMPRESOR', 'REPOSICION Y REPARACION', 'MAQUINARIA', 'BANOS PORTATILES', 'BANO PORTATIL'], 'paq' => 'Equipos y maquinaria de obra', 'tipos' => ['EQUIPO', 'MATERIAL'], 'soloDesc' => true],
        // Tecnología: no se contrata como obra, se pide a necesidad; su modalidad es consumo directo.
        ['kw' => ['COMPUTADOR', 'TABLET TIPO', 'TABLETS', 'IPAD', 'IMPRESORA', 'SOFTWARE', 'INTERNET', 'LICENCIA', 'RADIOS DE COMUNICACION', 'RADIO DE COMUNICACION'], 'paq' => 'Tecnología y software de obra', 'tipos' => ['EQUIPO', 'MATERIAL', 'SUBCONTRATO'], 'soloDesc' => true],
        // Transporte externo. OJO: «TRANSPORTE INTERNO» de mano de obra es acarreo dentro de la obra
        // y pertenece a la cuadrilla de estructura (regla más abajo); por eso aquí se filtra por tipo.
        ['kw' => ['ACARREO', 'FLETE', 'BUSETA', 'TRANSPORTE INTERNO', 'TRANSPORTE DE MATERIAL'], 'paq' => 'Transporte y acarreos', 'tipos' => ['TRANSPORTE'], 'soloDesc' => true],
        // Bolsas de presupuesto sin alcance definido: no se le compran a nadie todavía.
        ['kw' => ['PARTIDA PRESUPUESTAL', 'DETALLE CASAS', 'DETALLE APARTAMENTO'], 'paq' => 'Provisiones y partidas globales', 'tipos' => ['SUBCONTRATO', 'MANO DE OBRA', 'MATERIAL'], 'soloDesc' => true],
        // Zonas verdes: la grama tiene contrato propio y gana por específica; el resto del oficio
        // (arborización, especies vegetales, jardinería) va a paisajismo.
        ['kw' => ['GRAMA', 'ENGRAMADO'], 'paq' => 'Sum + Inst ENGRAMADO', 'tipos' => ['SUBCONTRATO', 'MATERIAL', 'MANO DE OBRA'], 'descPrimero' => true],
        ['kw' => ['ARBOL', 'ESPECIE VEGETAL', 'ESPECIES VEGETALES', 'JARDINERIA', 'PAISAJISMO', 'SIEMBRA'], 'paq' => 'Sum + Inst PAISAJISMO Y ZONAS VERDES', 'tipos' => ['SUBCONTRATO', 'MATERIAL', 'MANO DE OBRA'], 'descPrimero' => true],
        // Servicios y obras menores con paquete propio ya en el catálogo, que el motor no usaba.
        // Toda la topografía en un solo paquete (revisión en obra 2026-07-27): la comisión y el
        // replanteo los hace el mismo topógrafo, con o sin equipos propios.
        ['kw' => ['LOCALIZACION Y REPLANTEO', 'TOPOGRAFIA', 'COMISION TOPOGRAFICA'], 'paq' => 'Sum + Inst TOPOGRAFÍA', 'tipos' => ['SUBCONTRATO', 'MANO DE OBRA'], 'descPrimero' => true],
        ['kw' => ['LECHO FILTRANTE', 'MATERIAL FILTRANTE', 'FILTRO'], 'paq' => 'M. de O FILTROS', 'tipos' => ['MANO DE OBRA'], 'descPrimero' => true],
        ['kw' => ['CUNETA'], 'paq' => 'M. de O CUNETA TALUD', 'tipos' => ['MANO DE OBRA', 'SUBCONTRATO'], 'descPrimero' => true],
        ['kw' => ['PLANTA ELECTRICA'], 'paq' => 'Sum + Inst PLANTA ELÉCTRICA', 'tipos' => ['EQUIPO', 'MATERIAL', 'SUBCONTRATO'], 'descPrimero' => true],
        // Sellantes de junta: NO son aditivos de concreto — la familia SIKA cubre las dos cosas y
        // mezclarlas ya nos costó un error (SIKAFLEX/SIKAROD sellan, ANTISOL cura).
        ['kw' => ['SIKAROD', 'SIKAFLEX', 'SISMOFLEX', 'SELLANTE', 'BACKER ROD'], 'paq' => 'Suministro JUNTA DE DILATACIÓN', 'tipos' => ['MATERIAL'], 'soloDesc' => true],
        // Tanques y accesorios de tanque: se contratan con el tanque, no con la red hidráulica.
        ['kw' => ['TANQUE'], 'paq' => 'Suministro TANQUES DE RESERVA DE AGUA', 'tipos' => ['MATERIAL'], 'soloDesc' => true],
        // Oficios de mano de obra que faltaban por nombre (mapeo del usuario, 2026-07-26).
        ['kw' => ['SOLADO'], 'paq' => 'M. de O CIMENTACIÓN SUPERFICIAL EN CONCRETO', 'tipos' => ['MANO DE OBRA', 'SUBCONTRATO'], 'descPrimero' => true],
        ['kw' => ['ANDEN', 'TOPELLANTA', 'SARDINEL'], 'paq' => 'M. de O URBANISMO (MUROS, ANDENES, ESCALAS, GRAMA, ETC)', 'tipos' => ['MANO DE OBRA', 'SUBCONTRATO'], 'descPrimero' => true],
        ['kw' => ['NIVELACION DEL TERRENO', 'NIVELACION DE TERRENO', 'ROCERIA', 'LIMPIEZA DEL LOTE'], 'paq' => 'M. de O MOVIMIENTOS DE TIERRA (EXCAVACIONES Y RELLENOS)', 'tipos' => ['MANO DE OBRA', 'SUBCONTRATO'], 'descPrimero' => true],
        ['kw' => ['INSTALACION SANITARIO', 'INSTALACION DE SANITARIO', 'BOCAMANGUERA'], 'paq' => 'M. de O APARATOS SANITARIOS Y GRIFERÍA', 'tipos' => ['MANO DE OBRA', 'SUBCONTRATO'], 'descPrimero' => true],
        ['kw' => ['BOCAMANGUERA'], 'paq' => 'Suministro APARATOS SANITARIOS Y GRIFERÍA', 'tipos' => ['MATERIAL'], 'soloDesc' => true],
        // Talón y rebanco cuelgan del subcapítulo de pisos: manda el frente, no el material (concreto).
        ['kw' => ['TALON', 'REBANCO'], 'paq' => 'M. de O MORTEROS DE PISO', 'tipos' => ['MANO DE OBRA', 'SUBCONTRATO'], 'descPrimero' => true],
        // Amoblamiento y electrodomésticos: sin regla acababan en griferías o en estructura por pura
        // coincidencia de agrupación contable. Cada uno tiene su paquete de dotación en el catálogo.
        ['kw' => ['CAMPANA EXTRACTORA', 'ASADOR', 'ESTUFA', 'HORNO EMPOTRA', 'CUBIERTA A GAS'], 'paq' => 'Sum + Inst DOTACIÓN COCINAS Y LAVADEROS', 'tipos' => ['MATERIAL', 'SUBCONTRATO'], 'soloDesc' => true],
        ['kw' => ['MESA DE JUNTAS', 'MESA PARA', 'MESA INFANTIL', 'SALA TIPO', 'JUEGO DE TERRAZA', 'MOBILIARIO', 'SOFA', 'POLTRONA', 'TAPETE'], 'paq' => 'Sum + Inst DOTACIÓN ZONAS COMUNES', 'tipos' => ['MATERIAL', 'SUBCONTRATO'], 'soloDesc' => true],
        // Puertas POR TIPO (decisión del usuario 2026-07-26): madera, metálicas y cortafuego son las
        // tres categorías vigentes. Los suministros de puerta metálica no los cubría la regla de
        // carpintería metálica, que solo admite subcontrato.
        ['kw' => ['PUERTA METAL', 'PUERTA EN LAMINA', 'PUERTA DE LAMINA'], 'paq' => 'Sum + Inst PUERTAS METÁLICAS', 'tipos' => ['MATERIAL', 'SUBCONTRATO'], 'descPrimero' => true],

        // ── Objetos físicos inequívocos: van primero para que no los capturen reglas de instalación ──
        ['kw' => ['CORTA FUEGO', 'CORTAFUEGO'], 'paq' => 'Sum + Inst PUERTAS CORTAFUEGO', 'tipos' => ['SUBCONTRATO', 'MATERIAL'], 'descPrimero' => true],
        ['kw' => ['PASAMANO', 'BARANDA', 'RODAMANOS'], 'paq' => 'Sum + Inst BARANDAS Y PASAMANOS', 'tipos' => ['SUBCONTRATO', 'MATERIAL', 'MANO DE OBRA'], 'descPrimero' => true],
        ['kw' => ['PERGOLA', 'CERCHA', 'ESTRUCTURA METALICA'], 'paq' => 'Sum + Inst ESTRUCTURA METÁLICA', 'tipos' => ['SUBCONTRATO'], 'descPrimero' => true],
        // Seguridad en alturas: certificador con matrícula, no el instalador de anclaje epóxico.
        ['kw' => ['LINEA DE VIDA', 'ANTIPENDULO', 'ANCLAJE CERTIFICADO', 'ANCLAJES CERTIFICADOS'], 'paq' => 'Sum + Inst SEGURIDAD HUMANA (LINEAS LONGITUDINALES Y TRANSVERSALES, PUNTOS ANTIPÉNDULO)', 'tipos' => ['SUBCONTRATO', 'MATERIAL'], 'descPrimero' => true],

        // ── Trámites y servicios: tienen su propio proceso de contratación, no son «indirectos» ──
        ['kw' => ['RETILAP', 'CERTIFICACION RETIE', 'INSPECCION RETIE'], 'paq' => 'Sum + Inst CERTIFICACION RETILAP Y RETIE', 'tipos' => ['SUBCONTRATO'], 'descPrimero' => true],
        ['kw' => ['VIGILANCIA'], 'paq' => 'Sum + Inst SERVICIO DE VIGILANCIA', 'tipos' => ['SUBCONTRATO'], 'descPrimero' => true],
        ['kw' => ['ASEO PERMANENTE', 'ASEO DE OBRA', 'ASEO MENSUAL'], 'paq' => self::PAQUETE_INDIRECTOS, 'tipos' => [], 'descPrimero' => true],
        ['kw' => ['ASEO'], 'paq' => 'Sum + Inst ASEO FINAL DE OBRA', 'tipos' => ['SUBCONTRATO'], 'descPrimero' => true],
        ['kw' => ['LAVADA', 'HIDROFUG'], 'ctx' => ['FACHADA'], 'paq' => 'Sum + Inst LAVADA E HIDROFUGADA DE FACHADAS', 'tipos' => ['SUBCONTRATO'], 'descPrimero' => true],
        ['kw' => ['DEMARCACION', 'NUMERACION'], 'ctx' => ['PARQUEADERO', 'VIAL', 'CELDA'], 'paq' => 'Sum + Inst PINTURA DEMARCACIÓN, NUMERACIÓN Y FLECHAS DE PARQUEADEROS', 'tipos' => ['SUBCONTRATO', 'MANO DE OBRA'], 'descPrimero' => true],
        ['kw' => ['NOMENCLATURA', 'SENALIZACION', 'AVISO'], 'paq' => 'Sum + Inst SEÑALIZACIÓN', 'tipos' => ['SUBCONTRATO', 'MATERIAL', 'MANO DE OBRA'], 'descPrimero' => true],
        ['kw' => ['POLISOMBRA', 'INVERNADERO', 'PROTECCION TEMPORAL', 'PROTECCION CON PLASTICO'], 'paq' => self::PAQUETE_INDIRECTOS, 'tipos' => ['SUBCONTRATO', 'MATERIAL'], 'descPrimero' => true],

        // ── Instalaciones (subcontrato / a todo costo) ──
        // Impermeabilización antes que ascensores: el ascensorista no impermeabiliza el foso — pero
        // tampoco necesita paquete aparte, lo hace el mismo impermeabilizador (revisión en obra).
        ['kw' => ['IMPERMEABILIZ'], 'paq' => 'Sum + Inst IMPERMEABILIZACIONES', 'tipos' => ['SUBCONTRATO', 'MANO DE OBRA', 'MATERIAL']],
        // Aparatos sanitarios en M.O. antes que la red hidrosanitaria: es el instalador de aparatos.
        ['kw' => ['REJILLA DE PISO', 'REJILLA PARA DUCHA', 'SIFON', 'CROMAD', 'GRIFERIA', 'LAVAMANOS', 'LAVAPLATOS', 'ORINAL', 'APARATO SANITARIO'], 'paq' => 'M. de O APARATOS SANITARIOS Y GRIFERÍA', 'tipos' => ['MANO DE OBRA'], 'descPrimero' => true],
        ['kw' => ['INSTALACION ELECTRIC', 'ELECTRIC', 'ILUMINACION', 'VOZ Y DATOS'], 'paq' => 'Sum + Inst INSTALACIONES ELÉCTRICAS, VOZ Y DATOS (INTERIORES)', 'tipos' => ['SUBCONTRATO', 'MANO DE OBRA']],
        ['kw' => ['HIDROSANITARI', 'HIDRAULIC', 'SANITARIA', 'DESAGUE', 'TUBERIA PVC', 'RED DE AGUA', 'GARGOLA'], 'paq' => 'Sum + Inst INSTALACIONES HIDROSANITARIAS', 'tipos' => ['SUBCONTRATO', 'MANO DE OBRA', 'MATERIAL']],
        ['kw' => ['RED DE GAS', 'GAS DOMICILIAR', 'INSTALACION DE GAS', 'GAS NATURAL'], 'paq' => 'Sum + Inst RED DE GAS', 'tipos' => ['SUBCONTRATO', 'MANO DE OBRA'], 'descPrimero' => true],
        ['kw' => ['RED CONTRA INCENDIO', 'DETECCION', 'EXTINCION', 'ROCIADOR'], 'paq' => 'Sum + Inst RED CONTRA INCENDIO, DETECCIÓN Y EXTINCIÓN', 'tipos' => ['SUBCONTRATO']],
        ['kw' => ['AIRE ACONDICIONADO', 'EXTRACCION', 'VENTILACION MECANIC'], 'paq' => 'Sum + Inst RED DE AIRE ACONDICIONADO Y EQUIPOS DE EXTRACCIÓN', 'tipos' => ['SUBCONTRATO']],
        ['kw' => ['ASCENSOR'], 'paq' => 'Sum + Inst ASCENSORES', 'tipos' => ['SUBCONTRATO']],
        // Cielos rasos ANTES de pintura y ventanería: el drywallero entrega el cielo pintado.
        ['kw' => ['CIELO', 'DRYWALL', 'SUPERBOARD', 'CIELORRASO', 'FALSO'], 'paq' => 'Sum + Inst CIELOS RASOS', 'tipos' => ['SUBCONTRATO', 'MANO DE OBRA']],
        ['kw' => ['CLOSET', 'MUEBLE', 'COCINA INTEGRAL', 'MOBILIARIO', 'VESTIER', 'ALACENA', 'LINO'], 'paq' => 'Sum + Inst CARPINTERÍA DE MADERA', 'tipos' => ['SUBCONTRATO']],
        // La puerta de madera es producto de catálogo; el closet es medida y despiece: gremios
        // distintos. Y por ser producto se COMPRA: aunque el presupuesto la traiga como subcontrato,
        // su destino es el suministro, no un alcance a todo costo (revisión en obra 2026-07-27).
        ['kw' => ['PUERTA EN MADERA', 'PUERTA MADERA', 'PUERTA CORREDIZA MADERA'], 'paq' => 'Suministro PUERTAS EN MADERA', 'tipos' => ['SUBCONTRATO']],
        ['kw' => ['CARPINTERIA MADERA'], 'paq' => 'Sum + Inst CARPINTERÍA DE MADERA', 'tipos' => ['SUBCONTRATO']],
        ['kw' => ['CABINA', 'DIVISION DE DUCHA', 'DIVISIONES DE BANO', 'ESPEJO'], 'ctx' => ['BANO', 'DUCHA', 'APTO', 'VIDRIO TEMPLADO'], 'paq' => 'Sum + Inst CABINAS Y ESPEJOS DE BAÑOS', 'tipos' => ['SUBCONTRATO', 'MATERIAL'], 'descPrimero' => true],
        ['kw' => ['VENTAN', 'VIDRIO', 'VIDRIER', 'FACHADA FLOTANTE', 'ALUMINIO'], 'paq' => 'Sum + Inst VENTANERÍA', 'tipos' => ['SUBCONTRATO']],
        ['kw' => ['CARPINTERIA METAL', 'PUERTA METAL', 'PUERTA ACCESO', 'PUERTA BATIENTE', 'PUERTA', 'REJA'], 'paq' => 'Sum + Inst CARPINTERÍA METÁLICA', 'tipos' => ['SUBCONTRATO']],

        // ── Mano de obra por OFICIO ──
        // El lagrimal (goterón de fachada) lo instala el mampostero, no un proveedor de urbanismo.
        ['kw' => ['MAMPOSTERIA', 'MURO EN LADRILLO', 'MURO EN BLOQUE', 'MURO EN CATALAN', 'MURO EN CONCRETO', 'MURO LADRILLO', 'LAGRIMAL', 'DOVELA'], 'paq' => 'M. de O MAMPOSTERÍA', 'tipos' => ['MANO DE OBRA', 'SUBCONTRATO'], 'descPrimero' => true],
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
        ['kw' => ['MORTERO DE PISO', 'ALISTADO', 'MORTERO DE NIVELACION', 'AFINADO DE PISO'], 'paq' => 'M. de O MORTEROS DE PISO', 'tipos' => ['MANO DE OBRA', 'SUBCONTRATO'], 'descPrimero' => true],
        // Mediacaña y regata: obra civil en mortero — la ejecuta el mampostero, no el impermeabilizador
        // ni el instalador de pisos (criterio del usuario 2026-07-25).
        ['kw' => ['MEDIA CANA', 'MEDIACANA', 'REGATA'], 'ctx' => ['CUBIERTA', 'IMPERMEABILIZ', 'TERRAZA'], 'paq' => 'M. de O MAMPOSTERÍA', 'tipos' => ['MANO DE OBRA', 'SUBCONTRATO']],

        // Acabado de piso SEPARADO POR OFICIO: el instalador de cerámico no es el de laminado ni el
        // de deck. El zócalo viaja con el piso de SU material (mismo gremio). Sin material → veto.
        ['kw' => ['DECK'], 'paq' => 'M. de O INSTALACIÓN DE PISO PARA DECK', 'tipos' => ['MANO DE OBRA', 'SUBCONTRATO'], 'descPrimero' => true],
        ['kw' => ['MADERA', 'LAMINAD'], 'ctx' => ['PISO', 'ZOCALO', 'GUARDAESCOBA', 'PIRLAN'], 'paq' => 'M. de O INSTALACIÓN DE PISOS EN MADERA', 'tipos' => ['MANO DE OBRA', 'SUBCONTRATO'], 'descPrimero' => true],
        ['kw' => ['PORCELANATO', 'CERAMIC', 'GRES', 'TABLETA', 'BALDOSA', 'ADOQUIN'], 'ctx' => ['PISO', 'ZOCALO', 'GUARDAESCOBA', 'PIRLAN', 'ADOQUIN'], 'paq' => 'M. de O INSTALACIÓN DE PISOS CERÁMICOS', 'tipos' => ['MANO DE OBRA', 'SUBCONTRATO'], 'descPrimero' => true],
        // Mediacaña sin contexto de cubierta: pendiente explícito (no la rellenan tokens/agrupación).
        ['kw' => ['MEDIA CANA', 'MEDIACANA'], 'paq' => self::SIN_PROPUESTA, 'tipos' => ['MANO DE OBRA', 'SUBCONTRATO']],
        // Enchape de MURO. «Pisos y enchapes son el mismo contrato» (revisión en obra 2026-07-27):
        // lo instala el mismo enchapador, así que comparte paquete con el piso.
        ['kw' => ['ENCHAPE', 'CERAMIC', 'PORCELANATO', 'BALDOSA', 'GRES', 'PUENTE ADHERENCIA'], 'paq' => 'M. de O INSTALACIÓN DE PISOS CERÁMICOS', 'tipos' => ['MANO DE OBRA', 'SUBCONTRATO']],
        ['kw' => ['TRANSPORTE INTERNO DE CONCRETO', 'TRANSPORTE INTERNO DE ACERO'], 'paq' => 'M. de O ESTRUCTURA EN CONCRETO', 'tipos' => ['MANO DE OBRA'], 'descPrimero' => true],
        ['kw' => ['PREPARACION MEZCLA', 'TRANSPORTE INTERNO', 'MEZCLA', 'PREPARACION DE CONCRETO'], 'paq' => 'M. de O ESTRUCTURA EN CONCRETO', 'tipos' => ['MANO DE OBRA']],

        // ── Materiales (suministro) — SOLO por la descripción: el nombre identifica el producto ──
        // Prefabricados antes que CONCRETO: su negociación no es volumen de mixer.
        ['kw' => ['CORDON', 'BORDILLO', 'SARDINEL'], 'paq' => 'Suministro BORDILLOS', 'tipos' => ['MATERIAL'], 'soloDesc' => true],
        // El lagrimal (goterón) es prefabricado de interior; los de urbanismo van al otro paquete.
        ['kw' => ['LAGRIMAL', 'SILLAR'], 'paq' => 'Suministro PREFABRICADOS INTERIORES', 'tipos' => ['MATERIAL'], 'soloDesc' => true],
        ['kw' => ['PREFABRICADO'], 'paq' => 'Suministro PREFABRICADOS URBANISMO Y EXTERIORES', 'tipos' => ['MATERIAL'], 'soloDesc' => true],
        ['kw' => ['ALIGERANTE', 'PORON'], 'paq' => 'Suministro ALIGERANTES LOSAS', 'tipos' => ['MATERIAL', 'SUBCONTRATO'], 'descPrimero' => true],
        // Químicos del concreto (curador, desmoldante, acelerante): proveedor propio, no vienen con el
        // mixer ni con la formaleta. Van ANTES de CONCRETO/ENCOFRADO para que no los capturen.
        ['kw' => ['ANTISOL', 'SEPAROL', 'CURADOR', 'DESMOLDANTE', 'ADITIVO', 'ACELERANTE', 'RETARDANTE', 'PLASTIFICANTE'], 'paq' => 'Suministro ADITIVOS DE CONCRETO', 'tipos' => ['MATERIAL'], 'soloDesc' => true],
        // Pegantes y boquillas del enchape: otro proveedor que el porcelanato. Van ANTES de la regla
        // de PORCELANATO/CERAMIC para que el adhesivo no se compre con el piso.
        ['kw' => ['PEGACOR', 'PEGANTE', 'CONCOLOR', 'BOQUILLA', 'ADERCRIL', 'PUENTE ADHERENCIA', 'ADHESIVO', 'FRAGUA'], 'paq' => 'Suministro PEGANTES Y BOQUILLAS', 'tipos' => ['MATERIAL'], 'soloDesc' => true],
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

    /**
     * `$conOverrides = false` corre el motor solo con su conocimiento generalizable (reglas, tokens,
     * agrupación), ignorando la lista curada a mano. Es la medida honesta de cuánto sabe el motor:
     * en DAPORTO los overrides explican el 71,4 % del valor asignado, así que sin distinguirlos la
     * cobertura mide sobre todo el trabajo manual del ejercicio (A3.3).
     */
    public function __construct(private readonly \Database $db, private readonly bool $conOverrides = true)
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
            "SELECT p.id, p.nombre, p.tipo_negociacion, p.modalidad_contratacion, COUNT(a.id) AS insumos_global
             FROM general_paquetes_contratacion p
             LEFT JOIN pdc_insumo_paquete a ON a.paquete_id = p.id
             WHERE {$where}
             GROUP BY p.id, p.nombre, p.tipo_negociacion, p.modalidad_contratacion
             ORDER BY p.nombre ASC",
            $params,
        )->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(static fn (array $r): array => [
            'id' => (int) $r['id'],
            'nombre' => $r['nombre'],
            'tipoNegociacion' => $r['tipo_negociacion'],
            'modalidad' => $r['modalidad_contratacion'],
            'insumosGlobal' => (int) $r['insumos_global'],
        ], $rows);
    }

    /** Crea un paquete global; duplicado por nombre_norm devuelve el existente (reactivado si estaba inactivo). */
    public function crearPaquete(string $nombre, string $tipo, string $usuario, string $modalidad = 'contrato'): array
    {
        $nombre = trim($nombre);
        if ($nombre === '' || !in_array($tipo, self::TIPOS, true) || !in_array($modalidad, self::MODALIDADES, true)) {
            return ['ok' => false, 'code' => 'PAQUETE_INVALIDO'];
        }
        $norm = mb_substr(MaestroInsumosService::normalizar($nombre), 0, 200);

        $existente = $this->db->query(
            'SELECT id, nombre, tipo_negociacion, modalidad_contratacion, activo FROM general_paquetes_contratacion WHERE nombre_norm = ?',
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
                'modalidad' => $existente['modalidad_contratacion'],
                'existente' => 1,
            ]];
        }

        try {
            $this->db->query(
                'INSERT INTO general_paquetes_contratacion (nombre, nombre_norm, tipo_negociacion, modalidad_contratacion, activo, creado_por, created_at)
                 VALUES (?, ?, ?, ?, 1, ?, NOW())',
                [mb_substr($nombre, 0, 200), $norm, $tipo, $modalidad, $usuario],
            );
        } catch (\PDOException $e) {
            // Carrera: otro proceso lo creó entre el SELECT y el INSERT (errno 1062) → devolver el existente.
            if ((int) ($e->errorInfo[1] ?? 0) !== 1062) {
                throw $e;
            }
            $row = $this->db->query(
                'SELECT id, nombre, tipo_negociacion, modalidad_contratacion FROM general_paquetes_contratacion WHERE nombre_norm = ?',
                [$norm],
            )->fetch(\PDO::FETCH_ASSOC);
            return ['ok' => true, 'paquete' => [
                'id' => (int) $row['id'], 'nombre' => $row['nombre'],
                'tipoNegociacion' => $row['tipo_negociacion'],
                'modalidad' => $row['modalidad_contratacion'], 'existente' => 1,
            ]];
        }
        return ['ok' => true, 'paquete' => [
            'id' => (int) $this->db->lastInsertId(),
            'nombre' => mb_substr($nombre, 0, 200),
            'tipoNegociacion' => $tipo,
            'modalidad' => $modalidad,
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

    /**
     * Asignación masiva insumo→paquete (upsert: reasignar mueve, no duplica; limpia omisión).
     *
     * `$procedencia` = ['origen' => capa, 'confianza' => alta|media|baja, 'evidencia' => texto,
     * 'confirmado' => bool] cuando la fila la produce el motor. Sin ella la asignación es un acto
     * humano desde cero. Origen y confirmación son ortogonales a propósito (A3.3):
     *   · origen de motor + confirmado=false → auto-asignada, el re-sembrado puede revisarla;
     *   · origen de motor + confirmado=true  → el humano ACEPTÓ la sugerencia: es un acierto del
     *     motor y así se contabiliza, pero ya es intocable;
     *   · origen 'humano'                     → la persona eligió el destino; no cuenta ni a favor
     *     ni en contra del motor.
     */
    public function asignar(int $projectId, array $insumos, int $paqueteId, string $usuario, array $procedencia = []): array
    {
        $paquete = $this->db->query(
            'SELECT id FROM general_paquetes_contratacion WHERE id = ? AND activo = 1',
            [$paqueteId],
        )->fetchColumn();
        if ($paquete === false) {
            return ['ok' => false, 'code' => 'PAQUETE_INVALIDO'];
        }
        $validos = self::insumosValidos($insumos);
        $origen = in_array($procedencia['origen'] ?? '', self::ORIGENES_MOTOR, true) ? $procedencia['origen'] : 'humano';
        $delMotor = $origen !== 'humano';
        $confianza = $delMotor && in_array($procedencia['confianza'] ?? '', ['alta', 'media', 'baja'], true)
            ? $procedencia['confianza'] : null;
        $evidencia = $delMotor ? mb_substr((string) ($procedencia['evidencia'] ?? ''), 0, 500) : '';
        $confirmado = !$delMotor || ($procedencia['confirmado'] ?? false) === true;

        // Antes de mover nada: si el destino previo lo puso el motor y un humano lo está cambiando,
        // ese par (sugerido → elegido) es la señal más valiosa que tenemos sobre dónde falla.
        if (!$delMotor) {
            $this->registrarCorrecciones($projectId, $validos, $paqueteId, $usuario);
        }

        // Lotes multi-fila (patrón generarVinculos): evita un round-trip por insumo.
        foreach (array_chunk($validos, 200) as $lote) {
            $valores = implode(', ', array_fill(0, count($lote), '(?, ?, ?, ?, 0, ?, ?, ?, ?, ?, NOW())'));
            $params = [];
            foreach ($lote as $u) {
                array_push(
                    $params,
                    $projectId, $u['norm'], $u['unidad'], $paqueteId,
                    $origen, $confianza, $evidencia, $confirmado ? 1 : 0, $usuario,
                );
            }
            $this->db->query(
                "INSERT INTO pdc_insumo_paquete
                    (project_id, descripcion_norm, unidad, paquete_id, omitido, origen, confianza, evidencia, confirmado_humano, asignado_por, updated_at)
                 VALUES {$valores}
                 ON DUPLICATE KEY UPDATE paquete_id = VALUES(paquete_id), omitido = 0,
                    origen = VALUES(origen), confianza = VALUES(confianza), evidencia = VALUES(evidencia),
                    confirmado_humano = VALUES(confirmado_humano),
                    asignado_por = VALUES(asignado_por), updated_at = NOW()",
                $params,
            );
        }
        return ['ok' => true, 'asignados' => count($validos)];
    }

    /**
     * Registra las correcciones humanas sobre destinos que había propuesto el motor.
     * Cuenta cuando el destino previo venía de una capa del motor y cambia — aunque el humano lo
     * hubiera aceptado antes: si se arrepiente, el motor falló y deja de contar como acierto.
     * Reasignar algo que ya era una decisión humana desde cero no es un error del motor.
     */
    private function registrarCorrecciones(int $projectId, array $validos, int $paqueteNuevo, string $usuario): void
    {
        foreach (array_chunk($validos, 200) as $lote) {
            $tuplas = implode(', ', array_fill(0, count($lote), '(?, ?)'));
            $params = [$projectId, $paqueteNuevo];
            foreach ($lote as $u) {
                array_push($params, $u['norm'], $u['unidad']);
            }
            $this->db->query(
                "INSERT INTO pdc_correcciones_motor
                    (project_id, descripcion_norm, unidad, paquete_sugerido, paquete_elegido, capa_sugerida, usuario, created_at)
                 SELECT project_id, descripcion_norm, unidad, paquete_id, ?, origen, ?, NOW()
                 FROM pdc_insumo_paquete
                 WHERE project_id = ? AND origen <> 'humano' AND paquete_id IS NOT NULL
                   AND paquete_id <> ? AND (descripcion_norm, unidad) IN ({$tuplas})",
                array_merge([$paqueteNuevo, $usuario, $projectId, $paqueteNuevo], array_slice($params, 2)),
            );
        }
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

    /**
     * Cobertura de la meta 100% + subtotales por paquete sobre la versión activa.
     *
     * Devuelve TRES indicadores porque uno solo miente (A3.3): por conteo («que no quede nada
     * suelto»), por valor (lo que de verdad mueve la aguja — la cola larga es barata) y la tasa de
     * acierto del motor, que sale de las correcciones humanas y es lo único que dice si el motor es
     * bueno o solo prolijo.
     */
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
                    SUM(CASE WHEN a.omitido = 1 THEN 1 ELSE 0 END) AS omitidos,
                    COALESCE(SUM(v.valor_total), 0) AS valor_total,
                    COALESCE(SUM(CASE WHEN a.paquete_id IS NOT NULL OR a.omitido = 1 THEN v.valor_total ELSE 0 END), 0) AS valor_cubierto
             FROM pdc_insumo_vinculos v
             LEFT JOIN pdc_insumo_paquete a
                    ON a.project_id = v.project_id AND a.descripcion_norm = v.descripcion_norm AND a.unidad = v.unidad
             WHERE v.project_id = ? AND v.version_id = ?',
            [$projectId, $vid],
        )->fetch(\PDO::FETCH_ASSOC);
        $total = (int) $tot['total'];
        $asignados = (int) $tot['asignados'];
        $omitidos = (int) $tot['omitidos'];
        $valorTotal = (float) $tot['valor_total'];
        $valorCubierto = (float) $tot['valor_cubierto'];
        $porPaquete = $this->db->query(
            'SELECT p.id, p.nombre, p.tipo_negociacion, p.modalidad_contratacion, COUNT(*) AS insumos, SUM(v.valor_total) AS subtotal
             FROM pdc_insumo_vinculos v
             JOIN pdc_insumo_paquete a
                   ON a.project_id = v.project_id AND a.descripcion_norm = v.descripcion_norm AND a.unidad = v.unidad
             JOIN general_paquetes_contratacion p ON p.id = a.paquete_id
             WHERE v.project_id = ? AND v.version_id = ?
             GROUP BY p.id, p.nombre, p.tipo_negociacion, p.modalidad_contratacion
             ORDER BY subtotal DESC',
            [$projectId, $vid],
        )->fetchAll(\PDO::FETCH_ASSOC);
        return [
            'version' => ['id' => $vid, 'label' => $version['version_label']],
            'total' => $total,
            'asignados' => $asignados,
            'omitidos' => $omitidos,
            'cobertura' => $total === 0 ? 0.0 : round(($asignados + $omitidos) * 100 / $total, 1),
            'coberturaValor' => $valorTotal <= 0 ? 0.0 : round($valorCubierto * 100 / $valorTotal, 1),
            'acierto' => $this->tasaDeAcierto($projectId),
            'porPaquete' => array_map(static fn (array $r): array => [
                'paqueteId' => (int) $r['id'],
                'nombre' => $r['nombre'],
                'tipoNegociacion' => $r['tipo_negociacion'],
                'modalidad' => $r['modalidad_contratacion'],
                'insumos' => (int) $r['insumos'],
                'subtotal' => (float) $r['subtotal'],
            ], $porPaquete),
        ];
    }

    /**
     * Tasa de acierto del motor en el proyecto: 1 − correcciones / sugerencias aplicadas.
     *
     * `tasa` es null mientras no haya sugerencias aplicadas — un 100 % sin datos sería una mentira
     * cómoda. La base de cálculo va expuesta para que el número sea auditable y no un adorno.
     */
    private function tasaDeAcierto(int $projectId): array
    {
        $aplicadas = (int) $this->db->query(
            "SELECT COUNT(*) FROM pdc_insumo_paquete WHERE project_id = ? AND origen <> 'humano'",
            [$projectId],
        )->fetchColumn();
        $correcciones = (int) $this->db->query(
            'SELECT COUNT(*) FROM pdc_correcciones_motor WHERE project_id = ?',
            [$projectId],
        )->fetchColumn();
        // Las corregidas ya no figuran como del motor (pasaron a 'humano'), así que el universo de
        // decisiones que tomó el motor es lo que sobrevive más lo que se le enmendó.
        $base = $aplicadas + $correcciones;
        return [
            'sugerenciasAplicadas' => $base,
            'correcciones' => $correcciones,
            'tasa' => $base === 0 ? null : round(($base - $correcciones) * 100 / $base, 1),
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
        // El mapa insumo↔actividades se materializa la primera vez que hace falta: una versión
        // importada ya no cambia, así que rehacerlo en cada consulta sería trabajo tirado.
        $this->asegurarActividades($projectId, (int) $ins['version']['id']);

        $catalogo = $this->catalogoActivoPorNombre();
        $overrides = $this->overridesIA($projectId);
        $actMap = $this->actividadDominantePorInsumo($projectId, $versionId);
        $domMap = $this->dominanciaPorInsumo($projectId, $versionId);

        $propuestas = [];
        foreach ($ins['insumos'] as $insumo) {
            $clave = $insumo['descripcionNorm'] . '@@' . mb_strtoupper((string) $insumo['unidad']);
            $rama = $actMap[$clave] ?? ['actividad' => '', 'cadena' => [], 'esIndirecto' => false];
            // Un MATERIAL se compra por lo que es, no por dónde se usa: su rama no debe influir en el
            // destino (A3.3). Solo mano de obra y subcontrato dependen del frente de obra.
            $mandaRama = self::mandaLaActividad($insumo['tipoRecurso'] ?? null);
            $cadena = $mandaRama ? $rama['cadena'] : [];
            $actividad = $mandaRama ? $rama['actividad'] : '';
            // El veto de reglas corta la cascada: ni tokens ni agrupacion deben rellenar un pendiente.
            $porReglas = $this->sugerirOverrideIA($insumo, $overrides, $catalogo)
                ?? $this->sugerirExacta($projectId, $insumo)
                ?? $this->sugerirPorReglas($insumo, $cadena, $catalogo);
            if (($porReglas['veto'] ?? false) === true) {
                $p = null;
            } else {
                $p = $porReglas
                    // Si el propio presupuesto lo cuelga del capítulo de costos indirectos, esa
                    // clasificación estructural pesa más que parecerse a un insumo de otra obra:
                    // se adelanta a la similitud de texto (A3.4).
                    ?? ($rama['esIndirecto'] === true ? $this->sugerirIndirectos($insumo, $catalogo, $rama) : null)
                    ?? $this->sugerirPorTokens($projectId, $insumo)
                    ?? $this->sugerirIndirectos($insumo, $catalogo, $rama)
                    // Último recurso para los MATERIALES que ninguna regla reconoce por su nombre: la
                    // rama es mejor pista que la familia contable de SINCO, que es la que mandaba el
                    // asador a gas a hidrosanitarias. Confianza baja: nunca se auto-asigna.
                    ?? $this->sugerirPorRamaDeMaterial($insumo, $rama['cadena'], $catalogo)
                    ?? $this->sugerirPorAgrupacion($insumo);
            }
            // Si la propuesta se apoyó en una actividad que apenas concentra valor, el motor lo dice
            // en vez de fingir certeza: baja la confianza para que no se auto-asigne y vaya a revisión.
            $dom = $domMap[$clave] ?? null;
            if ($p !== null && $actividad !== '' && $dom !== null
                && $dom['actividades'] > 1 && $dom['peso'] < self::DOMINANCIA_MINIMA) {
                $p['confianza'] = 'baja';
                $p['evidencia'] = rtrim($p['evidencia'], '.') . sprintf(
                    '. Ojo: el insumo se reparte entre %d actividades y la mayor solo concentra el %d%% del valor.',
                    $dom['actividades'],
                    (int) round($dom['peso'] * 100),
                );
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
            'SELECT id, nombre, nombre_norm, tipo_negociacion, modalidad_contratacion, admite_materiales
             FROM general_paquetes_contratacion WHERE activo = 1',
        )->fetchAll(\PDO::FETCH_ASSOC);
        $mapa = [];
        foreach ($rows as $r) {
            $mapa[$r['nombre_norm']] = [
                'id' => (int) $r['id'], 'nombre' => $r['nombre'],
                'tipoNegociacion' => $r['tipo_negociacion'], 'modalidad' => $r['modalidad_contratacion'],
                'admiteMateriales' => (int) $r['admite_materiales'],
            ];
        }
        return $mapa;
    }

    /** Overrides expertos (pasada semántica IA) desde el JSON versionado: NORMA@@UNIDAD → nombre de paquete. */
    /**
     * Overrides curados: NORMA@@UNIDAD → nombre de paquete.
     *
     * Cada entrada declara su `alcance` (A3.3): `global` es vocabulario que sirve en cualquier obra
     * de AIA; `proyecto` es una decisión atada a un presupuesto concreto y solo aplica ahí. La
     * distinción importa porque sin ella la cobertura confunde memoria de un ejercicio con
     * conocimiento del motor. Se acepta también el formato plano (valor string) por compatibilidad.
     */
    private function overridesIA(?int $projectId = null): array
    {
        $ruta = __DIR__ . '/../../../database/seeds/sembrado_ia_overrides.json';
        if (!$this->conOverrides || !is_file($ruta)) {
            return [];
        }
        $data = json_decode((string) file_get_contents($ruta), true);
        $crudos = is_array($data['overrides'] ?? null) ? $data['overrides'] : [];
        $out = [];
        foreach ($crudos as $clave => $valor) {
            if (is_string($valor)) {
                $out[$clave] = $valor;
                continue;
            }
            if (!is_array($valor) || !is_string($valor['paquete'] ?? null)) {
                continue;
            }
            $esDeProyecto = ($valor['alcance'] ?? 'global') === 'proyecto';
            if ($esDeProyecto && (int) ($valor['projectId'] ?? 0) !== (int) $projectId) {
                continue;
            }
            $out[$clave] = $valor['paquete'];
        }
        return $out;
    }

    /** Actividad dominante (mayor valor) por insumo de la versión: NORMA@@UNIDAD → texto de la actividad. */
    /**
     * Actividad dominante de cada insumo + su CADENA DE ANCESTROS, de la actividad hacia arriba.
     *
     * Hasta A3.3 esto devolvía solo el texto de la actividad, y con eso se perdía la señal cuando el
     * oficio no estaba en su nombre: «REBANCO COCINA» no dice «piso» pero cuelga del grupo «PISOS EN
     * ZONAS PRIVADAS». Ahora se devuelve la rama entera para que las reglas puedan subir (A3.4).
     *
     * El capítulo queda FUERA de la cadena a propósito: solo toma dos valores (COSTO DIRECTO /
     * INDIRECTO), así que no puede identificar ningún oficio. Su información viaja aparte, en
     * `esIndirecto`, porque sí dice algo: lo que cuelga de indirectos no se contrata como obra.
     *
     * @return array<string, array{actividad: string, cadena: list<array{descripcion: string, tipoFila: string}>, esIndirecto: bool}>
     */
    private function actividadDominantePorInsumo(int $projectId, ?int $versionId): array
    {
        $version = $this->versionDe($projectId, $versionId);
        if ($version === null) {
            return [];
        }
        $vid = (int) $version['id'];

        // Un solo SELECT de los ítems y la rama se arma en memoria: son ~500 filas por versión y
        // resolverlas con una consulta por nivel serían miles de round-trips.
        $items = $this->db->query(
            'SELECT id, codigo, codigo_padre, tipo_fila, descripcion
             FROM pdc_presupuesto_items WHERE project_id = ? AND version_id = ?',
            [$projectId, $vid],
        )->fetchAll(\PDO::FETCH_ASSOC);
        $porId = [];
        $porCodigo = [];
        foreach ($items as $it) {
            $porId[(int) $it['id']] = $it;
            $porCodigo[(string) $it['codigo']] = $it;
        }

        // Actividad de mayor valor por insumo. Se lee de los APU y no de `pdc_insumo_actividades`
        // a propósito: esa tabla se materializa perezosamente y podría ir por detrás de los datos.
        // Aquí la fuente de verdad manda.
        $filas = $this->db->query(
            'SELECT ai.descripcion, ai.unidad, ai.item_id, it.descripcion AS actividad,
                    SUM(ai.valor_total) AS valor
             FROM pdc_presupuesto_apu_insumos ai
             JOIN pdc_presupuesto_items it ON it.id = ai.item_id
             WHERE ai.project_id = ? AND ai.version_id = ?
             GROUP BY ai.descripcion, ai.unidad, ai.item_id, it.descripcion
             ORDER BY valor DESC',
            [$projectId, $vid],
        )->fetchAll(\PDO::FETCH_ASSOC);

        $mapa = [];
        $cacheRama = [];
        foreach ($filas as $f) {
            $clave = MaestroInsumosService::normalizar((string) $f['descripcion'])
                . '@@' . mb_strtoupper(trim((string) $f['unidad']));
            if (isset($mapa[$clave])) {
                continue; // ya tenemos la de mayor valor: el ORDER BY garantiza que es la primera
            }
            $itemId = (int) $f['item_id'];
            if (!isset($cacheRama[$itemId])) {
                $cadena = [];
                $esIndirecto = false;
                $actual = $porId[$itemId] ?? null;
                while ($actual !== null) {
                    if ($actual['tipo_fila'] === 'capitulo') {
                        // Tope: el capítulo no nombra oficios, solo naturaleza.
                        $esIndirecto = str_contains(mb_strtoupper((string) $actual['descripcion']), 'INDIRECTO');
                        break;
                    }
                    $cadena[] = ['descripcion' => (string) $actual['descripcion'], 'tipoFila' => (string) $actual['tipo_fila']];
                    $padre = $actual['codigo_padre'];
                    $actual = $padre !== null ? ($porCodigo[(string) $padre] ?? null) : null;
                }
                $cacheRama[$itemId] = ['cadena' => $cadena, 'esIndirecto' => $esIndirecto];
            }
            $mapa[$clave] = [
                'actividad' => (string) $f['actividad'],
                'cadena' => $cacheRama[$itemId]['cadena'],
                'esIndirecto' => $cacheRama[$itemId]['esIndirecto'],
            ];
        }
        return $mapa;
    }

    /**
     * Último recurso para un MATERIAL que ninguna regla reconoce por su nombre (A3.4).
     *
     * La doctrina no cambia: un material se compra por lo que es, y por eso su rama no entra en la
     * pasada normal de reglas. Pero cuando el nombre no dice nada, el frente donde se consume es
     * mejor pista que la familia contable de SINCO — que es la capa que mandaba el asador a gas a
     * instalaciones hidrosanitarias. Siempre confianza baja: esto no se auto-asigna nunca.
     *
     * @param list<array{descripcion: string, tipoFila: string}> $cadena
     */
    private function sugerirPorRamaDeMaterial(array $insumo, array $cadena, array $catalogo): ?array
    {
        if ($cadena === [] || self::mandaLaActividad($insumo['tipoRecurso'] ?? null)) {
            return null; // la mano de obra ya usó la rama en la pasada de reglas
        }
        $p = $this->sugerirPorReglas($insumo, $cadena, $catalogo);
        if ($p === null || ($p['veto'] ?? false) === true) {
            return null;
        }
        $p['confianza'] = 'baja';
        $p['evidencia'] = rtrim($p['evidencia'], '.')
            . '. Ningún criterio reconoce este material por su nombre: el destino sale del frente donde se consume.';
        return $p;
    }

    /** Etiqueta legible del nivel jerárquico, para que la evidencia diga de dónde salió el acierto. */
    private static function etiquetaNivel(string $tipoFila): string
    {
        return match ($tipoFila) {
            'actividad' => 'actividad padre',
            'grupo' => 'grupo',
            'subcapitulo' => 'subcapítulo',
            default => 'rama',
        };
    }

    /**
     * Peso de la actividad dominante sobre el valor total del insumo, por clave NORMA@@UNIDAD.
     *
     * Un insumo repartido entre muchos frentes no tiene «su» actividad: en DAPORTO el mortero vive
     * en 33 actividades de 9 oficios y la mayor concentra el 36 %. Elegir esa por unas décimas es
     * una moneda al aire, así que el motor degrada la confianza en vez de fingir certeza (A3.3).
     */
    private function dominanciaPorInsumo(int $projectId, ?int $versionId): array
    {
        $version = $this->versionDe($projectId, $versionId);
        if ($version === null) {
            return [];
        }
        $rows = $this->db->query(
            'SELECT ai.descripcion, ai.unidad, ai.item_id, SUM(ai.valor_total) AS valor
             FROM pdc_presupuesto_apu_insumos ai
             WHERE ai.project_id = ? AND ai.version_id = ?
             GROUP BY ai.descripcion, ai.unidad, ai.item_id',
            [$projectId, (int) $version['id']],
        )->fetchAll(\PDO::FETCH_ASSOC);

        $acum = [];
        foreach ($rows as $r) {
            $clave = MaestroInsumosService::normalizar((string) $r['descripcion']) . '@@' . mb_strtoupper(trim((string) $r['unidad']));
            $valor = (float) $r['valor'];
            $acum[$clave]['total'] = ($acum[$clave]['total'] ?? 0.0) + $valor;
            $acum[$clave]['max'] = max($acum[$clave]['max'] ?? 0.0, $valor);
            $acum[$clave]['n'] = ($acum[$clave]['n'] ?? 0) + 1;
        }
        $out = [];
        foreach ($acum as $clave => $a) {
            // Sin valor no hay dominancia medible: se trata como concentrado para no penalizar de más.
            $out[$clave] = [
                'peso' => $a['total'] > 0 ? $a['max'] / $a['total'] : 1.0,
                'actividades' => $a['n'],
            ];
        }
        return $out;
    }

    /** Materializa el mapa solo si esa versión aún no lo tiene (una versión importada no cambia). */
    private function asegurarActividades(int $projectId, int $versionId): void
    {
        $hay = (int) $this->db->query(
            'SELECT COUNT(*) FROM pdc_insumo_actividades WHERE project_id = ? AND version_id = ?',
            [$projectId, $versionId],
        )->fetchColumn();
        if ($hay === 0) {
            $this->materializarActividades($projectId, $versionId);
        }
    }

    /**
     * Materializa el mapa insumo↔actividades de una versión (idempotente: reemplaza el de esa versión).
     *
     * Seguimiento necesita la fecha de la PRIMERA actividad que consume cada insumo —para una orden
     * de compra el plan garantiza la primera entrega—, y eso no se puede sacar de la dominante. En
     * A4 cada fila recibirá su `unique_id` de `programa_consolidado`.
     */
    public function materializarActividades(int $projectId, ?int $versionId = null): ?array
    {
        $version = $this->versionDe($projectId, $versionId);
        if ($version === null) {
            return null;
        }
        $vid = (int) $version['id'];
        $rows = $this->db->query(
            'SELECT ai.descripcion, ai.unidad, ai.item_id, it.codigo, it.descripcion AS actividad,
                    SUM(ai.cantidad_total) AS cantidad, SUM(ai.valor_total) AS valor
             FROM pdc_presupuesto_apu_insumos ai
             JOIN pdc_presupuesto_items it ON it.id = ai.item_id
             WHERE ai.project_id = ? AND ai.version_id = ?
             GROUP BY ai.descripcion, ai.unidad, ai.item_id, it.codigo, it.descripcion',
            [$projectId, $vid],
        )->fetchAll(\PDO::FETCH_ASSOC);

        $this->db->query('DELETE FROM pdc_insumo_actividades WHERE project_id = ? AND version_id = ?', [$projectId, $vid]);
        $filas = 0;
        foreach (array_chunk($rows, 200) as $lote) {
            $valores = implode(', ', array_fill(0, count($lote), '(?, ?, ?, ?, ?, ?, ?, ?, ?)'));
            $params = [];
            foreach ($lote as $r) {
                array_push(
                    $params,
                    $projectId, $vid,
                    mb_substr(MaestroInsumosService::normalizar((string) $r['descripcion']), 0, 500),
                    mb_substr(mb_strtoupper(trim((string) $r['unidad'])), 0, 20),
                    (int) $r['item_id'],
                    mb_substr((string) $r['codigo'], 0, 50),
                    mb_substr((string) $r['actividad'], 0, 500),
                    round((float) $r['cantidad'], 4),
                    round((float) $r['valor'], 2),
                );
            }
            $this->db->query(
                "INSERT INTO pdc_insumo_actividades
                    (project_id, version_id, descripcion_norm, unidad, item_id, codigo, actividad, cantidad, valor)
                 VALUES {$valores}",
                $params,
            );
            $filas += count($lote);
        }
        return ['versionId' => $vid, 'filas' => $filas];
    }

    /**
     * Umbral de valor por defecto para la auto-asignación (A3.3, decisión del usuario 2026-07-26).
     *
     * Por debajo, una sugerencia de confianza alta se aplica sola; por encima, la ve un humano.
     * Con DAPORTO eso deja ~90 insumos en manos del experto, que son el 80 % del presupuesto: el
     * Pareto es tan agudo (93 insumos = 80 % del valor) que revisar todo cuesta lo mismo que revisar
     * lo que de verdad importa.
     */
    public const UMBRAL_AUTO_ASIGNACION = 20000000.0;

    /**
     * Reparte las propuestas entre lo que el motor puede aplicar solo y lo que necesita un humano.
     *
     * Solo se auto-asigna confianza alta por debajo del umbral. Todo lo caro y todo lo dudoso queda
     * en `revision` con el motivo, para que la UI explique por qué pide atención. Nada de lo que un
     * humano ya decidió entra siquiera en el reparto.
     */
    public function planAutoAsignacion(int $projectId, ?int $versionId = null, ?float $umbral = null): ?array
    {
        $umbral ??= self::UMBRAL_AUTO_ASIGNACION;
        $r = $this->proponerSembrado($projectId, $versionId, 'sin_asignar');
        if ($r === null) {
            return null;
        }
        $auto = [];
        $revision = [];
        foreach ($r['propuestas'] as $p) {
            if ($p['propuesta'] === null) {
                continue;
            }
            $fila = [
                'descripcionNorm' => $p['descripcionNorm'],
                'unidad' => $p['unidad'],
                'descripcion' => $p['descripcion'],
                'valorTotal' => (float) $p['valorTotal'],
                'paqueteId' => (int) $p['propuesta']['paqueteId'],
                'paqueteNombre' => $p['propuesta']['paqueteNombre'],
                'capa' => $p['propuesta']['capa'],
                'confianza' => $p['propuesta']['confianza'],
                'evidencia' => $p['propuesta']['evidencia'],
            ];
            if ($fila['confianza'] !== 'alta') {
                $revision[] = $fila + ['motivo' => 'confianza'];
            } elseif ($fila['valorTotal'] >= $umbral) {
                $revision[] = $fila + ['motivo' => 'valor'];
            } else {
                $auto[] = $fila;
            }
        }
        return [
            'version' => $r['version'],
            'umbral' => $umbral,
            'auto' => $auto,
            'revision' => $revision,
        ];
    }

    /** Aplica SOLO la parte automática del plan, con su procedencia y sin confirmar por humano. */
    public function aplicarAutoAsignacion(int $projectId, ?int $versionId, ?float $umbral, string $usuario): ?array
    {
        $plan = $this->planAutoAsignacion($projectId, $versionId, $umbral);
        if ($plan === null) {
            return null;
        }
        // Un lote por (paquete, capa, confianza) para no perder la evidencia de cada grupo.
        $grupos = [];
        foreach ($plan['auto'] as $a) {
            $clave = $a['paqueteId'] . '|' . $a['capa'];
            $grupos[$clave]['paqueteId'] = $a['paqueteId'];
            $grupos[$clave]['capa'] = $a['capa'];
            $grupos[$clave]['evidencia'] ??= $a['evidencia'];
            $grupos[$clave]['insumos'][] = ['descripcionNorm' => $a['descripcionNorm'], 'unidad' => $a['unidad']];
        }
        $asignados = 0;
        foreach ($grupos as $g) {
            $r = $this->asignar($projectId, $g['insumos'], $g['paqueteId'], $usuario, [
                'origen' => $g['capa'],
                'confianza' => 'alta',
                'evidencia' => $g['evidencia'],
                'confirmado' => false,
            ]);
            $asignados += $r['ok'] ? (int) $r['asignados'] : 0;
        }
        return ['asignados' => $asignados, 'aRevision' => count($plan['revision']), 'umbral' => $plan['umbral']];
    }

    /**
     * Duraciones del proceso de contratación de un paquete, vía el puente `duracion_ref` (A3.3).
     *
     * El catálogo legacy `general_dias_procesos_contratacion` guarda las filas sin el prefijo de
     * tipo («CONCRETO», no «Suministro CONCRETO»), así que buscarlas por nombre no encontraba nada.
     * Devuelve null cuando el paquete no tiene fila emparejada: A4 deberá resolverlo con un default
     * por modalidad, nunca inventando días.
     */
    public function duracionesDePaquete(int $paqueteId): ?array
    {
        $r = $this->db->query(
            'SELECT d.paqueteContratacion, d.tipoPaquete, d.diasElaboracionPliegos, d.diasEntregaPliegos,
                    d.diasReciboPropuestas, d.diasCuadrosComparativos, d.diasLegalizacionContrato,
                    d.diasFabricacion, d.diasInsumosObra
             FROM general_paquetes_contratacion p
             JOIN general_dias_procesos_contratacion d ON d.id = p.duracion_ref
             WHERE p.id = ?',
            [$paqueteId],
        )->fetch(\PDO::FETCH_ASSOC);
        if ($r === false) {
            return null;
        }
        $pasos = [
            'elaboracionPliegos' => (int) $r['diasElaboracionPliegos'],
            'entregaPliegos' => (int) $r['diasEntregaPliegos'],
            'reciboPropuestas' => (int) $r['diasReciboPropuestas'],
            'cuadrosComparativos' => (int) $r['diasCuadrosComparativos'],
            'legalizacionContrato' => (int) $r['diasLegalizacionContrato'],
            'fabricacion' => (int) $r['diasFabricacion'],
            'insumosObra' => (int) $r['diasInsumosObra'],
        ];
        return [
            'filaLegacy' => (string) $r['paqueteContratacion'],
            'tipoLegacy' => (string) $r['tipoPaquete'],
            'pasos' => $pasos,
            'diasTotales' => array_sum($pasos),
        ];
    }

    /**
     * ¿Ese tipo de recurso puede ir a ese paquete? Expone la doctrina de compatibilidad para la UI y
     * para quien audite una asignación, sin tener que replicar el cuadro en el cliente.
     */
    public function tipoRecursoAdmitido(?string $tipoRecurso, int $paqueteId): bool
    {
        $r = $this->db->query(
            'SELECT tipo_negociacion, modalidad_contratacion, admite_materiales
             FROM general_paquetes_contratacion WHERE id = ? AND activo = 1',
            [$paqueteId],
        )->fetch(\PDO::FETCH_ASSOC);
        if ($r === false) {
            return false;
        }
        // Los buckets sin proceso no son una negociación: aceptan lo que sea (ver resolverPaquete).
        if (in_array($r['modalidad_contratacion'], self::MODALIDADES_SIN_PROCESO, true)) {
            return true;
        }
        if (!in_array($r['tipo_negociacion'], self::tiposCompatibles($tipoRecurso), true)) {
            return false;
        }
        return !(mb_strtoupper((string) $tipoRecurso) === 'MATERIAL'
            && $r['tipo_negociacion'] === 'a_todo_costo'
            && (int) $r['admite_materiales'] !== 1);
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
        // Los buckets sin proceso de contratación (nómina, imprevistos, provisiones, ferretería,
        // tecnología) no son una negociación: nadie los licita, así que el cuadro de compatibilidad
        // tipo_recurso ↔ tipo_negociacion no aplica. Ese cuadro existe para no meter un material en
        // un paquete de mano de obra, no para impedir que una partida global caiga en su bucket.
        if (in_array($paq['modalidad'] ?? 'contrato', self::MODALIDADES_SIN_PROCESO, true)) {
            return $paq;
        }
        if (!in_array($paq['tipoNegociacion'], self::tiposCompatibles($tipoRecurso), true)) {
            return null;
        }
        // Doble conteo: si el paquete es a todo costo, el material lo pone el contratista. Que además
        // figure como insumo asignado ahí es contarlo dos veces (A3.3). Los paquetes que compran
        // producto terminado —dotación, planta eléctrica— llevan la excepción marcada en el catálogo.
        if (mb_strtoupper((string) $tipoRecurso) === 'MATERIAL'
            && $paq['tipoNegociacion'] === 'a_todo_costo'
            && (int) ($paq['admiteMateriales'] ?? 0) !== 1) {
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
    /**
     * @param list<array{descripcion: string, tipoFila: string}> $cadena rama de la actividad hacia
     *        arriba (actividad → grupo → subcapítulo), sin el capítulo.
     */
    private function sugerirPorReglas(array $insumo, array $cadena, array $catalogo): ?array
    {
        $desc = self::henoParaReglas($insumo['descripcionNorm']);
        $tipoRecurso = mb_strtoupper((string) ($insumo['tipoRecurso'] ?? ''));

        // Doctrina de precedencia (feedback del usuario 2026-07-25):
        //  1) Si la propia DESCRIPCION nombra el oficio/material, manda (reglas `descPrimero`) — p.ej.
        //     «M.O. ZOCALO EN PORCELANATO» es del enchapador aunque su actividad dominante sea de madera.
        //  2) Si la descripcion calla, el material/trabajo lo aporta la ACTIVIDAD PADRE dominante.
        //  3) Respaldo: el resto de reglas sobre la descripcion.
        // Las reglas de material (`soloDesc`) nunca miran la actividad: el nombre identifica el producto.
        // Orden de pasadas (A3.4). Los ancestros se recorren de abajo arriba —gana el nivel más
        // cercano, que es el que mejor describe lo que se ejecuta—, pero la descripción del propio
        // insumo se agota ANTES de subir más allá de la actividad directa. Sin eso, un paraguas como
        // «MAMPOSTERIA Y REVOQUE» se lleva un «M.O. ENCHAPE CERAMICA» que su propio nombre resolvía:
        // el subcapítulo agrupa oficios distintos y no puede pisar al insumo.
        $eslabonPasada = static fn (array $e): array => [
            'heno' => self::henoParaReglas(MaestroInsumosService::normalizar($e['descripcion'])),
            'origen' => 'actividad',
            'nivel' => $e['tipoFila'],
            'texto' => $e['descripcion'],
            'soloDescPrimero' => false,
        ];
        $actividadDirecta = ($cadena[0]['tipoFila'] ?? '') === 'actividad' ? array_slice($cadena, 0, 1) : [];
        $ancestros = array_slice($cadena, count($actividadDirecta));

        $pasadas = [['heno' => $desc, 'origen' => 'descripcion', 'nivel' => '', 'soloDescPrimero' => true]];
        foreach ($actividadDirecta as $e) {
            $pasadas[] = $eslabonPasada($e);
        }
        $pasadas[] = ['heno' => $desc, 'origen' => 'descripcion', 'nivel' => '', 'soloDescPrimero' => false];
        foreach ($ancestros as $e) {
            $pasadas[] = $eslabonPasada($e);
        }

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
                        ? 'en su ' . self::etiquetaNivel((string) $pasada['nivel']) . " «{$pasada['texto']}»"
                        : 'en la descripcion del insumo';
                    // Veto explicito: sin senal suficiente es preferible dejarlo pendiente que inventar.
                    if ($regla['paq'] === self::SIN_PROPUESTA) {
                        return [
                            'veto' => true, 'capa' => 'reglas', 'confianza' => 'media',
                            'paqueteId' => 0, 'paqueteNombre' => '',
                            'evidencia' => "Regla de veto: «{$kw}» {$donde} sin contexto suficiente → queda pendiente.",
                        ];
                    }
                    // Doble conteo: la regla acierta el oficio, pero el destino es «a todo costo» y
                    // este insumo es un material. Prohibir no puede significar mandarlo a cualquier
                    // otro sitio — sin este veto el material caía en el primer fallback de tokens o
                    // agrupación, que es como un enchape acababa en griferías. Va a revisión humana.
                    $destino = $catalogo[mb_substr(MaestroInsumosService::normalizar($regla['paq']), 0, 200)] ?? null;
                    if ($destino !== null
                        && mb_strtoupper((string) ($insumo['tipoRecurso'] ?? '')) === 'MATERIAL'
                        && $destino['tipoNegociacion'] === 'a_todo_costo'
                        && (int) ($destino['admiteMateriales'] ?? 0) !== 1) {
                        return [
                            'veto' => true, 'capa' => 'reglas', 'confianza' => 'baja',
                            'paqueteId' => 0, 'paqueteNombre' => '',
                            'evidencia' => "«{$kw}» {$donde} apunta a «{$destino['nombre']}», que es a todo costo: "
                                . 'el material lo pondría el contratista y asignarlo aquí sería contarlo dos veces. Requiere decisión humana.',
                        ];
                    }
                    $paq = $this->resolverPaquete($regla['paq'], $insumo['tipoRecurso'] ?? null, $catalogo, $insumo['descripcionNorm']);
                    if ($paq !== null) {
                        // La confianza la da la evidencia, no la capa: si el propio nombre del insumo
                        // dice el oficio o el material («CONCRETO 3000PSI», «M.O. MURO EN LADRILLO»),
                        // no hay inferencia que valga y es tan fiable como una decisión curada. Si el
                        // destino se dedujo de la actividad padre, sí hay un salto y queda en media.
                        // (La dominancia débil la degrada después a baja, en proponerSembrado.)
                        return [
                            'paqueteId' => $paq['id'], 'paqueteNombre' => $paq['nombre'],
                            'capa' => 'reglas',
                            'confianza' => $pasada['origen'] === 'descripcion' ? 'alta' : 'media',
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
    /**
     * @param array{actividad: string, cadena: list<array{descripcion: string, tipoFila: string}>, esIndirecto: bool} $rama
     */
    private function sugerirIndirectos(array $insumo, array $catalogo, array $rama = ['actividad' => '', 'cadena' => [], 'esIndirecto' => false]): ?array
    {
        $tipoRecurso = mb_strtoupper((string) ($insumo['tipoRecurso'] ?? ''));
        // La rama cuenta aquí aunque el insumo sea un material: lo que cuelga del capítulo de costos
        // indirectos no se le compra a un contratista de alcance, sea lo que sea (A3.4). Y el nombre
        // del frente («PAPELERIA Y UTILES») suele decirlo cuando el del insumo calla.
        $heno = $insumo['descripcionNorm'];
        foreach ($rama['cadena'] as $eslabon) {
            $heno .= ' ' . MaestroInsumosService::normalizar($eslabon['descripcion']);
        }
        $desc = ' ' . $heno . ' ';
        $casa = static function (array $kws) use ($desc): ?string {
            foreach ($kws as $kw) {
                if (self::casaKeyword($desc, $kw)) { return $kw; }
            }
            return null;
        };

        // Orden deliberado: primero lo que NO se le compra a nadie (nomina, imprevistos), luego lo que
        // se compra sin proceso (ferreteria) y por ultimo el bucket administrativo.
        $destino = null; $motivo = '';
        if ($tipoRecurso === 'NOMINA') {
            $destino = self::PAQUETE_NOMINA;
            $motivo = 'personal propio de obra (tipo de recurso NOMINA)';
        } elseif (($kw = $casa(['IMPREVISTO', 'PROVISION', 'AIU', 'RESERVA PRESUPUESTAL'])) !== null) {
            $destino = self::PAQUETE_IMPREVISTOS;
            $motivo = "«{$kw}»: reserva presupuestal, no se le compra a nadie";
        } elseif (($kw = $casa(self::KEYWORDS_FERRETERIA)) !== null) {
            $destino = self::PAQUETE_FERRETERIA;
            $motivo = "«{$kw}»: se pide a necesidad contra almacen, sin proceso de contratacion";
        } elseif ($tipoRecurso === 'HONORARIOS' || $tipoRecurso === 'CONSUMIBLES') {
            $destino = self::PAQUETE_INDIRECTOS;
            $motivo = "tipo de recurso {$tipoRecurso}";
        } elseif (($kw = $casa(self::KEYWORDS_INDIRECTOS)) !== null) {
            $destino = self::PAQUETE_INDIRECTOS;
            $motivo = "«{$kw}» en la descripcion";
        } elseif ($rama['esIndirecto'] === true) {
            $destino = self::PAQUETE_INDIRECTOS;
            $motivo = 'cuelga del capitulo de costos indirectos del presupuesto';
        }
        if ($destino === null) {
            return null;
        }

        $paq = $catalogo[mb_substr(MaestroInsumosService::normalizar($destino), 0, 200)] ?? null;
        if ($paq === null) {
            return null;
        }
        return [
            'paqueteId' => $paq['id'], 'paqueteNombre' => $paq['nombre'],
            'capa' => 'indirectos', 'confianza' => 'media',
            'evidencia' => "Sin proceso de contratacion ({$motivo}) -> {$paq['nombre']}.",
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

    /**
     * Top-N paquetes candidatos para CADA insumo sin asignar, con confianza (0-100).
     * Pensado para el asistente («elige entre estas 3») y para revisar la cola larga: donde el
     * sembrado no tiene una regla clara, al menos ofrece opciones ordenadas y explicadas.
     *
     * Señales (en orden de peso): regla de dominio > tokens de la descripción contra el nombre del
     * paquete > tokens de la actividad padre > consenso local por agrupación SINCO. Filtro duro de
     * compatibilidad de tipo (un suministro no puede caer en un paquete de mano de obra).
     */
    public function alternativas(int $projectId, ?int $versionId = null, int $n = 3): ?array
    {
        $sin = $this->insumosDeVersion($projectId, 'sin_asignar', $versionId);
        if ($sin === null) {
            return null;
        }
        $catalogo = $this->catalogoActivoPorNombre();
        $overrides = $this->overridesIA($projectId);
        $actMap = $this->actividadDominantePorInsumo($projectId, $versionId);

        // Consenso local: qué paquetes ya recibieron insumos de cada agrupación SINCO en el proyecto.
        $porAgrupacion = [];
        $filas = $this->db->query(
            'SELECT m.agrupacion, p.nombre, COUNT(*) AS n
             FROM pdc_insumo_paquete a
             JOIN general_paquetes_contratacion p ON p.id = a.paquete_id AND p.activo = 1
             JOIN general_maestro_insumos m ON m.descripcion_norm = a.descripcion_norm AND m.unidad = a.unidad
             WHERE a.project_id = ? AND m.agrupacion IS NOT NULL
             GROUP BY m.agrupacion, p.nombre',
            [$projectId],
        )->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($filas as $f) {
            $porAgrupacion[(string) $f['agrupacion']][(string) $f['nombre']] = (int) $f['n'];
        }

        $salida = [];
        foreach ($sin['insumos'] as $insumo) {
            $clave = $insumo['descripcionNorm'] . '@@' . mb_strtoupper((string) $insumo['unidad']);
            $rama = $actMap[$clave] ?? ['actividad' => '', 'cadena' => [], 'esIndirecto' => false];
            $actividad = (string) $rama['actividad'];
            $tokIns = self::tokens($insumo['descripcionNorm']);
            $tokAct = $actividad !== '' ? self::tokens(MaestroInsumosService::normalizar($actividad)) : [];
            $agrup = (string) ($insumo['agrupacion'] ?? '');

            $cands = [];
            foreach ($catalogo as $normPaq => $paq) {
                if ($this->resolverPaquete($paq['nombre'], $insumo['tipoRecurso'] ?? null, $catalogo, $insumo['descripcionNorm']) === null) {
                    continue; // incompatible por tipo
                }
                $tokPaq = self::tokens($normPaq);
                if ($tokPaq === []) {
                    continue;
                }
                $hitDesc = count(array_intersect($tokIns, $tokPaq));
                $hitAct = count(array_intersect($tokAct, $tokPaq));
                $consenso = $agrup !== '' ? min(4, (int) ($porAgrupacion[$agrup][$paq['nombre']] ?? 0)) : 0;
                $score = (3.0 * $hitDesc) + (2.0 * $hitAct) + (0.5 * $consenso);
                if ($score <= 0.0) {
                    continue;
                }
                $cands[] = [
                    'paquete' => $paq['nombre'],
                    'tipoNegociacion' => $paq['tipoNegociacion'],
                    'score' => $score,
                    'motivo' => self::motivoAlternativa($hitDesc, $hitAct, $consenso, $agrup),
                ];
            }

            // La propuesta del motor (regla/override) encabeza siempre, con confianza alta.
            $delMotor = $this->sugerirOverrideIA($insumo, $overrides, $catalogo)
                ?? $this->sugerirPorReglas($insumo, $rama['cadena'], $catalogo);
            if ($delMotor !== null && ($delMotor['veto'] ?? false) !== true) {
                $cands = array_values(array_filter($cands, static fn (array $c): bool => $c['paquete'] !== $delMotor['paqueteNombre']));
                array_unshift($cands, [
                    'paquete' => $delMotor['paqueteNombre'],
                    'tipoNegociacion' => '',
                    'score' => 12.0,
                    'motivo' => $delMotor['evidencia'],
                ]);
            }

            usort($cands, static fn (array $a, array $b): int => $b['score'] <=> $a['score']);
            $top = array_slice($cands, 0, $n);
            foreach ($top as $i => $c) {
                $top[$i]['confianza'] = self::confianzaDeScore((float) $c['score']);
                unset($top[$i]['score']);
            }
            $salida[] = [
                'descripcionNorm' => $insumo['descripcionNorm'],
                'unidad' => $insumo['unidad'],
                'descripcion' => $insumo['descripcion'],
                'tipoRecurso' => $insumo['tipoRecurso'],
                'agrupacion' => $insumo['agrupacion'],
                'valorTotal' => $insumo['valorTotal'],
                'actividad' => $actividad,
                'opciones' => $top,
            ];
        }
        return ['version' => $sin['version'], 'insumos' => $salida];
    }

    /** Traduce el score de una alternativa a una confianza legible (0-100). */
    private static function confianzaDeScore(float $score): int
    {
        return match (true) {
            $score >= 12.0 => 85,
            $score >= 9.0 => 75,
            $score >= 6.0 => 62,
            $score >= 4.5 => 50,
            $score >= 3.0 => 40,
            $score >= 2.0 => 30,
            default => 20,
        };
    }

    /** Explica de dónde sale una alternativa (para que el humano pueda juzgarla). */
    private static function motivoAlternativa(int $hitDesc, int $hitAct, int $consenso, string $agrup): string
    {
        $partes = [];
        if ($hitDesc > 0) {
            $partes[] = "{$hitDesc} palabra(s) en común con el nombre del insumo";
        }
        if ($hitAct > 0) {
            $partes[] = "{$hitAct} con su actividad padre";
        }
        if ($consenso > 0) {
            $partes[] = "otros insumos de «{$agrup}» ya van a ese paquete";
        }
        return $partes === [] ? 'Afinidad de tipo de recurso.' : ucfirst(implode('; ', $partes)) . '.';
    }

}
