---
capa: fuente
tipo: guia
estado: vigente
fecha: 2026-04-07
fuente: docs/STITCH.md
resumen: Guía permanente para conectar, autenticar e interactuar con Stitch desde este workspace.
---

# Stitch

Guía permanente para conectar, autenticar e interactuar con Stitch desde este workspace.

## Estado actual

- Google Cloud project: `gen-lang-client-0025081828`
- Stitch project limpio actual: `projects/18267806638374981908`
- Design system base: `assets/11053095891930424655`
- Proyecto Stitch anterior (legacy/deprecated): `projects/4177037855941203861`
- MCP client principal en este repo: `OpenCode`
- Enfoque recomendado: `gcloud` + ADC + `stitch-mcp proxy`
- Modelo recomendado para generación: `GEMINI_3_1_PRO`

## Casos de uso

- Crear proyectos nuevos de diseño
- Crear y actualizar design systems
- Generar pantallas por texto
- Generar variantes de una pantalla existente
- Obtener HTML o imagen de una pantalla
- Construir un sitio a partir de pantallas Stitch

## Conexión recomendada

Esta es la forma más estable en este workspace.

```bash
gcloud auth application-default login
gcloud config set project gen-lang-client-0025081828
gcloud auth application-default print-access-token
```

Para usar el proxy de Stitch con OpenCode o CLI:

```bash
STITCH_ACCESS_TOKEN=$(gcloud auth application-default print-access-token) \
GOOGLE_CLOUD_PROJECT=gen-lang-client-0025081828 \
npx @_davideast/stitch-mcp tool list_tools -s
```

Si quieres que Stitch rehaga la configuración desde cero, usa el asistente interactivo:

```bash
npx @_davideast/stitch-mcp init --client opencode --transport stdio
```

## Opción con API key en `.env`

Si prefieres usar API key directa, guárdala en `.env` y no la commitees.

```env
STITCH_API_KEY=tu_api_key_aqui
GOOGLE_CLOUD_PROJECT=gen-lang-client-0025081828
```

Uso típico:

```bash
set -a
source .env
set +a
npx @_davideast/stitch-mcp tool list_tools -s
```

## MCP en OpenCode

La configuración actual usa el proxy local de Stitch:

```json
{
  "mcp": {
    "stitch": {
      "command": "npx",
      "args": ["-y", "@_davideast/stitch-mcp", "proxy"],
      "env": {
        "STITCH_USE_SYSTEM_GCLOUD": "1",
        "GOOGLE_CLOUD_PROJECT": "gen-lang-client-0025081828"
      },
      "enabled": true
    }
  }
}
```

Si cambias la config, reinicia OpenCode para que recargue el MCP.

## Comandos útiles

### Ver salud de la conexión

```bash
npx @_davideast/stitch-mcp doctor --verbose
```

### Listar herramientas disponibles

```bash
STITCH_ACCESS_TOKEN=$(gcloud auth application-default print-access-token) \
GOOGLE_CLOUD_PROJECT=gen-lang-client-0025081828 \
npx @_davideast/stitch-mcp tool list_tools -o json
```

### Crear un proyecto Stitch

```bash
STITCH_ACCESS_TOKEN=$(gcloud auth application-default print-access-token) \
GOOGLE_CLOUD_PROJECT=gen-lang-client-0025081828 \
npx @_davideast/stitch-mcp tool create_project -d '{"title":"AIA Last Planner Design System"}' -o json
```

### Crear o actualizar un design system

- Herramienta: `create_design_system`
- Luego: `update_design_system`
- Campos clave: `displayName`, `theme`, `customColor`, `headlineFont`, `bodyFont`, `labelFont`, `roundness`

### Listar y aplicar design systems

```bash
STITCH_ACCESS_TOKEN=$(gcloud auth application-default print-access-token) \
GOOGLE_CLOUD_PROJECT=gen-lang-client-0025081828 \
npx @_davideast/stitch-mcp tool list_design_systems -d '{"projectId":"4177037855941203861"}' -o json
```

Para aplicar un design system a pantallas existentes:

1. Primero consulta el proyecto:

```bash
STITCH_ACCESS_TOKEN=$(gcloud auth application-default print-access-token) \
GOOGLE_CLOUD_PROJECT=gen-lang-client-0025081828 \
npx @_davideast/stitch-mcp tool get_project -d '{"name":"projects/4177037855941203861"}' -o json
```

2. Toma los `screenInstances` del resultado.
3. Aplica el design system:

```bash
STITCH_ACCESS_TOKEN=$(gcloud auth application-default print-access-token) \
GOOGLE_CLOUD_PROJECT=gen-lang-client-0025081828 \
npx @_davideast/stitch-mcp tool apply_design_system -d '{
  "assetId":"9031881493948324564",
  "projectId":"4177037855941203861",
  "selectedScreenInstances":[
    {"id":"screen-instance-id-aqui"}
  ]
}' -o json
```

> Nota: en la práctica conviene pasar los objetos completos de `screenInstances` devueltos por
> `get_project`, no solo el `id`.

### Listar proyectos y pantallas

```bash
STITCH_ACCESS_TOKEN=$(gcloud auth application-default print-access-token) \
GOOGLE_CLOUD_PROJECT=gen-lang-client-0025081828 \
npx @_davideast/stitch-mcp tool list_projects -d '{"filter":"view=owned"}' -o json

STITCH_ACCESS_TOKEN=$(gcloud auth application-default print-access-token) \
GOOGLE_CLOUD_PROJECT=gen-lang-client-0025081828 \
npx @_davideast/stitch-mcp tool list_screens -d '{"projectId":"4177037855941203861"}' -o json
```

### Generar una pantalla desde texto

```bash
STITCH_ACCESS_TOKEN=$(gcloud auth application-default print-access-token) \
GOOGLE_CLOUD_PROJECT=gen-lang-client-0025081828 \
npx @_davideast/stitch-mcp tool generate_screen_from_text -d '{
  "projectId":"4177037855941203861",
  "prompt":"Create a premium login screen for AIA using liquid glass style, corporate green dominant, Montserrat headings, Inter body, linen background, and subtle shadows.",
  "deviceType":"DESKTOP",
  "modelId":"GEMINI_3_1_PRO"
}' -o json
```

### Generar variantes de una pantalla existente

```bash
STITCH_ACCESS_TOKEN=$(gcloud auth application-default print-access-token) \
GOOGLE_CLOUD_PROJECT=gen-lang-client-0025081828 \
npx @_davideast/stitch-mcp tool generate_variants -d '{
  "projectId":"4177037855941203861",
  "selectedScreenIds":["screen-id-aqui"],
  "prompt":"Generate four variants: desktop linen, desktop dark, mobile linen, mobile dark.",
  "variantOptions":{
    "variantCount":4,
    "creativeRange":"REFINE",
    "aspects":["LAYOUT","COLOR_SCHEME","TEXT_FONT"]
  }
}' -o json
```

### Obtener código o imagen de una pantalla

```bash
npx @_davideast/stitch-mcp tool get_screen_code -d '{"projectId":"4177037855941203861","screenId":"screen-id-aqui"}' -o json
npx @_davideast/stitch-mcp tool get_screen_image -d '{"projectId":"4177037855941203861","screenId":"screen-id-aqui"}' -o json
```

### Construir un sitio desde pantallas Stitch

```bash
npx @_davideast/stitch-mcp tool build_site -d '{
  "projectId":"4177037855941203861",
  "routes":[
    {"screenId":"screen-id-aqui","route":"/"}
  ]
}' -o json
```

## Flujo recomendado para este repo

1. Crear o reutilizar el proyecto Stitch.
2. Crear/actualizar el design system antes de generar pantallas.
3. Generar primero los módulos prioritarios:
   - `login/`
   - `projects/`
   - `programa-general/`
   - `programacion-intermedia/`
   - `programacion-semanal/`
4. Pedir 4 variantes por módulo.
5. Revisar pantalla por pantalla con `get_screen_code` o `get_screen_image`.
6. Aplicar el design system a las pantallas resultantes si todavía no quedó aplicado.
7. Ajustar el brief antes de expandir al resto de módulos.

## Brief de diseño base

Usar siempre el documento:

- `docs/stitch-aia-design-system.md`

Ese brief define:

- Apple liquid glass like
- Verde corporativo como base
- Naranja de construcción como acento operativo
- Modo claro linen y modo oscuro
- Tipografía Montserrat + Inter
- Reglas de responsive y estados

## Troubleshooting

### Error de autenticación

Si aparece `Authentication failed. Provide either 'apiKey' OR ('accessToken' + 'projectId')`:

- Usa `STITCH_ACCESS_TOKEN` + `GOOGLE_CLOUD_PROJECT`
- O define `STITCH_API_KEY` en `.env`

### Error con `-f`

Si un comando falla con algo parecido a `Bun is not defined`, usa `-d` con JSON inline en lugar de `-f`.

### No aparecen cambios en OpenCode

- Reinicia OpenCode
- Revisa `~/.config/opencode/opencode.json`
- Verifica que el proxy use `STITCH_USE_SYSTEM_GCLOUD=1`

### No existe borrado duro de pantallas

- Stitch MCP no expone una herramienta `delete_screen`.
- Si necesitas retirar pantallas creadas por error, la opción práctica es neutralizarlas con `edit_screens` o crear un proyecto limpio nuevo.

### API key en `.env`

- Sí, puede vivir en `.env`
- No debe commitearse
- Úsala solo si necesitas auth directa en lugar de OAuth/ADC

## Regla local

- No usar `registrate/` como objetivo de diseño.
- La creación de usuarios se centralizó en `admin/`.
