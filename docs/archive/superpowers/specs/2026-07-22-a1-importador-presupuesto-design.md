---
capa: fuente
tipo: spec
estado: cerrado
fecha: 2026-07-22
areas: [proceso]
tags: [archivo]
fuente: docs/archive/superpowers/specs/2026-07-22-a1-importador-presupuesto-design.md
resumen: Diseño: Fase A1 — Importador de presupuesto
---

# Diseño: Fase A1 — Importador de presupuesto

**Fecha:** 2026-07-22
**Estado:** aprobado en brainstorming (pendiente revisión final del spec)
**Roadmap:** fase A1 de `docs/superpowers/plans/2026-07-22-roadmap-pdc-v2.md` (submódulo Ensamble del Plan de Compras)

## Propósito

Llevar el presupuesto de obra (Excel exportado del software de presupuestos, hoja `Presupuesto`) a tablas MySQL de lps-aia aisladas por `project_id`, de forma **versionada, validada y transaccional**. Es la fuente de datos de todo el flujo posterior (maestro de insumos → paquetes → matching → plan).

## Decisiones de producto (usuario, 2026-07-22)

1. **Versionado acumulado con una activa:** cada import crea una versión nueva; la más reciente confirmada queda activa; las anteriores se conservan para auditoría. Todo el flujo aguas abajo lee la versión activa.
2. **Se persiste la jerarquía completa** (capítulos > subcapítulos > grupos > actividades) **más los insumos de APU** — habilita trazabilidad insumo←actividad y el matching por código de la fase A4.
3. **Validación todo-o-nada con reporte:** si el archivo tiene cualquier error, no se importa nada; se devuelve el reporte por fila/columna/motivo (tope ~200 errores) para corregir el Excel y reintentar.
4. **RBAC:** clave nueva `lps.pdc.importar` en el catálogo (patrón de la casa), asignada por defecto a Admin (A) y Director de Obra (D).
5. **Flujo en 2 pasos (enfoque B):** *preview* (subir → parsear → validar → resumen, sin escribir tablas) y *confirmar* (persistir como versión activa). Nadie activa un presupuesto sin ver qué contiene.

## Esquema de base de datos (migración `.sql` en `lps-aia/database/migrations/`)

Convenciones de tablas globales de lps-aia: `project_id int NOT NULL`, índices compuestos liderados por `project_id`, `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci`.

**`pdc_presupuesto_versiones`** — cabecera de cada import:
- `id` bigint AUTO_INCREMENT PK, `project_id` int NOT NULL
- `version_label` varchar(100) (columna `VERSION` del Excel, ej. "PI_Version_3")
- `archivo_nombre` varchar(255), `archivo_hash` char(64) (sha256 — detecta re-import de un archivo idéntico y lo advierte)
- `total_actividades` int, `total_insumos` int, `costo_total` decimal(18,2)
- `activa` tinyint NOT NULL DEFAULT 0 — **única activa por proyecto**, garantizada en la transacción de confirmación (`UPDATE pdc_presupuesto_versiones SET activa=0 WHERE project_id=?` + INSERT con `activa=1`)
- `importado_por` varchar(100), `created_at` datetime
- KEY (`project_id`, `activa`), KEY (`project_id`, `created_at`)

**`pdc_presupuesto_items`** — filas de la jerarquía:
- `id` bigint PK, `project_id`, `version_id` bigint NOT NULL (FK → versiones)
- `codigo` varchar(50) (jerárquico: `01`, `01.01`, `01.01.01.01`), `codigo_padre` varchar(50) NULL, `nivel` tinyint
- `tipo_fila` enum('capitulo','subcapitulo','grupo','actividad')
- `descripcion` varchar(500), `unidad` varchar(20) NULL, `cantidad` decimal(18,4) NULL, `id_apu` varchar(50) NULL
- KEY (`project_id`, `version_id`, `codigo`), KEY (`project_id`, `version_id`, `tipo_fila`)

**`pdc_presupuesto_apu_insumos`** — insumos por actividad:
- `id` bigint PK, `project_id`, `version_id`, `item_id` bigint NOT NULL (FK → items; la actividad dueña del APU)
- `descripcion` varchar(500) (nombre del insumo), `tipo_insumo` varchar(100) (`MAT-ACABADOS`, `SUBCONTRATACION PERSONAL`, …), `unidad` varchar(20)
- `cant_apu` decimal(18,6), `rendimiento` decimal(18,6), `cantidad_total` decimal(18,4) (**calculada al importar**: rendimiento × cantidad de la actividad)
- `valor_unitario` decimal(18,2), `valor_total` decimal(18,2), `iva` decimal(5,2) NULL
- KEY (`project_id`, `version_id`, `item_id`), KEY (`project_id`, `version_id`, `descripcion`(191))

Las versiones no activas se conservan; no hay borrado en esta fase.

## Endpoints (namespace `/plan-compras/api`, envelope `{ok,data|error}`, sesión global + RBAC `lps.pdc.importar` + CSRF `plan_compras_v2`)

1. **`POST /plan-compras/api/presupuesto/preview`** — multipart (`archivo`): límite 10MB, solo `.xlsx` (extensión + mime). Guarda el archivo en storage temporal privado (nombre aleatorio, fuera de rutas servibles, TTL con limpieza oportunista en cada preview), parsea y valida el archivo completo. Respuesta:
   - Válido: `{ok:true, data:{importToken, resumen:{versionLabel, capitulos, subcapitulos, grupos, actividades, insumos, costoTotal}, advertencias:[…]}}`
   - Con errores: `{ok:false, error:{code:'VALIDATION_FAILED', message, errores:[{fila, columna, motivo}]}}` — sin token: no hay nada que confirmar.
2. **`POST /plan-compras/api/presupuesto/confirmar`** — `{importToken}` (un solo uso; expira con el TTL). Re-parsea el temporal y persiste **en una transacción**: desactiva la versión activa previa, inserta cabecera + items + insumos, activa la nueva, borra el temporal. Respuesta: resumen de la versión creada.
3. **`GET /plan-compras/api/presupuesto/versiones`** — historial del proyecto (id, label, fecha, quién, totales, activa) para la UI.

## Parsing (PhpSpreadsheet — restricción SiteGround)

- **Modo read-only + iteración por filas** (no cargar el libro completo en memoria).
- Hoja `Presupuesto` obligatoria (si falta → `INVALID_FILE` con mensaje claro).
- Encabezados mapeados **por nombre** (tolerante al orden y a columnas extra).
- Discriminación de filas: con `Tipo Insumo` presente = insumo del APU (asociado a la actividad vigente); sin él = fila jerárquica (nivel por la profundidad del `Código`).
- Árbol validado por código: padre existente, actividad presente para cada insumo, números parseables, unidad presente en insumos.
- Las reglas exactas se calibran contra el Excel real de DAPORTO (`docs/102 - 2026 09 DAPORTO - RIONEGRO - PI_Version_3 (4).xlsx`, local no versionado) durante la implementación.

## UI (SPA — nace el submódulo Ensamble)

- **Navegación de 2 submódulos** en el shell de la SPA: *Ensamble* | *Seguimiento* (deshabilitado hasta B1).
- Vista **Importar presupuesto** (`#/ensamble/importar`): selector de archivo → llamada a preview → resumen en cards (conteos + costo total) → grilla AG Grid con el reporte de errores cuando los hay → botón **Confirmar e importar** (visible solo con preview válido) → al confirmar, éxito + historial de versiones (grilla con la activa marcada).
- Cliente: nueva función `apiUpload<T>(path, file, extra?)` en `src/lib/api.ts` (FormData; deja que el navegador ponga el Content-Type; incluye `X-CSRF-Token` y `X-AIA-Expect-Json`; mismo envelope y errores que `apiPost`).

## Manejo de errores

Códigos del envelope: `INVALID_FILE` (no es .xlsx / hoja faltante / encabezados irreconocibles), `VALIDATION_FAILED` (con `errores[]`), `FILE_TOO_LARGE`, `TOKEN_EXPIRED` (token vencido o ya usado), `FORBIDDEN`, `NO_PROJECT`. El cliente ya mapea HTTP 401/419 → `SESSION_EXPIRED`. Fallos de `json_encode` cubiertos por `JSON_INVALID_UTF8_SUBSTITUTE` (patrón ya adoptado).

## Testing (dos patas, sobre entorno real)

**Base de datos (MySQL 8 de Docker, nunca mocks):**
- Migración aplicada en el contenedor + verificación `SHOW CREATE TABLE` de las 3 tablas.
- Tests PHP autoejecutables (`tests/test_pdc_v2_import_*.php`) con fixtures `.xlsx` **generados por script** (PhpSpreadsheet) — uno válido y uno con errores: transaccionalidad (archivo inválido → 0 filas en las 3 tablas), versionado (2º confirm desactiva la 1ª versión), aislamiento por `project_id`, cálculo de `cantidad_total`.
- Gates: `test_global_table_safety.php` y `test_global_table_reconciliation.php` en verde.

**Aplicación:**
- Vitest: `apiUpload` (headers/FormData/envelope) y máquina de estados de la vista (idle → preview → confirmable → confirmado / con-errores).
- e2e Playwright: login → Ensamble → subir fixture pequeño → ver resumen → confirmar → la versión aparece activa en el historial.
- PHPStan sobre el PHP nuevo.

## Fuera de alcance de A1

Normalización contra el maestro de insumos (A2), paquetes (A3), matching con cronograma (A4), edición manual del presupuesto importado (no se edita: se re-importa).

## Riesgos

| Riesgo | Mitigación |
|---|---|
| Memoria/timeout de PhpSpreadsheet en SiteGround con presupuestos grandes | Read-only + iteración por filas; límite 10MB; medir con el Excel real de DAPORTO en Docker antes de staging |
| Acumulación de archivos temporales | TTL + limpieza oportunista en cada preview; confirm borra su temporal |
| Formato del Excel cambia entre exports del software de presupuestos | Mapeo de encabezados por nombre + errores accionables por columna faltante; fixtures de test cubren variaciones de orden |
