# Saneamiento de las deudas abiertas del goal de usabilidad

**Fecha:** 2026-08-03
**Rama:** `worktree-usabilidad-altas-y-medias`
**Base:** `e80d14b`
**Estado:** aprobado

## Por qué

Ejecutando las cuatro primeras tareas del plan
[2026-08-03-usabilidad-altas-y-medias](../plans/2026-08-03-usabilidad-altas-y-medias.md)
aparecieron cinco deudas que el plan no preveía. Una de ellas rompe al fusionar a `main`, y otra
hizo que unos tests midieran la rama equivocada durante varias vueltas de diagnóstico. Quedan 23
tareas por delante: conviene cerrar las trampas antes de recorrerlas, no después.

Este spec cubre **solo las deudas de entorno y verificación**. Las dos que exigen decisiones ajenas
a este goal se derivan a su propio ciclo (ver «Fuera de alcance»).

## Alcance

Tres piezas independientes, un commit cada una. Ninguna toca código de producción.

### Pieza 1 · Los specs dejan de fijar el puerto

**El daño:** `tests/browser/bi-kpi-copy.spec.mjs` y `tests/browser/escalamientos-sin-errores.spec.mjs`
llevan `http://localhost:8091` escrito a fuego. Ese puerto lo publica el stack **de este worktree**;
en `main` no existe. Al fusionar, ambos specs atacarán un puerto muerto y fallarán sin explicar por
qué.

**El arreglo:** importar `BASE_URL` de `./fixtures/base-url.mjs` y construir las URL con él, igual
que hace ya `tests/browser/escalamientos-acciones.spec.mjs`. `BASE_URL` se deriva del stack del
working tree, así que resuelve al puerto correcto en cada checkout sin que nadie lo configure.

**Por qué esto y no un guardarraíl más elaborado:** se consideró que los specs verificasen al
arrancar que atacan el stack correcto. Ya existe ese guardarraíl —`razonStackDistinto()` en
`tests/browser/support/pdc-sandbox.mjs`— y no se disparó, porque el problema no era un `E2E_BASE_URL`
mal apuntado: era que `BASE_URL` se calculaba bien y los specs lo ignoraban. Con el puerto fijo
fuera, el guardarraíl existente vuelve a ser suficiente.

**Verificación:** los tres specs de escalamientos y el de BI en verde, **y** comprobando que las
peticiones salen al 8091 y no al 8081. Esta segunda comprobación no es redundante: el fallo original
consistía justamente en pasar en verde contra el stack equivocado.

### Pieza 2 · El entorno del worktree deja de ser un secreto

**El daño:** `docker compose` a secas, desde este worktree, resuelve el stack del checkout principal
(8081), porque el override propio solo entra con un tercer `-f`. Eso arrastra a `BASE_URL`, a
`sqlEnApp()` y a cualquier comando que una sesión escriba de memoria. Se corrigió declarando
`COMPOSE_FILE` en el `.env` del worktree, pero `.env` no se versiona: la próxima sesión que abra este
worktree no tiene forma de enterarse.

**El arreglo:** dejarlo escrito en los dos sitios que una sesión sí lee — la cabecera de
`docker-compose.usabilidad.yml` (que ya documenta el resto del andamiaje) y el ledger
`.superpowers/sdd/2026-08-03-usabilidad-altas-y-medias/progress.md`.

**Qué NO se hace:** no se versiona nada nuevo. Tanto `docker-compose.usabilidad.yml` como `.env` son
locales por diseño —el propio compose lo dice en su primera línea— y desaparecen cuando el worktree
se cierre. Construir infraestructura versionada para sostenerlos sería trabajo con fecha de
caducidad.

**Efecto secundario ya conocido, que conviene dejar escrito:** con `COMPOSE_FILE` declarado,
`docker compose exec db` deja de existir en este worktree. No es un fallo: el override anula `db` y
`adminer` a propósito, porque la base la sirve el stack principal. Para SQL directo se usa
`docker compose exec -T app php -r "…"` o se ejecuta desde el checkout principal.

### Pieza 3 · La prueba de la Task 2 mira la mitad que le faltaba

**El daño:** `tests/browser/bi-kpi-copy.spec.mjs` solo afirma que la cadena `count` **no** aparece. El
brief de aquella tarea pedía además garantizar que `%` y las demás unidades **siguen** viéndose. Tal
como está, un cambio que borrase todas las unidades pasaría en verde.

**El arreglo:** añadir al mismo spec la afirmación que falta, sobre un KPI que hoy muestra `%`.

## Fuera de alcance

Ambas salen de aquí con una acción concreta, no con un «ya se verá».

### El CSS que otra rama dejó a medias

El commit `ad14fc1`, llegado con el merge de `main` (goal `pg-chip-de-estado`), añadió
`tests/design-system/ops-state-contract.test.mjs:157`, que lee
`public/css/design-system/components/ops-state-chip.css`. **Ese archivo no existe.** Por eso
`npm run test:design-system:static` nunca está limpio, para todo el mundo, no solo aquí.

No lo arreglamos: crear una hoja del design system sin conocer las decisiones visuales de ese goal es
justo lo que el contrato de `docs/design-system/` prohíbe. **Acción:** chip a su goal, y el rojo
queda anotado como conocido en el ledger para que un rojo *nuevo* siga siendo detectable.

### La FK que impide cerrar una crisis

`LpsApiController` guarda en `lps_escalamientos.usuario_cierre_id` el `Id` de `general_usuarios`,
pero la FK `fk_le__profesionales__usuario_cierre` apunta a `profesionales(project_id, id)`. Son dos
espacios de identificadores distintos: en el sandbox, test.R es 368 y los profesionales del proyecto
990100 son 1, 2 y 3. El `try/catch` lo convierte en un «No se pudo mitigar la crisis» genérico.
Afecta a las cuatro superficies que cierran crisis.

No cabe aquí porque no tiene arreglo obvio: `profesionales` y `general_usuarios` solo comparten
`nombre`/`email`, no hay clave que las una, y ningún otro punto del código escribe ese campo, así que
no hay precedente que copiar. Hay que **decidir a quién representa «quién cerró la crisis»**, y según
la respuesta puede exigir migración de esquema — con las reglas duras de `AGENTS.md`: dry-run,
respaldo verificable y plan de restauración. **Acción:** chip ya creado, con su propio ciclo
spec → plan.

### Las dos políticas de unidades en BI

`valueWithUnit()` (`public/js/modules/bi-spa.js:3661`) muestra cualquier unidad salvo `count` —lista
negra—, mientras que `renderPDC` (línea 2733) muestra solo `%` —lista blanca—. No es código
duplicado: son criterios **opuestos**. Si el backend emite una unidad nueva, una la enseña y la otra
la esconde.

Unificarlas cambia lo que se ve en pantalla, y `AGENTS.md` exige aprobación explícita para los
cambios visuales. **Acción:** queda documentado aquí como hallazgo; unificarlo es una decisión de
producto, no de saneamiento.

## Riesgo y verificación

Riesgo bajo: ninguna de las tres piezas toca código de producción. Se modifican dos specs, un
comentario de compose y el ledger.

Verificación de cierre:

- `npx playwright test tests/browser/escalamientos-sin-errores.spec.mjs tests/browser/escalamientos-acciones.spec.mjs tests/browser/bi-kpi-copy.spec.mjs --workers=1` en verde.
- Las peticiones de esos specs salen al puerto del stack de este worktree (8091), comprobado, no supuesto.
- `npm run test:design-system:static` con el árbol limpio: **un solo rojo**, el heredado del CSS
  ausente. Un segundo rojo sería nuestro.

Nota sobre ese suite, medida en esta tanda y que conviene no volver a olvidar: el gate
`canonical design-system contracts pass the executable gate` exige `worktree and index must be
clean`. Medir la línea base con cambios sin commitear lo cuenta como rojo propio y despista.

## Archivos de este goal

- Plan en curso: [2026-08-03-usabilidad-altas-y-medias](../plans/2026-08-03-usabilidad-altas-y-medias.md)
- Inventario de origen: [inventario-usabilidad](../../../goals/repaso-usabilidad-no-tablas/inventario-usabilidad.md)
- Estado de los goals: [estado](../../../memoria/goals/estado.md)
