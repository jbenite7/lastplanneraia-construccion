---
capa: fuente
tipo: spec
estado: vigente
id: S02
fecha: 2026-08-30
superficie: recuperacion-clave
rutas: ["/password/forgot"]
depende_de: [S01, T01]
views: [VIEW-02]
areas: [arquitectura, rbac, design-system]
fuente: "auditoria de PasswordResetController, PasswordResetService, password-forgot.view.php, pruebas y respuestas HTTP reales en shell-minimo-react"
resumen: "Migracion de la solicitud de recuperacion de contraseña a la SPA React, con contrato JSON no enumerativo, error honesto de transporte, ambos temas y rollback sin cambiar correo, tokens, RLS ni datos."
---

# S02 — Recuperar contraseña en React

> **Estado:** diseño técnico autorrevisado. Felipe eligió conservar el formulario después del
> mensaje genérico de éxito. La ejecución corrida S01–S27 autoriza cerrar decisiones técnicas sin
> pausas; no queda una decisión de negocio, producto, estrategia o PM pendiente en S02. Esta spec
> no autoriza implementación, DDL/DML, cambios RLS, deploy ni publicación.

## 1. Resultado buscado

`GET /password/forgot` sirve la SPA principal y muestra una pantalla React para solicitar un enlace
de restablecimiento. React envía únicamente el correo a un endpoint JSON protegido por CSRF; PHP
conserva la elegibilidad, el alcance `app`, el correo, la auditoría y el ciclo de vida de los tokens.

La migración preserva dos propiedades que no pueden colapsarse:

1. Una dirección enviada y una ignorada reciben el mismo mensaje para no enumerar cuentas.
2. Una indisponibilidad real del transporte muestra el aviso técnico honesto aprobado al cerrar
   B-10; no afirma que llegará un correo que no pudo enviarse.

Después de una respuesta genérica exitosa se limpia el campo y el formulario permanece disponible,
por decisión de Felipe. La persona puede corregir un correo escrito por error o solicitar otro
enlace sin salir de la pantalla.

## 2. Autoridad, dependencias y propiedad

Orden aplicable:

1. código, pruebas y respuestas reales del worktree;
2. esta spec para S02;
3. S01 para `MarcoAcceso`, tema, sesión, errores HTTP y login;
4. T01 para host SPA, router, bootstrap, mantenimiento y rollback;
5. spec maestra de las 27 superficies.

| Pieza | Propietario | Relación con S02 |
|---|---|---|
| VIEW-02 `views/auth/password-forgot.view.php` | S02 | Se retira tras el corte y su ventana de rollback. |
| VIEW-03 `views/auth/password-reset.view.php` | S03 | Permanece íntegra. |
| `PasswordResetService` | Auth PHP compartido | S02 solo consume `request(email, 'app')`; S03 conserva consulta y consumo del token. |
| `MarcoAcceso`, `ApiClient`, `ErrorApi`, tema | S01/T01 | S02 los reutiliza; no crea duplicados. |
| `login-brand-unified.css`, `auth_forms.js` | S02/S03 durante convivencia | No se retiran hasta que S03 pierda su último consumidor. |
| `password_reset_tokens` | Persistencia existente | No se cambia schema ni se ejecuta DDL/DML durante diseño o verificación documental. |

`/admin/` comparte hoy `PasswordResetService`, pero está expresamente fuera: S02 no cambia sus rutas,
controladores, vistas, mensajes, permisos ni alcance `admin`.

## 3. Alcance

### 3.1 Incluido

- Ruta piloto `/app/password/forgot` y ruta canónica `/password/forgot`.
- Formulario React con correo, ayuda, busy, avisos y retorno a login.
- Nuevo contrato JSON `POST /api/auth/password/forgot`.
- Esquema Zod de request/response y gateway dentro del cliente común.
- Equivalencia de `enviado`, `ignorado`, `fallido` y excepción técnica.
- CSRF anónimo mediante la clave `shell_api` de S01.
- Tema oscuro inicial y modo claro completo.
- Móvil, tablet, desktop canónico y desktop amplio.
- Teclado, foco, anuncios y validación accesible.
- Pruebas React, PHP contractuales, navegador y corte/rollback.
- Retiro de VIEW-02 y de los métodos legacy exclusivos de solicitud después del gate.

### 3.2 Excluido

- Abrir, validar o consumir el enlace: pertenece a S03 `/password/reset`.
- Cambiar la contraseña o tocar `general_usuarios`.
- Cambiar TTL, formato, hash, invalidación o tabla de tokens.
- Cambiar `SmtpMailer`, plantillas de correo, remitente, transporte o `APP_URL`.
- Añadir rate limiting, CAPTCHA, colas, workers o un proveedor de correo nuevo.
- Cambiar la fuga residual de B-10 durante una caída del transporte.
- RLS, schema, migraciones, grants, usuarios, credenciales o datos.
- `/admin/`.

## 4. Auditoría del comportamiento actual

### 4.1 Rutas y middleware

`public/index.php` registra:

| Método y ruta | Controlador actual | Resultado observable |
|---|---|---|
| `GET /password/forgot` | `PasswordResetController::forgot()` | `200 text/html`, sesión pública, `no-store`, VIEW-02. |
| `HEAD /password/forgot` | FastRoute/Apache | `200 text/html`, sin cuerpo. |
| `POST /password/forgot` | `PasswordResetController::sendLink()` | Siempre vuelve a VIEW-02 en `200 text/html`; éxito/error via mensaje HTML. |

La ruta está en `$publicRoutes`: no exige autenticación, proyecto, rol ni capacidad. También se puede
abrir con una sesión completa o con una sesión temporal de cambio obligatorio; el controlador no
redirige. `SessionMiddleware::beginRequest(false)` puede invalidar una cookie vencida, pero no
bloquea la pantalla.

Cuando mantenimiento está activo, `/password/forgot` no es exenta. La respuesta pública es 503; una
sesión con `maintenance_bypass` puede acceder. S02 conserva ambas ramas.

La comprobación HTTP real del 2026-08-30 confirmó:

- GET y HEAD `200` con `Content-Type: text/html; charset=UTF-8`;
- cookie `HttpOnly`, `SameSite=Lax` y cache deshabilitada;
- un `h1`, formulario, token CSRF, email requerido, copy ocupado y vuelta a `/login`;
- email inválido y CSRF inválido vuelven en `200` HTML, preservan el correo y anuncian error.

### 4.2 Payload legacy

El formulario envía `application/x-www-form-urlencoded`:

| Campo | Regla actual |
|---|---|
| `csrf_token` | Token de sesión bajo `password_forgot`. |
| `email` | `trim`; no vacío; `FILTER_VALIDATE_EMAIL`. |

CSRF inválido muestra «No fue posible validar la solicitud. Intenta nuevamente.» y conserva email.
Formato inválido muestra «Ingresa un correo electrónico válido.» y conserva email. Ambos casos se
detienen antes de `PasswordResetService::request()`.

### 4.3 Elegibilidad y no enumeración

`PasswordResetService::request()`:

1. normaliza con `trim` y minúsculas multibyte cuando está disponible;
2. busca filas por `LOWER(TRIM(email))`;
3. conserva solo usuarios activos;
4. exige un único nombre de usuario canónico entre las filas activas;
5. trata correo inválido, inexistente, inactivo, username vacío o varios usernames como
   `RESULTADO_IGNORADO`;
6. nunca envía `scope` desde el navegador: el controlador fija `app`.

| Resultado interno | Significado | Respuesta visible actual |
|---|---|---|
| `RESULTADO_ENVIADO` | Usuario elegible y transporte aceptó el mensaje. | Mensaje genérico de éxito. |
| `RESULTADO_IGNORADO` | No existe un destinatario elegible. | El mismo mensaje genérico. |
| `RESULTADO_FALLIDO` | Había destinatario, pero el transporte falló. | Aviso técnico rojo y correo preservado. |

Mensaje genérico contractual:

> Si el correo existe y está habilitado, enviaremos un enlace de restablecimiento en unos minutos.

Mensaje técnico contractual:

> No pudimos enviar el correo en este momento por un problema técnico. Vuelve a intentarlo en unos
> minutos; si sigue fallando, avisa al administrador.

La distinción `enviado`/`ignorado` nunca puede cruzar la frontera JSON. La fuga residual durante una
caída —registrado puede recibir fallo técnico e inexistente mensaje genérico— es una decisión previa
documentada en B-10 y no se reabre en una migración de presentación.

### 4.4 Correo y tokens

Para un usuario elegible, el servicio existente:

- genera 32 bytes aleatorios y solo persiste `sha256`;
- fija expiración a 3600 segundos;
- construye URL con `APP_URL` y `/password/reset?token=...` antes de insertar;
- invalida tokens vivos previos del mismo username y scope;
- inserta `user_id`, `scope`, hash, IP y expiración;
- envía HTML y texto mediante `SmtpMailer`;
- elimina el token recién creado si el transporte lanza;
- audita solicitud ignorada, envío, fallo y finalización.

S02 no cambia ninguna de estas operaciones. S03 es quien valida `used_at`, expiración y consumo.

### 4.5 VIEW-02 observable

| ID | Comportamiento legacy que debe sobrevivir |
|---|---|
| S02-UX-01 | Marca «Last Planner AIA», título «Restablecer contraseña» y explicación de enlace seguro. |
| S02-UX-02 | Label «Correo electrónico», placeholder `nombre@empresa.com` y `autocomplete=email`. |
| S02-UX-03 | Campo obligatorio y validación de formato antes y después del transporte. |
| S02-UX-04 | Botón «ENVIAR ENLACE» cambia a «Enviando…» y bloquea doble submit. |
| S02-UX-05 | Email y cuenta inexistente/inactiva no se distinguen en éxito. |
| S02-UX-06 | Error CSRF conserva el correo y permite nuevo intento explícito. |
| S02-UX-07 | Error de formato conserva el correo y se asocia al campo. |
| S02-UX-08 | Fallo de transporte conserva el correo y muestra aviso técnico honesto. |
| S02-UX-09 | Éxito limpia correo, muestra aviso y mantiene el formulario. |
| S02-UX-10 | Enlace «Volver al inicio de sesión» lleva a `/login`. |
| S02-UX-11 | Footer AIA y +CERTEZA permanecen dentro del marco compartido. |
| S02-UX-12 | La ruta abre aun con sesión completa o cambio obligatorio pendiente. |

### 4.6 Responsive, tema y accesibilidad actuales

La evidencia versionada `golden-2026-08-06` cubre 1180×820 y 1440×900 en dark, sin violaciones Axe,
sin errores de consola y sin overflow. `login-brand-unified.css` limita la tarjeta a 25rem, usa
clamp/container query, targets mínimos y foco visible. No existe evidencia dedicada 390/768 ni un
conmutador claro en VIEW-02; ambos son requisitos nuevos ya aprobados para la SPA.

El mensaje legacy usa `role=alert aria-live=assertive` incluso para éxito. React conserva alert
assertive para error y usa `role=status aria-live=polite` para éxito para evitar interrupción
innecesaria sin perder feedback.

### 4.7 Estado React y cobertura actual

React solo contiene un enlace desde S01 a `/password/forgot`; no existe componente, ruta, gateway ni
esquema S02. `react-router-dom` ya es dependencia, pero el shell actual todavía no lo consume.

Cobertura existente:

- `test_password_reset_resultados.php` caracteriza los tres resultados y restauración de tokens;
- `test_login_design_system_contract.mjs` fija fuentes/assets legacy compartidos;
- `design-system-consumer-smoke.mjs` verifica la ruta y head segmentado;
- evidencia visual dark 1180/1440;
- no existe E2E dedicado ni contrato JSON de recuperación.

El test histórico de resultados escribe tokens y los reconcilia. La ejecución documental y los
nuevos contratos S02 no lo corren porque el alcance prohíbe DML; los resultados JSON se prueban con
servicio y lector de body inyectados.

## 5. Decisiones de diseño

### S02-D01 — Una ruta de la SPA principal

Se usa `BrowserRouter` dentro de `frontend/`; no se crea una isla React ni una vista PHP que monte un
segundo root. Durante piloto existen `/app/password/forgot` y la ruta canónica. Tras el corte se
retira el alias piloto cuando ya no tenga consumidores.

### S02-D02 — API JSON dedicada

`POST /api/auth/password/forgot` vive en `PasswordRecoveryApiController`. Un controlador pequeño
mantiene la solicitud pública separada de login/logout/cambio obligatorio y deja inyección explícita
para contratos sin DB.

### S02-D03 — Resultado del servicio, no estado de cuenta

El servidor mapea `enviado` e `ignorado` al mismo `200`. El body no incluye `delivery`, `eligible`,
`user`, username, token, scope, ID ni timing interno.

### S02-D04 — Fallo técnico distinguible

`fallido` o una excepción controlada del servicio se mapea a `503 recovery_unavailable`. No se
inserta el mensaje de excepción. Esta decisión conserva B-10 y permite reintento manual.

### S02-D05 — El formulario permanece

Después de `200`, React limpia el valor, mantiene la misma pantalla y deja el botón habilitado. No
redirige, no inicia una cuenta regresiva y no asume que la persona recibió el mensaje.

### S02-D06 — La ruta pública prevalece sobre el estado de sesión

El router reconoce `/password/forgot` antes de decidir login/selector/shell. Una sesión autenticada o
pendiente puede mostrar S02, igual que PHP. El bootstrap solo aporta CSRF y tema; S02 no muestra
identidad ni datos operativos.

### S02-D07 — CSRF común, sin mezcla durante rollback

React usa `X-CSRF-Token` con `shell_api`. El POST legacy conserva `csrf_token` con
`password_forgot`. Ambos contratos coexisten sin aceptar el token del otro.

### S02-D08 — Sin retry automático

Una mutación nunca se reenvía por red, 403, 503, remount o `StrictMode`. Ante 403 se ofrece
«Actualizar sesión»; al obtener un CSRF nuevo la persona vuelve a pulsar «Enviar enlace».

### S02-D09 — Marco y temas compartidos

S02 reutiliza `MarcoAcceso`, `ConmutadorTema` y `auth-react.css` de S01. Oscuro es inicial; claro y
oscuro tienen idénticos campos, avisos y acciones.

### S02-D10 — Sin ampliación de seguridad o persistencia

No se añade throttling, CAPTCHA, tabla, columna ni cambio de auditoría. Son frentes de seguridad con
impacto de producto/operación propios, no requisitos para portar VIEW-02.

## 6. Arquitectura destino

```text
BrowserRouter
  └─ Ruta pública /password/forgot
      └─ Bootstrap de sesión existente
          └─ MarcoAcceso
              └─ PantallaRecuperarClave
                  └─ solicitarRecuperacion(email, csrf)
                      └─ ApiClient.pedir + Zod
                          └─ POST /api/auth/password/forgot
                              └─ PasswordRecoveryApiController
                                  └─ PasswordResetService::request(email, 'app')
                                      ├─ enviado  -> 200 genérico
                                      ├─ ignorado -> 200 genérico
                                      └─ fallido  -> 503 técnico
```

Unidades:

| Unidad | Hace | Depende de |
|---|---|---|
| `PantallaRecuperarClave` | Estado de formulario, foco y feedback. | Props/acción tipada; no HTTP. |
| `auth.ts` | Serializa request y valida respuesta. | `cliente.ts`, esquemas Zod. |
| `PasswordRecoveryApiController` | CSRF, forma, status y mapping. | Servicio existente inyectable. |
| `PasswordResetService` | Elegibilidad, token, correo y auditoría. | Código actual sin cambios funcionales. |
| `SpaRouter` | Piloto, corte y rollback GET/HEAD. | Mapa exacto de rutas. |

## 7. Contrato HTTP objetivo

### S02-API-01 — `POST /api/auth/password/forgot`

Headers:

```http
Accept: application/json
Content-Type: application/json
X-CSRF-Token: <shell_api>
```

Request único:

```json
{"email":"persona@empresa.com"}
```

El controlador solo lee `email`; fija `scope='app'`. Campos como `scope`, `username`, `project_id`,
`project`, `db`, prefijo o rol no cambian el alcance y se rechazan como forma inválida si aparecen.

#### 200 — enviado o ignorado

```json
{
  "success": true,
  "message": "Si el correo existe y está habilitado, enviaremos un enlace de restablecimiento en unos minutos."
}
```

#### 422 — email ausente, no string o inválido

```json
{
  "success": false,
  "code": "validation_error",
  "message": "Revisa el correo electrónico.",
  "fieldErrors": {
    "email": ["Ingresa un correo electrónico válido."]
  }
}
```

#### 403 — CSRF ausente o inválido

```json
{
  "success": false,
  "code": "csrf_invalid",
  "message": "No fue posible validar la solicitud. Intenta nuevamente."
}
```

#### 503 — transporte o servicio indisponible

```json
{
  "success": false,
  "code": "recovery_unavailable",
  "message": "No pudimos enviar el correo en este momento por un problema técnico. Vuelve a intentarlo en unos minutos; si sigue fallando, avisa al administrador."
}
```

Todas las respuestas llevan `Content-Type: application/json; charset=utf-8` y
`Cache-Control: no-store, no-cache, must-revalidate, max-age=0`. JSON mal formado produce el mismo
422 controlado. Un método distinto de POST lo resuelve `Router` como 405 JSON porque la ruta vive
bajo `/api/`; nunca devuelve VIEW-02 ni intenta el servicio.

## 8. Contratos frontend

Los esquemas viven con auth y producen tipos mediante `z.infer`:

```ts
const EsquemaSolicitudRecuperacion = z.object({
  email: z.string().trim().email(),
}).strict();

const EsquemaRecuperacionAceptada = z.object({
  success: z.literal(true),
  message: z.string().min(1),
}).strict();
```

`solicitarRecuperacion(email, csrfToken)` llama `pedir()` y devuelve solo la respuesta aceptada. Los
errores 403/422/503 llegan como `ErrorApi`; el gateway no los convierte en `success:false` local.

No hay `fetch`, rutas literales ni lectura de cookies dentro de componentes.

## 9. Estados de interfaz

| Estado | Presentación | Foco/acción |
|---|---|---|
| Cargando bootstrap | Estado global T01, sin flash de formulario incompleto. | Sin acción. |
| Inicial | Campo vacío y botón «Enviar enlace». | Foco en email al entrar desde login. |
| Inválido cliente | Mensaje asociado al email; no hay request. | Email. |
| Enviando | `aria-busy=true`, campo/botón deshabilitados, copy «Enviando…». | Conserva contexto. |
| 200 genérico | Aviso `role=status`, email vacío, formulario habilitado. | Aviso anunciado; siguiente Tab vuelve al campo. |
| 422 | Error inline y resumen breve, email preservado. | Email. |
| 403 | Alert y acción «Actualizar sesión», sin reenvío. | Acción de actualización. |
| 503 | Alert técnico, email preservado, formulario habilitado. | Alert; reintento manual disponible. |
| Red/contrato | Alert genérico recuperable, email preservado. | Alert; reintento manual. |

No se pinta «correo enviado», porque `200` también cubre solicitudes ignoradas.

## 10. Validación

- Campo `type=email`, `required`, `autocomplete=email`, `inputMode=email`, `autoCapitalize=none` y
  `spellCheck=false`.
- React recorta extremos para validar/enviar; no altera caracteres internos ni muestra el correo en
  la URL.
- La validación de cliente mejora feedback, pero PHP repite toda regla.
- `aria-invalid=true` y `aria-describedby` apuntan al error del campo.
- Un Enter válido equivale a un click y genera una sola mutación.
- Cambiar el valor limpia el error de campo, no un error de sistema hasta el próximo submit.

## 11. Seguridad, permisos y RLS

- S02 es pública y no tiene capacidad RBAC propia.
- No requiere ni consulta proyecto, membresía, semana o datos operativos.
- El servidor fija `app`; un scope cliente no puede alcanzar `/admin/`.
- CSRF se valida antes de email y servicio.
- `enviado`/`ignorado` tienen status, body y copy idénticos.
- El token plano solo existe en el servicio/correo de S03; nunca cruza S02 React.
- No se muestran excepciones, SQL, SMTP host, destinatario, correlation sensible ni stack.
- No hay credenciales ni direcciones reales en fixtures, snapshots o documentación.
- Mantenimiento sigue cerrando la ruta y la API salvo `maintenance_bypass` ya autorizado.
- RLS, grants, schema y datos quedan sin cambios; no se introduce bypass de `ProjectScope` porque
  S02 no toca tablas operativas.

## 12. Presentación, responsive y temas

S02 usa tokens de `public/css/tokens.css` a través de `auth-react.css`; no añade hex, estilos inline,
`!important` ni una hoja exclusiva si el marco ya cubre la composición.

| Viewport | Contrato |
|---|---|
| 390×844 | Una columna, tarjeta/panel dentro del ancho, teclado no oculta acción, cero overflow. |
| 768×1024 | Marco tablet, campo y acción completos, targets táctiles. |
| 1180×820 | Gate principal dark; composición S01, formulario visible sin scroll innecesario. |
| 1440×900 | Canvas limitado, sin estirar el formulario más de su ancho legible. |

Claro y oscuro muestran los mismos estados. El conmutador es accesible antes de autenticar y la
preferencia persiste. Los avisos usan tokens semánticos; no dependen solo de color.

## 13. Accesibilidad

- Un `main` y un `h1` «Restablecer contraseña».
- Label visible; placeholder no reemplaza label.
- Orden: tema, email, enviar, volver a login, según el marco aprobado de S01.
- Foco visible, targets de al menos 44px, zoom 200 % y reduced motion.
- Busy anunciado sin cambiar título del botón fuera de la mutación.
- Éxito `role=status aria-live=polite`; error `role=alert`.
- No existe focus trap: la pantalla no es modal.
- Axe sin `critical` o `serious`, más revisión manual de teclado y lector.

## 14. Manejo de errores

| Causa | Comportamiento |
|---|---|
| Email inválido | 422 de campo; nunca llama servicio. |
| CSRF inválido | 403; actualizar bootstrap manualmente; no repetir payload. |
| `RESULTADO_FALLIDO` | 503 técnico contractual; conserva email. |
| Excepción de config/DB/servicio | Log servidor sin payload; 503 genérico idéntico al fallo técnico. |
| Red | Mensaje «No pudimos conectar. Intenta nuevamente.»; conserva email. |
| HTML/JSON inválido | `ErrorApi.kind=unexpected_response/contract`; mensaje seguro. |
| Sesión expirada | S02 sigue siendo pública; refrescar bootstrap obtiene CSRF anónimo nuevo. |
| Mantenimiento sin bypass | 503 de mantenimiento antes del router, sin formulario. |

## 15. Estrategia de pruebas

### 15.1 Zod y gateway

- Request válido, trim y rechazo de propiedades extra.
- 200 exacto; rechazo de un outcome interno filtrado.
- 422/403/503 tipados mediante `ErrorApi`.
- Cabecera CSRF, JSON y una llamada única.
- Guard global: no `fetch` fuera de `cliente.ts`.

### 15.2 Componentes y router

- S02-UX-01…12.
- Sesión anónima, autenticada y pendiente muestran S02 en su ruta.
- Piloto y canónica resuelven el mismo componente.
- Validación, Enter/click único, busy, éxito, errores y foco.
- Tema claro/oscuro y retorno a login.
- `StrictMode` no duplica mutación.

### 15.3 PHP contractual sin DML

`PasswordRecoveryApiController` acepta por constructor un `PasswordResetService` y un lector de body
con defaults productivos. El contrato inyecta un fake que devuelve `enviado`, `ignorado`, `fallido`
o lanza; captura status/body/headers sin consultar ni escribir DB. Genera CSRF solo en sesión
efímera y prueba:

- 200 idéntico para enviado/ignorado;
- 503 idéntico para fallido/excepción;
- 422 antes del fake para forma/email;
- 403 antes del fake para CSRF;
- scope `app` fijo y rechazo de campos de autoridad.

Una prueba HTTP de routing usa solo CSRF inválido y email inválido, caminos que no llaman al servicio.
No se ejecuta `test_password_reset_resultados.php` en este frente por su DML histórico.

### 15.4 Navegador y visual

- `/app/password/forgot` y `/password/forgot`: deep link, refresh, back/forward.
- API interceptada para 200, 422, 403, 503, red y contrato roto; ningún correo real.
- 390/768/1180/1440, dark/light, teclado, zoom, overflow, Axe y consola.
- Candidatos visuales no reemplazan goldens sin aprobación explícita.

### 15.5 Corte y rollback

- `SpaRouter` sirve solo GET/HEAD canónicos durante el corte.
- POST legacy permanece durante ventana de rollback.
- Retirar la ruta del mapa devuelve GET a VIEW-02 sin tocar datos.
- S03 `/password/reset` sigue 200 y conserva assets compartidos.
- `/admin/` no aparece en diff ni en cobertura de cierre.

## 16. Rollout, rollback y retiro

1. Construir S02 y probar `/app/password/forgot` con API controlada.
2. Registrar API pública JSON y verificar contrato sin DML.
3. Añadir únicamente GET/HEAD `/password/forgot` al mapa exacto de SPA.
4. Mantener POST `/password/forgot` y VIEW-02 durante la ventana de rollback.
5. Verificar ruta canónica, mantenimiento, ambos temas, cuatro viewports y S03 intacta.
6. Tras el gate post-rollout, retirar `PasswordResetController::forgot()` y `sendLink()`, el POST
   legacy y VIEW-02.
7. Conservar `PasswordResetController::reset()/update()`, VIEW-03, `login-brand-unified.css` y
   `auth_forms.js` hasta S03.

Rollback: retirar la ruta exacta del mapa SPA; GET vuelve a PHP y POST legacy sigue disponible. No
se revierten tokens ni datos porque React y PHP consumen el mismo servicio.

## 17. Criterios de aceptación

- S02-AC-01: `/app/password/forgot` y GET/HEAD canónico sirven React con refresh y deep link.
- S02-AC-02: S02-UX-01…12 están cubiertos y VIEW-02 no se retira antes del gate.
- S02-AC-03: El endpoint solo acepta `{email}` y fija `scope='app'` server-side.
- S02-AC-04: CSRF usa `shell_api`; ausencia/invalidez responde 403 controlado.
- S02-AC-05: Email inválido responde 422, conserva valor y enfoca el campo.
- S02-AC-06: `enviado` e `ignorado` producen exactamente el mismo 200/body/copy.
- S02-AC-07: `fallido` y excepción producen 503 seguro; nunca afirman envío.
- S02-AC-08: Éxito limpia el campo, conserva el formulario y no redirige.
- S02-AC-09: No hay retry automático ni doble mutación con Enter, click o StrictMode.
- S02-AC-10: S02 abre con sesión anónima, autenticada o pendiente sin exponer identidad.
- S02-AC-11: Oscuro inicial y claro completo pasan en 390, 768, 1180 y 1440.
- S02-AC-12: Teclado, foco, anuncios, zoom y Axe no presentan bloqueos graves.
- S02-AC-13: No existe `fetch` fuera de `cliente.ts`; request/response tienen Zod.
- S02-AC-14: Contrato PHP cubre todos los outcomes sin DML ni correo real.
- S02-AC-15: Mantenimiento, S03, `/admin/`, RLS, schema, grants, usuarios, credenciales y datos quedan intactos.
- S02-AC-16: Rollback a VIEW-02 está probado antes de retirar el POST legacy.

## 18. Riesgos y mitigaciones

| Riesgo | Mitigación |
|---|---|
| Filtrar `enviado` frente a `ignorado` en JSON | Un único schema 200 y contrato de igualdad exacta. |
| Doble correo por retry/remount | Mutación solo por submit explícito, busy y pruebas StrictMode. |
| Romper S03 al retirar assets | Lista de consumidores y smoke `/password/reset` en cada gate. |
| API pública sin CSRF | Token anónimo `shell_api`, validado antes del payload/servicio. |
| Probar con SMTP o cuentas reales | Fakes inyectados y API interceptada; cero correo y cero DML. |
| `password_reset_tokens` no está en fixture CI | S02 no toca schema; contrato nuevo no depende de esa tabla. |
| BrowserRouter cambia precedencia de sesión | Pruebas de anónimo/autenticado/pendiente sobre la ruta pública. |
| Mantenimiento abre recuperación por accidente | Contrato confirma 503 sin bypass y no exime la ruta/API. |

## 19. Decisiones pendientes

Ninguna. Rate limiting, CAPTCHA, colas o cambios de proveedor serían decisiones nuevas de producto y
seguridad; no son necesarias para migrar S02 y no se incorporan silenciosamente.

## 20. Gate siguiente

La spec fue auditada y autorrevisada sin decisiones funcionales pendientes. Se invocó
`superpowers:writing-plans` para crear
`docs/superpowers/plans/2026-08-30-s02-recuperar-clave-react.md`; consume solo el incremento T01 de
BrowserRouter/corte que S02 necesita, asume S01 ejecutado y mantiene toda verificación sin DML. Esta
documentación no autoriza implementación, commit, publicación ni cambios de datos.
