---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-08-25
areas: [proceso]
tags: [leer-antes-de-tocar]
fuente: "depuración documental del 2026-08-25; `scripts/wiki-frontmatter.reglas.mjs`, `docs/wiki-operacion.md` §tipos"
resumen: "El frontmatter de `docs/` lo puso un backfill que deduce el `tipo` desde la ruta, así que `tipo: guia` es su valor por defecto y no una afirmación de nadie"
---

# El `tipo` de una fuente lo dedujo un script, no una persona

**El síntoma.** Abres un documento de `docs/` para saber si manda o solo cuenta lo que pasó, miras
su `tipo` y dice `guia`. Lo lees como instrucción vigente. También lo lee así la alarma de
veracidad, que cuenta `contrato · guia · biblia` como autoridad
(`docs/wiki-operacion.md` §«Mandan»). Pero el documento es una auditoría cerrada de julio.

**Lo que parece.** Que alguien clasificó ese documento y decidió que manda.

**Lo que es.** Nadie lo clasificó. El frontmatter de la capa de fuentes se puso en un **backfill**,
y `scripts/wiki-frontmatter.reglas.mjs` **deduce el `tipo` desde la ruta** — la línea 58 mapea
`evidence/`, `runtime-measurements/` y `manifests/` a `evidencia`, y lo que no cae en ninguna regla
aterriza en `guia`, que es el cajón de sastre («el grueso de `docs/`», dice el catálogo de tipos).

**La huella que lo delata, y es barata de comprobar:** un documento tipado a mano trae `resumen`;
uno tipado por el backfill, no. Medido el 2026-08-25: de las **90** páginas `tipo: guia`, **89 no
tenían `resumen`**. La única que lo tenía —`docs/coordinacion-sesiones.md`— era la única escrita a
mano, y era de verdad una guía.

```bash
# ¿este tipo lo decidió alguien, o cayó por defecto?
awk '/^tipo:/{t=$2} /^resumen:/{r=1} END{print FILENAME, t, r?"a mano":"BACKFILL"}' <archivo>
```

**Cómo se sale.** No confíes en `tipo` para decidir si un documento manda: mira qué es. Los cuatro
grupos, con el criterio que los separa — **manda** si describe cómo se hace algo *hoy* (`guia`),
qué es el dominio (`biblia`) o qué es obligatorio (`contrato`); **no manda** si está fechado y
cuenta qué pasó (`reporte`), qué se va a hacer (`spec`) o en qué orden (`plan`).

Corregido el 2026-08-25: **72 documentos reclasificados, de 90 «guías» a 18.** Entre lo que se
leía como instrucción vigente estaban las 16 páginas de `docs/design-system/auditoria/`,
`DESIGN-AUDIT.md`, `MIGRATION_FINDINGS.md` y los cinco barridos del 2026-08-03 — todos reportes
fechados.

**Cuánto costó.** No costó una sesión de golpe: costó peor, en cuotas. Un tipado que no afirma nada
no falla nunca de forma visible — degrada las búsquedas del catálogo, infla la alarma de veracidad
y hace que un agente lea una auditoría cerrada como si fuera el procedimiento. El caso concreto que
lo destapó: `docs/pdc-v2.md`, `tipo: guia` y de lectura obligatoria por `CLAUDE.md:304`, llevaba
desde la mudanza del 2026-08-18 mandando a un disco que ya no existía y afirmando que el contenedor
monta el worktree cuando monta la raíz.

**La regla que sobrevive al caso.** Un metadato que un script rellena por defecto **parece** un
dato y **no** lo es. La wiki ya conocía la mitad de esto —`docs/wiki-operacion.md` admite que la
regla «falla hacia el ruido» porque un documento mal tipado se silenciaría a sí mismo—, pero solo
miraba el falso negativo. El falso positivo es el que estaba haciendo daño: **cientos de documentos
declarándose con autoridad que nadie les dio.**

Relacionadas: [[wiki-operacion]], [[el-archivo-que-tocas-puede-tener-un-contrato]],
[[el-trabajo-hecho-no-vuelve-solo-al-documento]]
