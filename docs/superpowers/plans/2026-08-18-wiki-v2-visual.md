---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-08-18
areas: [proceso]
fuente: docs/superpowers/plans/2026-08-18-wiki-v2-visual.md
resumen: Plan — Wiki v2 visual y etiquetada
---

# Plan — Wiki v2 visual y etiquetada

**Spec:** `docs/superpowers/specs/2026-08-18-wiki-v2-visual-design.md` · **Estado:** al gate,
sin ejecutar. **Esfuerzo estimado:** ~2 jornadas en 6 tandas; cada tanda cierra en verde antes de
la siguiente. Se ejecuta después de la Fase 0 (mudanza): es el orden que fijó el usuario, y un
cambio de cientos de archivos conviene hacerlo con el repo ya asentado en su sitio definitivo —
las rutas internas no cambian con la mudanza, pero verificar tandas mientras el árbol se muda es
pagar la verificación dos veces.

## Fase 1 · Tanda 1 — Esquema y herramientas (base de todo)

1. Reescribir `docs/wiki-operacion.md` al esquema v2 (capa, tags, tipos nuevos, regla de plugins).
2. `scripts/wiki-lint.mjs` v2: `capa`, vocabulario `tags`, tipos de fuente; modo fuente =
   solo-frontmatter. Tests en `tests/wiki/` actualizados.
3. `scripts/wiki-frontmatter.mjs` nuevo con `--dry-run` y reglas por ruta.
4. Plantillas core (`templates/`): una por `tipo` frecuente (decision, trampa, concepto, spec, plan).
- **Verifica:** `npm run test:wiki` verde con la wiki actual intacta (retrocompatible antes de
  tocar fuentes); `--dry-run` del backfill imprime el censo completo sin escribir.

## Fase 2 · Tanda 2 — Frontmatter a las fuentes (por lotes)

1. Medir el censo real (`--dry-run`): cuántos `.md` por carpeta (docs/, goals/, raíz).
2. Aplicar por lotes: raíz+contratos → `docs/flujos/` y `docs/design-system/` → resto de `docs/`
   → `goals/` (99 archivos) → `docs/archive/` (tag `archivo`).
3. Revisión muestral humana por lote (10%) antes del siguiente.
- **Verifica:** lint v2 verde tras cada lote; `git diff --stat` solo muestra bloques frontmatter
  (ningún cuerpo tocado — comprobado con `git diff -U0 | grep -v '^[+-]---\|^[+-][a-z]*:'` vacío).

## Fase 3 · Tanda 3 — Retag fino de la wiki (145 páginas)

1. Añadir `capa: wiki` y `tags` donde apliquen (trampas → `trampa`, mapas → `moc`, etc.) por
   script + pasada manual sobre las que el script no clasifique.
- **Verifica:** lint verde; vista «Abierto ahora» y catálogo siguen completos.

## Fase 4 · Tanda 4 — Capa visual

1. Activar Canvas; crear `tablero-de-control.canvas`, `mapa-del-sistema.canvas`,
   `cascada-lps.canvas` en `memoria/`.
2. Grupos de color del grafo por capa/área; snippet CSS de severidad; iconos (Iconize) por carpeta.
3. Instalar y versionar plugins: Dataview, Tasks, Kanban, Excalidraw, Iconize, Homepage, tema
   Minimal + Style Settings. Ajustar `.gitignore` si excluye `.obsidian/plugins`.
4. Home dashboard: `memoria/index.md` renovado (callouts, Bases embebidos, accesos) + Homepage
   apuntándole. Kanban de la cola enlazado con [[cola-de-pendientes]].
- **Verifica:** clon frío en carpeta temporal abre el vault con tema, iconos, dashboard y canvas
  operativos; sin plugins, el contenido sigue legible (Bases/Markdown).

## Fase 5 · Tanda 5 — MOCs completos

1. Completar los 13 MOCs de área (hoy 7), cada uno con Bases embebido de su área y trampas
   arriba; taggear `moc`.
- **Verifica:** lint verde (ninguna página fuera de índice/vistas); cada área lista ≥1 MOC.

## Fase 6 · Tanda 6 — Cierre

1. `node scripts/wiki-arquitectura.mjs --cobertura` y `--escribir` (zonas generadas con tag).
2. `wiki-registro.mjs --escribir`; línea `ingest` en `memoria/log.md`; actualizar
   [[cola-de-pendientes]] (Fase 0b cerrada).
3. Gate de publicación de AGENTS.md (verificar → integrar → re-verificar → `scripts/publicar.sh`).
- **Verifica:** condición de hecho de la spec completa, con salidas pegadas en el goal.

## Riesgos y reversas

- **Backfill masivo mal deducido** → siempre `--dry-run` + lotes + muestreo; revertir un lote es
  `git checkout -- <carpeta>`.
- **Plugins rompen al actualizar Obsidian** → quedan versionados; la regla «legible sin plugins»
  es la red.
- **Roce con sesiones vivas** → tandas 2-3 tocan cientos de archivos: ejecutar con la cola parada
  (ya lo está por orden del usuario) y publicar por tanda, no al final.
