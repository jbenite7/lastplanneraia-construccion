# Ocultar Control Tower fuera de desarrollo

**Fecha:** 2026-08-13 · **Estado:** aprobado en chat (enfoque A)

## Objetivo

Mientras el módulo BI (Control Tower) se termina de desarrollar, no debe verse ni poder
abrirse en los servidores (pruebas y producción). En el entorno local de desarrollo sigue
funcionando igual, que es donde se trabaja.

## Decisiones (grilleo 2026-08-13)

1. **Ocultar y bloquear**: desaparecen los accesos de navegación y además las rutas
   `/bi/*` y `/api/bi/*` responden 404 con enlace directo.
2. **Nadie en servidores**: oculto en pruebas y producción para todos los roles,
   incluido Admin. Visible solo en local.
3. **Gate por entorno, no por flag**: se descartó un `BI_ENABLED` en `.env` para no
   añadir otra llave de configuración no versionada que derive por máquina.

## Restricción medida

`AppEnvironment::allowsInternalTools()` **no sirve** como gate: incluye `testing`, y el
`.env` de pruebas tiene `APP_ENV="testing"` (verificado por SSH el 2026-08-13; producción
tiene `"production"`). El gate es estrictamente `AppEnvironment::current() === 'development'`.
Docker local inyecta `APP_ENV: ${APP_ENV:-development}` (docker-compose.yml:12), así que
local queda visible sin tocar nada.

## Cambios

| Pieza | Cambio |
|---|---|
| `src/View/Components/BiAccessComponent.php` | `canAccess()` y `canAccessAny()` devuelven `false` como primera línea si el entorno no es `development`. Oculta de golpe: sidebar (`shell_sidebar.php:76`), selector de proyectos (`project_selector.view.php:30,56`), tarjeta del drawer (`drawer_unificado.php:23`), botones «BI …» de los 5 módulos (`renderLink`) y los boot-configs JS (`renderBootConfig`). |
| `src/Controllers/Bi/BiViewController.php` | 404 en `renderView()` (choke point de las 8 vistas `/bi/*`) si el entorno no es `development`. Mismo patrón que `DesignSystemLabAccessPolicy` (404 para no revelar que existe). |
| `src/Controllers/Api/BiControlTowerApiController.php` | Mismo 404 en su punto de entrada común (las ~20 APIs `/api/bi/*`). |
| `src/Legacy/datosGeneralesPagina.php:30` | `canAccessBi` deja de calcularse con RBAC directo y pasa por `BiAccessComponent::canAccess()`, para que el JSON que consume el JS respete el mismo interruptor. |
| `tests/test_bi_dev_gate.php` (nuevo) | Test autónomo estilo `test_dev_door_guard.php`: oculto en `production` y `testing`, visible en `development`; y las rutas devuelven 404 fuera de development. |

El chequeo de entorno se escribe una sola vez (método estático pequeño, p. ej.
`BiAccessComponent::isEnabledInEnvironment(?string $env = null): bool`, con el parámetro
inyectable para el test) y los demás puntos lo consultan.

## Lo que NO cambia

- RBAC (`lps.indicadores.ver`), `BiProjectScope` y toda la lógica interna del BI quedan
  intactos: el gate va **antes**, no en lugar de.
- No se tocan datos ni esquema. No hay migraciones.

## Reversión

Cuando el desarrollo termine: eliminar el gate (las llamadas a
`isEnabledInEnvironment()` y el método) y borrar o adaptar el test. Un solo commit.

## Verificación

- `tests/test_bi_dev_gate.php` en verde dentro del contenedor.
- Local (development): sidebar muestra «Control Tower - Informes» y `/bi/control-tower` responde 200.
- Simulación de servidor: con `APP_ENV=production` o `testing` forzado en el chequeo, la
  entrada desaparece y `/bi/control-tower` y `/api/bi/control-tower` devuelven 404.
- PHPStan sin errores nuevos sobre los archivos tocados.

## Archivos de este goal

Sin carpeta `goals/`: frente corto con spec propio. Estado y cierre se anotan en
`memoria/log.md`.
