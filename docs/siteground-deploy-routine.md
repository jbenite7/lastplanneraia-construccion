# Rutina de despliegue a SiteGround

Checklist operativo para desplegar este proyecto en `prueba-lps.lastplanneraia.com` desde la rama
`main` sin perder cambios locales del servidor.

## Supuestos

- El repositorio local ya esta en `main` y sincronizado con `origin/main`.
- El servidor usa PHP 8.3 para web y CLI.
- El proyecto esta desplegado en `~/www/prueba-lps.lastplanneraia.com/public_html`.
- El archivo `.env` solo vive en el servidor y no se versiona.

## 1. Preparacion local

```bash
git switch main
git pull --ff-only origin main
git status --short --branch
```

Si hay cambios locales, resuelvelos antes de desplegar.

## 2. Entrar al servidor

```bash
ssh -p <PUERTO> <USUARIO>@<HOST>
cd ~/www/prueba-lps.lastplanneraia.com/public_html
pwd
git status --short --branch
php -v
```

## 3. Backup antes del deploy

```bash
mkdir -p ~/backups
tar -czf ~/backups/prueba-lps-predeploy-$(date +%Y%m%d-%H%M%S).tar.gz -C ~/www/prueba-lps.lastplanneraia.com public_html
ls -lt ~/backups | head -n 3
```

Esto permite rollback rapido aun si el repo queda en un estado inconsistente.

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
composer install --no-dev --optimize-autoloader
```

Notas:

- Usa siempre `--ff-only` para evitar merges manuales en produccion.
- Si Composer detecta cambio de plataforma, confirma primero la version de PHP.

## 6. Smoke tests minimos

```bash
REQUEST_METHOD=GET REQUEST_URI=/ php public/index.php
curl -I https://prueba-lps.lastplanneraia.com/
```

Resultado esperado:

- El primer comando debe renderizar el HTML del login o la pagina esperada.
- El segundo debe responder `HTTP/2 200`.

## 7. Verificacion funcional

- Abrir `https://prueba-lps.lastplanneraia.com/`.
- Hacer login.
- Probar al menos un modulo critico del flujo afectado por el deploy.
- Si el cambio toca reportes o importaciones, ejecutar un caso real corto.

## 8. Rollback rapido

Si el sitio falla despues del deploy:

1. Identifica el backup mas reciente.
2. Restaura el contenido respaldado desde `~/backups`.
3. Revalida `php -v`, `composer install` y `/`.

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
- No hacer `git pull` en produccion sin backup.
- No resolver conflictos manuales directamente en el servidor.
- Todo deploy debe salir desde `main`.
