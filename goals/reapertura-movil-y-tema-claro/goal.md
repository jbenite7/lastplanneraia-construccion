# Goal: Reapertura de móvil/tablet y tema claro

**Objetivo:** Devolver al producto los alcances que el repositorio se había prohibido —móvil,
tablet y un tema claro— empezando por los contratos, siguiendo por los gates y terminando por la
interfaz. Cada fase deja evidencia real o falla ruidosamente; ninguna se declara cerrada sin
comprobarlo con una mutación.

**Condición de hecho del goal completo:** las cuatro fases cerradas (F1 destrabar, F2 móvil real,
F3 tema claro, F4 matriz diagonal), con `npm run test:design-system:static` en 8/8 y sin pendientes
abiertos sin dueño.

**Spec del programa:** `docs/superpowers/specs/2026-08-07-reapertura-movil-y-tema-claro-design.md`
(decisiones D1–D8, 2026-08-07).

## Estado por fase

| Fase | Estado | Evidencia |
|---|---|---|
| **F1 — Destrabar** | **CERRADA** (2026-08-07) | DS-032. `390x844` vuelve a ser soportado y no requerido; el gate distingue `SUPPORTED_VIEWPORTS` de `REQUIRED_VIEWPORTS` y valida por primera vez los viewports declarados en `homologation.json`. Commits `01564ff9..0de9b753` + tanda final `c776b429`. |
| **F2a-1 — Precondiciones** | **CERRADA** (2026-08-07) | El harness de fixtures admite caso positivo, el gate valida los 15 manifiestos (miraba 4), ata cada golden a su tema, viewport y contenido, y ningún carril descarta ya el móvil. Commits `1aea682c..dbc3536a`. Spec: `2026-08-07-f2a-piloto-movil-programacion-design.md`. |
| **F2a-2a — Deudas de arranque** | **CERRADA** (2026-08-07) | El golden debe medir exactamente su viewport salvo recorte declarado y autorizado por lista blanca; los 17 manifiestos declaran `1.1.0` y pasan por el chequeo de versión; los seis minors de F2a-1 saldados. DS-033. Commits `0fadef2c..e00a1772`. |
| **F2a-2b — Piloto móvil** | **CERRADA** (2026-08-14) | Reglas de habilitación extraídas a módulo probado (11 pruebas puras) en los dos módulos, sin cambiar comportamiento (`9ad170d6`, `d54ba91b`). Umbral único de 1180 para tarjeta/grilla, coherente con el shell (`c6d99fc8`). Handsontable deja de instanciarse bajo el umbral: 0 nodos medidos en 390×844, en los dos módulos (`74117635`). El sidebar del shell —causa raíz medida el 2026-08-13 de que móvil fuera inusable pese a tener tarjetas: se comía 240 de 390px y nunca colapsaba por ancho— pasó a menú flotante bajo el mismo umbral, sin ensuciar la preferencia de escritorio (spec `2026-08-14-shell-menu-flotante-responsive-design.md`, plan `2026-08-14-shell-menu-flotante-responsive.md`). Tres pruebas de tabla-en-tablet saltadas con motivo escrito, reescritura contra tarjetas diferida a la evidencia visual. Costo medido para F2b: censo de dos patrones de conversión (Handsontable y DataTables), guard de módulo diferido, y el hallazgo de que un umbral compartido exige revisar helpers de test que codifiquen el umbral viejo como string. Redes de habilitación 14/14, suite estática 8/8. |
| **F2b — Resto de módulos** | Pendiente | Los 13 módulos restantes, planificados con el coste medido en el piloto. |
| **F3 — Tema claro** | Pendiente | Paleta clara nueva, conmutador con preferencia guardada. |
| **F4 — Matriz diagonal** | Pendiente | Los gates adoptan la matriz de D6 y los candados se reinstalan en su forma nueva. |

## Pendientes

Se resuelven dentro de este goal, no se difieren fuera de él.

| # | Pendiente | Origen | Estado |
|---|---|---|---|
| P-A | El chequeo de dimensiones del golden usaba `<=` solo sobre el ancho, así que un golden móvil pasaba como evidencia de escritorio. | Re-revisión final de F2a-1, medido | **Cerrado** (`0fadef2c`). Campo `capture` en el manifiesto; regla estricta de ancho y alto salvo recorte declarado. |
| P-B | 11 de los 17 manifiestos declaraban `designSystemVersion: 1.0.0` con `version.json` en `1.1.0`. | Revisión final de F2a-1 | **Cerrado** (`5fa6f007`, `719bff02`). Los 17 declaran `1.1.0` y entran por derivación al chequeo. |
| P-C | Seis minors diferidos de F2a-1. | Revisiones por tarea de F2a-1 | **Cerrado** (`c61b25e3`). Cinco arreglados; el sexto resultó cerrado por construcción. |
| P-D | `capture: "element"` no acotaba el alto, así que un PNG móvil etiquetado como recorte pasaba bajo un viewport de escritorio. | Revisión de F2a-2a Task 1, medido | **Cerrado** (`9d0e7331`, `e00a1772`). Lista blanca con clave compuesta `moduleId/scenarioId`. |
| P-E | Un conteo fijo cambiado por otro conteo fijo en `foundation.test.mjs`. | Revisión de F2a-2a Task 3 | **Cerrado** (`e00a1772`). |
| P-F | La lista blanca se indexaba por `scenario.id`, que no es único entre módulos: otro manifiesto podía reclamar el id y heredar la excepción. Reproducido en verde. | Revisión final de F2a-2a, medido | **Cerrado** (`e00a1772`). Clave compuesta, dos pruebas que se ponen rojas si se borra la regla, y DS-033. |
| P-G | `moduleId` tampoco era único: un manifiesto que se declarara `laboratory` heredaba la excepción. Reproducido en verde. | Caza de variantes, medido | **Cerrado** (`c4624ee8`). Unicidad de `moduleId` y correspondencia con el nombre del archivo. |
| P-H | **La raíz de la escalera:** el gate nunca aplicaba los esquemas, solo comprobaba la presencia de campos obligatorios. Por eso `theme: "linen"` —prohibido por DS-030— entraba por la evidencia, y las propiedades inventadas también. | Caza de variantes, medido | **Cerrado** (`be97a947`, `2ca44f55`). Validador parcial propio (sin dependencias nuevas) aplicado a los siete pares esquema/documento. Cubre `enum`, `const`, `additionalProperties:false`, `required` de subobjetos, `$ref` local e `items`, y desde el 2026-08-09 también `type`, `pattern`, `format: date`, `minLength`, `minimum`, `minItems`, `maxItems`, `uniqueItems` y `prefixItems`; **no** cubre las combinaciones (`oneOf`/`anyOf`/`allOf`/`not`, `if/then/else`), que ningún esquema del repo usa. Al aplicarlas se descubrió que `foundation-shell.json` incumplía su propio `minItems: 1` de `scenarios`: se resolvió con `visualEvidence`, delegación de evidencia con lista blanca explícita (`foundation-shell` → `shell-navigation`) y comprobada contra `homologation.json`, no relajando la regla. Además el validador **falla ante toda palabra clave que no implementa** en vez de ignorarla, así que `runtime-budget.schema.json` (que usa `oneOf`, `allOf` y `maximum`) no puede entrar en alcance sin que el gate lo diga. |
| P-I | La suite estática dejó de terminar (dos intentos abortados a 20 y 41 min). No era lentitud: era un cuelgue de `process.exit(1)` en `uv_thread_join` contra un hilo de Maglev (defecto de Node 26.5.0, medido: 1 cuelgue en 1200 ejecuciones, 0 en 2400 sin él). | Verificación del coordinador | **Cerrado** (`929a74bb`, `431b38e4`). Salida natural en vez de `process.exit`, fixtures concurrentes y `GIT_OPTIONAL_LOCKS=0`. **16 s**, verificado; exit 1 al romper una puerta. |

### Deudas anotadas: ninguna abierta

Las tres que quedaban se cerraron el 2026-08-08, y con ellas los siete pendientes que
destaparon al ejecutarlas.

| Deuda | Cierre |
|---|---|
| Validador de esquema parcial (no cubría `type`, `pattern`, `minimum`, `minItems`). | **Cerrada.** Nueve palabras clave, incluidas tres que el censo destapó (`minLength`, `prefixItems`, `format: date`). Y ahora **falla ante una palabra clave que no implementa** en vez de ignorarla, así que una validación no puede volver a ser silenciosamente incompleta. |
| Las listas blancas vivían como constantes de un `.mjs`, no como contrato. | **Cerrada.** `docs/design-system/evidence-exceptions.json` con su esquema, cada entrada con su motivo escrito, y unicidad por clave para que no haya entradas sombra. Toda entrada debe además **estar en uso**: una excepción concedida y no usada falla. |
| El cuelgue de `process.exit()` no estaba auditado fuera del gate. | **Cerrada.** 20 llamadas en 13 scripts auditadas: 18 convertidas, 2 dejadas con motivo (solo se invocan a mano, no desde `package.json` ni CI). Códigos de salida medidos uno por uno en éxito y en fallo. |

### Lo que quedó sin cerrar, y por qué

| Punto | Estado |
|---|---|
| `$schema`, propiedad opcional y documental, no se valida por tipo en los artefactos `baseline` y `measurement` (sí en `sample`). | Abierto, de riesgo marginal: ningún documento real lo usa mal. Declarado en el código, no escondido. |
| El cruce de `unevaluatedProperties` entre dos ramas de un mismo `allOf` no tiene mutación dedicada. El validador ya lo resuelve reconstruyendo claves; lo que falta es la prueba que lo ejercite. | Abierto y declarado en el comentario de la prueba, con su motivo. |

## La lección de esta fase

El mismo agujero —presentar como evidencia algo que no corresponde— se cerró **siete veces**, y cada cierre destapó el escalón siguiente: la ruta del golden, su hash, el id del escenario, el `moduleId`, los esquemas que nunca se aplicaban, la excepción concedida sin usar, y las entradas sombra. La raíz común es que el contrato se apoyaba en **identidades auto-declaradas**: un nombre que el propio documento se pone y que nadie cruzaba contra nada.

Los seis defectos de plan de esta fase comparten causa con eso: **afirmar el estado de la infraestructura sin medirlo**. Dos causas donde había tres, cuatro manifiestos donde había quince, una prueba que se comparaba consigo misma, una regla imposible de cumplir, dos divergencias donde había tres. Los seis los encontró quien ejecutaba, midiendo.

Para las fases siguientes, dos reglas que ya se aplicaron aquí y funcionaron: **todo gate se entrega con una mutación que lo pone rojo, ejecutada**; y **todo paso que quite algo de una lista mide qué cobertura pierde**, no solo qué gana.

## Archivos de este goal

Estado y relación con los demás goals: [[estado|Estado de los goals]].
