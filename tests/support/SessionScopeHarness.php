<?php

declare(strict_types=1);

use App\Security\DataScope\ProjectScopeResolver;

/**
 * Enlaza el alcance de datos como lo haría SessionMiddleware, para tests que invocan un
 * controlador directamente.
 *
 * En una petición real nadie llama a un controlador «a pelo»: `public/index.php` pasa antes por
 * `SessionMiddleware::check()`, y es ahí donde el `ProjectScope` queda enlazado a partir de
 * `$_SESSION`. Un test que construye el controlador con `new` se salta ese paso, así que desde que
 * entró ProjectSqlGuard toda consulta a tablas de proyecto muere con `MissingProjectScope` — no
 * porque el controlador esté mal, sino porque el test está reproduciendo media petición.
 *
 * Este harness reproduce la otra media, y lo hace con el mismo componente que usa producción
 * (`ProjectScopeResolver`), no con un atajo: el alcance sigue validándose contra `project_members`,
 * el proyecto sigue teniendo que estar activo y el rol sigue saliendo de la base. Un test que
 * enlaza por aquí no puede alcanzar más de lo que alcanzaría el usuario real que dice ser.
 */
final class SessionScopeHarness
{
    /**
     * Enlaza el alcance derivado de $_SESSION. Devuelve false si la sesión no da para uno,
     * que es la misma respuesta que daría el middleware.
     */
    public static function bindFromSession(Database $db): bool
    {
        $context = $db->dataScope();
        $context->clear();

        $scope = (new ProjectScopeResolver($db))->resolve($_SESSION);
        if ($scope === null) {
            return false;
        }

        $context->bind($scope);

        return true;
    }

    /**
     * Enlaza y falla ruidosamente si no se pudo, para el caso normal: el test ya sembró una
     * membresía válida y que no resuelva significa que el fixture está mal, no que el escenario
     * sea inalcanzable. Sin esto el test seguiría hasta morir más adelante con un mensaje que no
     * apunta a la causa.
     */
    public static function requireFromSession(Database $db): void
    {
        if (!self::bindFromSession($db)) {
            throw new RuntimeException(
                'No se pudo resolver un ProjectScope desde $_SESSION: revisa usuario, project_id, '
                . 'la membresía en project_members y que el proyecto esté activo.',
            );
        }
    }

    /**
     * Deja el contexto como estaba al empezar. Los tests comparten proceso dentro del runner.
     */
    public static function clear(Database $db): void
    {
        $db->dataScope()->clear();
    }
}
