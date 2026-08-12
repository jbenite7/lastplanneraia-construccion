# Plan de restauración para el despliegue pendiente

- **Escrito el:** 2026-08-12, por la sesión coordinadora, **antes** de tener autorización.
- **Estado:** preparación. **No autoriza nada.** El despliegue a pruebas y a producción necesita
  autorización explícita, propia y en el momento, del usuario — y son **dos**, no una
  (`AGENTS.md` §Publicación; precisión del 2026-08-12).
- **Por qué existe:** la rutina (`docs/siteground-deploy-routine.md`) exige respaldo verificable y
  estrategia de restauración **antes** de empezar. Redactarla con el servidor ya tocado es tarde.

## Qué se despliega

~1.255 commits acumulados desde el último despliegue. **No es una entrega pequeña**, y eso cambia el
perfil de riesgo: cuanto más grande el salto, menos sirve el «si algo falla, lo vemos enseguida».

## Antes de tocar nada

1. **Local:** `git switch main && git pull --ff-only origin main && git status --short --branch`.
   Árbol limpio y sincronizado, o no se empieza.
2. **Confirmar contra qué base se trabaja.** Pruebas y producción **comparten cuenta SSH** y solo
   cambian de carpeta y de base (`dbbfn7fojgsqao` pruebas, `dbhif4pdimjtxe` producción). **Imprimir
   `$DB_NAME` y leerlo** antes de cualquier comando destructivo. Es el paso que convierte un error de
   carpeta en un incidente.

## Respaldo, y por qué el comando terminando bien no basta

- **Archivos:** `tar -czf ~/backups/<entorno>-predeploy-<fecha>.tar.gz` de `public_html`.
- **Base:** `mysqldump --single-transaction --routines --triggers`, leyendo credenciales de `.env`
  **en el servidor** — nunca escritas en el comando ni en un log.
- **Y probarlo:** un dump no probado no es un respaldo. Restaurar en una base aparte y **comparar
  `COUNT(*)` exactos** contra el origen en `programa_consolidado`, `programa`,
  `programacion_semanal`, `general_usuarios`, `pi_shared_constraint_links` y `pdc`. **No vale
  `table_rows` de `information_schema`: es una estimación.** Si no cuadran, no se despliega.
- **Trampa ya medida:** el dump lleva cláusulas `DEFINER=` con el usuario de SiteGround (30 en la
  base de pruebas). Restaura bien en **el mismo** servidor; en otro muere con
  `ERROR 1449 ... definer does not exist` **a mitad del archivo** — es decir, deja la base a medias
  sin decir que falló al principio. Para probarlo fuera, neutralizarlas antes con el `perl -pe` de la
  rutina.

## Cómo se vuelve atrás

1. **Código:** `git reset --hard <sha-anterior>` en el servidor, o desempaquetar el `tar` sobre
   `public_html`. El sha anterior **se anota antes** del `pull`, no se busca después.
2. **Drift del servidor:** si `git status` mostraba cambios locales, van a `git stash push -u` antes
   del `pull`. **Restaurarlos es parte del rollback**, no un extra: son cambios que alguien hizo en el
   servidor y que nadie tiene en git.
3. **Base:** solo si el despliegue trajo migraciones. Restaurar el dump **en el mismo servidor** y
   repetir los `COUNT(*)` de arriba.
4. **Comprobar que se volvió:** el smoke del flujo afectado tiene que pasar **después** del rollback.
   Un rollback no verificado es una suposición.

## Límites de esta preparación

- **No cubre el despliegue en sí.** Es el plan de qué hacer si sale mal.
- **No se ha ejecutado ningún comando contra ningún servidor**, ni de lectura. Todo lo de arriba sale
  de `docs/siteground-deploy-routine.md` y de lo medido en este repositorio.
- **Una autorización de despliegue no autoriza limpiar drift del servidor ni desplegar otros
  cambios.** Aprueba esa publicación y nada más.

## Estado del CI al escribir esto, para que la decisión se tome con el dato

- `design-system-static`: **verde** desde el 2026-08-12.
- `full-app-flow`: **verde** en CI, primera vez.
- `runtime-budgets`: **rojo honesto** — `D-GAC-5`, baseline congelado el 2026-07-17.
- Carril visual: **rojo** — `D-GAC-4`, goldens de una sola plataforma.

**Ninguno de los dos rojos es una regresión conocida del producto**, pero **tampoco están
descartados**: lo que está medido es que las causas son de entorno y de baselines viejos, no que la
aplicación esté sana. Desplegar con esos dos rojos abiertos es una decisión legítima, pero es **una
decisión**, no un trámite.
