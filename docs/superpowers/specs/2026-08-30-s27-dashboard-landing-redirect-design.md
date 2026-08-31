---
capa: fuente
tipo: spec
estado: autorrevisado
id: S27
fecha: 2026-08-31
superficie: dashboard-landing-redirect
rutas:
  - "/dashboard"
depende_de: [T01, S04, S05, S06, S08, S11, S26]
views: []
areas: [routing, sesion, proyecto, semanas, rbac, rls, seguridad, cobertura, pruebas]
fuente: "auditoria de public/index.php, DashboardController, SessionMiddleware, ProjectAccessService, ProjectLandingService, ProjectScopeResolver, RbacService, SpaRouter, coverage-closure, coverage-debt, contratos S01-S26 y respuestas HTTP reales en shell-minimo-react, 2026-08-31"
resumen: "Contrato S27 para conservar /dashboard como transición HTTP server-side sin interfaz: valida sesión y contexto de proyecto, reutiliza una sola autoridad de landing por área/rol/semana, emite un 302 interno sin cuerpo y sale de la deuda visual sin inventar una pantalla React."
---

# S27 — Landing redirect de `/dashboard`

> **Estado:** diseño técnico autorrevisado y decision-complete. Esta spec no autoriza
> implementación, commit, push, PR, publicación, deploy, cambios RLS, schema, datos, capacidades,
> credenciales ni trabajo en `/admin/`. Su plan se escribe a continuación con
> `superpowers:writing-plans` como parte del programa aprobado de 27 specs y 27 planes.

## Relación con el programa

S27 cierra la última ruta inventariada, pero no migra una pantalla. La auditoría encontró que
`DashboardController::index()` nunca requiere una vista: toma el contexto de sesión, consulta
`ProjectLandingService`, guarda la semana y responde con `Location`. Crear un dashboard React para
cumplir una lista nominal introduciría producto nuevo y un flash que legacy no tiene.

- T01 posee autenticación, sesión, tema, foco y el shell React.
- S04 posee selección de proyecto y declara a `ProjectLandingService` como autoridad del landing.
- S05, S06, S08 y S11 poseen los cuatro posibles destinos de producto.
- S27 posee únicamente la transición `GET /dashboard` y la coherencia entre ambos caminos.
- S25 posee `/dashboard/escalamientos`; esa pantalla no forma parte de S27.
- S26 posee el laboratorio del Design System; `/dashboard` no es un escenario visual.
- `/admin/` está completamente excluido.

## Hallazgo de auditoría

El estado vigente tiene una divergencia observable:

1. `ProjectAccessService` llama
   `resolve($dbName, $permiso, $_SESSION['area'])` después de enlazar el scope.
2. `DashboardController::index()` llama `resolve($dbName, $permiso)` y omite el área.
3. El default del servicio es `Construccion`.
4. Una sesión válida de `Pre-Construccion` que visita después `/dashboard` puede entrar en lecturas
   semanales y aterrizar en `/programa-general-actualizar` o Programación Semanal, aunque la
   selección inicial la dirigió correctamente a `/programa-general`.

S27 no cambia la política de negocio. Corrige el wiring para que selección y redirect usen la misma
entrada autorizada. También sustituye el normalizador local de roles del servicio por
`RbacService::normalizeRole()`, autoridad exigida por el repositorio. No se añaden capacidades ni
se duplica la matriz en React.

### Evidencia de baseline

- `public/index.php` registra exactamente `GET /dashboard`.
- `SpaRouter::RUTAS_MIGRADAS` sólo contiene `/app`.
- `tests/test_spa_frontera.php` exige que `/dashboard` no sirva la SPA y pasa.
- Una petición sin sesión a `http://localhost:8081/dashboard` devuelve `302`, `Location: /login`,
  cuerpo vacío y directivas de no cache.
- No existe test enfocado para `ProjectLandingService` ni para el redirect.
- `coverage-closure.test.mjs` deduce “pantalla” a partir de cualquier GET y por eso mantiene
  `/dashboard` en `coverage-debt.json` pese a que no renderiza contenido.

## Resultado buscado

Una navegación a `/dashboard` debe:

1. dejar que el middleware valide autenticación, usuario activo y timeout;
2. exigir un proyecto activo y autorizado ya enlazado en `ProjectScope`;
3. validar la coherencia mínima del contexto de sesión sin confiar en query params;
4. resolver área, rol y semana mediante las autoridades server-side existentes;
5. elegir uno de cuatro destinos internos cerrados;
6. persistir la semana únicamente si el resultado es válido;
7. responder `302`, `Location` y `no-store` sin cuerpo;
8. dejar que el módulo de destino —legacy o React durante la convivencia— renderice la primera UI;
9. permanecer fuera de la SPA, la sidebar y la cobertura visual;
10. poder probar toda la decisión sin DML ni proyectos reales.

## Alcance

### Incluido

- contrato HTTP de `GET /dashboard`;
- integración con `SessionMiddleware` y el `ProjectScope` ya enlazado;
- coherencia de `project_id`, prefijo `db` y área de sesión;
- rol canónico mediante `RbacService`;
- política existente de área, semanas activas, calificación y CNC;
- conjunto cerrado de destinos y fallback defensivo;
- escritura de `$_SESSION['semana']` sólo tras un resultado válido;
- separación testeable entre decisión pura, lectura scoped y emisión HTTP;
- clasificación de `/dashboard` como transición no visual;
- pruebas puras, de contrato PHP, de frontera SPA y un smoke HTTP sin sesión;
- corte y rollback exclusivamente de código/documentación.

### Excluido

- crear una vista o página React para `/dashboard`;
- añadir `/dashboard` a la sidebar o a rutas navegables del shell;
- cambiar S25 `/dashboard/escalamientos`;
- cambiar el algoritmo de landing, semana, calificación o CNC;
- cambiar destinos, rutas públicas o capacidades;
- crear API o esquema Zod;
- modificar T01 o la apariencia de los módulos destino;
- manifiestos, goldens o screenshots para una respuesta sin documento;
- RLS, `ProjectScope`, `ProjectScopeResolver`, `ProjectSqlGuard` o reglas de datos;
- DDL, DML, selección real de proyectos, logs de actividad de prueba o fixtures persistentes;
- retirar vistas/assets de otro módulo;
- cualquier archivo, ruta o dependencia de `/admin/`.

## Decisiones cerradas

### D-S27-01 — Es una transición, no una pantalla

`/dashboard` permanece server-side. El navegador nunca monta React para decidir adónde ir y no se
crea una pantalla vacía. La URL es un alias de decisión que termina en una ruta canónica.

### D-S27-02 — Se conserva `302`

La petición es `GET` y no muta negocio. Se conserva el `302` legacy en vez de introducir `303`,
`307` o `308`. El único estado de sesión escrito es la semana ya calculada por la política vigente.

### D-S27-03 — Una sola autoridad de landing

`ProjectLandingService` sigue siendo el único orquestador de área, metadatos semanales,
calificación y destino. `ProjectAccessService` y S27 lo llaman con la misma área y rol canónicos.

### D-S27-04 — Contexto autorizado antes de leer semanas

El middleware debe haber enlazado un `ProjectScope`. El rol sale de ese scope. La sesión aporta el
prefijo y el área porque son campos establecidos por la selección server-side, pero se validan:
`project_id` coincide con el scope, el prefijo tiene formato seguro y resuelve el mismo proyecto, y
el área pertenece al conjunto cerrado.

La ausencia histórica de `area` conserva el default `Construccion` para sesiones legacy todavía
válidas. Un valor presente pero extraño no se convierte silenciosamente: vuelve a `/proyectos`.

### D-S27-05 — Rol canónico del repositorio

Se elimina la tabla local `P → D`, `U/vacío → V` como autoridad. El servicio usa
`RbacService::normalizeRole()`, que ya conoce todos los alias canónicos. Esto no concede capacidad:
sólo evita que selección y redirect interpreten un mismo rol de forma distinta.

### D-S27-06 — Destino cerrado

El resultado sólo puede apuntar a:

| Destino | Propietario | Motivo |
|---|---|---|
| `/programa-general` | S05 | Pre‑Construcción |
| `/programa-general-actualizar` | S06 | Construcción sin semanas activas |
| `/programacion-semanal` | S08 | semana preferida para roles no CIC |
| `/programacion-semanal/cic` | S11 | semana preferida para G, S o SG |

`/proyectos` es fallback defensivo del adaptador, no un resultado de negocio del servicio. Cualquier
valor vacío o fuera del conjunto se rechaza antes de emitir `Location`. No existe `next`,
`returnTo` ni redirect definido por el cliente.

### D-S27-07 — La semana sólo cambia con una decisión válida

Un landing válido escribe su semana entera, incluso `0`. Si el contexto o el resultado falla, S27 no
arrastra ni inventa una semana: redirige a proyectos y deja que S04 establezca un contexto nuevo.

### D-S27-08 — No hay contrato visual

No existe HTML intermedio que pueda tener tema, breakpoint, overflow, foco o contraste. Oscuro,
claro, desktop, tablet, móvil y 200% pertenecen a la primera pantalla de destino. Crear un golden de
una respuesta `302` falsificaría cobertura.

### D-S27-09 — No hay API ni Zod

La decisión necesita sesión server-side y ya puede responder con HTTP nativo. Una API obligaría a
montar un loader React sólo para volver a navegar, expondría metadatos internos y añadiría un salto
de red. La regla “endpoint nuevo → Zod + contrato PHP” no se activa porque no se crea endpoint.

### D-S27-10 — Error de lectura conserva el fallback legacy

Si fallan las lecturas semanales, el servicio conserva la semántica existente de “sin semanas
activas” y dirige a actualizar cronograma. No se inventa una pantalla de error ni se filtra detalle
SQL. Este comportamiento se caracteriza para que un cambio futuro sea explícito.

## Contrato HTTP

### Request

| Campo | Contrato |
|---|---|
| método | `GET` |
| path | `/dashboard` exacto |
| autenticación | sesión privada validada por `SessionMiddleware` |
| proyecto | `ProjectScope` ya enlazado |
| query | ignorada para la decisión |
| body | no usado |
| CSRF | no aplica: no existe mutación de negocio |
| respuesta exitosa | `302` + `Location` interna + cuerpo vacío |
| cache | `Cache-Control` contiene `no-store` |

### Matriz previa al landing

| Condición | Autoridad | Resultado |
|---|---|---|
| sin sesión | middleware/controlador defensivo | `/login` |
| usuario inactivo | middleware | `/login?inactive=1` |
| timeout | middleware | `/login?timeout=1` |
| sesión obsoleta/no verificable | middleware | `/login` |
| scope ausente | acción S27 | `/proyectos` |
| project_id incoherente | acción S27 | `/proyectos` |
| db ausente/inválida/ajena | acción S27 | `/proyectos` |
| área inválida | acción S27 | `/proyectos` |
| resultado no permitido | acción S27 | `/proyectos` |
| resultado válido | acción S27 | destino cerrado y semana |

Los redirects de middleware ocurren antes del controlador. La rama defensiva de login del
controlador existe para invocaciones directas/tests y no reemplaza al middleware.

## Contrato de decisión del landing

### Área

| Área | Lecturas semanales | Destino | Semana | Razón interna |
|---|---:|---|---:|---|
| Pre-Construccion | no | `/programa-general` | 0 | `pre-construccion` |
| Construccion sin semanas | sí | `/programa-general-actualizar` | 0 | `no-active-weeks` |
| Construccion con semanas | sí | según rol | preferida | según selección |

### Semana preferida

Sea:

- `O`: mayor semana activa no confirmada;
- `P`: mayor semana confirmada con calificación o CNC pendiente;
- `M`: mayor semana activa.

La decisión es:

1. si `O` existe y `P` no existe o `O >= P`, elegir `O`;
2. si `P` existe, elegir `P`;
3. en cualquier otro caso, elegir `M`.

Los empates favorecen la semana abierta, igual que hoy. Todas las semanas se ordenan
descendentemente antes de elegir.

### Pendiente de calificación/CNC

Una semana confirmada es pendiente cuando alguna fila activa:

- clasifica como `cal-sin-calificar` para fase `calificacion`; o
- tiene `Compromiso > 0` y `Ejecutado_Real + 0.0001 < Compromiso`, y además
  `Categoria_CNC` o `CNC` está vacío.

Valores no numéricos, compromiso no positivo o ejecución que alcanza el compromiso no exigen CNC.
La clasificación y las conversiones siguen delegadas a `LpsService`.

### Destino por rol con semanas activas

| Rol canónico | Destino |
|---|---|
| G, S, SG | `/programacion-semanal/cic` |
| C | `/programacion-semanal` |
| cualquier otro canónico | `/programacion-semanal` |

La decisión no es una autorización de destino. Cada ruta conserva su propio middleware y
capacidades. S27 sólo reproduce el landing histórico.

## Forma del resultado interno

`ProjectLandingService::resolve()` conserva su array compatible:

```php
[
    'route' => '/programacion-semanal',
    'module' => 'programacion-semanal',
    'week' => 37,
    'hasActiveWeeks' => true,
    'maxActiveWeek' => 38,
    'maxConfirmedWeek' => 37,
    'reason' => 'highest-confirmed-week-pending-calificacion',
]
```

La frontera S27 valida tipos y destino antes de usarlo. `module` y `reason` son diagnósticos internos;
no forman payload ni cabecera.

## Arquitectura objetivo

### Piezas

1. `ProjectLandingDecision`: política pura de área, semanas, pendientes, rol y destino.
2. `ProjectLandingResult`: resultado inmutable con la forma histórica y conversión explícita a array.
3. `ProjectLandingResolver`: interfaz mínima para desacoplar el adaptador HTTP.
4. `ProjectLandingService`: lecturas scoped y orquestación; delega la elección pura y normaliza con
   `RbacService`.
5. `DashboardLandingAction`: valida sesión/scope/contexto, llama al resolver y acepta sólo destinos
   cerrados.
6. `DashboardLandingResponse`: `302`, location y mutación opcional de semana.
7. `DashboardController::index()`: obtiene el scope actual, aplica la mutación válida y emite headers
   sin cuerpo.

La separación no crea una nueva capa de negocio. Permite probar el árbol completo con arrays y
fakes, sin abrir DB ni interceptar `exit`.

### Flujo

```text
GET /dashboard
  → SessionMiddleware
      → invalidez: 302 /login[?reason]
      → ProjectScope enlazado
  → DashboardController
      → DashboardLandingAction
          → valida project_id + db + área + scope
          → ProjectLandingService
              → RbacService canonicaliza
              → lecturas queryWithProject
              → ProjectLandingDecision
          → valida destino cerrado + semana
      → escribe semana sólo si corresponde
      → 302 Location + no-store + cuerpo vacío
  → primera UI: módulo destino
```

## Integración con React

S27 no añade código al bundle. Cuando S05/S06/S08/S11 todavía sean legacy, el navegador seguirá esas
rutas legacy. Cuando cada dueño corte a React, la misma URL resolverá al shell React. El redirect no
conoce ni detecta la tecnología del destino; por eso no requiere un segundo corte.

T01 posee la restauración del tema, el foco inicial y el estado de sesión de la página destino. No
hay sidebar para “Dashboard”: los accesos siguen apuntando a módulos reales o a `/dashboard` como
alias de entrada.

## Seguridad, RLS y datos

- La autenticación ocurre antes de resolver.
- El proyecto debe existir como `ProjectScope` activo.
- El rol autorizado viene del scope y sólo se normaliza por `RbacService`.
- El prefijo se valida y debe resolver al mismo `project_id`.
- Las consultas existentes mantienen `queryWithProject`.
- La cabecera sólo admite valores de un conjunto literal.
- No se aceptan datos de redirect desde query, formulario, JSON o headers.
- No se añaden logs con sesión, SQL o credenciales.
- No se cambia ninguna pieza RLS ni su documentación de frontera.
- No se ejecuta ninguna escritura, siquiera dentro de rollback de prueba.

La escritura de `$_SESSION['semana']` es estado efímero de sesión, no DML. Debe ocurrir después de
validar el resultado.

## Accesibilidad, temas y responsive

La transición no genera árbol accesible. Por tanto:

- no hay foco, anuncio, live region o título intermedio;
- no hay color, token, densidad, hover o contraste;
- no hay layout que validar en 390, 768, 1180 o 1440;
- no hay snapshot o screenshot razonable.

La exigencia observable es negativa: ningún contenido aparece antes de la UI de destino. Esa UI sí
debe cumplir oscuro/claro, responsive y accesibilidad según su spec propietaria.

## Cobertura del Design System

`coverage-closure.test.mjs` debe distinguir rutas GET que renderizan pantallas de transiciones que
sólo redirigen. S27 añade `/dashboard` a un conjunto exacto de rutas no renderizantes dentro del
gate; no introduce un filtro general que pueda ocultar pantallas futuras.

Después:

- `pantallasReales()` no cuenta `/dashboard`;
- `coverage-debt.json` elimina sólo `/dashboard`;
- `/reportes/{tipo}` conserva su deuda y explicación;
- el máximo histórico no aumenta;
- no se crea manifiesto visual falso.

## Estrategia de pruebas

### Decisión pura

Sin DB:

- área Pre‑Construcción;
- ninguna semana;
- semana abierta, confirmada y pendientes en todas sus combinaciones;
- empate abierto/pendiente;
- estados de calificación y CNC;
- roles canónicos, alias textuales y desconocidos;
- forma y razones del resultado.

### Acción de transición

Con resolver fake y lookup de prefijo fake:

- ausencia/mismatch de scope;
- db y área inválidas;
- prefijo de otro proyecto;
- cada destino permitido;
- destino hostil o desconocido;
- mutación/no mutación de semana;
- garantía de no invocar el resolver en contexto rechazado.

### Adaptador HTTP

Un contrato PHP enfocado fija `302`, `Location`, `no-store` y cuerpo vacío sin depender de una DB
productiva. Un smoke HTTP sin cookie comprueba `/login` con seguimiento de redirects desactivado.

### Regresión

- frontera SPA;
- coverage closure;
- contratos enfocados de selección de proyecto y consumidores de `sanitizeWeek()`;
- sintaxis y análisis estático de archivos afectados;
- ausencia de diff bajo `admin/`;
- ausencia de artefactos en el checkout padre.

No se ejecuta el contrato de selección que escribe `logActivity` ni un flujo real de proyecto. Si
una prueba existente mezcla lectura con DML, S27 la omite y documenta el límite.

## Corte y rollback

No hay cambio de URL ni feature flag. El corte es atómico: clases testeables, controlador y
clasificación de cobertura entran juntos. El runtime legacy de los módulos destino permanece hasta
que su propietario lo retire.

Rollback:

1. revierte el refactor y los tests de S27;
2. restaura la clasificación documental previa sólo si vuelve el código previo;
3. no toca sesión persistida, DB, usuarios, grants ni RLS;
4. no elimina ni restaura vistas, scripts o CSS.

Una semana ya escrita por una navegación previa sigue siendo estado de sesión normal; no existe
migración que revertir.

## Riesgos y mitigaciones

| Riesgo | Mitigación |
|---|---|
| Pre‑Construcción cae en lógica semanal | área exacta entregada por ambos callers y prueba enfocada |
| open redirect | conjunto literal validado antes de `Location` |
| rol divergente | única normalización con `RbacService` |
| contexto de proyecto obsoleto | scope requerido y coherencia project_id/prefijo |
| cambio accidental del algoritmo | política pura con matriz exhaustiva |
| error DB visible | fallback existente, log interno sin detalle HTTP |
| cobertura visual inventada | clasificación explícita no-render y sin manifiesto |
| test escribe actividad | fakes/spies y exclusión documentada del contrato mutante |
| flash al montar React | ruta queda fuera de SPA y cuerpo vacío |
| alcance se expande a Admin | guard de diff exacto |

## Criterios de aceptación

- S27-AC-001: `GET /dashboard` continúa registrado como ruta privada server-side y no como prefijo SPA.
- S27-AC-002: Una petición válida a `/dashboard` termina en una respuesta `302`; no devuelve `200` intermedio.
- S27-AC-003: La respuesta de transición no renderiza PHP, React, plantilla, skeleton ni documento HTML.
- S27-AC-004: El cuerpo de la respuesta `302` es vacío.
- S27-AC-005: S27 no crea página, componente, hook, estado ni estilos frontend.
- S27-AC-006: S27 no crea endpoint API.
- S27-AC-007: Al no existir endpoint nuevo, S27 no introduce un esquema Zod artificial.
- S27-AC-008: `/dashboard` no aparece en la sidebar, navegación móvil, command palette ni breadcrumbs.
- S27-AC-009: Los query params de `/dashboard` no influyen en el destino.
- S27-AC-010: S27 no añade parámetros de ruta a `/dashboard`.
- S27-AC-011: S27 no amplía el contrato a `POST`, `PUT`, `PATCH` o `DELETE`.
- S27-AC-012: S27 no añade una ruta `HEAD` explícita ni cambia el comportamiento del router para métodos no registrados.
- S27-AC-013: La frontera `SpaRouter` sigue excluyendo `/dashboard`.
- S27-AC-014: El redirect no apunta a `/app` ni a una pantalla genérica de bienvenida.
- S27-AC-015: El redirect siempre usa una ruta relativa que empieza por una sola barra.
- S27-AC-016: El redirect nunca contiene esquema, host, doble barra inicial, backslash ni valor derivado del request.
- S27-AC-017: Las únicas salidas funcionales del landing son las cuatro rutas canónicas enumeradas por esta spec.
- S27-AC-018: `/proyectos` es la única salida defensiva cuando falta o no es coherente el contexto de proyecto.
- S27-AC-019: `/login` y sus variantes de invalidez pertenecen a `SessionMiddleware`, no a la política de landing.
- S27-AC-020: El controlador conserva una defensa a `/login` si se invoca fuera del middleware.
- S27-AC-021: Una sesión inexistente redirige a `/login` antes de resolver proyecto o semana.
- S27-AC-022: Una sesión inactiva redirige a `/login?inactive=1` antes de resolver proyecto o semana.
- S27-AC-023: Una sesión expirada redirige a `/login?timeout=1` antes de resolver proyecto o semana.
- S27-AC-024: Una sesión obsoleta o no verificable redirige a `/login` antes de resolver proyecto o semana.
- S27-AC-025: `SessionMiddleware` sigue siendo la autoridad de autenticación y timeout.
- S27-AC-026: Una transición autenticada exige un `ProjectScope` enlazado para el request.
- S27-AC-027: Sin `ProjectScope`, la transición redirige a `/proyectos` y no consulta semanas.
- S27-AC-028: El `project_id` de sesión debe coincidir con `ProjectScope::projectId()`.
- S27-AC-029: Un `project_id` ausente, no entero, no positivo o incoherente termina en `/proyectos`.
- S27-AC-030: El prefijo `db` debe ser no vacío y cumplir `^[A-Za-z0-9_]+$`.
- S27-AC-031: Un prefijo `db` inválido termina en `/proyectos` antes de llamar al resolver.
- S27-AC-032: El proyecto resuelto por el prefijo `db` debe coincidir con el proyecto del scope.
- S27-AC-033: Un prefijo válido pero perteneciente a otro proyecto termina en `/proyectos`.
- S27-AC-034: El rol usado por el landing proviene de `ProjectScope::role()`, no de un campo enviado por el navegador.
- S27-AC-035: Todo rol que entre al algoritmo se normaliza mediante `RbacService::normalizeRole()`.
- S27-AC-036: `ProjectLandingService` elimina su normalizador local de alias como autoridad paralela.
- S27-AC-037: Los alias históricos `P` y `U` conservan el resultado de la normalización canónica del repositorio.
- S27-AC-038: Los alias textuales reconocidos por `RbacCatalog::roleAliases()` reciben el mismo destino que su rol canónico.
- S27-AC-039: Un rol desconocido adopta el fallback seguro de `RbacService` y nunca amplía capacidades.
- S27-AC-040: S27 no añade, renombra ni reinterpreta roles, capacidades o rutas de capacidad.
- S27-AC-041: `Construccion` y `Pre-Construccion` son los únicos valores de área aceptados.
- S27-AC-042: `Pre-Construccion` se compara de forma exacta, sin normalización aproximada.
- S27-AC-043: Un área de sesión ausente conserva compatibilidad legacy tratándose como `Construccion`.
- S27-AC-044: Un área presente pero no permitida termina en `/proyectos`.
- S27-AC-045: El controlador entrega el área validada a `ProjectLandingService::resolve()`.
- S27-AC-046: `ProjectAccessService` y `/dashboard` consumen la misma autoridad de landing.
- S27-AC-047: Ni React ni el navegador calculan el destino a partir de rol, área, semana o proyecto.
- S27-AC-048: El request no puede suministrar `role`, `area`, `week`, `db`, `project_id` ni `route` al landing.
- S27-AC-049: Los datos internos `module` y `reason` no se exponen en la respuesta HTTP.
- S27-AC-050: El resultado del resolver se valida antes de construir la cabecera `Location`.
- S27-AC-051: El conjunto de destinos de landing es exactamente `/programa-general`, `/programa-general-actualizar`, `/programacion-semanal` y `/programacion-semanal/cic`.
- S27-AC-052: Un destino vacío, desconocido o fuera del conjunto cerrado falla a `/proyectos`.
- S27-AC-053: Un resultado inválido no modifica `$_SESSION['semana']`.
- S27-AC-054: Un resultado válido escribe en sesión una semana entera mayor o igual a cero.
- S27-AC-055: `Pre-Construccion` resuelve `/programa-general`.
- S27-AC-056: `Pre-Construccion` fija semana `0`.
- S27-AC-057: `Pre-Construccion` declara `hasActiveWeeks=false` y razón interna `pre-construccion`.
- S27-AC-058: `Pre-Construccion` no consulta tablas de semanas ni programación semanal.
- S27-AC-059: En Construcción, la ausencia de semanas activas resuelve `/programa-general-actualizar`.
- S27-AC-060: Sin semanas activas, la semana resultante es `0`.
- S27-AC-061: Sin semanas activas, el destino no varía por rol.
- S27-AC-062: Un fallo de lectura de metadatos de semanas conserva el fallback `no-active-weeks` existente.
- S27-AC-063: Los fallos de lectura se registran sin propagar SQL, credenciales o datos sensibles al navegador.
- S27-AC-064: Los números de semana no positivos se descartan del conjunto activo.
- S27-AC-065: `Semanal_Confirmada=1` es la única representación confirmada; cualquier otro valor se trata como abierto.
- S27-AC-066: `maxActiveWeek` es el máximo número positivo presente.
- S27-AC-067: `maxConfirmedWeek` es el máximo número positivo confirmado o `null`.
- S27-AC-068: Las semanas se evalúan de mayor a menor.
- S27-AC-069: `highestOpenWeek` es la semana abierta de mayor número.
- S27-AC-070: Solo una semana confirmada puede ser candidata a calificación pendiente.
- S27-AC-071: Para buscar pendientes se consideran únicamente filas activas según `LpsService::isActiveRow()`.
- S27-AC-072: El estado `cal-sin-calificar` de `classifyWeeklyState(..., 'calificacion')` marca pendiente.
- S27-AC-073: Una CNC requerida pero incompleta también marca pendiente.
- S27-AC-074: La regla CNC exige `Compromiso` y `Ejecutado_Real` numéricos válidos y compromiso positivo.
- S27-AC-075: No se exige CNC cuando `Ejecutado_Real + 0.0001 >= Compromiso`.
- S27-AC-076: Cuando el ejecutado real queda bajo el compromiso, `Categoria_CNC` o `CNC` en blanco marcan pendiente.
- S27-AC-077: `highestPendingCalificacionWeek` es la semana confirmada pendiente de mayor número.
- S27-AC-078: Si existe semana abierta y es mayor o igual que la pendiente, se elige la abierta.
- S27-AC-079: Si la semana pendiente es mayor que la abierta, se elige la pendiente.
- S27-AC-080: Si no existe abierta y sí pendiente, se elige la pendiente.
- S27-AC-081: Si no existe abierta ni pendiente, se elige `maxActiveWeek`.
- S27-AC-082: Elegir la abierta conserva razón interna `highest-open-week`.
- S27-AC-083: Elegir la pendiente conserva razón interna `highest-confirmed-week-pending-calificacion`.
- S27-AC-084: Elegir la última confirmada sin pendientes conserva razón interna `latest-week-confirmed-without-pending-or-open-activities`.
- S27-AC-085: Con semanas activas, el módulo preferido continúa siendo `programacion-semanal`.
- S27-AC-086: Los roles canónicos `G`, `S` y `SG` aterrizan en `/programacion-semanal/cic`.
- S27-AC-087: El rol canónico `C` aterriza en `/programacion-semanal`.
- S27-AC-088: Los demás roles canónicos aterrizan en `/programacion-semanal` cuando existen semanas activas.
- S27-AC-089: La rama Pre‑Construcción se evalúa antes que el enrutamiento por rol.
- S27-AC-090: La rama sin semanas activas se evalúa antes que el enrutamiento por rol.
- S27-AC-091: El fallback interno de prefijo inválido de `ProjectLandingService` conserva su comportamiento para otros callers, aunque `/dashboard` lo intercepte antes.
- S27-AC-092: La forma pública del resultado de `ProjectLandingService::resolve()` conserva `route`, `module`, `week`, `hasActiveWeeks`, `maxActiveWeek`, `maxConfirmedWeek` y `reason`.
- S27-AC-093: `sanitizeWeek()` conserva la misma política de semana preferida que `resolve()`.
- S27-AC-094: `sanitizeWeek()` devuelve semana `0` cuando el prefijo es inválido o no existen semanas activas.
- S27-AC-095: `ProjectAccessService` continúa guardando la misma semana y devolviendo el mismo route calculado.
- S27-AC-096: Los controllers de Programa General y Programación Semanal que usan `sanitizeWeek()` no cambian de contrato.
- S27-AC-097: Cada lectura operacional conserva `queryWithProject` y el `project_id` autorizado.
- S27-AC-098: S27 no introduce SQL dinámico nuevo a partir de datos de usuario.
- S27-AC-099: S27 no modifica `ProjectScope`, `ProjectScopeResolver`, `ProjectSqlGuard` ni la frontera RLS.
- S27-AC-100: S27 no modifica schema, migraciones, tablas, columnas, índices, triggers, vistas, grants, usuarios o credenciales.
- S27-AC-101: S27 no ejecuta DDL ni DML en implementación, verificación o rollback.
- S27-AC-102: Las pruebas de S27 usan decisiones sintéticas, fakes y spies; no seleccionan un proyecto real ni escriben actividad.
- S27-AC-103: No se ejecuta `tests/test_api_projects_contract.php` como parte de S27 porque su camino feliz escribe actividad.
- S27-AC-104: S27 no toca ningún archivo bajo `admin/` ni rutas `/admin/`.
- S27-AC-105: El redirect añade una directiva `Cache-Control` que incluye `no-store`.
- S27-AC-106: La respuesta no contiene mensaje flash, alerta, loader, texto oculto ni nodo enfocables.
- S27-AC-107: Al no haber documento intermedio, tema claro, tema oscuro y responsive son no aplicables a S27.
- S27-AC-108: El primer documento renderizado es el de la ruta de destino y hereda de T01 su tema y gestión de foco.
- S27-AC-109: S27 no crea manifiesto visual, escenario Playwright visual ni golden para `/dashboard`.
- S27-AC-110: El gate de cobertura clasifica explícitamente `/dashboard` como transición que no renderiza pantalla.
- S27-AC-111: `/dashboard` se retira de `coverage-debt.json` al quedar clasificado por el gate.
- S27-AC-112: `/reportes/{tipo}` permanece sin cambios y fuera de S27.
- S27-AC-113: El máximo de deuda visual disminuye sin fabricar una ficha visual.
- S27-AC-114: El bundle frontend no cambia por S27.
- S27-AC-115: `public/css/tokens.css` no cambia por S27.
- S27-AC-116: Una prueba pura cubre toda la matriz de área, rol, semanas abiertas, confirmadas y pendientes.
- S27-AC-117: Una prueba pura cubre alias canónicos, textuales, desconocidos y rol vacío.
- S27-AC-118: Una prueba pura cubre el desempate abierto `>=` pendiente.
- S27-AC-119: Una prueba pura cubre la tolerancia `0.0001` y los dos campos CNC.
- S27-AC-120: Una prueba de acción cubre ausencia de scope, incoherencia de proyecto, db inválida y área inválida.
- S27-AC-121: Una prueba de acción demuestra que el resolver no se invoca cuando el contexto es inválido.
- S27-AC-122: Una prueba de acción rechaza cada Location no permitida y no muta la semana.
- S27-AC-123: Una prueba de acción acepta cada uno de los cuatro destinos y devuelve la semana exacta.
- S27-AC-124: Una prueba de contrato del controlador fija `302`, `Location`, `no-store` y cuerpo vacío.
- S27-AC-125: Una comprobación HTTP sin sesión fija `302 /login` con redirects desactivados.
- S27-AC-126: `tests/test_spa_frontera.php` sigue demostrando que `/dashboard` no sirve la SPA.
- S27-AC-127: `tests/design-system/coverage-closure.test.mjs` pasa tras reclasificar la transición.
- S27-AC-128: Las regresiones enfocadas de `ProjectLandingService`, `ProjectAccessService` y `sanitizeWeek()` pasan.
- S27-AC-129: El análisis estático y sintáctico de cada archivo PHP nuevo o modificado pasa.
- S27-AC-130: La verificación registra los códigos de salida en líneas independientes y el SHA exacto evaluado.
- S27-AC-131: El corte mantiene la misma URL `/dashboard`; no requiere flag, alias, proxy ni cambio de enlaces.
- S27-AC-132: Durante convivencia, los destinos legacy o React se alcanzan por la misma ruta canónica.
- S27-AC-133: Tras los cortes S05, S06, S08 y S11, el redirect no necesita conocer la tecnología del destino.
- S27-AC-134: El rollback revierte únicamente clases, tests y clasificación documental; no restaura datos.
- S27-AC-135: No se borra ninguna vista, script o estilo legacy como parte de S27.
- S27-AC-136: No se crea compatibilidad temporal en JavaScript.
- S27-AC-137: El cierre documenta el contrato no visual y su relación con S04 y los módulos destino.
- S27-AC-138: El cierre confirma diff vacío bajo `admin/` y ausencia de artefactos en el checkout padre.
- S27-AC-139: No quedan decisiones de producto, negocio, estrategia o PM abiertas en S27.

## Trazabilidad

El plan `docs/superpowers/plans/2026-08-30-s27-dashboard-landing-redirect.md` debe contener una fila
por cada criterio S27-AC, asignada a una tarea y a evidencia ejecutable. La igualdad de conjuntos es
bloqueante: ningún criterio puede faltar, duplicarse o aparecer sólo en prosa.

## Decisiones pendientes

Ninguna. Cualquier propuesta futura de convertir `/dashboard` en una pantalla, cambiar el destino
por rol/área, exponer el árbol al cliente o alterar el fallback de errores es producto nuevo y exige
una spec distinta.
