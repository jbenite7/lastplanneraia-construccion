# F2 · Las nueve superficies del agregador

**Depende de:** F1. Puede correr en paralelo con F3.
**Riesgo:** medio — nueve trabajos independientes, ninguno estructural.

## Objetivo

Que las nueve superficies hoy marcadas `inventory-only` pasen a tener manifiesto, presupuesto
y evidencia propios, de modo que ninguna pueda regresar a claro sin que CI falle.

## Problema

Estas nueve superficies cargan el design system y hoy arrancan en dark, pero **nada lo
garantiza**: no tienen manifiesto, no aparecen en `pathBudgets` del audit y no tienen evidencia
visual versionada. Son las que más deuda concentran por vista.

| Superficie | Vista | `<style>` | inline | hex | `rgba()` |
|---|---|---|---|---|---|
| programa-general-actualizar | `views/programa-general-actualizar/programaGeneralActualizar.view.php` | 1 | 25 | 55 | 14 |
| profesionales | `views/profesionales/profesionales.view.php` | 1 | 7 | 21 | 4 |
| subcontratistas | `views/subcontratistas/subcontratistas.view.php` | 1 | 9 | 18 | 5 |
| dashboard/escalamientos | `views/dashboard/escalamientos.php` | 1 | 3 | 2 | 33 |
| control-cambios | `views/control-cambios/controlCambios.view.php` | 1 | 8 | 0 | 0 |
| indicadores | `views/indicadores/indicadores.view.php` | 0 | 4 | 0 | 0 |
| listado-actividades | `views/listado-actividades/listadoActividades.view.php` | 0 | 0 | 1 | 0 |
| contratos | `views/contratos/contratos.view.php` | 0 | 0 | 0 | 0 |
| pdc | `views/pdc/pdc.view.php` | 0 | 4 | 0 | 0 |

`listado-actividades`, `contratos` y `pdc` ya tienen presupuesto de ruta en el audit
(435, 140 y 368 hallazgos emparejados) pero **no manifiesto**, así que no participan del
contrato de consumo ni del gate de partición del entrypoint.

## Alcance

Un trabajo por superficie, nueve en total, independientes entre sí y paralelizables.

### Patrón por superficie

1. **Crear el manifiesto** en `docs/design-system/manifests/<moduleId>.json`, siguiendo
   `module-manifest.schema.json` y el patrón de los siete existentes: `moduleId`, `routes`,
   `sources`, `components`, `vendors`, `layouts`, `states`, `roles`, `persistence`,
   `exceptions`, `tests`, `evidence`, `scenarios`.
2. **Migrar el head** de `DesignSystemHeadComponent::render()` a
   `renderForModule('<moduleId>')`, declarando en `vendors[]` sólo los realmente usados. El
   componente degrada al agregador si el manifiesto falla, así que el cambio es seguro por
   construcción.
3. **Vaciar la vista de estilos**: eliminar el bloque `<style>`, los `style="…"` y los hex,
   llevándolos a la hoja del módulo en su capa correcta y tokenizados contra `--ds-active-*`.
4. **Declarar presupuesto de ruta** en el audit, con cero para `hardcoded-hex`,
   `hardcoded-color-function`, `inline-style`, `embedded-style-block`, `forbidden-font-roboto`
   y `hardcoded-radius`, igual que los presupuestos de `login` y `programa-general`.
5. **Registrar en `inventory.json`** con estado `pilot` y su manifiesto.
6. **Evidencia visual** en `1180×820` dark, archivada en `evidence/F2/<moduleId>/`.

### Notas por superficie

- **`programa-general-actualizar`** es la de mayor deuda del goal (95 infracciones en la
  vista). Conviene atacarla primero: lo que se aprenda ahí abarata las ocho restantes.
  Cuidado: `DESIGN.md` protege Programa General y sus archivos; verificar que esta vista, que
  es la de *actualización*, no comparte hojas con el piloto antes de tocar nada.
- **`indicadores`** embebe un informe de Power BI *publish-to-web* por iframe. El contenido del
  iframe es ajeno y **no es tematizable**: se documenta como excepción permanente en
  `exceptions.json`, no como deuda pendiente. El alcance es el marco alrededor del iframe.
- **`profesionales`** y **`subcontratistas`** comparten un bloque `<style>` casi idéntico cuyo
  propósito declarado es «prevenir que `styles.css` rompa el layout de Handsontable». Tras F1
  ese bloque debería ser innecesario; verificarlo antes de reescribirlo.
- **`dashboard/escalamientos`** concentra 33 `rgba()` y 7 radios hardcodeados en un solo
  `<style>`: es tokenización directa, sin ambigüedad de diseño.
- **`contratos`** ya está limpia en la vista; su trabajo es sólo manifiesto, presupuesto y
  evidencia.

## Fuera de alcance

- Rediseñar ninguna de las nueve. F2 es normalización, no diseño.
- Las rutas `/bi/*` (F3) y `admin/` (F4).
- El contenido del iframe de Power BI.

## Verificación

Por superficie:

```bash
node scripts/design-system-audit.mjs
node scripts/design-system-entrypoint-partition.mjs
npm run test:design-system:static
```

Al cerrar cada superficie: validación funcional del módulo en navegador contra el contenedor
servido, consola y red limpias, foco visible, objetivos de 44 px, y captura en `1180×820`
dark. Un rol permitido y uno denegado si la superficie tiene restricción por capacidad.

## Criterio de cierre

1. Nueve manifiestos nuevos, validados contra `module-manifest.schema.json`.
2. Nueve entradas nuevas en `pathBudgets` con presupuesto cero en las seis reglas duras.
3. Ninguna de las nueve vistas conserva `<style>`, `style="…"` ni hex.
4. `inventory.json` sin ninguna entrada `inventory-only`.
5. Evidencia visual de las nueve en `evidence/F2/`.
