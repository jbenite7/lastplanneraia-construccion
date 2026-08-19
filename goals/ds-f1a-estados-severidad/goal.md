<!-- cas:cita-textual — registro del frente: cita salidas y comandos tal como se midieron -->
# Frente: ds-f1a-estados-severidad

## Fase del plan
Plan: docs/superpowers/plans/2026-08-19-estados-severidad-contrato.md
Fase: Tasks 1 a 8 — el frente ejecutó el plan entero en una sesión
Sha verificado: (ver `## Publicaciones`)
Presupuesto: ?

## Objetivo
Que cada canal visual de las tablas de estado codifique **un solo eje**: el color dice qué estado
es, el filete dice cuán grave, el orden desempata. Es el primero de los tres cajones en que se
partió DS-F1, y el único que llegó con dirección ya decidida por el usuario.

## Condición de hecho
El contrato declara la regla de un-canal-un-eje y los niveles corregidos; existe la primitiva del
filete con su ficha y su aprobación visual; las superficies aplicables pintan identidad en el fondo
y gravedad en el filete, medido con **color computado contra computado** a 1180×820 dark por sesión
real; el botón de agrupar existe y arranca apagado.
Verificación: `bash scripts/publicar.sh --solo-verificar`

## Posture
- No tocar los hex de `--ds-state-tint-*`.
- No regenerar ningún golden sin aprobación visual explícita del usuario, por su nombre.
- No ablandar ningún test: si cambia, cambia declarando qué mide ahora.
- No arreglar «de paso» nada del inventario de DS-F0 que no sea de estados.
- Sin dependencias nuevas.

## Leer primero
- `goals/bug-coloreado-severidad/` — el diagnóstico medido y la dirección, con su procedencia.
- `docs/superpowers/specs/2026-08-19-estados-severidad-contrato-design.md`
- `docs/design-system/state-semantics.json`

## Archivos declarados
docs/superpowers/specs/**, docs/superpowers/plans/**, goals/ds-f1a-estados-severidad/**,
docs/design-system/**, public/css/**, public/js/modules/**, tests/**, views/**

## Resultado

**El entregable más duradero es una regla, no CSS:** `axisRules` en el contrato dice que ningún
canal codifica dos cosas. Eso es lo que se había roto.

| Superficie | Qué se hizo |
|---|---|
| `/programacion-intermedia` | Fondo por matiz (8 colores, cero pares idénticos), filete por nivel, botón de agrupar, leyenda alineada, goldens conciliados |
| `/programa-general` | Fondo por matiz (7 colores), filete por nivel, leyenda alineada, goldens conciliados |
| `/programacion-semanal` | **A medias**: contrato y chip arreglados (dos empates de ámbar deshechos); el fondo sigue en su sistema propio de cubos |
| `/plan-compras` | **No aplica** — ver abajo |

**Cuatro de los ocho niveles de Intermedia cambiaron**, uno de ellos revirtiendo a sabiendas una
ratificación del propietario del producto del 2026-08-03. La procedencia de cada nivel —cuál decidió
el usuario y cuáles propuso el implementador y él confirmó— se conserva en
`goals/bug-coloreado-severidad/respuestas-ds-f1.md` **a propósito**: fundirlas convertiría el
criterio del implementador en el del usuario sin dejar rastro.

**Resultado no buscado que valida las decisiones:** los cajones P1/P2/P3 de la Guía Operativa
coinciden ahora **uno a uno** con los niveles del contrato. La tercera autoridad que discrepaba dejó
de discrepar sin tocarla.

### Lo que se aprendió, y costó dos veces

**Medir no es mirar.** Dos entregas distintas llegaron con todas las medidas en verde y la pantalla
rota: el filete puesto en cada `<td>` dibujaba diecisiete barras por fila, y en Programa General una
regla vieja en otro archivo le ganaba a la nueva. Las dos las cazó **una captura**, no un assert.
`railGrosor` daba los cuatro grosores correctos y «pares idénticos: ninguno» estaba verde en ambos
casos. Es la misma trampa del frente que originó este —`contadores-cero`, `severidad-runtime`— y
sigue viva.

### Dos guards mejorados de paso, ninguno ablandado

- `state-tint-ladder` comparaba los diez estados de Semanal **como si convivieran** y tapaba el
  resultado con una lista de matices tolerados que mezclaba repeticiones inocuas entre fases con
  colisiones reales dentro de una fase. Se sustituyó por el predicado que compara **por fase**: es
  **más estricto** y ya no necesita lista.
- `states-feedback` buscaba la aprobación de familia solo por `familyId`. Con una sola aprobación
  acertaba por casualidad; con dos habría pasado mirando otra cosa. Ahora busca por el par
  familia+candidato. **El defecto ya estaba, latente.**

## Pendientes
- **VERIFICACIÓN EN PANTALLA PENDIENTE del apagado del filete (`1ff946f8`).** Está verificado en
  estático —`severity-rail` 7/7, suite 8/8— pero **sin captura**. Falta confirmar en
  `/programacion-intermedia` a 1180×820 dark que las filas `healthy`/`neutral` dejaron de llevar
  filete y que ninguna superposición de Handsontable (`pi-cell-readonly`, `pi-cell-editable`) se
  coló en su sitio. Ventana de contenedor concedida por la coordinadora **con turno**: espera a que
  `bold-neumann` publique y restaure. **No se toma el contenedor sin esa confirmación** — hacerlo
  hoy ya costó una medición entera a otro frente.
- **`/programacion-semanal`, conversión del fondo** → frente propio. No es «aplicar lo mismo»: su
  fondo usa un sistema propio de cubos de alerta (`ps-alert-*`), otra escalera ordinal como la que
  se retiró de Intermedia, y hay que rehacerlo en sus dos fases con sus goldens.
- **`r0` de Programa General perdió su color propio** → frente propio. Significaba «detenido por
  otro» y tenía el único ancla propia de la escala (`--ds-cell-state-bloqueado-bg`); ahora es ámbar
  como sus tres vecinos, porque el contrato declara **un** estado donde el módulo pinta **cuatro**.
  Devolvérselo es **añadir un estado al contrato**, y `teal` está libre en esa superficie.
- **Los siete estados fantasma de `/plan-compras`** → cajón de DS-F1 que decide qué significa
  «cubierto». El contrato los declara y **nadie los pinta**; el guard los da por buenos porque
  comprueba que los tokens nombren la paleta, que es una declaración validándose contra sí misma.
- **`states-feedback.css:162`** sigue siendo letra muerta por `legacy-bridge.css:104-142`.
- **`#fef3c7` embebido en `hot.js:2857`**, crema clara sobre tema oscuro en la leyenda de Intermedia.
- **`--ds-cell-state-*` NO queda huérfano**: lo sigue consumiendo `public/css/handsontable-module.css`.
  Comprobado antes de cerrar; no se borra nada.

## Publicaciones
**Ninguna. Este frente no ha publicado nada.**

La línea anterior decía que había publicado con `scripts/publicar.sh` y era falsa: se escribió
anticipando el paso 8, que nunca llegó a ejecutarse porque el gate destapó el frente hermano justo
al integrar. Se corrige en vez de borrarse — `## Publicaciones` es un hecho que otros leen, y una
publicación inventada es peor que ninguna.

## Cierre

**RETIRADO el 2026-08-19, el mismo día que se escribió.** Esta sección afirmaba que el frente
terminaba aquí, y dejó de ser cierta media hora después. Se sustituye en vez de borrarse, porque la
presencia de `## Cierre` es el hecho que derivan el mapa de estado y el aviso de fase previa: dejarla
puesta haría mentir a los dos a la vez, y ninguno se pondría rojo.

**Por qué se retira.** Al integrar `origin/main` para publicar apareció el frente hermano
`ds-f1a-estado` (worktree `bold-neumann-485f23`), que había publicado un contrato de **la misma
escala** midiendo contra **50.966 actividades reales**. Los dos frentes se contradicen:

| | Este frente | `ds-f1a-estado` |
|---|---|---|
| Niveles | cuatro | **tres** |
| Filete | en todas las filas | **solo en el 21,3% que pide algo**; la ausencia es la señal |
| Medido contra | el contrato y 9 filas de fixture | **50.966 actividades, 16 proyectos** |

Y sus mediciones invalidan parte de lo que este frente pintó en Programa General:
**`Con Alerta Restricciones` no existe en ninguna de las 65.549 filas** —y aquí se le dio color y se
aplanó `r0` dentro— mientras **`Fuera de Ventana`, el 24,2%**, no está declarado ni pintado.

**Decisión de Felipe, 2026-08-19, con las dos propuestas delante:** manda el contrato del frente
hermano —tres niveles y su vocabulario de trece estados—; esta maquinaria se adapta (filete apagado
en `controlado`, Programa General remapeado a los estados reales); y **se coordina con esa sesión
antes de tocar nada**.

**Causa raíz, sin adornos:** no se midió la contención antes de arrancar. La regla existe —«¿qué
archivos toca esto y quién más los está tocando?»— y un `git log` de dos minutos lo habría cazado.
Los dos frentes actuaron de buena fe con decisiones directas del usuario, en la ventana en que no
había coordinadora.

## Estado real
**Pausado, integrado y sin publicar.** La rama tiene `origin/main` integrado, el conflicto de
`memoria/log.md` resuelto conservando las dos ramas, y la suite en verde. Espera respuesta de
`bold-neumann-485f23`, que además tiene **vivo** el frente `estados-fuera-de-ventana` tocando
`src/Legacy/estado_programa_general.php` y `LpsService.php`: remapear los colores de Programa
General mientras cambia su cálculo sería construir sobre terreno que se mueve.

**Lo que sobrevive intacto**, porque es maquinaria y no vocabulario: la primitiva `severity-rail` con
su aprobación visual, `axisRules`, `/programacion-intermedia` entera con sus goldens aprobados —su
vocabulario no lo toca el frente hermano—, el botón de agrupar, y los dos guards mejorados.

## Archivos de este goal
- [[docs/superpowers/specs/2026-08-19-estados-severidad-contrato-design]]
- [[docs/superpowers/plans/2026-08-19-estados-severidad-contrato]]
- [[goals/bug-coloreado-severidad/respuestas-ds-f1]] · [[goals/bug-coloreado-severidad/diagnostico]]
- [[memoria/goals/estado]]
