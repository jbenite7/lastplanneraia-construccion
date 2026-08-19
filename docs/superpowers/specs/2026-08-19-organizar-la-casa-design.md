---
capa: fuente
tipo: spec
estado: vigente
fecha: 2026-08-19
areas: [proceso]
fuente: docs/superpowers/specs/2026-08-19-organizar-la-casa-design.md
resumen: repo y sus sesiones»; PDC como frente propio; registros versionados en git; diseño aprobado tal como está).
---

# Organizar la casa — el repo y sus sesiones

**Fecha:** 2026-08-19 · **Decidido con Felipe en el canal de la coordinadora** (alcance: «solo este
repo y sus sesiones»; PDC como frente propio; registros versionados en git; diseño aprobado tal
como está).

## Problema

La coordinación entre sesiones funcionó toda la jornada, pero sobre memoria de chat y archivos
locales: los vistos viven bajo `.claude/` (ignorado por `.gitignore:219`, invisible desde
worktrees), dos registros de decisiones con contenido real están sin commitear, las reglas de
tráfico (contenedor, base, relato, vistos) existen solo en mensajes entre sesiones, y una sesión
reinstanciada arranca ciega — pasó tres veces hoy. Precedente medido del costo: 2026-08-11, doce
hallazgos perdidos sin diff por un archivo no versionado.

## Diseño

### 1 · Registros de coordinación versionados

- `decisiones/gobierno-relato-de-autorizaciones.md`, `decisiones/estados-consolidado-coordinadora.md`
  y `decisiones/linea-base-contractual-coordinadora.md` se commitean, con frontmatter v2 (el gate
  de publicación exige forma de wiki en `decisiones/*.md` desde `56f4e05a`; lo añade
  `node scripts/wiki-frontmatter.mjs --solo <archivo> --escribir`).
- Los vistos se **mudan** de `.claude/vistos/` a **`decisiones/vistos/`** (versionados, con
  frontmatter). Sin tocar `.gitignore`. `.claude/vistos/` queda vacío y deja de usarse.
- `.claude/sesiones.md` **no se versiona** (estado vivo reescrito por hooks; versionarlo = un
  conflicto por turno). Se depura: las filas `terminada` se mueven a
  `decisiones/sesiones-historial.md` (versionado, solo-lectura) y el registro vivo queda con las
  filas activas.

### 2 · Las reglas de sesiones, escritas y versionadas

`docs/coordinacion-sesiones.md`, fuente única de las siete reglas vigentes (hoy: prosa de chat):

1. **Frentes:** se declaran antes de ejecutar (goal.md + contención medida con `git log` de los
   globs y el registro); el plan pasa por el gate de la coordinadora antes de tocar código.
2. **Vistos:** la coordinadora re-verifica sobre el sha exacto; visto caduco si el sha cambia;
   se archivan en `decisiones/vistos/`.
3. **Relato de autorizaciones:** el relato de la coordinadora vale como autorización de Felipe
   SOLO para publicar en `main`; deploy, borrados y migraciones exigen su palabra directa o su
   cita textual registrada (`decisiones/gobierno-relato-de-autorizaciones.md` manda).
4. **Contenedor compartido:** solo se reapunta con ventana coordinada, y solo para lo que lo
   exige: el invariante de `publicar.sh` y la verificación en navegador. Se devuelve a la raíz al
   terminar, siempre.
5. **Contenedor efímero:** todo lo CLI corre con
   `LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app <cmd>`. Frontera real:
   «¿necesita Apache arriba?» (el nivel `http` de la suite es CLI y aun así lo necesita).
6. **Base de dev:** las escrituras se coordinan; durante una migración (respaldo→dry-run→apply→
   reconciliación) la base se congela entera para terceros — ni escrituras ni mediciones.
7. **Paso 0:** ningún RC de un comando dentro del contenedor se lee sin verificar antes qué árbol
   monta (`docker inspect … /var/www/html`).

Más **una línea en `AGENTS.md`** (sección Autoridad y alcance) apuntando a ese documento, para que
toda sesión lo lea al arrancar.

### 3 · Limpieza puntual

- Borrar `decisiones/wiki-t1-coordinadora.md` y `decisiones/runtime-budgets-al-ci-coordinadora.md`
  (plantillas vacías, la segunda con rol equivocado en el nombre).
- `git pull --ff-only` en el checkout raíz, en ventana coordinada (el contenedor lo sirve).

### 4 · La cola (registro, no diseño)

apply-recalculo-estados (en ejecución) → linea-base-contractual (en curso) → **PDC
`PlanFechasService` como frente propio** (decisión de Felipe) → remapeo PG contra el contrato →
DS-F1b z-index → verificación visual de Semanal/PG (al reabrir la sesión de reverent) → tests BI
frágiles (chip ya arrancado). Pendiente de Felipe, sin prisa: los realces sin declarar (r0 de PG y
ruta crítica de Semanal) como decisión única de producto.

## Ejecución

La coordinadora, inline, en un frente propio (`organizar-la-casa`) con rama y worktree como
cualquier frente: dos commits atómicos — (1) registros + mudanza de vistos + limpieza,
(2) `docs/coordinacion-sesiones.md` + línea en `AGENTS.md` — verificados con `npm run test:wiki`
(los `.md` nuevos deben pasar el esquema v2) y publicados con `scripts/publicar.sh`.

## Errores y bordes

- Si el gate de wiki rechaza frontmatter de los archivos mudados: se corrige con el backfill, nunca
  relajando el lint.
- La mudanza de vistos conserva los archivos con su historia textual intacta (se mueven, no se
  reescriben).
- `AGENTS.md` recibe exactamente una línea de referencia; el contrato no se reescribe.

## Fuera de alcance

El empaquetado del plugin (CAS), el rediseño del contenedor por worktree, la base compartida como
infraestructura, y el apply de producción. El deploy a producción sigue exigiendo autorización
propia, siempre.
