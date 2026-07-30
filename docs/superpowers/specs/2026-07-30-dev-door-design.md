# Puerta de servicio de desarrollo (`DevDoor`)

Fecha: 2026-07-30
Estado: diseño aprobado, pendiente de plan de implementación

## Problema

Validar cambios en el navegador exige una sesión autenticada. Hoy la única forma de
obtenerla es teclear usuario y contraseña en `/login`. Eso tiene tres costes reales:

1. El asistente no puede escribir credenciales en formularios (política del entorno de
   ejecución), así que depende de que una persona haga el login por él.
2. El panel de navegador integrado pierde la cookie cada 60–90 segundos, de modo que el
   login manual hay que repetirlo constantemente durante una sesión de QA.
3. Las pruebas e2e de la app principal no tienen un equivalente a
   `E2E_ADMIN_PASSWORD`, que sí existe para `admin/` (`e2e/support/admin.mjs:16`).

## Objetivo

Una entrada de desarrollo que deje una sesión válida —usuario **y** proyecto— sin teclear
credenciales, que sea imposible de alcanzar fuera de una máquina de desarrollo, y que no
altere el flujo de login real cuando no se usa.

## Alcance

**Dentro:** la app principal (`public/index.php`, `http://localhost:8081`).

**Fuera:** el panel `admin/`, que tiene front controller y sesión propios y cuyos e2e ya
resuelven la autenticación por variable de entorno. No se toca.

**Fuera:** cualquier cambio al login real, al `SessionMiddleware` o al RBAC. La puerta
omite la comprobación de contraseña; no relaja ningún permiso posterior.

## Decisión de diseño

Se evaluaron tres mecanismos:

| Opción | Descripción | Por qué no |
| --- | --- | --- |
| Script CLI que emite un `PHPSESSID` | Cero superficie HTTP | Fricción alta; inservible con la pérdida de cookie del panel |
| Autologin en cada petición | Sin URL que recordar | Impide probar login real, estado deslogueado y timeout |
| **Ruta condicional** | `GET /dev/entrar` registrada solo bajo candado | **Elegida** |

La ruta condicional es la única que sirve a la vez para QA manual en el navegador y para
los e2e, y la única que deja el flujo real intacto.

## El candado

Tres condiciones, **todas** obligatorias, evaluadas antes de registrar la ruta en el
router:

1. `App\Core\AppEnvironment::allowsInternalTools()` — `APP_ENV` es `development` o
   `testing`. `AppEnvironment::normalize()` ya cae a `production` ante cualquier valor
   desconocido (`src/Core/AppEnvironment.php:19`), así que un `.env` corrupto o ausente
   **cierra** la puerta en vez de abrirla.
2. `$_SERVER['REMOTE_ADDR']` pertenece a `127.0.0.1`, `::1` o al rango privado de la red
   de Docker Compose.
3. `DEV_DOOR=1` presente y explícito en `.env`, y `DEV_DOOR_USERS` no vacío.

Si falla cualquiera de las tres, la ruta **no se registra** y `/dev/entrar` deja de estar
en `$publicRoutes`. La respuesta observada depende de quién pregunte, y en ambos casos es
indistinguible de una URL que nunca existió:

- Visitante anónimo: `302` a `/login`, exactamente igual que cualquier otra ruta protegida.
- Sesión ya iniciada: `404` de FastRoute.

Nunca un `403`, que confirmaría la existencia del endpoint.

### Trampa medida: `APP_ENV` no se puede cerrar editando `.env` en local

`docker-compose.yml:12` inyecta `APP_ENV: ${APP_ENV:-development}` como variable de
entorno del contenedor. `Dotenv::createImmutable()` **no sobrescribe** variables que ya
existen en el entorno, así que editar `APP_ENV` en `.env` es un no-op dentro de Docker: el
contenedor sigue viendo `development`.

Consecuencia práctica: la condición 1 no se puede ejercitar por HTTP en local, solo por el
test unitario (que manipula `$_ENV` directamente). Quien intente comprobar el candado
cambiando `APP_ENV` en `.env` concluirá erróneamente que está roto. Para cerrar la puerta
en local hay que usar `DEV_DOOR=0`.

Donde importa —producción en SiteGround, sin Docker— `APP_ENV` sí sale de `.env`, y
`AppEnvironment::normalize()` devuelve `production` si falta o es desconocido.

## Usuarios admitidos

`DEV_DOOR_USERS` en `.env` lista los logins admitidos, uno por rol RBAC relevante:

```
DEV_DOOR=1
DEV_DOOR_USERS=test.A,test.R,test.V
```

Se usan las cuentas de prueba sembradas, no cuentas de personas reales. Verificado contra
la base local el 2026-07-30: `test.A`, `test.R` y `test.V` están activos y tienen
respectivamente los roles `A`, `R` y `V` tanto en `Da Porto` como en el proyecto sintético
`PDC Sandbox E2E` (990100). Existen además `test.C` (rol `C`, subcontratista) y `test.D`
(rol `D`, director de obra) por si alguna verificación futura los necesita; se añaden a la
lista solo cuando haga falta, no por adelantado.

Que la puerta acepte únicamente cuentas `test.*` es una segunda propiedad útil: aunque el
candado fallara, no habría forma de suplantar a un usuario real del proyecto.

Cada instalación puede ajustar la lista en su propio `.env`, que no está versionado. Un
login fuera de la lista produce 404, igual que el candado cerrado.

Tener un usuario por rol (Admin, Residente, Visualizador) es lo que permite cumplir el
contrato de `AGENTS.md`: verificar al menos un rol permitido y uno denegado cuando se
tocan rutas protegidas.

No hay contraseñas en `.env` ni en ningún otro sitio: la puerta no valida credenciales,
las omite. Por eso el candado es la única defensa y por eso son tres condiciones.

## Comportamiento

`GET /dev/entrar?u=<login>&p=<Proyecto_Proceso>`

1. Verifica que `u` esté en `DEV_DOOR_USERS`.
2. Verifica que el usuario exista y tenga `activo = 1` en `general_usuarios` — el mismo
   criterio que aplica `SessionMiddleware::check()` (`src/Core/SessionMiddleware.php:44`).
3. Establece `$_SESSION['usuario']`.
4. Si viene `p`, delega la entrada al proyecto en la lógica existente y redirige a la ruta
   de aterrizaje que esa lógica resuelve. Sin `p`, redirige a `/proyectos`.

Cualquier fallo en los pasos 1–2 responde 404, sin mensaje que distinga la causa.

### Reutilización, no duplicación

`ProjectSelectorController::select()` ya hace, hoy, el trabajo correcto: verifica la
membresía real contra `project_members`, normaliza el código de rol, respeta
`Acceso = 0`, fija el contexto de proyecto en `Database` y resuelve la semana de
aterrizaje (`src/Controllers/Core/ProjectSelectorController.php:96-130`).

La puerta **no reimplementa nada de eso**. Se extrae el cuerpo de `select()` a un método
público `enterProject(string $usuario, string $proyecto)` y lo invocan los dos caminos.
`select()` queda como el adaptador HTTP que lee `$_POST['proyecto']` y llama a
`enterProject()`.

Consecuencia deseada: el rol con el que quedo dentro es el rol **real** de ese usuario en
ese proyecto, leído de la base. Es lo que hace que la puerta sirva para probar RBAC en vez
de falsearlo.

Esta extracción es refactor sin cambio de comportamiento y se verifica con los e2e de
selección de proyecto ya existentes.

## Archivos

| Archivo | Cambio |
| --- | --- |
| `src/Core/DevDoor.php` | Nuevo. El candado (`isOpen()`) y la comprobación de lista (`allows(string $login)`). Sin estado, testeable aislado. |
| `src/Controllers/Core/DevDoorController.php` | Nuevo. La ruta. |
| `src/Controllers/Core/ProjectSelectorController.php` | Extracción de `enterProject()`. Sin cambio de comportamiento. |
| `public/index.php` | Registro condicional: `if (DevDoor::isOpen()) { $router->get('/dev/entrar', ...); }` |
| `.env` | Dos claves nuevas. Ya está en `.gitignore`. |
| `tests/test_dev_door_guard.php` | Nuevo. Ver abajo. |

## Verificación

`tests/test_dev_door_guard.php`, siguiendo el patrón autoejecutable del repositorio,
comprueba que `DevDoor::isOpen()` devuelve `false` con:

- `APP_ENV=production`
- `APP_ENV` ausente o con un valor desconocido
- IP remota fuera de los rangos locales
- `DEV_DOOR` ausente o distinto de `1`
- `DEV_DOOR_USERS` vacío

y que `DevDoor::allows()` rechaza un login fuera de la lista incluso con el candado
abierto.

El test existe para que el candado no se afloje sin que nadie se entere: si alguien
elimina una de las tres condiciones, el test falla.

Verificación adicional en navegador: abrir `/dev/entrar` con cada uno de los tres usuarios
y confirmar que se aterriza autenticado con el rol correspondiente, en el viewport
canónico 1180×820 y dark mode.

## Lo que este diseño no hace

- No toca `admin/`.
- No modifica el login real, el timeout de sesión ni el estado deslogueado, que siguen
  siendo probables.
- No otorga permisos: una vez dentro, el RBAC se aplica exactamente igual.
- No añade credenciales a ningún archivo versionado.
