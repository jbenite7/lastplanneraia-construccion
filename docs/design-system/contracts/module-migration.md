# Contrato de migración por módulo

Cada migración posterior cubre un módulo por sprint. Debe consumir los
componentes canónicos y adaptadores aprobados; nunca crea una primitiva local
para resolver una diferencia del consumidor.

## Frontera permitida

- El CSS local contiene solo composición, responsive y geometría de dominio.
- No redefine color, tipografía, forma, estado, acción ni skin de vendor.
- Declara rutas, componentes, vendors, roles, estados y excepciones en manifest.
- No amplía el alcance a módulos vecinos ni cambia negocio o datos.

## Gates mínimos

- Dark y linen en 390x844, 1180x820 y 1440x900.
- Teclado, foco, targets, reflow, overflow y texto sin palabras fragmentadas.
- Axe sin hallazgos critical o serious y revisión manual accesible.
- Contrato funcional, consola limpia y requests esperados.
- Si hay persistencia: snapshot inicial, acción real, recarga y restauración
  verificada mediante un fingerprint idéntico al inicial.

La aprobación visual ocurre sobre la familia en el laboratorio y luego sobre
el módulo migrado. No se recorren otros consumidores como condición de cierre.
