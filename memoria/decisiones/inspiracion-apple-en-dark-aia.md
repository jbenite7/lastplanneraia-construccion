---
capa: wiki
tipo: decision
estado: vigente
fecha: 2026-08-03
areas: [design-system, proceso]
fuente: sesion
resumen: "Premisa de diseño de alta prioridad: principios de Apple —claridad, deferencia al contenido, jerarquía sin cromo— expresados con los tokens y primitivas de AIA en dark mode, nunca copiando el aspecto de macOS/iOS"
---
Decisión del usuario del 2026-08-03, con **alta prioridad**: la interfaz debe quedar con un
diseño **inspirado en Apple, pero con el design system de AIA en dark mode**.

## Qué significa, y qué no

**Sí** — se adoptan los principios:

- **Deferencia al contenido.** La interfaz cede el protagonismo al dato. En esta aplicación el
  dato es la tabla: el cromo alrededor se reduce, no se decora.
- **Claridad.** Jerarquía por espaciado, tamaño y peso tipográfico antes que por bordes, cajas y
  fondos. Un control que se explica solo no necesita marco.
- **Un solo acento por vista.** La acción primaria destaca; las demás quedan en segundo plano.
  Nada de barras donde todos los botones pesan igual.
- **Controles discretos que ganan presencia al necesitarse.** Reposo tenue, foco y hover
  inequívocos. El gatillo de filtro del task 17 es el patrón de referencia.
- **Materiales sobrios.** Profundidad por elevación medida, no por sombras dramáticas ni
  gradientes gratuitos.

**No** — no se copia el aspecto:

- No se imitan colores, tipografías ni componentes de macOS/iOS. La identidad sigue siendo AIA:
  tokens `--ds-*`, primitivas `aia-*`, la paleta del repo, cero hex.
- No se importa nada que choque con el alcance del repo: dark mode y `1180x820` siguen siendo el
  **defecto y lo canónico a validar**. **Corrección del 2026-08-10:** desde DS-032, `AGENTS.md` ya
  no lo plantea como veto exclusivo — dice «Otros viewports y un tema claro son admisibles cuando
  la petición lo pida, sin prohibición previa». Lo que dejó de ser cierto es el «exclusivamente»,
  no el defecto. Ver [[design-system]] (`memoria/mapas/design-system.md:22-24,69-71`), que ya lo
  documentaba bien.

## Su límite duro

La libertad es **de forma, no de fondo**. Un cambio inspirado en esta premisa **no puede** alterar
funcionalidad: ni comportamientos, ni datos, ni reglas de negocio, ni rutas, ni permisos, ni el
texto que comunica una regla de dominio. Si una mejora visual exige tocar funcionalidad, no se
hace: se registra como decisión pendiente del usuario.

## Cómo se aplica

El barrido horario de diseño (sesión del 2026-08-03) lleva esta premisa como criterio vinculante
y tiene libertad para aplicar mejoras que la cumplan, con tokens, verificadas en navegador y con
la suite estática en verde. Para el detalle de las tres lentes, ver [[design-system]] y
[[qa-y-gates]].
