---
capa: fuente
tipo: decision
estado: vigente
fecha: 2026-08-24
areas: [proceso]
tags: [proyecto]
fuente: decisión de Felipe en sesión, 2026-08-24
resumen: "Handsontable se queda con la clave no comercial: Felipe declara el uso como interno"
project: lps-aia
type: decision
status: activo
updated: 2026-08-24
---

# Handsontable con clave no comercial: uso declarado interno

**Decisión de Felipe, 2026-08-24:** «La app es interna, no comercial.» Las seis rejillas siguen
arrancando con `licenseKey: 'non-commercial-and-evaluation'` y no se cotiza licencia comercial.

## Lo que se le presentó antes de decidir

El propio archivo que servimos (`public/vendor/handsontable/handsontable.full.min.css`, cabecera)
dice que el software está **doblemente licenciado** y que la clave no comercial cubre uso
«estrictamente personal o solo de evaluación, para probar la idoneidad del software **fuera del
entorno de producción**». Esta aplicación corre en producción, en una empresa, con obras reales.

Se señaló una vez, con el texto de la licencia a la vista. Felipe decidió. **No se vuelve a
levantar**: esta página existe para que la razón quede registrada y la discusión no se repita sola.

## Qué la reabriría

- Que Handsontable o su representante contacte a AIA por el uso de la clave.
- Que el producto se ofrezca a un tercero, aunque sea a otra empresa del grupo: ahí el «interno»
  deja de aplicar por su propia definición.
- Una revisión legal que quiera mirarlo con la definición de «commercial advantage» del texto.

## Alternativa descartada de paso

Migrar las seis rejillas a AG Grid (MIT, ya en la casa por el Plan de Compras) **no** es un ahorro:
son el corazón del Last Planner, llevan años de reglas de negocio en renderers y validaciones, y
cambiarlas de motor es un proyecto, no una limpieza. Ver [[TASKS]] §Diferibles para el único carril
de tabla que sí se retira, que es DataTables.

## Archivos de esta decisión
- [[TASKS]]
- [[ROADMAP]]
