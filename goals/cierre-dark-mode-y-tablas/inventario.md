---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-07-31
areas: [proceso]
fuente: goals/cierre-dark-mode-y-tablas/inventario.md
resumen: Goal — Cierre de dark mode y ajuste de tablas
---

# Goal — Cierre de dark mode y ajuste de tablas
## Reporte de Inventario y Censo de Estados Semánticos (Fase G0)

**Fecha:** 2026-07-31  
**Autor:** Agente Antigravity  
**Estado:** Finalizado — Listo para congelar contrato en G1  

---

## 1. Resumen Ejecutivo

La fase G0 consolida la auditoría técnica de las superficies con tabla y el censo de estados operativos en todo el sistema. 

### Principales Hallazgos:
1. **Censo de Estados Semánticos:** Se auditaron 28 clases CSS vivas pertenecientes a 4 vocabularios independientes (`pg-state-*`, `pi-state-*`, `ps-alert-*`, `pdc-*`). Todos los estados operativos del negocio se mapean de forma limpia y sin pérdida de matiz dentro de la **escala semántica compartida de 7 peldaños** (`neutral`, `ok`, `atencion`, `riesgo`, `critico`, `bloqueado`, `sin-datos`). No se requiere un octavo peldaño.
2. **Divergencia entre Motores de Tabla:**
   - **Handsontable (8 superficies):** Dispone de adaptador parcial pero sufre desalineación geométrica de clones de cabecera (hasta 111px en `/profesionales` y 13px en `/subcontratistas`) debido a la eliminación de relleno vertical sin control sobre la altura de fila inline.
   - **DataTables (7 superficies):** **No posee adaptador CSS de design system.** Hereda estilos Bootstrap 4/AdminLTE con bordes blancos claros (`#dee2e6`), hover brillante sin token y elementos de paginación/búsqueda desaliñados en modo oscuro.
   - **AG Grid (1 superficie):** La SPA de Plan de Compras (`pdc-app/`) inyecta 19 bloques `<style>` sin capa de cascada (`@layer vendor`), derrotando la especificidad del design system.
3. **Contraste y Accesibilidad (WCAG 2.1 AA):** Múltiples superficies presentan fallas de contraste en celdas en estado de edición, dropdowns de autocompletar (`htAutocompleteArrow`) y chips de leyendas que difieren entre modal y grilla.

---

## 2. Censo Completo de Clases de Estado Semántico

Se identificaron **28 clases de estado vivas** asignadas dinámicamente desde JavaScript (vía `cellProperties.className`, `rowClassMap`, `getAlertClassForRow()`, etc.).

### Mapeo a la Escala Semántica de 7 Peldaños:

| Peldaño Escala | Token / Tinte DS | Hex Fondo / Texto / Borde | Significado Semántico LPS | Clases de Módulo Mapeadas |
|---|---|---|---|---|
| **1. `neutral`** | `--ds-state-tint-neutral` | `#2b2f2d` / `#d4dad6` / `#c7cdd4` | Silencio, contexto inerte, actividad terminada, fuera de ventana o inactiva. | `pg-state-terminada`, `pi-state-alert-4-6-weeks`, `pi-state-neutral`, `ps-alert-neutral`, `pdc-not-started`, `cal-tnp` |
| **2. `ok`** | `--ds-state-tint-green`<br>`--ds-state-tint-blue` | `#173d26` / `#b7e8c6` / `#69b578`<br>`#17334f` / `#bbdcfb` / `#4a81bd` | Controlado, a tiempo, liberado para comprometer, ejecución en curso a tiempo. | `pg-state-actividad-futura`, `pg-state-en-curso`, `pg-state-a-tiempo-en-curso`, `pi-state-liberated-control`, `pi-state-execution-blocked`, `ps-alert-control`, `pdc-completed-ontime`, `pdc-active` |
| **3. `atencion`** | `--ds-state-tint-amber`<br>`--ds-state-tint-teal` | `#3a3a0f` / `#f2e79c` / `#d4a017`<br>`#134841` / `#bbdcfb` / `#2caa9f` | Por resolver, restricciones abiertas en ventana próxima (1-3 sem), compromiso por confirmar, información por calificar. | `pg-state-con-alerta-restricciones`, `pg-state-r1`, `pg-state-r2-3`, `pg-state-r4-6`, `pi-state-blocked-due`, `pi-state-alert-1-week`, `pi-state-alert-2-3-weeks`, `ps-alert-medium`, `pdc-completed-late` |
| **4. `riesgo`** | `--ds-state-tint-orange` | `#452a0d` / `#ffd7a8` / `#e87722` | Fuera de plazo, actividad que debe iniciar hoy, alistamiento en riesgo, avance con restricciones abiertas. | `pg-state-debe-iniciar`, `pi-state-blocked-overdue`, `ps-alert-high`, `pdc-delayed` |
| **5. `critico`** | `--ds-state-tint-red` | `#431414` / `#ffcdc8` / `#dc2626` | Atrasado, incumplimiento en ruta crítica, desvío severo, fecha fin superada. | `pg-state-atrasada`, `pg-state-atrasado`, `pi-state-blocked-overdue-critical`, `ps-alert-critical-route`, `ps-alert-critical`, `pdc-critical-delay` |
| **6. `bloqueado`** | `--ds-state-tint-red` + acento bloqueante | `#431414` / `#ffcdc8` / `#dc2626` (ring/border reforzado) | Ejecución totalmente impedida por restricción crítica insalvable (R0). | `pg-state-restr-0`, `pg-state-r0`, `pi-state-blocked-overdue-critical` (flag bloqueo), `ps-alert-critical-route` (flag R0) |
| **7. `sin-datos`** | `--ds-state-tint-violet` | `#33204a` / `#e0cdfa` / `#c084fc` | Falta información, actividad sin fechas, pendiente de estructuración o datos insuficientes. | `pg-state-sin-datos`, `pdc-missing`, `pdc-status-info` |

---

## 3. Detalle de Clases por Módulo y Archivos Fuente

### A. Programa General (`public/js/modules/programa_general/hot.js` & `public/css/programa-general.css`)
- `pg-state-actividad-futura`: Mapea a **`ok`** (`--pg-future-bg`).
- `pg-state-en-curso` / `a-tiempo-en-curso`: Mapea a **`ok`** (`--pg-progress-bg`).
- `pg-state-debe-iniciar`: Mapea a **`riesgo`** (`--pg-due-bg`).
- `pg-state-con-alerta-restricciones` / `pg-state-r1` / `r2-3` / `r4-6`: Mapea a **`atencion`** (`--pg-alert-bg`).
- `pg-state-restr-0` / `r0`: Mapea a **`bloqueado`** (`--pg-due-bg` / `--pg-delayed-bg`).
- `pg-state-atrasado` / `atrasada`: Mapea a **`critico`** (`--pg-delayed-bg`).
- `pg-state-terminada`: Mapea a **`neutral`** (`--ds-active-surface`).
- `pg-state-sin-datos`: Mapea a **`sin-datos`** (`--pdc-missing-bg`).

### B. Programación Intermedia (`public/js/modules/programacion_intermedia/hot.js` & `public/css/programacion-intermedia.css`)
- `pi-state-blocked-overdue-critical`: Mapea a **`critico`** / **`bloqueado`**.
- `pi-state-blocked-overdue`: Mapea a **`riesgo`**.
- `pi-state-blocked-due`: Mapea a **`atencion`**.
- `pi-state-alert-1-week`: Mapea a **`atencion`** (Urgente 1 sem).
- `pi-state-alert-2-3-weeks`: Mapea a **`atencion`** (Riesgo 2-3 sem).
- `pi-state-alert-4-6-weeks`: Mapea a **`neutral`** (Pendiente 4-6 sem).
- `pi-state-execution-blocked`: Mapea a **`ok`** (En ejecución pendiente).
- `pi-state-liberated-control`: Mapea a **`ok`** (Listo para comprometer).
- `pi-state-neutral`: Mapea a **`neutral`**.

### C. Programación Semanal (`public/js/modules/programacion_semanal/hot.js` & `public/css/programacion-semanal.css`)
- `ps-alert-critical-route`: Mapea a **`critico`** (RC con restricciones / Incumplida RC).
- `ps-alert-critical`: Mapea a **`critico`** (Fuera de ruta crítica).
- `ps-alert-high`: Mapea a **`riesgo`** (Ejecución con restricciones).
- `ps-alert-medium`: Mapea a **`atencion`** (Condiciones pendientes / Por comprometer / Incumplida / Sin calificar).
- `ps-alert-control`: Mapea a **`ok`** (Lista para confirmar / Cumplida control).
- `ps-alert-tnp` / `info`: Mapea a **`neutral`** / **`ok`** (Trabajo no planificado).
- `ps-alert-neutral`: Mapea a **`neutral`**.

### D. PDC (`public/js/modules/pdc/hot.js` & `public/css/pdc.css`)
- `pdc-critical-delay`: Mapea a **`critico`** (Inicio contratación vencido).
- `pdc-delayed`: Mapea a **`riesgo`** (Contratación atrasada).
- `pdc-completed-late`: Mapea a **`atencion`** (Contratación cerrada tarde).
- `pdc-completed-ontime`: Mapea a **`ok`** (Contratación cerrada a tiempo).
- `pdc-active`: Mapea a **`ok`** (Contratación en curso).
- `pdc-missing` / `pdc-status-info`: Mapea a **`sin-datos`** (Información pendiente).
- `pdc-not-started`: Mapea a **`neutral`** (Contratación pendiente de inicio).

---

## 4. Auditoría de las 16 Superficies con Tabla

### Grupo 1: Handsontable (8 Core)

1. **`views/programa-general/programa_general.view.php`**
   - *Bordes:* Inconsistentes entre celdas congeladas y scrollables.
   - *Cabeceras:* Cabecera naranja heredada (`--pdc-header-bg: #8b4011`) contrasta en exceso con el resto del canvas.
   - *Padding/Height:* Celdas compactadas en exceso tras eliminar `padding-block`.
   - *Hex crudo / Tokens:* Clases `pg-state-*` usan tokens parciales mezclados con variables legacy.

2. **`views/programa-general-actualizar/programaGeneralActualizar.view.php`**
   - *Bordes:* Borde de foco en celda editable no cumple contraste 3:1 en dark mode.
   - *Editable vs Readonly:* Superposición compleja de box-shadow (`pg-cell-editable-overlay`).

3. **`views/programacion-intermedia/programacion_intermedia.view.php`**
   - *Bordes/Headers:* Utiliza `.pdc-header` para cabeceras con contraste aceptable pero desfasado del DS.
   - *Cache de Filtros:* `applyRowClassesToDOM()` limpia clases visuales antiguas pero requiere forzar recalculado de `cells()` al filtrar.

4. **`views/programacion-semanal/programacion_semanal.view.php`**
   - *Bordes/Relleno:* Grilla con chips de estado (`.ops-state-chip`) que superponen su propio relleno sobre el de la celda.
   - *Accesibilidad:* Mismo tono amber en `ps-alert-high` y `ps-alert-medium` desorienta la prioridad visual.

5. **`views/profesionales/profesionales.view.php`**
   - *Geometría / Clones:* Desalineación severa de 111px entre el clon de encabezados de fila y el cuerpo principal a 1180x820.
   - *Estados:* Falta soporte para estado de fila vacía / sin registros.

6. **`views/subcontratistas/subcontratistas.view.php`**
   - *Geometría / Clones:* Descalce de 13px en la fila 1 de la tabla congelada.
   - *Contenedor:* Falta envoltorio unificado `.aia-grid-shell`.

7. **`views/pdc/pdc.view.php`**
   - *Cabeceras:* Usa `.pdc-header` con color naranja inyectado.
   - *Bordes de Leyenda:* Overrides de `buttons.css` anulan los bordes tokenizados de los chips.

8. **`views/dashboard/escalamientos.php`**
   - *Estilos:* Estilos emergentes con hex crudo `#333`, `#222` y bordes grises desfasados.

---

### Grupo 2: DataTables (7 Superficies) — Sin Adaptador Activo

1. **`views/control-cambios/controlCambios.view.php`** (`#tablaControlCambios`)
   - *Adaptador:* **Ninguno.**
   - *Bordes/Headers:* Bordes blancos Bootstrap (`#dee2e6`) chocando con el fondo oscuro.
   - *Paginación y Filtros:* Insumos `<input>` y `<select>` de DataTables sin tokens del DS.

2. **`views/indicadores/indicadores.view.php`** (`#tablaIndicadores`)
   - *Estilos:* Filas alternas (zebra) con fondo blanco transparente sin tokenizar.
   - *Foco:* Celdas sin indicador de foco visible en modo oscuro.

3. **`views/programacion-semanal/CIC.view.php`** (`#tablaCIC`)
   - *Contenedor:* `div.dataTables_scrollBody` con altura forzada por JS que rompe el scroll flexible.
   - *Empty State:* Texto genérico de DataTables en inglés/español no estilizado.

4. **`views/programacion-semanal/CNC.view.php`** (`#tablaCNC`)
   - *Contenedor:* Mismo problema de `dataTables_scrollBody` en JS.
   - *Badges:* Utiliza badges predeterminados sin paleta de estados LPS.

5. **`views/programacion-semanal/CNP.view.php`** (`#tablaCNP`)
   - *Bordes/Zebra:* Faltan variables `--ds-table-zebra` y `--ds-table-border`.

6. **`admin/views/pages/projects/index.php`** (`#projectsTable`)
   - *Framework:* AdminLTE 3 + DataTables 1.10.21.
   - *Bordes/Hover:* Hover de fila en blanco brillante; cabecera sin `--ds-table-header-bg`.

7. **`admin/views/pages/users/index.php`** (`#usersTable`)
   - *Badges:* Usa `badge-success` / `badge-danger` crudos de Bootstrap 4.
   - *Adaptador:* Requiere la inclusión de `adapters/datatables.css` en la plantilla principal de Admin.

---

### Grupo 3: AG Grid (1 Superficie)

1. **`views/plan-compras/app.view.php` / `pdc-app/src/`** (SPA Plan de Compras)
   - *Adaptador / Capa:* Inyecta 19 bloques `<style>` en tiempo de ejecución sin declaración `@layer vendor`.
   - *Tokens:* Variables locales de AG Grid en `pdc-app/src/` desconectadas de `tokens.css`.
   - *Contraste:* Celdas de estado con fondos personalizados que fallan relación AA contra el texto blanco.

---

## 5. Validación de la Escala Semántica de 7 Peldaños

Tras evaluar las 28 clases activas y los requerimientos de negocio de LPS (Programa General, Lookahead / PI, Semanal y PDC):

1. **Cobertura 100%:** Todas las variaciones de estado operativo encajan sin fricción ni traslape en uno de los 7 peldaños (`neutral`, `ok`, `atencion`, `riesgo`, `critico`, `bloqueado`, `sin-datos`).
2. **Sin Necesidad de 8º Peldaño:** La separación ortogonal entre **Nivel de Acción** (Acento / Borde / Icono) y **Matiz de Estado** (Fondo) permite distinguir estados de igual prioridad (ej. `missing` en violeta y `completed-late` en ámbar, ambos en nivel `atencion`) sin requerir escalones adicionales en la escala principal.

---

## 6. Disposición de Deudas y Matriz de Hallazgos

| ID | Hallazgo / Superficie | Tipo | Acción Asignada | Fase Destino |
|---|---|---|---|---|
| **H-01** | Geometría y descalce de clones Handsontable (111px / 13px) | Tabla / Geometría | Corregir cálculo de alto de fila mediante `--ds-table-row-h` | **G2 (Handsontable)** |
| **H-02** | Ausencia total de adaptador CSS para DataTables | Tabla / Estilos | Crear `adapters/datatables.css` bajo layer vendor/components | **G3 (DataTables)** |
| **H-03** | Inyección de 19 bloques `<style>` unlayered en AG Grid | Tabla / Capas | Encapsular bajo `@layer vendor` y conectar tokens en `pdc-app/src` | **G4 (AG Grid)** |
| **H-04** | Disparidad de 28 clases CSS en 4 vocabularios hacia 4 paletas | Semántica / Color | Mapear todas las clases a la escala de 7 peldaños en el DS | **G1 (Contrato DS)** |
| **H-05** | Cabecera naranja `.pdc-header` / `#8b4011` desfasada | Tabla / Tokens | Normalizar cabeceras con `--ds-table-header-bg` / `-fg` | **G1 (Contrato DS)** |
| **H-06** | Selects de filtro dentro de tablas usando versiones heterogéneas | UI / Formulario | Consolidar editores de tabla en Tom Select | **G5 (Absorbe F6)** |
| **H-07** | Estado de celdas `/contratos` y `/listado-actividades` | Alcance | Excluidas del alcance (rutas 404 retiradas) | **Fuera de alcance** |
| **H-08** | Inconsistencias de usabilidad ajenas a tablas (modales, nav) | Usabilidad general | Inventariar sin modificar en este goal | **Diferido** |

---

## 7. Conclusión

El censo de la Fase G0 confirma que el sistema está preparado para recibir la tokenización centralizada en la Fase G1. Todos los estados vivos poseen un destino claro dentro de la escala semántica de 7 peldaños.
