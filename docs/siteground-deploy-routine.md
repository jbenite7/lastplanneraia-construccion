# Rutina de despliegue a SiteGround

Checklist operativo para desplegar este proyecto primero en pruebas
`prueba-lps.lastplanneraia.com` y luego en produccion `lastplanneraia.com`, siempre desde
la rama `main` y sin perder cambios locales del servidor.

## Supuestos

- El repositorio local esta en `main`, sincronizado con `origin/main` y verificado.
- El servidor usa PHP 8.3 para web y CLI.
- Pruebas vive en `~/www/prueba-lps.lastplanneraia.com/public_html`.
- Produccion vive en `~/www/lastplanneraia.com/public_html`.
- Alias SSH de pruebas: `siteground-pruebas-lastplanner`.
- Alias SSH de produccion: `siteground-produccion-lastplanner`.
- El archivo `.env` solo vive en el servidor y no se versiona.
- Produccion solo se despliega despues de validar pruebas.

## 1. Preparacion local

```bash
git switch main
git pull --ff-only origin main
git status --short --branch
```

Si hay cambios locales, resuelvelos antes de desplegar.

## 2. Elegir entorno y entrar al servidor

### Pruebas

```bash
ssh siteground-pruebas-lastplanner
cd ~/www/prueba-lps.lastplanneraia.com/public_html
pwd
git status --short --branch
php -v
```

URL de validacion: `https://prueba-lps.lastplanneraia.com/`.

### Produccion

Usa produccion solo despues de que pruebas haya pasado smoke test y verificacion funcional:

```bash
ssh siteground-produccion-lastplanner
cd ~/www/lastplanneraia.com/public_html
pwd
git status --short --branch
php -v
```

URL de validacion: `https://lastplanneraia.com/`.

## 3. Backup antes del deploy

### Pruebas

```bash
mkdir -p ~/backups
tar -czf ~/backups/prueba-lps-predeploy-$(date +%Y%m%d-%H%M%S).tar.gz -C ~/www/prueba-lps.lastplanneraia.com public_html
ls -lt ~/backups | head -n 3
```

### Produccion

```bash
mkdir -p ~/backups
tar -czf ~/backups/lastplanneraia-predeploy-$(date +%Y%m%d-%H%M%S).tar.gz -C ~/www/lastplanneraia.com public_html
ls -lt ~/backups | head -n 3
```

Esto permite rollback rapido aun si el repo queda en un estado inconsistente.

### 3.1 Respaldo de la base, y probarlo

El tar del paso 3 **no cubre la base**. Si el deploy trae migraciones, toma tambien un dump completo
(en el servidor, leyendo las credenciales de `.env` para no escribirlas en el comando):

```bash
set -a; . ./.env; set +a
mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" --single-transaction --routines --triggers "$DB_NAME" \
  > ~/backups/db-predeploy-$(date +%Y%m%d-%H%M%S).sql
tail -1 ~/backups/db-predeploy-*.sql   # debe decir "Dump completed"
```

> [!IMPORTANT]
> **El dump no restaura en otro servidor tal como sale.** `mysqldump` escribe clausulas
> `DEFINER=` con el usuario de SiteGround en vistas y rutinas (30 en la base de pruebas), y en
> cualquier otra maquina la restauracion muere con `ERROR 1449 ... definer does not exist` a mitad
> del archivo. Restaurar en el **mismo** servidor funciona; en un entorno de prueba hay que
> neutralizarlas primero:

```bash
perl -pe 's/\sDEFINER=`[^`]+`@`[^`]+`//g; s/SQL SECURITY DEFINER/SQL SECURITY INVOKER/g' \
  dump.sql > dump-sin-definer.sql
```

**Un dump no probado no es un respaldo.** Restauralo en una base aparte y **compara conteos exactos
contra el origen** — no te conformes con que el comando termine sin error ni con `table_rows` de
`information_schema`, que es una estimacion. Comparacion usada el 2026-07-30 en pruebas:
`programa_consolidado`, `programa`, `programacion_semanal`, `general_usuarios`,
`pi_shared_constraint_links` y `pdc`, con `COUNT(*)` a los dos lados. Si no cuadran, no despliegues.

> [!CAUTION]
> Pruebas y produccion **viven en la misma cuenta SSH**, solo cambian de carpeta y de base
> (`dbbfn7fojgsqao` en pruebas, `dbhif4pdimjtxe` en produccion). Antes de cualquier comando
> destructivo, imprime `$DB_NAME` y confirma contra cual estas trabajando.

## 4. Guardar drift del servidor

Si `git status` muestra cambios locales del servidor, guardalos antes del `pull`:

```bash
git stash push -u -m pre-deploy-$(date +%Y%m%d-%H%M%S)
git stash list | head -n 3
```

Usa esto solo para drift real del servidor. No reemplaza una rama ni una estrategia formal de cambios.

## 5. Desplegar desde main

```bash
git pull --ff-only origin main
```

> [!WARNING]
> **Importante (Problema de PHP CLI en SiteGround):**
> En terminales SSH de SiteGround, el comando global `composer` y `php` suelen estar anclados a versiones obsoletas (como PHP 7.4). Para que el autoloader y las dependencias funcionen para la versión moderna del código, **es obligatorio forzar la ejecución con el binario de PHP 8.3 apuntando directamente al script `.phar` de Composer:**

```bash
/usr/local/php83/bin/php-cli -d memory_limit=4096M /usr/local/bin/composer.phar install --no-dev --optimize-autoloader
```

Notas:

- Usa siempre `--ff-only` para evitar merges manuales en cualquier servidor.
- Si `git pull --ff-only origin main` falla, aborta el deploy y resuelve fuera del servidor.

### 5.1 Migraciones de base de datos

> [!CAUTION]
> **La migracion va ANTES de que el codigo nuevo atienda trafico, no despues.**
> El codigo desplegado asume el esquema nuevo. Si entra primero, cualquier consulta que toque
> una columna o tabla que aun no existe revienta la vista completa, no solo la funcion nueva.
> Ejemplo real (deploy de julio 2026): `20260728_pdc_v2_responsable_usuario.sql` elimina
> `pdc_plan_paquete.responsable` y agrega `responsable_user_id`. Con el codigo publicado y la
> migracion sin correr, **toda la pestana Plan del modulo de compras responde 500**, porque el
> `SELECT` de `plan()` pide la columna nueva — no se degrada solo el responsable.

No hay runner automatico: las migraciones se aplican a mano. Revisa que llego algo nuevo antes de
aplicar:

```bash
git log --name-only --diff-filter=A HEAD@{1}..HEAD -- database/migrations/ | grep -E '^database/migrations/'
```

Si la lista sale vacia, no hay migraciones en este deploy y puedes seguir al paso 6.

> [!CAUTION]
> **El orden NO es cronologico por nombre de archivo.** Es por lo que hace cada migracion: primero
> todo lo que cambia el esquema, despues todo lo que escribe datos. La extension no dice cual es
> cual — hay `.php` que crean tablas y columnas.
>
> Medido en pruebas el 2026-07-30 (34 migraciones, 642 commits de atraso): aplicando por nombre
> fallaron 5 de 34, todas por la misma causa. Las 4 primeras (`paquete_indirectos`,
> `paquetes_profesional_daporto`, `seed_paquetes_aia`, `backfill_modalidades`) son de datos puros y
> caen por nombre *antes* de `modalidad_contratacion.php`, que es la que crea la columna que
> necesitan. La quinta, `desamarrar_paquete.sql`, necesita la tabla que crea `plan_fechas.sql`,
> tambien posterior por nombre. Las cinco pasaron al repetirlas. **Repetir hasta que pase no es un
> plan de deploy:** clasifica antes.

**Como clasificar las migraciones de un deploy.** El criterio es si el archivo toca esquema, sin
importar su extension:

```bash
for f in $(ls database/migrations/*.php | sort); do
  printf "%s  %s\n" "$(grep -cE 'CREATE TABLE|ALTER TABLE|ADD COLUMN|ADD INDEX|MODIFY COLUMN' "$f")" "$(basename "$f")"
done | sort -rn
```

Las que devuelven `0` son de datos y van en la segunda fase. En el deploy del 2026-07-30 el corte
fue 10 de esquema y 11 de datos.

**Las tres fases, en este orden:**

1. **Esquema.** Todos los `.sql`, y las `.php` con conteo distinto de `0` (estas tambien admiten
   `--apply`). Si una falla por una tabla o columna que no existe, su dependencia es otra migracion
   de esta misma fase: aplica el resto y repite la que fallo al terminar la fase.
2. **Datos.** Las `.php` con conteo `0`. Dentro de esta fase, **el sembrado del catalogo va
   primero** (`seed_paquetes_aia`): varias de las demas mueven o retiran paquetes que ese sembrado
   crea, y sin el reportan «destino no existe activo» y no hacen nada.
3. **Repaso.** Vuelve a correr `admite_materiales` y `puente_duraciones` con `--apply`. Son
   idempotentes y dependen del catalogo ya sembrado, asi que en la fase 1 quedan a medias.

**Comprobar el resultado, no el codigo de salida.** Al terminar, vuelve a correr todas en seco: las
que reporten trabajo pendiente hay que mirarlas una por una contra la base. Dos mienten en el
simulacro y estan bien aplicadas — `rama_frente` dice «26 a insertar» con las 26 puestas, y
`admite_materiales` dice «a marcar: 5» con las 5 marcadas. Confirma en SQL antes de repetirlas.

> [!WARNING]
> **Una migracion con `DELIMITER $$` no se puede aplicar por PDO ni por PHP.** `DELIMITER` es una
> directiva del cliente `mysql`, no SQL, y el error que sale parece SQL mal escrito sin serlo. Esas
> van por `mysql <archivo` obligatoriamente. Caso conocido en camino:
> `20260729_pdc_v2_subpaquetes.sql`.

**Las `.php` necesitan el entorno exportado.** `src/Core/Database.php` lee `$_ENV`/`getenv()` y no
carga `.env` por su cuenta, asi que en CLI hay que exportarlo antes o toda migracion `.php` falla
con «No se pudo conectar a la base de datos», que parece un problema del servidor y no lo es:

```bash
set -a; . ./.env; set +a
```

**Archivos `.sql` (DDL).** Se aplican directo. Backup de las tablas afectadas primero, porque un
`DROP COLUMN` no se deshace con el backup de archivos del paso 3:

```bash
mysqldump -u USUARIO -p BASE tabla_afectada > ~/backups/tabla_afectada-$(date +%Y%m%d-%H%M%S).sql
mysql -u USUARIO -p BASE < database/migrations/NOMBRE.sql
```

**Archivos `.php` (backfills).** Corren primero en seco y solo despues de leer el reporte se
aplican. Nunca lances `--apply` sin haber mirado la salida del dry-run:

```bash
/usr/local/php83/bin/php-cli database/migrations/NOMBRE.php
/usr/local/php83/bin/php-cli database/migrations/NOMBRE.php --apply
```

**Orden correcto del deploy con migraciones:** backup (paso 3) -> `git pull` -> `composer install`
-> migraciones -> smoke tests. Si la migracion falla a la mitad, no sigas al paso 6: ve al
rollback y restaura tambien la base, no solo los archivos.

## 6. Smoke tests minimos

### Pruebas

```bash
REQUEST_METHOD=GET REQUEST_URI=/ php public/index.php
curl -I https://prueba-lps.lastplanneraia.com/
```

### Produccion

```bash
REQUEST_METHOD=GET REQUEST_URI=/ php public/index.php
curl -I https://lastplanneraia.com/
```

Resultado esperado:

- El primer comando debe renderizar el HTML del login o la pagina esperada.
- El segundo debe responder `HTTP/2 200`.

## 7. Verificacion funcional

- En pruebas, abrir `https://prueba-lps.lastplanneraia.com/`.
- Hacer login.
- Probar al menos un modulo critico del flujo afectado por el deploy.
- Si el cambio toca reportes o importaciones, ejecutar un caso real corto.
- Solo despues de aprobar pruebas, repetir la verificacion en `https://lastplanneraia.com/`.

## 8. Rollback rapido

Si el sitio falla despues del deploy:

1. Identifica el backup mas reciente.
2. Restaura el contenido respaldado desde `~/backups`.
3. **Si el deploy incluyo migraciones, restaurar los archivos NO basta:** el codigo vuelve a la
   version vieja pero la base se queda en el esquema nuevo, y el fallo cambia de forma en vez de
   desaparecer. Restaura tambien el dump de las tablas afectadas que tomaste en el paso 5.1.
4. Revalida `php -v`, `composer install` y `/`.

Ejemplo de apoyo:

```bash
ls -lt ~/backups | head -n 5
```

Si el problema fue un cambio local guardado en stash, revisa antes de recuperarlo:

```bash
git stash list
git stash show --stat stash@{0}
```

## 9. Limpieza post deploy

Solo cuando el sitio este estable:

```bash
git stash list
```

Si el stash de predeploy ya no sirve, eliminalo explicitamente:

```bash
git stash drop stash@{0}
```

Tambien limpia carpetas no trackeadas viejas dentro de `public_html` si confirmas que no se usan.

## Regla operativa

- No editar produccion a mano salvo emergencia.
- No subir `.env` al repositorio.
- No hacer `git pull` en ningun entorno sin backup.
- No resolver conflictos manuales directamente en el servidor.
- Todo deploy debe salir desde `main`.
- Produccion requiere verificacion local y smoke test exitoso en pruebas.
- Si el deploy trae migraciones, se aplican antes de los smoke tests (paso 5.1) y con dump previo
  de las tablas afectadas. El backup de archivos del paso 3 no cubre la base.
- Las migraciones **no se aplican en orden de nombre de archivo**, sino en tres fases: esquema,
  datos, repaso (paso 5.1). Repetir las que fallan hasta que pasen no es un plan de deploy.
- Un respaldo de base **sin restauracion probada y conteos comparados** no cuenta como respaldo
  (paso 3.1). Si no se puede volver atras, no se despliega.
