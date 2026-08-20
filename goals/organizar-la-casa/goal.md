---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-08-20
areas: [proceso]
fuente: goals/organizar-la-casa/goal.md
resumen: "Ejecutar la spec organizar-la-casa: registros de coordinación versionados, vistos mudados a decisiones/vistos/, sesiones depuradas, siete reglas escritas y referenciadas desde AGENTS.md"
---

# organizar-la-casa

**Objetivo.** Ejecutar [[docs/superpowers/specs/2026-08-19-organizar-la-casa-design]] (aprobada
por Felipe el 2026-08-19; la spec es el plan): la coordinación entre sesiones deja de vivir en
memoria de chat y archivos locales invisibles desde los worktrees.

**Condición de hecho.**
1. Los 7 vistos de `.claude/vistos/` viven en `decisiones/vistos/` con frontmatter v2 y su texto
   intacto; `.claude/vistos/` queda vacío.
2. Las filas `terminada` de `.claude/sesiones.md` viven en `decisiones/sesiones-historial.md`
   (versionado) y el registro vivo queda solo con las activas.
3. `decisiones/wiki-t1-coordinadora.md` y `decisiones/runtime-budgets-al-ci-coordinadora.md`
   (plantillas vacías) no existen.
4. `docs/coordinacion-sesiones.md` contiene las siete reglas vigentes y `AGENTS.md` lo referencia
   con una línea en «Autoridad y alcance».
5. `npm run test:wiki` en verde y el trabajo publicado en `main`.

**Estado previo medido (2026-08-20).** Los tres registros de coordinación
(`gobierno-relato-de-autorizaciones`, `estados-consolidado-coordinadora`,
`linea-base-contractual-coordinadora`) ya estaban commiteados con frontmatter — ese punto de la
spec se ejecutó antes de este frente. El `git pull --ff-only` del checkout raíz quedó satisfecho
sin acción: la raíz está exactamente en `origin/main` (`3144ca5e`).

## Archivos de este goal

- [[docs/superpowers/specs/2026-08-19-organizar-la-casa-design|Spec (es el plan)]]
- [[decisiones/sesiones-historial|Historial de sesiones]] · `decisiones/vistos/`
- [[docs/coordinacion-sesiones|Las siete reglas]]
- [[memoria/goals/estado]]
