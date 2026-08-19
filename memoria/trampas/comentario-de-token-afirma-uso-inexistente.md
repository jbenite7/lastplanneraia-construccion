---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-08-03
areas: [design-system, qa]
fuente: sesion
resumen: "Un comentario de tokens.css afirmaba un uso que el consumidor real nunca ejerció: el exportador a Excel existe pero montó su propia paleta en paralelo"
---
`public/css/tokens.css:263` rotula ocho tokens (los ocho, en `:273-280`) como «Valores de soporte para impresos /
XLSX»:

```
--ds-color-state-{success,warning,critical,info}-{bg,text}-light
```

Un grep sobre todo el repositorio devuelve **solo sus propias declaraciones**. Ningún
`.css`, `.php`, `.js` ni `.ts` los consume.

Y el giro: **el consumidor que el comentario invocaba sí existe.**
`src/Controllers/Gestion/ReportController.php` exporta con PhpSpreadsheet y pinta el
estado de cada fila. Pero no llegó a cablearse: lleva su propia paleta ARGB escrita a
mano —y con valores distintos de estos ocho—. O sea que hay dos paletas claras para lo
mismo, y el comentario describía una integración que alguien planeó y nadie terminó.

**Why:** el comentario se lee como documentación de una integración existente, y en un
archivo que el repo trata como fuente de verdad eso pesa. Costó un error real: el
frontmatter de `DESIGN.md` cargaba los cuatro pares claros como si fueran la paleta de
pantalla, en una aplicación que es dark-only por contrato. Quien siguiera el contrato de
consumo pintaba pasteles claros sobre penumbra. Ver [[design-system]].

**How to apply:** un comentario de `tokens.css` describe **intención**, no consumo. Antes
de citarlo como contrato —y sobre todo antes de copiar sus valores a otro archivo— grepea
el token y comprueba quién lo usa de verdad. Si no lo usa nadie, el token es una de dos
cosas y conviene decir cuál: reserva declarada a la espera de su consumidor, o peso
muerto.

**Resuelto el 2026-08-03 (decisión del usuario):** los ocho se quedan y el comentario
pasa a decir la verdad —reserva sin cablear, con el exportador y su paleta paralela
nombrados—. Unificar las dos paletas cambiaría el aspecto de los Excel que la gente ya
recibió, así que es decisión aparte y no se toma de oficio.

El mismo filo que [[gate-estatico-no-ve-tokens-rotos]], en la otra dirección: allí un
token bien escrito no resolvía; aquí un token que resuelve perfectamente no lo pide nadie.
Ninguna de las dos cosas la ve leer el archivo con buena fe.
