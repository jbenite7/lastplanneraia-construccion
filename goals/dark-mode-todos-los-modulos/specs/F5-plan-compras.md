# F5 · plan-compras

**Depende de:** F0. Independiente del resto; puede correr en cualquier momento tras F0.
**Riesgo:** medio — el trabajo principal ocurre en **otro repositorio**.

## Objetivo

Que la isla React de Plan de Compras consuma los tokens canónicos y quede bajo gate
verificable, en lugar de fijar el tema a mano y esconder su CSS fuera del alcance de los
controles de este repositorio.

## Estado

`/plan-compras` se sirve desde `views/plan-compras/app.view.php`, un shell mínimo:

```php
<html lang="es" data-aia-theme="dark">
  ...
  <link rel="stylesheet" href="/css/tokens.css?v=…">
  <link rel="stylesheet" href="/pdc-app/assets/pdc.css?v=…">
  ...
  <script type="module" src="/pdc-app/assets/pdc.js?v=…"></script>
```

Características:

- El tema se fija con un **atributo escrito a mano** en `<html>`, sin `theme-bootstrap.js`. Es
  el tercer mecanismo de tema del repositorio (F0 unifica los otros dos y deja éste para aquí).
- Carga `tokens.css`, así que las variables `--ds-*` están disponibles — pero nada garantiza
  que `pdc.css` las use.
- `pdc.css` y `pdc.js` se compilan en el repositorio externo **`plan-de-compras`** (`npm run
  sync`) y llegan compilados a `public/pdc-app/`. **Ningún gate de este repositorio los ve**:
  no están en `scanRoots` del audit, no tienen manifiesto, y `public/pdc-app/` es artefacto de
  build, no fuente.
- No carga el entrypoint del design system, así que no hereda componentes ni adaptadores.

## Decisión habilitante

Decisión 14: **tenemos acceso y autoridad sobre `plan-de-compras`**. F5 es, por tanto, un spec
de implementación en dos repositorios, no sólo de contrato.

## Alcance

### En este repositorio

#### T5.1 — Shell bajo contrato

`app.view.php` pasa a emitir `theme-bootstrap.js` mediante `DesignSystemHeadComponent` y
elimina el `data-aia-theme` escrito a mano. Con F0 aplicado, `:root` ya sirve dark, así que la
isla queda oscura aunque el script fallara.

#### T5.2 — Manifiesto

Crear `docs/design-system/manifests/plan-compras.json` con la ruta `/plan-compras`, los
vendors reales de la isla y, en `sources`, tanto el shell PHP como los artefactos de
`public/pdc-app/`. Registrar en `inventory.json`.

#### T5.3 — Gate de artefacto

`public/pdc-app/assets/pdc.css` es artefacto compilado: auditarlo con las mismas reglas que el
CSS fuente produciría ruido inútil (bundlers minifican y reescriben). El gate debe verificar el
**contrato**, no el estilo:

- El bundle no declara colores propios fuera de `var(--ds-*)`, salvo lo registrado en
  `exceptions.json`.
- El bundle no define `[data-aia-theme]` ni redefine tokens `--ds-*`.
- La versión de design system contra la que se compiló coincide con
  `docs/design-system/version.json`.

Se implementa como script nuevo bajo `scripts/`, invocado desde el enrutador de gates.

#### T5.4 — Verificación en runtime

Prueba de navegador que carga `/plan-compras` en `1180×820` dark y comprueba que las
superficies principales resuelven a los valores de `--ds-active-*`, no a colores propios. Es
la red de seguridad frente a un `npm run sync` que traiga una regresión desde el otro
repositorio.

### En el repositorio `plan-de-compras`

#### T5.5 — Migrar a tokens compartidos

Inventariar los colores propios del código fuente de la isla y sustituirlos por
`var(--ds-active-*)` / `var(--ds-*)`, que ya están disponibles porque el shell carga
`tokens.css`.

#### T5.6 — Declarar la versión de DS

El build emite la versión de design system contra la que compiló, para que T5.3 pueda
compararla.

#### T5.7 — Publicar

`npm run sync` trae el bundle actualizado; el commit en este repositorio incluye artefacto,
manifiesto y evidencia juntos.

## Dependencia de secuencia

T5.5 ocurre en el otro repositorio y su ritmo no lo controla este plan. T5.1 a T5.3 pueden
completarse antes, de forma independiente. **T5.4 y el cierre de F5 requieren que T5.7 haya
llegado.**

## Fuera de alcance

- Rediseñar la interfaz de Plan de Compras.
- Cambiar la lógica de la isla ni los endpoints `/plan-compras/api/*`.
- Absorber el repositorio externo dentro de éste.

## Verificación

```bash
node scripts/design-system-audit.mjs
node scripts/design-system-entrypoint-partition.mjs
npm run test:design-system:static
```

Más el gate de artefacto de T5.3 y la prueba de runtime de T5.4.

En navegador: `/plan-compras` en `1180×820` dark, consola y red limpias, sin destello de tema
al cargar, contraste AA en las superficies principales.

## Riesgos

| Riesgo | Mitigación |
|---|---|
| El otro repositorio se desincroniza y trae una regresión | T5.4 la detecta en CI de este repositorio |
| El gate de artefacto produce falsos positivos por minificación | Verifica contrato, no estilo: presencia de `var(--ds-*)` y ausencia de redefinición de tokens |
| La versión de DS diverge entre repositorios | T5.6 la declara y T5.3 la compara |

## Criterio de cierre

1. `app.view.php` sin atributo de tema escrito a mano, con `theme-bootstrap.js`.
2. `plan-compras.json` existe y valida.
3. Gate de artefacto activo en el enrutador de gates.
4. Prueba de runtime en verde.
5. El bundle publicado consume tokens compartidos.
6. Evidencia visual en `evidence/F5/`.
