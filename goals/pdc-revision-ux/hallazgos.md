# Hallazgos de la revisión de la SPA — 2026-07-28

Feedback del dueño del producto recorriendo el módulo en el navegador (proyecto Da Porto, stack
del worktree en el puerto 8091). Registrados en el orden en que aparecieron, sin resolver ninguno.

Estado del módulo cuando se recogieron: A4 completo y mergeado a `main`, responsable como usuario
del proyecto incluido.

---

## 1. La pestaña «Ensamble» está mal nombrada

En la barra, «Ensamble» apunta a `#/ensamble/importar` y muestra solo el cargue del Excel y su
historial de versiones. Pero Ensamble es la **etapa completa**: Maestro, Presupuesto, Comparar,
Paquetes y Plan también son Ensamble. Un mismo nombre hace de etiqueta de etapa y de nombre de
pantalla, y quien lee la barra no puede saber que las otras cinco cuelgan de la primera.

**Dónde:** `pdc-nav`, ruta `/ensamble/importar`.

## 2. Texto truncado en las tablas

Se corta «102 DAPORTO RIONEGRO PI_Version…», «$ 29.492.804.3…», «Import Da Po…». Los anchos son
fijos (284px, 150px, 130px) y no responden al contenido.

**Petición:** ajuste de línea (word wrap) en todos los campos y ancho de columna dinámico según
contenido. **Alcance: todas las tablas del módulo.**

**Tensión a resolver al implementar:** envolver texto y ajustar ancho al contenido tiran en
direcciones opuestas. Un importe probablemente nunca debe envolverse; un nombre de archivo largo
probablemente sí. Hay que decidir por columna.

## 3. Adoptar la sidebar de lps-aia

Hoy el módulo trae su barra horizontal propia (`pdc-nav`) dentro del shell de lps-aia, que ya
tiene su rail lateral. Son dos sistemas de navegación conviviendo.

**Contexto:** hay tráfico ajeno en esa zona — varias sesiones tocaron el design system y el rail
durante el día (mejoras de accesibilidad del rail colapsado entraron en `main` el 2026-07-28).
Hay que mirar en qué estado quedó antes de construir encima.

**Relación:** arrastra al hallazgo 1 — si la navegación se rehace, el nombre «Ensamble» se decide
dentro de ese trabajo.

## 4. Elegir a mano la versión oficial del presupuesto

Hoy la marca «Activa» se la lleva automáticamente la última importación. Se pide poder decidir
cuál rige en cada momento, desde el historial de versiones.

**Consecuencia a resolver:** la versión activa es la raíz de todo lo demás — maestro, asignación
de paquetes y plan de fechas cuelgan de ella. Cambiarla no es mover una etiqueta: hay que decidir
qué pasa con el trabajo ya hecho sobre la versión que se abandona.

## 5. Atajo del historial al visor

Clic en una fila del historial de versiones → modal preguntando si quiero ver ese presupuesto →
si acepto, a la pestaña **Presupuesto** con esa versión ya desplegada.

Hoy hay que ir a Presupuesto y volver a elegir la versión a mano.

## 6. Selección múltiple + botón «Comparar»

Marcar versiones en el historial (**máximo dos**: la casilla se bloquea al marcar la tercera) y
pulsar «Comparar» → modal → al comparador con esas versiones cargadas.

Mismo par que el hallazgo 5: puentes desde el historial hacia pantallas que ya existen.

## 7. Tablas en cascada — revisión de UX/UI

Las pantallas apilan tablas que solo se descubren haciendo scroll. Se pide que cada tabla tenga
forma de acceso desde arriba.

**Confirmado en tres pantallas:**
- **Maestro:** Importar SINCO / Pendientes por vincular / Catálogo global (3.079 insumos).
- **Paquetes:** grilla masiva y el resto de bloques.
- **Plan:** tabla principal y «Sin frente» con 40 sugerencias debajo.

Es un problema estructural del módulo, no de una pantalla. **Método pedido:** usar las skills de
diseño frontend (Impeccable).

## 8. Visor del presupuesto: selector de nivel y abrir desplegado

Hoy arranca colapsado en dos filas (COSTO DIRECTO / COSTO INDIRECTO). Se pide (a) un selector de
**hasta qué nivel** se visualiza — capítulo, subcapítulo, grupo, actividad, insumo — y (b) que por
defecto llegue desplegado.

**A decidir:** las dos piezas son la misma. Abrir todo hasta insumo son cientos de filas (403
actividades y 820 insumos en la versión activa), así que el defecto tendrá que ser un nivel
concreto, no «todo».

## 9. Lo mismo en el comparador

Selector de nivel y abrir desplegado en el diff jerárquico de `#/ensamble/comparar`, con el mismo
criterio del 8, para que moverse entre Presupuesto y Comparar no cambie de lenguaje.

## 10. En Paquetes no se distingue qué insumos piden atención

La cobertura dice 99,7 % (394 asignados + 1 omitido de 396): hay **un insumo sin asignar perdido
entre 396 filas**. El filtro arranca en «Todos» y nada señala cuáles faltan — hay que saber de
antemano que existe el desplegable «Sin asignar». Lo que se ve primero es el trabajo hecho, no el
que queda.

## 11. «Sembrar 1ª iteración» es jerga interna

*(Absorbido por el 12: si hay un solo botón, el nombre se resuelve dentro del mismo cambio.)*

El nombre viene del vocabulario del desarrollo y no dice que lo que hace es **proponer destinos
sin guardar nada**. El dueño del producto tuvo que preguntar qué era.

## 12. «Sembrar» y «Auto-asignar lo seguro» deberían ser un solo botón

Obligan a entender una distinción interna del motor —proponer versus escribir, y el umbral de
$20M— para elegir cuál pulsar.

**A decidir al implementar:** si es una sola acción, **¿escribe o no escribe?** Las dos salidas
razonables son distintas: que aplique sola lo seguro y deje el resto propuesto, o que solo
proponga y nada se guarde hasta confirmar. Cambia cuánta confianza se le da al motor por defecto.

## 13. «Recalcular» no dice qué conserva y qué cambia

El dueño del producto preguntó si perdía lo avanzado antes de atreverse a pulsarlo. Es un botón
primario, verde, sin ninguna indicación.

**Lo que de verdad hace** (verificado en código): conserva responsables (las tres columnas quedan
fuera del `ON DUPLICATE KEY UPDATE`, con test que lo vigila), conserva amarres y conserva las
filas de los pasos (upsert, no DELETE+INSERT). **Solo recalcula fechas.** El riesgo real no es
perder trabajo: es mover fechas que ya se comunicaron a un proveedor.

**Consecuencia:** la gente evitará el botón que más va a necesitar cuando el cronograma se
reprograme.

## 14. Amarrar un paquete a un frente es irreversible desde la interfaz ⚠️

**El más serio de los quince.** Una vez amarrado, no hay forma de cambiar el frente ni de deshacer
el amarre:

- La columna «Frente» de la tabla principal es texto plano, no editable.
- El selector de frente y el botón «Amarrar» solo existen en la sección «Sin frente», y un paquete
  amarrado ya no aparece ahí.
- No existe ningún «desamarrar»: ni botón, ni endpoint, ni función en el servicio.

**Dos carencias distintas:**
- **Cambiar de frente ya está implementado en el servidor** — `amarrar()` hace `ON DUPLICATE KEY
  UPDATE` sobre `pdc_paquete_frente`, así que sobrescribe el amarre previo. Solo falta exponerlo.
- **Quitar el frente no existe en ninguna capa**, y hay que decidir qué significa: ¿el plan
  calculado de ese paquete se borra, se conserva?

Los demás hallazgos son fricción o confusión; este es una decisión sin retorno sobre datos reales.

## 15. Editar exige doble clic

Debería bastar con un clic. Aplica al desplegable de responsable y a cualquier celda editable.

**Conflicto a resolver:** en la tabla del Plan el clic sencillo ya está ocupado — abre el detalle
de la fila con los siete pasos. Si el clic pasa a editar, hay que decidir qué hace el detalle:
otro gesto, un botón, o depender de la columna.
