---
capa: fuente
tipo: spec
estado: vigente
id: T01
fecha: 2026-08-31
superficie: shell-runtime-react
rutas:
  - "/app"
  - "/api/session"
  - "/api/auth/logout"
  - "/session/touch"
  - "/context/week"
  - "/context/clear-week"
  - "/api/context/weeks/create"
  - "/api/context/weeks/delete-last"
  - "/runtime/frontend-config.js"
depende_de: []
consumido_por: [T02, T03, S01, S02, S03, S04, S05, S06, S07, S08, S09, S10, S11, S12, S13, S14, S15, S16, S17, S18, S19, S20, S21, S22, S23, S24, S25, S26, S27]
views: [VIEW-26, VIEW-29, VIEW-30]
areas: [arquitectura, rbac, design-system]
fuente: "auditoría de shell legacy y React, rutas, controladores, contratos y pruebas en shell-minimo-react; arquitectura aprobada por Felipe el 2026-08-30 y auto-revisión del 2026-08-31"
resumen: "Contrato transversal del shell React: arranque, sesión, routing, navegación autorizada, proyecto y semana, temas claro/oscuro, errores, responsive y convivencia con las rutas PHP."
---

# T01 — Shell y runtime React

> **Estado:** diseño técnico autorrevisado y decision-complete. T01 es un contrato transversal; no
> autoriza implementación, commit, push, PR, publicación, deploy, cambios de datos, DDL/DML,
> cambios RLS, schema, grants, usuarios, credenciales ni trabajo en `/admin/`. Su plan se escribe
> por separado con `superpowers:writing-plans`.

## 1. Resultado buscado

La aplicación principal tiene un único shell React + TypeScript que decide qué experiencia mostrar
después de consultar al servidor: acceso, selección de proyecto o aplicación con contexto completo.
PHP sigue siendo autoridad de sesión, membresía, RBAC, RLS, CSRF, proyecto, semana y destinos
autorizados. Los módulos aún no migrados continúan abriendo su URL PHP mediante navegación completa.

T01 evita que cada superficie vuelva a implementar sidebar, tema, sesión, contexto semanal,
notificaciones de expiración o manejo de errores. Las specs S01–S27 consumen este contrato y solo
declaran su integración particular.

## 2. Autoridad y relación con otras specs

Orden de autoridad para este frente:

1. código y respuestas reales del worktree;
2. esta spec para el destino del shell;
3. `docs/security/rls-runtime-boundary.md` para la frontera RLS ya cerrada;
4. `docs/superpowers/specs/2026-08-28-paridad-shell-react-rls-design.md` para decisiones aún no
   reemplazadas;
5. `docs/superpowers/specs/2026-08-28-migracion-react-typescript-design.md` como programa maestro.

La decisión de Felipe del 2026-08-30 fija **oscuro como modo inicial** y claro como modo completo.
Reemplaza únicamente las frases anteriores que declaraban claro como entrada. El código actual de
`frontend/` y sus pruebas todavía caen a claro; eso es estado auditado, no el contrato objetivo.

T01 no reabre el diseño RLS ni autoriza tocar tablas, grants, usuarios o credenciales.

## 3. Alcance

### 3.1 Incluido

- Estado de arranque y sesión canónica.
- Host de la SPA y routing de convivencia.
- Layout, sidebar, navegación, cuenta y outlet de módulos.
- Proyecto activo y contexto de semana.
- Crear y eliminar semana desde el shell cuando la capacidad lo permita.
- Timeout, touch de sesión y salida.
- Tema claro/oscuro y aplicación antes del primer render.
- Pantallas globales de carga, error, 403, 404 y 500.
- Responsive y accesibilidad del shell.
- Configuración pública de runtime y carga de assets.
- Retiro progresivo de los parciales globales cuando pierdan su último consumidor.

### 3.2 Excluido

- Contenido interno de las superficies S01–S27.
- Contratos de comentarios, crisis y drawer, propiedad de T02.
- Layout, filtros y gráficos BI, propiedad de T03.
- Recuperación y restablecimiento de contraseña, propiedad de S02 y S03.
- Reglas internas para crear, cerrar o reabrir compromisos semanales.
- `/admin/`.
- Cualquier cambio de RLS o datos.

## 4. Archivos propiedad de T01

| ID | Archivo actual | Responsabilidad | Condición de retiro |
|---|---|---|---|
| VIEW-26 | `views/errors/error.view.php` | Error HTML 404/405/500 | Las navegaciones SPA tienen estados equivalentes y los errores PHP residuales conservan una salida segura. |
| VIEW-29 | `views/partials/head_brand.php` | Favicon y touch icon | El último host PHP principal deja de consumirlo y el host React contiene los mismos assets. |
| VIEW-30 | `views/partials/shell_sidebar.php` | Sidebar, cuenta, semanas y scripts inline | Todas las superficies principales usan `AppShell` y las acciones semanales tienen API tipada. |

T01 también gobierna, sin apropiarse automáticamente de ellos:

- `frontend/index.html`, `frontend/src/main.tsx`, `frontend/src/App.tsx`;
- `frontend/src/shell/*`;
- `frontend/src/lib/api/cliente.ts` y el esquema de sesión;
- `src/Core/SpaRouter.php`, `SessionMiddleware.php` y `MaintenanceMode.php`;
- `SessionApiController`, `SessionController`, `ContextController` y
  `FrontendConfigController`;
- `public/js/core/SessionTimeoutManager.js`, `public/js/modules/aia_ui/theme.js`,
  `sidebar_navigation.js`, `shell_week_admin.js` y sus reemplazos React;
- tokens y adapters canónicos del shell.

## 5. Estado actual comprobado

### 5.1 React existente

- `SpaRouter::RUTAS_MIGRADAS` solo contiene `/app`.
- `Rutas` distingue cargando, error, anónimo, autenticado sin proyecto y autenticado con proyecto.
- `GET /api/session` entrega identidad, proyecto, capacidades, navegación BI y CSRF.
- La sidebar React tiene grupos Información, Obra y Compras, pero repite
  `ocultasPorRol`; esa tabla debe desaparecer.
- La pantalla con proyecto solo muestra el nombre del proyecto; aún no hay outlet ni router de
  módulos.
- El conmutador persiste `aia-theme`, pero el fallback actual es claro.
- `cliente.ts` centraliza `fetch` y Zod, pero solo maneja respuestas JSON exitosas y convierte los
  errores HTTP en un mensaje sin cuerpo tipado.

### 5.2 Legacy que todavía aporta paridad

- `shell_sidebar.php` construye navegación, Control Tower, proyecto, usuario, cuenta, semana,
  flyouts por módulo, crear/eliminar semana y permisos.
- La visibilidad sigue mezclando RBAC real con una tabla histórica por rol.
- Cambiar semana usa `/context/week`; crear y eliminar todavía llaman scripts bajo `/legacy/` y
  envían el prefijo `db` desde el navegador.
- `SessionTimeoutManager.js` lee el timeout de `/runtime/frontend-config.js` y toca
  `/session/touch`.
- `theme.js` de las páginas legacy aplica oscuro sin conmutador.
- Los errores de navegación se renderizan con `error.view.php`.

### 5.3 Inconsistencias que el diseño resuelve

- El objetivo aprobado exige ambos temas y oscuro inicial; React actual inicia claro y legacy solo
  expone oscuro.
- React y PHP duplican el catálogo de navegación y la lógica de visibilidad.
- La respuesta de sesión solo autoriza expresamente BI; las demás entradas se filtran en cliente.
- Crear/eliminar semana confían todavía en un nombre de base enviado por el navegador, aunque la
  ejecución ya exige `ProjectScope`.
- Varias mutaciones de contexto no tienen aún un contrato JSON/Zod uniforme.
- Los scripts de semana y sidebar contienen comportamiento inline difícil de probar por unidad.

## 6. Arquitectura destino

```text
public/app/index.html
  └─ ThemeBootstrap (antes del primer paint)
      └─ SesionProvider
          ├─ AuthOutlet                 S01–S03
          ├─ ProjectPicker              S04
          └─ AppShell
              ├─ Navigation             manifiesto del servidor
              ├─ ProjectContext
              ├─ WeekContext
              ├─ AccountMenu
              ├─ NotificationEntry      T02
              ├─ RouteErrorBoundary
              └─ ModuleOutlet           S05–S27
```

Un único `ApiClient` conoce transporte, CSRF, contenido, Zod y errores. Los componentes reciben
modelos validados y acciones; no conocen cookies, códigos de rol, prefijos ni nombres de base.

## 7. Máquina de estados del arranque

| Estado | Condición server-authoritative | Salida React |
|---|---|---|
| `loading` | Bootstrap pendiente | Skeleton/estado de carga, sin contenido operativo. |
| `anonymous` | `authenticated=false`, razón `missing_session` | S01 Login. |
| `password_change_required` | Sesión temporal pendiente | Diálogo/panel de cambio obligatorio de S01; sin navegación ni proyecto. |
| `authenticated_without_project` | Usuario válido y `project=null` | S04 Selector de proyectos. |
| `ready` | Usuario y `ProjectScope` válidos | `AppShell` y ruta autorizada. |
| `expired` | Razón `timeout`, `inactive`, `stale_session` o `session_unverified` | Limpiar estado, aviso seguro y volver a S01. |
| `recoverable_error` | Red, 5xx o contrato inválido | Pantalla de recuperación con reintento explícito. |

Nunca se conserva UI operativa de un estado anterior mientras se vuelve a resolver sesión o
proyecto.

## 8. Bootstrap canónico

### 8.1 Decisión

`GET /api/session` continúa como endpoint canónico y crece de manera compatible; no se crea un
segundo bootstrap. El endpoint es público en el sentido de que una sesión ausente recibe `200`,
pero no entrega datos operativos sin un alcance válido.

### 8.2 Forma objetivo

```json
{
  "state": "authenticated",
  "authenticated": true,
  "reason": null,
  "user": {
    "username": "test.A",
    "displayName": "Test A",
    "role": "A"
  },
  "project": {
    "id": 73,
    "name": "Da Porto",
    "area": "Construccion"
  },
  "capabilities": {
    "canManageWeeks": true
  },
  "navigation": {
    "groups": [
      {
        "id": "obra",
        "label": "Obra",
        "items": [
          {
            "id": "programa-general",
            "label": "Programa General",
            "href": "/programa-general",
            "icon": "calendar"
          }
        ]
      }
    ]
  },
  "week": {
    "current": 6,
    "options": [
      {"number": 6, "startsOn": "2026-08-24", "endsOn": "2026-08-30"}
    ],
    "actions": {"select": true, "create": true, "deleteLast": true}
  },
  "csrfToken": "<64 hex>"
}
```

Reglas de forma:

- `state` solo acepta `anonymous`, `password_change_required` o `authenticated`; permite
  representar la sesión temporal sin exponer `usuario_temp`.
- `reason` es `null` cuando `authenticated=true`; cuando es anónimo usa solo el vocabulario no
  sensible ya producido por `SessionMiddleware`.
- `user`, `project`, `navigation.groups` y `week` respetan el estado de arranque; no se fabrican
  objetos vacíos para esconder ausencia.
- `capabilities` sigue siendo un mapa booleano extensible.
- `navigation.groups` llega ordenado y filtrado por servidor. Un ítem no autorizado no viaja.
- React deriva un único estado activo desde la URL canónica y los patrones de ruta declarados por
  cada spec S; el `href` siempre viene autorizado por el servidor.
- `week=null` cuando no hay proyecto o el módulo no usa semana.
- La respuesta nunca incluye `db`, prefijos, cookies, tokens internos, `usuario_temp` ni secretos.

El esquema Zod aplica refinamientos entre estado y campos, no solo tipos primitivos.

## 9. Contratos HTTP propiedad de T01

| ID | Método y ruta | Estado actual | Contrato objetivo |
|---|---|---|---|
| T01-API-01 | `GET /api/session` | Existe | Bootstrap de §8; `200` aun sin sesión; no-store. |
| T01-API-02 | `POST /api/auth/logout` | Existe | CSRF `shell_api`; destruye sesión/cookie; idempotente; `{success:true}`. |
| T01-API-03 | `POST /session/touch` | Existe | Pasa por cliente común; devuelve `success`, `timestamp`, `timeoutSeconds`; no refresca fuera de una sesión válida. |
| T01-API-04 | `POST /context/week` | Existe | JSON `{semana:int positivo}`; CSRF; verifica que la semana pertenezca al proyecto actual; devuelve bootstrap/contexto actualizado. |
| T01-API-05 | `POST /context/clear-week` | Existe | CSRF; deja semana en estado neutral y devuelve contexto actualizado. |
| T01-API-06 | `POST /api/context/weeks/create` | Nuevo adaptador | JSON `{startsOn:"YYYY-MM-DD"}`; capacidad `lps.semana.crear`; ejecuta servicio extraído del flujo legacy; no acepta `db`. |
| T01-API-07 | `POST /api/context/weeks/delete-last` | Nuevo adaptador | JSON `{week:int}`; capacidad `lps.semana.eliminar`; solo última semana; no acepta `db`. |
| T01-API-08 | `GET /runtime/frontend-config.js` | Existe | Se retira cuando timeout y flags públicos viajen en bootstrap/config de build; mientras tanto no contiene secretos. |

T01-API-06 conserva antes de crear:

- verificación de CIC pendiente;
- bloqueo de la siguiente semana cuando la anterior no está confirmada, salvo la excepción real
  de administración;
- programa maestro obligatorio para la primera semana;
- rango de siete días;
- copias, normalizaciones y arrastres ejecutados por los servicios actuales;
- diferencias Construcción/Pre-Construcción;
- respuesta clara de bloqueo, sin arrays posicionales.

T01-API-07 conserva cascada y regla de última semana. La lógica se extrae a servicios PHP
compartidos; React no llama permanentemente `src/Legacy/`.

Todo endpoint nuevo tiene esquema Zod y prueba contractual PHP. Ninguna mutación se reintenta de
forma automática.

## 10. Navegación y sidebar

### 10.1 Propiedad

T01 es el único propietario de estructura, grupos, orden, iconos, estado activo, colapso,
responsive y cuenta. Cada spec S declara solo:

- `id`, etiqueta, grupo y ruta canónica;
- capacidad requerida y acciones particulares;
- deep links y coincidencia activa;
- comportamiento durante convivencia.

### 10.2 Autorización

- Se elimina `ocultasPorRol` de React.
- Se retira la tabla equivalente de `shell_sidebar.php` al último corte.
- PHP resuelve alias con `RbacService::normalizeRole()` y produce el manifiesto según capacidades,
  área, membresía y flags autorizados.
- Ocultar no reemplaza la guarda de ruta; entrar por URL conserva 403/404 server-side.
- El destino BI proviene del servidor y sigue respetando `BiAccessComponent` hasta T03.

### 10.3 Cuenta

La cuenta muestra nombre, proyecto y acciones autorizadas: cambiar proyecto, tema y salir. Salir
usa `POST /api/auth/logout`; React no navega a un GET destructivo. Durante la convivencia,
`GET /logout` conserva el comportamiento actual únicamente para consumidores PHP. Al retirarse el
último shell legacy se desregistra y `POST /api/auth/logout` queda como única mutación de salida.

## 11. Proyecto y semana

- El proyecto activo proviene exclusivamente del `ProjectScope` enlazado por
  `SessionMiddleware::beginRequest()`.
- Cambiar proyecto vuelve a consultar bootstrap completo y descarta semana, navegación y datos del
  proyecto anterior antes de pintar el nuevo.
- El selector semanal muestra número y rango; no depende de prefijos ni variables globales.
- Cambiar semana actualiza servidor primero y luego invalida las consultas del módulo.
- Crear/eliminar aparece solo si el manifiesto de acciones lo permite.
- Eliminar solo ofrece la semana máxima y exige confirmación accesible.
- Después de crear o eliminar se vuelve a leer el estado canónico; no se actualiza una copia local
  optimista de la lista.

## 12. Sesión, timeout y concurrencia

- El timeout contractual sigue siendo 3600 segundos mientras no cambie
  `SessionMiddleware::IDLE_TIMEOUT_SECONDS`.
- Solo un controlador de actividad del shell programa avisos y touch; los módulos no crean timers.
- Lecturas de fondo pueden enviar `X-AIA-Idle-Refresh: 0` para no prolongar una sesión sin actividad
  humana.
- Al vencer, el cliente cancela o ignora resultados pendientes, limpia caches de proyecto y
  muestra S01 con el aviso correspondiente.
- Un `401` JSON nunca se intenta renderizar como filas, archivo o HTML.
- El logout es idempotente y limpia la cookie además de destruir la sesión.

## 13. Tema claro y oscuro

### 13.1 Contrato aprobado

- Oscuro es el modo inicial cuando no existe preferencia válida.
- Claro y oscuro tienen la misma jerarquía funcional y no esconden controles.
- La preferencia usa `localStorage['aia-theme']` con valores `dark` y `light`.
- Un bootstrap inline mínimo aplica la clase/atributo antes de cargar CSS y antes del primer paint.
- Un valor corrupto o storage bloqueado cae a oscuro sin impedir el arranque.
- El selector tiene nombre accesible, estado anunciado y funciona con teclado.
- Todas las superficies consumen tokens de `public/css/tokens.css`; no crean literales de color.

### 13.2 Convivencia

Las páginas PHP que aún solo soporten oscuro permanecen oscuras hasta su migración. El retorno a la
SPA restaura la preferencia. Una spec S no puede declararse cerrada hasta soportar ambos modos.

Esta decisión exige actualizar las pruebas React actuales que afirman “claro por defecto”; no se
regeneran baselines visuales sin aprobación propia.

## 14. Responsive y accesibilidad

| Modo | Referencia | Shell |
|---|---|---|
| Móvil | `390×844` | Drawer con velo, botón menú, fondo bloqueado y contenido en una columna. |
| Tablet | `768×1024` | Drawer/touch; acciones reordenadas sin desaparecer. |
| Desktop canónico | `1180×820` | Sidebar operable y contenido sin overflow; densidad según tokens. |
| Desktop amplio | `1440×900` | Sidebar persistente/colapsable y canvas limitado por token. |

Requisitos:

- landmark `nav`, `main` y labels únicos;
- `aria-current="page"` en un solo destino;
- apertura/cierre por teclado, `Escape`, trampa y retorno de foco en drawer;
- orden de tabulación coherente y foco siempre visible;
- objetivos touch de al menos 44 px;
- zoom permitido y `prefers-reduced-motion` respetado;
- cero overflow horizontal de página;
- título de documento actualizado por ruta;
- skip link al contenido principal.

## 15. Errores globales

`ApiClient` devuelve un error tipado con `status`, `code`, `message`, `fieldErrors`, `redirect` y
`correlationId` cuando esos campos existan. Si el servidor devuelve HTML o un archivo cuando se
esperaba JSON, el error identifica el contrato roto sin insertar el cuerpo en la UI.

| Caso | Comportamiento |
|---|---|
| Bootstrap/red | Pantalla recuperable con reintento; no muestra login por descarte. |
| `401` | Limpiar sesión/proyecto y volver a S01 con razón segura. |
| `403` | Pantalla de acceso denegado con salida acorde al estado. |
| `404` | Ruta no encontrada dentro del shell; ofrece landing autorizado. |
| `409/422` | Feedback contextual de la superficie consumidora. |
| `5xx` o fallo de render | Error boundary por ruta, correlación cuando exista y recuperación. |

Los errores `/api/*` permanecen JSON PHP. `error.view.php` solo puede retirarse cuando las rutas PHP
residuales mantengan una plantilla segura o dejen de existir.

## 16. RBAC y frontera RLS

- T01 no interpreta roles en React.
- Sesión y proyecto se resuelven con `SessionMiddleware`, `ProjectScopeResolver` y membresía activa.
- Sin proyecto se permiten únicamente identidad, auth, membresías y configuración pública; ninguna
  consulta operacional.
- Un `project_id`, nombre de proyecto o prefijo enviado por el cliente no concede alcance.
- Las acciones de semana requieren capacidad además de RLS.
- El bootstrap nunca enumera destinos o acciones no autorizados.
- Un proyecto declarado en sesión pero sin membresía se limpia y devuelve al selector, tal como ya
  protege `test_api_session_contract.php`.
- Los casos de aislamiento reutilizan la frontera documentada; no cambian DDL/DML, policies ni
  credenciales.

## 17. Convivencia, corte y rollback

1. `/app` continúa como piloto de T01.
2. Cada S añade su ruta exacta o prefijo a `SpaRouter` solo al cerrar paridad.
3. `SpaRouter` distingue rutas exactas de prefijos; `/` nunca se registra como prefijo global.
4. Assets `/app/assets/*` y `/api/*` nunca se confunden con una ruta de pantalla.
5. Las rutas PHP no migradas provocan carga completa y conservan sesión.
6. Refresh y deep link de una ruta React devuelven siempre el host SPA.
7. El rollback modifica el mapa de rutas para volver a PHP; no revierte datos ni relaja RLS.

El host se sirve desde el mismo origen y conserva cookie `SameSite=Lax`/`HttpOnly`. No se introduce
iframe, microfrontend ni segunda sesión.

## 18. Estrategia de pruebas

### 18.1 PHP contractual

- `test_api_session_contract.php`: anónimo, válido, vencido, huérfano, inactivo y proyecto ajeno.
- `test_api_auth_contract.php`: CSRF y logout.
- Nuevos contratos para bootstrap ampliado, navegación, contexto y administración de semanas.
- Permitido/denegado para `lps.semana.crear` y `lps.semana.eliminar`.
- Aislamiento de dos proyectos y rechazo de prefijo/ID cliente.
- `test_maintenance_asset_exemption.php` para no abrir rutas normales durante mantenimiento.

### 18.2 React

- Zod del bootstrap y errores, incluidas combinaciones inválidas.
- Máquina de estados sin flash de contenido.
- Navegación server-driven; ausencia total de tablas por rol en cliente.
- Tema: oscuro inicial, claro/oscuro persistidos, storage corrupto/bloqueado y sin destello.
- Timeout, logout y descarte de estado del proyecto.
- Contexto semanal y acciones según manifiesto.

### 18.3 Navegador

- `/app`, refresh y deep links.
- Rol con acción y rol denegado.
- Dos proyectos sin arrastre de datos/contexto.
- Claro/oscuro × 390/768/1180; 1440 cuando el manifiesto visual lo exija.
- Drawer, teclado, foco, zoom, overflow y consola/red limpias.
- Crear/cambiar/eliminar semana con servicios/fakes y red totalmente interceptada; una prueba de
  persistencia real exige autorización separada para DML y no pertenece a este plan documental.
- Rollback de ruta probado al menos una vez antes del corte canónico.

## 19. Criterios de aceptación

- T01-AC-01: Los siete estados de arranque de §7 tienen salida explícita y pruebas.
- T01-AC-02: `GET /api/session` es la única fuente de bootstrap y pasa Zod/contrato PHP.
- T01-AC-03: React no contiene mapas de visibilidad por rol ni construye URLs privilegiadas.
- T01-AC-04: Sidebar, cuenta, proyecto, semana y outlet funcionan en los cuatro viewports.
- T01-AC-05: Oscuro inicia sin preferencia; claro y oscuro persisten sin flash.
- T01-AC-06: Logout, timeout y proyecto inválido limpian todo estado operativo.
- T01-AC-07: Cambiar, crear y eliminar semana conserva reglas legacy, RBAC y RLS sin aceptar `db`.
- T01-AC-08: No hay `fetch` fuera de `frontend/src/lib/api/cliente.ts`.
- T01-AC-09: 401/403/404/409/422/5xx y contrato roto tienen recuperación segura.
- T01-AC-10: No existe overflow horizontal, trampa de foco ni control sin nombre accesible.
- T01-AC-11: Las rutas legacy no migradas siguen funcionando durante convivencia.
- T01-AC-12: El rollback por mapa de rutas está probado y nunca desactiva RLS.
- T01-AC-13: VIEW-26, VIEW-29 y VIEW-30 tienen consumidor React y condición de retiro demostrada.
- T01-AC-14: `/admin/`, datos, grants, usuarios y credenciales permanecen fuera.

## 20. Preguntas abiertas

No quedan decisiones arquitectónicas abiertas para T01. El plan deberá implementar los payloads de
§9 mediante servicios extraídos del flujo legacy inventariado, sin cambiar sus reglas de negocio.

## 21. Siguiente gate

Escribir y autorrevisar `docs/superpowers/plans/2026-08-30-t01-shell-runtime-react.md` con
trazabilidad exacta de los catorce criterios. El plan debe reconocer el shell mínimo ya implementado,
cerrar sólo sus brechas medidas por incrementos y diferir el retiro de VIEW-26/29/30 hasta su censo
cero. No se implementa en esta sesión documental.
