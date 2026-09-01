<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Router;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tarea 13 (S01), ronda de arreglos 1: se pidió declarar HEAD explícito para `/` y `/login`
 * (`Router::head()`) porque `grep -c "router->head("` daba 0 antes de esta ronda, y la premisa
 * era que sin esa declaración FastRoute respondería 405 a HEAD.
 *
 * `testSinHeadRegistradoElFallbackAGetYaFunciona()` deja evidencia de que esa premisa NO era
 * cierta para `nikic/fast-route` en la versión instalada aquí (`vendor/nikic/fast-route/src/
 * Dispatcher/RegexBasedAbstract.php:35-47`, comentario "For HEAD requests, attempt fallback to
 * GET"): una ruta estática (`/`, `/login`, sin parámetros) que solo tiene GET registrado YA
 * responde FOUND ante HEAD, sin declarar `head()`. El rollback de `App\Core\SpaRouter`
 * (quitar `/login` de `RUTAS_EXACTAS_MIGRADAS`) ya era simétrico en HEAD por esta razón, antes
 * de que este archivo existiera. `Router::head()` y su registro explícito en
 * `public/index.php` para `/` y `/login` se conservan de todos modos — no estorban, hacen la
 * intención explícita en el código en vez de depender de un detalle de implementación de una
 * librería de terceros, y `testHeadRegistradoDespachaComoFound()` prueba que también funcionan.
 *
 * No dispara I/O real ni toca `public/index.php`: construye su propio `Router` con handlers de
 * closure, así que corre en el nivel `puro`.
 */
#[Group('puro')]
class RouterHeadRegistrationTest extends TestCase
{
    private ?string $metodoOriginal = null;
    private ?string $uriOriginal = null;

    protected function setUp(): void
    {
        $this->metodoOriginal = $_SERVER['REQUEST_METHOD'] ?? null;
        $this->uriOriginal = $_SERVER['REQUEST_URI'] ?? null;
    }

    protected function tearDown(): void
    {
        $this->restaurarServidor($this->metodoOriginal, $this->uriOriginal);
    }

    public function testHeadRegistradoDespachaComoFound(): void
    {
        $router = new Router();
        $llamado = false;
        $router->get('/login', static function () {});
        $router->head('/login', static function () use (&$llamado): void {
            $llamado = true;
        });

        $this->despachar($router, 'HEAD', '/login');

        $this->assertTrue($llamado, 'HEAD /login con head() registrado debe invocar SU PROPIO handler');
    }

    public function testSinHeadRegistradoElFallbackAGetYaFunciona(): void
    {
        $router = new Router();
        $llamado = false;
        $router->get('/login', static function () use (&$llamado): void {
            $llamado = true;
        });
        // A propósito, sin registrar head(): documenta que nikic/fast-route ya hace fallback
        // de HEAD a GET para rutas estáticas — ver el docblock de la clase.

        $codigo = $this->despachar($router, 'HEAD', '/login');

        $this->assertTrue($llamado, 'el fallback de FastRoute debe invocar el handler de GET cuando no hay head() explícito');
        $this->assertNotSame(405, $codigo, 'el fallback de HEAD a GET de FastRoute no debe responder 405');
    }

    public function testHeadRegistradoParaRaizYLogin(): void
    {
        // Las dos rutas exactas migradas de SpaRouter (RUTAS_EXACTAS_MIGRADAS = ['/', '/login'])
        // deben poder revertir HEAD, no solo GET — con head() explícito o con el fallback de
        // FastRoute, cualquiera de los dos basta; aquí se prueba con head() explícito, que es
        // lo que quedó registrado en public/index.php.
        $router = new Router();
        $llamadas = [];
        $handler = static function () use (&$llamadas): void {
            $llamadas[] = true;
        };
        $router->get('/', $handler);
        $router->head('/', $handler);
        $router->get('/login', $handler);
        $router->head('/login', $handler);

        $this->despachar($router, 'HEAD', '/');
        $this->despachar($router, 'HEAD', '/login');

        $this->assertCount(2, $llamadas, 'ambas rutas exactas deben despachar su handler en HEAD, no responder 405');
    }

    private function despachar(Router $router, string $metodo, string $uri): int
    {
        $this->restaurarServidor($metodo, $uri);
        http_response_code(200);

        ob_start();
        $router->dispatch();
        ob_end_clean();

        return http_response_code();
    }

    private function restaurarServidor(?string $metodo, ?string $uri): void
    {
        if ($metodo === null) {
            unset($_SERVER['REQUEST_METHOD']);
        } else {
            $_SERVER['REQUEST_METHOD'] = $metodo;
        }

        if ($uri === null) {
            unset($_SERVER['REQUEST_URI']);
        } else {
            $_SERVER['REQUEST_URI'] = $uri;
        }
    }
}
