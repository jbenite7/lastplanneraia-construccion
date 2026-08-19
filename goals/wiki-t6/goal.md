---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-08-19
areas: [proceso]
fuente: goals/wiki-t6/goal.md
resumen: Cerrar la Fase 0b: regenerar lo generado, dejar la bitácora escrita, poner la cola al día y comprobar la condición de hecho de la spec entera, punto por punto.
---

<!-- cas:cita-textual — registro del frente: cita salidas y comandos tal como se midieron -->
# Frente: wiki-t6

## Fase del plan
Plan: docs/superpowers/plans/2026-08-18-wiki-v2-visual.md
Fase: Fase 6 · Tanda 6 — Cierre
Sha verificado: ?
Presupuesto: ?

## Objetivo
Cerrar la Fase 0b: regenerar lo generado, dejar la bitácora escrita, poner la cola al día y
comprobar la condición de hecho **de la spec entera**, punto por punto.

## Condición de hecho
Los siete puntos de la condición global de la spec, cada uno con la salida que lo demuestra o la
razón declarada de por qué no se cumple.
Verificación: npm run test:wiki

## Posture
- No marcar hecho lo que no se hizo. Un punto sin cumplir se declara, no se omite.
- No tocar lo que mide la alarma de veracidad para que deje de sonar.
- No escribir una línea `veracidad` sin haber hecho el pase de verdad.

## Leer primero
- docs/superpowers/specs/2026-08-18-wiki-v2-visual-design.md § Condición de hecho global
- memoria/goals/cola-de-pendientes.md

## Archivos declarados
memoria/**,goals/wiki-t6/**

## Cierre

**Fase 6 · Tanda 6 cerrada el 2026-08-19. Con ella cierra la Fase 0b entera.**

### La condición de hecho de la spec, punto por punto

| # | Punto | Resultado |
|---|---|---|
| 1 | Lint v2 verde sobre todo el vault | ✔ `--estricto` RC=0 · 156 páginas y 414 de 417 fuentes |
| 2 | Home dashboard | ✔ callouts, los tres canvas y la lista de lo abierto |
| 3 | 13 MOCs operativos | ✔ 13/13 áreas, comprobado por conteo |
| 4 | Los 3 canvas creados | ✔ validados como JSON, todos los destinos existen |
| 5 | Plugins versionados y funcionando en frío | **✖ NO SE HIZO** — decisión del usuario: sin plugins de comunidad |
| 6 | `wiki-operacion.md` reescrito a v2 | ✔ |
| 7 | Vault funcionando en un clon limpio | ✔ `.obsidian/`, `snippets/`, `bases/` y los 3 canvas viajan en git |

**El punto 5 no se cumple, y se declara en vez de omitirse.** Con él quedan fuera el Kanban de la
cola, el arranque automático del dashboard (Homepage), los iconos por carpeta y el tema Minimal.
El resto del vault no depende de ellos: la regla de v2 —«los plugins amplifican, no sostienen»— es
precisamente lo que hace que su ausencia no rompa nada.

### Lo que hizo esta tanda

```
node scripts/wiki-arquitectura.mjs --cobertura  →  RC=0 · «Cobertura completa: ninguna ruta queda sin módulo» (201 rutas)
node scripts/wiki-arquitectura.mjs --escribir   →  RC=0 · «0 páginas creadas, 0 zonas generadas actualizadas»
node scripts/wiki-registro.mjs --escribir       →  RC=0 · 106 trabajos, 45 emparejados, 20 archivados
npm run test:wiki                               →  RC=0
node scripts/wiki-lint.mjs --estricto           →  RC=0
```

La regeneración salió **idempotente**: las zonas generadas ya estaban al día. Se corrió igual,
porque «probablemente está al día» y «RC=0 y cero cambios» no son la misma afirmación.

`--escribir` necesita PHP dentro del contenedor y el worktree no traía `vendor/` (vive solo en la
raíz y está en `.gitignore`). Se instaló con `composer install` dentro del contenedor apuntando a
este árbol. **Enlazarlo desde la raíz no sirve** —el destino del enlace queda fuera de lo que el
contenedor monta—, al revés que con `.env`, donde el enlace sí es la receta correcta.

### El pase de veracidad, que la alarma exigió

Al añadir la línea `ingest`, la alarma saltó: **69 commits de código desde el pase anterior**,
por encima del umbral de 40. Se hizo el pase en vez de silenciarla.

**Alcance medido, no supuesto.** De esos 69 commits, el código de producto tocado son **8
archivos**; el resto del recuento es el propio backfill de la wiki v2, que tocó 413 archivos de
`docs/` sin cambiar una línea de contenido. Cuatro páginas verificadas contra el código citando
archivo y línea, **0 corregidas y 0 derogadas**.

**Dos hallazgos sobre el instrumento, no sobre la wiki:**

1. **Las citas ancladas a número de línea envejecen aunque la afirmación siga siendo cierta.**
   `hot.js` creció y las dos referencias apuntan ya unas líneas antes de lo que citan.
2. **La alarma cuenta como deriva de código los commits que solo añaden frontmatter**, y esos por
   construcción no pueden volver falsa una página. Es lo que la hizo saltar aquí.

El segundo **queda escalado y sin tocar**: cambiar lo que mide una alarma no se decide desde el
frente que la disparó, y menos cuando el arreglo le vendría bien a ese frente.

### Lo que la Fase 0b deja abierto

Todo anotado en [[cola-de-pendientes]], para que no se pierda al marcarla hecha: los plugins de
comunidad, los grupos de color del grafo, enchufar `--estricto` al gate, los tres archivos
congelados por contrato y los ocho `goal.md` que son andamiajes sin objetivo escrito.
