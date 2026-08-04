# La biblia de flujos: describir, verificar y auditar el comportamiento de la app

**Fecha:** 2026-08-04
**Estado:** aprobado en brainstorming, pendiente de planes
**Áreas:** qa, arquitectura, lps, pdc, rbac, proceso

## Problema

Hoy el repositorio sabe **dónde está** cada cosa y **por qué** se decidió, pero no **qué debe
pasar**:

- `memoria/arquitectura/` (23 páginas) inventaría rutas, controladores, servicios y tablas,
  generado desde el código. Dice qué existe, no qué debería ocurrir.
- `memoria/flujos/` (2 páginas) narra la cascada LPS y el PDC a vista de pájaro, en párrafos.
- `docs/qa/workflows.md` es una guía para escribir pruebas, no un contrato de comportamiento.
- `e2e/` y `tests/browser/` cubren caminos concretos, sin mapa de qué queda sin cubrir.

Falta la capa intermedia: **el comportamiento esperado, escenario por escenario, con detalle
suficiente para afirmar que una función está mal**. Sin ella no se puede distinguir «la app hace
esto» de «la app debe hacer esto», que es exactamente lo que se necesita para tener certeza.

## Diseño

### 1. Dónde vive y con qué autoridad

`docs/flujos/`, un documento por flujo, en la **capa de fuentes** (versionado, editable por
humanos, no lo escribe solo el asistente).

La cláusula que la convierte en biblia y no en documentación:

> **Si la biblia y el código divergen, es un bug de uno de los dos, y hay que resolverlo.** No se
> corrige la biblia en silencio para que cuadre con el código.

Esto la distingue de `memoria/`, cuya regla es la contraria (código > AGENTS.md > wiki: una nota
que contradice al repo se corrige y se marca `derogada`). Una biblia que pierde por definición
contra el código no puede certificar que el código está mal.

Precedencia resultante: **código y biblia se contrastan entre sí**; `AGENTS.md` sigue siendo el
contrato de trabajo; `memoria/` sigue siendo contexto. La wiki **enlaza** la biblia desde sus mapas
y le cuelga las trampas encima; no la duplica.

### 2. La unidad es el escenario

Un escenario es **un camino concreto por un flujo**, no un flujo entero ni un clic suelto.
Ejemplos: «Residente crea un compromiso en semana abierta», «Residente intenta editar una semana
cerrada», «Visualizador abre Programa General».

Cada escenario declara:

| Campo | Qué contiene |
|---|---|
| `id` | Estable, citable desde una prueba: `PS-004`, `RBAC-011` |
| Rol | Cuál de los diez roles de `RbacCatalog` lo ejecuta |
| Precondiciones | Estado de datos y de sesión necesarios (semana abierta, proyecto seleccionado, fila existente) |
| Pasos | Numerados; cada uno nombra **la variable, el endpoint o la capacidad** que toca |
| Resultado esperado | Qué cambia en pantalla **y** en datos |
| Verificación | Cita `archivo:línea` y, si es crítico, el `e2e/` que lo cubre |

**Los caminos de error y de permiso denegado son escenarios de primera clase**, con el mismo peso
que el camino feliz: es donde suelen vivir los bugs, y donde `AGENTS.md` ya exige probar un rol
permitido y uno denegado.

### 3. Verificación en dos niveles

1. **Lectura (todos los escenarios).** Se verifica contra el código con cita `archivo:línea`. Es
   barato, cubre el 100 % y caza divergencias. Misma regla que el pase de veracidad de la wiki:
   **verificar, no sospechar**; lo que no se pueda comprobar leyendo se declara «no comprobable en
   lectura», nunca se da por bueno.
2. **Ejecutable (los críticos).** Prueba Playwright en `e2e/` que cita el `id` del escenario.
   Criterio para subir de nivel: toca permisos, muta datos, o cierra/abre un periodo. La matriz de
   priorización decide el resto.

### 4. Las cinco tandas y su orden

Se escriben **spec, plan y `goal.md` de las cinco por adelantado**; la ejecución sigue la matriz
esfuerzo/impacto, no el orden narrativo.

| # | Tanda | Cubre | Impacto | Esfuerzo | Orden |
|---|---|---|---|---|---|
| T1 | Transversal | Autenticación, puerta de servicio, selector de proyecto, RBAC y capacidades | Alto | **Bajo** | **1.º** |
| T2 | Cascada LPS | Programa General, actualizar cronograma, Programación Intermedia, Semanal, CIC/CNC/CNP | Muy alto | Alto | 2.º |
| T3 | PDC | Presupuesto → maestro de insumos → paquetes → plan con fechas → seguimiento | Alto | Medio | 3.º |
| T4 | Soporte | Contratos, listado de actividades, subcontratistas, profesionales, control de cambios, escalamientos | Medio | Medio | 4.º |
| T5 | Lectura | Indicadores y Torre de Control BI | Medio | Bajo | 5.º |

**Por qué transversal va primera pese a no ser el corazón del negocio:** es bajo esfuerzo (pocas
rutas, reglas duras y ya documentadas en `RbacCatalog`) y contamina todo lo demás — cada escenario
de las otras cuatro tandas empieza con «un rol X con un proyecto seleccionado». Si esa base tiene
un hueco, los demás escenarios lo heredan.

### 5. Qué pasa con los hallazgos

Cuando la auditoría encuentre que el código no hace lo que debería:

1. Se registra en el backlog único con esfuerzo, impacto y el escenario que lo destapó.
2. **La pasada continúa.** No se arregla en caliente.
3. Si la duda es *cuál es la conducta correcta* —y no *si el código la cumple*—, la decisión es del
   usuario, no del asistente.

Arreglar sobre la marcha mezclaría en un mismo commit documentación y cambios de comportamiento, y
descarrilaría la auditoría.

### 6. Encaje con `improve-app`

`docs/IMPROVE-APP-PLAN.md` ya existe (creado el 2026-08-04 por la sesión de la campaña de dark
mode) y este proyecto se entrelaza con él en vez de duplicarlo:

- **Un solo backlog.** La matriz esfuerzo/impacto de la biblia **es** `docs/EXPERIMENTS.md`
  `## Experiment Backlog`, con su columna ICE. No se crean dos listas de pendientes.
- **La fase 1 (`jobs-to-be-done`) va primero de todo**, y es gate de ambos proyectos: sin saber qué
  le pide cada rol a la app, los escenarios serían un inventario sin criterio de qué importa.
- **La biblia alimenta las fases 3 y 9.** El paso a paso atómico es el insumo que los gulfs de
  Norman sobre PG→PI→PS y el recorrido en frío necesitan y hoy no tienen.
- **La regla 8 se cumple sola.** «Ningún cambio de UI sin hallazgo de fases 1-3 detrás»: la
  auditoría de la biblia es una fuente formal de hallazgos.

## Fuera de alcance

- **Arreglar los bugs que aparezcan.** Este proyecto los encuentra y los prioriza; arreglarlos es
  trabajo aparte, con su propia decisión.
- **El panel `admin/`**, que es otra aplicación (front controller, router y modelos propios) y
  merece su propia biblia si algún día se decide.
- **`src/Legacy/`** más allá de lo que la cascada LPS toque de paso.
- **Reescribir `memoria/flujos/`**: las dos páginas narrativas siguen siendo el resumen de
  entrada, y pasan a enlazar la biblia.

## Condición de hecho

1. Existe `docs/flujos/` con su README declarando la cláusula de autoridad y el formato del
   escenario.
2. Las cinco tandas tienen spec, plan y `goal.md` escritos, con su prioridad esfuerzo/impacto.
3. La tanda T1 está completa: todos sus escenarios descritos, verificados por lectura con cita, y
   los críticos con prueba en `e2e/` citando su `id`.
4. Los hallazgos de T1 están en el backlog único con esfuerzo e impacto, sin arreglar.
5. `memoria/` enlaza la biblia desde los mapas afectados, y `npm run test:wiki` sigue en verde.
6. `docs/IMPROVE-APP-PLAN.md` refleja el encaje: fase 1 cerrada y el backlog compartido.

## Riesgos conocidos

- **La biblia puede envejecer más rápido que la app.** Mitigación: los escenarios críticos tienen
  prueba ejecutable, que falla cuando el comportamiento cambia; los de solo lectura entran en la
  rotación del pase de `veracidad`, que ya existe y ya avisa por cuenta de commits.
- **El detalle atómico puede volverse transcripción del código.** Un escenario que solo repite lo
  que hace la función no aporta nada: debe decir qué **debe** pasar, para poder discrepar. Si al
  escribirlo no se puede afirmar nada que el código no diga ya, ese escenario sobra.
- **Cinco tandas es mucho alcance.** Por eso se escriben los cinco planes pero se ejecuta por
  matriz: cada tanda cerrada vale por sí sola aunque las siguientes se aplacen.
