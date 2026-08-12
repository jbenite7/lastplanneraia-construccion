---
tipo: referencia
estado: vigente
fecha: 2026-07-23
areas: [deploy]
fuente: memoria-claude
origen: lps-aia-produccion-deploy
resumen: Cómo se despliega producción (SiteGround, SSH+git pull), la llave dedicada, el backlog de código pendiente — y que desde el 2026-08-12 la BASE ya está nivelada y reparada aunque el código no
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
