---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-09-29
areas: [proceso]
fuente: "gh api repos/jbenite7/lps-aia/... (404) frente a gh repo view --json nameWithOwner (jbenite7/lastplanneraia-construccion), 2026-09-28 y 2026-09-29"
resumen: "El directorio local es lps-aia, pero el repositorio de GitHub es jbenite7/lastplanneraia-construccion: consultar la API con el nombre del directorio da 404 en todo, y un 404 no prueba que no haya protección ni reglas"
---
# Un 404 de GitHub puede ser el nombre del repo

**El síntoma.** `gh api repos/jbenite7/lps-aia/branches/main/protection` y
`.../rulesets` respondieron 404 «Not Found». Se leyó como «no hay evidencia de protección de rama» y se
escribió en la spec del CI por carriles (hecho «La API de GitHub responde 404 a protección de rama y a
rulesets de `main`») y en el aviso a Felipe sobre checks obligatorios.

**Lo que parece.** Que la rama no está protegida, o que la API no deja verlo sin permisos de
administrador.

**Lo que es.** El nombre estaba mal. El directorio se llama `lps-aia`, pero el remoto es
`jbenite7/lastplanneraia-construccion`. Con el nombre correcto, `branches/main` responde
`protected: false` y el endpoint de protección dice «Branch not protected»: una respuesta útil, no un
404 a secas. El nombre real ya estaba escrito en [[repo-publico-por-ci]].

**Cómo se sale.** Antes de leer un 404 de `gh api`, correr
`gh repo view --json nameWithOwner` dentro del repo, o usar los subcomandos de `gh` que toman el
remoto del directorio actual (`gh run list`, `gh pr list`), que sí funcionaron todo el tiempo. Y
consultar la wiki antes de escribir un nombre de repo de memoria.

**Cuánto costó.** Un día con una premisa falsa en una spec aprobada y un riesgo mal descrito a Felipe.
Se corrigió el 2026-09-29 al reconsultar con el nombre correcto.

Relacionadas: [[repo-publico-por-ci]] ·
[[un-check-obligatorio-no-cubre-una-pata-de-matriz-que-no-existe]] · [[qa-y-gates]]
