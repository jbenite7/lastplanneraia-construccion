---
capa: fuente
tipo: spec
estado: vigente
fecha: 2026-08-19
areas: [proceso]
fuente: docs/superpowers/specs/2026-08-19-ds-f1a-estado-design.md
resumen: DS-F1a · La escala de estado: vocabulario y lectura — diseño
---

# DS-F1a · La escala de estado: vocabulario y lectura — diseño

**Fase:** DS-F1, primer contrato de cuatro. **Frente:** `ds-f1a-estado`.
**Base medida:** `1af2e9ac`. **Datos:** las 65 549 filas de `programa_consolidado`, 16 proyectos.

## De dónde sale

DS-F1 es la segunda fase del programa que nació de la conclusión del usuario del 2026-08-18: «el
design system no está bien definido, ni bien implementado, ni bien controlado». Su encargo es
redefinir el contrato — tokens, primitivas `aia-*`, escalas de estado y de stacking.

**Son cuatro contratos, no uno**, y el usuario decidió partirlos y empezar por este. Los otros tres
—stacking (z-index), primitivas y tokens— vuelven a la cola con su propio turno.

Este frente entra además con dos deudas abiertas que le pertenecen: **D-VOC-1**, donde el usuario
aprobó la dirección de tres ejes y pidió **replantear el diseño antes de ejecutar**, y el insumo
medido que dejó el frente `bug-coloreado-severidad`, cuyas tres preguntas eran para esta fase.

## El problema, en una frase

**La tabla comunica bien qué estado es y no comunica en absoluto cuán grave es.** No es una
impresión: los ocho tintes se eligieron equiluminantes a propósito, para distinguirse por tono. Se
distinguen —el ΔE-OK mínimo entre peldaños es 0,0487, casi el triple del umbral de percepción que
el propio repo fija en 0,0168— pero ninguno pesa más que otro. Y donde hay orden, va al revés: el
peldaño más claro es `atencion` y `critico` es el penúltimo más oscuro.

## Lo que decidió el usuario

Siete decisiones, tomadas en conversación directa el 2026-08-19. Registradas con su fundamento en
`decisiones/ds-f1a-estado-ejecutor.md`.

1. **DS-F1 se parte en cuatro contratos** y se empieza por la escala de estado.
2. **Vocabulario y escala se diseñan juntos.** Decidir cómo se ve la gravedad exige saber cuántos
   niveles hay, y eso exige saber cómo se llaman los estados.
3. **Tres niveles de gravedad**: urgente, atención, controlado. La categoría `neutral` del contrato
   **no es un cuarto nivel**: es ausencia de gravedad, no un grado.
4. **Se recupera el estado de 7+ semanas**, que estaba marcado como eliminado desde mayo y se
   estaba borrando solo. Definición del usuario: «actividad que comienza en 7 semanas o más
   respecto a la fecha de inicio de la semana actual» — exactamente lo que cae fuera de
   `PG_LOOKAHEAD_DAYS = 42`.
5. **Ese estado se llama «Fuera de Ventana»**, no `No Requerida`. El nombre viejo dice lo contrario
   de lo que significa: se lee como «no hace falta hacerla».
6. **El fondo de la celda lleva identidad y horizonte.**
7. **Una barra al borde de la fila lleva la gravedad**, en tres niveles.

## El diseño

### Dos canales con un trabajo cada uno

| Canal | Qué dice | Dónde aparece |
|---|---|---|
| **Fondo de celda** | Qué estado es y en qué horizonte cae | Siempre |
| **Barra al borde de fila** | Cuánto pesa: urgente / atención | Solo en el 21,3% que pide algo |

**Por qué el fondo no puede llevar la gravedad**, que es el argumento que cierra la decisión y no
es estético: `Fuera de Ventana` (24,2%), `Actividad Futura` (33,6%) y `Terminada` (19,0%) tienen
**urgencia cero las tres**. Si el fondo codifica gravedad, las tres se pintan igual y se pierde la
distinción de horizonte — la misma que la decisión 4 acaba de rescatar. El fondo ya tiene un
trabajo que ningún otro canal puede hacer.

**Por qué la ausencia de barra significa algo.** El 78,7% de las actividades no lleva barra, y eso
no es un hueco: es la señal de «no pide nada». Es lo que permite que el 21,3% se lea de un barrido
vertical sin comparar tonos — reconocer, no recordar.

### Tres niveles de gravedad, dos marcas visibles

Un punto que el diseño tiene que decir sin ambigüedad, porque «tres niveles» se puede entender de
dos formas: **los tres niveles son conceptuales; la barra dibuja dos y la ausencia de barra dibuja
el tercero.**

| Nivel conceptual | Cómo se ve |
|---|---|
| Urgente | Barra, tratamiento fuerte |
| Atención | Barra, tratamiento medio |
| Controlado | **Sin barra** |

No es un atajo de implementación: es la razón por la que el 21,3% se lee de un vistazo. Si
«controlado» tuviera su propia marca, el 78,7% de las filas llevaría una y la señal dejaría de ser
escasa. La ausencia es la tercera marca, y es la más frecuente.

**`Fuera de Ventana` y `Sin Datos` tampoco llevan barra**, pero por una razón distinta de
`Controlado`: no es que su gravedad sea baja, es que **no tienen gravedad**. Se distinguen en el
fondo, que es su canal.

### Qué estado cae en cada nivel

Medido sobre las **50 966 actividades reales** (65 549 filas menos 14 583 capítulos, que no son
actividades — ver §Capítulo).

| Nivel | Estados | % |
|---|---|---:|
| **Urgente** | `Atrasada` · `Debe Iniciar esta Semana y Restricciones Pendientes` | 9,5% |
| **Atención** | `En Liberación de Restricciones` · `Debe Iniciar` · `Debe Iniciar esta Semana` | 11,8% |
| **Controlado** | `Actividad Futura` · `Terminada` · `En Curso` · `Terminada Antes` · `A Tiempo` · `Adelantada` | 54,4% |
| *(sin gravedad)* | `Fuera de Ventana` — horizonte propio, se lee en el fondo | 24,2% |
| *(sin gravedad)* | `Sin Datos` — ausencia de información, no de gravedad | 0,1% |

Las tres últimas filas comparten «sin barra» y no significan lo mismo. Esa distinción vive en el
fondo y es exactamente el trabajo que la decisión 6 le asigna.

**Dos asignaciones son propuestas de esta spec y no decisiones del usuario**, y se marcan para que
puedan revocarse sin releer la conversación:

- `Debe Iniciar esta Semana` → **atención**. Su hermano *con restricciones pendientes* va a urgente;
  lo que los separa es si algo lo bloquea. Si «le toca esta semana» ya es urgente en obra, se mueve.
- `En Liberación de Restricciones` → **atención**. Parece el sustituto vivo de
  `Con Alerta Restricciones`, que el contrato declara como atención y que **no existe en ninguna de
  las 65 549 filas**.

### La regla que sobrevive del contrato actual

`82832685` fijó que **el matiz desempata en todos los niveles menos en el crítico**, y su prueba lo
vigila. Este diseño es compatible: el matiz sigue viviendo en el fondo, la gravedad se muda a la
barra, y el crítico conserva su excepción.

## Lo que este frente NO hace

- **No repara nada del inventario de DS-F0.** Reparar es DS-F2.
- **No sanea `state-semantics.json`.** El hallazgo de que el 51,1% de las filas tiene un estado
  que el contrato no declara **excede este frente**: está en la mesa del usuario por vía de la
  coordinadora, y si vuelve como ampliación de alcance, será explícita. Aquí se documenta, no se
  arregla.
- **No decide qué pasa con `Capítulo`.** Los 14 583 capítulos ocupan la columna `Estado` sin ser un
  estado, y son la causa de las 7 705 filas «vacías» (el 100% de ellas tiene `Titulo = 1`).
  Sacarlos es la resta más limpia disponible, y toca datos guardados en 16 proyectos: es decisión
  del usuario y probablemente frente propio.
- **No implementa CSS.** El entregable es el contrato; su implementación es DS-F2.

## Restricciones duras que hereda

Ninguna es negociable por diseño; cualquiera de ellas puede invalidar una propuesta.

- **Los valores de estado están persistidos.** `pg_calculate_status()` escribe `Estado` en
  `programa_consolidado` en cada guardado, y los leen `LpsService`, `GeneralApiController`,
  `SemanalApiController`, `ProgramChangeDetector`, `ReportProcessor` y `test_weekly_governance.php`.
  Renombrar un valor es migración de datos con respaldo, dry-run y gate, no un cambio de etiqueta.
  **«Fuera de Ventana» debe declarar si es etiqueta de presentación o valor persistido.**
- **`tests/design-system/state-tint-ladder.test.mjs` fija los ocho hex exactos** y prohíbe un
  noveno. Un ancla nueva es un cambio de vocabulario del contrato.
- **`tests/browser/ops-state-chip-hue.mjs`** exige que el fondo del chip sea el tinte de su matiz.
- **Los goldens de imagen** de `programacion-intermedia.visual.mjs` y `programa-general.visual.mjs`
  se mueven con cualquier cambio de color, y regenerarlos **exige aprobación visual explícita**.
- **`docs/design-system/state-tint-exceptions.json`** tiene siete entradas medidas contra esos hex.
- **Ya se intentó una escala de tres pasos por familia y falló**, medida el 2026-07-28: separación
  máxima de 1,012:1 y ΔE-OK 0,0168 — fondos bit-idénticos. Repetir el método daría lo mismo.
- **`pg_calculate_status` no mira restricciones en absoluto.** Sus entradas son título, ejecutado y
  tres fechas. Cualquier estado de alerta por restricciones en Programa General **no existe hoy** y
  tendría que implementarse, no configurarse.

## Lo que no se puede medir todavía

El contraste real de la barra contra cada fondo, y su comportamiento en `hover`, `focus` y
`selección`, son medición de navegador y dependen del carril de gates. Se dejan marcados, no
rellenados con cifras indefendibles. Las tres sondas de `goals/bug-coloreado-severidad/evidence/`
sirven para medir cualquier propuesta antes y después, computado contra computado.

## Posture

- **No tocar `state-semantics.json`** hasta que la ampliación de alcance sea explícita.
- **No tocar ningún baseline ni golden.**
- **No implementar CSS**: el entregable es el contrato.
- **No renombrar valores persistidos** sin decidir antes si el cambio es de presentación o de datos.
- **Sin dependencias nuevas.**
- **Prefijo `ds-f1a` en todo `.md` nuevo**, por la colisión de wikilinks del vault-en-raíz.

## Leer primero

- `docs/design-system/state-semantics.json` — el contrato ejecutable vigente.
- `goals/bug-coloreado-severidad/insumo-ds-f1.md` — lo ya medido, para no volver a medirlo.
- `decisiones/vocabulario-estados-cascada.md` — D-VOC-1 a D-VOC-4 y las respuestas del usuario.
- `docs/design-system/auditoria/transversal.md` — los gates que no ven su propia deuda.
- `docs/ESTADOS-PG-PI-PS.md` — la cuarta autoridad, que declara legacy lo que este frente recupera.

## Condición de hecho

Un documento de contrato en `docs/design-system/` que declare, sin ambigüedad y sin placeholders:
el vocabulario de estados con su nivel, la definición de los tres niveles, la regla de los dos
canales (fondo = identidad y horizonte; barra = gravedad), y qué queda explícitamente fuera. Cada
estado con su porcentaje medido y su origen. Las dos asignaciones propuestas marcadas como
revocables. Cero cambios en código de producto y cero cambios en baselines.

Verificación: `npm run test:design-system:static` y `npm run test:wiki`, ambos en verde sobre el
sha que se publique.
