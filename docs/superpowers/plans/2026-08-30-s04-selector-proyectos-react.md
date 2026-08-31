---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-08-30
areas: [arquitectura, rbac, design-system]
fuente: docs/superpowers/plans/2026-08-30-s04-selector-proyectos-react.md
resumen: "migrar /proyectos a React con paridad completa de listado, búsqueda, tarjetas, sidebar, BI autorizado, cambio de proyecto y landing contextual, preservando…"
---

# S04 Project Selector React Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development
> (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use
> checkbox (`- [ ]`) syntax for tracking.

**Goal:** migrar `/proyectos` a React con paridad completa de listado, búsqueda, tarjetas, sidebar,
BI autorizado, cambio de proyecto y landing contextual, preservando `ProjectAccessService`, RBAC y
la frontera RLS sin ejecutar una selección real en las pruebas S04.

**Architecture:** S04 adapta los dos endpoints existentes. El GET serializa los campos que VIEW-11
ya consume y la navegación BI preproyecto; el POST conserva `{name}`, vuelve a delegar toda
autorización/contexto en `ProjectAccessService` y devuelve el route calculado por
`ProjectLandingService`. React usa Zod + gateway, filtra localmente y entrega el path a una
navegación completa del shell. El piloto vive en `/app/proyectos`; el corte canónico GET/HEAD es
method-aware y VIEW-11 se retira solo después del gate.

**Tech Stack:** PHP 8.3, React 19, TypeScript 7, react-router-dom 7, Vite 8, Zod 4, Vitest 4,
Testing Library, Playwright 1.61, Axe y CSS design system AIA.

**Spec:** `docs/superpowers/specs/2026-08-30-s04-selector-proyectos-react-design.md`

## Global Constraints

- Trabajar exclusivamente en
  `/Users/felipebenitez/Developer/lps-aia/.claude/worktrees/shell-minimo-react`, rama
  `shell-minimo-react`; no usar el repositorio padre de `/Volumes/Crucial X6`.
- Ejecutar después de S01, S02 y S03. Se consumen su `BrowserRouter`, unión `Sesion`, `ErrorApi`,
  cliente, tema dark-first, host SPA, CSRF `shell_api` y mapa method-aware de `SpaRouter`.
- Revisar status/diff antes de editar y preservar cambios ajenos. No hacer refactors fuera de S04 y
  del incremento de sidebar T01 estrictamente necesario para esta superficie.
- `/admin/` queda fuera. No tocar sus rutas, controladores, vistas, CSS, usuarios ni permisos.
- No modificar RLS, schema, migraciones, grants, usuarios, credenciales, membresías, roles,
  `managementRoles()`, política de proyecto cerrado ni resolución de landing/semana.
- No ejecutar DDL/DML. Los tests S04 nunca seleccionan con éxito un proyecto real, no abren la
  puerta de desarrollo y no corren flujos que invoquen `Database::logActivity()`.
- Conservar `ProjectAccessService`, `ProjectLandingService`, `ProjectScopeResolver` y el orden de
  bind/limpieza de scope. El API es un adaptador; no mueve lógica al navegador.
- El POST acepta únicamente `{name}`. No aceptar `id`, `project_id`, `db`, prefijo, área, rol,
  semana o route desde cliente.
- Todo `fetch` productivo permanece en `frontend/src/lib/api/cliente.ts`; componentes mockean el
  gateway, nunca `fetch`.
- Los esquemas Zod son estrictos y la única fuente de tipos TypeScript.
- La mutación no tiene retry automático. Un `401` descarta sesión; un `403` exige actualización
  explícita sin reenvío.
- Oscuro es fallback; claro y oscuro conservan capacidad en `390×844`, `768×1024`, `1180×820` y
  `1440×900`.
- Solo tokens de `public/css/tokens.css`; sin colores literales, inline styles, `!important`,
  Bootstrap, jQuery, Font Awesome ni reglas unlayered nuevas.
- No regenerar ni sustituir baselines visuales sin aprobación explícita. Los candidatos permanecen
  fuera de git.
- No implementar, commitear, publicar ni desplegar durante la fase documental actual. Los commits
  de cada Task son instrucciones para una ejecución futura autorizada.

## File Structure

### Create

- `frontend/src/lib/api/esquemas/proyectos.ts` — request y respuestas Zod estrictas S04.
- `frontend/src/lib/api/esquemas/proyectos.test.ts` — matriz de forma, BI y route.
- `frontend/src/lib/api/proyectos.ts` — gateway de lista/selección.
- `frontend/src/lib/api/proyectos.test.ts` — endpoints, signal, JSON y CSRF.
- `frontend/src/shell/proyectos/filtrarProyectos.ts` y `.test.ts` — normalización y conteo puro.
- `frontend/src/shell/proyectos/TarjetaProyecto.tsx` y `.test.tsx` — tarjeta semántica.
- `frontend/src/shell/proyectos/SelectorProyectos.tsx` y `.test.tsx` — máquina de estados S04.
- `frontend/src/shell/proyectos/NavegacionSelectorProyectos.tsx` y `.test.tsx` — navegación global
  server-driven del selector.
- `frontend/src/shell/navegacion/BarraLateral.tsx` y `.test.tsx` — rail/drawer/cuenta compartidos.
- `public/css/project-selector-react.css` — composición responsive tokenizada.
- `tests/test_api_projects_pure_contract.php` — controlador con fakes, cero DB.
- `tests/browser/project-selector-react.spec.mjs` — funcional con APIs interceptadas.
- `tests/browser/project-selector-react.visual.mjs` — candidatos dark/light, fuera de git.

### Modify

- `src/Controllers/Api/ProjectApiController.php` — inyección, JSON estricto, campos completos y route.
- `tests/test_api_projects_contract.php` — solo HTTP seguro, sin selección válida.
- `frontend/src/shell/rutas.tsx` y `.test.tsx` — aliases S04 y precedencia con/sin proyecto.
- `frontend/src/shell/NavegacionLateral.tsx` y `.test.tsx` — consumir `BarraLateral` y «Cambiar
  proyecto» en contexto listo.
- `frontend/src/shell/SelectorProyecto.tsx` y `.test.tsx` — retirar wrapper viejo al migrar imports.
- `frontend/index.html` — hoja S04, ya con bootstrap dark-first de S01.
- `public/index.php`, `src/Core/SpaRouter.php`, `tests/test_spa_frontera.php` y
  `tests/test_spa_frontera_http.php` — piloto, corte GET/HEAD y rollback.
- `tests/test_selector_proyectos_criterio_unico.php` — apuntar al adaptador vigente sin perder
  invariantes RBAC.
- `docs/design-system/manifests/project-selector.json`, `docs/design-system/exceptions.json`,
  `docs/design-system/unlayered-delivery-inventory.json` y
  `docs/design-system/ui-groups-inventory.json` — transición VIEW-11 → React.
- `tests/design-system/project-selector-contract.test.mjs`,
  `tests/browser/project-selector-sidebar.spec.mjs`,
  `tests/browser/design-system-compliance.mjs` y
  `tests/browser/design-system-consumer-smoke.mjs` — expectativas React.
- `public/app/index.html` y `public/app/assets/index-*` — build Vite generado, nunca editado a mano.

### Preserve

- `src/Services/ProjectAccessService.php`, `ProjectLandingService.php` y
  `src/Security/DataScope/ProjectScopeResolver.php`.
- `src/Security/RbacCatalog.php`, `RbacService.php`, `BiPreviewAccessPolicy.php` y
  `src/View/Components/BiAccessComponent.php`.
- `project_members`, `general_usuarios`, `general_proyectos_procesos`, sesiones y auditoría.
- Todas las superficies S05–S27 y todo `/admin/`.

### Retire only after post-rollout gate

- `views/core/project_selector.view.php` (VIEW-11).
- `src/Controllers/Core/ProjectSelectorController.php`.
- Registro POST legacy `/proyecto/seleccionar`.
- `public/css/project-selector.css` y el JS inline de VIEW-11.

---

### Task 1: Definir contratos Zod y gateway S04

**Files:**
- Create: `frontend/src/lib/api/esquemas/proyectos.ts`
- Create: `frontend/src/lib/api/esquemas/proyectos.test.ts`
- Create: `frontend/src/lib/api/proyectos.ts`
- Create: `frontend/src/lib/api/proyectos.test.ts`
- Test: `frontend/src/lib/api/frontera.test.ts`

**Interfaces:**
- Produces: `ProyectoDisponible`, `ListaProyectos`, `SolicitudSeleccionProyecto` y
  `ResultadoSeleccionProyecto` exclusivamente con `z.infer`.
- Produces: `listarProyectos(signal?)` y `seleccionarProyecto(name, csrfToken)`.
- Consumes: `pedir()` y `ErrorApi` de S01; no exporta `fetch` ni opciones HTTP a componentes.

- [ ] **Step 1: Write failing strict-schema tests**

```ts
import { expect, test } from 'vitest';
import {
  EsquemaListaProyectos,
  EsquemaResultadoSeleccionProyecto,
  EsquemaSolicitudSeleccionProyecto,
} from './proyectos';

const project = {
  id: 73,
  name: 'Da Porto',
  area: 'Construccion',
  active: true,
  role: 'A',
  roleLabel: 'Administrador',
};

test('lista exige tarjeta completa y BI coherente', () => {
  expect(EsquemaListaProyectos.parse({
    projects: [project], navigation: { bi: { visible: true, href: '/bi/control-tower' } },
  }).projects[0]).toEqual(project);
  expect(EsquemaListaProyectos.safeParse({
    projects: [{ ...project, db: 'da_porto' }],
    navigation: { bi: { visible: false, href: null } },
  }).success).toBe(false);
  expect(EsquemaListaProyectos.safeParse({
    projects: [project], navigation: { bi: { visible: false, href: '/bi/control-tower' } },
  }).success).toBe(false);
});

test('selección solo acepta name y route interno seguro', () => {
  expect(EsquemaSolicitudSeleccionProyecto.parse({ name: '  Da Porto  ' }))
    .toEqual({ name: 'Da Porto' });
  expect(EsquemaSolicitudSeleccionProyecto.safeParse({ name: 'Da Porto', project_id: 73 }).success)
    .toBe(false);
  expect(EsquemaResultadoSeleccionProyecto.safeParse({
    success: true, message: null, route: '/programacion-semanal',
  }).success).toBe(true);
  expect(EsquemaResultadoSeleccionProyecto.safeParse({
    success: true, message: null, route: '//evil.example',
  }).success).toBe(false);
  expect(EsquemaResultadoSeleccionProyecto.safeParse({
    success: false, message: 'No se pudo acceder al proyecto seleccionado.', route: null,
  }).success).toBe(true);
});
```

En `proyectos.test.ts`, mockear `pedir` y comprobar el endpoint/signal del GET y el body/CSRF del
POST; confirmar que el request se parsea antes de llamar al cliente.

- [ ] **Step 2: Run focused tests and confirm RED**

```bash
npm --prefix frontend test -- src/lib/api/esquemas/proyectos.test.ts src/lib/api/proyectos.test.ts
```

Expected: FAIL porque los dos archivos productivos S04 no existen.

- [ ] **Step 3: Implement schemas and gateway exactly**

```ts
import { z } from 'zod';

export const EsquemaRutaInterna = z.string().regex(
  /^\/(?!\/)[^\u0000-\u001f\u007f\\]+$/,
  'route debe ser un path interno seguro',
);

export const EsquemaProyectoDisponible = z.object({
  id: z.number().int().positive(),
  name: z.string().trim().min(1),
  area: z.enum(['Construccion', 'Pre-Construccion']),
  active: z.literal(true),
  role: z.string().trim().min(1),
  roleLabel: z.string().trim().min(1),
}).strict();

const EsquemaNavegacionBi = z.discriminatedUnion('visible', [
  z.object({ visible: z.literal(true), href: EsquemaRutaInterna }).strict(),
  z.object({ visible: z.literal(false), href: z.null() }).strict(),
]);

export const EsquemaListaProyectos = z.object({
  projects: z.array(EsquemaProyectoDisponible),
  navigation: z.object({ bi: EsquemaNavegacionBi }).strict(),
}).strict();

export const EsquemaSolicitudSeleccionProyecto = z.object({
  name: z.string().trim().min(1),
}).strict();

export const MENSAJE_RECHAZO_PROYECTO = 'No se pudo acceder al proyecto seleccionado.';
export const EsquemaResultadoSeleccionProyecto = z.discriminatedUnion('success', [
  z.object({ success: z.literal(true), message: z.null(), route: EsquemaRutaInterna }).strict(),
  z.object({
    success: z.literal(false), message: z.literal(MENSAJE_RECHAZO_PROYECTO), route: z.null(),
  }).strict(),
]);

export type ProyectoDisponible = z.infer<typeof EsquemaProyectoDisponible>;
export type ListaProyectos = z.infer<typeof EsquemaListaProyectos>;
export type SolicitudSeleccionProyecto = z.infer<typeof EsquemaSolicitudSeleccionProyecto>;
export type ResultadoSeleccionProyecto = z.infer<typeof EsquemaResultadoSeleccionProyecto>;
```

```ts
export async function listarProyectos(signal?: AbortSignal): Promise<ListaProyectos> {
  return pedir('/api/proyectos', EsquemaListaProyectos, { signal });
}

export async function seleccionarProyecto(
  name: string,
  csrfToken: string,
): Promise<ResultadoSeleccionProyecto> {
  const body = EsquemaSolicitudSeleccionProyecto.parse({ name });
  return pedir('/api/proyectos/seleccionar', EsquemaResultadoSeleccionProyecto, {
    method: 'POST',
    headers: { 'X-CSRF-Token': csrfToken },
    body: JSON.stringify(body),
  });
}
```

- [ ] **Step 4: Run schemas, gateway, fetch guard and typecheck**

```bash
npm --prefix frontend test -- src/lib/api/esquemas/proyectos.test.ts src/lib/api/proyectos.test.ts src/lib/api/frontera.test.ts
npm --prefix frontend run typecheck
```

Expected: formas estrictas, endpoint/signal/CSRF exactos y guard de `fetch` PASS; TypeScript RC 0.

- [ ] **Step 5: Commit the frontend contract boundary**

```bash
git add frontend/src/lib/api/esquemas/proyectos.ts frontend/src/lib/api/esquemas/proyectos.test.ts frontend/src/lib/api/proyectos.ts frontend/src/lib/api/proyectos.test.ts
git commit -m "feat(projects): tipar contratos del selector"
```

---

### Task 2: Adaptar `ProjectApiController` con un contrato puro

**Files:**
- Modify: `src/Controllers/Api/ProjectApiController.php`
- Create: `tests/test_api_projects_pure_contract.php`

**Interfaces:**
- `__construct(?ProjectAccessService $service = null, ?callable $bodyReader = null,
  ?callable $biResolver = null)` permite fakes sin DB.
- GET produce los seis campos de tarjeta y `navigation.bi`.
- POST valida JSON/claves antes de `select()` y devuelve `route` solo en éxito.
- Paths inseguros de un colaborador roto producen `500 invalid_landing`; nunca llegan a React como
  éxito.

- [ ] **Step 1: Write the failing no-DB controller contract**

Crear un fake que omite el constructor real:

```php
final class FakeProjectAccessService extends ProjectAccessService
{
    /** @param list<array<string,mixed>> $projects */
    public function __construct(
        private array $projects,
        private array $selection,
    ) {}

    public array $selectCalls = [];

    public function listForUser(string $usuario): array
    {
        return $this->projects;
    }

    public function select(string $usuario, string $proyectoSeleccionado): array
    {
        $this->selectCalls[] = [$usuario, $proyectoSeleccionado];
        return $this->selection;
    }
}
```

Preparar `$_SESSION['usuario']='fixture'`, generar CSRF con
`CsrfTokenManager::generate('shell_api')`, capturar `ob_start()`/`http_response_code()` y probar:

```php
$projects = [[
    'ID' => 73,
    'Proyecto_Proceso' => 'Da Porto',
    'Area' => 'Construccion',
    'Activo' => 1,
    'permiso' => 'A',
    'rol_nombre' => 'Administrador',
]];
$service = new FakeProjectAccessService($projects, [
    'success' => true, 'message' => null, 'route' => '/programacion-semanal',
]);
$controller = new ProjectApiController(
    $service,
    static fn(): string => '{"name":"  Da Porto  "}',
    static fn(): array => ['visible' => true, 'href' => '/bi/control-tower'],
);
```

Comprobar forma exacta del GET; POST llama una vez con `fixture/Da Porto`; éxito contiene route;
rechazo contiene route null; JSON roto, lista, string, nombre no-string, vacío y extra `project_id`
dan 422 sin llamada; `//evil.example` como landing da 500. Probar además que un resolver BI
inconsistente (`visible=false` con href) o con `//evil.example` no se serializa como éxito. Afirmar
que ninguna respuesta contiene `Base_de_Datos`, `db`, `Acceso` o causa interna.

- [ ] **Step 2: Run the pure contract and confirm RED**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php tests/test_api_projects_pure_contract.php
```

Expected: FAIL porque el constructor no admite colaboradores y el payload todavía es laxo/incompleto.

- [ ] **Step 3: Implement injected collaborators and strict parsing**

Usar propiedades `Closure` y defaults productivos:

```php
private ProjectAccessService $projectAccess;
private \Closure $bodyReader;
private \Closure $biResolver;

public function __construct(
    ?ProjectAccessService $projectAccess = null,
    ?callable $bodyReader = null,
    ?callable $biResolver = null,
) {
    $this->projectAccess = $projectAccess ?? new ProjectAccessService();
    $this->bodyReader = \Closure::fromCallable(
        $bodyReader ?? static fn(): string => (string) file_get_contents('php://input'),
    );
    $this->biResolver = \Closure::fromCallable($biResolver ?? static function (): array {
        $visible = \App\View\Components\BiAccessComponent::canAccessAny();
        return [
            'visible' => $visible,
            'href' => $visible ? \App\View\Components\BiAccessComponent::globalUrl() : null,
        ];
    });
}
```

En GET mapear exactamente:

```php
$projects[] = [
    'id' => (int) $project['ID'],
    'name' => (string) $project['Proyecto_Proceso'],
    'area' => (string) $project['Area'],
    'active' => (int) $project['Activo'] === 1,
    'role' => (string) $project['permiso'],
    'roleLabel' => (string) $project['rol_nombre'],
];
```

Normalizar la salida del resolver BI antes de responder: `visible` debe ser booleano; `false`
fuerza `href=null`; `true` exige el mismo patrón de path interno seguro usado por landing. Un
resolver roto responde `500 invalid_navigation`, no una lista 200 que fallará después en Zod.

Para POST, `json_decode(..., true, 512, JSON_THROW_ON_ERROR)`, exigir array asociativo con
`array_keys($body) === ['name']`, valor string y `trim()!==''`; responder `422` común en otro caso.
No hacer cast de escalares. Conservar la validación CSRF antes del body. En éxito:

```php
$route = $result['route'] ?? null;
if (!is_string($route) || preg_match('~^/(?!/)[^\x00-\x1F\x7F\\\\]+$~D', $route) !== 1) {
    $this->respond([
        'success' => false,
        'code' => 'invalid_landing',
        'message' => 'No se pudo abrir el proyecto seleccionado.',
    ], 500);
    return;
}
$this->respond(['success' => true, 'message' => null, 'route' => $route]);
```

En rechazo responder el literal no enumerativo y `route => null` con HTTP 200.

- [ ] **Step 4: Run pure contract, lint and forbidden-input scan**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php tests/test_api_projects_pure_contract.php
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php -l src/Controllers/Api/ProjectApiController.php
rg -n "project_id|Base_de_Datos|dbPrefix|\['db'\]|\['area'\]|\['role'\]|\['week'\]" src/Controllers/Api/ProjectApiController.php
```

Expected: contrato/lint PASS. El scan solo puede mostrar los campos de salida auditados
`area`/`role`; nunca lectura de esos campos desde body.

- [ ] **Step 5: Commit the pure API adapter**

```bash
git add src/Controllers/Api/ProjectApiController.php tests/test_api_projects_pure_contract.php
git commit -m "feat(api): completar selector de proyectos"
```

---

### Task 3: Hacer seguro y no mutante el contrato HTTP existente

**Files:**
- Modify: `tests/test_api_projects_contract.php`
- Verify: `public/index.php`
- Verify: `tests/test_api_session_contract.php`

**Interfaces:**
- El test HTTP conserva sesión real + SELECT de membresías, pero no ejecuta selección válida.
- Toda sesión temporal se elimina en `finally`.
- Casos pre-servicio verifican `401`, `403` y `422`; el único caso que llama `select()` usa un
  nombre sintético no autorizado y no llega al logger.

- [ ] **Step 1: Rewrite the test first and make the new shape fail**

Cambiar `sesionArtificialProyectos()` para devolver cookie + path/id de sesión, añadir helper de
body raw y envolver el contrato en `try/finally`. Sustituir la selección válida por:

```php
$lista = pedirJsonProyectos("{$base}/api/proyectos", $cookie);
$primero = $lista['json']['projects'][0] ?? null;
comprobarProyecto('tarjeta HTTP completa',
    is_array($primero)
    && is_int($primero['id'] ?? null)
    && is_string($primero['name'] ?? null)
    && in_array($primero['area'] ?? null, ['Construccion', 'Pre-Construccion'], true)
    && ($primero['active'] ?? null) === true
    && is_string($primero['role'] ?? null)
    && is_string($primero['roleLabel'] ?? null)
);
comprobarProyecto('GET incluye BI coherente',
    isset($lista['json']['navigation']['bi'])
    && is_bool($lista['json']['navigation']['bi']['visible'] ?? null)
);
```

Con CSRF válido enviar `{"name":"__proyecto_no_autorizado_contrato__"}` y comprobar 200, mensaje
literal y `route=null`. Añadir body roto y `{name,project_id}` esperando 422 sin selección. Eliminar
por completo el POST con `$nombre` real.

- [ ] **Step 2: Run HTTP contract and confirm RED against the old/new boundary**

```bash
docker compose exec -T app php tests/test_api_projects_contract.php
```

Expected: FAIL si Task 2 no quedó aplicada o si la respuesta no contiene shape/validación nueva;
ningún acceso de proyecto exitoso aparece en el log.

- [ ] **Step 3: Align only routing/error details discovered by HTTP**

Mantener las rutas API existentes. Si FastRoute/middleware altera `422`, ajustar el adapter para que
el body llegue al controlador; no hacer pública la ruta ni saltar `SessionMiddleware`. El JSON de
error usa la forma común S01:

```json
{
  "success": false,
  "code": "validation_error",
  "message": "Selecciona un proyecto válido.",
  "fieldErrors": {"name": ["Selecciona un proyecto válido."]}
}
```

No añadir errores detallados del servicio.

- [ ] **Step 4: Run safe HTTP, session and criterion contracts**

```bash
docker compose exec -T app php tests/test_api_projects_contract.php
docker compose exec -T app php tests/test_api_session_contract.php
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php tests/test_selector_proyectos_criterio_unico.php
```

Expected: tres contratos PASS; el primero solo hace SELECT y escribe/elimina su archivo de sesión,
sin selección válida ni DML.

- [ ] **Step 5: Commit the safe HTTP contract**

```bash
git add tests/test_api_projects_contract.php
git commit -m "test(projects): evitar seleccion real en contrato http"
```

---

### Task 4: Implementar búsqueda y conteos como dominio puro

**Files:**
- Create: `frontend/src/shell/proyectos/filtrarProyectos.ts`
- Create: `frontend/src/shell/proyectos/filtrarProyectos.test.ts`

**Interfaces:**
- `normalizarBusqueda(value): string` hace trim, lowercase `es-CO`, NFD y retira marcas.
- `filtrarProyectos(projects, query): ProyectoDisponible[]` conserva referencia/orden cuando query
  está vacío y nunca muta la respuesta.
- `textoConteo(total, hasQuery): string` fija singular/plural observable.

- [ ] **Step 1: Write failing search/count tests**

```ts
const projects = [
  proyecto({ id: 1, name: 'Construcción Norte' }),
  proyecto({ id: 2, name: 'Ágora' }),
  proyecto({ id: 3, name: 'Da Porto' }),
];

test('trim, locale y diacríticos encuentran sin reordenar', () => {
  expect(filtrarProyectos(projects, '  CONSTRUCCION ')).toEqual([projects[0]]);
  expect(filtrarProyectos(projects, 'agora')).toEqual([projects[1]]);
  expect(filtrarProyectos(projects, '')).toEqual(projects);
  expect(projects.map(({ id }) => id)).toEqual([1, 2, 3]);
});

test('conteos conservan los cuatro textos', () => {
  expect(textoConteo(1, false)).toBe('1 proyecto disponible');
  expect(textoConteo(3, false)).toBe('3 proyectos disponibles');
  expect(textoConteo(1, true)).toBe('1 proyecto encontrado');
  expect(textoConteo(0, true)).toBe('0 proyectos encontrados');
});
```

- [ ] **Step 2: Run and confirm RED**

```bash
npm --prefix frontend test -- src/shell/proyectos/filtrarProyectos.test.ts
```

Expected: FAIL porque el módulo puro no existe.

- [ ] **Step 3: Implement deterministic normalization/filtering**

```ts
export function normalizarBusqueda(value: string): string {
  return value.trim().toLocaleLowerCase('es-CO').normalize('NFD').replace(/\p{M}/gu, '');
}

export function filtrarProyectos(
  projects: readonly ProyectoDisponible[],
  query: string,
): ProyectoDisponible[] {
  const needle = normalizarBusqueda(query);
  if (needle === '') return [...projects];
  return projects.filter(({ name }) => normalizarBusqueda(name).includes(needle));
}

export function textoConteo(total: number, hasQuery: boolean): string {
  const noun = total === 1 ? 'proyecto' : 'proyectos';
  const state = hasQuery ? (total === 1 ? 'encontrado' : 'encontrados')
    : (total === 1 ? 'disponible' : 'disponibles');
  return `${total} ${noun} ${state}`;
}
```

- [ ] **Step 4: Run focused tests and typecheck**

```bash
npm --prefix frontend test -- src/shell/proyectos/filtrarProyectos.test.ts
npm --prefix frontend run typecheck
```

Expected: búsqueda/acento/orden/conteo PASS y TypeScript RC 0.

- [ ] **Step 5: Commit search domain**

```bash
git add frontend/src/shell/proyectos/filtrarProyectos.ts frontend/src/shell/proyectos/filtrarProyectos.test.ts
git commit -m "feat(projects): filtrar y contar tarjetas"
```

---

### Task 5: Construir tarjetas y estados de lectura S04

**Files:**
- Create: `frontend/src/shell/proyectos/TarjetaProyecto.tsx`
- Create: `frontend/src/shell/proyectos/TarjetaProyecto.test.tsx`
- Create: `frontend/src/shell/proyectos/SelectorProyectos.tsx`
- Create: `frontend/src/shell/proyectos/SelectorProyectos.test.tsx`
- Modify: `frontend/src/shell/SelectorProyecto.tsx`

**Interfaces:**
- `TarjetaProyecto({project,current,busy,disabled,onSelect})` no conoce transporte.
- `SelectorProyectos({session,onOpen,onRevalidate})` carga por gateway, filtra y controla estados.
- El wrapper viejo reexporta temporalmente el componente nuevo solo hasta que Task 7 migre imports.

- [ ] **Step 1: Write failing card and read-state tests**

Mockear `listarProyectos`/`seleccionarProyecto`, nunca `fetch`. Casos mínimos:

```tsx
test('tarjeta presenta toda la metadata sin depender del color', () => {
  render(<TarjetaProyecto
    project={project} current busy={false} disabled={false} onSelect={vi.fn()}
  />);
  expect(screen.getByRole('heading', { name: 'Da Porto' })).toBeVisible();
  expect(screen.getByText('Construcción')).toBeVisible();
  expect(screen.getByText('Activo')).toBeVisible();
  expect(screen.getByText(/Rol:/)).toHaveTextContent('Rol: Administrador');
  expect(screen.getByText('Proyecto actual')).toBeVisible();
  expect(screen.getByRole('button', { name: 'Ingresar al proyecto Da Porto' })).toBeEnabled();
});

test('lista, busca, cuenta y limpia el filtro', async () => {
  vi.mocked(listarProyectos).mockResolvedValue(listaConTresProyectos);
  render(<SelectorProyectos {...props} />);
  expect(await screen.findByText('3 proyectos disponibles')).toBeVisible();
  await user.type(screen.getByRole('searchbox', { name: 'Buscar proyecto' }), 'agora');
  expect(screen.getByText('1 proyecto encontrado')).toBeVisible();
  expect(screen.queryByRole('heading', { name: 'Da Porto' })).not.toBeInTheDocument();
  await user.click(screen.getByRole('button', { name: 'Limpiar búsqueda' }));
  expect(screen.getByText('3 proyectos disponibles')).toBeVisible();
});
```

Añadir loading estable, `projects=[]`, no-results, aborto al desmontar y load error con retry. En
retry comprobar una sola nueva llamada vigente y que el error desaparece.

- [ ] **Step 2: Run component tests and confirm RED**

```bash
npm --prefix frontend test -- src/shell/proyectos/TarjetaProyecto.test.tsx src/shell/proyectos/SelectorProyectos.test.tsx
```

Expected: FAIL porque los componentes no existen.

- [ ] **Step 3: Implement cards and loading/filter states**

`SelectorProyectos` guarda `ListaProyectos|null`, query y error. Cada carga crea
`AbortController`; cleanup aborta y el catch ignora `AbortError`. Derivar resultados con `useMemo`.
La estructura esencial es:

```tsx
<main id="main-content" className="project-selector-react" tabIndex={-1}>
  <header className="project-selector-react__header">
    <div>
      <h1>Tus proyectos</h1>
      <p>Selecciona el proyecto en el que quieres trabajar.</p>
    </div>
    {data?.navigation.bi.visible && (
      <a className="aia-btn aia-btn--secondary" href={data.navigation.bi.href}>
        Control Tower
      </a>
    )}
  </header>
  {data && data.projects.length > 0 && (
    <div role="search" className="project-selector-react__search">
      <label htmlFor="project-search">Buscar proyecto</label>
      <input id="project-search" type="search" value={query} autoComplete="off"
        aria-controls="project-list" onChange={(event) => setQuery(event.currentTarget.value)} />
      {query !== '' && <button type="button" onClick={() => setQuery('')}>Limpiar búsqueda</button>}
    </div>
  )}
</main>
```

Usar `<p aria-live="polite" role="status">` para el conteo, `<ul id="project-list">`, un estado
vacío sin link admin y un no-results con botón de limpiar. Loading usa elementos con dimensiones
estables y texto «Cargando proyectos…».

- [ ] **Step 4: Run read-state tests, fetch guard and typecheck**

```bash
npm --prefix frontend test -- src/shell/proyectos src/lib/api/frontera.test.ts
npm --prefix frontend run typecheck
```

Expected: S04-UX-02…09 de lectura PASS, sin `fetch` fuera de cliente y TypeScript RC 0.

- [ ] **Step 5: Commit the read slice**

```bash
git add frontend/src/shell/proyectos frontend/src/shell/SelectorProyecto.tsx
git commit -m "feat(projects): presentar selector React"
```

---

### Task 6: Completar selección, errores y landing server-authoritative

**Files:**
- Modify: `frontend/src/shell/proyectos/SelectorProyectos.tsx`
- Modify: `frontend/src/shell/proyectos/SelectorProyectos.test.tsx`
- Modify: `frontend/src/shell/proyectos/TarjetaProyecto.tsx`
- Modify: `frontend/src/shell/proyectos/TarjetaProyecto.test.tsx`

**Interfaces:**
- `onOpen(route: string): void` es la única salida de éxito del componente.
- `onRevalidate(): Promise<void>` se usa para 401 o actualización explícita tras 403.
- Un resultado funcional `success=false` no se convierte en excepción y conserva lista/filtro.
- `ErrorApi` clasifica 401/403/422/5xx/red/contrato; ninguna rama reenvía el POST.

- [ ] **Step 1: Write failing selection/error/focus tests**

```tsx
test('éxito bloquea concurrencia y entrega exactamente el route servidor', async () => {
  let resolveSelection!: (value: ResultadoSeleccionProyecto) => void;
  vi.mocked(seleccionarProyecto).mockReturnValue(new Promise((resolve) => {
    resolveSelection = resolve;
  }));
  render(<SelectorProyectos {...props} />);
  const button = await screen.findByRole('button', { name: 'Ingresar al proyecto Da Porto' });
  await user.click(button);
  expect(screen.getByRole('button', { name: 'Abriendo Da Porto…' })).toBeDisabled();
  await user.click(screen.getByRole('button', { name: /Ingresar al proyecto Ágora/ }));
  expect(seleccionarProyecto).toHaveBeenCalledOnce();
  resolveSelection({ success: true, message: null, route: '/programacion-semanal' });
  await waitFor(() => expect(props.onOpen).toHaveBeenCalledWith('/programacion-semanal'));
});

test('rechazo no enumerativo conserva filtro y devuelve foco a la tarjeta', async () => {
  vi.mocked(seleccionarProyecto).mockResolvedValue({
    success: false,
    message: MENSAJE_RECHAZO_PROYECTO,
    route: null,
  });
  render(<SelectorProyectos {...props} />);
  await user.type(await screen.findByRole('searchbox'), 'porto');
  const button = screen.getByRole('button', { name: 'Ingresar al proyecto Da Porto' });
  await user.click(button);
  expect(await screen.findByRole('alert')).toHaveTextContent(
    'No pudimos abrir ese proyecto. Verifica tu acceso e inténtalo de nuevo.',
  );
  expect(screen.getByRole('searchbox')).toHaveValue('porto');
  await user.click(screen.getByRole('button', { name: 'Cerrar aviso' }));
  expect(button).toHaveFocus();
});
```

Añadir:

- 401 llama `onRevalidate`, oculta lista operativa mientras se resuelve y nunca `onOpen`;
- 403 muestra «Actualizar sesión»; solo el click llama `onRevalidate` y no vuelve a llamar
  `seleccionarProyecto`;
- 422, red, 5xx y contrato roto muestran copy seguro, habilitan intento manual y nunca afirman
  éxito;
- el CSRF/name exactos llegan al gateway;
- proyecto actual conserva chip y puede volver a seleccionarse.

- [ ] **Step 2: Run selection tests and confirm RED**

```bash
npm --prefix frontend test -- src/shell/proyectos/SelectorProyectos.test.tsx src/shell/proyectos/TarjetaProyecto.test.tsx
```

Expected: FAIL porque Task 5 todavía no clasifica resultados/errores ni dirige foco.

- [ ] **Step 3: Implement one-flight selection and typed recovery**

Guardar `selectingId`, `selectionError`, `originButtonRef` y `securityStale`. La rama principal:

```ts
async function selectProject(project: ProyectoDisponible, button: HTMLButtonElement) {
  if (selectingId !== null) return;
  originButtonRef.current = button;
  setSelectingId(project.id);
  setSelectionError(null);
  try {
    const result = await seleccionarProyecto(project.name, session.csrfToken);
    if (!result.success) {
      setSelectionError('No pudimos abrir ese proyecto. Verifica tu acceso e inténtalo de nuevo.');
      return;
    }
    onOpen(result.route);
  } catch (cause) {
    if (esErrorApi(cause) && cause.detail.status === 401) {
      await onRevalidate();
      return;
    }
    if (esErrorApi(cause) && cause.detail.status === 403) {
      setSecurityStale(true);
      setSelectionError('Tu sesión de seguridad cambió. Actualízala antes de volver a intentar.');
      return;
    }
    setSelectionError('No pudimos confirmar si el proyecto se abrió. Inténtalo nuevamente.');
  } finally {
    setSelectingId(null);
  }
}
```

`TarjetaProyecto.onSelect` recibe el botón desde `event.currentTarget`; el nombre accesible busy es
`Abriendo ${name}…`, usa `aria-busy` y `disabled`. Al cerrar aviso, `requestAnimationFrame()` enfoca
el botón si sigue conectado. La acción 403 ejecuta solo `await onRevalidate()` y limpia el aviso;
no llama la mutación.

- [ ] **Step 4: Run UI, gateway, typecheck and boundary tests**

```bash
npm --prefix frontend test -- src/shell/proyectos src/lib/api/proyectos.test.ts src/lib/api/frontera.test.ts
npm --prefix frontend run typecheck
```

Expected: selección única, route exacto, ramas de error y foco PASS; TypeScript RC 0.

- [ ] **Step 5: Commit selection behavior**

```bash
git add frontend/src/shell/proyectos
git commit -m "feat(projects): seleccionar con landing autorizado"
```

---

### Task 7: Integrar sidebar T01, ruta piloto y cambio de proyecto

**Files:**
- Create: `frontend/src/shell/navegacion/BarraLateral.tsx`
- Create: `frontend/src/shell/navegacion/BarraLateral.test.tsx`
- Create: `frontend/src/shell/proyectos/NavegacionSelectorProyectos.tsx`
- Create: `frontend/src/shell/proyectos/NavegacionSelectorProyectos.test.tsx`
- Modify: `frontend/src/shell/NavegacionLateral.tsx`
- Modify: `frontend/src/shell/NavegacionLateral.test.tsx`
- Modify: `frontend/src/shell/proyectos/SelectorProyectos.tsx`
- Modify: `frontend/src/shell/rutas.tsx`
- Modify: `frontend/src/shell/rutas.test.tsx`
- Delete: `frontend/src/shell/SelectorProyecto.tsx`
- Delete: `frontend/src/shell/SelectorProyecto.test.tsx`

**Interfaces:**
- `BarraLateral` recibe grupos ya preparados; no interpreta roles ni capacidades.
- `NavegacionSelectorProyectos` crea solo «Tus proyectos» y BI desde `ListaProyectos.navigation`.
- `NavegacionLateral` conserva temporalmente su mapa de proyecto de T01, pero reutiliza estructura,
  cuenta, tema y drawer; S04 no añade reglas de rol.
- `/app/proyectos` y la ruta cliente `/proyectos` prevalecen sobre `session.project`; una sesión sin
  proyecto visitando otro path se redirige al alias piloto hasta Task 10.

- [ ] **Step 1: Write failing shared-shell and route-precedence tests**

En `BarraLateral.test.tsx` cubrir:

```tsx
render(<BarraLateral
  activeId="projects"
  accountName="Ana"
  groups={[{ id: 'global', label: 'Navegación', items: [
    { id: 'projects', label: 'Tus proyectos', href: '/proyectos' },
    { id: 'bi', label: 'Control Tower - Informes', href: '/bi/control-tower' },
  ] }]}
  showChangeProject={false}
/>);
expect(screen.getByRole('link', { name: 'Tus proyectos' })).toHaveAttribute('aria-current', 'page');
expect(screen.getAllByRole('link', { current: 'page' })).toHaveLength(1);
expect(screen.queryByRole('link', { name: 'Cambiar proyecto' })).not.toBeInTheDocument();
expect(screen.getByRole('link', { name: 'Cerrar sesión' })).toHaveAttribute('href', '/logout');
expect(screen.getByRole('button', { name: /tema/i })).toBeVisible();
```

Probar drawer móvil: botón anuncia estado, abre, `Escape` cierra y devuelve foco; click en velo
cierra; al abrir mueve foco al primer link. En `NavegacionLateral.test.tsx`, con proyecto activo,
comprobar «Cambiar proyecto» → `/proyectos`.

En rutas, mockear `SelectorProyectos` y `useSesion`:

```tsx
for (const pathname of ['/app/proyectos', '/proyectos']) {
  window.history.pushState({}, '', pathname);
  mockAuthenticatedSession({ project: { id: 73, name: 'Da Porto' } });
  render(<App />);
  expect(screen.getByTestId('selector-proyectos')).toBeVisible();
  cleanup();
}
```

Añadir anónimo (S01, nunca selector), autenticado sin proyecto y path `/app` (redirect
`/app/proyectos`), loading/error global y callback success.

- [ ] **Step 2: Run shell/router tests and confirm RED**

```bash
npm --prefix frontend test -- src/shell/navegacion/BarraLateral.test.tsx src/shell/proyectos/NavegacionSelectorProyectos.test.tsx src/shell/NavegacionLateral.test.tsx src/shell/rutas.test.tsx
```

Expected: FAIL porque no existe la barra genérica ni rutas S04 explícitas.

- [ ] **Step 3: Implement generic rail/drawer and S04 routes**

Definir modelos locales de presentación, no dominio:

```ts
export type ItemBarraLateral = { id: string; label: string; href: string };
export type GrupoBarraLateral = { id: string; label: string; items: readonly ItemBarraLateral[] };
type Props = {
  activeId: string;
  accountName: string;
  context?: { primary: string; secondary?: string };
  groups: readonly GrupoBarraLateral[];
  showChangeProject: boolean;
};
```

`BarraLateral` renderiza marca a `/proyectos`, nav, un `aria-current`, `ConmutadorTema`, cuenta con
link condicional y `/logout`. Estado `open` controla toggle/velo; `useEffect` escucha `Escape`,
enfoca primer link al abrir y devuelve foco al toggle al cerrar. No escribir `document.body.style`;
aplicar/quitar clase `aia-shell-drawer-open` y CSS tokenizado en Task 8.

`NavegacionSelectorProyectos` siempre prepara «Tus proyectos» y agrega BI solo si visible:

```ts
const items = [
  { id: 'projects', label: 'Tus proyectos', href: '/proyectos' },
  ...(navigation.bi.visible
    ? [{ id: 'bi', label: 'Control Tower - Informes', href: navigation.bi.href }]
    : []),
];
```

En `rutas.tsx`, las rutas S02/S03 públicas siguen primero; después:

```tsx
<Route path="/app/proyectos" element={<RutaProyectos state={sessionState} />} />
<Route path="/proyectos" element={<RutaProyectos state={sessionState} />} />
```

`RutaProyectos` requiere `state==='authenticated'`, incluso si `project` existe. Pasa
`onOpen={(route) => window.location.assign(route)}` y `onRevalidate=recargar`. El fallback
authenticated con `project=null` usa `<Navigate replace to="/app/proyectos" />` durante piloto.
Eliminar wrapper/tests viejos una vez que no haya imports.

- [ ] **Step 4: Run all shell/frontend tests and typecheck**

```bash
npm --prefix frontend test -- src/shell src/lib/api
npm --prefix frontend run typecheck
rg -n "ocultasPorRol|role ===|role ==" frontend/src/shell/proyectos frontend/src/shell/navegacion
```

Expected: shell/routing PASS. El scan no muestra decisiones de visibilidad por rol en piezas S04.

- [ ] **Step 5: Commit shell integration**

```bash
git add frontend/src/shell
git commit -m "feat(shell): integrar selector y cambio de proyecto"
```

---

### Task 8: Entregar responsive, ambos temas y build piloto

**Files:**
- Create: `public/css/project-selector-react.css`
- Modify: `frontend/index.html`
- Modify: `frontend/src/shell/navegacion/BarraLateral.tsx`
- Modify: `frontend/src/shell/proyectos/SelectorProyectos.tsx`
- Modify: `tests/design-system/project-selector-contract.test.mjs`
- Modify generated: `public/app/index.html`
- Modify generated: `public/app/assets/index-*`

**Interfaces:**
- La hoja nueva vive en `@layer module` y solo consume `--ds-*`.
- `390/768` usan drawer/una o dos columnas; `1180/1440` rail persistente y tres columnas.
- `data-aia-theme=dark|light` no cambia estructura ni visibilidad.
- Build público referencia la hoja nueva y no carga `project-selector.css` legacy.

- [ ] **Step 1: Write failing static/design assertions**

Extender `project-selector-contract.test.mjs` para leer React + CSS:

```js
const reactCss = await readFile(new URL('../../public/css/project-selector-react.css', import.meta.url), 'utf8');
const selector = await readFile(new URL('../../frontend/src/shell/proyectos/SelectorProyectos.tsx', import.meta.url), 'utf8');
assert.match(reactCss, /@layer module/);
assert.doesNotMatch(reactCss, /#[0-9a-f]{3,8}\b|rgba?\(|hsla?\(|!important/i);
assert.doesNotMatch(selector, /style=\{|bootstrap|jquery|font-awesome/i);
for (const token of ['--ds-active-bg-page', '--ds-active-surface-raised', '--ds-active-focus-ring']) {
  assert.ok(reactCss.includes(`var(${token})`), `missing ${token}`);
}
```

Comprobar que `frontend/index.html` enlaza tokens/core, `auth-react.css` de S01 y la hoja S04 una vez,
en ese orden; todavía no tocar el manifiesto canónico legacy.

- [ ] **Step 2: Run static contract and confirm RED**

```bash
node --test tests/design-system/project-selector-contract.test.mjs
```

Expected: FAIL porque la hoja React no existe.

- [ ] **Step 3: Implement tokenized responsive composition**

Crear reglas para `.aia-shell-react`, rail, velo, header, search, list, card, chips, empty/error y
footer. Breakpoints pueden usar valores estructurales, pero tamaños/espacios/colores/radios/sombras
usan tokens. Ejemplo:

```css
@layer module {
  .project-selector-react__list {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: var(--ds-space-4);
    margin: 0;
    padding: 0;
    list-style: none;
  }

  .project-selector-react__card {
    min-width: 0;
    border: var(--ds-border-width) solid var(--ds-active-border);
    border-radius: var(--ds-radius-card);
    background: var(--ds-active-surface-raised);
    box-shadow: var(--ds-shadow-xs);
    color: var(--ds-active-text-primary);
  }

  .project-selector-react :focus-visible {
    outline: var(--ds-outline-width) solid var(--ds-active-focus-ring);
    outline-offset: var(--ds-outline-offset);
  }

  @media (min-width: 48rem) {
    .project-selector-react__list { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  }

  @media (min-width: 73.75rem) {
    .project-selector-react__list { grid-template-columns: repeat(3, minmax(0, 1fr)); }
  }

  @media (prefers-reduced-motion: reduce) {
    .aia-sidebar-react, .aia-sidebar-react__overlay { transition: none; }
  }
}
```

Targets usan `min-height: var(--ds-target-min)`. Bajo desktop el rail se posiciona fijo y main usa
el token de ancho; bajo tablet/móvil es drawer con velo, `position: fixed`, scroll interno y clase
body que bloquea solo el fondo. Header/search no desbordan a 390 px. Añadir link de la hoja en
`frontend/index.html` después del core y antes del bundle.

- [ ] **Step 4: Run design contract, frontend build and inspect generated references**

```bash
node --test tests/design-system/project-selector-contract.test.mjs
npm --prefix frontend run build
rg -n "project-selector-react.css|project-selector.css|tokens.css" frontend/index.html public/app/index.html
```

Expected: contrato/build PASS; ambos índices enlazan S04 React y el host SPA no enlaza CSS legacy.

- [ ] **Step 5: Commit responsive pilot assets**

```bash
git add public/css/project-selector-react.css frontend/index.html frontend/src/shell public/app tests/design-system/project-selector-contract.test.mjs
git commit -m "feat(projects): entregar selector responsive"
```

---

### Task 9: Verificar el piloto en navegador sin tocar datos

**Files:**
- Create: `tests/browser/project-selector-react.spec.mjs`
- Create: `tests/browser/project-selector-react.visual.mjs`
- Modify: `tests/browser/project-selector-sidebar.spec.mjs`

**Interfaces:**
- Todos los escenarios interceptan `/api/session`, `/api/proyectos` y, cuando aplica,
  `/api/proyectos/seleccionar`.
- El backend real solo sirve HTML/assets; no se usa login, dev door, cookie fixture ni POST real.
- La navegación de éxito se prueba con un route interceptado del mismo origen.
- Visual genera candidatos temporales; no actualiza manifest/hashes.

- [ ] **Step 1: Write browser behavior scenarios**

Crear fixtures literales:

```js
const session = {
  state: 'authenticated', authenticated: true, reason: null,
  user: { username: 'fixture', displayName: 'Ana', role: 'A' },
  project: { id: 73, name: 'Da Porto' },
  capabilities: {}, navigation: { bi: null }, csrfToken: 'a'.repeat(64),
};
const projects = {
  projects: [
    { id: 73, name: 'Da Porto', area: 'Construccion', active: true, role: 'A', roleLabel: 'Administrador' },
    { id: 91, name: 'Ágora', area: 'Pre-Construccion', active: true, role: 'R', roleLabel: 'Residente de Obra' },
  ],
  navigation: { bi: { visible: true, href: '/bi/control-tower' } },
};
```

Interceptar endpoints antes de `page.goto('/app/proyectos')`. Cubrir:

- una sola respuesta vigente, metadata, proyecto actual, BI y cuenta;
- búsqueda `agora`, conteo, limpiar, no-results;
- vacío;
- GET 500 y retry a 200;
- selección rechazada y 403 sin segundo POST;
- selección exitosa que devuelve `/programacion-semanal`, intercepta ese GET con una página
  «landing fixture» y espera URL exacta;
- drawer/toggle/Escape/foco en 390 y sidebar/aria-current en desktop;
- no request con `project_id`, `db`, `role`, `area`, `week` o `route` en body;
- consola sin error y `documentElement.scrollWidth-clientWidth <= 1`.

Ejecutar Axe y fallar solo por impactos `serious`/`critical`:

```js
const report = await new AxeBuilder({ page }).analyze();
expect(report.violations.filter(({ impact }) => ['serious', 'critical'].includes(impact)))
  .toEqual([]);
```

- [ ] **Step 2: Run functional pilot and confirm failures first**

```bash
npx playwright test tests/browser/project-selector-react.spec.mjs --workers=1
```

Expected: la primera corrida revela cualquier selector/foco/layout incompleto; no hace requests de
mutación al backend real.

- [ ] **Step 3: Fix only S04 behavior and add visual candidate matrix**

Corregir componentes/CSS S04 según evidencia. El visual spec itera:

```js
const scenarios = [
  ['dark', 390, 844], ['light', 390, 844],
  ['dark', 768, 1024], ['light', 768, 1024],
  ['dark', 1180, 820], ['light', 1180, 820],
  ['dark', 1440, 900], ['light', 1440, 900],
];
```

Fija `localStorage['aia-theme']`, intercepta los mismos fixtures, visita piloto y guarda
`testInfo.outputPath(...)`. No usar `toHaveScreenshot` contra golden legacy y no copiar imágenes a
`tests/browser/__screenshots__`.

- [ ] **Step 4: Run functional, visual candidates and frontend regression**

```bash
npx playwright test tests/browser/project-selector-react.spec.mjs --workers=1
npx playwright test tests/browser/project-selector-react.visual.mjs --workers=1
npm --prefix frontend test
npm --prefix frontend run typecheck
```

Expected: funcional/Axe/frontend/typecheck PASS; ocho candidatos quedan solo bajo test output para
revisión visual, sin modificación de baselines versionados.

- [ ] **Step 5: Commit behavior tests, not visual output**

```bash
git add tests/browser/project-selector-react.spec.mjs tests/browser/project-selector-react.visual.mjs tests/browser/project-selector-sidebar.spec.mjs frontend/src/shell public/css/project-selector-react.css
git commit -m "test(projects): cubrir piloto React"
```

---

### Task 10: Cortar `/proyectos`, actualizar contratos y retirar VIEW-11

**Files:**
- Modify: `src/Core/SpaRouter.php`
- Modify: `public/index.php`
- Modify: `tests/test_spa_frontera.php`
- Modify: `tests/test_spa_frontera_http.php`
- Modify: `frontend/src/shell/rutas.tsx`
- Modify: `frontend/src/shell/rutas.test.tsx`
- Modify: `tests/test_selector_proyectos_criterio_unico.php`
- Modify: `docs/design-system/manifests/project-selector.json`
- Modify: `docs/design-system/exceptions.json`
- Modify: `docs/design-system/unlayered-delivery-inventory.json`
- Modify: `docs/design-system/ui-groups-inventory.json`
- Modify: `tests/design-system/project-selector-contract.test.mjs`
- Modify: `tests/browser/project-selector-sidebar.spec.mjs`
- Modify: `tests/browser/design-system-compliance.mjs`
- Modify: `tests/browser/design-system-consumer-smoke.mjs`
- Delete after gate: `views/core/project_selector.view.php`
- Delete after gate: `src/Controllers/Core/ProjectSelectorController.php`
- Delete after gate: `public/css/project-selector.css`
- Modify generated: `public/app/index.html`
- Modify generated: `public/app/assets/index-*`

**Interfaces:**
- `SpaRouter::RUTAS_EXACTAS_MIGRADAS` añade `/proyectos`; solo GET/HEAD sirven SPA.
- El fallback autenticado sin proyecto cambia de `/app/proyectos` a `/proyectos`.
- POST legacy `/proyecto/seleccionar` se elimina solo después de piloto + rollback + aprobación
  visual; POST `/api/proyectos/seleccionar` permanece.
- Manifest S04 apunta a React/CSS nuevo, cuatro layouts y ocho escenarios aprobables.

- [ ] **Step 1: Write failing canonical/rollback/retirement tests**

En `test_spa_frontera.php`:

```php
comprobar(SpaRouter::sirveLaSpa('/proyectos', 'GET'), 'GET /proyectos debe servir SPA');
comprobar(SpaRouter::sirveLaSpa('/proyectos', 'HEAD'), 'HEAD /proyectos debe servir SPA');
comprobar(!SpaRouter::sirveLaSpa('/proyectos', 'POST'), 'POST /proyectos no debe servir SPA');
comprobar(!SpaRouter::sirveLaSpa('/api/proyectos', 'GET'), 'API no debe servir host SPA');
comprobar(!SpaRouter::coincideConMapa('/proyectos', 'GET', ['/', '/login'], ['/app']),
    'rollback sin /proyectos devuelve la ruta a PHP');
```

En HTTP comprobar GET/HEAD canónico contiene root React; POST `/proyectos` no contiene root SPA;
assets/API no son capturados. En rutas React, session sin project navega a `/proyectos`.

Actualizar el test de criterio único para leer `ProjectApiController.php` en vez del controlador
legacy y conservar aserciones sobre `ProjectAccessService`, `normalizeRole()` y dos usos de
`managementRoles()`.

- [ ] **Step 2: Run route/design tests and confirm RED before cut**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php tests/test_spa_frontera.php
docker compose exec -T app php tests/test_spa_frontera_http.php
node --test tests/design-system/project-selector-contract.test.mjs
```

Expected: canonical route/manifest still point legacy and at least esas aserciones FAIL.

- [ ] **Step 3: Promote method-aware route and perform rollback drill**

Añadir `/proyectos` a exactas migradas, no a prefijo global. El front controller conserva las rutas
API y deja de registrar GET legacy; antes de borrar VIEW-11 ejecutar el drill puro con mapa sin
`/proyectos` y confirmar que `/app/proyectos` sigue SPA mientras `/proyectos` volvería a PHP.

Cambiar el fallback React a `/proyectos`, reconstruir y ejecutar ruta/HTTP. Verificar canonical en
browser usando los mismos intercepts de Task 9:

```bash
npm --prefix frontend run build
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php tests/test_spa_frontera.php
docker compose exec -T app php tests/test_spa_frontera_http.php
npx playwright test tests/browser/project-selector-react.spec.mjs --workers=1
```

Expected: piloto y canonical PASS; rollback lógico PASS; ningún POST real de selección.

- [ ] **Step 4: After visual approval, retire legacy and run the complete no-DML gate**

Solo después de aprobar los candidatos:

1. eliminar registro `/proyecto/seleccionar`, VIEW-11, controlador core y CSS legacy;
2. actualizar manifest `sources` a React/CSS, `vendors: []`, layouts mobile/tablet/desktop y los
   escenarios dark/light aprobados;
3. eliminar la excepción/unlayered entry legacy y cambiar VIEW-11 por archivos React en inventarios;
4. adaptar los tests browser existentes a DOM React, sin login/selección real;
5. confirmar cero consumidores:

```bash
rg -n "project_selector\.view|ProjectSelectorController|/proyecto/seleccionar|project-selector\.css" public src views frontend tests docs/design-system
```

Solo deben quedar referencias históricas explícitas del spec/plan o ninguna referencia productiva.
Ejecutar:

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php tests/test_api_projects_pure_contract.php
docker compose exec -T app php tests/test_api_projects_contract.php
docker compose exec -T app php tests/test_api_session_contract.php
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php tests/test_selector_proyectos_criterio_unico.php
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php tests/test_spa_frontera.php
docker compose exec -T app php tests/test_spa_frontera_http.php
npm --prefix frontend test
npm --prefix frontend run typecheck
npm --prefix frontend run build
node --test tests/design-system/project-selector-contract.test.mjs
npx playwright test tests/browser/project-selector-react.spec.mjs tests/browser/project-selector-sidebar.spec.mjs --workers=1
git diff --check
```

No correr `tests/browser/full-app-flow.spec.mjs`, helpers de login/selección real ni el antiguo caso
HTTP exitoso: todos pueden registrar acceso y violar el gate sin DML. Expected: cada comando RC 0,
consola/red limpias y árbol de cambios limitado a S04/T01 compartido.

- [ ] **Step 5: Commit canonical cut and retirement**

```bash
git add src/Core/SpaRouter.php public/index.php tests/test_spa_frontera.php tests/test_spa_frontera_http.php frontend/src/shell frontend/index.html public/app tests/test_selector_proyectos_criterio_unico.php docs/design-system/manifests/project-selector.json docs/design-system/exceptions.json docs/design-system/unlayered-delivery-inventory.json docs/design-system/ui-groups-inventory.json tests/design-system/project-selector-contract.test.mjs tests/browser/project-selector-react.spec.mjs tests/browser/project-selector-sidebar.spec.mjs tests/browser/design-system-compliance.mjs tests/browser/design-system-consumer-smoke.mjs public/css/project-selector-react.css
git add -u views/core/project_selector.view.php src/Controllers/Core/ProjectSelectorController.php public/css/project-selector.css
git commit -m "feat(projects): cortar selector React canonico"
```

---

## Traceability Matrix

| Requirement | Planned evidence |
|---|---|
| S04-UX-01…04 | Tasks 5, 7 y 9: encabezado, metadata, active nav y proyecto actual. |
| S04-UX-05…08 | Task 4 puro + Task 5 componente + Task 9 navegador. |
| S04-UX-09…12 | Tasks 5–6 unitarias, Task 9 funcional y API Tasks 2–3. |
| S04-UX-13…15 | Task 7 navegación server-driven/cuenta y Task 9 BI visible/oculto. |
| S04-UX-16…18 | Tasks 8–9: tokens, temas, cuatro viewports, Axe, zoom y overflow. |
| S04-AC-01…02 | Tasks 7 y 10: aliases, canonical, sesión y middleware. |
| S04-AC-03…09 | Tasks 1–3: Zod, controlador puro, HTTP seguro y servicios preservados. |
| S04-AC-10…13 | Tasks 4–7 y 9: filtro, errores, descarte y BI. |
| S04-AC-14…17 | Tasks 7–9: sidebar T01, boundary fetch, temas, responsive y a11y. |
| S04-AC-18 | Tasks 2, 3, 9 y 10: fakes/intercepts y gate explícito sin DML. |
| S04-AC-19…20 | Task 10: mapa method-aware, rollback, cero consumidores y retiro post-gate. |

## Explicit Non-Goals for Execution

- No convertir selección por nombre a id.
- No modificar `ProjectAccessService` para facilitar pruebas; se prueba el adaptador con fake y se
  reutiliza la cobertura vigente del servicio.
- No crear usuario sin proyectos ni editar membresías para conseguir un fixture.
- No ejecutar un acceso válido real para «probar» el landing.
- No migrar el contenido de Programa General, Programación Semanal, CIC o BI.
- No cerrar T01 completo dentro de S04: la barra genérica es solo el incremento requerido por esta
  superficie; navegación de módulos/semana continúa en sus entregas.
- No tocar `/admin/`, RLS, datos, deploy ni publicación.
