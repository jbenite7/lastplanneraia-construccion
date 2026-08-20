---
capa: fuente
tipo: reporte
estado: vigente
fecha: 2026-08-19
areas: [proceso]
tags: [pendiente]
fuente: decisiones/vistos/ds-f1a-estados-severidad-c766a338.md
resumen: Visto — frente ds-f1a-estados-severidad (tras la cirugía de separación)
---

# Visto — frente ds-f1a-estados-severidad (tras la cirugía de separación)

- **Fecha:** 2026-08-19 (emitido por la coordinadora vigente; reemplaza al de `cf3eaf43`, caduco
  por contenido — aquel sha incluía el remapeo de PG sobre vocabulario superado)
- **Sha con visto:** `c766a338` (HEAD de `claude/reverent-golick-aaf932`, origin/main integrado,
  árbol limpio, 24 commits; la retirada de PG va como commit de reversión encima, con rastro)
- **Cirugía verificada por la coordinadora, criterio de aceptación exacto:**
  - `git diff origin/main -- public/css/styles.css | grep -c 'pg-'` → **0** (esperado 0)
  - `… grep -c 'pi-'` → **26** (esperado 26)
  - Los cuatro archivos propios de PG: **cero diferencia** contra origin/main.
- **Verificación re-ejecutada tras la cirugía:** static 8/8 RC=0 · contrato piloto PG RC=0 ·
  wiki RC=0 (160 páginas, modo estricto). Carriles sin contenedor; la evidencia de navegador de
  PI es la de la ventana coordinada previa.
- **Autoriza:** publicar `c766a338` con `bash scripts/publicar.sh`. El ejecutor confirmará el push
  con Felipe directamente antes de ejecutarlo (su decisión de procedimiento, respetada: la orden de
  publicar la dio Felipe a la coordinadora y el ejecutor prefiere oírla de él).
- **No autoriza:** remapeo de PG (frente futuro contra los 13 estados declarados); Semanal completa
  (frente propio); peldaño r0 (decisión de Felipe).
