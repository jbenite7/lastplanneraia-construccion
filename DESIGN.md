# AIA Design System consumer contract

Este archivo es una guía de consumo para desarrolladores. No crea una segunda
fuente de verdad: la autoridad ejecutable permanece en `docs/design-system/`,
`public/css/tokens.css`, `public/css/design-system/core.css` y
`public/css/aia-design-system.css`. El laboratorio en `/internal/design-system`
es la referencia visual versionada de esas mismas primitivas.

## Reglas para superficies migradas

- Consumir tokens `--ds-*`/`--aia-*` y primitivas `aia-*`; no introducir
  colores, tipografías, radios, sombras, spacing o estados locales.
- No usar estilos inline, bloques `<style>`, gradientes decorativos, skins de
  vendors ni nuevas CDN. Los vendors deben estar declarados en el manifiesto y
  servirse mediante los adaptadores aprobados.
- Mantener WCAG AA, foco visible, `prefers-reduced-motion` y objetivos de
  interacción de al menos `44px`.
- La validación visual vigente es únicamente desktop dark: `1180x820` (canónico)
  y `1440x900`. Mobile, tablet y `linen` no forman parte de este alcance.
- Programa General y sus archivos protegidos no se modifican desde una
  migración de otra superficie.

## Flujo obligatorio

Antes de editar una superficie declarada:

1. Ejecutar `$impeccable audit <superficie>` y revisar su manifiesto.
2. Mantener manifiesto, pruebas y evidencia en el mismo cambio.
3. Ejecutar el contrato estático fail-closed, validación funcional, Axe,
   consola/red, foco, targets de `44px` y QA visual desktop dark.
4. Obtener aprobación humana antes de reconciliar goldens.

Solo las superficies registradas como migradas fallan cerradamente. Un módulo
legacy no declarado queda congelado: para editarlo primero hay que crear su
manifiesto y activar sus gates.
