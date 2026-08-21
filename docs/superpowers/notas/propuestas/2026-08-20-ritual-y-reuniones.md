---
capa: fuente
tipo: reporte
estado: abierto
fecha: 2026-08-20
areas: [bi, lps]
fuente: investigación de solo lectura sobre GLOSARIO, docs/flujos, memoria/flujos, docs/ESTADOS-PG-PI-PS y la base lastplanneraia_dev (semanas_activas), bajo el método antes-del-almuerzo
resumen: "En qué reunión real entra cada hoja de la Control Tower: la semana de cada obra no arranca el lunes, hay una sola reunión semanal evidenciada por obra, dos hojas no caben en ninguna reunión y el correo de D50 debe salir la víspera de esa reunión, obra por obra"
---

# El ritual y las reuniones de la Torre

Propuesta de ajuste a [[2026-08-20-replanteo-control-tower-design]]. No modifica la spec: la sesión
dueña decide. Método: [[2026-08-20-decisiones-control-tower|las 66 decisiones]] pasadas por la vara
de `antes-del-almuerzo` — una hoja vale si alguien toma una acción concreta antes del almuerzo, y
una hoja que no cabe en ninguna reunión con decisión y dueño es un hallazgo, no un fracaso.

## 0. Cómo se hizo y qué no se pudo hacer

- Solo lectura sobre el repositorio y sobre `lastplanneraia_dev`.
- **El grilleo a Felipe no ocurrió.** La sesión corrió sin nadie al otro lado, así que las
  preguntas que `brainstorming` manda hacer de a una quedan en la sección 7 con la respuesta que
  se asumió para poder avanzar. Cada supuesto está marcado; si Felipe responde distinto, esa
  respuesta manda sobre este documento, igual que el supuesto 1 de la spec dice de la obra.

## 1. Las reuniones que el sistema y el dominio evidencian

No se inventó ninguna. Cada fila dice de dónde sale la evidencia y qué tan fuerte es.

| Reunión | Quién | Evidencia | Fuerza |
|---|---|---|---|
| **R1 · Reunión semanal de obra** («Actualización Semanal») | Director, residente, subcontratistas | `GLOSARIO.md:70` la define: «ritual de cierre de semana donde se reportan avances, se analizan CNC y se planifica la semana siguiente». `docs/CUSTOMER.md:83` la nombra como la alternativa que compite con la app. Y la base la confirma: de 116 semanas con `fechaCierreCompromisos`, el cierre cae **el mismo día del arranque de semana (21)** o **6 a 8 días después (26)** — se cierra la semana anterior y se abre la siguiente en una sola sentada | **Fuerte**: glosario + dato |
| **R2 · Reunión diaria de obra** («comité diario») | Residente con cuadrillas y subcontratistas | `docs/last-planner-programacion-intermedia-estados.md:34` («escalamiento inmediato en reunión diaria») y `docs/last-planner-programacion-semanal-estados.md:41` («escalar en comité diario, asignar responsable y fecha de destrabe») | **Media**: la nombran los contratos de estado, ningún dato la registra |
| **R3 · Comité de compras** | Compras, director, gerencia | D45 la declara insumo; `docs/pdc-v2.md:363` registra una «Ola 1 del comité» del 2026-07-29 con Da Porto y `docs/qa/brief-estandar-minimo-agrupaciones-correctas.md:5` una «reunión Plan de Compras Da Porto» transcrita | **Media**: existe al menos para Da Porto; no se sabe si es periódica |
| **R4 · Revisión de gerencia** (portafolio) | Gerencia con directores | Solo indirecta: el informe Power BI de cuatro páginas que la gerencia usa (spec §1) y D17 («en qué obra meterse esta semana») | **Débil**: nadie la documenta como reunión; puede ser lectura individual |
| **R5 · Rendición al cliente** (comité de obra con cliente) | Director, gerencia, cliente | Solo D40 («rendición hacia arriba y hacia afuera») y D53 (vista de cliente diferida) | **Débil**: se asume por el dominio, no por el repo |

**Qué no hay:** ninguna reunión «de la Torre». Bien — un tablero que necesita reunión propia es un
tablero que nadie abre.

### 1.1 El hallazgo que cambia la spec: la semana no arranca el lunes

Medido en `semanas_activas` (155 semanas, 37 obras incluidas las de prueba):

| Día en que arranca la semana | Semanas |
|---|---|
| Viernes | 38 |
| Martes | 36 |
| Lunes | 34 |
| Jueves | 28 |
| Miércoles | 19 |

Y **cada obra tiene su día ancla**: la obra 62 arranca jueves (17 de 28), la 63 y la 65 viernes
(10 de 17 y 23 de 26), la 70 martes (22 de 22), la 68 miércoles (9 de 11). Los lunes son casi todos
de los proyectos sembrados de prueba (ids ≥ 990100).

Consecuencias:

- **D35 dice «el director prepara el comité del lunes». No hay comité del lunes.** Hay una reunión
  semanal por obra, cada una en su día. El lunes aparece en `CUSTOMER.md` y `POSITIONING.md` como
  figura retórica («que nada te frene el lunes»), no como dato.
- **El día de cierre de compromisos cae viernes (34) o jueves (31)** con más frecuencia, coherente
  con obras que arrancan semana jueves o viernes.
- Todo lo que la Torre empuje «antes de la reunión» tiene que calcularse **por obra, contra el
  día ancla de esa obra**, no contra un día fijo de la semana. Un correo de los lunes llega cuatro
  días tarde a la obra 62 y tres días tarde a la 65.

## 2. Qué hoja entra en cada reunión y qué se proyecta

Cada hoja pasada por el test: si la cifra cambió anoche, ¿qué hace alguien con nombre antes del
almuerzo, y en qué reunión lo decide?

| Hoja (spec §8) | Reunión | Decisión antes del almuerzo | Dueño | Veredicto |
|---|---|---|---|---|
| **8.3 Intermedia** | **R1, segunda mitad** (planificar la semana que entra) y **R2** para lo vencido | Asignar dueño y fecha a las restricciones huérfanas de las próximas 3 semanas; liberar hoy la vencida que bloquea un compromiso | Director asigna, residente ejecuta | **Pasa.** Es la hoja del ritual. Se proyecta la alarma de huérfanas y la lista por urgencia; D33 se usa **en vivo, en la reunión** |
| **8.4 Semanal** | **R1, primera mitad** (cerrar la semana que termina) | Registrar causa de cada incumplida, reasignar lo que no se hizo, decidir si el compromiso de la siguiente encoge (contrapeso D36) | Residente registra, director decide | **Pasa.** Se proyecta «17 de 20» con su variación y las causas con atribución completa |
| **8.4 Semanal · Riesgo de incumplimiento** (D38) | **R2**, a diario | Reforzar hoy el compromiso que va a caerse (cuadrilla, material, decisión pendiente) | Residente | **Pasa, pero en otra reunión que el resto de la hoja.** Es una señal diaria dentro de una hoja semanal; debe ser la primera cosa que se ve entre reuniones semanales |
| **8.1 Resumen Ejecutivo** | **R4**, si existe; si no, correo | Llamar hoy al director de la obra en rojo | Gerente | **Pasa solo si R4 existe o si llega empujada.** La acción («llamar») no necesita reunión; necesita llegar al gerente el día en que las obras cerraron. Ver §3 |
| **8.6 Plan de Compras** | **R3** y **R1** (el director anticipa qué le va a faltar) | Destrabar hoy el paso de contratación vencido | Compras | **Pasa en R3.** Hoy nace vacía (D63): no se proyecta en ninguna parte hasta que haya calendario |
| **8.7 Proveedores** | **R3**, al contratar | A quién no volver a contratar | Comité de compras | **Pasa a medias.** «A quién no contratar» es decisión por evento (cuando se abre un paquete), no semanal. «A quién apretar esta semana» ya lo responde Semanal por subcontratista: es la misma causa de no cumplimiento vista por proveedor. Lugar: insumo de R3, desglose desde Semanal |
| **8.2 Programa General** | **R4 / R5, mensual**; el riesgo combinado (D26) alimenta a R1 | Reordenar la ventana de seis semanas; decidir si se replanifica | Director propone, gerencia decide | **No pasa el test semanal.** El P50 de terminación no cambia de la noche a la mañana ni obliga a nada antes del almuerzo. Su ritmo es mensual o por hito. Lo único semanal de esta hoja es el riesgo por restricciones en ruta crítica, que ya vive en 8.3 |
| **8.5 Curva S** | **R5** (y R4), mensual | Convocar la replanificación cuando la brecha pasa el umbral (abierto §18.2) | Director y gerencia | **No pasa el test semanal; pasa el mensual.** Es la hoja de contrato. Se proyecta ante cliente, no en obra |
| **8.8 Responsables** | **Ninguna** | Descargar o destrabar a quien va mal | Jefe directo | **No cabe en ninguna reunión, y es correcto.** D46 («nunca todos ven a todos») y D47 («ayuda, no reconvención») la hacen imposible de proyectar: proyectarla es la reconvención pública que D47 prohíbe. Su lugar es el 1:1 y el correo personal, no el lienzo |

Resumen en una frase: **la Torre tiene una reunión de verdad (R1) y en ella caben dos hojas (8.3 y
8.4). Las demás son mensuales, por evento, personales o empujadas.** Eso no reduce el alcance de
D11 — las ocho siguen existiendo — pero sí dice cuáles se proyectan y cuáles se consultan.

## 3. Antes y después: quién prepara, quién registra, qué empuja la Torre

### 3.1 La víspera (día ancla − 1, por obra)

| Quién | Qué | Con qué hoja |
|---|---|---|
| Residente | Deja registrado PAC y causa de la semana que cierra (la captura ya está sana: 92,3 % y 89,4 %, D60) | Programación Semanal del módulo LPS, no la Torre |
| Torre → director y residente | **Correo de víspera** (ver 3.4) | Intermedia + Semanal |
| Director | Lee el correo, llega con las huérfanas ya vistas | — |

### 3.2 En la reunión (R1)

1. **Se proyecta Semanal**: «17 de 20», variación contra la semana anterior, causas con atribución.
   Se registra en el módulo LPS lo que falte (causa, calificación).
2. **Se proyecta Intermedia**: alarma de huérfanas primero. **Cada huérfana sale de la reunión con
   dueño y fecha, escritos en vivo desde la Torre (D33).** Esto responde el abierto §18.3 por el
   lado del ritual: **la lista va arriba del titular**, porque en la reunión se trabaja la lista; el
   titular es para quien llega sin contexto (el correo, la gerencia).
3. Se confirma la semana que entra en el módulo LPS (`Semanal_Confirmada`), como hoy.

Quien registra: **el residente** en el módulo LPS, **el director** las asignaciones en la Torre.
El registro de la Torre (`AsignadoPor`, `AsignadoEn`) deja fecha, y esa fecha es la que mide §3.5.

### 3.3 Después

- **A diario (R2):** el residente mira Riesgo de incumplimiento y las restricciones vencidas de su
  obra. Esto es consulta, no proyección; cabe en el móvil (ya hay tarjetas bajo 1180 px).
- **Día ancla + 1, hacia gerencia:** Resumen Ejecutivo actualizado con la obra que acaba de cerrar.
  Como las obras cierran en días distintos, **el panorama de obras de gerencia nunca está «todo al
  día» el mismo día** — cada fila debe decir de qué fecha es su corte (es exactamente el `basis` de
  §6.2 aplicado por fila).

### 3.4 El correo de D50, aterrizado

D50 se tomó contra el método; el método, entonces, pide al menos medir la respuesta. Propuesta:

| Campo | Propuesta |
|---|---|
| Cuándo | **La víspera del día ancla de cada obra**, una vez por obra. No un día fijo para todas |
| A quién | Director y residente de esa obra. Nadie más en la primera versión |
| Qué lleva | Tres líneas y nada más: restricciones huérfanas de las próximas 3 semanas (conteo y las 5 más urgentes con la actividad que bloquean) · restricciones vencidas con dueño (dueño y días) · compromisos de la semana que cierra sin causa registrada. **Cada línea con enlace directo a la hoja y la acción** |
| Qué no lleva | PAC, gráficas, titulares narrativos, comparaciones con otras obras. Nada que no pida una acción antes de la reunión |
| Gerencia | Un correo aparte, **semanal en día fijo** (propuesta: el día siguiente al último ancla de la semana), con el panorama de obras y la fecha de corte de cada fila |

**Dos correcciones de hecho sobre «el sistema ya manda correo» (D50):** el único correo que hoy
sale es el de recuperación de contraseña (`SmtpMailer` solo lo usa `PasswordResetService`); las
alertas de restricciones (`NotificationType`: bajó de nivel, liberada, responsable asignado) son
**campana dentro de la app**, no correo. La tubería SMTP/sendmail existe; **lo que no existe es
nada que se dispare solo por calendario**. El correo de víspera necesita un disparador programado
(cron del hosting o equivalente), y eso es una pieza nueva que F4 debe nombrar.

### 3.5 Cómo se mide la respuesta (no las aperturas)

La medida que el método pide y que D51 ya insinúa, hecha operativa:

> **De las restricciones huérfanas listadas en el correo de víspera, qué porcentaje tiene dueño y
> fecha 48 horas después** (es decir, al cierre de la reunión).

Se calcula con `AsignadoEn` contra la hora de envío, sin instrumentar nada más. Si a las cuatro
semanas ese porcentaje no despega, el correo no mueve a nadie y se apaga (riesgo «el correo no lo
lee nadie», spec §16). Es compatible con el criterio de muerte de §15 y lo adelanta: §15 mira a 90
días; esto avisa a los 28.

## 4. Dónde se rompe

1. **Programa General (8.2) y Curva S (8.5) no tienen reunión semanal.** No producen decisiones
   antes del almuerzo; producen decisiones mensuales o por hito. No deben competir en el lienzo con
   las hojas de R1. La spec ya las trata con respeto (titular P50, banda de incertidumbre); lo que
   falta es decir que **su ritmo es otro** y que no se evalúan con el criterio de muerte de las
   restricciones.
2. **Responsables (8.8) no cabe en ninguna reunión, por diseño.** Dicho con el método en la mano:
   es una hoja de consulta personal, no de proyección. Su distribución es push al jefe directo, no
   lienzo. Debe declararlo para que nadie la proyecte en R1 «porque está ahí».
3. **Resumen Ejecutivo (8.1) depende de una reunión que no está evidenciada (R4).** Si R4 no
   existe, la hoja solo produce la llamada del gerente si le llega empujada. Hay que confirmar R4
   con Felipe; si no existe, 8.1 es un correo con pantalla detrás, no una pantalla con correo.
4. **«Comité del lunes» (D35) no existe como tal.** Corrige la premisa de la hoja Semanal y del
   correo: la semana es por obra y el día es el ancla de cada una.
5. **Plan de Compras (8.6) y Proveedores (8.7) dependen de R3**, que solo está evidenciada en Da
   Porto y sin periodicidad conocida. Mientras no haya calendario de compras (D63) ni periodicidad
   del comité, estas dos hojas no tienen reunión donde proyectarse.

## 5. Criterio de muerte por hoja, según su reunión

| Hoja | A los 90 días se mantiene si… | Si no… |
|---|---|---|
| 8.3 | El % sin análisis baja (§15) y las huérfanas del correo ganan dueño en 48 h | Se rehace o se apaga (ya en spec) |
| 8.4 | Se proyecta en R1 (preguntar, no instrumentar: con ~10 obras, preguntar es más barato) | Problema de distribución, no de diseño |
| 8.1 | El gerente hizo al menos una llamada que atribuye al correo o a la hoja | Bajar a correo puro |
| 8.2, 8.5 | Se usaron en al menos una revisión mensual o rendición | Desglose, no lienzo |
| 8.6, 8.7 | R3 existe con periodicidad y el calendario de compras está cargado | Nacen vacías y lo dicen (ya en spec) |
| 8.8 | Un jefe la abrió para un 1:1 | Se retira del menú y queda como correo personal |

## 6. Ajustes propuestos a la spec

| Sección | Cambio concreto (texto propuesto) | Por qué |
|---|---|---|
| **§4 Supuestos** | Añadir **6.** «**El calendario de reuniones no se confirmó con la obra.** Se asume una reunión semanal por obra en su día ancla (R1), una diaria (R2) y un comité de compras (R3) al menos en Da Porto. La revisión de gerencia (R4) y la rendición al cliente (R5) están supuestas, no evidenciadas. Ver [[2026-08-20-ritual-y-reuniones]].» | El método diseña desde la decisión y la decisión se toma en una reunión; si la reunión es supuesta, debe decirlo donde se dicen los demás supuestos |
| **§8, encabezado** | Añadir, antes de 8.1, una tabla **«8.0 El ritual»**: reunión → hoja que se proyecta → acción → quién registra, con las filas de la sección 2 de este documento, y la frase: «**Solo 8.3 y 8.4 se proyectan en la reunión semanal de obra. 8.2 y 8.5 son de ritmo mensual; 8.6 y 8.7 son del comité de compras; 8.1 llega por correo a gerencia; 8.8 no se proyecta nunca.**» | Hoy cada hoja declara su decisión pero ninguna declara su momento. Sin momento, la hoja se abre cuando alguien se acuerda, que es como la Torre lleva meses |
| **§8.3, lienzo** | Resolver el abierto §18.3 así: «**La lista de restricciones va arriba del titular.** En la reunión se trabaja la lista y se asignan dueños en vivo; el titular narrativo sirve a quien llega sin contexto y encabeza el correo, no la pantalla proyectada.» | Se resuelve por el ritual: lo que se proyecta para actuar va primero; lo que se lee solo, después |
| **§8.4, D35** | Reemplazar «el director prepara el comité del lunes» por «**el director prepara la reunión semanal de su obra, que cae en el día ancla de esa obra** (medido: viernes 38, martes 36, lunes 34, jueves 28, miércoles 19 semanas; la obra 70 siempre martes, la 65 viernes, la 62 jueves)». Añadir: «**Riesgo de incumplimiento (D38) es la señal de la reunión diaria**, no de la semanal: entre reuniones es lo primero que ve el residente.» | El comité del lunes no existe en los datos. Un diseño que asume lunes pone el correo y la semana por defecto en el día equivocado para la mayoría de las obras |
| **§8.8** | Añadir al inicio: «**Esta hoja no se proyecta en ninguna reunión, por diseño.** D46 y D47 la hacen de consulta personal y de 1:1. Su distribución es un correo al jefe directo; nunca entra al lienzo de R1.» | Proyectarla es la reconvención pública que D47 prohíbe. Mejor decirlo que confiar en que nadie lo haga |
| **§8.2 y §8.5** | Añadir a cada una: «**Ritmo: mensual o por hito**, en revisión de gerencia o rendición al cliente. No se evalúa con el criterio de muerte de §15, que es semanal.» | No pasan el test de la decisión semanal y no deben fingir que sí; tienen otro ritmo y otra reunión |
| **§9, D14** | Añadir: «La «semana en curso» se resuelve **por obra contra `Fecha_Inicio_Sem` de esa obra**, no contra el lunes calendario. En el día ancla, la hoja Semanal abre con la semana que cierra y ofrece el paso a la que entra.» | La semana es de la obra, no del calendario; si el motor de período asume lunes, el día de la reunión muestra la semana equivocada |
| **§13, F4 (correo)** | Reemplazar «correo automático» por «**correo de víspera por obra** (día ancla − 1, a director y residente: huérfanas de 3 semanas, vencidas con dueño, incumplidas sin causa, cada línea con enlace a la acción) y **correo semanal de gerencia** en día fijo con la fecha de corte de cada obra». Condición de hecho de F4: «El módulo está en el menú, **el correo llega la víspera de la reunión de cada obra**, y se mide qué porcentaje de las huérfanas listadas tiene dueño 48 h después.» Añadir a F4 la pieza: «**disparador programado** (no existe hoy: el único correo del sistema es el de contraseña y las alertas de restricciones son campana in-app).» | D50 se tomó contra el método; esto la vuelve medible y la ata a la reunión real. Y corrige el hecho «el sistema ya manda correo», que es cierto solo a medias |
| **§15** | Añadir un segundo indicador, temprano: «**A las 4 semanas de F4:** si menos del X % de las huérfanas listadas en el correo de víspera gana dueño en 48 h (X lo fija Felipe; propuesta: 30 %), el correo se apaga antes de invertir más.» | El método pide medir la respuesta al empuje, no la apertura; §15 mide a 90 días y esto avisa a los 28 con el mismo dato (`AsignadoEn`) |
| **§16 Riesgos** | Añadir fila: «**La revisión de gerencia (R4) no existe como reunión** → 8.1 no produce la llamada → confirmar con Felipe antes de F3; si no existe, 8.1 se diseña como correo con pantalla detrás.» | Es la hoja de gerencia y su reunión es la menos evidenciada de las cinco |
| **§18 Abiertos** | Añadir las preguntas de la sección 7 de este documento que sigan sin respuesta, y marcar §18.3 como resuelto por el ritual si Felipe acepta el ajuste a §8.3 | Son decisiones de producto, suben a Felipe |

## 7. Preguntas para Felipe, de a una, con la respuesta asumida

Estas son las que `brainstorming` mandaba hacer en vivo. Se listan en el orden en que se harían;
cada una trae el supuesto con que se siguió adelante.

1. **¿La reunión semanal de cada obra es una sola sentada donde se cierra la semana que termina y se
   compromete la que entra?** *Asumido: sí* (el dato de cierre en día 0 o día 7 lo sugiere).
2. **¿Quién la dirige y quién teclea: el director conduce y el residente registra?** *Asumido: sí.*
3. **¿Existe una reunión diaria de obra, y la conduce el residente?** *Asumido: sí, corta, sin
   computador; por eso Riesgo de incumplimiento debe caber en el móvil.*
4. **¿La gerencia tiene una reunión fija de revisión de obras, o el informe de Power BI lo lee cada
   gerente por su cuenta?** *Asumido: no hay reunión fija; por eso 8.1 se diseña para llegar por
   correo.* **Esta es la que más cambia el diseño si la respuesta es otra.**
5. **¿El comité de compras es periódico o se convoca por paquete?** *Asumido: por paquete, al menos
   hoy.*
6. **¿La rendición al cliente es mensual?** *Asumido: mensual o por hito.*
7. **¿Quién debería recibir el correo de víspera además del director y el residente?** *Asumido:
   nadie en la primera versión.*
8. **¿Qué porcentaje de huérfanas con dueño a las 48 h consideraría «el correo funciona»?**
   *Asumido: 30 % para arrancar; lo fija Felipe.*

## Archivos relacionados

- Spec: [[2026-08-20-replanteo-control-tower-design]] · decisiones:
  [[2026-08-20-decisiones-control-tower]] · data: [[2026-08-20-que-data-tenemos]]
- Dominio: [[GLOSARIO]] · [[lps-dominio]] · [[flujo-lps]] · [[docs/flujos/lps-semanal|biblia Semanal]]
