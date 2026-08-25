---
capa: fuente
tipo: plan
estado: cerrado
fecha: 2026-08-19
areas: [proceso]
fuente: docs/superpowers/plans/2026-08-19-apply-recalculo-estados.md
resumen: aplicar el recálculo de la columna Estado sobre los 16 proyectos de desarrollo, con la red que el frente anterior dejó montada, y dejarlo verificado o…
---

# Apply del recálculo de estados — plan de ejecución

> **SUB-SKILL REQUERIDA:** `superpowers:executing-plans`. Los pasos usan casillas (`- [ ]`).

**Goal:** aplicar el recálculo de la columna `Estado` sobre los 16 proyectos de **desarrollo**,
con la red que el frente anterior dejó montada, y dejarlo verificado o restaurado — nunca a medias.

**Architecture:** el script ya existe, publicado y con su dry-run medido. Este frente **quita la
guarda del `--apply`**, ejecuta, y compara el resultado real contra las transiciones que el dry-run
predijo. Cualquier diferencia no explicada revierte con el respaldo.

**El apply es un momento; el plan es todo lo que lo rodea.**

## La autorización

Felipe autorizó **«Sí, apply completo»**, con el informe delante y tres opciones sobre la mesa
—apply completo, apply excluyendo las 24 y las 296, o aplazar—. Citado en
`decisiones/estados-consolidado-coordinadora.md` §2b.

**Cubre solo la base de DESARROLLO.** Producción es deploy y va aparte, con su propia autorización.

> **Pendiente al escribir este plan:** la confirmación directa del usuario en el canal de esta
> sesión. La regla que este mismo frente escribió dice que ni el visto ni el relato habilitan una
> migración, y esa regla no se dobla porque incomode. **El plan se escribe; el paso 3 no se ejecuta
> sin ese sí.**

## Paso 0 de toda tarea que ejecute PHP

```bash
docker inspect $(docker compose ps -q app) \
  --format '{{range .Mounts}}{{if eq .Destination "/var/www/html"}}{{.Source}}{{end}}{{end}}'
```

Las tareas de este plan van por **efímero** (`docker compose run --rm --no-deps`) salvo que se
indique lo contrario: ninguna necesita Apache.

## Global Constraints

- **Solo la base de desarrollo.** Ni una acción sobre producción.
- **Ventana de base exclusiva** durante el apply: nadie escribe ni mide. La abre la coordinadora
  cuando este plan diga «listo para ejecutar».
- **Si la reconciliación no cuadra, se restaura.** No se investiga con los datos migrados.
- **La clave de escritura es `(project_id, Consecutivo)`**, la PK real.

---

### Task 1: Re-verificar el respaldo contra el estado ACTUAL

El respaldo se probó hace horas. **La base pudo moverse desde entonces** — de hecho se movió
durante el frente anterior, de 65 549 a 65 557 filas.

- [x] **Step 1: Paso 0 y comparación respaldo vs realidad**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php -r '
$pdo = new PDO("mysql:host=db;dbname=".getenv("DB_NAME").";charset=utf8mb4", getenv("DB_USER"), getenv("DB_PASS"));
$o = $pdo->query("SELECT COUNT(*) FROM programa_consolidado")->fetchColumn();
$r = $pdo->query("SELECT COUNT(*) FROM programa_consolidado_estado_respaldo_20260819")->fetchColumn();
$d = $pdo->query("SELECT COUNT(*) FROM programa_consolidado p JOIN programa_consolidado_estado_respaldo_20260819 b
   ON b.project_id=p.project_id AND b.Consecutivo=p.Consecutivo WHERE NOT (p.Estado <=> b.Estado)")->fetchColumn();
$h = $pdo->query("SELECT COUNT(*) FROM programa_consolidado p LEFT JOIN programa_consolidado_estado_respaldo_20260819 b
   ON b.project_id=p.project_id AND b.Consecutivo=p.Consecutivo WHERE b.Consecutivo IS NULL")->fetchColumn();
printf("origen=%s respaldo=%s difieren=%s sin_respaldo=%s\n", $o, $r, $d, $h);'
```

**Criterio de parada:** si `difieren` > 0 o `sin_respaldo` > 0, el respaldo **ya no cubre** la base
actual. **Parar, rehacer el respaldo, y volver a probar la restauración** antes de seguir.

- [x] **Step 2: Rehacer el respaldo si hizo falta, y anotar cuál se usa**

---

### Task 2: Re-correr el dry-run y comparar con el informe

- [x] **Step 1: Dry-run sobre el estado actual**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php database/migrations/20260819_recalculo_estados.php
```

- [x] **Step 2: Comparar contra el informe publicado**

Esperado: 40 664 cambios, 24 777 iguales, 116 sin semana activa, y las mismas transiciones.

**Criterio de parada:** si los totales se desvían más de lo que expliquen las filas nuevas de la
tabla, **parar y reportar**. El apply se autorizó sobre unos números concretos; si los números son
otros, la autorización es sobre otra cosa.

---

### Task 3: El apply

**No se ejecuta sin (a) el sí directo del usuario en el canal de esta sesión y (b) la ventana de
base exclusiva abierta por la coordinadora.**

- [x] **Step 1: Quitar la guarda del `--apply`**

En `database/migrations/20260819_recalculo_estados.php`, sustituir el bloque que deniega por la
llamada real, dejando **en el código** la cita de la autorización y su fecha.

- [x] **Step 2: Confirmar la ventana con la coordinadora**

- [x] **Step 3: Ejecutar**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php database/migrations/20260819_recalculo_estados.php --apply
```

Leyendo el código de salida **en su propia línea**.

---

### Task 4: Reconciliación, y restaurar si no cuadra

- [x] **Step 1: Contar las transiciones reales y compararlas con las del dry-run**

Las filas que ahora difieren del respaldo tienen que ser **exactamente** las 40 664 previstas, con
el mismo desglose por transición.

- [x] **Step 2: Los gates obligatorios**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php tests/test_global_table_safety.php
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php tests/test_global_table_reconciliation.php
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php scripts/run-php-tests.php --nivel=db
```

- [x] **Step 3: Si algo no cuadra, RESTAURAR**

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app php database/migrations/20260819_recalculo_estados.php --restaurar --apply
```

y reportar. **No se investiga con los datos migrados.**

- [x] **Step 4: Comprobar que las 113 y las 296 quedaron donde el informe decía**

Usando `113-contradictorias-capturadas.csv` y su `Consecutivo`.

---

### Task 5: Actualizar el contrato con la distribución nueva

- [x] **Step 1: Medir el reparto real tras la migración**

- [x] **Step 2: Actualizar `ds-f1a-escala-estado.{json,md}`**

Sustituir los porcentajes —que hoy llevan aviso de ser pre-migración— por los medidos, con su
fecha, y **retirar el aviso**. La prueba del contrato tiene que seguir en verde.

---

### Task 6: El acta

- [x] Escribir `goals/apply-recalculo-estados/acta-del-apply.md`: qué se ejecutó, cuándo, con qué
      autorización, los números antes y después, el resultado de la reconciliación y qué respaldo
      quedó disponible para restaurar.

## Cierre

- [x] Verificar con salida real · `git status` limpio · fetch · integrar · **re-verificar después**
      · pedir visto · publicar el sha visado · confirmar · anotar · **marcar las casillas**.
