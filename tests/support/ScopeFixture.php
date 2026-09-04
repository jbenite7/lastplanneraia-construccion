<?php

declare(strict_types=1);

use App\Security\DataScope\ProjectScope;
use App\Security\DataScope\SystemScopeRunner;

/**
 * Declara el alcance de datos de un tramo de test, para pruebas que hablan con la base
 * directamente en vez de a través de un controlador.
 *
 * Existe porque `ProjectSqlGuard` exige un alcance activo para tocar tablas de proyecto, y un
 * script de test no pasa por `SessionMiddleware` (eso lo cubre `SessionScopeHarness`) ni es una
 * operación de mantenimiento (eso lo cubre `SystemScopeRunner` a secas).
 *
 * La regla al usarlo, que es la parte que importa:
 *
 * · Una consulta que **prepara** el escenario y ya venía acotada a un `project_id` va en
 *   `enProyecto()` con **ese mismo** project_id. El gate reescribe el WHERE al valor que la
 *   consulta ya pedía, así que no cambia lo que la prueba mide.
 * · Una consulta que **es la aserción** —sobre todo la que comprueba que la obra vecina NO se
 *   contaminó— va en `enProyecto()` con el project_id de **la obra que se está observando**,
 *   nunca con el de la obra que se acaba de escribir. Elegir el alcance equivocado aquí tapa
 *   justo la propiedad de aislamiento que el test existe para probar.
 * · `comoSistema()` es solo para lo que cruza obras por diseño: limpiezas por marca en tablas
 *   globales, comprobaciones de esquema, catálogos de empresa. Nunca para ahorrarse pensar qué
 *   obra corresponde.
 */
final class ScopeFixture
{
    /**
     * Corre $operacion con el alcance de un proyecto concreto, y devuelve el contexto como estaba.
     */
    public static function enProyecto(
        Database $db,
        int $projectId,
        callable $operacion,
        string $usuario = 'test-fixture',
        string $rol = 'A',
    ): mixed {
        $contexto = $db->dataScope();
        $previo = $contexto->current();

        $contexto->clear();
        $contexto->bind(new ProjectScope($projectId, $usuario, $rol));
        try {
            return $operacion();
        } finally {
            $contexto->clear();
            if ($previo !== null) {
                $contexto->bind($previo);
            }
        }
    }

    /**
     * Abre el alcance de un proyecto para el tramo que sigue, en tests procedurales donde meter el
     * cuerpo en una closure obligaría a arrastrar media docena de variables. Cierra con cerrar().
     */
    public static function abrir(
        Database $db,
        int $projectId,
        string $usuario = 'test-fixture',
        string $rol = 'A',
    ): void {
        $contexto = $db->dataScope();
        $contexto->clear();
        $contexto->bind(new ProjectScope($projectId, $usuario, $rol));
    }

    public static function cerrar(Database $db): void
    {
        $db->dataScope()->clear();
    }

    /**
     * Corre $operacion bajo SystemScope. Solo para lo que cruza proyectos por diseño; la razón
     * queda escrita y viaja con el alcance.
     */
    public static function comoSistema(Database $db, string $razon, callable $operacion): mixed
    {
        $contexto = $db->dataScope();
        $previo = $contexto->current();
        $contexto->clear();

        try {
            return (new SystemScopeRunner($contexto))->run($razon, $operacion);
        } finally {
            $contexto->clear();
            if ($previo !== null) {
                $contexto->bind($previo);
            }
        }
    }
}
