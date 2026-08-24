---
capa: fuente
tipo: spec
estado: vigente
fecha: 2026-08-24
areas: [design-system, bi, ci, accesibilidad]
resumen: "Cierre de cuatro pendientes diferibles que dejo el frente de tablas: fugas de tipografia, color de rol en BI, las listas de SQL de CI y el gemelo callado del filtro de cabecera"
project: lps-aia
type: design
status: aprobado-en-chat
updated: 2026-08-24
---

# Pendientes del frente de tablas

## Objetivo

Cerrar cuatro de los cinco pendientes que `TASKS.md` §Diferibles dejo anotados el 2026-08-24,
dejando cada uno **hecho y verificado** o **devuelto a la lista con lo aprendido**. Ninguno queda
a medias.

## Alcance, decidido por Felipe en chat

Entran: las dos fugas de tipografia en las tablas, el color de rol en los anillos de BI, las
cuatro listas de SQL de CI (rediseno incluido) y el gemelo callado del filtro de cabecera.

**Queda fuera, y no por olvido:**

- **Retirar DataTables.** `ROADMAP.md` lo declara decision de rumbo sin frente ni fecha, a
  proposito: «quien entre a una de esas pantallas por otra razon, sale con AG Grid». Convertirlo
  en frente con fecha cambia esa decision, y Felipe la mantuvo.
- **La baseline de presupuesto de runtime.** Tiene sesion propia corriendo desde el 2026-08-24.
- **La licencia de Handsontable.** Decidida en `decisiones/licencia-handsontable-uso-interno.md`.

## Decisiones tomadas en el grilleo

### D-1 · Los anillos de BI usan colores de dato, no tokens nuevos

`ControlTowerService.php:2895-2908` devuelve hoy `status-critical` / `status-warning` /
`status-success` como color de **relleno** de dos donas: el anillo de «Avance fisico» y el de
«Cumplimiento cronograma». Esos tokens resuelven a `--ds-color-state-*-text`
(`bi-control-tower.css:133-135`), que es tinta pensada para leerse **sobre** un fondo, no para
pintar un area. En el sistema no existe hoy un token de estado hecho para rellenar dato.

**Decision de Felipe:** mapear cada estado a un color de la paleta de datos que ya existe. No se
crean tokens de relleno de estado en este frente.

**Por que no se crean:** `DS-F1` esta abierto en `TASKS.md` §Ahora y es el frente que define
tokens y escala de severidad. Que dos frentes escriban el mismo contrato por separado se paga
despues. El hallazgo se anota para DS-F1.

**Correccion al pendiente original:** `TASKS.md` senalaba `bi-spa.js:3704`. Esa linea es el
*fallback* que solo corre si el servidor no manda color, y el servidor siempre lo manda. La causa
esta en el PHP. La linea se corrige tambien, pero como lo que es.

### D-2 · Un frente puede crear tokens sin significado; los que cargan significado son de DS-F1

La frontera no es «infraestructura contra semantica». Es: **¿alguien podria estar en desacuerdo
con el valor?** Si si, es de DS-F1. Si no, es plomeria y va donde se necesite.

Nadie va a debatir cual es la fuente de iconos, asi que `--ds-font-icon` entra en este frente.
Que color significa «inaceptable» si es discutible, asi que se queda en DS-F1.

**Consecuencia:** sin `--ds-font-icon` la tarea 1 solo podria cambiar `monospace` y dejar abierta
la fuga del icono — medio pendiente cerrado, que es el que se olvida.

## Las cuatro tareas

### T1 · Las dos fugas de tipografia

- `public/css/handsontable-module.css:579` — `font-family: monospace` pasa a
  `var(--ds-font-mono)`, que ya existe en `public/css/tokens.css:135`.
- Se crea `--ds-font-icon` en `tokens.css` con la familia de Font Awesome.
- Se aplica en los **dos** sitios que la llaman a mano, no solo en el anotado:
  `public/css/handsontable-header-global.css:167` y
  `public/css/design-system/components/table-filter-trigger.css:72`. Dejar uno mantiene la fuga
  abierta y la proxima auditoria vuelve a medirla.

**Limite explicito:** en `table-filter-trigger.css` se cambia la familia y nada mas. Es archivo
del design system con contratos ejecutables encima.

**Verificado ya:** ninguno de los tres CSS esta fijado por hash. `handsontable-module.css` vive
bajo el presupuesto `foundation-shell` de `docs/design-system/exceptions.json:341`, y el cambio no
anade hex ni violaciones. `unlayered-delivery-inventory.json` compara el **conjunto** de hojas, no
su contenido.

### T2 · El color de los anillos de BI

Se corrige en `src/Services/ControlTowerService.php`: `semanticMetricRange()` y
`schedulePerformanceRange()` dejan de devolver `status-*` como `color_token` de relleno y
devuelven colores de la paleta de datos. Se corrige tambien el fallback de
`public/js/modules/bi-spa.js:3704`.

Los dos anillos se miran en pantalla antes y despues. El verde corporativo es oscuro sobre fondo
dark: si en el anillo se lee pesado, se dice y se decide, no se deja pasar.

### T3 · Las listas de SQL de CI

Son cuatro listas con **tres roles distintos**, no cuatro copias de lo mismo:

| Lista | Rol | Que se hace |
|---|---|---|
| `database/fixtures/design-system-ci.Dockerfile` | la verdad ejecutable | nada; es lo vigilado |
| `EXPECTED_INIT_COPIES` en `scripts/design-system-ci-preflight.mjs` | el guardarrail fail-closed | **intacta** |
| `INIT_COPIES` en `tests/design-system/ci-preflight.test.mjs` | copia para armar un Dockerfile sintetico | **se deriva** de la anterior |
| nombres destino en `tests/design-system/visual-ci-contract.test.mjs` | segundo testigo independiente | **se conserva duplicada** |

La tercera solo existe para probar el validador, y ya tiene su propio antidoto en la linea 232 de
ese archivo («the real db init Dockerfile satisfies the allowlist it is built from»). La cuarta
vale precisamente por no depender del guardarrail.

Quedan tres listas con roles distintos en vez de cuatro que fingen ser la misma. Los comentarios
pasan a decir la verdad: el de `ci-preflight.test.mjs:28` habla de «las dos listas» y ya son
cuatro.

**Lo que NO se hace, y por que:** no se unifican las cuatro en una sola fuente derivada, aunque
sea lo que pedia el pendiente. Esa redundancia es lo que rechazo el cambio **tres veces seguidas**
al sembrar `general_flags` el 2026-08-24, una por lista. Colapsarlas todas cambia un guardarrail
que ya demostro que funciona a cambio de comodidad al editar.

### T4 · El gemelo callado del filtro de cabecera

**No es un cambio de una linea: es un debugging, y entra por `superpowers:systematic-debugging`.**

El codigo ya hace lo correcto. `markDecorativeHeaderTriggers`
(`public/js/modules/programa_general/hot.js:2415`) marca sin condicion, y
`observeDecorativeHeaderTriggers` vigila `childList` **y** el atributo `aria-hidden` sobre
`#hot-container`, que contiene el maestro y el clon. Sobre el papel los 24 quedan marcados. En
vivo quedan 12.

Existe entonces una via por la que Handsontable repone esos nodos sin que el observer se entere, y
**no se sabe cual es**. Se reproduce, se mide, se arregla donde este la causa.

**Si no se logra reproducir**, el pendiente vuelve a `TASKS.md` con lo aprendido. No se toca codigo
a ciegas: el comentario de esa misma funcion ya afirmaba haber cerrado el defecto que seguia
abierto, y repetir esa clase de arreglo es como se llego aqui.

## Verificacion

Se corre el gate que gobierna el archivo tocado, no el que este a mano. La leccion es del
2026-08-24: cambiar el Dockerfile de CI y verificar la wiki dejo pasar un fallo que cazo CI.

| Cambio | Gate |
|---|---|
| CSS y JS de tablas / `programa_general` | `npm run test:design-system:static` |
| PHP de BI | `vendor/bin/phpstan analyse src admin/src` y `run-php-tests.php --nivel=puro` |
| Listas de CI | `tests/design-system/ci-preflight.test.mjs` y `visual-ci-contract.test.mjs` |
| T2 y T4 | navegador, contenedor efimero en otro puerto |

**El presupuesto `hardcoded-hex` de `programacion-semanal` es 0 y el audit lee tambien los
comentarios**, de CSS y de JS. No se escribe ningun hex, ni dentro de un comentario explicativo.
Ya puso el gate en rojo dos veces.

## Dependencias y coordinacion

- **`.env` de este worktree.** Debe ser **archivo real, no enlace**: dentro del contenedor una
  ruta absoluta no resuelve, Dotenv no carga y `/dev/entrar` redirige a `/login` sin explicar por
  que. Sin el no hay navegador ni tests de linea de comandos, o sea T2 y T4 no se verifican. El
  harness deniega copiarlo; lo corre Felipe.
- **El contenedor `app` compartido no monta este arbol.** Medido en el paso 0 el 2026-08-24:
  monta `.claude/worktrees/validate-session-coordination-dca393`, o sea otra sesion lo reapunto y
  sigue trabajando ahi. **No se reapunta.** Para mirar en navegador, contenedor efimero en otro
  puerto (regla 5 de `docs/coordinacion-sesiones.md`).

## Condicion de hecho

1. T1, T2 y T3 ejecutadas y verificadas con salida real de comandos de esta sesion.
2. T4 arreglada y verificada, **o** devuelta a `TASKS.md` con la medicion de por que no se
   reprodujo.
3. Lo que no se hizo, anotado en `TASKS.md` con su porque — incluido el hallazgo de los tokens de
   relleno de estado, dirigido a DS-F1.
4. Publicado en `main` con `bash scripts/publicar.sh` (el del repo: `--solo-verificar` y
   `--con-merges`; **no** acepta `-v/-p/-m`).
