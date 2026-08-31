---
capa: fuente
tipo: spec
estado: autorrevisado
id: S04
fecha: 2026-08-30
superficie: selector-proyectos
rutas: ["/proyectos"]
depende_de: [S01, S02, S03, T01]
views: [VIEW-11]
areas: [frontend, react, proyectos, sesion, rbac, rls, csrf, tema, responsive, accesibilidad]
fuente: "auditoria de rutas, ProjectSelectorController, ProjectApiController, ProjectAccessService, ProjectLandingService, BiAccessComponent, project_selector.view.php, React actual, contratos y pruebas en shell-minimo-react"
resumen: "Migracion completa del selector de proyectos a React, preservando membresia, roles, proyectos cerrados, destino contextual, contexto de sesion/RLS, BI autorizado, busqueda, sidebar, ambos temas y rollback sin modificar la frontera de datos."
---

# S04 — Selector de proyectos en React

> **Estado:** diseño técnico autorrevisado, sin decisiones de negocio, producto, estrategia o PM
> pendientes. Esta spec no autoriza implementación, selección real de proyecto durante la fase
> documental, DDL/DML, cambios RLS, cambios de permisos, deploy, publicación ni trabajo en `/admin/`.

## 1. Resultado buscado

`GET /proyectos` sirve la SPA principal y presenta en React todos y solo los proyectos que la
persona autenticada puede abrir. La pantalla conserva la búsqueda, conteos, tarjetas, área, estado,
rol legible, Control Tower cuando corresponde, cuenta, salida y destino contextual calculado por
PHP. También se convierte en la entrada explícita para cambiar de proyecto desde el shell.

Seleccionar una tarjeta no concede acceso por el contenido cargado previamente. El servidor vuelve
a comprobar usuario, membresía, área, proyecto activo, apertura para el perfil y rol normalizado;
solo entonces reemplaza el contexto de sesión, enlaza `ProjectScope`, determina la semana y devuelve
el landing autorizado. React navega al destino recibido sin construir rutas según rol o área.

La apariencia puede evolucionar dentro del design system, pero no puede perder ninguna capacidad
observable de VIEW-11 ni crear una segunda política de autorización en el navegador.

## 2. Alcance y propiedad

S04 posee:

- VIEW-11 `views/core/project_selector.view.php`;
- GET/HEAD canónico `/proyectos` y piloto `/app/proyectos`;
- listado React de proyectos disponibles;
- búsqueda efímera, conteos, vacío y sin resultados;
- tarjetas con nombre, área, estado, rol y proyecto actual;
- selección mediante `/api/proyectos/seleccionar`;
- entrega del landing autorizado al navegador;
- navegación preproyecto «Tus proyectos» y Control Tower cuando PHP lo autoriza;
- integración de «Cambiar proyecto» desde la cuenta del shell;
- adaptación y retiro gateado del POST legacy `/proyecto/seleccionar`.

S04 consume, sin redefinirlos:

- la máquina de sesión, `BrowserRouter`, tema, cliente HTTP, errores tipados y host SPA de S01–S03;
- estructura, responsive, cuenta, salida y foco de la sidebar de T01;
- `ProjectAccessService` como única autoridad de listado y selección;
- `ProjectLandingService` como única autoridad del destino y semana iniciales;
- `BiAccessComponent::canAccessAny()` y `globalUrl()` como autoridad temporal de BI;
- `SessionMiddleware`, `ProjectScopeResolver`, `RbacService` y la frontera RLS vigente.

Quedan fuera:

- crear, editar, cerrar, reabrir, asignar miembros o administrar proyectos;
- cambiar roles, alias, capacidades o el criterio de proyectos cerrados;
- alterar la resolución del landing o de la semana inicial;
- rediseñar Control Tower o sus permisos;
- persistir búsqueda, favoritos, orden manual o proyecto preferido;
- `/admin/`;
- RLS, schema, migraciones, grants, usuarios, credenciales y datos.

## 3. Auditoría del estado actual

### 3.1 Rutas, sesión y middleware

`public/index.php` registra hoy:

| Método | Ruta | Handler |
|---|---|---|
| GET | `/proyectos` | `ProjectSelectorController::index()` |
| POST | `/proyecto/seleccionar` | `ProjectSelectorController::select()` |
| GET | `/api/proyectos` | `ProjectApiController::index()` |
| POST | `/api/proyectos/seleccionar` | `ProjectApiController::select()` |

Las cuatro rutas requieren sesión. `/proyectos` no es una ruta pública y una sesión ausente termina
en `/login`; las APIs responden JSON `401`. No se exige proyecto activo porque esta superficie es la
que lo establece. El selector también debe abrir cuando ya existe proyecto: ese es el flujo de
cambio de proyecto.

`SpaRouter` solo reconoce actualmente `/app`; `/app/proyectos` todavía no tiene una ruta React
explícita y `/proyectos` continúa rindiendo VIEW-11.

### 3.2 Autoridad de membresía y permisos

`ProjectAccessService::listForUser()` consulta `project_members`, `general_usuarios` y
`general_proyectos_procesos`. Aplica este contrato:

1. el usuario se toma de `$_SESSION['usuario']`, no del request;
2. solo aparecen áreas `Construccion` y `Pre-Construccion`;
3. solo aparecen proyectos con `Activo=1`;
4. el rol crudo se normaliza con `RbacService::normalizeRole()`;
5. un proyecto con `Acceso=0` solo aparece para los roles canónicos de jefatura devueltos por
   `RbacCatalog::managementRoles()`, actualmente `A` y `D`;
6. el nombre legible se produce en PHP como `rol_nombre`;
7. el orden es `Proyecto_Proceso ASC`.

`RbacCatalog::closedProjectVisibleRoles()` conserva además `P` únicamente para consultas que leen el
rol crudo; el selector trabaja con el rol ya normalizado. La prueba
`tests/test_selector_proyectos_criterio_unico.php` fija que listar y seleccionar compartan este
criterio y que no vuelva una lista privada o una barra de avance aleatoria.

React no debe filtrar proyectos por rol, área, `Activo` o `Acceso`. Si un proyecto no viaja en la
respuesta, el cliente no conoce su existencia.

### 3.3 Selección y contexto de sesión

`ProjectAccessService::select(usuario, nombre)` vuelve a consultar por usuario, membresía, nombre,
área válida y `Activo=1`. Después normaliza el rol y vuelve a aplicar el criterio de `Acceso=0`.
En éxito establece:

- `proyecto`;
- `project_id`;
- `area`;
- `db` por compatibilidad legacy;
- `permiso` y `permiso_canonico`;
- `pdcActivo`;
- `semana`, después de resolver el landing.

Luego resuelve `ProjectScope`, limpia el scope anterior, rechaza y limpia contexto si el nuevo scope
no es válido, enlaza el scope válido y registra el acceso mediante el mecanismo de auditoría
existente. S04 no cambia ese orden ni añade `project_id`, `db`, rol, área o semana al payload
cliente.

La selección por nombre es el contrato legacy vigente. El `id` del listado sirve para identidad de
UI y proyecto actual, pero S04 no cambia la clave de selección sin una decisión de dominio sobre
nombres duplicados.

### 3.4 Landing contextual

`ProjectLandingService` devuelve un path relativo al mismo origen:

| Condición | Landing actual |
|---|---|
| Área `Pre-Construccion` | `/programa-general` |
| Construcción sin semanas activas | `/programa-general-actualizar` |
| Rol `G`, `S` o `SG` | `/programacion-semanal/cic` |
| Rol `C` | `/programacion-semanal` |
| Semana abierta o confirmada pendiente de calificación | `/programacion-semanal` |
| Fallback autorizado restante | `/programa-general` |

La resolución también fija `$_SESSION['semana']`. React no reproduce el árbol de decisión, no mira
el rol y no sustituye el path por un landing genérico.

### 3.5 Contrato JSON existente

`GET /api/proyectos` responde hoy:

```json
{
  "projects": [
    {"id": 73, "name": "Da Porto", "role": "A"}
  ]
}
```

La respuesta omite `Area`, `Activo`, `rol_nombre` y navegación BI, aunque VIEW-11 sí los usa.

`POST /api/proyectos/seleccionar` acepta JSON laxo, lee `name`, exige CSRF `shell_api` en
`X-CSRF-Token` y responde una unión implícita:

```json
{"success": true, "message": null}
```

o:

```json
{"success": false, "message": "No se pudo acceder al proyecto seleccionado."}
```

El rechazo funcional usa HTTP `200` para no distinguir proyecto inexistente, inactivo, cerrado o
ajeno. El éxito omite el `route` que el servicio ya calculó, de modo que React solo recarga sesión y
no conserva el landing legacy.

El controlador instancia el servicio directamente, lee `php://input` dentro del método y no permite
un contrato puro con fake. `tests/test_api_projects_contract.php` llega a ejecutar una selección
real válida; eso puede escribir auditoría y no es admisible durante este programa documental ni en
la verificación S04 sin consentimiento de mutación.

### 3.6 VIEW-11 y comportamiento observable

La pantalla PHP presenta:

- sidebar con marca, «Tus proyectos» activo, Control Tower condicional, cuenta y logout;
- título «Tus proyectos»;
- buscador con label oculto, placeholder, `aria-controls` y live region;
- conteo inicial y conteo filtrado con singular/plural;
- alerta de sesión descartable;
- grilla de tres columnas desktop y tarjetas con nombre largo, área, estado y rol legible;
- botón «Ingresar al proyecto» por tarjeta;
- deshabilitado y `aria-busy` del botón enviado;
- estado vacío «No tienes proyectos asignados / Contacta al administrador»;
- estado sin resultados «No encontramos proyectos / Prueba con otro término»;
- footer de producto.

La búsqueda recorta extremos, usa minúsculas del locale, filtra por nombre y agrupa los cambios en
`requestAnimationFrame`. Es efímera y no altera el orden del servidor.

El CSS consume tokens, pero está acoplado a Bootstrap, selectores de VIEW-11 y compensaciones
unlayered. El manifiesto solo declara desktop oscuro `1180×820` y `1440×900`. No existe contrato
móvil/tablet y la página legacy no ofrece modo claro.

### 3.7 React existente

`frontend/src/shell/SelectorProyecto.tsx` ya cubre una fracción del módulo:

- carga el GET con `pedir()`;
- muestra loading, error, vacío y una lista de botones;
- publica `{name}` con CSRF;
- impide selecciones concurrentes;
- recarga `/api/session` después del éxito.

Sus brechas son:

- esquemas Zod inline y no estrictos;
- ausencia de gateway de dominio;
- pruebas que interceptan `fetch` directamente en vez del gateway;
- sin búsqueda, conteos, área, estado, rol legible ni proyecto actual;
- sin Control Tower, sidebar completa, cuenta o cambio de proyecto;
- sin retry de carga ni tratamiento tipado de `401/403/422/5xx`;
- sin landing server-authoritative;
- sin ruta explícita para una sesión que ya tiene proyecto;
- sin responsive, claro, foco dirigido ni pruebas visuales React.

`NavegacionLateral.tsx` tiene todavía visibilidad hardcodeada por rol. S04 no añade otra tabla: la
sidebar preproyecto consume únicamente los ítems globales enviados por PHP y T01 retirará las tablas
cliente del shell completo.

### 3.8 Cobertura existente y límites

- `tests/test_api_projects_contract.php` cubre sesión, listado, CSRF, éxito y rechazo, pero el éxito
  real puede ejecutar DML de auditoría.
- `tests/test_selector_proyectos_criterio_unico.php` protege normalización y proyecto cerrado.
- `tests/browser/project-selector-sidebar.spec.mjs` fija sidebar desktop oscuro y su teclado.
- `tests/browser/design-system-compliance.mjs` y el manifiesto fijan tokens, assets y dos viewports
  desktop.
- `tests/design-system/project-selector-contract.test.mjs` fija consumo del design system.
- `tests/browser/support/session.mjs` depende del flujo selector para muchas suites.
- No hay fixture autorizado de usuario autenticado sin proyectos; el flujo vacío se cubre mejor por
  contrato/UI interceptada que inventando datos.
- No existe cobertura de claro, móvil, tablet, ruta piloto React, búsqueda React, proyecto actual,
  landing JSON o cambio de proyecto.

## 4. Alternativas evaluadas

### A. Completar el selector React y adaptar las APIs existentes — elegida

Mantiene `ProjectAccessService`, amplía el GET con los campos que VIEW-11 ya consume y devuelve el
landing que el POST ya calcula. React obtiene paridad sin duplicar autorización ni crear endpoints
paralelos.

### B. Incrustar una isla React dentro de VIEW-11

Reduciría el primer corte, pero conservaría PHP como dueño del documento, duplicaría sidebar/tema y
no resolvería la ruta canónica de cambio de proyecto. Se descarta.

### C. Usar `project_id` y decidir el landing en React

El id parece más estable, pero cambiaría un contrato de dominio no auditado y trasladaría política
de rol/área/semana al cliente. Se descarta. El servicio sigue recibiendo el nombre y PHP sigue
devolviendo el destino.

### D. Recargar `/api/session` y dejar la URL actual

Es el comportamiento React actual. Pierde el landing contextual y puede dejar al usuario en una
ruta que no corresponde a su área o semana. Se descarta.

## 5. Arquitectura objetivo

```text
/proyectos o /app/proyectos
  → host SPA + estado authenticated de /api/session
  → SelectorProyectos
      → GET /api/proyectos
          → ProjectAccessService::listForUser(usuario de sesión)
          → BiAccessComponent::canAccessAny/globalUrl
      → búsqueda local, tarjetas y navegación global autorizada
      → POST /api/proyectos/seleccionar {name}
          → ProjectAccessService::select(usuario de sesión, name)
          → ProjectScope nuevo + semana + landing
      → navegación completa al route devuelto
```

Responsabilidades:

- PHP: identidad, lista, orden, rol legible, BI, revalidación, sesión, scope, semana y landing.
- Zod: forma exacta de requests/responses y paths internos.
- gateway `proyectos.ts`: únicas operaciones de transporte del módulo.
- React: estados visuales, búsqueda, conteos, interacción, foco y entrega del path al shell.
- T01: sidebar, cuenta, logout, tema, drawer móvil y descarte de contexto previo.

## 6. Contratos JSON objetivo

### 6.1 Listar proyectos

```http
GET /api/proyectos
Accept: application/json
Cache-Control: no-store
```

```json
{
  "projects": [
    {
      "id": 73,
      "name": "Da Porto",
      "area": "Construccion",
      "active": true,
      "role": "A",
      "roleLabel": "Administrador"
    }
  ],
  "navigation": {
    "bi": {
      "visible": true,
      "href": "/bi/control-tower"
    }
  }
}
```

Reglas:

- el objeto raíz y cada proyecto son estrictos;
- `id` es entero positivo, `name` y `roleLabel` no vacíos;
- `area` solo acepta `Construccion` o `Pre-Construccion`;
- `active` es `true` porque el servicio excluye `Activo!=1`; se mantiene para la paridad del chip;
- `role` es un código canónico, pero React solo lo conserva como dato, no toma decisiones con él;
- `navigation.bi.visible=true` exige un `href` interno; `false` exige `href=null`;
- el servidor conserva orden alfabético y no entrega `db`, `Base_de_Datos`, `Acceso`, `project_id`
  ajeno, semana ni capacidades ocultas;
- sin proyectos se responde `200` con `projects: []`, no `404` ni `401`.

### 6.2 Seleccionar proyecto

```http
POST /api/proyectos/seleccionar
Content-Type: application/json
X-CSRF-Token: <shell_api>
Cache-Control: no-store

{"name":"Da Porto"}
```

Éxito:

```json
{
  "success": true,
  "message": null,
  "route": "/programacion-semanal"
}
```

Rechazo funcional no enumerativo:

```json
{
  "success": false,
  "message": "No se pudo acceder al proyecto seleccionado.",
  "route": null
}
```

Reglas:

- request estricto: exactamente `name`, string recortado no vacío; no se inventa un límite distinto
  del campo y servicio existentes;
- no se aceptan `id`, `project_id`, `db`, `area`, `role`, `week`, `route` ni campos extra;
- `401` significa sesión ausente/vencida y usa el error JSON común;
- `403` significa CSRF inválido y nunca reintenta la selección;
- JSON roto, raíz no objeto, nombre vacío o campos extra responden `422` antes del servicio;
- proyecto inexistente, ajeno, inactivo o cerrado para el perfil conserva HTTP `200` y el mismo
  rechazo para no crear un oráculo;
- `route` de éxito debe ser un path absoluto del mismo origen: empieza por una sola `/`, no contiene
  esquema, host, backslash, control chars ni `//` inicial;
- React no acepta un éxito sin `route` ni un rechazo con route.

## 7. Modelo y máquina de estados

El selector usa un estado explícito:

| Estado | Condición | UI |
|---|---|---|
| `loading` | GET inicial pendiente | Estructura estable, título, búsqueda deshabilitada y skeleton/lista anunciada. |
| `ready` | Lista no vacía | Conteo, buscador y tarjetas. |
| `empty` | `projects=[]` | Mensaje de acceso y contacto; buscador oculto/deshabilitado. |
| `no_results` | Filtro no vacío sin coincidencias | Mensaje específico; conserva buscador y opción de limpiarlo. |
| `load_error` | Red, 5xx o contrato inválido | Alerta segura y retry explícito. |
| `selecting` | Una tarjeta en POST | Solo esa tarjeta dice «Abriendo…»; todas impiden nueva selección. |
| `selection_rejected` | Unión funcional `success=false` | Alerta no enumerativa, lista intacta y foco recuperable. |
| `session_expired` | `401` | T01 descarta sesión/contexto y muestra S01. |
| `csrf_stale` | `403` | Acción explícita para actualizar sesión; no reenvía POST. |

Un GET viejo o abortado no puede reemplazar una carga posterior. Al desmontar se cancela la
lectura. La mutación no se reintenta automáticamente.

## 8. Selección, aterrizaje y cambio de proyecto

1. React envía solo el nombre de la tarjeta elegida y el CSRF de sesión.
2. Mientras espera, conserva búsqueda y scroll; bloquea otro submit.
3. Un rechazo deja la persona en el selector y no revela la causa interna.
4. Un éxito entrega `route` a una callback del shell.
5. La callback descarta/invalida el bootstrap y datos del proyecto anterior antes de mostrar otro
   módulo.
6. Durante convivencia usa navegación completa `window.location.assign(route)`: así una ruta aún
   legacy carga PHP y una ya migrada carga su host canónico con la nueva sesión.
7. No se hace navegación optimista ni se pinta el nuevo proyecto antes de que el servidor acepte.

La cuenta de T01 muestra «Cambiar proyecto» cuando hay proyecto activo y enlaza `/proyectos`. En el
selector esa acción se omite por redundante. Si se llegó con proyecto activo, la tarjeta cuyo `id`
coincide con `session.project.id` muestra «Proyecto actual»; sigue siendo seleccionable para volver
a resolver su landing.

## 9. Composición de la pantalla

La superficie React contiene:

- skip link y `main` único;
- sidebar T01 con marca, «Tus proyectos» activo, BI autorizado, tema y cuenta;
- encabezado con `h1` «Tus proyectos» y explicación breve;
- acceso a Control Tower también en el encabezado cuando `navigation.bi.visible=true`, como en
  legacy;
- búsqueda por nombre con botón accesible para limpiar cuando tiene valor;
- conteo visible y live region separada para cambios;
- grilla/lista semántica de tarjetas;
- área «Construcción» o «Pre-Construcción»;
- chip «Activo»;
- rol legible precedido por «Rol:»;
- chip «Proyecto actual» cuando aplica;
- acción «Ingresar al proyecto» y estado «Abriendo…»;
- vacío, sin resultados, error y retry;
- footer compartido de producto.

La búsqueda aplica `trim()` y comparación case-insensitive de locale español. Puede normalizar
diacríticos de entrada y nombre para que «construccion» encuentre «Construcción» sin cambiar orden,
permisos ni datos. El conteo usa singular/plural y se actualiza después de cada filtro.

## 10. Responsive y temas

| Viewport | Composición S04 |
|---|---|
| `390×844` | Sidebar T01 como drawer; encabezado, BI, búsqueda y tarjetas en una columna; botones de ancho completo. |
| `768×1024` | Drawer/touch; dos columnas cuando el ancho útil lo permite; búsqueda y acción sin solaparse. |
| `1180×820` | Sidebar persistente/colapsable; tres columnas; documento scrolleable y sin overlay de grilla. |
| `1440×900` | Tres columnas dentro del canvas máximo; gutters simétricos y nombres largos contenidos. |

Oscuro es el modo inicial sin preferencia. Claro y oscuro muestran idénticas acciones, estados,
contraste semántico y foco. S04 usa únicamente tokens de `public/css/tokens.css` y componentes del
design system: sin hex, estilos inline, `!important`, Bootstrap, jQuery, Font Awesome ni reglas
unlayered de rescate.

## 11. Permisos, RBAC y frontera RLS

- Sesión, no React, aporta el usuario.
- `ProjectAccessService` sigue siendo el único filtro de membresía, proyecto activo y cierre.
- `RbacService::normalizeRole()` y `RbacCatalog::managementRoles()` no cambian.
- El cliente nunca recibe ni envía `Base_de_Datos`, prefijo o `Acceso`.
- El POST no confía en que el proyecto apareció en el GET; revalida todo.
- La selección exitosa conserva el orden actual: reemplazar sesión → resolver scope → limpiar scope
  anterior → enlazar scope nuevo → resolver landing/semana.
- Si `ProjectScopeResolver` falla, el servicio limpia el contexto y responde rechazo.
- Cambiar proyecto descarta datos/caches cliente del anterior antes de cualquier render operativo.
- BI sigue resuelto por `BiAccessComponent`; React nunca infiere visibilidad desde el rol.
- S04 no cambia RLS ni vuelve a probar su implementación mediante DDL/DML; solo protege que el
  adaptador no amplíe la entrada cliente.

## 12. Errores y recuperación

| Caso | Resultado |
|---|---|
| GET red/5xx | Alerta «No pudimos cargar tus proyectos», botón «Reintentar». |
| GET contrato roto | Mismo copy seguro; detalle solo en diagnóstico local, nunca cuerpo crudo. |
| GET 401 | T01 vuelve a S01 y limpia contenido del selector. |
| POST 200 `success=false` | «No pudimos abrir ese proyecto. Verifica tu acceso e inténtalo de nuevo.» sin causa. |
| POST 403 | «Tu sesión de seguridad cambió» y acción «Actualizar sesión»; no retry automático. |
| POST 422 | Error de selección seguro; foco en alerta y luego retorno a la tarjeta. |
| POST red/5xx | Estado incierto; no afirmar éxito ni reenviar; permite intento manual. |
| route inválido/contrato roto | No navegar; tratar como error de contrato. |

El error no borra el filtro ni las tarjetas. Cerrar la alerta devuelve foco a la tarjeta que originó
la selección cuando sigue visible.

## 13. Accesibilidad

- `main`, `nav`, encabezados y regiones tienen nombres únicos.
- Existe un solo `h1` y un solo `aria-current="page"`.
- El buscador tiene label visible o equivalente inequívoco, `type=search`, `autocomplete=off` y
  `aria-controls` sobre la lista.
- Conteo inicial y filtrado no se anuncian dos veces; la live region usa `polite`.
- La lista usa `ul/li` o roles equivalentes sin mezclar semánticas.
- El nombre de cada botón incluye el proyecto en su nombre accesible.
- Busy usa texto visible, `disabled` y `aria-busy`; no depende solo de spinner.
- Área, estado, rol y proyecto actual no dependen solo del color.
- Targets miden al menos 44 px, el foco es visible y reduced motion elimina transiciones no
  esenciales.
- Drawer móvil cumple `Escape`, trampa y retorno de foco por T01.
- El zoom al 200 % no elimina controles ni crea overflow horizontal de página.

## 14. Rendimiento y concurrencia

- Solo queda un GET vigente al montar; bajo `StrictMode` el cleanup puede abortar la invocación de
  prueba antes de iniciar la definitiva, pero nunca deja dos respuestas capaces de escribir estado.
- El filtro es local porque la lista ya está autorizada y acotada; no hace request por tecla.
- La normalización de búsqueda se memoriza por lista y el conteo deriva del mismo resultado.
- Solo puede existir una selección pendiente.
- No hay precarga de módulos ni datos operativos antes de enlazar proyecto.
- La respuesta usa `Cache-Control: no-store`; no se guarda en local/session storage.

## 15. Estrategia de pruebas sin DML

### 15.1 PHP puro

Un contrato nuevo instancia `ProjectApiController` con:

- `ProjectAccessService` fake sin constructor real;
- lector de body inyectado;
- resolver BI inyectado.

Cubre lista completa/estricta, vacío, BI visible/oculto, body roto, claves extra, nombre vacío,
rechazo no enumerativo, éxito con route y rechazo de route inseguro. No abre DB.

### 15.2 HTTP real seguro

`tests/test_api_projects_contract.php` se reduce a caminos que no seleccionan un proyecto válido:

- anónimo `401`;
- GET autenticado de solo lectura;
- CSRF ausente/inválido;
- JSON/campos inválidos `422` antes del servicio;
- nombre sintético no autorizado con CSRF válido y respuesta no enumerativa.

Elimina la selección válida real y limpia siempre el archivo de sesión temporal. No ejecuta el
logger de acceso ni DML.

### 15.3 React

- Zod rechaza omisiones, extras, áreas/paths inválidos e inconsistencias BI/success.
- Gateway usa endpoints, JSON, signal y CSRF exactos.
- Filtro cubre trim, mayúsculas, acentos, singular/plural, limpiar y orden estable.
- Componente cubre todos los estados de §7, proyecto actual, BI, foco y selección única.
- Sidebar cubre variante preproyecto y cambio de proyecto desde variante con proyecto.
- No se mockea `fetch` en componentes; se mockea el gateway.

### 15.4 Navegador y visual

Las APIs se interceptan con fixtures sintéticos; una selección exitosa devuelve un route y se
observa la callback/navegación sin llegar al backend real. Se cubren:

- piloto y canónico, refresh y deep link;
- lista, búsqueda, vacío, no-results, load error/retry y rechazo;
- proyecto actual y cambio de proyecto;
- BI visible/oculto;
- teclado, foco, drawer, overflow, consola y requests duplicados;
- oscuro/claro en `390×844`, `768×1024`, `1180×820` y `1440×900`.

Los candidatos visuales se guardan fuera de git. No se reemplazan los dos baselines legacy ni se
añaden nuevos hashes sin aprobación visual explícita.

## 16. Corte, convivencia y rollback

1. `/app/proyectos` activa primero S04 con APIs existentes adaptadas.
2. `/proyectos` legacy y `/proyecto/seleccionar` continúan disponibles durante el piloto.
3. El piloto demuestra paridad funcional, ambos temas y cuatro viewports.
4. `SpaRouter` promueve solo GET/HEAD `/proyectos`; POST a esa URL no se captura.
5. El shell muestra «Cambiar proyecto» hacia la URL canónica.
6. Después del gate post-corte se retiran VIEW-11, su JS inline, la ruta POST legacy y el CSS
   exclusivo legacy.
7. El API y `ProjectAccessService` permanecen.

Rollback antes del retiro vuelve a quitar `/proyectos` del mapa React y conserva VIEW-11 + POST
legacy. Después del retiro, restaurar esos consumidores requiere revertir el commit de retiro; no
se cambian datos, sesión, RLS ni contratos del servicio.

## 17. Requisitos UX trazables

- S04-UX-01: «Tus proyectos» es el título único y «Tus proyectos» es el único destino activo.
- S04-UX-02: Loading mantiene shell estable y anuncia carga una sola vez.
- S04-UX-03: La lista muestra nombre, área, Activo y rol legible en cada tarjeta.
- S04-UX-04: El proyecto de sesión se identifica como «Proyecto actual».
- S04-UX-05: La búsqueda filtra por nombre sin alterar orden ni permisos.
- S04-UX-06: El conteo inicial y filtrado usa singular/plural y live region.
- S04-UX-07: Cero coincidencias conserva buscador y ofrece limpiarlo.
- S04-UX-08: Cero proyectos explica que se contacte al administrador, sin CTA administrativo.
- S04-UX-09: Error de carga conserva sidebar y permite retry explícito.
- S04-UX-10: Solo una selección puede estar pendiente y muestra «Abriendo…» en su tarjeta.
- S04-UX-11: Rechazo funcional no revela si el proyecto existe, está cerrado o es ajeno.
- S04-UX-12: Éxito navega al landing exacto enviado por PHP.
- S04-UX-13: Control Tower aparece únicamente cuando el servidor lo entrega.
- S04-UX-14: «Cambiar proyecto» aparece en cuenta con proyecto y no se duplica en el selector.
- S04-UX-15: Logout y tema permanecen operables en selector.
- S04-UX-16: Claro y oscuro conservan contenido, contraste, foco y estados.
- S04-UX-17: Móvil/tablet conservan búsqueda, tarjetas, BI, cuenta y selección.
- S04-UX-18: Nombres largos, zoom y 390 px no crean overflow horizontal.

## 18. Criterios de aceptación

- S04-AC-01: `/app/proyectos` y luego `/proyectos` rinden el mismo componente React protegido.
- S04-AC-02: Usuario sin sesión obtiene estado S01/401 y nunca una lista vacía engañosa.
- S04-AC-03: GET contiene solo proyectos devueltos por `ProjectAccessService`, en su orden.
- S04-AC-04: Los cinco campos de tarjeta y la navegación BI pasan Zod estricto.
- S04-AC-05: El POST acepta solo `{name}` y CSRF `shell_api`.
- S04-AC-06: El POST revalida autorización en servidor y no acepta contexto cliente.
- S04-AC-07: Rechazos por ausencia/inactividad/cierre/membresía son indistinguibles externamente.
- S04-AC-08: Éxito devuelve y usa un route interno calculado por `ProjectLandingService`.
- S04-AC-09: El orden de reemplazo/bind de `ProjectScope` permanece intacto.
- S04-AC-10: Búsqueda, conteos, vacío y no-results cumplen S04-UX-05…08.
- S04-AC-11: Loading, retry, 401, 403, 422, rechazo y fallo incierto tienen salida probada.
- S04-AC-12: Proyecto actual y cambio de proyecto no arrastran semana, navegación o datos previos.
- S04-AC-13: BI nunca se infiere en React y respeta el resolver servidor.
- S04-AC-14: Sidebar, cuenta, logout y tema cumplen T01 sin duplicar tablas por rol.
- S04-AC-15: No existe `fetch` productivo fuera de `frontend/src/lib/api/cliente.ts`.
- S04-AC-16: Claro/oscuro y los cuatro viewports no pierden capacidad ni crean overflow.
- S04-AC-17: Teclado, foco, anuncios, targets, zoom y reduced motion pasan sus escenarios.
- S04-AC-18: Contratos PHP/HTTP y navegador S04 no ejecutan selección válida real ni DML.
- S04-AC-19: El corte y rollback method-aware están probados antes de retirar VIEW-11.
- S04-AC-20: VIEW-11, POST legacy y assets exclusivos solo se retiran tras demostrar cero consumidores.

## 19. Decisiones pendientes

No quedan decisiones de negocio, producto, estrategia o PM para S04. Se preservan las reglas
existentes de membresía, cierre, rol, landing, semana y BI. La selección por nombre se mantiene por
compatibilidad; cualquier migración futura a selección por id requerirá una auditoría de unicidad y
una decisión de dominio separada.

## 20. Siguiente gate

Invocar `superpowers:writing-plans` para producir el plan S04. El plan debe ser vertical, empezar por
contrato/gateway y adaptador PHP puro, evitar toda selección real que escriba auditoría, cerrar el
piloto antes del canónico y no implementar ninguna superficie S05+.
