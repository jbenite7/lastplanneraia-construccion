---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-08-19
areas: [proceso]
fuente: goals/ds-f1a-estado/goal.md
resumen: Publicar el contrato de la escala de estado —vocabulario, tres niveles de gravedad y la regla de los dos canales— en formato legible y consultable por máquina…
---

<!-- cas:cita-textual — registro del frente: cita salidas y comandos tal como se midieron -->
# Frente: ds-f1a-estado

## Fase del plan
Plan: docs/superpowers/plans/2026-08-19-ds-f1a-estado.md
Fase: -
Sha verificado: ?
Presupuesto: ?

`Fase: -` porque el plan de este frente no divide en fases numeradas sino en tres tareas, y
`cas-frente.sh` solo parsea encabezados `Task N` o `Fase N`. No es que se desconozca: es que la
unidad de este plan es la tarea, y las tres se ejecutaron en una sesión.

## Objetivo
Publicar el contrato de la escala de estado —vocabulario, tres niveles de gravedad y la regla de
los dos canales— en formato legible y consultable por máquina, con una prueba que lo vigile. Es el
primero de los cuatro contratos en que el usuario partió DS-F1.

## Condición de hecho
`docs/design-system/ds-f1a-escala-estado.{json,md}` con los trece estados, sus niveles, su origen y
la regla de los dos canales; una prueba que ate los dos archivos; y el contrato nombrado desde el
índice del design system. Cero cambios en código de producto.
Verificación: npm run test:design-system:static

## Posture
- **No tocar `state-semantics.json`** ni ningún baseline ni golden.
- **No implementar CSS**: construir los dos canales es DS-F2.
- **No renombrar valores persistidos** sin decidir antes si el cambio es de presentación o de datos.
- **Sin dependencias nuevas.**
- **Prefijo `ds-f1a` en todo `.md` nuevo**, por la colisión de wikilinks del vault-en-raíz.

## Leer primero
- `docs/superpowers/specs/2026-08-19-ds-f1a-estado-design.md` — el diseño aprobado.
- `goals/bug-coloreado-severidad/insumo-ds-f1.md` — lo ya medido, para no volver a medirlo.
- `decisiones/vocabulario-estados-cascada.md` — D-VOC-1 a D-VOC-4 y las respuestas del usuario.
- `docs/design-system/auditoria/transversal.md` — los gates que no ven su propia deuda.

## Archivos declarados
docs/superpowers/specs/*-ds-f1a-*,docs/superpowers/plans/*-ds-f1a-*,goals/ds-f1a-estado/**,docs/design-system/ds-f1a-*,tests/design-system/ds-f1a-*,docs/design-system/README.md,memoria/trampas/publicar-sh-*,memoria/trampas/vault-en-raiz-*,memoria/log.md

## Contención
`docs/design-system/README.md` lo declara también `wiki-t2` (`f8dcf2b1`) vía `docs/**`. La
inserción de cuatro líneas la **autorizó la coordinadora** con dos condiciones, ambas cumplidas:
rutas ampliadas al archivo exacto y no a `docs/design-system/**`, e integración de `origin/main`
antes de editar. Ella avisó a `wiki-t2` para que no lo reescribiera debajo.

`memoria/log.md` dio conflicto al integrar: dos líneas de este frente contra dos de wiki, pura
adición por ambos lados. Resuelto conservando las cuatro. Trivial, así que no se devolvió.
