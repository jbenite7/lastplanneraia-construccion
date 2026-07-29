# Fundación PDC v2 (isla React en lps-aia) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Montar el esqueleto andante del módulo Plan de Compras v2: SPA React+Vite+TS+AG Grid desarrollada en `plan-de-compras`, servida como "isla" dentro de lps-aia con un endpoint real (`GET /plan-compras/api/contexto`) protegido por sesión+RBAC (+CSRF listo para POSTs), verificado end-to-end con Playwright.

**Architecture:** El build de Vite produce estáticos con nombres fijos que se sincronizan a `lps-aia/public/pdc-app/` y **se commitean** (el deploy SiteGround es `git pull` en el servidor — no hay build allá). Una vista shell PHP (FastRoute; `SessionMiddleware` ya corre global) inyecta `window.__PDC_BOOTSTRAP__` (projectId, proyectoNombre, rol, CSRF, usuario) y carga el bundle. La SPA usa HashRouter y rutas absolutas same-origin (sin `apiBase` configurable en la fundación). En dev, Vite hace proxy de `/plan-compras/api` a localhost:8081 y la SPA obtiene el bootstrap por fetch.

**Tech Stack:** React 18+, Vite, TypeScript, AG Grid Community (MIT), react-router-dom (HashRouter), Vitest. Lado lps-aia: PHP 8.3, nikic/FastRoute, PDO/MySQL (`Database` singleton), tests `tests/test_*.php` autoejecutables, Playwright (`tests/browser/` + helpers `support/session.mjs`).

## Global Constraints

- **Solo AG Grid Community** (MIT). Prohibido: features Enterprise, Handsontable (tier gratis = *non-commercial-and-evaluation*).
- **SiteGround hosting compartido:** build local/CI; al servidor solo estáticos + PHP vía `git pull` desde `main`. El bundle compilado va commiteado en lps-aia.
- **Aislamiento por `project_id`** en toda query (usar `Database::queryWithProject`).
- **API nueva:** namespace `/plan-compras/api/*` (NO tocar el legacy `/api/pdc/*` ni la vista vieja `/pdc`). Envelope propio: `{"ok":true,"data":...}` | `{"ok":false,"error":{"code","message"}}` con `JSON_UNESCAPED_UNICODE`.
- **Assets del bundle en `public/pdc-app/`** — NO en `public/plan-compras/` (un directorio homónimo de la ruta `/plan-compras` la rompería) ni bajo `public/js|css/` (biome lintaría el bundle minificado). Requiere añadir `pdc-app` a la whitelist del `.htaccess` raíz (Task 5).
- **Sesión:** `SessionMiddleware::check()` ya es global en `public/index.php` — no re-implementar auth. La SPA envía `X-AIA-Expect-Json: 1` para recibir 401 JSON en vez de redirect.
- **CSRF:** form key nuevo `plan_compras_v2` (`CsrfTokenManager`); POSTs futuros validan `$_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['_csrf_token']`.
- **RBAC:** lectura = permiso existente `lps.pdc.ver` vía `RbacService->can()`.
- **Design system:** el shell carga `/css/tokens.css` (`--aia-*`/`--ds-*`); dark, desktop ≥1180px. Sin CDNs, sin `<style>`, sin `style=` en la vista (contrato `DESIGN.md`). La adopción del entrypoint completo `aia-design-system.css` + manifiesto se decide cuando empiece la UI real (ver Riesgos).
- **Idioma:** dominio/comentarios/docs en español; identificadores en inglés idiomático. **TypeScript** en la SPA. Sin PHPUnit: tests PHP autoejecutables.
- Dos repos, rama `pdc-v2-fundacion` en ambos: Tasks 1–4 y 8a en `/Volumes/Crucial X6/Developer/plan-de-compras`; Tasks 5–9 en `/Volumes/Crucial X6/Developer/lps-aia`.

---

## File Structure

**Repo `plan-de-compras` (scaffold nuevo en la raíz):**

```
package.json, vite.config.ts, tsconfig.json, index.html   # scaffold Vite
src/main.tsx                  # entry
src/App.tsx                   # HashRouter + shell dark
src/styles.css                # fallbacks de tokens + layout
src/lib/types.ts              # Bootstrap, ApiResult<T>, Contexto
src/lib/bootstrap.ts          # getBootstrap(): inyectado (prod) o fetch (dev)
src/lib/api.ts                # apiGet/apiPost: envelope, CSRF, X-AIA-Expect-Json, 401
src/lib/bootstrap.test.ts     # Vitest
src/lib/api.test.ts           # Vitest
src/pages/MaestroInsumos.tsx  # página inicial con AG Grid (contexto real)
scripts/sync-to-lps.sh        # build → copia dist/ a ../lps-aia/public/pdc-app/
```

**Repo `lps-aia` (glue):**

```
public/index.php                                   # Modify: +2 rutas (vista + api)
.htaccess                                          # Modify: whitelist estáticos + pdc-app
src/Controllers/Gestion/PlanComprasController.php  # Create: controller de la vista shell
views/plan-compras/app.view.php                    # Create: shell HTML de la isla
src/Controllers/Api/PlanComprasApiController.php   # Create: GET /plan-compras/api/contexto
tests/test_pdc_v2_contexto.php                     # Create: test autoejecutable
tests/browser/pdc-v2-fundacion.spec.mjs            # Create: e2e Playwright
public/pdc-app/assets/pdc.js|pdc.css               # Generated: build commiteado
```

---

### Task 1: Scaffold Vite + React + TS en `plan-de-compras`

**Files:**
- Create: `package.json`, `vite.config.ts`, `tsconfig.json`, `index.html`, `src/main.tsx`, `src/App.tsx`, `src/styles.css`
- Modify: `.gitignore`

**Interfaces:**
- Produces: `npm run dev` (proxy `/plan-compras/api`→localhost:8081), `npm run build` (salida `dist/` con nombres fijos `assets/pdc.js`, `assets/pdc.css`, base `/pdc-app/`), `npm run test` (Vitest).

- [ ] **Step 1: Crear branch e instalar dependencias**

```bash
cd "/Volumes/Crucial X6/Developer/plan-de-compras"
git checkout -b pdc-v2-fundacion
npm init -y
npm install react react-dom react-router-dom ag-grid-community ag-grid-react
npm install -D vite @vitejs/plugin-react typescript @types/react @types/react-dom vitest
```

Expected: deps instaladas sin errores de peers (React 18/19, AG Grid v33+, Vite 5+).

- [ ] **Step 2: Escribir `vite.config.ts`**

```ts
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// base '/pdc-app/': los assets viven en lps-aia/public/pdc-app/ (nombre distinto
// de la ruta /plan-compras para que Apache no sirva el directorio en vez de rutear).
// Nombres de salida FIJOS (sin hash): el shell PHP los referencia directo y
// cache-busted con ?v=filemtime (no hay manifest que leer en SiteGround).
export default defineConfig({
  plugins: [react()],
  base: '/pdc-app/',
  build: {
    outDir: 'dist',
    rollupOptions: {
      output: {
        entryFileNames: 'assets/pdc.js',
        chunkFileNames: 'assets/chunk-[name].js',
        assetFileNames: 'assets/pdc.[ext]',
      },
    },
  },
  server: {
    proxy: {
      // En dev la API vive en el Docker de lps-aia. Las cookies de sesión de
      // localhost:8081 llegan igual (las cookies ignoran el puerto).
      '/plan-compras/api': 'http://localhost:8081',
    },
  },
  test: {
    environment: 'node',
  },
})
```

- [ ] **Step 3: Escribir `tsconfig.json`**

```json
{
  "compilerOptions": {
    "target": "ES2020",
    "lib": ["ES2020", "DOM", "DOM.Iterable"],
    "module": "ESNext",
    "moduleResolution": "bundler",
    "jsx": "react-jsx",
    "strict": true,
    "noEmit": true,
    "skipLibCheck": true
  },
  "include": ["src", "vite.config.ts"]
}
```

- [ ] **Step 4: Escribir `index.html`, `src/main.tsx`, `src/App.tsx`, `src/styles.css`**

`index.html` (solo dev; en prod el shell PHP cumple este rol):

```html
<!doctype html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Plan de Compras — AIA (dev)</title>
  </head>
  <body>
    <div id="root"></div>
    <script type="module" src="/src/main.tsx"></script>
  </body>
</html>
```

`src/main.tsx`:

```tsx
import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import App from './App'
import './styles.css'

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <App />
  </StrictMode>,
)
```

`src/App.tsx` (mínimo; la página real llega en Task 4):

```tsx
import { HashRouter, Routes, Route, Navigate } from 'react-router-dom'

export default function App() {
  return (
    <HashRouter>
      <div className="pdc-shell">
        <Routes>
          <Route path="/" element={<Navigate to="/maestro" replace />} />
          <Route path="/maestro" element={<main>Plan de Compras v2</main>} />
        </Routes>
      </div>
    </HashRouter>
  )
}
```

`src/styles.css`:

```css
:root {
  /* Fallbacks para `npm run dev` fuera de lps-aia. En prod manda tokens.css. */
  --aia-green-primary: #1a5633;
  --aia-green-dark: #1a3c2a;
  --ds-font-body: "Inter", system-ui, sans-serif;
}

.pdc-shell {
  min-height: 100vh;
  padding: 16px;
  background: #1c1c1e; /* dark: contrato design system lps-aia */
  color: #f4f1ea;
  font-family: var(--ds-font-body);
}
```

- [ ] **Step 5: Dejar exactamente estos scripts en `package.json`**

```json
{
  "scripts": {
    "dev": "vite",
    "build": "tsc && vite build",
    "test": "vitest run",
    "sync": "bash scripts/sync-to-lps.sh"
  }
}
```

- [ ] **Step 6: Ampliar `.gitignore`** (añadir al final)

```
node_modules/
dist/
```

- [ ] **Step 7: Verificar build**

```bash
npm run build && ls dist/assets/
```

Expected: compila sin errores; `dist/assets/` contiene `pdc.js`.

- [ ] **Step 8: Commit**

```bash
git add -A && git commit -m "feat(pdc): scaffold Vite+React+TS con salida de nombres fijos para lps-aia"
```

---

### Task 2: Tipos + `bootstrap.ts` (TDD)

**Files:**
- Create: `src/lib/types.ts`, `src/lib/bootstrap.ts`
- Test: `src/lib/bootstrap.test.ts`

**Interfaces:**
- Produces:
  - `type Bootstrap = { projectId: number; proyectoNombre: string; rol: string; csrfToken: string; usuario: string }`
  - `type ApiResult<T> = { ok: true; data: T } | { ok: false; error: { code: string; message: string } }`
  - `type Contexto = { projectId: number; proyectoNombre: string; usuario: string; rol: string; csrfToken: string }`
  - `getBootstrap(): Promise<Bootstrap>` — usa `window.__PDC_BOOTSTRAP__` si existe (prod); si no, `GET /plan-compras/api/contexto` (dev). Cachea.
  - `__resetBootstrapForTests(): void`

- [ ] **Step 1: Escribir `src/lib/types.ts`**

```ts
export type Bootstrap = {
  projectId: number
  proyectoNombre: string
  rol: string
  csrfToken: string
  usuario: string
}

export type ApiError = { code: string; message: string }

export type ApiResult<T> = { ok: true; data: T } | { ok: false; error: ApiError }

// Payload de GET /plan-compras/api/contexto (contrato con lps-aia, Task 7)
export type Contexto = {
  projectId: number
  proyectoNombre: string
  usuario: string
  rol: string
  csrfToken: string
}
```

- [ ] **Step 2: Escribir el test que falla — `src/lib/bootstrap.test.ts`**

```ts
import { afterEach, describe, expect, it, vi } from 'vitest'
import { getBootstrap, __resetBootstrapForTests } from './bootstrap'

afterEach(() => {
  __resetBootstrapForTests()
  delete (globalThis as Record<string, unknown>).__PDC_BOOTSTRAP__
  vi.unstubAllGlobals()
})

const contextoOk = {
  ok: true,
  data: { projectId: 3, proyectoNombre: 'DAPORTO', usuario: 'pipe', rol: 'D', csrfToken: 'tok-2' },
}

describe('getBootstrap', () => {
  it('usa window.__PDC_BOOTSTRAP__ cuando el shell PHP lo inyecta', async () => {
    ;(globalThis as Record<string, unknown>).__PDC_BOOTSTRAP__ = {
      projectId: 7, proyectoNombre: 'DAPORTO', rol: 'D', csrfToken: 'tok-1', usuario: 'pipe',
    }
    const b = await getBootstrap()
    expect(b.projectId).toBe(7)
    expect(b.csrfToken).toBe('tok-1')
  })

  it('en dev (sin inyección) obtiene el contexto por fetch con X-AIA-Expect-Json', async () => {
    const fetchMock = vi.fn().mockResolvedValue({ ok: true, status: 200, json: async () => contextoOk })
    vi.stubGlobal('fetch', fetchMock)
    const b = await getBootstrap()
    const [url, init] = fetchMock.mock.calls[0]
    expect(url).toBe('/plan-compras/api/contexto')
    expect(init.headers['X-AIA-Expect-Json']).toBe('1')
    expect(b).toEqual({ projectId: 3, proyectoNombre: 'DAPORTO', rol: 'D', csrfToken: 'tok-2', usuario: 'pipe' })
  })

  it('cachea el resultado (no repite el fetch)', async () => {
    const fetchMock = vi.fn().mockResolvedValue({ ok: true, status: 200, json: async () => contextoOk })
    vi.stubGlobal('fetch', fetchMock)
    await getBootstrap()
    await getBootstrap()
    expect(fetchMock).toHaveBeenCalledTimes(1)
  })

  it('lanza error claro si el contexto responde envelope de error', async () => {
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue({
      ok: true, status: 200,
      json: async () => ({ ok: false, error: { code: 'NO_PROJECT', message: 'Selecciona un proyecto' } }),
    }))
    await expect(getBootstrap()).rejects.toThrow('Selecciona un proyecto')
  })
})
```

- [ ] **Step 3: Correr el test y verificar que falla**

```bash
npx vitest run src/lib/bootstrap.test.ts
```

Expected: FAIL — `Cannot find module './bootstrap'` (o equivalente).

- [ ] **Step 4: Implementar `src/lib/bootstrap.ts`**

```ts
import type { ApiResult, Bootstrap, Contexto } from './types'

let cached: Bootstrap | null = null
let pending: Promise<Bootstrap> | null = null

function fromInjected(raw: unknown): Bootstrap | null {
  if (!raw || typeof raw !== 'object') return null
  const r = raw as Record<string, unknown>
  if (typeof r.projectId !== 'number' || typeof r.csrfToken !== 'string') return null
  return {
    projectId: r.projectId,
    proyectoNombre: String(r.proyectoNombre ?? ''),
    rol: String(r.rol ?? ''),
    csrfToken: r.csrfToken,
    usuario: String(r.usuario ?? ''),
  }
}

async function fetchContexto(): Promise<Bootstrap> {
  const res = await fetch('/plan-compras/api/contexto', {
    credentials: 'same-origin',
    headers: { 'X-AIA-Expect-Json': '1' },
  })
  const body = (await res.json()) as ApiResult<Contexto>
  if (!body.ok) throw new Error(body.error.message)
  const c = body.data
  return { projectId: c.projectId, proyectoNombre: c.proyectoNombre, rol: c.rol, csrfToken: c.csrfToken, usuario: c.usuario }
}

export async function getBootstrap(): Promise<Bootstrap> {
  if (cached) return cached
  const injected = fromInjected((globalThis as Record<string, unknown>).__PDC_BOOTSTRAP__)
  if (injected) {
    cached = injected
    return cached
  }
  pending ??= fetchContexto()
    .then((b) => {
      cached = b
      return b
    })
    .finally(() => {
      pending = null
    })
  return pending
}

export function __resetBootstrapForTests(): void {
  cached = null
  pending = null
}
```

- [ ] **Step 5: Correr el test y verificar que pasa**

```bash
npx vitest run src/lib/bootstrap.test.ts
```

Expected: PASS (4 tests).

- [ ] **Step 6: Commit**

```bash
git add src/lib/types.ts src/lib/bootstrap.ts src/lib/bootstrap.test.ts
git commit -m "feat(pdc): bootstrap del módulo (inyección del shell o fetch de contexto en dev)"
```

---

### Task 3: Cliente API con CSRF, sesión y envelope (TDD)

**Files:**
- Create: `src/lib/api.ts`
- Test: `src/lib/api.test.ts`

**Interfaces:**
- Consumes: `getBootstrap()` (Task 2).
- Produces:
  - `apiGet<T>(path: string): Promise<T>` y `apiPost<T>(path: string, payload: unknown): Promise<T>`
  - Ambos: `credentials:'same-origin'` + header `X-AIA-Expect-Json: 1` (así `SessionMiddleware` responde 401 JSON, no redirect HTML).
  - POST añade `Content-Type: application/json` y `X-CSRF-Token` (del bootstrap).
  - `class PdcApiError extends Error { code: string }` — códigos: `SESSION_EXPIRED` (HTTP 401/419), `BAD_RESPONSE` (payload sin envelope), o el `error.code` del envelope.

- [ ] **Step 1: Escribir el test que falla — `src/lib/api.test.ts`**

```ts
import { afterEach, describe, expect, it, vi } from 'vitest'
import { apiGet, apiPost, PdcApiError } from './api'
import { __resetBootstrapForTests } from './bootstrap'

function stubBootstrap() {
  ;(globalThis as Record<string, unknown>).__PDC_BOOTSTRAP__ = {
    projectId: 7, proyectoNombre: 'DAPORTO', rol: 'D', csrfToken: 'tok-csrf', usuario: 'pipe',
  }
}

afterEach(() => {
  __resetBootstrapForTests()
  delete (globalThis as Record<string, unknown>).__PDC_BOOTSTRAP__
  vi.unstubAllGlobals()
})

describe('apiGet', () => {
  it('desenvuelve data del envelope y manda X-AIA-Expect-Json', async () => {
    stubBootstrap()
    const fetchMock = vi.fn().mockResolvedValue({
      ok: true, status: 200, json: async () => ({ ok: true, data: { x: 1 } }),
    })
    vi.stubGlobal('fetch', fetchMock)
    await expect(apiGet<{ x: number }>('/plan-compras/api/algo')).resolves.toEqual({ x: 1 })
    const [, init] = fetchMock.mock.calls[0]
    expect(init.headers['X-AIA-Expect-Json']).toBe('1')
  })

  it('lanza PdcApiError con code del envelope de error', async () => {
    stubBootstrap()
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue({
      ok: true, status: 403,
      json: async () => ({ ok: false, error: { code: 'FORBIDDEN', message: 'Sin permiso' } }),
    }))
    const err = await apiGet('/plan-compras/api/algo').catch((e) => e)
    expect(err).toBeInstanceOf(PdcApiError)
    expect((err as PdcApiError).code).toBe('FORBIDDEN')
  })

  it('mapea HTTP 401 a SESSION_EXPIRED', async () => {
    stubBootstrap()
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue({
      ok: false, status: 401, json: async () => ({ success: false, sessionExpired: true }),
    }))
    const err = await apiGet('/plan-compras/api/algo').catch((e) => e)
    expect((err as PdcApiError).code).toBe('SESSION_EXPIRED')
  })

  it('mapea payload sin envelope a BAD_RESPONSE', async () => {
    stubBootstrap()
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue({
      ok: false, status: 500, json: async () => { throw new Error('not json') },
    }))
    const err = await apiGet('/plan-compras/api/algo').catch((e) => e)
    expect((err as PdcApiError).code).toBe('BAD_RESPONSE')
  })
})

describe('apiPost', () => {
  it('envía JSON con X-CSRF-Token del bootstrap', async () => {
    stubBootstrap()
    const fetchMock = vi.fn().mockResolvedValue({
      ok: true, status: 200, json: async () => ({ ok: true, data: null }),
    })
    vi.stubGlobal('fetch', fetchMock)
    await apiPost('/plan-compras/api/algo', { a: 1 })
    const [, init] = fetchMock.mock.calls[0]
    expect(init.method).toBe('POST')
    expect(init.headers['X-CSRF-Token']).toBe('tok-csrf')
    expect(init.headers['Content-Type']).toBe('application/json')
    expect(init.body).toBe(JSON.stringify({ a: 1 }))
  })
})
```

- [ ] **Step 2: Correr el test y verificar que falla**

```bash
npx vitest run src/lib/api.test.ts
```

Expected: FAIL — `Cannot find module './api'`.

- [ ] **Step 3: Implementar `src/lib/api.ts`**

```ts
import { getBootstrap } from './bootstrap'
import type { ApiResult } from './types'

export class PdcApiError extends Error {
  code: string
  constructor(code: string, message: string) {
    super(message)
    this.code = code
  }
}

async function request<T>(path: string, init: RequestInit & { headers?: Record<string, string> }): Promise<T> {
  const res = await fetch(path, {
    credentials: 'same-origin',
    ...init,
    headers: { 'X-AIA-Expect-Json': '1', ...(init.headers ?? {}) },
  })
  if (res.status === 401 || res.status === 419) {
    throw new PdcApiError('SESSION_EXPIRED', 'La sesión expiró. Vuelve a iniciar sesión en Last Planner.')
  }
  const body = (await res.json().catch(() => null)) as ApiResult<T> | null
  if (!body || typeof (body as { ok?: unknown }).ok !== 'boolean') {
    throw new PdcApiError('BAD_RESPONSE', `Respuesta inválida del servidor (HTTP ${res.status}).`)
  }
  if (!body.ok) throw new PdcApiError(body.error.code, body.error.message)
  return body.data
}

export function apiGet<T>(path: string): Promise<T> {
  return request<T>(path, { method: 'GET' })
}

export async function apiPost<T>(path: string, payload: unknown): Promise<T> {
  const boot = await getBootstrap()
  return request<T>(path, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': boot.csrfToken,
    },
    body: JSON.stringify(payload),
  })
}
```

- [ ] **Step 4: Correr todos los tests**

```bash
npm run test
```

Expected: PASS (bootstrap 4 + api 5 = 9 tests).

- [ ] **Step 5: Commit**

```bash
git add src/lib/api.ts src/lib/api.test.ts
git commit -m "feat(pdc): cliente API con envelope, CSRF, X-AIA-Expect-Json y sesión expirada"
```

---

### Task 4: Página inicial con AG Grid mostrando el contexto

**Files:**
- Create: `src/pages/MaestroInsumos.tsx`
- Modify: `src/App.tsx`, `src/styles.css`

**Interfaces:**
- Consumes: `getBootstrap()` (Task 2), tipo `Bootstrap`.
- Produces: ruta `#/maestro` con AG Grid (tema dark aia) y `<p data-testid="pdc-contexto">` con `proyectoNombre · usuario (rol)` — lo usa el e2e (Task 9). Fila de grilla con valor `` `${projectId} — ${proyectoNombre}` ``.

- [ ] **Step 1: Escribir `src/pages/MaestroInsumos.tsx`**

```tsx
import { useEffect, useMemo, useState } from 'react'
import { AgGridReact } from 'ag-grid-react'
import { AllCommunityModule, ModuleRegistry, themeQuartz } from 'ag-grid-community'
import type { ColDef } from 'ag-grid-community'
import { getBootstrap } from '../lib/bootstrap'
import type { Bootstrap } from '../lib/types'

// AG Grid v33+: registro explícito de módulos (Community completo).
ModuleRegistry.registerModules([AllCommunityModule])

// Tema oscuro alineado al design system aia — Theming API de Community.
const pdcTheme = themeQuartz.withParams({
  backgroundColor: '#1c1c1e',
  foregroundColor: '#f4f1ea',
  accentColor: '#69b578',
  headerBackgroundColor: '#1a3c2a',
})

type Fila = { campo: string; valor: string }

export default function MaestroInsumos() {
  const [boot, setBoot] = useState<Bootstrap | null>(null)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    getBootstrap().then(setBoot).catch((e: Error) => setError(e.message))
  }, [])

  const rows: Fila[] = useMemo(() => boot ? [
    { campo: 'Proyecto', valor: `${boot.projectId} — ${boot.proyectoNombre}` },
    { campo: 'Usuario', valor: boot.usuario },
    { campo: 'Rol', valor: boot.rol },
  ] : [], [boot])

  const cols: ColDef<Fila>[] = [
    { field: 'campo', headerName: 'Campo', flex: 1 },
    { field: 'valor', headerName: 'Valor', flex: 2 },
  ]

  if (error) return <div className="pdc-error" role="alert">Error: {error}</div>

  return (
    <section className="pdc-page">
      <header className="pdc-header">
        <h1>Plan de Compras</h1>
        <p data-testid="pdc-contexto">
          {boot ? `${boot.proyectoNombre} · ${boot.usuario} (${boot.rol})` : 'Cargando contexto…'}
        </p>
      </header>
      <div style={{ height: 320 }}>
        <AgGridReact<Fila> theme={pdcTheme} rowData={rows} columnDefs={cols} />
      </div>
    </section>
  )
}
```

- [ ] **Step 2: Conectar la ruta en `src/App.tsx`**

```tsx
import { HashRouter, Routes, Route, Navigate } from 'react-router-dom'
import MaestroInsumos from './pages/MaestroInsumos'

export default function App() {
  return (
    <HashRouter>
      <div className="pdc-shell">
        <Routes>
          <Route path="/" element={<Navigate to="/maestro" replace />} />
          <Route path="/maestro" element={<MaestroInsumos />} />
        </Routes>
      </div>
    </HashRouter>
  )
}
```

- [ ] **Step 3: Añadir al final de `src/styles.css`**

```css
.pdc-page { max-width: 1180px; margin: 0 auto; }
.pdc-header h1 { font-size: 20px; margin: 0 0 4px; }
.pdc-header p { margin: 0 0 16px; opacity: 0.8; }
.pdc-error { padding: 16px; border: 1px solid #dc2626; background: #2c1618; color: #fecaca; border-radius: 8px; }
```

- [ ] **Step 4: Verificar build y tests**

```bash
npm run build && npm run test
```

Expected: build OK (`dist/assets/pdc.js` y `dist/assets/pdc.css`); 9 tests PASS.

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "feat(pdc): página inicial con AG Grid (tema dark aia) mostrando contexto del proyecto"
```

---

### Task 5: lps-aia — rutas, `.htaccess` y controller de la vista shell

**Files:**
- Modify: `public/index.php` (zona `// Gestion` ~línea 126; zona `// Api/PDC` ~línea 178)
- Modify: `.htaccess` (raíz de lps-aia, regla 0 de estáticos)
- Create: `src/Controllers/Gestion/PlanComprasController.php`

**Interfaces:**
- Consumes: `BaseController` (`requireAuth`, `authorizePermission`), `CsrfTokenManager::generate('plan_compras_v2')`, `$_SESSION['project_id'|'proyecto'|'nombreUsuario'|'usuario'|'permiso_canonico'|'permiso']`.
- Produces: ruta `GET /plan-compras` que renderiza `views/plan-compras/app.view.php` con `$bootstrapJson` (string JSON seguro) y `$assetVersion` (int). Permiso: `lps.pdc.ver`. URL de assets `/pdc-app/*` servida como estático.

- [ ] **Step 1: Crear branch en lps-aia**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia"
git checkout -b pdc-v2-fundacion
```

- [ ] **Step 2: Whitelist de estáticos en `.htaccess` raíz**

En la regla 0, añadir `pdc-app` a la alternancia (el docroot de SiteGround es la raíz del repo; sin esto `/pdc-app/*` caería al front controller y daría 404):

```apache
    # 0. Mapear rutas de FrontEnd y assets absolutos a /public en local
    RewriteCond %{REQUEST_URI} ^/(css|js|img|vendor|storage|archivosBase|pdc-app)/(.*)$ [NC]
    RewriteRule ^(.*)$ public/%{REQUEST_URI} [L]
```

*(En `public/.htaccess` no hay que tocar nada: los archivos físicos bajo `public/` ya se sirven directo por la condición `!-f`.)*

- [ ] **Step 3: Registrar rutas en `public/index.php`**

En la zona `// Gestion` (junto a `$router->get('/pdc', ...)`):

```php
$router->get('/plan-compras', [\App\Controllers\Gestion\PlanComprasController::class, 'index']);
```

En la zona de APIs (junto al bloque `// Api/PDC`):

```php
// Api/Plan de Compras v2 (isla React — namespace nuevo, no tocar /api/pdc/*)
$router->get('/plan-compras/api/contexto', [\App\Controllers\Api\PlanComprasApiController::class, 'contexto']);
```

*(No tocar `$publicRoutes`: la protección de sesión global ya cubre ambas rutas.)*

- [ ] **Step 4: Crear `src/Controllers/Gestion/PlanComprasController.php`**

```php
<?php

namespace App\Controllers\Gestion;

use App\Controllers\BaseController;
use App\Security\CsrfTokenManager;

/**
 * Shell de la isla React del Plan de Compras v2.
 * El bundle se compila en el repo hermano `plan-de-compras` (npm run sync)
 * y se sirve desde public/pdc-app/ (ver docs/superpowers/specs/ de ese repo).
 */
class PlanComprasController extends BaseController
{
    public function index(): void
    {
        $this->requireAuth();
        $this->authorizePermission('lps.pdc.ver', 'No autorizado para consultar el plan de compras.');

        $projectId = (int) ($_SESSION['project_id'] ?? 0);
        if ($projectId <= 0) {
            // Sin proyecto activo no hay contexto: volver al selector de proyectos.
            header('Location: /');
            return;
        }

        $bootstrap = [
            'projectId' => $projectId,
            'proyectoNombre' => (string) ($_SESSION['proyecto'] ?? ''),
            'usuario' => (string) ($_SESSION['nombreUsuario'] ?? ($_SESSION['usuario'] ?? '')),
            'rol' => (string) ($_SESSION['permiso_canonico'] ?? ($_SESSION['permiso'] ?? '')),
            'csrfToken' => CsrfTokenManager::generate('plan_compras_v2'),
        ];

        $bootstrapJson = json_encode(
            $bootstrap,
            JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );

        $bundlePath = PROJECT_ROOT . '/public/pdc-app/assets/pdc.js';
        $assetVersion = is_file($bundlePath) ? (int) filemtime($bundlePath) : 0;

        require PROJECT_ROOT . '/views/plan-compras/app.view.php';
    }
}
```

- [ ] **Step 5: Verificación estática**

```bash
docker compose exec app php -l src/Controllers/Gestion/PlanComprasController.php
docker compose exec app vendor/bin/phpstan analyse src/Controllers/Gestion/PlanComprasController.php --memory-limit=1G
```

Expected: `No syntax errors detected`; PHPStan sin errores nuevos. *(La vista aún no existe — no probar la ruta en navegador todavía.)*

- [ ] **Step 6: Commit**

```bash
git add .htaccess public/index.php src/Controllers/Gestion/PlanComprasController.php
git commit -m "feat(pdc-v2): ruta /plan-compras, whitelist de /pdc-app y controller shell de la isla"
```

---

### Task 6: lps-aia — vista shell `app.view.php`

**Files:**
- Create: `views/plan-compras/app.view.php`

**Interfaces:**
- Consumes: `$bootstrapJson` (string), `$assetVersion` (int) — inyectados por `PlanComprasController::index` (Task 5).
- Produces: HTML que define `window.__PDC_BOOTSTRAP__` ANTES de cargar `/pdc-app/assets/pdc.js` (type module) y `/pdc-app/assets/pdc.css`; carga `/css/tokens.css`. Sin CDNs, sin `<style>`, sin `style=` (contrato DESIGN.md).

- [ ] **Step 1: Crear `views/plan-compras/app.view.php`**

```php
<?php
/**
 * Shell de la isla React — Plan de Compras v2.
 * Variables: $bootstrapJson (JSON seguro), $assetVersion (int cache-busting).
 * El bundle NO se edita aquí: se compila en el repo `plan-de-compras`
 * (npm run sync) y llega a public/pdc-app/.
 */
?>
<!DOCTYPE html>
<html lang="es" data-aia-theme="dark">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Plan de Compras — Last Planner AIA</title>
	<link rel="icon" href="/favicon.ico">
	<link rel="stylesheet" href="/css/tokens.css?v=<?php echo (int) $assetVersion; ?>">
	<link rel="stylesheet" href="/pdc-app/assets/pdc.css?v=<?php echo (int) $assetVersion; ?>">
</head>
<body>
	<div id="root"></div>
	<script>
		window.__PDC_BOOTSTRAP__ = <?php echo $bootstrapJson; ?>;
	</script>
	<script type="module" src="/pdc-app/assets/pdc.js?v=<?php echo (int) $assetVersion; ?>"></script>
</body>
</html>
```

- [ ] **Step 2: Verificación de sintaxis**

```bash
docker compose exec app php -l views/plan-compras/app.view.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 3: Commit**

```bash
git add views/plan-compras/app.view.php
git commit -m "feat(pdc-v2): vista shell que monta la isla React con bootstrap inyectado"
```

---

### Task 7: lps-aia — endpoint `GET /plan-compras/api/contexto` (TDD estilo lps-aia)

**Files:**
- Create: `src/Controllers/Api/PlanComprasApiController.php`
- Test: `tests/test_pdc_v2_contexto.php`

**Interfaces:**
- Consumes: `Database::getInstance()`, `\App\Security\RbacService->can('lps.pdc.ver')`, `CsrfTokenManager::generate('plan_compras_v2')`, `$_SESSION`.
- Produces: envelope del módulo:
  - OK: `{"ok":true,"data":{"projectId":int,"proyectoNombre":string,"usuario":string,"rol":string,"csrfToken":string}}`
  - Error: `{"ok":false,"error":{"code":"NO_PROJECT"|"FORBIDDEN","message":string}}` (HTTP 409 / 403)
  - Métodos privados `ok(array $data)` / `fail(string $code, string $message, int $status)` reutilizables por endpoints futuros. **Sin `exit`** (retornan) para ser testeables.

- [ ] **Step 1: Escribir el test que falla — `tests/test_pdc_v2_contexto.php`**

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Controllers\Api\PlanComprasApiController;
use App\Security\RbacService;

$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if ($condition) {
        fwrite(STDOUT, "PASS: {$message}\n");
        return;
    }
    $failures[] = $message;
    fwrite(STDERR, "FAIL: {$message}\n");
};

$capture = static function (callable $fn): array {
    ob_start();
    $fn();
    $raw = (string) ob_get_clean();
    return json_decode($raw, true) ?? ['__raw' => $raw];
};

echo "=== PDC v2: GET /plan-compras/api/contexto ===\n";

// Caso 1: sesión completa de un director → envelope ok con contexto y csrf.
$_SESSION = [
    'usuario' => 'test', 'nombreUsuario' => 'Test Dir', 'permiso' => 'D',
    'permiso_canonico' => 'D', 'project_id' => 999, 'proyecto' => 'PROYECTO TEST',
];
$out = $capture(static fn () => (new PlanComprasApiController())->contexto());
$assert(($out['ok'] ?? null) === true, 'Responde envelope ok:true con sesión válida.');
$assert(($out['data']['projectId'] ?? 0) === 999, 'projectId viene de $_SESSION[project_id].');
$assert(($out['data']['proyectoNombre'] ?? '') === 'PROYECTO TEST', 'proyectoNombre viene de la sesión.');
$assert(($out['data']['rol'] ?? '') === 'D', 'rol usa permiso_canonico.');
$assert(is_string($out['data']['csrfToken'] ?? null) && strlen($out['data']['csrfToken']) === 64, 'csrfToken generado (64 hex).');

// Caso 2: segunda llamada en la misma sesión → csrfToken estable (mismo form key).
$out2 = $capture(static fn () => (new PlanComprasApiController())->contexto());
$assert(($out2['data']['csrfToken'] ?? '') === ($out['data']['csrfToken'] ?? null), 'csrfToken es estable por sesión.');

// Caso 3: sin proyecto activo → envelope de error NO_PROJECT.
$_SESSION['project_id'] = 0;
$out3 = $capture(static fn () => (new PlanComprasApiController())->contexto());
$assert(($out3['ok'] ?? null) === false, 'Sin proyecto responde ok:false.');
$assert(($out3['error']['code'] ?? '') === 'NO_PROJECT', 'Código de error NO_PROJECT.');

// Caso 4: contrato RBAC — V (visualizador) puede ver pero no editar.
$db = Database::getInstance();
$rbac = new RbacService($db);
$assert($rbac->can('lps.pdc.ver', 'V'), 'V conserva lectura del PDC (lps.pdc.ver).');
$assert(!$rbac->can('lps.pdc.editar', 'V'), 'V no recibe edición del PDC (lps.pdc.editar).');

// Caso 5: rol sin lps.pdc.ver → el endpoint responde FORBIDDEN.
// Se busca un rol real sin el permiso; si todos lo tienen, se registra PASS informativo.
$rolSinVer = null;
foreach (['C', 'V', 'S', 'G', 'SG', 'OT', 'DCV'] as $rolCandidato) {
    if (!$rbac->can('lps.pdc.ver', $rolCandidato)) {
        $rolSinVer = $rolCandidato;
        break;
    }
}
if ($rolSinVer !== null) {
    $_SESSION['project_id'] = 999;
    $_SESSION['permiso'] = $rolSinVer;
    $_SESSION['permiso_canonico'] = $rolSinVer;
    $out5 = $capture(static fn () => (new PlanComprasApiController())->contexto());
    $assert(($out5['ok'] ?? null) === false && ($out5['error']['code'] ?? '') === 'FORBIDDEN',
        "Rol {$rolSinVer} sin lps.pdc.ver recibe FORBIDDEN.");
} else {
    fwrite(STDOUT, "PASS: (informativo) todos los roles canónicos tienen lps.pdc.ver; rama FORBIDDEN cubierta por diseño.\n");
}

echo $failures === [] ? "=== OK ===\n" : '=== ' . count($failures) . " FAILED ===\n";
exit($failures === [] ? 0 : 1);
```

- [ ] **Step 2: Correr el test y verificar que falla**

```bash
docker compose exec app php tests/test_pdc_v2_contexto.php
```

Expected: FAIL — `Class "App\Controllers\Api\PlanComprasApiController" not found`.

- [ ] **Step 3: Implementar `src/Controllers/Api/PlanComprasApiController.php`**

```php
<?php

namespace App\Controllers\Api;

use App\Security\CsrfTokenManager;
use App\Security\RbacService;

/**
 * API JSON del Plan de Compras v2 (isla React).
 * Envelope propio del módulo: {"ok":true,"data":...} | {"ok":false,"error":{code,message}}.
 * La sesión ya está garantizada por SessionMiddleware global (public/index.php).
 */
class PlanComprasApiController
{
    private $db;

    public function __construct()
    {
        $this->db = \Database::getInstance();
    }

    /** GET /plan-compras/api/contexto */
    public function contexto(): void
    {
        if (!$this->can('lps.pdc.ver')) {
            $this->fail('FORBIDDEN', 'No autorizado para consultar el plan de compras.', 403);
            return;
        }

        $projectId = (int) ($_SESSION['project_id'] ?? 0);
        if ($projectId <= 0) {
            $this->fail('NO_PROJECT', 'No hay proyecto activo. Selecciona un proyecto.', 409);
            return;
        }

        $this->ok([
            'projectId' => $projectId,
            'proyectoNombre' => (string) ($_SESSION['proyecto'] ?? ''),
            'usuario' => (string) ($_SESSION['nombreUsuario'] ?? ($_SESSION['usuario'] ?? '')),
            'rol' => (string) ($_SESSION['permiso_canonico'] ?? ($_SESSION['permiso'] ?? '')),
            'csrfToken' => CsrfTokenManager::generate('plan_compras_v2'),
        ]);
    }

    private function can(string $permissionKey): bool
    {
        return (new RbacService($this->db))->can($permissionKey);
    }

    private function ok(array $data): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
    }

    private function fail(string $code, string $message, int $status): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(
            ['ok' => false, 'error' => ['code' => $code, 'message' => $message]],
            JSON_UNESCAPED_UNICODE
        );
    }
}
```

- [ ] **Step 4: Correr el test y verificar que pasa**

```bash
docker compose exec app php tests/test_pdc_v2_contexto.php
```

Expected: todos `PASS` y `=== OK ===`, exit 0.

- [ ] **Step 5: PHPStan**

```bash
docker compose exec app vendor/bin/phpstan analyse src/Controllers/Api/PlanComprasApiController.php --memory-limit=1G
```

Expected: sin errores.

- [ ] **Step 6: Commit**

```bash
git add src/Controllers/Api/PlanComprasApiController.php tests/test_pdc_v2_contexto.php
git commit -m "feat(pdc-v2): endpoint de contexto con envelope propio, RBAC y CSRF por sesión"
```

---

### Task 8: Script de sync + primer build desplegado en lps-aia

**Files:**
- Create (repo `plan-de-compras`): `scripts/sync-to-lps.sh`
- Create (repo `lps-aia`, generado): `public/pdc-app/assets/pdc.js`, `public/pdc-app/assets/pdc.css`

**Interfaces:**
- Consumes: `npm run build` (Task 1).
- Produces: `npm run sync` deja el bundle en `lps-aia/public/pdc-app/`; el bundle **se commitea en lps-aia** (el deploy SiteGround es `git pull`: sin commit no llega a producción). `biome.json` no cubre `public/pdc-app/**` — el bundle no se linta.

- [ ] **Step 1: Crear `scripts/sync-to-lps.sh`** (repo `plan-de-compras`)

```bash
#!/usr/bin/env bash
# Compila la SPA y sincroniza el bundle a lps-aia (public/pdc-app/).
# El deploy a SiteGround es el de lps-aia (git pull): el bundle viaja commiteado.
set -euo pipefail

REPO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
LPS_DIR="${LPS_DIR:-$REPO_DIR/../lps-aia}"
DEST="$LPS_DIR/public/pdc-app"

if [ ! -d "$LPS_DIR/public" ]; then
  echo "ERROR: no encuentro lps-aia en $LPS_DIR (exporta LPS_DIR si vive en otra ruta)" >&2
  exit 1
fi

cd "$REPO_DIR"
npm run build

rm -rf "$DEST"
mkdir -p "$DEST"
cp -R "$REPO_DIR/dist/." "$DEST/"

echo "OK: bundle sincronizado en $DEST"
ls -l "$DEST/assets"
```

- [ ] **Step 2: Darle permisos y correrlo**

```bash
chmod +x scripts/sync-to-lps.sh
npm run sync
```

Expected: `OK: bundle sincronizado en .../lps-aia/public/pdc-app` con `pdc.js` (+ `pdc.css`).

- [ ] **Step 3: Commit en ambos repos**

```bash
git add scripts/sync-to-lps.sh && git commit -m "feat(pdc): script de sync del bundle hacia lps-aia"
cd "/Volumes/Crucial X6/Developer/lps-aia"
git add public/pdc-app && git commit -m "feat(pdc-v2): primer bundle de la isla React (build de plan-de-compras)"
```

---

### Task 9: e2e Playwright del flujo completo

**Files:**
- Create (lps-aia): `tests/browser/pdc-v2-fundacion.spec.mjs`

**Interfaces:**
- Consumes: helpers existentes `loginAndSelectProject(page, project, credentials)` y `logout(page)` de `tests/browser/support/session.mjs`; fixture `PROJECTS` de `tests/browser/fixtures/projects.mjs` (proyecto canónico: `key:'construction'`, name `Da Porto`, projectId 73); credenciales `E2E_APP_USERNAME`/`E2E_APP_PASSWORD` con fallback del fixture. Config raíz `playwright.config.mjs` (baseURL `http://localhost:8081`, `testDir: './tests/browser'`).
- Consumes (de Tasks 4–7): `[data-testid="pdc-contexto"]`, celdas AG Grid (`.ag-cell`), endpoint `/plan-compras/api/contexto`.

- [ ] **Step 1: Escribir `tests/browser/pdc-v2-fundacion.spec.mjs`**

```js
import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject, logout } from './support/session.mjs';

const project = PROJECTS.find(({ key }) => key === 'construction');

test('la isla React del PDC v2 monta con contexto real del proyecto', async ({ page }) => {
  test.skip(!project, 'Se requiere el proyecto de construcción (Da Porto)');

  await loginAndSelectProject(page, project);
  try {
    // La API de contexto responde envelope ok durante la carga de la vista.
    const contextoPromise = page.waitForResponse(
      (res) => res.url().includes('/plan-compras/api/contexto') && res.status() === 200,
    ).catch(() => null); // en prod el bootstrap va inyectado: el fetch puede no ocurrir

    const response = await page.goto('/plan-compras', { waitUntil: 'domcontentloaded' });
    expect(response?.status(), '/plan-compras debe responder').toBeLessThan(400);

    // El shell inyectó el bootstrap para la SPA.
    const bootstrap = await page.evaluate(() => window.__PDC_BOOTSTRAP__);
    expect(bootstrap?.projectId).toBe(project.projectId);
    expect(String(bootstrap?.csrfToken || '')).toHaveLength(64);

    // La SPA montó y muestra el contexto.
    await expect(page.locator('[data-testid="pdc-contexto"]')).toContainText(project.name, { timeout: 15000 });

    // AG Grid renderizó la fila del proyecto.
    await expect(
      page.locator('.ag-cell').filter({ hasText: `${project.projectId} — ${project.name}` }),
    ).toBeVisible({ timeout: 15000 });

    expect(await page.locator('body').innerText()).not.toContain('Fatal error');
    await contextoPromise;
  } finally {
    await logout(page).catch(() => {});
  }
});
```

- [ ] **Step 2: Levantar el stack y correr el e2e**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia"
docker compose up -d --build db app adminer
npx playwright test tests/browser/pdc-v2-fundacion.spec.mjs --workers=1
```

Expected: `1 passed`. *(Requiere el bundle ya sincronizado — Task 8 — y el usuario e2e `test.A` del entorno local.)*

- [ ] **Step 3: Commit**

```bash
git add tests/browser/pdc-v2-fundacion.spec.mjs
git commit -m "test(pdc-v2): e2e de la fundación — login, isla montada y contexto real en AG Grid"
```

---

## Verificación end-to-end

1. `cd plan-de-compras && npm run test && npm run build` → 9 tests PASS, build OK.
2. `cd ../lps-aia && docker compose up -d --build db app adminer` → stack arriba.
3. `docker compose exec app php tests/test_pdc_v2_contexto.php` → `=== OK ===`, exit 0.
4. `npm run sync` (en plan-de-compras) → bundle en `lps-aia/public/pdc-app/`.
5. Manual: login en `http://localhost:8081` (test.A / aia2026), seleccionar *Da Porto*, ir a `/plan-compras` → grilla AG Grid dark con `73 — Da Porto`, usuario y rol.
6. `npx playwright test tests/browser/pdc-v2-fundacion.spec.mjs --workers=1` → 1 passed.
7. `docker compose exec app vendor/bin/phpstan analyse src admin/src --memory-limit=1G` → sin errores nuevos.
8. Al final: actualizar `CLAUDE.md` de `plan-de-compras` con los comandos reales (`npm run dev/build/test/sync`) — quedó pendiente ahí explícitamente.

## Riesgos y decisiones registradas

- **Design system:** la fundación consume solo `tokens.css` (sin el entrypoint `aia-design-system.css` ni manifiesto). Cuando empiece la UI real del módulo, decidir manifiesto + adopción de primitivas `aia-*` según `DESIGN.md` (el router del design system advierte sin bloquear en superficies nuevas).
- **AG Grid v33+:** el código usa Theming API (`themeQuartz`) y `AllCommunityModule`; si la major instalada difiere, ajustar según su guía de migración.
- **Caso 5 del test PHP:** si todos los roles canónicos tienen `lps.pdc.ver`, la rama FORBIDDEN queda cubierta solo por diseño (PASS informativo) — aceptable en la fundación.
- **Deploy real a SiteGround:** fuera de alcance de este plan (staging primero, rutina `docs/siteground-deploy-routine.md`); el bundle commiteado ya viaja con `git pull`.

## Fuera de alcance (planes siguientes)

Import de presupuesto (PhpSpreadsheet + migraciones de tablas nuevas), maestro de insumos real, Pareto, paquetes, matching de cronograma, permisos RBAC nuevos (`lps.paquetes_contratacion.*` ya existen en el catálogo), deploy a SiteGround staging/producción.
