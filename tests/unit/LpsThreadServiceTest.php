<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Lps\LpsTarget;
use App\Services\Lps\LpsTargetException;
use App\Services\Lps\LpsThreadCommentRecord;
use App\Services\Lps\LpsThreadPresenter;
use App\Services\Lps\LpsThreadRepository;
use App\Services\Lps\LpsThreadService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * T02-AC-080..092: hilo, comentarios, respuestas y menciones. Fake in-memory del repositorio
 * (nunca DB real, D-T02 restricción global "sin DDL/DML"): el servicio y el presenter son la
 * unidad bajo prueba, no la capa SQL legacy.
 */
#[Group('puro')]
final class LpsThreadServiceTest extends TestCase
{
    public const PROJECT_ID = 73;

    private function target(int $activityId = 4102, string $module = 'PG', int $week = 14, ?int $escalamientoId = null): LpsTarget
    {
        return LpsTarget::forActivity(self::PROJECT_ID, $activityId, $module, $week, $escalamientoId);
    }

    /** @param list<LpsThreadCommentRecord> $seed */
    private function repository(array $seed = []): LpsThreadRepository
    {
        return new class ($seed) implements LpsThreadRepository {
            public array $insertCalls = [];
            private int $nextId = 1000;

            public function __construct(private array $rows)
            {
            }

            public function findByTarget(int $projectId, int $activityId, int $week, ?int $escalamientoId): array
            {
                if ($projectId !== LpsThreadServiceTest::PROJECT_ID) {
                    throw new \RuntimeException('El repositorio recibió un project_id ajeno al scope.');
                }

                return array_values($this->rows);
            }

            public function findRootById(int $projectId, int $activityId, int $week, int $commentId): ?LpsThreadCommentRecord
            {
                foreach ($this->rows as $row) {
                    if ($row->id === $commentId && $row->isRoot()) {
                        return $row;
                    }
                }

                return null;
            }

            public function insert(
                int $projectId,
                int $activityId,
                int $week,
                int $authorUserId,
                string $text,
                ?int $parentId,
                ?int $escalamientoId,
                ?array $mentions,
            ): int {
                $this->insertCalls[] = [
                    'projectId' => $projectId,
                    'activityId' => $activityId,
                    'week' => $week,
                    'authorUserId' => $authorUserId,
                    'text' => $text,
                    'parentId' => $parentId,
                    'escalamientoId' => $escalamientoId,
                    'mentions' => $mentions,
                ];

                $id = $this->nextId++;
                $this->rows[] = new LpsThreadCommentRecord($id, $parentId, $text, '2026-08-31 10:00:00', $authorUserId, 'Autor', 'Cargo', $mentions);

                return $id;
            }
        };
    }

    private function record(
        int $id,
        ?int $parentId,
        string $text,
        string $createdAt,
        int $authorUserId = 5,
        ?string $authorName = 'Profesional AIA',
        ?string $authorRole = 'Residente',
        ?array $mentions = null,
    ): LpsThreadCommentRecord {
        return new LpsThreadCommentRecord($id, $parentId, $text, $createdAt, $authorUserId, $authorName, $authorRole, $mentions);
    }

    // --- lectura: orden scoped, un solo nivel (T02-AC-084/085/086) ---

    public function testLeerDevuelveLaFilaPlanaQueDevuelveElRepositorioEnOrdenAscendente(): void
    {
        $seed = [
            $this->record(1, null, 'raíz', '2026-08-31 09:00:00'),
            $this->record(2, 1, 'respuesta', '2026-08-31 09:05:00'),
        ];
        $service = new LpsThreadService($this->repository($seed));

        $flat = $service->read($this->target());

        self::assertSame($seed, $flat);
    }

    public function testPresenterArmaRaicesPorFechaAscendenteConSusRespuestasPropias(): void
    {
        $flat = [
            $this->record(1, null, 'raíz 1', '2026-08-31 09:00:00'),
            $this->record(2, null, 'raíz 2', '2026-08-31 10:00:00'),
            $this->record(3, 1, 'respuesta a raíz 1', '2026-08-31 09:05:00'),
        ];

        $tree = (new LpsThreadPresenter())->presentReact($flat);

        self::assertSame([1, 2], array_column($tree, 'id'));
        self::assertCount(1, $tree[0]['respuestas']);
        self::assertSame(3, $tree[0]['respuestas'][0]['id']);
        self::assertCount(0, $tree[1]['respuestas']);
    }

    public function testPresenterConservaUnSoloNivelYDescartaReplyAReplyHuerfana(): void
    {
        $flat = [
            $this->record(1, null, 'raíz', '2026-08-31 09:00:00'),
            $this->record(2, 1, 'respuesta', '2026-08-31 09:05:00'),
            // "nieto": su parent_id (2) no es una raíz presente en el árbol.
            $this->record(3, 2, 'respuesta a respuesta', '2026-08-31 09:10:00'),
        ];

        $tree = (new LpsThreadPresenter())->presentReact($flat);

        self::assertCount(1, $tree);
        self::assertCount(1, $tree[0]['respuestas']);
        self::assertSame(2, $tree[0]['respuestas'][0]['id']);
    }

    // --- presenter: forma aditiva legacy vs. React sin campos de actor (T02-AC-082/083) ---

    public function testPresenterLegacyConservaUsuarioIdPorqueLpsDrawerJsLoLee(): void
    {
        $flat = [$this->record(1, null, 'raíz', '2026-08-31 09:00:00', authorUserId: 42)];

        $legacy = (new LpsThreadPresenter())->presentLegacy($flat);

        self::assertSame(42, $legacy[0]['usuario_id']);
        self::assertSame('raíz', $legacy[0]['comentario']);
        self::assertSame('Profesional AIA', $legacy[0]['autor_nombre']);
        self::assertSame('Residente', $legacy[0]['autor_cargo']);
    }

    public function testPresenterReactNoExponeUsuarioIdNiCamposInternos(): void
    {
        $flat = [$this->record(1, null, 'raíz', '2026-08-31 09:00:00', authorUserId: 42)];

        $react = (new LpsThreadPresenter())->presentReact($flat);

        self::assertArrayNotHasKey('usuario_id', $react[0]);
        self::assertArrayNotHasKey('project_id', $react[0]);
        self::assertArrayNotHasKey('proyecto_id', $react[0]);
        self::assertSame(['id', 'comentario', 'created_at', 'autor_nombre', 'autor_cargo', 'menciones', 'respuestas'], array_keys($react[0]));
    }

    // --- escritura: parent_id de la misma raíz (T02-AC-087/088) ---

    public function testResponderAUnaRaizDelMismoTargetInsertaConParentId(): void
    {
        $raiz = $this->record(10, null, 'raíz', '2026-08-31 09:00:00');
        $repo = $this->repository([$raiz]);
        $service = new LpsThreadService($repo);

        $id = $service->addComment($this->target(), 5, 'respuesta válida', 10, null);

        self::assertGreaterThan(0, $id);
        self::assertSame(10, $repo->insertCalls[0]['parentId']);
    }

    public function testUnParentIdQueNoExisteEnElRepositorioEsValidationFailed(): void
    {
        $service = new LpsThreadService($this->repository([]));

        try {
            $service->addComment($this->target(), 5, 'respuesta huérfana', 999, null);
            self::fail('Se esperaba LpsTargetException.');
        } catch (LpsTargetException $exception) {
            self::assertSame('VALIDATION_FAILED', $exception->apiError()->code);
            self::assertSame(422, $exception->apiError()->httpStatus);
            self::assertArrayHasKey('parent_id', $exception->apiError()->fields);
        }
    }

    // --- escritura: no reply-a-reply (T02-AC-089) ---

    public function testResponderAUnaRespuestaEsValidationFailed(): void
    {
        $raiz = $this->record(10, null, 'raíz', '2026-08-31 09:00:00');
        $respuesta = $this->record(11, 10, 'respuesta', '2026-08-31 09:05:00');
        $service = new LpsThreadService($this->repository([$raiz, $respuesta]));

        try {
            // 11 es una respuesta, no una raíz: findRootById debe rechazarla.
            $service->addComment($this->target(), 5, 'respuesta a respuesta', 11, null);
            self::fail('Se esperaba LpsTargetException.');
        } catch (LpsTargetException $exception) {
            self::assertSame('VALIDATION_FAILED', $exception->apiError()->code);
        }
    }

    public function testUnComentarioRaizSinParentIdNoValidaNada(): void
    {
        $repo = $this->repository([]);
        $service = new LpsThreadService($repo);

        $id = $service->addComment($this->target(), 5, 'comentario raíz', null, null);

        self::assertGreaterThan(0, $id);
        self::assertNull($repo->insertCalls[0]['parentId']);
    }

    // --- menciones: roles canónicos deduplicados (T02-AC-090) ---

    public function testNormalizeMentionsDedupeYCanonicaliza(): void
    {
        $normalized = LpsThreadService::normalizeMentions(['roles' => ['d', 'D', ' ot ', 'OT']]);

        self::assertSame(['roles' => ['D', 'OT']], $normalized);
    }

    // --- menciones: token desconocido no se vuelve destinatario (T02-AC-091) ---

    public function testNormalizeMentionsDescartaTokenDesconocido(): void
    {
        $normalized = LpsThreadService::normalizeMentions(['roles' => ['D', 'ROL_INVENTADO', 'ZZ']]);

        self::assertSame(['roles' => ['D']], $normalized);
    }

    public function testNormalizeMentionsSoloConTokensDesconocidosDevuelveNull(): void
    {
        self::assertNull(LpsThreadService::normalizeMentions(['roles' => ['ROL_INVENTADO']]));
    }

    public function testNormalizeMentionsVacioONuloDevuelveNull(): void
    {
        self::assertNull(LpsThreadService::normalizeMentions(null));
        self::assertNull(LpsThreadService::normalizeMentions([]));
        self::assertNull(LpsThreadService::normalizeMentions(['roles' => []]));
    }

    // --- menciones: metadata, nunca dispara notificación (T02-AC-092) ---
    // No hay assertion ejecutable de "nunca llama NotificationService" sin un spy de ese
    // servicio: la garantía real es que ni LpsThreadService ni LpsThreadRepository importan o
    // referencian App\Services\Notifications en absoluto (comprobado por lectura, no por test).

    // --- escritura: pasa target/actor exactos al repositorio, nunca datos crudos del cliente ---

    public function testAddCommentPasaProyectoActividadYSemanaDelTargetResueltoNoDelCliente(): void
    {
        $repo = $this->repository([]);
        $service = new LpsThreadService($repo);

        $service->addComment($this->target(activityId: 4102, module: 'PG', week: 14, escalamientoId: 30), 5, 'texto', null, null);

        self::assertSame(self::PROJECT_ID, $repo->insertCalls[0]['projectId']);
        self::assertSame(4102, $repo->insertCalls[0]['activityId']);
        self::assertSame(14, $repo->insertCalls[0]['week']);
        self::assertSame(30, $repo->insertCalls[0]['escalamientoId']);
    }
}
