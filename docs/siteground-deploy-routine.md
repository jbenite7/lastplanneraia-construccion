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
- No hacer `git pull` en ningun entorno sin backup.
- No resolver conflictos manuales directamente en el servidor.
- Todo deploy debe salir desde `main`.
- Produccion requiere verificacion local y smoke test exitoso en pruebas.
