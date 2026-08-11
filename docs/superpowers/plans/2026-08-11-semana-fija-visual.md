# Plan — fijar la semana en la prueba visual de PI

- Spec: [`2026-08-11-semana-fija-visual-design.md`](../specs/2026-08-11-semana-fija-visual-design.md)
- Frente: `semana-fija-visual` · sesión 06e4383d · sha de arranque `6dd69bb7`

## Archivos

- `tests/browser/programacion-intermedia.visual.mjs` — el fixture.
- Solo si el diff queda explicado: los dos `.png` de
  `tests/browser/__screenshots__/programacion-intermedia.visual.mjs/` y sus `sha256` en
  `docs/design-system/manifests/programacion-intermedia.json`.

**No se tocan** la vista ni el CSS de la leyenda (contención con `vocabulario-estados-cascada`).

## Paso 1 — el escenario fija la semana

En `mockDeterministicData`, o justo tras `loginAndSelectProject`, fijar la semana con la ruta que
ya existe, usando el contexto autenticado de Playwright:

```js
// La semana se pinta en servidor (shell_sidebar.php:24, desde $_SESSION['semana']), asi que
// ningun page.route la alcanza: cuando llega al cliente ya esta en el HTML. Se fija por la ruta
// que la aplicacion ya usa. Sin esto la captura retrata la semana en la que este el proyecto el
// dia que se corra, y el golden falla por algo que no mide.
await page.request.post('/context/week', { data: { semana: 1 } });
```

Semana **1**, que es la que retrata el golden.

## Paso 2 — correr y mirar el diff

`E2E_BASE_URL` apuntando al stack que sirve **este** worktree, no al principal
(`tests/browser/fixtures/base-url.mjs` cae a 8081 si el worktree no tiene stack de compose propio).

Resultados posibles, y qué hacer con cada uno:

- **El diff queda vacío** → no hay nada que regenerar. La aprobación no se usa, y mejor así.
- **Queda solo la zona del botón** → paso 3.
- **Queda algo más** → **parar y avisar**, sin tocar el golden.

## Paso 3 — «Restricción Compartida»: diagnosticar, no arreglar

Con la semana ya fija, el diff tiene una sola variable. Comparar la zona contra el golden y
determinar la causa (cambio de estilo del primario desde el `11b8d93c` del 2026-08-07, u otra).

**Sea cual sea el resultado, en este frente no se arregla.** Si es un cambio de diseño real e
intencionado, entra en lo que la regeneración debe recoger, y se dice explícitamente al enseñar el
antes/después. Si es un defecto, **se encola con la evidencia**.

## Paso 4 — regeneración, solo si todo lo que queda está explicado

1. Generar las dos capturas nuevas (1180×820 y 1440×900).
2. **Enseñar antes y después a la coordinadora, para el usuario, ANTES de tocar ningún `sha256`.**
3. Solo con su confirmación: escribir los `.png` y recalcular las dos firmas del manifiesto.
4. Dejar escrito **qué cambió, quién lo aprobó, cuándo y sobre qué sha**.

## Paso 5 — la prueba tiene que saber fallar

Con el golden nuevo puesto, introducir un cambio visible a propósito, correr, **ver el rojo**, y
revertir. Se entrega con la salida real de esa corrida. Un golden que ya no caza nada es peor que
el que había.

## Condición de hecho

La prueba deja de fallar por una razón que no mide nada; lo que quede del diff está explicado o
encolado; y si se regeneró, hay antes/después aprobado y mutación ejecutada. El gate `runtime`
sigue `blocked` por otras causas y **no se pretende ponerlo verde**.
