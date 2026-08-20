---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-08-19
areas: [lps]
fuente: goals/semanal-fondo-por-matiz/goal.md
resumen: Llevar el fondo de fila de /programacion-semanal del sistema propio de cubos de alerta al modelo de tres canales ya publicado: el matiz dice qué estado es, el…
---

<!-- cas:cita-textual — registro del frente: cita salidas y comandos tal como se midieron -->
# Frente: semanal-fondo-por-matiz

## Fase del plan
Plan: docs/superpowers/plans/2026-08-19-semanal-fondo-por-matiz.md
Fase: ?
Sha verificado: ?
Presupuesto: ?

## Objetivo
Llevar el fondo de fila de `/programacion-semanal` del sistema propio de cubos de alerta al modelo
de tres canales ya publicado: **el matiz dice qué estado es, el filete dice cuán grave, el orden
desempata**. Su contrato y su chip ya se arreglaron y publicaron en `c766a338`; lo que falta es el
fondo, que es el único sitio donde sobreviven las colisiones.

## Condición de hecho
Los cinco estados de **cada fase** pintan cinco fondos distintos, medidos con **color computado
contra computado** a 1180×820 dark por sesión real; el filete aparece solo en `urgent` y
`attention`; y ningún par de estados de la misma fase comparte fondo.
Verificación: `bash scripts/publicar.sh --solo-verificar`

## El problema, medido antes de arrancar

`WEEKLY_ALERT_MODEL` (`public/js/modules/programacion_semanal/hot.js:117-191`) asigna a cada estado
una clase de **cubo de alerta**, y **diez estados colapsan en cinco cubos**:

| Estado | Cubo actual |
|---|---|
| `prog-bloqueo-critico-sin-compromiso` | `ps-alert-critical-route` |
| `prog-ejecucion-con-restricciones` | `ps-alert-high` |
| `prog-condiciones-pendientes` | `ps-alert-medium` |
| **`prog-sin-compromiso`** | **`ps-alert-medium`** ← colisión en fase Programación |
| `prog-lista-para-confirmar` | `ps-alert-control` |
| `cal-incumplida-critica` | `ps-alert-critical-route` |
| `cal-incumplida` | `ps-alert-medium` |
| **`cal-sin-calificar`** | **`ps-alert-medium`** ← colisión en fase Calificación |
| `cal-cumplida-control` | `ps-alert-control` |
| `cal-tnp` | `ps-alert-tnp` |

**Las dos colisiones son exactamente las que ya se desempataron en el contrato y en el chip**
(«Por Comprometer» → violeta, «Sin Calificar» → gris, publicado en `37479689`). El fondo de la fila
no se enteró porque no consume matiz: consume cubo. **Hoy el chip y la fila de la misma actividad
dicen cosas distintas.**

Las repeticiones **entre** fases (`critical-route` en las dos, `control` en las dos) son inocuas:
`stateMachine.js:58` resuelve una fase u otra según `semanalConfirmada`, así que nunca conviven.

## Posture
- **No tocar los hex de `--ds-state-tint-*`.** Ocho anclas, cerradas por test.
- **No regenerar ningún golden sin aprobación visual explícita del usuario**, por su nombre.
- **No ablandar ningún test**: si cambia, cambia declarando qué mide ahora.
- **No tocar `/programacion-intermedia` ni `/programa-general`**, ya cerrados o en cola ajena.
- **No tomar el contenedor compartido sin ventana pedida a la coordinadora.**
- Sin dependencias nuevas.

## Leer primero
- `docs/design-system/state-semantics.json` — módulo `programacion-semanal`, con los matices ya
  desempatados y `axisRules`.
- `public/js/modules/programacion_semanal/hot.js` — `WEEKLY_ALERT_MODEL` y `statePresentation`.
- `public/js/modules/programacion_semanal/stateMachine.js:58` — cómo se resuelve la fase.
- `public/css/design-system/components/severity-rail.css` — la primitiva del filete.
- `goals/ds-f1a-estados-severidad/goal.md` — el frente hermano ya publicado, y sus dos trampas.

## Archivos declarados
goals/semanal-fondo-por-matiz/**, docs/superpowers/specs/*-semanal-fondo-por-matiz*,
docs/superpowers/plans/*-semanal-fondo-por-matiz*, public/css/programacion-semanal.css,
public/js/modules/programacion_semanal/hot.js, public/css/styles.css,
tests/design-system/**, tests/browser/__screenshots__/**,
memoria/trampas/important-invierte-el-orden-de-capas.md, memoria/log.md

> Los dos últimos se añadieron el 2026-08-19 **por autorización expresa de la coordinadora**, por
> nombre exacto y no como glob abierto, para escribir la trampa de capas medida al convertir.

## Contención — medida el 2026-08-19 antes de arrancar
- `public/css/programacion-semanal.css` → **0 commits hoy**
- `public/js/modules/programacion_semanal/stateMachine.js` → **0**
- `public/js/modules/programacion_semanal/hot.js` → 1, mío (`37479689`)
- `public/css/styles.css` → 3, míos
- **Ningún otro frente declara archivos de Semanal.** `estados-fuera-de-ventana` declara
  `estado_programa_general.php`, `LpsService.php` y `ds-f1a-escala-estado.*`: cero solape.
- **Archivo caliente: `docs/design-system/state-semantics.json`**, tocado hoy por `ds-f1a-estado` y
  por mí. Si este frente lo edita, se avisa a la coordinadora antes.

## Por qué no lleva spec propia
La dirección ya está decidida, aprobada y **publicada**: el modelo de tres canales y su regla
`axisRules` viven en el contrato desde `c766a338`, y este frente no decide nada de vocabulario ni de
niveles — solo lleva una superficie más al modelo que ya existe. La spec que lo gobierna es
`docs/superpowers/specs/2026-08-19-estados-severidad-contrato-design.md`. Sí lleva **plan**, porque
el gate lo exige y porque hay goldens de por medio.

## Biome: medido y decidido, no ignorado
`npm run check:frontend` no es carril del gate (ni de `publicar.sh` ni de la suite estática) y el
repo está en **905 errores / 113 archivos** de base. Medido lo mío contra `origin/main`, archivo por
archivo:

| Archivo | Antes | Ahora |
|---|---|---|
| `programacion-semanal.css` | 0 | **0** |
| `styles.css` | 3 | **3** |
| `programacion_semanal/hot.js` | 207 | **213** |

Los seis que añadí son **`noInnerDeclarations`** —regla que este archivo ya incumple **201 veces**,
porque es un IIFE de estilo ES5 con funciones internas— más uno de `useTemplate`, por concatenar
cadenas como hace todo el archivo. **Mis funciones siguen el estilo circundante a propósito.**
Refactorizar el estilo del módulo para bajar seis diagnósticos de una regla incumplida 201 veces
sería un refactor de cortesía dentro de un frente ajeno a eso.

## Hallazgo encargado
La coordinadora pidió anotar, si aparecen al convertir, **cuántas filas de Semanal serían «detenido
por otro»** — el dato que ayuda a decidir el `r0` de Programa General, hoy en la mesa de Felipe.

## Cierre

**Cerrado el 2026-08-19**, sobre el sha `4f76d3b1` (`origin/main` integrado: 38 commits, sin
conflictos). El trabajo estaba terminado desde el 13:39; lo que faltaba era la verificacion que lo
demostrara, y al hacerla apareció un defecto **en la sonda, no en el producto**.

### Condicion de hecho, medida despues de integrar

| Comprobacion | Resultado |
|---|---|
| `bash scripts/publicar.sh --solo-verificar` | 4/4 en `RC=0` (dos bloqueantes: `design-system:static`, `contrato piloto PG`) |
| Fase **Programacion** | 5 filas, **5 fondos distintos**, filete solo en `urgent` y `attention` |
| Fase **Calificacion** | 5 filas, **5 fondos distintos**, filete solo en `urgent` y `attention` |
| Captura mirada, 1180x820 dark, puerta de servicio | las dos fases, encabezado correcto en cada una |

Ningun par de estados de la misma fase comparte fondo. Las dos colisiones que abrieron el frente
—«Por Comprometer» contra «Condiciones Pendientes», y «Sin Calificar» contra «Incumplida»— quedan
desempatadas: `#33204a` frente a `#3a3a0f`, y `#2b2f2d` frente a `#3a3a0f`.

### El defecto que casi firma un cierre falso

La sonda **no forzaba la fase**, y lo declaraba en voz alta. Reescribia
`id="Semanal_Confirmada" value="0"` en el HTML del servidor e imprimia «fase forzada a
calificacion» comprobando **su propia sustitucion de texto**, nunca el efecto. Al reejecutarla, la
fase Calificacion salio con cinco filas en fase Programacion —dos fondos, estados `prog-*`— mientras
la sonda seguia diciendo que habia forzado la fase.

**Causa raiz, medida instrumentando el setter de `.value`** y leyendo el stack: el unico escritor de
ese input es `public/js/cargarDatosGeneralesPagina2.js:183`, dentro del `success` del AJAX a
`datosGeneralesPagina.php`, y pisa siempre lo que pinto el PHP. La clave ademas viaja en `data`, no
en la raiz de la respuesta (`cargarDatosGeneralesPagina2.js:120`, `datosGenerales =
json_info_global['data']`) — inyectarla en la raiz no hace nada, y eso costo una hipotesis fallida.
Segundo factor: al entrar al proyecto la app ya aterriza en `/programacion-semanal`, asi que un
`page.route` registrado despues no ve esa primera carga.

**Por que salio verde el 13:39 y rojo hoy no se sabe con certeza**, y no se finge: las semanas de Da
Porto estan hoy en `Semanal_Confirmada = 0`, y el `apply` del recalculo de estados (`aa965bf5`) corrio
un minuto despues de aquella medicion. Lo que si esta probado es que el mecanismo de forzado nunca
pudo funcionar por si mismo.

**Arreglo.** La sonda fuerza la fase donde de verdad se decide —inyecta
`json.data.Semanal_Confirmada = 1` en la respuesta del AJAX— y registra el route **antes** de la
primera navegacion. Y sobre todo, **ahora puede ponerse roja**: comprueba la fase efectiva en el DOM,
que ningun estado sea ajeno a la fase pedida y que no haya colision de fondos, y sale con `1`.
Comprobado desactivando el forzado a proposito: `RC=1` y los tres fallos nombrados.

**La prueba de que el producto estaba bien:** la medicion regenerada con la sonda arreglada es
**identica byte a byte** a la que quedo commiteada el 13:39 (`git diff` vacio sobre
`medicion-ps-calificacion.json`). Lo roto era el instrumento.

### Familia de la trampa

Es la tercera vez que se mide lo mismo en este repo: un guard que **valida su propia declaracion en
vez del efecto** —hermana de [[memoria/trampas/guard-de-texto-no-ve-el-parseo]] y de
[[memoria/trampas/guard-valida-declaracion-contra-si-misma]]. Merece ficha propia en
`memoria/trampas/`; no se escribe aqui porque este frente no declara esa ruta, y queda anotado como
pendiente.

### Hallazgo encargado, sin respuesta

La coordinadora pidio contar **cuantas filas de Semanal serian «detenido por otro»**, para decidir el
`r0` de Programa General. **No aparecieron al convertir**: el vocabulario de Semanal no tiene ese
concepto en ninguna de sus dos fases, ni en `WEEKLY_ALERT_MODEL` ni en `stateMachine.js`. El dato que
se buscaba no existe de este lado, y esa ausencia es en si misma la respuesta.

## Archivos de este goal
- [[docs/superpowers/plans/2026-08-19-semanal-fondo-por-matiz]]
- [[docs/superpowers/specs/2026-08-19-estados-severidad-contrato-design]]
- [[goals/ds-f1a-estados-severidad/goal]] · [[memoria/goals/estado]]
