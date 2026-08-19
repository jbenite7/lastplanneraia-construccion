---
capa: fuente
tipo: spec
estado: vigente
fecha: 2026-07-23
areas: [proceso]
fuente: docs/superpowers/specs/2026-07-23-a25-importador-maestro-sinco-design.md
resumen: Diseño: Fase A2.5 — Importador del maestro SINCO
---

# Diseño: Fase A2.5 — Importador del maestro SINCO

**Fecha:** 2026-07-23
**Estado:** aprobado en brainstorming (pendiente revisión final del spec)
**Roadmap:** fase A2.5 de `docs/superpowers/plans/2026-07-22-roadmap-pdc-v2.md` (submódulo Ensamble; prerequisito de A3)

## Propósito

El maestro de insumos de A2 nace vacío y se puebla desde los presupuestos importados (cola de pendientes + creación masiva). AIA ya tiene un **maestro autoritativo** exportado de su ERP **SINCO** (`docs/Maestro_Insumos_SINCO.xlsx`, hoja `Maestro Insumos`, 3.088 insumos activos de Constructora AIA). Esta fase importa ese archivo para **sembrar `general_maestro_insumos`** y volverlo el catálogo autoritativo de la empresa. Tras A2.5, la cola de pendientes de A2 pasa a ser la excepción (insumo del presupuesto que aún no existe en SINCO), no la norma. Además, cada insumo trae **Agrupación** (73 familias) y **Tipo** (categoría de recurso), que la Fase A3 usa como filtro y señal del motor de sugerencias.

## Estructura del archivo fuente (verificada)

Hoja `Maestro Insumos`, encabezados en la fila 1. Columnas relevantes:
`Empresa, Codigo Insumo, Insumo Descripcion, Agrupacion, Agrupacion Descripcion, Tipo, Tipo Descripcion, Unidad, Descripcion Unidad, Estado, Valor Unitario, Porcentaje IVA, Valor Neto, SkIdInsumo, Codigo Insumo Id`.

- **`Codigo Insumo`** — código estable del insumo en SINCO (clave del upsert).
- **`Agrupacion Descripcion`** — 73 familias (ej. `MAT-ACABADOS`, `MAT-ACEROS Y ELEMENTOS METALICOS`, `SUBCONTRATACION PERSONAL`).
- **`Tipo Descripcion`** — categoría de recurso (MATERIAL, SUBCONTRATO, MANO DE OBRA, EQUIPO, NÓMINA, CONSUMIBLES, TRANSPORTE, HONORARIOS…).
- **`Unidad`** / `Descripcion Unidad`, `Estado` (ACTIVO/INACTIVO), `Valor Unitario`, `Porcentaje IVA`.
- El archivo actual contiene solo la empresa `CONSTRUCTORA AIA S.A.S`; se importan sus insumos **activos** (~3.084).

## Decisiones

1. **`general_maestro_insumos` pasa a ser autoritativo** y se extiende con datos de SINCO. La UNIQUE existente `(descripcion_norm, unidad)` de A2 se conserva (matching de presupuestos por norma+unidad sigue funcionando); se agrega `codigo_sinco` con su propia UNIQUE como clave del upsert de este import.
2. **Upsert idempotente por `codigo_sinco`**: re-importar el archivo actualiza (no duplica). Insumos que ya existían por norma+unidad (creados por A2 desde un presupuesto) se enriquecen: si su `codigo_sinco` está NULL y llega una fila SINCO con misma norma+unidad, se completa; conflicto de norma+unidad con distinto código se reporta como advertencia (no se pisa silenciosamente).
3. **Flujo en 2 pasos** (patrón A1): preview (parsea + valida + resumen, sin escribir) → confirmar (upsert transaccional). RBAC `lps.pdc.maestro` (misma clave que administra el maestro en A2). CSRF `plan_compras_v2`.
4. **Solo insumos activos** por defecto; los INACTIVO se omiten (contados en el resumen).

## Esquema de base de datos (migración `.sql` en `lps-aia/database/migrations/`)

`ALTER TABLE general_maestro_insumos` — columnas nuevas (aditivo, nullable para no romper filas de A2):
- `codigo_sinco` varchar(50) DEFAULT NULL, `UNIQUE KEY uq_gmi_codigo_sinco (codigo_sinco)`
- `agrupacion` varchar(150) DEFAULT NULL
- `tipo_recurso` varchar(60) DEFAULT NULL
- `valor_unitario` decimal(18,4) DEFAULT NULL
- `iva` decimal(5,2) DEFAULT NULL
- `KEY idx_gmi_agrupacion (agrupacion)` (filtro de A3)

(`descripcion`, `descripcion_norm`, `unidad`, `tipo_insumo`, `activo`, `creado_por`, `created_at` ya existen de A2. `tipo_recurso` es la categoría SINCO — distinta de `tipo_insumo` que A2 tomaba del presupuesto; ambos se conservan.)

## Backend (lps-aia)

- **Parser** `MaestroSincoParser::parse(string $filePath): array` — read-only, hoja `Maestro Insumos` obligatoria; encabezados por nombre (misma normalización del `Normalizador`/parser de presupuesto); por fila activa produce `{codigoSinco, descripcion, descripcionNorm, unidad, agrupacion, tipoRecurso, valorUnitario, iva}`; validación todo-o-nada con reporte por fila (código faltante, unidad faltante, valor no numérico); tope 200 errores.
- **Servicio** `MaestroSincoImportService` (reusa `PresupuestoImportStore` para el temporal preview→confirmar): `preview(ruta, projectId, usuario)` → `{importToken, resumen:{total, activos, omitidos, agrupaciones, tiposRecurso}, advertencias}`; `confirmar(token)` → upsert transaccional por `codigo_sinco` (`INSERT … ON DUPLICATE KEY UPDATE` de descripcion/agrupacion/tipo_recurso/valor/iva/updated), completando `codigo_sinco` en filas huérfanas por norma+unidad cuando corresponda; retorna `{creados, actualizados, enriquecidos}`.
- **Controller** `PlanComprasMaestroImportController` (trait `PlanComprasJsonRespuestas`): `POST /plan-compras/api/maestro/importar/preview` (multipart, ≤10MB .xlsx, RBAC `lps.pdc.maestro`+CSRF), `POST /plan-compras/api/maestro/importar/confirmar` (`{importToken}`). Códigos: `INVALID_FILE`, `VALIDATION_FAILED`, `FILE_TOO_LARGE`, `TOKEN_EXPIRED`, `FORBIDDEN`, `NO_PROJECT`, `CSRF_INVALID`. Nota: el maestro es global, pero el guard exige proyecto activo por consistencia de sesión (la escritura afecta al catálogo global de la empresa).

## UI (SPA)

En la vista **Maestro** (A2), sección nueva "Importar maestro (SINCO)" arriba de la cola de pendientes: selector de archivo → resumen del preview (total/activos/omitidos, nº de agrupaciones y tipos) + reporte de errores en grilla si los hay → botón **Confirmar e importar**. Tras confirmar, el catálogo (grilla existente de A2) se recarga poblado. Reusa `apiUpload` y el reducer de import de A1 (o uno análogo mínimo). Solo visible para RBAC `lps.pdc.maestro`.

## Manejo de errores

Mismos códigos y semántica que el importador de presupuesto (A1). Fallos de `json_encode` con `JSON_INVALID_UTF8_SUBSTITUTE`. Mensajes de PhpSpreadsheet genéricos (no filtrar rutas). `finfo` mime + extensión `.xlsx`.

## Testing (dos patas, entorno real)

**BD/PHP (MySQL 8 de Docker, nunca mocks):**
- Migración aplicada + `SHOW CREATE TABLE` (nuevas columnas, UNIQUE de `codigo_sinco`).
- Tests autoejecutables con fixture `.xlsx` generado por script (subconjunto de ~6 insumos representando MATERIAL/SUBCONTRATO/MANO DE OBRA/EQUIPO con Agrupación): upsert idempotente (2º import no duplica), enriquecimiento de fila huérfana por norma+unidad, omisión de INACTIVO, aislamiento (el catálogo es global — el test limpia por marca `creado_por`/`codigo_sinco` de prueba, como A2).
- Gates `test_global_table_safety`/`reconciliation` en verde.

**Aplicación:** Vitest del estado del import del maestro; e2e Playwright (subir fixture → preview → confirmar → catálogo poblado en la vista Maestro). Idempotente vs catálogo global (cleanup por marca de test).

## Fuera de alcance

Multi-empresa (el archivo actual es solo AIA; si aparece un export con varias empresas, extender el filtro). Edición/retiro de insumos del catálogo (paquete de follow-ups de A2). Re-keying del matching de presupuestos a `codigo_sinco` (el presupuesto no trae el código SINCO; sigue casando por norma+unidad).

## Riesgos

| Riesgo | Mitigación |
|---|---|
| Colisión norma+unidad con distinto `codigo_sinco` (dos insumos SINCO que normalizan igual) | Reportar como advertencia en el preview; no pisar; decidir manualmente |
| Memoria de PhpSpreadsheet con 3.088 filas | Read-only + `toArray` medido OK a escala DAPORTO (parse 0.13s); límite 10MB |
| Empresa distinta a AIA en un export futuro | Fuera de alcance; el importador filtra/mapea por la hoja tal cual hoy |
