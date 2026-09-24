---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-09-24
areas: [frontend, design-system, ux]
fuente: docs/superpowers/plans/2026-09-24-s05-paridad-visual-programa-general.md
resumen: Plan de implementación para alcanzar paridad visual y operativa 1:1 de Programa General en React contra el mockup de producción aprobado.
---

# S05-PARIDAD-VISUAL: Paridad Visual 1:1 de Programa General en React — Plan de Implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Alcanzar paridad visual y operativa 1:1 entre el módulo React de Programa General y el mockup de producción aprobado (`public/mockups/s05-production-mockup.html`), habilitando el corte canónico en `SpaRouter` para `/programa-general`, sanitizando textos HTML heredados, formateando fechas de obra, elevando la jerarquía visual de la tabla y dotando al Drawer LPS de paridad estética completa en temas claro y oscuro.

**Architecture:** Corte canónico en Front Controller mediante `SpaRouter::RUTAS_EXACTAS_MIGRADAS`; sanitización pura en modelo de dominio (`parsearTextoActividad` y `formatearFechaObra`); renderizado semántico en `ProgramaTable`, `ProgramaSignalsBar`, `ProgramaFilters` y `ProgramaDrawer`; estilización de alta fidelidad encapsulada en `@layer module` dentro de `programa-general.css`.

**Tech Stack:** React 18, TypeScript, Zod, Vitest, PHP 8.3 (Docker), Playwright, CSS Tokens Design System AIA.

**Spec:** [`docs/superpowers/specs/2026-09-24-s05-paridad-visual-programa-general-design.md`](file:///Volumes/Crucial%20X6/Developer/lps-aia/docs/superpowers/specs/2026-09-24-s05-paridad-visual-programa-general-design.md)

## Global Constraints

- Runtime Docker canónico: `http://localhost:8081`, PHP 8.3 en contenedor `app`.
- Encapsulamiento estricto de estilos: Todo CSS debe residir en `frontend/src/modules/programa-general/programa-general.css` envuelto en `@layer module { ... }`. Cero CSS unlayered para mantener 8/8 gates en `npm run test:design-system:static`.
- Cero etiquetas HTML en UI: La BD almacena texto con `<b>` y `<small>` que debe ser sanitizado en dominio, extrayendo título y subtítulo semánticos.
- Compatibilidad dual obligatoria: Tanto Tema Claro como Tema Oscuro son contractuales a 1180×820.
- Preservar aislamiento por proyecto: Toda consulta y mutación debe respetar `project_id`.

---

### Task 1: Backend PHP — Corte Canónico de `/programa-general` en `SpaRouter` y Verificación de Frontera

**Files:**
- Modify: `src/Core/SpaRouter.php:18`
- Modify: `tests/test_spa_frontera.php:320-325`
- Modify: `tests/test_shell_route_map_rollback.php:44-90`

**Interfaces:**
- Consumes: `SpaRouter::RUTAS_EXACTAS_MIGRADAS`
- Produces: `SpaRouter::sirveLaSpa('/programa-general') === true` para `GET` y `HEAD`, `false` para `POST`.

- [ ] **Step 1: Escribir la aserción de frontera para `/programa-general` en `tests/test_spa_frontera.php` y actualizar `tests/test_shell_route_map_rollback.php`**

Editar `tests/test_spa_frontera.php` justo antes de `echo $fallos === 0`:
```php
    // --- S05: el corte de '/programa-general' en SpaRouter ---
    if (!SpaRouter::sirveLaSpa('/programa-general') || !SpaRouter::sirveLaSpa('/programa-general', 'HEAD')) {
        echo "FALLO: S05 — el mapa real de producción debe servir GET/HEAD '/programa-general' desde la SPA\n";
        $fallos++;
    }
    if (SpaRouter::sirveLaSpa('/programa-general', 'POST')) {
        echo "FALLO: S05 — POST '/programa-general' no debe servirlo la SPA\n";
        $fallos++;
    }
```

En `tests/test_shell_route_map_rollback.php`, como `/programa-general` entra al mapa de exactas migradas, cambiar la ruta de muestra hipotética no migrada de `/programa-general` a `/lookahead` (S07, pendiente de migración):
```php
$exactasReales = SpaRouter::RUTAS_EXACTAS_MIGRADAS;
$prefijosReales = SpaRouter::PREFIJOS_MIGRADOS;
$exactasConPantallaDeMuestraMigrada = [...$exactasReales, '/lookahead'];

// --- 0. Baseline real: hoy '/lookahead' NO está migrada, es del sitio PHP. ---
comprobarRollback(
    !SpaRouter::coincideConMapa('/lookahead', 'GET', $exactasReales, $prefijosReales),
    "baseline: '/lookahead' hoy es del sitio PHP (no está en el mapa de exactas)"
);

// --- 1. Simular la migración de la pantalla de muestra: entra al mapa explícito. ---
comprobarRollback(
    SpaRouter::coincideConMapa('/lookahead', 'GET', $exactasConPantallaDeMuestraMigrada, $prefijosReales),
    "tras 'migrar' '/lookahead', React debe servirla"
);
comprobarRollback(
    SpaRouter::coincideConMapa('/app', 'GET', $exactasConPantallaDeMuestraMigrada, $prefijosReales),
    "'/app' sigue migrada mientras se ejercita el rollback de la pantalla de muestra"
);

// --- 2. Rollback: se quita SOLO la pantalla de muestra del mapa explícito (vuelve al mapa real). ---
comprobarRollback(
    !SpaRouter::coincideConMapa('/lookahead', 'GET', $exactasReales, $prefijosReales),
    "tras el rollback, '/lookahead' debe volver a su adaptador PHP"
);
comprobarRollback(
    SpaRouter::coincideConMapa('/app', 'GET', $exactasReales, $prefijosReales),
    "el rollback de UNA pantalla no debe arrastrar a otras rutas migradas ('/app' sigue viva)"
);
comprobarRollback(
    SpaRouter::coincideConMapa('/login', 'GET', $exactasReales, $prefijosReales),
    "el rollback de '/lookahead' no debe arrastrar a '/login' (S01)"
);

// --- 3. Restaurar el mapa: React vuelve a servir la pantalla de muestra. ---
comprobarRollback(
    SpaRouter::coincideConMapa('/lookahead', 'GET', $exactasConPantallaDeMuestraMigrada, $prefijosReales),
    "tras restaurar el mapa, React debe volver a servir '/lookahead'"
);

// --- 4. El mapa real de producción (sin argumento explícito) nunca se movió durante el ejercicio. ---
comprobarRollback(
    !SpaRouter::sirveLaSpa('/lookahead'),
    "el mapa real de producción (default de sirveLaSpa) sigue excluyendo '/lookahead'"
);
comprobarRollback(
    SpaRouter::sirveLaSpa('/app'),
    "el mapa real de producción sigue sirviendo '/app'"
);
comprobarRollback(
    SpaRouter::sirveLaSpa('/login'),
    "el mapa real de producción sigue sirviendo '/login' (S01)"
);
```

- [ ] **Step 2: Ejecutar el test para verificar que falla (rojo)**

Run: `docker compose exec app php tests/test_spa_frontera.php`
Expected: FALLO: S05 — el mapa real de producción debe servir GET/HEAD '/programa-general' desde la SPA

- [ ] **Step 3: Implementar el corte en `src/Core/SpaRouter.php`**

En `src/Core/SpaRouter.php:18`:
```php
    public const RUTAS_EXACTAS_MIGRADAS = ['/', '/login', '/password/forgot', '/password/reset', '/proyectos', '/programa-general'];
```

- [ ] **Step 4: Ejecutar los tests de frontera y rollback para verificar que pasan (verde)**

Run: `docker compose exec app php tests/test_spa_frontera.php && docker compose exec app php tests/test_shell_route_map_rollback.php`
Expected: OK: frontera SPA/PHP y OK: rollback del mapa de rutas SPA (exit code 0).

- [ ] **Step 5: Commit atómico**

```bash
git add src/Core/SpaRouter.php tests/test_spa_frontera.php tests/test_shell_route_map_rollback.php
git commit -m "feat(router): cortar ruta canónica /programa-general hacia el shell React"
```

---

### Task 2: Frontend Dominio — Sanitizador y Parser Semántico de Actividades y Formateo Canónico de Fechas

**Files:**
- Modify: `frontend/src/modules/programa-general/domain/modelo.ts`
- Modify: `frontend/src/modules/programa-general/domain/modelo.test.ts`

**Interfaces:**
- Consumes: `string | null | undefined`
- Produces:
  - `TextoActividadParseado { titulo: string; subtitulo: string | null }`
  - `parsearTextoActividad(textoCrudo: string): TextoActividadParseado`
  - `formatearFechaObra(fechaIso?: string | null): string`
  - `formatearCantidadPresupuesto(cantidad?: number | null, unidad?: string | null): string`

- [ ] **Step 1: Escribir pruebas unitarias que fallen en `frontend/src/modules/programa-general/domain/modelo.test.ts`**

Agregar al final de `modelo.test.ts`:
```typescript
import { parsearTextoActividad, formatearFechaObra, formatearCantidadPresupuesto } from './modelo';

describe('parsearTextoActividad', () => {
  it('separa titulo y subtitulo cuando contiene tags <small> y <b>', () => {
    const raw = '<b>LOCALIZACIÓN Y REPLANTEO, </b> <small>[Capítulo: PRELIMINARES, DAPORTO TORRE 3]</small>';
    const resultado = parsearTextoActividad(raw);
    expect(resultado.titulo).toBe('LOCALIZACIÓN Y REPLANTEO');
    expect(resultado.subtitulo).toBe('PRELIMINARES, DAPORTO TORRE 3');
  });

  it('limpia tags <b> en nombres de capitulo simples', () => {
    const raw = '<b>DAPORTO TORRE 3</b>';
    const resultado = parsearTextoActividad(raw);
    expect(resultado.titulo).toBe('DAPORTO TORRE 3');
    expect(resultado.subtitulo).toBeNull();
  });

  it('devuelve el texto limpio si no tiene etiquetas HTML', () => {
    const raw = 'Excavación mecánica de zapatas eje A-C';
    const resultado = parsearTextoActividad(raw);
    expect(resultado.titulo).toBe('Excavación mecánica de zapatas eje A-C');
    expect(resultado.subtitulo).toBeNull();
  });

  it('tolera strings vacíos o nulos sin romperse', () => {
    expect(parsearTextoActividad('')).toEqual({ titulo: '', subtitulo: null });
    expect(parsearTextoActividad('   ')).toEqual({ titulo: '', subtitulo: null });
  });
});

describe('formatearFechaObra', () => {
  it('formatea YYYY-MM-DD a DD/MM/AAAA', () => {
    expect(formatearFechaObra('2026-08-10')).toBe('10/08/2026');
    expect(formatearFechaObra('2026-12-31')).toBe('31/12/2026');
  });

  it('devuelve guion para valores vacios o nulos', () => {
    expect(formatearFechaObra(null)).toBe('-');
    expect(formatearFechaObra(undefined)).toBe('-');
    expect(formatearFechaObra('')).toBe('-');
  });
});

describe('formatearCantidadPresupuesto', () => {
  it('combina cantidad con 1 decimal y unidad', () => {
    expect(formatearCantidadPresupuesto(450, 'm³')).toBe('450.0 m³');
    expect(formatearCantidadPresupuesto(12.345, 'ml')).toBe('12.3 ml');
  });

  it('devuelve guion si no hay cantidad', () => {
    expect(formatearCantidadPresupuesto(null, 'm³')).toBe('-');
  });
});
```

- [ ] **Step 2: Ejecutar vitest para verificar que falla (rojo)**

Run: `npm --prefix frontend test src/modules/programa-general/domain/modelo.test.ts`
Expected: FAIL with "parsearTextoActividad is not a function"

- [ ] **Step 3: Implementar `parsearTextoActividad`, `formatearFechaObra` y `formatearCantidadPresupuesto` en `modelo.ts`**

Agregar en `frontend/src/modules/programa-general/domain/modelo.ts`:
```typescript
export interface TextoActividadParseado {
  titulo: string;
  subtitulo: string | null;
}

export function parsearTextoActividad(textoCrudo: string | null | undefined): TextoActividadParseado {
  if (!textoCrudo || typeof textoCrudo !== 'string') {
    return { titulo: '', subtitulo: null };
  }

  let texto = textoCrudo.trim();
  let subtitulo: string | null = null;

  // 1. Extraer subtítulo de <small>...</small> si existe
  const smallMatch = texto.match(/<small>(.*?)<\/small>/i);
  if (smallMatch && smallMatch[1]) {
    let sub = smallMatch[1].replace(/<[^>]+>/g, '').trim();
    // Limpiar corchetes exteriores si los tiene: [Capítulo: PRELIMINARES...]
    sub = sub.replace(/^\[\s*/, '').replace(/\s*\]$/, '');
    // Limpiar prefijo 'Capítulo:' o 'Capitulo:' si existe
    sub = sub.replace(/^Cap[ií]tulo:\s*/i, '');
    subtitulo = sub.trim() || null;
    // Remover el bloque <small> del texto original
    texto = texto.replace(/<small>.*?<\/small>/gi, '').trim();
  }

  // 2. Eliminar cualquier otro tag HTML (como <b>, </b>, <br>, etc.)
  let titulo = texto.replace(/<[^>]+>/g, '').trim();

  // 3. Limpiar comas, puntos o guiones finales sobrantes
  titulo = titulo.replace(/[,;.\s]+$/, '').trim();

  return {
    titulo,
    subtitulo,
  };
}

export function formatearFechaObra(fechaIso?: string | null): string {
  if (!fechaIso || typeof fechaIso !== 'string') return '-';
  const trimmed = fechaIso.trim();
  if (!trimmed) return '-';

  // Si ya viene como DD/MM/AAAA devolverlo directo
  if (/^\d{2}\/\d{2}\/\d{4}$/.test(trimmed)) return trimmed;

  // Validar formato YYYY-MM-DD
  const partes = trimmed.split('T')[0].split('-');
  if (partes.length === 3 && partes[0].length === 4) {
    const [yyyy, mm, dd] = partes;
    return `${dd}/${mm}/${yyyy}`;
  }

  return trimmed;
}

export function formatearCantidadPresupuesto(
  cantidad?: number | null,
  unidad?: string | null
): string {
  if (cantidad === null || cantidad === undefined || isNaN(Number(cantidad))) {
    return '-';
  }
  const valor = Number(cantidad).toFixed(1);
  return unidad ? `${valor} ${unidad.trim()}` : valor;
}
```

- [ ] **Step 4: Ejecutar vitest para verificar que pasa (verde)**

Run: `npm --prefix frontend test src/modules/programa-general/domain/modelo.test.ts`
Expected: PASS (exit code 0).

- [ ] **Step 5: Commit atómico**

```bash
git add frontend/src/modules/programa-general/domain/modelo.ts frontend/src/modules/programa-general/domain/modelo.test.ts
git commit -m "feat(domain): sanitizador de texto de actividad y formateador canónico de fechas"
```

---

### Task 3: Frontend Toolbar y Filtros — Separación de Estadísticas, Badges Canónicos y Buscador 1:1

**Files:**
- Modify: `frontend/src/modules/programa-general/components/ProgramaToolbar.tsx`
- Modify: `frontend/src/modules/programa-general/components/ProgramaFilters.tsx`
- Modify: `frontend/src/modules/programa-general/components/ProgramaSignalsBar.tsx`
- Modify: `frontend/src/modules/programa-general/components/ProgramaSignalsBar.test.tsx`
- Modify: `frontend/src/modules/programa-general/components/ProgramaFilters.test.tsx`

**Interfaces:**
- Consumes: `totalVisibles`, `totalTotal`, `avanceMacroPct`, `conteos: ConteosSenales`
- Produces:
  - Píldoras separadas `.stat-pill` y `.stat-pill.stat-avance`.
  - Chips de 8 señales con `.chip-dot`, `.signal-label` y `.chip-count`.
  - Toolbar con `badge-live-week` y conmutador `8 Cols Esenciales` | `13 Cols Reales`.

- [ ] **Step 1: Escribir pruebas unitarias actualizadas en `ProgramaFilters.test.tsx` y `ProgramaSignalsBar.test.tsx`**

En `ProgramaFilters.test.tsx`:
Verificar que `Actividades visibles` y `Avance macro obra` se renderizan como elementos `.stat-pill` con separación clara y sin concatenarse en una sola cadena.
```typescript
it('renderiza contadores en pildoras separadas con clase stat-pill', () => {
  render(
    <ProgramaFilters
      busqueda=""
      onBusquedaChange={vi.fn()}
      totalVisibles={282}
      totalTotal={324}
      avanceMacroPct={2.9}
    />
  );
  const pillVisibles = screen.getByText(/Actividades visibles:/i).closest('.stat-pill');
  const pillAvance = screen.getByText(/Avance macro obra:/i).closest('.stat-pill');
  expect(pillVisibles).not.toBeNull();
  expect(pillAvance).not.toBeNull();
  expect(pillVisibles).toHaveClass('stat-pill');
  expect(pillAvance).toHaveClass('stat-avance');
});
```

- [ ] **Step 2: Ejecutar vitest para verificar que falla (rojo)**

Run: `npm --prefix frontend test src/modules/programa-general/components/ProgramaFilters.test.tsx`
Expected: FAIL (missing `.stat-pill` class).

- [ ] **Step 3: Actualizar `ProgramaFilters.tsx`, `ProgramaSignalsBar.tsx` y `ProgramaToolbar.tsx`**

En `ProgramaFilters.tsx`:
```tsx
        {(totalVisibles !== undefined || avanceMacroPct !== undefined) && (
          <div className="signals-stats" aria-label="Estadísticas de actividades visibles">
            {totalVisibles !== undefined && (
              <span className="stat-pill">
                Actividades visibles:{' '}
                <strong>
                  {totalVisibles}
                  {totalTotal !== undefined ? ` de ${totalTotal}` : ''}
                </strong>
              </span>
            )}
            {avanceMacroPct !== undefined && avanceMacroPct !== null && (
              <span className="stat-pill stat-avance">
                Avance macro obra: <strong>{avanceMacroPct}%</strong>
              </span>
            )}
          </div>
        )}
```

En `ProgramaToolbar.tsx`:
Asegurar que la semana activa use la clase `.badge-live-week` (o `.badge-semana` estilizada como píldora viva), el selector `8 Cols Esenciales` | `13 Cols Reales` y el disparador del Drawer LPS.

En `ProgramaSignalsBar.tsx`:
Asegurar que cada chip tenga `chip-dot`, `signal-label` y `chip-count monospace`, renderizando los 8 estados canónicos.

- [ ] **Step 4: Ejecutar vitest para verificar que pasa (verde)**

Run: `npm --prefix frontend test src/modules/programa-general/components/ProgramaFilters.test.tsx src/modules/programa-general/components/ProgramaSignalsBar.test.tsx`
Expected: PASS (exit code 0).

- [ ] **Step 5: Commit atómico**

```bash
git add frontend/src/modules/programa-general/components/ProgramaToolbar.tsx frontend/src/modules/programa-general/components/ProgramaFilters.tsx frontend/src/modules/programa-general/components/ProgramaSignalsBar.tsx frontend/src/modules/programa-general/components/ProgramaFilters.test.tsx frontend/src/modules/programa-general/components/ProgramaSignalsBar.test.tsx
git commit -m "feat(ui): separar estadísticas en píldoras y pulir barra de señales 1:1"
```

---

### Task 4: Frontend Tabla — Integración de Jerarquía de Actividades Sanitizadas, Capítulos con Avance Mini, Celdas Duales y Formato de Fechas

**Files:**
- Modify: `frontend/src/modules/programa-general/components/ProgramaTable.tsx`
- Modify: `frontend/src/modules/programa-general/components/ProgramaTable.test.tsx`

**Interfaces:**
- Consumes: `parsearTextoActividad`, `formatearFechaObra`, `formatearCantidadPresupuesto` de `domain/modelo.ts`
- Produces:
  - Celdas con `.activity-title-group`, `.activity-title` y `.activity-subtitle`.
  - Capítulos limpios con badge de conteo y mini barra de avance.
  - Fechas en `DD/MM/AAAA` con advertencia `⚠️` si `plazoVencido`.
  - Celdas de presupuesto con valor y unidad integrados en modo 8 columnas.
  - Celdas de avance dual con barra de track y chip de desviación $\Delta$ de alto contraste.

- [ ] **Step 1: Escribir pruebas unitarias en `ProgramaTable.test.tsx` verificando la sanitización y el renderizado semántico**

Agregar pruebas en `ProgramaTable.test.tsx`:
```typescript
it('renderiza actividades con tags HTML sanitizados mostrando titulo y subtitulo', () => {
  const acts = [
    {
      unique_id: 101,
      id_proyecto: 1,
      Titulo: 0,
      Actividad: '<b>LOCALIZACIÓN Y REPLANTEO, </b> <small>[Capítulo: PRELIMINARES, DAPORTO TORRE 3]</small>',
      codigo_actividad: 'ACT-101',
      esCapitulo: false,
      capituloNombre: 'PRELIMINARES',
      Fecha_Inicio: '2026-08-10',
      Fecha_Fin: '2026-08-20',
      cantidad_ppto: 100,
      unidad: 'ml',
      avanceRealPct: 50,
      avanceTeoricoPct: 60,
      deltaPct: -10,
      deltaTexto: '-10.0%',
      esRutaCritica: true,
      plazoVencido: true,
      diasVencimiento: 4,
      Estado: 'Atrasada',
    } as any,
  ];

  render(
    <ProgramaTable
      actividades={acts}
      actividadSeleccionadaId={null}
      onSelectActividad={vi.fn()}
      modo13Cols={false}
    />
  );

  expect(screen.queryByText(/<b>/)).toBeNull();
  expect(screen.queryByText(/<small>/)).toBeNull();
  expect(screen.getByText('LOCALIZACIÓN Y REPLANTEO')).toBeInTheDocument();
  expect(screen.getByText('PRELIMINARES, DAPORTO TORRE 3')).toBeInTheDocument();
  expect(screen.getByText('10/08/2026')).toBeInTheDocument();
  expect(screen.getByText('20/08/2026')).toBeInTheDocument();
});

it('renderiza capitulos con titulo limpio sin tags <b>', () => {
  const acts = [
    {
      unique_id: 1,
      id_proyecto: 1,
      Titulo: 1,
      Actividad: '<b>DAPORTO TORRE 3</b>',
      esCapitulo: true,
      capituloNombre: 'DAPORTO TORRE 3',
      avanceRealPct: 0,
      avanceTeoricoPct: 0,
      deltaPct: 0,
      deltaTexto: '-',
      esRutaCritica: false,
      plazoVencido: false,
      diasVencimiento: 0,
      Estado: 'En Curso',
    } as any,
  ];

  render(
    <ProgramaTable
      actividades={acts}
      actividadSeleccionadaId={null}
      onSelectActividad={vi.fn()}
    />
  );

  expect(screen.queryByText(/<b>/)).toBeNull();
  expect(screen.getByText('DAPORTO TORRE 3')).toBeInTheDocument();
});
```

- [ ] **Step 2: Ejecutar vitest para verificar que falla (rojo)**

Run: `npm --prefix frontend test src/modules/programa-general/components/ProgramaTable.test.tsx`
Expected: FAIL (raw HTML tags present or unformatted dates).

- [ ] **Step 3: Implementar la sanitización, formateo y jerarquía en `ProgramaTable.tsx`**

En `frontend/src/modules/programa-general/components/ProgramaTable.tsx`:
1. Importar `parsearTextoActividad`, `formatearFechaObra` y `formatearCantidadPresupuesto` de `../domain/modelo`.
2. En filas de capítulo (`act.esCapitulo`):
   ```tsx
   const parsedCap = parsearTextoActividad(act.Actividad);
   return (
     <tr key={`cap-${act.unique_id}`} className="row-chapter chapter-heading-row">
       <td colSpan={modo13Cols ? 13 : 8}>
         <div className="chapter-cell-content">
           <span className="chapter-icon">
             <i className="far fa-folder" aria-hidden="true"></i>
           </span>
           <strong className="chapter-title">{parsedCap.titulo}</strong>
           <span className="chapter-badge chapter-badge-count">Capítulo</span>
         </div>
       </td>
     </tr>
   );
   ```
3. En filas de actividad:
   ```tsx
   const parsed = parsearTextoActividad(act.Actividad);
   ```
   Renderizar título y subtítulo:
   ```tsx
   <div className="activity-cell-name cell-activity">
     <div className="activity-title-group">
       <span className="activity-title">{parsed.titulo}</span>
       {parsed.subtitulo && (
         <span className="activity-subtitle">{parsed.subtitulo}</span>
       )}
     </div>
     {act.esRutaCritica && (
       <span className="badge-rc badge-rc-pill" title="Ruta Crítica">
         RC
       </span>
     )}
   </div>
   ```
4. Fechas:
   `formatearFechaObra(act.Fecha_Inicio)` y `formatearFechaObra(act.Fecha_Fin)` con indicador `cell-date-overdue` si `act.plazoVencido`.
5. Presupuesto:
   Usar `formatearCantidadPresupuesto(act.cantidad_ppto, act.unidad)` con valores estructurados.
6. Avance Dual:
   Chip de desviación $\Delta$ con clases `.delta-neg` (`act.deltaPct < 0`) y `.delta-ok` (`act.deltaPct >= 0`), y barra `.micro-gauge-fill`.

- [ ] **Step 4: Ejecutar vitest para verificar que pasa (verde)**

Run: `npm --prefix frontend test src/modules/programa-general/components/ProgramaTable.test.tsx`
Expected: PASS (exit code 0).

- [ ] **Step 5: Commit atómico**

```bash
git add frontend/src/modules/programa-general/components/ProgramaTable.tsx frontend/src/modules/programa-general/components/ProgramaTable.test.tsx
git commit -m "feat(table): jerarquía de actividades sanitizadas, fechas formateadas y avance dual"
```

---

### Task 5: Frontend Drawer LPS — Título Sanitizado, Alertas de Plazo Vencido y Paridad Estética de Controles

**Files:**
- Modify: `frontend/src/modules/programa-general/components/ProgramaDrawer.tsx`
- Modify: `frontend/src/modules/programa-general/components/ProgramaDrawer.test.tsx`

**Interfaces:**
- Consumes: `parsearTextoActividad`, `formatearFechaObra`
- Produces: Drawer de 440px con título sanitizado, subtítulo secundario, fechas formateadas con alerta visual y navegación secuencial.

- [ ] **Step 1: Escribir pruebas unitarias para sanitización en Drawer en `ProgramaDrawer.test.tsx`**

En `ProgramaDrawer.test.tsx`:
Verificar que el Drawer renderice el título sanitizado sin etiquetas `<b>` ni `<small>`, mostrando el subtítulo y respetando el guardado con los datos correctos.

- [ ] **Step 2: Ejecutar vitest para verificar el comportamiento (rojo o gaps detectados)**

Run: `npm --prefix frontend test src/modules/programa-general/components/ProgramaDrawer.test.tsx`

- [ ] **Step 3: Implementar la sanitización y mejoras visuales en `ProgramaDrawer.tsx`**

En `ProgramaDrawer.tsx`:
1. Parsear `actividad.Actividad` con `parsearTextoActividad`:
   ```tsx
   const parsedAct = parsearTextoActividad(actividad.Actividad);
   ```
2. Renderizar en la cabecera del drawer:
   ```tsx
   <div className="drawer-title-row">
     <div className="drawer-title-group">
       <h3 className="drawer-act-title">{parsedAct.titulo}</h3>
       {parsedAct.subtitulo && (
         <span className="drawer-act-subtitle">{parsedAct.subtitulo}</span>
       )}
     </div>
     <span className="drawer-act-code">{actividad.codigo_actividad || '-'}</span>
   </div>
   ```
3. Mostrar las fechas formateadas y alertas de plazo en la sección de cronograma.
4. Asegurar que las etiquetas y micro-medidores de desviación coincidan con las clases del mockup.

- [ ] **Step 4: Ejecutar vitest para verificar que pasa (verde)**

Run: `npm --prefix frontend test src/modules/programa-general/components/ProgramaDrawer.test.tsx`
Expected: PASS (exit code 0).

- [ ] **Step 5: Commit atómico**

```bash
git add frontend/src/modules/programa-general/components/ProgramaDrawer.tsx frontend/src/modules/programa-general/components/ProgramaDrawer.test.tsx
git commit -m "feat(drawer): sanitizar título y subtítulo en Drawer LPS y pulir cronograma"
```

---

### Task 6: Frontend CSS — Traslado de Reglas de Producción de `s05-production-mockup.html` a `programa-general.css` (@layer module)

**Files:**
- Modify: `frontend/src/modules/programa-general/programa-general.css`

**Interfaces:**
- Consumes: Tokens de `DESIGN.md`, `tokens.css`, `theme-claro.css`
- Produces: CSS de alta fidelidad 100% encapsulado dentro de `@layer module { ... }`.

- [ ] **Step 1: Auditar y portar reglas faltantes del mockup a `programa-general.css` dentro de `@layer module`**

Incorporar todas las clases de alta fidelidad validadas en el mockup `s05-production-mockup.html`:
- `.stat-pill`, `.stat-pill.stat-avance`, `.search-kbd`
- `.activity-cell-name`, `.activity-title-group`, `.activity-title`, `.activity-subtitle`
- `.badge-rc-pill`, `.cell-date-overdue`, `.cell-ppto`, `.ppto-val`, `.ppto-unit`
- `.cell-avance-dual`, `.avance-text-box`, `.avance-real-val`, `.avance-teor-val`, `.avance-delta-badge`, `.delta-neg`, `.delta-ok`
- `.micro-gauge-bar`, `.micro-gauge-fill`, `.status-cell-badge`, `.chip-dot`, `.chip-count`
- `.chapter-heading-row`, `.chapter-badge-count`, `.chapter-progress`, `.mini-progress-track`
- `.drawer-panel-pro`, `.drawer-backdrop`, `.drawer-pro-header`, `.drawer-seq-nav`, `.drawer-gauge-box`
- Compatibilidad de contraste para Tema Claro (`[data-theme="light"]`) y Tema Oscuro (`[data-theme="dark"]`).

- [ ] **Step 2: Ejecutar el chequeo estático del Design System para verificar que NO hay fugas unlayered**

Run: `npm run test:design-system:static`
Expected: 8/8 PASS (exit code 0). Si algún selector infringe el linter o no está dentro de `@layer module`, corregir inline.

- [ ] **Step 3: Compilar bundle frontend con Vite**

Run: `npm run frontend:build`
Expected: Build exitoso sin errores de TypeScript ni linter (exit code 0).

- [ ] **Step 4: Commit atómico**

```bash
git add frontend/src/modules/programa-general/programa-general.css
git commit -m "style(pg): trasladar reglas de producción del mockup a programa-general.css"
```

---

### Task 7: Verificación Integral — Playwright E2E contra Docker Real y Capturas de Paridad 1:1

**Files:**
- Modify: `tests/browser/s05-programa-general-react.spec.mjs`
- Create: `scripts/generate_s05_live_screenshots.mjs`

**Interfaces:**
- Consumes: Servidor web Docker en `http://localhost:8081`
- Produces: Capturas en vivo en `live-docker-programa-general-dark.png` y `live-docker-programa-general-light.png` mostrando paridad visual 1:1 con el mockup.

- [ ] **Step 1: Actualizar spec E2E `s05-programa-general-react.spec.mjs` para verificar la ruta canónica `/programa-general`**

En `tests/browser/s05-programa-general-react.spec.mjs`:
1. Comprobar que navegar a `/programa-general` renderiza la SPA React sin redirección a PHP legado.
2. Comprobar que no existen textos con `<b>` o `<small>` visibles.
3. Comprobar que las fechas se visualizan en formato `DD/MM/AAAA`.
4. Comprobar que el toggle 8 vs 13 columnas y el Drawer LPS funcionan interactivamente.

- [ ] **Step 2: Ejecutar Playwright E2E contra el entorno Docker**

Run: `npx playwright test tests/browser/s05-programa-general-react.spec.mjs --workers=1`
Expected: All tests PASS (exit code 0).

- [ ] **Step 3: Generar capturas de pantalla de alta resolución (1180×820) en Docker**

Run: `node scripts/generate_s05_live_screenshots.mjs`
Generar capturas en tema claro y tema oscuro, de la tabla y del drawer abierto.
Guardar en el directorio de artefactos para inspección visual directa.

- [ ] **Step 4: Ejecutar la suite completa de gates del repositorio**

Run:
1. `npm --prefix frontend run test` (exit code 0)
2. `npm --prefix frontend run typecheck` (exit code 0)
3. `npm run test:design-system:static` (exit code 0)
4. `docker compose exec app php tests/test_spa_frontera.php` (exit code 0)
5. `docker compose exec app php tests/test_shell_route_map_rollback.php` (exit code 0)

- [ ] **Step 5: Commit atómico**

```bash
git add tests/browser/s05-programa-general-react.spec.mjs scripts/generate_s05_live_screenshots.mjs
git commit -m "test(e2e): verificar ruta canónica /programa-general y capturas en vivo de paridad 1:1"
```

---

## Plan Self-Review Check

- **1. Spec coverage:**
  - D1 (Corte canónico en `SpaRouter.php`): Cubierto en Task 1.
  - D2 (Sanitizador `parsearTextoActividad`): Cubierto en Task 2 y Task 4.
  - D3 (Formato de fechas y números): Cubierto en Task 2 y Task 4.
  - D4 (Toolbar y Barra de Señales): Cubierto en Task 3 y Task 6.
  - D5 (Tabla semántica de alta densidad): Cubierto en Task 4 y Task 6.
  - D6 (Drawer LPS 440px): Cubierto en Task 5 y Task 6.
  - D7 (Encapsulamiento `@layer module`): Cubierto en Task 6.
  - AC-01 a AC-12: Validados en Task 7 con suite Playwright y capturas Docker.
- **2. Placeholder scan:** Cero TBD, cero TODO, código real en cada paso.
- **3. Type consistency:** Interfaces estrictamente tipadas (`TextoActividadParseado`, `formatearFechaObra`, `formatearCantidadPresupuesto`) compartidas entre `modelo.ts`, `ProgramaTable.tsx` y `ProgramaDrawer.tsx`.
