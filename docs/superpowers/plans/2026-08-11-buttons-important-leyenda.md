---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-08-11
areas: [proceso]
fuente: docs/superpowers/plans/2026-08-11-buttons-important-leyenda.md
resumen: Medido sobre f1f5bd87. 41 !important en 7 reglas: 25 en el chip, 16 en .indicator y .count-badge.
---

# Plan — los `!important` de `.pdc-legend-item`

Spec: [2026-08-11-buttons-important-leyenda-design.md](../specs/2026-08-11-buttons-important-leyenda-design.md)
Medido sobre `f1f5bd87`. **41 `!important` en 7 reglas**: 25 en el chip, 16 en `.indicator` y
`.count-badge`.

## Orden de trabajo

El orden no es cosmético: va de lo que no puede cambiar nada a lo que sí, para que un cambio visual
inesperado tenga siempre un único candidato.

### T1 — Línea base computada, antes de tocar

Sobre las tres pantallas a 1180×820 dark, con la leyenda renderizada, capturar
`getComputedStyle()` de `.pdc-legend-item`, `.indicator` y `.count-badge` para las 41 propiedades
implicadas, más el estado con un filtro activo (que reintroduce ítems ocultos). Se guarda como
JSON en el worktree. **Es el patrón de comparación de todo el frente** y se remide entera tras
cada restauración, no se reutiliza.

### T2 — Las redundantes de `@media (max-width: 992px)`

`font-size` y `padding` de L1131 declaran **el mismo valor** que L970. Retirar esas dos
declaraciones enteras (no solo su `!important`) no puede cambiar el computado a ningún ancho.
`min-height` se queda: es la única que aporta algo ahí.

Es el caso más limpio y sirve para validar el método: si el computado se mueve, el método está mal
antes de haber tocado nada arriesgado.

### T3 — El `:where()` de L65

`line-height` compite con L970 desde especificidad cero. Comprobar cuál gana hoy y retirar la que
no llegue a aplicarse nunca sobre el chip. **Cuidado:** ese `:where()` cubre también `.badge`,
`.badge-status`, `.aia-chip` y `.count-badge`, así que la retirada se mide en todos ellos, no solo
en la leyenda.

### T4 — El bloque de 16

Una a una, y cada una con el ciclo completo: quitar el `!important` (no la declaración) → recargar
→ computado contra la base → si el computado se mueve, **restaurar y anotar por qué hacía falta**,
nombrando la regla que ganaba. Las cuatro que el encargo daba por necesarias —`white-space`,
`font-size`, `line-height`, `border`— **se miden igual que las demás**: es una hipótesis heredada,
no un resultado.

### T5 — Los 16 de los descendientes

`.indicator` y `.count-badge`, mismo ciclo. Van al final porque son los que menos se parecen al
resto y porque su tamaño afecta a la altura del chip, que es lo que sostiene el piso AA de 24px
declarado en `toolbar-controls.css`.

### T6 — Verificación y cierre

`npm run test:design-system:static`, las pruebas visuales que cubran las tres pantallas,
y el par final «quitadas / quedan» de las 41.

## Lo que este plan NO hace

- **No toca `@media (prefers-reduced-motion: reduce)`** (L1187). Esas dos anulan a propósito y
  quitarlas rompería una preferencia de accesibilidad.
- **No mueve declaraciones a otra capa ni a otro archivo.** Reubicar es otro frente: cambiaría qué
  gana en la cascada, no cuántos `!important` hay.
- **No toca `programacion-intermedia.css`, `programa-general.css` ni `programacion-semanal.css`.**
  Si una retirada exige tocarlos, se para y se encola.
- **No promete bajar ningún contador del auditor:** `buttons.css` no está en `exceptions.json`.

## Riesgo declarado

`buttons.css` es compartido por los tres módulos y por controles que no son la leyenda (`.btn`,
`.badge`, `.aia-chip`). Cada retirada de una regla con `:where()` o con lista de selectores puede
alcanzar superficies fuera del alcance; por eso T3 y T4 miden en todos los selectores de la regla,
no solo en `.pdc-legend-item`.
