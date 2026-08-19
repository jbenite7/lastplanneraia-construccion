---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-08-19
areas: [proceso]
fuente: goals/wiki-t5/goal.md
resumen: Que las trece áreas tengan su MOC, y que «MOC» sea un tipo de página y no una etiqueta.
---

<!-- cas:cita-textual — registro del frente: cita salidas y comandos tal como se midieron -->
# Frente: wiki-t5

## Fase del plan
Plan: docs/superpowers/plans/2026-08-18-wiki-v2-visual.md
Fase: Fase 5 · Tanda 5 — MOCs completos
Sha verificado: ?
Presupuesto: ?

## Objetivo
Que las trece áreas tengan su MOC, y que «MOC» sea un tipo de página y no una etiqueta.

## Condición de hecho
`npm run test:wiki` y `wiki-lint.mjs --estricto` en verde, y **cada una de las 13 áreas lista al
menos un MOC**, comprobado por conteo y no por vista.
Verificación: npm run test:wiki

## Posture
- No tocar el cuerpo de ninguna página existente: solo se añaden MOCs nuevos.
- Un MOC **enruta, no reinventa**: enlaza páginas que ya existen y cita el documento que manda.
  Si un MOC afirma algo que no está escrito en otro sitio, es que se está inventando conocimiento.
- No crear un MOC vacío por completar la cuenta de 13.

## Leer primero
- memoria/mapas/qa-y-gates.md — el modelo de estructura
- docs/wiki-operacion.md § Los siete `tags`

## Archivos declarados
memoria/**,docs/wiki-operacion.md,scripts/wiki-esquema.mjs,tests/wiki/**

## Cierre

**Fase 5 · Tanda 5 cerrada el 2026-08-19.**

```
npm run test:wiki                      →  RC=0 · «Sin hallazgos. 156 páginas de wiki y 411 de 414 fuentes declaradas»
node scripts/wiki-lint.mjs --estricto  →  RC=0
áreas con MOC: 13 / 13 — ninguna área sin MOC
```

### La decisión que la precedió, y el error que la destapó

El 2026-08-19 escribí que «los 9 `tipo: mapa` **son** los MOCs» al escalar el choque entre spec y
plan. **Era falso y lo corregí al medirlo: son 7 de 9.** `index.md` (la portada) y
`registro-de-trabajo.md` (catálogo generado) también eran `tipo: mapa` sin ser mapas de área. Ese
dato dio la vuelta al planteamiento: el tag `moc` **no** habría duplicado el tipo, sí discriminaba.

Con eso sobre la mesa se eligió la **salida B**: `moc` sale del vocabulario (quedan siete tags),
`registro-de-trabajo` pasa a `tipo: referencia` —que es lo que de verdad es— y **`tipo: mapa`
significa MOC**. El argumento: un mapa de área tiene estructura propia y fija, así que es una
**clase** de página, y las clases viven en `tipo`. El tag habría existido solo para parchear una
página mal tipada.

El movimiento de vista quedó **probado, no supuesto**: `wiki-vistas.mjs --comparar` reportó
exactamente `VISTA «Referencias»: 5 → 6 · + entró: memoria/registro-de-trabajo.md`, y nada más.

### Los cinco MOCs nuevos

`worktrees` · `datos` · `bi` · `admin` · `procesos-y-sesiones`. Enrutan, no reinventan: cada uno
cita el documento que manda y enlaza páginas que ya existían. El de `worktrees` recoge 12 trampas
que eran la misma sorpresa vista desde ángulos distintos —se mide en un árbol y se concluye sobre
otro—, y el de `procesos-y-sesiones` recoge 11 sobre las formas en que un verde miente.

`entorno-y-despliegue` mencionaba worktrees en su cuerpo pero declaraba `areas: [docker, deploy]`;
ahora el área tiene mapa propio en vez de estar cubierta de refilón.

### Lo que NO se hizo, y hay que decirlo

El plan pedía «cada uno con **Bases embebido de su área**». **No se creó ninguna vista Bases por
área.** El motivo es que no puedo verificar la sintaxis de filtrado por `areas` sin abrir Obsidian,
y un `.base` mal escrito **no lo caza ningún gate**: renderiza un error dentro de la aplicación y
el lint pasa en verde. Escribir cinco archivos que no puedo comprobar sería exactamente el tipo de
verde falso que la mitad de las trampas de este repo documentan.

Los MOCs enrutan hoy con wikilinks en Markdown, que es la capa que la propia regla de v2 exige
(«los plugins amplifican, no sostienen»). **Queda para la Tanda 4**, que es cuando alguien abre el
vault y puede comprobar que la vista renderiza.
