---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-08-04
areas: [design-system]
fuente: docs/design-system/version.json, docs/design-system/CHANGELOG.md, commit 58b850e7
resumen: "Trampa histórica del 16 de julio al 2026-08-04: el CHANGELOG encabezaba «0.3.6 - En construcción» mientras version.json ya decía 1.0.0 stable. Corregido; la versión viva hoy es 1.1.0 (2026-08-07)"
---
# El changelog del design system encabeza una versión que ya se activó

**Nota histórica (corregida el 2026-08-10):** el cuerpo de abajo describe el estado entre el 16 de
julio y el 2026-08-04, cuando la versión viva era `1.0.0`. **Hoy la versión viva es `1.1.0`**
(desde el 2026-08-07, ver [[goals/cierre-version-1-1-0-design-system/goal|cierre-version-1-1-0-design-system]]) y el CHANGELOG está sincronizado:
`docs/design-system/CHANGELOG.md:3` encabeza `## 1.1.0 - 2026-08-07`. La trampa de fondo —confiar
en el encabezado del changelog para saber la versión, en vez de leer `version.json`— sigue siendo
válida como lección; el relato en presente que sigue es de un momento anterior.

**La versión viva era `1.0.0`** (histórico), y la fuente que manda es `docs/design-system/version.json`:

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
