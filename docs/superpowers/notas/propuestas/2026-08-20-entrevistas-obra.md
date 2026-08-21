---
capa: fuente
tipo: reporte
estado: abierto
fecha: 2026-08-20
areas: [bi, lps]
fuente: "Encargo del chip «entrevistas-obra»; método antes-del-almuerzo, paso 1; spec del replanteo (sección 4, supuesto 1) y nota de las 66 decisiones"
resumen: "Guion, plantilla de registro y tabla de contraste para las dos conversaciones con la obra (un residente y un director) que la spec del replanteo de la Control Tower dejó como supuesto más caro. Sin respuestas todavía: el veredicto por decisión queda mecánico para cuando lleguen."
---

# Entrevistas con la obra — replanteo de la Control Tower

La spec [[2026-08-20-replanteo-control-tower-design]] declara en su sección 4, supuesto 1, que
**la obra no fue entrevistada**: Felipe respondió por ella, y cualquier hallazgo de un residente o
director que contradiga lo decidido **manda sobre la spec**. Este archivo prepara esas
conversaciones para que, cuando lleguen, el contraste contra las 66 decisiones de
[[2026-08-20-decisiones-control-tower]] sea mecánico y no interpretable.

**Estado al 2026-08-20: sin respuestas.** La sesión fue autónoma; las preguntas de arranque a
Felipe (quiénes, cuándo, cómo se registra) quedan en la sección 1 como supuestos para que las
responda al recibir esto. Las conversaciones las consigue y sostiene Felipe; esta nota no contacta
a nadie.

## 1. Lo que Felipe debe fijar antes de la primera conversación

Una pregunta por renglón, en orden. La respuesta reemplaza el supuesto.

| # | Pregunta | Supuesto mientras no responda |
|---|---|---|
| 1 | ¿Qué residente y qué director? Basta cargo y obra; el nombre no entra a git | Un residente y un director **de obras distintas**, para no leer una sola cultura de obra como «la obra» |
| 2 | ¿Cuándo? Fecha de cada conversación | Las dos antes de que arranque la fase 1 de la spec; si la fase 1 arranca primero, el supuesto 1 sigue abierto y se dice en el cierre de fase |
| 3 | ¿Cómo se registran las respuestas? | Felipe anota a mano durante la charla, en la plantilla de la sección 3, **textual** en las celdas marcadas. Sin grabación: grabar cambia lo que la gente cuenta de sus propios incumplimientos |
| 4 | ¿Quién procesa? | Felipe pega las dos plantillas crudas en este archivo (sección 5) y la sesión siguiente corre la tabla de la sección 4 |

Condición de hecho de esta investigación: **dos plantillas llenas, veredicto por decisión
marcado, y la sección «Ajustes propuestos» actualizada** — incluido el caso de que nadie nombre una
decisión concreta, que también es un resultado y también se reporta.

## 2. Guion de entrevista

Veinte minutos. Una sola pregunta; lo demás son repreguntas sobre **el mismo incidente real**.

**La pregunta del método:**

> «La última vez que un compromiso suyo se cayó, ¿cuándo supo que se iba a caer, y qué habría
> necesitado saber una semana antes para evitarlo?»

**Repreguntas permitidas** (solo si hace falta tirar del hilo; todas sobre ese último incidente):

1. «¿Cuál era el compromiso y para qué semana?»
2. «¿Qué día de la semana se dio cuenta? ¿Cómo se enteró: se lo dijo alguien, lo vio usted, lo vio
   en el sistema?»
3. «En ese momento, ¿todavía se podía hacer algo? ¿Qué?»
4. «¿Quién tenía que mover algo para que no se cayera, y esa persona lo sabía?»
5. «Si ese lunes alguien le hubiera puesto ese compromiso en rojo en una pantalla, ¿qué habría
   hecho distinto ese mismo día?» — *Cierra con acción concreta o con «nada»; las dos valen.*
6. «¿La restricción que lo tumbó estaba anotada en el lookahead? ¿Con responsable y fecha?»
7. Solo al director: «¿Con qué preparó el último comité: una hoja, el PAC, lo que le contaron?»
8. Solo al residente: «Si su jefe viera su lista de compromisos caídos, ¿cambiaría lo que usted
   anota como causa?» — *Es la trampa 2 del método (captura envenenada); se pregunta, no se sugiere.*

**Prohibido**, y no se negocia: «¿qué le gustaría ver?», «¿qué indicador le serviría?», «¿le
gustaría poder…?». Cualquier pregunta sobre deseos produce lista de deseos, y la lista de deseos
produce el museo de métricas. Tampoco se le muestra la Torre ni se le describen las hojas.

**Qué anotar textual** (palabra por palabra, sin ordenar): la respuesta a la pregunta principal; la
respuesta a la repregunta 5; la causa que nombra; el día que nombra; cualquier frase donde diga
«yo habría…» o «nadie me dijo…».

## 3. Plantilla de registro — una por entrevistado, sin interpretar

```
Entrevistado: [Residente | Director]        Obra: ______      Fecha: __________
Duración real: ____ min                     Registró: Felipe

Compromiso que se cayó (textual): ..........................................
Semana del compromiso: ...........

Cuándo supo que se caía (día, textual): ....................................
Cómo se enteró (persona / vista propia / sistema): ..........................
¿Se podía hacer algo todavía? (textual): ...................................

Qué habría necesitado saber una semana antes (TEXTUAL, sin resumir):
  "..........................................................................."

Repregunta 5 — qué habría hecho ese día (TEXTUAL):
  "..........................................................................."
  → ¿Nombra una acción concreta?  [ sí | no | «depende» ]

Causa que nombra (textual): ...............  ¿Estaba en el lookahead? [sí|no|no sabe]
  ¿Con responsable y fecha? [sí|no|no sabe]
Quién tenía que mover algo: ...............  ¿Lo sabía? [sí|no|no sabe]

Solo director — con qué preparó el último comité (textual): ..................
Solo residente — ¿cambiaría la causa si el jefe la viera? (textual): ..........

Frases sueltas que valgan oro ("yo habría…", "nadie me dijo…"):
  - ...
```

Regla: la plantilla guarda lo que la persona dijo. El veredicto lo da la sección 4, después, y
con las dos plantillas a la vista.

## 4. Contraste — decisión afectada → qué la confirma → qué la tumba

El veredicto se marca **solo con lo que hay en las plantillas**. Si ninguna celda aplica, la
decisión queda «sin evidencia», no «confirmada».

| Decisión | Qué dice la spec | La confirma si… | La tumba si… | Veredicto |
|---|---|---|---|---|
| **D9 / D59** | Las restricciones del lookahead sin liberar (y el cero como «no se analizó») son el indicador principal | La causa que nombra **es una restricción** (material, diseño, permiso, frente anterior) y respondió que **no estaba en el lookahead** o estaba sin responsable/fecha; o supo tarde porque **nadie la analizó** | La causa de los dos incidentes es de otra naturaleza (rendimiento de cuadrilla, clima, recorte de personal) y la restricción **sí estaba anotada y liberada**; o los dos supieron con una semana de antelación **aunque no hubiera lookahead** | pendiente |
| **D33** | Asignar responsable y fecha a una restricción **desde la Torre** es algo que alguien de obra haría | En la repregunta 5 alguno dice, con sus palabras, que habría **llamado, asignado o puesto fecha** a alguien ese mismo día; o «quién tenía que mover algo» **no lo sabía** | Los dos responden «nada» o «depende» a la repregunta 5; o dicen que eso se resuelve **en la reunión / por WhatsApp / en campo** y que una pantalla no es donde lo harían; o quien tenía que mover algo **ya lo sabía** y igual se cayó | pendiente |
| **D35 / D38** | El director prepara el comité del lunes con la hoja Semanal y el riesgo por compromiso | El director dice que preparó el último comité con **lo que le contaron** o con el PAC ya cerrado, y que habría querido saber **cuáles compromisos de la semana que entra estaban flojos**; y su «una semana antes» apunta a un compromiso concreto, no a un agregado | El director **no prepara** el comité (llega y escucha); o lo prepara con algo que ya le sirve y no nombra ningún compromiso que habría tratado distinto; o el día en que «supo» fue **el mismo lunes en el comité** y dice que ahí estuvo bien saberlo | pendiente |
| **D46 / D47** | La hoja de Responsables se lee como ayuda («quién necesita ayuda»), cada quien la suya, el jefe ve su equipo | El residente, en la repregunta 8, dice que **sí cambiaría la causa** si el jefe la viera (confirma que el muro público envenena y que D46 es necesaria); y alguno describe su caída como **sobrecarga o restricción ajena**, no como falta propia | El residente dice que **no cambiaría nada** y que todos ven todo sin problema (D46 sobra, no daña); o dice que la lista de caídos **ya se usa para regañar** y que ninguna etiqueta en pantalla cambia cómo se lee (D47 es cosmética y la hoja debería no existir para jefes) | pendiente |

**Condición de parada del método, aplicada:** si en las dos plantillas la repregunta 5 sale «no»
o «depende», **no se construye nada nuevo sobre esa base**: el problema no es el tablero, y eso se
reporta como hallazgo a la sesión dueña de la spec, con las frases textuales. Vale tanto como un
«sí».

**Regla de desempate:** un residente y un director son dos personas, no «la obra». Si se
contradicen entre sí, la decisión queda «dividida» y **no se tumba ni se confirma**: sube a Felipe
con las dos frases textuales, y la recomendación es una tercera conversación (el método pide de
tres a cinco).

## 5. Respuestas crudas

*Vacío al 2026-08-20. Pegar aquí las dos plantillas llenas, tal cual, y luego marcar la columna
«Veredicto» de la sección 4.*

## Ajustes propuestos a la spec

| Sección | Cambio concreto | Por qué |
|---|---|---|
| 4, supuesto 1 | Convertir el párrafo en un plan con renglones: **quién** (un residente y un director, de obras distintas), **cuándo** (fecha de cada conversación, antes de arrancar la fase 1), **cómo** (guion y plantilla de esta nota), **dónde cae el resultado** (sección 5 de esta nota y veredicto de la sección 4) | Un supuesto sin fecha ni nombre no se cierra nunca; queda como advertencia decorativa mientras la fase 1 avanza encima de él |
| 4, supuesto 1 | Añadir la frase: «Si la repregunta de acción sale “nada” en las dos conversaciones, la condición de parada del método aplica y se reabren D9, D33 y D35 antes de construir» | La spec dice que la obra «manda», pero no dice qué pasa si la obra no nombra ninguna decisión. Sin eso, ese resultado se lee como «no dijeron nada» y se ignora |
| 9 (riesgos) | Fila nueva: «La obra contradice D33 → la Torre vuelve a solo lectura en fase 1 y la asignación se difiere; se ahorra T3 (CSRF, capacidad, auditoría)» | D33 es el cambio de naturaleza más grande de la spec y el más caro de construir; conviene que su vía de retirada esté escrita antes de gastarlo |
| 10 (cierre de fase 1) | Añadir como criterio: «las dos plantillas de [[2026-08-20-entrevistas-obra]] llenas y con veredicto» | Si no es criterio de cierre, no ocurre |
