<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Auth\PasswordPolicyService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Hace explícitas, campo por campo, las cinco reglas de contraseña que ya vivían en
 * `PasswordPolicyService::validate()` (longitud mínima 6, mayúscula, carácter especial,
 * confirmación coincidente, distinta a la clave anterior — hash moderno o legado sha512).
 * `validateFields()` no cambia ninguna regla: solo expone en qué campo cae cada fallo, para
 * que S02/S03 puedan pintar el error junto al input correcto en vez de un mensaje suelto.
 *
 * Nivel `puro`: no toca base de datos ni red.
 */
#[Group('puro')]
final class PasswordPolicyServiceTest extends TestCase
{
    #[DataProvider('invalidPasswords')]
    public function testReportsTheExactField(string $password, string $confirmation, string $field): void
    {
        $errors = (new PasswordPolicyService())->validateFields($password, $confirmation, null);
        self::assertArrayHasKey($field, $errors);
    }

    public static function invalidPasswords(): array
    {
        return [
            'minimum' => ['Aa!', 'Aa!', 'password'],
            'uppercase' => ['abcdef!', 'abcdef!', 'password'],
            'special' => ['Abcdef1', 'Abcdef1', 'password'],
            'confirmation' => ['Abcdef!', 'Abcdef?', 'confirmation'],
        ];
    }

    public function testRejectsTheCurrentPasswordForModernAndSha512Hashes(): void
    {
        $policy = new PasswordPolicyService();
        self::assertArrayHasKey('password', $policy->validateFields('Nueva!', 'Nueva!', password_hash('Nueva!', PASSWORD_DEFAULT)));
        self::assertArrayHasKey('password', $policy->validateFields('Nueva!', 'Nueva!', hash('sha512', 'Nueva!')));
    }

    public function testAValidPasswordProducesNoErrors(): void
    {
        $errors = (new PasswordPolicyService())->validateFields('Abcdef!', 'Abcdef!', null);
        self::assertSame([], $errors);
    }

    public function testValidatePreservesTheLegacySingleMessageOrder(): void
    {
        $policy = new PasswordPolicyService();

        // Longitud manda sobre las demás, igual que antes de este cambio.
        self::assertSame(
            'La contraseña debe tener al menos 6 caracteres',
            $policy->validate('Aa!', 'Aa!', null),
        );

        // Sin fallo de longitud/mayúscula/especial, la confirmación manda sobre la igualdad
        // con la clave anterior (el orden original de los `if` en `validate()`).
        self::assertSame(
            'Las contraseñas no coinciden',
            $policy->validate('Abcdef!', 'Abcdef?', password_hash('Abcdef!', PASSWORD_DEFAULT)),
        );

        self::assertNull($policy->validate('Abcdef!', 'Abcdef!', null));
    }
}
