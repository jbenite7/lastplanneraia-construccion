---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-08-25
areas: [proceso]
fuente: "Integración de dos sesiones el 2026-08-25: seis specs derogadas quedaron clasificadas `cerrado` porque el estado se dedujo de la presencia de `## Cierre`"
resumen: "Deducir `estado: cerrado` de que exista una sección `## Cierre` clasifica como trabajo hecho lo que ese cierre declara derogado — y `cerrado` es una afirmación fuerte"
---
# `cerrado` no es lo mismo que `derogada`, y la sección `## Cierre` no distingue

**El síntoma.** Una spec dice `estado: cerrado` en su frontmatter, y su cuerpo empieza con
**«DEROGADA. Su trabajo no se hizo por esta vía»**. Las dos cosas a la vez, en el mismo archivo.

**Lo que parece.** Que el trabajo se hizo. `cerrado` es la palabra que usa este repo para «terminado
con evidencia», y quien lea el índice sin abrir el documento no tiene motivo para dudarlo.

**Lo que es.** El estado se dedujo de que **existiera** la sección `## Cierre`, sin leer qué dice
esa sección. Y un cierre tiene al menos dos desenlaces distintos:

| Desenlace | Qué afirma | Ejemplo |
|---|---|---|
| `cerrado` | El trabajo **se hizo**, con evidencia | `espacio-cuenta-siteground`: cuatro frentes resueltos |
| `derogada` | El trabajo **no se hizo por esta vía** y cambió de dueño | `cierre-dark-mode`: sustituida por el programa DS |

**Medido el 2026-08-25:** seis specs derogadas horas antes —`ui-audit-and-repair-plan`,
`ui-audit-core-lps-ops`, `cierre-dark-mode`, `f2a-piloto-movil-programacion`,
`reapertura-movil-y-tema-claro`, `programa-cierre-pendientes`— salieron clasificadas `cerrado`. El
recuento pasó de `105 · 19 · 3` a **`99 · 19 · 9`** al leer los cuerpos.

**Cómo se sale.** Al clasificar, leer el cierre, no contarlo. Un `grep` que sirve:

```bash
grep -l '^estado: cerrado' docs/superpowers/specs/*.md \
  | xargs grep -l '\*\*DEROGADA' 
```

Si devuelve algo, hay documentos afirmando dos cosas contrarias sobre sí mismos.

**Cuánto costó.** Nada todavía, porque se cruzó al integrar. Lo que habría costado es lo que hace
que valga la pena la ficha: `derogada` existe precisamente para que **nadie vuelva a buscarle dueño
a un trabajo que ya lo tiene en otro sitio**. Una spec derogada leída como «cerrada» desaparece del
radar sin que sus traslados se revisen; leída como «parcial» reaparece como tarea de alguien. Las
tres lecturas llevan a sitios distintos.

**Es la tercera vez que la misma huella aparece en este repo**, y las tres en la misma semana: el
`tipo` de una fuente deducido de su ruta ([[el-tipo-de-una-fuente-lo-dedujo-un-script]]), el guard
que valida su propia declaración ([[guard-valida-declaracion-contra-si-misma]]), y ahora el estado
deducido de la presencia de una sección. **El patrón común no es el descuido: es que clasificar por
la forma es barato y leer el contenido es caro**, así que la forma gana siempre que nadie ponga la
regla por escrito.

Relacionadas: [[el-tipo-de-una-fuente-lo-dedujo-un-script]] ·
[[guard-valida-declaracion-contra-si-misma]] · [[el-trabajo-hecho-no-vuelve-solo-al-documento]] ·
[[el-goal-cierra-un-alcance-menor-que-el-del-plan]]
