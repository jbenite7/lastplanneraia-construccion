---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-08-18
areas: [proceso]
fuente: goals/contador-no-mide-el-archivo/goal.md
resumen: Andamiaje del frente contador-no-mide-el-archivo: el goal.md se creo y su objetivo nunca se escribio.
---

<!-- cas:cita-textual — registro del frente: cita salidas y comandos tal como se midieron -->
# Frente: contador-no-mide-el-archivo

## Objetivo
Entender por qué el hook de diseño reportaba 40, 38, 37, 22, 2 y 1 hallazgos sobre el mismo
archivo sin que el archivo cambiara acorde.

## Condición de hecho
<!-- qué comando, con qué salida, prueba que el frente terminó -->

## Archivos declarados
memoria/trampas/contador-que-baja-porque-ya-lo-miraste.md

## Contención
<!-- archivo · commits hoy · quién más lo declara -->

## Cadena de herramientas
<!-- ids del arsenal, máx 8, una línea de porqué cada uno -->

## Cierre

Frente **ejecutado el 2026-08-11**; sección escrita el 2026-08-19.

```
$ ls memoria/trampas/el-contador-no-mide-el-archivo.md
memoria/trampas/el-contador-no-mide-el-archivo.md
$ npm run test:wiki
RC=0   Sin hallazgos (modo estricto)
```

La causa se comprobó ejecutando el hook **dos veces seguidas sobre el mismo contenido: la segunda
devuelve vacío.** Guarda estado de sesión y suprime lo ya señalado, así que el total describe
cuántas veces se disparó antes, no el archivo.

**Lo grave no es el número, es su forma.** Tenía forma de dato reproducible —salía de una
herramienta, citaba líneas y reglas ciertas— y se usó como indicador de avance del programa de
cierre, viajando en informes durante un día entero entre dos sesiones. El criterio que deja:
**antes de usar un número como indicador de avance, ejecútalo dos veces sobre el mismo contenido y
comprueba que da lo mismo.** Si no coincide, no mide el mundo: mide el estado de quien pregunta.
