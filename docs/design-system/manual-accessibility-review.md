# Revisión automatizada básica de accesibilidad — Sprint 00

Estado: **pendiente de evidencia automatizada fresca**.

Superficies autorizadas: las diez familias aprobadas del laboratorio y
Programa General como único piloto. No se revisan otros módulos.

La matriz obligatoria cubre dark y linen en 390x844, 1180x820 y 1440x900.
Accessibility Insights se usa únicamente para la revisión automatizada básica. Cada
export registra superficie, tema, viewport, estado revelado, regla, cantidad de
instancias, evidencia y decisión. Un resultado bloqueante vuelve a la familia
del laboratorio antes de corregirse en el piloto.

## Checklist

| Gate | Criterio mínimo | Estado |
|---|---|---|
| Accessibility Insights — laboratorio | Revisión automatizada básica separada con cero reglas fallidas y cero instancias fallidas | Pendiente |
| Accessibility Insights — piloto | Revisión automatizada básica separada con cero reglas fallidas y cero instancias fallidas | Pendiente |
| Accessibility Insights — estados revelados | Revisión automatizada básica separada con cero reglas fallidas y cero instancias fallidas | Pendiente |

## Evidencia requerida

- Fecha, revisor y versión del design system.
- URL y familia o estado exacto de Programa General.
- Tema, viewport y densidad aplicada.
- Export completo de cada revisión automatizada básica separada.
- Conteo explícito de reglas fallidas e instancias fallidas; ambos deben ser cero.
- Fecha, revisor local y hash del diff o commit evaluado.

No se usa lenguaje de aprobación general de Accessibility Insights ni se infiere
el cumplimiento integral de un estándar a partir de estos resultados.

## Evidencia no bloqueante

Teclado y reflow continúan ejecutándose para producir artefactos diagnósticos,
pero no son gates de activación y no forman parte de `test:design-system:runtime`.
CI los ejecuta con tolerancia al fallo y conserva sus artefactos cuando fallan.
Los resultados del 2026-07-14 son observaciones históricas superseded y no
evidencia de activación para `1.0.0`.
