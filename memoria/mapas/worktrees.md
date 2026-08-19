---
capa: wiki
tipo: mapa
estado: vigente
fecha: 2026-08-19
areas: [worktrees]
fuente: AGENTS.md, CLAUDE.md, las 12 trampas del área
resumen: "Trabajar en un worktree: qué comparte con la raíz, qué no, y por qué casi todo verde medido aquí puede ser de otro árbol"
---

# Mapa · Worktrees

## Qué manda

[[AGENTS|AGENTS.md]] §Runtime local y §Publicación · [[CLAUDE|CLAUDE.md]] §Runtime & commands.

## La idea que ordena el área

**Un worktree aísla el código, no el entorno.** El repositorio da a cada rama su propio árbol de
archivos, pero el contenedor Docker, la base de datos, los puertos y el registro de sesiones son
**uno solo para todos**. Casi todas las trampas de abajo son la misma sorpresa vista desde ángulos
distintos: se mide en un árbol y se concluye sobre otro.

## Antes de tocar

- **Enlaza el `.env` de la raíz**, no lo copies: `ln -s "<raíz>/.env" .env`. Un worktree nace sin
  él porque está en `.gitignore`, y sin él `docker compose` resuelve las credenciales a cadena
  vacía — la web sigue verde porque ella sí lee el `.env`, y la línea de comandos muere.
- **El contenedor sirve la raíz, no tu rama.** Para ver tu trabajo en el navegador:
  `LPS_CODE_ROOT="$(pwd)" docker compose up -d app`, y devuélvelo al terminar.

## Trampas

- [[un-verde-solo-vale-para-el-arbol-donde-se-midio]] — la trampa madre del área.
- [[verificas-un-arbol-y-publicas-otro]] — el paso 5 y el 6 del gate pueden mirar árboles distintos.
- [[exec-en-contenedor-vivo-corre-el-repo-ajeno]] — un contenedor en marcha conserva su bind mount.
- [[restart-devuelve-el-montaje-a-la-raiz]] — `LPS_CODE_ROOT` dura hasta el primer `restart`.
- [[suite-estatica-miente-en-worktree-secundario]] y [[suite-estatico-mide-dos-arboles]] — rojos
  falsos por comparar mtimes de dos árboles.
- [[gate-que-mide-dos-arboles-a-la-vez]] — el mismo defecto dentro de un solo test.
- [[worktree-compartido-arrastra-commits]] — dos sesiones en un worktree se pisan sin avisar.
- [[borrar-el-worktree-no-cierra-la-sesion]] — el registro no se entera.
- [[cada-worktree-tiene-su-copia-congelada]] — el registro de sesiones vive en la raíz.
- [[servir-worktree-stack-efimero]] y [[aislar-stack-docker-por-worktree]] — cómo darle identidad
  propia cuando de verdad hace falta.
- [[variable-vacia-tapa-el-env]] — una variable inyectada vacía cuenta como definida.

## Vecinos

[[entorno-y-despliegue]] para Docker y publicación · [[qa-y-gates]] para qué suite creer ·
[[procesos-y-sesiones]] para el gate de cierre y la coordinación entre sesiones.
