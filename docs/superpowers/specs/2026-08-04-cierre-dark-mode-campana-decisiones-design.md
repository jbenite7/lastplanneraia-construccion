# Spec — Campaña de cierre de dark mode: las 54 decisiones convertidas en trabajo

**Fecha:** 2026-08-04 · **Estado:** aprobado en diseño, pendiente de plan
**Fuentes:** `docs/superpowers/decisiones-pendientes-2026-08-03.md` (A-1..A-3, B-1..B-2, C-1..C-49),
`docs/superpowers/barrido-diseno-2026-08-03.md` (8 pasadas),
ledger `.superpowers/sdd/2026-08-03-cierre-dark-mode-fases-0-3/progress.md` (ciclos 1–38).
**Decisiones del usuario:** grilleo del 2026-08-04 en esta sesión, una pregunta por vez. Cada
disposición de abajo cita la decisión que la autoriza.

## Objetivo

Cerrar el registro de decisiones pendientes del cierre de dark mode: cada una de las 54 entradas
queda **ejecutada, convertida en chip para otra sesión, o cerrada explícitamente**, según lo que el
usuario decidió. Un solo plan por fases ordenado por dependencias (decisión: estructura A).

## Premisa de diseño vinculante

Diseño inspirado en Apple —claridad, deferencia al contenido, jerarquía por espaciado y tipografía
en vez de por cromo, materiales sobrios, un solo acento por vista, controles discretos que ganan
presencia solo al necesitarse— expresado íntegramente con el design system de AIA en dark mode:
tokens `--ds-*`, nada de hex (tampoco en comentarios: el audit los cuenta ahí), primitivas `aia-*`.
No es copiar macOS/iOS: es adoptar sus principios dentro de la identidad AIA.
Referencia: `memoria/decisiones/inspiracion-apple-en-dark-aia.md`.

## PDC V1 deprecado — fuera de alcance (decisión del usuario, 2026-08-04)

El usuario deprecó **PDC V1**: los módulos `listado-actividades/`, `contratos/` y el `pdc/` viejo
(`public/js/modules/pdc/`, `public/css/pdc.css`, ruta `/pdc`). **Ninguna task de esta campaña lo
toca, lo mide ni lo verifica.** No confundir con **Plan de Compras v2** (`/plan-compras`,
`pdc-app/`, `src/Services/Pdc/`), que sigue vivo y sí está en alcance.

Consecuencias sobre este spec, aplicadas abajo:

| Entrada | Disposición |
|---|---|
| **C-3** (borde-acento del toast de `pdc.css:318`) | Cerrada — no aplica: módulo deprecado. No se declara excepción para un archivo que se retira. |
| **C-10 + C-43** (chips de filtro del PDC: teclado, `aria-pressed`, región de estado) | Cerradas — no aplica: módulo deprecado. Retirada la task F4-3 entera. |
| **C-36** (34 etiquetas del modal de contrato) | Cerrada — no aplica: pertenece a `contratos/`. Retirada la task F5-3 entera. |
| **C-31, parte PDC** («ESTADO DEL PROCESO» ocultando «71 días de retraso», 54 truncamientos) | Cerrada — no aplica. F3-4 se queda con PG, PI, PS y Subcontratistas. |
| **C-44** (filas de capítulo) | Ya ejecutada sobre `/pdc` antes de la deprecación (`1e479a94`); inocua. El valor vivo pasa a la task de paridad en PG y PI. |
| **C-40** (racimos tipográficos) | Ya ejecutada; incluía `pdc.css` en su barrido. Sin trabajo adicional. |

## Reglas transversales (no negociables)

- Desktop ≥1180 px, dark only; viewport canónico 1180×820 (AGENTS.md). Nada de mobile, tablet ni
  tema `linen`.
- Sesión local siempre por `/dev/entrar` (nunca `/login`). Proyectos con datos —«Da Porto» y
  «Optimización Aeropuerto JMC»— **solo lectura**, sin mutar datos.
- `npm run test:design-system:static` debe dar **8/8** antes de dar cualquier task por bueno.
- Sin commit/push/deploy fuera de lo pactado; hay 27+ commits locales sin subir y **no se publica**
  sin petición explícita.
- `memoria/log.md` y `memoria/trampas/drawer-en-handsontable-module.md` están modificados por otra
  sesión: **no entran en ningún commit** de esta campaña sin confirmación del usuario.
- Método: un resultado vacío, redondo o idéntico al anterior se sospecha **de la sonda** antes que
  de la aplicación; la captura de pantalla es parte de la medición, no adorno del informe.

## Verificación integrada (sustituye a los dos encargos recurrentes)

1. **Ciclo triple como gate de cierre de cada task visual:** `/impeccable audit` →
   `/ux-heuristics` (severidad Nielsen) → `/refactoring-ui` (jerarquía, espaciado, bordes), en ese
   orden estricto, sobre las superficies tocadas, a 1180×820 dark. Resultado al ledger del plan.
2. **Barrido completo al cierre de cada fase** (ya no como bucle horario suelto): las ~25
   superficies de la app principal (según `memoria/arquitectura/*.md`) más las 7 de `admin/` (vía
   `/admin/dev/entrar?u=test.A`), con las tres lentes en orden, consolidando contra
   `docs/superpowers/barrido-diseno-2026-08-03.md` y reportando solo lo nuevo o lo que cambió.
   Razón: los tres últimos ciclos del bucle libre cerraron sin aplicar nada porque todo lo restante
   esperaba decisiones del usuario, que este spec ya recoge.

---

## Fase 1 · Redes de seguridad

Primero, para que el resto del plan no avance sin protección.

| Task | Entrada | Qué se hace | Decisión |
|---|---|---|---|
| F1-1 | C-21 | Reparar los fixtures de `tests/browser/programacion-semanal-*` (34/35 fallan por entorno: `openProgrammingWeek:103`, «Project card not found: Prueba»; verificado preexistente contra árbol limpio). **Sin bajar ningún umbral ni relajar aserciones.** | «Ambas, como tasks propios» |
| F1-2 | C-47 | Guard `tests/browser/programa-general-legend-hue.mjs`: investigar el histórico del token (`oklch(... calc(c*1.4) ...)`) para distinguir (a) el punto perdió croma → se corrige el token, de (b) el piso de 35 % se fijó alto → se corrige el guard. Re-medir con el chip ya teñido (el fondo cambió tras la paridad de chips de PG). | Ídem |

## Fase 2 · Goldens (A-1, el nudo)

| Task | Entrada | Qué se hace | Decisión |
|---|---|---|---|
| F2-1 | A-1 | Presentar al usuario el par **esperado / actual / diferencias** de cada golden de tabla afectado (empezando por `/programa-general`, ficheros en `test-output/programa-general.visual.mj-b4b64-…/`). **Solo tras su visto bueno explícito** se recaptura el lote. Desbloquea toda la fase 3. | «Recapturar en el plan, tras revisarlas» |

## Fase 3 · Lo que mueve el píxel

Todo posterior a la recaptura de F2-1. Cada task actualiza los goldens que su cambio mueva, con
evidencia antes/después, y cierra con el ciclo triple.

| Task | Entrada | Qué se hace | Decisión |
|---|---|---|---|
| F3-1 | A-3, C-7 | Variante B de bordes (aprobada en maqueta) llevada a Handsontable y DataTables reales: sustituir `rgb(203,213,225)` en `TD.htMiddle` (17) y `TH` (15+2) de `/programacion-intermedia` —11,96:1 contra el fondo de celda, lo más brillante del área de contenido— por tokens conforme a la premisa de deferencia. Numéricas alineadas a la derecha. De paso, verificación visual del gatillo de DataTables con ordenación activa (C-7, nunca visto en acción). | A-3 desbloqueada por A-1 |
| F3-2 | C-40 | Colapsar los **dos racimos tipográficos** (14,4/14,08/14/13,6 → 1 valor; 12,8/12,48/12,16/12 → 1 valor) en los 4 módulos del plan (`buttons.css`, `programacion-intermedia.css`, `programacion-semanal.css`, `pdc.css`). Solo tipografía: espaciado y radios **no** se tocan. La baseline del audit no debe subir. | «Solo los dos racimos» |
| F3-3 | C-44 | Filas de «Capítulo» del PDC: jerarquía por **peso tipográfico + filete superior** en vez del bloque `rgb(139,64,17)` (6,6× más brillante que una fila normal, 40 de 100 celdas). Comprobar antes que ese color no carga semántica de estado del módulo. | Recomendación aprobada en diseño |
| F3-4 | C-16, C-31, C-49p1 | **Anchos de columna, solo las que ocultan datos** (`colWidths` en JS, módulo a módulo): «Id» en PG y PI (códigos jerárquicos ambiguos: `3.5.2.1.1` = `3.5.2.1`), correos de Subcontratistas, y «Estado Operativo» por encima de 120 px para que el container query vuelva a mostrar el nombre. **Más, por añadido del usuario (2026-08-04): todas las cabeceras de PG, PI y PS deben verse enteras** — aprovechando el hueco de C-16 (la caja interna `.colHeader` desperdicia 23 px/columna ya pagados). Verificación con datos reales en solo lectura. (La parte PDC quedó fuera: módulo deprecado.) | «Ensanchar solo las columnas que ocultan datos» + añadido de cabeceras |
| F3-5 | C-48 | Gatillo de filtro de PS: **opción (b)** — completar la intención de T-5 dándole al botón su caja de 44 px (hoy: `::before` de 11×32 dentro de un botón de 13, override en `programacion-semanal.css:2496`). La (a) dejaría los tres módulos bajo el mínimo táctil de 24 px. | Asumida por recomendación, ratificada al aprobar el diseño |
| F3-6 | C-34 | Hover del botón secundario (`.aia-btn--secondary:hover`): pasa a **superficie elevada + borde más vivo** en vez del relleno verde que hoy lo hace 80 % más luminoso que el primario en reposo. Primitiva compartida: verificación en las superficies que la consumen. | Lote «Retoques UI» |
| F3-7 | C-24 | Chip contador **atenuado cuando marca cero**; con cuenta > 0 conserva su color saturado (con datos reales el color hace su trabajo: 31 atrasadas merece rojo). | Lote «Retoques UI» |
| F3-8 | C-17 | «Recargar» **y** «BI Semanal» salen del menú «Más» a la barra de PS; en el menú quedan Leyenda, Imprimir y Exportar CSV. Verificar que la barra no vuelve a desbordar a 1180 px (motivo original del menú, task 25). | Lote «Retoques UI» + ajuste del usuario («Sacar Recargar y BI Semanal») |
| F3-9 | C-29, C-23 | **Régimen de excepciones puntuales** (C-2 acotado): dos entradas justificadas, cada una con su medición escrita en el JSON correspondiente. (1) C-29: mensajes de error de admin al token crítico de texto (11,21:1 medido) vía `state-token-exceptions.json`. (2) C-23: degradado de anuncio de corte en el carril de pestañas de BI, como excepción de presupuesto de color. (C-3 retirada: era del PDC deprecado.) **No se abre la campaña de ~2.600 hallazgos de fase 6.** | «Excepciones puntuales, una a una» + lote «Admin/limpieza» |

## Fase 4 · Comportamiento y estructura

| Task | Entrada | Qué se hace | Decisión |
|---|---|---|---|
| F4-1 | C-46 | Ids duplicados de origen JS: **manda el PHP**. `cargarDatosGeneralesPagina2.js` (inyecta `Max_Semana`, `Semanal_Confirmada`, `baseDatos`, `permiso_canonico`, `semana`) y `funcionesGenerales6.js` (`Id`, `opcion`) dejan de inyectar campos que la vista PHP ya renderiza. Antes de tocar: seguir **cada lectura** de esos ids en el JS y comprobar que ninguna vista depende solo de la copia inyectada. Arregla las 4 vistas a la vez, incluida `/programa-general-actualizar` (importación de cronogramas: equivocarse aquí lee la semana equivocada — verificación propia obligada). | «Manda el PHP; el JS deja de inyectar» |
| F4-2 | C-49p2 | **Investigación de datos, no de estilo** (con `systematic-debugging`): por qué `classifyState` declara «Lista para Confirmar» con condiciones pendientes — el usuario decidió que **no pueden convivir**: si hay pendientes, no está lista. Diagnóstico primero; el remedio (regla de cálculo y/o presentación) se diseña con el diagnóstico delante y se le presenta. Nota: el contador ya quedó sin color (aplicado por la sesión anterior); esta task lleva la raíz. | «No pueden convivir: si hay pendientes, no está lista» |
| ~~F4-3~~ | ~~C-10, C-43~~ | **RETIRADA** — los chips de filtro eran del PDC V1, deprecado. C-10 y C-43 cerradas como «no aplica». | Anulada por la deprecación |
| F4-4 | C-30 | `<main>` + `h1` (sin saltos de nivel) en las once vistas que no lo declaran, copiando el patrón de `/dashboard/escalamientos` (la mejor estructura medida: un `h1`, `h2` por sección, sin saltos ni ids duplicados). Verificación de que ningún estilo colgado de los selectores movidos cambia. | «`<main>` + `h1` en las once vistas» |
| F4-5 | C-26 | La Guía Operativa **sustituye** a la vieja «Leyenda de Colores»: se borra el markup muerto (inalcanzable, verificado en vivo) y el id `modal_leyenda_colores` queda único. En el mismo lote se limpian los demás ids duplicados propios de PI (`modal_leyenda_colores_Label`, `modalEliminarLabel`; `permiso_canonico` y `Semanal_Confirmada` caen con F4-1). | «La Guía sustituye a la vieja» |
| F4-6 | C-27 | Tildes de la guía operativa alineadas con los chips de la misma pantalla («Ejecución», «Gestión», «crítica», «habilitación», «preparación», «Programación», «técnico», «Guía»). Solo el texto del modal; los nombres de estado del dominio (`GLOSARIO.md`) no se renombran en datos. | «Corregir las tildes de la guía» |
| F4-7 | C-22 | Marco alrededor del iframe de Power BI en `/indicadores` para suavizar la isla blanca (transición visual, sin `filter: invert()`). El tema oscuro del informe queda como **tarea del usuario en Power BI**, fuera del repo. | «Enmarcarlo para suavizar el salto» |

## Fase 5 · Admin, metadatos y limpieza

| Task | Entrada | Qué se hace | Decisión |
|---|---|---|---|
| F5-1 | C-38, C-25 | Terminar el adaptador de `admin/`: las 3 variantes `.btn-outline-success/info/warning` ancladas a `.dark-mode` (como el vendor, especificidad suficiente, **sin `!important`**), radio de impacto las 8 rutas de admin; y la marca «AIA» un escalón de luminancia arriba (hoy 4,46:1 vs mínimo 4,5). No migra AdminLTE: termina lo que el adaptador ya empezó. | Lote «Admin/limpieza» |
| F5-2 | C-28 | «Email (opcional)» y «Cargo (opcional)» en `/admin/usuarios/crear` (5 de 7 campos son obligatorios; marcar los dos opcionales, guía de Nielsen). | Lote «Retoques UI» |
| ~~F5-3~~ | ~~C-36~~ | **RETIRADA** — el modal de contrato pertenece a `contratos/`, deprecado. C-36 cerrada como «no aplica». Si PDC v2 monta un formulario equivalente, se audita allí desde cero. | Anulada por la deprecación |
| F5-4 | C-32 | Tabla equivalente `.sr-only` junto a cada gráfico de BI, generada **de la misma fuente que alimenta la serie** (no puede desincronizarse). | Lote «Admin/limpieza» |
| F5-5 | C-37, C-18, C-19, C-8, C-5, C-11, C-15, C-20 | **Lote mecánico:** `aria-hidden` uniforme en los 24 `.changeType` de PG (hoy 12/24); borrar `fitActionsRowSingleLine()` (código muerto con comentario falso, `hot.js:1203`); tooltip de cabecera condicionado a recorte real; migrar `state-tint-exceptions.json` de línea a firma (como su hermano del 12-bis); regenerar el sidecar `.impeccable/design.json`; auditar los media queries que solapan 1180 px (dos ya mordieron el viewport canónico); auditar los `@import … layer(x)` sobre archivos auto-encapsulados (`components.components` en `buttons.css`, `utilities` en `access.css`); auditar qué otros tokens carecen de variante oscura (raíz de C-20: `--ds-color-surface`, `--ds-color-brand-architecture` — las auditorías **reportan**, el arreglo se decide con el informe). | Diseño aprobado |
| F5-6 | C-1 | Borrar las 22 ramas viejas (censo `docs/superpowers/ramas-viejas-2026-08-03.md`: 22/22 sin contenido único, verificado por muestreo). | Lote «Admin/limpieza» |

## Cerradas sin trabajo

- **A-2** (densidad compacta): ratificada — aplicada en `67f35c4`, aprobada dos veces, reversible.
- **B-1, B-2**: caducan con la sesión que las asumió (el barrido ya no corre como bucle libre).
- **C-45** (FAB tapa la última fila): **aceptado** sin cambios, decisión explícita del usuario.
- **C-13** (chips a dos líneas): ya arreglado (`ed8c411`).
- **C-4**: parte racionalizada por el task 19; el resto cae bajo el régimen puntual de F3-9 o queda
  en la baseline.

## Fuera del plan — chips para otras sesiones

| Entrada | Por qué queda fuera |
|---|---|
| C-33 | La frase del estado vacío de Control de Cambios explica una regla de dominio que solo el usuario conoce. |
| C-35, C-39, C-42 | Comportamiento de teclado no autorizado en el grilleo. (El único paquete de teclado aprobado era el de los chips del PDC, y ese módulo quedó deprecado.) |
| C-41 | Renombrar los diez `buscador*` de `/control-cambios` toca el cableado del módulo; no fue seleccionado. |
| C-12 | Falta que el usuario diga cuál es la acción primaria de PI. |
| C-14 | Falta la observación del usuario la próxima vez que le ocurra (¿`⚠ Sin asignar` visible? ¿tooltip del chip?). |
| C-6 | Investigación de datos del sandbox (HOMECENTER), no es diseño. |
| C-9 | Cuánto texto de «Actividad» se lee de un vistazo es decisión de producto pendiente. |
| C-2 (campaña completa) | La fase 6 estructural (~2.600 hallazgos) merece su propia campaña; aquí solo rige el régimen puntual. |

## Adenda · improve-app entrelazado con la campaña

El usuario incorporó el journey `improve-app` (2026-08-04). Tracker:
`docs/IMPROVE-APP-PLAN.md`, con el intake respondido (job por rol, flujo objetivo PG→PI→PS,
evidencia = quejas directas de obra sin registro + las mediciones de esta campaña; fase 7
skipped por ser app interna, fase 8 deferred hasta evidencia de lentitud).

Encaje — sin duplicar trabajo: las fases 2 (ux-heuristics) y 4 (refactoring-ui) del journey **las
satisface el ciclo triple y los barridos de esta campaña**; sus hallazgos se vuelcan a los
artefactos del journey en vez de re-medirse. Lo genuinamente nuevo entra así al plan:

| Task | Fase improve-app | Qué se hace | Cuándo |
|---|---|---|---|
| IA-1 | 1 · jobs-to-be-done (**GATE**) | Entrevista al usuario: job por rol (Residente/Director/Gerencia) en formato «Cuando…, quiero…, para…», dimensiones funcional/emocional/social con dónde sub-entrega cada una, y alternativas (incluido el Excel paralelo / no-consumo). Produce `docs/CUSTOMER.md`. | Temprano, en paralelo a F1 — no bloquea las redes ni los goldens, pero sí las fases 3+ del journey |
| IA-2 | encaje 2/4 | Volcar los hallazgos ya medidos (C-*, barridos) a `docs/DESIGN.md` `## UX Audit Findings` con severidad 0–4, y crear `docs/EXPERIMENTS.md` con el backlog ICE. Sin re-medir nada. | Con el cierre de la fase 3 de la campaña |
| IA-3 | 3 · design-everyday-things | Lente de Norman sobre la cascada PG→PI→PS: signifiers débiles, dónde una restricción evita el error (mejor que avisar), feedback <0,1 s, deshacer en vez de «¿estás seguro?». Absorbe la revisión de C-14 (descubribilidad del motivo de retención) con la observación del usuario. | Tras F4 de la campaña |
| IA-4 | 5 · microinteractions | Auditoría Trigger/Rules/Feedback/Loops de las acciones diarias: confirmar compromisos, guardar celda, filtrar, importar cronograma. Mapa de estados (vacío/cargando/parcial/error) y **un** momento firma. | Tras IA-3 |
| IA-5 | 6 · made-to-stick | Pasada SUCCESs al copy in-app (onboarding de **Plan de Compras v2**, estados vacíos, errores, CTAs). Absorbe F4-6 (tildes) como parte del lote; C-33 sigue necesitando la frase de dominio del usuario y se pregunta aquí. | Tras IA-3 |
| IA-6 | 9 · steve-jobs-design-review | Revisión final en frío del flujo PG→PI→PS completo: el One Thing, pasos-hasta-valor, veredicto binario, lista de cortes y arreglos, y auditoría de «la parte de atrás de la valla» (vacíos, errores, 404). Cierra el journey y la campaña juntos. | Última task del plan, tras el barrido final |

Regla heredada del journey que pasa a regir toda la campaña (ya se cumplía de facto): **ningún
cambio de UI sin un hallazgo medido detrás**, y cada cambio embarcado queda como fila en
`docs/EXPERIMENTS.md`.

## Condición de hecho de la campaña

1. Las 54 entradas del registro tienen disposición final: ejecutada (con evidencia), chip creado, o
   cerrada explícitamente — ninguna queda «pendiente de criterio».
2. Suite estática 8/8; goldens recapturados y en verde tras la revisión del usuario; las dos redes
   de F1 protegen de nuevo (o su imposibilidad quedó documentada con evidencia).
3. Barrido completo final (32 superficies, tres lentes) sin hallazgos nuevos de las categorías
   tratadas.
4. Las tasks IA-1..IA-6 del journey improve-app cerradas o con estado justificado en
   `docs/IMPROVE-APP-PLAN.md` (done / deferred / skipped con razón), y los artefactos del journey
   (`CUSTOMER.md`, `DESIGN.md` ampliado, `EXPERIMENTS.md`, `POSITIONING.md`, `PRODUCT.md`)
   creados o extendidos según su fase.
5. Ledger del plan al día; `memoria/` actualizada con `ingest` al cierre.

## Riesgos señalados

- **F4-1 y F4-2 tocan la vista de importación de cronogramas y una regla de estado:** son los dos
  puntos donde un error no rompe un estilo sino un dato. Ambos llevan verificación propia y la
  investigación precede a cualquier edición.
- **F3-4 (anchos)** cambia geometría de tablas virtualizadas; se mide con datos reales antes y
  después, en solo lectura.
- **Los goldens solo se recapturan una vez revisados** (F2-1); ninguna task de fase 3 arranca antes.
