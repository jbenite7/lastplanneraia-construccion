# Invertir la paleta de estado del design system a oscuro

Fecha: 2026-07-28 · Base: `e32e7e8`

## El problema

Esta aplicación es solo oscura desde que el goal `dark-mode-todos-los-modulos` retiró el tema
`linen`. Pero los cuatro colores de estado del design system siguen siendo los del tema claro:

```
--ds-color-state-critical-bg: #fdecec   --ds-color-state-critical-text: #8f1d1d
--ds-color-state-warning-bg:  #fff8e1   --ds-color-state-warning-text:  #5d4200
--ds-color-state-success-bg:  #ddefe6   --ds-color-state-success-text:  #1a5633
--ds-color-state-info-bg:     #e3f9f7   --ds-color-state-info-text:     #006d66
```

El design system tiene indirección de tema para superficies, texto y bordes (`--ds-active-surface`,
`--ds-active-text-primary`…). **Los colores de estado no la tienen**: se consumen en crudo. Cada
módulo ha ido tapándolos por su cuenta, y esa es la raíz de casi todo lo que se ha arreglado en las
últimas sesiones:

- `adapters/legacy-bridge.css` duplica las cuatro reglas de nivel en la última capa para que el
  design system alcance las superficies de módulo.
- Las hojas de módulo declaran `background` encima de los chips, lo que dejaba inerte la primitiva
  de `states-feedback.css`.
- `--pg-dot-*` se deriva a mano en OKLCH porque mezclar contra `--ds-color-state-*-bg` —un pastel
  casi blanco— devolvía gris.
- /pdc mantuvo durante meses siete hex propios porque los tokens compartidos no le servían.

## La decisión

Los cuatro pares se **invierten**: el fondo pasa a ser oscuro y el texto claro tintado, tomando los
valores que /pdc ya tiene elegidos y medidos.

| Token | Hoy | Nuevo | Contraste medido |
|---|---|---|---|
| `critical-bg` / `-text` | `#fdecec` / `#8f1d1d` | `#431414` / `#ffcdc8` | 10,99:1 |
| `warning-bg` / `-text` | `#fff8e1` / `#5d4200` | `#3a3a0f` / `#f2e79c` | 9,31:1 |
| `success-bg` / `-text` | `#ddefe6` / `#1a5633` | `#173d26` / `#b7e8c6` | 8,88:1 |
| `info-bg` / `-text` | `#e3f9f7` / `#006d66` | `#134841` / *derivado* | por medir |

/pdc no aporta un texto teal, así que el de `info` se deriva del ancla con la misma receta que los
otros tres —aclarar conservando el matiz— y se mide antes de fijarlo.

**Los dos vocabularios conviven a propósito:** cuatro tokens de NIVEL (prioridad de acción) y ocho
de MATIZ (identidad del estado, `--ds-state-tint-*`). Miden cosas distintas y fundirlos borraría el
eje que costó separar.

**Los valores claros no se borran.** Se conservan con nombre propio, documentados como paleta de
documento impreso: el export XLSX es un documento blanco y hoy los tiene duplicados a mano en
`src/Controllers/Gestion/ReportController.php`.

## Alcance

`--ds-color-state-*` tiene **297 usos en 23 hojas**. La mayoría son operandos de `color-mix` y
bordes que ya se mezclan contra superficies oscuras y no corren peligro. Los que importan:

| | |
|---|---|
| Parejas `bg`+`text` en el mismo bloque | **52** — se mueven juntas y siguen legibles |
| `-bg` sin su `-text` | **13** |
| `-text` sin su `-bg` | **26** |

Los **39 descompensados** están concentrados: 16 en `programacion-semanal.css`, 7 en
`listado-actividades.css`, 3 en BI, 2 en `contratos.css`, 2 en `navigation.css`, 1 en
`login-brand-unified.css`.

**Dentro:** los cuatro tokens, los 39 sitios, `.aia-chip`, `.aia-feedback`, los parches de
`background` de las hojas de módulo, y la verificación de login y BI.

**Fuera:** la convergencia del componente de chip (va después, como refactor que no debe mover un
píxel, y así cualquier cambio visible es atribuible); la paleta de marca `--aia-*`, que es un tercer
vocabulario y merece su propia decisión; el export XLSX más allá de darle una fuente legítima.

## Secuencia

Cuatro pasos, cada uno con una pregunta distinta que responder. Mezclarlos hace que ninguna se pueda
responder.

1. **Clasificar los 39** en un commit que no cambia ningún color: cada sitio se marca como «se
   empareja», «ya estaba mal» o «era claro a propósito». Deja el terreno preparado y hace que
   cualquier cosa rara en el paso 2 sea atribuible al cambio de token.
2. **Invertir los cuatro pares.** Un commit. Aquí cambia el aspecto de toda la app.
3. **Retirar los parches de módulo**, un módulo por commit, con medición antes y después. Sin esto
   el token nuevo no llega a verse justo donde más importa.
4. **Verificar** las cuatro rutas operativas más login y BI, en el navegador.

## Superficies claras deliberadas

Algunas son claras a propósito y hay que protegerlas antes del paso 2. La conocida es
`.ps-reopen-header`, la cabecera del modal de reabrir semana: un comentario del repo ya advierte que
un token oscuro fijo la rompería. Se inventarían todas y se declaran como excepción explícita con su
porqué junto a la regla.

## Verificación

- `npm run test:design-system:static`
- `npm run test:a11y:lab` — la baseline dejará de coincidir. **No se regenera de golpe**: se revisa
  hallazgo por hallazgo, que es la regla del repo y aquí además hay motivo para esperar mejoras
  (los pares nuevos miden 8,88–10,99:1 frente a los actuales).
- Navegador en `/pdc`, `/programacion-intermedia`, `/programacion-semanal`, `/programa-general`,
  `/login` y Control Tower, a 1180×820 en dark, proyecto Da Porto.
- Baselines visuales: **no regenerar**. El cambio propio se mide comparando el número de píxeles
  antes y después, porque varias ya están rojas por un reflow ajeno.

## Riesgos

- **El paso 2 cambia toda la app en un commit**, incluidas superficies que nadie ha revisado en esta
  línea de trabajo. Mitigación: el paso 1 deja los 39 descompensados ya resueltos, así que lo que
  quede es el comportamiento esperado del token.
- **`.aia-feedback`** aparece en momentos de tensión (un guardado que falla) y pasa a oscuro con el
  mismo cambio. Se verifica en pantalla explícitamente.
- **El login** es la única pantalla que se ve antes de autenticarse y es fácil que se rompa sin que
  nadie lo note. Se verifica con captura.
- **Sesiones paralelas activas** en este repo empujan a `main` con frecuencia y ya dejaron obsoleta
  una medición a mitad de camino. Re-medir antes de cada paso que dependa de un valor.
