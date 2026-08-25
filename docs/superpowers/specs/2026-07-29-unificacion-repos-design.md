---
capa: fuente
tipo: spec
estado: cerrado
fecha: 2026-07-29
areas: [proceso]
fuente: docs/superpowers/specs/2026-07-29-unificacion-repos-design.md
resumen: Unificar plan-de-compras dentro de lastplanneraia-construccion — diseño
---

# Unificar `plan-de-compras` dentro de `lastplanneraia-construccion` — diseño

Fecha: 2026-07-29. Decidido con el usuario en el grilleo de esta fecha.

## Por qué

El módulo PDC v2 vive hoy partido en dos repositorios: la SPA (React + Vite + AG Grid) en
`plan-de-compras`, y el «glue» PHP —vista shell, endpoints, servicios, migraciones— en `lps-aia`.
El reparto tenía sentido cuando la SPA era un experimento con stack propio. Ya no: el submódulo A
está completo y en producción de `main`, y cada cambio real toca los dos lados a la vez.

El costo de la separación dejó de ser teórico. El 2026-07-29, al integrar el cierre del submódulo A,
apareció un **bundle publicado cuya fuente no estaba versionada**: la sesión que construyó el panel
de «Sin frente» commiteó `public/pdc-app/assets/pdc.js` en lps-aia y dejó sin commitear
`planFechas.ts`, `PlanFechas.tsx` y su test en el otro repo. El artefacto viajó; su fuente no. Ese
error solo es posible porque construir y publicar son dos repos distintos.

## Qué se decide

Seis decisiones, todas tomadas en el grilleo:

1. **La SPA vive en `pdc-app/`, con su propio `package.json`.** No se fusionan las dependencias con
   el `package.json` de la raíz: ese lo usan a diario las sesiones de design-system y BI (biome,
   playwright, `node --test`), y meterles React, Vite y AG Grid en su árbol de dependencias les
   cambia el `npm install` y el alcance de sus linters sin darles nada a cambio.
2. **Se conserva el historial con `git subtree`.** Los 114 commits entran con sus rutas reescritas
   bajo `pdc-app/`. En este proyecto el porqué de cada decisión vive en los mensajes de commit, no
   en comentarios: perder `git blame` sobre la SPA sería perder la mitad de la documentación.
3. **La documentación se fusiona con la que ya existe.** Los 22 specs y planes van a
   `docs/superpowers/`, junto a los 13 de lps-aia; los 5 `goals/` a `goals/`, junto a los 6 de allá.
   Verificado: **ningún nombre choca** — los cinco de la SPA ya llevan prefijo `pdc-`. Unificar y
   dejar dos sitios donde buscar specs sería no unificar.
4. **El build escribe directo a `public/pdc-app/`.** Mueren `dist/` y `scripts/sync-to-lps.sh`. Es
   la corrección estructural del error descrito arriba: si compilar ya deja el archivo en su destino
   servido, no hay un paso de copia que olvidar.
5. **El conocimiento del PDC va a `docs/pdc-v2.md`**, con ~10 líneas de puntero en el `CLAUDE.md`
   raíz. Las 290 líneas actuales triplicarían un archivo que se carga en **cada** sesión del repo,
   incluidas las que solo tocan CSS. No se anida como `pdc-app/CLAUDE.md` porque buena parte de ese
   conocimiento describe PHP que vive fuera de esa carpeta (`src/Services/Pdc/`, migraciones), donde
   un CLAUDE.md anidado no se cargaría.
6. **El repo viejo se archiva en GitHub; la carpeta local se conserva** con un aviso en su
   `CLAUDE.md`. Archivar es reversible con un clic y evita que una sesión futura commitee ahí por
   costumbre, que es exactamente cómo el trabajo se volvería a partir en dos.

## Arquitectura resultante

```
lps-aia/
├── pdc-app/              # la SPA: src/, package.json, vite.config.ts, index.html
├── public/pdc-app/       # bundle publicado — destino directo del build
├── src/Services/Pdc/     # el PHP del módulo, sin moverse
├── docs/superpowers/     # 13 specs de lps-aia + 22 del PDC
├── docs/pdc-v2.md        # conocimiento del PDC (antes CLAUDE.md de la SPA)
├── goals/                # 6 carpetas de lps-aia + 5 del PDC
├── CLAUDE.md             # +10 líneas de puntero
└── package.json          # intacto: biome, playwright, node --test
```

`vite.config.ts` conserva los nombres de salida fijos `assets/pdc.js` y `assets/pdc.css`: el shell
PHP (`views/plan-compras/app.view.php`) los referencia por nombre. El servidor de desarrollo mantiene
su proxy hacia el Docker, con el puerto por variable de entorno — hoy 8091 en el worktree del PDC y
8081 en el árbol principal.

## Orden de ejecución

Cuatro commits separados, para que un fallo se revierta solo hasta donde llegó:

1. `git subtree add --prefix=pdc-app` desde el repo local.
2. Mover documentación (`git mv`) a `docs/superpowers/` y `goals/`.
3. `CLAUDE.md` de la SPA → `docs/pdc-v2.md` + puntero en el raíz.
4. Ajustar `vite.config.ts` (outDir), `package.json` (scripts) y borrar `scripts/sync-to-lps.sh`.

Se ejecuta en el worktree `/Volumes/Crucial X6/Developer/lps-aia-pdc`, sobre rama propia. A `main`
solo al final, con todo verificado, y comprobando antes que `origin/main` no se haya movido.

## Verificación

La prueba central es de **equivalencia, no de funcionamiento**: reconstruir desde el código ya mudado
y exigir que el bundle salga **byte a byte idéntico** al publicado hoy
(`md5 3b51ff54a8523fb8ddda5c881568dd80`).
Si cambia un byte, algo se movió que no debía, y se sabe antes de tocar `main`.

Además: los 242 tests de Vitest corriendo desde `pdc-app/`; las seis suites PHP del PDC; los dos
gates `test_global_table_safety` y `test_global_table_reconciliation`; PHPStan; y `git log --follow`
sobre un archivo de la SPA, que debe seguir mostrando su historia anterior a la mudanza.

## Fuera de alcance

No se toca el PHP del módulo, no se cambia una línea de la SPA, no se unifican los `package.json` y
no se modifica el modelo de despliegue: el bundle se sigue commiteando porque el deploy a SiteGround
es `git pull`.

## Riesgos

- **Otras sesiones activas.** El árbol principal de lps-aia tiene 47 commits sin subir. La superficie
  de choque de esta mudanza son solo `CLAUDE.md` y `.gitignore` en la raíz: `pdc-app/` no existe hoy.
- **`git subtree` deja un merge commit grande.** Es el precio de conservar `blame`; se acepta.
- **El bundle podría no salir idéntico** por diferencias de entorno (versión de Node, orden de
  módulos). Si pasa, se investiga la causa antes de continuar: un bundle distinto sin explicación es
  un cambio de comportamiento no auditado.

---

## Estado verificado — cerrado

Verificado contra el código el 2026-08-25. **`estado: cerrado` es una afirmación deliberada**, no el valor por defecto del backfill.

**Evidencia:** pdc-app/ en la raiz con package.json y vite.config.ts; public/pdc-app/ como destino del build; docs/pdc-v2.md referenciado desde CLAUDE.md

Criterio y método: [[docs/superpowers/plans/2026-08-25-estado-real-de-planes-y-specs]].
