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
- Ver `git log`. El frente publicó con `bash scripts/publicar.sh`, verificando **después** de
  integrar `origin/main`.

## Cierre
El frente termina aquí: su condición de hecho está cumplida sobre las superficies que aplican, y lo
que queda vivo son los pendientes de arriba, que pertenecen a otros frentes.

**Dos cosas que se dicen sin adornos:**

1. **El alcance de la spec estaba mal y lo escribí yo.** Dio `/plan-compras` por dentro leyendo el
   contrato sin comprobar que algo lo pintara. Corregido en la propia spec, no en silencio.
2. **`/programacion-semanal` queda a medias a propósito**, con el usuario informado del tamaño real
   antes de decidir. Media conversión declarada es mejor que una entera improvisada.

## Archivos de este goal
- [[docs/superpowers/specs/2026-08-19-estados-severidad-contrato-design]]
- [[docs/superpowers/plans/2026-08-19-estados-severidad-contrato]]
- [[goals/bug-coloreado-severidad/respuestas-ds-f1]] · [[goals/bug-coloreado-severidad/diagnostico]]
- [[memoria/goals/estado]]
