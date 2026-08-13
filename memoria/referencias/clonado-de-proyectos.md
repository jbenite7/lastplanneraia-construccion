---
tipo: referencia
estado: vigente
fecha: 2026-08-13
areas: [arquitectura, deploy, pdc]
resumen: cómo se clona un proyecto sobre otro con scripts/clonar-proyecto.php, y las cuatro trampas que solo aparecen al aplicar — vistas de BI, columnas generadas, UNIQUE globales y las dos cadenas de ids que hay que remapear
fuente: sesion-ejecucion
---
`scripts/clonar-proyecto.php` deja un proyecto como copia de otro: vacía el destino y copia el
origen tabla por tabla. **Dry-run por defecto**; `--apply` escribe dentro de una transacción que
deshace todo si algo falla — cosa que pasó tres veces al construirlo, y por eso el destino nunca
quedó a medias.

```bash
set -a; . ./.env; set +a
php scripts/clonar-proyecto.php --origen=73 --destino=27          # simulacro
php scripts/clonar-proyecto.php --origen=73 --destino=27 --apply  # aplica
```

## Por qué no es un `INSERT … SELECT` con el `project_id` cambiado

La intuición dice que basta cambiar el número de proyecto. Y **para la mayoría de las tablas es
cierto**: `programa`, `programa_consolidado`, `programacion_semanal`, `profesionales`,
`semanas_activas` y `pdc` tienen PK compuesta `(project_id, X)`, y sus claves foráneas también
incluyen `project_id`, así que `unique_id`, `Consecutivo` y `Semana` siguen apuntando a la fila
correcta ya dentro del destino.

Lo que no viaja son los `id` AUTO_INCREMENT globales, porque MySQL asigna números nuevos al
insertar. De las 81 claves foráneas de la base **solo dos cadenas** están en ese caso y hay que
reescribirlas después de copiar:

```
pdc_presupuesto_versiones.id  ->  items.version_id, apu_insumos.version_id, insumo_vinculos.version_id
pdc_presupuesto_items.id      ->  apu_insumos.item_id
```

Conviene desconfiar del recuento de FKs: mirando solo las claves foráneas parecían **seis** cadenas
en riesgo, y al leer las claves primarias reales resultaron **dos**. `profesionales` es el mejor
ejemplo — su `id` no es autoincremental sino parte de la PK compuesta, así que
`pdc.idProveedorAdjudicado` sigue siendo válido sin tocar nada.

## Lo que el script resuelve por ti

Cuatro obstáculos que solo aparecen al escribir — vistas `bi_*` que no son tablas, columnas
generadas, UNIQUE globales como `import_token`, y las `semi_auto_*` muertas — están explicados en
[[clonar-no-es-cambiar-el-project-id]]. El script ya los contempla; la página existe para que
nadie los vuelva a descubrir a golpes.

## Lo que el script no toca, a propósito

El **nombre** del proyecto destino. Copiarlo dejaría dos proyectos con el mismo nombre, y en esta
base ya hay varios casos así (dos «Camino Verde», dos «Milan Campestre»), lo que vuelve ambiguo
elegir proyecto. Sí copia la configuración operativa —línea base, costo de retraso, `pdcActivo`—,
que es la que cambia cómo se comporta el proyecto; con `--sin-config` ni eso.

## Cómo se verifica que el clon no quedó corrupto por dentro

Comparar totales no basta: un clon con las referencias cruzadas apuntando al proyecto original da
los mismos conteos y **falla en silencio**. Las dos comprobaciones, ambas usadas el 2026-08-13:

1. Conteo por tabla de origen y destino, que deben coincidir exactamente.
2. Siete consultas de huérfanos que exigen que cada referencia del destino caiga **dentro del
   destino** — ítems y APU contra sus versiones, vínculos contra versiones, consolidado y semanal
   contra `programa` por `unique_id`, y `pdc` contra `profesionales`.

## Ejecución del 2026-08-13: Da Porto (73) → Prueba (27)

Aplicado en los tres entornos por decisión del usuario, con la lista de accesos copiada también
(«Prueba» pasó de 40 miembros a los 17 de Da Porto; las cuentas `test.*` sobrevivieron porque
también están en Da Porto). Resultado idéntico en los tres: **22 tablas con conteos coincidentes,
0 discrepancias y 0 huérfanos en las 7 comprobaciones**. En local se verificó además en el
navegador — el proyecto «Prueba» muestra el presupuesto de Da Porto por $29.492.804.354 y la
Programación Intermedia carga sus actividades, sin errores de consola.

Respaldos previos: `~/backups/db-pruebas-pre-clon-20260813-051029.sql` y
`~/backups/db-produccion-pre-clon-20260813-051056.sql` (61 MB cada uno, «Dump completed»
verificado). Son la única vuelta atrás: el clon **borra** lo que el destino tuviera.

Ver [[produccion-deploy]] y [[espejo-y-reparacion-unique-id]].
