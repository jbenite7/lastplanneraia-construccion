---
capa: fuente
tipo: spec
estado: cerrado
fecha: 2026-07-23
areas: [proceso]
fuente: docs/superpowers/specs/2026-07-23-a3-paquetes-contratacion-design.md
resumen: Indicador prominente de cobertura = (asignados + omitidos) / total de la versión activa. 100% = "no queda nada suelto". Solo lectura/semáforo en A3 (el bloqueo…
---

# Diseño: Fase A3 — Paquetes de contratación + asistente de empaquetamiento

**Fecha:** 2026-07-23 (revisado el 2026-07-23 tras destilar la app externa de Tomás)
**Estado:** propuesto (revisado — pendiente revisión del usuario)
**Roadmap:** fase A3 de `docs/superpowers/plans/2026-07-22-roadmap-pdc-v2.md` (submódulo Ensamble). Prerequisito: A2.5 (maestro sembrado con Agrupación/Tipo de recurso).

## Changelog de esta revisión

Este spec **absorbe y mejora** el empaquetamiento que hoy vive en la app externa de Tomás (`analisis-presupuestos.web.app` v32, destilada de su bundle). Decisión de Juanfe 2026-07-23: no solo importar el output de esa app, sino **incorporar sus funcionalidades en la plataforma y mejorarlas** con BD multiproyecto, memoria cross-proyecto real y RBAC. Cambios respecto a la versión inicial del spec:
1. **Catálogo semilla de 188 paquetes reales de AIA** (extraídos del bundle) → migración de seed; el cold-start deja de existir.
2. **Tipos de negociación alineados** a los 3 reales de la app (Suministro e Instalación / Suministro / Mano de Obra), conservando el enum de 4 del dominio.
3. **Asistente paso a paso** (wizard estilo app de Tomás) además del modo masivo por grilla.
4. **Estado "omitido"** para insumos que no van al plan de compras (la app permite omitir; sin esto la meta 100% no cierra).
5. **Cuarta señal del motor: filtro por `tipo_recurso`** (de SINCO/A2.5) — replica y mejora el filtro por tipo de insumo del wizard.

## Propósito

Agrupar los insumos únicos del presupuesto activo en **paquetes de contratación** — el paso intermedio entre el maestro de insumos y el plan de compras final. Principio del dominio (confirmado en reunión): *no se compran actividades, se compran/negocian insumos*; **"los paquetes son los insumos del plan de compras"**. Ejemplo: "Piso cerámico", "Piso gres" y "Piso porcelanato" → paquete **Pisos**. Meta central: que el **100% de los insumos** del presupuesto quede **asignado a un paquete u omitido explícitamente** (que "no quede nada suelto"). Un **motor/asistente cross-proyecto** acelera la asignación aprendiendo de las decisiones humanas de otros proyectos.

**Mejora sobre la app de Tomás:** su catálogo y asignaciones viven en `localStorage` de **un solo navegador** — no se comparte entre usuarios ni aprende entre proyectos. Aquí el catálogo es corporativo (con RBAC), la asignación se versiona por proyecto y **el motor se alimenta de las asignaciones reales de todos los proyectos** (memoria que la app de Tomás no puede tener). Y el paquete se conecta luego al cronograma para fechas (A4), que es justo lo que la app de Tomás no hace y por lo que exporta a Las Planner.

## Decisiones de producto

1. **Unidad asignable:** los insumos únicos consolidados de la versión activa (los mismos `pdc_insumo_vinculos` de A2). La meta 100% se mide sobre ese universo.
2. **Cardinalidad:** un insumo pertenece a lo sumo a **un** paquete (o está **omitido**); el paquete tiene **un** tipo de negociación.
3. **Catálogo de paquetes global y reutilizable** (como el maestro): se define una vez y se reúsa entre proyectos; la asignación insumo→paquete es por proyecto.
4. **Asignación atada a la versión activa**, con herencia automática en re-import (mismo insumo reaparece → hereda su paquete/omisión; insumo nuevo → sin asignar).
5. **Tipo de negociación** como atributo del paquete (editable). Enum interno de 4 valores del dominio — `a_todo_costo`, `mano_obra`, `suministro`, `consumibles` — con **etiquetas** que mapean a la nomenclatura de la app de Tomás. El **seed** usa solo los 3 que existen hoy (ver tabla). Sin lógica de bloqueo todavía (→ A4).
6. **Agrupación SINCO** (de A2.5) = categoría por encima del paquete: filtro/orden en la UI + señal del motor. **Tipo de recurso** SINCO (material/mano de obra/subcontrato/nómina/…) = filtro de candidatos del asistente (replica el filtro de la app de Tomás).
7. **Estado "omitido":** un insumo puede marcarse como *no va al plan de compras* (ej. "imprevistos de obra"). Cuenta como resuelto para la cobertura.
8. **Motor con confirmación humana** (mismo principio que A2): nada entra a un paquete sin que un humano lo acepte.

### Mapeo de tipos de negociación

| Enum interno (BD/API) | Etiqueta UI | Nomenclatura app de Tomás | En seed |
|---|---|---|---|
| `a_todo_costo` | A todo costo (Sum. + Inst.) | Suministro e Instalación (107) | ✅ |
| `suministro` | Suministro | Suministro (53) | ✅ |
| `mano_obra` | Mano de obra | Mano de Obra (28) | ✅ |
| `consumibles` | Consumibles | — (no existe en la app) | ❌ (disponible para uso manual) |

## Esquema de base de datos (migraciones `.sql` en `lps-aia/database/migrations/`)

**`general_paquetes_contratacion`** (catálogo global, sin `project_id`, patrón `general_*`):
- `id` bigint PK, `nombre` varchar(200), `nombre_norm` varchar(200) UNIQUE, `tipo_negociacion` enum('a_todo_costo','mano_obra','suministro','consumibles') NOT NULL DEFAULT 'a_todo_costo', `activo` tinyint NOT NULL DEFAULT 1, `creado_por` varchar(100), `created_at` datetime, `updated_at` datetime NULL
- `utf8mb4_unicode_ci`

**`pdc_insumo_paquete`** (asignación, por `project_id`):
- `id` bigint PK, `project_id` int NOT NULL, `descripcion_norm` varchar(500), `unidad` varchar(20), `paquete_id` bigint **NULL** (NULL cuando el insumo está omitido), `omitido` tinyint NOT NULL DEFAULT 0, `asignado_por` varchar(100), `updated_at` datetime
- `UNIQUE KEY uq_pip_insumo (project_id, descripcion_norm(150), unidad)` → **un insumo, un destino** (paquete u omisión)
- `KEY idx_pip_paquete (project_id, paquete_id)`; `KEY idx_pip_norm (descripcion_norm(150), unidad)` (motor cross-proyecto)
- `CONSTRAINT fk_pip_paquete FOREIGN KEY (paquete_id) REFERENCES general_paquetes_contratacion(id) ON DELETE RESTRICT`
- Invariante de aplicación: `omitido=1 ⟺ paquete_id IS NULL` (una fila es asignación-a-paquete **o** omisión, nunca ambas ni ninguna).

**Migración de seed** (`.sql`, idempotente `INSERT ... ON DUPLICATE KEY UPDATE` no-op / `INSERT IGNORE` por `nombre_norm`): siembra los **188 paquetes** extraídos del bundle de la app de Tomás con su `tipo_negociacion` mapeado y `creado_por='seed-tomas'`. Idempotente: re-aplicar no duplica ni pisa ediciones posteriores.

La asignación se clava por `(project_id, descripcion_norm, unidad)`: el re-import hereda paquete/omisión gratis; insumos nuevos quedan sin asignar; insumos que desaparecen dejan una fila huérfana inofensiva (la vista hace JOIN con los insumos de la versión activa). **El motor no tiene tabla propia**: su "memoria" es `pdc_insumo_paquete` (filas con `paquete_id`) agregada entre proyectos.

## Motor de sugerencias (cuatro señales, sin tabla nueva)

Para cada insumo **sin asignar** de la versión activa, `sugerencias(projectId)` produce `{paqueteId, capa, confianza, evidencia}`:

1. **Exacta (alta):** mismo `(descripcion_norm, unidad)` asignado en **otros** proyectos → `COUNT(DISTINCT project_id)` por paquete, ordena por consenso. Evidencia: "en N de M proyectos → Pisos".
2. **Similitud por tokens (media):** tokens ≥4 chars de `descripcion_norm`, `LIKE` con `addcslashes($t,'\\%_')`, ponderando tokens coincidentes × proyectos. Solo si la exacta no dio.
3. **Agrupación SINCO (baja, respaldo):** paquete más frecuente entre insumos ya asignados que comparten la `agrupacion` del maestro. Solo si 1 y 2 no dieron.
4. **Filtro por Tipo de recurso (transversal):** las capas 1-3 **restringen candidatos** al `tipo_recurso` compatible con el tipo de negociación en curso (materiales no ofrecen mano de obra, etc.), replicando el filtro del asistente de Tomás. Cuando el usuario ya fijó el tipo de negociación del paquete, el asistente solo ofrece insumos de `tipo_recurso` coherente.

**Confirmación humana siempre**: el motor solo pre-marca; el humano acepta en bloque o ajusta.

## Dos modos de empaquetamiento (ambos sobre los mismos endpoints)

- **Modo masivo (grilla):** AG Grid con filtros (sin asignar / asignados / omitidos / todos) + filtro por Agrupación; selección múltiple → "Asignar a paquete" / "Omitir" / "Aceptar sugeridos". Para usuarios que quieren barrer rápido.
- **Modo asistente (wizard paso a paso, estilo app de Tomás):** recorre los insumos **sin asignar en orden Pareto** (valor total desc). Por cada insumo: muestra descripción + tipo de recurso + costo; el usuario elige **tipo de negociación**; el motor **recomienda un paquete** (+ candidatos filtrados por tipo de recurso); el usuario **asigna a un paquete existente, crea uno nuevo, u omite**; tras asignar, el asistente **ofrece otros insumos sin asignar candidatos al mismo paquete** (por similitud/agrupación/tipo de recurso) para engrosarlo de una vez. Es el flujo que Tomás demostró, ahora con memoria cross-proyecto.

## Backend (lps-aia)

`PaquetesService`:
- `catalogo(?string $busqueda): array` — paquetes globales activos (id, nombre, tipo, nº de asignaciones global).
- `crearPaquete(string $nombre, string $tipo, string $usuario): array` — dup por `nombre_norm` → devuelve el existente.
- `insumosDeVersion(int $projectId, string $filtro): array` — insumos únicos de la versión activa (norma, unidad, descripción, agrupación, tipo_recurso, costo total consolidado) LEFT JOIN su asignación/omisión; `filtro` ∈ {sin_asignar, asignados, omitidos, todos}.
- `sugerencias(int $projectId): array` — las cuatro señales, solo para los sin_asignar.
- `asignar(int $projectId, array $insumos, int $paqueteId, string $usuario): array` — upsert masivo por lotes (mueve si estaba en otro paquete o quita omisión).
- `omitir(int $projectId, array $insumos, string $usuario): array` / `desasignar(int $projectId, array $insumos): array` (quita paquete u omisión → vuelve a sin_asignar).
- `resumen(int $projectId): array` — `{total, asignados, omitidos, cobertura, porPaquete:[{paqueteId, nombre, tipo, insumos, subtotal}]}`; cobertura = (asignados + omitidos) / total.

`PlanComprasPaquetesController` (trait `PlanComprasJsonRespuestas`; lectura `lps.paquetes_contratacion.ver`, escritura `lps.paquetes_contratacion.editar` + CSRF `plan_compras_v2`): GET paquetes / insumos / sugerencias / resumen; POST paquetes / asignar / omitir / desasignar. Validación de arrays con elementos escalares (lección review A2). Códigos: `PAQUETE_INVALIDO` 422, `NO_VERSION` 404, `FORBIDDEN` 403, `NO_PROJECT` 409, `CSRF_INVALID` 403.

## UI (SPA)

Pestaña nueva **Paquetes** en la nav de Ensamble. Reducer `paquetesState`. Tres zonas:
- **Insumos** (grilla, modo masivo): filtros por estado y Agrupación; columnas descripción, agrupación, tipo de recurso, unidad, costo total, paquete/omisión, y (tras "Sugerir") columna de sugerencia con capa/confianza. Selección múltiple → asignar / omitir / aceptar sugeridos.
- **Asistente** (modo wizard): tarjeta por insumo en orden Pareto con tipo de negociación → recomendación → asignar/crear/omitir → candidatos del mismo paquete.
- **Paquetes:** lista con nombre, tipo, nº de insumos y subtotal; **barra de cobertura 100%** ("N asignados + K omitidos de M"). Sin AG Grid boolean cells (enteros/strings + valueFormatter; `ValidationModule` dev-only).

## Meta del 100%

Indicador prominente de cobertura = `(asignados + omitidos) / total de la versión activa`. 100% = "no queda nada suelto". Solo lectura/semáforo en A3 (el bloqueo real y las fechas son A4).

## Testing (dos patas, entorno real)

**BD/PHP (MySQL 8 de Docker):**
- Migraciones + `SHOW CREATE TABLE` (UNIQUEs, FK RESTRICT, `paquete_id` nullable + `omitido`).
- **Seed:** el test verifica que la migración de seed dejó los 188 paquetes con su tipo correcto y que re-aplicarla es idempotente.
- Tests autoejecutables (proyectos 999901/999902 + cleanup por marca): crear paquete (dup→existente), asignación masiva, un-insumo-un-destino (reasignar mueve; omitir quita paquete; asignar quita omisión), cobertura con omitidos, herencia en re-import, aislamiento por proyecto; **motor**: exacto multi-proyecto, similitud por tokens, señal Agrupación, filtro por tipo_recurso, y sin historial → sin sugerencia exacta; RBAC contract. Aislamiento test/e2e por marca.
- Gates `test_global_table_safety`/`reconciliation`.

**Aplicación:** Vitest del `paquetesState` (selección, aceptar sugeridos, wizard). e2e Playwright del ciclo completo: importar → (maestro sembrado por A2.5) → Paquetes → crear/asignar/omitir → cobertura → re-import hereda; y un paso del asistente. Idempotente vs catálogo global.

## Fuera de alcance (→ A4)

Bloqueo real de "suministro+instalación" (contratar a todo costo bloquea los otros para ese alcance), fechas por matching con `programa_consolidado`, duraciones por paso (`general_dias_procesos_contratacion`), responsable por paquete, plan de compras final. **Export JSON/XLSX de paquetes** (interop hacia afuera / la app de Tomás): follow-up opcional — con el empaquetamiento ya en la plataforma deja de ser el camino principal; se mantiene el contrato `{tipo, version, exportadoEn, presupuestoOrigen, paquetes:[{id, nombre, insumos}]}` documentado por si se necesita interoperar.

## Riesgos

| Riesgo | Mitigación |
|---|---|
| Seed de 188 con nombres que colisionan al normalizar | UNIQUE por `nombre_norm`; el seed usa `INSERT IGNORE`, colisión → conserva el primero (revisar el conteo tras sembrar) |
| Señal Agrupación / tipo_recurso sobre-sugiere | Baja confianza + solo respaldo; el tipo_recurso filtra, no manda |
| Escape de comodines LIKE en similitud | `addcslashes($t, '\\%_')` (corrige el follow-up de A2) |
| `omitido` mal usado como "papelera" | La cobertura distingue asignados de omitidos; el resumen muestra ambos por separado |
| Contaminación test PHP ↔ e2e por catálogo global | Cleanup por marca en ambos, secuencia alternada (lección A2); el seed usa marca `seed-tomas` disjunta de `test-a3` |

---

## Estado verificado — cerrado

Verificado contra el código el 2026-08-25. **`estado: cerrado` es una afirmación deliberada**, no el valor por defecto del backfill.

**Evidencia:** PaquetesService.php, PlanComprasPaquetesController.php, pdc-app/src/pages/PaquetesContratacion.tsx

Criterio y método: [[docs/superpowers/plans/2026-08-25-estado-real-de-planes-y-specs]].
