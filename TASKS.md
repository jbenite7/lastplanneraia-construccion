---
capa: fuente
tipo: goal
estado: vigente
fecha: 2026-08-18
areas: [proceso]
tags: [proyecto]
fuente: sesión de coordinación 2026-08-18 (inventario de planes, specs y sesiones + 22 decisiones del usuario); consolidación de fases 2026-08-18
resumen: "Fuente única de pendientes: las 22 fases de los cuatro programas, su orden y su estado"
project: lps-aia
type: tasks
status: activo
updated: 2026-09-03
---

# Tareas

**Fuente única de pendientes.** El trabajo corre en un enjambre de sesiones sobre
`.claude/worktrees/` (ver [[docs/coordinacion-sesiones]]); cada frente tiene su
`goals/<slug>/goal.md` y su registro en `decisiones/`. Esta lista es la vista para retomar sin
releer el chat de cada sesión.

Para **en qué fase va cada programa**, el detalle por bloques al final de esta misma página: la
cola vivía en `memoria/goals/cola-de-pendientes.md` y se migró aquí el 2026-08-19, por decisión de
Felipe, para no sostener dos fuentes únicas. Para el **estado de cada goal**, [[goals/estado]].

> Releído el 2026-08-19 contra `origin/main`. La versión anterior de este archivo se escribió desde
> un árbol 114 commits atrasado y daba por activos cinco frentes que ya habían cerrado y publicado.
> **Es el modo de fallo a vigilar aquí:** este archivo se escribe desde lo que una sesión ve, y una
> sesión ve su worktree.

## Gate de datos: lo que el guard destapó y sigue roto (2026-09-02)

**Frente abierto el 2026-09-04 con su medición: [[goals/guard-datos-suite/goal|guard-datos-suite]].**
Los 24 tests que `G_PHP_SUITE` reporta en rojo en cada corrida están ahí clasificados uno por uno —
**8 destapan código de producción roto** (import SINCO del PDC, panel de administración, la
consolidación de informes y las vistas BI) y **16 son tests que consultan sin declarar alcance**.
La condición de hecho y lo que el frente tiene prohibido hacer viven en ese goal.

**FRENTE CERRADO el 2026-09-04, las dos mitades.** El nivel `http` pasa de **24 fallos a 0**, sin
ningún fallo nuevo, medido sobre los dos árboles con la misma suite y el mismo stack. La segunda
mitad —los 16 tests sin adaptar— destapó **cuatro fallos de producción más** que estaban escondidos
detrás de ellos: el catálogo de paquetes mutilado, las tres capas de sugerencia muertas y dos JOIN
que unían tablas de proyecto sin relacionar `project_id`. Ver `CHANGELOG.md` › «el gate de datos
vuelve a verde» y la sección `## Cierre` del goal.

Dos cosas que la segunda mitad dejó medidas:

- **Los 16 no eran 16.** Había además **dos clases PHPUnit** con la misma causa (9 errores):
  `CarryoverAvanceSemanalTest` y `PgAvanceEdicionManualTest`. Ninguna medición previa las contó
  porque `scripts/run-php-tests.php` reporta PHPUnit en una línea aparte de la de los scripts
  sueltos, y quien lee «24 fallaron» se queda con esa. Al contar fallos de este runner, hay que
  mirar **las dos** líneas `===`.
- **La lectura entre obras del catálogo de paquetes quedó declarada, por decisión de Felipe del
  2026-09-04**, no suprimida: pasa por `PaquetesService::leerCatalogoEntreObras()` y está
  autorizada con su justificación en `test_project_scope_callsite_audit`.

Tres cosas que esa mitad dejó medidas y cambian lo escrito más abajo:

- **La consolidación de informes NO estaba muerta por el alias.** Estaba muerta por siete consultas
  sin `project_id` que cruzaban obras, y la peor **borraba** el CIC/CIP de todas las demás. El
  `FROM DUAL` se quitó (era decorativo). El alias ambiguo, que se daba por tercer problema, ya no
  bloquea nada: el test de aislamiento pasa sin tocarlo.
- **De los diez archivos con `information_schema`, ocho ya estaban migrados** y solo conservaban el
  comentario que lo explica. La lista de «16 sitios del runtime servido» de más abajo está medida
  sobre un árbol viejo: re-medir antes de usarla.
- **Sigue abierto:** `admin/src/Controllers/DashboardController.php:~579` (`getDatabaseStats()`)
  arma SQL contra `information_schema` dentro de un `try/catch` que devuelve ceros. No revienta:
  **falla en silencio** y el panel muestra 0 MB y 0 tablas. Es el único uso real que queda.

- [ ] **Hallazgo nuevo (2026-09-04): `nueva_semana.php` no puede activar un borrador de semana.**
  Su `UPDATE ... AS dest INNER JOIN (SELECT ...) AS src` usa una tabla derivada, y el guard no
  reconoce `src.project_id = dest.project_id` como relación válida: responde
  «La tabla programa_consolidado no está relacionada por project_id con dest». Lo destapó
  `test_schedule_update_draft_import` al llegar más lejos que antes. **Ese test pasa igual**, porque
  su aserción mira el conteo de filas y no el payload, así que hoy nadie lo vigila. La reescritura
  natural es cambiar la derivada por un self-join con las condiciones en el `ON`; es legado sin
  cobertura propia, así que merece su propia tarea y no se hizo de paso.

`ProjectSqlGuard` (2026-08-29, `48e06072`) rompió más que la herramienta de línea de comandos.
~~Lo de la suite quedó arreglado en el frente `fix/suite-main-scope`~~ — **eso caducó: la suite
sigue con 24 tests en rojo**, medidos el 2026-09-04 en dos corridas distintas (ver el goal enlazado
arriba). Las dos de abajo son de producción y siguen vivas en `main`, medidas con petición HTTP real
contra el stack local:

- [ ] **`tests/test_bi_constraint_write.php` sigue muerto por el guard — la suite no quedó
  arreglada del todo (medido el 2026-09-04).** El encabezado de arriba dice que «lo de la suite
  quedó arreglado en el frente `fix/suite-main-scope`»; para este archivo no. Corriendo contra la
  base de dev muere en su primer fixture con `MissingProjectScope: La consulta a tablas de proyecto
  exige un ProjectScope activo` (`ProjectSqlGuard.php:57`, vía `Database::query()`), antes de
  ejercitar una sola aserción. Son **cinco** consultas del archivo a tablas de proyecto sin scope, y
  dos de ellas son la comprobación de persistencia, no fixtures: envolverlas exige criterio sobre
  qué alcance corresponde en cada punto —`SystemScope` o el del proyecto 73— y hacerlo a ojo podría
  tapar justo la propiedad de aislamiento que el test existe para probar. Por eso se dejó como está
  al pasar por ahí el 2026-09-04. **Sigue abierto tras cerrar `guard-datos-suite`**, y a propósito:
  es nivel `datos-proyecto`, fuera del CI, así que no bloquea `G_PHP_SUITE`. Lo que sí cambió es que
  ahora existe la herramienta y, sobre todo, la regla escrita para hacerlo bien —
  `tests/support/ScopeFixture.php`: el alcance de una aserción de aislamiento es el de la obra que
  se **observa**, nunca el de la que se acaba de escribir. Su hermano `test_bi_metric_endpoint.php` sí pasa entero (39
  aserciones): consulta `project_members`, que es tabla global. El mismo síntoma tienen los 24 tests
  que `G_PHP_SUITE` reporta en rojo en cada corrida de CI.
- [x] **Arreglado el 2026-09-02: el alias ambiguo de `semanas_activas`.** La misma consulta estaba
  copiada en dos sitios —`src/Legacy/datosGeneralesPagina.php` y `ProgramaGeneralController`— y
  nombraba la tabla dos veces sin alias, así que `/programacion-semanal` y `/programa-general`
  reventaban al cargar. Cada referencia lleva ahora su alias y su `project_id` calificado; cubierto
  por `ProjectSqlGuardTest`, con la pareja rechaza/acepta. La consulta se sacó a
  `App\Services\EstadoSemanalService`, que es ahora el único sitio donde tocarla: estaba copiada en
  los dos archivos, y `BaseController::getWeekStatusVars()` mantenía una tercera copia parcial cuyo
  comentario ya advertía que no podía divergir.
- [x] **Medidos los seis hermanos del alias ambiguo (2026-09-02).** El resultado parte la lista en dos
  mitades que no se parecen, y por eso solo se arregló una:
  - **`ForecastService::getContractorPac4W()` y `getResponsiblePac4W()` — arreglados.** No los dispara
    nada: son código muerto, sin un solo llamador en el repositorio (las claves
    `contractor_pac_4w`/`responsible_pac_4w` que parecen invocarlos llegan a `predict()` ya
    calculadas, desde fuera). Se arreglaron igual porque son dos líneas y quedan correctos para quien
    los cablee; verificado con datos reales (proyecto 62, «CONSTRUALMANZA»: devuelve 0.3166 donde
    antes lanzaba `ProjectScopeViolation`).
  - **Los cuatro de `ReportProcessor` — NO arreglados, y no por pereza.** Sí hay disparador: el botón
    «Consolidar informes» del panel de administración (`admin/async/consolidate.php`,
    `DashboardController`) y la ruta `/reportes/{tipo}` de `ReportController`. Pero **ese código nunca
    llega hasta las consultas del alias**, porque muere antes, dos veces. Ver la tarea de abajo.
- [ ] **La consolidación de informes está muerta entera, y el alias es su tercer problema, no el
  primero.** Medido el 2026-09-02 ejecutando `generateCurvaS()` y `generateReporteGeneral()` bajo
  `SystemScopeRunner`, que es como corren de verdad: las dos abortan con `DomainException: Las tablas
  calificadas por schema no están soportadas por el gate`. En orden de aparición:
  1. **`information_schema` a través de `Database::query()`.** Mata el informe en
     `ReportProcessor::reportTableHasProjectId()` (`:137`), antes de tocar dato ninguno. La
     comprobación del guard ocurre al analizar la consulta, **antes** de mirar el alcance, así que
     `SystemScope` no salva. `Database` ya resuelve esto bien y en privado con PDO crudo y caché
     (`rawTableExists`, `rawColumnExists`), y la migración `20260828_project_scope_contract.php` fija
     el patrón. Hay al menos **tres** sitios así dentro de `ReportProcessor` y otros tres fuera que
     también pasan por el guard: `ProfesionalesApiController:519`, `SubcontratistasApiController:456`
     y `ProgramChangeDetector:476`.
  2. **`FROM DUAL`.** Tres consultas de `ReportProcessor` cuelgan sus subconsultas de `DUAL`, que no
     está en el catálogo de esquema, así que el guard lanza `Tabla no clasificada en el schema: dual`
     con cualquier alcance. Comprobado aparte.
  3. **Y solo detrás de esas dos**, el alias ambiguo de `:211`, `:985`, `:1040` y `:1063`.

  Arreglar (3) sin (1) y (2) no cambia nada observable y no se puede verificar, que es la razón de
  dejarlo. Esto ya no es «un barrido de alias»: es **restaurar una funcionalidad caída**, con
  escritura sobre `general_curvas`, `general_informe_consolidado` y hermanas. Merece frente propio.

- [ ] **El problema (1) no es de informes: son 16 sitios del runtime servido.** Medido el 2026-09-02
  pasando los 966 literales SQL del árbol por el `ProjectSqlGuard` real (no por una regex) y
  clasificando cada uno por quién lo ejecuta. 77 consultas nombran una tabla calificada por esquema;
  el guard las rechaza **todas**, y no solo `information_schema`: la regla salta ante cualquier
  `esquema.tabla` después de `FROM`/`JOIN`. Reparto:

  **Runtime servido — 16 rompen.** Trece detectados y tres resueltos a mano:
  `admin/src/Controllers/FamilyCatalogController.php:577`, `admin/src/Models/Project.php:983`, `:1007`,
  `:1193`, `:1205`, `src/Controllers/Api/ProfesionalesApiController.php:519`,
  `src/Controllers/Api/SubcontratistasApiController.php:456`, `src/Core/Lps/LpsService.php:349`,
  `src/Legacy/productividad_temporal.php:51`, `src/Services/ProgramChangeDetector.php:476`,
  `src/Services/ReportProcessor.php:112`, `:123`, `:137`, más
  `admin/src/Controllers/DashboardController.php:576`, `src/Security/EventService.php:109` y
  `src/Security/RbacService.php:258`. A salvo, con conexión propia: `Database.php:796` y `:816`
  (`rawTableExists`/`rawColumnExists`, que son el patrón a copiar) y `TableScopeCatalog.php:22`.

  **Tres de esos dieciséis no revientan: fallan en silencio, que es peor.** `EventService`,
  `RbacService` y `DashboardController` envuelven la consulta en un `catch` que devuelve un valor por
  defecto, así que la excepción del guard se traga y el código sigue con una respuesta falsa.
  Comprobado: `RbacService::tableExists('rbac_roles')` devuelve **false** con la tabla existiendo. El
  efecto es que el rol deja de re-resolverse desde `project_members` y se cae al valor de sesión o a
  `RbacCatalog::DEFAULT_ROLE`. **Degrada hacia abajo —`DEFAULT_ROLE` es `C`, subcontratista— así que
  no hay escalada de privilegios**, pero sí permisos y diccionario de eventos resolviéndose mal sin
  que nadie se entere. Empezar por estos tres.

  **Herramienta (migraciones y scripts) — 25 rompen, 18 a salvo, 12 sin resolver.** Prioridad mucho
  menor: casi todas son migraciones fechadas que ya se ejecutaron y no vuelven a correr. Conviene
  mirarlas solo antes de replantar una base desde cero.

  El detector que produjo estos números fue temporal y no quedó en el repo. Convertirlo en prueba con
  una línea base congelada es una opción, pero hoy saldría en rojo con estos 16 y hay que arreglarlos
  antes de fijar la línea.
- [x] **Arreglado el 2026-09-03: el guard ya valida el INSERT multifila del PDC.** Decisión de
  Felipe: enseñarle al guard, acotado a que todas las filas del lote compartan el `project_id` del
  `ProjectScope` activo — no reescribir los lotes fila a fila. La restricción resultó gratis: `
  guardInsert()` nunca recibe `MultiProjectScope` (el guard de escritura no lo acepta), así que
  "todas las filas de la misma obra" ya lo garantizaba el tipo de `$scope`, no hubo que agregar una
  comprobación aparte.
  `ProjectSqlGuard::guardInsertValues()` (`src/Security/DataScope/ProjectSqlGuard.php`) parseaba una
  sola tupla de `VALUES` y rechazaba en cuanto veía una coma después. Ahora `collectInsertValueRows()`
  recolecta todas las tuplas de nivel superior y aplica, por cada una, la misma validación que antes
  corría una vez: inyecta o verifica el placeholder de `project_id`.

  **Dos rondas: implementación, luego revisión (general + seguridad) en paralelo, luego una segunda
  ronda que arregló lo que la revisión encontró.** Verdicto de las dos: aprobado, sin críticos ni
  altos — ninguna vía de fuga probada tras un ataque adversarial de la revisora de seguridad (~40
  INSERT multifila a mano + un corpus diferencial de 8.414 casos de una fila, comparando byte a byte
  contra el commit anterior). Tres hallazgos MEDIUM, los tres arreglados aquí antes de cerrar:
  - **El recorrido de parámetros era O(filas × tokens), no O(tokens).** `positionalPlaceholderCountBefore()`
    y `assertScopePlaceholder()` recontaban `$tokens` desde cero en CADA fila. Medido por la
    revisora: 800→3200 filas escalaba 3,7× en vez de lineal. Arreglado con un solo recorrido de
    `$tokens` que precomputa cuántos placeholders preceden a cada token, compartido entre todas las
    filas — en las dos ramas del método (la explícita, que es la que de verdad usan los cinco
    servicios del PDC, no solo la de inyección). Medido después: la rama de inyección pasó a casi
    lineal (800→3200: 4,1×); la rama explícita mejoró de una curva peor a ~3,4× en el mismo tramo —
    sigue habiendo un residuo por debajo de lineal perfecto (probablemente el costo constante de
    pasar arrays de decenas de miles de elementos por PHP, no un segundo bug algorítmico), pero a la
    escala real (lotes de 200 filas) el costo es trivial en cualquiera de los dos casos y perseguir
    ese residuo era exceder el alcance acotado que decidió Felipe.
  - **El `array_splice` en orden inverso solo era correcto porque el valor inyectado es siempre el
    mismo.** Dos filas sin placeholders entre sí producían el mismo índice de inserción; el
    `rsort()` + `array_splice()` repetido daba el resultado correcto únicamente porque ambas
    inserciones escriben el mismo `$scope->projectId()`, no porque el algoritmo lo garantizara. La
    revisora lo calificó de "cheap insurance on a row-level-security boundary": si algún día una
    fila necesitara un valor distinto, el fallo sería escribir en la obra equivocada en silencio, no
    un error visible. Reemplazado por un solo recorrido hacia adelante que construye el nuevo array
    de parámetros en el mismo orden en que aparecen las filas — el orden queda fijado por
    construcción, ya no por la coincidencia de que los valores sean iguales.
  - **Cobertura de pruebas insuficiente para los casos adversariales.** Sumadas seis pruebas más:
    rechazo cuando solo la fila 3 de 3 trae otra obra (no solo la 2 de 2); un literal o expresión en
    la posición de `project_id` en una fila que no es la primera (dos variantes, fila 2 y fila 3);
    inyección con conteo de placeholders desparejo entre filas, incluida una fila sin ningún
    placeholder propio (el caso que expondría un error de uno-por-uno en el recorrido hacia
    adelante); y dos casos de "falla cerrado" ante separadores malformados (`VALUES (?,?), x` y coma
    final sin tupla).

  TDD en las dos rondas: tests primero. Primera ronda — tres pruebas (inyección en 3 filas, validación
  explícita en 2 filas, rechazo si cualquier fila trae otra obra) más una que replica la forma real de
  `MaestroInsumosService::generarVinculos()` (`project_id` explícito mezclado con un literal fijo en
  la misma tupla — el caso concreto que esta entrada tenía confirmado reventando). Segunda ronda — las
  seis de arriba. Total: 10 pruebas nuevas.

  Verificado (segunda ronda, estado final): `phpunit --group db tests/unit/ProjectSqlGuardTest.php` →
  65/65; `phpunit tests/unit/` completo → 171 pruebas, mismos 11 errores + 1 fallo preexistentes
  (fixture del proyecto 27 ausente en el runtime aislado, confirmado idéntico revirtiendo el cambio)
  — ninguna regresión nueva; `phpstan analyse src admin/src` → `[OK] No errors`; microbenchmark propio
  confirmando la mejora de complejidad en las dos ramas.

  **No se auditaron los otros 45 sitios** que usan el patrón `array_fill()` en `src/` — solo se cerró
  el caso del PDC que esta entrada tenía confirmado. Cualquiera de esos 45 que hoy dependa del INSERT
  multifila queda cubierto por el mismo arreglo (es el mismo guard), pero no se verificó uno por uno.

  **Cuatro huecos preexistentes que la auditoría de seguridad encontró y confirmó idénticos en el
  commit anterior — no introducidos aquí, no arreglados aquí, quedan para frente propio:**
  - `ON DUPLICATE KEY UPDATE` no se valida en absoluto: `INSERT INTO t (project_id, a) VALUES (?, ?)
    ON DUPLICATE KEY UPDATE project_id = ?` con otra obra pasa el guard. Hoy es inofensivo porque
    `MaestroInsumosService.php:99` usa exactamente ese patrón pero solo con `VALUES(col)` sobre
    columnas que no son la clave — nada lo obliga a seguir siendo así.
  - Una subconsulta dentro de `VALUES` que lea tablas de proyecto no queda acotada: `VALUES (?,
    (SELECT MAX(id) FROM programa))` pasa sin filtro de proyecto, a diferencia de `INSERT ... SELECT`
    que sí inyecta el `WHERE`. Sin uso conocido en `src/` ni `admin/src/` hoy.
  - Comentarios versionados de MySQL (`/*!40000 ... */`) esconden una fila del tokenizador aunque
    MySQL sí la ejecute. Solo explotable por quien ya controle el string SQL — es decir, ya tendría
    inyección.
  - `project_id` listado dos veces en la lista de columnas usa la primera aparición
    (`array_search()`) e ignora la segunda — no explotable porque MySQL rechaza columnas duplicadas
    con error 1110.
- [x] **Arreglado el 2026-09-03 en el PR [#24](https://github.com/jbenite7/lastplanneraia-construccion/pull/24): los siete errores de PHPStan en `src`.** Esta entrada quedó desactualizada tras ese
  cierre — seguía marcada `[ ]` señalando un estado que ya no existe. Verificado de nuevo aquí antes
  de corregirla: `phpstan analyse src admin/src --memory-limit=1G` → `[OK] No errors` sobre `main`
  (`d413b92e`). Detalle de la causa y el arreglo en la entrada del 2026-09-03 más arriba
  («Cerrado en este frente, con `fingerprints: []` intacto»).

También quedó anotada una trampa de documentación: `CLAUDE.md` manda enlazar el `.env` en un worktree
con `ln -s` a ruta absoluta del host. Sirve para que `docker compose` sustituya variables, pero
**dentro del contenedor ese enlace apunta a la nada**, así que Dotenv no lee nada, `DEV_DOOR` queda
apagado y los e2e fallan en el login con un mensaje que culpa al `.env` (que está bien). Con el
contenedor montado sobre un worktree hace falta copia, no enlace.

## Migración React — shell mínimo

- [x] **Shell mínimo React cerrado (2026-08-28):** `/app` cubre login, selección de proyecto,
  navegación por rol y tema claro/oscuro; la frontera conserva en PHP los módulos que aún no han
  migrado.
- [ ] **Migrar recuperación de clave:** `password-forgot` y `password-reset` siguen en PHP por
  decisión R12; requieren un frente propio que cubra correo, tokens y expiración.
- [ ] **Resolver el menú contextual de Semanas:** definir su comportamiento y su lugar en la
  navegación React antes de migrar los módulos de programación.
- [ ] **Definir QA y goldens durante la convivencia:** decidir por cada módulo si su golden PHP se
  archiva o se reemplaza al cruzar a React, y mantener cobertura extremo a extremo en ambos mundos.

## Pendiente de decisión: despliegue a producción

**El arreglo del arrastre de avance semanal está en `main` (`c1e3365e`) y desplegado en
`prueba-lps`, no en producción.** Decisión de Felipe del 2026-08-25: validar primero en pruebas.

Lo que hay que saber para retomarlo:

- **Producción va 233 commits atrás de `main`** (`6fa3cff1`), de los cuales 86 tocan código y
  96 archivos son de runtime. Desplegar arrastra ese paquete entero, no solo el arreglo — entre
  otras cosas el fix de CSRF de `LpsApiController` (`39ff0b8f`), que sigue sin llegar a la obra.
- **Dos migraciones pendientes en producción**: `20260819_sembrar_linea_base_contractual.sql` y
  `20260825_carryover_testigo.sql`. La segunda es aditiva y reversible
  (`DROP COLUMN Ejecutado_Carryover`); la primera no la revisé.
- Staging quedó en `c1e3365e` con la migración aplicada y smoke en verde (home y login 200,
  65.549 filas intactas). Respaldo previo en `~/backups/prueba-lps-predeploy-*`.
- **Qué validar en pruebas**: reportar avance en Programación Semanal *después* de abrir el
  Programa General de la semana siguiente, y comprobar que el acumulado sí lo recoge.
  **Probado y verificado por Felipe el 2026-08-25.** Sigue pendiente la decisión de desplegar a
  producción — el tamaño del paquete arriba descrito no cambió.
- **Tercera migración que se sumó al paquete pendiente**: `20260826_pg_avance_edicion_manual.sql`
  (bitácora de ediciones manuales, ver más abajo). Aditiva y reversible (`DROP TABLE`).

## Otros pendientes anotados el 2026-08-25

- **Actualizar Programa General reemplaza el avance de quien solo vino a cambiar otra cosa.**
  `public/js/modules/programa_actualizar/hot_actualizar.js:526` manda `editarActividadAsociar=1` en
  **toda** edición de celda, no solo al cambiar la asociación. Si el residente corrige una fecha de
  una actividad ya asociada, `GeneralApiController::update()` dispara la herencia y le sobrescribe
  `Ejecutado`, `unidad`, `cantidad_ppto`, `Responsable_AIA` y `Sub_Contratista` con los de la
  semana anterior, sin que lo pida ni lo vea venir. Descubierto al mapear escrituras para la
  bitácora (spec `2026-08-25-bitacora-ediciones-manuales-carryover-design`), pero es un defecto de
  producto por derecho propio: la bitácora permitirá **detectarlo** cuando ocurra, no lo evita.
  Merece decisión de producto propia — ¿la herencia debería dispararse solo cuando el usuario
  cambia la asociación, en vez de en cada guardado?

**Cierre del frente de la bitácora, 2026-08-26.** Construida como registro consultable: tabla
`pg_avance_edicion_manual` y `PgAvanceEdicionManualService`, conectados a `GeneralApiController::update()`.
El arrastre semanal **no** la consulta — se implementó, un test candado encontró que la consulta no
podía cambiar ninguna decisión (en el único caso donde se aplicaría, el resultado ya estaba
decidido de antemano), y se revirtió por decisión de Felipe. Detalle completo en la spec v2
(`docs/superpowers/specs/2026-08-25-bitacora-ediciones-manuales-carryover-design.md`). Suites
`puro` y `http` en verde, `phpstan` sin errores. Sin publicar en `main`.

- **Asociar a mano y con "Auto-Asociar" no heredan lo mismo.** Los dos traen el avance de la
  actividad anterior —el botón lo hace vía el arrastre, que usa `programaAnteriorAsociar` como
  primera vía de mapeo—, pero la herencia manual de `GeneralApiController::update()` copia además
  las siete restricciones, `Estado_Restricciones`, `Observaciones`, `codigo_actividad` y
  `medir_productividad`, y el arrastre no. Así que una actividad asociada con el botón arranca sin
  sus restricciones y otra asociada a mano sí las tiene, sin que nada lo advierta. Felipe lo señaló
  el 2026-08-25 ("si auto-asocia, debe heredar"); queda pendiente decidir si el botón debe heredar
  el paquete completo.

  **Medido en producción el 2026-08-25 (solo lectura): 3 casos de 1.277.** De las actividades
  asociadas cuya actividad de origen sí tenía restricciones liberadas, solo tres quedaron en cero —
  proyecto `68`, semanas 7 y 11 (Morteros, Redes hidrosanitarias, Redes Eléctricas), las tres con
  1/7 restricciones en el origen. **Prioridad baja:** el camino que lo produce sigue abierto, pero
  no está haciendo daño. Dos límites de esa medición: mira el estado actual, no si algo se perdió y
  se volvió a llenar; y 4.653 actividades asociadas no encontraron origen en la semana anterior
  (asociación más lejana, o el nombre cambió), así que de esas no dice nada.

- **`tests/test_schedule_update_draft_import.php` deja un proyecto huérfano.** Crea
  `Base_de_Datos = 'it_schedule_draft'` y no lo borra al terminar, así que la corrida siguiente
  de la suite falla con «already exists» y la de después pasa. Alterna verde y rojo según quién
  corrió antes, y hace desconfiar de un gate que sí funciona. Limpiado a mano el 2026-08-25
  (proyecto `1000030`); la causa sigue ahí.
- **`Database::beginTransaction()` + `rollBack()` no aislaron las escrituras de un servicio** en
  una prueba manual del 2026-08-25. No se determinó por qué. Mientras no se sepa, no confiar en
  el rollback para experimentos contra la base de dev: volver a sembrar desde el dump.

## Bloqueantes

**2026-09-03 — Dos de los gates del carril visual ya cerraron; quedan tres, y uno de ellos es una
deuda de otros frentes, no del carril.** Frente `fix/carril-visual-verde` (rama en PR),
continuación de la entrada de abajo. Dos correcciones a esa entrada, medidas al retomarla:

- **No son seis gates bloqueantes, son cinco.** `G_KEYBOARD_REFLOW_EVIDENCE` está excluido a
  propósito del bucle que decide el veredicto (`.github/workflows/ci.yml:548-554` no lo incluye), y
  `visual-ci-contract.test.mjs` protege esa exclusión por contrato. Contarlo como bloqueante es
  sobreestimar el frente.
- **De los 7 avisos de `G_PHPSTAN_BASELINE`, tres no eran avisos de código: eran excepciones
  caducadas del propio `phpstan-baseline.neon`.** PHPStan las reportaba como `ignore.unmatched
  (non-ignorable)` — el patrón de dos ya no calzaba con ningún error real, y el patrón de una tercera
  (`Database.php`) apuntaba a errores que ya no se producen. La excepción que sobraba era ella misma
  el error que ponía el gate en rojo. Retirarlas es lo contrario de regenerar un baseline: deja de
  tolerarse lo que ya no ocurre, no oculta nada nuevo.

**Cerrado en este frente, con `fingerprints: []` intacto (commit `437ad4ea`):**
- `G_PHPSTAN_BASELINE`: los 7 avisos, en dos clases — las tres excepciones caducadas de arriba, y
  cuatro reales (`MultiProjectScope::$user`/`$role` sin lectura, un `is_int()` ya estrechado por su
  propio `@param`, y un `!== false` muerto en `ProgramacionIntermediaController.php:624` sobre un
  método que hoy lanza o devuelve statement, nunca `false`).
- `G_PHPSTAN_PDC`: un docblock de una sola línea que juntaba `@param` y `@return` — PHPDoc no lee dos
  etiquetas en la misma línea, así que el `@return` de `SeguimientoService::activePackageNames()` se
  perdía entero.
- Verificado sobre el árbol de la rama, en contenedor efímero: `phpstan analyse src admin/src` y
  `phpstan -c phpstan-pdc.neon` → `[OK] No errors` los dos (antes 7 y 1); `run-php-tests.php
  --nivel=puro` → 33/33 + PHPUnit 86 pruebas en verde.

**Diferido, cada uno con su porqué — no entran en este PR:**
- **`G_PHP_SUITE` sigue en rojo a propósito; solo se arregló lo que era fixture/CI, no scope.**
  Medido con línea base real: levanté un runtime aislado de `origin/main` (sha `36b731c3`) y otro de
  esta rama, y antes de tocar nada las listas de scripts que fallan en `--nivel=http` eran
  **idénticas — 26 en las dos**. El frente de PHPStan no agregaba ni tapaba ningún fallo. De esos 26,
  la mayoría es deuda preexistente ya repartida en las entradas de abajo (información_schema en 16
  sitios del runtime servido, la consolidación de informes muerta, los INSERT por lote del PDC
  contra el guard — esta última con decisión de arquitectura pendiente de Felipe) y **no se toca
  aquí**: mezclarla en este PR volvería el diff irrevisable y tocaría código de producción fuera de
  alcance. Dos de los 26 sí eran de fixture/CI, no de scope, y se arreglaron:
  `.dockerignore` no dejaba viajar `docs/security/` a la imagen (lo audita
  `test_project_scope_schema_contract.php`, que por eso pasaba en local y fallaba en CI) y
  `test_bi_project_scope.php` tenía cableados dos project_id (`73, 27`) donde el 27 no es miembro de
  `test.A` en el fixture aislado de CI — se ató a los proyectos que el propio test ya calcula. Un
  tercer ajuste salió de arreglar el primero: `test_project_scope_schema_contract.php` nunca
  imprimía una señal de comprobación en su rama de éxito (siempre había fallado antes, así que esa
  rama nunca se había ejercitado en el corredor real) y `scripts/run-php-tests.php` lo marcaba
  SOSPECHOSO. **Reverificado con línea base real tras los tres ajustes:** `--nivel=http` pasa de 76
  a 78 aprobados, de 26 a 24 fallos y de 1 a 0 sospechosos; la lista de 24 que sigue fallando es
  subconjunto exacto de los 26 originales — ningún caso nuevo roto (`diff` limpio salvo los dos que
  se arreglaron).
- **Los dos gates visuales (`G_FULL_APP_FLOW`, `G_RUNTIME_BUDGET_CHECK`) no tienen línea base en CI
  desde hace cinco días.** Establecerla puede exigir aprobar capturas o presupuestos nuevos — decisión
  de Felipe, no de esta sesión.

  **Corregido el 2026-09-04, y la mitad de esta entrada era falsa.** De los dos, **solo
  `G_RUNTIME_BUDGET_CHECK` era línea base.** `G_FULL_APP_FLOW` no tenía nada que aprobar: eran **tres
  bugs de código**, cada uno dejando un módulo respondiendo error (CIC, indicadores y
  auto-programación semanal). Se arreglaron en `fix/carril-visual-full-app-flow` y el gate quedó en
  `13 passed`. La lección, que es lo que vale para la próxima: **«el gate lleva días sin correr» no
  es lo mismo que «su línea base caducó»** — dar por caducado lo que no se ha reproducido deja bugs
  vivos escondidos detrás de una excusa de proceso. Se reprodujeron en runtime aislado en la primera
  corrida, con los mismos 3 fallos de 13 que la corrida `33895697935`.

**2026-09-03 — Seis gates del carril visual están en rojo, y ninguno es de los frentes que los
destaparon. → Merece frente propio: «poner el carril visual en verde».** El job
`design-system-runtime` llevaba sin correr desde el 2026-08-29 (primero por el contrato del
compose, después por el 403 del laboratorio). Con los dos arreglados en
`fix/gate-metadatos-rol-admin`, el job **llega hasta el final por primera vez** y deja ver la deuda
que estaba escondida detrás. Corrida `33759660935`, tema `dark`, sha `d419072d`:

| Gate | Resultado |
|---|---|
| `G_LABORATORY_GATES` | **success** ← las 24 pruebas del 403, arregladas |
| `G_PILOT_LAB_GATES`, `G_PG_PERSISTENCE_RBAC`, `G_SEMANAL_ROLES_PHASES`, `G_RUNTIME_GRANTS`, `G_PHP_ADMIN_DB`, `G_RUNTIME_BUDGET_MEASURE` | success |
| `G_PHPSTAN_BASELINE` | failure — `New PHPStan findings: 7` |
| `G_PHPSTAN_PDC` | failure — 1 error, `Services/Pdc/SeguimientoService.php:672` |
| `G_PHP_SUITE` | failure — los mismos fallos ya medidos en `main` |
| `G_FULL_APP_FLOW`, `G_RUNTIME_BUDGET_CHECK`, `G_KEYBOARD_REFLOW_EVIDENCE` | failure |

Atribución de cada rojo, medida y no supuesta:

- **`G_PHPSTAN_BASELINE`**: `docs/design-system/phpstan-baseline.json` tiene `fingerprints: []`
  —tolera cero— y `main` produce 7 avisos. Los 7 son idénticos en `main` y en la rama (medido con
  `phpstan analyse src admin/src`: `Found 7 errors` en ambos, misma lista:
  `ProgramacionIntermediaController.php:624`, `Database.php`, `MultiProjectScope.php:13,14,28`).
  **Límite de esta afirmación:** el gate imprime el conteo, no los fingerprints, así que la
  equivalencia se sostiene en el número más la comparación local de la lista.
- **`G_PHPSTAN_PDC`**: `activePackageNames()` sin tipo de iterable, en un archivo que ninguno de
  los dos frentes tocó.
- **`G_PHP_SUITE`**: es la cola del gate de scope, ya anotada en el bloqueante siguiente.
- **Los tres últimos**: gates visuales y de rendimiento que llevaban cinco días sin ejecutarse; su
  línea base en CI no existe todavía, así que hay que establecerla antes de leerlos.

**No se arreglan desde el frente del 403 a propósito.** El camino corto —registrar los 7
fingerprints en el JSON para que el gate se ponga verde— es un renglón de trabajo y apaga el único
control que iba a avisar del próximo; `AGENTS.md` lo prohíbe con nombre propio («no regeneres
snapshots ni baselines para forzar un resultado verde»), y sería especialmente torcido hacerlo
desde un PR cuyo hallazgo central es que tapar un error lo esconde durante días.

**Trampa de lectura, medida el 2026-09-03:** en este job los pasos individuales aparecen con ✓
aunque fallen —cada uno registra su resultado en una variable `G_*` y el rojo lo pone el paso final
`Summarize gate results`—. Leer «✓ Enforce PHPStan baseline» y concluir que pasó es un error fácil:
el veredicto está en el resumen, no en los pasos.

**2026-09-02 — El gate de scope rompe la lectura de metadatos en `admin/` y en 20+ pruebas; el
403 del laboratorio ya se arregló, esta es su cola.** `ProjectSqlGuard` rechaza cualquier tabla
calificada por schema desde el 2026-08-29, e `information_schema` lo está. La rama
`fix/gate-metadatos-rol-admin` cerró los siete llamadores de `src/` pasándolos por
`Database::tableExists()/columnExists()/tablesWithColumn()`, pero quedan dos frentes sin tocar,
ambos **ya rojos en `main` antes de ese arreglo** (medido: 24 pruebas fallan en `--nivel=http` y 23
en `--nivel=db` sobre `main` 58d11137, con el mismo conjunto exacto antes y después de la rama):

- **`admin/src/`** arma la consulta a mano en seis puntos (`Models/Project.php:983,1007,1193,1205`,
  `Controllers/DashboardController.php:579`, `Controllers/FamilyCatalogController.php:578`). El
  panel de Admin comparte el `Database` de `src/`, así que sí pasa por el gate: `Project.php`
  aparece en el stack de `test_admin_global_project_model.php`.
- **Las pruebas mismas**, que consultan por `Database::query()` con SQL propio
  (`test_cip_poblado.php`, `test_pdc_v2_*`, `test_preconstruction_import_global_ids.php`,
  `test_schedule_update_draft_import.php`, …). Aquí hay dos causas mezcladas: metadatos calificados
  por schema, y consultas a tablas de proyecto sin `ProjectScope` activo. **No son el mismo
  problema y conviene separarlas antes de tocar nada** — la segunda puede ser el gate funcionando
  como debe, con pruebas que no declaran su scope.

Merece frente propio: es más grande que el 403 y no lo bloquea.

**2026-09-02 — Trampa medida: el nivel `http` de la suite no se puede correr desde un worktree con
el `.env` enlazado.** `CLAUDE.md` manda `ln -s` y para `docker compose` está bien (lo lee desde el
host), pero el enlace apunta a una ruta del host que **dentro del contenedor no existe**, así que
el PHP servido lee un `.env` ilegible: `DEV_DOOR` aparece cerrado y fallan
`test_admin_dev_door_guard`, `test_dev_door_http`, `test_admin_modulos` y
`test_semanal_sanear_csrf`. Parecen regresión y no lo son — con una copia real del `.env` las
cuatro vuelven a verde. Costó una vuelta el 2026-09-02. Pendiente decidir si se documenta en
`CLAUDE.md` o si `scripts/` monta el `.env` de otra forma para el caso http.

**Resuelto 2026-09-02 — el bloqueante «el laboratorio de diseño responde 403 al administrador»
deja de serlo.** Se retira de esta sección, no se deja marcado: el reporte planteaba dos caminos
(semilla con rol `C`, o mala elección entre membresías) y **ninguno de los dos era**. Los datos
estaban sanos — `test.A` conservaba sus cinco membresías `'A'`. La causa real fue el gate de scope
rechazando `information_schema` dentro de un `catch` que convertía la excepción en «la tabla no
existe», con `DEFAULT_ROLE` (`'C'`) como relleno. Arreglado en `fix/gate-metadatos-rol-admin`, que
además trae el contrato del compose de CI porque los dos PR se necesitaban mutuamente para poder
ponerse verdes. La cadena completa, en la entrada del `CHANGELOG`. Lo que queda vivo de aquel
reporte es el primer bloqueante de esta sección: la cola del mismo defecto en `admin/` y en las
pruebas.

**2026-08-28 — `theme.js` deshace el claro de entrada (D12) en 7 páginas reales; bloquea el
arranque del plan de Programa General, no la fase cero actual.** Destapado ejecutando el goal
[[goals/temas-y-forma-fase-cero/goal]] (Task 6): `public/js/modules/aia_ui/theme.js` es un
script vestigial de la época de un solo tema (F0/Task 9) que fuerza `data-aia-theme="dark"`
sin condición — nunca lee `localStorage`, nunca respeta el default. Además publica
`window.AiaDesignSystem.getTheme()`, del cual depende un «gate del shell» sin explorar
(comentario en `views/plan-compras/app.view.php:40`: «sin él, el gate del shell se queda
esperando ese global»), así que no se puede retirar sin más — el arreglo correcto exige
entender ese gate primero.

Dos mecanismos distintos, verificados en el código real:
- **Override** (`theme-bootstrap.js` corre primero, `theme.js` lo pisa después):
  `views/plan-compras/app.view.php`, `views/bi/control-tower-piloto.php`.
- **Ausencia** (`theme-bootstrap.js` nunca se carga, solo `theme.js`):
  `views/auth/login.view.php`, `views/auth/password-reset.view.php`,
  `views/auth/password-forgot.view.php`, `views/core/project_selector.view.php`,
  `views/bi/_layout.php`.

Resultado observable en las 7: siempre oscuro, pese a que D12 (spec
[[docs/superpowers/specs/2026-08-28-temas-claro-oscuro-end-to-end-design]]) diga claro. **No
bloquea el cierre de la fase cero** — ninguna de sus tareas restantes renderiza estas páginas.
**Sí bloquea el plan de Programa General**, el primer módulo: sin resolver esto, D12 nunca se
manifiesta en producción aunque los tests del design system pasen en verde. Detalle completo,
con la investigación y el ruling, en el ledger del goal
(`.superpowers/sdd/2026-08-28-fase-cero-temas-y-forma/progress.md`, sección Task 6).

**Ampliado 2026-08-28 (Task 9, mismo goal): el mismo origen bloquea además D16 (CI corre ambos
temas completos).** Al intentar generar los goldens `light` del carril visual, se midió que el
laboratorio de diseño (`/internal/design-system`) tampoco rinde en claro, por una causa distinta
a `theme.js` pero de la misma familia:

- **`public/css/design-system/laboratory-foundation.css`** ata los tokens oscuros a `:root` a
  secas dentro de `@layer theme`, sin condicionar. **Corregido 2026-08-28 (revisión de Task 9):
  no es un empate de especificidad con `theme-claro.css` — esa hoja NO SE CARGA en el laboratorio
  en absoluto.** Verificado en `DesignSystemHeadComponent::renderLaboratory()` (emite solo
  `theme-bootstrap.js`, `tokens.css` y `lab-entrypoint.css`) y en los ~19 `@import` de
  `lab-entrypoint.css`, ninguno hacia `theme-claro.css`. `laboratory-foundation.css` es la única
  fuente de `--ds-active-*` en esa página. De 18 capturas «claras» intentadas, **9 salieron byte a
  byte idénticas a su gemela oscura** (inspección visual de `actions-light-1180x820.png`: fondo
  oscuro). **El arreglo no es solo condicionar el bloque a `[data-aia-theme="dark"]`** — hecho así
  a solas, el laboratorio se queda sin ningún token declarado en claro (se rompe, no se aclara).
  Hace falta además enlazar `theme-claro.css` en `lab-entrypoint.css`.
- Cabo suelto relacionado: `theme-claro.css` ofrece el gancho `.aia-theme-light`, pero
  `theme-bootstrap.js` (Task 6) solo conmuta `aia-theme-dark` y nunca añade el gancho claro — ese
  camino tampoco entra hasta que se añada.
- **Programa General queda confirmado como una de las 7 páginas de `theme.js`** (arriba): poner
  `aia-theme: light` en `localStorage` y recargar no mueve el atributo — sigue saliendo
  `data-aia-theme="dark"`, verificado con la suite Playwright.

Ningún golden `light` se comiteó (el implementador los generó, confirmó que eran capturas oscuras
disfrazadas, y los borró en vez de fabricar evidencia de cobertura falsa). La matriz de CI, el
carril dark restaurado (17/20 escenarios que antes NUNCA llegaban a capturar) y el contrato
generalizado sí quedaron hechos — D16 cierra con alcance dark-only hasta que este bloqueo se
resuelva. Detalle: `.superpowers/sdd/2026-08-28-fase-cero-temas-y-forma/task-9-report.md` y
sección Task 9 del ledger del mismo goal.

**Decisión de Felipe (2026-08-28), sobre el costo de la matriz doble: mantenerla en
`[light, dark]` desde ya, contra la recomendación de reducirla a `[dark]` mientras el claro no
rinde.** El costo (el job `design-system-runtime` completo se dobla en cada corrida — solo los
dos `.visual.mjs` leen `E2E_THEME`, el resto de gates no distingue tema) se paga desde ahora sin
contrapartida hasta que este bloqueo se resuelva. Los 5 goldens dark con deriva de Tasks 1-8
(3 laboratorio + 2 piloto) también fueron aprobados por Felipe tras revisar la galería — golden
base (macOS) actualizado, `goldenPlatforms.linux` deliberadamente sin tocar (se actualiza desde
una corrida real de Actions si el gate Linux repite la deriva, para no repetir la trampa de golden
Linux desincronizado ya medida y cerrada el 2026-08-24). Commit `717d8a87`.

**El atasco de publicación anterior se desatascó el 2026-08-24**: por orden de Felipe se
consolidaron **trece** ramas en `main` (`6c736d91`) y se retiraron todas las ramas y worktrees.
`main` salió del rojo — el runner de tests PHP da 29/29 con **0 sospechosos**. El cierre, con lo que
los merges destaparon, en
[[docs/superpowers/plans/2026-08-24-p1-desague-y-consolidacion|P1 §Cierre]].

**P1 quedó cerrado entero el mismo día**: noveno pase de veracidad hecho (17 hallazgos, 15 páginas
corregidas, `npm run test:wiki` en `RC=0`) y los dos hallazgos de `linea-base-contractual`
verificados por fin — llevaban desde el 2026-08-19 relatados y sin comprobar.
`test_bi_programa_general_chart_values.php` pasa de **15 `FAIL` a 0**; llevaba rojo desde el
2026-08-14.

**El estado del repo se consolidó en una spec y seis planes**, por encargo de Felipe del
2026-08-24: [[docs/superpowers/specs/2026-08-24-estado-consolidado-del-repo-design]]. Esta lista
sigue siendo la fuente viva de pendientes; la spec es el mapa que dice qué bloquea a qué.
De las 12 sesiones simultáneas del censo, **solo 4 eran de este repositorio**.

<details><summary>Lo que decía antes de este bloque</summary>

Ninguno. El único que había —«abrir una coordinadora nueva»— quedó resuelto el 2026-08-19 cuando
Felipe declaró el reparto y consolidó el repo en una sola sesión. **Y estaba mal planteado desde el
principio:** `docs/coordinacion-sesiones.md:18` dice que «el reparto lo declara el usuario, no lo
reclama nadie», así que no tener coordinadora no es una carencia que haya que subsanar — es el
estado por defecto mientras Felipe no reparta.

</details>

## Ahora

- [x] **CI · regenerar el presupuesto de runtime a la generación 0.5.0 — decisión de Felipe del
  2026-08-28, con su método ya fijado.** **Hecho el 2026-09-04**, aprobado por Felipe ese día, en
  la rama `runtime-budget-0.5.0`: artefacto propio para la medición (era el prerrequisito medido
  abajo), tres corridas por `workflow_dispatch` sobre `70ae2922` en serie, mediana
  `run-33934598207-1-dark` versionada como `0.5.0-measurement.json`, manifiesto, baseline con
  tolerancias de 0.4.0, generación declarada, `check` apuntado, atribución al byte. Validación
  local de forma y procedencia en verde y `static` en `RC=0`; el veredicto final es
  `G_RUNTIME_BUDGET_CHECK` en Actions. Lo que sigue es el texto original de la entrada. El gate `runtime-budgets` está en rojo en el PR #18 por
  `cssGzipBytes`: **131.451 B medidos contra 128.266 + 2.048 de tolerancia** (+3.185 B, ~2,5 %). No
  es una regresión: es el CSS nuevo de la fase cero de temas y forma (24 tokens de estado claro,
  `gravity-flag.css`, tokens de forma/tabla/densidad, `--ds-color-surface-well-*`), todo ello
  posterior a la baseline 0.4.0.

  **Remedido el 2026-09-04 sobre `main` (`aba2589d`, corrida `33895697935`): son 131.477 B**, 26 más
  que en el PR #18, contra el mismo máximo de 130.314. Es **el único gate del carril visual que
  sigue en rojo** tras arreglar `G_FULL_APP_FLOW`, y su atribución se confirmó por
  `git diff --stat 13e692aa..HEAD -- public/css` (el `sourceRef` de la baseline 0.4.0): **846 líneas
  añadidas en 15 hojas**, todas de trabajo legítimo — `theme-claro.css` (115), `tokens.css` (212),
  `readiness-popover.css` (195), `readiness-squares.css`, `gravity-flag.css`,
  `programacion-intermedia.css`. Nada duplicado ni indebido: no hay un arreglo que evite la
  aprobación. **Sigue esperando decisión de Felipe.**

  **Y la sospecha de esta entrada quedó confirmada, así que el trabajo previo existe:** «puede que
  haya que añadir ese artefacto al job» — hay que añadirlo. Comprobado el 2026-09-04 sobre la corrida
  `33901153624` (PR #31), que falla y por tanto dispara `Preserve failure evidence`: su artefacto
  `design-system-failure-evidence-light` **no contiene** `test-output/design-system-runtime-budget.json`.
  Solo trae los dos directorios del laboratorio de teclado y el `docker-compose.log`. La causa es la
  ya conocida de `test-output/`: `Collect keyboard and reflow evidence` corre después de
  `Measure runtime budgets` y pisa la carpeta — el mismo mecanismo que en 2026-08-28 dejó al piloto
  sin capturas y que `ci.yml:424-431` documenta. O sea que **la medición de las seis métricas no
  sobrevive hoy a ninguna corrida**, ni verde ni roja, y sin ella no se puede escribir
  `0.5.0-measurement.json` con procedencia real. Primer paso de este pendiente: subir ese JSON como
  artefacto propio, pegado a su paso de medición y antes de que nada pise la carpeta.

  **El método NO es negociable y está escrito en el propio script** (`scripts/design-system-runtime-budget.mjs`,
  comentario de la generación `0.4.0`): la baseline se mide **en el mismo entorno donde el gate la
  verifica**, es decir en una corrida real de GitHub Actions, nunca en la máquina local. Ese fue
  exactamente el defecto que obligó a saltar de 0.3.5 a 0.4.0 (`initializationMs` agrupa por
  máquina antes que por código: 191-268 ms local contra 596-1.071 ms en Actions), y repetirlo sería
  volver a caer en una trampa ya medida y ya corregida una vez.

  Qué hace falta, en concreto: bajar el `design-system-runtime-budget.json` completo de una corrida
  verde de Actions (el workflow de hoy sube el *recibo* del gate, con el resultado agregado, pero
  **no** el measurement con las seis métricas — puede que haya que añadir ese artefacto al job
  primero), escribir `docs/design-system/runtime-measurements/0.5.0-measurement.json` y su
  `0.5.0-recovery-manifest.json`, declarar `'0.5.0'` en `BASELINE_GENERATIONS`, y apuntar
  `test:runtime-budget:check` a la baseline nueva. Con la atribución escrita al lado, como se hizo
  en [[docs/design-system/runtime-measurements/2026-08-24-atribucion-0.4.0]].

  **No se hizo dentro de la fase cero a propósito**: fabricar la baseline con datos parciales o
  medidos localmente es «actualizar el baseline a mano para forzar verde», que el goal de ese
  frente prohíbe explícitamente.

- [ ] **Terminar la biblia de flujos T3 (PDC v2)** — [[docs/superpowers/plans/2026-08-04-biblia-t3-pdc]].
  Presupuesto y Seguimiento se cerraron el 2026-08-25 (`PDC-006` a `PDC-015`, 11 de 70 rutas). Falta
  Maestro de insumos (13 rutas — empezar aquí, el código ya deja una pista citada en
  `PlanComprasMaestroController:172`), Paquetes y subpaquetes (21 rutas), la SPA (`pdc-app/src/`) y
  las deudas de datos de `docs/pdc-v2.md`. T4 (soporte) y T5 (lectura) quedaron cerradas del todo el
  mismo día.
- [ ] **Los seis planes del reparto del 2026-08-24**, en orden de dependencia:
  [[docs/superpowers/plans/2026-08-24-p1-desague-y-consolidacion|P1 · Desagüe]] (**CERRADO** el
  2026-08-24, con su `## Cierre` escrito) ·
  [[docs/superpowers/plans/2026-08-24-p2-ci-en-verde-y-presupuestos|P2 · CI y presupuestos]] ·
  [[docs/superpowers/plans/2026-08-24-p3-design-system-contrato-y-control|P3 · Design System]] ·
  [[docs/superpowers/plans/2026-08-24-p4-movil-y-tema-claro|P4 · Móvil y tema claro]] ·
  [[docs/superpowers/plans/2026-08-24-p5-cierre-hasta-produccion|P5 · Cierre hasta producción]] ·
  [[docs/superpowers/plans/2026-08-24-p6-higiene-documental-y-coordinacion|P6 · Higiene]], que
  **corre en paralelo a todos los demás**. Ninguna tarea de esta lista queda huérfana: cada una
  está asignada a uno de los seis.

- [ ] **Auditoría de specs 2026-08-20 — pendientes nuevos que no estaban en esta lista.** Las 61
  specs vigentes se verificaron contra el código; el informe completo, con evidencia y cada
  pendiente atado a su plan, está en
  [[docs/superpowers/reports/2026-08-20-auditoria-estado-specs]]. Lo que no estaba anotado en
  ningún lado:
  1. ~~`organizar-la-casa` sin ejecutar~~ — **HECHO (2026-08-20)**: vistos en
     `decisiones/vistos/`, historial de sesiones versionado, plantillas borradas, las siete
     reglas en `docs/coordinacion-sesiones.md` y `AGENTS.md` las referencia.
     Ver [[goals/organizar-la-casa/goal]].
  2. ~~`estados-severidad-contrato` bajo 3 niveles~~ — **HECHO (2026-08-20)**: spec reescrita
     con notas de revisión fechadas. La ejecución del frente **también cerró el mismo día** (ver
     el cierre de `ds-f1a-estados-severidad` más abajo): la contención se midió, los frentes de
     `bold-neumann` ya habían terminado, y la saturación del filete en Intermedia **no se confirmó
     en pantalla** — capturas en `goals/ds-f1a-estados-severidad/evidence/` para veto de Felipe.
  3. ~~Verificación de `/indicadores` y CNP/CNC/CIC~~ — **HECHO (2026-08-20)**: `/indicadores`
     está migrada (pilot; su contenido es un iframe). **CNP/CNC/CIC son legacy real**: el shell es
     `aia-*` pero `legacyCards.js` pinta todo con clases legacy. F0-022 (mayor) lo detectó sin
     tarea que lo cierre → la migración entra a **DS-F2** con dueño (fila nueva abajo) y los dos
     planes de UI-audit (2026-07-31, 2026-08-01) quedan **superados como vehículo**.
  4. **Humo del PDC v2 en `prueba-lps`** — la mitad anónima **HECHA (2026-08-20)** con códigos
     crudos: `/plan-compras` 302→login (enrutado y protegido), bundle `pdc.js` 200, `/dev/entrar`
     302→login (candado puesto). **La mitad autenticada quedó HECHA el mismo día**: Felipe
     abrió sesión (`test.R`) en el navegador integrado y sobre ella se verificó SPA con datos
     reales, APIs en 200, RBAC permitido/denegado y consola limpia. El paso previo de CP-F-E
     está cumplido. Evidencia:
     [[docs/superpowers/reports/2026-08-20-cierre-pendientes-auditoria]].

- [ ] **apply-recalculo-estados en PRODUCCIÓN** — el apply sobre **desarrollo** ya se ejecutó
  (`aa965bf5`, 2026-08-19 13:40): 40.664 filas migradas, acta en
  `goals/apply-recalculo-estados/acta-del-apply.md`, reconciliación exacta. **Producción sigue sin
  tocar y necesita su propia autorización explícita** — publicar en `main` no la concede. Cuando
  llegue, la lección del apply de desarrollo aplica: **el respaldo probado horas antes ya no cubría
  la base** (8 filas nuevas sin respaldo), así que se rehace y se vuelve a probar la restauración
  inmediatamente antes, no la víspera.
- [x] 2026-08-24 — **`runtime-budgets-al-ci` CERRADO vía Plan P2.** Fase 2 confirmada sin causa
  local que arreglar; Fase 3 tomó la procedencia de la corrida verde de Actions
  [32787664690](https://github.com/jbenite7/lastplanneraia-construccion/actions/runs/32787664690).
  `closeout-evidence.json` llega a **9/9 sin ningún gate `blocked`**. Detalle: [[goals/runtime-budgets-al-ci/goal]].
- [ ] **DS-F1, lo que queda del contrato** — la escala de estado cerró (F1a). Faltan tokens,
  primitivas `aia-*`, escala de severidad y escala de z-index. Arranca con brainstorming: el
  contrato es decisión de negocio. Entrada lista: los 68 hallazgos de DS-F0.
- [x] 2026-08-24 — **`linea-base-contractual` cerrada, integrada por otra vía.** Ya no espera su
  `## Cierre` propio: Felipe ordenó mergear todas las ramas en el desagüe de P1, así que se integró
  junto con las otras doce en vez de declararse y publicarse aparte. Sus dos hallazgos, relatados
  desde el 19 de agosto y sin comprobar por nadie, se verificaron el mismo día: la migración no
  movía ninguna fila porque los 30 proyectos sin línea base no tienen ni una de cronograma —hueco
  de datos, no defecto— y `test_bi_programa_general_chart_values.php` afirmaba un contrato ya
  derogado. Detalle: [[docs/superpowers/plans/2026-08-24-p1-desague-y-consolidacion|P1 §Tarea 5]].

  **`semanal-fondo-por-matiz` ya cerró y está publicado** (`2fc5998e`, 2026-08-19): las dos fases
  con cinco fondos distintos, filete solo en `urgent` y `attention`, capturas miradas a 1180×820
  dark, suite del gate 4/4 en `RC=0` **después** de integrar. Su cierre destapó que la sonda de la
  fase Calificación **no forzaba la fase** y lo declaraba igual, porque comprobaba su propia
  sustitución de texto: ver `## Cierre` en [[goals/semanal-fondo-por-matiz/goal]].

- [x] 2026-08-19 — **cola de estados, severidad y color**: los siete pendientes atendidos. Cuatro
  ejecutados (remapeo de Programa General con `Fuera de Ventana` —el 39,3 % de la tabla, que se
  pintaba igual que `Actividad Futura`—, la crema de la leyenda de Intermedia, y dos guards que antes
  no podían ponerse rojos) y cinco decisiones medidas y elevadas en [[DECISIONES_PENDIENTES]].
  Registro: [[goals/cola-estados-severidad/goal]].

- [x] 2026-08-19 — **repaso de TODOS los specs y frentes**: de 13 `goals` sin cerrar quedaron **6**.
  Siete se cerraron con verificación de hoy: `adopcion-logo-construccion`, `pdc-tanda2-plan-verdad`,
  `apply-recalculo-estados` (solo desarrollo), `contadores-cero`, `semana-fija-visual`,
  `repaso-usabilidad-no-tablas` y `contrato-estados-modulo-fantasma`. Los dos últimos estaban
  cerrados y firmados en prosa, **sin el encabezado `## Cierre` que el mapa de estado lee**.

- [x] 2026-08-19 — **el CI llevaba 40 corridas sin pasar** (23 `failure`, 17 `cancelled`, ni una
  verde) por una sola aserción: `full-app-flow` exigía en móvil que el `body` reservara sitio para
  el carril, justo lo que la spec del menú flotante derogó el 2026-08-14. Arreglado en `ab2c34f1`.
  Y el lint de wiki denegaba publicaciones por basura que git ignora: arreglado preguntándole a git.

- [x] 2026-08-24 — **`gates-al-ci` CERRADO vía Plan P2.** Sus dos decisiones escaladas (D-7 `test.C`
  en `DEV_DOOR_USERS`, D-GAC-5(b) baseline con `cssGzipBytes` medido) ya estaban ejecutadas por
  trabajo previo; faltaba el CI en verde, que P2 resolvió. Detalle: [[goals/gates-al-ci/goal]].

- [ ] **G7 · paralelizar PHPStan en su propio job, sin datos suficientes todavía.** El plan P2 lo
  proponía como candidato («no necesita la app levantada»); el resumen del job (`Summarize gate
  results`, cableado en P2) ya vuelca duración de tres gates a `GITHUB_STEP_SUMMARY`, pero falta
  reunir varias corridas para saber si el ahorro compensa el costo de un `checkout`+`setup` extra.

- [ ] **zizmor · dos hallazgos `cache-poisoning` (confidence: Low) evaluados y aceptados, no
  arreglados.** El repo `lastplanneraia-construccion` es **público** (confirmado con `gh repo view`,
  no asumido): `actions/setup-node` + `docker/build-push-action` con `cache-from/to: type=gha`
  conviven en `design-system-runtime`. Quitar el cache de capas de Docker eliminaría el vector, pero
  con costo de performance real y en contra de G7. Revisar si zizmor mejora su detección o si el
  repo cambia de visibilidad.

- [x] 2026-08-20 — **Las once decisiones pendientes, RESUELTAS** en sesión dedicada con Felipe
  (D-1 a D-9, D-11 y los plugins de Obsidian). Ninguna queda abierta; el detalle y el porqué de cada
  una, en [[DECISIONES_PENDIENTES]] §«Ronda de decisiones del 2026-08-20». Lo que destraba: **D-11
  es el único paso rojo del CI**, y D-7/D-8/D-9 sacan de la parálisis a tres frentes cuyas
  condiciones contaban artefactos que ya no existen. Los plugins quedaron instalados y verificados
  en pantalla; **Iconize se excluyó** por estar declarado como descontinuado por su autor.

  <details><summary>La redacción anterior de este punto</summary>

- [ ] ~~Las nueve decisiones de [[DECISIONES_PENDIENTES]] esperan a Felipe.~~ D-1 realces por
  condición del dato · D-2 la excepción crítica del chip · D-3 los 30 estados sin `key` · D-4
  `foundation-shell` y sus 20 rutas sin escenario · D-5 la variante de pestañas que le falta a
  `navigation` · **D-6** el vocabulario de la cascada, cuyo objetivo numérico ya se cumplió solo
  (25 cadenas contra las 29 que pedía) · **D-7** `bi-control-tower-gemini`, parado mes y medio por
  una condición imposible · **D-8** `design-system-nucleo-gobernanza`, que exige quince gates donde
  el archivo declara nueve · **D-9** hasta dónde llega la reapertura móvil y el tema claro.
  Cada una lleva su medición hecha; ninguna necesita más trabajo antes de decidirse.

  </details>

- [ ] **Los controles que miden el papel y no el código: ya van cuatro.** El aviso que pidió decidir
  sobre `2026-07-20-sidebar-canonico-laboratorio` mide la antigüedad del documento y nunca comprueba
  si el trabajo existe: estuvo 35 días pidiendo criterio a Felipe sobre algo aprobado por él en julio
  y en producción desde entonces. Es la misma familia que
  [[memoria/trampas/guard-valida-declaracion-contra-si-misma]],
  [[memoria/trampas/guard-de-texto-no-ve-el-parseo]] y [[el-contador-no-mide-el-archivo]]. **Merece
  ficha propia y, sobre todo, arreglo del disparador** — vive en el harness, no en este repo, así que
  el arreglo se propone, no se aplica.

- [ ] **`test_bi_programa_general_chart_values.php` imprime `FAIL` y sale con `RC=0`.** No propaga su
  propio fallo, así que un runner que solo mire el código de salida lo da por bueno. Detectado al
  cerrar P1 el 2026-08-24. Familia de [[memoria/trampas/el-codigo-de-salida-se-pierde-en-la-tuberia]].
- [ ] **30 proyectos sin cronograma consolidado** — medido al verificar `linea-base-contractual`: no
  es deuda de la migración, que es correcta, sino un hueco de datos. Los 30 sin línea base no tienen
  **ni una fila** en `programa_consolidado`. Cerrarlo es decisión de negocio.

- [ ] **Ficha de trampa pendiente: «el guard que valida su declaración, no su efecto».** Es la
  tercera vez que se mide la misma familia en este repo —hermana de
  [[memoria/trampas/guard-de-texto-no-ve-el-parseo]] y
  [[memoria/trampas/guard-valida-declaracion-contra-si-misma]]— y le falta ficha propia en
  `memoria/trampas/`. El caso nuevo, con su medición, está en el `## Cierre` de
  [[goals/semanal-fondo-por-matiz/goal]]: no se escribió allí porque ese frente no declaraba la
  ruta `memoria/trampas/**`.

- [ ] **linea-base-contractual** — sembrado por migración SQL, con `database/migrations/**`
  autorizado explícitamente por Felipe para este frente. **No tiene `goals/<slug>/` propio**: su
  registro vive solo en `decisiones/linea-base-contractual-coordinadora.md`.

  **Dos hallazgos RELATADOS y sin verificar por esta sesión** (2026-08-19, de la sesión que integró
  las ramas). Verificarlos es el primer paso del frente, no un trámite:
  1. **La migración se corrió contra desarrollo y no modificó ni una fila.** De 30 proyectos sin
     línea base, ninguno tiene cronograma consolidado usable, así que el `JOIN` no alcanza a
     ninguno. Se ejecuta, sale en verde y no hace nada — el mismo patrón que
     [[el-contador-no-mide-el-archivo]]: una herramienta que ante «no hay nada que hacer» devuelve
     algo con forma de resultado. Respaldo en `~/Documents/respaldo-lineabase-20260819-2037.sql`.
  2. **`test_bi_programa_general_chart_values.php` se pone rojo con el merge** (los `FALLA` de nivel
     `datos-proyecto` pasan de 12 a 13). No es regresión: el frente movió el origen de la fecha
     contractual y el test todavía afirma lo viejo. Si el comportamiento nuevo es el correcto, el
     test hay que actualizarlo — y eso es parte de cerrar, no algo aparte.

  Ese test **se arma sus propios fixtures** (34 inserts en el archivo), así que ninguna migración
  sobre datos existentes lo va a tocar.
- [ ] **bi-control-tower-gemini** — bloqueado desde el 2026-08-10 por causa mal diagnosticada: no
  es «falta aprobación visual», es que pide aprobar 6 modos y 3 usan el tema `linen`, retirado el
  2026-07-25. Hay que rehacer la condición de hecho, no correr los tests. **Ya NO depende de
  MO-F3** (D-9, 2026-08-20): la condición se recorta a los tres modos dark y el frente puede
  cerrar sin esperar a ningún tema claro.

- [ ] **Ordenar `CHANGELOG.md`.** No está en orden cronológico inverso: `[1.1.1]` y `[1.1.0]`
  aparecen antes que `[Sin publicar]` y que `[1.2.0]`. Detectado el 2026-08-19 y **no corregido en
  el mismo turno a propósito**: reordenar 400 líneas de historia ajena a mano arriesga perder
  contenido, y eso pide su propia pasada con verificación.

- [x] 2026-08-28 — **Corregido: la entrada de este mismo día sobre el `.env` roto dentro del
  contenedor duplicaba una trampa ya fichada.** Al verificar visualmente la Task 10 del goal
  [[goals/temas-y-forma-fase-cero/goal]] con `LPS_CODE_ROOT="$(pwd)" docker compose up -d app`, la
  puerta de desarrollo se cerró sin explicación (`DEV_DOOR`/`DEV_DOOR_USERS` quedan `(unset)`
  porque el symlink de `.env` apunta a una ruta del host que no existe dentro del contenedor cuando
  este monta un worktree). Esto ya está fichado con su mecanismo completo y su remedio en
  [[memoria/trampas/env-enlazado-se-rompe-dentro-del-contenedor]] (2026-08-21). **Dato nuevo que sí
  vale la pena anotar:** el remedio prescrito ahí (copia temporal del `.env`, borrarla al terminar)
  requiere tocar un archivo con secretos — en esta sesión ese comando específico fue denegado sin
  autorización explícita, así que se usó la pila aislada de CI (`docker-compose.ci.yml`) como
  alternativa, que no depende del symlink en absoluto. Vale como tercera vía cuando la copia
  temporal no está autorizada.

## Diferibles

- [x] 2026-08-28 — **El botón de colapsar el sidebar del laboratorio no responde a Enter por
  teclado** (`design-system-lab-keyboard.mjs:83`, ambas patas del CI, tema claro y oscuro).
  **Resuelto el 2026-09-05, y era el test, no el componente.** Reproducido en runtime aislado e
  instrumentado en Playwright: la pulsación de Enter sobre el botón dispara
  `keydown→keypress→click→keyup`, nadie hace `preventDefault`, y el `click` cambia el estado **una
  sola vez** — igual que el click de ratón y Espacio. Lo que fallaba era el punto de partida: el
  test nació el 2026-07-20 (`321b0951`) cuando el sidebar del laboratorio arrancaba expandido, y
  `4bc75ef9` (2026-07-23) cambió el fixture a `'initialState' => 'collapsed'` a propósito. Enter
  alterna, así que sobre un sidebar colapsado lo expandía y el assert esperaba lo contrario. El HTML
  servido ya trae `data-sidebar-state="collapsed"` antes de cualquier JS. Arreglo en el test, sin
  aflojar ningún assert: si arranca colapsado, se expande primero (y se afirma), y desde ahí se
  prueban las dos transiciones que Escape necesita. `test:design-system:evidence` en `RC=0` claro y
  oscuro, `static` en `RC=0`. Sigue siendo no bloqueante por contrato; no se ascendió. Texto original: El test
  enfoca `[data-sidebar-toggle]` y presiona Enter; `data-sidebar-state` se queda en `expanded` en
  vez de pasar a `collapsed`. El botón es nativo (`<button type="button">`,
  `src/View/Components/DesignSystemComponent.php:432`) y el listener solo escucha `click`
  (`public/js/modules/aia_ui/sidebar_navigation.js:127`) — un `<button>` nativo sí dispara `click`
  al presionar Enter en un navegador real, así que hace falta reproducirlo en Playwright para saber
  si es el propio test o el componente. **No es una regresión de fase cero**: el mismo assert ya
  existía antes, pero el test moría dos líneas más arriba por el tema hardcodeado (`data-aia-theme`
  fijo en `'dark'`) — al arreglar eso en `b97a1b54` el test avanzó y recién ahora se ve este fallo.
  No bloquea el gate (vive en `keyboard-reflow-evidence`, no bloqueante por diseño). Fuera de
  alcance de `docs/superpowers/plans/2026-08-28-fase-cero-temas-y-forma.md`.

- [ ] 2026-08-27 — **ESLint instalado en `ct-app/` y `pdc-app/` (rama `eslint-ct-pdc-app`); `pdc-app`
  destapó 39 hallazgos reales sin arreglar.** Ninguna de las dos SPA tenía ESLint configurado
  (`biome.json` de la raíz solo cubre `public/js`, `public/css`, `admin/public/css` — no las SPA); lo
  disparó una revisión de `react-reviewer` del 2026-08-27 sobre `Semaforo.tsx`/`Pareto.tsx` que
  marcó como HIGH que las reglas de hooks dependían solo de revisión manual. Instalados
  `eslint` + `eslint-plugin-react-hooks` + `eslint-plugin-jsx-a11y` en las dos, con `npm run lint`.
  **Sin `typescript-eslint` a propósito:** rechaza correr contra TypeScript 7 (el compilador nativo
  que ya usan ambas SPA) con un hard-stop, no un warning — issue
  [typescript-eslint/typescript-eslint#10940](https://github.com/typescript-eslint/typescript-eslint/issues/10940),
  sin ETA de soporte. Probé aislarlo con `overrides` de npm (TS7 para `tsc`/build, TS6 solo para el
  linter) y no es viable: `typescript` es peerDependency pura del lado del linter, así que npm nunca
  crea la copia anidada que el override necesita para tener efecto. La alternativa real —el alias
  global que documenta Microsoft, renombrando el paquete `typescript` del proyecto— toca el pipeline
  de compilación real de ambas SPA y se descartó por desproporcionado para «instalar un linter»;
  decisión confirmada con Felipe. En su lugar, el parser es `@babel/eslint-parser` +
  `@babel/preset-typescript` (parseo sintáctico puro, sin depender de la API del compilador TS, igual
  que ya hace Vite/esbuild en el build). Costo: `no-undef`/`no-unused-vars` quedan apagadas —sin
  info de tipos, un import o una interfaz usados solo como tipo salen como falso positivo—; `tsc` en
  build ya cubre ambos casos con tipos de verdad.
  - **`ct-app/` corre limpio: 35 archivos, 0 hallazgos.**
  - **`pdc-app/` no: 24 errores + 15 warnings en 12 de 85 archivos**, sin tocar — el pedido explícito
    era documentar, no arreglar sin revisar. Por regla: 18 `react-hooks/set-state-in-effect`
    (`setState` síncrono dentro de un efecto — patrón nuevo del ruleset `recommended-latest` de
    react-hooks v7, alineado a React Compiler), 15 `react-hooks/exhaustive-deps`, 3
    `react-hooks/rules-of-hooks`, 2 `jsx-a11y/no-static-element-interactions`, 1
    `jsx-a11y/click-events-have-key-events`. **Los 3 `rules-of-hooks` de `src/lib/agGrid.ts:310-312`
    son falso positivo, no bug:** el hook custom se llama `usaAnchoContenedor` (español, «usa») y la
    regla solo reconoce el prefijo inglés `use` como hook custom válido — cualquier hook nombrado en
    español dispara esto. Revisar si conviene una convención de nombres para hooks custom o vivir con
    el ruido. Archivos con hallazgos reales (deps arrays / setState-en-efecto):
    `src/components/ListaBuscable.tsx`, `src/components/SubpaquetesPanel.tsx`,
    `src/pages/ComparativoPresupuesto.tsx`, `src/pages/ImportarPresupuesto.tsx`,
    `src/pages/MaestroInsumos.tsx`, `src/pages/PaquetesAsistente.tsx`,
    `src/pages/PaquetesContratacion.tsx`, `src/pages/PasosContratacion.tsx`,
    `src/pages/PlanFechas.tsx`, `src/pages/Seguimiento.tsx`, `src/pages/VisorPresupuesto.tsx`.
  - **Decisión: no se integró a `npm run check:frontend` ni a `.github/workflows/ci.yml`.**
    `check:frontend` es específicamente biome sobre PHP-side JS, dominio distinto. Y el CI del repo
    hoy no corre build, test ni lint de ninguna de las dos SPA — meter solo un gate de lint sería
    prematuro (bloquearía CI por deuda preexistente de `pdc-app` sin que build/test estén cubiertos
    tampoco) e inconsistente. Revisar cuando exista integración CI real de las SPA.
  - No bloqueante para el piloto Ola 1 (`ola1-torre-piloto`) — deuda de infraestructura de calidad.

- [ ] 2026-08-26 — **Cuatro tokens decididos el 2026-08-11 y nunca definidos** (`D-F1-3`): la
  decisión fue «definirlos como tokens de verdad» y hoy `--aia-text-muted`, `--aia-warning-soft-bg`,
  `--aia-warning-border` y `--aia-red-primary` siguen sin existir en ningún CSS — el JS pinta con
  las reservas hex. Medido el 2026-08-26 al destaparse que la entrada se contradecía consigo misma
  (cuerpo «resuelta», índice «sin aplicar»; ganó el índice). Trabajo de una línea por token, con el
  valor de su reserva. Entra con la Ola 2 de la v0 o antes si estorba.

- [ ] 2026-08-26 — **«Panel de inicio» no existe: `/dashboard` es un redirect ciego, no una
  decisión de producto tomada.** Verificado al planear la tarea cero de la v0:
  `DashboardController::index()` (`src/Controllers/Core/DashboardController.php:17`) nunca renderiza
  vista, solo calcula con `ProjectLandingService::resolve()` a dónde mandar y hace
  `header(Location)`. La línea F de `reparto-trabajo-pendiente` (2026-08-03) ya lo pedía como
  decisión de producto propia —qué ve un residente al entrar, qué ve un visualizador, qué pasa sin
  semana activa— y sigue sin resolverse tres semanas después. Se había dado por «HECHA» dos veces
  hoy (mapa único y mi propia lectura del router) por ver que la ruta existía, sin abrir el
  controlador. Corregido en
  [[docs/superpowers/specs/2026-08-25-mapa-unico-del-trabajo-vivo-design]]. Entra en la Ola 3 de
  [[docs/superpowers/specs/2026-08-26-v0-del-producto-design]] si Felipe decide construirlo; si no,
  el redirect actual es aceptable y esta línea se descarta con ese motivo.

- [ ] 2026-08-25 — **Existe una spec madre; el trabajo vivo se lee ahí y de ahí salen los planes.**
  [[docs/superpowers/specs/2026-08-25-mapa-unico-del-trabajo-vivo-design|El mapa único del trabajo
  vivo]] mide las siete specs vigentes contra el código y agrupa lo que queda en cuatro bloques:
  papeles por cerrar (minutos), pequeño de valor alto (horas), tres frentes grandes de verdad
  (semanas: que la Torre de Control escriba, el design system sobre las tablas, y el tema claro que
  no existe) y lo bloqueado por decisión humana. **Por decisión de Felipe no se abren más specs
  atómicas:** lo siguiente son planes con `writing-plans` sobre ese documento.

- [ ] 2026-08-25 — **`despliegue-pdc-v2-produccion`: falta la prueba de uso, NO el despliegue.**
  Corregido hoy un dato que llevaba trece días circulando falso: «producción sin tocar, sigue en
  `1aa7c69`». **El release completo salió el 2026-08-12** (`1aa7c694` → `939b7928`, 1.763 commits,
  con el Plan de Compras dentro), y hubo más despliegues el 2026-08-20. De las siete condiciones de
  hecho de esa spec, **cinco están cumplidas**; las dos que faltan son el **humo funcional
  autenticado** (el del 12-ago se hizo con el sitio en mantenimiento, por rutas exentas: prueba que
  la aplicación arranca, no que el módulo opere) y la **constancia escrita de que alguien de obra
  llegó a la pantalla del plan**. Es media hora de comprobación, no un despliegue. Trampa escrita en
  [[memoria/trampas/el-sha-de-partida-leido-como-estado-actual]].

- [x] 2026-08-25 — **`runtime-budgets-al-ci`: le falta media condición de hecho, y no es el cierre
  de dos minutos que parecía.** **Cerrado el 2026-09-04:** al final sí fue corto, pero solo porque
  otro frente lo dejó maduro sin buscarlo — el PR #31 arregló los tres bugs que tenían a
  `full-app-flow` en rojo, y la primera corrida verde de `main` (33902983755, `6d82bba2`) produjo
  el recibo con procedencia real que faltaba. Bajado del artefacto y fijado en dos tiempos
  (`15b075c2` recibo, `98dee120` índice), `static` en `RC=0`. Spec a `cerrado`. Texto original: Medido al pasar: los nueve gates de `closeout-evidence.json` están
  en `passed`, y `runtime-budgets` **sí** tiene procedencia de corrida real de Actions (32787664690).
  Pero la condición de hecho exige **dos** gates con esa procedencia, y `full-app-flow` lleva recibo
  **«regenerado localmente»** (`verifiedAt: 2026-08-14`, `sourceRef: 79debf28`). Un recibo local no
  es una corrida de Actions — es exactamente la distinción que esa spec existe para instaurar, así
  que darla por buena vaciaría su propósito. **Por eso su spec sigue `vigente` mientras su goal ya
  declara `## Cierre`: el goal se adelantó.** Qué falta, en una línea: bajar de una corrida verde de
  Actions el recibo de `full-app-flow` y fijar su procedencia, igual que se hizo con el otro.

- [ ] 2026-08-25 — **Aviso, no encargo: `test_legacy_csrf_guard.php` está rojo en `main`.** Medido
  sobre `410ac132` con árbol limpio, nivel `puro`: **26 pasan, 3 fallan de 29**. El rojo es
  `FAIL token válido pasa el guard` (rc=1) — y llega el mismo día en que se cerró el frente de CSRF
  de `LpsApiController` (ver «Hechas»), así que **con toda probabilidad es trabajo en curso de esa
  sesión y no una regresión suelta**. Se anota sin tocarlo, para que quien lo vea no lo diagnostique
  desde cero. Los otros dos (`test_pdc_v2_import_parser`, `test_pdc_v2_maestro_sinco_parser`, rc=255)
  son **ambientales**: `/tmp` de solo lectura dentro del contenedor, no código.

- [x] 2026-08-24 — **Frente C de SiteGround · ejecutado y DESCARTADO por su propia verificación.**
  Autorizado por Felipe. `fetch --depth=1` + `reflog expire` + `gc --prune=now` sobre `prueba-lps`,
  los tres en `rc=0`, y entonces las comprobaciones lo rechazaron: **`git pull --ff-only` da
  `rc=128`** («Not possible to fast-forward») — y ese es **el comando del que depende la rutina de
  despliegue**, así que el shallow no lo degrada, lo inutiliza. La comprobación que la spec declaró
  decisiva lo confirma por el otro lado: con shallow **no detecta** la migración nueva del rango, y
  con historia completa **sí** (`20260819_sembrar_linea_base_contractual.sql`). **Y el ahorro
  tampoco aparecía:** tras el `gc` el `.git` seguía en 366 MB, porque `gc` no poda lo alcanzable
  desde `HEAD` y mover `HEAD` es justo lo que el `pull` roto impide. Revertido con
  `git fetch --unshallow` y **servidor verificado sano**: `pull` en `rc=0`, sin shallow, árbol con 0
  cambios sueltos. Efecto colateral benigno: `prueba-lps` quedó al día tras 213 commits de atraso.
  **No se reintenta.** Trampa escrita en
  [[memoria/trampas/shallow-rompe-el-pull-ff-only-del-despliegue]].
- [ ] **Retirar `cell-state-vocabulary.mjs`, código muerto** —
  `public/js/modules/shared/cell-state-vocabulary.mjs` no lo importa nadie salvo su propio gate: los
  renderers de Handsontable nunca lo llaman, así que su `STATE_MAP` documenta una intención, no un
  comportamiento. Venía de la fase 7 de `cierre-dark-mode`, que se derogó el 2026-08-24 — **este
  pendiente sobrevive a la derogación** y se anota aquí para que no se pierda con ella. Lo detectó
  el saneamiento del 2026-08-03 en [[goals/cierre-dark-mode-y-tablas/goal]] y sigue vivo, verificado
  hoy.
- [ ] **Separar `Capítulo` del eje de estado (D-VOC-4)** — decidido el 2026-08-11: sí se separa,
  pero en frente propio con autorización aparte, porque `Capítulo` es un valor persistido en datos
  reales de obra (`{prog_consolidado}.Estado`) y exige dry-run, respaldo verificable y gate según
  `docs/global-tables-architecture.md`. No se ejecuta dentro de otro frente. Ver
  `docs/decisiones-pendientes.md` D-VOC-4 y el `## Cierre` de
  [[docs/superpowers/specs/2026-08-11-vocabulario-estados-cascada-design]].
- [ ] **A11y · el gemelo callado del filtro de cabecera (Programa General) — no se reprodujo
  (2026-08-24)** — medido de nuevo tras el hallazgo del 2026-08-24: 24/24 botones con
  `aria-hidden` en las dos mitades (12/12 `ht_master`, 12/12 `ht_clone_top`), sostenido en 12
  muestras cada 250 ms, tras borrar el atributo a mano, tras `render()` a +50 ms y +1550 ms, tras
  `updateSettings()`, `loadData()`, abrir/cerrar menú, resize, scroll y recarga. Código idéntico
  entre `origin/main` y `HEAD` en esa función. **Primera hipótesis a descartar si reaparece: la
  medición original se tomó contra un contenedor que montaba otro árbol** — el mismo fallo que
  mordió dos veces esa misma jornada en la sesión que lo midió. Solo se actualizó el comentario en
  `public/js/modules/programa_general/hot.js:2411`, sin arreglo de código.
- [x] 2026-08-24 — **PG · golden visual CERRADO — no era no-determinismo de fuente, era un golden
  Linux 12 días atrasado.** La hipótesis de `tabular-nums`/no-determinismo de esta ficha era falsa:
  el golden **Linux** de `programa-general.visual.mjs` quedó congelado en `6cf8d28c` (2026-08-12) y
  nunca se recapturó, mientras el golden macOS del mismo test se recapturó al menos tres veces
  después (`18d05c1f`, `b1cf59c9`, `f52d8120`) con cambios reales aprobados por Felipe. El diff era
  real: el estado **Fuera de Ventana** entró al vocabulario el 2026-08-19 (`8418449a`, una semana
  después de la captura Linux) y su chip nuevo parte la leyenda en dos filas a 1180×820, empujando
  toda la tabla. Recapturado con la evidencia real de la corrida de Actions 32776968532 (no una
  captura local en macOS), aprobado por Felipe viendo las tres imágenes. Detalle en el commit
  `76b86555`.

- [ ] **BI · 336 filas huérfanas en `programacion_semanal`** — sin `unique_id` que exista en
  `programa` (verificado en `lastplanneraia_dev` con `LEFT JOIN`). Destapado el 2026-08-20 al
  aplicar el arreglo de mojibake de F0 (Control Tower): una fila huérfana bloqueó el `UPDATE` con
  un error de llave foránea. No se investigó el origen ni si están en producción — solo se
  confirmó que existen y que no se tocaron. Origen:
  [[docs/superpowers/plans/2026-08-20-control-tower-f0-higiene-datos]], Task 4.
- [ ] **BI · `tests/test_causas_codificacion.php` tiene un punto ciego de colación** — usa
  `SELECT DISTINCT` sin `BINARY`; bajo `utf8mb4_general_ci`, un texto roto («Diseńos») y su
  versión ya reparada («Diseños») colapsan al mismo grupo `DISTINCT` y MySQL puede devolver el
  representante correcto, escondiendo la fila rota. Confirmado el 2026-08-20: el test reporta
  PASA con 2 filas todavía rotas (las huérfanas de arriba), verificado con `LIKE BINARY` directo.
  Arreglo: reescribir la detección con `LIKE BINARY` o comparar bytes, no `DISTINCT` normal.
- [ ] **BI · `tests/test_cip_poblado.php` no prueba realmente el arreglo del backfill** —
  solo comprueba `COUNT(DISTINCT profesional) > 0`, que pasaría igual con el código viejo si
  coincide una sola semana. Debería aseverar cobertura multi-semana (`COUNT(DISTINCT Semana)`).
  Hallazgo de la revisión final de F0, 2026-08-24. Origen:
  [[docs/superpowers/plans/2026-08-20-control-tower-f0-higiene-datos]], Task 1.
- [ ] **BI · el backfill de `cip` no tiene guarda de costo** — `updateCICProyectos()` repite
  ~4 consultas por semana por proyecto en cada corrida, incluidas semanas ya completas que no
  cambian. Un proyecto en semana 60 son ~240 consultas por corrida solo para reconfirmar lo ya
  hecho. No medido bajo carga real. Guarda barata propuesta: saltar la semana si
  `COUNT(*) FROM cip WHERE Semana = ?` ya iguala el número de responsables de esa semana.
  Hallazgo de la revisión final de F0, 2026-08-24.
- [ ] **BI · `scripts/higiene/reparar-mojibake-causas.php` no está acotado por `project_id`** —
  escribe a través de todos los proyectos. Defendible para higiene global de catálogo, pero
  contradice la regla general de aislamiento del repo; falta un comentario que lo declare
  explícito. Hallazgo de la revisión final de F0, 2026-08-24.
- [ ] **BI · dos tests de F0 salen "sospechosos" para el runner por el mismo patrón** —
  `tests/test_causa_atribucion.php` y `tests/test_causas_codificacion.php` imprimen `"PASA: ..."`,
  y `PASA` (con A) no contiene ninguna señal reconocida por `SENALES_DE_COMPROBACION` (`pass`,
  `ok`, `comprobacion`, `comprobación`, `✓`, `correcto`) — difiere de `pass` en la cuarta letra.
  Los dos comprueban algo de verdad (ejecutan y pasan directamente, rc=0), pero el runner no los
  reconoce y los marca sospechosos. Arreglo: cambiar el texto de éxito de los dos a algo que
  incluya una señal reconocida, p. ej. `"PASA (correcto): ..."`. Preexistentes de las Tareas 3 y 4
  de F0 (ambas ya cerradas con revisión limpia); confirmado por el controller el 2026-08-24 que
  son idénticos contra el commit previo a la ronda de arreglos de la revisión final — no los
  introdujo esa ronda. Origen:
  [[docs/superpowers/plans/2026-08-20-control-tower-f0-higiene-datos]], Tasks 3 y 4.

- [ ] **A11y · el gemelo callado del filtro de cabecera (Programa General)** — de 24 botones de
  filtro idénticos, `markDecorativeHeaderTriggers` marca 12 con `aria-hidden` y deja 12 sin marcar.
  Son 12 columnas por 2 contenedores (`ht_master` y `ht_clone_top`), así que cada columna tiene un
  botón anunciado y su gemelo callado — el mismo defecto que el comentario de esa función venía a
  cerrar, reaparecido por el otro lado. Medido en vivo el 2026-08-24; anotado en
  `public/js/modules/programa_general/hot.js:2411`. Con `navigableHeaders: true` el camino de
  teclado NO pasa por esos botones, así que marcarlos los 24 es lo coherente.

- [ ] **DS · faltan tokens de relleno para los estados** — los dos anillos de BI dejaron de usar
  tinta de estado (`status-*`) como color de relleno (2026-08-24, commit `880e9d4a`) y ahora pintan
  con colores de dato (`critical`, `brand-construction`, `brand-primary`). Pero el problema de fondo
  sigue: el design system no ofrece un color de estado pensado para rellenar área, solo la mitad
  `-text` pensada para tinta. **Dirigido a DS-F1**, que es el frente dueño de tokens y de la escala
  de severidad — quien decida la escala decide esto.
  **Decision heredada pendiente de ratificar:** al resolver el reemplazo, este mismo frente eligio
  que `brand-construction` es el color del nivel medio («Aceptable» / «Cumple Parcialmente», valor
  entre 70 y 90, en `semanticMetricRange()` y `schedulePerformanceRange()` de
  `src/Services/ControlTowerService.php`). Reutilizar un token de dato ya existente no vuelve neutral
  esa eleccion: mapear un color a un nivel de severidad es exactamente el tipo de decision que este
  frente dijo que le tocaba a DS-F1, no a si mismo. DS-F1 debe revisarla y ratificarla o deshacerla
  como parte del contrato de escala, no asumir que ya quedo resuelta.

- [ ] **BI · confirmar visualmente los dos anillos con avance mayor que cero** — el reemplazo de
  color de relleno (commit `880e9d4a`) no se vio a tamaño real: el único proyecto accesible del
  sandbox tiene 0 % de avance en ambas métricas y el arco queda invisible. Riesgo bajo — los tres
  tokens (`critical`, `brand-construction`, `brand-primary`) ya pintan área en el mismo tablero
  (barras de «Causas de no cumplimiento», curva de ejecución, pronóstico de fecha) — pero sin ver
  un arco de dona con esos colores. Confirmar en obra o con datos de un proyecto con avance real.

- [x] **CI · regenerar la baseline de presupuesto de runtime** — hecho el 2026-08-24, generación
  **0.4.0** (`docs/design-system/runtime-baseline-0.4.0.json`). El rojo no era una regresión: el
  baseline 0.3.5 se había medido **en la máquina local** (`ciRunId: run-local-01428901`) mientras
  el gate lo verifica en runners de GitHub Actions, e `initializationMs` agrupa por máquina antes
  que por código — local 191-268 ms, Actions 596-1.071. Los 596,5 del rojo son la mitad del único
  precedente medido en el mismo entorno. `jsGzipBytes` +9.548 B atribuidos al byte y sin residuo,
  de los cuales 1.132 B son ruido de zlib entre entornos en 42 archivos de sha256 idéntico. Desde
  0.4.0 la referencia se toma donde el gate la verifica. Atribución completa en
  [[docs/design-system/runtime-measurements/2026-08-24-atribucion-0.4.0]].

- [x] **DS · el guard de laboratorio exigía una excepción que el CSS retiró el 2026-08-20** —
  resuelto el 2026-08-24 alineando el test con el replanteo B, decisión de Felipe. La excepción
  crítica (`[hue][high][now]` conservando el fondo crítico) se había retirado del CSS en
  `b7d5dd18`: hoy el chip pinta sólido por familia y la gravedad vive en el filete
  (`severity-rail.css`), y `states-feedback.css:151-158` delega en `state-tint-ladder` el guard
  contra su reaparición. `design-system-lab.mjs` se había quedado en su versión del 2026-08-11
  (`82832685`). Al caer la excepción el nivel crítico entra en la regla general, así que el test
  comprueba **más** que antes: ahora también exige que dos estados críticos de matiz distinto se
  distingan. **Llevaba cuatro días en rojo sin que se viera** — es el paso 24 del job y el check de
  presupuesto, en el 23, lo dejaba `skipped`; mismo patrón que `general_flags`. Lo destapó la
  regeneración de la baseline a 0.4.0.

- [ ] **Tablas · retirar DataTables, el tercer motor** — quedan cinco superficies en
  DataTables 1.10.21 (2020, con jQuery detrás): `views/programacion-semanal/CIC|CNC|CNP.view.php`,
  `views/control-cambios/controlCambios.view.php` y las tablas del panel `admin/`. El destino es
  AG Grid, ya en uso en Plan de Compras. **Sin frente propio y sin fecha:** ninguna de esas
  pantallas duele hoy, así que la regla es «quien entre a una de ellas por otra razón, sale con
  AG Grid». Al hacerlo, llevarse también las cifras tabulares, que ese carril no las tiene
  (`font-variant-numeric: tabular-nums`, ya aplicado en Handsontable y en el PDC). Decisión de
  rumbo del 2026-08-24 en [[ROADMAP]].

- [ ] **Deploy · limpiar drift residual en producción** — stash `pre-deploy-20260820-185447`
  (SmtpMailer, ya superado por `21243c7e` versionado) y 7 `.bak` de `indicadores.view.php` del
  2026-07-23 en `public_html`. Confirmar y borrar.

- [ ] **CI · G4 path filters** — excluir de los triggers lo que ningún gate lee (`memoria/**`,
  `.md` de raíz); `docs/design-system/` es contractual y NO se excluye. Origen:
  [[docs/superpowers/specs/2026-08-20-deuda-ci-design]].
- [ ] **CI · G7 paralelización** — medir duración por paso primero; candidato: PHPStan como job
  paralelo (no necesita la app levantada). Origen: spec 2026-08-20-deuda-ci-design.
- [ ] **CI · G8 job summaries** — volcar recibos y presupuestos ya generados a
  `GITHUB_STEP_SUMMARY`. Origen: spec 2026-08-20-deuda-ci-design.
- [ ] **CI · zizmor** — auditoría de seguridad del YAML complementaria a actionlint; exige tooling
  extra. Origen: spec 2026-08-20-deuda-ci-design.
- [x] 2026-08-20 — **CI · Frente 2 (G2, cache de capas Docker)**: ejecutado en alcance A
  (cache buildx `type=gha` de la capa base, Dockerfile intacto). Medido en caliente: build del
  estático 81 s → 20 s (−75 %); runtime 93 s → 72 s (−23 %). Cierre en
  [[goals/deuda-ci-frente-2/goal]].
- [ ] **DECISIÓN (Felipe) · G6 branch protection / merge queue** — cambia el flujo de publicación
  de todas las sesiones (`publicar.sh` → PRs). No aplicar sin visto explícito. Origen: spec
  2026-08-20-deuda-ci-design.
- [ ] **PROPUESTA (Felipe) · hook `task-completed-verify.sh`** — corre `composer test` en el host,
  donde composer no existe (repo Docker-only): rojo falso en toda tarea sin código. Es `~/.claude`:
  proponer el fix, no aplicarlo.
- [ ] **Escribir el cierre de dos goals ya ejecutados** — `pdc-tanda2-plan-verdad` y
  `adopcion-logo-construccion` tienen el trabajo hecho y ninguna sección `## Cierre`, así que la
  regla de lectura los cuenta como abiertos. Es escribir el cierre, no re-ejecutar.
- [ ] **Enchufar `--estricto` a `npm run test:wiki`** — hoy el gate corre en estricto por línea de
  comandos, pero la decisión de hacerlo obligatorio es de contrato: a partir de ahí toda fuente
  nueva nace con frontmatter o el gate se pone rojo. El hueco ya se midió: una fuente entró sin
  declarar por un merge y el gate no lo detectó.
- [x] 2026-08-20 — **Plugins de Obsidian instalados y verificados en pantalla** (Dataview, Tasks,
  Kanban, Excalidraw, Homepage y el tema Minimal), publicado en `2888ab77`. El bloqueo original
  —«no se puede verificar sin abrir Obsidian»— se resolvió abriéndolo. **Iconize quedó fuera**: su
  autor lo declara descontinuado. **Kanban entró con advertencia**: funciona, pero busca quien lo
  mantenga. Hallazgo de paso: **el vault de `lps-aia` no estaba registrado** en la app —
  **corregido 2026-08-24: sí está registrado**, solo que Obsidian tenía activo otro vault
  ("Gerencia") con una carpeta que replica `proyectos/lps-aia/` sin ser el mismo vault — y
  `visor-gantt` sigue apuntando al disco Crucial X6 — roto desde la mudanza.
- [x] 2026-08-24 — **Grupos de color del grafo, configurados y verificados en pantalla**
  (`computer-use`, con acceso autorizado por Felipe). Tres `colorGroups` en `.obsidian/graph.json`:
  wiki (`path:memoria`, rojo), fuentes (`path:docs OR path:goals`, ámbar), contratos de raíz
  (`file:AGENTS OR file:CLAUDE OR ...`, verde). Verificado pintando en la Vista gráfica real, no
  solo escrito en el JSON. Presentado a Felipe como decisión de panel —3 grupos amplios, grano fino
  por `tipo`, o intermedio—: **ratificó los 3 grupos como definitivos**. Cierra el único pendiente
  que quedaba de la Fase 0b. Detalle en el `## Cierre` de
  [[docs/superpowers/specs/2026-08-18-wiki-v2-visual-design]].
- [ ] **Proponer verificación de tests en contenedor como config por proyecto.** La vía Docker se
  quitó del gate global de `~/.claude` el 2026-08-19; este repo es 100% dockerizado y su
  `verify.quick` en `.claude/gate.yaml` evita PHP/Docker por costo, pero el resto de la suite sí
  necesita el contenedor. Afecta config global, no solo este repo.
- [ ] **Fusionar contenido solapado de `AGENTS.md` / `GEMINI.md` / `CLAUDE.md`** con lo que ahora
  vive en [[README]] y [[ROADMAP]]. No se tocó su contenido en el bootstrap, solo se enlazó.
- [ ] **Plan espacio SiteGround** — tareas 1–5 de
  `docs/superpowers/plans/2026-08-18-espacio-cuenta-siteground.md`.
- [ ] **Dropdown PS sobre selector de semana** — diagnóstico del stacking en
  `/programacion-semanal`, con `systematic-debugging`.
- [ ] **Backlog Fase 7-10** (notificaciones por rol, QA sistemático, despliegue gradual, shared
  schema): sin frente abierto. Ver [[ROADMAP]].
- [ ] **Realces sin declarar** (r0 de Programa General y ruta crítica de Programación Semanal) como
  decisión única de producto — en la cola de [[docs/decisiones-pendientes]], sin prisa.

- [ ] **Rediseñar el proxy de la alarma de veracidad.** Hoy cuenta commits y **no sabe de qué habla
  la wiki**: pesa igual un commit en un área con quince páginas que uno en un área sin ninguna.
  Ahora sería afinable —las 13 áreas tienen mapa y las fuentes declaran su `areas`—, pero es
  cambiar el proxy entero, no recortarlo. Los tres descuentos del 2026-08-19 ya exprimieron el atajo.
- [ ] **Versionar el estado de coordinación.** `.claude/vistos/` está en `.gitignore:219` y
  `decisiones/gobierno-relato-de-autorizaciones.md` está sin commitear, así que ninguna sesión que
  trabaje en un worktree los ve. Precedente medido el 2026-08-11: un archivo de estado compartido
  sin versionar se llevó doce hallazgos sin diff y sin rastro.

## Lo que no está aquí a propósito

**El despliegue a producción** (CP-F-E, ~1.255 commits de retraso) no es una tarea de esta lista:
necesita autorización propia y explícita de Felipe, siempre, y publicar en `main` no la concede.

## Hechas (últimas 10)

- [x] 2026-09-03 — **Publicado `2670146b` (skill `datatables-to-handsontable` archivada) más
  `2bc9c6d0`** con `scripts/publicar.sh` del repo: frontmatter v2 a la skill y vocabulario cerrado
  en `decisiones/merge-carril-rojo-pr23.md`, que tenían `wiki (forma)` en rojo. El aviso de
  veracidad que quedaba (131 commits desde el 2026-08-25) se atendió el mismo día: **undécimo pase
  publicado en `00218633`**, 65 páginas, 16 corregidas y 1 derogada; detalle en [[memoria/log]].

- [x] 2026-08-28 — **Fase cero de temas y forma, CERRADA — las 11 tareas del plan, PR abierto
  contra `main`.** Ejecutada de corrido con `subagent-driven-development` sobre el goal
  [[goals/temas-y-forma-fase-cero/goal]], las dos specs hermanas
  [[docs/superpowers/specs/2026-08-28-temas-claro-oscuro-end-to-end-design|temas]] (D1-D24) y
  [[docs/superpowers/specs/2026-08-28-forma-bordes-radios-relieves-design|forma]] (F1-F40), y el
  plan único [[docs/superpowers/plans/2026-08-28-fase-cero-temas-y-forma]]. Detalle completo,
  rulings y hallazgos por tarea en el ledger
  (`.superpowers/sdd/2026-08-28-fase-cero-temas-y-forma/progress.md`); resumen de producto en
  [[CHANGELOG]]. Verificación de cierre: 600/600 tests de design-system, 8/8 suite estática, 29/29 +
  58/58 PHP `puro`; `check:frontend` no pasa pero es deuda preexistente ya presente en el commit
  base, no una regresión de este plan (verificado comparando biome sobre los mismos archivos:
  504 errores antes, 500 después).

  **Dos bloqueos de producto quedaron destapados y documentados, no resueltos aquí** (fuera del
  alcance de una fase cero de tokens): `theme.js` fuerza oscuro en 7 páginas reales y
  `laboratory-foundation.css` no rinde el tema claro en el laboratorio de diseño — ver §Bloqueantes
  arriba. D16 (CI corre ambos temas) cierra con alcance dark-only por esa misma causa, con la
  matriz `[light, dark]` corriendo de todos modos por decisión explícita de Felipe (costo de CI
  doblado sin contrapartida hasta que se resuelva).

  **Pendientes que este frente deja para la serie por módulo** (Programa General primero, luego
  Programación Intermedia → Semanal → PDC/SPA → resto → admin → Torre de Control): la edición del
  manual de marca AIA con la rampa de paleta propuesta (D20 — **pide visto de Felipe en su propia
  sesión, línea roja explícita del goal**), y la recogida del botón «volver a oscuro» de la nav al
  menú de usuario tras el primer mes de estreno (D13).

- [x] 2026-08-25 — **La spec `cierre-prelanzamiento-pdc` se midió condición por condición: estaba
  bien marcada, y el que se contradecía era el inventario.** Encargo de verificar una contradicción
  aparente (`estado: cerrado` sin sección de cierre, mientras `IMPLEMENTATION_PLAN_INVENTORY.md` la
  daba por parcial). Las seis condiciones de hecho, contra el código y con evidencia citada: **1, 2,
  3 y 5 cumplidas**; **la 4 cumplida en el fondo pero con otro instrumento** —se midió residuo del
  sandbox, no la brecha del motor, porque el trinquete estaba roto, y la bitácora ya lo declaraba
  sin adornos—; **la 6 no cumplida y así dicho**, que es justo lo que la §Riesgos de la spec exige.
  **El matiz que resuelve la aparente contradicción:** Felipe cerró el hueco del piloto vacío
  (`estado-olas.md:211`), lo que **retira el punto 6 del alcance en vez de darlo por satisfecho** —
  nadie fabricó evidencia. No queda trabajo vivo que la spec gobierne: H3 arreglado, H1 diferido con
  destino, H4 descartado con motivo y **H2 reparado después** (verificado hoy:
  `tests/test_pdc_v2_brecha_daporto.php:62` ya resuelve la versión en vez de fijar la 292 muerta).
  Escrito su `## Cierre` real, corregido el inventario a **53 ejecutadas · 2 parciales**.

- [x] 2026-08-25 — **`LpsApiController` ya valida CSRF en sus tres mutaciones.** Encargo de
  Felipe («arranca el hallazgo del CSRF»), directo tras el cierre de la biblia T4/T5. Mismo
  patrón que el precedente ya cerrado (`88ba6e0d`/`ca642189`): `legacy_require_csrf('lps_drawer')`
  en `addComment()`, `registerCrisis()`, `closeCrisis()`; token compartido emitido en las cuatro
  páginas anfitrionas del cajón contextual LPS; `lps_drawer.js` lo adjunta.
  **Trampa real destapada al verificar, no al codificar:** el `.env` enlazado por symlink (el
  patrón que `CLAUDE.md` manda para todo worktree nuevo) se rompe en cuanto el contenedor apunta
  al worktree en vez de a la raíz — el enlace resuelve en el host pero es ilegible desde dentro
  del contenedor. Ya estaba documentada en
  [[memoria/trampas/env-enlazado-se-rompe-dentro-del-contenedor]]; el remedio (copia temporal del
  `.env`, no enlace, mientras dure la verificación) se aplicó tal como la trampa lo prescribe.
  Verificado en ejecución: `tests/test_csrf_lps_api.php` 7/7 aserciones contra el contenedor real;
  `phpstan analyse src admin/src` sin errores; 22/22 en `e2e/tests/biblia/`. Las 12 fallas del
  nivel `datos-proyecto` del runner son de dominios ajenos (PDC, BI, human-decision) sobre datos
  ya mutados por otras sesiones del día — no relacionadas con este cambio.
  Ver [[docs/EXPERIMENTS.md]], fila `SOP-010`.

- [x] 2026-08-25 — **El estado real de los 127 planes y specs.** Encargo de Felipe en sesión
  propia. Los 127 verificados contra el código: **105 `cerrado` · 19 `vigente` · 3 `derogada`**,
  cada uno con su evidencia o su motivo escrito dentro del propio documento.
  **Corregido el mismo día, tras integrar el trabajo de la otra sesión: son 99 · 19 · 9.** Seis
  specs que se clasificaron `cerrado` llevaban en su `## Cierre` la palabra **DEROGADA** —
  `ui-audit-and-repair-plan`, `ui-audit-core-lps-ops`, `cierre-dark-mode`,
  `f2a-piloto-movil-programacion`, `reapertura-movil-y-tema-claro` y `programa-cierre-pendientes`.
  **Corregida la causa el mismo día:** no fue que el estado se dedujera de la presencia de la
  sección —**51 de las 57 `cerrado` no la tienen**, así que ese no era el criterio—, sino que el
  frente clasificó verificando el código («¿está hecho?») y para las seis la respuesta es *sí, pero
  por otra vía*. `cerrado` no puede expresar eso; `derogada` sí. La distinción no es cosmética:
  `cerrado` afirma que el trabajo se hizo, y en las seis **no se hizo** — cambió de vehículo a
  P3/P4, con cinco hallazgos trasladados a mano para que no se perdieran.
  **El hallazgo que cambió el frente:** la Tarea 0 validó la señal del goal en 5 casos y **falló
  uno** — `biblia-t5-lectura` tenía el goal cerrado como HECHO y su Task 3 entera sin ejecutar
  (`e2e/tests/biblia/lectura.spec.mjs` no existe), sin tomar siquiera la salida que el propio plan
  autorizaba. La señal se degradó a indicio sin ablandarla y los 33 que cubría pasaron al lote
  manual: de 92 a 127. El defecto resultó sistémico — **`biblia-t3`, `t4` y `t5` lo tienen los
  tres**. Contraste que quedó escrito: `control-tower-f0` también dejó una Task sin ejecutar y sí
  cierra, porque **dejó constancia escrita de por qué**. Lint 98/98 sin hallazgos.
  Ver [[docs/superpowers/plans/2026-08-25-estado-real-de-planes-y-specs]].

- [x] 2026-08-25 — **Depuración de `docs/`: el tipado decía lo que no era.** Encargo de Felipe
  («que la wiki quede sin ruido y refleje la realidad de hoy»), alcance `memoria/` + `docs/`.
  **72 documentos reclasificados** —de **90 `tipo: guia` a 18**, y las 18 sí son «cómo se hace
  algo»—, **13 specs y planes sellados `cerrado`** por tener `## Cierre` con contenido, y tres
  afirmaciones falsas corregidas en documentos que *mandan*: `docs/pdc-v2.md` (disco muerto,
  «el override monta `./` relativo» —monta ruta absoluta a la raíz— y «no hay PHPUnit») y
  `CLAUDE.md` (103/1 → **117/5** scripts y clases). `docs/qa/evidence/` sellado como historia,
  con el respaldo externo verificado íntegro (282 MB, 46 archivos).
  **Hallazgo que conviene no olvidar:** `memoria/` **no estaba sucia**. El lint sale verde, el
  pase de veracidad es del mismo día, y las 11 «citas rotas» que encontré resultaron ser todas
  menciones que *narran* algo eliminado — que es justo lo correcto. El ruido estaba entero en
  `docs/`, y su causa es que el frontmatter lo puso un backfill que dedujo el tipo desde la ruta.
  Verificado: `test:wiki` 98/98 sin hallazgos, `test:design-system:static` 8/8.

- [x] 2026-08-25 — **Las tres specs de móvil, tema claro y programa de cierre, DEROGADAS tras
  medirlas frente por frente** (`f2a-piloto-movil-programacion`, `reapertura-movil-y-tema-claro`,
  `programa-cierre-pendientes`). La hipótesis la había propuesto el propio asistente, así que la
  medición se encargó **con instrucción explícita de buscar lo que la refutara** — y encontró cinco
  hallazgos que solo vivían en esas specs, trasladados **antes** de derogarlas:
  **(1)** E5, el aviso al cruzar los 1180 px en caliente → P4/MO-F2b. Verificado: **nunca se
  implementó** y P4 no lo mencionaba.
  **(2)** El Plan de Compras está entre los 13 módulos de MO-F2b pero **no admite la receta del
  piloto** — es React + AG Grid sin código compartido — y la spec propia que se pedía no existe.
  **(3) y (4)** Los dos candados del tema claro: `theme-default.test.mjs` fija 22 declaraciones a
  mano y `linen-removal.test.mjs` compara cadena en vez de intención. **Con el tema claro puesto,
  los dos se ponen rojos por hacer bien las cosas.**
  **(5)** El backlog de `docs/EXPERIMENTS.md`: **~35 filas `abierto`, ~21 sin dueño**, y **ningún
  plan de P1 a P6 lo nombraba** → a P3/DS-F2 con triaje en tres grupos.
  **Lo que enseñó el traslado:** se comprobó la fila más grave —una guarda de autorización
  presuntamente abierta— y **estaba arreglada desde el 2026-08-10**. Una fila `abierto` no prueba
  que el defecto siga vivo, así que el triaje quedó escrito como «verificar antes de asignar
  dueño». Inventario: **52 ejecutadas · 3 parciales · 6 derogadas**.
- [x] 2026-08-24 — **`espacio-cuenta-siteground` revisada — no cierra, pero le falta solo el frente
  C.** Medido en el servidor: **el frente D ya estaba ejecutado** (los cuatro archivos fuera del
  webroot, cero dumps en el home, `2026_MASTER_FUSION.sql` movido a `~/backups/` y no borrado) y el
  **frente B verificado en vivo** — tars de **5,1–6,7 MB** contra los **687 MB** del último viejo,
  5 manifiestos, rotación exacta a 3 por sitio. Falta solo el clon shallow de `prueba-lps`, cuyo
  `.git` sigue en 366 MB. Frentes A y B en el repo ya estaban. Las cuatro pruebas PHP de la
  condición 2 dan RC=1 y **se probó que no es por este frente** (el fallo es de un catálogo en base
  de datos, cero menciones de `evidence`, y la carpeta que leen conserva sus 6 `.md` y 2 `.json`);
  límite declarado: se probó de qué **no** son, no de qué sí. **Hallazgo no buscado: el drift de
  producción ya no existe** — los siete `.bak` de `indicadores.view.php` no están y
  `git status --porcelain` del webroot sale vacío, lo que cierra de paso el pendiente de drift de la
  Tarea 2 de P5; quién los retiró no se determinó.
  **Error propio, corregido el mismo día:** una primera pasada afirmó —y publicó en `0a79d905`— que
  no había acceso SSH y que C y D eran imposibles. Falso: se grepearon los `Host` de `~/.ssh/config`
  sin resolver sus doce `Include`, y los alias viven en el archivo incluido. Lo desmintió Felipe en
  una línea. No se dañó nada: el error fue de lectura, no se tocó ningún servidor bajo esa premisa.
- [x] 2026-08-24 — **`cierre-dark-mode`, DEROGADA** — la sospecha de la pasada anterior, medida y
  confirmada: mismo motivo que las dos `ui-audit` (sustituida por DS-F0..F3). **Pero se deroga
  distinto: aquí sí se ejecutó trabajo real.** Medido hoy con `design-system-audit.mjs` (RC=0): la
  deuda pasó de un techo de **7 076 a 3 858 hallazgos vivos, −45 %**. Lo que se deroga es el
  remanente: fases 2, 5, 6 y 7 sin hacer — y la 5 **no pudo hacerse**, porque dependía de la 2 (la
  puerta de servicio de `admin/`, que no existe: cadena rota en su primer eslabón). La fase 6, que
  era el grueso, consistía en bajarle el techo a un gate que **DS-F3 declara que se reemplaza, no se
  arregla**. **Hallazgo cuantificado al cerrar:** entre la deuda real y el techo hay **3 218 de
  holgura** — se pueden añadir 3 218 violaciones nuevas y el gate sigue verde, que es el defecto que
  la fase 1 de esa misma spec existía para erradicar. Ya tiene dueño como `F0-030` de DS-F0, así que
  no abre frente.
- [x] 2026-08-24 — **Las dos specs de auditoría de UI, DEROGADAS** (no ejecutadas — el trabajo no se
  hizo por esa vía, se superó por otra). `ui-audit-and-repair-plan` (2026-07-31) y
  `ui-audit-core-lps-ops` (2026-08-01) se solapaban casi por completo y su veredicto ya estaba
  medido desde el 2026-08-20: «los dos planes viejos quedan superados como vehículo». Su inventario
  de 18+ superficies lo sustituye DS-F0 (68 hallazgos sobre 257 rutas); su plan de reparación,
  DS-F2 con dueño. Re-medido hoy sobre el árbol, no leído del informe: `/indicadores` es shell
  `aia-*` con iframe de Power BI — **las tarjetas KPI que ambas prometían refactorizar no existen en
  el repo** (F0-082); CNP/CNC/CIC siguen legacy real en `legacyCards.js` (0 clases `aia-*`, 10
  `ps-legacy-card`, intacto desde el veredicto), con F0-022 mayor y dueño en DS-F2. El error de
  fondo que las condenaba: proponían tocar vistas PHP y la deuda vive en un módulo JS que ninguna de
  sus fases nombra. **Estrena la casilla `derogada` del inventario**, que llevaba en cero.
- [x] 2026-08-24 — **`plan-cierre-hasta-produccion` cerrada.** Verificado sobre el código, no sobre
  el plan `2026-08-24-p5-cierre-hasta-produccion.md` que la daba por pendiente: CP-F-C (superficie
  obligatoria de estados) ya estaba ejecutada desde el 2026-08-12 (D-CEF-1) — esquema exige
  `surface`, 10 módulos la declaran, el gate comprueba la ruta real en `public/index.php`. CP-F-AB
  cerrada vía P2 el mismo día (9/9 gates `passed`). CP-F-D retirada desde el 2026-08-12 (ya estaba
  hecha, mejor de lo pedido). Solo queda CP-F-E (despliegue), sin ejecutar por diseño — necesita
  autorización explícita de Felipe, siempre. Corregido de paso el plan P5 para que no se repita el
  trabajo de CP-F-C. Detalle: [[docs/superpowers/specs/2026-08-11-plan-cierre-hasta-produccion-design]].
- [x] 2026-08-24 — **Las tres specs huérfanas de la auditoría del 20 de agosto, cerradas** (ninguna
  necesitó código, solo verificación y `## Cierre`). `stack-plan-de-compras`: la brecha era por qué
  el módulo se unificó en `lps-aia`, ya respondido en
  [[docs/superpowers/specs/2026-07-29-unificacion-repos-design]]. `vocabulario-estados-cascada`: el
  trabajo mecánico (35→29 en Intermedia) ya estaba en el código, y las cuatro decisiones D-VOC-1..4
  ya estaban resueltas desde el 11 de agosto en `docs/decisiones-pendientes.md` — el archivo
  `decisiones/vocabulario-estados-cascada.md` que sugería "en replanteo" era una copia del 18 de
  agosto nunca sincronizada con ese cierre; pendiente real fuera de este frente: separar `Capítulo`
  (D-VOC-4) en frente propio. `wiki-v2-visual`: los plugins de comunidad ya estaban instalados y
  verificados desde el 20 de agosto (`2888ab77`); el grafo con grupos de color, que quedó pendiente
  en el primer cierre de esta sesión por no poder verificarse sin abrir Obsidian, se resolvió en la
  misma sesión al confirmar Felipe que ya lo tenía abierto — ver entrada aparte. Inventario
  actualizado: 50 ejecutadas · 11 parciales.
- [x] 2026-08-24 — **Tarea 8 de P2 — `design-system.yml` renombrado a `ci.yml`.** Decisión de
  Felipe (2026-08-20), micro-frente propio. Barrido de referencias confirmado archivo por archivo
  (no solo grep): 3 tests que leían la ruta literal, `CLAUDE.md`, `DESIGN.md` y una trampa de
  memoria actualizados; ~25 docs históricos (`goals/`, planes/specs ya ejecutados, `decisiones/`,
  informes) se dejan intactos porque narran hechos de cuando el archivo tenía el nombre viejo.
  `actionlint` RC=0, suite estática 8/8, publicado en `3c670c5c`. Corrida real confirmada:
  [32791129071](https://github.com/jbenite7/lastplanneraia-construccion/actions/runs/32791129071)
  (`gh run list --workflow=ci.yml`), ambos jobs en `success`. Como se advirtió, partió el
  historial: `gh run list --workflow=design-system.yml` ya no devuelve nada. Detalle:
  [[docs/superpowers/plans/2026-08-24-p2-ci-en-verde-y-presupuestos]].
- [x] 2026-08-24 — **Los cuatro pendientes de `habilitacion-en-una-columna`, cerrados**: test de
  teclado del recorrido del globo (`pi-globo-recorrido.mjs`, ArrowUp/ArrowDown); tooltip «?»
  educativo repuesto en la cabecera de Habilitación (un solo trigger con las siete restricciones
  concatenadas, sin volver al mapa índice→prop que causó el hallazgo Important 1);
  `alerta-restricciones` de Programa General migrado a ámbar sólido sin forzarla al contrato de
  estados (es una insignia orthogonal, no un `Estado_PG` de fila — verificado contra 65.633 filas);
  `construirCuadrito` unificado en `readiness-box.js`, consumido por `hot.js` (IIFE) y
  `readiness-popover.js` (módulo ES) sin duplicar lógica. Verificado en pantalla y con los 8
  guardianes de navegador del frente + `test:design-system:static` 8/8 + PHP 52/52. Detectado de
  paso y anotado por separado (no es de este frente): el golden visual de Programa General está
  desactualizado — ver diferible arriba.
- [x] 2026-08-24 — **Habilitación en una columna — Programación Intermedia**: 15 commits, ejecutado
  con `subagent-driven-development`. Fusiona 7 columnas de restricción + `% Liberación` en una
  columna de cuadritos con globo de liberación (abrir/cerrar/foco/teclado/recorrido/guardado
  idéntico al de hoy), tabla cabe a 1100 sin scroll (antes 1490), leyenda de PI y PG con color
  sólido, tarjeta móvil comparte pieza con el globo. Revisión final encontró y corrigió 5 hallazgos
  (2 Critical) antes de publicar; goldens de `programacion-intermedia.visual.mjs` regenerados con
  aprobación visual de Felipe. Spec: [[docs/superpowers/specs/2026-08-20-habilitacion-en-una-columna-design]]
  · plan: [[docs/superpowers/plans/2026-08-21-habilitacion-en-una-columna]] · procedencia del golden:
  [[docs/design-system/manifests/programacion-intermedia.goldens]].
- [x] 2026-08-20 — **Los tres pendientes restantes de la auditoría de specs**: spec de severidad
  reescrita a 3 niveles, veredicto de indicadores/CNP-CNC-CIC (legacy real → DS-F2), humo anónimo
  de `prueba-lps` en verde. [[docs/superpowers/reports/2026-08-20-cierre-pendientes-auditoria]].
- [x] 2026-08-20 — **Auditoría de estado real de las 61 specs** contra el código, publicada en
  `3144ca5e`: 44 ejecutadas · 16 parciales · 1 pendiente · 12 cerradas. Informe con evidencia:
  [[docs/superpowers/reports/2026-08-20-auditoria-estado-specs]].
- [x] 2026-08-20 — **organizar-la-casa** ejecutada: coordinación versionada y las siete reglas
  escritas ([[docs/coordinacion-sesiones]]).
- [x] 2026-08-19 — **El gate de publicación frena las integraciones**: publicar con merges exige
  `--con-merges` y lista qué frentes entran y cuáles declaran cierre. Nace del cuarto choque del
  día: dos frentes a medio terminar entraron en `main` y solo los detectó una revisión a mano.
  **No bloquea por «goal.md sin cierre» a secas** —un frente activo commitea su goal mientras
  trabaja, y eso serían falsos positivos a diario— así que frena por el hecho comprobable y enseña
  el estado para decidir con datos.

- [x] 2026-08-19 — **Los nueve goals en plantilla, cerrados** (`697978ec`): los nueve habían
  corrido; ocho reciben objetivo y cierre con evidencia re-medida hoy, y `a187ccda` se borra por
  ser un id de sesión, no un frente. De ahí sale por qué `runtime-budgets` sigue `blocked`.

- [x] 2026-08-19 — **DS-F0 cerrada y publicada** (`567e566e`): `docs/design-system/auditoria/` con
  68 hallazgos clasificados sobre un censo de 257 rutas, sin tocar código de producto.
- [x] 2026-08-20 — `replanteo-coloreado-estados` cerrado: el chip solido pasa a portar la identidad
  (paleta auditada WCAG AA + manual AIA), el filete queda homogeneo en los tres modulos con el
  marcador `ready`, y **nada se recorta en silencio** — tres olas en workflows dependientes
  erradicaron elipsis, `overflow-x: hidden` irreversible, palabras partidas y ~20 tamanos fuera de
  rampa. Efecto no previsto: Intermedia muestra sus 9 filas donde antes cabian 5. Censo de las 22
  tablas de la app en `goals/replanteo-coloreado-estados/censo-tablas.md`.
  **Pendientes que dejo, cada uno frente propio:** cabeceras de grilla desalineadas (PI 0.75rem vs
  PS 0.72rem, decision de producto); `overflow-wrap: anywhere` en el chip de PI; `1.75rem` del boton
  de cierre de modal en PS; siete `console.log('[PI-DEBUG]')` tras flag; y el resto del censo
  (Admin y vistas HTML) fuera de estas olas por alcance.
- [x] 2026-08-20 — `ds-f1a-estados-severidad` cerrado: maquinaria adaptada al contrato de 3
  niveles (filete apagado en `Controlado` `1ff946f8`, PG remapeado con `Fuera de Ventana`
  `8418449a`), publicada, y verificada en pantalla post-fix a 1180×820 dark (sondas y capturas en
  `goals/ds-f1a-estados-severidad/evidence/`). Los pendientes que sobreviven son frentes propios
  (`r0` de PG, fantasmas de `/plan-compras`, `states-feedback.css:162`).
- [x] 2026-08-19 — **Fase 0b, wiki v2**: las seis tandas cerradas y publicadas, lint estricto verde.
- [x] 2026-08-19 — `ds-f1a-estado` (`4a152a54`): la escala de estado del contrato, medida contra
  50.966 actividades reales.
- [x] 2026-08-19 — `estados-fuera-de-ventana` (`aeaa7a77`): los dos calculadores producen
  `Fuera de Ventana` desde la séptima semana, y por primera vez tienen pruebas.
- [x] 2026-08-19 — `migracion-estados`: dry-run, respaldo probado restaurando 2.024 filas, y guarda
  que deniega el `--apply` con `RC=1`. Prepara, no aplica.
- [x] 2026-08-19 — `bug-coloreado-severidad` cerrado.
- [x] 2026-08-19 — Bootstrap de la wiki LLM de 5 archivos en la raíz.
- [x] 2026-08-18 — Fuente única de las 22 fases; lo verificado se archiva (`fc098810`).
- [x] 2026-08-18 — Los goals dejan de escaparse del control de versiones (`9711ae3f`): regla general
  al final del `.gitignore` en vez de lista blanca a mano.
- [x] 2026-08-18 — El correo sale por el MTA local del hosting, no por relay externo (`21243c7e`).

## El detalle, por bloques

**Esta página manda.** Es el único sitio donde se mira qué está pendiente y en qué orden. El
detalle de cada decisión sigue en `decisiones/<frente>.md` y en cada `goals/<slug>/goal.md`, pero
el **estado y la prioridad** se leen aquí y no se deducen de ningún otro lado.

Se actualiza al cerrar o reordenar, no se deja derivar. Nada de lo que hay aquí es contrato:
precedencia **código > `AGENTS.md` > `memoria/`**.

## Por qué existe esta consolidación

El proyecto tenía sus fases repartidas en cuatro planes que **numeran igual sin ser lo mismo**: hay
tres cosas distintas llamadas «F0» y dos llamadas «F1». Nadie podía responder «¿dónde quedó la
fase X?» sin abrir cuatro archivos y adivinar a cuál se refería. Consolidado el 2026-08-18.

Segundo hallazgo de esa consolidación, que vale por sí solo: **las casillas de los planes no miden
avance.** Medido el 2026-08-18 sobre los 17 planes de entonces: 435 casillas, **0 marcadas**,
incluidos planes cuyo trabajo estaba en producción. Es el mismo defecto que
`coordinating-agent-sessions` tiene medido en su propio plan.

**Re-medido el 2026-08-24, y el dato de arriba ya no es universal.** Hoy son **71 planes vivos con
2.127 casillas, de las que 162 sí están marcadas** — repartidas en solo 7 planes, todos del
2026-08-19 en adelante. La costumbre cambió a mitad de agosto, así que «0 marcadas» describe el
estado de 17 planes en una fecha, no una ley del repo: citarlo sin su alcance hace creer que nada
se ha hecho. Lo que **no** cambió es la conclusión operativa: solo **9 de los 71 planes** tienen su
sección `## Cierre` escrita, y hay **2 contradicciones activas** — cierre escrito con casillas sin
marcar, en `2026-08-04-cierre-dark-mode-campana-decisiones` (148, con nota explícita en su propio
cierre) y en `2026-08-24-p1-desague-y-consolidacion` (25). Para saber si algo está hecho se
verifica **contra el código y contra la sección de cierre**, nunca contra la casilla.

**Y no se marcan retroactivamente.** La regla la fijó el cierre de la campaña de dark mode el
2026-08-07, con su porqué escrito: reescribir casillas sin haber presenciado cada paso «sería
fabricar evidencia».

## Bloque 0 — Arranque (bloquea todo lo demás)

Orden del usuario, 2026-08-18: los frentes y chips no arrancan hasta cerrar estas dos.

| Fase | Qué es | Estado |
|---|---|---|
| **Fase 0** | Mudanza del repositorio a `~/Developer/lps-aia` | **HECHA** (2026-08-18). Copia verificada (fsck limpio, 2.7G), 6 worktrees reparados, montaje Docker actualizado, web 200, PHP 24/24. Respaldo en `/Volumes/Crucial X6/Developer/lps-aia.pre-mudanza-2026-08-18`; borrarlo es decisión aparte. La BD no se movió: vive en el volumen Docker `htdocs_db_data` |
| **Fase 0b** | Replanteo completo de la wiki: metodología Karpathy intacta, Obsidian visual, vault entero etiquetado y frontmatter en todas las fuentes (solo metadato; el cuerpo sigue intocable) | **HECHA** (2026-08-19), las seis tandas, publicadas. `wiki-lint.mjs --estricto` verde sobre 156 páginas y 414 de 417 fuentes. **Con dos salvedades declaradas:** los plugins de comunidad quedaron fuera por decisión del usuario, y los grupos de color del grafo quedaron pendientes por no poder verificarse sin abrir Obsidian |

Las seis tandas de la 0b, en `docs/superpowers/plans/2026-08-18-wiki-v2-visual.md` (~2 jornadas;
cada tanda cierra en verde antes de la siguiente):

| Tanda | Qué hizo | Cerrada en |
|---|---|---|
| **1 · Esquema y herramientas** | `wiki-operacion.md` a v2, lint v2, `wiki-frontmatter.mjs`, 5 moldes | `7208edf9` |
| **2 · Frontmatter a las fuentes** | 413 archivos por lotes, con revisión entre uno y otro. **Cero borrados**: solo se añadió metadato | `e5c540c3` |
| **3 · Retag fino** | `capa: wiki` en las 151 páginas, `generado` en 26, `trampa` en 4 | `26a8fe80` |
| **5 · MOCs completos** | 5 mapas nuevos; las 13 áreas tienen MOC. `moc` sale del vocabulario | `58240c2c` |
| **4 · Capa visual** | 13 vistas Bases, 3 canvas, dashboard, snippet de severidad. **Sin plugins** | `66012929` |
| **6 · Cierre** | Regeneración, línea `ingest`, esta tabla | esta tanda |

La 5 se cerró antes que la 4 porque el usuario reordenó: la 4 tocaba plugins de terceros y quedó
esperando su decisión.

**Lo que la Fase 0b deja pendiente**, para que no se pierda al marcarla hecha:

| Pendiente | Por qué quedó fuera |
|---|---|
| Plugins de comunidad (Dataview, Tasks, Kanban, Excalidraw, Iconize, Homepage, tema Minimal) | **Resuelto 2026-08-20** (instalados y verificados, `2888ab77`) |
| Grupos de color del grafo (`.obsidian/graph.json`) | **Resuelto 2026-08-24** (configurado y verificado en pantalla) |
| Enchufar `--estricto` a `npm run test:wiki` | Es decisión de contrato: a partir de ahí toda fuente nueva nace con frontmatter o el gate se pone rojo. **Ya se midió el hueco**: una fuente entró sin declarar por un merge y el gate no lo detuvo |
| 3 archivos del design system sin frontmatter | Están congelados por sha256 en `goal-provenance.json`. Ratificado por el usuario |
| 8 `goal.md` que son andamiajes sin objetivo escrito | Salen ahora en el catálogo con un resumen que lo dice. Hay que decidir cuáles siguen vivos |

## Bloque 1 — Programa Design System (cuatro fases)

Decisión del usuario del 2026-08-18, en [[programa-design-system-en-cuatro-fases]]: «el design
system no está bien definido, ni bien implementado, ni bien controlado». **Es el programa que
manda sobre los gates.**

| Fase | Qué es | Estado |
|---|---|---|
| **DS-F0 · Auditoría total** | Toda la app: módulo, objeto, variable y escenario. Absorbe como semilla las 48 decisiones del 3-ago y F-4…F-9 de `docs/DESIGN-AUDIT.md`. Entregable: inventario por severidad «Crítico → Sin problema», verificando de paso el bug de coloreado que el usuario sospecha | No empezada |
| **DS-F1 · Redefinición del contrato** | Tokens, primitivas `aia-*`, escalas de estado/severidad y escala de stacking (z-index). Arranca con brainstorming con el usuario: el contrato es decisión de negocio | No empezada |
| **DS-F2 · Reimplementación por adaptadores** | Primero Handsontable y DataTables, que concentran la deuda; luego módulo a módulo según DS-F0. **Entrada añadida el 2026-08-20:** CNP/CNC/CIC (`legacyCards.js` entero es legacy bajo shell `aia-*`, hallazgo F0-022 sin dueño hasta hoy) | No empezada |
| **DS-F3 · Control** | Gates nuevos derivados del contrato. **Los 15 actuales se reemplazan, no se arreglan.** Cinco principios: pocos y atados a contratos que duelan; nunca bloquean el flujo local, solo el merge; actualizar un baseline cuesta un comando con diff visible; todo rojo dice qué archivo y qué hacer; cuarentena explícita para gates ruidosos | No empezada |

Consecuencia de secuencia ya decidida: **la Torre de Control BI no se recaptura**, se reconstruye
con enfoque data storytelling sobre el contrato de DS-F1; hacerlo antes sería construirla dos veces.

## Bloque 2 — Cierre hasta producción (cinco fases)

`docs/superpowers/plans/2026-08-11-cierre-hasta-produccion.md`.

| Fase | Frente | Estado |
|---|---|---|
| **CP-F0 · Poner el CI en verde** | `ci-en-verde` | **Resuelta** como efecto de cerrar CP-F-AB — el CI no corría limpio de punta a punta y P2 lo arregló junto con los dos gates |
| **CP-F-AB · Cablear los dos gates al CI** | `gates-al-ci` | **CERRADA** (2026-08-24, vía Plan P2). Las dos decisiones ya estaban ejecutadas; faltaba el CI en verde |
| **CP-F-C · Cada módulo declara dónde pinta sus estados** | `superficie-de-estados` | **Corregido 2026-08-24: ya estaba ejecutada desde el 2026-08-12 (D-CEF-1)** — esta fila decía «Pendiente» y el plan P5 la reasignaba como tarea sin verificar contra `docs/decisiones-pendientes.md`. Verificado en código: esquema exige `surface`, 10 módulos la declaran, el gate comprueba la ruta real |
| **CP-F-D** | — | **RETIRADA** el 2026-08-12: su premisa estaba caducada, ya estaba hecha |
| **CP-F-E · Despliegue a producción** | `despliegue` | Pendiente. ~1.255 commits de retraso. **Necesita autorización propia y explícita, siempre** |

**Spec `plan-cierre-hasta-produccion` cerrada el 2026-08-24**: todo lo que es trabajo (F-AB, F-C,
F-D, CI en verde) está hecho; solo F-E sigue sin ejecutar, por diseño del propio plan. Detalle en el
`## Cierre` de [[docs/superpowers/specs/2026-08-11-plan-cierre-hasta-produccion-design]].

## Bloque 3 — Móvil, tablet y tema claro (siete fases)

`goals/reapertura-movil-y-tema-claro/goal.md`.

| Fase | Qué es | Estado |
|---|---|---|
| **MO-F1 · Destrabar** | `390x844` vuelve a ser soportado y no requerido | **CERRADA** (2026-08-07, DS-032) |
| **MO-F2a-1 · Precondiciones** | El gate valida los 15 manifiestos (miraba 4) y ata cada golden a su tema, viewport y contenido | **CERRADA** (2026-08-07) |
| **MO-F2a-2a · Deudas de arranque** | El golden mide exactamente su viewport salvo recorte declarado; los 17 manifiestos en `1.1.0` | **CERRADA** (2026-08-07, DS-033) |
| **MO-F2a-2b · Piloto móvil** | Handsontable deja de instanciarse bajo el umbral (0 nodos en 390×844); el sidebar pasa a menú flotante — era la causa raíz de que móvil fuera inusable: se comía 240 de 390 px y nunca colapsaba | **CERRADA** (2026-08-14) |
| **MO-F2b · Resto de módulos** | Los 13 restantes, con el coste ya medido en el piloto | Pendiente |
| **MO-F3 · Tema claro** | Paleta clara nueva y conmutador con preferencia guardada. Ojo: `linen` se retiró del producto el 2026-07-25 (DS-030), así que es **reconstruir, no reactivar**: paleta nueva, conmutador con preferencia guardada y revalidar todas las superficies | **Pendiente — arranca al cerrar MO-F2b.** Orden de Felipe (2026-08-20), revisando D-9: no queda estacionada, va **justo detrás de móvil**. Sigue **sin bloquear a `bi-control-tower-gemini`**, que cierra en dark por decisión propia (D-7) |
| **MO-F4 · Matriz diagonal** | Los gates adoptan la matriz de D6 y los candados se reinstalan | Pendiente — **absorbida por DS-F3**, ver «El solape de los gates» |

## El solape de los gates, y cómo se resuelve

Tres bloques empujaban la misma pieza: **DS-F3** dice que los 15 gates se reemplazan, **CP-F-AB**
está cableando dos de esos mismos gates, y **MO-F4** quiere cambiarles la matriz.

**Resolución (2026-08-18): manda DS-F3.** Los otros dos se subordinan:

- **MO-F4 se retira como fase propia** y entra como requisito dentro de DS-F3: la matriz de D6 es
  una entrada del contrato nuevo, no un trabajo aparte sobre los gates viejos.
- **CP-F-AB se recorta a lo mínimo que desbloquea el CI** y no se amplía. Cablear dos gates que
  DS-F3 va a reemplazar solo se justifica porque sin CI verde no hay forma de medir nada de DS-F0.
  Es andamio declarado, no inversión.

## Frentes en espera (no arrancan hasta cerrar el bloque 0)

- [[goals/contadores-cero/goal|contadores-cero]] — visto concedido; localizar rama, re-verificar, publicar.
- **Plan espacio SiteGround** — tareas 1–5 de `docs/superpowers/plans/2026-08-18-espacio-cuenta-siteground.md`.
- **Dropdown PS sobre selector de semana** — diagnóstico (`systematic-debugging`) del stacking en `/programacion-semanal`.
- **Higiene de coordinación** — sesiones zombi, `cas-log.*` de la raíz, triaje de goals.

## Habilitación en una columna (cerrado y publicado, 2026-08-24)

Plan `docs/superpowers/plans/2026-08-21-habilitacion-en-una-columna.md` (once tareas), desde la spec
v2 aprobada el 2026-08-21. Lanzado en sesión propia el 2026-08-21, publicado en `main` el 2026-08-24
(`c57455e5`), con sus cuatro pendientes cerrados el mismo día — ver «Hechas» arriba. Cubrió los dos
pendientes que quedaron vivos del frente de replanteo de coloreado:

- **Desborde de Programación Intermedia** — 17 columnas piden 1490 px en 1100. Lo cierra la Task 5,
  con un guardián que falla solo si alguien vuelve a ensanchar.
- **Contadores de leyenda del color equivocado** — consumen `--ds-state-tint-*` mientras los chips
  usan `--ds-state-solid-*`. Lo cierra la Task 1, que es independiente del resto.

Pendiente propio derivado: **Programación Semanal hereda la pieza en la ola siguiente**, con
Intermedia ya rodado una semana en obra. Comparte las mismas cinco restricciones duras
(`programacion_semanal/hot.js:570`), así que dejarla distinta indefinidamente reintroduce el
problema que el frente vino a corregir.

## Apuestas planificadas (tras lo anterior)

Torre de Control reconstruida con data storytelling (tras DS-F1 y el tema claro, que vuelve a la
secuencia detrás de móvil) · semana fija en
el resto de módulos con corte semanal · extensión de contadores-cero a todos los módulos · backlog
del 3-ago (48 decisiones; accesibilidad primero, absorbido por DS-F0).
