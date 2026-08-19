---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-08-18
areas: [proceso]
fuente: goals/buttons-important-leyenda/goal.md
resumen: Andamiaje del frente buttons-important-leyenda: el goal.md se creo y su objetivo nunca se escribio.
---

# Frente: buttons-important-leyenda

## Objetivo
Retirar de la leyenda los `!important` que no ganan nada, midiendo el valor **computado** en las
tres pantallas en vez del declarado en la hoja.

## Condición de hecho
<!-- qué comando, con qué salida, prueba que el frente terminó -->

## Archivos declarados
public/css/buttons.css

## Contención
<!-- archivo · commits hoy · quién más lo declara -->

## Cadena de herramientas
<!-- ids del arsenal, máx 8, una línea de porqué cada uno -->

## Cierre

Frente **ejecutado el 2026-08-11**; esta sección se escribe el 2026-08-19 porque nadie la llenó al
terminar. La evidencia es de hoy, no de entonces — no se finge una sesión que ya no existe.

```
$ grep -c '!important' public/css/buttons.css
138
```

Coincide exactamente con lo que el frente declaró: 160 → 138 en el archivo, y 41 → 16 sobre la
leyenda. Lo reutilizable del método: **quitar los `!important` en bloque, medir el computado y
reponer solo los que se movieron**, en vez de ir uno a uno. De las 16 supervivientes, ninguna hace
falta en las tres pantallas a la vez.

Dos trampas suyas siguen valiendo más que la resta: un contenedor lanzado con `docker run` **no
existe para `docker compose`**, así que Playwright corría contra el árbol principal mientras el
navegador veía el worktree; y `git stash` sin cambios locales **no guarda nada y no falla**, así que
un «antes» medido con `stash`/`pop` devuelve el mismo árbol presentado como pasado.
