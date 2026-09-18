---
capa: fuente
tipo: plan
estado: cerrado
fecha: 2026-09-17
areas: [arquitectura, lps, qa]
resumen: "LpsApiController emite error.fields vacío como lista y el cliente descarta el error entero; el cajón LPS pinta su cuerpo normal sobre una actividad inexistente en vez de «no disponible». Se corrige en el servidor y se extiende el contrato PHP↔Zod"
---

# Errores de `/api/lps/*` alineados con el esquema del cliente

## Estado verificado — cerrado

Verificado contra el código y el remoto el 2026-09-17. **`estado: cerrado` es una afirmación deliberada**, no el valor por defecto del backfill. El `## Cierre` de abajo se escribió antes de publicar y dice «pendiente de PR»; esta sección lo supera, no lo corrige.

**Evidencia:** PR #45 mergeado en `main` (`a8155a06`, 2026-09-17): arreglo `1339a124`, contrato `2176d40f`, navegador `386a4ef6`. `tests/test_lps_api_contract.php` y `tests/browser/lps-errores-contrato.spec.mjs` existen en `origin/main`.

Criterio y método: [[docs/superpowers/plans/2026-08-25-estado-real-de-planes-y-specs]].

**Goal:** que el cajón contextual LPS reconozca `LPS_TARGET_NOT_FOUND` y `LPS_TARGET_STALE` y muestre «no disponible», y que un test falle si la forma real de los errores LPS vuelve a divergir del esquema.

**Origen:** hallazgo de la revisión del PR #44 (`docs/superpowers/plans/2026-09-17-errores-api-auth-contrato.md`, `## Cierre`), que resolvió lo mismo para `AuthApiController`.

## Problema medido

- **Emisores:** `LpsApiController::renderApiError()` (`src/Controllers/Api/LpsApiController.php:304-319`) y `renderLegacyError()` (`:327-341`) emiten siempre `error.fields`. `LpsApiError` lo inicializa en `[]` (`src/Services/Lps/LpsApiError.php:18`), y PHP lo serializa como lista.
- **Por qué se descarta:** `EsquemaCuerpoErrorApi` (`frontend/src/lib/api/esquemas/error.ts`) exige un objeto en `fields`. `pedir()` (`frontend/src/lib/api/cliente.ts`) descarta entonces el cuerpo **entero** y entrega `codigo = HTTP_<status>` y un mensaje genérico.
- **Lo que ve el usuario:** medido en la revisión del #44, `GET /api/lps/comments?consecutivo=999999999&modulo=PS` responde 404 `LPS_TARGET_NOT_FOUND` con `fields: []`. `LpsDrawerProvider.tsx:140` no reconoce el código y `noDisponible` queda en `false`. `CajonContextualLps.tsx:200` no muestra la rama «no disponible». **Corrección del cierre (2026-09-17):** este diagnóstico era inexacto en el efecto. El usuario no lee «respondió 404», porque `CajonContextualLps.tsx:200-204` no pinta el mensaje del error cuando `noDisponible` es falso. Lo que ve es el cajón normal (estado, restricciones y «0 comentario(s)», con su formulario) sobre una actividad que no existe. Lo midió la captura RED del spec de navegador. Con el 409 `LPS_TARGET_STALE` pasa lo mismo.

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

## Cierre

**Estado (2026-09-17):** hecho en la rama `fix/errores-api-lps-contrato`, sin push. Tres commits de código y tests más este de documentación.

**Qué se hizo**

- **Contrato entre PHP y Zod.** `tests/test_lps_api_contract.php` captura cuatro cuerpos y los contrasta con `tests/fixtures/api-lps-error-bodies.json`: 404 `LPS_TARGET_NOT_FOUND` (`GET comments?consecutivo=999999999&modulo=PS`), 409 `PROFILE_REQUIRED` (`POST comments/add`, sin DML), 422 `VALIDATION_FAILED` con `fields` (`modulo=ZZ`) y 409 `LPS_TARGET_STALE`. Se regenera con `LPS_REGENERAR_CUERPOS=1`. `frontend/src/lib/api/esquemas/error.contrato.test.ts` ahora lee los dos archivos, el de auth y el de LPS, y pasa cada cuerpo por `EsquemaCuerpoErrorApi` y por `pedir()`.
- **Arreglo.** `renderApiError()` y `renderLegacyError()` arman el bloque `error` con un helper común, `errorBlock()`, que omite `fields` cuando va vacío. El test PHP afirma además que sin campos no hay `fields` y que, con campos, `fields` es un objeto no vacío, incluido el error legacy de `comments/add`.
- **Navegador.** `tests/browser/lps-errores-contrato.spec.mjs` corre contra `:8099` sin interceptar la red: la sesión entra por la puerta de servicio y `/api/session` y `/api/lps/comments` son reales. Comprueba que el cajón muestra «Esta actividad o alerta ya no está disponible.», que no pinta el formulario, que el cuerpo coincide con el archivo y que no hay errores de página.

**RED → GREEN**

- Vitest contra los cuerpos previos: `6 failed | 12 passed (18)`. El esquema respondía «expected record, received array» y `pedir()` entregaba `HTTP_404`/`HTTP_409`. Después: `18 passed`.
- Spec de navegador antes del arreglo: el cajón abría con el cuerpo normal (diagnóstico, restricciones, «0 comentario(s)») y sin la alerta «no disponible». Después: `1 passed`.
- El contrato PHP detectó solo la divergencia tras el arreglo (`FALLOS: 1 de 68`) hasta regenerar el archivo.

**Verificación final** (cada RC en su propia línea): `npm --prefix frontend test` 638 passed RC=0 · `npm run frontend:typecheck` RC=0 · `npm run frontend:build` RC=0, sin cambios en `public/app` · `test_lps_api_contract.php` 73 aserciones RC=0 · `test_api_auth_contract.php` RC=0 · `run-php-tests.php --nivel=http` 110/110 y 31 clases PHPUnit RC=0 · Playwright contra `:8099` (el spec nuevo, `auth-errores-contrato`, `escalamientos-sin-errores`, `lps-drawer-fetch-lifecycle` y `lps-drawer-design-system`): 11 passed y 1 failed, RC=1; el fallo es previo, ver abajo · `npm run test:design-system:static` RC=0 · `git diff --check` RC=0 · `npm run test:wiki`: la primera corrida dio RC=1 con 41 commits, pero esa cifra no se reproduce. Remedido sobre `af8260e3`, `node scripts/wiki-lint.mjs --estricto` da RC=0 con 39 commits de código contra un umbral de 40. La diferencia viene de que `scripts/wiki-veracidad.mjs:129` usa `--since=<fecha>` sin hora, y la ventana se mueve durante el día. **La deuda real sigue:** el pase de veracidad está pendiente y el umbral se cruza con muy pocos commits de código más.

**Desvíos**

- **`meta.requestId` es aleatorio en cada respuesta.** Al capturar se reemplaza por `<requestId>`, y solo si tiene la forma de 16 hex; si no, el archivo no coincidiría nunca.
- **El 409 `LPS_TARGET_STALE` no se provoca por HTTP.** Necesita una alerta existente y cerrada, y `lps_escalamientos` tiene 0 filas en la base de dev: crear una sería DML. Se captura invocando por reflexión el `renderApiError()` real con `LpsApiError::targetStale()`, sin constructor (el render no toca la base). En el archivo queda marcado `origen: render-puro`.
- **Ninguna pantalla React abre hoy el cajón.** `DisparadorLps` no tiene consumidores y `rutas.tsx` dice «ninguna superficie migrada todavía». `AppShell` sí monta el provider y el cajón reales bajo `/app`, así que el spec llama a `abrir()` alcanzando el valor del provider por el árbol de fibras de React. Si el árbol cambia, falla con un mensaje propio, nunca en verde.
- **El usuario no leía «respondió 404», a diferencia de lo que decía el plan.** `CajonContextualLps` no pinta `error.mensaje` cuando `noDisponible` es falso: pinta el cuerpo normal del cajón (diagnóstico, hilo vacío y formulario de comentario) sobre una actividad que no existe. El defecto es el mismo, pero se ve distinto.
- Se agregó el caso 409 `PROFILE_REQUIRED`, que el plan no pedía. Es un error real sin campos que se alcanza sin DML.

**Barrido**

- **`SessionApiController` con `reason: null`:** confirmado que no hace daño. `frontend/src/lib/api/esquemas/arranque.ts` declara `reason: z.null()` en los estados autenticado y de cambio de clave, y `z.enum(...)` en el anónimo. Esas respuestas no pasan por el esquema de error.
- **Consumidores de `error.fields`:** en `frontend/src` solo lo lee `cliente.ts`, con `?? null`. `ct-app/src` y `pdc-app/src` no lo usan. `public/js/modules/lps_drawer.js` lee `respuesta` y `mensaje`, no `fields`. `tests/unit/LpsThreadServiceTest.php` mira `apiError()->fields` en el objeto, que no cambió. Nadie depende de que `fields` exista siempre.

**Fuera de alcance, anotado**

- `tests/browser/lps-drawer-design-system.mjs` falla por su golden (`lps-drawer-dark-1180x820.png`, ratio 0,94 de píxeles distintos). Es previo: con el controlador de `a33d87d6` restaurado temporalmente falla igual. La página es el cajón legado de `/dashboard/escalamientos`, que el arreglo no toca. Ese spec además entra por `/login` con credenciales, no por la puerta de servicio.
- `tests/browser/escalamientos-acciones.spec.mjs` no se corrió: siembra y borra una alerta con `docker compose exec`, y eso es DML y está prohibido en este frente.
- `tests/browser/lps-errores-contrato.spec.mjs` no corre en CI, igual que `auth-errores-contrato.spec.mjs`.
- **En CI solo se vigila el lado cliente.** `tests/test_lps_api_contract.php` es `@requiere: datos-proyecto`, y el CI solo corre los niveles `puro`, `http` y `admin-db` (`.github/workflows/ci.yml:124,280`; niveles en `scripts/run-php-tests.php:19`). Vitest compara contra el archivo guardado, así que si el servidor volviera a emitir `fields: []` el CI seguiría verde. Solo lo detecta una corrida local del contrato PHP. **Pendiente:** bajar la captura a nivel `http`, a condición de confirmar antes que la base del CI siembra el proyecto `PDC Sandbox E2E` y la cuenta `test.R` que usa el test. No se implementó.
- **(minor)** La ventana de la alarma de veracidad depende de la hora de la corrida (`scripts/wiki-veracidad.mjs:129`, `--since` sin hora), así que la cifra cambia a lo largo del día.
- **(minor, fragilidad conocida)** `tests/browser/lps-errores-contrato.spec.mjs:66-78` copia a mano `configuracionPorDefecto()`. Si la forma de la configuración de restricciones cambia, la copia se queda vieja en silencio.
