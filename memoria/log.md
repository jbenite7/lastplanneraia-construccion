---
tipo: log
estado: vigente
fecha: 2026-08-02
areas: []
fuente: sesion
resumen: "Bitácora cronológica de la wiki: qué se ingirió, qué se consultó y qué se verificó"
---
# Bitácora

Append-only. Una línea por operación, la más reciente abajo. Formato:

```
- YYYY-MM-DD · operación · asunto · páginas tocadas
```

Las operaciones son `ingest`, `query` y `lint`, descritas en [[index]]. Se escribe al final de
cada una, no antes.

---

- 2026-08-02 · ingest · Se funda la wiki con el patrón LLM Wiki de Karpathy · [[index]], [[log]]
- 2026-08-02 · ingest · Migradas 31 memorias privadas del asistente desde `~/.claude`, conservando el cuerpo verbatim y acortando los wikilinks · 23 páginas en `trampas/`, 6 en `decisiones/`, 2 en `referencias/`
- 2026-08-02 · ingest · Leídos los 16 `goal.md` para fijar el estado real de cada goal · [[estado]]
- 2026-08-02 · ingest · Escritos los siete mapas de área a partir de la documentación existente · [[arquitectura]], [[design-system]], [[pdc]], [[lps-dominio]], [[rbac-y-rutas]], [[entorno-y-despliegue]], [[qa-y-gates]]
- 2026-08-02 · ingest · Enganche de la wiki en [[CLAUDE]] (sección «Memoria del proyecto», con el esquema y las tres operaciones) y en [[AGENTS]] (línea de precedencia) · `CLAUDE.md`, `AGENTS.md`
- 2026-08-02 · ingest · Las 31 memorias privadas de `~/.claude` reducidas a punteros; su `MEMORY.md` pasa a avisar de la mudanza
- 2026-08-02 · lint · Apareció un primer tramo de la wiki ya en disco (`Inicio.md` y dos páginas de `decisiones/`), sin autoría identificable; se asume trabajo previo propio no registrado. Se retiraron dos páginas duplicadas entre `trampas/` y `decisiones/`, se adoptó su campo `origen:` en las 31 páginas migradas y se fusionó `Inicio.md` en [[index]]. Una versión previa del spec, no versionada, se perdió al sobrescribirla · [[index]], [[compras-migrado-shell-sidebar]], [[dev-door-acceso-local]]
- 2026-08-02 · lint · Filtrados `.agents/` y `.github/` del vault: espejaban la documentación de skills y creaban 44 nombres duplicados. Quedan 8 duplicados estructurales (`goal.md`, `plan.md`, `facts.md` por goal) que no afectan a ningún enlace · `.obsidian/app.json`
- 2026-08-02 · lint · Verificación: 186 wikilinks resueltos, 0 rotos y 0 ambiguos; 0 rotos también en un clon fresco; 31 páginas migradas frente a 31 punteros; toda página aparece en [[index]]; ningún `.md` de `docs/` modificado
- 2026-08-02 · ingest · Escrita la lección del episodio de atribución: no deducir autoría de un archivo por la hora de última actividad de una sesión · [[autoria-por-coincidencia-de-hora]], [[index]]
- 2026-08-02 · ingest · Repasada la lista blanca de `goals/` en `.gitignore`: `shell-layout-design-system` y `sidebar-todos-modulos` no tenían ningún archivo en git, faltaba `cierre-dark-mode-y-tablas/specs/diseno.md`, y `bi-control-tower-gemini` viajaba solo por accidente histórico. Añadidas sus excepciones: los 16 goals viajan ahora, 97 `.md` en un clon fresco. Corregida la sección que los daba por perdidos · [[estado]], `.gitignore`
- 2026-08-02 · ingest · Convertidas a wikilinks las menciones que los mapas y [[estado]] ya hacían por ruta: 9 documentos de `docs/` y los 16 goals. La conectividad del grafo pasa de 50 a 75 nodos (de 265). Se dejó en código `docs/ROUTES.md`, que no viaja en git y quedaría roto en un clon · 7 mapas, [[estado]]
- 2026-08-02 · ingest · Tejidos los goals a sus áreas: [[design-system]] y [[pdc]] enlazan ahora los goals que trabajaron cada zona, así que cada `goal.md` tiene dos entrantes en vez de colgar solo de [[estado]]
- 2026-08-02 · ingest · Por decisión del usuario se abre una excepción a la inmutabilidad de las fuentes: cada `goals/<slug>/goal.md` recibe al pie una sección «Archivos de este goal» que enlaza a sus hermanos versionados y a [[estado]]. `goals/` pasa de 16 a 97 nodos conectados de 99; el grafo completo, de 75 a 156 de 265. `docs/` no se toca · 16 `goal.md`, [[index]], [[CLAUDE]]
