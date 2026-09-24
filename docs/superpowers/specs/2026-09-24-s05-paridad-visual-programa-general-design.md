---
capa: fuente
tipo: spec
estado: vigente
id: S05-PARIDAD-VISUAL
fecha: 2026-09-24
superficie: programa-general
rutas: ["/programa-general", "/app/programa-general"]
depende_de: [S05, T01, T02]
version: 1.0
areas: [frontend, design-system, ux]
fuente: "auditoría visual de public/mockups/s05-production-mockup.html, live-docker en http://localhost:8081/programa-general, SpaRouter.php, ProgramaTable.tsx y programa-general.css, 2026-09-24"
resumen: "Elevación estética y paridad visual 1:1 de Programa General en React contra el mockup de producción aprobado (public/mockups/s05-production-mockup.html), corte canónico de /programa-general en SpaRouter, parseo de texto HTML legado en actividades, formateo de fechas y estilización canónica de chips de señales, tabla de 8 columnas y Drawer LPS."
---

# S05-PARIDAD-VISUAL — Elevación Estética y Paridad 1:1 de Programa General en React

> **Estado:** diseño técnico autorrevisado según el estándar de `superpowers:brainstorming`.
> **Principio de Calidad:** Lo que se aprueba en el mockup visual de producción (`public/mockups/s05-production-mockup.html`) debe reflejarse con total fidelidad 1:1 en el código real de producción al ejecutarse contra la base de datos real en Docker.

---

## 1. Problema y Diagnóstico

Tras completar y fusionar la migración técnica S05 a `main` (PR #58), la inspección visual en el navegador real (`http://localhost:8081/programa-general` y `http://localhost:8081/app/programa-general`) reveló cuatro discrepancias críticas frente al diseño aprobado:

1. **Ruta canónica desfasada (`/programa-general` vs `/app/programa-general`):**
   - `SpaRouter.php` mantiene `public const RUTAS_EXACTAS_MIGRADAS = ['/', '/login', '/password/forgot', '/password/reset', '/proyectos']`.
   - Como `/programa-general` no está listada, una visita a `http://localhost:8081/programa-general` cae al controlador PHP legado (`ProgramaGeneralController::index`), sirviendo la vista vieja con Handsontable, AdminLTE y sidebar verde desalineado.
   - Sólo la ruta de prefijo piloto `/app/programa-general` llegaba al shell React.
2. **Etiquetas HTML crudas en nombres de actividades y capítulos:**
   - La base de datos MySQL real (`Da Porto` y proyectos estándar) almacena cadenas HTML heredadas en la columna `Actividad`:
     `<b>LOCALIZACIÓN Y REPLANTEO, </b> <small>[Capítulo: PRELIMINARES, DAPORTO TORRE 3]</small>`
     `<b>DAPORTO TORRE 3</b>`
   - React escapa estas cadenas por seguridad contra XSS, mostrándolas literalmente como texto sin formato.
3. **Colisión de texto y fechas sin formatear:**
   - En el toolbar superior, el contador de actividades y el indicador de avance colisionan sin margen: `Actividades visibles: 324 de 282Avance macro obra: 2.9%`.
   - Las fechas de inicio y fin se muestran en formato ISO crudo (`2026-06-22`) en lugar del formato de obra estándar (`22/06/2026`).
4. **Fidelidad y tokens de `programa-general.css`:**
   - La implementación previa de `programa-general.css` no trasladó la riqueza de estilos del mockup: faltaban los fondos y bordes de los chips de señales, los badges de código WBS, el track del micro-medidor de avance con barra de progreso estilizada, el chip de desviación $\Delta$ de alto contraste, y la estilización de las filas de capítulos con barra de progreso acumulada.

---

## 2. Decisiones de Arquitectura y Diseño

### D1. Corte de la ruta canónica en `SpaRouter.php`
- Se incorpora `'/programa-general'` al arreglo `SpaRouter::RUTAS_EXACTAS_MIGRADAS`.
- La ruta principal `http://localhost:8081/programa-general` será servida directamente por `SpaHostRenderer` y el shell React, alcanzando paridad con `/proyectos` y `/login`.
- El prefijo de convivencia `/app/programa-general` se mantiene plenamente operativo para compatibilidad con suites E2E existentes.

### D2. Sanitizador y Parser Semántico de Actividades (`parsearTextoActividad`)
Se crea una función de dominio pura en `domain/modelo.ts`:
```typescript
export interface TextoActividadParseado {
  titulo: string;
  subtitulo: string | null;
}

export function parsearTextoActividad(textoCrudo: string): TextoActividadParseado;
```
- **Comportamiento:**
  1. Si contiene etiquetas `<small>...</small>`, extrae el contenido interior (eliminando corchetes `[` `]` o prefijos redundantes como `Capítulo:`) y lo asigna a `subtitulo`.
  2. Remueve todas las etiquetas HTML (`<b>`, `</b>`, `<small>`, `</small>`, `<br>`, etc.).
  3. Limpia comas o espacios finales sueltos (ej. `LOCALIZACIÓN Y REPLANTEO, ` $\to$ `LOCALIZACIÓN Y REPLANTEO`).
  4. En capítulos, elimina etiquetas `<b>` para dejar el nombre del capítulo limpio y legible.
- **Renderizado semántico en la celda:**
  ```tsx
  <div className="activity-cell-name cell-activity">
    <div className="activity-title-group">
      <span className="activity-title">{parsed.titulo}</span>
      {parsed.subtitulo && <span className="activity-subtitle">{parsed.subtitulo}</span>}
    </div>
    {act.esRutaCritica && <span className="badge-rc badge-rc-pill" title="Ruta Crítica">RC</span>}
  </div>
  ```

### D3. Formato Canónico de Fechas y Números
- Función pura `formatearFechaObra(fechaIso: string | null): string`:
  - Recibe `YYYY-MM-DD` y devuelve `DD/MM/AAAA` (ej. `2026-08-10` $\to$ `10/08/2026`). Si es nulo o vacío, devuelve `-`.
- En caso de plazo vencido en la fecha de fin:
  - Muestra la fecha en rojo tenue con icono de alerta: `<span className="cell-date-overdue">20/08/2026 ⚠️</span>` y tooltip con los días de retraso.
- Presupuesto: Muestra el valor formateado con 1 decimal y la unidad (`450.0 m³`). Si no tiene cantidad, muestra `-`.

### D4. Toolbar Superior y Barra de Señales 1:1
- **Top Header:**
  - Título `Programa General` acompañado del badge de semana activa: `<span className="badge-live-week">Semana 33 Vigente</span>`.
  - Conmutador de píldora: `8 Cols Esenciales` | `13 Cols Reales` con estado activo iluminado.
  - Botón disparador del Drawer: `<button className="btn-drawer-trigger"><i className="fas fa-columns"></i> Drawer LPS</button>`.
  - Botones de acción: `CSV` y `Corte XLSX` con iconos FontAwesome.
- **Barra de Señales:**
  - Input de búsqueda con icono de lupa, placeholder descriptivo y badge `⌘K`.
  - Estadísticas separadas limpiamente:
    `<span className="stat-pill">Actividades visibles: <strong>{visibles} de {total}</strong></span>`
    `<span className="stat-pill stat-avance">Avance macro obra: <strong>{avanceMacro}%</strong></span>`
  - Chips de señales canónicos (`Atrasada`, `Con Alerta`, `Debe Iniciar`, `En Curso`, `Actividad Futura`, `Terminada`, `Fuera de Ventana`, `Sin Datos`):
    - Con punto de color (`.chip-dot`), etiqueta y contador en badge monospace (`.chip-count`).
    - Paleta de color basada en `--ds-state-*` de `DESIGN.md`.

### D5. Tabla Semántica de Alta Densidad (1180×820)
- Encabezado fijo (`position: sticky; top: 0; z-index: 5`) con tipografía condensada mayúscula y separadores sutiles.
- Filas de capítulos (`.chapter-heading-row`):
  - Fondo diferenciado `--ds-surface-raised`.
  - Icono de carpeta `📁`.
  - Título en negrita limpia.
  - Badge numérico con cantidad de actividades (ej. `8 actividades`).
  - Barra de progreso mini del capítulo a la derecha (`Avance 45%` con micro-track).
- Filas de actividades:
  - Altura compacta (36px).
  - Efecto hover con `--ds-surface-card` y fila seleccionada con borde lateral o fondo iluminado `--ds-surface-raised`.
  - Columna de avance dual:
    - Línea de números: `25.0% / 50.0%` junto al chip de desviación $\Delta$ (`-25.0%` en rojo, `+5.0%` en verde).
    - Micro-gauge: pista de fondo `--ds-surface-raised` y barra de progreso `--aia-corporate` o de estado.
  - Columna de estado: Píldora de estado centrada con punto y etiqueta (`.status-pill`).

### D6. Cajón Contextual LPS (440px)
- Migas de pan superiores: `1. Cimentación > Actividad 101`.
- Navegación secuencial: Botones `Anterior [` y `Siguiente ]` con atajos de corchetes.
- Secciones organizadas con cabecera en mayúsculas y bordes sutiles:
  1. **Plazos y Cronograma:** Fechas de inicio/fin, cálculo de duración contractual y alerta de plazo vencido.
  2. **Responsables y Asignaciones:** Selector de `Profesional AIA` y `Subcontratista` con aviso de cascada hacia Lookahead S07.
  3. **Presupuesto y Avance Físico:** Unidad de medida, cantidad de presupuesto, avance teórico del servidor, avance real y medidor gráfico dual de desviación $\Delta$.
  4. **Recursos Lean & Bitácora SOS:** Matriz de los 7 recursos y comentarios contextuales.
- Barra inferior fija con botones: `Descartar (Esc)` y `Guardar Cambios (⌘S)`.

### D7. Encapsulamiento Estricto en `@layer module`
- Todo el CSS se aloja en `frontend/src/modules/programa-general/programa-general.css` envuelto en `@layer module { ... }`.
- Cero reglas unlayered en el bundle generado por Vite.
- Compatibilidad perfecta con tokens de **Tema Claro** y **Tema Oscuro** según `theme-claro.css` y `tokens.css`.

---

## 3. Criterios de Aceptación (AC)

- **AC-01 (Ruta Canónica):** `http://localhost:8081/programa-general` responde 200 y renderiza el shell React de Programa General, no la vista PHP legada.
- **AC-02 (Cero Etiquetas HTML):** Ninguna celda de la tabla ni encabezado de capítulo muestra etiquetas HTML crudas (`<b>`, `<small>`). Todo texto se sanitiza y estructura semánticamente.
- **AC-03 (Jerarquía de Actividad):** Las actividades con capítulo o WBS secundario muestran el título principal destacado y el subtexto en tono secundario.
- **AC-04 (Formato de Fechas):** Las fechas en tabla y drawer se visualizan en formato `DD/MM/AAAA`. Fechas vencidas incluyen alerta visual `⚠️`.
- **AC-05 (Separación de Estadísticas):** El conteo de actividades y el avance macro de la obra están visualmente separados en píldoras independientes sin colisión.
- **AC-06 (Paleta de Chips de Señales):** Los 8 chips de estado reflejan la paleta canónica de `DESIGN.md` con punto de color, texto y contador monospace.
- **AC-07 (Doble Medidor de Avance con $\Delta$):** La celda de avance en 8 columnas muestra porcentaje real, teórico, chip con $\Delta$ (rojo/verde) y micro-barra de progreso con track.
- **AC-08 (Filas de Capítulos):** Los capítulos muestran icono de carpeta, nombre limpio, contador de actividades y barra de avance de capítulo.
- **AC-09 (Drawer LPS Pulido):** El Drawer LPS de 440px presenta la misma calidad visual del mockup, con navegación secuencial, alerta de plazos y medidor de desviación física dual.
- **AC-10 (Paridad de Temas):** Tanto en tema oscuro como en tema claro, todos los componentes resuelven correctamente contraste AA y legibilidad tipográfica.
- **AC-11 (Cero Fuga Sin Capa):** `npm run test:design-system:static` pasa 8/8 gates en verde sin reportar hojas ni reglas sin capa.
- **AC-12 (E2E y Screenshots):** La suite Playwright verifica la pantalla en vivo contra Docker y genera capturas actualizadas idénticas al mockup.

---

## 4. Plan de Verificación

1. **Tests unitarios frontend:**
   - Pruebas para `parsearTextoActividad` cubriendo casos con `<b>`, `<small>`, texto plano, tags malformados y caracteres especiales.
   - Pruebas para `formatearFechaObra`.
   - Ejecutar: `npm --prefix frontend run test` (todos en verde, exit code 0).
2. **Chequeo de tipos y build:**
   - `npm --prefix frontend run typecheck` (`tsc --noEmit`, exit code 0).
   - `npm run frontend:build` (exit code 0).
3. **Gates estáticos del Design System:**
   - `npm run test:design-system:static` (8/8 checks PASS, exit code 0).
4. **Verificación Playwright en navegador en vivo:**
   - `npx playwright test tests/browser/s05-programa-general-react.spec.mjs` (exit code 0).
   - Captura de pantallas en Docker real (`http://localhost:8081/programa-general`) confirmando la paridad 1:1 con el mockup.
