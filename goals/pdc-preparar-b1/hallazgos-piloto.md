# Hallazgos del piloto — Da Porto, plan de compras real

Hueco reservado por el spec [`cierre-prelanzamiento-pdc`](../../docs/superpowers/specs/2026-07-29-cierre-prelanzamiento-pdc-design.md).
Cada hallazgo lleva **decisión**: se arregla en la Ola 1, se difiere con fecha, o se descarta con motivo.

## Estado del punto 6 de la condición de hecho — **no cumplido, sin contenido de Tomás**

A 2026-07-29, **Tomás no ha reportado ningún hallazgo** a esta sesión. Lo que sigue no son sus
hallazgos: son **observaciones propias** de haber tropezado con el piloto mientras se verificaba el
resto. Se registran porque afectan al lanzamiento, pero **no sustituyen** la lista del dueño del
producto. El punto 6 sigue abierto y no se da por cumplido.

Lo que sí está comprobado es que **el piloto arrancó**: el presupuesto de Da Porto se importó el
2026-07-29 a las 15:09:53 (403 actividades, 820 insumos, $29 492 804 353,65) y hay trabajo humano
encima (396 vínculos al maestro, 12 insumos asignados a paquetes, 3 paquetes planeados y con fecha,
4 correspondencias de rama confirmadas).

---

## H1 · Dos e2e de Da Porto se ponen rojos cuando la obra trabaja

**Qué se observó.** `tests/browser/pdc-v2-modalidades.spec.mjs` y `pdc-v2-sin-scroll-x.spec.mjs` fallan
sobre el estado actual del proyecto 73:

```
modalidades:  Locator: .pdc-paq-modalidad--consumo_directo  →  element(s) not found
sin-scroll-x: Locator: .pdc-grid, .pdc-grid-wrap, .pdc-grid-corta  →  element(s) not found
```

**Por qué pasa.** Los dos son specs «de solo lectura» que atacan **el proyecto real** y dan por
supuesta la riqueza de sus datos. Uno lleva escrito en un comentario «Da Porto está al 100 % por
valor»; el otro exige que las seis pantallas con tabla tengan grilla. Con el presupuesto recién
reimportado no hay paquetes de todas las modalidades ni filas en todas las pantallas, así que los
localizadores no encuentran nada. **No es una regresión de código:** ninguno de los dos importa el
sandbox, así que el arreglo de la limpieza (punto 4) no los toca, y su causa es el dato.

**Por qué importa más de lo que parece.** Mientras Tomás monte el plan, estos specs van a alternar
verde y rojo según lo que él haya cargado esa mañana. Un rojo que depende de la obra deja de ser señal
y se convierte en ruido, justo en la semana en que hace falta distinguir un bug de un dato.

**Decisión: se difiere — Ola 2, junto a la ayuda in-app (semana del 2026-08-03).** Repuntarlos al
sandbox `990100` (que es para esto) o darles su propio fixture. No entra en la Ola 1 porque no bloquea
el lanzamiento y tocarlos ahora exige decidir su escenario, que es trabajo de diseño de pruebas.
**Mitigación mientras tanto:** están nombrados aquí, de modo que quien los vea rojos sepa que es este
hallazgo y no una regresión.

## H2 · El trinquete de la brecha del motor se quedó sin punto de referencia

**Qué se observó.** `tests/test_pdc_v2_brecha_daporto.php` falla con `no hay versión 292 en el proyecto
73`. Fija en código el estado de Da Porto que A3.5 declaró canónico; la reimportación de hoy dejó una
sola versión viva, la `376`.

**Consecuencia.** Nadie está vigilando la brecha del motor. Era el único trinquete que detectaba «una
regla nueva rompió algo que ya funcionaba», y llevaba `BRECHA_MAXIMA = 7`.

**Decisión: se difiere — bloquea la Ola 2, no la Ola 1.** Hay que decidir cuál es el nuevo estado
canónico (el de Tomás cuando cierre el piloto, o un fixture congelado) y reapuntar el trinquete. No se
repunta a la versión 376 ahora mismo a propósito: mediría el motor contra 12 decisiones humanas y
seguiría pareciendo verde, que es peor que estar rojo. Diagnóstico completo en
[`evidence/cierre-prelanzamiento-2026-07-29.md`](evidence/cierre-prelanzamiento-2026-07-29.md).

## H3 · El panel de correspondencias estaba escribiendo dos textos pegados

**Qué se observó.** Al fotografiar el panel por primera vez —el punto 1 del spec— el botón que lo
despliega leía:

```
▸ Correspondencias del presupuesto con el cronograma4 confirmadas · ninguna rama pendiente
```

Las clases `.pdc-plan-panel-toggle` y `.pdc-plan-panel-resumen` no tenían **ninguna** regla en
`pdc-app/src/styles.css`: el resumen se pegaba a la etiqueta sin separación. Es exactamente el tipo de
cosa que un e2e verde no ve y una captura sí, que era el motivo de este punto.

**Decisión: arreglado en esta ola.** Dos reglas (`display: inline-flex` + `gap: 8px` en el toggle, y el
tamaño del resumen), bundle reconstruido y recapturado. Ver las dos capturas en `evidence/`.

## H4 · `/pdc` sigue teniendo tres radios fuera de la escala de `DESIGN.md`

**Qué se observó.** El hook de diseño señala tres `border-radius: 6px` en `pdc-app/src/styles.css`
(líneas 675, 686 y 698) que no están en la escala documentada. Son **preexistentes**: están en zonas
que esta sesión no tocó.

**Decisión: se descarta para este goal, con motivo.** La decisión del usuario registrada en el
[`goal.md`](goal.md) es **no abrir más trabajo de diseño sobre la pantalla del PDC viejo**, que se
retira en la Ola 3 (C1). Pulir tres radios de una pantalla condenada es trabajo que se borra solo. Se
deja escrito para que la retirada no lo pierda de vista.

---

## Cuando Tomás reporte

Añadir aquí, con el mismo formato: qué se observó (literal, con su pantalla), por qué pasa si se
averigua, y la decisión. Si un hallazgo es un **bug real de datos**, sube a bloqueante del lanzamiento
y se dice el mismo día — no al cierre de la ola.
