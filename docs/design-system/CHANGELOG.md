---
capa: fuente
tipo: guia
estado: vigente
fecha: 2026-07-15
areas: [design-system]
fuente: docs/design-system/CHANGELOG.md
resumen: Design System AIA changelog
---

# Design System AIA changelog

## 1.1.0 - 2026-08-07

> Activada el 2026-08-07 junto con la revisión una a una de las 39 excepciones que vencían en esta
> versión: **7 pagadas y 32 re-vencidas a `1.2.0`** con su evidencia medida. La activación deja de
> ser un hito por versión: los gates ahora aceptan cualquier SemVer con major ≥1 y `status: stable`,
> porque el hito fue único y se cumplió en `1.0.0`.

Deuda saldada en este cierre:

- **Las 2 reglas duplicadas del buscador de `/proyectos`** salen de `theme-overrides.css` y del
  agregador: eran copia literal de las dos reglas genéricas que las preceden, y la forma del control
  ya la fija `project-selector.css`. Medido: el `input-group` no mueve un píxel (4 excepciones).
- **El acento `--primary` deja de ser un hex suelto** en `styles.css`: su valor pasa a
  `--ds-color-accent-legacy-dark` en `tokens.css`, documentado como acento *legacy*, no de marca.
  Mismo color resuelto, cero píxeles movidos (1 excepción).
- **`project-selector.css` vuelve a la escalera canónica de capas**: `@layer responsive` estaba
  declarada y vacía, y la única regla de `@layer states` pasa a `components` sin cambiar la cascada
  (2 excepciones).

Deuda re-vencida a `1.2.0`, con la medición que lo justifica:

- **11 blindajes globales de `border-radius`** en `theme-overrides.css`. Al degradarlos, el radio cae
  a los valores heredados de las hojas legacy (`4px`, `11.52px`, `15.2px`, esquinas inferiores a 0).
- **21 blindajes de los adaptadores Handsontable.** El puente y la piel legacy siguen cargando con
  226 declaraciones `!important`, y `#hot-container td` gana por especificidad al selector del
  adaptador aunque nadie usara `!important`; dos de ellas combaten estilos inline del vendor, que
  solo un `!important` puede batir.

Las 32 las paga el retiro del puente legacy móvil/vendor, obra de `1.2.0`.

Cambios contractuales entrados entre el 25 de julio y el 3 de agosto de 2026 (los fixes de
módulos que solo consumen el sistema no se listan):

- **Densidad compacta en toda la familia de tablas**, con piso AA de 24px de área de clic
  (`67f35c4f`).
- **Botones y chips contadores compactos**, también con área de clic mínima de 24px (`201dd5ec`).
- **El control compacto de toolbar es un componente real**: se consolida en una sola definición lo
  que eran cinco copias (`03434f64`).
- **Gatillo de filtro unificado**: un componente único y quieto compartido por las tres librerías
  de tabla (`a6a664f9`).
- **Paridad del chip de estado** entre Programa General, Programación Intermedia y Programación
  Semanal, medida en píxeles (`51ccd5ca`).

## 1.0.0 - Activada

> Ciclo de cierre hacia `1.0.0`. **La activación ya ocurrió:** el commit
> `58b850e7 docs(design-system): activate closeout and align test`, del 16 de julio de 2026, subió
> `version.json` de `0.3.6 / construction` a `1.0.0 / stable` junto con `closeout-evidence.json` y
> `stable-api-1.0.0.json`. Lo que sigue documenta cómo se llegó hasta aquí, así que conserva la
> redacción en futuro con la que se escribió; la versión viva la manda `version.json`, no este
> encabezado.

### API candidata para la garantía 1.0.0

El contrato de release enumera únicamente componentes con madurez `stable` y
evidencia tanto en el laboratorio como en Programa General: `shell`,
`page-header`, `toolbar`, `button`, `field`, `state`, `card`, `table-shell`,
`overlay` y `handsontable-adapter`. Los componentes `candidate`, las primitivas
BI y los adaptadores sin consumidor real quedan fuera. Esta enumeración no
activa la garantía SemVer: la versión permanece `0.3.6 / construction` hasta
que todos los gates de cierre estén aprobados y se cree el commit de release.

- Reduce el contrato de activación a los quince gates aprobados, en orden fijo,
  y sustituye la revisión externa por una revisión local trazable.
- Separa teclado y reflow del runtime bloqueante; CI los conserva como evidencia
  tolerada con artefactos de fallo.
- Define Accessibility Insights como tres revisiones automatizadas básicas separadas —
  laboratorio, piloto y estados revelados— con cero reglas e instancias fallidas,
  sin activar todavía la versión `1.0.0`.
- Vincula cada recibo de cierre a un `commandId` y comando canónicos, y rechaza
  activar `1.0.0` si índice/worktree no están limpios o si los documentos de
  activación no coinciden byte por byte con el commit `HEAD` que los contiene.
- Define un artefacto versionado y un comparador fail-closed para los presupuestos de runtime de Programa General: CSS/JS gzip, adapters cargados, solicitudes duplicadas, flash de tema, inicialización, interacción Handsontable y ausencia de assets del laboratorio.
- Hace portable la retrospectiva histórica de 0.3.3 como procedencia incompleta: declara `sourceRef:null` y `rawSamplesPreserved:false`, conserva solo resúmenes recuperados y los verifica mediante un manifiesto comprometido, sin depender de un objeto o checkpoint Git oculto ni presentarlos como muestras crudas de release.
- Registra la aprobación explícita de los límites de presupuesto. El gate runtime actual sigue pendiente hasta ejecutar y agregar tres muestras frescas sobre un `HEAD` limpio, con recibos verificables, antes de comparar contra ese baseline.
- Mide la interacción Handsontable abriendo el filtro de columna, sin editar datos y sin depender de que la grilla contenga actividades.
- Exige cero flashes de tema y cero assets del laboratorio en Programa General aunque el runtime histórico tuviera un flash.
- Aplica dark o la preferencia linen persistida mediante un bootstrap síncrono y versionado antes de las hojas de estilo; la prueba real de Programa General pasa de un flash de tema a cero.
- Añade un preflight fail-closed que rechaza bases, volúmenes, puertos, compose files y dumps fuera del fixture CI allowlisted.
- Convierte el contrato, la seguridad de tablas globales y el E2E con restauración de Programa General en pasos continuos del workflow aislado.
- Conserva JSON axe del piloto, traces, capturas, video y logs Docker cuando un gate runtime falla.
- Vincula la leyenda, las líneas y los puntos de la Curva S a los mismos tokens semánticos por serie, para conservar su distinción en dark y linen.
- Centra estructuralmente el radar dentro de su tarjeta en cualquier ancho del laboratorio.
- Ajusta las etiquetas del radar a la escala del gráfico SVG y las mantiene dentro del área de lectura.
- Invalida los estilos del laboratorio para que la corrección sea reproducible en una recarga normal.
- Evita que los chips semánticos interpolen colores durante el cambio dark/linen, para no exponer contraste transitorio insuficiente.
- Distingue la base de Fundamentos aprobada del candidato activo que añade inventario visible y acción primaria dark; no hereda una aprobación visual ajena.
- Hace que la acción primaria aplique su color corporativo de cada tema sin interpolación cromática y la expone como candidato visual independiente.
- 2026-07-25: Retira el tema `linen` del producto (F0 del goal `dark-mode-todos-los-modulos`, `goals/dark-mode-todos-los-modulos/facts.md`). Un solo tema reduce a la mitad la superficie de tokens, gates y evidencia, y es coherente con el alcance desktop-dark de `AGENTS.md`; dark queda como único tema, sin conmutador.

## 0.3.5 - En construcción

- Hace efectivo el padding canónico de formularios, archivo, mensajes de error y retroalimentación.
- Añade la referencia Select2 multiselección y alinea la navegación adaptable en anchos táctiles.
- Muestra los ocho estados operativos de Programación Intermedia sobre el mismo mapa compartido de urgencia.
- Corrige la precedencia frente al reset heredado e invalida los estilos anidados ya almacenados en caché.

## 0.3.3 - En construcción

- Fija la Curva S con guía acolchada y cuadrícula de baja intensidad.
- Refuerza el medidor como dona circular completa, nunca semicírculo.
- Define el mapa transversal de gravedad y urgencia para que todos los módulos orienten la acción con los mismos colores.

## 0.3.2 - En construcción

- Añade padding efectivo y cuadrícula sutil a la Curva S.
- Sustituye el medidor relleno por una dona y agrega el radar multidimensional accesible.

## 0.3.1 - En construcción

- Hace legible la Curva S con líneas finas, uniones redondeadas y puntos sutiles por corte.

## 0.3.0 - En construcción

- Inventaría 88 grupos UI de toda la app y bloquea grupos sin dark/linen, fuente o API canónica.
- Amplía formularios con choice, switch, fecha, archivo, ayuda y validación.
- Amplía analítica con Curva S, PPC, PAC vs Programado, gauge, proyección y estados sin datos.
- Expone el inventario por familia dentro del laboratorio protegido.

## 0.2.1 - En construcción

- Corrige la prioridad de capa del reset de Handsontable frente a reglas legacy importantes.

## 0.2.0 - En construcción

- Remapea las acciones primarias al verde corporativo canónico de cada tema.
- Normaliza padding de navegación, feedback y Select2.
- Aísla Handsontable de las tarjetas móviles legacy y agrega opciones de prueba al laboratorio.
- Versiona los imports locales para invalidar caché de forma reproducible.

## 0.1.0 - En construcción

- Inicia el contrato ejecutable del Sprint 00.
- Programa General es el único piloto autorizado.
- No representa todavía una API estable.
- Homologa diez familias y sus primitivas canónicas en el laboratorio protegido.
- Añade 60 goldens del laboratorio y 6 del piloto, axe bloqueante y CI aislado
  con fixture sanitizado.
- Publica contratos ejecutables de gobierno, migración por módulo y cierre.
