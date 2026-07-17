# Laboratorio AIA: contrato de diseño

El contrato canónico de tokens y componentes vive en [`design-system/`](design-system/README.md). Este documento concreta la superficie del laboratorio interno.

## Alcance Foundations-first

- Escritorio dark: el laboratorio se revisa en 1180 × 820 o superior.
- La rail lista las diez familias desde el inicio; cada destino declara número de grupos y estado.
- Fundamentos muestra color, tipografía, densidad e inventario visible antes de los demás patrones.
- La densidad se controla mediante un grupo nativo de radios; compacta es el valor inicial para escritorio.

## Accesibilidad

- Los destinos de familia son enlaces reales con URL compartible, historial y `aria-current` solo en el destino actual.
- Todos los controles y destinos conservan un mínimo de 44 px y foco visible de contraste suficiente en dark.
- Las muestras de densidad son comparativas: no se anuncian como página, pestaña ni elemento actual.
- Los tamaños de encabezado descienden de H1 a H4 y el contenido usa Inter mientras la jerarquía usa Montserrat.
