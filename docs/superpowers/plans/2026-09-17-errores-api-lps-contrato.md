---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-09-17
areas: [arquitectura, lps, qa]
resumen: "LpsApiController emite error.fields vacío como lista y el cliente descarta el error entero; el cajón LPS muestra «respondió 404» en vez de «no disponible». Se corrige en el servidor y se extiende el contrato PHP↔Zod"
---

# Errores de `/api/lps/*` alineados con el esquema del cliente

**Goal:** que el cajón contextual LPS reconozca `LPS_TARGET_NOT_FOUND` y `LPS_TARGET_STALE` y muestre «no disponible», y que un test falle si la forma real de los errores LPS vuelve a divergir del esquema.

**Origen:** hallazgo de la revisión del PR #44 (`docs/superpowers/plans/2026-09-17-errores-api-auth-contrato.md`, `## Cierre`), que resolvió lo mismo para `AuthApiController`.

## Problema medido

- **Emisores:** `LpsApiController::renderApiError()` (`src/Controllers/Api/LpsApiController.php:304-319`) y `renderLegacyError()` (`:327-341`) emiten siempre `error.fields`. `LpsApiError` lo inicializa en `[]` (`src/Services/Lps/LpsApiError.php:18`), y PHP lo serializa como lista.
- **Por qué se descarta:** `EsquemaCuerpoErrorApi` (`frontend/src/lib/api/esquemas/error.ts`) exige un objeto en `fields`. `pedir()` (`frontend/src/lib/api/cliente.ts`) descarta entonces el cuerpo **entero** y entrega `codigo = HTTP_<status>` y un mensaje genérico.
- **Lo que ve el usuario:** medido en la revisión del #44, `GET /api/lps/comments?consecutivo=999999999&modulo=PS` responde 404 `LPS_TARGET_NOT_FOUND` con `fields: []`. `LpsDrawerProvider.tsx:140` no reconoce el código y `noDisponible` queda en `false`. `CajonContextualLps.tsx:200` no muestra la rama «no disponible», así que el usuario lee «/api/lps/comments respondió 404». Con el 409 `LPS_TARGET_STALE` pasa lo mismo.

## Decisión: se corrige en el servidor

Es el mismo criterio del #44, por las mismas razones:

- **El esquema no se relaja**, porque así sirve de alarma para la próxima divergencia.
- **El servidor omite `fields` cuando va vacío.** Cuando trae campos (validación, como `trigger` o `justificacion`), se mantiene igual, y `tests/test_lps_api_contract.php` los sigue viendo.

## Barrido de otros emisores (2026-09-17, grep en `src/`)

- **Con errores en claves del esquema:** solo `LpsApiController` (`:314` y `:336`) emite `fields`/`campos` que pueden ir vacíos.
- **`SessionApiController`:** emite `reason: null` en respuestas **de éxito** de `/api/session`. Esas las valida el esquema de sesión, no el de error; la tarea confirma que ese esquema acepta `null` y lo anota.
- **`AuthApiController`:** ya quedó resuelto en el #44.

## Tarea única (TDD)

1. **Contrato.** Extender el patrón del #44 a LPS: cuerpos reales capturados del servidor por el test PHP de contrato en un archivo versionado propio (p. ej. `tests/fixtures/api-lps-error-bodies.json`), con 404 `LPS_TARGET_NOT_FOUND`, 409 `LPS_TARGET_STALE` y un error con campos (validación).
   - El test PHP falla si los cuerpos divergen del archivo, y se regenera con un flag explícito.
   - El test de Vitest pasa cada cuerpo por el esquema y `pedir()` reales y exige el código y el mensaje del servidor, más los campos en el de validación.
   - RED primero.
2. **Arreglo.** `renderApiError()` y `renderLegacyError()` omiten `fields` cuando va vacío. Se regeneran los cuerpos y la línea base queda verde.
3. **Navegador.** Contra el servidor real, sin interceptar `/api/lps/*`, el cajón LPS muestra «no disponible» ante un target inexistente (404). Si el 409 no se puede provocar sin mutar datos, se documenta y se prueba con el cuerpo real capturado.
4. **Consumidores.** Confirmar que nadie depende de que `error.fields` exista siempre (`frontend/src`, `ct-app/src`, `public/js`, `pdc-app/src`, tests), sin aflojar aserciones.

## Restricciones

- **Contenedor:** efímero en un puerto libre (`:8099`) para HTTP y Playwright. Puros con `LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps -T app`. Nunca `:8081` ni `docker compose exec`.
- **Datos:** no se escriben datos de proyecto. Las lecturas por la puerta de servicio usan cuentas sembradas.
- **Alcance:** solo `LpsApiController`. Otros hallazgos se anotan.
- **Verificación:** `npm --prefix frontend test`, `npm run frontend:typecheck`, `npm run frontend:build` (y el bundle si cambia), `test_lps_api_contract.php`, `test_api_auth_contract.php`, `run-php-tests.php --nivel=http`, Playwright del spec nuevo y de los specs LPS existentes contra `:8099`, `npm run test:design-system:static`, `npm run test:wiki` y `git diff --check`.
- **Cierre:** PR con condición de hecho declarada antes del CI: `design-system-static` y los 13 `G_*` en `success` en las dos patas.
