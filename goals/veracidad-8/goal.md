---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-08-18
areas: [proceso]
fuente: goals/veracidad-8/goal.md
resumen: Andamiaje del frente veracidad-8: el goal.md se creo y su objetivo nunca se escribio.
---

<!-- cas:cita-textual — registro del frente: cita salidas y comandos tal como se midieron -->
# Frente: veracidad-8

## Fase del plan
Plan: -
Fase: -
Sha verificado: ?

## Objetivo
Correr el octavo pase de la operación `veracidad` de la wiki: verificar contra el código que lo
escrito sigue siendo cierto, por rotación de áreas.

## Condición de hecho
<!-- qué comando, con qué salida, prueba que el frente terminó -->

## Archivos declarados
memoria/,docs/wiki-operacion.md

## Contención
<!-- archivo · commits hoy · quién más lo declara -->

## Cadena de herramientas
<!-- ids del arsenal, máx 8, una línea de porqué cada uno -->

## Cierre

Frente **ejecutado**; sección escrita el 2026-08-19.

```
$ grep -c 'Octavo pase' memoria/log.md
1
$ grep -c '· veracidad ·' memoria/log.md
14
```

El octavo pase está en la bitácora y desde entonces se corrieron seis más — el undécimo y los
posteriores lo dejan holgadamente superado. **El frente no tiene nada pendiente porque su unidad de
trabajo es el pase, y el pase se hizo.**

Que un frente de este tipo quede sin cerrar en el papel es previsible: su producto es una línea en
`log.md`, no un archivo nuevo, y nada obliga a volver al `goal.md`.
