# Recorrido de usuario final — hallazgos y plan

Fecha: 2026-07-29. Rama `pdc-revision-ux` (SPA + `lps-aia-pdc`), bundle desplegado idéntico al build
local (`md5 20e81fd0…`). Proyecto **Da Porto** (datos reales), stack del worktree en `:8091`.

**Cómo se hizo:** recorrido automatizado de **solo lectura** con Playwright usando el login del propio
repo — 18 capturas a 1440×900 y una pasada a 1024×768 sobre la pantalla más densa. Cero escrituras:
no se pulsó «Recalcular», «Proponer destinos», «Aceptar 40 sugeridas», «Amarrar», «Retirar» ni
«Confirmar». **Consola: 0 errores, 0 `pageerror`.** Nada de lo que sigue es un fallo funcional: el
módulo hace lo que promete. Lo que sigue es lo que estorba mirándolo como quien entra por primera vez.

---

## Lo que ya funciona bien (no tocar)

- El **asistente paso a paso** de Paquetes es la mejor pantalla del módulo: tarjeta, ruta de la
  actividad, el aviso «Sin propuesta. Ninguna señal alcanzó…» y el atajo `Enter` visible.
- La lista **«Paquetes con insumos»** y la tabla **«Sin frente»** ordenadas por valor descendente:
  quien entra ve primero lo que más plata mueve.
- Los estados vacíos escritos a mano («Ningún amarre quedó desactualizado») son claros y en español.
- La nota de «Recalcular» cumple lo prometido (f09) y las pestañas del módulo se leen bien.

---

## Hallazgos

Severidad: **P0** rompe la lectura del dato · **P1** cuesta trabajo o induce error · **P2** pulido.

### P0 — tres defectos con causa raíz localizada en una línea de código cada uno

| # | Qué se ve | Causa raíz (verificada) |
|---|---|---|
| **H1** | En **Maestro, Presupuesto y Comparar** el encabezado entero (título, bajada y el KPI de cobertura) está **pegado al borde derecho**. En Importar y Paquetes va a la izquierda. Parece otra aplicación. | `src/styles.css:64-65`: `.pdc-header` fija `flex-direction: column` y `.pdc-header-fila` **no la sobreescribe**. Queda una columna con `align-items: flex-end` → todo a la derecha. |
| **H2** | Las columnas de dinero **no alinean**: `$ 25.430.823.601,77`, `$ 102.290.635,8`, `$ 3.144.138`, `$ 1.866.977.292` en la misma tabla. Comparar magnitudes de un vistazo es imposible. | `src/lib/agGrid.ts:38`: `toLocaleString('es-CO')` **sin** `minimum/maximumFractionDigits` → 0, 1 o 2 decimales según el número. |
| **H3** | Todas las tablas miden lo mismo y muestran **5–6 filas**, con la última cortada por la mitad, mientras el **40–45 % inferior de la pantalla queda negro y vacío**. En Catálogo global son 5,5 filas de 3.079. | `src/styles.css:69`: `.pdc-grid { height: 320px }` fijo, sin relación con el viewport. |

### P1 — fricción real

- **H4 · El plan está al 11 % y la pantalla no lo dice.** «Plan (11)» convive con «Sin frente (85)»,
  pero el encabezado anuncia «**11** paquete(s)» como si fuera el total. Paquetes sí muestra su
  cobertura (99,7 %); el Plan —el entregable final— no tiene indicador equivalente.
- **H5 · Las palabras se parten a mitad.** `SUBCONTRATACIO/N PERSONAL` a 1440 px y en tres trozos a
  1024 px. Contradice el hecho f02 («el texto largo envuelve»): envuelve, pero rompiendo la palabra,
  porque la columna es más angosta que la palabra más larga.
- **H6 · A 1024 px las columnas «Destino» y «Sugerencia» de Paquetes quedan cortadas** sin barra de
  scroll visible ni pista de que hay más a la derecha.
- **H7 · «Ahorros $ -46.629.280.886,6»** — un ahorro con signo negativo, en verde, junto a un Δ
  también negativo. Se lee como si ahorrar restara.
- **H8 · «Aceptar 40 sugerida(s)» escribe 40 amarres de un clic**, sin previsualización ni desglose
  por confianza. Medido en el navegador el 2026-07-29: de las 40, **37 son de confianza media y
  solo 3 de confianza alta**.
  *Corrección al informe original:* dije que el badge de MEDIA y el de ALTA eran del mismo verde.
  **Es falso** — lo leí de una captura a tamaño reducido. Los colores computados sí difieren:
  media `rgb(58,47,24)` sobre ámbar, alta `rgb(26,60,42)` sobre verde, baja rojiza. Lo que falta no
  es color, es la **proporción**: nada en pantalla dice que 37 de 40 son medias antes de pulsar.
- **H9 · Paquetes abre por el residuo.** Con `POR VALOR 100 %`, la pestaña inicial muestra **un solo
  insumo de $ 0** precedido de **tres barras de controles y once botones**, casi todos inertes. El
  acierto de f06 («abrir por lo que falta») se vuelve en contra cuando ya no falta nada que importe.
- **H10 · Maestro abre por una pestaña vacía**: «Pendientes por vincular (0)» con el mensaje de
  AG Grid **«No Rows To Show»** — inglés y sin diseñar, justo donde la noticia es buena (100 % de
  cobertura). Con 0 pendientes debería abrir en el Catálogo.
- **H11 · El cargador de Excel es el control nativo del navegador**: «**Choose File · No file chosen**»,
  en inglés y sin estilo, en la **primera pantalla** del módulo.
- **H12 · Los tres paquetes vencidos (98, 83 y 66 días de retraso)** —lo más grave del proyecto— se
  informan con texto pequeño dentro de la fila, sin destacado ni acción asociada.

### P2 — pulido

- **H13** Locales mezclados en una misma tabla: dinero con coma decimal (`$ 3.499,65`) y cantidad con
  punto (`1433.08`). `columnaNumero` no formatea a propósito, pero el resultado se lee como un error.
- **H14** «11 paquete(s)», «0 seleccionado(s)», «8 insumo(s)», «1343 fila(s)», «Aceptar 40 sugerida(s)»:
  el plural con `(s)` aparece en las seis pantallas. Y `1343` sin separador de miles.
- **H15** «Catálogo global (3079)» en la pestaña vs «(3.079 insumos)» en el título, a diez píxeles.
- **H16** Tres cifras de insumos sin explicar: **820** (historial), **396** (cobertura del maestro),
  **3.079** (catálogo). Nada dice que cuentan cosas distintas.
- **H17** Etiquetas contradictorias: «Nómina de obra» e «Imprevistos y provisiones» llevan el badge
  **CONSUMIBLES**. Y el nombre repite el tipo que el badge ya dice (`Suministro CONCRETO` + `SUMINISTRO`).
- **H18** «Retirar» —acción destructiva— es un enlace subrayado en cada fila del catálogo, bajo una
  cabecera de columna vacía.
- **H19** «ACIERTO DEL MOTOR 100 %» sin decir sobre cuántos casos se mide ni qué cuenta como acierto.
- **H20** En el asistente, los botones de decisión («Omitir», «Saltar por ahora») caen **bajo el
  pliegue** a 900 px de alto, y la tarjeta ocupa media pantalla con la otra media vacía.
- **H21** «Sin frente» no es una tabla sino filas ad-hoc: los anchos de los `select` bailan de fila en
  fila y una cifra larga salta de renglón y desplaza su control.
- **H22** Sin buscador en «Paquetes con insumos» (102 filas), en «Sin frente» (85) ni en el comparador.
- **H23** La nota larga de «Recalcular» se repite en las cuatro pestañas del Plan, en todas las visitas.
- **H24** El anillo de foco quedó pintado sobre la pestaña activa tras el clic del recorrido —
  conviene confirmarlo con un clic humano antes de tocar nada.

---

## Plan de corrección y mejora

### Tanda 1 — «media hora, tres líneas» (P0) ✅ **hecha el 2026-07-29**

Verificada contra Da Porto en `:8091`: los tres encabezados a margen 0 del contenedor, 0 cifras con
decimales en el visor y en el comparador, el catálogo de 5,5 filas a 12, y la tabla acabando 84 px
sobre el pliegue en 768/900/1200/1440 px de alto. Vitest 184/184 (3 nuevos), build y los 14 e2e
`pdc-v2-*` en verde. Detalle de lo que se tocó, abajo.

| Paso | Cambio | Archivo |
|---|---|---|
| 1 | `.pdc-header-fila { flex-direction: row; }` | `src/styles.css:65` |
| 2 | `moneda()` → `toLocaleString('es-CO', { minimumFractionDigits: 0, maximumFractionDigits: 0 })`. En obra los pesos no se leen con centavos; si se quieren, que sean **siempre** dos. | `src/lib/agGrid.ts:38` |
| 3 | `.pdc-grid` de altura fija a altura fluida (`height: clamp(320px, calc(100vh - 340px), 780px)`), con `.pdc-grid--corta` para las tablas que sí deben ser bajas. | `src/styles.css:69` |

**Corrección sobre el diagnóstico del punto 3:** `.pdc-grid { height: 320px }` era **CSS muerto** —
ninguna página lo usaba. Las alturas reales estaban en `style` en línea, una por tabla: 260 y 280 en
Importar, 300 y **280 en el catálogo de 3.079 filas**, 520 en Comparar, 560 en el Visor. Paquetes y
Plan no entran: usan `domLayout="autoHeight"` y crecen con sus filas. El arreglo fue reutilizar
`.pdc-grid` —ahora fluida— en las cuatro tablas largas y `.pdc-grid-corta` en las dos cortas,
borrando los seis números escritos a mano.

También se ajustó `signo()` en `ComparativoPresupuesto.tsx:24`, que formateaba la columna Δ con el
mismo problema de decimales variables y vive justo al lado de las dos columnas de dinero.

Verificación: un test de `moneda()` en `agGrid.test.ts` que fije los decimales, y volver a correr este
mismo recorrido comparando capturas. **Ganancia desproporcionada respecto al costo — empezar aquí.**

### Tanda 2 — que el Plan diga la verdad (P1) ✅ **hecha el 2026-07-29**

Grillada (14 preguntas), planificada y aprobada en el gate: `goals/pdc-tanda2-plan-verdad/`.
Verificada en Da Porto: cobertura 54 % por valor · «11 de 96 paquetes con fecha»; franja de
vencidos que filtra a 3 filas; desglose «3 ALTA · 37 MEDIA · 0 BAJA» con dos botones y confirmación
de $ 5.790.756.244; ahorros sin signo; 0 palabras partidas a 1440 y 1024. Vitest 197/197, build y
14/14 e2e en verde.

4. **Cobertura del Plan en el encabezado**: «11 de 96 paquetes con fecha · 85 sin frente», con la
   misma barra que ya usa Paquetes. Hoy el número grande miente por omisión (H4).
5. **Los vencidos, arriba**: franja de alerta sobre la tabla («3 paquetes debieron arrancar hace hasta
   98 días») con enlace a la fila (H12).
6. **`Aceptar N sugeridas` con red de seguridad**: desglose por confianza, permitir aceptar solo las
   altas, y previsualizar antes de escribir (H8). Colores distintos para MEDIA y ALTA.
7. **Ancho mínimo por columna ≥ la palabra más larga** + `overflow-wrap: normal` en las celdas
   envueltas, y scroll horizontal visible por debajo de 1200 px (H5, H6).
8. **Arreglar «Ahorros»**: valor absoluto y rótulo explícito, o un único Δ con signo (H7).

### Tanda 3 — la primera impresión (P1/P2) ✅ **hecha el 2026-07-29**

Grillada, planificada y aprobada: `goals/pdc-tanda34-pulido/`. Verificado en Da Porto: cargador
propio con arrastre, el Maestro abre en «Catálogo global» con 0 pendientes, Paquetes muestra el
cierre por valor con los controles plegados, y **0 apariciones** de «No Rows To Show» y de «(s)».

9. **Abrir por donde hay trabajo, de verdad**: Maestro entra al Catálogo si hay 0 pendientes;
   Paquetes muestra el estado de cierre («100 % por valor · queda 1 insumo de $ 0») en vez de tres
   barras de controles para una fila (H9, H10). Y traducir/diseñar el vacío de AG Grid (`No Rows To Show`).
10. **Cargador de Excel propio**: botón del sistema + zona de arrastre + nombre del archivo elegido (H11).
11. **Plurales sin `(s)`** y separador de miles en todos los conteos, de una sola pasada (H14, H15).
12. **Una línea que explique los tres números de insumos** (820 / 396 / 3.079) donde aparecen (H16).

### Tanda 4 — cuando haya aire (P2) ✅ **hecha el 2026-07-29**

Mismo goal. Verificado: buscadores en las tres listas (con acentos), «Retirar» con confirmación y
cabecera, nota de «Recalcular» plegada, «Nómina de obra · NO CONTRATABLE» sin el CONSUMIBLES falso,
«acierto del motor 100 % sobre 2 decisiones», «Sin frente» alineada y el asistente sobre el pliegue.

13. Buscador en las tres listas largas (H22); «Retirar» con cabecera y confirmación (H18); la nota de
    «Recalcular» como ayuda contextual y no como párrafo permanente (H23); revisar los badges de
    modalidad para nómina e imprevistos (H17); explicar «acierto del motor» (H19); subir los botones
    del asistente sobre el pliegue (H20); convertir «Sin frente» en tabla real (H21).

### Recomendación

Ejecutar **tanda 1 ya, en esta rama** — son tres líneas, arreglan lo que más afea el módulo y no
tienen riesgo. La **tanda 2 merece su propio ciclo con grilleo**, porque el punto 4 y el punto 6
cambian qué entiende el usuario por «el plan está listo» y qué tan fácil es escribir 40 amarres de
confianza media: eso es decisión de producto, no de estilo. Tandas 3 y 4, sesiones aparte.

### Fuera de alcance, pero conviene mirarlo

El historial muestra **dos versiones del mismo archivo** (`102 DAPORTO RIONEGRO PI_Version_3.xlsx`,
403 actividades y 820 insumos en ambas) con costos que difieren **2,5×**: $ 74.974.013.394,31 → $ 29.492.804.353,65.
Encaja con el fix **A1.8** (antes se ignoraba `Cant APU` y los insumos salían inflados), así que lo más
probable es que la V1 sea del importador viejo. Vale confirmarlo antes de que alguien compare esas dos
versiones creyendo que el presupuesto bajó $ 45 mil millones.
