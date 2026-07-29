# Hechos — Tanda 2: que el Plan de compras diga la verdad

Derivados del grilleo del 2026-07-29 (`interview-result.json`, 14 preguntas, 13 recomendaciones
aceptadas y una divergencia deliberada). Cada hecho es una frase verificable.

## H4 — cobertura del Plan

- **f01** El encabezado del Plan muestra la cobertura **por valor** como número grande, en porcentaje.
- **f02** Debajo se lee el conteo: «N de M paquetes con fecha».
- **f03** El total M cuenta **solo** los paquetes cuya modalidad genera proceso de contratación
  (`contrato` y `orden_compra`). Nómina, imprevistos y consumo directo no entran.
- **f04** Un paquete amarrado a un frente pero todavía sin recalcular **cuenta como cubierto**.
- **f05** Cuando hay paquetes en ese estado, un aviso aparte dice cuántos son y que falta «Recalcular».
- **f06** La barra de cobertura del Plan reutiliza el mismo componente visual que la de Paquetes de
  contratación: quien pasa de una pantalla a otra ve la misma forma.
- **f07** Los contadores que ya existían (`N paquete(s)`, `N vencido(s)`, `N con duración estimada`)
  siguen visibles y no cambian de significado.

## H12 — franja de vencidos

- **f08** Con al menos un paquete vencido, aparece una franja de alerta **sobre** la tabla del Plan.
- **f09** La franja dice cuántos son y cuántos días lleva el más atrasado.
- **f10** Un clic en la franja filtra la tabla para mostrar **solo** los vencidos.
- **f11** Con el filtro puesto, la franja ofrece quitarlo y vuelven todas las filas.
- **f12** La franja se puede cerrar; vuelve a aparecer al recargar la página.
- **f13** Sin paquetes vencidos, la franja no aparece.
- **f14** El filtro de vencidos no altera el orden de la tabla (lo vencido ya iba primero).

## H8 — aceptar propuestas por confianza

- **f15** La pestaña «Sin frente» muestra el desglose de propuestas por confianza: cuántas altas,
  cuántas medias, cuántas bajas.
- **f16** El botón principal acepta **solo** las de confianza alta y dice cuántas son.
- **f17** Un botón secundario, visualmente menos llamativo, acepta las de confianza media.
- **f18** El botón de las medias **no escribe nada** hasta que el usuario confirma.
- **f19** La confirmación muestra un resumen: cuántas propuestas son y cuánta plata suman.
- **f20** La confirmación incluye la lista de qué paquete va a qué frente, **plegada** por defecto.
- **f21** Las propuestas de confianza **baja** no las acepta ningún botón masivo; solo una a una
  desde su fila.
- **f22** Si no hay ninguna propuesta de confianza alta, el botón principal no invita a pulsarlo.
- **f23** Aceptar en masa sigue respetando el frente que el usuario haya cambiado a mano en la fila,
  y sigue sin acreditar al motor cuando el destino elegido no coincide con su propuesta.

## H7 — comparador

- **f24** «Ahorros» se muestra **sin** signo menos.
- **f25** El Δ total conserva su signo.
- **f26** Una línea explica que Δ = sobrecostos − ahorros.

## H5/H6 — texto y pantallas angostas

- **f27** Ninguna celda parte una palabra por la mitad.
- **f28** El ancho mínimo de cada columna de texto alcanza para su palabra más larga.
- **f29** Por debajo de 1200 px de ancho, las columnas secundarias de Paquetes se esconden.
- **f30** «Destino» y «Sugerencia» siguen visibles a 1024 px — son lo que se viene a mirar.

## Regresión

- **f31** Los 14 e2e `pdc-v2-*.spec.mjs` siguen pasando.
- **f32** Vitest y `npm run build` en verde.

## Decisiones del grilleo que conviene recordar

1. **Cobertura en plata como número grande, conteo debajo** — no una sola de las dos.
2. **La divergencia:** en pantallas angostas se decidió **esconder columnas secundarias**, no poner
   scroll lateral. Mi recomendación era el scroll; el dueño del producto eligió esconder. Se aplica
   tal cual, escondiendo «Agrupación» y «Recurso», que es lo prescindible en esa pantalla.
3. **Solo SPA**, sin tocar PHP: todos los números ya llegan al navegador.
