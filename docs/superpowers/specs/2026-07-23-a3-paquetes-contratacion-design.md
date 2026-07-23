# Diseño: Fase A3 — Paquetes de contratación + motor de sugerencias

**Fecha:** 2026-07-23
**Estado:** aprobado en brainstorming (pendiente revisión final del spec)
**Roadmap:** fase A3 de `docs/superpowers/plans/2026-07-22-roadmap-pdc-v2.md` (submódulo Ensamble). Prerequisito: A2.5 (maestro sembrado con Agrupación/Tipo).

## Propósito

Agrupar los insumos únicos del presupuesto activo en **paquetes de contratación** — el paso intermedio entre el maestro de insumos y el plan de compras final. Principio del dominio: *no se compran actividades, se compran/negocian insumos*. Ejemplo: "Piso cerámico", "Piso gres" y "Piso porcelanato" se agrupan en el paquete **Pisos**. Meta central: que el **100% de los insumos** del presupuesto quede asignado a algún paquete (que "no quede nada suelto"). Un **motor de sugerencias cross-proyecto** acelera la asignación aprendiendo de las asignaciones humanas de otros proyectos.

## Decisiones de producto (usuario, 2026-07-23)

1. **Unidad asignable:** los insumos únicos consolidados de la versión activa (los mismos `pdc_insumo_vinculos` de A2). La meta 100% se mide sobre ese universo.
2. **Cardinalidad:** un insumo pertenece a lo sumo a **un** paquete; el paquete tiene **un** tipo de negociación.
3. **Catálogo de paquetes global y reutilizable** (como el maestro de insumos): "Pisos", "Acero estructura" se definen una vez y se reúsan entre proyectos; la asignación insumo→paquete es por proyecto.
4. **Asignación atada a la versión activa**, con herencia automática en re-import (mismo insumo reaparece → hereda su paquete; insumo nuevo → sin asignar).
5. **Tipo de negociación** (`a_todo_costo`, `mano_obra`, `suministro`, `consumibles`) como atributo del paquete desde A3 (editable, por defecto `a_todo_costo`). Sin lógica de bloqueo todavía (→ A4).
6. **Agrupación SINCO** (de A2.5) = categoría por encima del paquete: filtro/orden en la UI + señal del motor. NO son los paquetes (son más gruesas: "Pisos" ⊂ "MAT-ACABADOS").
7. **Motor de sugerencias con las tres señales, siempre con confirmación humana** (mismo principio que A2): nada entra a un paquete sin que un humano lo acepte.

## Esquema de base de datos (migración `.sql` en `lps-aia/database/migrations/`)

**`general_paquetes_contratacion`** (catálogo global, sin `project_id`, patrón `general_*`):
- `id` bigint PK, `nombre` varchar(200), `nombre_norm` varchar(200), `tipo_negociacion` enum('a_todo_costo','mano_obra','suministro','consumibles') NOT NULL DEFAULT 'a_todo_costo', `activo` tinyint NOT NULL DEFAULT 1, `creado_por` varchar(100), `created_at` datetime, `updated_at` datetime NULL
- `UNIQUE KEY uq_gpc_nombre_norm (nombre_norm)`; `utf8mb4_unicode_ci`

**`pdc_insumo_paquete`** (asignación, por `project_id`):
- `id` bigint PK, `project_id` int NOT NULL, `descripcion_norm` varchar(300), `unidad` varchar(20), `paquete_id` bigint NOT NULL, `asignado_por` varchar(100), `updated_at` datetime
- `UNIQUE KEY uq_pip_insumo (project_id, descripcion_norm(150), unidad)` → **un insumo, un paquete**
- `KEY idx_pip_paquete (project_id, paquete_id)`; `KEY idx_pip_norm (descripcion_norm(150), unidad)` (motor cross-proyecto)
- `CONSTRAINT fk_pip_paquete FOREIGN KEY (paquete_id) REFERENCES general_paquetes_contratacion(id) ON DELETE RESTRICT`

La asignación se clava por `(project_id, descripcion_norm, unidad)` a nivel proyecto: el re-import hereda el paquete gratis (el insumo reaparecido ya tiene fila); insumos nuevos quedan sin asignar; insumos que desaparecen del presupuesto dejan una fila huérfana inofensiva (nunca aparece en la vista, que hace JOIN con los insumos de la versión activa). **El motor no tiene tabla propia**: su "memoria" es `pdc_insumo_paquete` agregada entre proyectos.

## Motor de sugerencias (tres señales, sin tabla nueva)

Para cada insumo **sin asignar** de la versión activa, `sugerencias(projectId)` produce una recomendación con `{paqueteId, capa, confianza, evidencia}`:

1. **Exacta (confianza alta):** `SELECT paquete_id, COUNT(DISTINCT project_id) AS conteo FROM pdc_insumo_paquete WHERE descripcion_norm=? AND unidad=? GROUP BY paquete_id ORDER BY conteo DESC LIMIT 1`. Confianza = `conteo / proyectos_que_usan_el_insumo`. Evidencia: "en N de M proyectos → Pisos".
2. **Similitud por tokens (confianza media):** tokens de `descripcion_norm` (≥4 letras, comodines `%_\` escapados con `addcslashes`); por cada token `WHERE descripcion_norm LIKE ?`; se agrega por `paquete_id` ponderando por nº de tokens coincidentes × `COUNT(DISTINCT project_id)`. Solo se usa si la capa exacta no dio resultado. Etiquetada como *similar*.
3. **Agrupación (confianza baja, respaldo):** si el insumo tiene `agrupacion` (del maestro SINCO) y ni 1 ni 2 aportan, sugiere el paquete más frecuente entre los insumos ya asignados que comparten esa `agrupacion` (JOIN `pdc_insumo_paquete` × `general_maestro_insumos` por norma+unidad). Explícitamente de baja confianza (la Agrupación es gruesa; no manda sobre un match exacto). Además, la Agrupación organiza la UI (filtro/orden), su uso principal.

**Aplicación:** botón **"Sugerir asignaciones"** → pre-marca cada insumo sin asignar con su paquete sugerido, la capa y la confianza. El usuario **acepta en bloque o ajusta** antes de confirmar. Al confirmar, las nuevas filas de `pdc_insumo_paquete` ya alimentan el motor para el siguiente proyecto (refuerzo).

## Backend (lps-aia)

`PaquetesService`:
- `catalogo(?string $busqueda): array` — paquetes globales activos (id, nombre, tipo, nº de insumos asignados global).
- `crearPaquete(string $nombre, string $tipo, string $usuario): array` — dup por `nombre_norm` → devuelve el existente (no falla).
- `insumosDeVersion(int $projectId, string $filtro): array` — insumos únicos de la versión activa (norma, unidad, descripción, agrupación, tipo_recurso, costo total consolidado) LEFT JOIN su asignación; `filtro` ∈ {sin_asignar, asignados, todos}.
- `sugerencias(int $projectId): array` — las tres capas descritas, solo para los sin_asignar.
- `asignar(int $projectId, array $insumos, int $paqueteId, string $usuario): array` — upsert masivo (mueve si ya estaba en otro paquete, no duplica); valida que `paqueteId` exista y esté activo.
- `desasignar(int $projectId, array $insumos): array`.
- `resumen(int $projectId): array` — `{total, asignados, cobertura, porPaquete:[{paqueteId, nombre, tipo, insumos, subtotal}]}`.

`PlanComprasPaquetesController` (trait `PlanComprasJsonRespuestas`; lectura `lps.paquetes_contratacion.ver`, escritura `lps.paquetes_contratacion.editar` + CSRF `plan_compras_v2`):
- `GET /plan-compras/api/paquetes` (catálogo), `GET …/paquetes/insumos?filtro=…`, `GET …/paquetes/sugerencias`, `GET …/paquetes/resumen`
- `POST …/paquetes` (crear), `POST …/paquetes/asignar`, `POST …/paquetes/desasignar`

Validación de `array` de ids escalares en los POST (lección del review final de A2). Códigos: `PAQUETE_INVALIDO` 422, `NO_VERSION` 404, `FORBIDDEN` 403, `NO_PROJECT` 409, `CSRF_INVALID` 403.

## UI (SPA)

Pestaña nueva **Paquetes** en la nav de Ensamble (Importar | Presupuesto | Maestro | **Paquetes**). Reducer `paquetesState` (patrón A2). Dos zonas:
- **Insumos** (AG Grid Community): filtro (sin asignar / asignados / todos) + filtro por **Agrupación**; columnas descripción, agrupación, unidad, costo total, paquete actual, y (tras "Sugerir asignaciones") columna de sugerencia con capa/confianza. Selección múltiple → "Asignar a paquete" (dropdown del catálogo o crear paquete inline con su tipo) y "Aceptar sugeridos".
- **Paquetes:** lista con nombre, tipo de negociación, nº de insumos y subtotal; **barra de cobertura 100%** ("N de M insumos asignados"). Sin AG Grid boolean cells (enteros/strings con valueFormatter; `ValidationModule` dev-only — patrón del repo).

## Meta del 100%

Indicador prominente de cobertura (`asignados / total de la versión activa`). 100% = "no queda nada suelto". Solo lectura/semáforo en A3 (no bloquea nada; el bloqueo real y las fechas son A4).

## Testing (dos patas, entorno real)

**BD/PHP (MySQL 8 de Docker):**
- Migración + `SHOW CREATE TABLE` (UNIQUEs, FK RESTRICT).
- Tests autoejecutables (proyectos 999901/999902 + cleanup del catálogo global de paquetes por marca): crear paquete (dup→existente), asignación masiva, un-insumo-un-paquete (reasignar mueve, no duplica), cobertura, herencia en re-import, aislamiento por proyecto; **motor**: exacto multi-proyecto (consenso ordena), similitud por tokens, señal Agrupación como respaldo, y sin historial → sin sugerencia exacta; RBAC contract. Aislamiento test/e2e por marca (como A2).
- Gates `test_global_table_safety`/`reconciliation`.

**Aplicación:** Vitest del `paquetesState` (selección múltiple, aceptar sugeridos, estados de acción). e2e Playwright del ciclo completo: importar → (maestro sembrado por A2.5) → Paquetes → crear paquete → sugerir/asignar → cobertura 100% → re-import hereda. Idempotente vs catálogo global.

## Fuera de alcance (→ Fase A4)

Bloqueo real de "suministro+instalación" (contratar a todo costo bloquea los otros para ese alcance), fechas por matching con `programa_consolidado`, duraciones por paso (`general_dias_procesos_contratacion`), responsable por paquete, plan de compras final.

## Riesgos

| Riesgo | Mitigación |
|---|---|
| Señal Agrupación sobre-sugiere (MAT-ACABADOS es gruesa) | Baja confianza + solo como respaldo; su uso principal es filtro/orden en UI |
| Escape de comodines LIKE en similitud | `addcslashes($t, '%_\\')` (corrige el follow-up de A2) |
| Rendimiento del motor con catálogo global grande | Índices por (descripcion_norm(150), unidad); las capas exactas usan la UNIQUE; similitud (token LIKE) solo on-demand al pulsar "Sugerir" |
| Contaminación test PHP ↔ e2e por catálogo global de paquetes | Cleanup por marca de test en ambos, verificado con secuencia alternada (lección de A2) |
