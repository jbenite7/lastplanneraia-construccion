---
capa: fuente
tipo: reporte
estado: vigente
fecha: 2026-09-03
areas: [proceso, seguridad]
tags: [autorizacion]
fuente: decisiones/merge-carril-rojo-pr23.md
resumen: Autorización de Felipe para mergear el PR #23 con design-system-runtime en rojo, y por qué los seis gates rojos no son suyos
---

# Autorización — merge del PR #23 con el carril visual en rojo

**Qué se decide.** Mergear el [PR #23](https://github.com/jbenite7/lastplanneraia-construccion/pull/23)
a `main` aunque el job `design-system-runtime` esté en rojo, y cerrar el
[#22](https://github.com/jbenite7/lastplanneraia-construccion/pull/22) a favor suyo.

**Quién y cuándo.** Felipe, en el chat, el 2026-09-03, después de que se le reportara el estado
gate por gate y de que la recomendación de la sesión fuera **no** meter más trabajo en ese PR. La
preocupación por el gate se planteó explícitamente antes de ejecutar; él reafirmó. Merge
`6021e347`.

**Por qué importa dejarlo escrito.** `AGENTS.md` § Publicación dice que un PR con CI rojo no se
mergea. Esta es una excepción autorizada, no un descuido ni un precedente: la próxima sesión que
vea `main` con ese job en rojo debe encontrar aquí el porqué en vez de deducirlo.

**Qué se midió, con sha.** Corrida `33759660935`, tema `dark`, sha `d419072d`:

- `G_LABORATORY_GATES: success` — las 24 pruebas del 403, corriendo por primera vez desde el
  2026-08-29 y pasando. Es lo que el PR venía a demostrar.
- Pasan además `G_PILOT_LAB_GATES`, `G_PG_PERSISTENCE_RBAC`, `G_SEMANAL_ROLES_PHASES`,
  `G_RUNTIME_GRANTS`, `G_PHP_ADMIN_DB`, `G_RUNTIME_BUDGET_MEASURE`, y el job `design-system-static`
  completo.
- Fallan seis: `G_PHPSTAN_BASELINE`, `G_PHPSTAN_PDC`, `G_PHP_SUITE`, `G_FULL_APP_FLOW`,
  `G_RUNTIME_BUDGET_CHECK`, `G_KEYBOARD_REFLOW_EVIDENCE`. **Ninguno causado por el PR**, atribuidos
  uno por uno en `TASKS.md` › Bloqueantes (2026-09-03) contra la línea base de `main` `58d11137`.

**Lo que NO autoriza esta decisión.** Nada más que ese merge. En particular: no autoriza silenciar
ninguno de los seis gates, ni registrar los 7 fingerprints de PHPStan en
`docs/design-system/phpstan-baseline.json` para forzar verde —lo que `AGENTS.md` prohíbe por
nombre—, ni regenerar capturas o presupuestos visuales. Y no autoriza deploy a producción, que
sigue exigiendo su palabra propia y explícita.

**Qué quedó pendiente.** El frente «poner el carril visual en verde», con los seis gates y su
evidencia, anotado en `TASKS.md`. La condición de hecho es cada gate arreglado en su causa, ninguno
tapado.
