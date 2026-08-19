---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-08-18
areas: [proceso]
fuente: goals/severidad-runtime/goal.md
resumen: Andamiaje del frente severidad-runtime: el goal.md se creo y su objetivo nunca se escribio.
---

<!-- cas:cita-textual — registro del frente: cita salidas y comandos tal como se midieron -->
# Frente: severidad-runtime

## Objetivo
Aplicar la decisión del usuario sobre el desempate por matiz: el matiz desempata en todos los
niveles menos en el crítico.

## Condición de hecho
<!-- qué comando, con qué salida, prueba que el frente terminó -->

## Archivos declarados
-

## Contención
<!-- archivo · commits hoy · quién más lo declara -->

## Cadena de herramientas
<!-- ids del arsenal, máx 8, una línea de porqué cada uno -->

## Cierre

**Cerrado sin ejecución propia: al verificar los cuatro puntos antes de tocar nada, ninguno se
sostenía.** El trabajo ya estaba hecho.

```
$ git merge-base --is-ancestor 82832685 origin/main && echo publicado
publicado
$ git log -1 --format='%h %s' 82832685
82832685 fix(design-system): el matiz desempata en todos los niveles menos en el critico
$ grep -n 'critico' public/css/design-system/components/states-feedback.css | head -1
149:     niveles MENOS en el critico. Va aqui, pegada a las reglas que gobierna,
```

La excepción vive pegada al eje que gobierna, con su porqué (el nivel crítico es el único que no
admite ambigüedad) y su nota de especificidad (0,3,0 para ganar a las reglas de matiz, que pesan
0,2,0). El test comprueba **más** que el anterior, no menos.

**Y de aquí sale por qué el gate `runtime-budgets` sigue `blocked`**, que es lo único que quedaba:
el recibo de `closeout-evidence.json` es **anterior al arreglo** —registrado a las 13:08, el arreglo
entró a las 13:48—. El gate no está rojo por algo pendiente: está rojo **porque nadie lo volvió a
medir después de arreglarlo**. Eso es lo que persigue [[goals/runtime-budgets-al-ci/goal|runtime-budgets-al-ci]].

El frente **no actuó, a propósito**: rehacer trabajo hecho habría sido peor que no hacer nada.
