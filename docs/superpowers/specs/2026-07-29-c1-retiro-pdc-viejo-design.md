# PDC · Fase C1 — Retirar el PDC viejo, y qué hacer con su dark a medias — Design

- **Fecha:** 2026-07-29
- **Ola:** 3 (lo grande), pero **con una decisión que aplica hoy**
- **Goal:** `goals/pdc-preparar-b1`
- **Origen:** roadmap maestro (fase C1) + un conflicto detectado al reunir los pendientes.
- **Estado:** aprobado en grilleo, pendiente de plan.

## El conflicto que se resolvió solo — y por qué queda escrito igual

Al reunir los pendientes se detectó una contradicción: **C1** retira el PDC viejo (`/pdc`,
`/api/pdc/*`, `OperationalFamilyPolicy`, la vista Handsontable), y al mismo tiempo se estaba invirtiendo
esfuerzo en migrarlo a dark. Trabajo sobre una pantalla condenada.

La decisión del usuario fue **parar el dark y acelerar C1**.

**Lo que se encontró al ir a aplicarla:** la decisión llegó tarde. El trabajo ya estaba **terminado y
commiteado** el mismo 2026-07-29:

- `a3d59e8` — `/pdc` cierra F2: `public/css/pdc.css` entra en `@layer module`, 22 de 23 `rgba()` pasan a
  tokens, y el defecto real (`.pdc-message-neutral`, tinta casi negra sobre canvas oscuro) queda
  corregido. Ese commit además **corrige la premisa con la que se abrió la tarea**: `/pdc` no se pintaba
  en claro — el body ya estaba en `rgb(11,16,13)` porque el grueso de la hoja ya usaba tokens. Lo que
  faltaba eran sombras, bordes y acentos.
- `c5af102` — el panel de limpieza del Plan de Compras (`PdcResetService`, `PdcMaintenanceController`,
  el seed y su test) también está commiteado, con sus cuatro salvaguardas antes de borrar.

O sea: **no hay nada que triar ni que congelar**. No quedó trabajo sin versionar y no hay archivos en
riesgo de morir con una rama.

### Qué queda de esa decisión

- **No se abre más trabajo de diseño sobre `/pdc`.** Lo hecho, hecho está; lo que venga se invierte en
  retirarla, no en pulirla.
- **`PdcResetService` y su panel de mantenimiento sobreviven al retiro.** No son del PDC viejo: sirven
  para vaciar el PDC **v2** de un proyecto y rehacer el flujo desde la carga del presupuesto. C1 no los
  toca.
- **La lección:** la contradicción se detectó al inventariar pendientes, no al planificar la tarea. Si el
  inventario se hubiera hecho antes, esas horas se habrían ahorrado. No es un reproche al trabajo hecho
  —que además corrigió una premisa falsa y arregló un defecto real de contraste— sino el motivo por el
  que este goal empieza por reunir los pendientes de todos los frentes.

## Alcance de C1

### Se retira

- La ruta `/pdc` y las `\/api/pdc/*` del front controller.
- La vista Handsontable del módulo viejo y su CSS (`public/css/pdc.css`, `views/pdc/pdc.view.php`).
- `OperationalFamilyPolicy` y el modelo de «familias», si no lo consume nada más — **hay que comprobarlo
  antes**, no suponerlo.

### Se decide, no se improvisa

- **Los datos históricos del PDC v1.** El roadmap lo deja explícitamente abierto. Antes de borrar nada:
  medir cuántas obras tienen datos en el modelo viejo, si alguien los mira, y si hay que conservarlos en
  frío. Esta decisión necesita respuesta del dueño del producto, no criterio técnico.

### Precondición innegociable

C1 **no empieza** hasta que el PDC v2 esté validado en producción con una obra trabajando de verdad. Es la
condición que ya fijaba el roadmap («cuando A+B estén validados en producción») y el comité no la cambió.

## La lección que hay que dejar escrita

Antes de borrar un asset o una vista: **grepear `public/js`, `views` y `src` ignorando comentarios, y
buscar los tests que lo leen**. Ya pasó una vez en este repositorio — un CSS declarado «solo comentarios
históricos» tenía un consumidor vivo en JavaScript. Esa comprobación es parte del trabajo, no una
precaución opcional.

## Además: la deuda de diseño del PDC nuevo

El módulo v2 entró al inventario del sistema de diseño como **`inventory-only`**: consume el shell y pasa
su gate, pero no tiene manifiesto de piloto con escenarios y evidencia. Declararlo migrado sin haberlo
hecho sería falso, y así se registró.

Cerrar esa deuda es trabajo de la migración de diseño propiamente dicha. Va en esta ola porque es el
momento coherente: el PDC viejo se retira y el nuevo pasa a ser **el** plan de compras de la empresa —
merece estar en el sistema de diseño como tal, no como inventario.

Ojo con la trampa ya medida: un manifiesto no se puede crear en seco. Exige al menos un escenario con su
golden real y su `sha256` coincidente.

## Condición de hecho

1. Está medido y escrito quién consume todavía el modelo viejo, con el grep completo como evidencia.
2. Hay decisión escrita del dueño del producto sobre los datos históricos del v1.
3. Retiradas las rutas, la aplicación arranca, la suite pasa y ninguna pantalla queda enlazando a `/pdc`.
4. El PDC v2 tiene manifiesto de piloto con escenarios y evidencia, y deja de ser `inventory-only`.
5. `PdcResetService` y el panel de mantenimiento siguen funcionando después del retiro: no eran del PDC
   viejo y no deben caer con él.

## Riesgos

- **Retirar antes de tiempo deja a una obra sin herramienta.** La precondición está para eso y no se
  negocia por comodidad de calendario.
- **Los datos históricos son irreversibles.** Cualquier borrado exige respaldo verificable y estrategia de
  restauración, como cualquier operación destructiva en este repositorio.
