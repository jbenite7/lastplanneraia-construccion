<?php

declare(strict_types=1);

namespace App\Services\Pdc;

/**
 * Los valores de `general_maestro_insumos.tipo_recurso` que designan equipo, y la pista de SINCO.
 *
 * `tipo_recurso` NO es un enum: es `varchar(60)` que siembra el importador SINCO desde la columna
 * «TIPO DESCRIPCION» del Excel del maestro. Por eso partir «Equipo» en alquilado y comprado no llevó
 * DDL de enum: es un cambio de datos y de reglas de lectura.
 *
 * Esta clase es el único sitio donde viven los strings, para que partir un tipo de recurso no se
 * convierta en literales sueltos por el código — el error que ya se pagó al partir el bucket de
 * indirectos en A3.2 (ver `docs/pdc-v2.md` §deudas de datos saldadas).
 *
 * OJO: el espejo de estos valores en la SPA es `pdc-app/src/lib/tipoRecurso.ts`, y tienen que
 * coincidir EXACTAMENTE. Cada lado lleva un test que fija los strings, por lo mismo que se fijaron
 * los cinco de `TIPOS_NEGOCIACION`: una divergencia no rompe nada visible hasta que alguien intenta
 * guardar, y entonces falla sin explicar por qué.
 */
final class TipoRecursoEquipo
{
    /** El valor que SINCO viene emitiendo y que ya no clasifica nada. */
    public const GENERICO = 'EQUIPO';

    /** Estado de tránsito: sabemos que es equipo, no sabemos si se alquila o se compra. */
    public const SIN_CLASIFICAR = 'EQUIPO (SIN CLASIFICAR)';

    /**
     * Adopta el valor que SINCO ya emite (había 2 filas con él en el maestro) en vez de inventar un
     * sinónimo: si no, cada carga del catálogo reabriría la deuda con dos nombres para lo mismo.
     */
    public const ALQUILADO = 'ALQUILER EQUIPOS';

    /**
     * Valor nuevo. SINCO no emite ninguno para esto: los insumos de compra le llegan como `EQUIPO`
     * con la `agrupacion` en `COMPRA ELEMENTOS-…`, así que aquí no había nada que adoptar.
     */
    public const COMPRADO = 'EQUIPO COMPRADO';

    /** Los dos destinos a los que un humano puede llevar un equipo. */
    public const CLASIFICADOS = [self::ALQUILADO, self::COMPRADO];

    /** Las cuatro formas en que un `tipo_recurso` puede designar equipo. */
    public const TODOS = [self::GENERICO, self::SIN_CLASIFICAR, self::ALQUILADO, self::COMPRADO];

    /**
     * Prefijos de `agrupacion` que sugieren un destino. Deliberadamente cortos y sin ambigüedad.
     *
     * `MTTO …` no está y no debe estar: mantener un equipo no dice de quién es.
     */
    private const PISTAS = [
        'ALQUILER' => self::ALQUILADO,
        'ALQUILERES' => self::ALQUILADO,
        'ARRIENDO' => self::ALQUILADO,
        'COMPRA' => self::COMPRADO,
        'COMPRAS' => self::COMPRADO,
    ];

    private static function norm(?string $t): string
    {
        return mb_strtoupper(trim((string) $t));
    }

    /** ¿Este tipo de recurso designa equipo, en cualquiera de sus cuatro formas? */
    public static function esEquipo(?string $tipo): bool
    {
        return in_array(self::norm($tipo), self::TODOS, true);
    }

    /** ¿Un humano ya dijo si se alquila o se compra? */
    public static function esClasificado(?string $tipo): bool
    {
        return in_array(self::norm($tipo), self::CLASIFICADOS, true);
    }

    /**
     * ¿Es un destino al que la API permite mover un equipo?
     *
     * «Sin clasificar» NO lo es: clasificar es avanzar, y devolver algo a la cola no es una operación
     * que esta puerta ofrezca.
     */
    public static function esDestinoValido(string $tipo): bool
    {
        return in_array(self::norm($tipo), self::CLASIFICADOS, true);
    }

    /**
     * Destino que sugiere la agrupación SINCO, o null si no dice nada.
     *
     * Es una SUGERENCIA para mostrar en la cola junto a su evidencia, nunca una escritura automática:
     * adivinar sin confirmación humana está descartado por el grilleo, y es justo lo que el módulo
     * evita en todo lo demás. Sólo lee `agrupacion` —un campo que el equipo de presupuestos escribió
     * a propósito—, jamás la descripción del insumo.
     *
     * Medido en el maestro real: de 167 equipos, 89 traen prefijo `ALQUILER`, 53 `COMPRA` y 3 `COMPRAS` — 145 de 167.
     */
    public static function pistaSinco(?string $agrupacion): ?string
    {
        // `explode` siempre devuelve al menos un elemento, así que el índice 0 existe: con la cadena
        // vacía devuelve [''], y de ahí sale el early return.
        $primera = explode(' ', self::norm($agrupacion))[0];
        if ($primera === '') {
            return null;
        }
        return self::PISTAS[$primera] ?? null;
    }
}
