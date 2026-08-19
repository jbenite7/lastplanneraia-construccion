---
capa: fuente
tipo: goal-doc
estado: abierto
fecha: 2026-08-19
areas: [datos]
fuente: goals/apply-recalculo-estados/goal.md
resumen: Andamiaje sin objetivo redactado — el frente que ejecutaria el apply del recalculo de Estado autorizado por Felipe el 2026-08-19; su goal.md sigue en plantilla.
---

<!-- cas:cita-textual — registro del frente: cita salidas y comandos tal como se midieron -->
# Frente: apply-recalculo-estados

## Fase del plan
Plan: -
Fase: -
Sha verificado: ?
Presupuesto: ?

## Objetivo
<!-- 1-3 frases -->

## Condición de hecho
<!-- qué comando, con qué salida, prueba que el frente terminó -->
Verificación: docker compose run --rm --no-deps app php tests/test_global_table_reconciliation.php

## Posture
<!-- restricciones como NEGACIONES de diseño: «no tocar X», «sin dependencias nuevas» -->

## Leer primero
<!-- rutas que se leen obligatoriamente antes de tocar código -->

## Archivos declarados
docs/superpowers/plans/*-apply-recalculo-*,goals/apply-recalculo-estados/**,database/migrations/20260819_recalculo_estados.php,docs/design-system/ds-f1a-escala-estado.*,memoria/log.md

## Contención
<!-- archivo · commits hoy · quién más lo declara -->

## Cadena de herramientas
<!-- ids del arsenal, máx 8, una línea de porqué cada uno -->
