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
- **Estado:** **ejecutado sobre `prueba-lps`** (fila 4 del tablero, `9e77dd2`), en dos pasadas. **La producción real NO se tocó**: sigue en `1aa7c69` del 16 de julio, sin una sola tabla `pdc_*`. Ese envío sigue pendiente y arrastra ~500 commits.

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

## Estado verificado — sigue vigente

Verificado contra el código el 2026-08-25. **`estado: vigente` aquí significa que el trabajo sigue abierto** — es una afirmación deliberada, no el valor por defecto del backfill.

**Qué falta:** goals/pdc-preparar-b1/estado-olas.md:68-76: lastplanneraia.com sigue en 1aa7c69 del 2026-07-16 con cero tablas pdc_*. Solo se desplego a prueba-lps

Criterio y método: [[docs/superpowers/plans/2026-08-25-estado-real-de-planes-y-specs]].
