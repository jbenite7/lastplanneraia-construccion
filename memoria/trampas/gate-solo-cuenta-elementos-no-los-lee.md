---
tipo: trampa
estado: vigente
fecha: 2026-08-10
areas: [design-system, qa]
fuente: tests/design-system/release-governance.test.mjs, Frente 0 (Task 6, 2026-08-10)
resumen: "Un gate que cuenta evidence.length > 0 sin abrir el recibo dejó pasar 14 stubs durante semanas; el hueco se cerró el 2026-08-11 con gate-receipt-content, que sí abre cada recibo"
---
# Un gate puede estar verde por no mirar

Cuando se escribió esta página, `tests/design-system/release-governance.test.mjs` validaba los
(entonces) 15 gates de cierre de `closeout-evidence.json` exigiendo `blocking === true`,
`status === 'passed'`, `verifiedAt` como string y `evidence.length > 0`. **Nunca abría el contenido
de `evidence`.** Un array con un stub dentro pasaba exactamente igual que uno con la salida real de
un comando.

Eso es lo que pasó: los 14 artefactos referenciados en `docs/design-system/evidence/*.json` eran
stubs literales de dos claves — `{"gateId": "static", "result": "passed"}`, 47–60 bytes cada uno —
sin comando, sin salida, sin fecha, sin hash. No era evidencia vieja que caducó: **nunca fue
evidencia real**. El gate llevaba semanas en verde sin haberlo notado, porque «verde» solo
significaba «el array no está vacío».

Medido en la Task 6 del Frente 0 (2026-08-10): de los 15 gates de entonces, solo 2 pasaban de
verdad (`static`, `global-table-safety`), 4 fallaban con evidencia real, 8 no eran ejecutables en
una sesión ad hoc y 1 era un recibo sin comando real detrás — `accessibility-insights`, que citaba
un binario que nunca estuvo instalado. Detalle completo en
`goals/design-system-nucleo-gobernanza/goal.md` («Por qué sigue abierto — 2026-08-10»).

**Why:** un gate que cuenta elementos de un array en vez de inspeccionar su contenido no distingue
entre «se ejecutó y produjo evidencia» y «alguien escribió dos líneas de JSON». Es la misma familia
que [[guard-valida-declaracion-contra-si-misma]] y
[[comentario-de-token-afirma-uso-inexistente]]: una fuente que se lee con buena fe y afirma algo
que el repositorio no respalda — aquí el gate mismo es la fuente que se engaña.

**How to apply:** al escribir un gate sobre "evidencia" o "recibos", pregúntate qué archivo
tendría que estar mal para que fallara. Si la respuesta es "que el array esté vacío" y nada más, no
vigila el contenido — solo la forma. Un gate de evidencia real necesita comprobar al menos un campo
que solo una ejecución verdadera puede producir (comando, salida, hash del artefacto medido).

Relacionado: [[condicion-de-hecho-caduca-sin-aviso]] — la misma sesión midió que la condición de
hecho del goal se apoyaba en esta evidencia falsa. Mapa del área: [[design-system]].

## Precisión del 2026-08-11: no todo el cierre se avalaba a sí mismo

La primera redacción de esta página, y varias notas que la citaron, decían que **el cierre del design
system se avalaba a sí mismo**. Dicho así es más ancho de lo que los hechos aguantan, y lo corrigió
la sesión del Frente 1b al intentar migrar los recibos.

Lo que de verdad ocurría, separado:

| Pieza | Qué hace |
|---|---|
| `tests/design-system/release-governance.test.mjs` | Comprueba `evidence.length > 0`. **Nunca abre el recibo.** Aquí sí, un stub pasa igual que una ejecución real. |
| `scripts/design-system-closeout-contract.mjs` | **Es estricto**: exige `verifiedAt` con formato exacto, posterior a `generatedAt`, `null` en los no resueltos, y delega en `design-system-evidence-receipt.mjs`, que **resuelve el `sourceRef` a un commit y recalcula el `sha256`**. Y corre en la suite, vía `design-system-contracts.mjs`. |

Así que el contrato **sí verificaba contenido y procedencia**. Lo que fallaba es que el artefacto al
que apuntaba era un sello de 47 bytes: **la cadena de verificación era buena y el eslabón final
estaba vacío.**

Eso cambia el diagnóstico y lo hace más útil. No era «nadie comprobaba nada», que invita a
reconstruirlo todo. Era **una comprobación que contaba en vez de leer, y un artefacto sin contenido
que alimentaba a las demás**. Reconstruir el mecanismo que genera el recibo —que ahora deriva el
resultado del código de salida en vez de aceptarlo como parámetro— arregla la cadena entera sin
tocar lo que ya era estricto.

**La lección de segundo orden:** al encontrar un gate que no mira, comprueba **cuáles de sus vecinos
sí miraban** antes de declarar que el conjunto no verificaba nada. Un diagnóstico demasiado ancho
lleva a reconstruir piezas sanas.

## Estado a 2026-08-12 (pase 8 de veracidad): el hueco está cerrado

El cierre del Frente 1b movió todo lo que este cuerpo describía como vigente, verificado sobre
`0e45ba1d`:

- `closeout-evidence.json` declara **8 gates, no 15** (`release-governance.test.mjs:75-77` exige
  `gates.length === 8`).
- El test **ya no exige `status: 'passed'`** en todos: D-F1b-5 (2026-08-11) retiró ese
  acoplamiento — el comentario en `release-governance.test.mjs:68-74` explica que exigirlo fue el
  incentivo que produjo los quince recibos `passed` sin ejecutar. Hoy `runtime-budgets` está
  `blocked` con `exitCode: 1` y el gate pasa, porque dice la verdad.
- Los stubs de 47–60 bytes **ya no existen**: los recibos de `docs/design-system/evidence/` llevan
  `command`, `exitCode`, `artifactSha256`, `sourceRef` y `outputTail` reales.
- Y la pieza que faltaba existe: `tests/design-system/gate-receipt-content.test.mjs` abre cada
  recibo con `validarRecibo()` (`scripts/design-system/gate-receipt.mjs`) y falla si no coincide
  con lo que el índice declara — exactamente el gate que este cuerpo describía como ausente.

La trampa queda como lección de diseño de gates; el estado del sistema que describía ya no es este.

