# Diseño: Fase A1.7 — Versionamiento inteligente del importador

**Fecha:** 2026-07-23
**Estado:** propuesto (pendiente revisión del usuario)
**Roadmap:** fase A1.7 de `docs/superpowers/plans/2026-07-22-roadmap-pdc-v2.md` (submódulo Ensamble; refuerzo de A1). Depende de A1 y reusa A1.6 (comparativo). Independiente de A2/A3.

## Motivación (hallazgo real)

Al cargar el presupuesto real **DAPORTO** (`102 - 2026 09 DAPORTO - RIONEGRO - PI_Version_3 (4).xlsx`), el usuario detectó que las versiones aparecen **sin identificador**. Diagnóstico confirmado (BD + código + parseo del archivo real):

- El versionado **sí ocurre**: cada cargue crea una versión nueva y la marca `activa=1` desactivando la anterior (`pdc_presupuesto_versiones`, proyecto 73 tiene la id 180 activa).
- Pero el `version_label` se copia crudo de la **columna VERSION del Excel**, y en el archivo real esa columna (índice 7) está **totalmente vacía** → label `''` → versiones indistinguibles (el selector muestra "—").
- La detección de duplicados existente (`previewDesdeArchivo`: `hash_file('sha256')` del **binario** vs `archivo_hash`) solo emite una **advertencia que no bloquea** — por eso el historial de pruebas acumuló 20+ versiones idénticas `PI_TEST_1`. Además, al ser hash del binario, dos exportaciones del mismo presupuesto con metadata distinta no se reconocen como iguales.

**Directriz del usuario:** *"Debe ser un versionamiento inteligente"*, reforzado con la **fecha de cargue**.

## Capacidades (acordadas con el usuario)

1. **Auto-numeración** — cada cargue recibe un número secuencial por proyecto (Versión 1, 2, 3…), SIEMPRE, independiente de la columna VERSION del Excel.
2. **Fecha de cargue** — parte reforzada del identificador (Versión N · fecha/hora del cargue).
3. **Anti-duplicado por contenido** — si el contenido del cargue es idéntico a la **versión activa** (hash del contenido normalizado, no del binario), NO se crea una versión nueva; se avisa "sin cambios respecto a la Versión N".
4. **Auto-comparativo al cargar** — tras confirmar una versión nueva (cuando hay una anterior), se muestra el resumen de qué cambió vs la versión anterior (reusa el comparativo de A1.6).

## Esquema de base de datos (migración `.sql` en `lps-aia/database/migrations/`)

`ALTER TABLE pdc_presupuesto_versiones` (aditivo):
- `version_numero int NOT NULL DEFAULT 0` — secuencial por proyecto (identificador estable del versionado inteligente).
- `contenido_hash char(64) DEFAULT NULL` — SHA-256 del **contenido canónico** (items + insumos), independiente del binario. Distinto de `archivo_hash` (que se conserva para trazabilidad del archivo original).
- `KEY idx_pdcpv_project_numero (project_id, version_numero)`.

**Backfill** (`.php` dry-run→`--apply` o inline en el `.sql` con variable): a las versiones existentes se les asigna `version_numero` por orden de `created_at` asc dentro de cada proyecto (`ROW_NUMBER()` por `project_id`). `contenido_hash` de las históricas queda NULL (solo se calcula en cargues nuevos; el anti-duplicado compara contra la activa, que se recalcula al re-importar; una activa con hash NULL simplemente no dispara "sin cambios" hasta el próximo cargue).

## Backend (lps-aia)

**Hash de contenido canónico** — nuevo helper en `PresupuestoImportService` (o en el parser): serializa determinísticamente los `items` (codigo, tipo_fila, cantidad) y los `insumos` (agrupados por codigo de actividad: descripcion_norm, unidad, cantidad_total, valor_total), **ordenados por código**, y calcula `sha256`. Estable ante reordenamiento irrelevante de filas y metadata del Excel. Usa `MaestroInsumosService::normalizar` para las descripciones (misma norma que A2).

**`previewDesdeArchivo`** — además de lo actual:
- Calcula `contenidoHash`.
- Consulta la versión **activa** del proyecto (`version_numero`, `contenido_hash`). Si `contenido_hash` coincide → retorna `sinCambios: true` + `versionActiva: {numero, label, createdAt}`. (La advertencia binaria actual se mantiene como señal secundaria o se reemplaza por esta, más fuerte.)
- Guarda `contenidoHash` en el meta del token temporal.

**`confirmar`** — cambios:
- Si el `contenidoHash` del temporal coincide con el `contenido_hash` de la versión activa → **no crea versión**; retorna `{ok:true, sinCambios:true, versionId:<activa>, versionNumero:<activa>}`. (Idempotencia semántica: recargar lo mismo no ensucia el historial.)
- Si no → `version_numero = COALESCE(MAX(version_numero),0)+1` del proyecto; persiste `version_numero` y `contenido_hash`; captura el `versionId` de la versión que estaba activa ANTES del `UPDATE ... activa=0` y lo retorna como `versionIdAnterior` (null si es el primer cargue). Retorna `{ok:true, versionId, versionNumero, versionIdAnterior}`.

**`versiones()`** — incluir `version_numero` en la respuesta (el front compone el display "Versión N").

**Controller** (`PlanComprasImportController`): `preview()` propaga `sinCambios`/`versionActiva`; `confirmar()` propaga `sinCambios`/`versionId`/`versionNumero`/`versionIdAnterior`. Sin endpoints nuevos (el auto-comparativo reusa `GET …/comparar`).

## UI (SPA — vista Importar)

- **Tras preview:** si `sinCambios`, mostrar aviso "Este presupuesto es idéntico a la **Versión N** (activa); no se creará una versión nueva." y ajustar el botón (deshabilitar confirmar, o texto "Sin cambios"). Si no, flujo normal.
- **Tras confirmar:**
  - Si `sinCambios` → mensaje "Sin cambios: se mantiene la Versión N."
  - Si versión nueva con `versionIdAnterior` → mensaje "Cargada la **Versión N** ({fecha})" + **resumen del auto-comparativo** (llama `GET …/comparar?versionA=<anterior>&versionB=<nueva>`: costoA→costoB, Δ, sobrecostos/ahorros, nuevos/eliminados/modificados) + CTA **"Ver comparativo completo"** → navega a `#/ensamble/comparar` (preseleccionado anterior vs nueva).
  - Si es el primer cargue (sin anterior) → "Cargada la Versión 1 ({fecha})".
- **Historial de versiones** (grilla de import) y **selectores de versión** (Visor A1.5, Comparar A1.6): mostrar **"Versión {N} · {fecha}"** compuesto desde `version_numero` + `createdAt` (+ el `version_label`/VERSION del Excel como descripción secundaria si no está vacío). Esto arregla el "—" que se veía.

## Testing (dos patas, entorno real)

**BD/PHP (MySQL 8 de Docker):**
- Migración + `SHOW CREATE TABLE` (columnas nuevas, índice); backfill deja los existentes numerados por created_at.
- Tests autoejecutables (proyectos 999901/999902): auto-numeración incremental por proyecto (1,2,3); contenido_hash idéntico a la activa → confirmar NO duplica (retorna la activa, sinCambios); contenido distinto → nueva versión numero+1 con `versionIdAnterior` correcto; hash canónico estable ante reordenamiento de filas irrelevante; aislamiento por proyecto; `versiones()` trae `version_numero`.
- Gates `test_global_table_safety`/`reconciliation`.

**Aplicación:** Vitest del estado del import (aviso sinCambios, resumen del auto-comparativo, navegación); e2e Playwright: cargar el mismo fixture dos veces → la 2ª avisa "sin cambios" y NO crea versión; cargar un fixture modificado → nueva versión + resumen de cambios + CTA al comparativo; el selector muestra "Versión N · fecha".

## Fuera de alcance

- **Revertir/activar manualmente** una versión histórica (reactivar una vieja como activa) — follow-up natural, pero no en A1.7.
- **Diff semántico parcial** (reconocer "casi igual"): el anti-duplicado es por hash exacto del contenido canónico (todo-o-nada).
- Limpieza/retención del historial (borrar versiones viejas) — follow-up.

## Riesgos

| Riesgo | Mitigación |
|---|---|
| Hash de contenido inestable por orden de filas | Serialización canónica **ordenada por código** + normalización de descripciones; test explícito de estabilidad ante reordenamiento |
| Backfill de `version_numero` con 20+ versiones de prueba en Da Porto | `ROW_NUMBER()` por proyecto ordenado por created_at; idempotente (solo setea donde numero=0) |
| Anti-duplicado demasiado estricto bloquea un re-cargue legítimo | Compara solo contra la **activa** (no todo el historial): volver a una versión anterior distinta de la activa SÍ crea versión; recargar exactamente lo activo no |
| `contenido_hash` NULL en históricas | La comparación trata NULL como "distinto" (no dispara sinCambios); se puebla al primer re-cargue |
