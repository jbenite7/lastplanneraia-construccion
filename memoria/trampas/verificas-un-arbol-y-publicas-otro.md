---
tipo: trampa
estado: vigente
fecha: 2026-08-18
areas: [proceso, worktrees]
fuente: medido en la sesión del contrato de login, 2026-08-18, con las horas de `git log` de `main`
resumen: "El gate de cierre verifica en el paso 5 y publica en el 6; si entre medias otra sesión fusiona en el `main` del worktree principal, el push publica un árbol que nadie verificó"
---
# Verificas un árbol y publicas otro

El gate de cierre de frente (`AGENTS.md` §Publicación) se apoya en que el paso 5 —re-verificar
después de integrar— gobierne el paso 6 —publicar—. Eso solo se cumple si **la referencia que
publicas es la misma que verificaste**. En este repositorio hay varias sesiones trabajando a la vez,
y `main` está **checkouteado en el worktree principal**: cualquiera de ellas puede fusionar ahí
mientras tú verificas.

`git push origin main` no publica lo que mediste. Publica **a dónde apunta `main` en el instante del
push**.

## Lo medido (2026-08-18)

| Hora | Qué pasó |
|---|---|
| 10:15:32 | cierro mi merge en el `main` del worktree principal (`c25d9164`) |
| — | verifico ese árbol: suite estática, contrato de login y lint de la wiki, los tres `EXIT=0` |
| 10:15:35 | **otra sesión** commitea `86fdca41` (docker-compose) en ese mismo `main` |
| 10:15:58 | esa sesión fusiona su rama: `cdad1e08` |
| ~10:16 | mi `git push origin main` publica `7f198a83..cdad1e08` |

Se publicaron dos commits ajenos —`docker-compose.yml`, `docker-compose.override.yml`, `CLAUDE.md`—
que **mi verificación nunca tocó**. Los revisé después y estaban verdes, pero eso es suerte, no
procedimiento: la verificación no gobernó la publicación.

**No es [[worktree-compartido-arrastra-commits]]**, que trata de cambios *sin commitear* barridos por
un `git add` ajeno. Aquí todo estaba commiteado y `git status` salió limpio en cada paso. Lo que se
movió fue la **rama**, no el árbol de trabajo, y por eso ninguna comprobación de limpieza lo detecta.

## Why

Es la misma familia que [[el-codigo-de-salida-se-pierde-en-la-tuberia]]: el gate sigue existiendo y
deja de gobernar, y el síntoma es un verde. Ahí lo que se perdía era el código de salida; aquí, la
identidad del árbol. Y encaja con [[un-verde-solo-vale-para-el-arbol-donde-se-midio]]: un verde vale
para un commit concreto, no para una rama.

Publicar trabajo ajeno sin verificar es además una forma silenciosa de pisar a otra sesión: si su
trabajo estaba a medias, el gate que debía protegerlo lo publica en su nombre.

## How to apply

- **No fusiones en el `main` del worktree principal.** Trabaja en el tuyo: `git fetch origin`,
  `git merge origin/main`, verifica, y publica **desde tu worktree** con
  `git push origin HEAD:main`. Eso publica exactamente el commit que mediste.
- **Si otra sesión publicó entre medias, el push se rechaza** por no ser fast-forward. El rechazo es
  el guardarraíl funcionando: repite integrar → verificar → publicar, como ya manda el gate.
- **Anota el SHA que verificas** (`git rev-parse HEAD`) y compáralo con el que vas a publicar. Si no
  coinciden, no has verificado lo que estás publicando.
- Vale la pena aunque trabajes solo: cuesta un comando y elimina la ventana entera.

Relacionado: [[worktree-compartido-arrastra-commits]],
[[el-codigo-de-salida-se-pierde-en-la-tuberia]], [[un-verde-solo-vale-para-el-arbol-donde-se-midio]],
[[branch-preexisting-red-gates]].
