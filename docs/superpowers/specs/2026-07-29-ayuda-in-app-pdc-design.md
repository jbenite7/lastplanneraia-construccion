---
capa: fuente
tipo: spec
estado: vigente
fecha: 2026-07-29
areas: [pdc]
fuente: docs/superpowers/specs/2026-07-29-ayuda-in-app-pdc-design.md
resumen: goals/pdc-preparar-b1 - Origen: Comité del 2026-07-29 — planteado por Daniela Betancur y respaldado por Tomás, que ya tiene el patrón funcionando en el visor…
---

# PDC v2 — Ayuda dentro de la aplicación — Design

- **Fecha:** 2026-07-29
- **Ola:** 2 (después del lanzamiento)
- **Goal:** `goals/pdc-preparar-b1`
- **Origen:** Comité del 2026-07-29 — planteado por Daniela Betancur y respaldado por Tomás, que ya tiene
  el patrón funcionando en el visor de cronogramas.
- **Estado:** **implementado y en `main`** (fila 6 del tablero, serie `b4294f3`…`5e80112`). Ocho pantallas con ayuda y recorrido omitible; la lectura del revisor ajeno la dio por cumplida Felipe el 2026-07-30.

## Problema

Textual, de la persona que recibe las preguntas:

> «Siento que al avanzar en tantas cosas también debemos dejar muchos instructivos. Ya la gente pregunta
> y pregunta y pregunta: y cómo hago esto. Me dicen que usted la maneja, y no, la verdad no sé.»

El módulo llega a dos obras a la vez con capacitación obra por obra. La capacitación se olvida; la
pregunta vuelve tres semanas después, en la pantalla donde la persona está atascada, y no hay a quién
preguntarle.

Existe además un patrón que la empresa ya va a conocer: el símbolo de ayuda que Tomás puso en el visor de
cronogramas.

## Decisión

**Las dos formas, porque atienden a dos personas distintas:**

1. **Botón de ayuda por pantalla** — el patrón del visor de cronogramas, replicado en cada pantalla del
   PDC. Atiende al que vuelve con una duda puntual.
2. **Recorrido guiado la primera vez** — se dispara al entrar por primera vez al módulo. Atiende a la
   primera impresión, que es el momento en que la gente decide si la herramienta le sirve.

Descartado como opción única: un instructivo largo del módulo entero, que obliga a buscar dentro de él
justo cuando la persona está bloqueada.

## Alcance

### Entra

- Símbolo de ayuda en cada pantalla del PDC: Importar, Visor, Comparar, Maestro, Paquetes, Sin frente,
  Plan, Seguimiento y Vencimientos.
- El contenido de cada ayuda responde tres cosas, en ese orden: **qué hace esta pantalla · qué tengo que
  hacer yo aquí · qué pasa después**. Sin jerga; en el vocabulario del `GLOSARIO.md`.
- Recorrido guiado de la primera visita, **omitible**, que no vuelve a aparecer una vez completado u
  omitido, y que se puede relanzar desde la ayuda.
- **Regla de proceso:** una pantalla no se da por cerrada hasta que tiene su ayuda escrita. Es lo que
  evita que esto se convierta en una deuda que nadie paga.

### No entra

- Centro de ayuda, buscador o base de conocimiento.
- Vídeos.
- Ayuda contextual campo por campo.

## Arquitectura

- El contenido vive **en el repositorio**, versionado junto a la pantalla que describe — no en una base de
  datos ni en un servicio externo. Cambiar una pantalla y su ayuda es un solo commit y una sola revisión.
- El componente de ayuda es **uno solo**, reutilizado; recibe el contenido de la pantalla. Nada de un
  panel por vista.
- El estado del recorrido («ya lo vi») se guarda por usuario en el navegador, como ya hace el estado de la
  barra lateral.
- Visual: primitivas y tokens del sistema de diseño existentes. Ni colores ni tipografías propias.

## Condición de hecho

1. Las nueve pantallas tienen su botón, y cada texto responde las tres preguntas.
2. Entrar por primera vez lanza el recorrido; omitirlo lo cierra y no reaparece al recargar; se puede
   relanzar desde la ayuda.
3. Un revisor que no conoce el módulo lee las ayudas y logra recorrer el flujo completo sin preguntar.
   Ese es el hecho de verdad — el resto es que los botones existan.
4. Accesibilidad: la ayuda se abre y se cierra con teclado y el foco vuelve a donde estaba.
5. Regresión: los e2e de las pantallas afectadas siguen verdes con el recorrido desactivado.

## Riesgos

- **La ayuda envejece peor que el código.** Una ayuda que miente es peor que ninguna. La regla de «no se
  cierra la pantalla sin su ayuda» aplica también a cambiarla: si una pantalla cambia y su ayuda no, el
  cambio no está terminado.
- **El recorrido puede estorbar** a quien ya sabe. Por eso es omitible en el primer clic y nunca
  reaparece solo.
