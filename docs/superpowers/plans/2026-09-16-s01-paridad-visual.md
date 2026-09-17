---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-09-16
areas: [design-system, arquitectura, qa]
resumen: "Reabre S01 por paridad visual: el login React conserva su base y trae de legacy la marca dentro de la tarjeta, los íconos de campo y botón, y el pie corporativo"
---

# S01 · Paridad visual del login React — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** que `/login` en React tenga paridad visual con el acceso legacy en lo que Felipe fijó —marca, íconos y pie— sin perder nada del comportamiento que S01 ya verificó.

**Architecture:** React sigue siendo el cimiento (`MarcoAcceso` + `PantallaLogin` + `CampoClave`). Se añade la marca dentro de la tarjeta, íconos SVG embebidos (sin vendor nuevo) y el pie de legacy; toda la presentación nueva va en `public/css/auth-react.css`, dentro de `@layer module` y bajo el scope `.aia-auth`.

**Tech Stack:** React + TypeScript + Vite (`frontend/`, bundle en `public/app/`), Vitest + Testing Library, Playwright (`tests/browser/login-react.*`), contrato estático del design system (`npm run test:design-system:static`).

**Spec:** `docs/superpowers/specs/2026-08-30-s01-login-react-design.md` (S01 original) + las decisiones de abajo. Plan cerrado que este reabre: `docs/superpowers/plans/2026-08-30-s01-login-react.md` (su `## Cierre` no se toca).

## Decisiones de Felipe (2026-09-16, en el chat)

1. **Paridad incluye lo visual.** S01 deja de cumplir el criterio de deploy hasta cerrar este plan.
2. **React es el cimiento.** No se copia la pantalla legacy: se adaptan **logo e íconos**.
3. **Pie de legacy:** `© 2026 Arquitectos e Ingenieros Asociados` / `Construyendo con +CERTEZA` sustituye a `© Last Planner AIA`.
4. **Logo dentro de la tarjeta, encima del título** (opción A). El texto suelto «Last Planner AIA» de la cabecera sale; en la cabecera queda solo el conmutador de tema.
5. Se conservan el botón **«Entrar»** y el **conmutador de tema**.
6. **Bienvenida y subtítulo del legado** (2026-09-16, opción A): el `h1` pasa a «Bienvenido a Last Planner AIA» con «Ingresa tus credenciales para continuar» debajo, centrados y con el tamaño de `.login-title`/`.login-subtitle`; el botón sigue «Entrar». Sustituye a lo que la decisión 5 decía del título.

## Global Constraints

- Íconos como **SVG embebido** con `fill`/`stroke` en `currentColor` y `aria-hidden="true"`. **Nada de Font Awesome** en el shell React (evita tocar `vendors.json` y `design-system-entrypoint-partition`).
- El logo usa el token existente `--ds-nav-brand-mark-image` (`public/css/tokens.css:808`, `/img/brand/glyph-mono.svg`) con `mask`, igual que legacy. Sin imagen nueva.
- CSS nuevo solo en `public/css/auth-react.css`, dentro de `@layer module`, con selectores bajo `.aia-auth`. Sin hex, sin estilos inline, solo tokens `--ds-*`.
- **Los nombres accesibles no cambian:** botón `Entrar` / `Entrando…`, alternador `Mostrar contraseña` / `Ocultar contraseña` con `aria-pressed`. El `h1` único es «Bienvenido a Last Planner AIA» (decisión 6).
- Objetivos táctiles ≥ `--ds-target-min`; contraste AA (4,5:1 texto, 3:1 íconos y controles) en **los dos temas**.
- Viewports de la pantalla: 390×844, 768×1024, 1180×820 (canónico), 1440×900.
- Goldens: **no** se regeneran sin aprobación explícita de Felipe sobre los candidatos. Para forzar la recaptura se borran los PNG (bajo tolerancia, `--update-snapshots` no reescribe).
- Fuera de alcance: placeholders de legacy, activar el legacy sin deploy (decisión abierta, ver Tarea 6), el golden huérfano `tests/browser/__screenshots__/auth/login-dark-1180x820.png`.

## Referencia medida

Capturas vivas legacy vs React (2 temas × 4 viewports), tomadas el 2026-09-16 con un contenedor efímero en `:8095` que sirve `/login` por legacy (quitando `'/login'` de `SpaRouter::RUTAS_EXACTAS_MIGRADAS` en un worktree desechable). Hallazgo extra al medir: en claro, el conmutador «Tema: claro» pinta `rgb(199, 212, 204)` sobre `rgb(250, 250, 250)` ≈ **1,5:1** — falla AA. Entra en este plan (Tarea 3) porque es la misma superficie y sus goldens se rehacen igual.

## File Structure

| Archivo | Responsabilidad |
|---|---|
| `frontend/src/shell/auth/iconos.tsx` (nuevo) | Cuatro íconos SVG sin estado: `IconoUsuario`, `IconoOjo`, `IconoOjoTachado`, `IconoFlecha` |
| `frontend/src/shell/auth/iconos.test.tsx` (nuevo) | Los íconos son decorativos y heredan color |
| `frontend/src/shell/auth/MarcoAcceso.tsx` | Marca en la tarjeta, cabecera sin texto, pie legacy |
| `frontend/src/shell/auth/PantallaLogin.tsx` | Ícono en el campo usuario y flecha en el botón (ambos modos) |
| `frontend/src/shell/auth/CampoClave.tsx` | Alternador como botón de ícono dentro del campo |
| `frontend/src/shell/auth/*.test.tsx` | S01-UX-01 y equivalentes con la marca y el pie nuevos |
| `public/css/auth-react.css` | Marca, campo con ícono, alternador, flecha, contraste del conmutador |
| `public/app/**` | Bundle reconstruido |
| `tests/browser/__screenshots__/login-react.visual.mjs/*.png` + `docs/design-system/manifests/auth.json` | 8 goldens aprobados y sus `sha256` |
| `TASKS.md` | S01 deja de cumplir el criterio de deploy; luego, cierre |

---

### Task 0: Registrar la reapertura

**Files:**
- Modify: `TASKS.md` (bloque «Criterio de deploy», ~línea 858)
- Create: este plan

- [ ] **Step 1: Corregir la afirmación «S01 cumple»**

En el bloque «Criterio de deploy», sustituir `**S01 cumple** (\`CODE_COMPLETE\`, legado de \`/login\` conservado a propósito);` por:

```markdown
**S01 no cumple desde el 2026-09-16**: Felipe fijó que la paridad **incluye lo visual** y el login
React no la tiene (marca, íconos y pie de legado). Se reabre en
`docs/superpowers/plans/2026-09-16-s01-paridad-visual.md`;
```

- [ ] **Step 2: Lint de wiki y commit**

Run: `npm run test:wiki` → Expected: RC=0.

```bash
git add TASKS.md docs/superpowers/plans/2026-09-16-s01-paridad-visual.md
git commit -m "docs(s01): reabrir S01 por paridad visual — decisiones de Felipe y plan"
```

---

### Task 1: Íconos SVG

**Files:**
- Create: `frontend/src/shell/auth/iconos.tsx`
- Test: `frontend/src/shell/auth/iconos.test.tsx`

**Interfaces:**
- Produces: `IconoUsuario`, `IconoOjo`, `IconoOjoTachado`, `IconoFlecha` — componentes sin props, `<svg className="aia-auth__icono" aria-hidden="true" focusable="false" viewBox="0 0 24 24">`.

- [ ] **Step 1: Test que falla**

```tsx
import { render } from '@testing-library/react';
import { expect, test } from 'vitest';
import { IconoFlecha, IconoOjo, IconoOjoTachado, IconoUsuario } from './iconos';

test.each([
  ['usuario', IconoUsuario],
  ['ojo', IconoOjo],
  ['ojo tachado', IconoOjoTachado],
  ['flecha', IconoFlecha],
])('el ícono %s es decorativo y hereda el color del texto', (_nombre, Icono) => {
  const { container } = render(<Icono />);
  const svg = container.querySelector('svg');
  expect(svg).toHaveAttribute('aria-hidden', 'true');
  expect(svg).toHaveAttribute('focusable', 'false');
  expect(svg).toHaveClass('aia-auth__icono');
  expect(container.innerHTML).not.toMatch(/#[0-9a-f]{3,8}\b/i);
  expect(container.innerHTML).toContain('currentColor');
});
```

- [ ] **Step 2: Verificar que falla**

Run: `npm --prefix frontend test -- iconos` → Expected: FAIL (`Cannot find module './iconos'`).

- [ ] **Step 3: Implementación**

```tsx
/**
 * Íconos del acceso (S01 · paridad visual, 2026-09-16). Sustituyen a los de Font Awesome del
 * login legado (`fa-user`, `fa-eye`, `fa-arrow-right`) sin traer el vendor al shell React.
 * Son decorativos: el nombre accesible lo lleva siempre el control que los contiene.
 */
const base = {
  className: 'aia-auth__icono',
  viewBox: '0 0 24 24',
  'aria-hidden': true,
  focusable: false,
} as const;

export function IconoUsuario() {
  return (
    <svg {...base} fill="currentColor">
      <circle cx="12" cy="8" r="4" />
      <path d="M4 20c0-4.4 3.6-7 8-7s8 2.6 8 7v1H4z" />
    </svg>
  );
}

export function IconoOjo() {
  return (
    <svg {...base} fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z" />
      <circle cx="12" cy="12" r="3" fill="currentColor" />
    </svg>
  );
}

export function IconoOjoTachado() {
  return (
    <svg {...base} fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z" />
      <circle cx="12" cy="12" r="3" fill="currentColor" />
      <path d="M3 3l18 18" />
    </svg>
  );
}

export function IconoFlecha() {
  return (
    <svg {...base} fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
      <path d="M5 12h14M13 6l6 6-6 6" />
    </svg>
  );
}
```

- [ ] **Step 4: Verificar que pasa**

Run: `npm --prefix frontend test -- iconos` → Expected: 4 PASS.

- [ ] **Step 5: Commit**

```bash
git add frontend/src/shell/auth/iconos.tsx frontend/src/shell/auth/iconos.test.tsx
git commit -m "feat(s01): íconos SVG del acceso, sin Font Awesome"
```

---

### Task 2: Marca en la tarjeta y pie de legacy

**Files:**
- Modify: `frontend/src/shell/auth/MarcoAcceso.tsx`
- Modify: `frontend/src/shell/auth/PantallaLogin.test.tsx:38-50`
- Modify: `frontend/src/shell/auth/CambioClaveObligatorio.test.tsx:34-41`
- Modify: `public/css/auth-react.css`

**Interfaces:**
- Produces: clases `aia-auth__marca`, `aia-auth__marca-glifo`, `aia-auth__marca-nombre`, `aia-auth__pie`, `aia-auth__cabecera`. Texto accesible de la marca: `Last Planner AIA` (un solo nodo de texto).

- [ ] **Step 1: Tests que fallan**

En `PantallaLogin.test.tsx` sustituir el test `S01-UX-01` por:

```tsx
test('S01-UX-01: MarcoAcceso trae un único h1, la marca en la tarjeta, tema y el pie corporativo', () => {
  const { container } = render(<PantallaLogin {...propiedades()} />);

  expect(screen.getAllByRole('heading', { level: 1 })).toHaveLength(1);
  expect(screen.getByRole('heading', { level: 1, name: 'Entrar' })).toBeInTheDocument();

  // Paridad visual (2026-09-16): la marca vive DENTRO de la tarjeta, antes del h1, y una sola vez.
  const tarjeta = container.querySelector('.aia-auth__layout > .aia-card');
  const marca = screen.getByText('Last Planner AIA');
  expect(tarjeta).toContainElement(marca);
  expect(screen.getAllByText('Last Planner AIA')).toHaveLength(1);
  expect(tarjeta?.firstElementChild).toHaveClass('aia-auth__marca');

  expect(screen.getByRole('button', { name: /cambiar a tema/i })).toBeInTheDocument();
  expect(screen.getByText('© 2026 Arquitectos e Ingenieros Asociados')).toBeInTheDocument();
  expect(screen.getByText('+CERTEZA')).toBeInTheDocument();
  expect(screen.queryByText('© Last Planner AIA')).not.toBeInTheDocument();
  expect(screen.getByRole('link', { name: /saltar al contenido/i })).toHaveAttribute(
    'href',
    '#contenido-acceso',
  );
});
```

En `CambioClaveObligatorio.test.tsx`, dentro de `el diálogo vive dentro de MarcoAcceso…`, sustituir la línea de `'© Last Planner AIA'` por:

```tsx
  expect(screen.getByText('© 2026 Arquitectos e Ingenieros Asociados')).toBeInTheDocument();
```

- [ ] **Step 2: Verificar que fallan**

Run: `npm --prefix frontend test -- PantallaLogin CambioClave` → Expected: FAIL en esos dos tests (no encuentra el pie nuevo; la marca está en la cabecera).

- [ ] **Step 3: Implementación en `MarcoAcceso.tsx`**

Sustituir el `return` por:

```tsx
  return (
    <div className="aia-shell aia-auth">
      <a className="aia-skip-link" href={`#${ID_CONTENIDO_ACCESO}`} onClick={alSaltarAlContenido}>
        Saltar al contenido
      </a>

      {/* Paridad visual (2026-09-16, opción A de Felipe): la marca ya no va suelta aquí, sino en
          la tarjeta. La cabecera se queda con la utilidad de tema. */}
      <header className="aia-page aia-auth__cabecera">
        <ConmutadorTema />
      </header>

      <main id={ID_CONTENIDO_ACCESO} ref={contenidoRef} className="aia-page aia-auth__layout" tabIndex={-1}>
        <section className="aia-card">
          <div className="aia-auth__marca">
            <span className="aia-auth__marca-glifo" aria-hidden="true" />
            <span className="aia-auth__marca-nombre">Last Planner AIA</span>
          </div>
          <h1 id={idTitulo}>{titulo}</h1>
          {children}
        </section>
      </main>

      <footer className="aia-page aia-auth__pie">
        <p className="aia-copy">
          <span>© 2026 Arquitectos e Ingenieros Asociados</span>
          <span>
            Construyendo con <strong>+CERTEZA</strong>
          </span>
        </p>
      </footer>
    </div>
  );
```

Actualizar el comentario del componente: «la marca» pasa a estar en la tarjeta.

- [ ] **Step 4: CSS en `public/css/auth-react.css`**

Añadir dentro de `@layer module`, tras el bloque `.aia-auth__layout > .aia-card`:

```css
  /* Marca en la tarjeta (paridad visual con el legado, 2026-09-16). El glifo es el mismo
     `--ds-nav-brand-mark-image` que usa `login-brand-unified.css`, pintado con `mask` para que
     tome el color del texto en los dos temas. */
  .aia-auth__marca {
    display: inline-flex;
    align-items: center;
    align-self: center;
    justify-content: center;
    gap: var(--ds-space-2);
    margin-inline: auto;
    margin-block-end: var(--ds-space-4);
    padding: var(--ds-space-2) var(--ds-space-3);
    border: 1px solid var(--ds-active-border);
    border-radius: var(--ds-radius-pill);
    color: var(--ds-active-text-primary);
    box-shadow: var(--ds-shadow-xs);
  }

  .aia-auth__layout > .aia-card {
    display: flex;
    flex-direction: column;
  }

  .aia-auth__marca-glifo {
    inline-size: 1.65rem;
    block-size: 1.65rem;
    background: currentColor;
    -webkit-mask: var(--ds-nav-brand-mark-image) center / contain no-repeat;
    mask: var(--ds-nav-brand-mark-image) center / contain no-repeat;
  }

  .aia-auth__marca-nombre {
    font-family: var(--ds-font-display);
    font-weight: 800;
    line-height: 1;
    white-space: nowrap;
  }

  .aia-auth__pie .aia-copy {
    display: flex;
    flex-direction: column;
    gap: var(--ds-space-1);
  }
```

- [ ] **Step 5: Verificar**

Run: `npm --prefix frontend test` → Expected: toda la suite PASS.
Run: `npm run frontend:typecheck` → Expected: RC=0.

- [ ] **Step 6: Commit**

```bash
git add frontend/src/shell/auth/MarcoAcceso.tsx frontend/src/shell/auth/PantallaLogin.test.tsx frontend/src/shell/auth/CambioClaveObligatorio.test.tsx public/css/auth-react.css
git commit -m "feat(s01): marca dentro de la tarjeta y pie corporativo del legado"
```

---

### Task 3: Íconos en usuario, contraseña y botón; contraste del conmutador

**Files:**
- Modify: `frontend/src/shell/auth/PantallaLogin.tsx` (dos formularios: normal y mantenimiento)
- Modify: `frontend/src/shell/auth/CampoClave.tsx`
- Modify: `frontend/src/shell/auth/PantallaLogin.test.tsx`
- Modify: `public/css/auth-react.css`

**Interfaces:**
- Consumes: `IconoUsuario`, `IconoOjo`, `IconoOjoTachado`, `IconoFlecha` (Task 1).
- Produces: clases `aia-auth__campo-icono`, `aia-auth__campo-adorno`, `aia-auth__clave-toggle` (se conserva el nombre), `aia-auth__boton-flecha`.

- [ ] **Step 1: Tests que fallan** (añadir a `PantallaLogin.test.tsx`)

```tsx
test('paridad visual: usuario con ícono, alternador de ícono con nombre accesible y botón con flecha', async () => {
  const user = userEvent.setup();
  render(<PantallaLogin {...propiedades()} />);

  const usuario = screen.getByLabelText('Usuario');
  expect(usuario.closest('.aia-auth__campo-icono')?.querySelector('svg[aria-hidden="true"]')).not.toBeNull();

  const alternador = screen.getByRole('button', { name: 'Mostrar contraseña' });
  expect(alternador).toHaveAttribute('aria-pressed', 'false');
  expect(alternador.querySelector('svg[aria-hidden="true"]')).not.toBeNull();
  expect(alternador).not.toHaveTextContent(/\S/);

  await user.click(alternador);
  expect(screen.getByRole('button', { name: 'Ocultar contraseña' })).toHaveAttribute('aria-pressed', 'true');

  const entrar = screen.getByRole('button', { name: 'Entrar' });
  expect(entrar.querySelector('svg[aria-hidden="true"]')).not.toBeNull();
});

test('paridad visual: el formulario de mantenimiento lleva los mismos íconos', () => {
  render(
    <PantallaLogin
      {...propiedades()}
      modo={{ tipo: 'mantenimiento', action: '/oculta', error: false, csrfToken }}
    />,
  );

  expect(screen.getByLabelText('Usuario').closest('.aia-auth__campo-icono')).not.toBeNull();
  expect(screen.getByRole('button', { name: 'Mostrar contraseña' }).querySelector('svg')).not.toBeNull();
  expect(screen.getByRole('button', { name: 'Entrar' }).querySelector('svg')).not.toBeNull();
});
```

- [ ] **Step 2: Verificar que fallan**

Run: `npm --prefix frontend test -- PantallaLogin` → Expected: FAIL en los dos tests nuevos.

- [ ] **Step 3: `CampoClave.tsx`**

Importar `import { IconoOjo, IconoOjoTachado } from './iconos';` y sustituir el bloque `<div className="aia-auth__clave">…</div>` por:

```tsx
      {/* Paridad visual (2026-09-16): el alternador vuelve a ser un ícono DENTRO del campo, como
          en el legado. El texto pasa a `aria-label`, así que el nombre accesible no cambia
          («Mostrar contraseña» / «Ocultar contraseña») y `aria-pressed` sigue diciendo el estado. */}
      <div className="aia-auth__campo-icono">
        <input
          id={id}
          name={name}
          className="aia-input"
          type={visible ? 'text' : 'password'}
          value={value}
          onChange={alCambiar}
          autoComplete={autoComplete}
          disabled={disabled}
          required
          aria-invalid={error ? true : undefined}
          aria-describedby={error ? idError : undefined}
        />

        <button
          type="button"
          className="aia-auth__clave-toggle"
          aria-label={visible ? 'Ocultar contraseña' : 'Mostrar contraseña'}
          aria-pressed={visible}
          disabled={disabled}
          onClick={() => setVisible((valor) => !valor)}
        >
          {visible ? <IconoOjoTachado /> : <IconoOjo />}
        </button>
      </div>
```

- [ ] **Step 4: `PantallaLogin.tsx`**

Importar `import { IconoFlecha, IconoUsuario } from './iconos';`. En **los dos** formularios:

(a) envolver el `<input id="usuario" …/>` así (el input no cambia):

```tsx
            <div className="aia-auth__campo-icono">
              {/* input de usuario tal cual */}
              <span className="aia-auth__campo-adorno">
                <IconoUsuario />
              </span>
            </div>
```

(b) en el botón de envío, el contenido pasa a:

```tsx
              <span>{enviando ? 'Entrando…' : 'Entrar'}</span>
              <span className="aia-auth__boton-flecha">
                <IconoFlecha />
              </span>
```

(en mantenimiento, `<span>Entrar</span>` fijo). El nombre accesible sigue siendo `Entrar`/`Entrando…` porque el SVG es `aria-hidden`.

- [ ] **Step 5: CSS en `public/css/auth-react.css`**

Sustituir los bloques `.aia-auth__clave`, `.aia-auth__clave > .aia-input` y `.aia-auth__clave-toggle` (incluido `:disabled`) por:

```css
  /* Campo con ícono a la derecha (paridad visual con el legado, 2026-09-16). El ícono de
     usuario es adorno; el de contraseña es el alternador y conserva objetivo táctil. */
  .aia-auth__campo-icono {
    position: relative;
  }

  .aia-auth__campo-icono > .aia-input {
    inline-size: 100%;
    padding-inline-end: calc(var(--ds-target-min) + var(--ds-space-1));
  }

  .aia-auth__campo-adorno,
  .aia-auth__clave-toggle {
    position: absolute;
    inset-block: 0;
    inset-inline-end: 0;
    inline-size: var(--ds-target-min);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: var(--ds-active-text-secondary);
  }

  .aia-auth__campo-adorno {
    pointer-events: none;
  }

  .aia-auth__clave-toggle {
    min-block-size: var(--ds-target-min);
    padding: 0;
    border: 0;
    background: none;
    cursor: pointer;
  }

  .aia-auth__clave-toggle:disabled {
    cursor: not-allowed;
    opacity: var(--ds-opacity-disabled, 0.6);
  }

  .aia-auth__icono {
    inline-size: 1.125rem;
    block-size: 1.125rem;
    flex: 0 0 auto;
  }

  .aia-auth__acciones > .aia-btn {
    gap: var(--ds-space-2);
  }

  /* El conmutador reutiliza `aia-sidebar__utility`, pensado para la barra lateral. Sobre el
     lienzo claro del acceso medía rgb(199,212,204) sobre rgb(250,250,250) ≈ 1,5:1 (2026-09-16):
     ilegible. En esta pantalla toma el texto secundario del tema activo. */
  .aia-auth__cabecera .aia-sidebar__utility,
  .aia-auth__cabecera .aia-sidebar__label {
    color: var(--ds-active-text-secondary);
  }
```

- [ ] **Step 6: Verificar**

Run: `npm --prefix frontend test` → Expected: toda la suite PASS (incluidos S01-UX-04 con «Mostrar contraseña» y todos los `name: 'Entrar'`).
Run: `npm run frontend:typecheck` → Expected: RC=0.
Run: `npm run check:frontend` → Expected: RC=0.

- [ ] **Step 7: Commit**

```bash
git add frontend/src/shell/auth/PantallaLogin.tsx frontend/src/shell/auth/CampoClave.tsx frontend/src/shell/auth/PantallaLogin.test.tsx public/css/auth-react.css
git commit -m "feat(s01): íconos de usuario, contraseña y botón; contraste del conmutador en el acceso"
```

---

### Task 3b: Bienvenida y subtítulo del legado (añadida en ejecución)

Decisión de Felipe en el chat (2026-09-16, opción A), tomada tras ver los candidatos de la Tarea 5
con Task 4 ya cerrada — se añadió esta tarea antes de cerrar la Tarea 5: el `h1` pasa de
«Entrar» a **«Bienvenido a Last Planner AIA»**, con el subtítulo **«Ingresa tus credenciales para
continuar»** debajo, en los dos modos de `PantallaLogin` (normal y mantenimiento); el botón sigue
diciendo «Entrar»/«Entrando…». `CambioClaveObligatorio` conserva su título «Actualiza tu
contraseña» y no lleva subtítulo. Brief completo:
`.superpowers/sdd/2026-09-16-s01-paridad-visual/task-3b-brief.md`.

Alcance: `MarcoAcceso.tsx` gana una prop `subtitulo?: string`; `PantallaLogin.tsx` pasa el nuevo
título y subtítulo en sus dos formularios; `public/css/auth-react.css` centra el `h1` y el
subtítulo bajo la marca con `--ds-font-display`; se regenera el espejo `public/dist-css/auth-react.css`
(`npm run css:minify`) y el bundle (`npm run frontend:build`); y se actualiza todo lugar que
esperaba el `h1` «Entrar» dentro del alcance de S01 (`PantallaLogin.test.tsx`,
`CambioClaveObligatorio.test.tsx`, `tests/browser/login-react.spec.mjs`,
`tests/browser/login-react.visual.mjs`, `tests/browser/zz-sonda-temporal.spec.mjs`,
`frontend/src/shell/rutas.tsx` en su comentario).

**Ronda de arreglo de tamaño (1/5, 0 abiertos):** el candidato inicial mostraba el `h1` con el
tamaño por defecto de un heading en vez del tamaño de `.login-title`/`.login-subtitle` del legado;
se corrigió con las reglas de tamaño explícitas sobre `.aia-auth__layout > .aia-card > h1` y
`.aia-auth__subtitulo` (commits `c9e735dc..e6894478`). Felipe aprobó los candidatos resultantes
(«Aprobados, congela los goldens») y se congelaron en la Tarea 5.

Commits: `80d150ce..c9e735dc` (código+tests+CSS+espejo, bundle) y `c9e735dc..e6894478` (fix de
tamaño).

---

### Task 4: Bundle, navegador y suites

**Files:**
- Modify: `public/app/**` (generado)

- [ ] **Step 1: Construir**

Run: `npm run frontend:build` → Expected: RC=0 y `public/app/index.html` apuntando a los assets nuevos.
Run: `node scripts/check-secret-not-in-bundle.mjs` → Expected: RC=0.

- [ ] **Step 2: Servir la rama y mirar en el navegador**

`LPS_CODE_ROOT="$(pwd)" docker compose up -d app` (anotar que el contenedor compartido queda en esta rama y **devolverlo a la raíz** al terminar la tarea). En `http://localhost:8081/login`, en claro y oscuro, a 390×844 y 1180×820: marca en la tarjeta, íconos visibles, alternador funciona con clic y teclado, consola sin errores.

Medir contraste en claro y oscuro con Axe o `getComputedStyle`: íconos ≥ 3:1, conmutador ≥ 4,5:1, pie ≥ 4,5:1.

- [ ] **Step 3: Suites de la superficie**

Run: `npx playwright test tests/browser/login-react.spec.mjs --workers=1` → Expected: PASS (incluye «el pie es alcanzable»).
Run: `npm run test:design-system:static` → Expected: RC=0 (el CSS está en capa; los goldens aún no cambiaron).

- [ ] **Step 4: Commit**

```bash
git add public/app
git commit -m "build(s01): bundle del acceso con la paridad visual"
```

---

### Task 5: Candidatos, aprobación de Felipe, goldens y pines

**Files:**
- Modify: `tests/browser/__screenshots__/login-react.visual.mjs/login-{light,dark}-{390x844,768x1024,1180x820,1440x900}.png`
- Modify: `docs/design-system/manifests/auth.json` (`sha256` de las 8 filas `auth-login-react-*`)

- [ ] **Step 1: Generar candidatos**

Run: `npx playwright test tests/browser/login-react.visual.mjs --grep candidate --workers=1` → Expected: 8 PNG en `test-output/s01-login-candidates/`.

- [ ] **Step 2: Mostrar a Felipe** los 8 candidatos junto a las capturas legacy de referencia y **esperar su aprobación explícita** en el chat. Si pide cambios, volver a la Tarea 2 o 3. Sin aprobación, el plan se detiene aquí.

- [ ] **Step 3: Recapturar goldens (solo con aprobación)**

```bash
rm tests/browser/__screenshots__/login-react.visual.mjs/login-*.png
S01_GOLDENS_APROBADOS=1 npx playwright test tests/browser/login-react.visual.mjs --grep golden --update-snapshots --workers=1
S01_GOLDENS_APROBADOS=1 npx playwright test tests/browser/login-react.visual.mjs --grep golden --workers=1
```

Expected: la segunda corrida 8 PASS. Comprobar que cada golden es idéntico byte a byte a su candidato aprobado (`shasum -a 256`).

- [ ] **Step 4: Pines**

Para cada una de las 8 filas `auth-login-react-*` de `auth.json`, poner el `sha256` nuevo (`shasum -a 256 <png>`). No tocar la fila `auth-login-dark-1180x820`.

Run: `npm run test:design-system:static` → Expected: RC=0.

- [ ] **Step 5: Commit**

```bash
git add tests/browser/__screenshots__/login-react.visual.mjs docs/design-system/manifests/auth.json
git commit -m "test(visual): goldens del acceso con paridad visual, aprobados por Felipe"
```

---

### Task 6: Cierre

**Files:**
- Modify: `TASKS.md`, este plan (`## Cierre`), `memoria/` (ingest)

- [ ] **Step 1:** Devolver el contenedor compartido a la raíz (`docker compose up -d app` desde la raíz) y confirmar el montaje.
- [ ] **Step 2:** `TASKS.md`: S01 vuelve a cumplir el criterio **cuando este PR esté en `main`**; dejar registrada como **decisión abierta** si el respaldo legacy debe poder activarse sin deploy (hoy exige quitar `'/login'` de `SpaRouter` y publicar). Mencionar el golden huérfano `auth/login-dark-1180x820.png`.
- [ ] **Step 3:** Escribir `## Cierre` en este plan con lo verificado y los SHA.
- [ ] **Step 4:** Ingest en `memoria/` (una línea en `log.md` y la página que corresponda) y `npm run test:wiki` → RC=0.
- [ ] **Step 5:** Commit, `git fetch origin`, integrar `origin/main` si avanzó, re-verificar (`npm --prefix frontend test`, `npm run test:design-system:static`), push y PR contra `main` con la **condición de hecho declarada en el cuerpo antes del CI**: `design-system-static` en `success` y los 13 `G_*` en `success` en las dos patas.

## Cierre

**Estado:** código completo en la rama `s01-paridad-visual`, pendiente de PR y merge a `main`. La
paridad visual (marca, íconos, bienvenida y pie del legado) está implementada, probada y con
goldens congelados; falta publicar.

**Tareas y commits:**

| Tarea | Commits |
|---|---|
| Task 0 | `489bc29a..9a038d8e` |
| Task 1 | `9a038d8e..c4448bec` |
| Task 2 | `c4448bec..b5c9aea8` |
| Task 3 | `b5c9aea8..c1fadb18` |
| Task 4 | `c1fadb18..80d150ce` |
| Task 3b | `80d150ce..c9e735dc` (código+CSS+espejo+bundle), `c9e735dc..e6894478` (fix de tamaño) |
| Task 5 | `e6894478..7baca3b5` |
| Final fix (revisión final) | `4f741797` (spec `shell-control-actividad.spec.mjs`) + este cierre |

**Verificación:**
- `npm --prefix frontend test` → 587 tests, PASS.
- `tests/browser/login-react.spec.mjs` → 16 tests, PASS.
- `tests/browser/shell-control-actividad.spec.mjs` (hoy, contra el contenedor efímero `:8096`) →
  3 tests, PASS, RC=0.
- `npm run test:design-system:static` → RC=0.
- Contraste AA medido en los dos temas: 24 combinaciones, todas cumplen (texto ≥4,5:1, íconos y
  controles ≥3:1).
- Goldens: los 8 PNG de `login-react.visual.mjs` son idénticos byte a byte a los candidatos
  aprobados por Felipe (`shasum -a 256`).

**Rulings (del ledger, en orden, con su costo):**
1. Tarea 0 la ejecuta el controlador (solo TASKS.md + commit del plan) — es registro, no
   implementación — costo si fuera error: nulo, dos líneas de doc revisables en el PR.
2. Tarea 4 sirve la rama en el contenedor compartido 8081 con `LPS_CODE_ROOT` y lo devuelve a la
   raíz al terminar la tarea, como manda CLAUDE.md — costo: una verificación ajena confundida
   durante minutos si otra sesión lo usa en el intervalo.
3. Tarea 5 se detiene tras generar candidatos hasta aprobación explícita de Felipe, lo exige
   AGENTS.md para cambios visuales — costo: espera.
4. `check:frontend` global sale rojo por errores preexistentes en `admin/public/css` (rama no los
   toca); el gate de las tareas es Biome sobre los archivos tocados con RC=0 — costo si fuera
   error: un rojo real en `public/css` pasaría desapercibido, mitigado porque la suite estática del
   DS corre en Tarea 4.
5. El commit `c1fadb18` firma `Co-Authored-By` con Sonnet 5 (el modelo que lo escribió) en vez de
   Opus 5 — es la atribución veraz; no se reescribe historia — costo si fuera error: una línea de
   atribución distinta en un commit.
6. La Tarea 4 NO usa el contenedor compartido 8081: se sirve la rama en un contenedor efímero
   `lps-s01-paridad` en `:8096` (vendor montado de la raíz) y los e2e corren con
   `E2E_BASE_URL=http://localhost:8096` — reemplaza el ruling anterior sobre 8081 — costo si fuera
   error: el efímero comparte la base de dev, igual que el compartido.
7. `public/dist-css/auth-react.css` entra en la rama (espejo versionado que `.htaccess` sirve en
   lugar de `public/css`); el plan lo omitía — costo si fuera error: un archivo generado de más en
   el PR.
8. Tarea 5 pasos 1-2 (generar candidatos y presentarlos) los corre el controlador: es un comando
   sin cambios de código y termina en la parada por aprobación de Felipe — costo si fuera error:
   ninguno, no hay commit.
9. Título y subtítulo centrados con fuente display — replica el legado — costo si fuera error:
   ajuste de CSS tras ver candidatos.
10. El centrado y la fuente display del `h1` también alcanzan a «Actualiza tu contraseña» de
    `CambioClaveObligatorio` — se acepta: misma familia de pantallas de acceso, coherencia visual;
    no hay goldens de esa pantalla — costo si fuera error: un ajuste de selector.
11. Los `rem`/`clamp` literales del título, subtítulo y glifo se aceptan como excepción
    documentada en este Cierre — calcan el legado por decisión de paridad y la suite estática los
    acepta — costo si fuera error: tokenizarlos después.

**Excepciones aceptadas:**
- `rem`/`clamp` literales del título, subtítulo y glifo, calcados del legado por decisión de
  paridad (ruling 11).
- El `h1` centrado con fuente display alcanza también a `CambioClaveObligatorio` (ruling 10).
- `public/dist-css/auth-react.css` se añadió al alcance de la rama, fuera de lo previsto en el
  plan original (ruling 7).

**Minors que esperan (deferred, sin bloquear el cierre):**
- `.aia-auth__layout > .aia-card` declarado dos veces en `auth-react.css` (Task 2).
- `.aia-auth__acciones > .aia-btn` declarado dos veces en `auth-react.css` (Task 3).
- `.aia-auth__boton-flecha` sin reglas propias, solo marcador estructural (Task 3).

**Decisión abierta de Felipe:** si el respaldo legado debe poder activarse sin deploy — hoy exige
quitar `'/login'` de `SpaRouter::RUTAS_EXACTAS_MIGRADAS` y publicar.

**Golden huérfano:** `tests/browser/__screenshots__/auth/login-dark-1180x820.png` sigue sin
consumidor (fuera de alcance de este plan).
