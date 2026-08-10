---
tipo: trampa
estado: vigente
fecha: 2026-08-10
areas: [design-system, qa]
fuente: tests/design-system/release-governance.test.mjs, Frente 0 (Task 6, 2026-08-10)
resumen: "El gate de gobernanza del design system solo comprueba evidence.length > 0, nunca su contenido; 14 stubs de dos claves pasaron como evidencia de release durante semanas"
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
