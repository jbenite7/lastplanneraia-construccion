---
tipo: trampa
estado: vigente
fecha: 2026-08-13
areas: [arquitectura, pdc]
resumen: copiar un proyecto sobre otro parece cambiar el project_id y no lo es; cuatro obstáculos (vistas, columnas generadas, UNIQUE globales y tablas muertas) solo aparecen al escribir, y contar claves foráneas sobrestima el remapeo necesario
fuente: sesion-ejecucion
---
**Clonar un proyecto no es copiar sus filas cambiando el `project_id`.** Pero tampoco es tan
grave como sugiere el esquema: las dos estimaciones fáciles fallan en direcciones opuestas.

**Contar claves foráneas sobrestima.** De las 81 FK de la base parecían **seis** cadenas de
identificadores en riesgo de romperse al copiar. Al leer las **claves primarias** resultaron
**dos**: casi todas las tablas operativas tienen PK compuesta `(project_id, X)` y sus FK incluyen
`project_id`, así que `unique_id`, `Consecutivo` y `Semana` viajan intactos. El caso que más
engaña es `profesionales`: su `id` **no** es autoincremental sino parte de la PK, de modo que
`pdc.idProveedorAdjudicado` sigue siendo válido sin tocar nada. Solo hay que reescribir lo que usa
AUTO_INCREMENT global — versiones de presupuesto e ítems — porque ahí MySQL asigna números nuevos.

**Y leer el esquema por encima subestima**, porque hay cuatro obstáculos que **solo aparecen al
escribir**, no en un simulacro:

- **Vistas disfrazadas de tablas.** Nueve `bi_*` tienen `project_id` y parecen tablas; son vistas
  que se derivan de las tablas base, así que el BI del clon se recalcula solo. MySQL las rechaza
  con «target table … of the DELETE is not updatable». Filtrar por `TABLE_TYPE = 'BASE TABLE'`.
- **Columnas generadas.** No admiten valor en un `INSERT` («The value specified for generated
  column … is not allowed»). Hay que omitirlas.
- **UNIQUE sin `project_id`.** Diez en la base. Un valor único *global* copiado choca contra su
  original; el que muerde es `import_token`, un candado anti-reimportación que además mentiría en
  el clon.
- **Tablas muertas con UNIQUE global.** Las `semi_auto_*` sobrevivieron al borrado del PDC v1 y no
  las lee **ningún** archivo de `src/`, `public/`, `admin/` ni `pdc-app/`; copiarlas no alimenta
  nada y sus uuid chocan.

Las tres primeras hicieron abortar la transacción en intentos sucesivos, y por eso el destino nunca
quedó a medias: **envolver el clonado en una transacción no es una precaución opcional**, es lo que
convierte cuatro sorpresas en cuatro reintentos limpios en vez de en una base mestiza.

El procedimiento, el script y la forma de verificar están en [[clonado-de-proyectos]].
