# Nodos sueltos del vault — clasificación del 2026-08-03

Medido con un script de un solo uso que reutiliza el recorrido y los filtros de
`scripts/wiki-lint.mjs`: cuenta enlaces `[[wikilink]]` entrantes y salientes de cada `.md` del
vault y lista los que no tienen ninguno de los dos.

| | |
|---|---|
| Archivos del vault | 323 (324 tras crear `docs/wiki-operacion.md`) |
| Sueltos antes | **124** |
| Tejidos como vigentes | **25** |
| Sueltos después | **99** |

La cifra «~109» que circulaba en el diagnóstico era de memoria y no coincidía; esta está medida.

## Cómo se decidió qué es vigente

Una primera clasificación delegada a un subagente barato **se descartó**: marcó 113 de 124 como
vigentes con justificaciones genéricas repetidas («plan vigente de trabajo») y su propio preámbulo
reconocía haber decidido por carpeta y fecha —«docs/superpowers/ fechado 2026-07-xx → vigente»—,
que era justo el atajo prohibido en el encargo. Tejer eso habría producido un grafo completo y
falso: por ejemplo, `2026-07-28-paleta-estado-oscura.md` es el plan de una inversión de paleta ya
ejecutada y documentada en `DESIGN.md`, no trabajo vivo.

Se sustituyó por un criterio comprobable, aplicado a mano:

1. **Citado por un documento vivo** — `grep` de la ruta en `AGENTS.md`, `CLAUDE.md`, `DESIGN.md`,
   `GLOSARIO.md`, `README.md`, `docs/design-system/README.md`,
   `docs/global-tables-architecture.md`, `docs/pdc-v2.md`, los mapas de `memoria/` y su índice.
   **9 archivos.**
2. **Contrato o referencia del design system** — leída la cabecera de cada uno: los tres de
   `contracts/` se autodeclaran autoridad, y `tokens`, `components`, `decisions`, `dark-palette`,
   `migration`, `CHANGELOG` (0.3.6 en construcción) y `manual-accessibility-review` son la
   referencia que `AGENTS.md` manda consultar. **10 archivos.**
3. **Operativo de infraestructura** — `docker/README.md` (el compose real),
   `database/patches/global/README.md` (parches en versión global) y
   `docs/global-tables-unique-ids.md` (hermano de la arquitectura de tablas globales).
   **3 archivos.**

Total tejido: 22, más `docs/brand/aia_design_system_web_apple_inspired.md` y
`database/seeds/biblioteca_maestra_pdc_source_of_truth_v1_0.md` como insumos citados en su mapa, y
`memoria/arquitectura/integracion.md`, que estaba suelto siendo página de la wiki —eso era un fallo
propio, no un histórico—. **25.**

La medición posterior bajó de 124 a 99 exactamente, lo que confirma que los 25 enlaces resolvieron.

## Los 99 que quedan sueltos, a propósito

Son trabajo fechado: planes y specs de `docs/superpowers/` de goals ya ejecutados, los ciclos PDCA
de `docs/archive/`, los walkthroughs de marzo, análisis de productividad y entregables de goals
cerrados. Su vía de acceso ya existe y no pasa por el grafo: `memoria/log.md` los cita cuando
importan, y cada `goals/<slug>/goal.md` enlaza sus propios hermanos.

Tejerlos daría un grafo del 100 % a cambio de enlaces que nadie recorrería. Un grafo honesto vale
más que uno completo.
