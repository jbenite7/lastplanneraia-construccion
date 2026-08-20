---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-08-18
areas: [proceso]
fuente: goals/reserva-redundante-green-dark/goal.md
resumen: Andamiaje del frente reserva-redundante-green-dark: el goal.md se creo y su objetivo nunca se escribio.
---

# Frente: reserva-redundante-green-dark

## Objetivo
Retirar la reserva de `--aia-green-dark` en `handsontable-module.css:758`, que nunca se evalúa
porque el token existe y resuelve al mismo valor.

## Condición de hecho
<!-- qué comando, con qué salida, prueba que el frente terminó -->

## Archivos declarados
public/css/handsontable-module.css

## Contención
<!-- archivo · commits hoy · quién más lo declara -->

## Cadena de herramientas
<!-- ids del arsenal, máx 8, una línea de porqué cada uno -->

## Cierre

Frente **ejecutado el 2026-08-11**; sección escrita el 2026-08-19.

```
$ sed -n '758p' public/css/handsontable-module.css
  background: var(--aia-green-dark); /* Verde Oscuro Oficial AIA */
```

Sin reserva. **La justificación buena y la mala quedaron ambas escritas**, que es lo que vale: el
token está definido y resuelve al **mismo** valor, luego la reserva no se evalúa nunca. La que **no**
vale, marcada como tal: que un token *vecino* se consuma sin reserva no prueba nada sobre este,
porque la regla es por token.

De paso corrigió una afirmación que ya había viajado — que retirar esta reserva dejaría el archivo
«sin literales de color». Quedan **47**, medidos.
