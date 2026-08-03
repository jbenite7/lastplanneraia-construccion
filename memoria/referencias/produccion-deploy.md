---
tipo: referencia
estado: vigente
fecha: 2026-07-23
areas: [deploy]
fuente: memoria-claude
origen: lps-aia-produccion-deploy
resumen: Cómo se despliega producción (SiteGround, SSH+git pull), la llave dedicada creada, y que prod va ~147 commits detrás de main (release completo pendiente)
---
Producción = `lastplanneraia.com` en **SiteGround**. Se despliega por SSH con `git pull --ff-only origin main` siguiendo `docs/siteground-deploy-routine.md` (pruebas primero → backup → pull → composer PHP 8.3 → smoke). Alias SSH: `siteground-produccion-lastplanner` y `siteground-pruebas-lastplanner` (ambos → `ssh.lastplanneraia.com:18765`, user `u2440-8uoflwe1kgey`; dir prod `~/www/lastplanneraia.com/public_html`). El remote del server es `git@github.com:jbenite7/lastplanneraia-construccion.git`.

**SSH desde una sesión no interactiva de Claude**: se creó una llave **dedicada sin passphrase** `~/.ssh/lps_siteground_deploy`, autorizada en **Site Tools → Devs → SSH Keys Manager**. La llave `~/.ssh/lastplanneraia_prod` tiene passphrase en Keychain y NO sirve en sesión no interactiva (agente launchd compartido visible, pero vacío hasta que el usuario haga `ssh-add`). Los alias resuelven vía `~/.ssh/config.d/recovered-aliases`.

**Al 2026-07-23, producción estaba ~147 commits detrás de `origin/main`** (807 archivos / ~116k líneas: design-system, shell/sidebar foundation, Handsontable, BI…). Un `git pull` despliega TODO ese backlog, no un solo cambio → el **release completo sigue pendiente** y exige pruebas-primero + composer + revisar migraciones. Por eso el hotfix de Power BI en `/indicadores` se aplicó como **parche quirúrgico** (editar solo `views/indicadores/indicadores.view.php` en el server, con backups `*.bak-*`); antes del release completo hay que descartar ese drift con `git checkout -- views/indicadores/indicadores.view.php`. El server ya tenía otro drift previo (`modal_reabrir.php`). Ver [[powerbi-indicadores]].

`gh pr merge` y `git stash drop` los **bloquea el clasificador del harness**: el usuario debe mergear los PRs y limpiar stashes.
