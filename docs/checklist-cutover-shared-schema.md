---
capa: fuente
tipo: guia
estado: vigente
fecha: 2026-03-09
areas: [datos]
fuente: docs/checklist-cutover-shared-schema.md
resumen: Ejecutar el cambio de tablas por prefijo ({proyecto}) a tablas compartidas con projectid, sin tablas de reporteria persistida, con minimo riesgo operativo.
---

# Checklist Operativo de Cutover a Shared Schema

## Objetivo

Ejecutar el cambio de tablas por prefijo (`{proyecto}_*`) a tablas compartidas con `project_id`, sin tablas de reporteria persistida, con minimo riesgo operativo.

---

## 1) Pre-Cutover (T-7 a T-1)

- [ ] Confirmar que `docs/001_shared_schema.sql` fue aplicado en staging y produccion.
- [ ] Ejecutar migracion en staging con volumen real y validar conteos por tabla/proyecto.
- [ ] Validar que los endpoints criticos ya leen desde shared schema en entorno de prueba.
- [ ] Habilitar feature flag para lectura dual (si aplica).
- [ ] Habilitar logs de slow query y dashboard de latencia.
- [ ] Validar backup full + prueba de restore exitosa (RTO/RPO documentados).
- [ ] Definir ventana de mantenimiento y plan de comunicacion.
- [ ] Congelar despliegues no relacionados durante la ventana.

---

## 2) Gate Tecnico (Go/No-Go, T-0)

- [ ] Diferencia de conteos por tabla <= 0.5% y explicada.
- [ ] Sin errores de FK en shared schema (`SHOW ENGINE INNODB STATUS` / checks).
- [ ] Curva S y Curva S PDC on-demand con P95 <= 2.5s en staging.
- [ ] Smoke tests funcionales core aprobados.
- [ ] Equipo de guardia asignado (Backend, DBA, QA, DevOps).

Si cualquier item falla, **NO-GO**.

---

## 3) Ejecucion Cutover (Ventana)

### 3.1 Inicio de ventana

- [ ] Anunciar inicio de mantenimiento.
- [ ] Activar modo controlado de escrituras (o cola temporal) si aplica.
- [ ] Tomar backup incremental de ultima hora.

### 3.2 Datos

- [ ] Correr migracion final incremental (delta) por proyecto.
- [ ] Validar conteos delta (legacy vs shared).
- [ ] Validar integridad de llaves unicas y FKs.

### 3.3 Aplicacion

- [ ] Activar `shared_schema_enabled=true`.
- [ ] Verificar logs de error en los primeros 15 minutos.
- [ ] Ejecutar smoke tests en produccion:
  - [ ] Login + seleccion de proyecto
  - [ ] Programa general
  - [ ] Programacion semanal
  - [ ] PDC CRUD
  - [ ] CIC/CIP
  - [ ] Indicadores
  - [ ] Curva S
  - [ ] Curva S PDC

### 3.4 Cierre

- [ ] Confirmar estabilidad minima 30-60 min.
- [ ] Comunicar cierre de ventana.

---

## 4) Post-Cutover (T+1 a T+14)

- [ ] Monitorear P50/P95 de endpoints de reportes y operacion.
- [ ] Revisar slow queries e iterar indices.
- [ ] Monitorear errores de datos (duplicados, nulls inesperados).
- [ ] Ejecutar reconciliacion diaria de conteos por 7 dias.
- [ ] Marcar tablas legacy como read-only.

---

## 5) Decommission Legacy (por etapas)

### Semana 1

- [ ] Sin escrituras a tablas `{prefijo}_*`.

### Semana 2

- [ ] Sin lecturas en runtime a tablas `{prefijo}_*`.

### Semana 3

- [ ] Backup final de tablas legacy.
- [ ] Aprobacion formal de negocio/tecnica para DROP.
- [ ] Eliminacion controlada de tablas legacy.

---

## 6) Rollback (si aplica)

### Trigger de rollback

- [ ] Error funcional critico sin workaround en < 60 min.
- [ ] Degradacion > 40% de latencia sostenida.
- [ ] Inconsistencia critica de datos.

### Pasos

- [ ] `shared_schema_enabled=false`.
- [ ] Restaurar rutas legacy de lectura.
- [ ] Mantener evidencia de incidente y causa raiz.
- [ ] Definir fecha de nuevo intento con acciones correctivas.

---

## 7) Evidencias minimas a archivar

- [ ] SQL de migracion ejecutados y versionados.
- [ ] Reporte de reconciliacion antes/despues.
- [ ] Resultados de smoke tests.
- [ ] Capturas/metricas de performance.
- [ ] Acta de go-live y/o rollback.
