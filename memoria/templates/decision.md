---
capa: wiki
tipo: decision
estado: vigente
fecha: {{date:YYYY-MM-DD}}
areas: []
tags: [plantilla]
fuente:
resumen:
---

<!-- `areas` y `tags` son LISTAS CERRADAS: el lint rechaza cualquier valor fuera de ellas.
     areas: design-system · qa · docker · worktrees · pdc · lps · datos · rbac · deploy ·
            bi · admin · proceso · arquitectura
     tags:  dashboard · plantilla · pendiente · trampa · leer-antes-de-tocar · generado ·
            archivo   — y nunca duplican `tipo` ni `estado`.
     La lista viva está en `scripts/wiki-esquema.mjs`; el porqué, en `docs/wiki-operacion.md`. -->

# {{title}}

<!-- Una nota, un hecho. Si necesitas «además» dos veces, son dos decisiones. -->

Qué se decidió, en una frase que se sostenga sola.

**Cuándo y quién.** Fecha del hecho, no de la escritura, y de dónde sale (sesión, commit, hilo).

**Por qué así.** El problema real que había delante. Si la decisión no resuelve un problema que
puedas nombrar, probablemente no es una decisión: es una preferencia.

**Qué se descartó y por qué.** La alternativa que parecía razonable y la razón concreta por la que
no. Sin esto, la nota no evita que alguien vuelva a proponerla dentro de tres meses.

**Qué la desmentiría.** El hecho que, si aparece, obliga a derogar esta nota. Escríbelo ahora, que
es cuando se sabe.

Relacionadas: [[ ]]
