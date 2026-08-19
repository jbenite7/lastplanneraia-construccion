---
capa: fuente
tipo: spec
estado: cerrado
fecha: 2026-08-03
areas: [proceso]
tags: [archivo]
fuente: docs/archive/superpowers/specs/2026-08-03-wiki-veracidad-y-grafo-design.md
resumen: La wiki memoria/ funciona: 78 páginas, lint en verde, bitácora que demuestra uso real. El diagnóstico de esta sesión encontró tres huecos, ninguno estructural…
---

# Cierre de los tres pendientes de la wiki `memoria/`

**Fecha:** 2026-08-03
**Estado:** aprobado en brainstorming, pendiente de plan
**Áreas:** proceso, arquitectura

## Problema

La wiki `memoria/` funciona: 78 páginas, lint en verde, bitácora que demuestra uso real. El
diagnóstico de esta sesión encontró tres huecos, ninguno estructural, los tres de mantenimiento.

1. **La veracidad depende de disciplina.** `scripts/wiki-lint.mjs` comprueba la *forma* (enlaces,
   frontmatter, áreas, orfandad). Que una nota siga siendo cierta contra el código de hoy solo se
   averigua leyendo el repositorio, y eso hoy ocurre porque las sesiones se acuerdan. Un sprint
   cerrado con prisa deja entrar deriva sin que nada avise.
2. **~109 nodos desconectados del grafo** (156 de 265 al último recuento). Mucho es `docs/`
   histórico que quizá no merece enlace, pero no está medido ni declarado.
3. **Bus factor del esquema.** Las tres operaciones viven en prosa de `CLAUDE.md`, que un
   colaborador humano nuevo no mira primero.

## Diseño

### 1. Cuarta operación: `veracidad`

Se añade a `ingest`, `query` y `lint` una operación hermana con el mismo formato de bitácora:

```
- 2026-08-03 · veracidad · áreas revisadas: design-system, rbac · 14 páginas · 2 corregidas, 1 derogada · [[design-system]], [[rbac-y-rutas]]
```

Es deliberadamente distinta de `lint`: `lint` comprueba la forma y lo hace un script; `veracidad`
comprueba que lo escrito siga siendo cierto contra el código, y lo hace un lector del repositorio
—delegable a un subagente barato— **con la exigencia de verificar cada afirmación, no de
sospecharla**. Una nota desmentida se corrige y se marca `estado: derogada`; no se borra.

**Alcance de cada pase — rotación por área.** Un pase toma:

- las áreas cuyo código cambió desde la fecha del pase anterior, leídas de `git log`;
- más las páginas más antiguas sin revisar, hasta un tope, para que un área quieta no quede
  eternamente sin verificar.

Así el coste es acotado y proporcional al riesgo, y en dos o tres pases se recorre la wiki entera.

### 2. Alarma de veracidad en `wiki-lint.mjs`

El script gana una comprobación sobre `memoria/log.md`: localiza la última línea `veracidad`, toma
su fecha, y cuenta los commits posteriores que tocan **código o contratos**.

- **Rutas que cuentan:** `src/`, `admin/`, `public/`, `tests/`, `scripts/`, `docs/`, `AGENTS.md`.
- **Rutas que NO cuentan:** cualquier commit que toque exclusivamente `memoria/`, para que la
  propia wiki no dispare su alarma al escribirse.
- **Umbral: más de 40 commits → hallazgo, salida en rojo.**

El umbral se elige contra el ritmo real medido en este repo (101 commits el 2026-08-03; picos de
124 y 181 la semana del 28-29 de julio). Con 40, la alarma salta dos a cuatro veces en un día de
trabajo intenso y ninguna en días quietos. Un umbral menor la haría saltar tantas veces que se
ignoraría, que es peor que no tenerla.

El umbral vive en una constante nombrada junto a `AREAS`, al principio del script, para ajustarlo
en una línea cuando la práctica lo pida.

El mensaje del hallazgo incluye el número de commits y las áreas que tocaron, para que el pase
arranque sabiendo dónde mirar.

**Arranque.** Hoy no existe ninguna línea `veracidad` en el log. Mientras no exista, el script
informa sin salir en rojo: nacer fallando entrenaría a ignorar el rojo desde el primer día. El
primer pase de veracidad siembra la línea y a partir de ahí la comprobación es normal.

### 3. Auditoría del grafo

Los nodos sin enlaces se clasifican en tres cubos:

- **vigente** — se teje desde el mapa de su área con un wikilink que aporte navegación real;
- **histórico** — se deja suelto **a propósito**; `memoria/index.md` gana un párrafo corto que dice
  cuántos son y por qué. Un grafo honesto vale más que uno lleno de enlaces de relleno;
- **dudoso** — se lista al usuario para que decida; no se teje de oficio.

Solo se tejen los vigentes. No se edita el contenido de `docs/`: los enlaces añadidos sustituyen
menciones por ruta que ya existen, igual que en el pase del 2026-08-02.

La clasificación es delegable a un subagente de bajo coste; el tejido lo hace el coordinador.

### 4. `docs/wiki-operacion.md`

Manual de operación en la **capa de fuentes** —versionado, editable por humanos, encontrable por
alguien que no lea `CLAUDE.md`—. Contiene:

- las cuatro operaciones (`ingest`, `query`, `lint`, `veracidad`) con su cuándo, su procedimiento y
  su línea de bitácora;
- el frontmatter obligatorio y qué significa cada campo, con la regla de corregir el `resumen` junto
  al cuerpo;
- la lista cerrada de trece áreas y cómo se amplía;
- los dos scripts (`wiki-lint.mjs`, `wiki-arquitectura.mjs`) con sus comandos y qué comprueba cada
  uno —y, explícitamente, qué **no** comprueba el lint;
- la regla de precedencia código > `AGENTS.md` > `memoria/` y el derogar-en-vez-de-borrar.

`CLAUDE.md` reduce su sección «Memoria del proyecto» a un resumen de un párrafo más el puntero al
manual: el archivo adelgaza en vez de engordar. `memoria/index.md` enlaza el manual desde la
sección de operaciones, que pasa de tres a cuatro.

## Fuera de alcance

- No se toca el contenido de `docs/` (solo se añaden enlaces donde ya había menciones por ruta).
- No se crea cron ni infraestructura fuera del repositorio: la alarma viaja en el propio lint.
- No se reescriben notas de oficio; una nota falsa se corrige y se marca `derogada`.
- No se cambia el esquema de frontmatter ni la lista de áreas.

## Condición de hecho

1. `node scripts/wiki-lint.mjs` corre en verde y su salida menciona el estado de veracidad
   (informativo mientras no haya línea sembrada).
2. Existe al menos una línea `veracidad` en `memoria/log.md`, producto de un pase real con
   verificación contra el código, no sembrada a mano.
3. Los nodos desconectados están clasificados; los vigentes tejidos; el recuento de históricos
   declarado en `memoria/index.md` con su porqué.
4. `docs/wiki-operacion.md` existe y describe las cuatro operaciones; `CLAUDE.md` apunta a él y su
   sección de memoria es más corta que antes.
5. Una relectura comprueba que el lint sigue sin corregir nada por su cuenta: comprueba y reporta.

## Riesgos conocidos

- **El umbral de 40 es una apuesta**, no una medición de lo que cuesta la deriva. Si la alarma
  resulta ruidosa o laxa, se ajusta la constante y se deja línea en el log explicando por qué.
- **Un pase de veracidad puede volverse ceremonial** si se ejecuta como trámite en vez de verificar
  contra el código. El manual lo dice explícitamente, pero ninguna comprobación automática puede
  distinguir un pase real de uno declarado.
- **Contar commits desde una fecha** es aproximado si varias sesiones trabajan en paralelo en
  worktrees distintos; el conteo se hace sobre la rama actual y eso queda dicho en el manual.
