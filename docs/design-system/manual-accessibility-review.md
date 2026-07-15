# Revisión manual de accesibilidad — Sprint 00

Estado: **pendiente de evidencia humana consolidada**.

Superficies autorizadas: las diez familias aprobadas del laboratorio y
Programa General como único piloto. No se revisan otros módulos.

La matriz obligatoria cubre dark y linen en 390x844, 1180x820 y 1440x900.
Cada hallazgo registra superficie, tema, viewport, elemento, criterio WCAG,
resultado, evidencia y decisión. Un fallo bloqueante vuelve a la familia del
laboratorio antes de corregirse en el piloto.

## Checklist

| Gate | Criterio mínimo | Estado |
|---|---|---|
| Accessibility Insights | Evaluación guiada sin hallazgos bloqueantes | Pendiente |
| Teclado | Orden lógico, foco visible, operación Enter/Espacio/Escape, sin trampas y retorno de foco | Pendiente |
| VoiceOver | Landmarks, nombres, estados y regiones vivas comprensibles; tabla y tarjetas equivalentes | Pendiente |
| Zoom 200% | Contenido y controles utilizables sin solapamiento ni recorte | Pendiente |
| Reflow | A 320 CSS px no hay scroll horizontal de página ni palabras fragmentadas | Pendiente |

## Evidencia requerida

- Fecha, revisor y versión del design system.
- URL y familia o estado exacto de Programa General.
- Tema, viewport y densidad aplicada.
- Captura o referencia de sesión para cada hallazgo.
- Resultado `aprobado`, `corregir` o `excepción`, con responsable y expiración.

La aprobación estética no sustituye este checklist y axe no sustituye las
evaluaciones manuales.

## Prechecks completados

- Teclado automatizado: orden, foco visible, Enter, Escape y retorno de foco
  pasan en Acciones, Formularios/filtros y Programa General.
- Árbol semántico nativo: Presentación de datos expone tabla, encabezados y
  celdas; Programa General expone navegación, grupos, acciones, artículos,
  campos nombrados por actividad y diálogo activo. VoiceOver sigue pendiente.
- Reflow automatizado: diez familias y Programa General pasan a 320 CSS px en
  dark y linen. Zoom real al 200 % y revisión humana siguen pendientes.
