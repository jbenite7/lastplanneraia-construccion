---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-09-28
fuente: docs/runbook-produccion-recalculo-y-linea-base-2026-09-28.md
resumen: Runbook para llevar a producción el recálculo de estados y, si hace falta, la siembra de línea base contractual; preparado, no ejecutado
---

# Runbook de producción: recálculo de estados y siembra de línea base

- **Escrito el:** 2026-09-28, por la sesión de software e infraestructura, con lecturas del repo y de
  las actas de desarrollo. **No se ejecutó nada y no se tocó producción.**
- **Estado:** preparado y **en espera**. La ventana es **después de terminar la migración React**
  (decisión de Felipe, 2026-09-28, en el chat; antes había dicho «esta semana» y lo cambió el mismo
  día). Falta la fecha, y las autorizaciones de la sección 1.
- **Qué NO es:** no es una autorización ni un «ya está aprobado». No hay fecha ni hora: **pendientes**.
  Mientras tanto producción sigue mostrando los estados con la regla vieja.
- **Antes de ejecutarlo, en la fecha que se fije:** repetir la sección 2 (precondiciones) y la 3
  (conteos), porque los datos y el código de producción habrán cambiado desde esta lectura, y
  rehacer el respaldo el mismo día (sección 4).

## 0. Qué se hace y en qué se apoya

| Pieza | Qué es | Evidencia de que funciona |
|---|---|---|
| Recálculo de estados | `database/migrations/20260819_recalculo_estados.php` reescribe `programa_consolidado.Estado` con los calculadores canónicos. | Apply en desarrollo el 2026-08-19: 40.664 filas migradas, reconciliación exacta. Acta: `goals/apply-recalculo-estados/acta-del-apply.md`. |
| Siembra de línea base | `database/migrations/20260819_sembrar_linea_base_contractual.sql` rellena `fechaInicioLineaBase` y `fechaFinLineaBase` donde faltan. | **En desarrollo no movió ninguna fila**: los 30 proyectos sin línea base tampoco tienen cronograma (`docs/superpowers/plans/2026-08-24-p1-desague-y-consolidacion.md`, tarea 5). |

**Consecuencia para esta ventana:** la siembra puede que en producción tampoco tenga nada que
hacer. Se mide primero (paso 3) y solo se ejecuta si el conteo lo justifica.

## 1. Autorizaciones que hacen falta, todas de Felipe

1. **`/visto-prod`**: sin él, la base de producción es de solo lectura. El `/visto` general no lo cubre.
2. **Autorización de despliegue de esta ventana**: publicar en `main` no la concede.
3. **Un «sí» explícito sobre el resultado del dry-run** del recálculo (paso 5). El propio script lo
   dice: ni un visto general ni una autorización relatada lo sustituyen. En desarrollo el «sí»
   se dio con el informe delante y tres opciones: completo, excluyendo lo dudoso, o aplazar.
4. **La ventana**: fecha, hora y quién avisa a los usuarios. Es una decisión de operación y de cliente.

## 2. Precondiciones, se comprueban antes de empezar

- [ ] El código desplegado en producción contiene `src/Legacy/estado_programa_general.php` y el
      script `20260819_recalculo_estados.php`. Si no, primero va un despliegue normal
      (`docs/siteground-deploy-routine.md`), con su respaldo.
- [ ] Las migraciones pendientes se aplican en el orden de la sección 5.1 de esa rutina, **antes**
      de que el código nuevo atienda tráfico.
- [ ] Nadie más escribe en la base durante la ventana. En desarrollo la coordinadora congeló al
      resto de sesiones; en producción esa función la cumple el aviso a los usuarios.
- [ ] Hay acceso a la base de producción por el canal de siempre (SSH al hosting). **Desde esta
      sesión no lo tengo ni debo tenerlo**: este runbook lo ejecuta quien abra `/visto-prod`.

## 3. Medir antes de tocar (solo lectura)

```sql
-- ¿Hay algo que sembrar? Proyectos con cronograma y sin línea base declarada.
SELECT COUNT(DISTINCT c.project_id) AS proyectos_a_sembrar
  FROM programa_consolidado c
  JOIN general_proyectos_procesos p ON p.Id = c.project_id
 WHERE c.Fecha_Inicio IS NOT NULL AND c.Fecha_Fin IS NOT NULL
   AND (p.fechaInicioLineaBase IS NULL OR p.fechaFinLineaBase IS NULL);

-- Tamaño de la tabla a recalcular, para dimensionar el respaldo.
SELECT COUNT(*) FROM programa_consolidado;
```

- Si `proyectos_a_sembrar = 0`: la siembra **no se ejecuta** y se anota «sin filas» en el acta.
- Si es mayor que 0: cada proyecto de la lista se revisa a mano antes, porque la fecha sembrada es
  «cuándo empezamos a registrar», no «qué se prometió en el contrato» (decisión de Felipe del
  2026-08-19, con esa advertencia delante).

## 4. Respaldo, y probarlo el mismo día

Es la lección del acta de desarrollo: **el respaldo probado horas antes ya no cubría la base**
(faltaban 8 filas nuevas). Por eso se rehace inmediatamente antes de aplicar.

1. Dump completo de la base (rutina de despliegue, sección 3.1), y comprobar que la última línea
   dice `Dump completed`.
2. Tabla de respaldo de la columna, creada con el propio script:

   ```bash
   php database/migrations/20260819_recalculo_estados.php --solo-respaldo          # informa
   php database/migrations/20260819_recalculo_estados.php --solo-respaldo --apply  # crea
   ```

3. **Probar la restauración sobre una copia**, estropeando filas a propósito (incluidas algunas a
   `NULL`) y restaurándolas con `--restaurar`. Criterio: `origen = respaldo`, `difieren = 0`,
   `sin_respaldo = 0`.
4. Si pasan minutos entre el paso 3 y el apply, **se repite el respaldo**.

## 5. Dry-run y decisión de Felipe

```bash
php database/migrations/20260819_recalculo_estados.php    # sin --apply: dry-run
```

Se entrega a Felipe el informe completo: filas que cambian, filas iguales, filas sin semana activa,
transiciones y cambios por proyecto. Las cifras de desarrollo (40.664 / 24.781 / 120) sirven de
**referencia de orden de magnitud, no de resultado esperado**: producción tiene otros datos.

Felipe elige con el informe delante: aplicar completo, aplicar excluyendo un subconjunto, o aplazar.
Sin «sí» explícito, aquí termina la ventana.

## 6. Apply

⚠️ **El script ya no tiene guarda: con `--apply` recalcula de inmediato**, contra la base que diga el
`.env` del servidor donde se lance. Comprobar el destino antes de ejecutar.

```bash
php database/migrations/20260819_recalculo_estados.php --apply
```

Se anota el código de salida en su propia línea (`RC=`), sin tubería.

## 7. Siembra de línea base (solo si el paso 3 dio más de 0)

Se ejecuta el `.sql` una sola vez. Es idempotente por construcción: el `WHERE` excluye a quien ya
tiene la línea declarada, así que reejecutarla no pisa nada. Se repite el conteo del paso 3 y debe
dar 0.

## 8. Reconciliación y humo

- **Reconciliación exacta:** las filas que difieren del respaldo deben igualar a las previstas por
  el dry-run. Si no coinciden, no se sigue: se restaura (sección 9).
- **Gates de datos:** `test_global_table_safety.php` y `test_global_table_reconciliation.php`, ambos con RC=0.
- **Humo funcional** del flujo afectado: Programa General y BI de un proyecto con cronograma,
  verificando que la fecha contractual no cambie al reprogramar.

## 9. Marcha atrás

| Qué falló | Qué se hace |
|---|---|
| Reconciliación no coincide | `php database/migrations/20260819_recalculo_estados.php --restaurar --apply`, y se verifica `diferencias_tras_restaurar = 0`. |
| Daño fuera de la columna `Estado` | Se restaura el dump completo de la sección 4, en una base aparte primero y comparando conteos exactos. |
| Siembra con una fecha equivocada | Se corrige a mano por proyecto: la siembra es write-once y no pisa lo que alguien corrigió. |

## 10. Qué se registra al terminar

Acta en `goals/apply-recalculo-estados/`, con lo mismo que la de desarrollo: qué se ejecutó y con
qué autorización textual, conteos antes y después, reconciliación, gates y transiciones. Se marca
cerrado el pendiente «apply-recalculo-estados en PRODUCCIÓN» de `TASKS.md`.

## Lo que no sé, dicho sin adornos

- Si la siembra ya se aplicó en producción por alguna otra vía: se ve con el conteo del paso 3.
- Qué versión del código corre hoy en producción: se ve en la precondición de la sección 2.
- Cuánto tarda el apply sobre los datos reales de producción: en desarrollo fueron 65.565 filas y
  no hay medición de tiempo de pared en el acta. Conviene medirlo en el dry-run.
