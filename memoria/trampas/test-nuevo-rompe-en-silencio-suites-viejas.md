---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-08-06
areas: [qa]
fuente: tests/browser/support/session.mjs, commit ca411b65
resumen: "Añadir una validación nueva de servidor rompe en silencio las suites E2E que posteaban directo — o peor, sus aserciones de mensaje quedan muertas sin fallar nunca"
---
# Una validación nueva de servidor puede romper en silencio las suites viejas

Al cerrar [[regla-solo-en-cliente-no-es-regla]] el 2026-08-06 (CSRF real en seis módulos que antes
no lo exigían), aparecieron dos fallos que ninguna corrida en rojo había avisado:

**`tests/browser/support/session.mjs` solo adjuntaba el token CSRF a dos prefijos de API**: las
peticiones de test contra los módulos recién blindados posteaban sin token y el servidor las
rechazaba — un fallo real, pero indistinguible a simple vista de un fallo de la funcionalidad en sí
si no se lee el mensaje de error. Y **dos tests comparaban el mensaje de error contra una subcadena
que el servidor nunca decía**: uno esperaba `'CSRF'` y el servidor respondía `'Token de seguridad
inválido'`; otro esperaba `'pasado'` y el servidor decía `'pasadas'`. Como la comparación nunca
coincidía con el mensaje real ni con el error que se esperaba evitar, ambas aserciones aprobaban
siempre, sin importar si la validación de servidor existía o no. Corregido en `ca411b65`.

## Por qué es peligroso

El segundo caso es el que importa: un test verde no significa que el comportamiento esté cubierto.
Una aserción de substring contra un mensaje de error es una prueba muda si la cadena no calza con lo
que el servidor realmente dice — pasa igual de verde con la validación presente o ausente.

## Cómo no caer

Al añadir una validación nueva de servidor (permiso, CSRF, candado de fecha, lo que sea):

Corre la suite E2E completa antes de dar por cerrado el hallazgo, no solo el flujo feliz. Si un test
compara el mensaje de error, verifica la cadena exacta contra la respuesta real del servidor
(`grep` del texto en el controlador/helper que lo emite), no una paráfrasis recordada. Y revisa los
helpers compartidos de fixtures (como `session.mjs`) para confirmar que cubren todos los prefijos de
ruta que la validación nueva ahora exige, no solo los que existían cuando se escribió el helper.

Mapas: [[qa-y-gates]] · vecina: [[regla-solo-en-cliente-no-es-regla]].
