# Estado de las olas — tablero de relevos

**Para qué existe.** Las tareas de este goal se ejecutan en sesiones independientes y varias tienen que
esperar a otra. Preguntarle a una sesión «¿ya acabaste?» no es fiable: puede no responder, puede haberse
caído, o puede creer que acabó. Este archivo convierte esa pregunta en algo **comprobable**.

**Cómo se usa.**

- **Al cerrar tu tarea** —y solo cuando su condición de hecho esté cumplida y verificada— cambia tu fila a
  `HECHO`, escribe el `sha` del commit que la cierra y la fecha, y commitea este archivo junto con tu
  trabajo. Si tu tarea queda a medias, escribe `PARADA` y el motivo en una línea: a quien espera le sirve
  más saber que no vas a llegar que seguir esperando.
- **Antes de empezar la tuya**, comprueba a tu predecesora así:

```bash
git fetch origin --quiet && git show origin/main:goals/pdc-preparar-b1/estado-olas.md
```

Lee la fila de tu predecesora. Si dice `HECHO`, estás autorizada. Si dice `PENDIENTE` o `EN CURSO`,
vuelve a mirar más tarde. Si dice `PARADA`, **no arranques**: avísale al usuario, porque el plan cambió.

**Regla que no se rompe:** nadie marca `HECHO` la fila de otra sesión, y nadie se marca `HECHO` a sí misma
sin haber corrido su verificación. Una fila mentida hace arrancar a la siguiente sobre un supuesto falso,
que es peor que no tener este archivo.

## Permisos de git para las sesiones de este goal

**Decisión de Felipe del 2026-07-30.** Se registra aquí, en el repositorio, porque una autorización que
viaja de sesión en sesión no es una autorización: la sesión que la recibe no puede distinguir un permiso
real de uno inventado por su emisor, y hace bien en frenar. Escrita una vez, vale para las diez.

| Acción | Permiso |
|---|---|
| **Commit en tu propia rama de worktree** | **Autorizado**, sin pedirlo cada vez. El mecanismo de relevos lo exige: marcar `HECHO` es un commit |
| `git merge origin/main` dentro de tu rama | **Autorizado** — es parte de mantenerte al día |
| **Push al remoto** | **No.** Lo hace solo quien lleva el ciclo de integración |
| **Escribir en `main`** | **No.** Igual que el anterior |
| Desplegar a producción | **No**, y además tiene puerta humana propia (fila 4) |

El alcance de lo autorizado es tu rama. Nada de esto publica hacia fuera: `main` y `origin` los toca una
sola sesión, después de verificar con salida real de comandos que la condición de hecho se sostiene.

Si al leer esto sigues teniendo dudas sobre si te alcanza, pregúntale a Felipe en tu propio hilo. Frenar
por prudencia nunca es el error caro.

---

## Ola 1 — lo que el comité comprometió

| # | Tarea | Espera a | Estado | Commit | Fecha |
|---|---|---|---|---|---|
| 1 | Tablero de vencimientos (B2, look-ahead) | — arranca ya | HECHO | `60f8bfe` | 2026-07-29 |
| 2 | Impacto al recargar + tamiz del presupuesto | — arranca ya | HECHO | `31e9145` (merge de main: `22d13c7`) | 2026-07-29 |
| 3 | Cierre pre-lanzamiento (los cuatro pendientes) | — arranca ya | HECHO | `88c37b8` | 2026-07-29 |
| 4 | Despliegue a producción | 1, 2 y 3 · **+ comunicado enviado + autorización explícita del usuario** | HECHO — **solo `prueba-lps`** | `9e77dd2` (desplegado) | 2026-07-30 |

### Nota sobre 4 — el alcance es `prueba-lps`, no la producción real

**Léela antes de arrancar la 5 o la 10.** «HECHO» aquí no significa que el PDC v2 esté en
`lastplanneraia.com`. La producción real **no se tocó**, por decisión de Felipe del 2026-07-30: lo que
él trata como producción es `prueba-lps`, y ahí se desplegó en dos pasadas (642 commits primero, 21
después) hasta `9e77dd2`, con las 35 migraciones aplicadas, los dos parches de RBAC del PDC
(`lps.pdc.importar`, `lps.pdc.maestro`) y el remap de `unique_id`.

`lastplanneraia.com` sigue en `1aa7c69` del 2026-07-16, con **cero tablas `pdc_*`**.

**Consecuencia directa para la fila 10:** su puerta era «una obra trabajando de verdad en
producción», y con la producción real intacta **esa puerta sigue cerrada**. Que esta fila diga HECHO
no la abre.

**Aplazado por decisión de Felipe, no olvidado:** el humo autenticado. Nadie ha mirado con sesión el
visor con los avisos del tamiz, el informe de impacto al recargar, la curva de desembolsos ni el
desplegable de frentes. Lo verificable por datos y comandos sí está: respaldos restaurados y
comparados por conteos, esquema comprobado en SQL, y `anclasDisponibles()` del proyecto 27 devolviendo
155 anclas donde devolvía 0.

**Dos huecos de datos que quedan abiertos y no son de esta fila:** ~18 700 filas de
`programa_consolidado` huérfanas sin pareja en `programa` (el remap no puede darles identificador, y
el porqué merece sesión propia), y 52 paquetes sin `duracion_ref`, que el propio tablero asigna a la
fila 1.

## Ola 2 — lo que el uso va a exigir

| # | Tarea | Espera a | Estado | Commit | Fecha |
|---|---|---|---|---|---|
| 5 | Equipo alquilado vs comprado | 4 | PENDIENTE | | |
| 6 | Ayuda dentro de la aplicación | 1 y 2 (necesita las pantallas terminadas) | PENDIENTE | | |
| 7a | Re-matching al reprogramar (B2, 2ª mitad) | 1 (comparten `PlanFechasService`) | **HECHO** | `3a0da33` (integra `b590b5e`, `13e6e31`, `b2859e3`, `c254955`, `87fa7a3`) | 2026-07-29 |
| 7b | Los cuatro diferidos de A4.1 (configuración de pasos) | 7a (misma superficie) | **HECHO — A4.1 cerrada del todo:** 3 construidos + 1 archivado con motivo el 2026-07-30 | `3a0da33` (integra `efe8d5e`, `20d6acf`, `c725fc7`) | 2026-07-29 |

`3a0da33` es el merge de la fila 1 (`9d90663`) dentro de esta rama, verificado después de integrar:
16 tests PHP en verde —incluidos `test_pdc_v2_vencimientos` y `test_pdc_v2_maestro_gobernado`, que
llegaron con la fila 1—, phpstan limpio, 278 de vitest y 11 e2e de navegador. El bundle
`public/pdc-app/assets/*` se **recompiló** desde la fuente ya mezclada, no se eligió una de las dos
versiones. La rama queda lista para que la integre quien lleva el ciclo; no se empujó desde aquí.

### Nota sobre 7a — la medición recortó el spec a la mitad

El primer entregable era medir, no construir, y cambió el alcance:
[`evidence/medicion-rematching-2026-07-29.md`](evidence/medicion-rematching-2026-07-29.md).

**Ya existía** (retirado del alcance, conservado como regresión): detectar el desfase
(`PlanFechasService::desfases()`), avisarlo en la pantalla del plan, no recalcular solo, conservar
`fecha_real` al recalcular, y no reamarrar solo un frente borrado.

**Apareció un bug que el spec no preveía:** «Recalcular» no recogía la fecha nueva del frente
—`amarres()` lee una copia congelada del ancla—, así que el desfase no se podía aplicar nunca y el
botón que la pestaña «Desfases» ofrecía no arreglaba lo que esa misma pestaña denunciaba. Corregido
en `13e6e31`.

**Construido:** simular la reprogramación sin escribir, aplicarla solo sobre lo confirmado, el panel
de delta con «Aplicar»/«Cancelar», y el aviso en el tablero de vencimientos.

**Verificado:** `tests/test_pdc_v2_reprogramacion.php` (nuevo), la regresión de la zona (9 tests PHP),
phpstan limpio, 267 tests de vitest, y el e2e `tests/browser/pdc-v2-plan.spec.mjs` con el recorrido
completo en navegador (simular no escribe · cancelar no escribe · aplicar corre 21 días · el conteo
de «Desfases» baja a 0). Rojo preexistente no relacionado, comprobado también sobre `1a75b19`:
`tests/browser/pdc-v2-sin-scroll-x.spec.mjs`.

### Nota sobre 7b — tres de cuatro diferidos

| # | Diferido | Estado |
|---|---|---|
| 2 | Copiar la configuración entre obras | **Hecho** (`efe8d5e`). Copia puntual, no vínculo vivo; la pantalla enseña qué trae y marca si el origen está a medias |
| 4 | Duraciones del catálogo editables | **Hecho** (`20d6acf`). Solo las filas que la obra usa, con aviso permanente de que son de la empresa. Recorte: el upsert ya existía en `/contratos`; lo que faltaba era llegar desde el PDC v2, con el permiso de reglas y recalculando |
| 3 | Historial de versiones | **Hecho** (`c725fc7`). Tabla de solo anexar; restablecer también deja rastro |
| 1 | Listas de pasos por modalidad | **ARCHIVADO el 2026-07-30.** Felipe preguntó a las dos obras: las modalidades siguen **el mismo proceso**. No se construye y no queda pendiente. Ver [`evidence/listas-por-modalidad-no-se-construye.md`](evidence/listas-por-modalidad-no-se-construye.md) |

**Límite conocido del nº 4, y su relación con los 42 paquetes sin `duracion_ref`**
([`evidence/paquetes-sin-duracion-ref.md`](evidence/paquetes-sin-duracion-ref.md)): la pantalla lista
las filas del catálogo por `JOIN … ON d.id = p.duracion_ref`, así que un paquete **sin** fila de
catálogo no aparece y su duración no se puede editar desde aquí. No es un descuido: no hay número que
editar — esos 42 reciben fechas por la mediana de su tipo, y darles un campo editable inventaría una
fila del catálogo de la empresa desde la pantalla de una obra. Cuando alguien mida uno de esos
procesos, hay que crear su fila y apuntar el paquete a ella; a partir de ahí sí es editable aquí.

**Verificado:** 14 tests PHP en verde (3 nuevos), phpstan limpio, 267 de vitest, y 3 e2e en
`tests/browser/pdc-v2-pasos.spec.mjs` (2 nuevos, en navegador contra el contenedor servido).
Cero regresión comprobada sobre Da Porto: sigue sin configurar y con los siete pasos por defecto.
De paso se corrigió que el reseteo del sandbox e2e no limpiaba `pdc_proyecto_pasos`.

## Ola 3 — lo grande

| # | Tarea | Espera a | Estado | Commit | Fecha |
|---|---|---|---|---|---|
| 8a | Subpaquetes de obra | 7a y 7b | EN CURSO (falta la pantalla de partir y repartir) | `6d702ef` (dominio, API y pruebas) | 2026-07-29 |
| 8b | Flujo de caja: curva mensual de desembolsos | 8a (reparte por subpaquete) | **HECHO** | `6d702ef` + `bfa0c7d` | 2026-07-30 |
| 9 | Torre de Control (B3) | 1 | PENDIENTE | | |
| 10 | Retiro del PDC viejo (C1) | 4 · **+ una obra trabajando de verdad en producción** | PENDIENTE | | |

---

## Dos avisos para quien espera

**El nº 4 y el nº 10 no se autorizan solos.** Tienen una puerta humana además de la técnica: el
despliegue necesita que el comunicado haya salido y que el usuario lo autorice en el momento; el retiro
necesita una obra trabajando de verdad. Que su predecesora diga `HECHO` **no basta** para arrancarlos.

**Qué cubre exactamente el `HECHO` del nº 3 (2026-07-29).** Sus puntos 1, 3, 4 y 5 están cerrados y
verificados con salida real de comandos, en
[`evidence/cierre-prelanzamiento-2026-07-29.md`](evidence/cierre-prelanzamiento-2026-07-29.md). Su
punto 2 (los 25 sin `duracion_ref`) es del nº 1, no de esta fila.

**Su punto 6 quedó sin contenido, por decisión explícita de Felipe.** Tomás no reportó hallazgos del
piloto, así que el hueco reservado se cierra vacío en vez de mantener la fila abierta esperándolo. Lo
que hay en [`hallazgos-piloto.md`](hallazgos-piloto.md) son cuatro observaciones propias de la sesión,
cada una con decisión: una arreglada, dos diferidas a la Ola 2, una descartada con motivo. **No es un
triage del piloto real.** Si Tomás reporta después, entra por la Ola 2 y no reabre esta fila.

**Dos cosas que el nº 4 tiene que saber antes de desplegar**, y que no se leen en la palabra `HECHO`:

- **La brecha del motor no la vigila nadie.** `test_pdc_v2_brecha_daporto` está rojo con causa escrita:
  su estado canónico (proyecto 73, versión 292) desapareció al reimportarse el presupuesto de Da Porto.
  Se dejó rojo a propósito en vez de repuntarlo a la versión viva, que lo habría dejado verde y hueco.
- **Dos e2e de Da Porto (`pdc-v2-modalidades`, `pdc-v2-sin-scroll-x`) alternan verde y rojo según lo
  que la obra haya cargado ese día.** Si los ves rojos, es el hallazgo H1, no una regresión.

**El nº 1 y el nº 3 se rozaban en un punto, y ya está resuelto.** Los paquetes sin `duracion_ref` eran de
la nº 1, no de la nº 3. **Cerrado el 2026-07-29:** no eran 25 sino 42, y ya recibían fechas solos por la
mediana de su tipo — ver [`evidence/paquetes-sin-duracion-ref.md`](evidence/paquetes-sin-duracion-ref.md).
El «25» venía del spec y era una cifra vieja: quien lo lea allí, que se fíe de esta medición.

**El nº 8 se partió en 8a y 8b. 8b está cerrada; 8a sigue EN CURSO.**

**8b — flujo de caja: HECHO.** Los seis puntos de su condición de hecho están cumplidos y verificados.
La pestaña «Flujo de caja» de Seguimiento se recorrió en Da Porto a 1180×820 en dark: 14 meses, la
suma de los meses igual al total del pie y al último acumulado ($6.192.372.106), cobertura del 87,4 %
del valor del plan, los $890.202.075 no contratables declarados arriba con su motivo, cero errores de
consola y sin desbordamiento horizontal. El CSV descarga con los mismos números, con BOM UTF‑8 y coma
decimal, y lleva la advertencia del método dentro del archivo. Matiz honesto sobre el punto 4: se
verificó el formato, las cabeceras de descarga y el contenido byte a byte, no se abrió Excel en esta
máquina.

**8a — subpaquetes: EN CURSO.** Dominio, API y pruebas terminados y verificados en `6d702ef` (40
asserts, la cero regresión comprobada fila a fila contra
`evidence/linea-base-plan-antes-subpaquetes.txt`). Lo que falta es **una sola pantalla**: partir un
paquete dándole nombre a sus lotes, mover insumos entre lotes, y ver el sombrilla con su rango y su
avance agregado. Sin ella no se cumplen los puntos 1 y 2 de su condición de hecho —«ver los tres en el
plan» y «el sombrilla muestra el rango»—; el punto 3, que el tablero de vencimientos liste lotes, ya
está en el servidor y probado, pero no se ha podido recorrer en pantalla porque nadie puede partir un
paquete todavía.

La API le deja el trabajo hecho a esa pantalla: `GET /plan-compras/api/subpaquetes?paqueteId=N`
devuelve los lotes con sus insumos, su valor y el resumen del sombrilla; `…/subpaquetes/destinos` trae
la unidad contratable con su etiqueta ya escrita; y `partir`, `agregar`, `actualizar`, `eliminar` y
`mover` cubren todas las acciones. `amarrar`/`desamarrar` aceptan `subpaqueteId` para darle a cada lote
su frente.
