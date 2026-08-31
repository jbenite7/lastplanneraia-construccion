---
capa: fuente
tipo: spec
estado: vigente
id: S01
fecha: 2026-08-30
superficie: acceso
rutas: ["/", "/login"]
depende_de: [T01]
views: [VIEW-01]
areas: [arquitectura, rbac, design-system]
fuente: "auditoría de LoginController, AuthApiController, AuthenticationService, login.view.php y frontend/src/shell/PantallaLogin.tsx"
resumen: "Migración decision-complete del acceso principal a React, incluido login normal, mensajes seguros, cambio obligatorio de contraseña y entrada oculta durante mantenimiento."
---

# S01 — Login React

> **Estado:** aprobado por Felipe el 2026-08-30. S01 no incluye recuperación/restablecimiento de
> contraseña, no modifica RLS ni datos y no autoriza implementación fuera de su plan aprobado.

## 1. Resultado buscado

`/` y `/login` muestran la superficie React de acceso con toda la capacidad observable del login
legacy y sin delegar el cambio obligatorio de contraseña a la vista PHP. El mismo sistema de
componentes sirve la entrada oculta de mantenimiento sin publicar el secreto ni ampliar sus
permisos.

Al terminar S01:

- el login normal, sus avisos, validaciones y recuperación ante errores son React;
- el cambio obligatorio se completa o cancela dentro del flujo React;
- PHP conserva verificación de credenciales, hashes, sesión, CSRF y autorización de mantenimiento;
- `views/auth/login.view.php` deja de ser necesaria para el acceso normal;
- S02 y S03 continúan en PHP hasta sus propios cortes.

## 2. Dependencias y propiedad

- **T01:** host SPA, bootstrap, `ApiClient`, tema, estados de sesión y error global.
- **S02:** destino `/password/forgot`; S01 solo preserva el enlace.
- **S03:** al completar un reset, `/login?reset=1` muestra confirmación segura.
- **RLS:** `docs/security/rls-runtime-boundary.md`; no se reabre.

S01 es propietaria de:

| ID | Archivo | Destino |
|---|---|---|
| VIEW-01 | `views/auth/login.view.php` | `frontend/src/shell/auth/PantallaLogin` y `CambioClaveObligatorio`. |

`head_brand.php` pertenece a T01. `login-brand-unified.css` y `auth_forms.js` siguen compartidos con
S02/S03 hasta que esas superficies migren.

## 3. Auditoría del comportamiento actual

### 3.1 Rutas y controladores

| Método y ruta | Implementación actual | Comportamiento comprobado |
|---|---|---|
| `GET /` | `LoginController::index` | Alias de `/login`; autenticado sin cambio pendiente redirige a `/dashboard`. |
| `GET /login` | `LoginController::index` | Renderiza VIEW-01; interpreta `timeout`, `inactive`, `reset` y sesión de cambio pendiente. |
| `POST /login` | `LoginController::login` | Form URL-encoded; valida credenciales, cuenta activa, cambio forzado y crea sesión parcial. |
| `POST /api/auth/login` | `AuthApiController::login` | JSON + `X-CSRF-Token`; endpoint que ya consume React. |
| `POST /password/update` | `LoginController::updatePassword` | FormData/JSON de salida; cambia la clave pendiente, pero no usa el contrato API común. |
| `GET /login/cancelar` | `LoginController::cancelPasswordChange` | Destruye únicamente la sesión pendiente y vuelve a login/ruta oculta. |
| `POST /api/auth/logout` | `AuthApiController::logout` | Contrato T01; CSRF, sesión y cookie. |
| `GET/POST` ruta oculta | `LoginController::index/maintenanceLogin` | Solo durante mantenimiento; exige credenciales válidas, cuenta activa y rol global A en Construcción. |

### 3.2 Credenciales y sesión

`AuthenticationService`:

- consulta `general_usuarios` por username preparado;
- admite `password_hash` y migra un hash SHA-512 legacy al primer acceso correcto;
- regenera el ID de sesión;
- usa `usuario_temp` + `must_change_password` antes de autenticación completa;
- al autenticar limpia proyecto, base legacy, semana, permiso y PDC activo.

Una cuenta inactiva y una credencial incorrecta reciben la misma respuesta JSON `401`, evitando
enumeración. La ruta form legacy todavía muestra un mensaje específico para cuenta inactiva; el
contrato React conserva la respuesta genérica del API.

### 3.3 Vista legacy

VIEW-01 aporta:

- lockup y contexto de marca;
- usuario y contraseña con labels/autocomplete;
- mostrar/ocultar contraseña;
- estado ocupado del submit;
- enlace de recuperación;
- avisos de error, reset, timeout e inactividad;
- footer corporativo;
- diálogo SweetAlert2 de cambio obligatorio;
- validación de longitud, mayúscula, especial y confirmación;
- cancelación explícita sin cierre por backdrop ni Escape accidental.

### 3.4 React existente

`PantallaLogin.tsx` ya:

- usa `/api/auth/login` mediante `cliente.ts` y un esquema Zod local;
- envía CSRF y credenciales JSON;
- muestra estado ocupado y mensaje genérico en `401`;
- conserva el enlace a S02.

Todavía falta:

- composición/marca equivalente;
- mostrar/ocultar contraseña;
- motivos `timeout`, `inactive` y confirmación `reset`;
- errores de campo tipados;
- cambio obligatorio React;
- cancelación segura;
- modo mantenimiento;
- tema accesible desde la pantalla;
- pruebas browser de ambos temas y tres viewports.

## 4. Matriz de paridad

| ID | Capacidad | Resultado objetivo |
|---|---|---|
| S01-UX-01 | Marca y contexto | Nombre Last Planner AIA, contexto Construcción y footer presentes sin dominar el formulario. |
| S01-UX-02 | Usuario | Label, `autocomplete=username`, sin autocapitalización ni corrección ortográfica. |
| S01-UX-03 | Contraseña | Label, `autocomplete=current-password` y toggle con `aria-pressed`. |
| S01-UX-04 | Envío | Enter/click envía una sola vez; campos y botón ocupados; copy `Entrando…`. |
| S01-UX-05 | Credenciales inválidas | Mensaje genérico idéntico para usuario inexistente, clave errónea o cuenta inactiva. |
| S01-UX-06 | Timeout/inactividad | Aviso seguro derivado de `reason`; se muestra una vez sin dejar query stale. |
| S01-UX-07 | Reset completado | `/login?reset=1` muestra confirmación y limpia el parámetro sin recarga. |
| S01-UX-08 | Recuperación | Enlace navegable a `/password/forgot`; S01 no duplica S02. |
| S01-UX-09 | Cambio obligatorio | Dos campos, política visible, errores asociados, confirmación y éxito sin salir a PHP. |
| S01-UX-10 | Cancelar cambio | Salida explícita destruye solo sesión pendiente y vuelve al acceso. |
| S01-UX-11 | Mantenimiento | Misma presentación React; acción y autorización continúan server-side en la ruta oculta. |
| S01-UX-12 | Temas | Oscuro inicial y conmutación clara/oscura antes y durante el formulario. |
| S01-UX-13 | Recuperación técnica | Red/5xx/contrato inválido permiten reintentar sin presentar una falsa credencial inválida. |

## 5. Modelo de pantalla

### 5.1 Login normal

```text
PantallaLogin
├─ Saltar al formulario
├─ PanelContextoMarca
│  ├─ marca Last Planner AIA
│  └─ contexto Construcción / +CERTEZA
└─ PanelAcceso
   ├─ ConmutadorTema (T01)
   ├─ Aviso de sesión/reset/error
   ├─ CampoUsuario
   ├─ CampoContrasena + toggle
   ├─ Acción Entrar
   ├─ Enlace recuperar contraseña
   └─ Footer
```

Desktop usa dos paneles; tablet reduce el panel contextual; móvil converge a una columna sin
ocultar nombre del producto ni acciones.

### 5.2 Cambio obligatorio

`CambioClaveObligatorio` es un diálogo modal en desktop/tablet y panel de página en móvil si el
espacio no permite un diálogo seguro. Contiene:

- nueva contraseña;
- confirmación;
- política visible y asociada con `aria-describedby`;
- toggle independiente por campo;
- resumen accesible y errores de campo;
- acciones `Actualizar y continuar` y `Salir`.

No cierra al pulsar backdrop. `Escape` no destruye la sesión silenciosamente: abre la confirmación
de salida y solo una segunda acción explícita ejecuta la cancelación. Al cerrar, el foco vuelve al
control que corresponda; durante el estado pendiente no existe acceso al shell.

## 6. Máquina de estados S01

| Estado | Entrada | Salida |
|---|---|---|
| `idle` | Bootstrap anónimo | Formulario disponible. |
| `submitting` | Submit válido | Una petición; controles deshabilitados y `aria-busy=true`. |
| `invalid_credentials` | `401` | Mensaje genérico; conservar username y vaciar password. |
| `invalid_request` | `422` | Errores de campo + resumen; foco al primer error. |
| `unavailable` | red/5xx/contrato | Aviso recuperable; no confundir con credenciales. |
| `password_change_required` | Login responde `next=password_change` o bootstrap pendiente | Diálogo/panel obligatorio. |
| `changing_password` | Confirmación de nueva clave | Una mutación; no permite cerrar. |
| `cancel_confirmation` | Salir/Escape | Confirmación accesible antes de destruir la sesión pendiente. |
| `authenticated` | Login/cambio exitoso | Recargar T01 y continuar a S04 o landing autorizado. |

## 7. Contratos HTTP

### 7.1 `GET /api/session`

Propiedad T01. Para S01 debe distinguir:

- `state="anonymous"` con `reason` no sensible;
- `state="password_change_required"` sin `user`, proyecto, rol ni navegación;
- `state="authenticated"` para continuar fuera de S01;
- CSRF válido en los tres estados.

Esto amplía la forma actual, que presenta una sesión pendiente como `missing_session` y obliga a
redirigir a PHP.

### 7.2 `POST /api/auth/login`

Request:

```json
{
  "username": "test.A",
  "password": "<secreto>"
}
```

Headers: `Content-Type: application/json`, `Accept: application/json`, `X-CSRF-Token`.

Respuestas objetivo:

| HTTP | Cuerpo | Caso |
|---:|---|---|
| 200 | `{"success":true,"next":"projects","message":null}` | Credenciales válidas sin cambio pendiente. |
| 200 | `{"success":true,"next":"password_change","message":null}` | Credenciales válidas con `force_password_change=1`. |
| 401 | `{"success":false,"next":null,"message":"Usuario o contraseña incorrectos."}` | Usuario inexistente, clave incorrecta o cuenta inactiva. |
| 403 | `{"success":false,"next":null,"message":"Solicitud no permitida."}` | CSRF ausente/incorrecto. |
| 422 | Error tipado con campos `username`/`password` | Campos vacíos o forma inválida. |

El cambio de `mustChangePassword:boolean` a `next` es atómico entre PHP, Zod y React; el endpoint
no tiene otro consumidor productivo encontrado.

### 7.3 `POST /api/auth/password/change`

Endpoint nuevo sobre `ForcedPasswordChangeService` y `UserPasswordService`.

```json
{
  "password": "<secreto>",
  "confirmation": "<secreto>"
}
```

Requiere sesión pendiente y CSRF `shell_api`; nunca acepta username. Responde:

- `200 {"success":true,"next":"projects"}` y promueve la sesión;
- `401` genérico si no existe sesión pendiente;
- `403` por CSRF;
- `422` con errores de `password` o `confirmation` para política/confirmación;
- `5xx` genérico sin concatenar excepciones.

### 7.4 `POST /api/auth/password/cancel`

Body `{}`; sesión pendiente + CSRF. Destruye únicamente el estado previo a autenticación y responde
`200 {"success":true,"next":"login"}`. Sin sesión pendiente devuelve estado anónimo idempotente y
no puede cerrar una sesión completa desde una petición pública ajena.

### 7.5 Ruta oculta de mantenimiento

No se crea una API pública equivalente. `GET` sirve el host React en modo mantenimiento mediante
bootstrap inyectado por servidor; el bundle no contiene el valor de `MaintenanceMode::SECRET_PATH`.
El formulario usa la ruta actual recibida del host y el servidor conserva la autorización.

`POST` valida:

1. credenciales;
2. cuenta activa;
3. existencia de una membresía global con rol A, proyecto activo y área Construcción;
4. cambio obligatorio cuando aplique;
5. `maintenance_bypass` antes de continuar.

Un fallo vuelve al host oculto con error genérico; no redirige a `/login`, no confirma existencia de
usuario ni abre `/api/auth/login` durante mantenimiento. La página pública de mantenimiento sigue
siendo estática y responde 503.

Todos los endpoints nuevos tienen esquema Zod y prueba contractual PHP.

## 8. Reglas de contraseña

El servidor sigue siendo autoridad. Se conservan exactamente las reglas de
`PasswordPolicyService`:

1. mínimo seis caracteres;
2. al menos una mayúscula;
3. al menos un carácter no alfanumérico;
4. confirmación idéntica;
5. diferente de la contraseña anterior, incluido hash SHA-512 legacy.

React puede mostrar cumplimiento progresivo, pero no declara éxito hasta recibir `200`. Nunca se
registran, persisten, inspeccionan ni adjuntan contraseñas.

## 9. Seguridad, RBAC y RLS

- Login es una operación de identidad sin proyecto; no habilita consultas operativas.
- La sesión autenticada queda inicialmente sin proyecto y debe pasar por S04.
- CSRF se obtiene del bootstrap anónimo y se rota/valida en servidor.
- El ID de sesión se regenera al iniciar sesión, comenzar cambio obligatorio y completarlo.
- La respuesta inválida no distingue cuenta ausente, inactiva o clave errónea.
- Los logs pueden registrar username y evento, nunca clave, token, cookie ni payload completo.
- La actualización de hash SHA-512 ocurre solo después de verificarlo con `hash_equals`.
- El acceso de mantenimiento no usa capacidades de `/admin/`; conserva su comprobación global A
  específica y no amplía ese panel.
- Ningún request S01 acepta `project_id`, `db`, prefijo o rol desde React.
- S01 no modifica la frontera RLS, DDL/DML, grants, usuarios ni credenciales. Las mutaciones de
  identidad existentes permanecen en sus servicios autorizados.

## 10. Temas, responsive y accesibilidad

### 10.1 Temas

- Oscuro inicia cuando no hay preferencia; claro y oscuro son completos.
- El conmutador T01 está disponible antes de autenticarse.
- Marca, inputs, avisos, modal y estados usan `public/css/tokens.css`; cero hex/rgba locales.
- Cambiar tema no borra campos ni reinicia una petición.

### 10.2 Viewports

| Viewport | Composición |
|---|---|
| `390×844` | Una columna, panel de marca compacto, diálogo de clave como panel seguro si hace falta. |
| `768×1024` | Formulario prioritario y contexto reducido. |
| `1180×820` | Dos paneles sin scroll horizontal; formulario visible completo. |
| `1440×900` | Misma jerarquía con ancho máximo tokenizado. |

### 10.3 Accesibilidad

- un solo `h1` y landmark `main`;
- labels persistentes y autocomplete correcto;
- toggle con nombre y `aria-pressed`;
- errores con `role=alert`, resumen y relación a campos;
- busy anunciado sin cambiar el nombre de los campos;
- foco visible y orden lógico;
- modal con foco contenido, backdrop inerte y retorno de foco;
- teclado completo, zoom permitido, reduced motion y objetivos touch de 44 px;
- cero overflow horizontal y copy legible a 200 %.

## 11. Errores y recuperación

- `401` usa exclusivamente el mensaje genérico de credenciales.
- `403` CSRF indica que la solicitud no pudo completarse y permite obtener un bootstrap nuevo; no
  reenvía la contraseña automáticamente.
- `422` mantiene username, limpia únicamente campos secretos afectados y enfoca el primer error.
- Red/5xx conserva username, limpia password y habilita reintento manual.
- Un cuerpo que no pasa Zod se trata como contrato roto, no como credencial inválida.
- Al recibir éxito y fallar la recarga de sesión, se muestra la recuperación T01; no se repite el
  login.

## 12. Convivencia, corte y rollback

1. Construir y verificar S01 bajo `/app`.
2. Añadir a la política de rollout únicamente `GET/HEAD /` y `GET/HEAD /login`.
3. Mantener `POST /login`, `/password/update` y `/login/cancelar` durante la ventana de rollback.
4. No interceptar `/password/forgot` ni `/password/reset`.
5. Servir la ruta oculta mediante su controlador, no mediante una regla pública del router SPA.
6. Tras verificar rollback, promover las rutas canónicas.
7. Retirar VIEW-01 y adaptadores form legacy solo después de observar el corte y conservar la ruta
   oculta.

`/` se trata como ruta exacta; nunca como prefijo, porque capturaría toda la aplicación. Volver a
PHP exige cambiar la política de rutas, no tocar sesión, datos o RLS.

## 13. Retiro de legacy

| Pieza | Destino |
|---|---|
| `views/auth/login.view.php` | Retirar al cerrar S01 y la entrada oculta React. |
| `LoginController::index/login` | Mantener durante la ventana de rollback; luego GET queda servido por SPA, `POST /login` responde 405 y el host oculto usa un método dedicado. |
| `LoginController::updatePassword` | Retirar tras adoptar `/api/auth/password/change`. |
| `LoginController::cancelPasswordChange` | Retirar tras adoptar `/api/auth/password/cancel`. |
| `LoginController::maintenanceLogin` | Conservar como adaptador de la ruta oculta o mover a controlador dedicado sin cambiar reglas. |
| jQuery/SweetAlert de VIEW-01 | Retirar para S01; no borrar assets mientras S02/S03 los consuman. |
| `login-brand-unified.css` y `auth_forms.js` | Permanecen hasta los cortes S02/S03. |

No se conserva código muerto “por si acaso”; cada pieza compartida mantiene su consumidor
documentado.

## 14. Estrategia de pruebas

### 14.1 PHP

Ampliar `tests/test_api_auth_contract.php` para cubrir:

- CSRF anónimo;
- forma inválida;
- respuesta no enumerable para usuario inexistente, existente con clave mala e inactivo;
- login normal `next=projects`;
- usuario temporal `next=password_change`;
- bootstrap conserva `password_change_required` sin filtrar identidad;
- cambio sin sesión/CSRF;
- las cinco reglas de clave;
- éxito, promoción de sesión y limpieza de contexto;
- cancelación idempotente que no destruye una sesión completa;
- logout T01.

El fixture temporal crea un usuario aleatorio autorizado, conserva credenciales solo en memoria y
lo elimina en `finally`; no usa ni imprime claves humanas.

Añadir contrato de mantenimiento para permitido/denegado sin revelar la ruta ni credenciales en
artefactos.

### 14.2 React

- `PantallaLogin.test.tsx`: S01-UX-01…08, busy, errores y tema.
- `CambioClaveObligatorio.test.tsx`: política, servidor autoridad, cancelación, foco y errores.
- esquemas auth: formas válidas e inconsistencias rechazadas.
- router T01: todos los estados de arranque sin flash.
- guard estático: ningún `fetch` fuera de `cliente.ts`.

### 14.3 Navegador

- formulario y estados React con API controlada; la verificación PHP real usa el contrato HTTP y
  un fixture temporal, nunca una persona ni un navegador tecleando credenciales en `/login`;
- los demás flujos autenticados entran por la puerta de desarrollo según AGENTS.md;
- credencial inválida y no enumeración;
- cambio obligatorio completo y cancelado;
- timeout, inactividad y reset notice;
- ruta oculta en mantenimiento, rol permitido y denegado;
- claro/oscuro × 390/768/1180; 1440 en evidencia visual cuando corresponda;
- teclado, foco, toggle, 200 % zoom, overflow, consola y red.

No se escriben credenciales en logs, screenshots, trazas ni repositorio.

## 15. Criterios de aceptación

- S01-AC-01: `/` y `/login` GET/HEAD sirven React; refresh y deep link funcionan.
- S01-AC-02: VIEW-01 tiene todas sus capacidades S01-UX cubiertas y puede retirarse.
- S01-AC-03: Login válido continúa a S04 sin proyecto y regenera sesión.
- S01-AC-04: Inválido/inactivo no enumera cuentas y nunca muestra detalle técnico.
- S01-AC-05: Timeout, inactividad y reset muestran el aviso correcto una sola vez.
- S01-AC-06: Mostrar/ocultar, Enter, busy y enlace S02 funcionan con teclado.
- S01-AC-07: El cambio obligatorio sucede en React, conserva las cinco reglas y no filtra usuario.
- S01-AC-08: Cancelar exige confirmación, limpia solo sesión pendiente y vuelve al acceso.
- S01-AC-09: La entrada oculta conserva 503 público, secreto, rol A global y bypass controlado.
- S01-AC-10: Todos los endpoints nuevos tienen Zod, CSRF y contrato PHP.
- S01-AC-11: Oscuro es inicial; claro y oscuro pasan en móvil, tablet y desktop.
- S01-AC-12: No existe overflow, error grave de accesibilidad ni request duplicado.
- S01-AC-13: S01 no acepta proyecto/prefijo/rol del cliente ni consulta datos operativos.
- S01-AC-14: Rollback a VIEW-01 se prueba sin cambiar RLS ni datos.
- S01-AC-15: `/admin/`, S02 y S03 permanecen fuera del cambio.

## 16. Preguntas abiertas

No quedan decisiones funcionales abiertas. El host oculto debe volver al mismo acceso React con un
error genérico ante cualquier rechazo; la prueba HTTP debe fijar ese resultado sin debilitar
mantenimiento para simplificar el montaje React.

## 17. Gate siguiente

Felipe aprobó esta spec el 2026-08-30. Se invocó `superpowers:writing-plans` para crear
`docs/superpowers/plans/2026-08-30-s01-login-react.md`, incluyendo únicamente el incremento T01 que
login necesita. El plan requiere revisión propia antes de ejecución; esta aprobación de diseño no
autoriza implementación, commit, publicación ni cambios de datos.
