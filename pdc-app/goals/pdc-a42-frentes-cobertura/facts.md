# Hechos — A4.2: que el plan de compras sepa a qué frente va cada paquete

Acordados en el grilleo del 2026-07-28 (14 preguntas) y revisados en Plannotator: **33 hechos, 33 aceptados**.
`auto` marca los que llevan comprobación automática.

## H1 — El puente entre presupuesto y cronograma

- **f01** Existe una tabla de correspondencias que dice, para cada **rama del presupuesto —subcapítulo
  o grupo—**, a qué nodo del cronograma corresponde. Ejemplo: MAMPOSTERIA Y REVOQUE → MAMPOSTERÍA.
  **Refinado el 2026-07-28:** tiene que admitir el nivel `grupo`, no solo `subcapitulo`. Comprobado:
  `REVOQUES` (grupo 01.05.06) ancla en `REVOQUE TRADICIONAL`, y heredar el frente de su subcapítulo
  padre lo adelantaría un mes; `IMPERMEABILIZACION FILTROS` (grupo 01.06.02) ancla en `ESTRUCTURA`
  mientras su subcapítulo hermano ancla casi un año después. `auto`
- **f02** Las correspondencias son globales: sirven para todos los proyectos de AIA, no solo para Da Porto. `auto`
- **f03** Una obra puede tener una excepción propia que gana sobre la correspondencia global, sin modificarla para los demás proyectos. `auto`
- **f04** El motor propone las correspondencias que faltan comparando el nombre del subcapítulo con el de los frentes; nunca las inventa a partir de nada. `auto`
- **f05** Una correspondencia que una persona ya revisó queda marcada como confirmada, y se distingue de una que el motor solo dedujo. `auto`

## H2 — Cómo se decide el frente

- **f06** Un paquete cuyo subcapítulo tiene una correspondencia CONFIRMADA recibe propuesta de frente con confianza ALTA. `auto`
- **f07** Un paquete cuyo subcapítulo tiene una correspondencia deducida pero NO confirmada recibe propuesta con confianza MEDIA. `auto`
- **f08** El subcapítulo acota a un grupo pequeño de frentes candidatos y el nombre del paquete desempata dentro de ese grupo. Los paquetes «M. de O MAMPOSTERÍA» y «Sum + Inst REVOQUE SECO», que comparten subcapítulo, pueden así proponerse a frentes distintos. `auto`
- **f09** Cuando el nombre del paquete señala un frente que está fuera del grupo que acotó el subcapítulo, la propuesta baja a confianza MEDIA y la evidencia dice que las dos señales no coinciden. `auto`
- **f10** Un paquete con insumos en varios subcapítulos se propone al frente que arranca antes, no al del subcapítulo con más plata. `auto`
- **f11** Cada propuesta explica en palabras de dónde salió, nombrando el subcapítulo y el frente concretos. `auto`

## H3 — Higiene del motor de nombres

- **f12** El parecido mínimo de nombres sigue en 0,33: no se baja para fabricar cobertura. `auto`
- **f13** Al comparar nombres se ignoran las palabras sin significado (DE, Y, EN, LA, EL, POR, CON). Las propuestas que ya existían antes del cambio siguen existiendo después. `auto`

## H4 — Frentes y actividades sueltas

- **f14** ~~El motor nunca propone una actividad suelta del cronograma: sus propuestas son siempre frentes.~~
  **CORREGIDO el 2026-07-28 con evidencia verificada.** El motor sí propone una actividad suelta
  **cuando una correspondencia curada lo dice**, y nunca por parecido de nombres. Motivo: el
  subcapítulo `CUBIERTA` no tiene frente propio y su ancla correcta es la hoja `LOSA AÉREA CUBIERTA`
  (arranca 2027-07-27); colgarlo del frente `ESTRUCTURA` (2026-08-18) daría **11 meses y 9 días de
  adelanto**, comprobado en la base. Lo mismo con `IMPERMEABILIZACIONES`. Sigue en pie lo medido: el
  motor **no** compara nombres contra las 242 actividades, que es lo que producía disparates. `auto`
- **f15** Una persona sí puede amarrar un paquete a mano a cualquier actividad del cronograma, no solo a los 31 encabezados. `auto`
- **f16** Un amarre hecho a mano a una actividad suelta calcula fechas igual que uno hecho a un frente, tomando la fecha de arranque de esa actividad. `auto`

## H5 — El panel de correspondencias

- **f17** La pestaña «Sin frente» tiene un panel plegable donde se revisan y corrigen las correspondencias subcapítulo → frente. Está cerrado por defecto.
- **f18** El panel dice cuántas correspondencias están confirmadas y cuántas están pendientes de revisar. `auto`
- **f19** Cambiar una correspondencia en el panel no escribe ningún amarre: solo cambia lo que el motor propone a partir de ese momento. `auto`
- **f20** Un paquete cuyo subcapítulo todavía no tiene correspondencia aparece sin propuesta, pero diciendo el motivo: «su subcapítulo X todavía no tiene frente asignado». `auto`
- **f21** Esa fila ofrece un atajo que abre el panel en la correspondencia que le falta, para resolverla sin buscarla.

## H6 — Aceptar sin escribir a ciegas

- **f22** El botón principal sigue aceptando solo las de confianza ALTA, y ahora incluye las que vienen de correspondencias confirmadas. `auto`
- **f23** Las de confianza MEDIA siguen pasando por la confirmación que muestra el importe y la lista de qué paquete va a qué frente, y cancelar no escribe nada. `auto`
- **f29** Ningún amarre se escribe solo: los 85 los confirma una persona en pantalla. `auto`

## H7 — Que el acierto se pueda medir

- **f24** Cuando una persona amarra un paquete a un frente distinto del propuesto, queda registrado el par: lo que el motor sugirió y lo que la persona eligió. `auto`
- **f25** Aceptar una propuesta tal cual conserva la capa que la produjo, para que el acierto se le acredite al motor y no aparezca como decisión humana. `auto`

## H8 — La cifra que cierra la tanda

- **f26** Medido con la misma consulta antes y después en Da Porto: los paquetes sin ninguna propuesta bajan de 45 a 10 o menos. `auto`
- **f27** Medido igual: las propuestas de confianza ALTA suben de 3 a 30 o más. `auto`
- **f28** Los 11 amarres que ya existían siguen intactos: nada de esto los reescribe ni los mueve. `auto`

## H9 — Lo que NO se toca

- **f30** No se toca el cálculo de fechas, los pasos de contratación ni la pestaña «Plan», que son de la tarea que corre en paralelo. `auto`
- **f31** No se arreglan los 25 paquetes que siguen sin duración de referencia: es un asunto distinto y queda anotado.
- **f32** El motor no lee pdc_insumo_actividades.unique_id, que hoy está vacío y que otra sesión va a poblar: esta tanda no depende de ese trabajo ni lo estorba. `auto`
- **f33** Sigue en verde lo que ya estaba: los tests de Vitest, la compilación de la SPA y los tests de plan de fechas contra la base real. `auto`
