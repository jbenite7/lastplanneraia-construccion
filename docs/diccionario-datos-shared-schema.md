# Diccionario de Datos — Shared Schema

> Generado desde `database/migrations/001_shared_schema.sql`
> Fecha: 2026-06-26
> Charset: utf8mb4 COLLATE utf8mb4_unicode_ci
> Engine: InnoDB

## Resumen

| Concepto | Valor |
|----------|-------|
| Tablas base | 2 (`projects`, `audit_actions`) |
| Catálogos | 3 (`cat_cnc`, `cat_codigos_actividades`, `cat_dias_procesos_contratacion`) |
| Tablas operativas | 12 (`project_*`) |
| Tablas auxiliares | 2 (`project_costos_cuadrillas`, `project_cuadrillas_tipicas`) |
| **Total** | **19 tablas** |
| FK constraints | 19 (todas con `ON DELETE RESTRICT`) |
| Unique keys | 14 |
| Indices adicionales | 10 |

---

## Tablas Base

### `projects`
**Origen:** `general_proyectos_procesos`
**Descripción:** Catálogo maestro de proyectos. Cada fila representa una obra o proyecto de construcción.

| Columna | Tipo | Nulo | Default | PK/FK/Idx | Descripción |
|---------|------|------|---------|-----------|-------------|
| id | INT UNSIGNED | NO | — | PK | ID auto-increment del proyecto |
| code | VARCHAR(100) | NO | — | UK | Código único del proyecto (ej. `prueba`, `da_porto`) |
| name | VARCHAR(255) | NO | — | — | Nombre comercial del proyecto |
| area | VARCHAR(50) | SI | NULL | — | Área: `Construcción`, `Pre-Construcción`, etc. |
| is_active | TINYINT(1) | SI | 0 | — | 1=activo, 0=inactivo |
| pdc_active | TINYINT(1) | SI | 0 | — | 1=PDC habilitado para el proyecto |
| linea_base_start | DATE | SI | NULL | — | Fecha de inicio línea base |
| linea_base_end | DATE | SI | NULL | — | Fecha de fin línea base |
| costo_dia_retraso | DECIMAL(14,2) | SI | NULL | — | Costo estimado por día de retraso |
| created_at | TIMESTAMP | SI | CURRENT_TIMESTAMP | — | Fecha de creación del registro |
| updated_at | TIMESTAMP | SI | CURRENT_TIMESTAMP ON UPDATE | — | Fecha de última modificación |

**UK:** `uk_projects_code` (code)

---

### `audit_actions`
**Origen:** `general_auditoria_acciones`
**Descripción:** Auditoría de acciones de usuarios en el sistema.

| Columna | Tipo | Nulo | Default | PK/FK/Idx | Descripción |
|---------|------|------|---------|-----------|-------------|
| id | BIGINT UNSIGNED | NO | — | PK | ID auto-increment |
| project_id | INT UNSIGNED | SI | NULL | FK→projects, Idx | Proyecto asociado (nulo si es acción global) |
| fecha | DATETIME | SI | NULL | Idx | Fecha y hora de la acción |
| usuario | VARCHAR(100) | SI | NULL | Idx | Nombre de usuario |
| id_sesion | VARCHAR(100) | SI | NULL | — | ID de sesión |
| modulo | VARCHAR(100) | SI | NULL | — | Módulo del sistema |
| accion | VARCHAR(100) | SI | NULL | — | Acción realizada |
| event_code | VARCHAR(120) | SI | NULL | — | Código de evento |
| event_action | VARCHAR(80) | SI | NULL | — | Tipo de acción del evento |
| event_result | VARCHAR(20) | SI | NULL | — | Resultado (success/failure) |
| descripcion | TEXT | SI | NULL | — | Descripción textual |
| context_json | JSON | SI | NULL | — | Contexto adicional en JSON |
| ip_address | VARCHAR(45) | SI | NULL | — | Dirección IP del usuario |

**FK:** `fk_audit_actions_project` → `projects(id)` ON DELETE RESTRICT
**Indices:** `idx_audit_actions_project_id`, `idx_audit_actions_usuario`, `idx_audit_actions_fecha`

---

## Catálogos Globales

### `cat_cnc`
**Origen:** `general_cnc`
**Descripción:** Catálogo de Causas de No Cumplimiento (CNC). Clasificación de razones por las que una asignación no se completó.

| Columna | Tipo | Nulo | Default | PK/FK/Idx | Descripción |
|---------|------|------|---------|-----------|-------------|
| id | INT UNSIGNED | NO | — | PK | ID auto-increment |
| category | VARCHAR(100) | NO | — | UK | Categoría de la CNC (ej. `Planificación`, `Materiales`) |
| code | VARCHAR(100) | NO | — | UK | Código de la CNC (ej. `M-01`, `M-02`) |

**UK:** `uk_cat_cnc_category_code` (category, code)

---

### `cat_codigos_actividades`
**Origen:** `general_codigos_actividades`
**Descripción:** Catálogo de códigos de actividades estándar usados en la plataforma.

| Columna | Tipo | Nulo | Default | PK/FK/Idx | Descripción |
|---------|------|------|---------|-----------|-------------|
| id | INT UNSIGNED | NO | — | PK | ID auto-increment |
| codigo | VARCHAR(20) | SI | NULL | — | Código de la actividad |
| actividad | VARCHAR(300) | SI | NULL | — | Nombre de la actividad |
| unidad | VARCHAR(50) | SI | NULL | — | Unidad de medida |

---

### `cat_dias_procesos_contratacion`
**Origen:** `general_dias_procesos_contratacion`
**Descripción:** Configuración de días estimados por etapa del proceso de contratación (PDC).

| Columna | Tipo | Nulo | Default | PK/FK/Idx | Descripción |
|---------|------|------|---------|-----------|-------------|
| id | INT UNSIGNED | NO | — | PK | ID auto-increment |
| paquete_contratacion | VARCHAR(200) | SI | NULL | — | Nombre del paquete de contratación |
| tipo_paquete | VARCHAR(200) | SI | NULL | — | Tipo de paquete |
| dias_elaboracion_pliegos | INT | SI | NULL | — | Días estimados para elaboración de pliegos |
| dias_entrega_pliegos | INT | SI | NULL | — | Días estimados para entrega de pliegos |
| dias_recibo_propuestas | INT | SI | NULL | — | Días estimados para recibo de propuestas |
| dias_cuadros_comparativos | INT | SI | NULL | — | Días estimados para cuadros comparativos |
| dias_legalizacion_contrato | INT | SI | NULL | — | Días estimados para legalización de contrato |
| dias_fabricacion | INT | SI | NULL | — | Días estimados para fabricación |
| dias_insumos_obra | INT | SI | NULL | — | Días estimados para insumos de obra |

---

## Tablas Operativas (project_*)

### `project_costos_cuadrillas`
**Origen:** `general_costos_cuadrillas`
**Descripción:** Costos de cuadrillas por proyecto. Define costo/hora de oficiales y ayudantes.

| Columna | Tipo | Nulo | Default | PK/FK/Idx | Descripción |
|---------|------|------|---------|-----------|-------------|
| id | INT UNSIGNED | NO | — | PK | ID auto-increment |
| project_id | INT UNSIGNED | NO | — | FK, UK | Proyecto asociado |
| costo_hora_oficial | DECIMAL(10,2) | SI | NULL | — | Costo por hora de oficial |
| costo_hora_ayudante | DECIMAL(10,2) | SI | NULL | — | Costo por hora de ayudante |

**FK:** `fk_pcostos_project` → `projects(id)` ON DELETE RESTRICT
**UK:** `uk_pcostos_project` (project_id) — 1 fila por proyecto

---

### `project_cuadrillas_tipicas`
**Origen:** `general_cuadrillas_tipicas`
**Descripción:** Configuración de cuadrillas típicas por actividad y proyecto.

| Columna | Tipo | Nulo | Default | PK/FK/Idx | Descripción |
|---------|------|------|---------|-----------|-------------|
| id | INT UNSIGNED | NO | — | PK | ID auto-increment |
| project_id | INT UNSIGNED | NO | — | FK | Proyecto asociado |
| codigo_actividad | VARCHAR(200) | NO | — | — | Código de la actividad |
| oficiales_tipica | INT | NO | — | — | Número de oficiales en cuadrilla típica |
| ayudantes_tipica | INT | NO | — | — | Número de ayudantes en cuadrilla típica |
| rendimiento_tipica | DECIMAL(10,2) | NO | — | — | Rendimiento esperado de la cuadrilla |
| numero_cuadrillas_tipicas | INT | NO | — | — | Número de cuadrillas típicas |

**FK:** `fk_pcuadrillas_project` → `projects(id)` ON DELETE RESTRICT
**UK:** `uk_pcuadrillas_project_actividad` (project_id, codigo_actividad)

---

### `project_semanas_activas`
**Origen:** `{prefix}_semanas_activas`
**Descripción:** Semanas activas por proyecto. Define el calendario semanal de cada obra.

| Columna | Tipo | Nulo | Default | PK/FK/Idx | Descripción |
|---------|------|------|---------|-----------|-------------|
| id | BIGINT UNSIGNED | NO | — | PK | ID auto-increment |
| project_id | INT UNSIGNED | NO | — | FK, UK | Proyecto asociado |
| legacy_id | INT | SI | NULL | — | ID original en tabla legacy |
| semana | INT | NO | — | UK, Idx | Número de semana |
| fecha_inicio_sem | DATE | NO | — | — | Fecha de inicio de la semana |
| fecha_fin_sem | DATE | NO | — | — | Fecha de fin de la semana |
| semanal_confirmada | TINYINT(1) | SI | 0 | — | 1=programación semanal confirmada |
| fecha_cierre_compromisos | DATE | SI | NULL | — | Fecha de cierre de compromisos |
| fecha_ultimo_saneo | DATETIME | SI | NULL | — | Último saneo de datos |
| fecha_creacion_semana | DATE | SI | NULL | — | Fecha de creación de la semana |
| reprogramacion | TINYINT(1) | SI | 0 | — | 1=la semana fue reprogramada |
| diferencia_estructura_cron | INT | SI | 0 | — | Diferencia con estructura cronológica |

**FK:** `fk_psemanas_project` → `projects(id)` ON DELETE RESTRICT
**UK:** `uk_psemanas_project_semana` (project_id, semana)
**Idx:** `idx_psemanas_semana` (project_id, semana)

---

### `project_subcontratistas`
**Origen:** `{prefix}_subcontratistas`
**Descripción:** Subcontratistas por proyecto.

| Columna | Tipo | Nulo | Default | PK/FK/Idx | Descripción |
|---------|------|------|---------|-----------|-------------|
| id | INT UNSIGNED | NO | — | PK | ID auto-increment |
| project_id | INT UNSIGNED | NO | — | FK | Proyecto asociado |
| legacy_id | INT | SI | NULL | — | ID original en tabla legacy |
| subcontratista | VARCHAR(200) | NO | — | UK | Nombre del subcontratista |
| correo_contacto | VARCHAR(200) | SI | NULL | — | Correo de contacto |
| nit | VARCHAR(20) | SI | NULL | — | NIT del subcontratista |
| alcance | VARCHAR(200) | SI | NULL | — | Alcance del servicio |
| tipo_proveedor | VARCHAR(200) | SI | NULL | — | Tipo de proveedor |
| activo | TINYINT(1) | SI | 1 | — | 1=activo, 0=inactivo |

**FK:** `fk_psubcontratistas_project` → `projects(id)` ON DELETE RESTRICT
**UK:** `uk_psubcontratistas_project_nombre` (project_id, subcontratista)
**Idx:** `idx_psubcontratistas_nombre` (project_id, subcontratista)

---

### `project_profesionales`
**Origen:** `{prefix}_profesionales`
**Descripción:** Profesionales asignados a cada proyecto (residentes, directores, etc.).

| Columna | Tipo | Nulo | Default | PK/FK/Idx | Descripción |
|---------|------|------|---------|-----------|-------------|
| id | INT UNSIGNED | NO | — | PK | ID auto-increment |
| project_id | INT UNSIGNED | NO | — | FK | Proyecto asociado |
| legacy_id | INT | SI | NULL | — | ID original en tabla legacy |
| nombre | VARCHAR(100) | NO | — | UK | Nombre del profesional |
| email | VARCHAR(100) | SI | NULL | — | Correo electrónico |
| cargo | VARCHAR(100) | SI | NULL | — | Cargo del profesional |
| activo | TINYINT(1) | SI | 1 | — | 1=activo, 0=inactivo |

**FK:** `fk_pprofesionales_project` → `projects(id)` ON DELETE RESTRICT
**UK:** `uk_pprofesionales_project_nombre` (project_id, nombre)

---

### `project_programa`
**Origen:** `{prefix}_programa`
**Descripción:** Programa maestro de actividades por proyecto (Plan Maestro / Programa General).

| Columna | Tipo | Nulo | Default | PK/FK/Idx | Descripción |
|---------|------|------|---------|-----------|-------------|
| id | BIGINT UNSIGNED | NO | — | PK | ID auto-increment |
| project_id | INT UNSIGNED | NO | — | FK | Proyecto asociado |
| legacy_id | INT | SI | NULL | — | ID original en tabla legacy |
| consecutivo | INT | NO | — | Idx | Consecutivo de la actividad en el programa |
| id_actividad | VARCHAR(500) | SI | NULL | — | ID de la actividad (legacy) |
| actividad | VARCHAR(500) | SI | NULL | — | Nombre de la actividad |
| titulo | INT | SI | NULL | — | Título/Nivel del WBS |
| fecha_inicio | DATE | SI | NULL | — | Fecha de inicio planificada |
| fecha_fin | DATE | SI | NULL | — | Fecha de fin planificada |
| ruta_critica | TINYINT(1) | SI | NULL | — | 1=actividad en ruta crítica |
| ejecutado | DECIMAL(10,2) | SI | 0.00 | — | % ejecutado |
| estado | VARCHAR(50) | SI | NULL | — | Estado de la actividad |
| semanas_inicio | INT | SI | 0 | — | Semanas desde inicio |
| estado_restricciones | DECIMAL(10,2) | SI | 0.00 | — | % de restricciones liberadas |
| dy_e | DECIMAL(10,2) | SI | 0.00 | — | Diseño y Estudios |
| materiales | DECIMAL(10,2) | SI | 0.00 | — | % restricción materiales |
| mde_o | DECIMAL(10,2) | SI | 0.00 | — | % restricción Mano de Obra |
| equipos | DECIMAL(10,2) | SI | 0.00 | — | % restricción equipos |
| predecesora | DECIMAL(10,2) | SI | 0.00 | — | % restricción predecesora |
| pdto_cons | DECIMAL(10,2) | SI | 0.00 | — | % restricción producto de consumo |
| modelo | VARCHAR(9) | SI | '0' | — | Modelo (legacy) |
| responsable_aia | VARCHAR(100) | SI | NULL | — | Responsable AIA asignado |
| observaciones | MEDIUMTEXT | SI | NULL | — | Observaciones |
| ult_act_est | DATE | SI | NULL | — | Última actualización de estado |
| ult_act_restr | DATE | SI | NULL | — | Última actualización de restricciones |

**FK:** `fk_pprograma_project` → `projects(id)` ON DELETE RESTRICT
**UK:** `uk_pprograma_project_legacy` (project_id, legacy_id)
**Idx:** `idx_pprograma_consecutivo` (project_id, consecutivo), `idx_pprograma_project_consecutivo` (project_id, consecutivo)

---

### `project_programa_consolidado`
**Origen:** `{prefix}_programa_consolidado`
**Descripción:** Programa consolidado por semana. Backup semanal del estado del programa general.

| Columna | Tipo | Nulo | Default | PK/FK/Idx | Descripción |
|---------|------|------|---------|-----------|-------------|
| id | BIGINT UNSIGNED | NO | — | PK | ID auto-increment |
| project_id | INT UNSIGNED | NO | — | FK | Proyecto asociado |
| legacy_id | INT | SI | NULL | — | ID original en tabla legacy |
| consecutivo | INT | NO | — | — | Consecutivo interno |
| semana | INT | NO | — | UK, Idx | Semana del consolidado |
| consecutivo_en_programa | INT | NO | — | UK | Consecutivo en programa general |
| id_actividad | VARCHAR(500) | SI | NULL | — | ID de actividad |
| actividad | VARCHAR(500) | SI | NULL | — | Nombre de actividad |
| titulo | INT | SI | NULL | — | Título/WBS |
| fecha_inicio | DATE | SI | NULL | — | Fecha inicio |
| fecha_fin | DATE | SI | NULL | — | Fecha fin |
| ruta_critica | TINYINT(1) | SI | NULL | — | 1=actividad en ruta crítica |
| ejecutado | DECIMAL(10,2) | SI | 0.00 | — | % ejecutado |
| estado | VARCHAR(100) | SI | NULL | — | Estado |
| semanas_inicio | INT | SI | 0 | — | Semanas desde inicio |
| estado_restricciones | DECIMAL(10,2) | SI | 0.00 | — | % restricciones |
| dy_e | VARCHAR(9) | SI | '0' | — | Diseño y Estudios |
| materiales | VARCHAR(9) | SI | '0' | — | Materiales |
| mde_o | VARCHAR(9) | SI | '0' | — | Mano de Obra |
| equipos | VARCHAR(9) | SI | '0' | — | Equipos |
| predecesora | VARCHAR(9) | SI | '0' | — | Predecesora |
| pdto_cons | VARCHAR(9) | SI | '0' | — | Producto de consumo |
| modelo | VARCHAR(9) | SI | '0' | — | Modelo |
| sub_contratista | VARCHAR(100) | SI | NULL | — | Subcontratista |
| responsable_aia | VARCHAR(100) | SI | NULL | — | Responsable AIA |
| observaciones | MEDIUMTEXT | SI | NULL | — | Observaciones |
| ult_act_est | DATE | SI | NULL | — | Última actualización estado |
| ult_act_restr | DATE | SI | NULL | — | Última actualización restricciones |
| activa | TINYINT(1) | SI | 0 | — | 1=actividad activa |
| ejecutado_siguiente_semana | DECIMAL(10,2) | SI | NULL | — | Ejecutado semana siguiente |
| codigo_actividad | VARCHAR(11) | SI | NULL | — | Código de actividad |
| medir_productividad | TINYINT(1) | SI | 0 | — | 1=mide productividad |
| cantidad_ppto | INT | SI | NULL | — | Cantidad presupuestada |
| unidad | VARCHAR(20) | SI | NULL | — | Unidad de medida |
| programa_anterior_asociar | VARCHAR(500) | SI | NULL | — | Programa anterior para asociar |
| alerta_crisis | TINYINT(1) | SI | 0 | — | 1=alerta de crisis activa |
| reprogramaciones_acumuladas | INT | SI | 0 | — | Número de reprogramaciones |
| dias_reprogramacion_acumulada | INT | SI | 0 | — | Días acumulados de reprogramación |

**FK:** `fk_ppconsolidado_project` → `projects(id)` ON DELETE RESTRICT
**UK:** `uk_ppconsolidado_project_semana_consecutivo` (project_id, semana, consecutivo_en_programa)
**Idx:** `idx_ppconsolidado_project_semana` (project_id, semana), `idx_ppconsolidado_semana` (project_id, semana)

---

### `project_programacion_semanal`
**Origen:** `{prefix}_programacion_semanal`
**Descripción:** Programación semanal / compromisos. Corazón del Last Planner System: compromisos semanales con PAC/CNC.

| Columna | Tipo | Nulo | Default | PK/FK/Idx | Descripción |
|---------|------|------|---------|-----------|-------------|
| id | BIGINT UNSIGNED | NO | — | PK | ID auto-increment |
| project_id | INT UNSIGNED | NO | — | FK | Proyecto asociado |
| legacy_id | INT | SI | NULL | — | ID original en tabla legacy |
| consecutivo | INT | NO | — | — | Consecutivo interno |
| semana | INT | SI | NULL | UK | Semana de la programación |
| consecutivo_en_programa | INT | NO | — | UK | Consecutivo en programa general |
| id_actividad | VARCHAR(500) | SI | NULL | — | ID de actividad |
| actividad | VARCHAR(500) | SI | NULL | — | Nombre de actividad |
| descripcion | MEDIUMTEXT | SI | NULL | — | Descripción de la actividad |
| ubicacion | MEDIUMTEXT | SI | NULL | — | Ubicación en obra |
| fecha_inicio | DATE | SI | NULL | — | Fecha de inicio |
| fecha_fin | DATE | SI | NULL | — | Fecha de fin |
| sub_contratista | VARCHAR(200) | SI | NULL | — | Subcontratista responsable |
| responsable_aia | VARCHAR(200) | SI | NULL | — | Residente/Director AIA responsable |
| empresa | VARCHAR(200) | SI | 'AIA' | — | Empresa ejecutora |
| ejecutado | DECIMAL(10,2) | SI | NULL | — | % ejecutado |
| medir_productividad | TINYINT(1) | SI | 0 | — | 1=mide productividad |
| unidad | VARCHAR(10) | SI | NULL | — | Unidad de medida |
| cantidad_ppto | INT | SI | NULL | — | Cantidad presupuestada |
| cantidad_sugerida | DECIMAL(10,2) | SI | NULL | — | Cantidad sugerida por el sistema |
| compromiso | DECIMAL(10,2) | SI | NULL | — | Cantidad comprometida |
| ejecutado_real | DECIMAL(10,2) | SI | NULL | — | Ejecutado real (para PAC) |
| p_completado | DECIMAL(10,2) | SI | NULL | — | % completado |
| pac | TINYINT(1) | SI | NULL | — | PAC: 1=completado, 0=no completado |
| critica | TINYINT(1) | SI | NULL | — | 1=actividad crítica |
| atrasada | TINYINT(1) | SI | NULL | — | 1=actividad atrasada |
| activa | VARCHAR(3) | SI | NULL | — | Activa (S/N) |
| reprogramada_por_usuario | TINYINT(1) | SI | 0 | — | 1=reprogramada manualmente |
| prog_sin_restricciones_100 | TINYINT(1) | SI | NULL | — | Programable al 100% sin restricciones |
| categoria_cnp | VARCHAR(100) | SI | NULL | — | Categoría de CNP |
| cnp | VARCHAR(100) | SI | NULL | — | Causa de No Programación |
| observaciones_cnp | MEDIUMTEXT | SI | NULL | — | Observaciones CNP |
| categoria_cnc | VARCHAR(100) | SI | NULL | — | Categoría de CNC |
| cnc | VARCHAR(100) | SI | NULL | — | Causa de No Cumplimiento |
| observaciones_cnc | MEDIUMTEXT | SI | NULL | — | Observaciones CNC |
| rendimientos | VARCHAR(500) | SI | NULL | — | Datos de rendimiento |
| codigo_actividad | VARCHAR(11) | SI | NULL | — | Código de actividad |
| alerta_crisis | TINYINT(1) | SI | 0 | — | 1=alerta de crisis |
| reprogramaciones_semanales | INT | SI | 0 | — | Número de reprogramaciones |

**FK:** `fk_ppsemanal_project` → `projects(id)` ON DELETE RESTRICT
**UK:** `uk_ppsemanal_project_semana_consecutivo` (project_id, semana, consecutivo_en_programa)
**Idx:** `idx_ppsemanal_semana` (project_id, semana)

---

### `project_pdc`
**Origen:** `{prefix}_pdc`
**Descripción:** Plan de Desarrollo de Contratos (PDC). Gestión de contratos desde elaboración de pliegos hasta legalización.

| Columna | Tipo | Nulo | Default | PK/FK/Idx | Descripción |
|---------|------|------|---------|-----------|-------------|
| id | BIGINT UNSIGNED | NO | — | PK | ID auto-increment |
| project_id | INT UNSIGNED | NO | — | FK | Proyecto asociado |
| legacy_id | INT | SI | NULL | — | ID original en tabla legacy |
| consecutivo | INT | NO | — | — | Consecutivo interno |
| semana | INT | NO | — | UK, Idx | Semana de planificación |
| titulo | INT | NO | — | UK | Título/WBS del paquete |
| tipo_paquete | VARCHAR(200) | NO | — | — | Tipo de paquete de contratación |
| paquete_contratacion | VARCHAR(200) | SI | NULL | UK | Nombre del paquete |
| contratos | VARCHAR(200) | SI | NULL | — | Número de contratos |
| numero_subcontratos | INT | SI | 1 | — | Número de subcontratos |
| subcontrato_paquete | INT | SI | 1 | — | Subcontrato dentro del paquete |
| estado | VARCHAR(200) | SI | NULL | — | Estado del proceso |
| fecha_elaboracion_pliegos | DATE | SI | NULL | — | Fecha programada elaboración pliegos |
| dias_elaboracion_pliegos | INT | SI | NULL | — | Días estimados |
| fecha_real_elaboracion_pliegos | DATE | SI | NULL | — | Fecha real |
| fecha_entrega_pliegos | DATE | SI | NULL | — | Fecha programada entrega pliegos |
| dias_entrega_pliegos | INT | SI | NULL | — | Días estimados |
| fecha_real_entrega_pliegos | DATE | SI | NULL | — | Fecha real |
| fecha_recibo_propuestas | DATE | SI | NULL | — | Fecha programada recibo propuestas |
| dias_recibo_propuestas | INT | SI | NULL | — | Días estimados |
| fecha_real_recibo_propuestas | DATE | SI | NULL | — | Fecha real |
| fecha_cuadros_comparativos | DATE | SI | NULL | — | Fecha programada cuadros comparativos |
| dias_cuadros_comparativos | INT | SI | NULL | — | Días estimados |
| fecha_real_cuadros_comparativos | DATE | SI | NULL | — | Fecha real |
| fecha_legalizacion_contrato | DATE | SI | NULL | — | Fecha programada legalización |
| dias_legalizacion_contrato | INT | SI | NULL | — | Días estimados |
| fecha_real_legalizacion_contrato | DATE | SI | NULL | — | Fecha real |
| fecha_fabricacion | DATE | SI | NULL | — | Fecha programada fabricación |
| dias_fabricacion | INT | SI | NULL | — | Días estimados |
| fecha_real_fabricacion | DATE | SI | NULL | — | Fecha real |
| fecha_insumos_obra | DATE | SI | NULL | — | Fecha programada insumos a obra |
| dias_insumos_obra | INT | SI | NULL | — | Días estimados |
| fecha_real_insumos_obra | DATE | SI | NULL | — | Fecha real |
| fecha_inicio | DATE | SI | NULL | — | Fecha de inicio de ejecución |
| fecha_inicio_proyectada | DATE | SI | NULL | — | Fecha de inicio proyectada |
| fecha_real_inicio | DATE | SI | NULL | — | Fecha real de inicio |
| id_proveedor_adjudicado | INT | SI | NULL | — | ID del proveedor adjudicado |
| numero_contrato | VARCHAR(50) | SI | NULL | — | Número de contrato |
| aplica_polizas | TINYINT(1) | SI | 1 | — | 1=aplica pólizas |
| fecha_vencimiento_polizas | DATE | SI | NULL | — | Vencimiento de pólizas |
| valor_presupuesto | DECIMAL(14,2) | SI | NULL | — | Valor presupuestado |
| valor_primera_negociacion | DECIMAL(14,2) | SI | NULL | — | Valor primera negociación |
| valor_adjudicado | DECIMAL(14,2) | SI | NULL | — | Valor adjudicado |
| valor_anticipo | DECIMAL(14,2) | SI | NULL | — | Valor del anticipo |
| valor_reclamado | DECIMAL(14,2) | SI | NULL | — | Valor reclamado |
| valor_devoluciones | DECIMAL(14,2) | SI | NULL | — | Valor de devoluciones |
| observaciones_contrato | MEDIUMTEXT | SI | NULL | — | Observaciones del contrato |

**FK:** `fk_ppdc_project` → `projects(id)` ON DELETE RESTRICT
**UK:** `uk_ppdc_project_semana_titulo_paquete` (project_id, semana, titulo, paquete_contratacion)
**Idx:** `idx_ppdc_project_semana` (project_id, semana), `idx_ppdc_semana` (project_id, semana)

---

### `project_cic`
**Origen:** `{prefix}_cic`
**Descripción:** Control Integral de Contratos (CIC). Evaluación de desempeño de subcontratistas: PAC, calidad, GSA, SST, ADM.

| Columna | Tipo | Nulo | Default | PK/FK/Idx | Descripción |
|---------|------|------|---------|-----------|-------------|
| id | BIGINT UNSIGNED | NO | — | PK | ID auto-increment |
| project_id | INT UNSIGNED | NO | — | FK | Proyecto asociado |
| legacy_id | INT | SI | NULL | — | ID original en tabla legacy |
| semana | INT | SI | NULL | UK | Semana de evaluación |
| subcontratista | VARCHAR(200) | SI | NULL | UK, Idx | Subcontratista evaluado |
| correo_contacto | VARCHAR(200) | SI | NULL | — | Correo de contacto |
| nit | VARCHAR(10) | SI | NULL | — | NIT |
| alcance | VARCHAR(200) | SI | NULL | — | Alcance del contrato |
| tipo_proveedor | VARCHAR(200) | SI | NULL | — | Tipo de proveedor |
| pac | VARCHAR(11) | SI | 'NA' | — | PAC (NA/POR/BIEN/EXC) |
| pac_acum | VARCHAR(11) | SI | 'NA' | — | PAC acumulado |
| p_completado | VARCHAR(11) | SI | 'NA' | — | % Completado |
| p_completado_acum | VARCHAR(11) | SI | 'NA' | — | % Completado acumulado |
| calidad | VARCHAR(11) | SI | 'NR' | — | Evaluación calidad |
| calidad_acum | VARCHAR(11) | SI | 'NR' | — | Calidad acumulada |
| gsa | VARCHAR(11) | SI | 'NR' | — | Evaluación GSA |
| gsa_acum | VARCHAR(11) | SI | 'NR' | — | GSA acumulada |
| sst | VARCHAR(11) | SI | 'NR' | — | Evaluación SST |
| sst_acum | VARCHAR(11) | SI | 'NR' | — | SST acumulada |
| adm | VARCHAR(11) | SI | 'NR' | — | Evaluación ADM |
| adm_acum | VARCHAR(11) | SI | 'NR' | — | ADM acumulada |
| cal_integral | DECIMAL(5,2) | SI | NULL | — | Calificación integral |
| cal_integral_acum | DECIMAL(5,2) | SI | NULL | — | Calificación integral acumulada |
| observaciones | MEDIUMTEXT | SI | NULL | — | Observaciones |
| mdo_calidad | VARCHAR(5) | SI | 'NR' | — | MDO calidad |
| mdo_calidad_acum | VARCHAR(5) | SI | 'NR' | — | MDO calidad acum |
| mdo_gsa | VARCHAR(5) | SI | 'NR' | — | MDO GSA |
| mdo_gsa_acum | VARCHAR(5) | SI | 'NR' | — | MDO GSA acum |
| mdo_sst | VARCHAR(5) | SI | 'NR' | — | MDO SST |
| mdo_sst_acum | VARCHAR(5) | SI | 'NR' | — | MDO SST acum |
| mdo_adm | VARCHAR(5) | SI | 'NR' | — | MDO ADM |
| mdo_adm_acum | VARCHAR(5) | SI | 'NR' | — | MDO ADM acum |
| mdo_cal_integral | VARCHAR(5) | SI | 'NR' | — | MDO cal integral |
| mdo_cal_integral_acum | VARCHAR(5) | SI | 'NR' | — | MDO cal integral acum |
| mdo_pac | VARCHAR(5) | SI | 'NR' | — | MDO PAC |
| mdo_pac_acum | VARCHAR(5) | SI | 'NR' | — | MDO PAC acum |
| mdo_p_completado | VARCHAR(5) | SI | 'NR' | — | MDO % completado |
| mdo_p_completado_acum | VARCHAR(5) | SI | 'NR' | — | MDO % completado acum |
| si_calidad | VARCHAR(5) | SI | 'NR' | — | SI calidad |
| si_calidad_acum | VARCHAR(5) | SI | 'NR' | — | SI calidad acum |
| si_gsa | VARCHAR(5) | SI | 'NR' | — | SI GSA |
| si_gsa_acum | VARCHAR(5) | SI | 'NR' | — | SI GSA acum |
| si_sst | VARCHAR(5) | SI | 'NR' | — | SI SST |
| si_sst_acum | VARCHAR(5) | SI | 'NR' | — | SI SST acum |
| si_adm | VARCHAR(5) | SI | 'NR' | — | SI ADM |
| si_adm_acum | VARCHAR(5) | SI | 'NR' | — | SI ADM acum |
| si_cal_integral | VARCHAR(5) | SI | 'NR' | — | SI cal integral |
| si_cal_integral_acum | VARCHAR(5) | SI | 'NR' | — | SI cal integral acum |
| si_pac | VARCHAR(5) | SI | 'NR' | — | SI PAC |
| si_pac_acum | VARCHAR(5) | SI | 'NR' | — | SI PAC acum |
| si_p_completado | VARCHAR(5) | SI | 'NR' | — | SI % completado |
| si_p_completado_acum | VARCHAR(5) | SI | 'NR' | — | SI % completado acum |
| mdo_rendimiento | VARCHAR(5) | SI | 'NR' | — | MDO rendimiento |
| mdo_rendimiento_acum | VARCHAR(5) | SI | 'NR' | — | MDO rendimiento acum |
| mdo_si | VARCHAR(5) | SI | 'NR' | — | MDO SI |
| mdo_si_acum | VARCHAR(5) | SI | 'NR' | — | MDO SI acum |
| si_rendimiento | VARCHAR(5) | SI | 'NR' | — | SI rendimiento |
| si_rendimiento_acum | VARCHAR(5) | SI | 'NR' | — | SI rendimiento acum |

**FK:** `fk_pcic_project` → `projects(id)` ON DELETE RESTRICT
**UK:** `uk_pcic_project_semana_subcontratista` (project_id, semana, subcontratista)
**Idx:** `idx_pcic_project_semana` (project_id, semana), `idx_pcic_semana` (project_id, semana), `idx_pcic_subcontratista` (project_id, subcontratista)

---

### `project_cambios`
**Origen:** `{prefix}_cambios`
**Descripción:** Gestión de cambios / órdenes de cambio en proyectos.

| Columna | Tipo | Nulo | Default | PK/FK/Idx | Descripción |
|---------|------|------|---------|-----------|-------------|
| id | BIGINT UNSIGNED | NO | — | PK | ID auto-increment |
| project_id | INT UNSIGNED | NO | — | FK | Proyecto asociado |
| legacy_id | INT | SI | NULL | — | ID original en tabla legacy |
| solicitante_cambio | INT | SI | NULL | — | ID del solicitante |
| detalle_solicitante_otro | LONGTEXT | SI | NULL | — | Detalle si el solicitante es otro |
| fecha_solicitud | DATE | SI | NULL | Idx | Fecha de solicitud |
| prioridad | INT | SI | NULL | — | Prioridad (1-5) |
| tipo_cambio | LONGTEXT | SI | NULL | — | Tipo de cambio |
| responsable_solucion | INT | SI | NULL | — | ID del responsable de solución |
| detalle_responsable_solucion | LONGTEXT | SI | NULL | — | Detalle del responsable |
| justificacion | LONGTEXT | SI | NULL | — | Justificación del cambio |
| descripcion | LONGTEXT | SI | NULL | — | Descripción del cambio |
| incidencia_alcance | LONGTEXT | SI | NULL | — | Incidencia en alcance |
| tiempo_cronograma | DECIMAL(10,2) | SI | NULL | — | Tiempo de cronograma |
| tiempo_cronograma_afectado | DECIMAL(10,2) | SI | NULL | — | Tiempo afectado |
| incidencia_cronograma | LONGTEXT | SI | NULL | — | Incidencia en cronograma |
| valor_presupuesto | DECIMAL(14,2) | SI | NULL | — | Valor del presupuesto |
| costo_directo | DECIMAL(14,2) | SI | NULL | — | Costo directo |
| costo_directo_aiu | DECIMAL(14,2) | SI | NULL | — | Costo directo + AIU |
| costo_directo_aiu_iva | DECIMAL(14,2) | SI | NULL | — | Costo directo + AIU + IVA |
| valor_aprobado | DECIMAL(14,2) | SI | NULL | — | Valor aprobado |
| incidencia_presupuesto | LONGTEXT | SI | NULL | — | Incidencia en presupuesto |
| incidencia_calidad | LONGTEXT | SI | NULL | — | Incidencia en calidad |
| incidencia_riesgo | LONGTEXT | SI | NULL | — | Incidencia en riesgo |
| incidencia_recurso | LONGTEXT | SI | NULL | — | Incidencia en recurso |
| fecha_tentativa_definicion | DATE | SI | NULL | — | Fecha tentativa de definición |
| fecha_entrega_interventoria | DATE | SI | NULL | — | Fecha entrega a interventoría |
| observaciones | LONGTEXT | SI | NULL | — | Observaciones |
| fecha_definicion | DATE | SI | NULL | — | Fecha de definición |
| aprobacion | TINYINT(1) | SI | NULL | — | 1=aprobado, 0=rechazado |
| soportes | LONGTEXT | SI | NULL | — | Documentos de soporte |

**FK:** `fk_pcambios_project` → `projects(id)` ON DELETE RESTRICT
**Idx:** `idx_pcambios_project_fecha_solicitud` (project_id, fecha_solicitud), `idx_pcambios_fecha` (project_id, fecha_solicitud)

---

### `project_actividades`
**Origen:** `{prefix}_actividades`
**Descripción:** Actividades detalladas por proyecto con desglose de subcontratos por paquete de información, supervisión, mano de obra y obra civil.

| Columna | Tipo | Nulo | Default | PK/FK/Idx | Descripción |
|---------|------|------|---------|-----------|-------------|
| id | BIGINT UNSIGNED | NO | — | PK | ID auto-increment |
| project_id | INT UNSIGNED | NO | — | FK | Proyecto asociado |
| legacy_id | INT | SI | NULL | — | ID original en tabla legacy |
| codigo | INT | NO | — | UK, Idx | Código de la actividad |
| actividad | VARCHAR(300) | NO | — | — | Nombre de la actividad |
| descripcion_actividad | MEDIUMTEXT | SI | NULL | — | Descripción detallada |
| actividad_inicio | VARCHAR(500) | SI | NULL | UK | Actividad de inicio asociada |
| nombre_actividad_inicio | VARCHAR(500) | SI | NULL | — | Nombre de la actividad de inicio |
| fecha_inicio | DATE | SI | NULL | UK | Fecha de inicio |
| tipo_contrato | VARCHAR(10) | SI | NULL | — | Tipo de contrato |
| semana_actualizacion | INT | SI | NULL | — | Semana de última actualización |
| SI1–SI5 | VARCHAR(200) | SI | NULL | — | Subcontrato de Información 1-5 |
| paquete_si1–paquete_si5 | VARCHAR(200) | SI | NULL | — | Paquete de SI 1-5 |
| S1–S5 | VARCHAR(200) | SI | NULL | — | Subcontrato de Supervisión 1-5 |
| paquete_s1–paquete_s5 | VARCHAR(200) | SI | NULL | — | Paquete de S 1-5 |
| MO1–MO5 | VARCHAR(200) | SI | NULL | — | Mano de Obra 1-5 |
| paquete_mo1–paquete_mo5 | VARCHAR(200) | SI | NULL | — | Paquete de MO 1-5 |
| ... | ... | ... | ... | ... | ... |

**FK:** `fk_pactividades_project` → `projects(id)` ON DELETE RESTRICT
**UK:** `uk_pactividades_project_codigo_actividad_inicio` (project_id, codigo, actividad_inicio, fecha_inicio)
**Idx:** `idx_pactividades_project_codigo` (project_id, codigo), `idx_pactividades_codigo` (project_id, codigo)
> Nota: columnas SI1-SI5, S1-S5, MO1-MO5, OC1-OC5 y sus paquetes asociados siguen el mismo patrón. Ver DDL completo para la lista exhaustiva (80+ columnas de subcontratos).

---

### `project_lps_drawer_comentarios`
**Origen:** `{prefix}_lps_drawer_comentarios`
**Descripción:** Comentarios del Drawer LPS (bitácora de obra digital con hilos de conversación).

| Columna | Tipo | Nulo | Default | PK/FK/Idx | Descripción |
|---------|------|------|---------|-----------|-------------|
| id | BIGINT UNSIGNED | NO | — | PK | ID auto-increment |
| project_id | INT UNSIGNED | NO | — | FK | Proyecto asociado |
| legacy_id | INT | SI | NULL | UK | ID original en tabla legacy |
| consecutivo_en_programa | INT | NO | — | — | Actividad asociada |
| semana | INT | NO | — | Idx | Semana del comentario |
| usuario_id | INT | NO | — | — | ID del autor |
| comentario | MEDIUMTEXT | NO | — | — | Contenido del comentario |
| escalamiento_id | INT | SI | NULL | — | ID de escalamiento asociado |
| parent_id | INT | SI | NULL | — | Comentario padre (hilos) |
| menciones | JSON | SI | NULL | — | Menciones a usuarios |
| created_at | TIMESTAMP | SI | CURRENT_TIMESTAMP | — | Fecha de creación |

**FK:** `fk_plps_drawer_comentarios_project` → `projects(id)` ON DELETE RESTRICT
**UK:** `uk_plps_drawer_comentarios_project_legacy` (project_id, legacy_id)
**Idx:** `idx_plps_drawer_comentarios_project_semana` (project_id, semana)

---

### `project_lps_escalamientos`
**Origen:** `{prefix}_lps_escalamientos`
**Descripción:** Escalamientos de crisis del Drawer LPS. Alerta temprana cuando una actividad crítica tiene retraso.

| Columna | Tipo | Nulo | Default | PK/FK/Idx | Descripción |
|---------|------|------|---------|-----------|-------------|
| id | BIGINT UNSIGNED | NO | — | PK | ID auto-increment |
| project_id | INT UNSIGNED | NO | — | FK | Proyecto asociado |
| legacy_id | INT | SI | NULL | UK | ID original en tabla legacy |
| semana | INT | NO | — | — | Semana del escalamiento |
| consecutivo_en_programa | INT | NO | — | — | Actividad asociada |
| modulo | ENUM('PG','PI','PS') | NO | — | — | Nivel de planificación |
| trigger_origen | VARCHAR(50) | NO | — | — | Código disparador |
| nivel_actual | TINYINT | SI | 1 | — | Nivel de escalamiento actual |
| estado | ENUM('Activo','Mitigado','Cerrado') | SI | 'Activo' | — | Estado del escalamiento |
| fecha_detonacion | TIMESTAMP | SI | CURRENT_TIMESTAMP | — | Fecha de detonación |
| fecha_ultimo_escalamiento | TIMESTAMP | SI | NULL | — | Último escalamiento |
| fecha_cierre | TIMESTAMP | SI | NULL | — | Fecha de cierre |
| usuario_cierre_id | INT | SI | NULL | — | Usuario que cerró |
| justificacion_cierre | MEDIUMTEXT | SI | NULL | — | Justificación de cierre |

**FK:** `fk_plps_escalamientos_project` → `projects(id)` ON DELETE RESTRICT
**UK:** `uk_plps_escalamientos_project_legacy` (project_id, legacy_id)

---

### `project_pi_shared_constraints`
**Origen:** `{prefix}_pi_shared_constraints`
**Descripción:** Restricciones compartidas del Plan Intermedio (Lookahead). Restricciones que aplican a múltiples actividades.

| Columna | Tipo | Nulo | Default | PK/FK/Idx | Descripción |
|---------|------|------|---------|-----------|-------------|
| id | BIGINT UNSIGNED | NO | — | PK | ID auto-increment |
| project_id | INT UNSIGNED | NO | — | FK | Proyecto asociado |
| legacy_id | BIGINT UNSIGNED | SI | NULL | UK | ID original en tabla legacy |
| semana | INT | NO | — | Idx | Semana |
| restriccion | VARCHAR(40) | NO | — | — | Nombre de la restricción |
| valor_objetivo | VARCHAR(20) | NO | — | — | Valor objetivo |
| nota | TEXT | SI | NULL | — | Nota adicional |
| creado_por | VARCHAR(120) | SI | NULL | — | Usuario que creó |
| creado_en | DATETIME | NO | CURRENT_TIMESTAMP | — | Fecha de creación |
| actualizado_en | DATETIME | NO | CURRENT_TIMESTAMP ON UPDATE | — | Fecha de actualización |

**FK:** `fk_pi_shared_constraints_project` → `projects(id)` ON DELETE RESTRICT
**UK:** `uk_pi_shared_constraints_project_legacy` (project_id, legacy_id)
**Idx:** `idx_pi_shared_constraints_project_semana` (project_id, semana)

---

### `project_pi_shared_constraint_links`
**Origen:** `{prefix}_pi_shared_constraint_links`
**Descripción:** Vínculos entre restricciones compartidas y las actividades del programa que afectan.

| Columna | Tipo | Nulo | Default | PK/FK/Idx | Descripción |
|---------|------|------|---------|-----------|-------------|
| id | BIGINT UNSIGNED | NO | — | PK | ID auto-increment |
| project_id | INT UNSIGNED | NO | — | FK | Proyecto asociado |
| legacy_id | BIGINT UNSIGNED | SI | NULL | UK | ID original en tabla legacy |
| shared_constraint_id | BIGINT UNSIGNED | NO | — | — | Restricción compartida asociada |
| semana | INT | NO | — | Idx | Semana |
| consecutivo_en_programa | BIGINT | NO | — | — | Actividad afectada |
| valor_aplicado | VARCHAR(20) | NO | — | — | Valor aplicado a esta actividad |
| override_local | TINYINT(1) | SI | 0 | — | 1=sobreescribe el valor objetivo |
| aplicado_en | DATETIME | SI | CURRENT_TIMESTAMP | — | Fecha de aplicación |

**FK:** `fk_pi_shared_constraint_links_project` → `projects(id)` ON DELETE RESTRICT
**UK:** `uk_pi_shared_constraint_links_project_legacy` (project_id, legacy_id)
**Idx:** `idx_pi_shared_constraint_links_project_semana` (project_id, semana)

---

### `project_auto_program_log`
**Origen:** `{prefix}_auto_program_log`
**Descripción:** Log del auto-programador. Registro de acciones automáticas de compromiso/descompromiso de actividades.

| Columna | Tipo | Nulo | Default | PK/FK/Idx | Descripción |
|---------|------|------|---------|-----------|-------------|
| id | BIGINT UNSIGNED | NO | — | PK | ID auto-increment |
| project_id | INT UNSIGNED | NO | — | FK | Proyecto asociado |
| legacy_id | INT | SI | NULL | UK | ID original en tabla legacy |
| semana | INT | NO | — | — | Semana |
| consecutivo | INT | NO | — | — | Actividad asociada |
| accion | ENUM('comprometer','descomprometer','insert_cnp') | NO | — | — | Acción realizada |
| detalle | TEXT | SI | NULL | — | Detalle de la acción |
| categoria_cnp | VARCHAR(100) | SI | NULL | — | Categoría CNP si aplica |
| cnp | VARCHAR(100) | SI | NULL | — | CNP si aplica |
| creado_en | TIMESTAMP | SI | CURRENT_TIMESTAMP | — | Fecha de creación |

**FK:** `fk_auto_program_log_project` → `projects(id)` ON DELETE RESTRICT
**UK:** `uk_auto_program_log_project_legacy` (project_id, legacy_id)

---

## Convenciones de Tipos

| Tipo de dato | Uso |
|-------------|-----|
| `INT UNSIGNED` | IDs numéricos, contadores, flags |
| `BIGINT UNSIGNED` | Tablas con alto volumen (>2^31 registros) |
| `DECIMAL(14,2)` | Montos monetarios (presupuestos, costos) |
| `DECIMAL(10,2)` | Porcentajes, cantidades operativas |
| `DECIMAL(5,2)` | Calificaciones (CIC) |
| `TINYINT(1)` | Flags booleanos (0/1) |
| `VARCHAR(*)*` | Texto corto y mediano |
| `MEDIUMTEXT` | Observaciones, descripciones largas |
| `LONGTEXT` | Documentos largos (cambios) |
| `JSON` | Datos semiestructurados (menciones, contextos) |
| `DATE` | Fechas sin hora |
| `DATETIME` | Fechas con hora |
| `TIMESTAMP` | Auditoría (created_at, updated_at) |
| `ENUM` | Valores fijos con lista cerrada |

## Resumen de Constraints

| Tipo | Cantidad |
|------|----------|
| Primary Keys (PK) | 19 |
| Foreign Keys (FK) | 19 (todas ON DELETE RESTRICT) |
| Unique Keys (UK) | 14 |
| Indices secundarios | 10 |

## Indices por Tabla

| Tabla | Indices |
|-------|---------|
| audit_actions | 3 (project_id, usuario, fecha) |
| project_semanas_activas | 1 (project_id, semana) |
| project_subcontratistas | 1 (project_id, subcontratista) |
| project_programa | 2 (project_id, consecutivo) |
| project_programa_consolidado | 2 (project_id, semana; project_id, semana, consecutivo_en_programa) |
| project_programacion_semanal | 1 (project_id, semana) |
| project_pdc | 2 (project_id, semana) |
| project_cic | 3 (project_id, semana; project_id, subcontratista) |
| project_cambios | 2 (project_id, fecha_solicitud) |
| project_actividades | 2 (project_id, codigo) |
| project_lps_drawer_comentarios | 1 (project_id, semana) |
| project_pi_shared_constraints | 1 (project_id, semana) |
| project_pi_shared_constraint_links | 1 (project_id, semana) |
