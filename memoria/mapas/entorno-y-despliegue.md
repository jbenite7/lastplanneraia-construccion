---
capa: wiki
tipo: mapa
estado: vigente
fecha: 2026-08-02
areas: [docker, deploy]
fuente: sesion
resumen: "Levantar el proyecto en local, aislar un worktree y publicar en producción — con las trampas que ya mordieron"
---
# Mapa · Entorno y despliegue

## Qué manda

[[AGENTS]] §Runtime local y §Publicación · [[CLAUDE]] §Runtime & commands ·
[[docs/siteground-deploy-routine]] para publicar.

Detalle del stack: [[docker/README|docker/README.md]] describe el compose `last-planner-aia`
servicio por servicio; la puerta de servicio que sustituye a `/login` está diseñada en
[[docs/superpowers/specs/2026-07-30-dev-door-design|su spec]]. Cómo quedaron unificados los dos
repositorios: [[docs/superpowers/specs/2026-07-29-unificacion-repos-design|unificacion-repos]].

## Local

Docker Compose es la única fuente de verdad. Nunca MAMP, XAMPP ni un PHP del host. Servicios:
`app` (PHP 8.3 + Apache, puerto 8081), `db` (MySQL 8.0, puerto 3307 en el host) y `adminer`
(8082). `docker-compose.override.yml` monta el código local, así que no hace falta reconstruir por
rutina.

Para abrir sesión: [[dev-door-acceso-local]]. Nunca se teclean credenciales ni se le pide el login
a una persona.

## Worktrees, que es donde se rompe todo

`docker-compose.yml` fija `name:` y usa un volumen externo compartido. Consecuencia: desde
cualquier worktree, un `docker compose` a secas opera sobre el stack **del árbol principal y su
misma base de datos**.

Lee, en este orden: [[dos-stacks-docker]] (el síntoma),
[[worktree-compartido-arrastra-commits]] (por qué compartir worktree arrastra commits ajenos),
[[aislar-stack-docker-por-worktree]] (la receta que funciona) y
[[servir-worktree-stack-efimero]] (por qué `docker run` suelto no basta).

Detalle que muerde aparte: la ruta del repo **contiene un espacio**
([[path-with-space-esm-guard-noop]]).

## Producción

[[produccion-deploy]] tiene el procedimiento real: SSH a SiteGround y `git pull --ff-only origin
main`, con llave dedicada. Producción va muy por detrás de `main`, así que un despliegue completo
no es un trámite.

No se puede navegar el entorno remoto como si fuera local:
[[siteground-sin-tunel-ssh]].

No se hace commit, push ni deploy sin petición explícita. Una publicación aprobada no autoriza
limpiar deriva del servidor ni desplegar de paso otros cambios.

## El área, en una tabla

<!-- Vista nativa de Obsidian Bases. Si no renderiza, el contenido de arriba sigue siendo
     legible: los plugins y las vistas amplifican, no sostienen. -->
![[area-docker.base]]
![[area-deploy.base]]

## Vecinos

[[qa-y-gates]] para validar antes de publicar · [[arquitectura]] para qué se despliega.
