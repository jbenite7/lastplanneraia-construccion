---
capa: fuente
tipo: spec
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

<!-- Fuente, no wiki: esto lo escribe una persona y la wiki solo lo lee. Nombre del archivo:
     docs/superpowers/specs/YYYY-MM-DD-slug.md — la fecha del nombre es la que lee el backfill. -->

**Fecha:** {{date:YYYY-MM-DD}} · **Decisión del usuario:** qué pidió, en sus términos.

## Qué se conserva

Lo que este cambio NO toca. Va primero a propósito: delimita antes de proponer.

## Qué cambia

Un apartado por pieza, con el porqué al lado. Las decisiones de grilleo que fijaron el diseño se
citan aquí, no se esconden en el plan.

## Condición de hecho global

Qué tiene que ser cierto para dar esto por terminado, con el comando que lo demuestra. La ejecución
por tandas y sus verificaciones, en el plan hermano.
