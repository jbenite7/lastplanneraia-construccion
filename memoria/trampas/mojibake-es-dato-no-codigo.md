---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-08-03
areas: [datos, qa]
fuente: sesion
resumen: "El mojibake de /programa-general y admin/usuarios no era un bug de encoding del codigo: eran dos filas doble-codificadas en la base local, y el pipeline PHP-PDO-json_encode esta bien byte a byte"
---
Un barrido visual encontro «CapÃ­tulo» en `/programa-general` y «InnovaciÃ³n» en un cargo de
`admin/usuarios`, y el encargo salio con la hipotesis obvia: `htmlspecialchars` con charset
equivocado, o una conexion con collation distinta en ese camino.

**Era falso.** Verificado byte a byte: el texto en el codigo fuente PHP esta en UTF-8 correcto
(`43 61 70 c3 ad`), `App\Core\Database` usa `charset=utf8mb4` siempre, y `json_encode` sale
limpio. El byte corrupto (`c3 83 c2 ad`, doble codificacion UTF-8 → Latin1 → UTF-8) **ya viene
del `fetch()`**, antes de cualquier escape. Son dos filas concretas de la base local:
`programa_consolidado` (`project_id=990100`, `Consecutivo=1`, columna `Actividad`) y
`general_usuarios` (`id=1`, columna `cargo`). Las mismas cadenas en otros proyectos —por
ejemplo `project_id=27`— estan sanas.

**El error de metodo que costo la primera vuelta:** se probo por CLI contra `project_id=27`
(dato sano) mientras la sesion real de `test.R` en «PDC Sandbox E2E» resuelve a
`project_id=990100` (dato corrupto). Con proyectos distintos a cada lado, el diagnostico
apuntaba a un bug de mysqlnd/Apache que no existia. **Al comparar dos caminos, iguala primero
la fila que cada uno lee.**

**Y una del arreglo:** el primer `CONVERT` fue en la direccion contraria y añadio una tercera
capa de codificacion. Se detecto al instante contra el respaldo `HEX()` tomado antes y se
restauro con `UNHEX()`. Sin ese respaldo previo la perdida habria sido silenciosa: el texto
sigue «viendose raro», solo que peor.

Queda abierto por que la fila 1 del sandbox `pdc_sandbox_e2e` contiene «ACTA INICIO…
HOMECENTER CALI…», que no coincide con lo que inserta
`database/seeds/pdc_e2e_sandbox_project.php`. Puede ser residuo de una importacion manual de
pruebas o un bug de importacion reproducible; no se investigo.
