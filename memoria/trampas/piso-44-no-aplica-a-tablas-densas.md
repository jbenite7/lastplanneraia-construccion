---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-08-04
areas: [design-system, qa]
fuente: DESIGN.md §5 bis, tests/browser/design-system-lab-desktop-layout.mjs, public/css/design-system/components/table-filter-trigger.css
resumen: "El piso táctil no es 44px en todas partes: en tablas densas desktop DESIGN.md §5 bis fija 24x24 (WCAG 2.2 SC 2.5.8); antes de arreglar un rojo de targets, mira contra qué vara se midió"
---
# El piso de 44px no aplica plano: las tablas densas tienen 24 por excepción registrada

`DESIGN.md:869` («Excepción al mínimo de 44 px») baja el suelo a **24×24px** —el mínimo real de
WCAG 2.2 SC 2.5.8 (AA)— en la familia de tablas densas desktop (`/programa-general`,
`/programacion-intermedia`, `/programacion-semanal`, `/pdc`, `/plan-compras`), porque el criterio de
44 protege el acierto del dedo sobre un cristal y esa familia está fuera del alcance móvil por
contrato de [[AGENTS]]. **Lo que no se relaja:** contraste AA, foco de 4px y teclado siguen
exigidos, y los 24 tampoco se cruzan.

`tests/browser/design-system-lab-desktop-layout.mjs` aplicaba 44 a todas las familias sin conocer la
excepción. Desde el 2026-08-04 lleva `DENSE_TABLE_TARGETS` / `DENSE_TABLE_MIN`, acotado **solo** al
gatillo canónico de filtro de columna (`.aia-table-filter-trigger` y `.changeType`, el nombre que
Handsontable impone en el DOM que genera); cualquier otro objetivo de esa misma tabla sigue
midiéndose contra 44, y cada violación reporta el `min` con el que se midió.

**Cómo no caer.**

- Ante un rojo de «targets below», lee el `min` del reporte antes de tocar CSS: 44 y 24 son dos
  contratos distintos, no una tolerancia.
- La excepción caduca si la superficie se abre a táctil. Entonces se vacía esa lista, no se
  reescribe el CSS a posteriori.
- Bajar el umbral de un test para poner verde está prohibido; **codificar una excepción ya escrita
  en `DESIGN.md` no es lo mismo**, y se comprueba mutando el valor (a 23px) para confirmar que el
  piso nuevo sigue mordiendo.

Vecinas: [[lab-desktop-layout-suite]] (la suite vive fuera del carril de gates habitual) ·
[[gate-visual-tolerancia-enganosa]]. Mapa del área: [[design-system]].
