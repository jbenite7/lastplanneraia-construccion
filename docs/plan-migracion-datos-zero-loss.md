# Plan de Migracion de Datos Zero-Loss (RPO=0)

> 🚀 **Arquitectura Futura / Ready for Development**

## 1) Objetivo

Migrar datos desde tablas legacy por prefijo (`{prefijo}_*`) hacia el esquema compartido (`project_*` con `project_id`) **sin perdida de informacion**.

Definicion operativa de zero-loss:

- RPO = 0 en el momento de cutover.
- Ninguna fila confirmada en legacy se pierde en destino.
- Ninguna fila se duplica en destino.
- Cualquier error de lote es trazable, repetible e idempotente.

---

## 2) Principios no negociables

1. Legacy se mantiene como fuente de verdad hasta cierre formal.
2. No se ejecutan `DROP`, `TRUNCATE` ni borrados destructivos sobre origen durante migracion.
3. Toda carga es idempotente (`UPSERT` por llave de negocio o llave tecnica estable).
4. Todo lote deja evidencia (`run_id`, conteo, checksum, estado, error).
5. No hay cutover sin reconciliacion final aprobada (Go/No-Go).

---

## 3) Artefactos de control requeridos

Crear tablas de control de migracion (en misma BD operativa o BD de control):

```sql
CREATE TABLE IF NOT EXISTS migration_runs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  started_at DATETIME NOT NULL,
  finished_at DATETIME NULL,
  mode ENUM('full','delta','cutover_delta') NOT NULL,
  status ENUM('running','success','failed','cancelled') NOT NULL,
  requested_by VARCHAR(120) NOT NULL,
  notes TEXT NULL
);

CREATE TABLE IF NOT EXISTS migration_batches (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  run_id BIGINT UNSIGNED NOT NULL,
  project_id BIGINT UNSIGNED NOT NULL,
  source_table VARCHAR(191) NOT NULL,
  target_table VARCHAR(191) NOT NULL,
  chunk_start BIGINT NULL,
  chunk_end BIGINT NULL,
  rows_read BIGINT NOT NULL DEFAULT 0,
  rows_written BIGINT NOT NULL DEFAULT 0,
  checksum_source VARCHAR(64) NULL,
  checksum_target VARCHAR(64) NULL,
  status ENUM('running','success','failed') NOT NULL,
  error_message TEXT NULL,
  started_at DATETIME NOT NULL,
  finished_at DATETIME NULL,
  KEY idx_mig_batches_run (run_id),
  KEY idx_mig_batches_proj (project_id)
);

CREATE TABLE IF NOT EXISTS migration_watermarks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id BIGINT UNSIGNED NOT NULL,
  source_table VARCHAR(191) NOT NULL,
  watermark_type ENUM('id','semana_id','timestamp','none') NOT NULL,
  watermark_value VARCHAR(191) NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_watermark (project_id, source_table)
);

CREATE TABLE IF NOT EXISTS migration_reconciliation (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  run_id BIGINT UNSIGNED NOT NULL,
  project_id BIGINT UNSIGNED NOT NULL,
  table_name VARCHAR(191) NOT NULL,
  source_count BIGINT NOT NULL,
  target_count BIGINT NOT NULL,
  diff_count BIGINT NOT NULL,
  source_checksum VARCHAR(64) NULL,
  target_checksum VARCHAR(64) NULL,
  status ENUM('ok','warn','fail') NOT NULL,
  details TEXT NULL,
  checked_at DATETIME NOT NULL,
  KEY idx_mig_rec_run (run_id),
  KEY idx_mig_rec_proj (project_id)
);

CREATE TABLE IF NOT EXISTS migration_errors (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  run_id BIGINT UNSIGNED NOT NULL,
  project_id BIGINT UNSIGNED NOT NULL,
  source_table VARCHAR(191) NOT NULL,
  source_pk VARCHAR(191) NULL,
  error_code VARCHAR(64) NULL,
  error_message TEXT NOT NULL,
  payload JSON NULL,
  created_at DATETIME NOT NULL,
  KEY idx_mig_err_run (run_id),
  KEY idx_mig_err_proj (project_id)
);
```

---

## 4) Matriz exacta de migracion (tabla -> llave -> validacion)

Referencia de prefijo: `projects.code` (ej. `milanCampestre`).

## 4.1 Base y seguridad

### A) `general_proyectos_procesos` -> `projects`

- Grano: 1 fila = 1 proyecto.
- Llave UPSERT: `projects.code` (UNIQUE).
- Campos de control: `nombre`, `activo`, `pdc_activo`, `costo_dia_retraso`.
- Validacion: conteo exacto y `code` sin duplicados.

### B) `general_usuarios` -> `users` + `project_members`

- Grano users: 1 fila por `usuario` (deduplicar).
- Llave UPSERT users: `users.usuario` (UNIQUE), respaldo `email`.
- Grano members: 1 fila por par (`project_id`,`user_id`).
- Llave UPSERT members: `UNIQUE(project_id,user_id)`.
- Validacion: usuarios unicos exactos + membresias exactas por proyecto.

---

## 4.2 Operacion compartida por proyecto

### C) `{prefijo}_semanas_activas` -> `project_semanas_activas`

- Grano: (`project_id`,`semana`).
- Llave UPSERT: `UNIQUE(project_id,semana)`.
- Watermark: `semana` (o `id` si existe).
- Validacion: conteo exacto por semana + fechas iguales.

### D) `{prefijo}_subcontratistas` -> `project_subcontratistas`

- Grano: (`project_id`,`subcontratista`).
- Llave UPSERT: `UNIQUE(project_id,subcontratista)`.
- Validacion: conteo exacto + control de `nit` duplicado.

### E) `{prefijo}_profesionales` -> `project_profesionales`

- Grano: (`project_id`,`nombre`).
- Llave UPSERT: `UNIQUE(project_id,nombre)`.
- Validacion: conteo exacto + emails validos/no validos contabilizados.

### F) `{prefijo}_programa` -> `project_programa`

- Grano: fila de actividad del plan.
- Llave UPSERT recomendada: (`project_id`,`consecutivo_legacy`,`ext_id`) segun disponibilidad.
- Watermark: `Consecutivo` o `Id` legacy.
- Campos checksum: `actividad`, `fecha_inicio`, `fecha_fin`, `ejecutado`, `estado_restricciones`.
- Validacion: conteo exacto + sumatorias de `ejecutado` dentro tolerancia 1e-6.

### G) `{prefijo}_programa_consolidado` -> `project_programa_consolidado`

- Grano: fila por semana/actividad.
- Llave UPSERT recomendada: (`project_id`,`semana`,`consecutivo_en_programa`,`ext_id`).
- Watermark: `id` si existe, de lo contrario (`semana`,`consecutivo_en_programa`).
- Campos checksum: `ejecutado`, `estado_restricciones`, `sub_contratista`, `responsable_aia`.
- Validacion: conteo exacto por (`project_id`,`semana`).

### H) `{prefijo}_programacion_semanal` -> `project_programacion_semanal`

- Grano: fila semanal operativa.
- Llave UPSERT recomendada: (`project_id`,`semana`,`consecutivo_en_programa`,`ext_id`).
- Watermark: `id` legacy o (`semana`,`consecutivo_en_programa`).
- Campos checksum: `pac`, `p_completado`, `compromiso`, `ejecutado_real`.
- Validacion: conteo exacto por semana + sumatorias de metricas.

### I) `{prefijo}_pdc` -> `project_pdc`

- Grano: fila de paquete/contrato por semana.
- Llave UPSERT recomendada: (`project_id`,`semana`,`titulo`,`paquete_contratacion`).
- Watermark: `id` legacy o (`semana`,`titulo`).
- Campos checksum: `valor_presupuesto`, `valor_adjudicado`, `fecha_inicio_proyectada`, `fecha_real_inicio`.
- Validacion: conteo exacto + sumatorias monetarias exactas a 2 decimales.

### J) `{prefijo}_papelera_pdc` -> `project_papelera_pdc`

- Grano: fila eliminada de PDC.
- Llave UPSERT recomendada: (`project_id`,`semana`,`titulo`,`deleted_at`).
- Watermark: `id` legacy.
- Validacion: conteo exacto + campo de auditoria no nulo.

### K) `{prefijo}_cic` -> `project_cic`

- Grano: (`project_id`,`semana`,`subcontratista`).
- Llave UPSERT: `UNIQUE(project_id,semana,subcontratista)`.
- Campos checksum: `pac`, `p_completado`, `cal_integral`.
- Validacion: conteo exacto por semana + promedio de `pac` equivalente.

### L) `{prefijo}_cip` -> `project_cip`

- Grano: (`project_id`,`semana`,`profesional`).
- Llave UPSERT: `UNIQUE(project_id,semana,profesional)`.
- Campos checksum: `pac`, `p_completado`, `pac_consolidado`.
- Validacion: conteo exacto por semana + promedio de `pac` equivalente.

### M) `{prefijo}_indicadores_generales` -> `project_indicadores_generales`

- Grano: (`project_id`,`semana`,`rol`,`subcontratista_profesional`).
- Llave UPSERT: `UNIQUE(project_id,semana,rol,subcontratista_profesional)`.
- Campos checksum: `pac`, `pac_acum`, `comp`, `comp_acum`.
- Validacion: conteo exacto por semana/rol.

### N) `{prefijo}_cambios` -> `project_cambios` (si se activa en DDL)

- Grano: fila de cambio.
- Llave UPSERT recomendada: (`project_id`,`fecha_solicitud`,`tipo_cambio`,`descripcion`).
- Validacion: conteo exacto + fechas y aprobaciones.

### O) `{prefijo}_actividades` -> `project_actividades` (si se activa en DDL)

- Grano: actividad tecnica.
- Llave UPSERT recomendada: (`project_id`,`codigo`,`actividad_inicio`,`fecha_inicio`).
- Validacion: conteo exacto + campos de paquete SI/S/MO preservados.

---

## 5) Orden exacto de migracion (con dependencias)

Orden obligatorio para evitar huerfanos y retrabajo:

1. `projects`
2. `users`
3. `project_members`
4. Catalogos (`cat_*`, `project_costos_*`, `project_cuadrillas_*`)
5. `project_semanas_activas`
6. `project_subcontratistas`
7. `project_profesionales`
8. `project_programa`
9. `project_programa_consolidado`
10. `project_programacion_semanal`
11. `project_pdc`
12. `project_papelera_pdc`
13. `project_cic`
14. `project_cip`
15. `project_indicadores_generales`
16. `project_cambios` (si aplica)
17. `project_actividades` (si aplica)

Regla de ejecucion por tabla:

- Full load por chunks.
- Reconciliacion parcial.
- Delta incremental.
- Reconciliacion final.

---

## 6) Metodo de carga para RPO=0

## 6.1 Full load (sin detener operacion)

- Ejecutar por proyecto y por tabla en chunks (5k-20k filas).
- Cada chunk en transaccion propia.
- `UPSERT` para idempotencia.
- Persistir watermark al terminar cada chunk.

## 6.2 Delta sync (pre-cutover)

- Repetir corrida por watermarks hasta que lag sea bajo.
- Medir lag por tabla/proyecto (filas pendientes).
- Objetivo pre-cutover: lag < 0.1% por tabla critica.

## 6.3 Cutover delta (ventana)

- Congelar escrituras de aplicacion (o cola temporal).
- Ejecutar delta final hasta lag = 0.
- Correr reconciliacion final.
- Habilitar lectura shared (`shared_schema_enabled=true`).

---

## 7) Reconciliacion obligatoria (numerica)

Se valida por tabla/proyecto, y para semanales tambien por semana.

## 7.1 Conteo

- `source_count == target_count` en tablas criticas.

## 7.2 Checksums

Usar checksum agregada estable por columnas criticas:

```sql
SELECT
  COUNT(*) AS c,
  LPAD(HEX(SUM(CRC32(CONCAT_WS('#',
    COALESCE(col1,''), COALESCE(col2,''), COALESCE(col3,'')
  )))), 8, '0') AS checksum_crc
FROM tabla
WHERE ...;
```

## 7.3 Sumatorias de negocio

- PDC: `SUM(valor_presupuesto)`, `SUM(valor_adjudicado)`.
- Programacion: `SUM(pac)`, `SUM(p_completado)`, `SUM(compromiso)`.
- Curvas on-demand: puntos semanales equivalentes vs legacy.

## 7.4 Integridad

- Huerfanos FK = 0.
- Duplicados de llaves unicas = 0.

---

## 8) Checklist Go/No-Go (umbral numerico)

## 8.1 Go (todos deben cumplirse)

- [ ] `diff_count = 0` en tablas criticas (`programacion`, `programa_consolidado`, `pdc`, `cic`, `cip`, `indicadores`).
- [ ] Duplicados en llaves unicas = 0.
- [ ] Huerfanos FK = 0.
- [ ] Errores severidad alta en `migration_errors` = 0.
- [ ] Lag delta final = 0 filas.
- [ ] Smoke tests funcionales core aprobados.

## 8.2 Warn (aceptable solo con ticket)

- [ ] Diferencia <= 0.01% en tabla no critica y con explicacion documentada.
- [ ] Diferencia monetaria <= 0.01 por redondeo confirmado.

## 8.3 No-Go automatico

- [ ] Cualquier perdida en tabla critica.
- [ ] Cualquier duplicado en tabla critica.
- [ ] Cualquier orfandad FK en dominio core.
- [ ] Fallo de restauracion en prueba de backup.

---

## 9) Plan de rollback sin perdida

1. Cambiar flag a legacy: `shared_schema_enabled=false`.
2. Reabrir flujo de lectura/escritura legacy.
3. Conservar datos shared para diagnostico (no borrar).
4. Reportar causa raiz y lote exacto afectado.
5. Corregir mapping/proceso y repetir solo lotes fallidos (idempotente).

Objetivo de rollback: <= 15 minutos.

---

## 10) Runbook de ejecucion (tiempo real)

## T-7 dias

- Validar backup + restore.
- Ejecutar full load en staging.
- Corregir mappings/anomalias.

## T-2 dias

- Full load en produccion (sin corte).
- Delta periodico cada 30-60 min.

## T-60 min (ventana)

- Congelar escrituras.
- Delta final.
- Reconciliacion final.

## T-10 min

- Switch de feature flag a shared.
- Smoke tests.

## T+60 min

- Si estable, cerrar ventana.
- Si no, rollback inmediato.

---

## 11) Evidencias minimas para auditoria

- Registro de `migration_runs` y `migration_batches`.
- Reporte de reconciliacion firmado por proyecto/tabla.
- Log de errores con remediacion.
- Captura de metricas de latencia antes/despues.
- Acta de Go/No-Go y acta de cierre o rollback.

---

## 12) Nota operativa importante

El archivo `docs/migrate_legacy_to_shared_template.php` es plantilla base.
Antes de uso en produccion debe reforzarse con:

1. UPSERT real por llave de negocio en cada tabla.
2. Carga por chunks con watermarks persistidos.
3. Registro obligatorio en tablas de control (`migration_*`).
4. Reconciliacion automatica bloqueante antes de cutover.
