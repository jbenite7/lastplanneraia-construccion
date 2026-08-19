---
capa: fuente
tipo: contrato
estado: vigente
fecha: 2026-07-15
areas: [design-system]
fuente: docs/design-system/contracts/module-migration.md
resumen: Cada migración posterior cubre un módulo por sprint. Debe consumir los componentes canónicos y adaptadores aprobados; nunca crea una primitiva local para…
---

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

- Dark en 1180x820 y 1440x900. Son los viewports **requeridos**: todo módulo migrado
  los cubre con evidencia. `390x844` está **soportado pero no requerido** desde DS-032:
  se puede declarar, y en cuanto se declara exige golden y `sha256` como cualquier otro.
- Teclado, foco, targets, reflow, overflow y texto sin palabras fragmentadas.
- Axe sin hallazgos critical o serious y revisión manual accesible.
- Contrato funcional, consola limpia y requests esperados.
- Si hay persistencia: snapshot inicial, acción real, recarga y restauración
  verificada mediante un fingerprint idéntico al inicial.

La aprobación visual ocurre sobre la familia en el laboratorio y luego sobre
el módulo migrado. No se recorren otros consumidores como condición de cierre.
