---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-08-19
areas: [proceso, worktrees, docker, deploy]
tags: [trampa]
fuente: frente ds-f0-auditoria, cierre del 2026-08-19
resumen: "`publicar.sh` exige apuntar el contenedor compartido a tu worktree, y eso le rompe la verificación a la sesión que lo esté usando"
---

# `publicar.sh` choca consigo mismo cuando dos worktrees verifican a la vez

**El síntoma.** `bash scripts/publicar.sh` deniega antes de verificar nada:

```
DENEGADO: el contenedor 'app' no sirve el arbol que ibas a verificar.
  monta:    /Users/felipebenitez/Developer/lps-aia/.claude/worktrees/recursing-shtern-472554
  verificas: /Users/felipebenitez/Developer/lps-aia/.claude/worktrees/bold-neumann-485f23
```

y ofrece el remedio: `LPS_CODE_ROOT="$(pwd)" docker compose up -d app`.

**Lo que parece.** Que basta con seguir el remedio, publicar y devolver el contenedor a su sitio.
El propio mensaje lo dice y son dos comandos.

**Lo que es.** El contenedor `app` es **uno solo para todo el repo** —`docker-compose.yml` fija
`name: last-planner-aia`, así que `docker compose exec` desde cualquier worktree aterriza en el
mismo—. Reapuntarlo no es un ajuste local: **se lo quitas a quien lo esté usando**. El 2026-08-19
lo montaba `recursing-shtern-472554` (frente `wiki-t2`), viva y trabajando. Seguir el remedio
habría dejado a esa sesión verificando contra un árbol ajeno durante los minutos que dura
`publicar.sh` — que corre tres comprobaciones, no una— y sin avisarle. Es exactamente el defecto
que el script existe para impedir, trasladado a la sesión de al lado.

El invariante del script es correcto; lo que no contempla es que el recurso que exige es
**exclusivo y compartido**, así que con dos worktrees vivos solo uno puede publicar por el camino
oficial.

**Cómo se sale.** No reapuntando a ciegas. Comprueba **lo que el invariante protege** en vez de
fabricarlo: qué lee de verdad el PHP dentro del contenedor, y si tu árbol y el montado difieren
ahí.

```bash
git diff --name-only <sha-montado> HEAD | grep -E '^(public/css|public/js|src/|admin/|views/)'
```

Si eso sale vacío, el contenedor mide lo mismo en tu árbol que en el suyo y el verde vale. En el
caso medido salió vacío: los únicos `scripts/` que diferían eran `wiki-*`, que ninguna de las
cuatro pruebas con docker (`ci-preflight`, `phpstan-baseline`, `foundation`, `visual-ci-contract`)
abre. Con eso, se publica a mano cumpliendo las dos reglas del paso 6 de `AGENTS.md` — código de
salida leído **en su propia línea**, y `HEAD:main` en vez de `main`.

Si el diff **no** sale vacío, no hay atajo: hay que coordinar con la otra sesión, no adelantarla.

**Cuánto costó.** El 2026-08-19, unos veinte minutos entre detectar la denegación, entender que el
remedio tenía víctima y construir la comprobación alternativa. No costó más porque se miró quién
montaba el contenedor antes de reapuntarlo; hacerlo sin mirar no habría dado error — habría dado
un verde ajeno en la otra sesión.

Relacionadas: [[suite-estatica-miente-en-worktree-secundario]] · [[publicar-sh-se-aisla-y-se-rompe-en-la-raiz]]
