---
capa: fuente
tipo: reporte
estado: abierto
fecha: 2026-08-20
areas: [pdc, datos]
fuente: tabla `pdc` y tablas hermanas del PDC v1 en producción, medidas por SELECT el 2026-08-21
resumen: Los 409 «planes de compras completos» del PDC v1 no son un historial de desempeño — son el esqueleto de un plan que nunca se llenó. 273 filas sin una sola fecha, 136 en la obra «Prueba» con fechas planeadas y 4 fechas reales, cero valores, cero contratos. No hay memoria que rescatar; sí hay una lección para el plan nuevo.
---

# Historial de compras del PDC v1 — qué hay de verdad y qué hacer con eso

**Para quién:** Felipe, y la sesión que edita la spec del replanteo (`sesion-40aa70aa-db1`).
**Decisión que alimenta:** D64 (consultar los 409 planes y sacarlos de producción) y la fase F6
de la spec, «rescate del historial del PDC v1».

## La respuesta corta

La premisa de D64 está equivocada en el dato, no en la decisión. La tabla `pdc` tiene 409 filas,
pero **no guarda ningún desempeño**: ni un valor de presupuesto, ni una negociación, ni un
contrato, ni una póliza, ni una fecha real de recibo, fabricación, insumos o inicio. Lo que hay es
la plantilla que el v1 generaba al crear el plan de una obra (un renglón por paquete y tipo), y en
una sola obra —la que se llama «Prueba»— alguien alcanzó a planear fechas y a marcar dos pasos
como hechos, el mismo día.

Eso cambia el frente: **no hay nada que calibrar con historia, y el retiro deja de ser un rescate
para volverse una limpieza**. El valor de esta investigación está en haberlo medido antes de
construir un `ForecastService` de compras sobre un fantasma (trampa 4 del método: el dato histórico
puede no ser confiable — aquí, directamente no existe).

## Lo medido (trampa 4, antes de cualquier promedio)

Consulta de solo lectura sobre producción, 2026-08-21, con `SET SESSION TRANSACTION READ ONLY`.
Agregados, sin volcado.

| Obra (`project_id`) | Filas | Con fecha planeada | Con alguna fecha **real** | Proveedor | Nº contrato | Valor (ppto / neg. / adjudicado) |
|---|---|---|---|---|---|---|
| Prueba (27) | 136 | 126 | **4** (2 cuadros, 2 legalización) | 2 | 0 | 0 / 0 / 0 |
| Optimización Aeropuerto JMC (68) | 162 | **0** | 0 | 0 | 0 | 0 / 0 / 0 |
| Milán Campestre Torre 19 (74) | 111 | **0** | 0 | 0 | 0 | 0 / 0 / 0 |
| **Total** | **409** | 126 | 4 | 2 | 0 | 0 |

Tres señales de que es esqueleto y no historia:

1. **Las seis columnas de valor están en `NULL` en las 409 filas** (`COUNT(valor*) = 0`). Igual
   `numeroContrato`, `fechaVencimientoPolizas` y `observacionesContrato`. La pregunta 2 del encargo
   (desvío de precio) y la 3 (cumplimiento por proveedor) **no tienen con qué responderse**.
2. **JMC y Milán son un producto cartesiano.** 47/47/47 y 32/32/32 filas por tipo de paquete
   (Suministro, Mano de Obra, Suministro e Instalación) más las órdenes de compra: es la rejilla que
   el v1 armaba al crear el plan, con `paqueteContratacion` en solo 4 valores distintos por obra.
   Nadie la tocó después.
3. **Las 4 fechas reales de «Prueba» valen todas `2026-05-12`**, están en pares duplicados
   (consecutivos 10/76 y 18/84, mismo paquete, distinto `titulo`) y las fechas de inicio planeadas
   van de mayo de 2026 a enero de 2028 — futuro. Es una prueba de la pantalla, no una obra.

Tablas hermanas que revisé por si la señal estaba en otra parte:

| Tabla | Filas | Qué es | Desempeño |
|---|---|---|---|
| `general_informe_pdc` | 252 | Corte semanal del v1 (mismo esquema + nombre y NIT del proveedor) | Las mismas 126 filas de «Prueba» copiadas dos veces (bajo `project_id` 27 y 73, ambas rotuladas «Da Porto»), un solo `fechaHoy` (2026-08-12), mismas 4 fechas reales, cero valores |
| `bi_pdc_general` | 126 | Vista BI del v1 con `dias_delta_*` | Derivada de lo anterior |
| `backup_licify_general_informe_pdc_20260612` | 30 | Fechas de ingreso a Licify | Otra cosa; no es desempeño de contratación |
| `papelera_pdc` | 0 | Borrados del v1 | Vacía |
| `contratos_trazabilidad` | 9 | Auditoría de ediciones de `/contratos` | 9 eventos, no historia de compras |

Conclusión de la trampa 4: **cobertura de fechas reales = 4 de 409 (1 %), todas de prueba.
Cobertura de valores = 0 %.** No hay base para una media, una mediana ni una dispersión. Publicar
cualquier «duración real por paso» desde aquí sería inventarla.

## Las seis preguntas del encargo, contestadas con lo que hay

1. **Cuánto tarda cada paso de verdad.** No se sabe. Lo único que existe son los días *planeados*
   del catálogo `general_dias_procesos_contratacion` (216 filas, de la empresa), que el v2 sigue
   usando para restar fechas hacia atrás: en promedio 119 días Suministro e Instalación, 100
   Suministro, 96 Orden de Compra, 78 Mano de Obra, de pliegos a insumos en obra. Es optimismo
   declarado, no medición — y es lo que hay que calibrar cuando exista con qué.
2. **Cuánto se desvía el precio.** Sin dato. Cero valores en cualquiera de las tres columnas.
3. **Qué proveedores cumplen.** Sin dato. Dos `idProveedorAdjudicado` (ambos `1`, en «Prueba»).
   `bi_cic_contratistas` (340 filas) y `subcontratistas` (101) siguen siendo las únicas fuentes de
   proveedores, y no tienen fecha de cumplimiento de contratación con qué cruzar.
4. **Confiabilidad.** Medida arriba. El dato no es «poco confiable»: es inexistente.
5. **Qué se pierde si se retira sin extraer.** En desempeño, nada. Lo que sí merece conservarse:
   - **El esquema** de `pdc` / `general_informe_pdc`: es la definición de campos de un contrato
     completo (valor presupuesto → primera negociación → adjudicado → anticipo → reclamado →
     devoluciones; pólizas; nº de contrato) que el v2 todavía no captura. Es una lista de requisitos
     gratis, y se conserva con un `SHOW CREATE TABLE` en el archivo histórico, no con datos.
   - **Los 66 nombres de paquete** planeados en «Prueba» y sus fechas planeadas, solo si alguien
     los reconoce como el borrador real de una obra (la copia en `general_informe_pdc` bajo el
     `project_id` 73 de Da Porto sugiere que pudo serlo). Cabe en un CSV de 126 líneas.
   - **El catálogo de duraciones** no se pierde: vive en otra tabla y el v2 lo usa.
6. **Si vale alimentar el pronóstico con esto.** **No.** No hay con qué. La recomendación es al
   revés: que el plan nuevo empiece a producir, desde el primer paso que se cierre, la memoria que
   el viejo nunca produjo (siguiente sección).

## Subir la cadena causal: dónde está de verdad la señal

El método pide subir desde la métrica ex post («cuánto se atrasó la contratación») hasta el último
punto donde alguien puede actuar. En compras esa cadena es:

`paso vencido hoy` ← `paso que vence esta semana con proveedor sin confirmar` ← `duración
planeada del paso más corta que la real histórica` ← **`fecha real de cierre de cada paso`**.

El último eslabón es la captura, y es exactamente lo que el v1 no consiguió: tenía la columna y
nadie la llenó (trampa 3: el cuello de botella es la captura, no la pantalla). El v2 ya tiene
`fecha_real` por paso en seguimiento (`SeguimientoService`, `pdc_plan_paso`) pero hoy está en cero
en todas las obras (D63). **La memoria de desempeño de compras de AIA empieza el día en que un
residente marque el primer paso como cerrado en el v2.** Hasta entonces la hoja 8.6 debe decir
«sin historia», no pintar una duración.

Test de la decisión sobre lo que propongo conservar en el lienzo: ninguna cifra de este historial
lo pasa — si los 409 cambiaran mañana, nadie haría nada antes del almuerzo. Por eso nada de aquí
va a la Torre; va al archivo y a la spec como supuesto corregido.

## Preguntas que le habría hecho a Felipe (sesión autónoma: van con el supuesto tomado)

1. *¿«Prueba» (27) fue alguna vez una obra real o siempre fue sandbox?* — Supuse sandbox, por el
   nombre y por las fechas en 2027-2028. Si fue el borrador de Da Porto, los 126 paquetes
   planeados valen como punto de partida del plan v2 de esa obra, y eso sube la prioridad del CSV.
2. *¿Las obras JMC y Milán tuvieron plan de compras en otro sitio (Excel, Licify)?* — Supuse que
   sí, porque en la base no hay nada. Si ese Excel existe, **ahí** está el historial, no aquí.
3. *¿El retiro incluye las tablas hermanas (`general_informe_pdc`, `bi_pdc_general`, `papelera_pdc`,
   `backup_licify_*`)?* — Supuse que sí: son el mismo esqueleto repetido y `bi_pdc_general` puede
   seguir alimentando un informe Power BI con ceros (verificar antes del retiro; F5 jubila ese
   informe).

## Ajustes propuestos a la spec

| Sección | Cambio concreto | Por qué |
|---|---|---|
| 13, «El frente aparte: el historial del PDC v1 (D64)» | Cambiar «409 planes de compras completos» por «409 filas de plantilla: 126 con fechas planeadas en la obra de prueba, 4 con fecha real, 0 con valor o contrato». Quitar «es el único historial de desempeño de compras que existe» | La frase actual es falsa medida contra producción y justificaría construir un rescate que no tiene nada que rescatar |
| 13, F6 | Rebautizar «rescate del historial del PDC v1» como **«retiro de las tablas del PDC v1»** y sacarlo de «Diferidos»: es higiene (F0) o parte de F5, con su gate de borrado intacto (visto, respaldo, archivo legible fuera, restauración). El archivo histórico se reduce a `SHOW CREATE TABLE` de las cinco tablas + CSV de las 126 filas planeadas de «Prueba» | Sin datos que proteger, mantener 409 filas muertas en producción solo cuesta confusión (ya costó esta investigación y una decisión tomada sobre un número inflado). El gate se mantiene porque borrar es borrar |
| 13, F6 | Añadir al retiro las hermanas `general_informe_pdc`, `bi_pdc_general`, `papelera_pdc` y `backup_licify_general_informe_pdc_20260612`, previa comprobación de que ningún informe Power BI vivo las lee | Son el mismo esqueleto repetido; dejarlas vivas reintroduce el mismo espejismo |
| 8.6 Plan de Compras | Añadir al supuesto D63: «**No existe historia de duraciones reales en AIA**; el pronóstico de vencimiento usa el catálogo de la empresa y debe rotularse como *planeado*, no *estimado*». Añadir a la hoja un contador «pasos cerrados con fecha real / pasos planeados» como contrapeso | Sin eso, la escala «vence en 2, 3, 6 semanas» se lee como predicción calibrada y es optimismo de catálogo. El contador es el que empieza a construir la memoria que faltó, y cumple D36 (contrapeso al lado) |
| 6, catálogo de métricas | Declarar una métrica `compras.duracion_real_paso` por tipo de paquete con `completitud` explícita, fuente `pdc_plan_paso.fecha_real`, estado `descriptiva` hasta que haya ≥ 20 pasos cerrados por tipo | Que la calibración nazca dentro del catálogo ejecutable y no como otro cálculo aparte; el umbral evita publicar una mediana de tres datos |
| 16 Riesgos | Añadir: «El catálogo de duraciones (`general_dias_procesos_contratacion`) nunca ha sido contrastado con una duración real» | Es el riesgo de fondo de toda la hoja 8.6, y hoy no está escrito |
| 19 Errores de medición | Registrar este: «se contaron filas de `pdc` como planes completos sin mirar el llenado de las columnas» | La spec ya tiene esa sección para eso, y este es el ejemplo más limpio de la trampa 4 |

## Lo que NO haría

- **No construiría un `ForecastService` de compras ni un importador del v1.** No hay entrada.
- **No borraría nada en esta sesión ni sin el gate de D64.** Que el contenido sea plantilla no
  releva el visto, el respaldo ni la prueba de lectura del archivo.
- **No propondría capturar los valores de contrato en el v2 «porque el v1 tenía las columnas».**
  Primero pasar cada campo por el test de la decisión: quién actúa si el anticipo cambia. Eso es
  una conversación de producto, no una migración de esquema.

## Evidencia

Consultas ejecutadas por `ssh siteground-produccion-lastplanner`, cliente `mysql` con
`--init-command="SET SESSION TRANSACTION READ ONLY"`, credenciales leídas del `.env` del servidor
y nunca copiadas. Solo `SHOW` y `SELECT` con `COUNT`/`SUM`/`MIN`/`MAX`; ninguna fila bajó a disco.
Tablas tocadas en lectura: `pdc`, `general_informe_pdc`, `bi_pdc_general`, `papelera_pdc`,
`backup_licify_general_informe_pdc_20260612`, `contratos_trazabilidad`,
`general_dias_procesos_contratacion`, `general_proyectos_procesos`, conteos de `bi_cic_contratistas`
y `subcontratistas`.
