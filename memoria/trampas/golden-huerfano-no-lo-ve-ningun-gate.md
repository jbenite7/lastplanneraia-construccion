---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-08-12
areas: [design-system, qa]
fuente: sesión del 2026-08-12, retiro de los goldens `linen` (commit 7f76015a)
resumen: los gates anclan cada golden desde su manifiesto, así que un PNG que ya no declara nadie no rompe nada y puede sobrevivir meses al borrado de su tema
---
En lps-aia, un golden solo existe para los gates si **un manifiesto lo declara**. `scripts/design-system-contracts.mjs`
recorre `docs/design-system/manifests/`, valida `golden` + `sha256` de cada escenario y comprueba
que la ruta caiga bajo `GOLDEN_ROOTS` (`tests/browser/__screenshots__/`, `:986`). Nunca lista el
directorio para preguntar lo contrario: **qué PNG hay ahí que nadie declare**. La suite visual va en
el mismo sentido — `tests/browser/design-system-lab.visual.mjs:167` captura escenario por escenario
desde el manifiesto.

La consecuencia es que **un golden huérfano es invisible en las dos direcciones**: no lo compara
ningún test y no lo denuncia ningún contrato. El tema `linen` se retiró del producto el 2026-07-25
(DS-030), sus esquemas y contratos quedaron limpios —hasta con una prueba dedicada,
`tests/design-system/linen-removal.test.mjs`— y aun así **18 PNG con `linen` en el nombre siguieron
versionados dieciocho días** en `tests/browser/__screenshots__/design-system-lab.visual.mjs/`. Los
39 goldens anclados son todos `dark`. Retirarlos no movió una sola aserción: static 8/8 y
`test:visual:lab` `20 passed`, igual que antes.

**Cómo aplicarlo:** al retirar un tema, un viewport o una familia, el borrado de los goldens es un
paso **explícito** de la limpieza, porque ninguna suite va a echarlos de menos. Y a la inversa: que
un PNG esté en `__screenshots__` no prueba que se compare — antes de fiarte de él como evidencia,
búscalo en los manifiestos. Es el mismo hueco que [[gate-solo-cuenta-elementos-no-los-lee]] señala
desde el otro lado, y el reverso de [[manifiesto-ds-exige-golden]]: el manifiesto exige que el PNG
exista, pero nada exige que el PNG tenga manifiesto.

Un juego sí está anclado por hash y **no** es huérfano pese a estar en el mismo árbol: el de CI en
`__screenshots__/design-system-lab.visual.mjs/linux/`, que añadió `D-GAC-4` para separar el render
por plataforma ([[visual-baselines-estado-real]]).
