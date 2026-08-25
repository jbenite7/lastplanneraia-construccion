---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-08-24
areas: [proceso]
fuente: "Cierre de cuatro specs el 2026-08-24: stack-plan-de-compras, vocabulario-estados-cascada, wiki-v2-visual y plan-cierre-hasta-produccion. Casos medidos contra 5095762d, 2e73013b, 06627082, 2888ab77"
resumen: "Un plan del 2026-08-24 reasignaba como tarea pendiente algo ejecutado el 2026-08-11; es el quinto caso medido del mismo patrón, y su costo no es reconstruir sino gastarle una decisión al gerente sobre algo ya resuelto"
---
# El trabajo hecho no vuelve solo al documento que lo encarga

**El síntoma.** Un plan, un inventario o una cola de decisiones dice «pendiente», «en replanteo» o
«esperando criterio del usuario» sobre algo que **ya está hecho y publicado**. No hay ningún error a
la vista: el documento se lee coherente, la tarea suena razonable, y nada en el repo la contradice.

**Lo que parece.** Que falta ejecutar ese trabajo. Es una lectura perfectamente sensata — el
documento es lo único que se mira, y dice lo que dice.

**Lo que es.** El documento y el código son dos cosas distintas y **nada las mantiene atadas en esta
dirección**. La página hermana [[una-decision-escrita-no-llega-sola-al-codigo]] cubre el sentido
contrario: se decide algo por escrito y se construye lo opuesto. Esta cubre el reverso, que es más
difícil de ver porque no produce ningún defecto visible: **se ejecuta el trabajo y el documento que
lo encargaba se queda como estaba**.

## Cinco casos medidos, no una anécdota

| Caso | Ejecutado | Seguía pidiéndose | Desfase |
|---|---|---|---|
| `D-CEF-1` · superficie obligatoria de estados (`5095762d`) | 2026-08-11 | Plan P5, «Tarea 1», escrito el 2026-08-24 | **13 días** |
| `D-VOC-1..4` cerradas (`2e73013b`) | 2026-08-11 | `decisiones/vocabulario-estados-cascada.md`, copia creada el 2026-08-18 (`06627082`) que nunca se sincronizó | **13 días** |
| Plugins de Obsidian (`2888ab77`) | 2026-08-20 | Spec `wiki-v2-visual`, «plugins por decisión del usuario» | 4 días |
| `D-BTN-1` · el `!important` que no gana | 2026-08-11 | Su propia ficha, que al marcarse admite: «ya estaba ejecutada antes de que nadie la marcara» | 1 día |
| Plan del sidebar canónico | 2026-07-31, en producción | Aviso del harness pidiendo «¿se ejecuta o se cierra?» | **35 días** |

El quinto lo cerró otra sesión el mismo 2026-08-24, y su acta ya nombraba la familia; los cuatro
primeros salieron de una sola sesión de cierre documental ese mismo día. Cinco casos en tres semanas
es un patrón del repositorio, no un descuido de alguien.

## Por qué cuesta caro, y no es lo que parece

El costo obvio sería reconstruir lo ya construido. **No es ese** — en los cinco casos alguien lo
verificó a tiempo y nadie reconstruyó nada.

El costo real es **que la pregunta le llegue al gerente cuando la respuesta está en el
repositorio**. Cuatro de los cinco pedían criterio de Felipe sobre algo ya resuelto, y compitieron
por su atención en cada revisión mientras tanto. El del sidebar lo hizo durante 35 días.

Y hay un costo de segundo orden: un plan escrito hoy que reasigna trabajo de hace dos semanas
**hereda esa mentira hacia adelante**. P5 se escribió el 2026-08-24 leyendo un `TASKS.md` que decía
«Pendiente», y sin ese cruce habría puesto a alguien a construir lo que ya existía.

## Cómo se sale

**Antes de asignar una tarea que lleva días escrita, mide el código, no el documento.** Es la regla
que `docs/superpowers/specs/2026-08-11-plan-cierre-hasta-produccion-design.md` ya escribió como
candado de proceso —«antes de abrir cada fase se comprueba que sigue haciendo falta»— y que este
mismo caso incumplió: P5 desciende de ese spec y reasignó su fase C sin ese cruce.

Tres comprobaciones concretas, en orden de coste:

1. **Cruzar contra la cola canónica.** `docs/decisiones-pendientes.md` es la fuente única; los
   archivos de `decisiones/<frente>.md` son copias por sesión y **pueden estar atrás**. El propio
   commit que las creó (`06627082`) lo dice: «el precio aceptado es duplicar
   `docs/decisiones-pendientes.md`, que sigue siendo la cola canónica del repo».
2. **Verificar el efecto, no la declaración.** Que una ficha diga «resuelta» no prueba que el código
   cambió, ni al revés. En `D-CEF-1` la comprobación que valió fue abrir
   `state-semantics.schema.json` y ver `required: [module, surface, states]`, más el test que
   comprueba que la ruta existe de verdad en `public/index.php`.
3. **Sospechar de todo lo que lleve más de una semana pidiendo criterio.** El desfase medio de estos
   casos es de casi dos semanas. Una petición vieja al gerente es, con más probabilidad de la que
   parece, una petición ya respondida.

**Lo que NO funciona:** contar casillas `- [ ]` del plan. `AGENTS.md` §Verificación ya lo mide: de
2.127 casillas en 71 planes vivos solo 162 están marcadas, y hay planes cerrados y en producción con
todas sus casillas vacías. Las casillas de P5 estaban sin marcar tanto en la tarea ya hecha como en
las que faltaban — no distinguen.

## Cuánto costó

El 2026-08-24, cerrar cuatro specs que se creían parciales resultó ser sobre todo verificar que ya
estaban hechas: **ninguna de las cuatro necesitó una línea de código**. Lo que costó fue el rastreo
—leer código, esquema, tests y el historial de cada decisión— para no repetir trabajo ni volver a
preguntarle a Felipe lo ya decidido. Y ese rastreo hay que rehacerlo cada vez, porque nada lo
automatiza.

Relacionadas: [[una-decision-escrita-no-llega-sola-al-codigo]] ·
[[condicion-de-hecho-caduca-sin-aviso]] · [[guard-valida-declaracion-contra-si-misma]] ·
[[el-contador-no-mide-el-archivo]]
