---
capa: fuente
tipo: biblia
estado: vigente
fecha: 2026-08-04
areas: [lps]
fuente: docs/flujos/transversal-autenticacion.md
resumen: Escenarios AUTH-. Qué debe pasar al entrar, al permanecer y al caducar.
---

# Biblia · Transversal · Autenticación y sesión

Escenarios `AUTH-*`. Qué debe pasar al entrar, al permanecer y al caducar.

Formato y reglas: `docs/flujos/README.md`. Todo lo de aquí se verificó por lectura el **2026-08-04**
contra el árbol de esa fecha.

---

## AUTH-001 · Una ruta protegida sin sesión manda al login

- **Rol:** ninguno (no hay sesión).
- **Precondiciones:** `$_SESSION['usuario']` no existe.
- **Pasos:**
  1. El navegador pide cualquier ruta que pase por `SessionMiddleware::check()`.
  2. El guardián no encuentra `usuario` en sesión y llama a
     `finishUnauthorized('/login', 'missing_session')`.
- **Resultado esperado:** cabecera `Location: /login` y fin de la ejecución. **En datos:** nada
  cambia.
- **Verificación:** lectura — `src/Core/SessionMiddleware.php:30-32`, `:98-113`.

## AUTH-002 · La misma falta de sesión, en una petición de datos, responde JSON

Es el hermano de `AUTH-001` y **el que rompe grillas cuando se equivoca**: si una petición que
espera datos recibe HTML de login, la grilla intenta pintarlo y falla de forma incomprensible.

- **Rol:** ninguno.
- **Precondiciones:** sin sesión, y la petición lleva la cabecera **`X-AIA-Expect-Json`** con valor
  `1`, `true` o `json`.
- **Pasos:**
  1. La petición llega a `SessionMiddleware::check()`.
  2. `expectsJsonResponse()` reconoce la cabecera y `finishUnauthorized()` toma la rama JSON.
- **Resultado esperado:** código **401**, `Content-Type: application/json`, y cuerpo
  `{"success":false,"sessionExpired":true,"reason":"missing_session","redirect":"/login"}`.
  **En datos:** nada.
- **Verificación:** lectura — `src/Core/SessionMiddleware.php:91-96` (la cabecera) y `:98-110` (la
  respuesta).

> **Atención al detalle que se presta a error:** la decisión **no** mira `Accept:
> application/json` ni `X-Requested-With`. Depende exclusivamente de la cabecera propietaria
> `X-AIA-Expect-Json`. Un cliente que envíe el `Accept` estándar y espere JSON recibirá un
> `Location` y se romperá. Cualquier código nuevo que consuma datos debe mandar esa cabecera.

> **Hallazgo del 2026-08-04 (registrado, no corregido).** Este escenario describe lo que **debe**
> pasar, y hoy pasa solo en dos sitios. La cabecera la envían únicamente
> `public/js/core/SessionTimeoutManager.js:145` y `public/js/components/notifications.js:25`:
> **ningún módulo de grilla la manda** (`grep -rl X-AIA-Expect-Json public/js/modules/` devuelve 0).
> Es decir, cuando la sesión caduca mientras alguien trabaja en una grilla, la petición recibe el
> HTML del login en vez del 401, y el usuario pierde lo que estaba haciendo sin un error
> entendible. Está en `docs/EXPERIMENTS.md`; aquí la biblia mantiene el comportamiento correcto,
> que es el que debería cumplirse.

## AUTH-003 · Una cuenta desactivada pierde la sesión en la siguiente petición

- **Rol:** cualquiera con sesión abierta.
- **Precondiciones:** sesión válida y `general_usuarios.activo` distinto de `1` para ese usuario.
- **Pasos:**
  1. `check()` consulta `SELECT activo FROM general_usuarios WHERE usuario = ?`.
  2. Al ver `activo <> 1`, ejecuta `session_unset()` y `session_destroy()`.
  3. Llama a `finishUnauthorized('/login?inactive=1', 'inactive')`.
- **Resultado esperado:** la sesión queda destruida y el usuario aterriza en `/login?inactive=1`.
  **En datos:** nada cambia en la base; solo muere la sesión.
- **Verificación:** lectura — `src/Core/SessionMiddleware.php:35-46`.

## AUTH-004 · Si la comprobación de cuenta activa falla, la petición **continúa**

Escenario incómodo y por eso obligatorio: describe un comportamiento *fail-open*.

- **Rol:** cualquiera con sesión abierta.
- **Precondiciones:** la consulta de `activo` lanza una excepción (base caída, tabla bloqueada).
- **Pasos:**
  1. `check()` entra en el `catch (\Throwable $e)`.
  2. Registra el error con `error_log(...)` y **no interrumpe**.
  3. La ejecución sigue con la comprobación de inactividad.
- **Resultado esperado:** la petición se atiende con normalidad; queda una línea en el log del
  servidor. **En datos:** nada.
- **Verificación:** lectura — `src/Core/SessionMiddleware.php:47-49`.

> **Lo que esto implica, dicho claro:** mientras la base no responda, una cuenta desactivada
> conserva su acceso. El impacto real es acotado —si la base está caída, casi nada de la aplicación
> funciona— y cerrar la sesión de todos ante un fallo transitorio tendría su propio coste. Se
> documenta como comportamiento conocido, no como bug: elegir entre *fail-open* y *fail-closed* aquí
> es una decisión de producto.

## AUTH-005 · La sesión caduca a la hora de inactividad

- **Rol:** cualquiera con sesión abierta.
- **Precondiciones:** `$_SESSION['timeout']` existe y han pasado **3600 segundos o más** desde su
  valor.
- **Pasos:**
  1. `check()` calcula `time() - $_SESSION['timeout']`.
  2. Si el resultado alcanza `IDLE_TIMEOUT_SECONDS`, destruye la sesión.
  3. Llama a `finishUnauthorized('/login?timeout=1', 'timeout')`.
- **Resultado esperado:** sesión destruida y redirección —o el 401 JSON de `AUTH-002` si la petición
  lleva la cabecera—, con `reason: "timeout"`. **En datos:** nada.
- **Verificación:** lectura — `src/Core/SessionMiddleware.php:7` (`IDLE_TIMEOUT_SECONDS = 3600`),
  `:52-60`.

## AUTH-006 · Una petición de fondo puede no refrescar el contador de inactividad

- **Rol:** cualquiera con sesión abierta.
- **Precondiciones:** la petición lleva `X-AIA-Idle-Refresh` con valor `0`, `false` o `skip`.
- **Pasos:**
  1. `shouldRefreshTimeout()` devuelve `false`.
  2. El contador de inactividad **no** se actualiza con esta petición.
- **Resultado esperado:** un sondeo automático no mantiene viva indefinidamente la sesión de alguien
  que se fue a comer. **En datos:** nada.
- **Verificación:** lectura — `src/Core/SessionMiddleware.php:84-89`.

## AUTH-007 · Login con credenciales correctas

- **Rol:** el real de la cuenta, resuelto después en `project_members` (ver `PROY-*`).
- **Precondiciones:** cuenta existente con `activo = 1` y contraseña correcta.
- **Pasos:**
  1. `POST /login` llega a `LoginController::login()`.
  2. Se comprueba que usuario y contraseña no vengan vacíos.
  3. Se verifica el hash con `password_verify()`.
  4. Se establece la sesión y se redirige.
- **Resultado esperado:** sesión iniciada y salto a la vista de entrada. **En datos:** solo lo que
  el propio inicio de sesión registre.
- **Verificación:** lectura — `src/Controllers/Auth/LoginController.php:42`, `:55`, `:208`.
- **No comprobable en lectura:** si se registra el intento y dónde. Pendiente de medir.

## AUTH-008 · Login con credenciales incorrectas no distingue el motivo

- **Rol:** ninguno.
- **Precondiciones:** usuario inexistente **o** contraseña equivocada.
- **Pasos:**
  1. `POST /login`; `password_verify()` falla o no hay fila.
  2. Se acumula el mensaje «Usuario o contraseña incorrectos».
- **Resultado esperado:** vuelve al formulario con **un mensaje genérico**, que no revela si el
  usuario existe. Es lo correcto: distinguirlos permitiría enumerar cuentas.
  **En datos:** nada.
- **Verificación:** lectura — `src/Controllers/Auth/LoginController.php:112`.

> Contrasta con `AUTH-003`: la cuenta **inactiva** sí recibe un mensaje específico
> (`LoginController.php:65-66`), lo que sí confirma que la cuenta existe. Es una decisión defendible
> —el usuario legítimo necesita saber a qué atenerse— pero conviene tenerla escrita y no descubrirla
> por accidente.

## AUTH-009 · La puerta de servicio solo abre con las tres condiciones

- **Rol:** el real de la cuenta sembrada; **no concede permisos extra**.
- **Precondiciones:** entorno que permite herramientas internas, `DEV_DOOR=1`, y `DEV_DOOR_USERS`
  con la cuenta pedida.
- **Pasos:**
  1. `GET /dev/entrar?u=<cuenta>&p=<Proyecto_Proceso>`.
  2. `DevDoor` comprueba entorno, bandera y lista de usuarios.
  3. Con `p`, entra al proyecto; sin `p`, aterriza en `/proyectos`.
- **Resultado esperado:** sesión abierta con el rol **real** de `project_members`.
  **En datos:** nada más que la sesión.
- **Verificación:** lectura — `src/Core/DevDoor.php:30` (entorno), `:67` (`DEV_DOOR === '1'`),
  `:47` (cuenta en la lista).

## AUTH-010 · Con la puerta cerrada, la ruta no confiesa que existe

- **Rol:** ninguno.
- **Precondiciones:** falta cualquiera de las tres condiciones de `AUTH-009`.
- **Pasos:** se pide `/dev/entrar` igual.
- **Resultado esperado:** **404**, no 403. Un 403 confirmaría que el endpoint existe y es una pista
  para quien busque puertas traseras. **En datos:** nada.
- **Verificación:** lectura — `src/Core/DevDoor.php:22` (la decisión está escrita como intención en
  el propio archivo).
- **Pendiente de comprobar en ejecución:** que la respuesta real sea 404 y no otra cosa. Sube a
  prueba ejecutable.

---

## Escenarios pendientes de esta pasada

Se enumeran para que el recorte no se lea como cobertura completa:

- Recuperación de contraseña: `/password/forgot`, `/password/reset`, `/password/update` — cuatro
  rutas, sin escenarios todavía. Requieren además revisar el envío de correo, que no es comprobable
  en lectura.
- Modo mantenimiento: `/_aia/operacion/7f3c9b`, su ruta secreta y qué ve un usuario normal mientras
  está activo.
- Cierre de sesión (`/logout`): qué se destruye exactamente y a dónde se aterriza.
