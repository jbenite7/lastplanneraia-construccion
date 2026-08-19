---
capa: wiki
tipo: trampa
estado: vigente
fecha: {{date:YYYY-MM-DD}}
areas: []
tags: [plantilla, trampa]
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

<!-- Una trampa se escribe cuando ya costó tiempo, no cuando se sospecha. Si no puedes citar la
     medida o el síntoma real, todavía no es una trampa: es una corazonada. -->

**El síntoma.** Qué se ve cuando caes: el mensaje exacto, la salida, la pantalla. Literal, para que
alguien que lo tenga delante encuentre esta página buscándolo.

**Lo que parece.** La explicación obvia y equivocada. Es la mitad que hace que la trampa sea una
trampa.

**Lo que es.** La causa real, con archivo y línea o con el comando que la demuestra.

**Cómo se sale.** El arreglo, concreto.

**Cuánto costó.** La fecha y el tiempo que se perdió. Es lo que hace que la próxima persona la lea
en vez de saltársela.

Relacionadas: [[ ]]
