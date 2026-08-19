# Sembrar los conceptos del design system en la wiki

**Fecha:** 2026-08-04
**Estado:** aprobado en brainstorming, pendiente de plan
**Áreas:** design-system, proceso

## Problema

El design system es el área **mejor sembrada** de la wiki `memoria/`: 27 páginas, más que
ninguna otra. Pero la composición está escorada, medida el 2026-08-04:

| Tipo | Páginas |
|---|---|
| `trampa` | 21 |
| `decision` | 4 |
| `mapa` | 1 |
| `referencia` | 1 |
| `concepto` | **0** |

En toda la wiki no existe **ninguna** página `tipo: concepto`, pese a que
`scripts/wiki-lint.mjs:13` lo admite como tipo válido. La consecuencia práctica: la wiki dice muy
bien **dónde te vas a tropezar** y casi nada de **cómo funciona el sistema y por qué**. Quien llega
nuevo aprende las veintiuna minas antes que el mapa del terreno.

Contrastando las fuentes contra la wiki (ignorando los `.schema.json` y las carpetas de artefactos
de corrida), **18 archivos de `docs/design-system/` no aparecen en la wiki ni una sola vez**. El
patrón no es aleatorio: son casi todos los artefactos de **gobierno** que los gates consumen. La
wiki registra que «el gate estático no ve tokens rotos», pero no explica qué es una baseline, qué
es `homologation.json` ni qué garantiza la API estable.

### Hallazgo colateral: la versión diverge entre fuentes

Buscando lo anterior apareció una contradicción **entre las propias fuentes**, que la wiki no
registra:

| Fuente | Dice |
|---|---|
| `docs/design-system/version.json` | `"version": "1.0.0", "status": "stable"` |
| `docs/design-system/stable-api-1.0.0.json` | `designSystemVersion: 1.0.0` |
| `DESIGN.md:220` | «Design System v1.0.0» |
| `docs/design-system/CHANGELOG.md:3` | «0.3.6 - En construcción» |

El changelog además afirma que «la versión permanece `0.3.6 / construction` hasta que todos los
gates de cierre estén aprobados y se cree el commit de release». Ese commit **existe**:
`58b850e7 docs(design-system): activate closeout and align test` es el que subió `version.json` a
1.0.0. Es decir, la activación ocurrió y el encabezado del changelog se quedó titulando el ciclo
anterior.

Agrava el problema que `memoria/mapas/design-system.md` remite hoy al changelog para saber «la
versión en curso» — manda a leer justo la fuente que engaña. Ese error se introdujo el 2026-08-03
al tejer el grafo.

## Diseño

### 1. Siete páginas `tipo: concepto`

Cada página responde **una sola pregunta**: para qué existe ese artefacto y qué decisión permite
tomar. No replica su contenido —eso ya está en `docs/` y caducaría—, así que cabe en una pantalla,
respeta la regla «una nota, un hecho» y no compite con la fuente.

| Concepto | Fuentes que deja de dejar huérfanas |
|---|---|
| Las dos capas de tokens | `tokens.md`, `dark-palette.md` |
| Madurez de un componente y la API estable | `component-catalog.json`, `stable-api-1.0.0.json`, `version.json` |
| Baselines y presupuestos | `audit-baseline.json`, `a11y-baseline.json`, `lab-performance-baseline.json`, `lab-performance-budget.json`, `runtime-baseline-0.3.3.json`, `phpstan-baseline.json` |
| Excepciones registradas | `a11y-exceptions.json`, `state-token-exceptions.json`, `state-tint-exceptions.json`, `legacy-aliases.json` |
| Homologación y aprobación de familias | `homologation.json`, `family-approvals.json` |
| El manifiesto de un módulo | `manifests/*.json`, `closeout-evidence.json`, `goal-provenance.json` |
| Los inventarios del sistema | `ui-groups-inventory.json`, `unlayered-delivery-inventory.json`, `operational-fixtures.json`, `vendors.json` |

Cada concepto **enlaza las trampas que ya existen sobre él**: `manifiesto-ds-exige-golden` cuelga
del manifiesto, `gate-estatico-no-ve-tokens-rotos` y `visual-baselines-estado-real` de las
baselines, `comentario-de-token-afirma-uso-inexistente` de los tokens,
`occurrence-no-resiste-insercion-entre-duplicados` de las excepciones. Eso es lo que convierte
veintiuna minas sueltas en un terreno con mapa.

**Frontmatter:** `tipo: concepto`, `estado: vigente`, `fecha` del día de la verificación,
`areas: [design-system]`, `fuente` con el archivo o comando leído, y `resumen` de una línea.

### 2. La divergencia de versión

Tres piezas, en este orden:

1. **Trampa nueva en la wiki** que diga qué fuente manda (`version.json`), cuál engaña
   (`CHANGELOG.md`) y cuál es el commit que zanjó la discusión (`58b850e7`).
2. **Corregir el encabezado del `CHANGELOG.md`**, decisión explícita del usuario el 2026-08-04. Se
   cambia el título del ciclo y se cita el commit de activación; **no se reescribe ni se reordena
   su contenido**, que documenta cómo se llegó hasta ahí y sigue siendo cierto. El design system
   tiene contrato de gobierno propio (`contracts/governance.md`), así que el cambio es mínimo y
   trazable.
3. **Corregir `memoria/mapas/design-system.md`**, que hoy manda al changelog a averiguar la
   versión.

### 3. El mapa gana una sección «Conceptos»

Al principio de `memoria/mapas/design-system.md`, **antes** de las trampas y los gates: primero el
terreno, después las minas. Enlaza las siete páginas nuevas.

## Cómo se verifica cada concepto

La misma regla del pase de veracidad, porque un concepto sembrado a ojo es peor que ninguno: cada
página se escribe leyendo **la fuente y el código o el gate que la consume**, y toda afirmación
comprobable lleva su cita `archivo:línea`. Si un artefacto resulta no tener consumidor real, eso se
escribe tal cual: es exactamente el tipo de hallazgo que ya produjo la trampa
`comentario-de-token-afirma-uso-inexistente`.

## Fuera de alcance

- Los `.schema.json`: son la **forma** de los artefactos anteriores, no conceptos propios.
- `docs/design-system/evidence/`, `baseline-approvals/` y `runtime-measurements/`: artefactos de
  corrida, no gobierno.
- Reescribir el contenido del `CHANGELOG.md` más allá del encabezado del ciclo.
- Cualquier cambio en el design system en sí: tokens, componentes, CSS o gates. Esto es trabajo de
  documentación.

## Condición de hecho

1. Existen siete páginas `tipo: concepto` en `memoria/`, cada una con su frontmatter completo y
   enlazada desde el mapa del área.
2. Ninguna de las 18 fuentes hoy huérfanas sigue sin aparecer en la wiki; se vuelve a medir con el
   mismo barrido y se reporta la cifra nueva.
3. Toda afirmación comprobable de las páginas nuevas lleva cita `archivo:línea` verificada en esta
   sesión.
4. La divergencia de versión está registrada como trampa, el encabezado del changelog corregido con
   su commit citado, y el mapa ya no remite al changelog para la versión.
5. `npm run test:wiki` en verde, y línea de bitácora en `memoria/log.md`.

## Riesgos conocidos

- **Un concepto puede envejecer peor que una trampa.** Una trampa describe un incidente que ocurrió;
  un concepto describe un sistema que cambia. Mitigación: escribir el *para qué*, que es estable, y
  no el *cómo*, que no lo es; y que la rotación de `veracidad` cubra estas páginas como cualquier
  otra.
- **Siete páginas de golpe es mucha superficie nueva.** Si alguna resulta no tener nada que decir
  más allá de repetir la fuente, se descarta en vez de rellenarla: seis conceptos ciertos valen más
  que siete completos.
