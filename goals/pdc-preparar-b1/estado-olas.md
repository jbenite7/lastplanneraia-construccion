# Estado de las olas — acta de cierre

> **GOAL CERRADO el 2026-08-03.** Este archivo dejó de ser un tablero vivo. Nació para que diez sesiones
> independientes supieran cuándo les tocaba —preguntarle a una sesión «¿ya acabaste?» no es fiable, y esto
> convertía esa pregunta en un `git show` que cualquiera podía correr sola— y ahora es el registro de lo
> que pasó: quién cerró qué, con qué sha y con qué evidencia.
>
> **Nadie tiene que consultarlo ya para saber si puede empezar.** Lo que quedó fuera está abajo, en «Lo que
> no entró al cierre», con su motivo y su dueño.

**Lo que este montaje enseñó, por si sirve para el siguiente.** El mecanismo funcionó, pero tuvo dos
fallos de diseño que costaron tiempo y conviene no repetir:

1. **Un buzón que nadie podía llenar.** A las diez sesiones se les dijo «no commitees sin permiso» y a la
   vez «marca `HECHO`, que es un commit». Una sesión honesta se paró en seco durante una hora, y con
   razón. La regla de permisos de abajo nació de ahí.
2. **Un permiso relatado no es un permiso.** Cuando se le transmitió esa autorización de sesión a sesión,
   la receptora la rechazó: no podía distinguirla de una inventada. Tenía razón, y por eso quedó escrita
   en el repositorio en vez de en un mensaje.

Y una lección que no es del mecanismo sino del trabajo: **cuatro premisas escritas en los specs resultaron
falsas al medirlas** —el BI de la Torre no era público, `/api/pdc/*` no era del módulo viejo, la jerarquía
del cronograma estaba en los datos y no había que deducirla, y `/pdc` no se pintaba en claro—. Las cuatro
las desmintió quien fue a comprobarlas antes de construir. Ese hábito es lo que evitó romper dos módulos
sanos.

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
| 5 | Equipo alquilado vs comprado | 4 | **HECHO** | `e992301` | 2026-07-29 |
| 6 | Ayuda dentro de la aplicación | 1 y 2 (necesita las pantallas terminadas) | **HECHO** — 8 pantallas con ayuda, recorrido omitible, 13 e2e en verde y la lectura del revisor dada por cumplida por Felipe | `5e80112` (serie `b4294f3`…`5e80112`) | 2026-07-30 |
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

### Nota sobre la fila 5 — no esperó al despliegue, y aparecieron dos cosas

Arrancó sin la fila 4 en `HECHO`: Felipe liberó la espera el 2026-07-29 (el despliegue a pruebas ya
estaba hecho y pidió que las sesiones avanzaran sin quedarse en comprobaciones humanas). Se entrega la
migración, el código y las pruebas en la rama; **no se aplicó nada al servidor** — eso es de la fila 4.

Evidencia completa, con salida real de comandos:
[`evidence/validacion-equipo-alquilado-comprado.md`](evidence/validacion-equipo-alquilado-comprado.md).

**El spec decía «amplía el enum de `tipo_recurso`» y no hay enum:** es `varchar(60)` que siembra el
importador SINCO. No hubo DDL de enum. Los dos riesgos reales estaban en otro sitio, y los dos se
midieron antes de arreglarlos: `PaquetesService::tiposCompatibles()` tiene un `default` que significa
«no filtro» (partir «Equipo» sin nombrar los valores nuevos ahí los volvía candidatos de cualquier
paquete — la trampa de A3.2 en sitio nuevo), y el importador que borraba el trabajo humano era el de
**SINCO**, no el de presupuestos.

**Dos decisiones que quedan para Felipe:**

1. **`OT` (Oficina Técnica / Compras) ya puede clasificar equipos** — Felipe dijo que sí el
   2026-07-30, y quedó aplicado. `lps.pdc.maestro` pasa a A, D y OT. **Consecuencia que hay que
   saber:** la capacidad es única y abre TODO el maestro (clasificar, crear a mano, vincular,
   retirar/reactivar e importar el Excel de SINCO), no sólo clasificar. Se asumió porque OT ya tenía
   `paquetes_contratacion.reglas`, que redirige insumos en todos los proyectos. Si el alcance resulta
   ser demasiado, la vuelta es partir la capacidad en dos, no revertir el permiso.
2. **Gastos generales: aprobado, va a sesión propia.** Felipe dijo que sí el 2026-07-30. El presupuesto
   **no** trae categorías que el maestro pierda (sus capítulos son sólo `COSTO DIRECTO` y `COSTO
   INDIRECTO`); las de Tomás llegan por el `agrupacion` de SINCO, que el maestro **ya guarda** y ninguna
   pantalla agrupa ni filtra. No se construye aquí: es un entregable distinto y necesita grilleo con
   Tomás para no duplicar lo que él ya tiene en su código.

⚠️ **Aviso para las demás sesiones — el volumen de MySQL del compose es `external` con nombre fijo
`htdocs_db_data`.** Un `COMPOSE_PROJECT_NAME` propio **no** da base propia: levanta un segundo MySQL
sobre los archivos de la base de desarrollo principal. Aquí murió con «Unable to lock ./ibdata1» porque
el principal estaba vivo (sin daño, verificado), pero **con el principal apagado le habría escrito**.
Hace falta un override local que declare un volumen propio.

## Ola 3 — lo grande

| # | Tarea | Espera a | Estado | Commit | Fecha |
|---|---|---|---|---|---|
| 8a | Subpaquetes de obra | 7a y 7b | **HECHO** | `6d702ef` · `935d194` · `ceb0e73` + el amarre por lote | 2026-07-30 |
| 8b | Flujo de caja: curva mensual de desembolsos | 8a (reparte por subpaquete) | **HECHO** | `6d702ef` + `bfa0c7d` | 2026-07-30 |
| 9 | Torre de Control (B3) | 1 | HECHO | `e610fbb` (rama `worktree-pdc-b3-torre-control`) | 2026-07-30 |
| 10 | Retiro del PDC viejo (C1) | — | **SALE DEL GOAL** — trabajo previo cerrado; la ejecución va a chip propio, autorizada por Felipe el 2026-08-03 | censo, medición y manifiesto en `main` | 2026-08-03 |

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

**8a — subpaquetes: HECHO.** Los siete puntos de su condición de hecho están cumplidos y verificados
en pantalla, no solo en tests.

Recorrido completo en Da Porto, con el paquete real «Suministro CONCRETO»:

1. Se partió en tres lotes desde la pantalla; nació el «Resto» automático con los insumos que nadie
   movió.
2. Se le repartieron insumos con casillas: un insumo a «Premezclado 3000», otro a «Premezclado 4000»,
   y el «Resto» quedó con cinco. Los valores suman el total del paquete.
3. En «Sin frente», cada lote apareció como **fila propia** rotulada «Suministro CONCRETO ›
   Premezclado 3000», con su propio desplegable de frente. Se les dio **frentes distintos**
   (PRELIMINARES y REDES) y al amarrar uno **su hermano no desapareció de la lista**.
4. Tras recalcular, los tres lotes salen en el plan con **tres anclas distintas**: 2026-05-25,
   2026-08-18 y 2027-03-16, cada uno con su propia fecha de arranque.
5. Se deshizo la partición borrando los tres lotes: el paquete se despartió solo y **la foto del plan
   volvió a ser idéntica a la línea base**. El punto 4 comprobado de ida y de vuelta, con fechas
   propias y recálculo en medio, no solo en reposo.

**La forma del amarre por lote se decidió antes de escribirla.** No es un segundo desplegable en la
fila: la lista «Sin frente» pasó a enumerar **unidades contratables** en vez de paquetes, así que cada
fila ya *es* un lote y solo le falta su frente. Esa fila ya llevaba el frente, la procedencia de la
sugerencia y el botón de amarrar; una segunda elección dentro de ella obligaría a leer dos controles
para entender una decisión.

**Un fallo silencioso que apareció al hacerlo, y que ningún tipo detectaba:** `preseleccionDestinos()`
seguía indexando por id de paquete mientras la pantalla ya leía «paquete:lote», así que la preselección
del motor dejó de aplicarse **sin que TypeScript dijera nada** —un `Record<number, T>` es asignable a
un `Record<string, T>`—. Corregido, con un test que lo fija.

**Dos límites escritos, no supuestos.** El motor sigue sugiriendo por paquete y no por lote (no
aprende de lotes, y preseleccionar la sugerencia del paquete en sus tres lotes les daría a los tres el
mismo frente, lo contrario de lo que se busca). Y un lote **sin insumos** no aparece como destino
contratable: no tiene valor que repartir ni nada que contratar, así que tampoco se le ofrece frente.

**El volumen ya está estresado** (2026-07-30). `tests/test_pdc_v2_subpaquetes_volumen.php` fabrica la
escala real del módulo —96 paquetes, 384 insumos, 12 partidos en 3 lotes cada uno = **132 destinos
contratables**— y comprueba que la unidad se sostiene: ningún destino repetido, una cabecera y
exactamente siete pasos por destino (924 filas, ni una mezclada), recalcular no duplica, y las tres
vistas que consumen la unidad —seguimiento, vencimientos y curva de caja— cuentan cada cosa una vez.
Medición: partir y repartir 0,41 s · amarrar 1,14 s · **calcular el plan de 132 destinos, 1,30 s**.

Y se comprobó que el test **sabe fallar**: rompiendo a propósito la unión por lote, el tablero pasó de
924 pasos a 1.932. Con ese mismo sabotaje el test pequeño seguía **en verde**, porque comprobaba
nombres y no totales — así que se le añadió también la aserción de conteo. Un test de regresión que
nunca se ha visto fallar no prueba nada.

La API que sostiene todo esto: `GET /plan-compras/api/subpaquetes?paqueteId=N`
devuelve los lotes con sus insumos, su valor y el resumen del sombrilla; `…/subpaquetes/destinos` trae
la unidad contratable con su etiqueta ya escrita; y `partir`, `agregar`, `actualizar`, `eliminar` y
`mover` cubren todas las acciones. `amarrar`/`desamarrar` aceptan `subpaqueteId` para darle a cada lote
su frente.

### Nota sobre la nº 6 — qué se construyó y cómo se verificó

Está **construido y commiteado** en `worktree-pdc-ola2-ayuda-in-app`: las ocho pantallas con su
botón, el contenido de las ocho ayudas, el recorrido de seis paradas con su memoria por usuario, el
e2e escrito y la regla de proceso en `DESIGN.md` y `docs/pdc-v2.md`.

**Verificado:** 362 tests de vitest en verde (25 archivos, 28 nuevos entre `ayuda.test.ts` y
`recorrido.test.ts`), tipos limpios, bundle recompilado a `public/pdc-app/`.

Las dos verificaciones que estaban aplazadas **están hechas (2026-07-30)**, y con eso la fila cierra:

1. ~~Correr los 6 e2e de `pdc-v2-ayuda.spec.mjs`.~~ **Corridos y en verde: 6 de 6**, sobre este árbol
   servido en `http://localhost:8083` (contenedor propio con la imagen del servicio `app`, red del
   stack principal y la misma base de datos). La primera pasada dio **2 fallos, y eran del
   andamiaje, no del módulo**: el helper `permitirRecorrido` borraba la memoria del recorrido con un
   `addInitScript`, que vuelve a correr **en cada navegación** — también en la recarga que la prueba
   usa para comprobar que la decisión se recuerda. Arreglado donde correspondía: `loginAndSelectProject`
   acepta `{ silenciarRecorrido: false }` y los dos tests entran así, sin escribir ni borrar nada
   después. La persistencia que se mide vuelve a ser la de la aplicación.
   **Regresión de la zona, también en verde:** los 7 casos de `pdc-v2-plan`, `pdc-v2-vencimientos` y
   `pdc-v2-maestro` — el silenciador llega a todos los e2e del PDC y ningún modal tapa un clic.
   **Rojo preexistente, ajeno a esto:** `pdc-v2-sin-scroll-x.spec.mjs` falla igual con estos cambios
   revertidos (comprobado por `git stash` en la misma sesión).
2. ~~La condición de hecho nº 3 del spec — un revisor que no conoce el módulo lee las ayudas y
   recorre el flujo sin preguntar.~~ **Dada por cumplida por Felipe el 2026-07-30.** Es el hecho de
   verdad del entregable y no lo cubre ningún test: los tests atrapan la pantalla sin ayuda y la
   jerga, no atrapan una explicación que no explica. Por eso lo cierra una persona y no una tubería,
   y por eso queda escrito **quién** lo cerró y cuándo.

**Lo que esta fila NO garantiza hacia el futuro.** Que las ayudas fueran verdad el 2026-07-30 no
dice nada de mañana. Lo único que lo sostiene es la regla escrita en `DESIGN.md` §Do y en
`docs/pdc-v2.md`: **una pantalla no se cierra sin su ayuda, y cambiarla cuenta como cerrarla otra
vez.** Ya se aplicó una vez con resultado (el número de pendientes resueltos del Maestro, `a62d619`,
donde el cambio de pantalla arrastró su texto en el mismo commit). Quien toque una pantalla del PDC
y no toque su entrada en `pdc-app/src/lib/ayuda.ts` está dejando el cambio a medias.

**Cambio de alcance medido, no supuesto:** el spec decía «nueve pantallas» mezclando páginas con
pestañas. El inventario contra el código son **8 páginas y 13 pestañas**, y el usuario decidió un
botón por página con apartados dentro. Subpaquetes queda sin ayuda a propósito (fila 8a `EN CURSO`).

---

## Lo que NO entró al cierre

Dos cosas quedan vivas fuera de este goal —la 2 y la 3—. Están aquí para que nadie las descubra por
sorpresa. La 1 se conserva tachada porque su letrero costó una sesión.

### 1 · Dos pantallas cuyo motor ya está hecho — RESUELTO, no era pendiente

**Corregido el 2026-08-04.** Esta fila decía que faltaba la vista de las dos pantallas, y era falso
cuando se escribió: las dos ya estaban en `main`. El letrero colgado mandó a una sesión a construir lo
construido; se deja escrito en vez de borrarlo porque el fallo fue del acta, no del código.

| Qué | Dónde está | Commit |
|---|---|---|
| **Curva de flujo de caja** | Pestaña «Flujo de caja» de Seguimiento: nota del método sin plegar, aviso provisional, excluidos, botón de exportar y tabla mensual con las tres columnas de origen | `4a6c88d5` |
| **Aviso «N frentes sin ancla»** | `avisoFrentesSinAncla()` en `pdc-app/src/lib/planFechas.ts`, hermano de `motivoSinAnclas()`, pintado en `PlanFechas.tsx` | `6f3f379d` |

Ambas montan su ayuda (`4680dc4d`). Comprobado el 2026-08-04 con `vitest` sobre `flujoCaja.test.ts`,
`planFechas.test.ts` y `ayuda.test.ts`: 142 asserts en verde.

### 2 · El retiro del PDC viejo (C1) — chip propio, ya autorizado

**Felipe lo autorizó el 2026-08-03**, y su criterio se sostiene por un hecho que la precondición original
no contemplaba: **el roadmap suponía que producción seguía de cerca a `main`, y no es así.** La producción
real está en `1aa7c69` del 16 de julio, sin una sola tabla `pdc_*`. Retirar el PDC viejo en `main` hoy no
le quita nada a nadie allí: cuando esa rama llegue, llegarán el retiro y el v2 en el mismo envío.

El riesgo residual es pequeño por dos decisiones ya tomadas: **C1 no toca tablas** —los 370 registros
históricos se conservan, decidido el 2026-07-30— y su alcance quedó recortado a código y rutas, con
`/api/pdc/auto/*` y `OperationalFamilyPolicy` **fuera** porque los consumen Contratos y Listado de
Actividades.

**Lo único que sí conviene preguntar antes:** en `prueba-lps` el PDC viejo está vivo y accesible por su
dirección. Si alguien de Da Porto o del aeropuerto lo abrió alguna vez, el retiro se lo quita sin aviso.

### 3 · El despliegue a la producción real

La fila 4 cerró con alcance explícito, «solo `prueba-lps`», y esa redacción fue deliberada: un `HECHO` a
secas habría arrancado la fila 10 sobre un supuesto falso. El envío a producción sigue pendiente, arrastra
~500 commits, y tiene su rutina escrita y su orden de migraciones medido en
`docs/siteground-deploy-routine.md`.

**Con dos avisos que ya están en esa rutina y no se pueden perder:** no ejecutar
`20260712_remap_consolidado_unique_id.php` sin decidir antes la contradicción de los frentes, y que
`20260729_pdc_v2_subpaquetes.sql` solo se aplica con el cliente `mysql`, nunca por PDO.

### Una verificación que sigue sin poder hacerse aquí

El arreglo de los frentes está en `main`, pero **la base local no puede reproducir el caso**: tiene dos
triggers no versionados que rellenan `unique_id` en cuanto llega nulo (0 de 53.705 filas en NULL). Solo se
comprueba en `prueba-lps`, y **después** del remap. El test lo detecta y se declara omitido en vez de dar
por probado lo que no probó.
