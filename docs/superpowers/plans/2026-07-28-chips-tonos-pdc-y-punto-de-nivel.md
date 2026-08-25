---
capa: fuente
tipo: plan
estado: derogada
fecha: 2026-07-28
areas: [pdc]
fuente: docs/superpowers/plans/2026-07-28-chips-tonos-pdc-y-punto-de-nivel.md
resumen: Con la fase anterior el sistema de estado quedó con dos ejes —nivel para prioridad de acción, matiz para identidad— y una escalera compartida en la capa de…
---

# Adoptar los tonos de PDC y el punto de nivel en todos los chips

> **TRASPASO 2026-07-28 · de la sesión «Chips y matices» a «F1 dark-mode fase estilos CSS».**
> Este plan vivía en `~/.claude/plans/attach-jolly-ritchie.md`, fuera del repo. Se copia aquí para
> que el traspaso no dependa de un archivo de sesión. Lo de abajo es el plan tal cual; lo de este
> bloque es lo que cambió DESPUÉS de escribirlo y que lo corrige en varios puntos.

> La fase anterior de este plan (T0–T5: escalera de tintes, matiz como eje del contrato,
> primitiva de componentes) está completa y en `main`: `82f5ea4`, `29fdf9e`, `78551cd`,
> `efcab97`, `ed8f90b`, `31db7ca`, `56f8b5f`.

## Medición en vivo del 2026-07-28 — LEER ANTES DE EJECUTAR

Medido con `getComputedStyle` sobre Docker, 1180×820 dark, proyecto Da Porto. Página de estado
publicada en <https://claude.ai/code/artifact/d4854414-b446-4960-9492-b9a933e2d0bb>.

**Lo que abarata el trabajo:**

- **El punto YA existe en los cuatro módulos.** Cada chip lleva `<span class="indicator">` y ocupa
  sus 5 px. En Intermedia (8) y Semanal (5) pinta `rgba(0,0,0,0)`: son trece puntos con estructura
  puesta y sin color. **F3 no tiene que añadir marcado, solo color.** El plan asumía que Intermedia
  y Semanal «no tienen punto» y eso es falso.

**Lo que corrige al plan:**

- **La leyenda de Programa General NO es `.aia-chip` a secas, es `.aia-chip.pg-filter-chip`.**
  Un `querySelector('.aia-chip')` devuelve `<span id="save-status">Guardado</span>`, la insignia de
  guardado. Medir con el selector corto da resultados falsos — me pasó.
- **Programa General tiene DOS juegos de leyenda con etiquetas distintas para el mismo
  `data-filter`**: «Por Iniciar» vs «Debe Iniciar», «Completada» vs «Terminada», «En Ejecución» vs
  «En Curso», «Con Restricción Pendiente» vs «Con Alerta Restricciones»
  (`views/programa-general/programa_general.view.php:74-80` y `:84+`). Hay que decidir cuál manda.
- **La leyenda de Semanal enseña 5 de los 10 estados del contrato** (faltan los cinco de
  calificación). F5 debe decidir si los cinco ausentes entran o si el contrato sobra.
- **Los siete estados de `/pdc` NO tienen campo `key` en `state-semantics.json`**, a diferencia de
  los otros módulos. Cualquier test que cruce contrato y DOM por `key` fallará ahí.

**Defectos medidos que las tareas deben cerrar:**

| Módulo | Medido | Tarea |
|---|---|---|
| General | Los 7 chips comparten fondo `#202d26`; el matiz solo vive en el punto | F4 |
| General | `Con Alerta Restricciones` y `Debe Iniciar` pintan el MISMO punto `#bb913a` pese a ser ámbar y naranja en el contrato | F3 |
| General | `Terminada` y `Sin Datos` usan `#4a5850`, invisible sobre su fondo | F3 |
| Intermedia | Fondos = anclas exactas; texto uniforme `#f7faf8`, sin tintar | F3/F4 |
| Semanal | Fondos mezclados, NO anclas: mide `#3f1615` donde el ancla es `#431414` | F5 |
| Semanal | `Condiciones Pendientes` y `Por Comprometer` son idénticos al píxel (`#373811`) | F5 |
| PDC | 5 de 7 puntos pintados, del vocabulario viejo `--aia-*`; el de `Información pendiente` es GRIS (`#a4a4a4`, croma 0) pese a fondo violeta | F3 |

**El contraste no es el problema:** los 27 chips cumplen AA de sobra (8,88–14,95:1). Lo que falla es
distinguir un estado de otro, y ningún test de contraste lo ve. No perseguir contraste en F3–F5.

## Colisiones de significado pendientes (Task 4)

Cuatro matices no significan lo mismo en los cuatro módulos:

- `neutral`: en General es `Terminada` —un final sano—; en PDC e Intermedia es un estado que pide
  atención. Sentidos opuestos.
- `blue`: sano en PDC y General, «requiere atención» en Intermedia.
- `orange`: «fuera de plazo» en PDC/Intermedia/Semanal, pero en General es `Debe Iniciar`, que no
  está vencida.
- `violet`: «sin datos» en PDC y General, pero en Intermedia es `Inicio por Habilitar`, un estado
  real del flujo con datos completos.

## Contexto

Con la fase anterior el sistema de estado quedó con dos ejes —nivel para prioridad de acción,
matiz para identidad— y una escalera compartida en la capa de tokens. Pero quedó a medias en tres
cosas que este plan cierra:

1. **Dos pendientes declarados.** Los renderers de Handsontable de Intermedia y Semanal siguen
   coloreando por clases en el `td` en vez de consumir `data-aia-hue`; y la leyenda de Semanal
   miente sobre la grilla (`programacion-semanal.css:2443` pinta `ps-alert-high` y
   `ps-alert-medium` con el mismo `--ps-high-bg`, pero los tokens difieren: ámbar 22 % vs 14 %).
2. **Los tonos.** Los de PDC (`#33204a`, `#431414`, `#452a0d`, `#3a3a0f`, `#173d26`, `#17334f`,
   `#2b2f2d`) son limpios y saturados porque se eligieron y midieron a mano. Los de la escalera se
   derivan con `color-mix` contra una superficie con alfa, y por eso salen apagados y agrisados
   (`#562522` es un rojo pardo, no un rojo). Se adoptan los de PDC como anclas.
3. **El punto.** De los 27 chips de estado, **15 no tienen punto**: los 8 de Intermedia, los 5 de
   Semanal y 2 de PDC (`completed-late`, `not-started`). La causa es que `buttons.css:1019-1033`
   solo declara el punto para 16 nombres de clase, y ninguno de PI/PS está en esa lista — de hecho
   11 de esos 16 son clases muertas que ya no existen en ninguna vista.

**Resultado buscado:** un solo sistema de chip en toda la aplicación, donde el fondo lleva el
matiz y el punto lleva el nivel, con los tonos limpios de PDC como base.

## Decisiones tomadas

| Decisión | Elegido |
|---|---|
| Generación de pasos | OKLCH desde el ancla, conservando matiz y croma del tono de PDC |
| Modelo de chip | Tintado + punto en todos, **incluido Programa General** |
| Programación Semanal | Entra a la escalera con su propio tramo tenue: chip en paso intenso, fila en paso tenue |
| Alcance | Las 4 leyendas operativas + el laboratorio + el selector de proyectos |

**Corrección técnica a registrar:** «variar solo la luminosidad» no funciona sobre estas anclas.
`#431414` está en L≈0,28 y el fondo de página en L≈0,19; al 0,74 el paso queda indistinguible del
canvas. Sobre oscuro el margen está en el **croma**. El eje exacto (croma, luminosidad, o ambos con
L ligeramente al alza) se fija por medición en F2 con dos restricciones duras: cada paso conserva
≥3:1 contra el fondo de página y ≥1,3:1 contra su paso vecino. Va también al grill.

## F0 · Grill de Plannotator — RESUELTO

Las 14 se respondieron confirmando la recomendación, sin respuestas libres ni dudas abiertas.
Ninguna cambia la arquitectura del plan, así que no hace falta reescribirlo.

| # | Pregunta | Decidido |
|---|---|---|
| 1 | Eje de la escalera | **Bajar croma, luminosidad fija** |
| 2 | Ancla de `teal` | Adoptar el `#134841` actual |
| 3 | Tamaño del punto | **8 px** vía `--ds-space-2` |
| 4 | Qué comunica el punto | **El nivel**, en los cuatro módulos |
| 5 | Texto tintado | Sí: cada ancla lleva su texto |
| 6 | 11 reglas muertas de punto | Podarlas aquí |
| 7 | Borde del chip | Retirar el `!important`, borde neutro tokenizado |
| 8 | Vocabulario del punto | `--ds-state-accent-<nivel>`, derivado en OKLCH |
| 9 | Filtro activo | Atenuar los inactivos con `.inactive-filter` |
| 10 | `.count-badge` | Neutro, tokenizado sin `!important` |
| 11 | Nivel en Semanal | Por etiqueta, desde el `priority` de `WEEKLY_ALERT_MODEL` |
| 12 | Pasos por matiz | **3 × 8 = 24 tokens** |
| 13 | Selector de proyectos | `Activo`/`Inactivo` con punto; dominio con matiz **sin** punto |
| 14 | Baselines | Solo tras revisar cada ruta; **no** tocar las rojas ajenas |

Bundle en `grill-chips.json` del scratchpad de sesión.

<details>
<summary>Las 14 preguntas, como se plantearon</summary>

1. **Eje de la escalera.** Sobre anclas ya oscuras, ¿los pasos quietos bajan croma, bajan
   luminosidad, o ambos? (Ver corrección técnica arriba.)
2. **Ancla de `teal`.** El catálogo declara 8 matices pero PDC solo aporta 7 — no tiene teal.
   ¿Se adopta el `#134841` actual como ancla, o se elige un teal nuevo a juego con los otros 7?
3. **Tamaño del punto.** Hoy son 5×5 px con `!important` desde `buttons.css:983`. A ese tamaño un
   matiz oscuro es casi invisible. ¿Crece, y a cuánto?
4. **Qué comunica el punto.** El modelo dice nivel, pero Programa General hoy lo usa para el matiz.
   ¿Se confirma punto = nivel en los cuatro módulos?
5. **Texto tintado.** PDC empareja cada tinte con su propio texto claro tintado (`#ffcdc8`,
   `#ffd7a8`…); Intermedia y General usan `--ds-active-text-primary` para todos. ¿El texto tintado
   viaja con las anclas?
6. **Las 11 reglas muertas** de punto en `buttons.css` (`pending-future`, `on-track`, `completed`,
   `should-start-*`, `started-*`, `pending-1-week`…). ¿Se podan en este trabajo?
7. **El borde anulado.** `buttons.css:980` fija `border: 1px solid oklch(0% 0 0 / 0.1) !important`
   y con eso los 7 bordes cromáticos de PDC no pintan (`tokens.css` lo avisa en un comentario).
   ¿Se retira para que se vean?
8. **Vocabulario del punto.** Hoy los puntos de PDC salen de `--aia-*` (paleta de marca en OKLCH,
   vívida) y los de General de `--ds-*`. ¿Se unifica, y en cuál?
9. **Estado activo del filtro.** Con el chip ya tintado en reposo, el truco actual de General
   —recuperar el tinte al activar— deja de servir. ¿Borde, anillo, elevación?
10. **`.count-badge` dentro del chip.** Hoy neutro con `!important`. Sobre un chip tintado,
    ¿se queda neutro o toma el matiz?
11. **Semanal: 5 clases, 10 etiquetas.** `ps-alert-medium` cubre etiquetas de fases distintas.
    Si el punto lleva el nivel, ¿basta la clase o hay que emitir el nivel por etiqueta?
12. **Cuántos pasos por matiz.** Chip intenso + fila tenue + los 3 rojos y 3 ámbares que Intermedia
    necesita. ¿4 pasos × 8 matices = 32 tokens, o solo los consumidos con huecos informativos?
13. **Selector de proyectos.** ¿Qué matiz para `Preconstrucción`/`Construcción` y para
    `Activo`/`Inactivo`? No son estados de obra; forzarles señal de estado puede sobrecargarlos.
14. **Baselines.** Los cuatro módulos cambian de aspecto. Las de General e Intermedia ya están rojas
    por un reflow ajeno. ¿Se regeneran ahora o se espera a que ese reflow se arregle?

</details>

## Estado de ejecución

En `origin/main`: `9f6de25` (leyenda de Semanal honesta), `cf8cf20` (celda de Intermedia),
`9c25280` (celda de Semanal), `8e5720e` + `e32e7e8` (la paleta baja a 8 matices, uno por estado).

**Lo que la medición del 2026-07-28 destapó y reorienta el plan:** los cuatro módulos tienen hoy
cuatro implementaciones distintas de chip, y el usuario no puede saber cuál es la canónica porque
no hay una.

| Módulo | Chip | Punto | Valores del fondo |
|---|---|---|---|
| `/pdc` | tintado con las anclas | 5 de 7, del vocabulario viejo `--aia-*`, sin relación con nivel ni matiz | anclas exactas |
| `/programacion-intermedia` | tintado con las anclas | ninguno (el punto toma el color del fondo) | anclas exactas |
| `/programacion-semanal` | tintado | ninguno | mezcla al 88 %, **no** las anclas (`#3f1615` vs `#431414`) |
| `/programa-general` | **neutro**, los 7 en `#202c26` | 7 de 7, vivos, con dos pares repetidos | el matiz solo vive en las filas |

Y hay dos componentes de chip conviviendo: `.aia-chip` (primitiva del design system, la usan
Programa General y el selector de proyectos) y `.pdc-legend-item` (clase legada, la usan las tres
leyendas operativas).

## Decisiones nuevas (2026-07-28)

1. **El matiz significa lo mismo en los cuatro módulos.** Hoy no: `orange` es «Contratación
   atrasada» en /pdc pero «Debe Iniciar» en General, que ni siquiera está vencida; `blue` es «en
   curso» en /pdc y General pero «En Ejecución Pendiente» en Intermedia. Se define el significado
   de los 8 y los módulos se ajustan, aceptando que algún módulo vuelva a repetir matiz
   internamente donde sus estados sean un gradiente ordinal genuino.
2. **La convergencia del componente de chip queda en suspenso** hasta que el usuario vea el mapa.

## Tareas

### Task 3 · El artefacto pasa a ser una página de estado

Es lo que desbloquea la decisión pendiente, así que va primero.

Archivo: `estados-vs-contrato.html` del scratchpad de sesión, publicado en
`https://claude.ai/code/artifact/d4854414-b446-4960-9492-b9a933e2d0bb` (republicar con el mismo
`file_path` conserva la URL).

Hoy el documento miente por partes: las secciones de Intermedia, Semanal y General siguen mostrando
la medición original, de antes de todo el trabajo, mientras la cabecera ya habla de la paleta de 8
matices. Se reescribe entero como respuesta a dos preguntas:

- **¿Cuál es el chip canónico?** Que se vea que hoy hay dos componentes y cuál usa cada módulo.
- **¿En qué estado está cada módulo?** Una fila por módulo: qué chip usa, cómo se ve medido hoy,
  y qué le falta.

Con dos tablas nuevas: la de los 8 matices contra los cuatro módulos —que es donde se ven las
colisiones de significado— y la de los estados de cada módulo con su color real medido.

Los valores se toman de una medición fresca en el navegador, no del historial de esta sesión.

### Task 4 · Significado universal de los 8 matices

- **Test primero:** un test estático que exija que cada matiz tenga un significado declarado en
  `docs/design-system/state-semantics.json` y que ningún módulo lo contradiga.
- Definir qué significa cada uno de los 8. Punto de partida medido, con los encajes que ya son
  ciertos hoy: `red` = vencido o bloqueado (los 4 coinciden), `amber` = requiere acción antes del
  siguiente hito (los 4), `green` = controlado o a tiempo (los 4). Los desencajes a resolver son
  `orange` (General), `blue` y `violet` (Intermedia), y `neutral` (PDC e Intermedia).
- Los tres estados de «Alistamiento» de Intermedia son un gradiente ordinal por semanas hasta el
  hito; bajo un significado universal caen en el mismo matiz. Es la repetición interna que el
  usuario aceptó: documentarla en el contrato en vez de disimularla.
- Retirar del contrato la regla que hoy afirma la universalidad sin cumplirla, y reescribirla con
  el alcance que de verdad tenga.

### Task 5 · Convergencia del componente de chip

**Bloqueada hasta que el usuario decida con la página de estado delante.** Las opciones que quedaron
sobre la mesa: migrar las tres leyendas a `.aia-chip`, o igualar la apariencia conservando las dos
clases.

### Task 6 · Punto de nivel en todos los chips

Como estaba planeado, pero ahora se sabe el punto de partida real: /pdc tiene 5 puntos del
vocabulario viejo, Intermedia y Semanal ninguno, y General los 7 con dos pares repetidos
(`--pg-dot-alert` y `--pg-dot-due` son la misma expresión).

Cierra además el hallazgo abierto de la revisión de Task 2: `programa-general-legend-hue.mjs`
asierta el punto contra el **nivel** y a `sin-datos` como acromático, así que **pasa en verde con
cuatro desajustes presentes**. Es el cuarto «test que no puede fallar» de esta serie y hay que
reescribirlo.

### Task 7 · Chip tintado en Programa General

Como estaba planeado. Es lo que hace visible el matiz en el único módulo donde hoy no se ve.

### Task 8 · Laboratorio y selector de proyectos

Como estaba planeado.

### Task 9 · Cierre

`impeccable audit` + `critique`, `plannotator review`, navegador en las cuatro rutas.
Pendientes menores heredados de la revisión de Task 2: el comentario falso de
`programacion_intermedia/hot.js:430` (afirma que `neutral` no toma tinte, y sí lo toma), y el de
ambas hojas que dice que «los bordes codifican el nivel», que la medición desmiente.

## Fuera de alcance

- El export XLSX (`ReportController.php`): Excel es un documento blanco y no consume la paleta oscura.
- Los otros 176 hex claros de `styles.css` que no son chips (botones, cabeceras de Handsontable).
  Merecen tarea propia, junto con añadir `styles.css` a un `pathBudget` — hoy no está en ninguno,
  y es la razón de que el residuo claro se acumule sin que ningún gate lo vea.
- Mobile, tablet y tema `linen`: prohibidos por `AGENTS.md`.
- Los archivos sucios de sesiones paralelas en el worktree: no stagear ni revertir.

## Verificación

```bash
npm run test:design-system:static
```

```bash
npx playwright test tests/browser/state-tint-ladder.mjs tests/browser/programa-general-legend-hue.mjs tests/browser/pdc-chips-dark.mjs --workers=1
```

```bash
npm run test:design-system:runtime
```

Reds preexistentes que **no** cuentan como regresión, medidos esta sesión: todas las baselines de
`design-system-lab.visual.mjs` (`actions-dark-1180x820` da 68.014 px con el árbol limpio);
`programa-general.visual.mjs` y `programacion-intermedia.visual.mjs` en 1180×820 (43.979 px y
30.812 px); el fallo de árbol limpio de `contracts.test.mjs`; y 879 errores de formato de biome.
Los golden `states-feedback-dark-*.png` no se comparan: el test retorna antes de `toHaveScreenshot`.

## Riesgos

- **F2 cambia color en producción.** Mitigación: los dos umbrales del test (contra el fondo y contra
  el vecino) convierten «se ve bien» en algo medible, y cada familia se revisa en el navegador.
- **F1b toca los dos grids más complejos.** Mitigación: un módulo por commit, y el mapping sale del
  contrato en vez de duplicarse en JS.
- **F4 toca el piloto.** Mitigación: aprobación visual explícita antes de regenerar nada.
- **Sesiones paralelas activas.** Dos commits ajenos aterrizaron a mitad de la sesión anterior y
  dejaron obsoleta una medición. Re-medir antes de cada tarea que dependa de un valor.

---

## Estado verificado — derogada

Verificado contra el código el 2026-08-25. La decisión que contiene **dejó de ser cierta**; el documento se conserva con su lápida.

**Qué dejó de ser cierto:** su premisa era armonizar el matiz entre cuatro modulos incluido /pdc, y el PDC v1 se elimino el 2026-08-04: cero rutas '/pdc' en public/index.php. Ademas su Task 3 quedaba «en suspenso» (linea 199) colgada de un artefacto externo. Lo vigente es el contrato de tres niveles ds-f1a-escala-estado

Criterio y método: [[docs/superpowers/plans/2026-08-25-estado-real-de-planes-y-specs]].
