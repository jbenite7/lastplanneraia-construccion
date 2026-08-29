---
capa: guia
tipo: seguridad
estado: vigente
fecha: 2026-08-29
---

# Cuenta MySQL de runtime

La aplicación debe conectarse con una cuenta distinta de la administrativa. La cuenta de runtime
solo recibe `SELECT`, `INSERT`, `UPDATE` y `DELETE` sobre la base de la aplicación; no recibe DDL,
administración de usuarios, `GRANT OPTION` ni privilegios globales. `GRANT USAGE ON *.*`, que
MySQL muestra para una cuenta sin capacidades globales, solo se acepta como línea adicional neutra:
la cuenta también debe tener exactamente los cuatro DML sobre la base esperada.

La configuración versionada usa estas fronteras:

| Uso | Variables |
| --- | --- |
| aplicación / cuenta runtime | `DB_RUNTIME_USER`, `DB_RUNTIME_PASS` |
| inicialización administrativa de MySQL local | `DB_ADMIN_PASS` |
| nombre de la base | `DB_NAME` |

No se guardan valores reales en git. Un cambio en `.env`, un usuario efectivo o un grant es una
mutación de credenciales y requiere la misma ventana autorizada que el cambio de schema.

## Local: volumen nuevo

En un volumen vacío, la imagen oficial de MySQL consume `MYSQL_USER` y `MYSQL_PASSWORD` una sola
vez y primero concede `ALL` sobre `MYSQL_DATABASE`. El config
`runtime_db_least_privilege` corre al final de `/docker-entrypoint-initdb.d`, revoca ese grant
automático y concede solo los cuatro DML admitidos. Antes de inicializarlo, define fuera de git
`DB_RUNTIME_USER`, `DB_RUNTIME_PASS`, `DB_ADMIN_PASS` y `DB_NAME`. No borres ni recrees el volumen
existente para forzar esta inicialización.

## Local: volumen existente

`MYSQL_USER` no modifica un volumen ya inicializado. En una ventana coordinada, después de freeze y
respaldo restaurable, captura los valores sin eco. Los tres identificadores y la contraseña runtime
se restringen deliberadamente a caracteres que no alteran el SQL del bloque:

```bash
read -r -p 'Usuario administrativo MySQL: ' RLS_DB_ADMIN_USER
printf 'Contraseña administrativa MySQL: '
IFS= read -r -s RLS_DB_ADMIN_PASS
printf '\n'
read -r -p 'Base de la aplicación: ' RLS_DB_NAME
read -r -p 'Usuario runtime: ' RLS_DB_RUNTIME_USER
printf 'Contraseña runtime: '
IFS= read -r -s RLS_DB_RUNTIME_PASS
printf '\n'

case "$RLS_DB_ADMIN_USER:$RLS_DB_NAME:$RLS_DB_RUNTIME_USER" in
  (*[!A-Za-z0-9_:]*|'') printf 'Identificador no permitido\n' >&2; return 1 ;;
esac
case "$RLS_DB_RUNTIME_PASS" in
  (*[!A-Za-z0-9._~!@%+=:-]*|'') printf 'Contraseña runtime fuera del alfabeto permitido\n' >&2; return 1 ;;
esac
```

Con esos valores únicamente en el shell, crear o actualizar la cuenta y conceder el DML explícito:

```bash
RLS_DB_SQL="CREATE USER IF NOT EXISTS '${RLS_DB_RUNTIME_USER}'@'%' IDENTIFIED BY '${RLS_DB_RUNTIME_PASS}';
ALTER USER '${RLS_DB_RUNTIME_USER}'@'%' IDENTIFIED BY '${RLS_DB_RUNTIME_PASS}';
GRANT SELECT, INSERT, UPDATE, DELETE ON \`${RLS_DB_NAME}\`.* TO '${RLS_DB_RUNTIME_USER}'@'%';"

printf '%s\n' "$RLS_DB_SQL" \
| docker compose exec -T \
  -e MYSQL_PWD="$RLS_DB_ADMIN_PASS" \
  db mysql --user="$RLS_DB_ADMIN_USER"
unset RLS_DB_SQL RLS_DB_ADMIN_PASS
```

Este comando es idempotente respecto de usuario, contraseña y privilegios DML declarados, pero no
revoca privilegios históricos. Si el auditor detecta uno, la revocación exacta se revisa y autoriza
por separado; no se ejecuta un `REVOKE ALL` genérico.

Verificar la cuenta desde su propia sesión, sin imprimir la contraseña ni conservar los grants:

```bash
docker compose exec -T \
  -e MYSQL_PWD="$RLS_DB_RUNTIME_PASS" \
  db mysql --batch --skip-column-names --user="$RLS_DB_RUNTIME_USER" \
  --execute='SHOW GRANTS FOR CURRENT_USER' \
| docker compose exec -T -e DB_NAME="$RLS_DB_NAME" app \
    php scripts/security/audit-runtime-db-grants.php
unset RLS_DB_RUNTIME_PASS
```

El resultado válido es `runtime_db_grants=ok`; el auditor nunca reimprime las líneas recibidas. Solo
después se cargan `DB_RUNTIME_USER`/`DB_RUNTIME_PASS` en el `.env` local y, con autorización, se
recrea `app`. La ejecución de este runbook no está implícitamente autorizada por su presencia.

## Aplicación one-off del contrato de schema

El dry-run usa la misma cuenta runtime de la aplicación:

```bash
docker compose exec -T app \
  php database/migrations/20260828_project_scope_contract.php
```

`--apply` exige deliberadamente un canal administrativo efímero distinto mediante
`DB_MIGRATION_ADMIN_USER` y `DB_MIGRATION_ADMIN_PASS`; nunca reutiliza `DB_USER`. Solo después de
freeze, respaldo restaurable verificado, dry-run renovado y autorización explícita, un operador
puede exportar esas variables desde su gestor seguro y ejecutar un contenedor one-off, sin
guardarlas en Compose ni `.env`:

```bash
export DB_MIGRATION_ADMIN_USER="$RLS_DB_ADMIN_USER"
export DB_MIGRATION_ADMIN_PASS="$RLS_DB_ADMIN_PASS"
docker compose run -T --rm --no-deps \
  -e DB_MIGRATION_ADMIN_USER \
  -e DB_MIGRATION_ADMIN_PASS \
  app php database/migrations/20260828_project_scope_contract.php --apply
unset DB_MIGRATION_ADMIN_USER DB_MIGRATION_ADMIN_PASS
```

Este bloque documenta la invocación; no autoriza ejecutarla. Si el preflight encuentra un tipo de
`project_id` no entero, una tabla con NULL o cualquier fallo de conexión, termina con RC distinto
de cero antes del primer DDL.

## CI

`docker-compose.ci.yml` declara credenciales efímeras distintas para administración y runtime:
`CI_DB_ADMIN_PASS`, `CI_DB_RUNTIME_USER` y `CI_DB_RUNTIME_PASS`. Sus defaults `ci-*-only-*` existen
solo dentro del proyecto Compose desechable de la corrida; ningún entorno persistente debe usarlos.
La app de CI conecta como runtime y el contenedor `db` conserva la contraseña administrativa solo
para inicialización y tareas de infraestructura explícitas. El mismo config final reduce el `ALL`
que crea la imagen oficial antes de que la base efímera quede saludable.

Los tests PHP que se ejecutan dentro de `app` no usan DDL: `MetricExecutorTest` dobla la frontera
Database/PDO en memoria y el fallo de inserción de `PgAvanceEdicionManualService` se prueba con un
double inyectado. La preparación estructural de la base desechable sigue exclusivamente en los SQL
de `/docker-entrypoint-initdb.d`, ejecutados durante la inicialización administrativa de `db`; la
suite runtime solo necesita DML.

El gate CI del usuario runtime debe obtener `SHOW GRANTS FOR CURRENT_USER` desde esa cuenta y pasar
la salida por `audit-runtime-db-grants.php`, con `DB_NAME=lastplanneraia_ci`.

## SiteGround

En SiteGround, crea la cuenta y asígnala a la base desde **Site Tools → Site → MySQL**, marcando
únicamente lectura y DML (`SELECT`, `INSERT`, `UPDATE`, `DELETE`). No marques administración ni
estructura (`CREATE`, `ALTER`, `DROP`, `INDEX`, `REFERENCES`, `EXECUTE`, `CREATE USER`,
`GRANT OPTION`). Los nombres con prefijo que imponga el hosting se copian exactamente a las
variables de entorno no versionadas del sitio.

Con SSH, valida la cuenta efectiva sin usar la cuenta administrativa:

```bash
read -r -p 'Host MySQL de SiteGround: ' RLS_DB_HOST
read -r -p 'Base de la aplicación: ' RLS_DB_NAME
read -r -p 'Usuario runtime: ' RLS_DB_RUNTIME_USER
printf 'Contraseña runtime: '
IFS= read -r -s RLS_DB_RUNTIME_PASS
printf '\n'

MYSQL_PWD="$RLS_DB_RUNTIME_PASS" mysql --batch --skip-column-names \
  --host="$RLS_DB_HOST" --user="$RLS_DB_RUNTIME_USER" \
  --execute='SHOW GRANTS FOR CURRENT_USER' \
| DB_NAME="$RLS_DB_NAME" php scripts/security/audit-runtime-db-grants.php
unset RLS_DB_RUNTIME_PASS
```

Si el plan de hosting no permite `SHOW GRANTS`, no se declara el gate en verde: se solicita a
soporte la salida redactada o se documenta el bloqueo. Cambiar credenciales activas, grants o
schema de SiteGround sigue siendo deploy y necesita autorización explícita propia.

## Orden del gate de datos

1. Congelar todas las escrituras de la base compartida.
2. Crear un respaldo nuevo y probar su restauración inmediatamente.
3. Ejecutar la migración sin `--apply` y conservar SQL, conteos y resumen.
4. Mostrar el dry-run y pedir autorización explícita a Felipe.
5. Solo tras autorización: aplicar schema, crear/ajustar la cuenta y auditar grants.
6. Ejecutar reconciliación read-only y el contrato `--enforce` contra el schema ya aplicado.

Nunca se asigna `project_id = 0`. Si aparece cualquier NULL, se detiene el gate y se diseña un
backfill por tabla a partir de una relación de pertenencia verificable; sin esa relación, la fila se
mantiene bloqueante.
