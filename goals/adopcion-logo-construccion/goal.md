---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-08-18
areas: [proceso]
fuente: goals/adopcion-logo-construccion/goal.md
resumen: Adoptar el ícono nuevo del kit Construcción en las cuatro superficies de marca visibles (favicon, sidebar del shell, login y Admin), con wordmark como texto…
---

# Goal: Adopción del logo «Last Planner · línea Construcción»

**Objetivo:** Adoptar el ícono nuevo del kit Construcción en las cuatro superficies de marca
visibles (favicon, sidebar del shell, login y Admin), con wordmark como texto vivo y sin cambios
de paleta en el design system.

**Condición de hecho:** Las cuatro superficies muestran la marca nueva verificadas en navegador a
1180×820 dark contra el contenedor; `/favicon.ico` responde 200; el SVG legado
`aia-last-planner-mark.svg` queda retirado sin consumidores rotos; `npm run check:frontend` sin
errores nuevos.

**Plan:** `docs/superpowers/plans/2026-08-06-adopcion-logo-construccion.md`
**Spec:** `docs/superpowers/specs/2026-08-06-adopcion-logo-construccion-design.md`

## Cierre

**Cerrado el 2026-08-19.** El trabajo se ejecutó el 2026-08-06 (`4437fcfa`, `6b618964`) y llevaba
trece días sin firmar. Condición de hecho verificada de nuevo hoy, con salida real:

| Hecho | Medición |
|---|---|
| Las cuatro superficies sirven marca a 1180×820 dark | login `/img/brand/glyph-mono.svg` · shell y proyectos `/public/img/brand/icon.svg` · admin `/public/img/aiaConstruccionMasCerteza.png` |
| `/favicon.ico` | **200** |
| SVG legado `aia-last-planner-mark.svg` | no existe en el árbol y **ningún archivo lo cita** |
| Recursos de imagen rotos en las cuatro superficies | **ninguno** |
| `npm run check:frontend` | 2.537 avisos y 416 informativos, **todos de base**: este frente no añadió ninguno, y ese comando no es carril del gate |

Sonda: `evidence/sonda-marca.mjs`, que comprueba que la página **sirva** la marca —recurso 200 y
nodo en el DOM— y no que el archivo exista: un `<img>` con `src` roto pasa cualquier comprobación de
fichero y deja la superficie sin logo.

**Lo que costó una corrección, y merece quedar escrito:** la primera corrida dio ROJO en `/login`
—«ninguna marca en el DOM»— y **la captura enseñaba el isotipo perfectamente**. La sonda solo leía
`background-image`, y esa superficie pinta la marca con `mask-image` sobre un `<span>` vacío
(`views/auth/login.view.php:19`). Era un falso rojo de la herramienta, no un defecto del producto.
Lo cazó mirar la captura, no razonar sobre el DOM.

## Archivos de este goal

- [[docs/superpowers/specs/2026-08-06-adopcion-logo-construccion-design|Spec de diseño]]
- [[docs/superpowers/plans/2026-08-06-adopcion-logo-construccion|Plan de implementación]]
- [[memoria/goals/estado|Estado de goals en la wiki]]
