---
capa: fuente
tipo: guia
estado: vigente
fecha: 2026-08-03
areas: [proceso]
fuente: docs/superpowers/diagnostico-2026-08-03.md
resumen: Diagnóstico del 3 de agosto de 2026 — avance, pendientes y lo que enseñó la jornada
---

# Diagnóstico del 3 de agosto de 2026 — avance, pendientes y lo que enseñó la jornada

**Alcance:** el día completo, desde el saneamiento del goal de tablas hasta el cierre de la línea G.
**Fuente:** medición contra el repositorio y el navegador. Donde una afirmación viene de un
documento y no de una medición, se dice.
**Compañeros:** `docs/superpowers/specs/2026-08-03-reparto-trabajo-pendiente-design.md` (el reparto
en ocho líneas) y `memoria/index.md` (la wiki del proyecto).

---

## 1. El reparto, línea por línea

| | Línea | Estado | Evidencia |
|---|---|---|---|
| **A** | Consolidar `--ds-state-tint-*` | ✅ Cerrada | `95a1827`, `08fe26c` |
| **B** | Que las pruebas digan la verdad | ✅ Cerrada | `96d9fd3`, `849bff9`, `101d765` |
| **C** | `bloqueado` al matiz azul | ⏸️ Puede haber quedado sin objeto | — |
| **D** | Puerta de servicio para `admin/` | ⬜ Sin empezar · toca autenticación | — |
| **E** | Usabilidad: 26 hallazgos | 🔧 En curso · 1 de 5 fases fusionada | `16348e4` + 8 commits sin fusionar |
| **F** | Panel de inicio | ⬜ Sin empezar · es producto | — |
| **F-bis** | Autoguardado al entrar | ⬜ Sin empezar · integridad de datos | — |
| **G** | Chip de estado de Programa General | ✅ Cerrada | `ad14fc1..53f6f26`, 9 commits |

### A · Tintes — cerrada

Cuatro alias aplicados (`teal`↔`info` incluido), medidos en navegador: los cuatro
`--ds-color-state-*-bg` resuelven idénticos a su ancla, cero píxeles movidos. Guard
`state-tint-pairing.test.mjs` con su inventario de excepciones.

**El desenlace correcto fue no arreglar nada.** Las seis reglas huérfanas se inventariaron como
`by-design` porque al medirlas ninguna tenía defecto: las tres celdas de grilla heredan el texto
primario (11,54 · 11,15 · 12,92:1), las dos de fila no pintan texto, y la píldora hereda del
contenedor. Declararles color habría sido una línea muerta.

**Lo que este tramo enseñó:** escribir el guard **antes** que el arreglo pagó. Su rojo levantó el
censo por su cuenta y corrigió el recuento a la baja —de ocho reglas a seis—, porque dos de
`admin/` sí recibían tinta en una regla agrupada que el escaneo manual había pasado por alto.

### B · Que las pruebas digan la verdad — cerrada

**B1** resultó no ser lo que decía. Ver §3.1.

**B2** resolvió un contrato que llevaba tres días en rojo: `--ds-sidebar-width-expanded` vale
`15rem` y `shell-navigation.test.mjs` esperaba `17.5rem`. Investigado antes de tocar nada, como
pidió el usuario: los valores originales de `321b095` (2026-07-20) **sí** eran 17,5/4,5, y los
cambió `72093c6` (2026-08-01) —un lote de 211 archivos y 69.819 inserciones— sin dar razón, sin
actualizar ningún test y sin recapturar un solo golden. No fue un reemplazo ciego: ese commit sí
tocaba `views/partials/shell_sidebar.php`. Resuelto a favor del token.

**Efecto colateral:** persiguiendo el último rojo apareció que
`docs/design-system/manifests/goal-provenance.json` tenía el hash de un `goal.md` desalineado —una
de quince fuentes— porque `9abedeb` le añadió al pie la sección de navegación que `CLAUDE.md`
documenta como excepción aprobada. Y su `sourceCommit` apuntaba a `054f395f…`, **un commit que no
existe en este repositorio y que nunca existió**: nació así en `3a13949`, el 15 de julio, en el
mismo commit que creó el manifiesto. Seis semanas apuntando a la nada sin que ningún gate lo
notara, porque solo valida que sean 40 caracteres hexadecimales.

### C · `bloqueado` al matiz azul — puede sobrar

Se aprobó cuando `--ds-cell-state-bloqueado-*` era la última ancla de color inventada. Pero
`bloqueado` solo lo usan `pg-state-r0` y `pg-state-restr-0`, que son **sub-estados de restricción**
y no figuran entre los siete estados que el contrato declara para `programa-general`. Quien abra la
tarea debe decidir primero si sigue teniendo objeto.

### D · Puerta de servicio para `admin/` — sin empezar

`/dev/entrar` solo abre sesión en la aplicación principal; `admin/` valida contra su propio
`/admin/login`, y el repositorio prohíbe teclear credenciales. **Bloqueo duro, no falta de tiempo:**
las 14 pantallas internas de `admin/` son invisibles para cualquier revisión automatizada.

Diseño de referencia en `docs/superpowers/specs/2026-07-30-dev-door-design.md`, `src/Core/DevDoor.php`
y `tests/test_dev_door_guard.php`. **Es una vía de autenticación nueva:** spec propio y revisión.

### E · Usabilidad — en curso

26 hallazgos ejecutables de los 39 del inventario (`goals/repaso-usabilidad-no-tablas/`). Cinco
fases de riesgo creciente. **F1 fusionada** (`16348e4`: tildes en PDC, «0 count» en BI, cabeceras de
control de cambios). **F2 en marcha** —estados vacíos— con 8 commits en su worktree.

La palanca que midió esa revisión: la aplicación **no está mal diseñada sino desigualmente
terminada**. `/programacion-semanal/cnc`, `/bi/programa-general`, `/proyectos` y `/plan-compras` ya
tienen el patrón correcto de estado vacío; el resto no lo heredó. Seis de los ocho puntos
heurísticos perdidos se concentran ahí y en consistencia.

### F · Panel de inicio — sin empezar, y es producto

`/dashboard` es hoy un redirect a `/programacion-semanal`. No es un arreglo: hay que decidir qué ve
un residente al entrar, qué ve un visualizador, y qué pasa si no hay semana activa. **Merece
grilleo propio.**

### F-bis · El autoguardado al entrar — sin empezar

Desgajado de F porque **no es usabilidad: es que abrir una pantalla escribe en la base de datos**.

Corregido hoy contra el código: la trampa
[`semanal-auto-dispara-mutaciones`](../../memoria/trampas/semanal-auto-dispara-mutaciones.md)
afirmaba que ocurre «en cada carga, sin interacción», y **es condicional**. `save` con
`opcion: 'sanear'` exige `canManageToolbarActions()` (`hot.js:2095-2106`) y `auto-program` exige
`semana > 0` (`changeMonitor.js:35-47`). El fondo sigue en pie: para un residente con permisos de
gestión sobre una semana válida —el caso normal de trabajo— se cumple.

### G · Chip de estado de Programa General — cerrada

Nueve commits. Los siete estados se distinguen por matiz y coinciden valor por valor con
`docs/design-system/state-semantics.json`. Ver §3.2 para la corrección de alcance que la encogió de
tres módulos a uno.

---

## 2. Lo cerrado hoy, medido

| Trabajo | Resultado |
|---|---|
| Escala de celda deriva de la de estado | Contraste subió en los cuatro peldaños derivados: 5,63→8,88 · 5,21→9,31 · 5,58→10,07 · 7,19→10,99. Ninguno bajó |
| `--ds-table-empty-fg` roto | Apuntaba a `--ds-active-text-tertiary`, **que nunca existió**; el texto caía a color heredado en todas las tablas |
| Cuatro alias de tintes | Cero píxeles movidos, verificado en navegador |
| `DESIGN.md` | Pasa a conocer el sistema de tablas. Antes: **cero de seis** términos (`ds-table-`, `cell-state`, `aia-grid-shell`, `datatables`, `ag-grid`, `handsontable`) |
| Contrato del sidebar | Alineado con el token; el test llevaba tres días en rojo contabilizado como «ajeno» |
| Procedencia de goals | Hash recalculado y `sourceCommit` apuntando por fin a un commit real |
| Chip de estado de PG | 7 matices distintos, todos ≥AA (11,15–14,82:1), con guard que mide el píxel |
| Goldens de PG | Recapturados con aprobación explícita, tras rechazar una recaptura anterior que habría consagrado una regresión |
| Inventario de usabilidad | 39 hallazgos sobre 26 de 45 superficies |
| Icono de campo en auth | Corregido en 5 campos de 3 pantallas |
| Wiki | De 30 a 35 trampas; dos notas corregidas contra el código |

---

## 3. Los cuatro diagnósticos que resultaron falsos

Lo más caro de la jornada no fue el trabajo: fue descubrir cuántas explicaciones dadas por buenas
no lo eran. **Ninguna la detectó un revisor leyendo código; las cuatro salieron de medir.**

### 3.1 «La grilla sale vacía porque faltan datos» — falso

Se aprobó B1 como «sembrar actividades con estado en el proyecto de pruebas». Medido después:

1. `Da Porto` tiene **273 filas** en `programa`.
2. Navegando a `/programa-general` a 1180×820 la grilla pinta **312 celdas en 26 filas**, con tres
   clases de estado vivas.
3. La causa real: `programa-general.visual.mjs:24`, la función `mockDeterministicData()` intercepta
   `**/api/general/list**` y devuelve `data: []`. **El test borra los datos a propósito** para que
   la captura sea determinista, y nunca consulta la base.

Sembrar la base no habría movido un píxel. El trabajo real —dar filas al mock— pasó de horas a
minutos.

**La lección de método:** «la grilla está vacía» admitía tres causas —sin datos, sin renderizar, o
con los datos interceptados— y se eligió la primera sin descartar las otras dos.

### 3.2 «Las tres grillas perdieron el canal de matiz» — falso en su mitad importante

G se escribió sobre la tesis de que unas reglas `!important` anulaban un mecanismo ya montado en los
tres módulos operativos. **Ejecutarla habría dejado las celdas sin fondo**, porque nada más las
pinta.

Lo medido: Programación Intermedia y Semanal **sí** implementan los dos canales —nivel en el fondo
de fila, matiz en un chip dentro de la celda con `data-aia-hue`— y no tienen defecto. Verificado en
`/programacion-intermedia`: un chip con `data-aia-hue="green"` resuelve a `#173d26`.

**El defecto era uno solo:** a Programa General nunca se le puso el chip. G encogió de tres módulos
a uno, y de inventar mecanismo a copiar uno probado.

### 3.3 «La suite estática está entera en verde» — falso, y lo dije yo

Al cerrar B2 se afirmó «363 pasan, cero fallan». El `ℹ fail 0` es **solo el resumen de
`node --test`**; `design-system-audit.mjs` corre después en la misma cadena y falla, así que el
comando entero sale con código 1.

Verificado: falla igual en `f30e3e1`. Lo introdujo `72093c6` —el mismo lote que rompió el contrato
del sidebar— con `var(--ds-color-brand-primary, #6c9077)` en `profesionales.css` y
`subcontratistas.css`. **Ese commit dejó dos regresiones sin verificar, no una.**

Otra sesión documentó ese mismo día un filo emparentado en
[`suite-estatico-mide-dos-arboles`](../../memoria/trampas/suite-estatico-mide-dos-arboles.md): la
suite es mitad Node y mitad PHP, y desde un worktree cada mitad lee un árbol distinto.

### 3.4 «Esos dos rojos son preexistentes» — falso, eran nuestros

Un implementador declaró ajenos dos fallos que había introducido la tarea anterior. Medido: **17
pasan / 0 fallan** antes del trabajo, **15 / 2** después.

Su error de razonamiento merece quedar escrito: *«este archivo de test no cambió» no prueba que el
fallo sea preexistente, porque lo que cambió fue el archivo que el test lee.*

**La única forma fiable que funcionó todo el día:** ejecutar la prueba en el commit anterior y
comparar.

---

## 4. La familia de trampas que dominó la jornada

Tres de las cinco notas nuevas de la wiki son la misma cosa vista desde ángulos distintos: **formas
de que un guard diga algo distinto de lo que el lector cree que dice.**

| Nota | El engaño |
|---|---|
| [`gate-estatico-no-ve-tokens-rotos`](../../memoria/trampas/gate-estatico-no-ve-tokens-rotos.md) | Un gate que lee archivos da verde con un token que apunta a una variable inexistente |
| [`guard-valida-declaracion-contra-si-misma`](../../memoria/trampas/guard-valida-declaracion-contra-si-misma.md) | El guard de «un matiz por estado» comprueba el JSON contra el propio JSON; nunca abre el CSS |
| [`axe-incomplete-cuenta-como-violacion`](../../memoria/trampas/axe-incomplete-cuenta-como-violacion.md) | El arnés aplana los «no pude medir» con los «está mal»; sobre fondos translúcidos, rojos falsos garantizados |

La heurística que las resume, formulada por una de las sesiones:

> **Al escribir un guard, pregúntate qué archivo tendría que estar mal para que fallara. Si la
> respuesta es «el mismo que lee», no vigila nada.**

Y una cuarta, de la misma raíz pero sobre documentación:
[`comentario-de-token-afirma-uso-inexistente`](../../memoria/trampas/comentario-de-token-afirma-uso-inexistente.md)
— ocho tokens llevaban rotulado un uso que ningún archivo ejercía, y resultó ser una integración
planeada y nunca cableada.

---

## 5. Pendientes

### 5.1 Decisiones que necesitan al usuario

| Decisión | Por qué importa | Coste de aplazarla |
|---|---|---|
| **¿C sigue teniendo objeto?** | G pudo disolverla | Bajo: nadie construye encima |
| **Separar `incomplete` de `violation`** en `tests/browser/support/accessibility.mjs:36` | Las superficies del repo son translúcidas por diseño, así que axe devolverá `incomplete` siempre | **Alto y creciente**: cada superficie nueva que entre al carril nace con rojos falsos, y la gente aprende a ignorarlos |
| **La cuarta paleta**, en `ReportController.php:379-384` | Unificarla cambia el aspecto de los Excel **ya descargados y archivados** | Bajo hoy, pero la divergencia crece |
| **Por dónde sigue el reparto** | D, F y F-bis sin empezar; F y F-bis son los de más impacto para el usuario final | Medio |

### 5.2 Deuda medida, sin dueño asignado

| Qué | Origen | Alcance |
|---|---|---|
| `design-system-audit.mjs` en rojo por hex en `profesionales` y `subcontratistas` | `72093c6` | Dos líneas: es un hex de reserva dentro de un `var()` |
| Los 9 hallazgos de severidad baja del inventario | Decisión del usuario | Fuera de alcance por ahora |
| Copias de `.ops-state-chip` en `.pi-page` y `.ps-page` | Anteriores | Pueden retirarse ahora que existe el componente compartido |
| Paridad visual del chip entre PG y PI/PS | G | El componente omite un `box-shadow` sutil y usa radio de 12 px contra 8 px |
| `cell-state-vocabulary.mjs` es código muerto | Goal de tablas | Nadie lo importa salvo su propio gate; los renderers asignan clases a mano |

### 5.3 En marcha ahora

- **H-08** trabaja la fase 2 de la línea E —estados vacíos compartidos para las mallas
  Handsontable— con 8 commits sin fusionar en su worktree.
- Hay **un commit local sin publicar** (`55295bd`) de otra sesión, con la trampa
  `suite-estatico-mide-dos-arboles`.

---

## 6. Cómo leer este documento dentro de un mes

La wiki (`memoria/`) es **contexto, nunca contrato**. La precedencia del repositorio es
**código > `AGENTS.md` > `memoria/`**, y este diagnóstico está por debajo de los tres: es una
fotografía de un día.

Todo lo que aquí se afirma como medido lleva su cifra y su fecha. Si alguna deja de cuadrar contra
el repositorio de entonces, gana el repositorio — y conviene corregir la nota en vez de borrarla,
que es la regla de la wiki.

**Los mapas por área** están en `memoria/mapas/`: siete, uno por zona. Antes de tocar cualquiera de
las líneas pendientes, el mapa de su área dice qué documentos mandan y qué trampas hay puestas.
Para casi todo lo de este documento, el que aplica es
[`memoria/mapas/design-system.md`](../../memoria/mapas/design-system.md); para la línea E,
[`memoria/mapas/qa-y-gates.md`](../../memoria/mapas/qa-y-gates.md).
