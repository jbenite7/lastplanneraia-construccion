# Diseño: Fase A1.6 — Comparativo de versiones del presupuesto

**Fecha:** 2026-07-23
**Estado:** propuesto (pendiente revisión del usuario)
**Roadmap:** fase A1.6 de `docs/superpowers/plans/2026-07-22-roadmap-pdc-v2.md` (submódulo Ensamble). Se inserta entre A1.5 (visor) y A2. Independiente: se puede ejecutar en paralelo a A3.

## Propósito

Incorporar (y mejorar) el **comparativo de versiones** que hoy vive en la app externa de Tomás (`analisis-presupuestos.web.app`): dado un presupuesto con varias versiones importadas, mostrar **qué cambió entre dos versiones** — por actividad y por insumo — clasificando los cambios en **sobrecostos** vs **ahorros**. Es una de las funcionalidades que Tomás pidió llevar a la plataforma ("revisiones gerenciales", "informes económicos: qué reportamos este mes vs el próximo").

**La mejora de la plataforma sobre la app de Tomás:** la app de Tomás exige cargar dos archivos Excel a mano y compara en memoria de un navegador. Aquí las versiones **ya están importadas y versionadas en BD** (Fase A1, `pdc_presupuesto_versiones` con única activa): el comparativo es solo elegir dos versiones de una lista — sin re-cargar archivos, con historial completo, multiproyecto y con RBAC.

## Alcance

Solo **lectura**. No hay migraciones (lee las tablas de A1: `pdc_presupuesto_versiones`, `pdc_presupuesto_items`, `pdc_presupuesto_apu_insumos`). No toca A2/A3.

## Decisiones de diseño (tomadas; validar en revisión)

1. **Se comparan dos versiones cualesquiera** del mismo proyecto (`versionA` = base/anterior, `versionB` = comparada/nueva). Δ = B − A. Por defecto la UI preselecciona `versionB` = activa y `versionA` = la versión inmediatamente anterior por `created_at`; el usuario puede elegir cualquier par.
2. **Dos ejes de comparación** (como la app de Tomás):
   - **Actividades** (jerárquico): se comparan las filas de `pdc_presupuesto_items` por **`codigo`** (identidad estable de la línea de presupuesto). Para las hojas (`tipo_fila='actividad'`) se comparan cantidad y valor; los nodos (capítulo/subcapítulo/grupo) agregan el valor de sus descendientes.
   - **Insumos** (plano, estilo Pareto): se comparan los insumos consolidados por **`(descripcion_norm, unidad)`** — misma clave e idéntica normalización que A2 (`MaestroInsumosService::normalizar`), consolidando `SUM(valor_total)` y `SUM(cantidad_total)` desde `pdc_presupuesto_apu_insumos` por versión.
3. **Clasificación de cada fila:** `nuevo` (existe en B, no en A), `eliminado` (en A, no en B), `modificado` (en ambas, cambió cantidad y/o valor), `igual`. Para las modificadas: Δcantidad, Δvalor unitario (solo actividades/insumos con unitario) y Δvalor total, con Δ% sobre el valor A (si A=0 → "nuevo"). Δvalor total > 0 = **sobrecosto**; < 0 = **ahorro**.
4. **Resumen económico** (cabecera): costo total A, costo total B, Δ total, suma de sobrecostos, suma de ahorros, nº de líneas nuevas/eliminadas/modificadas. Es el "sobrecostos a un lado, ahorros al otro" de la app de Tomás.
5. **UI = pestaña nueva "Comparar"** en la navegación de Ensamble, no dentro del visor (para no recargar el visor de A1.5). Dos selectores de versión + toggle Actividades/Insumos + tabla con color (sobrecosto en rojo, ahorro en verde) + cabecera de resumen.

## Backend (lps-aia)

- **Endpoint** `GET /plan-compras/api/presupuesto/comparar?versionA=N&versionB=M` (RBAC `lps.pdc.ver`; proyecto activo). Validaciones: ambas versiones deben existir y pertenecer al proyecto (`NO_VERSION` 404 si falta alguna); `versionA === versionB` → `PARAMS_INVALIDOS` 422. Sin `versionA/B` explícitos, usa activa vs anterior.
- **Servicio** `PresupuestoImportService::comparar(int $projectId, int $versionA, int $versionB): ?array` (método nuevo; reusa las tablas de A1). Retorna:
  ```
  {
    versionA: {id, label}, versionB: {id, label},
    resumen: {costoA, costoB, delta, sobrecostos, ahorros, nuevos, eliminados, modificados},
    actividades: [{codigo, codigoPadre, nivel, tipoFila, descripcion, cantidadA, cantidadB, valorA, valorB, deltaValor, deltaPct, estado}],
    insumos: [{descripcionNorm, unidad, descripcion, tipoInsumo, cantidadA, cantidadB, valorUnitA, valorUnitB, valorA, valorB, deltaValor, deltaPct, estado}]
  }
  ```
  Las actividades se devuelven **planas con jerarquía** (código + código padre + nivel), como en A1.5; la SPA arma el árbol y colapsa. Los insumos se ordenan por `MAX(valorA, valorB) DESC` (Pareto del cambio).
- **Estrategia SQL:** un `FULL OUTER JOIN` emulado (MySQL no lo tiene) vía `LEFT JOIN` de A→B `UNION` el `LEFT JOIN` de B→A filtrando lo ya visto, o dos `SELECT ... GROUP BY` cargados en PHP y fusionados por clave en un mapa (más simple y legible; el volumen por versión es ~cientos-miles de filas, aceptable). Se elige la fusión en PHP (misma técnica que la consolidación de A2).

## UI (SPA)

Pestaña **"Comparar"** (`#/ensamble/comparar`) en la nav de Ensamble. Componentes:
- Dos `<select>` de versión (reusa `GET /plan-compras/api/presupuesto/versiones`), con la preselección activa-vs-anterior.
- Cabecera de resumen: costo A → costo B, Δ total (con signo y color), chips de sobrecostos/ahorros/nuevos/eliminados/modificados.
- Toggle **Actividades | Insumos**.
- Tabla AG Grid Community (módulos selectivos, `ValidationModule` dev-only; `cellClassRules` para colorear sobrecosto/ahorro — enteros/strings, sin boolean cells). Actividades con indentación por nivel (patrón A1.5, `white-space: pre`); Insumos plano ordenado por magnitud del cambio.
- Estado vacío: si el proyecto tiene <2 versiones, mensaje "Necesitas al menos dos versiones importadas para comparar".

## Testing (dos patas, entorno real)

**BD/PHP (MySQL 8 de Docker):** test autoejecutable con un fixture de 2 versiones del mismo proyecto (999901) que cubra: insumo nuevo, insumo eliminado, insumo con Δcantidad, insumo con Δvalor unitario, actividad modificada, y el resumen (sobrecostos/ahorros correctos); aislamiento por proyecto; `versionA===versionB` rechazado; versión inexistente → null. Gates `test_global_table_safety`/`reconciliation` sin cambios (no hay tablas nuevas, pero se corren igual).

**Aplicación:** Vitest de la lógica pura de armado del árbol/clasificación si vive en el front (p.ej. `src/lib/comparativo.ts`); e2e Playwright: importar 2 versiones (fixtures del importador de A1) → Comparar → ver Δ y colores → togglear a Insumos.

## Fuera de alcance

- Comparar versiones de **proyectos distintos** (solo intra-proyecto).
- Exportar el comparativo a Excel/PDF (la app de Tomás lo hace; se puede añadir como follow-up con PhpSpreadsheet, reusando el patrón del importador). Se deja fuera para mantener la fase pequeña.
- Comparar el desglose interno del APU de una actividad entre versiones (solo se compara el total de la actividad y el consolidado de insumos).

## Riesgos

| Riesgo | Mitigación |
|---|---|
| Códigos de actividad reusados/duplicados entre versiones | La comparación por `codigo` asume unicidad de código por versión (garantizada por el import de A1); si un código se repite, se agregan sus valores (documentado) |
| Volumen (miles de filas × 2 versiones) en memoria PHP | Fusión por mapa en PHP medida OK a escala DAPORTO; si un presupuesto real lo exige, paginar por eje o mover el diff a SQL |
| Insumos que normalizan igual con distinta descripción cruda | Se consolidan por `descripcion_norm+unidad` (más robusto que la clave por descripción cruda de la app de Tomás) — coherente con A2 |
