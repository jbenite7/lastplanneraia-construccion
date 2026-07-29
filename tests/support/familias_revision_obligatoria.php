<?php

declare(strict_types=1);

/**
 * Familias ACTIVAS que hoy exigen revisión humana, y por qué.
 *
 * Varios tests de cierre de goal afirmaban que no quedaba ninguna, apoyándose en
 * `database/migrations/20260711_apply_human_family_decisions.sql`, que efectivamente puso
 * `siempre_revision = 0` en todas. Pero hay una migración POSTERIOR,
 * `20260713_seed_v1_0_test_contract_families.sql`, cuya línea 146 vuelve a marcar cinco:
 *
 *     UPDATE `general_pdc_familias` SET `siempre_revision` = 1
 *      WHERE `codigo` IN ('CAMPAMENTO', 'RED_TELECOMUNICACIONES', 'ASEO',
 *                         'BOTADA_ESCOMBROS', 'AMENIDADES_CUBIERTA');
 *
 * De esas cinco solo dos siguen activas, así que son las dos que un catálogo sano debe mostrar.
 * La base no está desactualizada: son los tests los que se habían quedado en el estado anterior.
 *
 * Esto deja de ser evidencia de un cierre pasado y pasa a ser una guarda viva: si alguien cambia
 * estas marcas sin querer —o aplica de nuevo la 20260711 encima de la 20260713—, los tests que la
 * usan lo señalan. Si la decisión de negocio cambia, se actualiza aquí y en un solo sitio.
 */

/** @var list<string> códigos ordenados alfabéticamente, para comparar contra un `ORDER BY codigo` */
const FAMILIAS_REVISION_OBLIGATORIA = ['ASEO', 'RED_TELECOMUNICACIONES'];

/**
 * Códigos de familias activas con revisión obligatoria, tal cual los tiene la base.
 *
 * @return list<string>
 */
function familiasConRevisionObligatoria(Database $db): array
{
    return $db->query(
        'SELECT codigo
         FROM general_pdc_familias
         WHERE COALESCE(activa, 1) = 1
           AND COALESCE(siempre_revision, 0) = 1
         ORDER BY codigo',
    )->fetchAll(PDO::FETCH_COLUMN);
}
