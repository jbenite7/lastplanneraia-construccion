---
capa: fuente
tipo: reporte
estado: vigente
fecha: 2026-08-19
areas: [proceso]
tags: [pendiente]
fuente: decisiones/vistos/ds-f1a-estado-4a152a54.md
resumen: Visto — frente ds-f1a-estado (reemplaza al de 5716c15d, caduco sin publicar)
---

# Visto — frente ds-f1a-estado (reemplaza al de 5716c15d, caduco sin publicar)

- **Fecha:** 2026-08-19 (emitido por la coordinadora vigente)
- **Sha con visto:** `4a152a54` (HEAD de `claude/bold-neumann-485f23`)
- **Motivo del reemplazo:** entre el visto de `5716c15d` y el push entró `70dd3946` a origin/main; el ejecutor integró y pidió visto nuevo en vez de publicar con el caduco — proceder correcto.
- **Verificación re-ejecutada por la coordinadora:**
  - `git log 5716c15d..HEAD` → solo el merge de `70dd3946` (wiki-veracidad); ningún archivo del frente cambió.
  - Árbol limpio, origin/main integrado, 0 entrantes.
  - `npm run test:design-system:static` → RC=0, 8/8 PASS.
  - `node --test ds-f1a-escala-estado` → 9 pass / 0 fail.
  - `npm run test:wiki` → sin hallazgos, 158 páginas.
- **Autoriza:** publicar `4a152a54` con `bash scripts/publicar.sh`, incluyendo reapuntar temporalmente el contenedor `app` al worktree (`LPS_CODE_ROOT`) y devolverlo a la raíz al terminar. La coordinadora congeló el uso del contenedor por otras sesiones durante la ventana.
- **No autoriza:** deploy a producción; la decisión «Fuera de Ventana»: etiqueta vs valor persistido sigue en la mesa del usuario.
