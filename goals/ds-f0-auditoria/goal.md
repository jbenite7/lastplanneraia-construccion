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
- Inventario completo de DS-F0 (tandas 1-4). **El visto se dio sobre `3fd1af09`** y lo que se
  publica es el commit siguiente, que solo añade este registro: un archivo no puede contener su
  propio sha, así que anotar el cierre mueve la cabeza por definición. Se dice en vez de
  disimularlo. El contenido técnico visado —los tres commits del inventario— es idéntico en ambos;
  `git diff --stat 3fd1af09..HEAD` toca **un** archivo, este, y ningún gate lo mide. La suite se
  volvió a correr igualmente sobre la cabeza real antes de publicar, y el sha publicado queda
  confirmado en el paso 7 contra `origin/main`.
- Visto dado **por el usuario en conversación directa**, no por la sesión coordinadora: la entrega
  se le mandó a `local_f42c2c37` por `send_message` y el usuario autorizó antes de que
  respondiera. Se anota así a propósito, porque «visto de la coordinadora» y «autorización del
  usuario» no son lo mismo y el registro no debe decir una por la otra.

## Cierre
Condición de hecho cumplida, medida después de integrar `origin/main` y repetida sobre la cabeza
que se publica:

```
$ npm run test:design-system:static
RC=0
[static-suite] resumen:
  ✔ entrypoint-partition  ✔ unlayered-delivery  ✔ bi-utilities  ✔ table-contract
  ✔ node-tests  ✔ contracts  ✔ consumer-contract  ✔ audit
```

`docs/design-system/auditoria/` con 68 hallazgos: 7 críticos, 31 mayores, 13 menores, 6
cosméticos y 11 «sin problema». Cada uno con archivo, línea y el porqué de su severidad. Diez
huecos marcados `bloqueadoPor: runtime-budgets-al-ci` y sin rellenar. Cero cambios en código de
producto: `git status --porcelain` fuera de `docs/design-system/auditoria/` quedó vacío en los
tres commits.

Lo que este frente deja para el siguiente: **cuatro de los siete críticos son de mecanismo, no de
código** (`F0-030`, `F0-031`, `F0-051`, `F0-052`). Mientras sigan así, un verde de la suite
estática no significa lo que parece — y eso condiciona cómo DS-F1 escriba el contrato.
