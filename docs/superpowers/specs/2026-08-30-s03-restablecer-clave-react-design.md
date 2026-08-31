---
capa: fuente
tipo: spec
estado: autorrevisado
id: S03
fecha: 2026-08-30
superficie: restablecer-clave
rutas: ["/password/reset"]
depende_de: [S01, S02, T01]
views: [VIEW-03]
areas: [frontend, react, autenticacion, contrasena, csrf, seguridad, tema, accesibilidad]
fuente: "auditoria de PasswordResetController, PasswordResetService, UserPasswordService, PasswordPolicyService, password-reset.view.php, auth_forms.js, rutas, pruebas y respuestas HTTP no mutantes en shell-minimo-react"
resumen: "Migracion del enlace de restablecimiento a React, conservando token de un solo uso, cinco reglas de contraseña, redireccion segura, ambos temas y rollback sin ejecutar DML ni modificar RLS."
---

# S03 — Restablecer contraseña en React

> **Estado:** diseño técnico autorrevisado, sin decisiones de negocio, producto, estrategia o PM
> pendientes. Esta spec no autoriza implementación, cambios de contraseña, DDL/DML, RLS, deploy,
> publicación ni trabajo dentro de `/admin/`.

## 1. Resultado buscado

`GET /password/reset?token=...` sirve la SPA principal y muestra una pantalla React que valida el
enlace, permite definir y confirmar una nueva contraseña y, después del cambio, reemplaza la entrada
actual del historial por `/login?reset=1`.

La migración conserva el comportamiento de seguridad del backend actual:

- el token es opaco, dura 3600 segundos, se guarda por hash y solo sirve una vez;
- el alcance `app` lo fija PHP, nunca el navegador;
- un enlace ausente, mal formado, vencido, usado o asociado a una cuenta inactiva produce el mismo
  estado inválido y no expone identidad;
- la contraseña debe tener al menos seis caracteres, una mayúscula y un carácter especial, debe
  coincidir con su confirmación y no puede ser igual a la anterior;
- un éxito invalida los tokens pendientes del mismo usuario y limpia `force_password_change` por
  medio del servicio existente;
- el redirect final conserva el aviso S01 «Tu contraseña fue restablecida correctamente. Ya puedes
  iniciar sesión.».

La UI cambia de tecnología y puede cambiar de composición, pero no cambia política, identidad,
persistencia, correo, token, sesión ni reglas de negocio.

## 2. Alcance y propiedad

S03 posee:

- VIEW-03 `views/auth/password-reset.view.php`;
- GET/HEAD canónico `/password/reset` y piloto `/app/password/reset`;
- validación visual del enlace;
- campos nueva contraseña/confirmación, toggles y política visible;
- submit, validaciones, estados terminales y retorno a S01/S02;
- adaptación JSON del servicio de reset existente;
- retiro gateado de VIEW-03 y de sus rutas PHP legacy.

S03 consume, sin redefinirlos:

- `MarcoAcceso`, `CampoClave`, tema, router y cliente HTTP de S01/T01;
- `/api/session`, su CSRF anónimo `shell_api` y la pantalla `/login?reset=1` de S01;
- `/password/forgot` React de S02 para solicitar un enlace nuevo;
- `PasswordResetService`, `UserPasswordService` y `PasswordPolicyService` como autoridad PHP.

Quedan fuera:

- toda ruta, vista, servicio o copy de `/admin/`;
- cambios de TTL, hashing, generación, almacenamiento, correo o invalidación de tokens;
- cambios en la política de contraseña;
- rate limiting, CAPTCHA, MFA, magic links o sesiones automáticas después del reset;
- RLS, schema, migraciones, grants, usuarios, credenciales y datos;
- ejecución de un reset real durante la redacción o verificación documental.

## 3. Auditoría del estado actual

### 3.1 Rutas, middleware y permisos

`public/index.php` registra:

| Método | Ruta | Handler legacy |
|---|---|---|
| GET | `/password/reset` | `PasswordResetController::reset()` |
| POST | `/password/reset` | `PasswordResetController::update()` |

La ruta está en `$publicRoutes`. No exige autenticación, proyecto, rol, capacidad ni sidebar. El
middleware de sesión puede inicializar cookie/CSRF, pero no autoriza el reset. El bearer token y el
scope servidor son la frontera de acceso.

Mantenimiento se evalúa antes del router y no exime esta ruta: sin bypass responde el 503 estático.
La ruta oculta de mantenimiento no cambia esta regla.

No hay `project_id`, prefijo, tabla operativa ni decisión RLS. Las tablas de autenticación son
globales y quedan fuera del runtime RLS de módulos por proyecto. S03 no añade una frontera de datos
ni modifica la existente.

### 3.2 GET y estado del enlace

`PasswordResetController::reset()`:

1. toma `token` del query string y hace `trim`;
2. llama `PasswordResetService::findValidToken($token, 'app')`;
3. si no encuentra fila, renderiza el aviso exacto «El enlace no es válido o ya expiró. Solicita
   uno nuevo.» y oculta el formulario;
4. si encuentra fila activa, renderiza el formulario;
5. nunca muestra usuario, nombre, email, id, expiración ni scope.

`findValidToken()` devuelve `null` para token vacío, hash inexistente, scope distinto, `used_at` no
nulo, expiración anterior a `NOW()`, usuario inactivo o username vacío. Para un token no vacío solo
hace SELECT por `sha256`; no escribe.

### 3.3 POST y mutación

Payload legacy `application/x-www-form-urlencoded`:

```text
csrf_token=<password_reset>
token=<bearer>
password=<secreto>
confirm_password=<secreto>
```

`PasswordResetController::update()`:

1. rechaza una invocación no POST redirigiendo a `/password/forgot`;
2. valida CSRF con la clave legacy `password_reset`;
3. ante CSRF inválido vuelve a consultar el token y renderiza «No fue posible validar la solicitud.
   Intenta nuevamente.»;
4. llama `PasswordResetService::reset(token, 'app', password, confirm_password)`;
5. si `success=true`, redirige a `/login?reset=1`;
6. si falla, vuelve a comprobar el token y renderiza el mensaje del servicio.

`PasswordResetService::reset()` vuelve a validar el token, delega el cambio a
`UserPasswordService::changePasswordForUsername(..., true)`, invalida todos los tokens `app` del
usuario y audita el éxito. No crea sesión autenticada.

### 3.4 Política y mensajes observables

`PasswordPolicyService` aplica en este orden:

| Orden | Regla | Mensaje exacto |
|---|---|---|
| 1 | No vacío y mínimo 6 bytes UTF-8 | `La contraseña debe tener al menos 6 caracteres` |
| 2 | Al menos una mayúscula ASCII | `Debe contener al menos una letra mayúscula` |
| 3 | Al menos un carácter no alfanumérico ASCII | `Debe contener al menos un carácter especial (!@#$%...)` |
| 4 | Coincide con confirmación | `Las contraseñas no coinciden` |
| 5 | No equivale al hash actual | `La nueva contraseña no puede ser igual a la anterior` |

Otros resultados actuales:

- token inválido: `El enlace no es válido o ya expiró. Solicita uno nuevo.`;
- usuario desaparecido durante la operación: `Usuario no encontrado`;
- error de persistencia: `Error al actualizar la contraseña.`;
- éxito interno: `Contraseña restablecida correctamente.`;
- aviso visible en S01 tras redirect: `Tu contraseña fue restablecida correctamente. Ya puedes
  iniciar sesión.`.

La política PHP sigue siendo autoridad aunque React replique cuatro comprobaciones para feedback
inmediato. Solo el servidor puede comprobar reutilización de la contraseña anterior.

### 3.5 UI legacy y comportamiento del navegador

VIEW-03 presenta:

- marca, título «Define tu nueva contraseña» y política resumida;
- alert `aria-live=assertive`;
- formulario solo cuando el token es válido;
- labels visibles para nueva contraseña y confirmación;
- `autocomplete=new-password`, `minlength=6`, `pattern` de mayúscula/especial y `required`;
- ayuda `password-policy` asociada al primer campo;
- dos toggles independientes con `aria-label`, `aria-pressed` e icono;
- botón `ACTUALIZAR CONTRASEÑA` con loading `Actualizando…`;
- enlace S02 cuando el token es inválido y enlace S01 siempre;
- footer AIA.

`auth_forms.js` sincroniza toggles, devuelve foco al campo, marca el formulario `aria-busy`, evita un
segundo submit y deshabilita el botón. No valida coincidencia ni política con JS propio; eso queda en
HTML/PHP.

`login-brand-unified.css` usa tokens, foco visible, targets táctiles, reduced motion, ancho máximo de
25 rem, scroll vertical y container query. El script actual fuerza solo dark; el programa aprobado
exige que React entregue dark inicial y light completo.

### 3.6 Respuestas HTTP comprobadas sin mutación

El 2026-08-30, contra el contenedor que monta este worktree:

| Solicitud segura | Resultado observado |
|---|---|
| GET sin token | 200 HTML; título, aviso inválido y enlaces S02/S01. |
| GET con token sintético inexistente | 200 HTML; mismo aviso inválido, sin identidad. |
| HEAD sin token | 200 `text/html`. |
| POST con token sintético y sin CSRF | 200 HTML; aviso CSRF y estado de enlace inválido. |

El POST observado termina antes de `reset()` y solo vuelve a consultar un hash inexistente. No se
ejecutó DML, SMTP, cambio de usuario ni prueba con token real.

### 3.7 Cobertura y brechas

- `tests/test_login_design_system_contract.mjs` fija campos, toggles, política y assets compartidos.
- `design-system-consumer-smoke.mjs` solo comprueba carga de entrypoint.
- `tests/test_password_reset_resultados.php` caracteriza solicitud/correo, no el reset; además
  escribe y reconcilia tokens, por lo que S03 no lo ejecuta.
- No existe E2E dedicado para token válido, política, mutación, expiración o redirect.
- `docs/qa/workflows.md` declara el flujo y reconoce el riesgo de mutar contraseña.
- No hay componente, ruta, esquema o gateway S03 en `frontend/src`.

## 4. Alternativas evaluadas

### A. SPA + contratos JSON separados — elegida

React valida el enlace mediante un endpoint de lectura en POST y aplica el cambio mediante otro POST.
El token viaja en body, no en una segunda URL de API; PHP conserva toda autoridad. Permite Zod,
errores tipados, no-retry y pruebas con servicio fake sin tocar datos.

### B. Isla React dentro de VIEW-03

Reduciría el corte, pero conservaría PHP como dueño del documento, duplicaría tema/router y dejaría
la superficie fuera de la SPA única. Se descarta.

### C. React que publica el formulario legacy

Mantendría el POST actual, pero una respuesta HTML desmontaría la SPA, impediría errores tipados y
dejaría la migración incompleta. Solo se conserva temporalmente para rollback.

## 5. Arquitectura objetivo

Flujo nominal:

```text
/password/reset?token=... → host SPA → Rutas públicas S03
  → POST /api/auth/password/reset/validate {token}
  → valid → formulario React
  → POST /api/auth/password/reset {token,password,confirmPassword}
  → PasswordResetService::reset(..., 'app', ...)
  → 200 → navigate('/login?reset=1', {replace:true})
```

Responsabilidades:

- React: query string, presentación, comprobaciones inmediatas, toggles, busy, foco y navegación.
- `frontend/src/lib/api/auth.ts`: únicas llamadas S03 al cliente común.
- Zod: request local y responses de éxito/estado.
- controlador API: CSRF, JSON estricto, token sintáctico, mapping de resultados y headers.
- `PasswordResetService`: validez real, política vía servicios existentes, mutación e invalidación.
- `SpaRouter`: piloto y luego GET/HEAD exacto canónico; jamás captura POST legacy durante rollback.

S03 no guarda token o contraseñas en un store global. El token vive en el query string original y en
estado local efímero; las contraseñas viven solo en inputs controlados durante el formulario.

## 6. Contratos JSON

### 6.1 Validar enlace

```http
POST /api/auth/password/reset/validate
Content-Type: application/json
X-CSRF-Token: <shell_api>
Cache-Control: no-store

{"token":"<64 hex lowercase>"}
```

Respuesta válida:

```json
{"success":true,"state":"valid"}
```

Respuesta inválida esperada, siempre 200:

```json
{
  "success": true,
  "state": "invalid",
  "message": "El enlace no es válido o ya expiró. Solicita uno nuevo."
}
```

Reglas:

- body debe ser objeto exacto `{token}`; JSON roto, lista, campo ausente/no string o extra → 422
  `validation_error`;
- un string vacío o que no sea exactamente 64 hex minúsculos produce estado `invalid` sin consultar
  el servicio;
- CSRF ausente/inválido se evalúa primero y responde 403 `csrf_invalid`;
- token bien formado llama una vez `findValidToken(token, 'app')`;
- una excepción de lectura responde 503 `reset_unavailable` con «No pudimos validar el enlace en
  este momento. Intenta nuevamente.»;
- nunca devuelve usuario, email, id, scope, expiración, hash ni razón de invalidez.

### 6.2 Aplicar cambio

```http
POST /api/auth/password/reset
Content-Type: application/json
X-CSRF-Token: <shell_api>
Cache-Control: no-store

{
  "token":"<64 hex lowercase>",
  "password":"<secreto>",
  "confirmPassword":"<secreto>"
}
```

Éxito:

```json
{
  "success": true,
  "message": "Contraseña restablecida correctamente.",
  "redirect": "/login?reset=1"
}
```

Errores:

| HTTP/code | Condición | Shape pública |
|---|---|---|
| 403 `csrf_invalid` | CSRF ausente/inválido | `success:false`, mensaje seguro. |
| 422 `validation_error` | body/clave/confirmación inválidos | `fieldErrors.password` o `fieldErrors.confirmPassword`. |
| 410 `reset_link_invalid` | token mal formado, vencido, usado, inactivo o usuario desaparecido | mensaje inválido único. |
| 503 `reset_unavailable` | fallo de persistencia, excepción o resultado desconocido | `Error al actualizar la contraseña.` sin detalle. |

El body es estricto. PHP rechaza `scope`, username, user id, email, rol, proyecto, `project_id`, DB o
prefijo. `scope='app'` es literal servidor.

El mapping 422 conserva los mensajes de `PasswordPolicyService`: mismatch apunta a
`confirmPassword`; longitud, mayúscula, especial y reutilización apuntan a `password`. El cliente no
inventa una sexta regla.

## 7. Privacidad y manejo del bearer token

- La URL original conserva `?token=` para que refresh y deep link funcionen hasta completar el
  proceso; no se copia a la URL de API.
- El host canónico emite `Referrer-Policy: no-referrer` y `Cache-Control: no-store`.
- Ambos endpoints emiten JSON UTF-8 y `Cache-Control: no-store, no-cache, must-revalidate, max-age=0`.
- React nunca pinta el token, lo pone en un input hidden, `data-*`, log, error, analytics, screenshot,
  nombre de archivo o mensaje.
- `ErrorApi.endpoint` recibe rutas constantes sin query token.
- No hay retry automático de validación o mutación. La única repetición ocurre después de una acción
  humana explícita.
- Al éxito, navegación `replace` elimina el token de la entrada actual del historial.
- Al desmontar, invalidar enlace o recibir cualquier respuesta de mutación, se vacían password y
  confirmación y ambos vuelven a `type=password`.
- Pruebas de navegador usan un token sintético fijo; nunca capturan token real, correo o contraseña
  humana.

## 8. Modelo de estados React

| Estado | UI y acciones |
|---|---|
| `bootstrapping` | Marco S01 y estado de carga sin flash de formulario/login. |
| `validating` | «Validando enlace…»; sin campos ni submit. |
| `valid` | Formulario completo, política, toggles, S01 link. |
| `invalid` | Alert exacto, enlace «Solicitar un nuevo enlace» a S02 y retorno S01; sin formulario. |
| `validation_error` | Error del primer campo; foco correspondiente; valores locales conservados. |
| `submitting` | Form `aria-busy`; campos/toggles/botón disabled; texto «Actualizando…». |
| `csrf_invalid_validate` | Alert y acción «Actualizar sesión»; tras click revalida sesión y enlace. |
| `csrf_invalid_update` | Secretos limpios; acción «Actualizar sesión»; nunca reenvía el cambio. |
| `link_became_invalid` | Secretos limpios; cambia al estado `invalid`. |
| `unavailable` | Secretos limpios; alert con 503; reintento solo llenando y enviando otra vez. |
| `network_or_contract` | Secretos limpios; mensaje que no afirma éxito y ofrece volver a login. |
| `success` | Sin aviso intermedio persistente; replace inmediato a `/login?reset=1`. |

Una pérdida de respuesta es ambigua. El copy no dice que la clave quedó igual ni que cambió; invita a
intentar iniciar sesión y, si falla, volver a abrir/solicitar enlace. Nunca repite el POST solo.

## 9. Formulario y validación

Capacidades observables:

| ID | Capacidad S03 |
|---|---|
| S03-UX-01 | Marca, título, resumen de política y footer compartidos. |
| S03-UX-02 | Estado de enlace en carga, válido e inválido sin filtrar identidad. |
| S03-UX-03 | Nueva contraseña con label, placeholder legacy, `autocomplete=new-password` y ayuda asociada. |
| S03-UX-04 | Confirmación con label, placeholder legacy y `autocomplete=new-password`. |
| S03-UX-05 | Dos toggles independientes con mostrar/ocultar, `aria-pressed` y retorno de foco. |
| S03-UX-06 | Mínimo 6, mayúscula y especial visibles y validados. |
| S03-UX-07 | Confirmación coincidente y foco en confirmación cuando falla. |
| S03-UX-08 | Reutilización de clave anterior rechazada solo por autoridad PHP. |
| S03-UX-09 | Submit por click/Enter una sola vez, busy y controles bloqueados. |
| S03-UX-10 | CSRF inválido recuperable sin reenvío automático. |
| S03-UX-11 | Token vencido/usado durante submit cambia a estado inválido. |
| S03-UX-12 | Fallo técnico/red/contrato no afirma éxito y limpia secretos. |
| S03-UX-13 | Éxito reemplaza historial y entrega aviso S01 mediante `reset=1`. |
| S03-UX-14 | Enlace S02 disponible cuando no sirve el token; enlace S01 siempre. |
| S03-UX-15 | Dark inicial y light completo con la misma capacidad. |
| S03-UX-16 | Teclado, foco, anuncios y zoom conservan todo el flujo. |

Validación cliente replica solo longitud, mayúscula, especial y coincidencia. La respuesta 422 del
servidor reemplaza cualquier conclusión cliente. No se muestra un medidor de fuerza ni requisitos
adicionales.

## 10. Routing y sesión

- Rutas React: `/app/password/reset` piloto y `/password/reset` canónica.
- `token` se obtiene con `URLSearchParams`; un valor repetido se considera request inválido y no se
  elige silenciosamente el primero.
- S03 tiene precedencia sobre las ramas `anonymous`, `password_change_required` y `authenticated`.
- La sesión solo aporta `csrfToken` y `recargar`; no aporta identidad ni autoriza el reset.
- Loading/error de `/api/session` usa el marco compartido y acción explícita.
- `/login` y `/password/forgot` usan `Link`; el success usa `navigate(..., {replace:true})`.
- Back después del éxito no reabre la URL con token. Antes del éxito, refresh conserva el link.
- GET y HEAD canónicos son SPA; POST legacy sigue PHP durante rollback gracias al mapa por método de
  T01/S01.

## 11. Responsive, tema y design system

Viewports obligatorios:

| Clase | Viewport |
|---|---|
| Móvil | 390×844 |
| Tablet vertical | 768×1024 |
| Desktop canónico | 1180×820 |
| Desktop ancho | 1440×900 |

Reglas:

- 1180×820 dark es el gate principal; los ocho pares tema/viewport son obligatorios;
- una columna y ancho legible; scroll vertical si la política/alert crecen;
- cero overflow horizontal a 100 % y 200 % zoom;
- targets mínimos, texto no truncado y toggles unidos visualmente a sus campos;
- dark es fallback antes de hidratar; light no es una inversión parcial;
- estilos únicamente en el entrypoint React auth y tokens `public/css/tokens.css`;
- sin hex, colores funcionales, estilos inline, `!important` o dependencias AdminLTE/Bootstrap nuevas;
- `prefers-reduced-motion` elimina transiciones no esenciales.

## 12. Accesibilidad

- Un `h1` único y landmarks del marco S01.
- Labels visibles; placeholder nunca sustituye label.
- Política enlazada mediante `aria-describedby` y, si se representa como lista, texto comprensible
  sin depender de color/iconos.
- Toggle `type=button`, nombre dinámico «Mostrar/Ocultar contraseña» y estado `aria-pressed`.
- Estado de validación del enlace con `role=status`; error terminal y mutación con `role=alert`.
- Error de campo enlazado por id; `aria-invalid` solo cuando corresponde.
- Primer error recibe foco: password para política/reutilización, confirmación para mismatch, acción
  para CSRF, alert para 503/red y enlace S02 para token inválido.
- Busy impide mutación duplicada sin retirar el feedback del árbol accesible.
- Tab, Shift+Tab, Enter, Space, Escape no bloquean ni pierden foco.
- Axe sin violaciones graves/críticas en válido, inválido, error y success previo a navegación.

## 13. Manejo de errores

| Evento | Resultado React |
|---|---|
| Query ausente/repetido/mal formado | Estado inválido local; no llama servicio. |
| 422 al validar contrato | Alert de solicitud inválida; no filtra body técnico. |
| 403 al validar | Acción de sesión y nueva validación solo tras click. |
| Red/contrato al validar | Error recuperable con botón «Intentar nuevamente». |
| 503 al validar | Mismo error recuperable; no cambia el enlace a inválido. |
| 422 al cambiar | Limpia secretos después de respuesta servidor; mensaje y foco del campo. |
| 403 al cambiar | Limpia secretos; actualiza sesión sin reenvío. |
| 410 | Limpia secretos y muestra estado inválido. |
| 503/excepción | Limpia secretos; mensaje técnico seguro, token no se da por usado. |
| Red tras submit | Limpia secretos; resultado no confirmado, sin retry automático. |
| Doble click/Enter/StrictMode | Una sola llamada de mutación. |

React nunca muestra stack, SQL, SMTP, username, hash, token, body crudo ni error interno.

## 14. Contratos y pruebas

### 14.1 Frontend

- Esquema request de token estricto y patrón de 64 hex.
- Esquema discriminado `valid/invalid` exacto.
- Esquema request de reset con cuatro reglas cliente y confirmación.
- Esquema success con redirect literal `/login?reset=1`.
- Guard estático: ningún `fetch` fuera de `frontend/src/lib/api/cliente.ts`.
- Componentes: estados S03-UX-01…16, toggles, limpieza, foco, no-retry y single-submit.
- Router: piloto/canónica, query ausente/repetida y tres estados de sesión.

### 14.2 PHP sin DML

Crear un controlador con `PasswordResetService` y body reader inyectables. El contrato puro usa un
fake sin constructor padre para:

- validar llamada exacta `findValidToken(token, 'app')`;
- cubrir valid/invalid sin DB;
- validar llamada exacta `reset(token, 'app', password, confirmPassword)`;
- mapear success, cinco mensajes de política, token inválido, usuario desaparecido, fallo técnico y
  excepción;
- asegurar CSRF/body inválido antes del fake;
- probar igualdad de estado inválido sin identidad.

El contrato HTTP real se limita a CSRF inválido, body extra/roto, token sintácticamente inválido,
método no permitido, headers `no-store`, host SPA y mantenimiento. Esos caminos no llegan a una
mutación. No usar token válido, correo real ni password humano.

No ejecutar `tests/test_password_reset_resultados.php`: escribe tokens. No crear fixtures, no
cambiar claves y no reconciliar usuarios en S03.

### 14.3 Navegador

Interceptar sesión y APIs para:

- token válido, inválido, vencido entre validate/update y query repetido;
- cinco reglas, dos toggles, Enter, doble click y busy;
- 403 de validate y update, 422 por cada campo, 410, 503, red y contrato roto;
- éxito con replace, aviso S01 y back seguro;
- S02/S01 links, deep link y refresh;
- dark/light × cuatro viewports, zoom, teclado, Axe, consola, red y overflow.

Los candidatos visuales usan token sintético, campos vacíos y directorio temporal. No regenerar ni
versionar baselines sin aprobación visual explícita.

## 15. Rollout, rollback y retiro

1. Implementar S03 bajo `/app/password/reset` con APIs interceptadas.
2. Publicar los dos endpoints JSON y probarlos con fake/HTTP pre-mutation.
3. Verificar componente, política, tema, viewports, accesibilidad y redirect S01.
4. Añadir únicamente GET/HEAD `/password/reset` al mapa exacto SPA.
5. Mantener POST legacy, VIEW-03 y CSRF `password_reset` durante la ventana de rollback.
6. Probar que retirar la ruta exacta devuelve GET a VIEW-03 y que el POST legacy sigue disponible.
7. Tras gate post-rollout, retirar GET/POST legacy, `reset()/update()/renderReset()` y VIEW-03.
8. Retirar `login-brand-unified.css` y `auth_forms.js` solo si un scan demuestra cero consumidores
   fuera de `/admin/`; no tocar los assets administrativos aunque sean homónimos.
9. Actualizar manifest auth para fuentes/scenarios React y conservar S01/S02.

Rollback antes del retiro: quitar la ruta exacta canónica del mapa SPA. GET vuelve a VIEW-03 y POST
continúa PHP; los endpoints nuevos pueden quedar sin consumidores o desregistrarse juntos. No se
revierte dato alguno porque el corte de presentación no transforma tokens ni usuarios.

## 16. Criterios de aceptación

- S03-AC-01: piloto y GET/HEAD canónico sirven React con token, deep link y refresh.
- S03-AC-02: S03-UX-01…16 tienen cobertura y VIEW-03 no se retira antes del gate.
- S03-AC-03: validar enlace usa endpoint constante, token en body, Zod y CSRF `shell_api`.
- S03-AC-04: un token inválido/vencido/usado/inactivo produce un estado único sin identidad.
- S03-AC-05: submit acepta solo `{token,password,confirmPassword}` y fija `scope='app'`.
- S03-AC-06: las cinco reglas y sus mensajes conservan autoridad PHP.
- S03-AC-07: 422 se asocia al campo correcto y no añade política nueva.
- S03-AC-08: 403 nunca reenvía automáticamente password ni conserva secretos.
- S03-AC-09: un token que deja de ser válido durante submit cambia a estado inválido.
- S03-AC-10: 503/red/contrato roto no afirman éxito y no filtran detalle.
- S03-AC-11: click, Enter, doble click y StrictMode producen una sola mutación.
- S03-AC-12: éxito hace replace a `/login?reset=1` y S01 muestra su aviso exacto.
- S03-AC-13: back tras éxito no recupera la URL con bearer token.
- S03-AC-14: dark/light pasan en 390, 768, 1180 y 1440 sin overflow.
- S03-AC-15: teclado, foco, anuncios, toggles, zoom y Axe no presentan bloqueo grave.
- S03-AC-16: nuevos endpoints tienen schemas Zod y contrato PHP sin DB/DML.
- S03-AC-17: mantenimiento, `/admin/`, RLS, schema, grants, usuarios, credenciales y datos quedan
  intactos durante diseño/verificación.
- S03-AC-18: rollback a VIEW-03/POST legacy está probado antes del retiro y assets compartidos solo
  se eliminan con cero consumidores.

## 17. Riesgos y mitigaciones

| Riesgo | Mitigación |
|---|---|
| Token termina en logs de una API GET | Validación por POST con token en body y endpoint constante. |
| React filtra identidad al validar | Respuesta binaria exacta y fake de igualdad. |
| Token vence entre carga y submit | 410 cambia a estado inválido. |
| Doble cambio o retry ante red | Busy, guard y ningún retry automático. |
| Secreto permanece en memoria/visible | Limpieza tras respuesta de mutación, invalidación o desmontaje. |
| Cliente suaviza política | PHP sigue autoridad; pruebas fijan cinco mensajes. |
| Corte GET captura POST legacy | `SpaRouter` por método de T01/S01 y contrato HTTP. |
| Retiro rompe S01/S02 | Scan de consumidores y retiro solo en el último consumidor. |
| Prueba cambia una clave real | Fakes/API interceptada; HTTP solo pre-mutation; cero DML. |
| Mantenimiento expone SPA/API | Sin exención; 503 antes del router. |

## 18. Decisiones pendientes

Ninguna. Cambiar la política de contraseña, iniciar sesión automáticamente, añadir MFA o modificar la
vigencia del enlace serían decisiones de producto/seguridad independientes y no se incorporan a una
migración de paridad.

## 19. Gate siguiente

La spec fue auditada y autorrevisada sin decisiones funcionales pendientes. Se invocó
`superpowers:writing-plans` para crear
`docs/superpowers/plans/2026-08-30-s03-restablecer-clave-react.md`; asume S01 y S02 ejecutados, usa
el incremento method-aware T01, prueba contratos PHP con fakes y conserva el gate absoluto de cero
DML durante esta migración documental. Esta documentación no autoriza implementación, commit,
publicación ni cambios de datos.
