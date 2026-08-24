---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-08-24
areas: [design-system]
fuente: docs/superpowers/plans/2026-08-24-p3-design-system-contrato-y-control.md
resumen: "P3 · El programa Design System de punta a punta: DS-F1 redefine el contrato con brainstorming, DS-F2 reimplementa por adaptadores empezando por Handsontable, DataTables y legacyCards, y DS-F3 reemplaza los quince gates"
---

# P3 · Programa Design System · DS-F1 → DS-F3

> **For agentic workers:** REQUIRED SUB-SKILL: `superpowers:brainstorming` **antes** de escribir
> nada de DS-F1, y `superpowers:writing-plans` para convertir cada fase en su propio plan de
> ejecución. Este documento es el **encuadre del programa**, no la lista de pasos: cada fase pide
> su plan propio porque su alcance sale del brainstorming, no de aquí.

**Spec:** [[docs/superpowers/specs/2026-08-24-estado-consolidado-del-repo-design]]
**Depende de:** P2 completo — sin CI verde no se mide nada.

**Goal:** cerrar el diagnóstico de Felipe del 2026-08-18 — «el design system no está bien definido,
ni bien implementado, ni bien controlado» — atacando las tres mitades en ese orden.

**DS-F0 ya está cerrada y publicada** (`567e566e`): `docs/design-system/auditoria/` con 68 hallazgos
clasificados sobre un censo de 257 rutas, sin tocar código de producto. Es la entrada de DS-F1.

---

## DS-F1 · Redefinición del contrato

**Arranca con brainstorming con Felipe. El contrato es decisión de negocio, no técnica** — y esa
frase es la que ordena la fase entera: nada se escribe antes del grilleo.

Alcance: tokens, primitivas `aia-*`, escala de severidad, y **escala de stacking (z-index)** — que
es la que hoy no existe y produce bugs como el dropdown de Programación Semanal sobre el selector
de semana.

- [ ] Brainstorming, pregunta a pregunta, con los 68 hallazgos de DS-F0 como entrada
- [ ] Resolver lo que dos frentes ya cerrados dejaron **explícitamente en manos de DS-F1**:
      faltan **tokens de relleno para estados**, y `brand-construction` quedó usado como severidad
      media — DS-F1 lo ratifica o lo deshace
- [ ] Absorber la matriz diagonal de D6 como **entrada del contrato**, no como trabajo aparte
      (`MO-F4` se retiró como fase propia el 2026-08-18)
- [ ] Escribir el contrato como spec propia y pasarlo por el gate del plan

**Condición de hecho:** el contrato escrito, aprobado por Felipe, y con cada una de las escalas
—estado, severidad, z-index— cerrada con sus valores, no con un principio.

**Ya cerrado y no se re-litiga:** DS-F1a, la escala de estado, medida contra 50.966 actividades
reales (`4a152a54`), y el contrato de 3 niveles con el chip sólido portando la identidad.

## DS-F2 · Reimplementación por adaptadores

Primero lo que concentra la deuda, después módulo a módulo según DS-F0.

- [ ] **Handsontable y DataTables** — concentran la deuda del repo
- [ ] **CNP/CNC/CIC** — entrada añadida el 2026-08-20. El shell es `aia-*` pero `legacyCards.js`
      pinta **todo** con clases legacy. Es el hallazgo F0-022 (mayor), que estuvo sin dueño hasta
      esa fecha. **Los dos planes de UI-audit (2026-07-31 y 2026-08-01) quedan superados como
      vehículo**: su trabajo entra aquí
- [ ] El resto de módulos, por severidad de DS-F0

**Entra aquí, no en un frente suelto**, el pendiente del replanteo de coloreado: cabeceras de grilla
desalineadas (PI 0.75rem vs PS 0.72rem — **decisión de producto**), `overflow-wrap: anywhere` en el
chip de PI, el `1.75rem` del botón de cierre de modal en PS, y el resto del censo de 22 tablas
(Admin y vistas HTML).

- [ ] Aparte y barato: **retirar los siete `console.log('[PI-DEBUG]')` tras flag**

**Ola derivada, con fecha propia:** **Programación Semanal hereda la pieza de habilitación en una
columna**, con Intermedia ya rodada una semana en obra. Comparte las mismas cinco restricciones
duras (`programacion_semanal/hot.js:570`), así que dejarla distinta indefinidamente reintroduce el
problema que el frente vino a corregir.

## DS-F3 · Control

**Los 15 gates actuales se reemplazan, no se arreglan.** Cinco principios ya decididos:

1. Pocos, y atados a contratos que duelan
2. Nunca bloquean el flujo local, solo el merge
3. Actualizar un baseline cuesta **un comando con diff visible**
4. Todo rojo dice qué archivo y qué hacer
5. Cuarentena explícita para gates ruidosos

- [ ] Derivar los gates nuevos del contrato de DS-F1
- [ ] Retirar los andamios que P2 dejó puestos — `runtime-budgets` y los dos de `gates-al-ci` eran
      andamio declarado desde el principio
- [ ] Incorporar la matriz de D6 (ex `MO-F4`)

**Consecuencia de secuencia ya decidida:** la **Torre de Control BI no se recaptura**, se reconstruye
con enfoque de data storytelling **sobre el contrato de DS-F1**. Hacerla antes sería construirla dos
veces.

---

## Condición de hecho del programa

El contrato escrito y aprobado; Handsontable, DataTables y `legacyCards.js` migrados; los gates
viejos retirados y los nuevos en verde sobre `main`.
