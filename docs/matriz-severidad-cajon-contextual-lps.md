---
capa: fuente
tipo: guia
estado: vigente
fecha: 2026-05-22
areas: [lps]
fuente: docs/matriz-severidad-cajon-contextual-lps.md
resumen: Definir una matriz unica para diagnosticar, colorear y escalar actividades en el Cajon Contextual LPS de Programa General (PG), Programacion Intermedia (PI) y…
---

# Matriz de severidad del Cajon Contextual LPS

## Objetivo

Definir una matriz unica para diagnosticar, colorear y escalar actividades en el Cajon Contextual LPS de Programa General (PG), Programacion Intermedia (PI) y Programacion Semanal (PS).

Esta matriz debe evitar dos fallas opuestas:

- Sobredimensionar alertas futuras, especialmente actividades a 4-6 semanas.
- Minimizar bloqueos reales de ruta critica cuando la actividad debe iniciar, ya inicio o esta incumplida.

## Fuente de marca

Referencia obligatoria: `docs/manual-de-marca-aia-con-logos.pdf`.

Lineamientos aplicados:

- Usar color con intencion operativa, no decorativa.
- Mantener maximo dos colores principales por pieza, mas neutros.
- Reservar rojo para errores, bloqueos destructivos o crisis reales.
- Usar amarillo para advertencias y atencion requerida.
- Usar verde AIA para control, cumplimiento y seguimiento normal.
- Usar naranja de construccion solo como acento contextual de obra, no como reemplazo de rojo critico.
- No usar blanco puro ni negro puro como colores principales de estado.

## Paleta operativa autorizada

| Severidad | Uso | Color principal | Fondo recomendado | Texto/borde recomendado | Sidebar |
| --- | --- | --- | --- | --- | --- |
| `normal` | Control, liberada, cumplida, seguimiento rutinario | Verde AIA `#1a5633` | Verde muy claro `#d5e5db` o Alabaster `#fafafa` | Verde oscuro `#1a3c2a` | Verde AIA, sin badge |
| `attention` | Riesgo gestionable, datos pendientes, habilitacion incompleta sin crisis | Advertencia `#ffca28` | Advertencia muy claro `#fff8e1` | Advertencia oscuro `#a0731a` | Amarillo/ambar, badge discreto |
| `critical` | SOS, bloqueo actual/vencido de ruta critica, incumplimiento critico | Alerta `#e53935` | Alerta muy claro `#fdecec` | Alerta oscuro `#9a1f1f` | Rojo, badge visible |
| `neutral` | Capitulo, no requerida, no activa, informacion sin accion | Gris claro `#eaeaea` | Alabaster `#fafafa` | Texto secundario `#4a4a4d` | Verde AIA, sin badge |
| `info` | Ayuda contextual, restriccion blanda, actividad futura sin riesgo | Azul arquitectura `#4a81bd` | Azul muy claro `#e6f0fa` | Azul oscuro `#2a5a8f` | Verde AIA, sin badge |
| `construction` | Acento visual para gestion de obra, no severidad | Naranja construccion `#b55211` | Naranja muy claro `#fbead9` | Naranja oscuro `#8b4011` | No aplica |

Nota: `construction` es un acento de dominio. No debe decidir si el sidebar parpadea o si se muestra escalamiento directivo.

## Orden de decision global

La severidad se resuelve en este orden. La primera regla que aplique gana.

| Orden | Regla | Severidad | Color |
| --- | --- | --- | --- |
| 1 | `alerta_crisis = 1` o SOS manual activo | `critical` | Alerta `#e53935` |
| 2 | Fila de capitulo/header | `neutral` | Gris claro `#eaeaea` |
| 3 | PS en fase semanal | Usar matriz PS | Segun estado PS |
| 4 | PG/PI con bloqueo actual/vencido de ruta critica | `critical` | Alerta `#e53935` |
| 5 | PG/PI con riesgo gestionable no critico | `attention` | Advertencia `#ffca28` |
| 6 | Actividad liberada, terminada, cumplida o sin accion abierta | `normal` | Verde AIA `#1a5633` |
| 7 | Sin evidencia suficiente para escalar | `neutral` | Gris claro `#eaeaea` |

## Reglas de restricciones habilitantes

Las restricciones duras definen si una actividad esta lista para comprometerse o iniciar. Las restricciones blandas son informacion de gestion y no bloquean inicio.

### Restricciones duras

| Restriccion | Alias esperados | Umbral de liberacion | Valor incompleto | Color incompleto | Color liberado |
| --- | --- | ---: | --- | --- | --- |
| Disenos y especificaciones | `D_y_E`, `restr_D_y_E` | `100%` | `<100%` | Advertencia `#ffca28` | Verde AIA `#1a5633` |
| Materiales | `Materiales`, `restr_Materiales` | `100%` | `<100%` | Advertencia `#ffca28` | Verde AIA `#1a5633` |
| Mano de obra | `MdeO`, `restr_MdeO` | `100%` | `<100%` | Advertencia `#ffca28` | Verde AIA `#1a5633` |
| Equipos | `Equipos`, `restr_Equipos` | `100%` | `<100%` | Advertencia `#ffca28` | Verde AIA `#1a5633` |
| Predecesora | `Predecesora`, `restr_Predecesora` | `50%` | `<50%` | Advertencia `#ffca28` | Verde AIA `#1a5633` |

### Restricciones blandas

| Restriccion | Alias esperados | Efecto en ITR habilitante | Color si pendiente | Color si completa |
| --- | --- | --- | --- | --- |
| Procedimiento constructivo | `Pdto_Cons`, `restr_Pdto_Cons` | No bloquea | Azul info `#4a81bd` o advertencia suave `#fff8e1` | Verde AIA `#1a5633` |
| Modelo BIM | `Modelo`, `restr_Modelo` | No bloquea | Azul info `#4a81bd` o advertencia suave `#fff8e1` | Verde AIA `#1a5633` |

### Normalizacion de valores

| Entrada | Ratio esperado | Caso | Color |
| --- | ---: | --- | --- |
| `100%`, `100`, `1`, `1.0`, `1,0` | `1.00` | Liberada para restricciones con umbral 100% | Verde AIA `#1a5633` |
| `66%`, `66`, `0.66`, `0,66` | `0.66` | Avance parcial; aun pendiente si umbral es 100% | Advertencia `#ffca28` |
| `50%`, `50`, `0.5`, `0,5` | `0.50` | Predecesora liberada; otras duras siguen pendientes | Verde para Predecesora, advertencia para otras |
| `33%`, `33`, `0.33`, `0,33` | `0.33` | Avance bajo | Advertencia `#ffca28` |
| `0%`, `0`, `0.0`, `0,0` | `0.00` | Pendiente total | Advertencia `#ffca28` |
| `N/A`, `NA`, `NO APLICA` | No aplica | Excluir del denominador de liberacion | Neutral `#eaeaea` |
| Vacio en columna existente | `0.00` | Pendiente por completar | Advertencia `#ffca28` |
| Campo inexistente en el modulo | No aplica | No usar para castigar el modulo | Neutral `#eaeaea` |

## Matriz PG: Programa General

PG usa el horizonte temporal y la ruta critica. Las restricciones informan riesgo, pero la actividad a 4-6 semanas no debe ser crisis por estar incompleta.

Variables:

- `SI`: `Semanas_Inicio`.
- `EJ`: `Ejecutado` normalizado `0..1`.
- `RC`: `Ruta_Critica`.
- `Ready`: todas las restricciones duras cumplen su umbral.
- `DeepGap`: alguna restriccion dura esta bajo `66%`, o `Predecesora < 50%`.

### Estados base PG

| Estado PG | Condicion resumida | Severidad | Color representativo | Sidebar | Accion |
| --- | --- | --- | --- | --- | --- |
| `header` | Fila de capitulo | `neutral` | Gris claro `#eaeaea` | Sin badge | Seleccionar actividad real |
| `no-requerida` | Fuera del horizonte operativo | `neutral` | Gris claro `#eaeaea` | Sin badge | Sin accion LPS inmediata |
| `terminada` | `EJ >= 99.9%` | `normal` | Verde AIA `#1a5633` | Sin badge | Mantener registro |
| `adelantada` | Avance sobre programa | `normal` | Verde AIA `#1a5633` | Sin badge | Sostener ritmo |
| `en-curso` | Avance iniciado y no atrasado | `normal` | Naranja construccion `#b55211` como acento, sin severidad | Sin badge | Control rutinario de obra |
| `actividad-futura` | `SI` entre 1 y 6, sin inicio | Ver matriz temporal | Azul info `#4a81bd` si no hay riesgo | Segun matriz temporal | Preparar restricciones |
| `debe-iniciar` | `SI = 0`, sin inicio | Ver matriz temporal | Advertencia o alerta | Segun matriz temporal | Validar arranque hoy |
| `atrasada` | `SI < 0` o avance bajo teorico, no RC | `attention` | Advertencia `#ffca28` | Badge ambar | Recuperacion operativa |
| `atrasada-critica` | Atrasada y `RC = true` | `critical` | Alerta `#e53935` | Badge rojo | Escalar recuperacion |

### Ventana temporal PG por restricciones

| Caso | Condicion | Severidad | Color representativo | Sidebar | Escalamiento |
| --- | --- | --- | --- | --- | --- |
| PG-SOS | `alerta_crisis = 1` | `critical` | Alerta `#e53935` | Rojo | Si |
| PG-R0-RC | `SI <= 0`, `RC = true`, `Ready = false`, `EJ < 99.9%` | `critical` | Alerta `#e53935` | Rojo | Si |
| PG-R0-NoRC | `SI <= 0`, `RC = false`, `Ready = false`, `EJ < 99.9%` | `attention` | Advertencia `#ffca28` | Ambar | No directivo por defecto |
| PG-R1-RC | `SI = 1`, `RC = true`, `Ready = false` | `attention` | Advertencia `#ffca28` | Ambar | Preparar destrabe urgente |
| PG-R1-NoRC | `SI = 1`, `RC = false`, `Ready = false` | `attention` | Advertencia `#ffca28` | Ambar | Gestion semanal |
| PG-R2-3-DeepGap | `SI in [2,3]`, `Ready = false`, `DeepGap = true` | `attention` | Advertencia `#ffca28` | Ambar | Cerrar brechas fuertes |
| PG-R2-3-Partial | `SI in [2,3]`, `Ready = false`, `DeepGap = false` | `normal` preventivo | Verde AIA `#1a5633` con nota | Sin badge | Seguimiento lookahead |
| PG-R4-6 | `SI in [4,6]`, `Ready = false` | `normal` preventivo | Azul info `#4a81bd` o verde control | Sin badge | Planificar sin escalar |
| PG-ReadyFuture | `SI > 0`, `Ready = true` | `normal` | Verde AIA `#1a5633` | Sin badge | Lista para preparar compromiso |
| PG-ReadyDueNoStart | `SI <= 0`, `Ready = true`, `EJ = 0` | `attention` | Advertencia `#ffca28` | Ambar | Validar arranque real |
| PG-StartedBlocked | `EJ > 0`, `Ready = false` | `attention`; `critical` si RC y impacto real | Advertencia `#ffca28` o alerta `#e53935` | Ambar o rojo | No comprometer mas produccion sin cerrar |

## Matriz PI: Programacion Intermedia

PI usa la ventana de 6 semanas con mas detalle que PG. El sidebar no debe pintar rojo por el simple hecho de que una actividad futura este incompleta.

Variables:

- `SI`: `Semanas_Inicio`.
- `EJ`: `Ejecutado` normalizado `0..1`.
- `RC`: `Ruta_Critica`.
- `Ready`: restricciones duras liberadas.
- `DeepGap`: alguna restriccion dura bajo `66%`, o `Predecesora < 50%`.

| Estado PI | Condicion | Severidad | Color representativo | Sidebar | Accion |
| --- | --- | --- | --- | --- | --- |
| `header` | Capitulo | `neutral` | Gris claro `#eaeaea` | Sin badge | Seleccionar actividad |
| `neutral` | Sin accion abierta | `neutral` | Gris claro `#eaeaea` | Sin badge | Control |
| `liberated-control` | `Ready = true` | `normal` | Verde AIA `#1a5633` | Sin badge | Lista para comprometer |
| `blocked-overdue-critical` | `SI < 0`, `RC = true`, `Ready = false`, `EJ = 0` | `critical` | Alerta `#e53935` | Rojo | Escalar destrabe inmediato |
| `blocked-overdue` | `SI < 0`, `RC = false`, `Ready = false`, `EJ = 0` | `attention` | Advertencia `#ffca28` | Ambar | Destrabe en 24-48h |
| `blocked-due` RC | `SI = 0`, `RC = true`, `Ready = false`, `EJ = 0` | `critical` | Alerta `#e53935` | Rojo | Bloqueo de inicio hoy |
| `blocked-due` No RC | `SI = 0`, `RC = false`, `Ready = false`, `EJ = 0` | `attention` | Advertencia `#ffca28` | Ambar | No cerrar jornada sin plan |
| `alert-1-week` RC | `SI = 1`, `RC = true`, `Ready = false` | `attention` alta | Advertencia `#ffca28` | Ambar | Cerrar habilitantes esta semana |
| `alert-1-week` No RC | `SI = 1`, `RC = false`, `Ready = false` | `attention` | Advertencia `#ffca28` | Ambar | Gestion normal priorizada |
| `alert-2-3-weeks` con brecha fuerte | `SI in [2,3]`, `DeepGap = true` | `attention` | Advertencia `#ffca28` | Ambar | Enfocar restricciones bajo 66% |
| `alert-2-3-weeks` sin brecha fuerte | `SI in [2,3]`, `Ready = false`, `DeepGap = false` | `normal` preventivo | Verde AIA `#1a5633` con nota | Sin badge | Seguimiento lookahead |
| `alert-4-6-weeks` | `SI in [4,6]`, `Ready = false` | `normal` preventivo | Azul info `#4a81bd` | Sin badge | Preparacion temprana |
| `execution-blocked` RC | `0 < EJ < 99.9%`, `RC = true`, `Ready = false` | `critical` si afecta inicio/continuidad | Alerta `#e53935` | Rojo | Escalar continuidad de frente |
| `execution-blocked` No RC | `0 < EJ < 99.9%`, `RC = false`, `Ready = false` | `attention` | Advertencia `#ffca28` | Ambar | Cerrar restricciones antes de ampliar produccion |
| Inicio liberado sin avance | `SI <= 0`, `Ready = true`, `EJ = 0` | `attention` | Advertencia `#ffca28` | Ambar | Verificar arranque real |

## Matriz PS: Programacion Semanal

PS no debe usar reglas de semanas. PS se decide por compromiso, fase semanal, ruta critica, restricciones duras, cumplimiento real, CNC y CNP.

Variables:

- `Critica`: actividad de ruta critica cuando `Critica >= 1`.
- `Compromiso`: cantidad comprometida semanal.
- `Ejecutado`: avance acumulado.
- `Ejecutado_Real`: cantidad real en calificacion.
- `Ready`: restricciones duras liberadas.
- `Activa`: actividad programable. `NA`, `0`, `NO`, `N`, `FALSE` indican no activa.

### Fase programacion

| Estado PS | Condicion | Severidad | Color representativo | Sidebar | Escalamiento |
| --- | --- | --- | --- | --- | --- |
| `ps-no-activa` | `Activa` no programable o actividad completa | `neutral` | Gris claro `#eaeaea` | Sin badge | No |
| `prog-bloqueo-critico-sin-compromiso` | `Critica >= 1`, `Ready = false`, actividad activa | `critical` | Alerta `#e53935` | Rojo | Si |
| `prog-ejecucion-con-restricciones` RC | `Ejecutado > 0`, `Ready = false`, `Critica >= 1` | `critical` | Alerta `#e53935` | Rojo | Si |
| `prog-ejecucion-con-restricciones` No RC | `Ejecutado > 0`, `Ready = false`, `Critica < 1` | `attention` | Advertencia `#ffca28` | Ambar | No directivo por defecto |
| `prog-condiciones-pendientes` | `Ready = false`, sin ejecucion, no RC | `attention` | Advertencia `#ffca28` | Ambar | No directivo por defecto |
| `prog-sin-compromiso` | `Ready = true` y falta `Compromiso > 0`, `Responsable_AIA` o `Sub_Contratista` | `attention` | Advertencia `#ffca28` | Ambar | No, pendiente operativo |
| `prog-lista-para-confirmar` | `Ready = true`, `Compromiso > 0`, responsable y subcontratista definidos | `normal` | Verde AIA `#1a5633` | Sin badge | No |
| SOS manual | `alerta_crisis = 1` | `critical` | Alerta `#e53935` | Rojo | Si |

### Fase calificacion

| Estado PS | Condicion | Severidad | Color representativo | Sidebar | Escalamiento |
| --- | --- | --- | --- | --- | --- |
| `cal-sin-calificar` | Falta `Ejecutado_Real` o no hay base para evaluar | `attention` | Advertencia `#ffca28` | Ambar | No directivo por defecto |
| `cal-incumplida-critica` | `Ejecutado_Real < Compromiso`, `Critica >= 1` | `critical` | Alerta `#e53935` | Rojo | Si |
| `cal-incumplida` | `Ejecutado_Real < Compromiso`, `Critica < 1` | `attention` | Advertencia `#ffca28` | Ambar | No directivo por defecto |
| `cal-cumplida-control` | `Ejecutado_Real >= Compromiso` | `normal` | Verde AIA `#1a5633` | Sin badge | No |
| SOS manual | `alerta_crisis = 1` | `critical` | Alerta `#e53935` | Rojo | Si |

### CNP en PS

| Caso CNP | Condicion | Severidad | Color representativo | Accion |
| --- | --- | --- | --- | --- |
| CNP no requerida | Actividad activa o manual sin necesidad de CNP | `neutral` | Gris claro `#eaeaea` | No mostrar como bloqueo |
| CNP pendiente | `Activa` indica no programada y falta categoria/causa | `attention` | Advertencia `#ffca28` | Registrar CNP antes del cierre |
| CNP parcial | Hay algun dato de CNP, pero falta categoria o causa | `attention` | Advertencia `#ffca28` | Completar CNP |
| CNP completa | Categoria y causa registradas | `normal` documental | Verde AIA `#1a5633` | Mantener evidencia |
| CNP conflictiva | Actividad activa con CNP diligenciada | `attention` | Advertencia `#ffca28` | Revisar consistencia |

### CNC en PS

| Caso CNC | Condicion | Severidad | Color representativo | Accion |
| --- | --- | --- | --- | --- |
| CNC no requerida | `Ejecutado_Real >= Compromiso` | `normal` | Verde AIA `#1a5633` | No pedir CNC |
| CNC pendiente RC | `Ejecutado_Real < Compromiso`, `Critica >= 1`, falta CNC | `critical` | Alerta `#e53935` | Registrar CNC y plan de recuperacion hoy |
| CNC pendiente No RC | `Ejecutado_Real < Compromiso`, `Critica < 1`, falta CNC | `attention` | Advertencia `#ffca28` | Registrar CNC y plan correctivo |
| CNC parcial | Hay algun dato de CNC, pero falta categoria o causa | Mantener severidad del incumplimiento | Alerta o advertencia segun criticidad | Completar CNC |
| CNC completa RC | Incumplimiento RC documentado | `critical` hasta recuperar | Alerta `#e53935` | Ejecutar recuperacion |
| CNC completa No RC | Incumplimiento no RC documentado | `attention` hasta recuperar | Advertencia `#ffca28` | Seguimiento correctivo |
| CNC conflictiva | CNC diligenciada aunque no parece requerida | `attention` | Advertencia `#ffca28` | Revisar datos |

## Matriz de sidebar y badges

| Severidad | Clase propuesta | Badge | Color base | Comportamiento |
| --- | --- | --- | --- | --- |
| `normal` | Sin clase extra | Oculto | Verde AIA `#1a5633` | Estado estable |
| `neutral` | Sin clase extra | Oculto | Verde AIA `#1a5633` | Estado sin accion |
| `info` | Sin clase extra | Oculto | Verde AIA `#1a5633` | Informativo |
| `attention` | `.has-attention` | `!` o indicador ambar | Advertencia `#ffca28` con texto `#a0731a` | No debe usar pulso de crisis |
| `critical` | `.has-crisis` | Indicador critico | Alerta `#e53935` con texto `#9a1f1f` | Pulso o badge visible permitido |

Regla: `Por Comprometer`, `Condiciones Pendientes`, `Inicio Vencido` no critico y `alert-4-6-weeks` no deben usar `.has-crisis`.

## Mensajes de diagnostico esperados

| Severidad | Tono del mensaje | Verbo operativo | Ejemplo |
| --- | --- | --- | --- |
| `normal` | Control y continuidad | Mantener, verificar, preparar | Actividad liberada y lista para compromiso confiable |
| `attention` | Gestion prioritaria sin alarma directiva | Completar, validar, cerrar, registrar | Pendiente operativo: definir compromiso y responsables |
| `critical` | Crisis o bloqueo real | Escalar, recuperar, intervenir | Ruta critica bloqueada para inicio actual o vencido |
| `neutral` | Informativo | Seleccionar, revisar si aplica | Fila de capitulo o actividad no requerida |

## Casos limite que deben probarse

| Caso | Datos minimos | Resultado esperado | Color |
| --- | --- | --- | --- |
| PI futura 6 semanas incompleta | `SI = 6`, `RC = true`, `Ready = false` | Preventivo, no crisis | Azul info `#4a81bd` o verde control |
| PI RC inicio vencido | `SI = -1`, `RC = true`, `Ready = false`, `EJ = 0` | Crisis | Alerta `#e53935` |
| PI no RC inicio vencido | `SI = -1`, `RC = false`, `Ready = false`, `EJ = 0` | Atencion | Advertencia `#ffca28` |
| PI inicio hoy RC bloqueada | `SI = 0`, `RC = true`, `Ready = false` | Crisis | Alerta `#e53935` |
| PI a 2 semanas con 66% en duras | `SI = 2`, duras en `66%`, `Predecesora >= 50%` | Seguimiento preventivo, no badge rojo | Verde control `#1a5633` |
| PI a 2 semanas con material 33% | `SI = 2`, `Materiales = 33%` | Atencion | Advertencia `#ffca28` |
| PS por comprometer RC habilitada | `Critica = 1`, `Ready = true`, `Compromiso = 0` | Atencion operativa, no crisis | Advertencia `#ffca28` |
| PS ejecucion con restricciones no RC | `Critica = 0`, `Ejecutado > 0`, `Ready = false` | Atencion | Advertencia `#ffca28` |
| PS ejecucion con restricciones RC | `Critica = 1`, `Ejecutado > 0`, `Ready = false` | Crisis | Alerta `#e53935` |
| PS incumplida no RC | `Ejecutado_Real < Compromiso`, `Critica = 0` | Atencion con CNC | Advertencia `#ffca28` |
| PS incumplida RC | `Ejecutado_Real < Compromiso`, `Critica = 1` | Crisis con CNC | Alerta `#e53935` |
| SOS manual en cualquier modulo | `alerta_crisis = 1` | Crisis | Alerta `#e53935` |
| Restriccion blanda pendiente | `Pdto_Cons < 100%` o `Modelo < 100%` con duras listas | Informativa, no bloquea | Azul info `#4a81bd` |
| Predecesora 50% | `Predecesora = 50%`, otras duras completas | Lista | Verde AIA `#1a5633` |
| Predecesora 33% | `Predecesora = 33%` | Pendiente | Advertencia `#ffca28` |

## Criterios de aceptacion para el plan tecnico

El plan de implementacion debe cumplir estos puntos:

- Existe una funcion unica de severidad del cajon, por ejemplo `getDrawerSeverity(context)`.
- `context.isCrisis` solo es verdadero cuando la severidad final es `critical`.
- El sidebar soporta `normal`, `attention` y `critical`.
- PS no usa `Semanas_Inicio` para severidad.
- PG/PI no pintan crisis roja por actividades futuras a 4-6 semanas.
- `Pdto_Cons` y `Modelo` no reducen el ITR habilitante ni bloquean inicio.
- `Predecesora >= 50%` cuenta como liberada.
- Valores `33%`, `33`, `0.33` y `0,33` se interpretan igual.
- Vacio en una columna de restriccion existente se trata como `0%`; solo `N/A` excluye.
- El color de alerta es coherente con el manual de marca y no depende de gustos visuales.
