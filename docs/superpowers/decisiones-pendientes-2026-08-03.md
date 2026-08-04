# Decisiones pendientes del usuario — cierre de dark mode

**Modo acordado el 2026-08-03:** el usuario pidió no ser interrumpido durante la ejecución. Todo
lo que requiere su criterio se acumula aquí y se le presenta **al final del plan**, en bloque.

Regla que sigue esta sesión mientras tanto: donde haga falta decidir para no detenerse, se toma la
opción **más conservadora y reversible**, se ejecuta, y se registra abajo como *asumida* — nunca
como aprobada. Lo irreversible o lo que consagra un estado visual (goldens) **no se aplica**: se
deja preparado y aquí anotado.

---

## A · Requieren aprobación antes de aplicarse (nada se ha aplicado)

| # | Qué | Estado | Por qué es tuya |
|---|---|---|---|
| **A-1** | **Goldens de tabla** (task 15) | Preparados, **sin commitear** | Recapturar consagra el aspecto actual como «lo correcto». Si algo se rompió sin que lo viéramos, quedaría bendecido. Se te mostrará par a par |
| **A-2** | **Densidad compacta** (task 19) | En curso; capturas antes/después al terminar | Cambia el aspecto de todas las tablas de la app. Verás filas ganadas por superficie y las cabeceras ya legibles |
| **A-3** | **Variante B de bordes en tablas reales** | Decidida en maqueta, **no aplicada** | Aprobaste B + numéricas a la derecha sobre una maqueta HTML. Handsontable y DataTables pintan sus propios bordes: hay que verlo ahí antes de producción |

## B · Asumidas por la sesión, ratifícalas o revócalas

| # | Decisión asumida | Razón | Reversible |
|---|---|---|---|
| — | *(se irá llenando durante la ejecución)* | | |

## C · Deuda inventariada que espera tu criterio

| # | Qué | Tamaño | Nota |
|---|---|---|---|
| **C-1** | **Las 22 ramas viejas** | Censo en `docs/superpowers/ramas-viejas-2026-08-03.md`: 22/22 sin contenido único, verificado por muestreo | Borrarlas es seguro según la medición, pero borrar ramas es tuyo |
| **C-2** | **Fase 6 — el grueso estructural** | ~2 600 hallazgos: `!important`, `css-outside-layer`, `raw-token-in-module` | Requiere que apruebes el inventario inicial de excepciones justificadas. Es la campaña larga; el punto natural de corte si quieres parar |
| **C-3** | **`pdc.css:318` — borde-acento del toast** | 1 línea | El detector lo marca en cada parada. O se suaviza el acento, o se declara intencional en config (necesita tu confirmación) |
| **C-4** | **23 hallazgos de `pdc.css` y 16 de PS** fuera de rampa tipográfica | Preexistentes, con techo en presupuesto | Parte los racionaliza el task 19; el resto entra en C-2 |
| **C-5** | **Sidecar `.impeccable/design.json` desactualizado** respecto a `DESIGN.md` | Trivial | Regenerarlo es un comando; no se hizo por no tocar el sistema de diseño sin necesidad |
| **C-6** | **Contenido de «HOMECENTER CALI» en la fila 1 del sandbox** `pdc_sandbox_e2e` | Desconocido | No coincide con el seed. Puede ser residuo de una importación manual o un bug de importación real; no se investigó |
| **C-7** | **DataTables: gatillo de filtro sin verificación visual** | Menor | Ninguna tabla alcanzable tiene ordenación activa hoy; la regla está escrita pero nadie la ha visto en acción |
| **C-8** | **`state-tint-exceptions.json` sigue anclado por número de línea** | Menor | Su hermano ya migró a firma (task 12-bis). Las 3 entradas actuales están verificadas correctas |
