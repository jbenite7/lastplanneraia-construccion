---
capa: fuente
tipo: registro-de-decisiones
estado: en-curso
fecha: 2026-08-20
areas: [bi, rbac, design-system]
fuente: entrevista con Felipe bajo el método `antes-del-almuerzo`, 2026-08-20
resumen: "Cierre de decisiones del replanteo de la Control Tower: una fila por decisión, con su porqué"
project: lps-aia
---

# Decisiones del replanteo — Control Tower

> Se llena en vivo durante la entrevista. Cada decisión es de Felipe salvo donde diga lo contrario.
> Inventario de partida: [[2026-08-20-inventario-control-tower]] · Hallazgos:
> [[2026-08-20-replanteo-control-tower-notas]]

## Supuestos declarados

- **El paso 1 del método se cumple con Felipe como entrevistado.** El método pide de 3 a 5
  conversaciones con quienes usan el tablero; Felipe declaró «yo soy la obra». Queda escrito que la
  validación con residentes y directores de obra **no se hizo**, y que cualquier hallazgo que los
  contradiga manda sobre lo aquí decidido.
- **Goodhart y los contrapesos** son criterio propio: la literatura de tableros consultada
  (186 fuentes) no los respalda. Es lo primero que debe ceder si el caso lo contradice.

## Decisiones cerradas

| # | Decisión | Qué se decidió | Por qué |
|---|---|---|---|
| D1 | Qué está mal hoy | No cuenta una historia · no se confía en las cifras · se ve y se siente mal | Diagnóstico de Felipe. No marcó falta de uso: hoy el módulo está oculto del menú |
| D2 | Naturaleza de la desconfianza | No se sabe de dónde sale el número · cada quien lo calcula distinto · el dato llega tarde o incompleto | Los tres tienen arreglo distinto; el segundo es de arquitectura |
| D3 | Audiencias | Tres, con caminos distintos: gerencia · obra · socio o cliente | |
| D4 | Orden de ataque | Primero el cimiento: una sola definición por métrica | Sin cifras que se defiendan solas, la mejor historia se cae en la primera pregunta del comité |
| D5 | Naturaleza del cimiento | **La ley, no el papel**: la definición manda el cálculo | Un catálogo que solo describe puede mentir, y da confianza falsa |
| D6 | Dato incompleto | Se muestra la cifra **declarando de qué se está parando** («8 de 12 obras, corte al viernes») | Ni ocultar ni engañar: que quien decide sepa cuánto pesa lo que ve |
| D7 | Punto de partida del inventario | El informe Power BI que la gerencia ya usa, no la lista de 19 del código | Lo que existe manda sobre lo que se documentó |
| D8 | **Quién manda** | **La Torre de Control. Power BI se jubila** | La Torre ya cubre más: multi-obra, curva S propia, plan de compras, pronóstico, riesgo y recomendación. Dos motores para la misma cifra es el origen de D2 |
| D9 | Indicador principal | Jerarquía: **1º restricciones del lookahead sin liberar · 2º curva S · 3º PAC** | Solo el primero se acciona el mismo día. Los otros dos entran, pero subordinados |
| D10 | Métricas ex post | No bajan a encabezado seco: se cuentan como **data storytelling** | Decisión de Felipe: el qué pasó debe llevar su porqué y su qué hacer, no quedarse en cifra |
| D11 | Alcance de hojas | **Las 8 hojas actuales son todas obligatorias.** Ninguna se apaga | Decisión de Felipe |
| D12 | Método de cierre | Entrevista elemento por elemento: cada hoja, cada gráfico, cada métrica, cada filtro, cada período | Decisión de Felipe |

| D13 | Semana contra rango de fechas | **El rango manda; la semana es un atajo** que rellena el rango | Un solo motor de período por debajo, dos formas de pedirlo. Hoy el rango anula la semana sin avisar |
| D14 | Período por defecto | **La semana en curso** | Es la semana sobre la que todavía se puede actuar, coherente con D9 |
| D15 | Obras por defecto | **Depende del cargo**: gerencia abre en todas las suyas, el director en la suya | Una pantalla que se comporta según quién entra, sin pasos extra diarios |
| D16 | Filtros de subcontratista, responsable y etapa | **Bajan al desglose.** La barra principal queda con obra y período | Filtrar por responsable es pregunta de investigación, no de primer vistazo. Evita la pantalla mitad-filtrada del informe actual |

### Hoja 1 · Resumen Ejecutivo

| # | Decisión | Qué se decidió | Por qué |
|---|---|---|---|
| D17 | La decisión que habilita | **Gerencia decide en qué obra meterse esta semana**; la acción es llamar hoy a ese director | Es la única hoja que aprovecha el multi-obra |
| D18 | Gráficas | **Se reemplazan** las dos actuales (PAC contra programado, PPC semanal) por un **panorama de obras**: una fila por obra con su señal de restricciones, su desviación y su tendencia | Permite comparar y decidir dónde intervenir. Las actuales repiten lo que ya está en Semanal |
| D19 | Narrativa | **Es el centro de la hoja.** Titular que afirma qué pasó y por qué; debajo, las acciones. Las gráficas sustentan el texto | Cumple D10; y la maquinaria (`StorytellingService`, `RiskScoringService`, `ActionRecommendationService`) ya existe |
| D20 | Acciones recomendadas | **Cada una con nombre y fecha** | El método lo exige: sin dueño nombrado, una recomendación es una sugerencia que nadie recoge. Los datos ya traen responsable AIA y subcontratista |

### Hoja 2 · Programa General

| # | Decisión | Qué se decidió | Por qué |
|---|---|---|---|
| D21 | La decisión que habilita | Tres a la vez: **el director reordena la ventana de 6 semanas**, **gerencia y dirección evalúan el riesgo de la fecha de entrega**, y **valor ganado** | Decisión de Felipe |
| D22 | Radar de tres ejes | **Se queda, mejor dibujado**: escala corregida y más grande | La gerencia ya lo reconoce y ver la figura completa tiene valor de símbolo. Se rechazó reemplazarlo por barras |
| D23 | Pronóstico P50 | **Es el titular de la hoja**, siempre con su margen de incertidumbre | Es lo único de esta hoja sobre lo que todavía se puede actuar |
| D24 | El «88 días» | **Con palabras, no con signo** («88 días de retraso»), **y además la fecha proyectada de terminación** | El signo y el color solos son ambiguos; la fecha es lo concreto para hablar con un cliente |
| D25 | What-if / escenarios | **Análisis de riesgos ahora, sin simulación** + **what-if acotado a restricciones después del cimiento** («¿cuántos días recupero si libero estas tres?») | Cuestionado y ajustado: simular sobre cifras que no se creen multiplica el error, y un simulador general no pasa el test de la decisión |
| D26 | Cálculo del riesgo | **Combinado**: restricciones sin liberar y su antigüedad, ponderadas por si caen en ruta crítica | Lo más correcto; se acepta que es lo más difícil de explicar |
| D27 | Valor ganado | **Solo la mitad que el dato sostiene**: desempeño de cronograma en plata, con el presupuesto y APU que ya existen. **La pantalla declara que no incluye costo real** | Verificado el 2026-08-20: hay `pdc_presupuesto_items`, APU, versiones con sobrecostos y ahorros, y avance físico. **No existe ninguna tabla de costo causado, facturado o pagado**, así que no hay índice de costo. Conectar contabilidad es un frente propio, no una hoja |

### Hoja 3 · Programación Intermedia — donde vive el indicador principal

| # | Decisión | Qué se decidió | Por qué |
|---|---|---|---|
| D28 | Qué muestra primero | **Data storytelling**: la hoja abre con el titular que afirma qué está pasando y por qué, y debajo la lista de restricciones por liberar ordenada por urgencia (nombre, actividad que bloquea, responsable, fecha comprometida, días de vencida) | Interpretación de la respuesta «data storytelling» sobre el indicador principal de D9. **Confirmar con Felipe si la lista debe ir arriba del titular** |
| D29 | Qué significa «sin gestionar» | **Nadie las ha tocado: no tienen responsable ni fecha.** 318 restricciones, el 31,55% | Dato de Felipe. Es trabajo que nadie ha recogido, no un problema de visualización |
| D30 | Las huérfanas | **Alarma propia, arriba de todo**: «318 restricciones sin dueño», con la acción de asignarlas | Es el trabajo más barato y de mayor efecto disponible |
| D31 | Quién registra | **Cada responsable registra la suya** | Dato de Felipe. Activa la trampa 2 del método: quien digita queda expuesto por el dato |
| D32 | Sesgo de captura | **Se declara en la pantalla y se pone contrapeso**: junto al ranking de causas, quién lo registró y cuántas quedaron sin causa | No corrige el sesgo, pero impide leer el ranking como verdad. **Criterio propio, sin respaldo en la literatura consultada** |
| D33 | **Leer o actuar** | **La Torre deja asignar responsable y fecha a una restricción desde la propia pantalla** | Cierra el ciclo entre ver y hacer. **Consecuencia grande: la Torre pasa de solo lectura a escribir en la base**, con todo lo que eso implica en permisos, CSRF, auditoría y pruebas |
| D34 | Pareto de restricciones | **Se queda, como contexto debajo de la lista** | Sirve para atacar de raíz (si el 40% es materiales, el problema es compras), pero no es la acción del día |

### Hoja 4 · Programación Semanal

| # | Decisión | Qué se decidió | Por qué |
|---|---|---|---|
| D35 | La decisión que habilita | Las tres, con una principal: **el director prepara el comité del lunes** (principal) · el residente revisa sus compromisos a diario · gerencia compara entre obras | Decisión de Felipe. La hoja se diseña para proyectarse, y admite filtro por persona |
| D36 | Contrapeso al PAC | **El conteo de compromisos al lado, siempre, y además la variación contra la semana anterior** | El PAC sube si el equipo se compromete a menos. La variación destapa el encogimiento gradual. **Criterio propio, sin respaldo en la literatura consultada** |
| D37 | Forma del PAC | **«17 de 20 compromisos» como cifra principal**, el porcentaje al lado, sin decimales de más | El compromiso es binario, uno o cero. El conteo deja de ser contrapeso escondido y pasa a ser la forma normal de decirlo |
| D38 | **`ps_pac_expected` está mal bautizada** | **Se rebautiza «Riesgo de incumplimiento» y se vuelve protagonista de la hoja** | Objeción de Felipe, verificada: su fórmula real es 25% histórico del contratista + 20% del responsable + 15% criticidad + 20% restricciones + 10% avance + 10% CNC. **No estima el PAC de la semana: estima, compromiso por compromiso, cuál se va a caer.** El PAC dice el viernes que falló; esto dice el martes cuáles van a fallar. El catálogo la marca `planned_for_programacion_semanal` — calculada y nunca integrada. No proyecta con menos de 3 observaciones históricas |
| D39 | Causas de no cumplimiento | **Viven aquí**; en Programa General queda solo el titular | La causa es de la semana que se cerró. Elimina la duplicación actual entre las dos hojas |

### Hoja 5 · Curva S

| # | Decisión | Qué se decidió | Por qué |
|---|---|---|---|
| D40 | La decisión que habilita | **Rendición hacia arriba y hacia afuera** (gerencia y cliente) **y disparar la replanificación** cuando la brecha pasa un umbral | Decisión de Felipe. No compite con las hojas de acción diaria: es la conversación de contrato, más el gatillo de rehacer el programa |
| D41 | Proyección | **Con proyección y su margen de incertidumbre**, hasta fecha probable de entrega | Misma matemática del P50 que ya es titular en Programa General (D23) |

### Hoja 6 · Plan de Compras

| # | Decisión | Qué se decidió | Por qué |
|---|---|---|---|
| D42 | La decisión que habilita | Las tres: **compras destraba el paso vencido de hoy** · gerencia vigila cobertura del presupuesto · el director anticipa qué le va a faltar | Decisión de Felipe. El primero es el gemelo del log de restricciones y se acciona igual de rápido |
| D43 | Escala de vencimiento | **Se conserva** (ya vencido, esta semana, en 2, en 3, en 6 semanas, sin fecha) **y «sin fecha» se trata como alarma** | Igual que las restricciones huérfanas de D30: un paso sin fecha no es holgado, es un paso que nadie programó |

### Hoja 7 · Proveedores

| # | Decisión | Qué se decidió | Por qué |
|---|---|---|---|
| D44 | Calificación integral incompleta | **No se publica el integral hasta que esté completo.** Se muestran por separado los componentes con dato; el integral aparece en gris declarando qué le falta | Aplica D6 al caso más grave: hoy la etiqueta promete cinco componentes (PAC 30%, Calidad 20%, Social-Ambiental 20%, SST 20%, Administración 10%) y solo PAC tiene datos |
| D45 | La decisión que habilita | Las tres: **a quién no volver a contratar** · **a quién apretar esta semana** · insumo del comité de compras | Decisión de Felipe |

### Hoja 8 · Responsables

| # | Decisión | Qué se decidió | Por qué |
|---|---|---|---|
| D46 | Quién ve qué | **Cada quien la suya; el jefe ve su equipo** | Evita el muro público de la vergüenza, que hace que la gente se comprometa a menos o registre causas cómodas. Conserva la señal sin el castigo social |
| D47 | Propósito de la alerta | **Ver quién necesita ayuda, no quién falla**, y decirlo explícitamente en la pantalla | El responsable con cumplimiento bajo casi siempre tiene más restricciones abiertas o más carga. La acción es descargarlo o destrabarlo. Usarla para evaluación de desempeño garantiza que la captura se envenene (trampa 2 del método) |

### Decisiones que atraviesan todo

| # | Decisión | Qué se decidió | Por qué |
|---|---|---|---|
| D48 | Trazabilidad | **Detrás de cada cifra, a un clic**: definición, fórmula, fuente y corte | Es la queja número uno de D2, y el dato ya viaja hasta el navegador y se bota. Falta pintarlo |
| D49 | Catálogo de causas | **Se depura antes de pintar nada**: lista cerrada, sin duplicados, con nombres que quepan | Sin esto cualquier pareto suma mal. Hoy «Actividad predecesora incompleta / n…» aparece dos veces |
| D50 | Distribución | **Correo automático desde el arranque** | Decisión de Felipe **contra la recomendación del método**, que pide mandarlo a mano unas semanas para ver si alguien responde. Lo defiende que el sistema ya manda correo, así que es barato |
| D51 | Criterio de muerte a 90 días | **Que las restricciones sin dueño no bajen de 318.** Si a los 90 días siguen ahí, la Torre no cambió el comportamiento: se rehace o se apaga | Medible sin instrumentar nada. Evita la trampa de medir aperturas cuando además se empuja por correo |
| D52 | Audiencias | **Un lienzo propio por audiencia** | Es lo que manda el método (un lienzo, una audiencia). **Advertido: triplica construcción y mantenimiento con una sola persona detrás.** Con el cliente diferido (D53), arrancan dos |
| D53 | Vista de cliente o socio | **Se difiere** hasta que el cimiento esté puesto | Es la de mayor riesgo: lo que salga mal se ve fuera de AIA. Con la calificación de proveedores incompleta y 318 restricciones sin dueño, hoy no hay nada que mostrar afuera con orgullo |
| D54 | Salir del escondite | **Cuando el cimiento y la hoja de restricciones estén listos** | Una sola primera impresión. Prenderlo hoy confirmaría la desconfianza que se está curando |
| D55 | La deuda de Power BI | **Se reconstruye la hoja de Liberación de Restricciones en la Torre antes de apagar nada** | Es la única pieza que la Torre no cubre, y es donde vive el indicador principal de D9 |
| D56 | `/indicadores` | **También se jubila** | Una sola casa para consultar cifras. Amplía el alcance, y Felipe lo asumió |
| D57 | Orden de construcción | **Cimiento → restricciones → el resto.** Cada fase se publica antes de abrir la siguiente | Coherente con D4 y con el gate de cierre de frente del repositorio |
| D58 | Métricas nuevas al catálogo | Las cuatro: **restricciones por semanas para iniciar · restricciones sin dueño · actividades afectadas por restricción · desempeño de cronograma en plata** | Las tres primeras sostienen la hoja de restricciones; la cuarta es la mitad del valor ganado que el dato sostiene (D27). «Sin dueño» hoy no existe en ninguna de las dos herramientas |

## Decisiones técnicas tomadas por Claude (bajo umbral, auditables)

| # | Decisión | Por qué |
|---|---|---|
| T1 | `bi-spa.js` (4.199 líneas) se parte por hoja antes de tocar nada | Un archivo de ese tamaño hace que cada cambio duela y arriesga romper hojas ajenas. Es prerrequisito de todo lo demás |
| T2 | El catálogo pasa de descriptivo a ejecutable sin cambiar su forma de datos | D5 exige que la definición mande el cálculo; conservar la forma permite migrar métrica por métrica sin romper las pantallas |
| T3 | Escribir desde la Torre (D33) exige CSRF, comprobación de capacidad y registro de auditoría | La Torre pasa de solo lectura a escritura; sin esto se abre un hueco de seguridad |

## Lo que queda abierto

- **D28 por confirmar:** en la hoja de restricciones, si el titular narrativo va arriba de la lista
  o al revés.
- **La validación con obra no se hizo.** Felipe respondió como la obra; residentes y directores no
  fueron entrevistados. Cualquier hallazgo suyo manda sobre lo aquí decidido.
- **Conectar contabilidad** para la otra mitad del valor ganado: frente propio, sin decidir.
- **Interruptor por obra:** hoy el interruptor es global. Si alguna vez se quiere piloto por obra,
  hay que hacerlo por proyecto.
