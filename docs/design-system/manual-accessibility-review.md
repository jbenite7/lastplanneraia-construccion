# Revisión automatizada básica de accesibilidad — Sprint 00

Estado: **Axe desktop dark aprobado; revisión manual independiente pendiente si se exige**.

Superficies autorizadas: las diez familias aprobadas del laboratorio. No se
revisan otros módulos.

La matriz obligatoria cubre dark en `1180x820` y `1440x900`; `1180x820` es el
viewport canónico. Mobile, tablet y `linen` están fuera del alcance visual.
Axe agrega los 20 escenarios permitidos y registra superficie, tema, viewport,
estado revelado, regla, impacto, tipo de resultado, selector y decisión.

## Checklist

| Gate | Criterio mínimo | Estado |
|---|---|---|
| Axe — laboratorio | 20 escenarios desktop dark, cero violaciones serias y excepciones `incomplete` exactas, vigentes y justificadas | Aprobado 2026-07-18 |
| Axe — estados revelados | Los estados interactivos incluidos en la matriz conservan el mismo contrato bloqueante | Aprobado 2026-07-18 |
| Revisión manual independiente | Teclado, foco, lectura y estados operables verificados por una persona si el release lo exige | Pendiente |

## Revisión local asistida — 2026-07-19

Revisor: Codex, mediante inspección local automatizada en Chromium. Esta revisión
no sustituye la revisión humana independiente, que permanece pendiente.

- Superficie: diez familias del laboratorio en dark, sin visitar otros módulos.
- Viewports: `1180x820` en densidad Touch y `1440x900` en densidad Compacta.
- Teclado y foco: orden de acciones y filtros correcto; el diálogo recibe foco,
  cierra con Escape y lo devuelve al disparador.
- Contraste de gráficos: 28 textos SVG medidos; mínimo `11.6:1`, sin resultados
  por debajo de `4.5:1`.
- Axe: 20 escenarios desktop dark aprobados, sin violaciones serias.

La revisión humana independiente continúa **pendiente** si el release la exige.

## Evidencia requerida

- Fecha, revisor y versión del design system.
- URL y familia o estado exacto del laboratorio.
- Tema, viewport y densidad aplicada.
- Export completo de cada revisión automatizada básica separada.
- Conteo explícito de violaciones e `incomplete`; cada `incomplete` aceptado debe
  coincidir exactamente con una excepción que incluya `kind` y no puede ocultar
  una violación futura.
- Fecha, revisor local y hash del diff o commit evaluado.

No se infiere el cumplimiento integral de un estándar a partir de Axe ni de una
revisión automatizada básica.

## Evidencia no bloqueante

Teclado y reflow continúan ejecutándose para producir artefactos diagnósticos,
pero no son gates de activación y no forman parte de `test:design-system:runtime`.
CI los ejecuta con tolerancia al fallo y conserva sus artefactos cuando fallan.
Los resultados del 2026-07-14 son observaciones históricas superseded y no
evidencia de activación para `1.0.0`.
