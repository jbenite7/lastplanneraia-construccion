# Objetivo: familias operativas y contratos desde cronograma

Implementar la separación entre familias operativas y elementos contractuales. `/listado-actividades/` debe proponer familias operativas reales tomadas del cronograma, mientras `/contratos/` debe conservar o autogenerar compras, insumos, materiales, equipos, suministros, subcontratos y paquetes contractuales.

La comprensión compartida está en `goals/familias-operativas-y-contratos-desde-cronograma/facts.md`.

El plan aprobado está en `goals/familias-operativas-y-contratos-desde-cronograma/plan.md`.

## Condición de terminado

El objetivo queda terminado cuando:

- `/listado-actividades/` solo propone familias operativas canónicas del cronograma.
- Los elementos contractuales no aparecen como familias listas para aplicar en `/listado-actividades/`.
- `/contratos/` conserva o genera propuestas para los elementos contractuales excluidos de listado.
- `general_dias_procesos_contratacion` se usa como primera base contractual para paquetes faltantes.
- La matriz/corpus se regenera separando familias operativas, elementos contractuales, aliases y dudas.
- `Enchapes Ceramicos en Muros` queda absorbido por `Pisos y Enchapes`.
- `Red RCI` y `Red Contra Incendio - Piping` quedan unificados como `Red de Extinción`.
- Las pruebas PHP cubren la política, quality gate, matriz y servicio semi-auto.
- Las pruebas E2E cubren JMC y Da Porto con evidencia guardada.
