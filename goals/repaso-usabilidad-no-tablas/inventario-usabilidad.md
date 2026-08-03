# Inventario de usabilidad — superficies ajenas a tablas (H-08)

Medido el **2026-08-03** contra el contenedor `app` (`http://localhost:8081`), a **1180×820, dark**,
con sesión abierta por la puerta de servicio como `test.R` en el proyecto `PDC Sandbox E2E`.

**No se modificó nada.** Este documento es para decidir, no para ejecutar.

---

## 1. Alcance: qué se cubrió de cuánto

El encuadre de la tarea avisaba de que la cifra «31 superficies de la app más 14 de admin» no estaba
verificada. **Se verificó y es correcta.** El recuento de 217 rutas / 67 GET no-API mezclaba cosas que
no son pantallas.

`public/index.php` declara **222 rutas**, de las cuales 116 son GET y 81 GET no-`/api`. De esas 81 hay
que descontar lo que no es una superficie de usuario:

| Se descuenta | Cuántas | Por qué |
|---|---|---|
| `/plan-compras/api/*` | 35 | Son API; el prefijo no empieza por `/api` pero lo son. |
| `/runtime/css/*`, `/runtime/frontend-config.js` | 10 | Assets servidos por PHP. |
| `/dev/entrar`, `/logout`, `/*/set-filtro`, `/*/set-view-all` | 5 | Redirecciones y mutadores sin vista. |

**Quedan 31 superficies de la app.** En `admin/public/index.php` hay 23 rutas GET; descontando 5
endpoints JSON (`sugerir-rol`, `sugerir-usuario`, `family-catalog/export`, `pdc/limpieza/conteos`,
`dashboard/report-progress`), `/logout` y `/` (redirect), **quedan ~15, de las que 14 son pantallas
internas** más `/admin/login`. Las dos cifras del goal anterior se sostienen.

### Cubierto: 26 de 45

**22 superficies de la app** recorridas con captura y medición (`evidence/*.png`), más
`/programacion-semanal` auditada aparte, más **4 pantallas sin sesión** (`/login`,
`/password/forgot`, `/admin/login`, `/admin/password/forgot`).

### No cubierto: 19, y por qué

| No cubierto | Cuántas | Motivo |
|---|---|---|
| Pantallas internas de `admin/` | 14 | **Bloqueo duro.** `/dev/entrar` solo abre sesión en la app principal; `admin/` valida contra su propio `/admin/login`. `AGENTS.md` prohíbe teclear credenciales y prohíbe pedirle a una persona que entre. Sin una puerta de servicio para admin, no hay vía legítima. |
| `/programa-general-actualizar` | 1 | Superficie de escritura; abrirla en un recorrido de «solo mirar» arriesga mutar datos. |
| `/password/reset` | 1 | Exige un token de correo válido. |
| `/reportes/{tipo}` | 1 | Ruta paramétrica; sin un `{tipo}` conocido no se puede elegir sin adivinar. |
| `/internal/design-system` | 1 | Es el laboratorio del sistema de diseño, no una superficie de producto. |
| `/legacy/cambiar_pagina.php` | 1 | Legado en modo mantenimiento. |

**Priorización de lo cubierto:** se priorizó por uso operativo real — los cuatro niveles de
planificación LPS (programa general, intermedia, semanal, CIC/CNC/CNP), el módulo de compras
(PDC v2 y la SPA) y las 8 superficies de BI, que es donde el residente y el director pasan el día.

---

## 2. Los ejes automáticos, re-medidos

Confirmado lo que decía el encuadre, y **ampliado**: el goal anterior midió contraste y tamaño de
objetivos solo en `/proyectos` y `/dashboard/escalamientos`. Re-medidos ahí, siguen limpios. Pero al
extender la medición a las 22 superficies aparecieron **dos fallos reales que nadie había mirado**,
ambos en BI (H-34 y H-33). No son «volver a medir lo mismo»: son cobertura que no existía.

Dos avisos metodológicos, para que nadie repita el error:

- **Los colores en `oklch()` rompen cualquier medidor ingenuo de contraste.** Un primer script propio
  dio 1.17:1 para el chip de BI; el valor real es **3.01:1**. `getComputedStyle()` devuelve la cadena
  `oklch(...)` sin resolver, y un parser de `rgb()` la lee como números sueltos. Hay 48 tokens en
  `oklch` en `public/css/tokens.css`.
- **El repositorio ya resuelve esto bien.** `tests/browser/support/contrast.mjs` rasteriza por canvas
  y documenta el problema en su propia cabecera. El gate del repo **no** es ciego. El hueco de H-34 es
  de *cobertura* (ningún spec de BI comprueba contraste), no de herramienta.

---

## 3. Hallazgos

**39 hallazgos: 15 altas, 15 medias, 9 bajas** (ninguno crítico). Severidad: **crítica** (impide la
tarea) · **alta** (falla o confunde de forma seria) · **media** (fricción con salida) · **baja**
(pulido).

> Una versión previa de este párrafo decía «38». Era un error de recuento en la prosa; las tablas
> siempre tuvieron 39 filas, de H-01 a H-39.

**Alcance aprobado por el usuario el 2026-08-03: las 15 altas y las 15 medias, 30 de 39.** Las 9
bajas quedan fuera. Reparto por identificador:

| Severidad | Identificadores | ¿Entra? |
|---|---|---|
| alta (15) | H-01 H-02 H-03 H-07 H-08 H-09 H-12 H-13 H-16 H-17 H-18 H-24 H-33 H-34 H-38 | sí |
| media (15) | H-04 H-05 H-10 H-14 H-19 H-20 H-21 H-25 H-26 H-28 H-29 H-30 H-35 H-36 H-39 | sí |
| baja (9) | H-06 H-11 H-15 H-22 H-23 H-27 H-31 H-32 H-37 | no |

### 3.1 Estados vacíos

| # | Superficie | Descripción | Sev. | Captura |
|---|---|---|---|---|
| H-01 | `/programacion-semanal` | La malla renderiza cabeceras y fila de filtros con **0 filas y ~700 px de vacío**, sin un solo mensaje. El usuario no sabe si no hay actividades, si falló la carga o si sigue cargando. Existe `.ps-mobile-empty` pero **no** su equivalente desktop. | alta | (auditada en vivo; ver §5) |
| H-02 | `/control-cambios` | **550 px de vacío absoluto** bajo las cabeceras. Ningún mensaje, ni bueno ni malo. Es el peor estado vacío de la app. | alta | `control-cambios.png` |
| H-03 | `/indicadores` | El informe Power BI embebido se pinta como un **rectángulo blanco vacío**, sin estado de carga, sin error, sin «no hay datos». Además llega en **tema claro dentro de la app oscura** y con paginación en inglés. | alta | `indicadores.png` |
| H-04 | `/pdc` | «No hay paquetes de contratación para mostrar.» flota suelta **encima de una malla vacía que se dibuja igual**, cabeceras incluidas. Sin acción siguiente ni explicación de cómo se crea un paquete. | media | `pdc.png` |
| H-05 | `/bi/control-tower` | La tarjeta «Resumen Ejecutivo» muestra **`--`** en 150 px de alto. Un guion doble no distingue «vacío» de «error» de «cargando». | media | `bi-control-tower.png` |
| H-06 | `/dashboard/escalamientos` | Las 4 tarjetas están dimensionadas para datos: bajo una línea («Sin crisis en este nivel») quedan **~250 px de vacío** cada una. No dice qué aparecería ahí ni de dónde vendría. | baja | `dashboard-escalamientos.png` |

**El patrón bueno ya existe en la casa.** No hay que inventarlo:

| Superficie | Por qué es el modelo |
|---|---|
| `/programacion-semanal/cnc` y `/cnp` | *«Sin causas de no cumplimiento esta semana. Se registran al justificar un avance menor al compromiso en Programación Semanal.»* Dice **qué** falta, **cómo** se crea y **dónde**. Es el estándar de oro de la app. |
| `/bi/programa-general` | Cuatro estados vacíos distintos, uno por gráfico, específicos: *«No hay CNC registradas para los filtros activos. El gráfico no inventa causas…»* |
| `/plan-compras` | «Todo empieza con el presupuesto» + explicación de por qué ese paso alimenta a los demás. |
| `/proyectos` | Dos estados distintos (sin proyectos / sin resultados de búsqueda) **y** un `role="status" aria-live="polite"` para el lector de pantalla. |

### 3.2 Etiquetas truncadas — Krug #1, «no puedo leer el control»

Medido con `scrollWidth` vs `clientWidth` sobre el DOM real, no a ojo.

| # | Superficie | Descripción | Sev. | Captura |
|---|---|---|---|---|
| H-07 | `/pdc` | **9 cabeceras truncadas sin elipsis ni tooltip.** Lo grave: tres columnas contiguas quedan como **«INICIO EN O…»** e **indistinguibles entre sí**. «INICIO DEL PROCESO DE CONTRATACIÓN» dispone de 75 px de los 262 que necesita (29 %). | alta | `pdc.png` |
| H-08 | `/control-cambios` | Los cuatro filtros son más estrechos que su propio valor: **«Tod⌄», «Toda⌄», «Bu»**. Un filtro que no muestra por qué está filtrando no es un filtro. | alta | `control-cambios.png` |
| H-09 | `/programacion-semanal` | Dos botones de la barra principal cortados: **«Leyenda» → «Leyend»** (40/67 px) y **«Recargar» → «Recarga»** (43/71 px). En `/cnc` el mismo botón «Leyenda» se ve entero: superficies hermanas, resultados distintos. | alta | (§5) |
| H-10 | `/programa-general` | 7 cabeceras cortadas («Sem. Inicio», «Crítica», «Cant. PPTO», «Ej. Teórico»…). | media | `programa-general.png` |
| H-11 | `/cnc`, `/cnp` | Buscador cortado: «Buscar actividad o cau». | baja | `programacion-semanal-cnc.png` |

### 3.3 Solapes — controles tapados por otros elementos

| # | Superficie | Descripción | Sev. | Captura |
|---|---|---|---|---|
| H-12 | `/programacion-semanal` | El rail vertical **«CONCURRENCIA LPS» cubre el botón «Ver Secciones» en 44×45 px**, es decir el botón entero. No es que estorbe: lo tapa. | alta | (§5) |
| H-13 | `/programacion-intermedia` | El mismo rail solapa **cabeceras de columna en 42×144 px**, incluida «Estado Operativo». | alta | `programacion-intermedia.png` |
| H-14 | `/programa-general` | 20 solapes detectados entre celdas y cabeceras de la malla. Territorio de tablas: se anota pero **corresponde al goal de tablas**, no a H-08. | media | `programa-general.png` |
| H-15 | `/plan-compras` | El botón «Omitir» del tour solapa los enlaces de navegación «Control Tower - Informes» y «Semanas del Proyecto». | baja | `plan-compras.png` |

### 3.4 Jerarquía, densidad y navegación

| # | Superficie | Descripción | Sev. | Captura |
|---|---|---|---|---|
| H-16 | Las 8 de `/bi/*` | **La barra de pestañas desborda: 1626 px de contenido en 1116 px visibles.** Con `overflow-x: auto` pero **sin flecha, sin degradado y sin barra visible**: ~3 de 8 módulos de BI son inalcanzables salvo que el usuario adivine que puede desplazar. Navegación oculta a módulos reales, en el viewport canónico. | alta | `bi-control-tower.png` |
| H-17 | `/plan-compras` | El coach-mark del tour **tapa justo lo que explica**: cubre el H1 «Importar presupuesto», el breadcrumb, dos pestañas y la zona de carga cuyo texto («…máx. 10MB») queda cortado a media frase. Un tour que esconde su propio objetivo. | alta | `plan-compras.png` |
| H-18 | `/dashboard/escalamientos` | **Rompe el shell entero**: sin sidebar, sin breadcrumb, sin nombre de proyecto. Único escape: «Volver a Planificación». Falla el Trunk Test en las seis preguntas y es la única superficie interna que lo hace. | alta | `dashboard-escalamientos.png` |
| H-19 | `/indicadores`, `/control-cambios`, `/cic`, `/cnc`, `/cnp`, `/pdc` | **Seis superficies sin ningún encabezado** (`h1`–`h4`). El único título es el breadcrumb. Para un lector de pantalla la página no tiene nombre. | media | varias |
| H-20 | `/plan-compras` | **Jerarquía invertida en el tour**: «Omitir» (descartar) va resaltado y con borde claro, «Siguiente» (avanzar) queda en verde apagado. La acción de abandono pesa más que la de continuar. | media | `plan-compras.png` |
| H-21 | `/proyectos` | Los dos botones tienen el **nombre accesible idéntico** («Ingresar al proyecto»). El título de la tarjeta no es enlace. Con lector de pantalla los dos proyectos son indistinguibles. | media | `proyectos.png` |
| H-22 | `/pdc` | **7 chips de filtro, todos en 0**, ocupando el tercio superior. Cuando todo vale cero, los chips son ruido puro sobre una malla vacía. | baja | `pdc.png` |
| H-23 | `/proyectos` | Dos tarjetas en una rejilla de 1180 px dejan ~60 % de ancho muerto, y el buscador ocupa 660 px para filtrar dos elementos. Densidad calibrada para un caso que no es el habitual. | baja | `proyectos.png` |

### 3.5 Retroalimentación y estado del sistema

| # | Superficie | Descripción | Sev. | Captura |
|---|---|---|---|---|
| H-24 | `/programacion-semanal` | **Abrir la pantalla dispara un POST de guardado y una auto-programación, sin que el usuario toque nada y sin avisar.** Ya estaba medido en `memoria/trampas/semanal-auto-dispara-mutaciones.md`; **se reconfirma** y se eleva: no es solo una trampa para asistentes, es un fallo de usabilidad — el usuario no puede «solo mirar» su semana. | alta | (§5) |
| H-25 | Las 8 de `/bi/*` | El disparador dice **«Filtros 0»** mientras justo debajo se muestra el chip activo **«Proyectos: PDC Sandbox E2E»**. El contador y la realidad se contradicen en pantalla. | media | `bi-control-tower.png` |
| H-26 | `/dashboard/escalamientos` | Error de JS en carga: **`hot.addHook is not a function`**. Falla en silencio: la página parece correcta. | media | `dashboard-escalamientos.png` |
| H-27 | `/cnc`, `/cnp` | «Mostrando registros del 0 al 0 de un total de 0 registros» aparece **debajo del excelente mensaje de vacío**, contradiciéndolo con jerga de paginador. Dos mensajes de vacío apilados, uno bueno y uno inútil. | baja | `programacion-semanal-cnc.png` |

### 3.6 Copia de interfaz

Comprobado contra `GLOSARIO.md` y `memoria/mapas/lps-dominio.md`: **CNC, CNP, CIC, PPC, PAC, LPS,
PDC, restricción, compromiso** son vocabulario de dominio **correcto** y no se reportan como error.
Lo de abajo no es vocabulario, son defectos.

| # | Superficie | Descripción | Sev. | Captura |
|---|---|---|---|---|
| H-28 | `/pdc` | **Siete chips sin tildes**: «Informacion pendiente», «Inicio de contratacion vencido», «Contratacion atrasada»… mientras el mensaje de vacío de la misma pantalla sí escribe «contratación». Incoherencia dentro de una sola vista. | media | `pdc.png` |
| H-29 | Las 8 de `/bi/*` | **«0 count»** en los cuatro KPIs: unidad cruda, en inglés, filtrada desde la capa de datos a la interfaz. | media | `bi-control-tower.png` |
| H-30 | `/control-cambios` | Dos columnas casi homónimas: **«Detalle Solicitante»** y **«Detalle»**. Nada indica en qué se diferencian. | media | `control-cambios.png` |
| H-31 | `/indicadores` | Paginación del embebido en inglés: **«1 of 4»**. | baja | `indicadores.png` |
| H-32 | `/login` | «Bienvenido a Last Planner AIA» sobre una página cuyo `h1` ya lo dice: *happy-talk* que no ayuda a entrar. | baja | `login.png` |

### 3.7 Consistencia entre superficies que hacen lo mismo

El hallazgo más barato de arreglar y el más revelador: **la app y admin resuelven la misma pantalla
de forma distinta, y admin la resuelve peor.**

| # | Superficie | Descripción | Sev. | Captura |
|---|---|---|---|---|
| H-33 | `/admin/login` | Los campos `usuario` y `password` **no tienen `<label>`** (solo `placeholder`, que desaparece al escribir) y llevan **`autocomplete=""` vacío**, así que ningún gestor de contraseñas los rellena. `/login` de la app hace las dos cosas bien: tiene labels y declara `autocomplete="username"` / `"current-password"`. Mismo trabajo, dos calidades. | alta | `admin-login.png` vs `login.png` |
| H-34 | Las 8 de `/bi/*` | **`.bi-chip` incumple WCAG AA: 3.01:1 frente al 4.5:1 exigido** (12 px, peso 900 — no califica como texto grande). Causa exacta en `public/css/bi-control-tower.css:106`: empareja `--ds-color-brand-aqua` sobre `--ds-color-state-info-bg`. **Los tokens están bien; el emparejamiento no.** Ningún spec de BI comprueba contraste. | alta | `bi-control-tower.png` |
| H-35 | `/admin/password/forgot` | Campo de correo **sin `<label>`**; el de la app sí lo tiene. Además el botón es «Enviar enlace» y en la app «ENVIAR ENLACE», y la copia difiere («un enlace **seguro** para crear una nueva contraseña» vs «un enlace para acceder nuevamente al panel»). Dos pantallas, mismo trabajo, tres diferencias gratuitas. | media | `admin-password-forgot.png` |
| H-36 | Las 8 de `/bi/*` | Botón «Quitar filtro» de **20×20 px** (`.bi-chip button`, `min-width/height: 1.25rem`). Por debajo de cualquier mínimo razonable, y es la única forma de deshacer un filtro. | media | `bi-control-tower.png` |
| H-37 | `/dashboard/escalamientos` | **Emoji `✅` crudo** como icono de estado en las 4 tarjetas, en vez de la iconografía del sistema de diseño que usa el resto de la app. Y los 4 badges de conteo usan 4 colores distintos para el mismo valor `0`, sin leyenda que explique qué codifica el color. | baja | `dashboard-escalamientos.png` |

### 3.8 Flujo — callejones sin salida

| # | Superficie | Descripción | Sev. | Captura |
|---|---|---|---|---|
| H-38 | `/control-cambios` | **No hay ninguna forma visible de crear un cambio.** La pantalla ofrece filtrar y leer una tabla vacía, y nada más. Sumado a H-02 (sin estado vacío) y H-08 (filtros ilegibles), es la superficie en peor estado de las 26 revisadas. | alta | `control-cambios.png` |
| H-39 | `/dashboard` | **Es un redirect a `/programacion-semanal`**, no una pantalla. La app no tiene panel de inicio: al entrar, el usuario aterriza directamente en la superficie que además muta datos sola (H-24). | media | — |

---

## 4. Puntuación heurística (Nielsen, 0–4)

Sobre el conjunto de las 26 superficies cubiertas, no sobre una sola pantalla.

| # | Heurística | Punt. | Hallazgo que la define |
|---|---|---|---|
| 1 | Visibilidad del estado del sistema | 1 | H-01/02/03 sin estados vacíos; H-25 contador que se contradice; H-24 muta sin avisar. |
| 2 | Correspondencia con el mundo real | 3 | Vocabulario LPS correcto y bien usado. Pierde por H-29 («0 count») y H-31. |
| 3 | Control y libertad del usuario | 2 | H-24 (no se puede «solo mirar»), H-38 (sin salida), H-36 (deshacer filtro de 20 px). |
| 4 | Consistencia y estándares | 1 | H-33/H-35 app vs admin; H-18 rompe el shell; H-09 el mismo botón se corta en una vista y no en su hermana. |
| 5 | Prevención de errores | 2 | H-24 es lo contrario de prevenir: actúa sin pedirlo. |
| 6 | Reconocer antes que recordar | 2 | H-07 tres columnas idénticas por truncado; H-16 pestañas ocultas; H-19 páginas sin título. |
| 7 | Flexibilidad y eficiencia | 2 | Sin atajos de teclado ni acciones masivas fuera de las mallas. |
| 8 | Diseño estético y minimalista | 2 | H-22 siete chips en cero; H-23 densidad mal calibrada; H-05 «--». |
| 9 | Recuperación de errores | 1 | H-26 falla en silencio; H-03 no distingue error de vacío. |
| 10 | Ayuda y documentación | 3 | El tour de `/plan-compras` y los estados vacíos de CNC/BI son ayuda contextual real y buena, aunque H-17 la estropee al taparla. |
| | **Total** | **19/40** | **Pobre** — pero concentrado: 6 de los 8 puntos perdidos salen de estados vacíos y consistencia. |

La lectura importante: **la app no está mal diseñada, está desigualmente terminada.** Las mejores
superficies (`/cnc`, `/bi/programa-general`, `/proyectos`, `/plan-compras`) son genuinamente buenas y
ya contienen el patrón correcto. El problema es que ese patrón no se aplicó a las demás.

---

## 5. Nota sobre `/programacion-semanal`

Se auditó **una sola vez y en vivo**, no en el barrido automático, precisamente por
`memoria/trampas/semanal-auto-dispara-mutaciones.md`. Aun así hubo **dos aperturas**: la puerta de
servicio con `p=<proyecto>` aterriza ahí por defecto, y `/dashboard` redirige ahí (H-39). Es decir:
la trampa es **inevitable** para cualquiera que abra una sesión en este proyecto, lo que refuerza
que H-24 es un problema de producto y no solo de método de prueba.

Las mediciones de H-01, H-09, H-12 y H-24 se tomaron con geometría del DOM (`getBoundingClientRect`,
`scrollWidth`/`clientWidth`), no a ojo sobre una captura.

---

## 6. Recomendación priorizada

### Bloqueante — nada debería cerrarse sin esto

1. **H-38 + H-02 + H-08 · `/control-cambios`.** Es la única superficie donde el usuario no puede
   hacer su trabajo: no puede crear un cambio, no sabe si la tabla está vacía o rota, y no puede
   leer sus propios filtros. Los tres se arreglan juntos o no se arregla ninguno.
2. **H-16 · pestañas de BI.** Tres de ocho módulos son inalcanzables en el viewport canónico. Es
   navegación perdida a funcionalidad que existe y está terminada. Arreglo barato (indicador de
   desplazamiento o envolver las pestañas).

### Ahora — alto valor, coste bajo y acotado

3. **H-33 + H-35 · labels y `autocomplete` en admin.** Dos líneas de HTML por campo, copiando lo que
   `/login` ya hace bien. Es el arreglo con mejor relación valor/esfuerzo del inventario.
4. **H-34 · contraste de `.bi-chip`.** Un emparejamiento de tokens en una línea de CSS, más un
   `expect` de contraste en los specs de BI para que no vuelva a pasar sin que nadie lo vea.
5. **H-07 + H-09 · etiquetas truncadas en `/pdc` y `/programacion-semanal`.** Que tres columnas se
   llamen igual en pantalla es un error de lectura esperando ocurrir.
6. **H-12 + H-13 · el rail «CONCURRENCIA LPS».** Tapa un botón entero y cabeceras de columna en dos
   superficies. Un solo arreglo de posicionamiento resuelve ambas.
7. **H-01 · estado vacío de la malla semanal.** Copiar literalmente el patrón de `/cnc`, que ya está
   escrito y es el mejor de la app.

### Diferible — mejora real, sin urgencia

8. **H-17 + H-20 · el tour de `/plan-compras`.** El contenido es bueno; el posicionamiento y la
   jerarquía de botones no.
9. **H-03 · `/indicadores`.** Requiere decidir antes si el embebido de Power BI se conserva. Es una
   decisión de producto, no de diseño (ver §7).
10. **H-18 · shell de `/dashboard/escalamientos`** y **H-26** (su error de JS).
11. **H-28 + H-29 + H-30 · copia.** Tildes, «0 count», columnas homónimas. Barato pero sin impacto
    en la tarea.
12. **H-04, H-05, H-06 · estados vacíos restantes**, siguiendo el mismo patrón de H-01.
13. Resto de severidad baja.

### Fuera de este inventario

- **H-14** y los objetivos de 13 px en las mallas pertenecen al **goal de tablas**, no a H-08.
- **H-24 · las mutaciones automáticas de `/programacion-semanal`** merecen **sesión propia**: tocan
  persistencia y no se pueden diagnosticar sin `systematic-debugging`. No es un arreglo de UI.

---

## 7. Decisiones que necesitaban al usuario — resueltas el 2026-08-03

Las tres se plantearon como preguntas abiertas y **ya están decididas**. Ninguna se ejecuta en este
goal: las tres generan trabajo propio.

| # | Decisión | Resolución | Consecuencia |
|---|---|---|---|
| 1 | **`/indicadores` (H-03):** ¿se conserva el embebido de Power BI? | **Se conserva**, con estado de carga y de error, y **enmarcado** para que el salto a tema claro se lea como «contenido externo» y no como un fallo. | No se puede oscurecer un iframe ajeno: la solución es de encuadre y de estados, no de tema. |
| 2 | **`/dashboard` (H-39):** ¿debe existir un panel de inicio? | **Sí**, con doble motivo: dar un aterrizaje neutro **y** quitar que entrar a la app dispare escrituras. | Absorbe **H-24**. Es funcionalidad nueva y va con su propio diseño, no como tarea suelta de este inventario. |
| 3 | **Las 14 pantallas de `admin/`:** ¿se extiende la puerta de servicio? | **Sí se extiende**, con el mismo candado que la existente: solo desarrollo, sin conceder permisos por encima de la propia cuenta. | Toca autenticación, así que **lleva spec propio**. Sin ella esas 14 pantallas son invisibles para cualquier revisión automatizada, hoy y en el futuro. |

Motivo de la decisión 3, para el registro: las dos únicas pantallas de admin que **sí** se pudieron
ver (H-33, H-35) resultaron ser las peores del inventario en accesibilidad. La sospecha sobre las
otras 14 es razonable, pero **sigue siendo sospecha**: nadie las ha mirado.
