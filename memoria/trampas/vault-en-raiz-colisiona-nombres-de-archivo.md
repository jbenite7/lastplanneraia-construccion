---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-08-19
areas: [proceso, qa]
tags: [trampa]
fuente: frente ds-f0-auditoria, tanda 2 del 2026-08-19
resumen: "Crear `docs/<algo>/<slug>.md` con un nombre que ya existe en `memoria/` vuelve ambiguo cada wikilink a ese slug"
---

# El vault es la raíz, así que el nombre de un archivo nuevo ya no es libre

**El síntoma.** `npm run test:wiki` pasa de cero hallazgos a decenas, todos de la misma forma y
ninguno en una página que hayas tocado:

```
ENLACE memoria/log.md: ambiguo: [[torre-de-control-bi]] →
  docs/design-system/auditoria/modulos/torre-de-control-bi.md,
  memoria/arquitectura/torre-de-control-bi.md
```

**Lo que parece.** Que rompiste la wiki. No la tocaste: los hallazgos salen en páginas de
`memoria/` que llevan meses escritas y que no aparecen en tu diff.

**Lo que es.** El vault de Obsidian **es la raíz del repo**, no `memoria/`. Un `[[wikilink]]`
resuelve por nombre de archivo en todo el árbol, así que crear
`docs/design-system/auditoria/modulos/programa-general.md` pone a competir ese archivo con
`memoria/arquitectura/programa-general.md` por el enlace `[[programa-general]]`. Cada nota que ya
enlazaba al módulo queda ambigua de golpe, y el lint las cuenta todas.

El 2026-08-19, once fichas nuevas produjeron **37 hallazgos**; nueve de los once nombres
colisionaban.

**Cómo se sale.** Prefija los `.md` nuevos con el frente o el ámbito que los produce
(`ds-f0-programa-general.md`), y prefija **todos** los del lote, no solo los que colisionan hoy:
el criterio no debe depender de que nadie cree mañana un archivo con ese nombre. Comprobación
antes de commitear:

```bash
for f in <dir>/*.md; do b=$(basename "$f"); \
  [ -f "memoria/arquitectura/$b" ] && echo "COLISIONA: $b"; done
```

**Cuánto costó.** Poco, porque saltó en la verificación previa a publicar y no después. Habría
costado bastante más al revés: los 37 hallazgos aparecen en páginas ajenas, así que la siguiente
sesión que corriera el lint los habría visto como deuda propia de la wiki y no como efecto de un
directorio nuevo en `docs/`.

Relacionadas: [[wiki-operacion]]
