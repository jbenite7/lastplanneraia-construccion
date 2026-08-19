---
capa: fuente
tipo: plan
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

# Plan — {{title}}

<!-- Un plan describe; no autoriza. Cambiar lo que una prueba mide, tocar un contrato o un
     baseline, o borrar algo, se escala aunque el plan lo pida. -->

**Spec:** `docs/superpowers/specs/YYYY-MM-DD-slug.md` · **Estado:** al gate, sin ejecutar.
**Esfuerzo estimado:** N tandas; cada tanda cierra en verde antes de la siguiente.

## Fase 1 — <nombre corto de lo que deja hecho>

1. Paso concreto, con la ruta del archivo que toca.
2. Otro.
- **Verifica:** el comando exacto y qué salida cuenta como verde. Si no hay comando, dilo: `?` es
  ignorancia y se rellena antes de empezar, no un valor que se pueda dejar puesto.

## Fase 2 — …

## Riesgos y reversas

Qué puede salir mal en cada fase y cómo se deshace. Una fase sin reversa escrita es una fase que
solo se puede terminar hacia adelante.
