---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-08-19
areas: [proceso]
fuente: goals/wiki-t3/goal.md
resumen: Que las 151 páginas de memoria/ declaren capa: wiki y lleven los tags transversales que les corresponden, para que el catálogo y las vistas puedan filtrar por…
---

<!-- cas:cita-textual — registro del frente: cita salidas y comandos tal como se midieron -->
# Frente: wiki-t3

## Fase del plan
Plan: docs/superpowers/plans/2026-08-18-wiki-v2-visual.md
Fase: Fase 3 · Tanda 3 — Retag fino de la wiki (151 páginas)
Sha verificado: ?
Presupuesto: ?

## Objetivo
Que las 151 páginas de `memoria/` declaren `capa: wiki` y lleven los `tags` transversales que les
corresponden, para que el catálogo y las vistas puedan filtrar por algo más que `tipo` y `areas`.

## Condición de hecho
`npm run test:wiki` verde y `node scripts/wiki-lint.mjs --estricto` verde. La vista «Abierto ahora»
y el catálogo de `memoria/paginas.base` siguen listando lo mismo que antes: el retag **no puede
sacar ninguna página de una vista donde ya estaba**.
Verificación: npm run test:wiki

## Posture
- **No tocar el cuerpo de ninguna página.** Solo el bloque de frontmatter.
- No cambiar `tipo`, `estado`, `fecha` ni `areas` de ninguna página: este frente añade `capa` y
  `tags`, no reclasifica lo que ya estaba decidido.
- No inventar un tag para que una página encaje: el vocabulario es cerrado y son ocho.
- No tocar `memoria/index.md` ni `.obsidian/` (Tandas 4 y 5).
- No tocar los tres archivos congelados por `goal-provenance.json` (ratificado el 2026-08-19).
- No cambiar lo que una vista de `paginas.base` mide.

## Leer primero
- docs/wiki-operacion.md § Los ocho `tags`
- goals/wiki-t2/goal.md § Cierre — los seis defectos que destapó aplicar
- memoria/paginas.base — qué mide cada vista hoy

## Archivos declarados
memoria/**

## Contención
`memoria/` no lo está tocando ninguna otra sesión: las tres vivas trabajan design system y CI.
El riesgo aquí no es la colisión sino la regresión silenciosa — de ahí la segunda mitad de la
condición de hecho, que compara las vistas antes y después en vez de confiar en que el lint pase.

## Cadena de herramientas
- `node scripts/wiki-lint.mjs --estricto` — la condición de hecho.
- Un censo de vistas antes y después, para probar que ninguna página se cayó de donde estaba.

## Cierre

**Fase 3 · Tanda 3 cerrada el 2026-08-19.** Las 151 páginas declaran `capa: wiki` y llevan los tags
que les corresponden bajo la regla que zanjó la contradicción entre spec y plan.

```
node scripts/wiki-lint.mjs --estricto  →  RC=0
  Sin hallazgos. 151 páginas de wiki y 411 de 414 fuentes declaradas (modo estricto).
node scripts/wiki-vistas.mjs --comparar  →  Ninguna vista cambió. 7 vistas, 151 páginas.
```

**Ningún cuerpo tocado:** el diff de `memoria/` solo contiene líneas de frontmatter.

### La contradicción, y cómo se zanjó

La spec pedía tags «que no duplican `tipo` ni `areas`»; el plan pedía «trampas → `trampa`, mapas →
`moc`». De cuál ganaba dependían **95 de las 151 páginas (63%)**. Decisión del 2026-08-19: **gana
la spec.** Un tag que devuelve lo mismo que un filtro por `tipo` es ruido con coste; los tags valen
cuando **cruzan** la clasificación.

### El reparto real, que es el hallazgo incómodo

| Tag | Páginas | Por qué |
|---|---|---|
| `generado` | 26 | tienen zona `<!-- generado:inicio -->`; editarlas a mano se pierde |
| `plantilla` | 5 | los moldes de la Tanda 1 |
| `trampa` | **4** | los conceptos con sección «Dónde se rompe esto en la práctica» |
| `moc` | 0 | los 9 `tipo: mapa` **son** los MOCs; taggearlos duplicaría el tipo |
| `pendiente` | 0 | hay 1 página `estado: abierto`, y el tag duplicaría `estado` |
| `leer-antes-de-tocar` | 0 | `CLAUDE.md` manda leer **el mapa del área** antes de tocarla, lo que correlaciona 1:1 con `tipo: mapa` |
| `dashboard`, `archivo` | 0 | `index.md` es Tanda 4; no hay archivo dentro de `memoria/` |

**Bajo la regla, seis de los ocho tags quedan vacíos en la capa wiki, y eso es información, no un
fracaso:** el vocabulario de tags se gana el sueldo en la capa de **fuentes** (donde `archivo`
marca 43 documentos y `leer-antes-de-tocar` los cuatro contratos de la raíz) y lo hará en las
Tandas 4 y 5, cuando existan el dashboard y los 13 MOCs. Dentro de `memoria/`, `tipo` y `areas` ya
discriminaban casi todo lo que había que discriminar.

Los 4 de `trampa` se eligieron **verificando, no sospechando**: un barrido por palabras clave dio
21 candidatos, y al leerlos los mapas y los módulos solo **enlazaban** trampas («Ver [[x]] para las
trampas de…»), que es el trabajo propio de su tipo. Solo cuatro conceptos las **documentan**, en
una sección titulada «Dónde se rompe esto en la práctica».

### Herramienta nueva

`scripts/wiki-vistas.mjs`. El plan verificaba «lint verde; vista Abierto ahora y catálogo siguen
completos», y **el lint verde no prueba lo segundo**: un frontmatter puede quedar bien formado y
mal clasificado a la vez, y entonces una página se cae del catálogo sin que nada se ponga rojo.
Censa qué lista cada vista de `paginas.base` —leyéndolas del propio `.base`, no de una lista en el
script— y `--comparar` sale en rojo si alguna cambió.
