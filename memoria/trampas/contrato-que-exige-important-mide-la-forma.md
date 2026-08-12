---
tipo: trampa
estado: vigente
fecha: 2026-08-12
areas: [design-system, qa]
fuente: tests/test_programa_general_sprint_contract.mjs:150, medicion en navegador sobre /programa-general
resumen: un contrato que exige `!important` en la hoja mide la forma y no el resultado — obliga a reponer prioridad que la cascada no necesita, y sólo el computado del navegador dice si el valor gana
---

`tests/test_programa_general_sprint_contract.mjs` exigía que `.pdc-legend-item` declarase
`display`, `white-space`, `overflow-wrap` y `word-break` **con `!important`**. El objetivo escrito
de esa aserción era que el chip envolviera entre palabras sin fragmentarlas — y eso lo dan los
**valores**, no la prioridad.

La consecuencia práctica: cuando `0a228a39` midió el computado de los dieciséis `!important` de la
leyenda y repuso solo los seis que hacían trabajo, el contrato se puso rojo. Exigía deshacer una
resta bien medida. El archivo llevaba en rojo desde el **2026-07-17**, y esta era la última de sus
28 aserciones en caer.

## Lo que la hoja de estilo no puede probar

Que las declaraciones estén escritas **no prueba que ganen**. Eso solo lo dice el navegador.
Medido el 2026-08-12 en `/programa-general`, 1180×820, tema dark, sesión abierta por la puerta de
servicio:

- **7 elementos `.pdc-legend-item`** encontrados. El recuento se declara a propósito: **cero
  elementos no es «no aplica», es «no lo encontraste»**, y un contrato que no casa con nada pasa
  igual de verde.
- En los 7, computado: `white-space: normal`, `overflow-wrap: normal`, `word-break: normal`.
  **Sin ningún `!important`.** La resta era correcta.
- Efecto lateral medido de paso: `display` computa **`flex`**, no el `inline-flex` que declara
  `public/css/buttons.css:977`. Alguna regla posterior lo gana. El chip se ve bien y no se tocó,
  pero la declaración de la hoja no describe lo que pasa en pantalla — que es justo el punto de
  esta página.

Antes de medir hay que comprobar que el CSS **servido** por el contenedor es el mismo que analiza
el test: con varios worktrees vivos no es automático (`shasum` de los dos archivos), o se mide un
árbol y se concluye sobre otro — ver [[suite-estatico-mide-dos-arboles]].

## Cómo se escribe la aserción

Contra los valores, tolerando el `!important` en vez de exigirlo, y **acotada al bloque** del
selector en lugar de una regex larga sobre el archivo entero: así el fallo señala qué propiedad
falta. Y se comprueba que muerde, borrando una de las declaraciones del CSS: si el test sigue
verde, la aserción no protege nada.

Un aviso sobre esa mutación, porque dio un falso «la aserción es laxa»: `perl -0pi -e 's/…/…/'`
sustituye la **primera** ocurrencia del archivo. `overflow-wrap: normal; word-break: normal;`
aparece también en el `:where(…)` de `buttons.css:25`, así que la mutación cayó ahí y el chip
quedó intacto. Mutar por número de línea del bloque correcto sí mata el test.

Familia: [[valor-declarado-no-es-valor-computado]] —de la que esta es el caso aplicado a un
contrato de test—, [[gate-solo-cuenta-elementos-no-los-lee]],
[[mutar-el-supuesto-no-solo-las-entradas]], [[pdc-legend-item-clase-compartida]],
[[css-layer-cascade]].
