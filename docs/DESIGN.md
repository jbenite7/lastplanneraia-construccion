---
capa: fuente
tipo: contrato
estado: vigente
fecha: 2026-07-17
fuente: docs/DESIGN.md
resumen: El contrato canónico de tokens y componentes vive en design-system/. Este documento concreta la superficie del laboratorio interno.
---

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

## Tipografía operativa

- La jerarquía usa la escala fija `--ds-type-size-3xl` a `--ds-type-size-xs`; no se introducen tamaños fluidos dentro del laboratorio.
- Montserrat se reserva para H1–H4. Inter cubre controles, datos, ayudas y texto de trabajo con `--ds-type-line-body`.
- Los párrafos explicativos se limitan a 72 caracteres aproximados y las tablas, conteos, porcentajes y fechas usan cifras tabulares.
- Los metadatos breves no dependen de mayúsculas espaciadas para crear jerarquía; chips, peso y proximidad comunican prioridad.

## Topologías operativas

El laboratorio usa tres topologías con una identidad común. La topología responde al trabajo y a la densidad de datos, no crea una variante visual del producto.

- **Control room:** comparación de cobertura, riesgo y actividad. Aloja selector de proyectos, contexto semanal, notificaciones, Admin y BI.
- **Banco de trabajo:** edición, validación, guardado y recuperación. Aloja grillas, revisión semiautomática y adaptadores de entrada.
- **Explorador de contratos:** secuencia, historial y control de calidad. Aloja drawer LPS, autenticación/sesión y contratos de tabla.

La cabecera permanece sticky. La rail mantiene su propio scroll solo cuando la lista excede el alto disponible y empieza después de la cabecera; el contenido de la familia es el propietario principal del scroll vertical.

## Primitivas del laboratorio

### Suite operativa

Una suite agrupa objetos P1/P2 de la familia activa. No agrega familias nuevas ni duplica componentes del catálogo.

Estados requeridos: `default`, `loading`, `empty`, `error`, `success` y los estados de dominio declarados por cada objeto. El selector de estados es un grupo de botones con `aria-pressed`; el resultado se anuncia mediante una región `role="status"`.

### Fixture operativo

Cada fixture expone título, prioridad, topología, consumidores reales, estado actual y una muestra interactiva. La muestra utiliza controles nativos o el adaptador canónico correspondiente; nunca una captura rasterizada.

Estados interactivos mínimos: hover, focus-visible, active y disabled. Toda mutación simulada ofrece feedback explícito y, cuando sea reversible, una acción de deshacer.

### Matriz y banco de trabajo

Las tablas conservan encabezados semánticos y una región desplazable enfocada cuando la densidad obliga a scroll horizontal. Selección, edición y acciones por fila no dependen solo del color. Los estados guardando, guardado, error y revertido se mantienen visibles en el mismo contexto.

### Portafolio analítico

`aia-bi` cubre comparación, serie temporal, embudo, gauge, radar, resumen de métricas, ranking y matriz de decisión. Las métricas se expresan en texto y cifras tabulares; los gráficos incluyen un resumen y datos equivalentes. El ranking prioriza causas por cantidad y la matriz vincula prioridad con acción y responsable. El embudo expone detalle contextual en hover y foco visible, mientras que sus datos equivalentes siguen disponibles sin interacción.

### Timeline contractual

La secuencia usa una lista ordenada con estado textual para cada hito. Aqua marca selección o momento actual; naranja señala riesgo que requiere acción. La línea es apoyo visual y nunca sustituye el texto.

## Alcance P1/P2

El contrato verificable vive en `docs/design-system/operational-fixtures.json`. P1 cubre diez objetos operativos; P2 cubre Tom Select avanzado y calendario enriquecido. La implementación y validación de esta expansión se limita a desktop dark en 1180 × 820 o superior.
