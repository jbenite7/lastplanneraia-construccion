---
capa: fuente
tipo: spec
estado: vigente
fecha: 2026-07-29
areas: [pdc]
fuente: docs/superpowers/specs/2026-07-29-despliegue-pdc-v2-produccion-design.md
resumen: goals/pdc-preparar-b1 - Origen: decisión del comité del 2026-07-29: no lanzar todavía, una semana más, y entonces sí. - Rutina obligatoria…
---

# PDC v2 — Despliegue a producción — Design

- **Fecha:** 2026-07-29
- **Ola:** 1 — **último hecho del goal**
- **Goal:** `goals/pdc-preparar-b1`
- **Origen:** decisión del comité del 2026-07-29: no lanzar todavía, una semana más, y entonces sí.
- **Rutina obligatoria:** `docs/siteground-deploy-routine.md`
- **Estado:** **el despliegue a producción SÍ ocurrió, el 2026-08-12.** Esta línea decía «la producción real NO se tocó: sigue en `1aa7c69` del 16 de julio, sin una sola tabla `pdc_*`», y **es falsa desde el 2026-08-12** — corregido el 2026-08-25. `1aa7c69` no es donde está producción: es el punto **de partida** del release. Producción pasó de `1aa7c694` a `939b7928` en un solo `git pull --ff-only` (**1.763 commits**, sitio en mantenimiento, respaldo de 709 MB), y la base se había nivelado ese mismo día de 71 a **102 objetos** incluyendo PDC v2 — por eso salió sin ejecutar ni una migración. El `composer install` regeneró el classmap con **16 entradas `Services\Pdc` donde había cero**, que es lo que hace existir el módulo allá. Registro completo en `memoria/referencias/produccion-deploy.md` §«El release completo, desplegado el 2026-08-12». Después hubo más despliegues (Torre de Control y recálculo de estados, 2026-08-20). **Lo que falta no es el envío, sino su prueba de uso** — ver el estado verificado al pie.

## Problema

Todo lo que se mostró en el comité corre **en local**. Textual:

> «— ¿Si yo entro hoy a Last Planner de Da Porto, puedo sentarme a hacer esto?
> — No, todavía está local.»

El comité decidió el orden: una semana para bugs e indicadores, luego el comunicado a las obras, luego el
despliegue, luego la capacitación obra por obra.

## Lo que hay que decir en voz alta

**Este despliegue no es «subir el PDC».** Producción va muy por detrás de `main` — el último dato
registrado eran ~147 commits de atraso al 2026-07-23, y desde entonces han entrado el submódulo A
completo, la migración a dark de todos los módulos, el shell con barra lateral y la unificación de repos.

Cuando la obra entre, **no verá un módulo nuevo: verá otra aplicación**. Eso es exactamente lo que el
comité entendió y por eso pidió el comunicado — «la interfaz se volvió negra y los botones pasaron de
arriba al lado» — pero significa que el riesgo del despliegue es el de un release grande, no el de una
función.

Por eso el despliegue es una **tarea con su propio respaldo, su propia ventana y su propia prueba de
humo**, no un `git pull` al final de la última tarea.

## Alcance

### Entra

1. **Medir el atraso real** antes de nada: cuántos commits, qué migraciones sin aplicar, qué cambios de
   dependencias. El número de la memoria es de hace días y no sirve como base.
2. **Respaldo verificable** de la base de datos de producción, con restauración probada — no solo tomado.
3. **Migraciones del PDC v2** aplicadas en orden. Cuidado ya medido: el orden cronológico por nombre
   revienta, porque `crearPaquete()` usa una columna que añade una migración posterior. **Todo el DDL
   primero, los seeds después.**
4. **Composer con PHP 8.3** y `git pull --ff-only`, según la rutina.
5. **Prueba de humo del flujo afectado**, no de la home: entrar a una obra, abrir Programación Semanal
   (lo que la gente usa a diario), y abrir el Plan de Compras y cargar una versión de presupuesto.
6. **Una obra puede entrar y trabajar.** Ese es el hecho, no «el deploy terminó sin error».

### No entra

- El comunicado y el material de capacitación: los lleva el comité (fuera de este goal, por decisión
  explícita).
- Limpiar el drift que haya en el servidor. Una publicación aprobada no autoriza a tocar otra cosa.
- Desplegar nada que no esté en `main` y verificado.

## Plan de vuelta atrás

Si la prueba de humo falla: volver al commit anterior con `--ff-only` desde el tag previo y restaurar la
base desde el respaldo del punto 2. **La condición para empezar es que ese camino esté probado**, no
supuesto. Si no se puede volver atrás, no se despliega.

## Condición de hecho

1. El atraso de producción está medido y escrito antes de tocar nada.
2. El respaldo existe **y se restauró en un entorno de prueba** para demostrar que sirve.
3. `main` está en el servidor por `--ff-only`, sin conflictos ni ficheros forzados.
4. Las migraciones del PDC están aplicadas, en el orden correcto, y una consulta de comprobación lo
   confirma en la base de producción.
5. La prueba de humo pasa: Programación Semanal abre y guarda; el Plan de Compras abre, importa un
   presupuesto y muestra el plan.
6. Una persona de obra —no el equipo de desarrollo— entra y llega a la pantalla del plan.
7. Nada de `.env`, evidencia local ni trabajo ajeno viajó en el push.

## Riesgos

- **El tamaño del salto es el riesgo principal**, y no se reduce con cuidado en el PDC: se reduce
  midiendo el atraso y probando la vuelta atrás. Si el punto 2 no se puede cumplir, se detiene el
  despliegue y se le dice al comité — es preferible correr el lanzamiento una semana a dejar a dos obras
  sin herramienta.
- **La sorpresa visual.** Aunque el comunicado no es de este goal, si el despliegue ocurre antes de que
  salga, la gente se encuentra otra aplicación sin aviso. **El comunicado enviado es precondición del
  despliegue**, aunque lo redacte otro.
- **Hay dos stacks Docker y dos bases que se llaman igual.** Al preparar y probar el respaldo, verificar
  contra cuál se está trabajando antes de cada comando destructivo.

---

## Estado verificado — vigente, pero por un motivo distinto del que se venía diciendo

**Corregido el 2026-08-25, y el error de fondo merece quedar escrito.** La nota anterior daba como
«qué falta» que *«lastplanneraia.com sigue en 1aa7c69 del 2026-07-16 con cero tablas `pdc_*`; solo
se desplegó a `prueba-lps`»*. Eso **lleva dos semanas siendo falso**, y el error tiene una forma
reconocible: `1aa7c69` es el sha **de origen** del release del 2026-08-12, y alguien lo leyó como el
estado actual. La afirmación se copió después a `IMPLEMENTATION_PLAN_INVENTORY.md`, al informe de
auditoría del 2026-08-20 y al encargo de verificación del 2026-08-25 — cuatro documentos repitiendo
un dato que la propia wiki desmentía en `memoria/referencias/produccion-deploy.md`. Lo destapó el
dueño del producto al leer una recomendación construida encima.

Las siete condiciones de hecho, medidas contra el registro del despliegue:

| # | Condición | Estado |
|---|---|---|
| 1 | Atraso medido y escrito antes de tocar nada | **cumplida** — 1.763 commits, escritos |
| 2 | Respaldo existe **y se restauró en prueba** | **cumplida** — tar de 709 MB, y `prueba-lps` restaurada como clon verificado en la operación espejo del mismo día |
| 3 | `main` en el servidor por `--ff-only`, sin forzar | **cumplida** — `1aa7c694` → `939b7928` en un solo pull |
| 4 | Migraciones del PDC aplicadas y comprobadas en la base | **cumplida por otra vía** — no hubo migraciones que aplicar: la base se niveló antes (71 → 102 objetos), verificada conteo a conteo |
| 5 | Humo funcional: Semanal abre y guarda; Plan de Compras abre, importa y muestra el plan | **NO consta** — el humo del 2026-08-12 se hizo **con el sitio en mantenimiento**, por rutas exentas: prueba que el front controller y el autoloader arrancan, no que el módulo opere |
| 6 | Una persona de obra —no desarrollo— entra y llega a la pantalla del plan | **NO consta por escrito** |
| 7 | Nada de `.env`, evidencia local ni trabajo ajeno viajó | **cumplida** — el drift del servidor se descartó, no se guardó |

**Qué falta, en una línea:** cinco de siete están cumplidas y el envío ocurrió; lo que no tiene
constancia escrita es **la prueba de que el módulo funciona en producción con una sesión real y que
alguien de obra llegó a usarlo**. Eso es una comprobación de media hora, no un despliegue.

**Precaución al retomar:** no vuelva a escribir «producción no se ha tocado». Si necesita el estado
del servidor, mídalo contra el servidor.

Criterio y método: [[docs/superpowers/plans/2026-08-25-estado-real-de-planes-y-specs]].
