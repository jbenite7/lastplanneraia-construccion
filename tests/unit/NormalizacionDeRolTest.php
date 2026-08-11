<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Security\RbacCatalog;
use App\Security\RbacManager;
use App\Security\RbacService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Cubre `RbacService::normalizeRole()`, que `AGENTS.md` §Seguridad señala como la única forma
 * correcta de traducir un alias de rol a su código canónico, y su regla asociada: «conserva solo
 * lectura como fallback seguro».
 *
 * Es el primer test escrito con PHPUnit en este repositorio. No sustituye a ninguno: los 101
 * `tests/test_*.php` siguen igual. Es de nivel `puro` porque `normalizeRole()` no consulta la base
 * —comprobado: no hay una sola referencia a `$this->db` en su cuerpo—, así que basta con darle al
 * constructor cualquier objeto para que no llame a `Database::getInstance()`.
 */
#[Group('puro')]
final class NormalizacionDeRolTest extends TestCase
{
    private RbacService $rbac;

    protected function setUp(): void
    {
        // Un doble cualquiera: el constructor solo lo guarda, y normalizeRole() no lo usa.
        $this->rbac = new RbacService(new \stdClass());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function aliasesDelCatalogo(): array
    {
        $casos = [];
        foreach (RbacCatalog::roleAliases() as $alias => $canonico) {
            $casos["'{$alias}' se traduce a '{$canonico}'"] = [$alias, $canonico];
        }

        return $casos;
    }

    #[DataProvider('aliasesDelCatalogo')]
    public function testCadaAliasDelCatalogoSeTraduceASuCodigoCanonico(string $alias, string $canonico): void
    {
        self::assertSame($canonico, $this->rbac->normalizeRole($alias));
    }

    public function testLosCodigosCanonicosSobrevivenSinCambio(): void
    {
        foreach (RbacCatalog::canonicalRoles() as $codigo) {
            self::assertSame($codigo, $this->rbac->normalizeRole($codigo));
        }
    }

    public function testNormalizaEspaciosYMayusculas(): void
    {
        self::assertSame('R', $this->rbac->normalizeRole('  residente de obra '));
        self::assertSame('D', $this->rbac->normalizeRole('director de obra'));
    }

    /**
     * @return array<string, array{?string}>
     */
    public static function entradasQueNoSonUnRol(): array
    {
        return [
            'cadena vacía' => [''],
            'solo espacios' => ['   '],
            'null' => [null],
            'un rol inventado' => ['SUPERUSUARIO'],
            'un código que no existe' => ['ZZ'],
        ];
    }

    #[DataProvider('entradasQueNoSonUnRol')]
    public function testLoQueNoEsUnRolCaeEnElRolPorDefecto(?string $entrada): void
    {
        self::assertSame(RbacCatalog::DEFAULT_ROLE, $this->rbac->normalizeRole($entrada));
    }

    /**
     * La parte que de verdad importa: que ese fallback sea inofensivo.
     *
     * `AGENTS.md` exige «conserva solo lectura como fallback seguro». Comprobarlo mirando que el
     * rol por defecto sea una constante concreta no demuestra nada: lo que hay que comprobar es que
     * ese rol no conceda ni una sola capacidad de escritura. Así, si mañana alguien cambia
     * `DEFAULT_ROLE` por un rol con permisos, este test lo caza aunque la constante siga existiendo.
     */
    public function testElRolPorDefectoNoConcedeNingunaCapacidadDeEscritura(): void
    {
        $capacidades = RbacManager::getCapabilities(RbacCatalog::DEFAULT_ROLE);

        self::assertTrue(
            $capacidades['isReadOnly'],
            'el rol de respaldo debe quedar marcado como de solo lectura',
        );

        foreach ($capacidades as $nombre => $concedida) {
            if ($nombre === 'isReadOnly' || $nombre === 'isExternal') {
                continue;
            }
            self::assertFalse(
                $concedida,
                "el rol de respaldo no debe conceder '{$nombre}': un fallo al resolver el rol no puede acabar dando permisos",
            );
        }
    }
}
