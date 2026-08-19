---
capa: fuente
tipo: guia
estado: vigente
fecha: 2026-07-15
areas: [design-system]
fuente: docs/design-system/components.md
resumen: Catalogo de componentes aia-*: superficies, controles y estados, con la clase canonica de cada uno.
---

# Componentes AIA

## Superficies

- `aia-shell`: fondo de aplicacion y contrato tipografico.
- `aia-page`: contenedor responsive de pagina.
- `aia-card` / `aia-panel`: superficies de contenido.
- `aia-glass`: material translucido controlado para jerarquia.
- `aia-table-shell` / `aia-grid-shell`: contenedores para DataTables y Handsontable.

## Controles

- `aia-btn`: accion primaria.
- `aia-btn--secondary`: accion secundaria.
- `aia-btn--construction`: accion de dominio construccion.
- `aia-input`, `aia-select`, `aia-textarea`: formularios.

## Estados

- `aia-chip`: estado informativo.
- `aia-chip--success`: avance o correcto.
- `aia-chip--warning`: requiere revision.
- `aia-chip--critical`: bloqueo, riesgo o error.
- `aia-alert`: mensaje contextual.
- `aia-empty`: estado vacio.

### Mapa de acción

Los módulos pueden conservar sus etiquetas de dominio, pero el nivel visual es común:

- `info`: contexto o falta de datos, sin corrección inmediata.
- `success`: controlado o completado, continuar el ciclo normal.
- `warning`: atención, revisar antes del siguiente hito.
- `critical`: acción inmediata, bloqueo, atraso, riesgo o error recuperable.

La gravedad y la urgencia se expresan con `data-aia-severity` y `data-aia-urgency`; el color refuerza el significado, nunca lo sustituye. Los estados `now` siempre usan `critical` y los estados sin datos no escalan a warning por sí solos.

## Regla

Todo componente nuevo o migrado debe usar estos nombres o registrar una excepcion temporal.
