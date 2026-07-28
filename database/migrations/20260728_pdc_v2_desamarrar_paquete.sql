-- Desamarrar un paquete sin perder a su responsable (f10-f13 de la revisión de UX de 2026-07-28).
--
-- Amarrar un paquete a un frente del cronograma era una decisión sin retorno: no había «desamarrar»
-- en ninguna capa. Al construirlo apareció el problema de fondo: la fila de `pdc_plan_paquete` es
-- la que guarda al responsable, y también la que guarda las fechas calculadas. Borrarla —que es lo
-- que hoy hace la invalidación de un reamarre— se lleva por delante quién iba a comprar el paquete,
-- en silencio.
--
-- La salida es separar los dos estados dentro de la misma fila: sin frente no hay fechas que
-- calcular, pero sí sigue habiendo un dueño. Estas cuatro columnas pasan a NULL para poder
-- representar «este paquete tiene responsable y todavía no tiene plan». Las fechas se vacían de
-- verdad (no se conservan viejas): una fecha huérfana en pantalla es indistinguible de una vigente,
-- y la gente las comunica a proveedores.
--
-- `plan()` filtra por `fecha_arranque IS NOT NULL`, así que una fila sin fechas no llega a la
-- grilla — el paquete aparece en «Sin frente» o en «Amarrados, pendientes de calcular», que es
-- donde debe estar. `calcular()` las vuelve a llenar con su upsert sin ningún cambio.
--
-- Cero regresión: todas las filas existentes tienen valor en las cuatro columnas y lo conservan;
-- lo único que cambia es que a partir de ahora se admite NULL.
ALTER TABLE pdc_plan_paquete
  MODIFY COLUMN unique_id INT NULL DEFAULT NULL,
  MODIFY COLUMN fecha_ancla DATE NULL DEFAULT NULL,
  MODIFY COLUMN fecha_arranque DATE NULL DEFAULT NULL,
  MODIFY COLUMN dias_totales INT NULL DEFAULT NULL;
