---
capa: fuente
tipo: biblia
estado: vigente
fecha: 2026-05-30
fuente: docs/ESTADOS-PG-PI-PS.md
resumen: Catálogo de Estados — PG / PI / PS
---

# Catálogo de Estados — PG / PI / PS

> Documentación hiperdetallada de todos los estados del Programa General (PG), Programación Intermedia (PI) y Programación Semanal (PS), incluyendo lógica de clasificación, labels, severidad, colores, acciones y fuentes.

---

## Índice

1. [Programa General (PG)](#1-programa-general-pg)
2. [Programación Intermedia (PI)](#2-programación-intermedia-pi)
3. [Programación Semanal (PS)](#3-programación-semanal-ps)
4. [Estados Rutinarios (sin alerta)](#4-estados-rutinarios-sin-alerta)
5. [Matriz de Restricciones Habilitantes](#5-matriz-de-restricciones-habilitantes)
6. [Matriz de Severidad Unificada](#6-matriz-de-severidad-unificada)
7. [Estados Legacy (por migrar)](#7-estados-legacy-por-migrar)

---

## 1. Programa General (PG)

### 1.1 Lógica de clasificación

Cada fila del `{prefix}_programa_consolidado` recibe un estado calculado en tiempo real mediante:

- **Backend moderno:** `LpsService::calculateGeneralStatus()` en `src/Core/Lps/LpsService.php:123-179`
- **Backend legacy (misma lógica):** `pg_calculate_status()` en `src/Legacy/estado_programa_general.php:130-193`

Variables de entrada:

| Variable | Fuente | Normalización |
|---|---|---|
| `Titulo` | Columna `Titulo` | `(int)$titulo === 1` → capítulo |
| `Ejecutado` | Columna `Ejecutado` | Clamp `0..1`, tolerancia `EPS = 0.001` |
| `Fecha_Inicio_Actividad` | Columna fecha inicio | `strtotime` a medianoche |
| `Fecha_Fin_Actividad` | Columna fecha fin | `strtotime` a medianoche |
| `Fecha_Inicio_Sem` | Semana activa | Desde `{prefix}_semanas_activas` |
| `Fecha_Fin_Sem` | Semana activa | Default: `Fecha_Inicio_Sem + 6 días` |

### 1.2 Estados canónicos (7)

| # | Estado | Label UX | Condición | Fuente |
|---|---|---|---|---|
| 1 | `Capítulo` | Capítulo | `Titulo = 1` | `LpsService.php:132`, `estado_programa_general.php:142` |
| 2 | `Terminada` | Terminada | `Ejecutado >= 0.999` (99.9%) | `LpsService.php:138-139`, `estado_programa_general.php:148-149` |
| 3 | `En Curso` | En Curso | `Ejecutado > EPS` **Y** `deltaTeórico <= EPS` (avance real >= esperado) | `LpsService.php:170-171`, `estado_programa_general.php:180-181` — también aplica si hay fechas nulas pero ejecutado > 0.1 (LpsService) o > EPS (legacy). |
| 4 | `Atrasada` | Atrasada | `Ejecutado > EPS` **Y** `deltaTeórico > EPS` (avance real < esperado); **O** `Ejecutado <= EPS` **Y** `fi < fs` (fecha inicio vencida sin ejecución) | `LpsService.php:162-163`, `estado_programa_general.php:177-178,184-185` |
| 5 | `Debe Iniciar` | Debe Iniciar | `Ejecutado <= EPS` **Y** `fi >= fs` **Y** `fi <= fe` (fecha inicio cae dentro de la semana activa) | `LpsService.php:166-167`, `estado_programa_general.php:188-189` |
| 6 | `Actividad Futura` | Actividad Futura | `Ejecutado <= EPS` **Y** `fi > fe` (fecha inicio después del fin de semana activa) | `LpsService.php:174-175`, `estado_programa_general.php:192` |
| 7 | `Sin Datos` | Sin Datos | `fi === null` o `ff === null` o `fs === null` **Y** `Ejecutado <= EPS` | `LpsService.php:147`, `estado_programa_general.php:161` — si hay ejecutado > 0.1/EPS pasa a `En Curso` |

### 1.3 Diagrama de decisión

```
Titulo = 1? → Capítulo
  ↓ no
Ej >= 0.999? → Terminada
  ↓ no
fi, ff, fs válidos? → no → ej > 0.001(EPS)? → sí → En Curso / no → Sin Datos
  ↓ sí
ff < fi? → ff = fi
calcular teórico, delta = teórico - real
  ↓
delta > EPS? → sí → Atrasada (ejecutó menos de lo debido)
  ↓ no
ej <= EPS y fi >= fs y fi <= fe? → sí → Debe Iniciar
  ↓ no
ej > EPS? → sí → En Curso
  ↓ no
ej <= EPS y fi > fe? → sí → Actividad Futura
  ↓
Actividad Futura (fallback)
```

### 1.4 Legend UI (PG)

Definido en `views/programa-general/programa_general.view.php:208-215`:

| Filtro | Label en leyenda | Clase CSS |
|---|---|---|
| `con-alerta-restricciones` | Con Alerta Restricciones | `alerta-restricciones` |
| `debe-iniciar` | Debe Iniciar | `debe-iniciar` |
| `actividad-futura` | Actividad Futura | `actividad-futura` |
| `en-curso` | En Curso | `en-curso` |
| `atrasada` | Atrasada | `atrasada` |
| `terminada` | Terminada | `terminada` |
| `sin-datos` | Sin Datos | `sin-datos` |

### 1.5 Mapeo en LPS Drawer

En `public/js/modules/lps_drawer.js:30-37`:

```javascript
PG_STATE_LABELS = {
  'debe-iniciar':     'Debe iniciar esta semana',
  'actividad-futura': 'Actividad futura',
  'en-curso':         'En curso',
  'atrasada':         'Atrasada',
  'terminada':        'Terminada',
  'header':           'Capítulo',
};
```

**Nota:** `Sin Datos` no tiene entrada en `PG_STATE_LABELS`. Esto significa que en el drawer LPS cae como 'Sin Datos' indefinido.

### 1.6 Constantes

Definidas en `estado_programa_general.php:3-17`:

| Constante | Valor | Propósito |
|---|---|---|
| `PG_STATUS_EPS` | `0.001` | Tolerancia de comparación de avance |
| `PG_STATUS_AHEAD_TOL` | `0.05` | Tolerancia de adelanto (no usada actualmente) |
| `PG_STATUS_DONE_THRESHOLD` | `0.999` | Umbral para considerar terminada |
| `PG_LOOKAHEAD_DAYS` | `42` | Ventana lookahead (6 semanas) |

En `LpsService.php` los valores están hardcodeados: umbral terminado `0.999`, EPS `0.001`, umbral "Sin Datos" vs "En Curso" es `0.1` (diferencia con legacy que usa `EPS`).

---

## 2. Programación Intermedia (PI)

### 2.1 Lógica de clasificación

Cada fila se clasifica mediante `PIStateMachine.getState()` en `public/js/modules/programacion_intermedia/stateMachine.js:125-172`.

Variables de entrada:

| Variable | Fuente | Normalización |
|---|---|---|
| `Titulo` | Columna `Titulo` | `!= 0` → header |
| `Semanas_Inicio` (SI) | Columna `Semanas_Inicio` | `Math.round()`, default `999` si vacío |
| `Ejecutado` (EJ) | Columna `Ejecutado` | `toNumber()`, default `0` |
| `Ruta_Critica` (RC) | Columna `Ruta_Critica` | `'1'`, `'si'`, `'sí'` → true |
| `D_y_E` | Columna o `restr_D_y_E` | Umbral `1.0` |
| `Materiales` | Columna o `restr_Materiales` | Umbral `1.0` |
| `MdeO` | Columna o `restr_MdeO` | Umbral `1.0` |
| `Equipos` | Columna o `restr_Equipos` | Umbral `1.0` |
| `Predecesora` | Columna o `restr_Predecesora` | Umbral `0.5` |

### 2.2 Estados operativos (10)

Definidos en `stateLabels` en `hot.js:171-182` y `getState()` en `stateMachine.js:125-172`.

| # | Clave | Label (UX) | Condición lógica | Prioridad en decisión |
|---|---|---|---|---|
| 1 | `header` | Capítulo | `Titulo != 0` | Siempre primero |
| 2 | `liberated-control` | Listo para comprometer | Restricciones duras cumplen umbral (`isReadyToCommit()`) | Se evalúa en múltiples ramas |
| 3 | `blocked-overdue-critical` | RC inicio vencido | `SI < 0`, `RC = true`, sin liberar, `EJ = 0` | `si <= 0 && isNotStarted && !isLiberated && isOverdueSignal && critical` |
| 4 | `blocked-overdue` | Inicio Vencido | `SI < 0`, `RC = false`, sin liberar, `EJ = 0` | `si <= 0 && isNotStarted && !isLiberated && isOverdueSignal && !critical` |
| 5 | `blocked-due` | Inicio por Habilitar | `SI = 0`, sin liberar, `EJ = 0`, no vencido | `si <= 0 && isNotStarted && !isLiberated && !isOverdueSignal` |
| 6 | `alert-1-week` | Alistamiento urgente | `SI = 1`, sin liberar, `EJ = 0` | `si === 1 && isNotStarted && !isLiberated` |
| 7 | `alert-2-3-weeks` | Alistamiento en riesgo | `SI ∈ [2,3]`, sin liberar, `EJ = 0` | `si >= 2 && si <= 3 && isNotStarted && !isLiberated` |
| 8 | `alert-4-6-weeks` | Alistamiento pendiente | `SI ∈ [4,6]`, sin liberar, `EJ = 0` | `si >= 4 && si <= 6 && isNotStarted && !isLiberated` |
| 9 | `execution-blocked` | Ejecución Pendiente | `0 < EJ < 0.999`, sin liberar | `isStarted && !isLiberated` |
| 10 | `neutral` | Control | Ninguna condición anterior aplica (SI > 6, o liberada con SI > 0, o sin datos) | Fallback final |

### 2.3 Diagrama de decisión

```
Titulo != 0? → header
  ↓ Titulo = 0
SI = round(Semanas_Inicio), EJ = toNumber(Ejecutado), RC = isCriticalRoute(Ruta_Critica)
Liberated = isReadyToCommit(data)  // todas las duras cumplen umbral
Started = 0 < EJ < 0.999
NotStarted = EJ <= 0
Overdue = SI < 0
  ↓
Started && !Liberated → execution-blocked
Started && Liberated  → liberated-control
  ↓ (NotStarted)
SI <= 0 && Liberated → liberated-control
SI <= 0 && !Liberated && Overdue && RC → blocked-overdue-critical
SI <= 0 && !Liberated && Overdue && !RC → blocked-overdue
SI = 0 && !Liberated && !Overdue → blocked-due
SI = 1 && !Liberated → alert-1-week
SI ∈ [2,3] && !Liberated → alert-2-3-weeks
SI ∈ [4,6] && !Liberated → alert-4-6-weeks
Liberated && SI > 0 && SI <= 6 → liberated-control
  ↓ (ninguna aplica)
neutral
```

### 2.4 Legend UI (PI)

Definido en `views/programacion-intermedia/programacion_intermedia.view.php:1229-1239`.

Notar: `neutral` y `liberated-control` NO aparecen en la leyenda visual como filtros — se muestran como "Control" y "Listo para Comprometer" respectivamente, pero sin entrada de filtro pulsable. El `header` tampoco aparece. La leyenda solo muestra los estados que requieren acción:

| Label en leyenda | Clave |
|---|---|
| RC inicio vencido | `blocked-overdue-critical` |
| Inicio Vencido | `blocked-overdue` |
| Inicio por Habilitar | `blocked-due` |
| Alistamiento Urgente | `alert-1-week` |
| Alistamiento en Riesgo | `alert-2-3-weeks` |
| Alistamiento Pendiente | `alert-4-6-weeks` |
| En Ejecución Pendiente | `execution-blocked` |
| Listo para Comprometer | `liberated-control` |

### 2.5 Función de liberación

En `stateMachine.js:117-123`:

```javascript
function isReadyToCommit(data) {
    return restrictionMeets(data, 'D_y_E', 1)
        && restrictionMeets(data, 'Materiales', 1)
        && restrictionMeets(data, 'MdeO', 1)
        && restrictionMeets(data, 'Equipos', 1)
        && restrictionMeets(data, 'Predecesora', 0.5);
}
```

Todas las duras al 100% excepto `Predecesora` que basta con 50%. `N/A` o `NO APLICA` se considera cumplido automáticamente. Restricciones blandas (`Pdto_Cons`, `Modelo`) no bloquean.

### 2.6 Acciones de habilitación por restricción

Definidas en `hot.js:94-141`. Cada restricción dura tiene acciones escalonadas por umbral:

| Restricción | Threshold | 0%-1% | 1%-50% | 50%-100% |
|---|---|---|---|---|
| **D_y_E** (Diseños) | 1.0 | Solicitar diseños | Revisar con dirección | Aprobar y entregar |
| **Materiales** | 1.0 | Gestionar contratos | Plan aprovisionamiento | Confirmar disponibles |
| **MdeO** (Mano de Obra) | 1.0 | Gestionar contratos | Ubicar recurso | Movilizar personal |
| **Equipos** | 1.0 | Gestionar contratos | Plan aprovisionamiento | Confirmar disponibles |
| **Predecesora** | 0.5 | _Recuperar o iniciar predecesora_ (única acción, si < 50%) | | |

### 2.7 Estado vs severidad (matriz PI con RC)

Para cada estado operativo, la severidad varía según RC. Fuente: `docs/matriz-severidad-cajon-contextual-lps.md:141-157`.

| Estado PI | RC | Severidad | Color | Sidebar |
|---|---|---|---|---|
| `header` | — | `neutral` | Gris `#eaeaea` | Sin badge |
| `neutral` | — | `neutral` | Gris `#eaeaea` | Sin badge |
| `liberated-control` | — | `normal` | Verde `#1a5633` | Sin badge |
| `blocked-overdue-critical` | true | `critical` | Rojo `#e53935` | Rojo |
| `blocked-overdue` | false | `attention` | Ámbar `#ffca28` | Ámbar |
| `blocked-due` | true | `critical` | Rojo `#e53935` | Rojo |
| `blocked-due` | false | `attention` | Ámbar `#ffca28` | Ámbar |
| `alert-1-week` | true | `attention` alta | Ámbar `#ffca28` | Ámbar |
| `alert-1-week` | false | `attention` | Ámbar `#ffca28` | Ámbar |
| `alert-2-3-weeks` (DeepGap) | — | `attention` | Ámbar `#ffca28` | Ámbar |
| `alert-2-3-weeks` (sin brecha) | — | `normal` preventivo | Verde `#1a5633` | Sin badge |
| `alert-4-6-weeks` | — | `normal` preventivo | Azul `#4a81bd` | Sin badge |
| `execution-blocked` | true | `critical` | Rojo `#e53935` | Rojo |
| `execution-blocked` | false | `attention` | Ámbar `#ffca28` | Ámbar |

---

## 3. Programación Semanal (PS)

### 3.1 Lógica de clasificación

Cada fila se clasifica mediante `PSStateMachine.classifyState()` en `public/js/modules/programacion_semanal/stateMachine.js:147-203`.

Variables de entrada:

| Variable | Fuente | Normalización |
|---|---|---|
| `Activa` | Columna `Activa` | `isBlank()` o valores `NA/0/N/NO/FALSE` → false |
| `Ejecutado` | Columna `Ejecutado` | `toNumberOrNull()` |
| `Compromiso` | Columna `Compromiso` | `toNumberOrNull()`; `null` o `<= 0` → vacío |
| `Ejecutado_Real` | Columna `Ejecutado_Real` | `toNumberOrNull()` |
| `Prog_Sin_Restricciones_100` | Columna flag | `> 0` → hay liberación |
| `Critica` | Columna `Critica` | `>= 1` → ruta crítica |
| `Sub_Contratista` | Columna | `isBlank()` → falta |
| `Responsable_AIA` | Columna | `isBlank()` → falta |
| Fase | `Semanal_Confirmada` | `== 1` → `calificacion`; otro → `programacion` |
| Restricciones duras | `D_y_E`, `Materiales`, `MdeO`, `Equipos`, `Predecesora` | Misma lógica que PI |

### 3.2 Estados operativos (10)

Definidos en `WEEKLY_ALERT_MODEL` en `hot.js:78-155` y `classifyState()` en `stateMachine.js:147-203`.

#### Fase de Programación (5 estados)

| # | Clave | Label (UX) | Prioridad | Condición lógica | Descripción | Acción |
|---|---|---|---|---|---|---|
| 1 | `prog-bloqueo-critico-sin-compromiso` | RC con restricciones | p1 | Activa, incompleta, RC, sin liberar, sin ejecución | Ruta crítica bloqueada por condiciones pendientes | Escalar hoy y cerrar acciones de habilitación |
| 2 | `prog-ejecucion-con-restricciones` | Ejecución con restricciones | p1 | Activa, incompleta, `Ejecutado > 0.001`, sin liberar | Actividad con avance pero condiciones pendientes | Revisar restricciones antes de comprometer más |
| 3 | `prog-condiciones-pendientes` | Condiciones Pendientes | p2 | Activa, incompleta, sin liberar, no RC, sin ejecución | Requiere habilitación antes de confirmar | Completar acciones por restricción y autoprogramar |
| 4 | `prog-sin-compromiso` | Por Comprometer | p2 | Activa, incompleta, liberada, pero sin `Compromiso > 0` o sin `Responsable_AIA`/`Sub_Contratista` | Habilitada pero sin compromiso semanal | Definir cantidad, responsable y subcontratista |
| 5 | `prog-lista-para-confirmar` | Lista para Confirmar | p3 | Activa, incompleta, liberada, con compromiso y responsables | Todo listo para confirmar en comité | Verificar recursos y confirmar |

#### Fase de Calificación (4 estados)

| # | Clave | Label (UX) | Prioridad | Condición lógica | Descripción | Acción |
|---|---|---|---|---|---|---|
| 6 | `cal-incumplida-critica` | Incumplida (RC) | p1 | `Ejecutado_Real < Compromiso`, RC | Compromiso no cumplido en ruta crítica | Registrar CNC, recuperación diaria del camino crítico |
| 7 | `cal-incumplida` | Incumplida | p2 | `Ejecutado_Real < Compromiso`, no RC | Compromiso no cumplido | Registrar CNC, plan correctivo |
| 8 | `cal-sin-calificar` | Sin Calificar | p2 | `Compromiso` vacío o `Ejecutado_Real === null` | Falta ejecutado real para evaluar | Completar ejecutado real |
| 9 | `cal-cumplida-control` | Cumplida Control | p3 | `Ejecutado_Real >= Compromiso` | Compromiso cumplido o superado | Documentar práctica, sostener ritmo |

#### Estado base (1)

| # | Clave | Label (UX) | Condición lógica |
|---|---|---|---|
| 10 | `ps-no-activa` | Programada Manualmente | `!isActiveRow()` — Activa = NA/0/N/NO/FALSE/vacío, o actividad completa (`Ejecutado >= 0.999` en fase programación) |

### 3.3 Diagrama de decisión

```
isActiveRow(data)? → No → ps-no-activa
  ↓ Sí
fase = getPhaseKey(Semanal_Confirmada)
  ↓
Ejecutado, Compromiso, Ejecutado_Real, liberaciónFlag, Critica
CompromisoVacio = compromiso === null || compromiso <= 0
Incompleta = ejecutado === null || ejecutado < 0.999
SinLiberacion = (liberaciónFlag > 0) || hasPendingCommitConditions(data)
  ↓
FASE PROGRAMACIÓN:
  Incompleta? → No → ps-no-activa
  Sí →
  TieneEjecucion (ej > 0.001)? && SinLiberacion → prog-ejecucion-con-restricciones
  SinLiberacion && Critica → prog-bloqueo-critico-sin-compromiso
  SinLiberacion → prog-condiciones-pendientes
  CompromisoVacio || FaltanResponsables → prog-sin-compromiso
  → prog-lista-para-confirmar
  ↓
FASE CALIFICACIÓN:
  CompromisoVacio || Ejecutado_Real === null → cal-sin-calificar
  Ejecutado_Real < Compromiso && Critica → cal-incumplida-critica
  Ejecutado_Real < Compromiso && !Critica → cal-incumplida
  → cal-cumplida-control
```

### 3.4 Legend UI (PS)

Generada dinámicamente por `renderAlertLegend()` en `hot.js:2680-2688`. La leyenda en `views/programacion-semanal/programacion_semanal.view.php:886` es un contenedor vacío `<div id="psAlertsLegend">` que se puebla con JS según la fase activa.

| Etiqueta | Clave | Prioridad | Clase CSS |
|---|---|---|---|
| RC con restricciones | `prog-bloqueo-critico-sin-compromiso` | p1 | `ps-alert-critical-route` |
| Ejecución con restricciones | `prog-ejecucion-con-restricciones` | p1 | `ps-alert-high` |
| Condiciones Pendientes | `prog-condiciones-pendientes` | p2 | `ps-alert-medium` |
| Por Comprometer | `prog-sin-compromiso` | p2 | `ps-alert-medium` |
| Lista para Confirmar | `prog-lista-para-confirmar` | p3 | `ps-alert-control` |
| Incumplida (RC) | `cal-incumplida-critica` | p1 | `ps-alert-critical-route` |
| Incumplida | `cal-incumplida` | p2 | `ps-alert-medium` |
| Sin Calificar | `cal-sin-calificar` | p2 | `ps-alert-medium` |
| Cumplida Control | `cal-cumplida-control` | p3 | `ps-alert-control` |

### 3.5 Estados con variante RC dinámica

En `getAlertClassForRow()` en `hot.js:910-916`, el estado `prog-ejecucion-con-restricciones` cuando `Critica >= 1` recibe clase `ps-alert-critical-route` en vez de `ps-alert-high`. Esto NO cambia la clave del estado, solo el estilo visual.

### 3.6 Legend modal (Guía operativa)

Renderizada por `renderLegendModal()` en `hot.js:2691-2755`. Agrupa por prioridad:

| Prioridad | Grupo | Descripción |
|---|---|---|
| p1 | Resolver hoy | Estados que requieren acción inmediata (críticos y alta prioridad) |
| p2 | Gestión semanal | Estados que requieren gestión durante el ciclo semanal |
| p3 | Seguimiento | Estados de control y cumplimiento |

---

## 4. Estados Rutinarios (sin alerta)

Definidos en `public/js/modules/lps_drawer.js:39-47`. Son estados que NO activan el drawer LPS ni muestran alerta en el sidebar:

```javascript
const ROUTINE_STATE_KEYS = [
    'liberated-control',      // PI: listo para comprometer
    'neutral',                // PI: control
    'terminada',              // PG: completada
    'prog-lista-para-confirmar',  // PS: listo para confirmar
    'cal-cumplida-control',   // PS: cumplida
    'ps-no-activa',           // PS: no activa
    'header',                 // PG/PI: capítulo
];
```

### 4.1 Estados de escalamiento directivo

Definidos en `lps_drawer.js:49-52`:

```javascript
const WEEKLY_ESCALATION_STATE_KEYS = [
    'prog-bloqueo-critico-sin-compromiso',
    'cal-incumplida-critica'
];
```

Estos estados activan el flujo de escalamiento jerárquico SOS cuando están en PS.

---

## 5. Matriz de Restricciones Habilitantes

Aplica tanto a PI como a PS. Definición completa en `docs/matriz-severidad-cajon-contextual-lps.md:59-85` y en `hot.js:94-141` (PI) y `stateMachine.js` (PS).

### 5.1 Restricciones duras

| Restricción | Alias en DB | Umbral | Incompleta | Liberada |
|---|---|---|---|---|
| Diseños y Especificaciones | `D_y_E`, `restr_D_y_E` | 100% | Ámbar `#ffca28` | Verde `#1a5633` |
| Materiales | `Materiales`, `restr_Materiales` | 100% | Ámbar `#ffca28` | Verde `#1a5633` |
| Mano de Obra | `MdeO`, `restr_MdeO` | 100% | Ámbar `#ffca28` | Verde `#1a5633` |
| Equipos | `Equipos`, `restr_Equipos` | 100% | Ámbar `#ffca28` | Verde `#1a5633` |
| Predecesora | `Predecesora`, `restr_Predecesora` | **50%** | Ámbar `#ffca28` | Verde `#1a5633` |

### 5.2 Restricciones blandas

| Restricción | Alias | Efecto en liberación |
|---|---|---|
| Procedimiento Constructivo | `Pdto_Cons`, `restr_Pdto_Cons` | No bloquea |
| Modelo BIM | `Modelo`, `restr_Modelo` | No bloquea |

### 5.3 Normalización de valores

| Entrada | Ratio | ¿Liberada? |
|---|---|---|
| `100%`, `100`, `1`, `1.0`, `1,0` | 1.00 | Sí (umbral 100%) |
| `66%`, `66`, `0.66` | 0.66 | No si umbral 100%; Sí si Predecesora |
| `50%`, `50`, `0.5` | 0.50 | Sí solo para Predecesora |
| `33%`, `33`, `0.33` | 0.33 | No |
| `0%`, `0`, `0.0` | 0.00 | No |
| `N/A`, `NA`, `NO APLICA` | null | Sí (excluido) |
| Vacío en columna existente | 0.00 | No |
| Campo inexistente | null | No aplica (no penaliza) |

La normalización acepta tanto punto como coma decimal, elimina espacios, y ajusta ratios >1 dividiendo por 100 hasta que quede en `[0,1]`.

---

## 6. Matriz de Severidad Unificada

Documentada en `docs/matriz-severidad-cajon-contextual-lps.md:39-51`. El orden de decisión es:

| Orden | Regla | Severidad |
|---|---|---|
| 1 | `alerta_crisis = 1` o SOS manual activo | `critical` |
| 2 | Fila de capítulo/header | `neutral` |
| 3 | PS en fase semanal | Según matriz PS |
| 4 | PG/PI con bloqueo actual/vencido de ruta crítica | `critical` |
| 5 | PG/PI con riesgo gestionable no crítico | `attention` |
| 6 | Actividad liberada, terminada, cumplida o sin acción abierta | `normal` |
| 7 | Sin evidencia suficiente para escalar | `neutral` |

### Paleta operativa

| Severidad | Color principal | Fondo | Texto/borde | Sidebar |
|---|---|---|---|---|
| `normal` | Verde `#1a5633` | Verde claro `#d5e5db` | Verde oscuro `#1a3c2a` | Verde, sin badge |
| `attention` | Ámbar `#ffca28` | Ámbar claro `#fff8e1` | Ámbar oscuro `#a0731a` | Ámbar, badge discreto |
| `critical` | Rojo `#e53935` | Rojo claro `#fdecec` | Rojo oscuro `#9a1f1f` | Rojo, badge visible |
| `neutral` | Gris `#eaeaea` | Alabaster `#fafafa` | Texto secundario `#4a4a4d` | Verde, sin badge |
| `info` | Azul `#4a81bd` | Azul claro `#e6f0fa` | Azul oscuro `#2a5a8f` | Verde, sin badge |

---

## 7. Estados Legacy (por migrar)

Estos estados persisten en la base de datos `{prefix}_programa_consolidado.Estado` y en consultas/filtros, pero ya NO son producidos por el backend moderno. Deben migrarse a los canónicos.

### 7.1 Estados legacy en DB (aún referenciados)

| Estado legacy | Agrupado con (filtro UX) | Reemplazo canónico | Referencias |
|---|---|---|---|
| `A Tiempo` | En Curso | `En Curso` | `GeneralApiController.php:49`, `ProgramaGeneralController.php:140` |
| `Terminada Antes` | Terminada | `Terminada` | `GeneralApiController.php:55`, `ProgramaGeneralController.php:142` |
| `Ya Debió Iniciar y Restricciones Pendientes` | Atrasada | `Atrasada` | `GeneralApiController.php:52`, `ProgramaGeneralController.php:141` |
| `En Liberación de Restricciones` | Actividad Futura | `Actividad Futura` | `GeneralApiController.php:43`, `ProgramaGeneralController.php:138` |

### 7.2 Estados históricos eliminados (patches aplicados)

| Estado eliminado | Reemplazo | Patch |
|---|---|---|
| `Debe Iniciar esta Semana` | `Debe Iniciar` | `20260527_rename_debe_iniciar.sql` |
| `Debe Iniciar esta Semana y Restricciones Pendientes` | `Debe Iniciar` | `20260527_rename_debe_iniciar.sql` |
| `No Requerida` | `Actividad Futura` / `Sin Datos` | `20260527_remove_no_requerida_state.sql` |
| `Adelantada` | `En Curso` | `20260527_remove_adelantada_state.sql` |

### 7.3 Estado documentado en matriz de severidad pero no canónico

`no-requerida` aparece en `docs/matriz-severidad-cajon-contextual-lps.md:104` como `neutral` — es un remanente del estado legacy `No Requerida` que fue eliminado por patch. No debe usarse.

---

## Anexo: Mapa de archivos fuente

| Archivo | Contenido |
|---|---|
| `src/Legacy/estado_programa_general.php` | Función `pg_calculate_status()` — 7 estados PG |
| `src/Core/Lps/LpsService.php` (L123-179) | Método `calculateGeneralStatus()` — 7 estados PG (moderno) |
| `public/js/modules/programacion_intermedia/stateMachine.js` | `PIStateMachine.getState()` — 10 estados PI |
| `public/js/modules/programacion_intermedia/hot.js` (L94-141) | Matriz de acciones de habilitación PI |
| `public/js/modules/programacion_intermedia/hot.js` (L171-182) | Labels de estados PI |
| `public/js/modules/programacion_semanal/stateMachine.js` (L147-203) | `PSStateMachine.classifyState()` — 10 estados PS |
| `public/js/modules/programacion_semanal/hot.js` (L78-155) | `WEEKLY_ALERT_MODEL` — definición completa PS |
| `public/js/modules/programacion_semanal/hot.js` (L918-934) | `getStateLabelByKey()` — label PS |
| `public/js/modules/lps_drawer.js` (L30-37) | `PG_STATE_LABELS` — labels PG en drawer |
| `public/js/modules/lps_drawer.js` (L39-47) | `ROUTINE_STATE_KEYS` — estados sin alerta |
| `public/js/modules/lps_drawer.js` (L49-52) | `WEEKLY_ESCALATION_STATE_KEYS` — escalamiento SOS |
| `src/Controllers/Api/GeneralApiController.php` (L42-55) | Filtros de leyenda PG con legacy states |
| `src/Controllers/Programacion/ProgramaGeneralController.php` (L136-143) | Conteos de indicadores PG con legacy states |
| `views/programa-general/programa_general.view.php` (L208-215) | Legend UI PG |
| `views/programacion-intermedia/programacion_intermedia.view.php` (L1229-1239) | Legend UI PI |
| `views/programacion-semanal/programacion_semanal.view.php` (L886) | Contenedor legend UI PS |
| `docs/matriz-severidad-cajon-contextual-lps.md` | Matriz de severidad unificada completa |

---

---

## 8. Matriz de Auto-Programación (Cascada de Decisión)

### 8.1 Variables de entrada

| Variable | Fuente | Valores |
|---|---|---|
| **Estado PG** | `{prefix}_programa_consolidado.Estado` | Ver sección 1.2. Agrupado en: `Debe Comprometer`, `Terminada`, `Actividad Futura`, `Sin Datos`, `Capítulo` |
| **Restricciones duras PI** | `D_y_E`, `Materiales`, `MdeO`, `Equipos`, `Predecesora` en PG | `OK` (todas cumplen umbral) / `NO OK` (alguna no cumple) |
| **Compromiso PS** | `{prefix}_programacion_semanal.Compromiso` | `NULL` (auto-insertada, sin asignar), `> 0` (tiene valor), `0` (no debería ocurrir, app lo bloquea) |
| **Existe en PG** | JOIN con `programa_consolidado` | Sí / No |
| **Existe en PS** | `programacion_semanal.Activa IN ('1','NA')` | Sí / No |

### 8.2 Grupos de estado PG

| Grupo | Estados incluidos | Comportamiento |
|---|---|---|
| **Debe Comprometer** | En Curso, Atrasada, Debe Iniciar, A Tiempo (legacy), Adelantada (legacy), Ya Debió Iniciar y Restricciones Pendientes (legacy), Debe Iniciar esta Semana (legacy), Debe Iniciar esta Semana y Restricciones Pendientes (legacy) | Actividad que debería ejecutarse |
| **Terminada** | Terminada, Terminada Antes (legacy) | Actividad completada (≥99.9%) |
| **Actividad Futura** | Actividad Futura, En Liberación de Restricciones (legacy), No Requerida (legacy) | Actividad con fecha inicio después del fin de semana activa |
| **Sin Datos** | Sin Datos | Fechas nulas sin ejecución — **Skip** |
| **Capítulo** | Capítulo (Titulo=1) | **Skip** — no se procesa |

### 8.3 Cascada de decisión

#### Pre-filtro (siempre Skip)
1. `Titulo = 1` (Capítulo) → Skip
2. Estado PG = vacío/null → Skip
3. Estado PG = `Sin Datos` → Skip

#### Paso 1 — PS huérfana (existe en PS, NO existe en PG)
| Condición | Acción |
|---|---|
| `Activa IN ('1','NA')` y `consecutivo` no está en PG | **DELETE** directo, sin CNP |

#### Paso 2 — PG nueva (existe en PG, NO existe en PS)

| Grupo PG | Restricciones duras | Acción |
|---|---|---|
| **Debe Comprometer** | OK | **Auto-INSERT** con `Activa='1'`, `Compromiso=NULL` |
| **Debe Comprometer** | NO OK | **Auto-INSERT** con `Activa='0'`, `Categoria_CNP='Programación'`, `CNP='Restricciones habilitantes no cumplidas'` |
| **Terminada** | cualquiera | Skip |
| **Actividad Futura** | cualquiera | Skip |

#### Paso 3 — Existe en PG + Existe en PS

| Grupo PG | Compromiso PS | Restricciones | Acción |
|---|---|---|---|
| **Debe Comprometer** | NULL | cualquiera | No tocar |
| **Debe Comprometer** | > 0 | cualquiera | No tocar |
| **Terminada** | NULL | OK | No tocar |
| **Terminada** | NULL | NO OK | **DELETE** |
| **Terminada** | > 0 | cualquiera | No tocar |
| **Actividad Futura** | NULL | OK | No tocar |
| **Actividad Futura** | NULL | NO OK | **DELETE** |
| **Actividad Futura** | > 0 | cualquiera | No tocar |

### 8.4 Notas

- `Compromiso = 0` no debería existir en producción porque la app lo bloquea al guardar. Si aparece, se trata como NULL.
- Las actividades auto-insertadas (Paso 2) se registran en `{db}_auto_program_log` para trazabilidad.
- No hay modal de decisión: todo se ejecuta automáticamente al cargar PS.
- El log se visualiza mediante un botón en toolbar de PS con contador flotante.

---

> **Total: 7 PG + 10 PI + 10 PS = 27 estados activos.** 4 legacy en DB pendientes de migrar, 4 históricos ya eliminados.
