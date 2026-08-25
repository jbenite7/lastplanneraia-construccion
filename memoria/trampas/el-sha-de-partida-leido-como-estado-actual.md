---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-08-25
areas: [deploy, proceso]
fuente: memoria-claude
resumen: "«Producción sigue en 1aa7c69» era el sha DE PARTIDA del release del 2026-08-12 leído como estado actual; el error se copió a cuatro documentos y sobrevivió trece días"
---

# El sha de partida, leído como estado actual

Durante trece días, cuatro documentos del repo afirmaron que **producción no se había tocado**:

> lastplanneraia.com sigue en `1aa7c69` del 2026-07-16, con cero tablas `pdc_*`.

Era falso desde el **2026-08-12**, y `memoria/referencias/produccion-deploy.md` lo desmentía en su
propia página desde ese mismo día: producción pasó de **`1aa7c694` a `939b7928`** en un solo
`git pull --ff-only` — 1.763 commits, con el Plan de Compras dentro (16 entradas `Services\Pdc` en
el classmap donde había cero).

**`1aa7c69` no era donde estaba producción: era de donde salió.** El sha de origen del release,
leído como el estado de llegada.

## Por qué sobrevivió tanto

Porque **se copió antes de comprobarse, y cada copia parecía una confirmación**. La cadena va así:
la spec `despliegue-pdc-v2-produccion` lo escribió en su línea de estado; el informe de auditoría
del 2026-08-20 lo tomó de la spec; `IMPLEMENTATION_PLAN_INVENTORY.md` lo tomó del informe; y el
encargo de verificación del 2026-08-25 lo tomó del inventario, ya convertido en premisa.

Para el cuarto lector el dato venía de tres sitios independientes. No lo eran: eran el mismo dato
cuatro veces. **Lo destapó el dueño del producto**, en una frase, al leer una recomendación de
negocio construida encima — «¿pero qué dices? el PDC ya está desplegado hace semanas».

## La regla que sale de aquí

**El estado de un servidor se mide contra el servidor.** Ni contra una spec, ni contra un
inventario, ni contra el informe que citó al inventario. Si no hay acceso para medirlo, la
afirmación correcta no es un sha: es «no verificado desde <fecha>».

Y al escribir un sha en prosa, **diga siempre de qué lado del cambio está**: «de `A` a `B`» no se
puede malinterpretar, «sigue en `A`» sí.

Es la misma familia que [[el-trabajo-hecho-no-vuelve-solo-al-documento]] —trabajo real que el
documento no recogió— pero con un agravante propio: aquí el documento **sí** tenía la verdad
escrita, en la wiki, y nadie la cruzó. Relacionado:
[[memoria/conceptos/condicion-retirada-no-es-condicion-incumplida]].
