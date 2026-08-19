---
capa: fuente
tipo: evidencia
estado: vigente
fecha: 2026-07-29
areas: [pdc]
fuente: goals/pdc-preparar-b1/evidence/paquetes-sin-duracion-ref.md
resumen: Los paquetes sin duracionref — medición y decisión
---

# Los paquetes sin `duracion_ref` — medición y decisión

- **Fecha:** 2026-07-29
- **Pendiente:** punto 2 de [`2026-07-29-cierre-prelanzamiento-pdc-design.md`](../../../docs/superpowers/specs/2026-07-29-cierre-prelanzamiento-pdc-design.md)
- **Medido en:** copia aislada del stack principal (worktree `pdc-b2-vencimientos`, stack `lps-aia-b2`),
  volcado del 2026-07-29. Solo lectura sobre el catálogo global.

## 1 · Cuántos son, y por qué les falta

No son 25: **son 42**. El spec heredó el número de una medición anterior; este es el de hoy.

| Tipo de negociación | Paquetes activos con proceso y sin `duracion_ref` utilizable |
|---|---|
| a todo costo | 16 |
| suministro | 20 |
| mano de obra | 6 |
| **Total** | **42** (sobre 216 paquetes activos) |

«Sin `duracion_ref` utilizable» incluye los tres casos que producen el mismo efecto: la columna en
`NULL`, apuntando a una fila borrada de `general_dias_procesos_contratacion`, o apuntando a una fila
con algún `dias*` nulo.

Consulta (reproducible tal cual):

```sql
SELECT p.tipo_negociacion, COUNT(*) sin_ref
  FROM general_paquetes_contratacion p
 WHERE p.activo = 1
   AND p.modalidad_contratacion IN ('contrato','orden_compra')
   AND (p.duracion_ref IS NULL
        OR p.duracion_ref NOT IN (SELECT id FROM general_dias_procesos_contratacion))
 GROUP BY p.tipo_negociacion WITH ROLLUP;
```

El motivo es el mismo en los 42: **el paquete no tiene equivalente en el catálogo de duraciones**. No
es que la fila esté mal: es que nadie ha medido nunca ese proceso concreto.

## 2 · Los resolubles, resueltos — por el camino que ya existe

Los tres tipos tienen muestra de sobra para su mediana, medida sobre las filas del catálogo con
desglose completo:

| Tipo | Filas con desglose completo que alimentan la mediana |
|---|---|
| a todo costo | 94 |
| suministro | 46 |
| mano de obra | 28 |

Y aquí está el hallazgo que corrige la premisa del spec: **un paquete sin `duracion_ref` sí recibe
fechas**. `PlanFechasService::calcular()` detecta el desglose incompleto, reparte la mediana de su tipo
entre los pasos según `PESOS_REPARTO`, y marca la cabecera con `duracion_provisional = 1`. El paquete
entra al plan, entra al tablero de vencimientos y se ve en pantalla como «plazo estimado».

Está comprobado sobre datos reales, no razonado: Da Porto tiene el paquete **191 «Sum + Inst RED
ELECTRICA»** con `duracion_ref` en `NULL` y, aun así, `dias_totales = 105`, `duracion_provisional = 1`
y sus siete pasos fechados. El gate `tests/test_pdc_v2_vencimientos.php` lo vigila: si algún día un
paquete sin duración dejara de recibir fechas, ese test se pone rojo.

**Los 42 quedan resueltos por esta vía. No hay backfill que hacer.**

## 3 · Lo que NO se hizo, a propósito

**No se escribió `duracion_ref` en el maestro global.** Habría sido inventar una medición que nadie
tomó, y además contradice lo que el comité del 2026-07-29 acababa de pedir: que la obra no toque el
maestro de paquetes y que solo un administrador lo actualice. Cuando alguien mida de verdad esos 42
procesos, la duración entra por el maestro y el plan la usa sin cambiar una línea de código.

## 4 · Lo que sí queda visible en pantalla

Lo que de verdad deja a un paquete fuera del tablero no es la duración: es **no tener plan**. Un
paquete sin frente amarrado, o amarrado sin recalcular, no tiene fechas y por tanto no puede vencer.

Por eso el tablero de Vencimientos declara en pantalla, arriba del todo, cuántos paquetes no está
mirando y por qué:

> Este tablero no está mirando N paquetes sin fechas: X sin frente y Y amarrados pendientes de recalcular.

El denominador son solo los paquetes que generan proceso de contratación: nómina, imprevistos y
consumo directo no se le compran a nadie y contarlos sería una alarma imposible de apagar. En Da Porto
hoy ese número es **0** —tres paquetes con plan y uno `no_contratable`—, así que el aviso no se muestra:
no se inventa una alarma cuando no hay nada que declarar.
