---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-08-20
areas: [design-system]
fuente: goals/replanteo-coloreado-estados/goal.md
resumen: "Dirección B del replanteo de estados: chip sólido como portador fuerte de identidad, filas a tinte sutil, y filete de severidad homogéneo en los tres módulos, sin cambiar estados ni matices"
---

# Frente: replanteo-coloreado-estados

## Fase del plan
Plan: docs/superpowers/plans/2026-08-20-replanteo-coloreado-estados.md
Fase: Tasks 1 a 7
Sha verificado: (ver `## Publicaciones`)

## Objetivo
Que el chip sólido sea el portador fuerte de identidad de estado, las filas bajen a tintes
sutiles, y el filete de severidad exista y se lea en los TRES módulos (`/programacion-intermedia`,
`/programa-general`, `/programacion-semanal`). Nace de retroalimentación de usuarios: visibilidad
baja por contraste en oficina, obra y proyector — los tintes oscuros se lavan iguales y el chip
apagado no rescata la identidad. Decisión de dirección de Felipe, 2026-08-20 (opción B del widget,
con paleta anclada al manual AIA tras auditoría WCAG + marca).

## Condición de hecho
Los tres módulos pintan chips sólidos (`--ds-state-solid-*`), filas sutiles (`--ds-state-row-*`)
y filete homogéneo (PG lo estrena en `Atrasada` y `Debe Iniciar`), medido computado-contra-computado
a 1180×820 dark por sesión de `/dev/entrar`; el guard `state-solid-contract` calcula WCAG contra el
contrato en cada corrida; los goldens afectados regenerados **solo tras aprobación visual explícita
de Felipe sobre capturas**; `bash scripts/publicar.sh --solo-verificar` en RC=0.

## Posture
- No tocar los hex de `--ds-state-tint-*` (los consume el PDC).
- Mismos estados y mismos matices: el vocabulario no se toca, cambia el portador y la intensidad.
- No regenerar ningún golden sin aprobación visual explícita de Felipe, por su nombre.
- No ablandar ningún test: si cambia, declara qué mide ahora.
- El cubo de alertas de Semanal (`ps-alert-*`) es otro eje y no se toca.
- Sin dependencias nuevas.

## Contención (medida 2026-08-20, antes de arrancar)
`git log origin/main --since=2026-08-20` sobre `docs/design-system/`, `public/css/`,
`public/js/modules/`, `tests/design-system/`: **cero commits ajenos** después de la publicación
`a4e34075` de esta misma sesión (cierre de `ds-f1a-estados-severidad`). `.claude/sesiones.md` solo
conserva entradas del 19-ago de frentes ya cerrados y publicados en main (bold-neumann terminó:
recálculo aplicado en pruebas y producción). Nadie más está sobre la superficie.

## Leer primero
- `docs/superpowers/plans/2026-08-20-replanteo-coloreado-estados.md` — el plan, con la paleta
  auditada como contrato (WCAG AA + manual AIA, tabla en su encabezado).
- `goals/ds-f1a-estados-severidad/goal.md` — el frente del que hereda maquinaria y posture.
- `docs/design-system/ds-f1a-escala-estado.md` — el contrato de 3 niveles que gobierna.

## Resultado

Tres olas, cada una en su workflow, dependientes a proposito porque tocaban los mismos archivos.

| Ola | Que cerro |
|---|---|
| 1 · Estados | Los tres modulos pintan el nombre corto oficial del contrato; fuera la elipsis del chip y el `@container` que escondia el nombre bajo 120px; el tooltip migra al top-layer con Popover API y el volteo por JS —parche a ese recorte— se retira |
| 2 · Contenido que desaparece | Los envoltorios de Semanal escondian con `overflow-x: hidden` (y en `#cuadroTabla` con `!important`, o sea irreversible): pasan a `auto` CON señal de borde. Fuera `word-break: break-all`, `hyphens: auto` y el `substring(0,157)` que recortaba por conteo de caracteres |
| 3 · Rampa y vecinos | ~20 literales vuelven a 0.72/0.70rem donde son celda o cabecera; `overflow-wrap: anywhere` pasa a `break-word` en tablas; el PDC anuncia las columnas que esconde y su tooltip deja de recortar |

**El entregable mas duradero es una regla, no CSS:** nada se recorta sin que el usuario lo sepa. El
texto cabe, envuelve entre palabras, o se acorta con un nombre corto **oficial** declarado en el
contrato. Envolver no es recortar.

**El efecto mas visible no estaba previsto:** `/programacion-intermedia` pasa a mostrar sus **nueve
filas** en el viewport donde antes cabian cinco. La altura dejo de gastarse en un widget de dos
renglones y en tipografia inflada.

### Lo que se aprendio, y vale mas que el diff

- **Tres tamanos eran invisibles a cualquier censo** por venir en unidades `em` encadenadas:
  `.ps-origin-badge` resolvia a ~7,5px reales, muy por debajo del piso de 11px de PRODUCT.md. Un
  censo que solo mira literales `rem` no los ve.
- **Silenciar el detector era esconder el contrato en otro archivo.** El detector construye su
  rampa desde el frontmatter de DESIGN.md, no del sidecar: declarar los pasos ahi bajo
  `typography.scale` llevo los hallazgos de **52 a 1**, y permitio RETIRAR los tres silenciamientos
  que se habian puesto antes.
- **Dos lecturas visuales del asistente alucinaron** tarjetas claras sobre miniaturas de 1180 que
  una rejilla de 60 pixeles muestreados desmintio (56 de 60 eran oscuros). La evidencia de detalle
  va a 2x desde entonces.
- **El ejecutor corrigio el encargo dos veces y tenia razon**: la linea señalada como envoltorio era
  `#hot-container`, y abrirle scroll no destapa nada porque Handsontable desborda en su nodo
  interno; y las columnas vecinas traian `overflow-wrap: anywhere`, el mismo daño con otro nombre.

## Pendientes (frentes propios, no de este)

- **Cabeceras de grilla desalineadas entre modulos**: Intermedia va a `0.75rem` (escala declarada en
  su Task 19) y Semanal quedo en `0.72rem`. Alinearlas es decision de producto.
- **`overflow-wrap: anywhere` en el chip de estado de Intermedia** (`programacion-intermedia.css`):
  puede partir el nombre de un estado. Semanal no lo tiene. Vive en territorio que la ola 1 cerro.
- **`1.75rem` en el boton de cierre de modal de Semanal**: unico hallazgo vivo del detector. No es
  tabla; cambiarlo altera apariencia fuera de alcance.
- **Siete `console.log('[PI-DEBUG]')` en Intermedia**, tras el flag `window.__PI_DEBUG_COLOR`.
  Andamio deliberado, pero andamio.
- **El censo completo de las 22 tablas** vive en `censo-tablas.md`: Admin y el resto de vistas HTML
  quedaron fuera de estas tres olas por decision de alcance.

## Publicaciones
Ver `## Cierre`.

## Archivos de este goal
- [[docs/superpowers/plans/2026-08-20-replanteo-coloreado-estados]]
- [[goals/ds-f1a-estados-severidad/goal]]
- [[memoria/goals/estado]]
