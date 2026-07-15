# Design System AIA changelog

## 0.3.6 - En construcción

### API candidata para la garantía 1.0.0

El contrato de release enumera únicamente componentes con madurez `stable` y
evidencia tanto en el laboratorio como en Programa General: `shell`,
`page-header`, `toolbar`, `button`, `field`, `state`, `card`, `table-shell`,
`overlay` y `handsontable-adapter`. Los componentes `candidate`, las primitivas
BI y los adaptadores sin consumidor real quedan fuera. Esta enumeración no
activa la garantía SemVer: la versión permanece `0.3.6 / construction` hasta
que todos los gates de cierre estén aprobados y se cree el commit de release.

- Define un artefacto versionado y un comparador fail-closed para los presupuestos de runtime de Programa General: CSS/JS gzip, adapters cargados, solicitudes duplicadas, flash de tema, inicialización, interacción Handsontable y ausencia de assets del laboratorio.
- Registra la medición retrospectiva reproducible de 0.3.3 sobre la misma fixture sanitizada del runtime actual: tres muestras, medianas explícitas, hash estable de assets y trazabilidad del checkpoint. La medición no se convierte en baseline aprobado ni inventa tolerancias.
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
