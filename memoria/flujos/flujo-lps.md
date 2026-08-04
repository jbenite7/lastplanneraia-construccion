---
tipo: flujo
estado: vigente
fecha: 2026-08-03
areas: [lps, arquitectura]
fuente: GLOSARIO.md
resumen: "El ciclo LPS de punta a punta: del programa general a los indicadores, y qué módulo cubre cada paso"
---
# Flujo LPS

El ciclo Last Planner tal como lo implementa esta aplicación. Cada paso enlaza al módulo que lo
cubre; el vocabulario lo fija [[GLOSARIO]] y el dominio, [[lps-dominio]].

Todo arranca en **[[programa-general]]**, la línea base del proyecto: qué actividades hay y cuándo
deberían pasar. Todo lo demás se mide contra esto. Cuando la obra se mueve, **[[cronograma]]**
actualiza esa línea base. De ahí baja a **[[programacion-intermedia]]**, la ventana de medio plazo
donde se bajan las actividades del programa general y se les levantan restricciones antes de que
lleguen a la semana. La ventana intermedia desemboca en **[[programacion-semanal]]**, el compromiso
de la semana: qué se va a hacer de verdad.

Cierran el ciclo de aprendizaje tres submódulos: **[[submodulo-cnp]]** registra lo que no llegó a
programarse y por qué, **[[submodulo-cnc]]** lo que se programó y no se cumplió y por qué, y
**[[submodulo-cic]]** califica a cada subcontratista en cuatro dimensiones —Calidad, GSA, SST y
ADM— que se agregan en `Cal_Integral`, tomando el PAC como uno de sus insumos; no es «el
cumplimiento medido», que es el PPC/PAC (corregido el 2026-08-04 contra [[GLOSARIO]] `:44` y
`src/Controllers/Api/CicApiController.php:61`). De ahí salen **[[indicadores]]**,
el PPC y las demás medidas, que **[[torre-de-control-bi]]** presenta a nivel de portafolio.

En paralelo, **[[escalamientos-y-crisis]]** recoge lo que se atasca, y **[[profesionales]]**,
**[[subcontratistas]]** y **[[control-de-cambios]]** aportan quién responde de cada compromiso.

El flujo de compras corre al lado: ver [[flujo-pdc]].

## El comportamiento esperado, escenario por escenario

Esta página narra el ciclo; **la biblia de flujos dice qué debe pasar en cada paso**, con
precondiciones, resultado en pantalla y en datos, y cita al código:
[[docs/flujos/lps-cascada|las invariantes de cascada]] (el candado de semana),
[[docs/flujos/lps-programa-general|programa general]], [[docs/flujos/lps-intermedia|intermedia]],
[[docs/flujos/lps-semanal|semanal]] y [[docs/flujos/lps-aprendizaje|CNP/CNC/CIC]].

Son capas distintas y con reglas opuestas a propósito: si la biblia y el código divergen, es un bug
de uno de los dos ([[docs/flujos/README|por qué]]); si esta wiki y el código divergen, gana el
código.
