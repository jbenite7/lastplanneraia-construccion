# Contrato de revisión y cierre de sprint

El cierre es una decisión verificable, no una inferencia basada en apariencia.
Cada gate conserva evidencia esencial y la aprobación humana es bloqueante.

## Registro de evidencia

Cada registro identifica ruta, rol, estado, viewport, tema, archivo, overflow,
targets, consola, contraste, gate y checksum. Los goldens deterministas se
versionan; videos, traces y capturas extensas permanecen como artefactos.

## Matriz de autoridad

- Playwright ejecuta flujos, axe y regresión visual.
- Axe bloquea hallazgos automáticos critical y serious.
- Accessibility Insights, teclado, VoiceOver, zoom 200% y reflow cubren la
  revisión manual que la automatización no demuestra.
- El laboratorio aprueba familias; el piloto aprueba integración real.

## Secuencia de cierre

1. Ejecutar contratos, auditor, runtime, seguridad y pruebas del piloto.
2. Presentar evidencia consolidada, límites y hunks exactos para aprobación.
3. Actualizar SemVer y changelog; repetir todos los gates.
4. Ejecutar Plannotator sobre el diff y resolver sus observaciones.
5. Hacer staging selectivo y comprobar el diff staged.
6. Crear un commit atómico exclusivo, sin push ni deploy.

Una falla, excepción vencida, dato sin restauración o ausencia de aprobación
impide cerrar el sprint y publicar la versión.
