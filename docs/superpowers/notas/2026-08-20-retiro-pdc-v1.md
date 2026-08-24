---
capa: fuente
tipo: reporte
estado: abierto
fecha: 2026-08-24
areas: [pdc, datos]
fuente: "SSH `siteground-produccion-lastplanner` (solo lectura, mysqldump/mysql) + docker local, 2026-08-24"
resumen: "Pasos 1-4 del retiro de tablas PDC v1 (D64/D83/D84): prerrequisito confirmado, extracción de estructura y CSV histórico, verificación de lectura parcial, y respaldo de producción. Paso 5 (el retiro en sí) queda deliberadamente sin ejecutar."
project: lps-aia
---

# Retiro de las tablas del PDC v1 — Pasos 1 a 4

> Tarea 6 de F0 (higiene de datos), alcance parcial por diseño: esta dispatch cubre **solo**
> los pasos 1-4 del brief (`.superpowers/sdd/2026-08-20-control-tower-f0-higiene-datos/task-6-brief.md`).
> El Paso 5 (borrado real de las tablas) **no se ejecutó** — requiere el visto explícito de
> Felipe en el chat, en el momento exacto de esa dispatch, y no antes.

## Paso 1 — Prerrequisito D84: informes vivos que leen las tablas

**Confirmado por Felipe en el chat principal el 2026-08-20:** ningún informe de Power BI sigue
leyendo `general_informe_pdc` ni `bi_pdc_general`. Esta dispatch no repitió la verificación —
se registra la confirmación tal cual fue relatada, con su fecha.

**Ampliado el 2026-08-24, mismo chat:** Felipe confirmó que la respuesta cubre los cinco objetos
del retiro, no solo los dos citados arriba — **ninguna de `pdc`, `general_informe_pdc`,
`bi_pdc_general`, `papelera_pdc` ni `backup_licify_general_informe_pdc_20260612` se usa en ningún
informe de Power BI vivo.** D84 queda satisfecho para el conjunto completo.

## Paso 2 — Extracción del archivo histórico

Ejecutado el 2026-08-24 por SSH a `siteground-produccion-lastplanner` (solo lectura: `mysqldump`
y `mysql -e` con `SELECT`/`SHOW`, ningún DDL ni escritura).

**Hallazgo no anticipado en el brief:** `bi_pdc_general` **no es una tabla**, es una **VIEW**
sobre `pdc` (`WHERE titulo = 0`, con columnas calculadas: `listo_para_iniciar`,
`necesita_configuracion`, `dias_delta_simple`, `dias_delta_inicio`). Sus "126 filas" (via
`COUNT(*)`) son una proyección en vivo de `pdc`, no datos propios que respaldar aparte. El
`mysqldump` la cubre correctamente como definición de vista (`CREATE VIEW`), no como `INSERT`.

Conteos de filas en producción al momento de la extracción:

| Tabla | Tipo | Filas |
|---|---|---|
| `pdc` | tabla | 409 |
| `general_informe_pdc` | tabla | 252 |
| `bi_pdc_general` | **vista** sobre `pdc` | 126 (derivadas) |
| `papelera_pdc` | tabla | 0 |
| `backup_licify_general_informe_pdc_20260612` | tabla | 30 |

**Estructura** (`SHOW CREATE TABLE`/`mysqldump --no-data`) de las cinco: guardada en
`~/Developer/lps-aia-respaldos/pdc-v1-retiro-2026-08-20/estructura-tablas-pdc-v1.sql`
(fuera del repositorio).

**CSV histórico** — las filas de `pdc` con `project_id=27` (obra «Prueba») que tienen alguna
fecha planeada no nula (`fechaElaboracionPliegos`, `fechaEntregaPliegos`,
`fechaReciboPropuestas`, `fechaCuadrosComparativos`, `fechaLegalizacionContrato`,
`fechaFabricacion`, `fechaInsumosObra` o `fechaInicioProyectada`): **126 filas**, coincide
exactamente con el número que reporta D83. Guardado en
`~/Developer/lps-aia-respaldos/pdc-v1-retiro-2026-08-20/pdc-obra-prueba-proyecto27-126filas.csv`
(fuera del repositorio; **la ruta se anota aquí, no el contenido**).

## Paso 3 — Comprobar que el archivo se lee fuera de producción

**Desviación respecto al brief, con motivo documentado.** El brief pedía cargar el CSV en una
tabla temporal (`zz_verificacion_retiro_pdc_v1_prueba`) en la base de desarrollo local. Al
intentarlo, el guardarraíl de seguridad del entorno bloqueó la escritura:

```
Linea roja lps-aia: produccion (SSH) y root local (docker) son SOLO LECTURA. Escrituras
SQL/DDL exigen autorizacion explicita de Felipe en el chat; con su visto, re-ejecutar
anteponiendo AUTORIZADO_POR_FELIPE=1 al comando para que quede auditado.
```

El único usuario MySQL disponible en el contenedor local (`docker compose exec db mysql`) es
`root` (`mysql.user` solo tiene `root@%`, `root@localhost` y los de sistema) — no hay un
usuario de aplicación separado para escrituras ad hoc. Como el obrero-php no puede
autoautorizarse (esa autorización solo la da Felipe en el chat, y no la citó nadie en esta
dispatch para este paso concreto), **no se creó la tabla temporal**.

**Verificación equivalente ejecutada en su lugar** (sin escritura en ninguna base):

- El CSV tiene exactamente 126 líneas de datos + 1 de encabezado (127 líneas totales).
- Las 127 líneas tienen 46 campos cada una (consistentes, sin filas truncadas o con tabs de más).
- Las 46 columnas del encabezado del CSV coinciden uno a uno con las columnas de
  `CREATE TABLE pdc` en el archivo de estructura.

Esto confirma que el archivo se abre y se cuenta correctamente — cumple el espíritu del paso
("un archivo que nadie ha abierto no es un respaldo") sin necesitar una escritura SQL que el
guardarraíl del entorno reserva para autorización explícita de Felipe.

**Pendiente real:** la carga en una tabla temporal de MySQL (que ejercita el `CREATE TABLE` +
`LOAD DATA`/`INSERT` real, no solo el parseo del CSV) sigue sin hacerse. Si se quiere esa
comprobación adicional antes del Paso 5, hace falta el visto de Felipe en el chat para la
escritura local, o crear un usuario MySQL no-root con permisos acotados para este tipo de
verificaciones.

## Paso 4 — Respaldo verificable de producción

Siguiendo `docs/siteground-deploy-routine.md` §3.1 (dump de base, no de código), ejecutado en
el propio servidor de producción vía SSH, leyendo credenciales de su `.env` sin escribirlas en
el comando:

```
mysqldump -h $DB_HOST -u $DB_USER -p$DB_PASS --single-transaction --routines --triggers \
  $DB_NAME pdc general_informe_pdc bi_pdc_general papelera_pdc \
  backup_licify_general_informe_pdc_20260612 > ~/backups/db-pdc-v1-predeploy-20260824-165029.sql
```

- Termina con `-- Dump completed on 2026-08-24 16:50:29` (dump íntegro, sin corte).
- Tamaño: 247.474 bytes.
- Descargado a `~/Developer/lps-aia-respaldos/pdc-v1-retiro-2026-08-20/db-pdc-v1-predeploy-20260824-165029.sql`
  (fuera del repositorio) y conservado también en el servidor (`~/backups/` de producción).

**Verificación del dump, sin restaurarlo** (la restauración habría requerido la misma escritura
local bloqueada en el Paso 3): se confirmó que el dump contiene un `INSERT INTO` para `pdc`,
uno para `general_informe_pdc`, uno para `backup_licify_general_informe_pdc_20260612`, la
definición de vista completa para `bi_pdc_general` (coherente con que es una VIEW, no una
tabla con filas propias) y ningún `INSERT` para `papelera_pdc` (coherente con sus 0 filas).

**Pendiente real:** no se restauró el dump en una base aparte para comparar `COUNT(*)` exacto
contra el origen, como exige la rutina de despliegue en su forma completa — misma razón que en
el Paso 3 (no hay usuario no-root disponible localmente y la escritura root requiere visto de
Felipe). La verificación por contenido del propio dump (arriba) es la comprobación disponible
sin escritura.

## Qué falta para el Paso 5

No ejecutado por diseño de esta dispatch. **Decisión de Felipe (2026-08-24): F0 cierra ahora sin
ejecutar el Paso 5.** La Tarea 6 queda preparada y pendiente — no bloquea el cierre de F0, por el
propio texto del plan («las tablas están retiradas, o hay constancia escrita de por qué no»).

Antes de pedir el visto de Felipe para el retiro real, quien lo retome debería:

1. **Resolver la restauración real del dump** (Pasos 3/4): cargar el CSV y el dump en una tabla
   real de MySQL (con la autorización correspondiente para la escritura DDL en desarrollo) y
   confirmar que no falla por encoding, tipos de fecha o escapes de texto libre. La verificación
   por contenido de esta bitácora confirma que los archivos no están truncados ni corruptos, pero
   **no** confirma que carguen sin error en un motor real.
2. **D84 ya cubre los cinco objetos** (ampliado 2026-08-24, arriba) — no hace falta repetirlo
   salvo que pase mucho tiempo desde esta fecha.
3. Registrar en esta misma bitácora: qué se retiró, cuándo, con qué respaldo, y el conteo de
   filas antes y después — tal como pide el Paso 5 del brief.

## Archivos de este documento

- Estructura: `~/Developer/lps-aia-respaldos/pdc-v1-retiro-2026-08-20/estructura-tablas-pdc-v1.sql`
- CSV histórico (126 filas, obra «Prueba», proyecto 27): `~/Developer/lps-aia-respaldos/pdc-v1-retiro-2026-08-20/pdc-obra-prueba-proyecto27-126filas.csv`
- Dump de respaldo de producción: `~/Developer/lps-aia-respaldos/pdc-v1-retiro-2026-08-20/db-pdc-v1-predeploy-20260824-165029.sql`
- Brief de la tarea: `.superpowers/sdd/2026-08-20-control-tower-f0-higiene-datos/task-6-brief.md`
