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

## Publicaciones
- **`4a152a54` — publicado el 2026-08-19** con `bash scripts/publicar.sh`, el camino oficial.
  Confirmado en el paso 7: `git rev-parse origin/main` devuelve ese sha y no queda `ahead` ni
  `behind`.

  **El visto se emitió dos veces y la primera caducó sin usarse.** El de `5716c15d` quedó
  inválido cuando entró `70dd3946` a `origin/main` y hubo que integrar; se pidió uno nuevo en vez
  de publicar con el viejo, porque «incluye lo revisado» no es «es lo revisado». El segundo,
  `.claude/vistos/ds-f1a-estado-4a152a54.md`, lleva el sha dentro y es el que el gate comparó.

  **La ventana del contenedor la autorizó la coordinadora**, que congeló su uso por otras
  sesiones mientras duró: se reapuntó `app` a este worktree con `LPS_CODE_ROOT`, se corrió
  `publicar.sh` completo y **se devolvió a la raíz al terminar** — verificado, monta
  `/Users/felipebenitez/Developer/lps-aia`.

## Cierre
Condición de hecho cumplida sobre `4a152a54`, medida después de integrar y confirmada por el
propio gate de publicación:

```
$ bash scripts/publicar.sh
Verificando sobre 4a152a54…
  ✔ design-system:static               RC=0
  ✔ contrato piloto PG                 RC=0
  ✔ wiki (lint + veracidad)            RC=0
Publicando…
   70dd3946..4a152a54  HEAD -> main
```

Entregado: `docs/design-system/ds-f1a-escala-estado.{json,md}` —trece estados, tres niveles de
gravedad, la regla de los dos canales y el origen de cada estado—, la prueba de nueve casos que
ata los dos archivos, y el contrato nombrado desde el índice del design system. Cero cambios en
código de producto en todo el frente.

**Dos incidentes que costaron tiempo y quedan dichos**, porque los dos son de entorno y no de
código:

1. **La re-verificación posterior a integrar salió RC=1 y no era un defecto.** El fallo fue
   `foundation.test.mjs:273` con `service "app" is not running`: el contenedor se estaba
   recreando en ese instante — llevaba 41 segundos arriba al mirarlo. Con él sano, verde. El
   paso 5 hizo su trabajo dos veces: cazó el rojo y permitió distinguir que era una carrera.
2. **A este worktree le faltaba el enlace del `.env`** que `CLAUDE.md` manda crear en cada
   worktree nuevo, y llevaba así todo el frente. No causó el fallo —el test solo hacía
   `filemtime`— pero lo habría causado en cuanto una prueba tocara la base, con un diagnóstico
   mucho más confuso. Enlazado.

**Lo que este frente deja abierto y no le toca decidir:** si `Fuera de Ventana` es etiqueta de
pantalla o valor persistido. Si es lo segundo, es una migración sobre 16 proyectos con respaldo,
dry-run y gate de tablas globales. Está escrito en el contrato legible, sección «Pendiente de
decisión del usuario», y la coordinadora lo sube con el parte del cierre.
