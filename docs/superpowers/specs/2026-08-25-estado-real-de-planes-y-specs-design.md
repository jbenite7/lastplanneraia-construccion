---
capa: fuente
tipo: spec
estado: vigente
fecha: 2026-08-25
areas: [proceso]
fuente: docs/superpowers/specs/2026-08-25-estado-real-de-planes-y-specs-design.md
resumen: "Los 127 planes y specs que siguen en `estado: vigente` no afirman nada: es el valor por defecto de un backfill. Cómo averiguar cuáles están cerrados de verdad sin fabricar estado"
project: lps-aia
---

# El estado real de los 127 planes y specs

**Fecha:** 2026-08-25
**Origen:** encargo de Felipe al cerrar la depuración documental del mismo día
(`ca608402`), que dejó este frente medido y explícitamente **sin ejecutar**.
**Qué NO sustituye:** ninguna spec anterior. Es el remate del hallazgo de
[[docs/superpowers/specs/2026-08-24-estado-consolidado-del-repo-design|la spec del estado
consolidado]] aplicado a `docs/superpowers/`.

---

## 1. El problema, medido

`docs/superpowers/` guarda 140 documentos. Su `estado` dice esto:

| | specs | plans | total |
|---|---|---|---|
| `cerrado` | 11 | 2 | **13** |
| `vigente` | 58 | 69 | **127** |
| `derogada` | 0 | 0 | 0 |

Los 13 se sellaron el 2026-08-25 aplicando la regla del repo —cerrado es tener `## Cierre`
**con contenido**—, que hasta entonces solo se usaba en `goals/`.

**Los 127 restantes no dicen «este trabajo sigue abierto». No dicen nada.** `vigente` es el
valor por defecto del backfill que puso el frontmatter de la capa de fuentes, el mismo que tipó
90 documentos como `guia` sin que nadie los clasificara — ver
[[memoria/trampas/el-tipo-de-una-fuente-lo-dedujo-un-script]]. La huella es idéntica: el campo
existe, luce como dato y nadie lo decidió.

**Por qué importa y no es cosmético.** Alguien que entra al repo y quiere saber qué está en
curso encuentra 127 documentos que se declaran vigentes, de los cuales una mayoría desconocida
describe trabajo terminado hace semanas y en producción. Es la misma clase de defecto que
`AGENTS.md` §Verificación ya nombra para las casillas: **un recuento que reporta lo contrario de
la realidad**. Y sale caro en cuotas, no de golpe — cada sesión que arranca paga el peaje de
reconstruirlo.

## 2. La trampa que este frente NO puede repetir

El reflejo obvio es automatizarlo: cruzar slugs, mirar fechas, deducir. **Ese reflejo es
exactamente el error que se acaba de corregir.** Un script que decide el `estado` de 127
documentos produce otro metadato que parece un dato y no lo es — y esta vez con la agravante de
que `cerrado` sí es una afirmación fuerte, no un cajón de sastre.

La regla de `AGENTS.md` no admite lectura blanda: **el avance se lee del código y del historial
de git, nunca de las casillas ni de la antigüedad.** Y el precedente del 2026-08-07 lo cierra:
marcar cierres sin haber presenciado cada paso «sería fabricar evidencia».

De ahí la restricción que gobierna todo el plan: **una señal automática puede decidir a quién
mirar, nunca qué escribir.**

## 3. Las tres señales, con su rendimiento medido

Medidas sobre los 127 el 2026-08-25:

| Señal | Cubre | Qué vale |
|---|---|---|
| Existe `goals/<slug>/goal.md` con `## Cierre` | **33** | Fuerte, **pero sin validar**: el slug puede coincidir y el alcance no |
| Slug citado en `CHANGELOG.md` | 15 (solapa con la anterior) | Indicio, no prueba: el changelog registra producto liberado |
| Existe con goal **abierto** | 2 | Se quedan `vigente`, y ahora sí afirmándolo |
| **Sin ninguna señal** | **92** | Verificación contra el código, una por una |

**El 92 es el dato que dimensiona el frente**, y es la razón de que Felipe lo mandara a sesión
propia en vez de rematarlo en la anterior: 92 verificaciones independientes contra el código no
caben como apéndice de otra tarea sin degenerar en adivinación.

## 4. Qué se decide y qué no

**Se decide:** el `estado` de cada uno de los 127 — `cerrado` con evidencia citada, `vigente`
si el trabajo sigue vivo, o `derogada` si la decisión que contiene dejó de ser cierta.

**No se decide, y conviene decirlo:** no se archiva nada a `docs/archive/`. Archivar tiene su
propio criterio —terminado **y** que ningún archivo lo cite— y mezclarlo aquí convertiría un
frente acotado en dos. Queda para después, y entonces será barato porque el estado ya será
fiable.

**Tampoco se tocan las casillas `- [ ]`.** Siguen sin medir nada; marcarlas retroactivamente es
la falta que `AGENTS.md` nombra por su nombre.

## 5. Condición de hecho

Ningún documento de `docs/superpowers/` en `estado: vigente` sin que ese valor sea una
afirmación deliberada; cada `cerrado` con su evidencia citada en el propio documento; y
`npm run test:wiki` sin hallazgos.
