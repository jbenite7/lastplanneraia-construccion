---
tipo: trampa
estado: vigente
fecha: 2026-08-04
areas: [design-system]
fuente: docs/design-system/version.json, docs/design-system/CHANGELOG.md, commit 58b850e7
resumen: "La versión viva del design system es 1.0.0 stable en version.json; el CHANGELOG encabeza «0.3.6 - En construcción» desde el 16 de julio y engaña a quien lo lea"
---
# El changelog del design system encabeza una versión que ya se activó

**La versión viva es `1.0.0`**, y la fuente que manda es `docs/design-system/version.json`:

```json
{ "version": "1.0.0", "status": "stable", "pilot": "/programa-general" }
```

Lo confirman `docs/design-system/stable-api-1.0.0.json` (`designSystemVersion: "1.0.0"`) y
[[DESIGN]] (`:220`, «Design System v1.0.0»).

**Pero `docs/design-system/CHANGELOG.md:3` titula «0.3.6 - En construcción»**, y su cuerpo afirma
que «la versión permanece `0.3.6 / construction` hasta que todos los gates de cierre estén
aprobados y se cree el commit de release». Leído de buena fe, dice que el sistema sigue en
construcción.

**Ese commit de release existe y está fechado.** `58b850e7 docs(design-system): activate closeout
and align test`, del 16 de julio de 2026, cambió `version.json` de `0.3.6 / construction` a
`1.0.0 / stable` en la misma pasada que actualizó `closeout-evidence.json` y
`stable-api-1.0.0.json`. La activación ocurrió; lo que se quedó atrás fue el encabezado del ciclo
en el changelog, que describe correctamente **cómo se llegó** a 1.0.0 pero se sigue anunciando como
trabajo en curso.

**Cómo no caer.** Para saber en qué versión está el design system, lee `version.json`, nunca el
encabezado del changelog. El changelog sirve para saber **qué entró** en cada ciclo, no en cuál
estás.

El encabezado se corrigió el 2026-08-04 por decisión del usuario, citando el commit de activación y
sin tocar el resto del documento. Esta nota se queda porque la trampa de fondo —confundir el
encabezado de un changelog con el estado del sistema— sobrevive a esa corrección.

Vecinas: [[comentario-de-token-afirma-uso-inexistente]] y
[[guard-valida-declaracion-contra-si-misma]], las otras dos veces que una fuente del design system
afirmaba algo que el repositorio no respaldaba. Mapa del área: [[design-system]].
