---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-09-17
areas: [arquitectura, qa]
resumen: "Los errores de /api/auth/* emiten nulos y listas vacías que el esquema del cliente rechaza; se corrige en el servidor y se ata la forma real al esquema con un contrato entre PHP y Zod"
---

# Errores de `/api/auth/*` alineados con el esquema del cliente

**Goal:** que el cliente React reciba siempre `codigo`, `mensaje` y `camposInvalidos` de los errores de `AuthApiController`, y que un test falle si la forma real del servidor vuelve a divergir del esquema.

**Origen:** deuda detectada el 2026-09-16 en la revisión final de S02 (PR #43).

## Problema medido

`AuthApiController::respondError()` (`src/Controllers/Api/AuthApiController.php:283-297`) emite siempre `fieldErrors`, `error.campos`, `redirect: null` y `correlationId: null`.

- Sin errores de campo, PHP serializa `[]` como lista, y `EsquemaCuerpoErrorApi` (`frontend/src/lib/api/esquemas/error.ts`) exige un objeto en `campos`.
- `redirect` y `correlationId` son `z.string().optional()`, y un `null` no pasa.
- Cualquiera de las dos cosas invalida el cuerpo **entero** en `pedir()` (`frontend/src/lib/api/cliente.ts:135-138`). El `ApiError` sale entonces con `codigo = HTTP_<status>`, mensaje genérico y `camposInvalidos = null`.

Efectos:

- **Login:** no se ve, porque `PantallaLogin` elige sus textos por status.
- **Cambio de clave obligatorio:** el 422 de la política de contraseñas del servidor (`AuthApiController.php:146-155`) llega sin `camposInvalidos`. `CambioClaveObligatorio` (`:70-71`) pinta el resumen sin la razón por campo, aunque `redirect: null` sea el único culpable, porque el cuerpo entero se descarta.
- **Tests:** no lo ven, porque `cuerpoError()` (`tests/browser/support/login-react-fixtures.mjs`) fabrica una forma distinta a la real.

## Decisión: se corrige en el servidor

**El servidor omite lo vacío o nulo.** No se relaja el esquema.

- **El defecto está en un solo emisor.** Solo `AuthApiController` emite `redirect`/`correlationId` en `null` (medido con grep en `src/`).
- **Hay precedente.** `PasswordRecoveryApiController::respondError()` ya omite `campos`/`fieldErrors` cuando van vacíos, y funciona en producción de la rama.
- **Un esquema estricto sirve de alarma.** Si el cliente tolerara `null` y `[]` en silencio, el próximo controlador que diverja no avisaría a nadie. El contrato nuevo convierte esa divergencia en un test rojo.
- **Las claves planas no cambian cuando traen datos.** `fieldErrors` sigue presente si hay errores de campo, así que los consumidores legados de la forma plana no pierden nada.

## Tarea única (TDD)

1. **Contrato entre PHP y Zod.**
   - Un archivo versionado `tests/fixtures/api-auth-error-bodies.json` con los cuerpos **reales**: 403 `csrf_invalid`, 401 `invalid_credentials` y 422 `validation_error` con campos, capturados del servidor por el test PHP de contrato.
   - El test PHP falla si los cuerpos actuales divergen del archivo, y se regenera con un flag explícito.
   - Un test de Vitest parsea cada cuerpo del archivo con `EsquemaCuerpoErrorApi` y exige `success`, más `codigo`, `mensaje` y `campos` (en el 422) presentes tras la extracción de `pedir()`.
   - RED primero: con el controlador actual, el test de Vitest falla.
2. **Arreglo del servidor.** `respondError()` omite `fieldErrors` y `campos` cuando no hay errores de campo, y nunca emite `redirect`/`correlationId` en `null`. Se regenera el archivo de cuerpos y se deja la línea base verde.
3. **Navegador contra servidor real.** Sin interceptar `/api/auth/*`; solo `/api/session` si hace falta para fijar un CSRF desconocido.
   - **403:** el cliente recibe el código y el mensaje del servidor.
   - **422:** los errores por campo del servidor llegan a la pantalla.
   - Si un 422 real no se puede provocar desde la UI sin mutar datos, se documenta por qué y se prueba el cuerpo real con la misma función de extracción del cliente.
4. **Fixtures.** `cuerpoError()` produce exactamente la forma real, sin claves vacías ni nulas, y los specs que lo usan siguen en verde.

## Restricciones

- **Contenedor:** efímero `lps-errores-auth` en `:8098` para HTTP y Playwright. Los tests PHP puros van con `LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps -T app`. Nunca `:8081` ni `docker compose exec`.
- **Datos:** no se tocan base, RLS, credenciales ni `admin/`. Ninguna prueba cambia una contraseña real.
- **Alcance:** solo `AuthApiController`. Otros controladores con forma plana quedan fuera y se anotan si aparecen.
- **Verificación:** `npm --prefix frontend test`, `npm run frontend:typecheck`, `npm run frontend:build`, `test_api_auth_contract.php`, `test_api_password_recovery_contract.php`, Playwright de `login-react`, `password-recovery-react` y `shell-control-actividad` contra `:8098`, `npm run test:design-system:static` y `npm run test:wiki`.
- **Cierre:** PR con condición de hecho declarada antes del CI: `design-system-static` y los 13 `G_*` en `success` en las dos patas.
