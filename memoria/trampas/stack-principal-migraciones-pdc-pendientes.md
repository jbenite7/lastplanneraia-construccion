---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-07-28
areas: [pdc, datos]
fuente: memoria-claude
origen: lps-aia-stack-principal-migraciones-pdc-pendientes
resumen: Replayar las migraciones PDC v2 contra el código de HEAD exige correr todo el DDL antes que los seeds — el orden cronológico por nombre de archivo revienta
---
**Resuelto el 2026-07-28**: la BD `lastplanneraia_dev` (contenedores `last-planner-aia-*`, MySQL 3307)
se había quedado en las migraciones PDC v2 del 2026-07-23 y `/plan-compras` → Paquetes daba tres 500
por tablas ausentes. Se aplicaron las 16 pendientes; el catálogo quedó en 221 paquetes (216 activos).

**La trampa, por si hay que repetirlo en otro entorno:** aplicarlas en orden cronológico por nombre de
archivo NO funciona, por dos motivos independientes.

1. Dentro del 20260724, `paquete_indirectos.php` ordena antes que `paquetes_contratacion.sql`, que es
   quien crea `general_paquetes_contratacion`.
2. El de fondo: las migraciones se replayean contra el código de HEAD, no contra el de su fecha.
   `PaquetesService::crearPaquete()` hoy hace SELECT e INSERT de `modalidad_contratacion`, columna que
   añade una migración del día SIGUIENTE (`20260725_pdc_v2_modalidad_contratacion.php`). Los tres seeds
   del 24-07 mueren con *Unknown column* si se corren antes.

**Cómo aplicarlo:** separa esquema de datos. Primero todo el DDL —
`paquetes_contratacion.sql` → `modalidad_contratacion` → `admite_materiales` → `puente_duraciones`
(su ALTER dice `AFTER admite_materiales`) → `procedencia_asignaciones` → `insumo_actividades` —,
después los seeds en orden cronológico, y al final **re-ejecuta** `admite_materiales` y
`puente_duraciones`: en la primera pasada emparejan contra un catálogo vacío y marcan 0. Todas las
`.php` traen dry-run por defecto y `--apply`, y todas son idempotentes (verificado re-corriéndolas).

`20260724_pdc_v2_version_numero_unique.sql` ya estaba aplicada y no hay que tocarla: es la que crea
`activa_unica` y `uq_pdcpv_activa_unica`, de las que depende el flag de versión activa del presupuesto.

No existe runner de migraciones en el repo: se corren a mano con
`docker compose exec app php database/migrations/<archivo>.php --apply`. Un bucle `for` sobre varias
en un solo comando lo bloquea el clasificador; hay que invocarlas de una en una.
Relacionado: [[pdc-e2e-sandbox]], [[dos-stacks-docker]].
