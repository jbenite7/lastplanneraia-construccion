---
capa: fuente
tipo: evidencia
estado: vigente
fecha: 2026-07-29
areas: [pdc]
fuente: goals/pdc-preparar-b1/evidence/validacion-equipo-alquilado-comprado.md
resumen: revisado y anotado como hecho aparte, no implementado. No hay dato perdido (los capítulos del presupuesto son sólo COSTO DIRECTO / COSTO INDIRECTO); hay dato…
---

# Validación — Equipo alquilado vs comprado (Ola 2, fila 5)

**Fecha:** 2026-07-29 · **Rama:** `worktree-pdc-ola2-equipo-alq-comp`
**Spec:** [`2026-07-29-equipo-alquilado-comprado-design.md`](../../../docs/superpowers/specs/2026-07-29-equipo-alquilado-comprado-design.md)
**Plan:** [`2026-07-29-equipo-alquilado-comprado.md`](../../../docs/superpowers/plans/2026-07-29-equipo-alquilado-comprado.md)

**Entorno:** stack propio `pdc-equipo` — app `localhost:8101`, MySQL `3313`, volumen
`pdc_equipo_db_data`. Base sembrada desde un volcado del stack principal. **No se tocó** 3307, 3308,
3310, 3312 ni 3322, ni se aplicó nada al servidor.

---

## Los siete puntos de la condición de hecho

### 1. Un insumo se puede clasificar como comprado o alquilado, y el valor sobrevive a recargar

`tests/test_pdc_v2_equipo_clasificacion.php` — `rc=0`:

```
PASS: El tipo quedó persistido en la BD.
PASS: Quedó registrado quién clasificó.
PASS: Quedó registrado cuándo: es lo que hace que SINCO lo respete.
```

Y en navegador, tras `page.reload()` (`tests/browser/pdc-v2-equipos.spec.mjs`, 2 passed): los tres
clasificados salieron de la cola y los dos sin pista siguen ahí, medido contra la base.

### 2. Todos los equipos preexistentes en «sin clasificar», y en la cola, contados

Censo antes (línea base, idéntico al del stack principal):

```
EQUIPO                    167
ALQUILER EQUIPOS            2
```

Censo después de `--apply`:

```
EQUIPO (SIN CLASIFICAR)   167
ALQUILER EQUIPOS            2
```

Cero filas con el genérico. Las 2 que SINCO ya traía clasificadas **no se tocaron**.

```
PASS: No queda ningún insumo con el tipo genérico «EQUIPO» (hay 0).
PASS: Los equipos preexistentes están en la cola de sin clasificar (167).
PASS: El total de la cola (172) cuadra con la BD (172).
```

### 3. Clasificar 20 de golpe funciona y la cola baja en 20

```
PASS: Clasificar 20 de golpe funciona.
PASS: Se clasificaron 20 (dice 20).
PASS: La cola bajó en 20: 192 → 172.
```

En navegador: el atajo selecciona 3, el botón los clasifica, y el encabezado pasa de `(N)` a `(N-3)`.
Antes de pulsar el botón de clasificar, la base sigue con los 5 sin clasificar — **el atajo selecciona,
no guarda.**

### 4. El motor no ofrece un alquilado como candidato de un paquete de compra

**La regresión se midió antes de arreglarla.** Con los valores nuevos y `tiposCompatibles()` sin tocar,
`test_pdc_v2_paquetes_motor.php` daba:

```
FAIL: Un equipo ALQUILADO no es admisible en un paquete de suministro.
FAIL: Sin clasificar NO cae en mano de obra: sigue filtrando, no cayó al default.
FAIL: Un equipo alquilado tampoco cae en mano de obra.
FAIL: Un equipo comprado tampoco cae en mano de obra.
=== 4 FAILED ===
```

Tras nombrar los tres valores en el `match`: `=== OK ===`, `rc=0`.

### 5. Reimportar no devuelve a «sin clasificar» un insumo ya clasificado

Dos caras, y la que el spec señalaba **no era la rota**:

- **Presupuesto (ya estaba sana, ahora fijada):** ningún `INSERT` de `MaestroInsumosService` escribe
  `tipo_recurso`, y sólo un método del servicio escribe la columna — el de clasificar a mano.
  Comprobado sobre los INSERT reales por regex, no por presencia del string.
- **SINCO (era la rota):** hacía `SET tipo_recurso = ?` a ciegas por `codigo_sinco`, y los 167 equipos
  tienen todos código. `resolverTipoRecurso()` lo acota:

```
PASS: SINCO manda EQUIPO sobre un equipo clasificado a mano: gana la persona.
PASS: SINCO mandando «sin clasificar» tampoco degrada una clasificación.
PASS: Sobre una fila migrada (sin autor), SINCO escribe con normalidad.
PASS: SINCO trayendo ALQUILER EQUIPOS sobre un sin clasificar sí escribe: gana precisión.
PASS: En tipos que no son equipo el importador sigue mandando, como siempre.
PASS: Con la fila real en BD: el importador resuelve conservar la clasificación humana, no degradarla.
```

### 6. La migración tiene vuelta atrás probada

**DDL:** aplicar → verificar → `DROP` → verificar vacío → reaplicar. Las dos columnas quedan.

**Datos:** dry-run no escribe (censo intacto tras correrlo), `--apply` idempotente (segunda corrida:
`A mover: 0`), `--revertir` devuelve **el censo exacto de la línea base**, y reaplicar vuelve a 167:

```
### IDEMPOTENCIA (segundo --apply)
  A mover a «EQUIPO (SIN CLASIFICAR)»: 0
  Ya en tránsito (no se tocan): 167

### REVERTIR
REVERTIR: 167 filas «EQUIPO (SIN CLASIFICAR)» → «EQUIPO».
Se CONSERVAN 0 clasificadas por una persona (revertir no borra su trabajo).
→ censo: EQUIPO 167 · ALQUILER EQUIPOS 2   (= línea base)

### REAPLICAR
  A mover: 167 · Del tapón, con pista SINCO: 145 de 167
→ censo: EQUIPO (SIN CLASIFICAR) 167 · ALQUILER EQUIPOS 2
```

### 7. Regresión en verde

```
test_global_table_safety            rc=0
test_global_table_reconciliation    rc=0
test_pdc_v2_equipo_clasificacion    rc=0   (nuevo)
test_pdc_v2_rbac_equipos            rc=0   (nuevo)
test_pdc_v2_paquetes_motor          rc=0
test_pdc_v2_paquetes                rc=0
test_pdc_v2_maestro_sinco_import    rc=0
test_pdc_v2_maestro_sinco_parser    rc=0
test_pdc_v2_reenganche_pendientes   rc=0
test_pdc_v2_maestro_gobernado       rc=0
test_pdc_v2_impacto_reimport        rc=0
```

`phpstan analyse src admin/src` → **`[OK] No errors`** (corrigió un `??` muerto que él encontró).
Vitest: **337 passed** (24 archivos, 3 nuevos). `npm run build`: limpio, `tsc` incluido.
Playwright `tests/browser/pdc-v2-equipos.spec.mjs`: **2 passed**.

**No hubo rojos preexistentes en esta zona**: la línea base del Task 0 dio `rc=0` en los 7 tests
medidos antes de tocar código.

---

## RBAC — un rol permitido y uno denegado

`test_pdc_v2_rbac_equipos.php` — `rc=0`, resuelto contra `RbacService` y la BD real:

```
Rol PERMITIDO — A: puede lps.pdc.maestro y lps.pdc.ver
Rol DENEGADO  — V: NO puede lps.pdc.maestro (→403 en el POST); SÍ puede el GET de lectura
Roles de obra (R, C, S, G, V): ninguno puede clasificar
Reparto: lps.pdc.maestro → A, D    ·    lps.paquetes_contratacion.reglas → A, D, OT
```

El spec dejaba la capacidad «a confirmar al escribir el plan». Se eligió **`lps.pdc.maestro`**, cuya
descripción en `RbacCatalog` es literalmente «Administrar el maestro global de insumos del plan de
compras v2». `paquetes_contratacion.reglas` gobierna reglas y overrides del motor: otra puerta, otro
objeto.

---

## Navegador — desktop 1180×820, dark

Captura: [`../../../tests/browser/__screenshots__/pdc-v2-equipos-cola.png`](../../../tests/browser/__screenshots__/pdc-v2-equipos-cola.png)

Verificado: la sección abre; la columna «SINCO dice» muestra la agrupación cruda y «Sugerencia» la
derivada; la cola viene **preordenada** por pista; el atajo dice «los 56 que SINCO marca como compra»
(53 `COMPRA` + 3 `COMPRAS`); clasificar baja el conteo; **sin overflow horizontal a 1180 px** (test
propio). Consola sin errores.

**El panel del navegador integrado no sirvió para esto:** pierde la cookie de sesión entre turnos y el
login nunca completa (credencial verificada por separado como válida). Es limitación conocida del
panel, no de la app; la validación se hizo con Playwright contra el contenedor servido, que es la
herramienta del repo.

---

## Dos huecos que sólo vio el navegador

Los tests de PHP no podían verlos, y son del código que escribí:

1. **La cola era inalcanzable en una obra sin presupuesto importado.** La pantalla del maestro hace
   *early return* con el aviso «importa un presupuesto», y eso escondía la cola de equipos — que es del
   catálogo **global** y no depende del presupuesto de ninguna obra. Justo la obra nueva, donde alguien
   está montando el maestro, se quedaba sin puerta de entrada al tapón.
2. **Clasificar no daba acuse en esa rama.** El `role="status"` vivía sólo en el render normal, así que
   la acción se completaba en silencio.

Ambos corregidos, y el e2e cubre las dos formas de la pantalla.

---

## Pendientes y decisiones para el usuario

1. **`OT` (Oficina Técnica / Compras) no puede clasificar equipos.** Tiene
   `lps.paquetes_contratacion.reglas` pero no `lps.pdc.maestro`. Se implementó según el spec —el
   maestro global es administración—, pero *quién decide si un equipo se alquila o se compra* suena a
   Compras más que a Administración. **Decisión del usuario**; el cambio sería una línea de RBAC.
2. **Gastos generales:** revisado y anotado como hecho aparte, **no implementado**. No hay dato perdido
   (los capítulos del presupuesto son sólo `COSTO DIRECTO` / `COSTO INDIRECTO`); hay dato no explotado
   —el `agrupacion` de SINCO, que el maestro ya guarda y ninguna pantalla agrupa—. Necesita grilleo con
   Tomás para no duplicar su código.
3. **El tapón queda puesto: 167 decisiones humanas.** Por decisión explícita del usuario. Mitigado a
   pocos clics para 145 de ellas mediante preorden y selección por lote, sin escribir nada sin
   confirmación. «Sin clasificar» hereda el comportamiento del viejo `EQUIPO`, así que el módulo se
   puede usar con el tapón puesto.
4. **Riesgo asumido en `resolverTipoRecurso()`:** si presupuestos clasifica mal en SINCO, sobrescribe
   una clasificación humana correcta. Es deliberado —SINCO es la fuente contable— y queda auditado en
   `clasificado_por` / `clasificado_at`. Si en obra molesta, la vuelta es invertir esa rama.
5. **Los números son del volcado del 2026-07-29.** Antes de aplicar en cualquier otro entorno hay que
   volver a correr el dry-run: recalcula por regla y no lleva ids fijos.
6. **El despliegue de esta migración es de otra sesión.** Aquí no se aplicó nada al servidor.
