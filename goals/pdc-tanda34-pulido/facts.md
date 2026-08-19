---
capa: fuente
tipo: evidencia
estado: vigente
fecha: 2026-07-28
areas: [pdc]
fuente: goals/pdc-tanda34-pulido/facts.md
resumen: Del grilleo del 2026-07-29 (interview-result.json, 14 preguntas, las 14 recomendaciones aceptadas sin divergencias). Todo en la SPA: ninguna migración, ningún…
---

# Hechos — Tandas 3 y 4: la primera impresión y el pulido

Del grilleo del 2026-07-29 (`interview-result.json`, 14 preguntas, las 14 recomendaciones aceptadas
sin divergencias). Todo en la SPA: ninguna migración, ningún endpoint.

## Tanda 3 — la primera impresión

- **f01** El cargador de Excel es un control propio, en español, con el estilo del módulo.
- **f02** Se puede soltar el archivo sobre una zona de arrastre.
- **f03** Elegido el archivo, su nombre queda a la vista.
- **f04** Con 0 pendientes, el Maestro abre en «Catálogo global».
- **f05** Con pendientes, el Maestro sigue abriendo en «Pendientes por vincular».
- **f06** Con el 100 % del valor asignado, Paquetes muestra arriba un mensaje de cierre que dice
  qué queda suelto y cuánto vale.
- **f07** En ese caso las barras de controles arrancan plegadas, y un control las despliega.
- **f08** Ninguna tabla vacía muestra «No Rows To Show».
- **f09** Cada tabla vacía explica qué significa estar vacía en esa pantalla.
- **f10** No queda ningún «(s)» de plural en la interfaz.
- **f11** Los conteos de cuatro cifras o más llevan separador de miles.
- **f12** Junto a cada una de las tres cifras de insumos (820 / 396 / 3.079) hay una línea que dice
  qué cuenta esa cifra.

## Tanda 4 — el pulido

- **f13** Un paquete de modalidad `no_contratable` o `consumo_directo` no muestra badge de tipo de
  negociación: en ellos el tipo no aporta y además miente («Nómina de obra · CONSUMIBLES»).
- **f14** La modalidad se sigue mostrando en esos paquetes: es la etiqueta que sí es cierta.
- **f15** «Retirar» un insumo del catálogo pide confirmación antes de escribir.
- **f16** La confirmación dice cuántos vínculos automáticos se van a revertir.
- **f17** La columna de acción del catálogo tiene cabecera, no un hueco.
- **f18** «Paquetes con insumos» tiene buscador.
- **f19** «Sin frente» tiene buscador.
- **f20** El comparador tiene buscador sobre la tabla de diferencias.
- **f21** La nota de «Recalcular» arranca plegada tras un «¿qué conserva?», y su texto no cambia.
- **f22** Las filas de «Sin frente» alinean sus columnas entre sí: nombre, cuantía, selector, botón
  y badge caen siempre en la misma posición.
- **f23** En el asistente, los botones de decidir se ven sin desplazar la página a 1440×900.
- **f24** «Acierto del motor» dice sobre cuántas decisiones se mide.

## Regresión

- **f25** Vitest y `npm run build` en verde.
- **f26** Los 14 e2e `pdc-v2-*.spec.mjs` siguen pasando.

## Lo que deliberadamente NO se hace

**No se corrige el dato de `tipo_negociacion`** de «Nómina de obra» e «Imprevistos y provisiones».
Vive en `general_paquetes_contratacion`, un catálogo que comparten todos los proyectos de AIA:
una migración cambiaría obras que no estamos mirando. Se esconde donde miente (f13) y el dato queda
anotado para revisarse aparte, con su propio alcance.
