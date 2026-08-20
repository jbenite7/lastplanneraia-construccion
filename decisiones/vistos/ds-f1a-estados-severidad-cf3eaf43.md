---
capa: fuente
tipo: reporte
estado: vigente
fecha: 2026-08-19
areas: [proceso]
tags: [pendiente]
fuente: decisiones/vistos/ds-f1a-estados-severidad-cf3eaf43.md
resumen: Visto — frente ds-f1a-estados-severidad (adaptación al contrato)
---

# Visto — frente ds-f1a-estados-severidad (adaptación al contrato)

> **SUSPENDIDO (2026-08-19, misma jornada):** el ejecutor corrigió una premisa antes de publicar —
> «Programa General intacto» era falso: la rama contiene el remapeo completo de PG contra el
> vocabulario viejo de 7 estados, con dos goldens regenerados. La decisión de publicar todo,
> separar commits o esperar es de Felipe. Este visto no autoriza publicar hasta que decida.

- **Fecha:** 2026-08-19 (emitido por la coordinadora vigente)
- **Sha con visto:** `cf3eaf43` (HEAD de `claude/reverent-golick-aaf932`, origin/main integrado, árbol limpio)
- **Verificación re-ejecutada por la coordinadora en su worktree:**
  - `npm run test:design-system:static` → RC=0, 8/8.
  - `node tests/test_programa_general_sprint_contract.mjs` → RC=0.
  - `npm run test:wiki` → RC=0, 160 páginas, modo estricto.
  - Alcance de la medición: los tres carriles que no requieren contenedor (igual que el ejecutor
    declaró). La evidencia de navegador es la de su ventana coordinada, ya verificada entonces:
    urgent 6px / attention 4px / healthy y neutral `none`, captura en
    `goals/ds-f1a-estados-severidad/evidence/pi-filete-apagado-1180x820-dark.png`.
- **Contenido:** adaptación al contrato de tres niveles (barra solo en urgente/atención; controlado
  y nivel-null sin barra, distinguidos por matiz), arreglo del comentario CSS que tragaba el token
  urgent (`feafc211`), trampa `guard-de-texto-no-ve-el-parseo.md`, sonda `sonda-vars.mjs`, backfill
  de frontmatter v2 en los archivos del frente.
- **Autoriza:** publicar `cf3eaf43` con `bash scripts/publicar.sh` desde ese worktree, con ventana
  de contenedor coordinada si el gate la exige.
- **No autoriza:** el remapeo de Programa General (frente aparte, contra los 13 estados declarados
  del contrato); Programación Semanal (frente propio); devolver el peldaño `r0` (decisión de Felipe).
