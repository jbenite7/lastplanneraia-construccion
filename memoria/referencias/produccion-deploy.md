---
capa: wiki
tipo: referencia
estado: vigente
fecha: 2026-07-23
areas: [deploy]
fuente: memoria-claude
origen: lps-aia-produccion-deploy
resumen: Cómo se despliega producción (SiteGround, SSH+git pull), la llave dedicada — y que el 2026-08-12 se desplegó por fin el release completo (1.763 commits) sobre una base ya nivelada, sin migraciones
---
Producción = `lastplanneraia.com` en **SiteGround**. Se despliega por SSH con `git pull --ff-only origin main` siguiendo `docs/siteground-deploy-routine.md` (pruebas primero → backup → pull → composer PHP 8.3 → smoke). Alias SSH: `siteground-produccion-lastplanner` y `siteground-pruebas-lastplanner` (ambos → `ssh.lastplanneraia.com:18765`, user `u2440-8uoflwe1kgey`; dir prod `~/www/lastplanneraia.com/public_html`). El remote del server es `git@github.com:jbenite7/lastplanneraia-construccion.git`.

**SSH desde una sesión no interactiva de Claude**: se creó una llave **dedicada sin passphrase** `~/.ssh/lps_siteground_deploy`, autorizada en **Site Tools → Devs → SSH Keys Manager**. La llave `~/.ssh/lastplanneraia_prod` tiene passphrase en Keychain y NO sirve en sesión no interactiva (agente launchd compartido visible, pero vacío hasta que el usuario haga `ssh-add`). Los alias resuelven vía `~/.ssh/config.d/recovered-aliases`.

**Al 2026-07-23, producción estaba ~147 commits detrás de `origin/main`** (807 archivos / ~116k líneas: design-system, shell/sidebar foundation, Handsontable, BI…). Un `git pull` despliega TODO ese backlog, no un solo cambio → el **release completo sigue pendiente** y exige pruebas-primero + composer + revisar migraciones. Por eso el hotfix de Power BI en `/indicadores` se aplicó como **parche quirúrgico** (editar solo `views/indicadores/indicadores.view.php` en el server, con backups `*.bak-*`); antes del release completo hay que descartar ese drift con `git checkout -- views/indicadores/indicadores.view.php`. El server ya tenía otro drift previo (`modal_reabrir.php`). Ver [[powerbi-indicadores]].

`gh pr merge` y `git stash drop` los **bloquea el clasificador del harness**: el usuario debe mergear los PRs y limpiar stashes.

**Actualización 2026-08-12**: con producción en mantenimiento y respaldo verificado, se le aplicó
la reparación de `unique_id` nulos (25.708 filas de consolidado + 1.422 de semanal) y la nivelación
de esquema (de 71 a 102 objetos: PDC v2, BI y catálogos, con datos verificados conteo a conteo
contra local). Detalle, respaldos y trampas en [[espejo-y-reparacion-unique-id]]. La asimetría
resultante es deliberada: **la base ya espera al código**; el `git pull` del release pendiente no
necesita migraciones de esquema para PDC v2. El drift del servidor (`indicadores.view.php`,
`modal_reabrir.php`) seguía presente ese día y no se tocó.

## El release completo, desplegado el 2026-08-12

**Ejecutado y verificado.** Producción pasó de `1aa7c694` a `939b7928` en un solo `git pull
--ff-only`: **1.763 commits**, con el sitio en mantenimiento durante toda la operación. Es el
release que llevaba desde julio pendiente, y por fin salió porque la base ya estaba nivelada — **no
se ejecutó ninguna migración**, y esa es la diferencia con todos los intentos anteriores.

Lo que se hizo, en orden: tar de respaldo de archivos
(`~/backups/lastplanneraia-predeploy-20260813-043021.tar.gz`, 709 MB) → **descartar el drift**
(`git checkout --` sobre `indicadores.view.php` y `modal_reabrir.php`, hotfixes de julio ya
superados por `main`, que además trae la corrección de seguridad de `/indicadores` en `4b1a2be0`)
→ `git pull --ff-only` → composer con el binario correcto.

**El `composer install` no fue trámite**: actualizó **11 paquetes**, entre ellos saltos mayores
—`phpmailer` 6.12 → **7.1.1**, `phpspreadsheet` 5.4 → 5.9, `zipstream` 3.1 → 3.2— además de
regenerar el classmap, que es lo que hace existir las clases nuevas (16 entradas de
`Services\Pdc` donde antes había cero). El binario global sigue anclado a PHP 7.4 y por eso el
comando va siempre por
`/usr/local/php83/bin/php-cli -d memory_limit=4096M /usr/local/bin/composer.phar`.

**Cómo se hace un smoke con el sitio en 503.** Bajo mantenimiento todo responde 503, así que el
código de estado no distingue «mantenimiento» de «la app está rota» — es justo la comprobación que
uno cree estar haciendo y no hace. Lo que sí discrimina son las **rutas exentas** que
`MaintenanceMode::isExemptRoute()` deja pasar: la puerta secreta `/_aia/operacion/7f3c9b` devolvió
**200 con el HTML del login completo**, y `/runtime/frontend-config.js` otro 200. Eso prueba que el
front controller arranca, el autoloader resuelve y las vistas renderizan. El `php_errorlog` no
sumó ni una línea nueva (la última es del 23 de julio). Sin reenvío de puertos, todo desde dentro
del servidor con `curl -k -H "Host: lastplanneraia.com" https://127.0.0.1/...`.

La **reapertura** (`rm .maintenance`) es un paso aparte y **exige confirmación explícita del
usuario en la conversación**; no viaja con el deploy.
