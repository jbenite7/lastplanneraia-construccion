---
capa: fuente
tipo: reporte
estado: vigente
fecha: 2026-08-19
areas: [datos]
tags: [pendiente]
fuente: decisiones/vistos/migracion-estados-7da358a7.md
resumen: Visto — frente migracion-estados (código, respaldo y dry-run; el apply queda EXCLUIDO)
---

# Visto — frente migracion-estados (código, respaldo y dry-run; el apply queda EXCLUIDO)

- **Fecha:** 2026-08-19 (emitido por la coordinadora vigente)
- **Sha con visto:** `7da358a7` (HEAD de `claude/bold-neumann-485f23`, origin/main integrado,
  re-verificado después de integrar, árbol limpio)
- **Verificación re-ejecutada por la coordinadora (contenedor efímero, sin ventana):**
  - `run-php-tests.php --nivel=puro` → RC=0 (24 sueltos + PHPUnit 2 clases, 45 tests).
  - `test_global_table_safety.php` → RC=0.
  - `npm run test:wiki` → RC=0, 161 páginas, modo estricto.
  - Diff contra origin/main: **cero archivos de producto** (src/, public/, views/, admin/) — el
    frente entrega migración, evidencia y documentos, no toca runtime.
  - Nivel `http`: corrido por el ejecutor con ventana coordinada — 7 rojos: 6 preexistentes
    conocidos + `test_bi_filters_apply_to_charts`, declarado ajeno con A/B real (checkout del
    padre, fallo idéntico sin el frente). La familia BI-frágil quedó encolada como tarea propia.
- **Desviaciones aceptadas:** clave del UPDATE cambiada a la PK real `(project_id, Consecutivo)`
  (la del plan habría escrito 704 filas donde esperaba una); heurística declarada en la medición
  de las 296, sostenida por la señal directa del avance.
- **La base quedó intacta:** cero diferencias contra el respaldo tras toda la sesión; la guarda
  del `--apply` deniega con RC=1 y sin escribir.
- **Autoriza:** publicar `7da358a7` en `main` con `bash scripts/publicar.sh`.
- **NO autoriza y no puede autorizar:** ejecutar el `--apply` del recálculo. Esa decisión es de
  Felipe sobre el informe del dry-run (40.664 cambios; las 24, las 296 y las 31 con
  recomendación), y ni este visto ni un relato la sustituyen.
