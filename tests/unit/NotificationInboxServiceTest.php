<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Notifications\NotificationInboxService;
use App\Services\Notifications\NotificationRepositoryInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * T02 Tarea 9 (AC-137..143). Fake in-memory del repositorio de identidad (nunca DB real —
 * restricción global del plan "sin DDL/DML"): lo que se prueba aquí es la proyección que hace
 * `NotificationInboxService` sobre lo que el repositorio devuelve/recibe, no el SQL — eso lo
 * cubre `tests/test_notifications_api_contract.php` contra el contenedor real.
 *
 * Fix round 1 (hallazgo de revisión): `NotificationInboxService` no toca `Database` en ningún
 * punto de su cadena de construcción — a diferencia del viejo `App\Services\NotificationService`
 * que este test usaba antes, cuyo constructor resolvía `Database::getInstance()` de forma
 * incondicional. Este archivo demuestra el aislamiento real deteniendo el contenedor `db` y
 * corriendo sólo este test (ver `task-9-report.md`, apéndice "Fix round 1").
 */
#[Group('puro')]
final class NotificationInboxServiceTest extends TestCase
{
    /**
     * @param array<int, array<string, mixed>> $filas
     */
    private function fake(array $filas): NotificationRepositoryInterface
    {
        // Propiedades públicas mutables (no promoción por referencia: PHP no la permite en
        // constructores) para que los tests lean qué recibió el fake después de invocar el
        // servicio.
        return new class ($filas) implements NotificationRepositoryInterface {
            public ?string $usuarioConsultado = null;
            /** @var list<array{0:int,1:string}> */
            public array $marcarLlamadas = [];
            public bool $markAsReadDebeResponder = true;

            public function __construct(private array $filas)
            {
            }

            public function findUnreadByUser(string $userId): array
            {
                $this->usuarioConsultado = $userId;
                return $this->filas;
            }

            public function markAsRead(int $notificationId, string $userId): bool
            {
                $this->marcarLlamadas[] = [$notificationId, $userId];
                return $this->markAsReadDebeResponder;
            }
        };
    }

    private function fila(array $overrides = []): array
    {
        return array_merge([
            'id' => 154,
            'type' => 'ps_autoprogrammed_cnp_restriction',
            'title' => 'Actividad autodesprogramada por restricciones',
            'message' => '1 actividad(es) pasaron a CNP genérica.',
            'item_count' => 19,
            'created_at' => '2026-08-26 22:54:06',
            'project_id' => 'pdc_sandbox_e2e',
        ], $overrides);
    }

    // --- getUnreadByUser: AC-137/142/143 -------------------------------------------------------

    public function test_getUnreadByUser_quita_project_id_de_cada_fila(): void
    {
        $repo = $this->fake([$this->fila()]);
        $service = new NotificationInboxService($repo);

        $resultado = $service->getUnreadByUser('test.R');

        $this->assertArrayNotHasKey('project_id', $resultado[0]);
        $this->assertSame(
            ['id' => 154, 'type' => 'ps_autoprogrammed_cnp_restriction', 'title' => 'Actividad autodesprogramada por restricciones', 'message' => '1 actividad(es) pasaron a CNP genérica.', 'item_count' => 19, 'created_at' => '2026-08-26 22:54:06'],
            $resultado[0],
        );
    }

    public function test_getUnreadByUser_conserva_orden_y_cuenta_para_multiples_filas(): void
    {
        $repo = $this->fake([
            $this->fila(['id' => 1, 'project_id' => null]),
            $this->fila(['id' => 2, 'project_id' => 'otro_proyecto']),
        ]);
        $service = new NotificationInboxService($repo);

        $resultado = $service->getUnreadByUser('test.R');

        $this->assertCount(2, $resultado);
        $this->assertSame(1, $resultado[0]['id']);
        $this->assertSame(2, $resultado[1]['id']);
        $this->assertArrayNotHasKey('project_id', $resultado[0]);
        $this->assertArrayNotHasKey('project_id', $resultado[1]);
    }

    public function test_getUnreadByUser_lista_vacia_no_falla(): void
    {
        $repo = $this->fake([]);
        $service = new NotificationInboxService($repo);

        $this->assertSame([], $service->getUnreadByUser('test.R'));
    }

    public function test_getUnreadByUser_consulta_al_repositorio_con_el_usuario_de_sesion(): void
    {
        $repo = $this->fake([]);
        $service = new NotificationInboxService($repo);

        $service->getUnreadByUser('test.R');

        $this->assertSame('test.R', $repo->usuarioConsultado);
    }

    // --- markAsRead: AC-140/141 -----------------------------------------------------------------

    public function test_markAsRead_delega_id_y_usuario_de_sesion_al_repositorio(): void
    {
        $repo = $this->fake([]);
        $service = new NotificationInboxService($repo);

        $resultado = $service->markAsRead(31, 'test.R');

        $this->assertTrue($resultado);
        $this->assertSame([[31, 'test.R']], $repo->marcarLlamadas);
    }

    public function test_markAsRead_id_ajeno_o_ya_leido_sigue_devolviendo_true_idempotente(): void
    {
        // El repositorio real no distingue "0 filas afectadas" de "1 fila afectada" (execute() no
        // lo informa) — el fake refleja exactamente esa propiedad no enumerativa (AC-141).
        $repo = $this->fake([]);
        $service = new NotificationInboxService($repo);

        $resultado = $service->markAsRead(999999999, 'test.R');

        $this->assertTrue($resultado);
    }

    public function test_markAsRead_id_cero_o_negativo_no_toca_el_repositorio(): void
    {
        $repo = $this->fake([]);
        $service = new NotificationInboxService($repo);

        $this->assertFalse($service->markAsRead(0, 'test.R'));
        $this->assertFalse($service->markAsRead(-5, 'test.R'));
        $this->assertSame([], $repo->marcarLlamadas);
    }

    public function test_markAsRead_nunca_usa_un_usuario_distinto_al_de_sesion(): void
    {
        $repo = $this->fake([]);
        $service = new NotificationInboxService($repo);

        $service->markAsRead(31, 'test.V');

        $this->assertSame('test.V', $repo->marcarLlamadas[0][1]);
    }
}
