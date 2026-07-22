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
- Deuda consciente `linen`: el tema `linen` se envía y el usuario puede activarlo
  con el toggle (`public/js/modules/aia_ui/theme.js`), pero **ningún gate lo
  valida** (shipped-but-ungated). Está fuera del alcance de enforcement vigente
  por decisión explícita, no por olvido; cualquier regresión visual en `linen`
  no está cubierta por los gates hasta que se decida validarlo o retirarlo.
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

## Enrutador de gates y hook local (opcional)

`scripts/design-system-router.mjs` traduce "estos archivos cambiaron" a "corre
estos gates". Úsalo manualmente:

```bash
node scripts/design-system-router.mjs            # sobre el diff actual del worktree
node scripts/design-system-router.mjs views/pdc/pdc.view.php
```

Para superficies UI sin manifiesto advierte (no bloquea) y recuerda que el cambio
no debe subir `docs/design-system/audit-baseline.json`.

Opcionalmente, cada desarrollador puede activarlo como hook `PostToolUse` de su
asistente para que se ejecute al editar UI. `.claude/settings.json` (Claude Code)
y `.codex/hooks.json` (Codex) están en `.gitignore`: son configuración local por
máquina, no contrato del repo. Ejemplo para Claude Code en `.claude/settings.json`:

```json
{
  "hooks": {
    "PostToolUse": [
      { "matcher": "Edit|Write",
        "hooks": [ { "type": "command", "command": "node scripts/design-system-router.mjs", "timeout": 10 } ] }
    ]
  }
}
```

La garantía compartida real sigue siendo CI (`.github/workflows/design-system.yml`)
más los gates en `scripts/`; el hook es solo una ayuda temprana.
