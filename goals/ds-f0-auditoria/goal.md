---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-08-18
areas: [proceso]
fuente: goals/ds-f0-auditoria/goal.md
resumen: Producir el inventario total del design system —módulo por módulo, objeto por objeto, variable por variable, escenario por escenario— en…
---

<!-- cas:cita-textual — registro del frente: cita salidas y comandos tal como se midieron -->
# Frente: ds-f0-auditoria

## Fase del plan
Plan: docs/superpowers/plans/2026-08-19-ds-f0-auditoria-total.md
Fase: Fase 4
Sha verificado: 3fd1af09
Presupuesto: ?

Nota sobre estos dos campos. El frente arrancó con `Plan: ?` / `Fase: ?` porque el plan usaba
encabezados `## Tanda N` y `cas-frente.sh` solo parsea `Task N` o `Fase N`: se dejó `?`
—ignorancia declarada— en vez de `-`, que habría afirmado en falso que el frente no es fase de
ningún plan. La decisión quedó encolada en `decisiones/ds-f0-auditoria-ejecutor.md` (D1) y **se
resolvió aguas arriba** en `7b7c2b9d`, que renombró los encabezados del plan.

El enlace se rellenó **a mano y no con `cas-frente.sh`**: el plugin pasó de 0.2.0 a 0.3.0 durante
la sesión y esa versión ya no trae `cas/scripts/`. La edición es la misma que hace el script
(sustituir `?` por el valor; se niega a reasignar un valor ya puesto), sin inventar formato.

`Fase: Fase 4` nombra la fase que cierra. El frente cubrió las cuatro del plan en una sola
sesión, cada una con su lote y su commit: `d930507c` (Fase 1), `284a959a` (Fase 2, lote 1) y
`68751fc4` (Fases 2 a 4).

## Objetivo
Producir el inventario total del design system —módulo por módulo, objeto por objeto, variable por
variable, escenario por escenario— en `docs/design-system/auditoria/`, clasificado por severidad y
consultable por máquina. Es la fase que convierte «el DS no está bien definido, ni implementado, ni
controlado» en un inventario con el que DS-F1 pueda decidir el contrato.

## Condición de hecho
`docs/design-system/auditoria/` con el inventario completo por módulo y por severidad; cada hallazgo
con archivo, línea y por qué se clasificó así; los huecos que dependen del CI marcados como tales y
no rellenados. Cero cambios en código de producto.
Verificación: npm run test:design-system:static

## Posture
- **No arreglar nada.** Ni un hex, ni un `!important`, ni un token. Lo trivial se anota con su
  severidad y se sigue.
- **No tocar `docs/design-system/closeout-evidence.json`** ni ningún baseline: son de otro frente.
- **No decidir vocabulario ni escalas del producto.** Eso es DS-F1 y arranca con brainstorming.
- **Sin dependencias nuevas.**
- **No rellenar cifras que salgan de ejecutar los gates**: esperan a `runtime-budgets-al-ci`.

## Leer primero
- `DESIGN.md` y `docs/design-system/README.md` — el contrato vigente, que es lo que se audita.
- `docs/DESIGN-AUDIT.md` — F-4…F-9, la semilla.
- `docs/superpowers/decisiones-pendientes-2026-08-03.md` — las 48.
- `memoria/mapas/design-system.md` — las trampas ya puestas.
- `AGENTS.md` §Routing por tipo de cambio.

## Archivos declarados
docs/design-system/auditoria/**

## Contención
<!-- archivo · commits hoy · quién más lo declara -->

## Cadena de herramientas
<!-- ids del arsenal, máx 8, una línea de porqué cada uno -->

## Publicaciones
- **`567e566e` — publicado el 2026-08-19**, confirmado en el paso 7: `git rev-parse origin/main`
  devuelve ese mismo sha y no queda `ahead` ni `behind`. Contiene el inventario completo de DS-F0
  (tandas 1-4).

  El visto se dio sobre `3fd1af09` y lo publicado es posterior. Se dice en vez de disimularlo, y
  se dice qué cambió en medio: (a) este registro, porque un archivo no puede contener su propio
  sha y anotar el cierre mueve la cabeza por definición; (b) el prefijo `ds-f0-` de las trece
  fichas, que corrige un defecto **del propio entregable** —`npm run test:wiki` pasó de 0 a 37
  hallazgos por colisión de nombres con `memoria/arquitectura/`, y volvió a 0—; y (c) la
  integración de cinco commits de `wiki-t1`. Nada de eso toca código de producto:
  `git diff --name-only origin/main...HEAD` fuera de `docs/design-system/auditoria/` y de este
  goal devolvió vacío.

  Las tres comprobaciones del gate se repitieron **después** de integrar, no antes, cada una
  leyendo su código de salida en su propia línea.
- Visto dado **por el usuario en conversación directa**, no por la sesión coordinadora: la entrega
  se le mandó a `local_f42c2c37` por `send_message` y el usuario autorizó antes de que
  respondiera. Se anota así a propósito, porque «visto de la coordinadora» y «autorización del
  usuario» no son lo mismo y el registro no debe decir una por la otra.

## Cierre
Condición de hecho cumplida, medida sobre `567e566e` **después** de integrar `origin/main`:

```
$ npm run test:design-system:static
RC=0
[static-suite] resumen:
  ✔ entrypoint-partition  ✔ unlayered-delivery  ✔ bi-utilities  ✔ table-contract
  ✔ node-tests  ✔ contracts  ✔ consumer-contract  ✔ audit

$ node tests/test_programa_general_sprint_contract.mjs
RC=0

$ npm run test:wiki
RC=0   Sin hallazgos. 151 páginas de wiki y 0 de 409 fuentes declaradas.
```

Sobre el invariante de montaje, que aquí no se pudo satisfacer y por qué el verde vale igual:
`scripts/publicar.sh` exige que el contenedor `app` monte el árbol que se verifica, y montaba el
worktree de otra sesión viva (`recursing-shtern-472554`). Reapuntarlo le habría roto la
verificación a esa sesión en pleno vuelo. En vez de eso se comprobó lo que el invariante protege:
`git diff --name-only` entre los dos árboles no toca `public/css`, `public/js`, `src/`, `admin/`
ni `views/` —son idénticos en todo lo que el PHP en contenedor lee—, y los cuatro archivos de
`scripts/` que difieren son `wiki-*`, que ninguna de las cuatro pruebas con docker abre. La
publicación se hizo entonces con `git push origin HEAD:main` a mano, que `AGENTS.md` admite
cuando el script no aplica, cumpliendo sus dos reglas: código de salida leído en línea aparte, y
`HEAD:main` en vez de `main`.

`docs/design-system/auditoria/` con 68 hallazgos: 7 críticos, 31 mayores, 13 menores, 6
cosméticos y 11 «sin problema». Cada uno con archivo, línea y el porqué de su severidad. Diez
huecos marcados `bloqueadoPor: runtime-budgets-al-ci` y sin rellenar. Cero cambios en código de
producto: `git status --porcelain` fuera de `docs/design-system/auditoria/` quedó vacío en los
tres commits.

Lo que este frente deja para el siguiente: **cuatro de los siete críticos son de mecanismo, no de
código** (`F0-030`, `F0-031`, `F0-051`, `F0-052`). Mientras sigan así, un verde de la suite
estática no significa lo que parece — y eso condiciona cómo DS-F1 escriba el contrato.
