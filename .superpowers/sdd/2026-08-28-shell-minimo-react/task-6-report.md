# Task 6 — frontera SPA/PHP

## Resultado

Commit de implementación: `c337e5a197329fe5ac9f3d6db6616e853c4caf06`

Commit de corrección de directorio de assets: `a552cfe9b5a270cbc7fead0176dab29f9d2321ee`

La frontera única es `App\Core\SpaRouter`:

- `/app` y cualquiera de sus descendientes son del shell React.
- `/api/*` nunca pasa a la SPA.
- `/app/assets` y `/app/assets/*` se sirven como contenido estático, nunca como
  `index.html`.
- Las rutas PHP existentes (incluida `/login`) conservan el `SessionMiddleware`
  obligatorio y su despacho original.

`public/index.php` aplica `SessionMiddleware::validationFailureReason()` a toda
ruta SPA antes de servir el shell. Así el visitante anónimo recibe el HTML, pero
una cookie inactiva, vencida, huérfana o que no se puede validar se invalida con
las mismas reglas compartidas antes de que el navegador haga el bootstrap.

La regla específica de `.htaccess` dirige `/app/assets/*` a
`public/app/assets/*`, para que Apache no envíe el bundle al front controller.
La ruta de directorio exacta `/app/assets` (con o sin barra final) responde
404 controlado: no lista archivos, no llega a PHP y no redirige a `/login`.

## TDD: evidencia rojo → verde

1. Se creó primero `tests/test_spa_frontera.php` con los casos de rutas SPA,
   rutas PHP, API y asset.
2. Rojo confirmado en un contenedor PHP con el worktree montado:

   ```text
   PHP Fatal error: Class "App\Core\SpaRouter" not found
   ```

   Comando:

   ```sh
   docker run --rm -v "$PWD:/var/www/html:ro" -w /var/www/html \
     lps-aia-app-ci:local php tests/test_spa_frontera.php
   ```

3. Se creó también antes del cambio del front controller
   `tests/test_spa_frontera_http.php`. Contra Apache sin la implementación,
   `/app` y `/app/login` respondían `302`, en vez de `200` con el shell.
4. Con `SpaRouter`, el check opcional, el hook de `index.html` y la regla de
   assets aplicados, las verificaciones finales fueron verdes:

   ```text
   OK: frontera SPA/PHP
   OK: frontera SPA/PHP por HTTP
   No syntax errors detected in src/Core/SpaRouter.php
   No syntax errors detected in public/index.php
   ```

   El test HTTP levanta Apache efímero con el worktree montado y usa la misma
   base `db` del stack; cubre `/app` anónimo, `/app/login`, entrega real de un
   asset de `public/app/assets`, y el límite legado `/login`.

## Archivos incluidos en el commit

- `.htaccess`
- `public/index.php`
- `src/Core/SpaRouter.php`
- `tests/test_spa_frontera.php`
- `tests/test_spa_frontera_http.php`

## Verificación amplia

También se repuntó temporalmente el servicio Docker compartido `app` al
worktree, tal como documenta `docker-compose.override.yml`:

```sh
LPS_CODE_ROOT="$PWD" docker compose up -d --no-deps --force-recreate app
docker compose exec app php tests/test_spa_frontera.php
docker compose exec -e APP_URL=http://127.0.0.1 app php tests/test_spa_frontera_http.php
```

Ambos tests dieron `OK`. Al terminar se restauró explícitamente el bind del
servicio al checkout raíz:

```sh
LPS_CODE_ROOT=/Users/felipebenitez/Developer/lps-aia \
  docker compose up -d --no-deps --force-recreate app
```

La comprobación final de Docker confirmó el mount
`/Users/felipebenitez/Developer/lps-aia:/var/www/html:rw` y `GET /login` en
`localhost:8081` devolvió HTTP 200.

## Ronda de corrección: directorio `/app/assets`

La revisión detectó que el patrón inicial solo cubría `/app/assets/*`.
Se añadió primero una aserción HTTP para `/app/assets` y se ejecutó contra el
worktree repuntando temporalmente `app`. El rojo fue reproducible: la ruta no
devolvía 404 y la cabecera contenía `Location: /login`, porque caía al front
controller.

La corrección mínima es la regla terminal:

```apache
RewriteRule ^app/assets/?$ - [R=404,L]
```

Después, ejecutados de nuevo contra el worktree mediante Docker Compose:

```text
OK: frontera SPA/PHP
OK: frontera SPA/PHP por HTTP
No syntax errors detected in tests/test_spa_frontera_http.php
```

`git diff --check` no informó errores. El servicio `app` se restauró otra vez
al bind del checkout raíz y `/login` respondió HTTP 200. La prueba mantiene la
aserción de que `/app/assets/*` entrega un asset real con HTTP 200.

Se ejecutó la suite PHP hasta nivel HTTP en un Apache Docker efímero con el
worktree montado:

```sh
docker exec <apache-efimero> php scripts/run-php-tests.php --nivel=http
```

El runner descubrió 131 tests y seleccionó 95. Los dos tests nuevos pasaron;
la suite terminó con 88 pasados y 7 fallidos. Los fallos no están relacionados
con la frontera y se deben al entorno/fixtures existente:

- `test_dev_door_http.php`: `DEV_DOOR` no estaba presente en el contenedor
  efímero.
- `test_admin_dev_door_guard.php` y `test_admin_modulos.php`: dependen de esa
  puerta de desarrollo y sus sesiones.
- `test_bi_alcance_responsables.php`: falta la tabla
  `lastplanneraia_dev_profesionales`.
- `test_bitacora_avance_endpoint.php`: no pudo obtener el token CSRF de
  `/programa-general`.
- `test_report_processor_cic_project_scope.php`: fixtures CIC/CIP no
  consistentes y una clave duplicada.
- `test_semanal_sanear_csrf.php`: el endpoint devolvió 200 donde el fixture
  esperaba 403.

Los contratos API existentes (`test_api_auth_contract.php`,
`test_api_projects_contract.php` y `test_api_session_contract.php`) también
pasaron en esa corrida.

## Preocupaciones

No quedan preocupaciones de implementación. La única limitación de validación
amplia es el conjunto de siete fallos de entorno/fixtures descrito arriba; no
se modificaron módulos, datos, pdc-app, ct-app ni tokens para intentar
silenciarlos.
