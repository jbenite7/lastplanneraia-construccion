---
tipo: mapa
estado: vigente
fecha: 2026-08-02
areas: []
fuente: sesion
resumen: "Puerta de entrada a la wiki: qué es, cómo se opera y catálogo de todas sus páginas"
---
# Memoria del proyecto

Esta carpeta es la **memoria del proyecto**: el porqué de las decisiones, las trampas que ya
costaron tiempo, y un mapa por área que enlaza con la documentación que ya existe en el repo.

Sigue el patrón [LLM Wiki](https://gist.github.com/karpathy/442a6bf555914893e9891c11519de94f):
tres capas, y el asistente mantiene la de en medio.

| Capa | Dónde | Regla |
|---|---|---|
| Fuentes | `docs/`, `goals/`, los `.md` de la raíz, el código | Se leen. **Su contenido no se edita desde aquí.** |
| Wiki | `memoria/` | La escribe el asistente. **Nunca se edita a mano.** |
| Esquema | [[wiki-operacion|Cómo se opera la wiki]] | Explica esta estructura y las cuatro operaciones. [[CLAUDE]] lo resume. |

**Una excepción, decidida el 2026-08-02:** cada `goals/<slug>/goal.md` lleva al final una sección
«Archivos de este goal» que enlaza a sus hermanos y a [[estado|Estado de los goals]]. Es
navegación añadida al pie, no contenido modificado, y es lo único que hace que los 99 archivos de
`goals/` aparezcan tejidos en el grafo en vez de como islas. `docs/` sigue intacto.

El vault de Obsidian es la **raíz del repo**, no esta carpeta. Por eso los enlaces alcanzan a
`docs/`, `goals/` y a los `.md` de la raíz sin copiarlos aquí.

## Precedencia

**Código > [[AGENTS]] > `memoria/`.**

Nada de lo que hay aquí es contrato. Si una nota contradice al repo, gana el repo: corrige la nota
y márcala `estado: derogada` en vez de borrarla — saber que algo dejó de ser cierto también es
memoria.

**Áreas válidas** (lista cerrada de trece; `scripts/wiki-lint.mjs` la comprueba): `design-system` ·
`qa` · `docker` · `worktrees` · `pdc` · `lps` · `datos` · `rbac` · `deploy` · `bi` · `admin` ·
`proceso` · `arquitectura`. Si necesitas una nueva, añádela primero al script y explica aquí qué
cubre; una lista que crece sin control deja de servir para filtrar.

## Las cuatro operaciones

- **Ingest** — al cerrar una tarea o al aparecer una fuente nueva: se escribe o actualiza la
  página, se actualiza este índice, se revisan las páginas relacionadas y se anexa una línea a
  [[log]].
- **Query** — preguntas contra la wiki, respondidas citando páginas. Si la respuesta era valiosa y
  no estaba escrita, se convierte en página.
- **Lint** — `npm run test:wiki`: comprueba la **forma** (enlaces, frontmatter, áreas, orfandad).
  Comprueba y reporta; nunca corrige. Un verde no significa que la wiki sea correcta.
- **Veracidad** — la otra mitad: verificar contra el código que lo escrito sigue siendo cierto,
  por rotación de áreas y verificando cada afirmación en vez de sospecharla. No depende de que
  alguien se acuerde: el lint cuenta los commits de código desde el último pase y sale en rojo por
  encima de 40.

Reglas de escritura: **una nota, un hecho**; si no cabe en una pantalla, probablemente son dos. Y
antes de tocar un área, lee su mapa: dice qué documentos mandan y qué trampas hay puestas.

El procedimiento completo está en [[wiki-operacion|Cómo se opera la wiki]].

## Mapas por área

| Mapa | Cubre |
|---|---|
| [[arquitectura]] | Front controller, `src/`, el mini-app `admin/`, tablas globales |
| [[design-system]] | Tokens, capas CSS, gates, baselines, el laboratorio |
| [[pdc]] | Plan de Compras v2: SPA en `pdc-app/` + servicios PHP |
| [[lps-dominio]] | Programación general, intermedia y semanal; estados; cajón contextual |
| [[rbac-y-rutas]] | Roles, capacidades, rutas protegidas, sesión |
| [[entorno-y-despliegue]] | Docker, puerta de servicio, worktrees, SiteGround |
| [[qa-y-gates]] | Suites de prueba, rojos preexistentes, evidencia |

Además: **[[estado|Estado de los goals]]** (qué goal está abierto, cerrado o absorbido),
**[[registro-de-trabajo|Registro de trabajo]]** (cada spec de diseño con el plan que la ejecutó,
por mes, incluido lo archivado) y **[[log]]** (bitácora cronológica de lo que se ha ingerido y
verificado).

## Arquitectura por módulo

Una página por módulo real de la aplicación en `memoria/arquitectura/`, y dos de flujo en
`memoria/flujos/`: [[flujo-lps]] y [[flujo-pdc]].

Cada página de módulo tiene dos zonas. Entre `<!-- generado:inicio -->` y `<!-- generado:fin -->`
manda `scripts/wiki-arquitectura.mjs`, que extrae del código las rutas con su verbo y destino, los
controladores, los servicios, las tablas y qué rol tiene cada capacidad. **Fuera de los marcadores
manda la persona**, y regenerar no lo toca. Cuando cambien rutas, controladores o permisos:

```bash
node scripts/wiki-arquitectura.mjs --cobertura   # ninguna ruta sin módulo
node scripts/wiki-arquitectura.mjs --escribir    # actualiza las zonas generadas
```

Si aparece un módulo nuevo, se declara en `scripts/wiki-arquitectura.modulos.mjs`; si una ruta
nueva no casa con ningún módulo, `--cobertura` falla en vez de dejarla fuera del mapa en silencio.

El inventario de rutas y la matriz de navegación vivían antes en `docs/ROUTES.md`, que no viajaba
en git. Se retiró el 2026-08-03: ahora están aquí y versionados.

## Catálogo

Decisiones, trampas, referencias, módulos y flujos, generados desde el frontmatter de cada página
(`tipo`, `resumen`, `areas`, `fecha`). La base trae las cinco vistas seleccionables — no hace falta
un embebido por tabla.

![[paginas.base]]

**Lo que queda fuera del grafo, y por qué.** Medido el 2026-08-03: el vault pasó de **124 archivos
sueltos a 25**. Primero se tejieron a mano los 25 que sí mandan hoy —contratos del design system,
referencias de infraestructura, los `.md` de la raíz—; después, los 74 planes y specs de trabajo
fechado dejaron de estar sueltos sin escribir un solo enlace a mano, porque
[[registro-de-trabajo|el registro de trabajo]] los cataloga y empareja cada spec con su plan. Y los
30 documentos ya cerrados que nadie citaba se movieron a `docs/archive/superpowers/`.

Los 25 que quedan son documentos sueltos de `docs/` y entregables de goals cerrados. Se quedan así:
[[log]] los cita cuando importan y cada `goals/<slug>/goal.md` enlaza sus hermanos, de modo que
tejerlos daría un grafo completo a cambio de enlaces que nadie recorrería. El criterio y las
mediciones están en
[[docs/superpowers/specs/evidencia/2026-08-03-nodos-sueltos|la evidencia del barrido]].

## Contratos del repo (no viven aquí)

[[AGENTS]] es el contrato autoritativo · [[CLAUDE]] orienta al asistente · [[DESIGN]] es el
contrato de consumo de UI · [[GLOSARIO]] fija el vocabulario · [[ROADMAP]] y [[CHANGELOG]] cuentan
hacia dónde va y por dónde pasó.
