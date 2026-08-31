<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Shell\ShellNavigationService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Contrato puro del manifiesto de navegación (Tarea 3, spec T01 §8.2/§10).
 *
 * `ShellNavigationService::build()` no toca sesión ni base de datos: recibe el rol ya
 * normalizado (por `RbacService::normalizeRole()`, responsabilidad del llamador —
 * `SessionApiController`), el área activa del proyecto y la entrada BI ya autorizada, y
 * devuelve grupos/ítems ordenados y filtrados. Por eso corre en el nivel `puro`.
 */
#[Group('puro')]
class ShellNavigationServiceTest extends TestCase
{
    private const BI_OCULTO = ['visible' => false, 'href' => null];

    public function testRolAdminVeTodosLosGruposEItemsEnOrdenDeclarado(): void
    {
        $manifiesto = ShellNavigationService::build('A', 'Construccion', self::BI_OCULTO);

        $grupos = array_column($manifiesto, 'id');
        $this->assertSame(['informacion', 'obra', 'compras'], $grupos);

        $informacion = $this->itemsDe($manifiesto, 'informacion');
        $this->assertSame(
            ['semanas-proyecto', 'profesionales', 'subcontratistas', 'indicadores', 'control-cambios'],
            array_column($informacion, 'id'),
        );

        $obra = $this->itemsDe($manifiesto, 'obra');
        $this->assertSame(
            ['programa-general', 'programacion-intermedia', 'programacion-semanal', 'actualizar-cronograma'],
            array_column($obra, 'id'),
        );

        $compras = $this->itemsDe($manifiesto, 'compras');
        $this->assertSame(['plan-compras'], array_column($compras, 'id'));

        $planCompras = $compras[0];
        $this->assertSame('/plan-compras', $planCompras['href']);
        $this->assertArrayHasKey('label', $planCompras);
        $this->assertArrayHasKey('icon', $planCompras);
    }

    public function testRolSubcontratistaDeniegaModulosAdministrativosYSusHrefsPrivilegiados(): void
    {
        $manifiesto = ShellNavigationService::build('C', 'Construccion', self::BI_OCULTO);

        $idsVisibles = $this->todosLosIds($manifiesto);
        $hrefsVisibles = $this->todosLosHrefs($manifiesto);

        foreach (['profesionales', 'subcontratistas', 'plan-compras', 'actualizar-cronograma', 'control-cambios', 'programa-general', 'programacion-intermedia'] as $idDenegado) {
            $this->assertNotContains($idDenegado, $idsVisibles, "el ítem '$idDenegado' no debe viajar para C");
        }

        foreach (['/plan-compras', '/programa-general-actualizar', '/control-cambios', '/programa-general', '/programacion-intermedia', '/profesionales', '/subcontratistas'] as $hrefDenegado) {
            $this->assertNotContains($hrefDenegado, $hrefsVisibles, "el href '$hrefDenegado' no debe viajar para C");
        }

        // Lo único que le queda a C es lo neutral: acción de semanas e indicadores/programación
        // semanal, que no están en la tabla histórica de ocultos para ningún rol operativo.
        $this->assertContains('semanas-proyecto', $idsVisibles);
        $this->assertContains('indicadores', $idsVisibles);
        $this->assertContains('programacion-semanal', $idsVisibles);

        // El grupo "compras" queda vacío para C y por lo tanto no viaja en absoluto.
        $this->assertNull($this->grupo($manifiesto, 'compras'));
    }

    public function testAreaPreConstruccionOcultaPlanDeComprasIncluyendoParaElRolAdmin(): void
    {
        $manifiesto = ShellNavigationService::build('A', 'Pre-Construccion', self::BI_OCULTO);

        $this->assertNotContains('plan-compras', $this->todosLosIds($manifiesto));
        $this->assertNotContains('/plan-compras', $this->todosLosHrefs($manifiesto));
        $this->assertNull($this->grupo($manifiesto, 'compras'));
    }

    public function testDestinoBiSoloViajaCuandoElServidorLoAutorizaYVaPrimeroEnInformacion(): void
    {
        $manifiestoSinBi = ShellNavigationService::build('A', 'Construccion', self::BI_OCULTO);
        $this->assertNotContains('control-tower', $this->todosLosIds($manifiestoSinBi));

        $manifiestoConBi = ShellNavigationService::build('A', 'Construccion', [
            'visible' => true,
            'href' => '/bi/control-tower?project_id=1&semana=8',
        ]);

        $informacion = $this->itemsDe($manifiestoConBi, 'informacion');
        $this->assertSame('control-tower', $informacion[0]['id']);
        $this->assertSame('/bi/control-tower?project_id=1&semana=8', $informacion[0]['href']);
    }

    public function testAccionSemanasProyectoNoTraeHrefYQuedaMarcadaComoAccion(): void
    {
        $manifiesto = ShellNavigationService::build('V', 'Construccion', self::BI_OCULTO);
        $informacion = $this->itemsDe($manifiesto, 'informacion');
        $accion = null;
        foreach ($informacion as $item) {
            if ($item['id'] === 'semanas-proyecto') {
                $accion = $item;
            }
        }

        $this->assertNotNull($accion, 'semanas-proyecto debe viajar siempre que hay proyecto activo');
        $this->assertNull($accion['href']);
        $this->assertTrue($accion['action']);
    }

    public function testRolVisualizadorConservaModulosDeConsultaPeroPierdeLosDosRestringidos(): void
    {
        $manifiesto = ShellNavigationService::build('V', 'Construccion', self::BI_OCULTO);
        $ids = $this->todosLosIds($manifiesto);

        $this->assertContains('programa-general', $ids);
        $this->assertContains('programacion-intermedia', $ids);
        $this->assertContains('profesionales', $ids);
        $this->assertContains('subcontratistas', $ids);
        $this->assertContains('plan-compras', $ids);

        $this->assertNotContains('actualizar-cronograma', $ids);
        $this->assertNotContains('control-cambios', $ids);
    }

    /**
     * Fix round 1 (revisión del controlador): `items` debe ser siempre una lista con índices
     * 0..n-1, nunca un mapa disperso — de lo contrario `json_encode()` produce un objeto
     * (`{"1":{...}}`) en vez de un array y el esquema Zod del frontend (`items:
     * z.array(...)`) rechaza el payload. Barrido de humo sobre todo el catálogo de roles real:
     * hoy no atrapa el bug puntual de "compras" (ese grupo solo tiene un ítem candidato, así
     * que su única clave sobreviviente ya es 0 aunque falte `array_values()` — por eso se coló
     * sin detectarse), pero si algún grupo con 2+ ítems perdiera el `array_values()` este test
     * sí lo agarraría. El caso que SÍ reproduce el bug puntual de "compras" vive en
     * `testCompactItemsReindexaAunqueSeFiltreUnItemQueNoSeaElUltimo()`, más abajo.
     */
    public function testItemsDeCadaGrupoSonSiempreUnaListaJsonArrayNuncaObjeto(): void
    {
        foreach (\App\Security\RbacCatalog::canonicalRoles() as $role) {
            $manifiesto = ShellNavigationService::build($role, 'Construccion', self::BI_OCULTO);
            foreach ($manifiesto as $grupo) {
                $this->assertTrue(
                    array_is_list($grupo['items']),
                    "los items del grupo '{$grupo['id']}' para el rol '$role' deben ser una lista "
                    . "(índices 0..n-1), no un mapa disperso — array_filter() sin array_values() "
                    . "produciría un objeto JSON en vez de un array",
                );
            }
        }
    }

    /**
     * Reproduce exactamente la clase de bug que tenía el grupo "compras" (Fix round 1): un
     * candidato en el medio de la lista se filtra (denegado), y el resultado debe reindexarse
     * a 0..n-1 en vez de conservar las claves originales dispersas. `compactItems()` es ahora
     * el único punto de la clase donde se descarta un ítem `null` — cualquier grupo futuro que
     * lo use, incluido uno con un solo candidato como "compras" hoy, queda cubierto por
     * construcción en vez de por disciplina de copiar-pegar el patrón correcto.
     *
     * Se invoca por reflexión porque `compactItems()` es privado a propósito: es un detalle de
     * implementación de `build()`, no parte del contrato público del servicio (que es
     * `build()` mismo, ya cubierto por el resto de esta clase).
     */
    public function testCompactItemsReindexaAunqueSeFiltreUnItemQueNoSeaElUltimo(): void
    {
        $metodo = new \ReflectionMethod(ShellNavigationService::class, 'compactItems');

        $candidatos = [
            ['id' => 'primero'],
            null, // denegado — el que expone el bug: no es el último del array.
            ['id' => 'tercero'],
        ];

        $resultado = $metodo->invoke(null, $candidatos);

        $this->assertTrue(array_is_list($resultado), 'el resultado debe reindexarse a 0..n-1');
        $this->assertSame(['primero', 'tercero'], array_column($resultado, 'id'));
        $this->assertSame('{"items":[{"id":"primero"},{"id":"tercero"}]}', json_encode(['items' => $resultado]));
    }

    private function grupo(array $manifiesto, string $id): ?array
    {
        foreach ($manifiesto as $grupo) {
            if ($grupo['id'] === $id) {
                return $grupo;
            }
        }

        return null;
    }

    private function itemsDe(array $manifiesto, string $idGrupo): array
    {
        return $this->grupo($manifiesto, $idGrupo)['items'] ?? [];
    }

    private function todosLosIds(array $manifiesto): array
    {
        $ids = [];
        foreach ($manifiesto as $grupo) {
            foreach ($grupo['items'] as $item) {
                $ids[] = $item['id'];
            }
        }

        return $ids;
    }

    private function todosLosHrefs(array $manifiesto): array
    {
        $hrefs = [];
        foreach ($manifiesto as $grupo) {
            foreach ($grupo['items'] as $item) {
                if ($item['href'] !== null) {
                    $hrefs[] = $item['href'];
                }
            }
        }

        return $hrefs;
    }
}
