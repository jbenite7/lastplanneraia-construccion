---
capa: fuente
tipo: spec
estado: vigente
fecha: 2026-08-26
areas: [proceso, design-system, bi, pdc]
fuente: docs/superpowers/specs/2026-08-26-v0-del-producto-design.md
resumen: "La v0 del producto, spec v1 (calidad de ejecución): 26 pantallas, la Control Tower completa como Ola 1, y las decisiones que un ejecutor ya no tiene que adivinar"
---

# La v0 del producto — Design · **spec v1**

- **Fecha:** 2026-08-26 · **v1 del mismo día**, tras la iteración de calidad con Felipe.
- **Qué cambió de v0 a v1:** la v0 tenía el qué y el orden; un ejecutor aún tenía que adivinar siete
  decisiones. La v1 las trae resueltas — dos eran contradicciones internas que solo salieron
  midiendo. El registro de la iteración está en la sección «Decisiones» de abajo.
- **Qué es:** **absorbe** [[docs/superpowers/specs/2026-08-25-mapa-unico-del-trabajo-vivo-design|el
  mapa único]] —que era un inventario— y le añade criterio de corte y orden. De aquí salen planes,
  no specs.

## Las decisiones de Felipe, y que este documento ejecuta

Las cinco del grilleo original (2026-08-26, mañana) más las seis de la iteración v0→v1 (tarde).
Ninguna se re-litiga.

| # | Decisión | Consecuencia |
|---|---|---|
| 1 | **Un solo documento** que define alcance de producto | No se abren specs atómicas. Lo siguiente son planes |
| 2 | **Prioridad: que el director decida > sin deuda > móvil > ciclo completo** | El orden de las olas |
| 3 | **El móvil es deuda, no frente aparte** | Cada pantalla se toca **una vez** y queda terminada en escritorio y celular |
| 4 | **El tema claro entra** | Sube el costo por pantalla, se paga una vez |
| 5 | **Enfoque C aprobado**: una pantalla completa primero, y que ella mida | La primera pantalla es piloto además de entrega |
| 6 | **El panel de administración queda FUERA de la v0** | El denominador baja de 41 a 26 (ver «El denominador»). Admin no usa el sistema de diseño y quedó declarado aparte con motivo; ni el móvil ni el tema claro le pagan a la obra |
| 7 | **El piloto es UNA pantalla de las 8 de la Torre** — la de decisión | Produce el número limpio de «una pantalla»; las otras 7 siguen dentro de la misma Ola 1 |
| 8 | **La Ola 1 es TODA la Control Tower** — las 5 fases de su diseño, las 8 pantallas | No solo escritura y trazabilidad: también catálogo ejecutable, narrativa, salir del escondite y jubilar Power BI. El diseño vive en [[docs/superpowers/specs/2026-08-20-replanteo-control-tower-design]] |
| 9 | **Móvil = misma decisión, otra forma** | En el celular se garantizan las DECISIONES de la pantalla (ver, asignar, confirmar) con la forma que le quepa al teléfono; la tabla completa es de escritorio, declarado |
| 10 | **Tema claro: visto visual de Felipe antes de propagar** | Nace en el piloto, anclado al manual AIA v1.0; ninguna otra pantalla lo hereda sin su aprobación en pantalla (precedente: su condición del 2026-08-03 en la línea C del reparto) |
| 11 | **Panel de inicio: descartado con motivo** | El redirect por rol se queda; se revisa solo que cada rol aterrice donde debe. Cero pantallas nuevas en la v0. Cierra la decisión pendiente desde el 2026-08-03 (línea F del reparto) |

Tres decisiones **técnicas** tomadas por el asistente, anotadas para auditoría (ninguna supera el
umbral):

- **El laboratorio del sistema de diseño (1 pantalla) no cuenta en el denominador**: es la
  herramienta interna con la que se valida el propio sistema, no una pantalla de producto.
- **Los 10 hallazgos de usabilidad «no verificables» se re-verifican al tocar su pantalla** en la
  ola que le toque — no antes, no en bloque.
- **El mecanismo de la condición de hecho 7** (actualizar este documento al cerrar un frente) es el
  **checklist del template de PR** (`.github/pull_request_template.md`, a crear en la Ola 1): con la
  política de cierre por Pull Request vigente desde hoy, es el único punto por el que todo cierre
  pasa.

## El problema, en una frase

El sistema está construido y desplegado, pero **quien dirige la obra todavía no puede decidir dentro
de él**: la Torre de Control deja mirar y no deja escribir. Y la mayoría de las pantallas solo
existe para escritorio, con las tablas heredadas y sin tema claro.

## Qué significa «v0 lista»

**Que el director de obra abra el sistema en el celular o en el computador, vea qué va a matar sus
compromisos, y decida ahí mismo — sin exportar a Excel, sin pedirle a nadie que le pase un dato, y
sin perder capacidad de decidir según por dónde entre.**

*(v1 corrige la frase original «sin que la pantalla se vea distinta según por dónde entre»: una
tabla de 20 columnas no cabe en un celular, y prometerlo era prometer lo imposible. Lo que se
promete es la decisión, no el píxel — decisión 9.)*

Todo lo que no sirva a esa frase queda fuera y se declara.

## El denominador: 26 pantallas

El censo tiene 21 módulos y 41 superficies. La v0 se compromete con **26**:

| Se resta | Cuántas | Por qué |
|---|---|---|
| Panel de administración | 14 | Decisión 6: exento del sistema de diseño, uso interno de escritorio |
| Laboratorio del design system | 1 | Herramienta interna de validación, no producto |

Las 26, por módulo: Torre de Control BI (8) · autenticación (3) · programación semanal con sus tres
submódulos CNP/CNC/CIC (4) · escalamientos y crisis (2) · y una cada uno: selector de proyectos,
programa general, cronograma, programación intermedia, plan de compras, profesionales,
subcontratistas, control de cambios, indicadores (9).

## Tarea cero — **ejecutada y cerrada el 2026-08-26**

Los dos inventarios de pantallas no se hablaban. Se midió, resultó mucho menor de lo descrito
(el error de describir sin abrir el archivo quedó registrado a propósito), se ejecutó en cinco
tareas y está en `main` (`68cf4016`): el gate `censo-fichas-coherencia.test.mjs` nació rojo y cierra
en verde 3/3, los enlaces rotos se arreglaron, y la deuda de cobertura bajó de 3 pantallas a 2 con
causa verificada. Cierre completo con el precedente de medición en
`docs/superpowers/plans/2026-08-26-tarea-cero-lista-canonica-de-pantallas.md`.

## Las olas

### Ola 1 · La Control Tower completa — y su primera pantalla es el piloto

**Alcance: las 5 fases del diseño de la Torre** ([[docs/superpowers/specs/2026-08-20-replanteo-control-tower-design]]
manda en el cómo): catálogo ejecutable de métricas, escritura, narrativa, salir del escondite y
jubilar Power BI. Las 8 pantallas del módulo, cada una terminada según las decisiones 3, 4, 9 y 10.

Estado medido (2026-08-25/26, re-medir al planear): la Torre no escribe — `public/index.php:337,358`
solo declara GET; no existen columnas de asignación (`grep ResponsableAsignado|FechaCompromiso` → 0);
`LineageService.php` existe y nadie lo consume desde `bi-spa.js`; `MetricDictionaryService` describe
pero no ejecuta.

**El orden interno lo fija el piloto:** primero la **pantalla de decisión** — donde el director ve
la restricción, asigna responsable y fecha, y pregunta de dónde sale el número. Se deja completa
(escritura, escritorio, celular a 390×844, tema oscuro y claro, usabilidad) y **su costo es el
número que dimensiona las otras 7 y las olas siguientes**. Hasta que ese número exista, este
documento no promete fecha total, a propósito.

**La unidad del número** (corrección de Felipe, 2026-08-26): **paradas** — cuántas veces lo escrito
no coincidió con el código y hubo que parar a mirar — y **decisiones de Felipe consumidas**. No
días: no se está midiendo tiempo humano. Precedente: la tarea cero costó 3 paradas y 0 decisiones.

**La migración de esquema NO viaja dentro de la pantalla.** Es lo más caro de revertir del repo:
tarea propia, con autorización explícita de Felipe, respaldo verificable probado, ensayo en seco
antes de aplicar y reversa escrita (`docs/global-tables-architecture.md` manda).

**El tema claro nace aquí** (decisión 10): tokens anclados al manual AIA v1.0, aprobados por Felipe
en pantalla sobre el piloto antes de que ninguna otra pantalla los herede.

### Ola 2 · Las 18 restantes, cada una una sola vez

Cada pantalla terminada en la misma pasada: tabla migrada al sistema de diseño, tema claro (ya
aprobado en la Ola 1), comportamiento en celular según la decisión 9, y los hallazgos de usabilidad
que le toquen — incluidos los no verificables, que se re-verifican al abrirla.

**Las familias, listadas** (el orden fino entre familias se fija cuando exista el número del
piloto):

| Familia | Pantallas | Nota medida |
|---|---|---|
| Planeación | programa general, cronograma, intermedia, semanal + CNP/CNC/CIC (7) | Los cinco módulos con tabla heredada viven aquí (`public/js/modules/*/hot*.js`). **Precondición del cronograma:** resolver primero el defecto de que Actualizar pisa el avance en toda edición (`TASKS.md:56`) — arreglarlo es parte de «dejarla bien», no un frente aparte |
| Entrada | autenticación ×3, selector de proyectos (4) | Incluye la revisión del aterrizaje por rol (decisión 11) |
| Gestión | plan de compras, profesionales, subcontratistas, control de cambios (4) | El formulario de creación de control de cambios **no existe** (`controlCambios.view.php:855`) — es producto, entra aquí |
| Seguimiento | indicadores, escalamientos ×2 (3) | El shell de escalamientos vive fuera del layout (`views/dashboard/escalamientos.php:20`) |

Los cuatro arreglos de una línea ya medidos (contraste y botón del chip de BI, etiqueta de recuperar
contraseña, nombre repetido del selector) no esperan a su familia: entran con la primera pantalla
que se abra de su módulo, o antes si estorban.

### Ola 3 · Que el ciclo cierre sin salirse

Lo que quede para que una obra planee, compre, haga seguimiento y vea indicadores sin otra
herramienta. Más corta de lo que parece: el Plan de Compras ya está desplegado y en uso, y el panel
de inicio quedó descartado (decisión 11).

## Lo que NO entra, dicho para que nadie lo suponga

- **El panel de administración** (14 pantallas) — decisión 6, con motivo. Sus arreglos de
  usabilidad ya medidos (etiquetas, autocomplete) quedan en `TASKS.md` como mantenimiento ordinario,
  no como parte de la v0.
- **El despliegue a producción.** Autorización explícita de Felipe, cada vez. Al corte: 233 commits
  atrás (`6fa3cff1`), dos migraciones pendientes.
- **Las 27 actividades con el acumulado corto en producción.** Dueño propio en `TASKS.md`.
- **Reescribir el legado.** Se migra lo que una pantalla necesita para quedar terminada, nada más.
- **El panel de inicio como pantalla.** Descartado con motivo (decisión 11).

## Riesgos

- **El mayor: prometer 26 sin saber qué cuesta 1.** Por eso el piloto, por eso sin fecha total.
- **Este documento envejece — ya se midió cómo.** El mapa que absorbe envejeció en horas. La
  mitigación es la condición 7: actualizarlo es un paso del checklist de cada PR de cierre, no una
  buena intención.
- **La Ola 1 creció por decisión deliberada** (de escritura+trazabilidad a la Torre completa,
  decisión 8). El piloto interno es lo que evita que ese crecimiento sea una apuesta a ciegas: si el
  número de la primera pantalla sale caro, las fases 3–5 de la Torre se re-priorizan **antes** de
  ejecutarlas, con este documento actualizado.

## Condición de hecho

1. ~~Lista canónica de pantallas con gate~~ — **cumplida el 2026-08-26** (tarea cero, `68cf4016`).
2. El director asigna responsable y fecha a una restricción **sin salir de la Torre**, y pregunta de
   dónde sale cualquier número con un clic — en escritorio y en celular.
3. Las 5 fases del diseño de la Torre están ejecutadas o re-priorizadas por escrito con el número
   del piloto en la mano; Power BI jubilado o su permanencia declarada con motivo.
4. La migración de la Ola 1 se aplicó con autorización explícita, respaldo probado y ensayo en seco,
   y su reversa está escrita.
5. Cada una de las 26 pantallas cerrada lo está en escritorio y en celular a 390×844 (decisión 9),
   en tema oscuro y claro (decisión 10), con su tabla migrada. Sin pantallas «a medias».
6. **Existe el número** — en paradas y decisiones consumidas — y las olas están dimensionadas con él.
7. Cerrar cualquier frente incluye actualizar este documento, forzado por el checklist del template
   de PR. Un frente que no lo actualizó no está cerrado.

## El enfoque, aprobado — y los descartados

**Enfoque C (2026-08-26):** una pantalla completa primero, y que ella mida. Descartados con motivo:
**barrer por familias** (compromete el total sin medir una) y **cimientos primero** (meses sin que
la obra note nada, y cimientos sin pared real encima que no encajan al llegar la primera).
