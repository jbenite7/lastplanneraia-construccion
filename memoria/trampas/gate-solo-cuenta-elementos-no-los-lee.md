---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-08-10
areas: [design-system, qa]
fuente: tests/design-system/release-governance.test.mjs, Frente 0 (Task 6, 2026-08-10)
resumen: "El gate de gobernanza del design system solo comprueba evidence.length > 0, nunca su contenido; 14 stubs de dos claves pasaron como evidencia de release durante semanas (corregido el 2026-08-11: 8 gates con recibos reales, y el contrato ya no exige status passed)"
---
# Un gate puede estar verde por no mirar

`tests/design-system/release-governance.test.mjs:68-74` valida los 15 gates de cierre de
`closeout-evidence.json` exigiendo `blocking === true`, `status === 'passed'`, `verifiedAt` como
string y `evidence.length > 0`. **Nunca abre el contenido de `evidence`.** Un array con un stub
dentro pasa exactamente igual que uno con la salida real de un comando.

Eso es lo que pasó: los 14 artefactos referenciados en `docs/design-system/evidence/*.json` eran
stubs literales de dos claves — `{"gateId": "static", "result": "passed"}`, 47–60 bytes cada uno —
sin comando, sin salida, sin fecha, sin hash. No era evidencia vieja que caducó: **nunca fue
evidencia real**. El gate llevaba semanas en verde sin haberlo notado, porque «verde» solo
significaba «el array no está vacío».

Medido en la Task 6 del Frente 0 (2026-08-10): de los 15 gates, solo 2 pasan de verdad hoy
(`static`, `global-table-safety`), 4 fallan con evidencia real, 8 no son ejecutables en una sesión
ad hoc y 1 es un recibo sin comando real detrás — `accessibility-insights`, que cita un binario que
nunca estuvo instalado. Detalle completo en `goals/design-system-nucleo-gobernanza/goal.md`
(«Por qué sigue abierto — 2026-08-10»).

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
| `tests/design-system/release-governance.test.mjs:68-74` | Comprueba `evidence.length > 0`. **Nunca abre el recibo.** Aquí sí, un stub pasa igual que una ejecución real. |
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

> [!check] Corregido el 2026-08-11 — `D-F1b-5`, verificado el 2026-08-12
> Lo de arriba describe el estado del 2026-08-10 y **ya no es el de hoy**, en dos puntos:
>
> - **Son 8 gates, no 15.** (Nueve desde el 2026-08-18, al entrar `semanal-roles-phases`.)
>   `docs/design-system/closeout-evidence.json` declara `static`, `runtime`,
>   `runtime-budgets`, `phpstan-scoped`, `phpstan-global`, `global-table-safety`, `full-app-flow` y
>   `atomic-commit`; `release-governance.test.mjs:76-79` ya **no** fija el número: compara contra
>   `closeoutGateIds.length`, para que añadir o retirar un gate no deje la prueba midiendo una cifra
>   que nadie mantiene.
> - **Ya no se exige `status: 'passed'`.** El comentario del propio test (`:72-74`) explica por qué se
>   retiró el acoplamiento: con la versión estable en 1.x, exigirlo **obligaba a declarar aprobados
>   gates que no lo estaban, y fue el incentivo que produjo los quince recibos `passed` sin ejecutar**.
>   Hoy uno de los ocho está `blocked` y el contrato lo admite. Que cada gate diga la verdad sobre sí
>   mismo lo comprueban sus propias pruebas.
>
> Y el eslabón vacío se llenó: los ocho artefactos referenciados traen `command`, `exitCode`,
> `durationMs`, `tree` y `outputTail` (de 288 a 7.832 bytes), no las dos claves de antes.
>
> **La lección aguanta y de hecho sale reforzada:** el arreglo no fue mirar más fuerte, sino **quitar
> el requisito que premiaba mentir**. Un gate que exige un verde que nadie puede dar honestamente no
> produce verdes honestos, produce recibos falsos.
