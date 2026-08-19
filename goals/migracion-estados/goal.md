---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 
areas: [datos]
fuente: goals/migracion-estados/goal.md
resumen: Dejar la columna Estado de los 16 proyectos diciendo lo que los calculadores canónicos dicen: ocho estados, sin legacy y sin vacíos. Este frente prepara y…
---

<!-- cas:cita-textual — registro del frente: cita salidas y comandos tal como se midieron -->
# Frente: migracion-estados

## Fase del plan
Plan: docs/superpowers/plans/2026-08-19-migracion-estados.md
Fase: -
Sha verificado: ?
Presupuesto: ?

Frente **(B)** de la partición aprobada. El (A) —contrato y calculadores— cerró y se publicó en
`aeaa7a77`.

## Objetivo
Dejar la columna `Estado` de los 16 proyectos diciendo lo que los calculadores canónicos dicen:
ocho estados, sin legacy y sin vacíos. **Este frente prepara y ensaya la migración; no la aplica.**

## Condición de hecho
1. **Las 113 contradictorias, capturadas y archivadas ANTES de tocar nada**, con sus `unique_id`,
   en un archivo versionado. Después del recálculo ya no habría forma de saber cuáles eran.
2. Un script de migración con **dry-run por defecto** que informe, sin escribir: cuántas filas
   cambian, de qué estado a cuál, por proyecto, y cuántas quedan igual.
3. **Respaldo verificable** de la columna `Estado` y estrategia de restauración **probada**, no
   solo descrita.
4. Los gates obligatorios de `docs/global-tables-architecture.md` en verde **antes** del apply.
5. Un informe del dry-run con el **tratamiento propuesto para las 24 filas terminadas con fecha de
   inicio futura** — propuesto, **no ejecutado**.
6. **Cero filas modificadas por este frente.** El `--apply` no corre aquí.

Verificación: docker compose exec -T app php database/migrations/20260819_recalculo_estados.php

## Posture
- **El `--apply` NO se ejecuta en este frente.** Exige el sí explícito del usuario sobre el
  resultado del dry-run. **Ni el visto de la coordinadora ni una autorización relatada lo
  habilitan**: la regla de gobierno del 2026-08-19 cubre *publicar*, y excluye las migraciones por
  texto propio.
- **No tocar los calculadores** — son de (A), ya publicados.
- **No corregir las 113**: se capturan y se proponen tratamientos.
- **No borrar nada.** El recálculo reescribe una columna; no elimina filas.
- **Sin dependencias nuevas.**

## Leer primero
- `docs/global-tables-architecture.md` §Migracion Y Reconciliacion y §Gates Obligatorios.
- `goals/estados-fuera-de-ventana/diagnostico-113-contradictorias.md` — el riesgo heredado.
- `docs/design-system/ds-f1a-escala-estado.md` — los ocho estados canónicos.
- `database/migrations/20260630_backfill_global_tables_from_zleg.php` — el patrón de dry-run/apply.

## Archivos declarados
docs/superpowers/plans/*-migracion-estados-*,goals/migracion-estados/**,database/migrations/20260819_recalculo_estados.php,tests/test_recalculo_estados_dry_run.php,memoria/log.md

## Contención
**Es el frente de mayor riesgo de los cuatro.** Toca la columna `Estado` de `programa_consolidado`:
65 549 filas, 16 proyectos, leída por `LpsService`, dos controladores de API,
`ProgramChangeDetector`, `ReportProcessor` y `test_weekly_governance.php`.

El riesgo no es el script: es que **el apply es irreversible sin respaldo**. Por eso el respaldo se
prueba restaurando, no se declara.

Lo que se sabe que va a cambiar, medido sobre `aeaa7a77`: **26 946 filas** llevan hoy un estado
legacy o vacío (`No Requerida` 12 338, vacío 7 705, `En Liberación de Restricciones` 5 463,
`Debe Iniciar esta Semana y Restricciones Pendientes` 771, `Terminada Antes` 317, `A Tiempo` 229,
`Debe Iniciar esta Semana` 110, `Adelantada` 13). Y **13 664** más que hoy son `Actividad Futura`
pasarán a `Fuera de Ventana`. El número exacto lo dirá el dry-run: esto es la cota, no la medida.
