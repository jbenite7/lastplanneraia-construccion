---
capa: fuente
tipo: spec
estado: vigente
fecha: 2026-08-25
areas: [proceso]
fuente: docs/superpowers/specs/2026-08-25-mapa-unico-del-trabajo-vivo-design.md
resumen: "Spec madre: el trabajo vivo del repo medido contra el código, en un solo documento, del que se desglosan los planes. Sustituye la práctica de abrir una spec por pendiente"
---

# El mapa único del trabajo vivo — Design

- **Fecha:** 2026-08-25
- **Origen:** decisión de Felipe — «construyamos un spec madre, que englobe a los demás, y de ahí se
  desglosa en los planes que sean requeridos. **No quiero más specs atómicos.**»
- **Método:** cada línea de este documento se midió contra el código o contra la salida de un
  comando. **Ninguna cita otra spec, otro inventario ni un `goal.md` como prueba.**

## Por qué existe, y el defecto que lo motiva

El repo llegó a **setenta specs**. La forma de trabajar era: aparece un pendiente, nace una spec. El
resultado no fue más orden sino menos, y tiene una medida exacta detrás.

**El 2026-08-25 se descubrió que cuatro documentos afirmaban, desde hacía trece días, que el Plan de
Compras no estaba en producción.** Llevaba desplegado desde el **2026-08-12**. El error nació en una
frase de `c1-retiro-pdc-viejo` —cierta al escribirse— que citaba `1aa7c69`, el sha **de partida** del
despliegue, y que se leyó como estado de llegada. De ahí pasó a la spec de despliegue, al informe de
auditoría del 20 de agosto, al inventario, y a un encargo del 25 ya convertida en premisa. Para el
cuarto lector el dato venía de tres sitios distintos: era el mismo texto cuatro veces.

Lo destapó el dueño del producto en una frase, no una auditoría.

**Ese no es un descuido: es lo que produce un sistema de setenta documentos que se citan entre sí.**
Ninguno miente; cada uno era cierto cuando se escribió. Lo que falla es que **una spec cerrada no
deja de ser leída, deja de ser revisada**, y sigue exportando su estado congelado a quien la cite.

Este documento reemplaza esa práctica: un solo mapa, medido, del que salen planes.

## Lo que se midió, y cómo

Siete specs (las seis vigentes más la que se cerró ese día), sus condiciones de hecho abiertas punto
por punto, y cada punto contrastado con código, `git` o la salida de un comando. Cuando algo no se
pudo medir, **está escrito como no verificable en vez de rellenarse**.

El hallazgo transversal, que se repitió en cada frente: **una parte grande de lo que figuraba como
pendiente ya estaba hecho.** No es anécdota, es la mitad del resultado.

| Dónde | Se creía pendiente | Estaba hecho | Medido en |
|---|---|---|---|
| `reparto-trabajo-pendiente` | 6 líneas «sin empezar» | **4 de 6** | goldens con estados (`tests/browser/programa-general.visual.mjs`), puerta de servicio de admin (`admin/public/index.php:73`), panel de inicio (`public/index.php:294`), chip de estado (`public/js/modules/programa_general/hot.js:1773`) |
| Usabilidad (línea E) | 26 hallazgos | **10 arreglados**, 10 no verificables | ver tabla del frente 3 |
| `despliegue-pdc-v2-produccion` | «producción sin tocar» | **desplegado el 2026-08-12** | `memoria/referencias/produccion-deploy.md` |
| `cierre-prelanzamiento-pdc` | contradictoria | **cerrada con evidencia** | seis condiciones medidas, `84291369` |

**Queda vivo mucho menos de lo que el papel decía, y está mucho más concentrado.**

## El trabajo vivo, en cuatro grupos

Ordenados por lo que cuestan, no por su antigüedad.

### Grupo 1 · Papeles que solo hay que cerrar — minutos

No hay trabajo de producto aquí. Es escribir un cierre que ya se ganó.

| Qué | Estado medido |
|---|---|
| `estado-real-de-planes-y-specs` | Su trabajo se hizo: 124 de 165 documentos ya llevan veredicto. **Es la única spec vigente sin sección de estado verificado — el barrido no se aplicó a sí mismo.** `npm run test:wiki` da verde hoy |
| `estado-consolidado-del-repo` | Andamio cumplido: los seis planes P1–P6 existen y tienen su estructura. Su pendiente **no es suyo**, es de P3–P6; mantenerla abierta confunde «mi cola no terminó» con «yo no terminé» |

### Grupo 2 · Pequeño y de valor alto — horas

| # | Qué | Por qué importa | Costo |
|---|---|---|---|
| 2.1 | **Comprobar que la obra usa el Plan de Compras en producción** | Es la única pregunta de negocio abierta del módulo. Cinco de las siete condiciones del despliegue están cumplidas; falta el humo funcional autenticado —el del 12-ago se hizo bajo mantenimiento, que prueba que la aplicación arranca, no que el módulo opere— y dejar constancia de que alguien de obra llegó a la pantalla | media hora |
| 2.2 | **Cuatro arreglos de usabilidad de una línea** | Contraste del chip de BI (`public/css/bi-control-tower.css:202`), su botón de 20 px (`:218`), la etiqueta ausente en recuperar contraseña (`admin/views/pages/password-forgot.php:39`), y el nombre repetido en el selector de proyecto (`views/core/project_selector.view.php:122`) | horas |
| 2.3 | **El recibo que le falta al candado de rendimiento** | `runtime-budgets` tiene procedencia de corrida real; `full-app-flow` lleva recibo «regenerado localmente». La condición exige los dos | horas |
| 2.4 | **El último color inventado del sistema** | `public/css/tokens.css:762` mantiene un color propio fuera de los dos canales. Sigue teniendo objeto: `pg-state-restr-0` continúa vivo (`hot.js:1066`). **Pide su visto visual antes de fijarlo** | horas |
| 2.5 | **Dos deudas de higiene, medidas** | El `CHANGELOG` está desordenado (`1.2.0` aparece después de `1.1.0`) y las dos reglas de coordinación que P6 mandaba escribir **nunca se escribieron** (cero coincidencias en `docs/coordinacion-sesiones.md`). La segunda importa: es la lección que evita repetir el defecto de arriba | horas |

### Grupo 3 · Frentes grandes de verdad — semanas

Son tres, y conviene no confundirlos con lo anterior.

**3.1 · La Torre de Control todavía no deja escribir.** Existen el catálogo de métricas y el servicio
de linaje en el fondo, pero nadie los conectó a la pantalla: no hay endpoint de escritura
(`public/index.php:337,358` solo declara lectura), no hay columnas donde guardar una asignación de
responsable o fecha, y el linaje no llega a la interfaz. Implica **migración de esquema**, con su
gate, respaldo y dry-run. Su diseño técnico vive en `2026-08-20-replanteo-control-tower-design.md`,
que **este documento no reemplaza**: lo referencia.

**3.2 · El design system sobre las tablas.** Cinco módulos siguen con Handsontable en su forma
heredada (`public/js/modules/*/hot*.js`) y los adaptadores de DataTables y las tarjetas legadas
siguen intactos. Es el trabajo de DS-F1 y DS-F2.

**3.3 · El tema claro no existe.** Cero ocurrencias de conmutador o tema en `public/css`. No es
reactivar nada: es construirlo.

Y dos piezas medianas que no son frente propio pero tampoco caben en el grupo 2: el **guardado que
se dispara al abrir** Programación Semanal (`public/js/modules/programacion_semanal/hot.js:2262`), y
las dos superficies que la usabilidad dejó como trabajo de producto — el shell de escalamientos
(`views/dashboard/escalamientos.php:20`) y el formulario de creación de control de cambios, que
**hoy no existe** (`views/control-cambios/controlCambios.view.php:855`).

### Grupo 4 · Bloqueado por decisión humana, no por código

El despliegue de todo lo demás a producción (CP-F-E) exige **autorización explícita de Felipe, cada
vez**. No entra en ninguna priorización técnica: entra cuando él lo diga.

## Lo que este documento NO hace

- **No reemplaza diseño técnico.** Las specs que contienen diseño real —`replanteo-control-tower`— se
  referencian, no se derogan. Absorber una lista de pendientes es útil; borrar un diseño es pérdida.
- **No ejecuta.** De aquí salen planes con `writing-plans`; este documento ordena y mide.
- **No cierra el trabajo de otros.** Las specs del grupo 1 se cierran porque su condición de hecho
  está cumplida y medida, no para bajar un conteo.

## Condición de hecho

1. Las specs del grupo 1 están cerradas, cada una con su evidencia citada en su propio documento.
2. `reparto-trabajo-pendiente` queda absorbida por este mapa: sus cuatro líneas cumplidas quedan
   registradas como tales, y sus tres restos vivos (2.2, 2.4 y el guardado al abrir) viven aquí.
3. Cada uno de los tres frentes grandes tiene **su plan escrito**, no su spec: `writing-plans` sobre
   este documento, sin abrir specs atómicas nuevas.
4. `IMPLEMENTATION_PLAN_INVENTORY.md` refleja el resultado y **ninguna de sus filas se contradice con
   el frontmatter del documento que describe**.
5. No queda en el repo ninguna afirmación sobre el estado de producción que no se haya medido contra
   producción. La regla que lo evita está escrita en `docs/coordinacion-sesiones.md` (tarea 2.5).

## Riesgos

- **El riesgo principal es que este documento envejezca igual que los setenta que ordena.** La
  mitigación no es escribirlo mejor: es que cada línea traiga su `archivo:línea`, de modo que
  comprobar si sigue siendo cierta cueste un `grep` y no una lectura. Está escrito así a propósito.
- **Diez de los veintiséis hallazgos de usabilidad quedaron no verificables**, la mayoría por
  superficies que cambiaron de dueño (el PDC viejo se retiró y su sucesor es otra aplicación).
  Declararlos así es deliberado: convertirlos en «arreglados» sería fabricar el resultado que este
  documento existe para evitar.
- **El grupo 3 no cabe en una sesión.** Si se arranca por ahí sin plan, se repite el patrón de dejar
  trabajo a medias que otro documento dará por pendiente meses después.
