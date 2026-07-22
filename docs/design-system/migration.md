# Migracion del design system

> Este orden describe migraciones posteriores. Sprint 00 homologa familias en el
> laboratorio y migra exclusivamente `/programa-general`; no reabre otros módulos.

## Orden aprobado

1. Fundacion obligatoria.
2. Shell global y navegacion.
3. Login.
4. Projects.
5. Programa General.
6. Programacion Intermedia.
7. Programacion Semanal.
8. PDC.
9. Contratos.
10. Listado de Actividades.

## Criterio de modulo migrado

Un modulo esta migrado cuando cumple:

- Usa tokens `--ds-*` o `--aia-*`.
- No agrega CSS inline visible salvo excepcion.
- No agrega hex sueltos salvo excepcion.
- Funciona en desktop y mobile.
- Funciona en linen y dark.
- Mantiene focus visible y targets principales de 44px.
- No expone run IDs, payloads ni trazas crudas a usuarios no admin.
- Tiene evidencia de navegador o Playwright.

## Estado del gate

- Login y Projects tienen contrato de migracion cubierto por Playwright y presupuesto cero por ruta en `exceptions.json`.
- Programa General tiene contrato de navegador y presupuesto cero por ruta en `exceptions.json`; su CSS de grilla vive en `public/css/programa-general.css`.
- PDC tiene contrato de navegador y presupuesto cero por ruta en `exceptions.json`; sus reglas propias viven en `public/css/pdc.css`.
- Contratos tiene contrato de navegador y presupuesto cero por ruta en `exceptions.json`; sus reglas propias viven en `public/css/contratos.css`.
- Listado de Actividades tiene contrato de navegador y presupuesto cero por ruta en `exceptions.json`; sus reglas propias viven en `public/css/listado-actividades.css`.
- `public/js/modules/aia_ui/theme.js` tambien tiene presupuesto cero para evitar deuda visual en la API de tema.
- Programacion Intermedia y Programacion Semanal tienen contrato base cubierto por Playwright: carga de assets canonicos, tema linen/dark, ausencia de overflow de pagina, grilla desktop alineada al contenedor y fallback mobile presente.
- PDC, Contratos y Listado de Actividades tienen contrato base cubierto por Playwright: carga de assets canonicos, tema linen/dark, ausencia de overflow de pagina y tabla/grilla visible en desktop/mobile.
- Programacion Intermedia tiene manifiesto (`programacion-intermedia.json`), goldens dark 1180x820/1440x900 y theming por tokens con variantes dark; su deuda restante (skin claro del editor Tom Select y `!important` heredados) queda inventariada en las excepciones del manifiesto. Aun no declara presupuesto cero por ruta en `exceptions.json`.
- PS aun no se declara migrado en `exceptions.json`; conserva deuda legacy de estilos embebidos, hex y radios que debe reducirse por lote antes de cerrar el objetivo.

## Compatibilidad legacy

El legacy puede convivir mientras esta inventariado. Al tocar una vista prioritaria, mover estilos embebidos al sistema o registrar una excepcion temporal con razon y fecha.
