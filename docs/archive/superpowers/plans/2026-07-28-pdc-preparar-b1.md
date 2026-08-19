---
capa: fuente
tipo: plan
estado: cerrado
fecha: 2026-07-28
areas: [pdc]
tags: [archivo]
fuente: docs/archive/superpowers/plans/2026-07-28-pdc-preparar-b1.md
resumen: Que un recálculo del plan de fechas deje de destruir las filas de pdcplanpaso, y que el responsable de un paquete sea una referencia a un usuario del proyecto…
---

# PDC A4 → preparar B1: upsert de pasos y responsable como usuario — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Que un recálculo del plan de fechas deje de destruir las filas de `pdc_plan_paso`, y que el responsable de un paquete sea una referencia a un usuario del proyecto en vez de texto libre — los dos cambios antes de que la fase B1 (Seguimiento) escriba datos reales encima.

**Architecture:** Dos cambios independientes sobre la fase A4 (rama `pdc-a4-fechas`, aún no mergeada a `main`). El primero es local a `PlanFechasService::calcular()`: el par `DELETE`+7 `INSERT` pasa a `INSERT ... ON DUPLICATE KEY UPDATE` sobre la clave única `(project_id, paquete_id, orden)` más un barrido de sobrantes, de modo que la fila persiste y con ella cualquier columna que B1 le cuelgue. El segundo atraviesa las cuatro capas: DDL (`responsable` VARCHAR → `responsable_id INT NULL` con FK a `general_usuarios`), servicio (el plan devuelve id + nombre denormalizado + si el usuario sigue siendo elegible), API (contrato nuevo del POST y endpoint de usuarios elegibles) y SPA (celda de texto → `agSelectCellEditor`).

**Tech Stack:** PHP 8.2 sin framework (PDO vía `\Database`), MySQL 8 en Docker (puerto app 8091, db 3308), tests PHP autoejecutables (`PASS:`/`FAIL:`, exit 0/1), SPA React + TypeScript + AG Grid + Vitest en el repo hermano `/Volumes/Crucial X6/Developer/plan-de-compras`.

## Global Constraints

- Español en código, comentarios, mensajes de error y de commit. Identificadores de tablas/columnas en el estilo ya existente (`snake_case` español).
- Comandos PHP: `docker compose exec -T app php <ruta>` desde `/Volumes/Crucial X6/Developer/lps-aia-pdc`.
- Comandos MySQL: `docker compose exec -T db sh -lc 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" lastplanneraia_dev'`, con el `.sql` por stdin cuando aplique.
- DDL en `.sql` con guardias idempotentes por `information_schema`, siguiendo el modelo de `database/migrations/20260728_pdc_v2_plan_fechas.sql` (converger, no solo no fallar).
- Los tests PHP son autoejecutables: imprimen `PASS:`/`FAIL:` y salen con exit 0/1. No PHPUnit.
- TDD estricto: test primero, verlo fallar, implementar el mínimo, verlo pasar, commitear.
- **Prohibido dejar datos alterados en DAPORTO (`project_id = 73`).** Baseline verificado el 2026-07-28: `pdc_paquete_frente` 0, `pdc_plan_paquete` 0, `pdc_plan_paso` 0, `pdc_insumo_paquete` 395, versión activa id 292, `general_paquetes_contratacion` activos 231 (237 totales). Los tests PHP usan los proyectos 999903/999904; no tocar 73.
- `tests/test_pdc_v2_brecha_daporto.php` debe seguir reportando **7 diferencias**. Ninguna tarea toca el motor de paquetes; cualquier movimiento de ese número es una regresión y aborta el trabajo.
- La lógica testeable de la SPA vive en `src/lib/`, no en los componentes. Gates de la SPA: `npx vitest run` y `npm run build`.

## Decisiones cerradas en el grilleo (`goals/pdc-preparar-b1/interview-result.json`)

| Decisión | Valor |
|---|---|
| Borrado de pasos sobrantes | `DELETE ... WHERE orden >= count(PASOS)` tras los upserts |
| Columnas del `ON DUPLICATE KEY UPDATE` de pasos | Lista explícita de las 4 programadas + comentario de advertencia |
| Invariante del test | Los `id` de las filas de `pdc_plan_paso` no cambian tras recalcular |
| `amarrar()` que borra los pasos al invalidar | Se deja como está, documentado para B1 |
| Columna del responsable | `DROP responsable`, `ADD responsable_id INT NULL` |
| FK | `ON DELETE RESTRICT` contra `general_usuarios(id)` |
| Universo del selector | `project_members` del proyecto activo ∩ `general_usuarios.activo = 1` |
| Responsable que deja de ser elegible | Se conserva el id, se muestra marcado |
| Forma de la migración | Solo `.sql`, con guardia que aborta si hay `responsable` no vacío |
| Contrato del POST | Cambio limpio a `responsableId: number \| null` |
| Payload del GET | `responsableId` + `responsableNombre` denormalizado |
| Lista de usuarios | Endpoint nuevo `GET /plan-compras/api/plan/usuarios` |
| Editor de la celda | `agSelectCellEditor` + `SelectEditorModule`, con «— sin asignar —» |
| `calculado_por` / `asignado_por` | Fuera de alcance, siguen siendo texto |

## File Structure

**Repo `lps-aia-pdc`:**
- Modify: `src/Services/Pdc/PlanFechasService.php` — upsert de pasos (T1), `responsable_id` en `plan()` y método `usuariosElegibles()` (T3).
- Modify: `tests/test_pdc_v2_plan_fechas.php` — invariante de ids (T1), responsable como usuario (T3).
- Create: `database/migrations/20260728_pdc_v2_plan_responsable_usuario.sql` — DDL del cambio 2 (T2).
- Modify: `src/Controllers/Api/PlanComprasPlanController.php` — contrato nuevo del POST + endpoint de usuarios (T3).
- Modify: `public/index.php` — ruta del endpoint nuevo (T3).

**Repo `plan-de-compras`:**
- Modify: `src/lib/types.ts` — `FilaPlan.responsableId/responsableNombre/responsableElegible`, tipo `UsuarioProyecto` (T4).
- Modify: `src/lib/planFechas.ts` — `etiquetaResponsable()` y `opcionesResponsable()` sustituyen a `valorResponsableMostrado()` (T4).
- Modify: `src/lib/planFechas.test.ts` — tests de los helpers nuevos (T4).
- Modify: `src/pages/PlanFechas.tsx` — columna con `agSelectCellEditor`, carga de usuarios (T4).

---

### Task 1: El recálculo conserva las filas de `pdc_plan_paso`

**Files:**
- Modify: `src/Services/Pdc/PlanFechasService.php:554-592` (bloque transaccional de `calcular()`), `src/Services/Pdc/PlanFechasService.php:433-440` (comentario para B1 en `amarrar()`)
- Test: `tests/test_pdc_v2_plan_fechas.php`

**Interfaces:**
- Consumes: nada de tareas anteriores.
- Produces: `PlanFechasService::calcular(int $projectId, string $usuario): array` mantiene su firma y su retorno `['ok' => true, 'calculados' => int, 'sinDuracion' => int]`. Lo que cambia es el invariante: las filas de `pdc_plan_paso` con la misma `(project_id, paquete_id, orden)` conservan su `id` entre recálculos.

- [ ] **Step 1: Escribir el test que falla**

En `tests/test_pdc_v2_plan_fechas.php`, insertar este bloque **justo antes** del comentario `// --- desfases: el cronograma se movió y el plan quedó viejo ---` (hacia la línea 603). Reutiliza `$paqEstructura`, `$svc` y `$P`, ya definidos más arriba en el archivo, y `$paqEstructura` ya tiene plan calculado en ese punto.

```php
// --- B1: recalcular no debe destruir las filas de pdc_plan_paso ---
// B1 (Seguimiento) va a colgar `fecha_real` de estas filas. Mientras `calcular()` hiciera
// DELETE + INSERT, cada recálculo las borraba y creaba otras nuevas: el avance real se perdía sin
// aviso. Esa columna todavía no existe, así que aquí se prueba el invariante observable
// equivalente — si el `id` de la fila sobrevive, la fila es la misma y con ella cualquier columna
// que B1 le añada.
$idsPaso = static function () use ($db, $P, $paqEstructura): array {
    return $db->query(
        'SELECT orden, id FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ? ORDER BY orden',
        [$P, $paqEstructura],
    )->fetchAll(\PDO::FETCH_KEY_PAIR);
};

$idsAntes = $idsPaso();
$assert(count($idsAntes) === count(PlanFechasService::PASOS),
    'B1: el paquete de referencia tiene una fila por paso antes de recalcular. Hay ' . count($idsAntes));

$svc->calcular($P, 'test-a4');
$idsDespues = $idsPaso();

$assert($idsAntes === $idsDespues,
    'B1: recalcular conserva las MISMAS filas de pdc_plan_paso (mismos ids por orden), no las borra y recrea.');

// El upsert tiene que seguir actualizando las fechas programadas: conservar la fila no puede
// significar dejarla congelada. Se mueve el frente, se recalcula y se comprueba que las fechas
// cambiaron mientras los ids siguen siendo los mismos.
$db->query('UPDATE programa_consolidado SET Fecha_Inicio = "2026-10-15" WHERE project_id = ? AND unique_id = 9001 AND Semana = 2', [$P]);
$svc->amarrar($P, $paqEstructura, 9001, 'test-a4');   // reamarre al mismo frente movido: invalida el plan viejo
$svc->calcular($P, 'test-a4');
$finDespues = $db->query(
    'SELECT fecha_fin FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ? AND orden = ?',
    [$P, $paqEstructura, count(PlanFechasService::PASOS) - 1],
)->fetchColumn();
$assert($finDespues === '2026-10-15',
    'B1: el upsert sigue reescribiendo las fechas programadas — el último paso termina en la fecha nueva del frente. Dio ' . var_export($finDespues, true));

// Los pasos sobrantes se retiran si el proceso se acortara. No se puede cambiar la constante PASOS
// desde el test, así que se simula el residuo insertando a mano una fila con un `orden` por encima
// del último válido y comprobando que el siguiente recálculo la barre.
$db->query(
    'INSERT INTO pdc_plan_paso (project_id, paquete_id, orden, paso, dias, fecha_inicio, fecha_fin)
     VALUES (?, ?, ?, ?, ?, ?, ?)',
    [$P, $paqEstructura, count(PlanFechasService::PASOS), 'PASO FANTASMA', 5, '2026-01-01', '2026-01-06'],
);
$svc->calcular($P, 'test-a4');
$sobrantes = (int) $db->query(
    'SELECT COUNT(*) FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ? AND orden >= ?',
    [$P, $paqEstructura, count(PlanFechasService::PASOS)],
)->fetchColumn();
$assert($sobrantes === 0,
    'B1: un paso sobrante (orden por encima del último válido) se borra en el siguiente recálculo. Quedaron ' . $sobrantes);
```

- [ ] **Step 2: Correr el test y verlo fallar**

Run:
```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && docker compose exec -T app php tests/test_pdc_v2_plan_fechas.php
```
Expected: FAIL en `B1: recalcular conserva las MISMAS filas de pdc_plan_paso (mismos ids por orden), no las borra y recrea.` — el `DELETE` actual hace que MySQL asigne ids nuevos con el AUTO_INCREMENT. Exit code 1.

- [ ] **Step 3: Implementar el upsert**

En `src/Services/Pdc/PlanFechasService.php`, sustituir el bloque que hoy va desde el comentario `// Los pasos se reemplazan enteros: recalcular no debe acumular.` hasta el cierre del `foreach (self::PASOS as $i => $p)` (líneas 577-587) por:

```php
                // Upsert, no DELETE + INSERT: B1 (Seguimiento) va a colgar la fecha REAL de cada
                // paso de estas mismas filas, y borrarlas en cada recálculo se llevaría por delante
                // el avance ya registrado sin ningún aviso. La clave única
                // (project_id, paquete_id, orden) hace que cada paso caiga siempre en su misma fila.
                //
                // El ON DUPLICATE KEY UPDATE lista SOLO las cuatro columnas programadas: lo que no
                // se lista, MySQL lo conserva. Es la misma garantía que protege `responsable` en
                // pdc_plan_paquete, y es lo que hace que las columnas que añada B1 sobrevivan sin
                // volver a tocar este servicio. No añadir aquí ninguna columna de seguimiento.
                foreach (self::PASOS as $i => $p) {
                    $ini = $cursor;
                    $cursor = $cursor->modify(sprintf('+%d days', $dias[$i]));
                    $this->db->query(
                        'INSERT INTO pdc_plan_paso (project_id, paquete_id, orden, paso, dias, fecha_inicio, fecha_fin)
                         VALUES (?, ?, ?, ?, ?, ?, ?)
                         ON DUPLICATE KEY UPDATE paso = VALUES(paso), dias = VALUES(dias),
                            fecha_inicio = VALUES(fecha_inicio), fecha_fin = VALUES(fecha_fin)',
                        [$projectId, $paqueteId, $i, $p['paso'], $dias[$i], $ini->format('Y-m-d'), $cursor->format('Y-m-d')],
                    );
                }

                // Sobrantes: si el proceso se acortara (PASOS pasa de 7 a 5), las filas de los
                // órdenes que ya no existen quedarían huérfanas y `plan()` las seguiría devolviendo.
                // Los órdenes son el índice del foreach, contiguos desde 0, así que todo lo que esté
                // en el último válido o por encima sobra. Se borra DESPUÉS del upsert para no dejar
                // ni un instante al paquete sin sus pasos dentro de la transacción.
                $this->db->query(
                    'DELETE FROM pdc_plan_paso WHERE project_id = ? AND paquete_id = ? AND orden >= ?',
                    [$projectId, $paqueteId, count(self::PASOS)],
                );
```

- [ ] **Step 4: Documentar la tensión pendiente en `amarrar()`**

En `src/Services/Pdc/PlanFechasService.php`, dentro del bloque `if ($reamarreInvalida) {` (línea 433), añadir al final del comentario que ya está ahí, antes del `DELETE`:

```php
                // Pendiente para B1: este borrado es deliberado —el plan viejo se calculó contra otro
                // frente y ya no vale— pero cuando `pdc_plan_paso` lleve la fecha REAL de cada paso se
                // llevará también por delante avance que sí ocurrió: una propuesta ya recibida no deja
                // de haberse recibido porque la obra se reprogramara. Resolverlo bien exige decidir qué
                // significa el avance real contra un plan invalidado, y eso es diseño de B1, no de A4.
```

- [ ] **Step 5: Correr el test y verlo pasar**

Run:
```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && docker compose exec -T app php tests/test_pdc_v2_plan_fechas.php
```
Expected: todas las líneas `PASS:`, ninguna `FAIL:`, exit code 0. Verificar en particular las cuatro aserciones nuevas que empiezan por `B1:`.

- [ ] **Step 6: Verificar que la brecha DAPORTO no se movió**

Run:
```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && docker compose exec -T app php tests/test_pdc_v2_brecha_daporto.php
```
Expected: exit code 0 y el recuento sigue en **7 diferencias**.

- [ ] **Step 7: Commit**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && git add tests/test_pdc_v2_plan_fechas.php src/Services/Pdc/PlanFechasService.php && git commit -m "fix(pdc): recalcular el plan ya no destruye las filas de pdc_plan_paso

Preparación para B1: el par DELETE + 7 INSERT de calcular() borraba y recreaba
las filas en cada recálculo. En cuanto B1 escriba una fecha real ahí, ese avance
se perdería sin aviso. Pasa a upsert sobre la clave única (project_id,
paquete_id, orden), con el ON DUPLICATE KEY UPDATE limitado a las cuatro
columnas programadas para que lo que añada B1 sobreviva solo, y un barrido de
los órdenes sobrantes por si el proceso se acortara.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 2: Migración — `responsable` pasa a `responsable_id`

**Files:**
- Create: `database/migrations/20260728_pdc_v2_plan_responsable_usuario.sql`

**Interfaces:**
- Consumes: nada de tareas anteriores.
- Produces: `pdc_plan_paquete` ya no tiene la columna `responsable`; tiene `responsable_id INT NULL` con la constraint `fk_ppp_responsable` hacia `general_usuarios(id)` `ON DELETE RESTRICT`. `NULL` significa «sin asignar». La Task 3 depende de este esquema.

- [ ] **Step 1: Escribir la migración**

Crear `database/migrations/20260728_pdc_v2_plan_responsable_usuario.sql`:

```sql
-- 20260728_pdc_v2_plan_responsable_usuario.sql
-- PDC v2 / preparación de B1: el responsable del plan deja de ser texto libre.
--
-- `pdc_plan_paquete.responsable` era un VARCHAR(100) que se editaba como celda libre en la SPA. El
-- diseño aprobado decía que debía elegirse de los usuarios del proyecto, y B1 (Seguimiento) no puede
-- unir ni notificar contra una cadena escrita a mano: dos personas tecleando el mismo nombre con
-- distinta tilde fragmentan el dato sin que nada lo detecte. Se sustituye por `responsable_id INT
-- NULL` con FK a `general_usuarios`, donde NULL significa «sin asignar».
--
-- ON DELETE RESTRICT, igual que el resto de FK de PDC (ver fk_ppp_paquete en
-- 20260728_pdc_v2_plan_fechas.sql): en este sistema los usuarios se desactivan (`activo = 0`), no se
-- borran, así que RESTRICT no estorba a ningún flujo real y sí impide que un borrado directo en base
-- deje planes apuntando al vacío.
--
-- Guardia de datos, no solo idempotencia: al escribir este archivo `pdc_plan_paquete` está vacía en
-- todos los entornos conocidos (A4 no ha llegado a main), así que no hay ningún texto que traducir a
-- un id y un script de backfill sería código sin nada que migrar. Pero un `DROP COLUMN` a ciegas en
-- un entorno que sí tuviera datos los destruiría en silencio. Por eso, si aparece alguna fila con
-- `responsable` no vacío, el procedimiento ABORTA con un mensaje explícito en vez de adivinar a qué
-- usuario corresponde cada nombre: quien se encuentre ese error tiene que decidirlo a mano.
--
-- Las guardias por `information_schema` hacen que el archivo converja desde cualquier punto de
-- partida (columna vieja presente, columna nueva ya creada, FK ya puesta) y que una segunda
-- ejecución sea un no-op real.

DELIMITER $$

DROP PROCEDURE IF EXISTS pdc_v2_migra_responsable_usuario$$
CREATE PROCEDURE pdc_v2_migra_responsable_usuario()
BEGIN
  DECLARE v_con_texto INT DEFAULT 0;

  -- (1) Guardia de datos. El SELECT sobre `responsable` vive dentro del IF a propósito: MySQL
  -- resuelve los nombres de columna al ejecutar cada sentencia, no al crear el procedimiento, así
  -- que cuando la columna ya no existe este bloque no se ejecuta y no falla.
  IF EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pdc_plan_paquete'
      AND COLUMN_NAME = 'responsable'
  ) THEN
    SELECT COUNT(*) INTO v_con_texto FROM pdc_plan_paquete WHERE responsable <> '';
    IF v_con_texto > 0 THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT =
        'ABORTADO: pdc_plan_paquete.responsable tiene filas con texto. Traduce a mano cada nombre a un id de general_usuarios antes de aplicar esta migracion; borrar la columna perderia el dato.';
    END IF;

    ALTER TABLE `pdc_plan_paquete` DROP COLUMN `responsable`;
  END IF;

  -- (2) La columna nueva. NULL = sin asignar (el VARCHAR usaba '' para lo mismo, pero un id no tiene
  -- cadena vacía que valga: NULL es el único «todavía nadie» honesto).
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pdc_plan_paquete'
      AND COLUMN_NAME = 'responsable_id'
  ) THEN
    ALTER TABLE `pdc_plan_paquete`
      ADD COLUMN `responsable_id` INT NULL AFTER `duracion_provisional`;
  END IF;

  -- (3) La FK. InnoDB crea por su cuenta el índice que la constraint necesita, así que no se declara
  -- ninguna KEY aparte (sería redundante, como el idx_pps_proyecto_paquete que la migración de A4 ya
  -- se encarga de retirar).
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pdc_plan_paquete'
      AND CONSTRAINT_NAME = 'fk_ppp_responsable' AND CONSTRAINT_TYPE = 'FOREIGN KEY'
  ) THEN
    ALTER TABLE `pdc_plan_paquete`
      ADD CONSTRAINT `fk_ppp_responsable` FOREIGN KEY (`responsable_id`)
        REFERENCES `general_usuarios` (`id`) ON DELETE RESTRICT;
  END IF;
END$$

CALL pdc_v2_migra_responsable_usuario()$$
DROP PROCEDURE IF EXISTS pdc_v2_migra_responsable_usuario$$

DELIMITER ;
```

- [ ] **Step 2: Aplicar la migración**

Run:
```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && docker compose exec -T db sh -lc 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" lastplanneraia_dev' < database/migrations/20260728_pdc_v2_plan_responsable_usuario.sql
```
Expected: sin salida y exit code 0.

- [ ] **Step 3: Verificar el esquema resultante**

Run:
```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && docker compose exec -T db sh -lc 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" lastplanneraia_dev -e "SHOW CREATE TABLE pdc_plan_paquete\G"'
```
Expected: aparece `responsable_id int DEFAULT NULL` y `CONSTRAINT fk_ppp_responsable FOREIGN KEY (responsable_id) REFERENCES general_usuarios (id) ON DELETE RESTRICT`; **no** aparece ninguna columna `responsable`.

- [ ] **Step 4: Verificar que reejecutar es un no-op**

Run:
```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && docker compose exec -T db sh -lc 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" lastplanneraia_dev' < database/migrations/20260728_pdc_v2_plan_responsable_usuario.sql && docker compose exec -T db sh -lc 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" lastplanneraia_dev -e "SHOW CREATE TABLE pdc_plan_paquete\G"'
```
Expected: exit code 0 las dos veces y el esquema idéntico al del paso anterior (una sola `fk_ppp_responsable`, una sola `responsable_id`).

- [ ] **Step 5: Verificar que la guardia de datos dispara**

Comprobar que el `SIGNAL` no es decorativo: se recrea la situación peligrosa (columna vieja con texto) y se confirma que la migración se planta en vez de borrar.

Run:
```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && docker compose exec -T db sh -lc 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" lastplanneraia_dev -e "
  ALTER TABLE pdc_plan_paquete ADD COLUMN responsable VARCHAR(100) NOT NULL DEFAULT \"\";
  INSERT INTO pdc_plan_paquete (project_id, paquete_id, unique_id, fecha_ancla, fecha_arranque, dias_totales, duracion_provisional, responsable, calculado_por, updated_at)
    SELECT 999999, id, 1, \"2026-01-01\", \"2026-01-01\", 1, 0, \"Fulano de Tal\", \"guardia\", NOW() FROM general_paquetes_contratacion WHERE activo = 1 LIMIT 1;
"' && docker compose exec -T db sh -lc 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" lastplanneraia_dev' < database/migrations/20260728_pdc_v2_plan_responsable_usuario.sql; echo "exit=$?"
```
Expected: la última orden falla con `ERROR 1644 ... ABORTADO: pdc_plan_paquete.responsable tiene filas con texto...` y `exit=1`.

- [ ] **Step 6: Limpiar el ensayo de la guardia y dejar el esquema migrado**

Run:
```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && docker compose exec -T db sh -lc 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" lastplanneraia_dev -e "
  DELETE FROM pdc_plan_paquete WHERE project_id = 999999;
"' && docker compose exec -T db sh -lc 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" lastplanneraia_dev' < database/migrations/20260728_pdc_v2_plan_responsable_usuario.sql && docker compose exec -T db sh -lc 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" lastplanneraia_dev -e "
  SELECT COUNT(*) filas_999999 FROM pdc_plan_paquete WHERE project_id = 999999;
  SHOW COLUMNS FROM pdc_plan_paquete LIKE \"responsable%\";
"'
```
Expected: `filas_999999` = 0 y `SHOW COLUMNS` lista únicamente `responsable_id`.

- [ ] **Step 7: Verificar DAPORTO intacto**

Run:
```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && docker compose exec -T db sh -lc 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" lastplanneraia_dev -e "
SELECT (SELECT COUNT(*) FROM pdc_paquete_frente WHERE project_id=73) ppf,
       (SELECT COUNT(*) FROM pdc_plan_paquete WHERE project_id=73) ppp,
       (SELECT COUNT(*) FROM pdc_plan_paso WHERE project_id=73) pps,
       (SELECT COUNT(*) FROM pdc_insumo_paquete WHERE project_id=73) pip,
       (SELECT COUNT(*) FROM general_paquetes_contratacion WHERE activo=1) catalogo;
"'
```
Expected: `ppf=0 ppp=0 pps=0 pip=395 catalogo=231`.

- [ ] **Step 8: Commit**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && git add database/migrations/20260728_pdc_v2_plan_responsable_usuario.sql && git commit -m "feat(pdc): el responsable del plan pasa a referenciar un usuario

Preparación para B1: pdc_plan_paquete.responsable era texto libre, y B1 no puede
unir ni notificar contra una cadena escrita a mano. Se sustituye por
responsable_id INT NULL con FK a general_usuarios (ON DELETE RESTRICT, como el
resto de PDC). Sin filas que traducir en ningún entorno, no hace falta backfill;
lo que sí lleva el archivo es una guardia que aborta con mensaje explícito si
encuentra texto, en vez de destruirlo al soltar la columna.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 3: Servicio y API — responsable como usuario elegible

**Files:**
- Modify: `src/Services/Pdc/PlanFechasService.php` (`calcular()` líneas 556-575, `plan()` líneas 653-700, método nuevo `usuariosElegibles()`)
- Modify: `src/Controllers/Api/PlanComprasPlanController.php` (`responsable()` líneas 106-145, método nuevo `usuarios()`)
- Modify: `public/index.php:221-227`
- Test: `tests/test_pdc_v2_plan_fechas.php`

**Interfaces:**
- Consumes: el esquema de la Task 2 (`pdc_plan_paquete.responsable_id`).
- Produces:
  - `PlanFechasService::usuariosElegibles(int $projectId): array` → lista de `['id' => int, 'nombre' => string, 'cargo' => string]`, ordenada por nombre.
  - `PlanFechasService::plan(int $projectId): array` — cada fila cambia `'responsable' => string` por `'responsableId' => ?int`, `'responsableNombre' => string` (vacío si no hay) y `'responsableElegible' => bool`.
  - `POST /plan-compras/api/plan/responsable` recibe `{paqueteId: int, responsableId: int|null}`. Códigos de error: `PAQUETE_INVALIDO` (422), `PAQUETE_SIN_PLAN` (422), `RESPONSABLE_INVALIDO` (422).
  - `GET /plan-compras/api/plan/usuarios` → `{usuarios: [{id, nombre, cargo}]}`.

- [ ] **Step 1: Escribir los tests que fallan**

En `tests/test_pdc_v2_plan_fechas.php`, dos ediciones.

**(a)** En el closure `$limpiar` (líneas 23-40), añadir el borrado del usuario de prueba **antes** del `DELETE` de `general_paquetes_contratacion` (la FK `fk_ppp_responsable` obliga a que `pdc_plan_paquete` ya esté vacía, y ese `DELETE` va primero):

```php
    $db->query("DELETE FROM project_members WHERE user_id IN (SELECT id FROM general_usuarios WHERE usuario LIKE 'testa4%')");
    $db->query("DELETE FROM general_usuarios WHERE usuario LIKE 'testa4%'");
```

**(b)** Sustituir el bloque actual del responsable (líneas 571-574 y la aserción de la línea 599-600) por lo siguiente. El bloque de siembra va donde hoy está el comentario `// El «responsable» no lo pone ningún método público todavía...`:

```php
// El responsable ya no es texto libre: es un usuario del proyecto. Se siembran dos usuarios de
// prueba —uno miembro del proyecto y otro que no lo es— para cubrir de una vez la elegibilidad y el
// caso «asignado y luego fuera del proyecto», que la vista tiene que seguir mostrando marcado.
$db->query(
    "INSERT INTO general_usuarios (nombre, email, cargo, usuario, password, activo)
     VALUES ('Test A4 Responsable', 'testa4resp@aia.com.co', 'Residente de Compras', 'testa4resp', 'x', 1)",
);
$usuarioResp = (int) $db->lastInsertId();
$db->query(
    "INSERT INTO general_usuarios (nombre, email, cargo, usuario, password, activo)
     VALUES ('Test A4 Ajeno', 'testa4ajeno@aia.com.co', 'Externo', 'testa4ajeno', 'x', 1)",
);
$usuarioAjeno = (int) $db->lastInsertId();
$db->query('INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, ?)', [$P, $usuarioResp, 'U']);

$elegibles = $svc->usuariosElegibles($P);
$idsElegibles = array_map(static fn (array $u): int => $u['id'], $elegibles);
$assert(in_array($usuarioResp, $idsElegibles, true),
    'Responsable: un miembro activo del proyecto es elegible.');
$assert(!in_array($usuarioAjeno, $idsElegibles, true),
    'Responsable: un usuario activo que NO es miembro del proyecto no es elegible.');

$db->query('UPDATE pdc_plan_paquete SET responsable_id = ? WHERE project_id = ? AND paquete_id = ?', [$usuarioResp, $P, $paqEstructura]);
```

Y donde estaba la aserción vieja de `'Juan Pérez'`, tras el `$svc->calcular($P, 'test-a4');` y el `$porId4`:

```php
$assert(($porId4[$paqEstructura]['responsableId'] ?? null) === $usuarioResp,
    'Responsable: `responsable_id` sobrevive a un recálculo (lo conserva el ON DUPLICATE KEY UPDATE).');
$assert(($porId4[$paqEstructura]['responsableNombre'] ?? '') === 'Test A4 Responsable',
    'Responsable: el plan devuelve el nombre del usuario, no solo su id. Dio ' . ($porId4[$paqEstructura]['responsableNombre'] ?? 'null'));
$assert(($porId4[$paqEstructura]['responsableElegible'] ?? null) === true,
    'Responsable: un responsable que sigue siendo miembro activo se marca como elegible.');

// Deja de ser miembro del proyecto: el dato NO se borra, se marca. Quien mire el plan tiene que
// enterarse de que ese paquete se quedó sin doliente, no encontrarse la celda vacía sin explicación.
$db->query('DELETE FROM project_members WHERE project_id = ? AND user_id = ?', [$P, $usuarioResp]);
$planFuera = $svc->plan($P);
$filaFuera = null;
foreach ($planFuera as $f) { if ($f['paqueteId'] === $paqEstructura) { $filaFuera = $f; } }
$assert(($filaFuera['responsableId'] ?? null) === $usuarioResp,
    'Responsable: sacar al usuario del proyecto no borra la asignación.');
$assert(($filaFuera['responsableElegible'] ?? null) === false,
    'Responsable: un responsable que ya no es miembro del proyecto se marca como no elegible.');
$assert(($filaFuera['responsableNombre'] ?? '') === 'Test A4 Responsable',
    'Responsable: se sigue devolviendo su nombre aunque ya no sea elegible.');
$db->query('INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, ?)', [$P, $usuarioResp, 'U']);
```

- [ ] **Step 2: Correr el test y verlo fallar**

Run:
```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && docker compose exec -T app php tests/test_pdc_v2_plan_fechas.php
```
Expected: error fatal de PHP `Call to undefined method App\Services\Pdc\PlanFechasService::usuariosElegibles()`. Exit code distinto de 0.

- [ ] **Step 3: Implementar en el servicio**

En `src/Services/Pdc/PlanFechasService.php`:

**(a)** En `calcular()`, quitar `responsable` del `INSERT` de `pdc_plan_paquete`. Sustituir la consulta y su bloque de parámetros (líneas 556-575) por:

```php
                $this->db->query(
                    'INSERT INTO pdc_plan_paquete
                        (project_id, paquete_id, unique_id, fecha_ancla, fecha_arranque, dias_totales,
                         duracion_ref, duracion_provisional, calculado_por, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                     ON DUPLICATE KEY UPDATE unique_id = VALUES(unique_id), fecha_ancla = VALUES(fecha_ancla),
                        fecha_arranque = VALUES(fecha_arranque), dias_totales = VALUES(dias_totales),
                        duracion_ref = VALUES(duracion_ref), duracion_provisional = VALUES(duracion_provisional),
                        calculado_por = VALUES(calculado_por), updated_at = NOW()',
                    [
                        // `responsable_id` no aparece ni en el INSERT (la fila nueva nace en NULL, que
                        // es «sin asignar») ni en el ON DUPLICATE KEY UPDATE: lo que no se lista,
                        // MySQL lo conserva. Así un recálculo nunca pisa al responsable que alguien ya
                        // asignó. No añadirlo a esa cláusula sin querer perder esta garantía.
                        $projectId, $paqueteId, $a['uniqueId'], $a['fechaAncla'], $arranque, $total,
                        $paq['duracion_ref'], $provisional ? 1 : 0, $usuario,
                    ],
                );
```

**(b)** Añadir el método `usuariosElegibles()` justo antes de `plan()`:

```php
    /**
     * Usuarios que pueden ser responsables de un paquete en este proyecto: los miembros del proyecto
     * que siguen activos.
     *
     * El universo es `project_members`, no `general_usuarios` entero: el responsable de contratar un
     * paquete es alguien de este equipo de obra, y ofrecer los cientos de usuarios de la empresa
     * convierte un desplegable en un buscador y multiplica las asignaciones equivocadas.
     *
     * @return list<array{id: int, nombre: string, cargo: string}>
     */
    public function usuariosElegibles(int $projectId): array
    {
        $rows = $this->db->query(
            'SELECT u.id, u.nombre, u.cargo
             FROM project_members pm
             JOIN general_usuarios u ON u.id = pm.user_id
             WHERE pm.project_id = ? AND u.activo = 1
             ORDER BY u.nombre ASC',
            [$projectId],
        )->fetchAll(\PDO::FETCH_ASSOC);

        return array_map(static fn (array $r): array => [
            'id' => (int) $r['id'],
            'nombre' => (string) $r['nombre'],
            'cargo' => (string) $r['cargo'],
        ], $rows);
    }
```

**(c)** En `plan()`, cambiar el `SELECT` (líneas 656-665) para traer al usuario y su elegibilidad:

```php
        $rows = $this->db->query(
            "SELECT pp.paquete_id, pp.unique_id, pp.fecha_ancla, pp.fecha_arranque, pp.dias_totales,
                    pp.duracion_provisional, pp.responsable_id, p.nombre, p.tipo_negociacion,
                    p.modalidad_contratacion, f.frente_nombre,
                    u.nombre AS responsable_nombre,
                    (u.activo = 1 AND pm.user_id IS NOT NULL) AS responsable_elegible
             FROM pdc_plan_paquete pp
             JOIN general_paquetes_contratacion p ON p.id = pp.paquete_id
             JOIN pdc_paquete_frente f ON f.project_id = pp.project_id AND f.paquete_id = pp.paquete_id
             LEFT JOIN general_usuarios u ON u.id = pp.responsable_id
             LEFT JOIN project_members pm ON pm.project_id = pp.project_id AND pm.user_id = pp.responsable_id
             WHERE pp.project_id = ? AND p.activo = 1
               AND p.modalidad_contratacion IN (" . self::modalidadesConProcesoSql() . ')
             ORDER BY pp.fecha_arranque ASC',
            [$projectId],
        )->fetchAll(\PDO::FETCH_ASSOC);
```

**(d)** En el mismo `plan()`, sustituir la línea `'responsable' => (string) $r['responsable'],` (línea 696) por:

```php
                // El id se conserva aunque el usuario deje de ser elegible (salió del proyecto o lo
                // desactivaron): borrar la asignación en silencio dejaría el paquete sin doliente sin
                // que nadie se entere. `responsableElegible` es lo que permite a la vista marcarlo.
                'responsableId' => $r['responsable_id'] === null ? null : (int) $r['responsable_id'],
                'responsableNombre' => (string) ($r['responsable_nombre'] ?? ''),
                'responsableElegible' => (int) ($r['responsable_elegible'] ?? 0) === 1,
```

- [ ] **Step 4: Correr el test y verlo pasar**

Run:
```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && docker compose exec -T app php tests/test_pdc_v2_plan_fechas.php
```
Expected: todas `PASS:`, exit code 0. En particular las ocho aserciones que empiezan por `Responsable:`.

- [ ] **Step 5: Implementar el controlador**

En `src/Controllers/Api/PlanComprasPlanController.php`, sustituir el método `responsable()` entero (líneas 106-145) por:

```php
    /** GET /plan-compras/api/plan/usuarios */
    public function usuarios(): void
    {
        $projectId = $this->guardLectura();
        if ($projectId === null) {
            return;
        }
        $this->ok(['usuarios' => $this->service->usuariosElegibles($projectId)]);
    }

    /** POST /plan-compras/api/plan/responsable  {paqueteId, responsableId} */
    public function responsable(): void
    {
        $projectId = $this->guardEscritura();
        if ($projectId === null) {
            return;
        }
        $body = $this->body();
        $paqueteId = filter_var($body['paqueteId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($paqueteId === false || $paqueteId === null) {
            $this->fail('PAQUETE_INVALIDO', 'paqueteId inválido.', 422);
            return;
        }

        // `null` es un valor legítimo: quitar el responsable. Cualquier otra cosa que no sea un entero
        // positivo es un error del cliente, no un «sin asignar» implícito.
        $crudo = $body['responsableId'] ?? null;
        if ($crudo === null) {
            $responsableId = null;
        } else {
            $validado = filter_var($crudo, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($validado === false) {
                $this->fail('RESPONSABLE_INVALIDO', 'responsableId inválido.', 422);
                return;
            }
            // Se valida contra los elegibles, no contra `general_usuarios`: la FK garantiza que el
            // usuario existe, pero no que sea miembro activo de ESTE proyecto. Sin esta comprobación
            // un cliente podría asignar a cualquiera de los cientos de usuarios de la empresa.
            $elegible = false;
            foreach ($this->service->usuariosElegibles($projectId) as $u) {
                if ($u['id'] === $validado) {
                    $elegible = true;
                    break;
                }
            }
            if (!$elegible) {
                $this->fail('RESPONSABLE_INVALIDO', 'Ese usuario no es miembro activo del proyecto.', 422);
                return;
            }
            $responsableId = $validado;
        }

        // No usar rowCount() del UPDATE para decidir si la fila existe: este repo no activa
        // PDO::MYSQL_ATTR_FOUND_ROWS (ver Database.php), así que MySQL reporta filas MODIFICADAS,
        // no coincidentes. Guardar el mismo responsable dos veces seguidas (algo normal: abrir la
        // vista y guardar sin cambiar nada) da rowCount=0 aunque la fila exista, y el controlador
        // respondía por error PAQUETE_SIN_PLAN. Se confirma la existencia con un SELECT explícito.
        $existe = $this->db->query(
            'SELECT 1 FROM pdc_plan_paquete WHERE project_id = ? AND paquete_id = ?',
            [$projectId, (int) $paqueteId],
        )->fetchColumn();
        if ($existe === false) {
            $this->fail(
                'PAQUETE_SIN_PLAN',
                'Este paquete todavía no tiene plan de compras calculado. Calcula el plan antes de asignar responsable.',
                422
            );
            return;
        }

        $this->db->query(
            'UPDATE pdc_plan_paquete SET responsable_id = ? WHERE project_id = ? AND paquete_id = ?',
            [$responsableId, $projectId, (int) $paqueteId],
        );

        $this->ok(['ok' => true]);
    }
```

- [ ] **Step 6: Registrar la ruta**

En `public/index.php`, tras la línea 224 (`$router->get('/plan-compras/api/plan', ...)`), añadir:

```php
$router->get('/plan-compras/api/plan/usuarios', [\App\Controllers\Api\PlanComprasPlanController::class, 'usuarios']);
```

Ojo al orden: las rutas más específicas de `/plan-compras/api/plan/*` ya están declaradas antes que `/plan-compras/api/plan` en el archivo (líneas 221-224). Colocar la nueva junto a `frentes`/`sugerencias`/`desfases`, **antes** de la ruta desnuda `/plan-compras/api/plan`, para no depender de cómo resuelva el router los prefijos.

- [ ] **Step 7: Verificar el endpoint contra el stack**

Run:
```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && docker compose exec -T app php -r '
require "/var/www/html/vendor/autoload.php";
require "/var/www/html/src/Core/Database.php";
$s = new App\Services\Pdc\PlanFechasService(Database::getInstance());
$u = $s->usuariosElegibles(73);
echo count($u), " usuarios elegibles en DAPORTO\n";
echo json_encode(array_slice($u, 0, 3), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), "\n";
'
```
Expected: `17 usuarios elegibles en DAPORTO` (los miembros activos de `project_members` para el proyecto 73) y tres objetos con `id`, `nombre`, `cargo`.

- [ ] **Step 8: Confirmar que la brecha DAPORTO sigue en 7**

Run:
```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && docker compose exec -T app php tests/test_pdc_v2_brecha_daporto.php
```
Expected: exit code 0, 7 diferencias.

- [ ] **Step 9: Commit**

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && git add tests/test_pdc_v2_plan_fechas.php src/Services/Pdc/PlanFechasService.php src/Controllers/Api/PlanComprasPlanController.php public/index.php && git commit -m "feat(pdc): el plan expone el responsable como usuario del proyecto

El servicio devuelve responsableId, responsableNombre y responsableElegible (un
responsable que salió del proyecto se marca, nunca se borra en silencio), y
aporta usuariosElegibles() con los miembros activos. El POST de responsable pasa
a recibir responsableId y lo valida contra esos elegibles: la FK garantiza que
el usuario existe, no que sea de este proyecto. Endpoint nuevo
GET /plan-compras/api/plan/usuarios para poblar el selector.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 4: SPA — la celda «Responsable» pasa a selector

**Files:**
- Modify: `/Volumes/Crucial X6/Developer/plan-de-compras/src/lib/types.ts:340-350`
- Modify: `/Volumes/Crucial X6/Developer/plan-de-compras/src/lib/planFechas.ts:171-179`
- Modify: `/Volumes/Crucial X6/Developer/plan-de-compras/src/lib/planFechas.test.ts`
- Modify: `/Volumes/Crucial X6/Developer/plan-de-compras/src/pages/PlanFechas.tsx` (imports, estado, `onResponsable`, `cols`)

**Interfaces:**
- Consumes: el contrato de la Task 3 (`responsableId`/`responsableNombre`/`responsableElegible` en `GET /plan`, `POST /plan/responsable` con `responsableId`, `GET /plan/usuarios`).
- Produces: nada que consuman tareas posteriores.

- [ ] **Step 1: Escribir los tests que fallan**

En `/Volumes/Crucial X6/Developer/plan-de-compras/src/lib/planFechas.test.ts`, sustituir el bloque de tests de `valorResponsableMostrado` por:

```ts
describe('etiquetaResponsable', () => {
  const usuarios: UsuarioProyecto[] = [
    { id: 7, nombre: 'Ana Ríos', cargo: 'Residente' },
    { id: 9, nombre: 'Beto Cruz', cargo: 'Compras' },
  ]

  it('muestra vacío cuando no hay responsable', () => {
    const fila = { paqueteId: 1, responsableId: null, responsableNombre: '', responsableElegible: false }
    expect(etiquetaResponsable(fila, {}, usuarios)).toBe('')
  })

  it('muestra el nombre de un responsable elegible', () => {
    const fila = { paqueteId: 1, responsableId: 7, responsableNombre: 'Ana Ríos', responsableElegible: true }
    expect(etiquetaResponsable(fila, {}, usuarios)).toBe('Ana Ríos')
  })

  it('marca al responsable que ya no es miembro del proyecto', () => {
    const fila = { paqueteId: 1, responsableId: 7, responsableNombre: 'Ana Ríos', responsableElegible: false }
    expect(etiquetaResponsable(fila, {}, usuarios)).toBe('Ana Ríos (ya no está en el proyecto)')
  })

  it('un override pendiente manda sobre el dato ya mutado por AG Grid', () => {
    const fila = { paqueteId: 1, responsableId: 9, responsableNombre: 'Beto Cruz', responsableElegible: true }
    expect(etiquetaResponsable(fila, { 1: 7 }, usuarios)).toBe('Ana Ríos')
  })

  it('un override a «sin asignar» vacía la celda', () => {
    const fila = { paqueteId: 1, responsableId: 9, responsableNombre: 'Beto Cruz', responsableElegible: true }
    expect(etiquetaResponsable(fila, { 1: null }, usuarios)).toBe('')
  })

  it('un override hacia un usuario que no está en la lista cae al id crudo, no rompe', () => {
    const fila = { paqueteId: 1, responsableId: null, responsableNombre: '', responsableElegible: false }
    expect(etiquetaResponsable(fila, { 1: 99 }, usuarios)).toBe('Usuario 99')
  })
})

describe('opcionesResponsable', () => {
  const usuarios: UsuarioProyecto[] = [
    { id: 7, nombre: 'Ana Ríos', cargo: 'Residente' },
    { id: 9, nombre: 'Beto Cruz', cargo: 'Compras' },
  ]

  it('antepone «sin asignar» a los miembros del proyecto', () => {
    expect(opcionesResponsable(usuarios, null)).toEqual([null, 7, 9])
  })

  it('incluye al responsable actual aunque ya no sea elegible, para no perderlo al abrir el editor', () => {
    expect(opcionesResponsable(usuarios, 42)).toEqual([null, 42, 7, 9])
  })

  it('no duplica al responsable actual si sigue siendo elegible', () => {
    expect(opcionesResponsable(usuarios, 7)).toEqual([null, 7, 9])
  })
})
```

Añadir `UsuarioProyecto` al `import type` que el archivo ya hace desde `./types`, y `etiquetaResponsable`, `opcionesResponsable` al import desde `./planFechas` (retirando `valorResponsableMostrado`).

- [ ] **Step 2: Correr los tests y verlos fallar**

Run:
```bash
cd "/Volumes/Crucial X6/Developer/plan-de-compras" && npx vitest run
```
Expected: FAIL — `etiquetaResponsable is not a function` / no exportado, y error de tipos sobre `UsuarioProyecto`.

- [ ] **Step 3: Actualizar los tipos**

En `src/lib/types.ts`, sustituir la línea `responsable: string` dentro de `FilaPlan` por:

```ts
  responsableId: number | null
  responsableNombre: string
  // false cuando el responsable asignado ya no es miembro activo del proyecto. El dato no se borra:
  // se marca, para que quien mire el plan sepa que ese paquete se quedó sin doliente.
  responsableElegible: boolean
```

Y añadir, junto a los demás tipos del plan:

```ts
// Payload de GET /plan-compras/api/plan/usuarios: los miembros activos del proyecto.
export type UsuarioProyecto = {
  id: number
  nombre: string
  cargo: string
}
```

- [ ] **Step 4: Implementar los helpers**

En `src/lib/planFechas.ts`, sustituir `valorResponsableMostrado()` (líneas 171-179) por:

```ts
/**
 * Texto que debe verse en la celda «Responsable». AG Grid muta `data` in-place al confirmar la
 * edición (valueSetter por defecto), sin esperar el POST — por eso un fallo de guardado no alcanza a
 * evitar la mutación, solo puede corregirla después. `overrides` es esa corrección: si hay uno
 * pendiente (el POST falló y se fijó el valor anterior), manda sobre el dato ya mutado.
 *
 * Un responsable que dejó de ser miembro del proyecto se muestra marcado, no vacío: la asignación
 * sigue ahí y quien lea el plan tiene que enterarse de que ese paquete se quedó sin doliente.
 */
export function etiquetaResponsable(
  fila: Pick<FilaPlan, 'paqueteId' | 'responsableId' | 'responsableNombre' | 'responsableElegible'>,
  overrides: Record<number, number | null>,
  usuarios: UsuarioProyecto[],
): string {
  const override = overrides[fila.paqueteId]
  if (override !== undefined) {
    if (override === null) return ''
    // El override viene de una edición local, así que el nombre se busca en la lista de usuarios; si
    // no está (caso raro: la lista se recargó y ya no lo trae), se muestra el id antes que nada.
    return usuarios.find((u) => u.id === override)?.nombre ?? `Usuario ${override}`
  }
  if (fila.responsableId === null) return ''
  return fila.responsableElegible ? fila.responsableNombre : `${fila.responsableNombre} (ya no está en el proyecto)`
}

/**
 * Ids que ofrece el desplegable de la celda, en orden: `null` («— sin asignar —») primero, luego los
 * miembros del proyecto tal como los ordenó el servidor.
 *
 * `actual` se incluye aunque no esté entre los elegibles (el responsable salió del proyecto después
 * de que lo asignaran): sin él, abrir el editor mostraría la celda como si estuviera vacía y un
 * despliegue accidental borraría la asignación sin que nadie lo pidiera.
 */
export function opcionesResponsable(usuarios: UsuarioProyecto[], actual: number | null): (number | null)[] {
  const elegibles = usuarios.map((u) => u.id)
  const huerfano = actual !== null && !elegibles.includes(actual) ? [actual] : []
  return [null, ...huerfano, ...elegibles]
}
```

Añadir `UsuarioProyecto` al `import type ... from './types'` que ya existe al principio del archivo.

- [ ] **Step 5: Correr los tests y verlos pasar**

Run:
```bash
cd "/Volumes/Crucial X6/Developer/plan-de-compras" && npx vitest run
```
Expected: toda la suite en verde, incluidos los nueve tests nuevos.

- [ ] **Step 6: Cambiar la celda a selector en el componente**

En `src/pages/PlanFechas.tsx`, cuatro ediciones.

**(a)** Imports y registro de módulos — sustituir `TextEditorModule` por `SelectEditorModule` en el import de `ag-grid-community`, actualizar el comentario y el `registerModules`:

```tsx
// Registro selectivo de módulos (no AllCommunityModule); ValidationModule solo en dev — patrón del repo.
// SelectEditorModule: la columna Responsable se edita con agSelectCellEditor (un desplegable de los
// miembros del proyecto); sin este módulo AG Grid rechaza la edición en runtime (error #200).
ModuleRegistry.registerModules([
  ClientSideRowModelModule,
  CellStyleModule,
  RowStyleModule,
  SelectEditorModule,
  ...(import.meta.env.DEV ? [ValidationModule] : []),
])
```

Y en el import de helpers, cambiar `valorResponsableMostrado` por `etiquetaResponsable,` y `opcionesResponsable,`. En el `import type`, añadir `UsuarioProyecto`.

**(b)** Estado — sustituir la declaración de `responsableOverride` (línea 63) por, y añadir la lista de usuarios:

```tsx
  const [responsableOverride, setResponsableOverride] = useState<Record<number, number | null>>({})
  // Miembros activos del proyecto: pueblan el desplegable de la celda «Responsable».
  const [usuarios, setUsuarios] = useState<UsuarioProyecto[]>([])
```

**(c)** En `cargar()`, junto a las demás peticiones (tras la de `/plan/frentes`), añadir:

```tsx
    apiGet<{ usuarios: UsuarioProyecto[] }>('/plan-compras/api/plan/usuarios')
      .then((d) => setUsuarios(d.usuarios))
      .catch(() => setUsuarios([]))
```

**(d)** Sustituir `onResponsable` (líneas 113-128) y la columna «Responsable» de `cols` (líneas 220-227) por:

```tsx
  const onResponsable = async (paqueteId: number, responsableId: number | null, anterior: number | null) => {
    // AG Grid ya mutó `data` (valueSetter por defecto, corrió antes de este handler). Confiamos en esa
    // edición optimista retirando cualquier override que quedara de un intento previo; si este intento
    // también falla, más abajo se vuelve a fijar.
    setResponsableOverride((prev) => trasGuardarEdicion(prev, paqueteId, { ok: true }))
    try {
      await apiPost('/plan-compras/api/plan/responsable', { paqueteId, responsableId })
    } catch (e) {
      const mensaje = e instanceof PdcApiError && e.code === 'PAQUETE_SIN_PLAN'
        ? 'Este paquete todavía no tiene plan calculado; usa «Recalcular» antes de asignar responsable.'
        : e instanceof PdcApiError && e.code === 'RESPONSABLE_INVALIDO'
          ? 'Ese usuario ya no es miembro activo del proyecto.'
          : mensajeError(e)
      dispatch({ type: 'FALLO', mensaje })
      // El guardado no ocurrió: la celda no puede seguir mostrando lo que AG Grid ya escribió.
      setResponsableOverride((prev) => trasGuardarEdicion(prev, paqueteId, { ok: false, anterior }))
    }
  }
```

```tsx
    {
      headerName: 'Responsable', field: 'responsableId', flex: 1, minWidth: 200, editable: true,
      cellEditor: 'agSelectCellEditor',
      cellEditorParams: (p: { data?: FilaPlan }) => ({
        values: opcionesResponsable(usuarios, p.data?.responsableId ?? null),
      }),
      // El editor trabaja con ids y la celda muestra nombres: `refData` no sirve aquí porque las
      // claves tendrían que ser strings y `null` no es una de ellas.
      valueFormatter: (p: { value: number | null }) =>
        p.value === null || p.value === undefined
          ? '— sin asignar —'
          : usuarios.find((u) => u.id === p.value)?.nombre ?? `Usuario ${p.value}`,
      // Sin `valueGetter` a propósito: `field` solo ya da el valueSetter por defecto que muta
      // `data.responsableId` al confirmar la edición, y de esa mutación depende el patrón de override
      // optimista (ver trasGuardarEdicion). Un valueGetter sin su valueSetter dejaría la celda de
      // solo lectura de hecho: AG Grid no sabría dónde escribir y `onCellValueChanged` no dispararía.
      cellRenderer: (p: { data?: FilaPlan }) =>
        p.data ? etiquetaResponsable(p.data, responsableOverride, usuarios) : '',
      onCellValueChanged: (p) => {
        if (!p.data) return
        void onResponsable(p.data.paqueteId, p.newValue ?? null, p.oldValue ?? null)
      },
    },
```

Y actualizar las dependencias del `useMemo` de `cols` (línea 233) a `[responsableOverride, desfasePorPaquete, usuarios]`.

**(e)** En el handler de clic de fila (línea 270), cambiar el campo comprobado:

```tsx
            if (!e.data || e.colDef.field === 'responsableId') return
```

- [ ] **Step 7: Correr los gates de la SPA**

Run:
```bash
cd "/Volumes/Crucial X6/Developer/plan-de-compras" && npx vitest run && npm run build
```
Expected: suite en verde y build sin errores de TypeScript. Si `npm run build` se queja de tipos en `cellEditorParams`/`valueFormatter`, ajustar las firmas a los tipos de AG Grid (`ICellEditorParams`, `ValueFormatterParams`) importándolos de `ag-grid-community` — no silenciar con `any`.

- [ ] **Step 8: Commit**

```bash
cd "/Volumes/Crucial X6/Developer/plan-de-compras" && git add src/lib/types.ts src/lib/planFechas.ts src/lib/planFechas.test.ts src/pages/PlanFechas.tsx && git commit -m "feat(plan): el responsable se elige de los miembros del proyecto

La celda deja de ser texto libre y pasa a agSelectCellEditor con los usuarios que
devuelve GET /plan-compras/api/plan/usuarios. Un responsable que salió del
proyecto se sigue mostrando, marcado, y el desplegable lo conserva como opción
para que abrir el editor no borre la asignación por accidente.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 5: Verificación integrada

**Files:** ninguno (solo verificación).

**Interfaces:**
- Consumes: todo lo anterior.
- Produces: la evidencia que respalda declarar el trabajo terminado.

- [ ] **Step 1: Los dos gates PHP**

Run:
```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && docker compose exec -T app php tests/test_pdc_v2_plan_fechas.php; echo "plan_fechas exit=$?"; docker compose exec -T app php tests/test_pdc_v2_brecha_daporto.php; echo "brecha exit=$?"
```
Expected: `plan_fechas exit=0`, `brecha exit=0` y el informe de brecha sigue diciendo **7 diferencias**.

- [ ] **Step 2: Los dos gates de la SPA**

Run:
```bash
cd "/Volumes/Crucial X6/Developer/plan-de-compras" && npx vitest run && npm run build
```
Expected: ambos en verde.

- [ ] **Step 3: Verificar DAPORTO idéntico al baseline**

Run:
```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && docker compose exec -T db sh -lc 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" lastplanneraia_dev -e "
SELECT (SELECT COUNT(*) FROM pdc_paquete_frente WHERE project_id=73) ppf,
       (SELECT COUNT(*) FROM pdc_plan_paquete WHERE project_id=73) ppp,
       (SELECT COUNT(*) FROM pdc_plan_paso WHERE project_id=73) pps,
       (SELECT COUNT(*) FROM pdc_insumo_paquete WHERE project_id=73) pip,
       (SELECT id FROM pdc_presupuesto_versiones WHERE project_id=73 AND activa=1) version_activa,
       (SELECT COUNT(*) FROM general_paquetes_contratacion WHERE activo=1) catalogo;
"'
```
Expected exacto: `ppf=0 ppp=0 pps=0 pip=395 version_activa=292 catalogo=231`. Cualquier desviación detiene el cierre.

- [ ] **Step 4: Confirmar que no quedó residuo de los tests**

Run:
```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && docker compose exec -T db sh -lc 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" lastplanneraia_dev -e "
SELECT COUNT(*) usuarios_test FROM general_usuarios WHERE usuario LIKE \"testa4%\";
SELECT COUNT(*) miembros_test FROM project_members WHERE project_id IN (999903, 999904);
SELECT COUNT(*) planes_test FROM pdc_plan_paquete WHERE project_id IN (999903, 999904, 999999);
"'
```
Expected: los tres en 0 tras la última corrida (el closure `$limpiar` corre al principio del test; si el archivo acabó en verde, la corrida siguiente los limpia — comprobar que no quedan usuarios `testa4%` huérfanos ejecutando el test una vez más y volviendo a contar).

- [ ] **Step 5: Verificación visual en el navegador integrado**

Levantar/adjuntar el stack y abrir la vista del plan:

```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && docker compose ps
```

Luego, con las herramientas del navegador integrado: `preview_start` con `url: http://localhost:8091`, iniciar sesión, seleccionar un proyecto con plan calculado y navegar a la ruta del plan de compras. Comprobar en pantalla:
- La columna «Responsable» abre un desplegable, no un campo de texto.
- La primera opción es «— sin asignar —».
- Las opciones son los miembros del proyecto, no una lista de cientos.
- Elegir un usuario persiste tras recargar la página.

Nota sobre el entorno: la sesión del panel del navegador se cae a los ~60-90 s (limitación del panel, no de la app); si eso ocurre, volver a iniciar sesión y continuar en vez de tratarlo como un fallo de la aplicación.

- [ ] **Step 6: Revisar el árbol antes de cerrar**

Run:
```bash
cd "/Volumes/Crucial X6/Developer/lps-aia-pdc" && git status --short && git log --oneline -4 && cd "/Volumes/Crucial X6/Developer/plan-de-compras" && git status --short && git log --oneline -2
```
Expected: sin cambios sin commitear en ninguno de los dos repos (salvo el `.sha256` de evidencia que ya venía modificado de antes en `lps-aia-pdc`), y los tres commits de PHP más el de la SPA en su sitio.

---

## Riesgos y preguntas abiertas

- **`general_usuarios` es `utf8mb3`, `pdc_plan_paquete` es `utf8mb4`.** No afecta a la FK: la clave es `INT` y el juego de caracteres solo importa en columnas de texto. Si aun así el `ADD CONSTRAINT` fallara, el error de MySQL lo dirá explícitamente en el paso 2 de la Task 2 y hay que resolverlo antes de seguir, no rodearlo quitando la FK.
- **El test siembra en `general_usuarios` y `project_members`, tablas globales.** Se limpian por el patrón `usuario LIKE 'testa4%'` en el mismo closure `$limpiar` que ya usa `creado_por = 'test-a4'` para el catálogo. El orden importa: la FK `fk_ppp_responsable` obliga a borrar `pdc_plan_paquete` antes que los usuarios; el closure ya borra las tablas del plan primero.
- **La Task 3 rompe el contrato del POST sin capa de compatibilidad** (decisión del grilleo). Si en el momento de ejecutar el plan la SPA estuviera desplegada por separado del backend, habría una ventana con la celda rota. Hoy no es el caso: los dos repos van en la misma rama `pdc-a4-fechas` y A4 no ha llegado a producción.
- **`amarrar()` sigue borrando los pasos al invalidar un plan.** Decisión consciente del grilleo: queda documentado en el código como pendiente de B1.
