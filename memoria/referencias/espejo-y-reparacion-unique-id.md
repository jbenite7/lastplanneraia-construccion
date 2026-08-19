---
capa: wiki
tipo: referencia
estado: vigente
fecha: 2026-08-12
areas: [deploy, datos]
fuente: sesion
resumen: "La operación del 2026-08-12: producción→local→pruebas→producción, la reparación de 27.130 unique_id nulos en los tres entornos, y qué quedó distinto en cada base"
---
El 2026-08-12 se ejecutó, autorizada paso a paso por el usuario, la operación **espejo y
reparación**: dump de producción → base local pisada → reparación → copia a pruebas → y, con
producción en mantenimiento y respaldo doble, la misma reparación y nivelación allí. El diseño
vive en [[docs/superpowers/specs/2026-08-12-espejo-produccion-a-pruebas-design|su spec]], con la
sección de cierre al final.

**El origen fue un error de UI y la causa era de datos.** «Id de actividad inválido» al editar en
Programación Intermedia no lo produce la pantalla: `hot.js` arma el guardado con
`row.unique_id || row.Consecutivo_en_Programa` y las filas de `programa_consolidado` tenían ambos
en NULL. Lo que parecía un bug de pruebas resultó estar **peor en producción**: 33.399 filas de
consolidado (50,5%) y 1.758 de semanal, repartidas en 12 proyectos activos — usuarios reales
llevaban tiempo sin poder editar esas actividades.

**La reparación es determinista y está en el repo**: `scripts/diagnostico-unique-id-nulos.php`
(solo lectura, sesión `TRANSACTION READ ONLY`) mide y clasifica; `scripts/reparar-unique-id-nulos.php`
(dry-run por defecto) backfillea emparejando contra `programa` del mismo proyecto con dos reglas en
orden — A: `Id`+`Actividad` idénticos y candidato único; B: `Id` idéntico y único — y **no toca** lo
ambiguo (C). Los números calcaron en los tres entornos, que es la señal de que las tres bases eran
la misma foto: consolidado A=9.144, B=16.564, C=7.691; semanal B=1.422, C=336. Los 8.027 del grupo
C son filas históricas sin candidato en `programa` y siguen NULL a propósito: inventarles un id
sería corromper, no reparar.

**Nivelación de esquema.** Producción tenía 71 objetos y el código de `main` espera 102: faltaban
las 22 tablas y 9 vistas de PDC v2/BI más catálogos (`general_maestro_insumos`, etc.). Se crearon
desde un dump parcial de la local ya verificada, conteo a conteo. Ojo a la asimetría que queda:
**producción tiene ya las tablas del PDC v2 pero no el código** — eso llega con el release
pendiente que describe [[produccion-deploy]], y tener el esquema antes que el código es el orden
correcto según la rutina de deploy.

**Qué NO viajó a producción**: el sandbox `PDC Sandbox E2E` (990100), las cuentas `test.*` y sus
membresías — se comprobó antes del dump parcial que ninguna de las 31 tablas tenía filas del
sandbox. Esos seeds solo existen en local y pruebas.

**Los datos del PDC de Da Porto solo existían en pruebas.** Producción no tenía las tablas y la
local se pisó: el snapshot de la pruebas vieja fue la única fuente de los presupuestos, vínculos y
APU (1.640/1.046/792 filas). Si algún día se repite un espejo, ese orden importa: exportar lo que
solo vive en pruebas ANTES de pisarla.

**Respaldos que existen de esta operación** (los locales no viajan en git, `backups/` está
ignorado): producción pre-mantenimiento en `backups/db-produccion-COMPLETO-pre-mantenimiento-20260812-224822.sql`
(59M, SHA-256 al lado, copia también en `~/backups/` del servidor); pruebas pre-espejo
(`db-pruebas-premirror-20260812-221736.sql`, servidor y `~/backups/lps-aia/` de la máquina del
usuario); local pre-espejo (`db-local-backup-20260812-170936.sql`, ídem).

**Trampas que mordieron y conviene recordar**: `docker exec -T` dentro de un `while read` se come
el stdin y el bucle «termina bien» habiendo hecho una sola iteración — la copia de 22 tablas
reportó `listo` sin copiar nada y solo lo delató re-contar después (pariente de
[[el-contador-no-mide-el-archivo]]: un instrumento que ante el fracaso devuelve algo con forma de
éxito). Y un dump de `mysqldump` no restaura fuera de su servidor sin neutralizar `DEFINER=`, que
ya estaba documentado en la rutina de deploy y volvió a ser cierto las tres veces.
