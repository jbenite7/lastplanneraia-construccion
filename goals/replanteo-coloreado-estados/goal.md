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

## Publicaciones
Ninguna todavía.

## Archivos de este goal
- [[docs/superpowers/plans/2026-08-20-replanteo-coloreado-estados]]
- [[goals/ds-f1a-estados-severidad/goal]]
- [[memoria/goals/estado]]
