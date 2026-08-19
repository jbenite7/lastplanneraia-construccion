---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-08-14
areas: [proceso, lps]
fuente: "docs/superpowers/specs/2026-08-07-f2a-piloto-movil-programacion-design.md (adenda del 2026-08-14); estado previo comprobado en 90dc5a5d~1"
resumen: "E2 decidió el modelo de tarjeta móvil el 2026-08-07 y nunca llegó al código: lo construido era justo la alternativa que esa decisión había descartado por escrito, y pasó una semana sin que nadie lo notara"
---
# Una decisión escrita no llega sola al código

El 2026-08-07, la decisión **E2** de la spec de F2A eligió para la tarjeta móvil el **modelo A —
resumen con detalle desplegable**, y descartó por escrito la ficha completa siempre abierta.

**Lo que se construyó fue la ficha completa siempre abierta.** Exactamente la alternativa
descartada. Ninguno de los dos módulos plegaba nada.

Pasó **una semana** sin que nadie lo notara. No lo destapó releer la spec ni revisar el código:
lo destapó **medir la tarjeta ya construida** el 2026-08-14 — 354×562 px por tarjeta en Semanal,
1,5 por pantalla, 31 tarjetas y unos 17.000 px de scroll para recorrerlas; 78 tarjetas en
Intermedia. La cifra fue la que hizo preguntar por qué era tan alta.

## El detalle que lo hacía difícil de ver

En Intermedia había un `details` en el código, y **no era un desplegable**:

```js
var details = document.createElement('dl');   // hot.js:4358, antes de 90dc5a5d
details.className = 'pi-mobile-card__details';
```

Era el **nombre de una variable** para un `<dl>` de campos siempre visibles. Un `grep details`
devolvía un acierto y confirmaba la creencia equivocada. La comprobación que sí distingue es mirar
**qué elemento se crea**, no cómo se llama la variable.

## La lección

**Una decisión registrada en una spec no es un hecho sobre el código.** Son dos cosas distintas y
nada las mantiene atadas: la spec no falla si nadie la implementa, y el código no avisa de que
contradice una decisión escrita.

De ahí, dos hábitos que este caso paga:

- **Antes de volver a decidir algo, mide lo construido.** Al abrir la decisión de qué iba en la
  tarjeta resultó que no había que decidir de cero: ya estaba decidido y sin aplicar. La ronda que
  siguió ([[tarjeta-movil-e2-bis]]) confirmó el modelo con dos cambios, en vez de reinventarlo.
- **Una decisión sin verificación que la fije se deshace en silencio.** Aquí no había prueba que
  comprobara que la tarjeta plegaba, así que la divergencia no tenía cómo salir a la luz.

Es la misma familia que [[condicion-de-hecho-caduca-sin-aviso]]: algo escrito que sigue pareciendo
válido porque nada lo confronta con el estado real.
