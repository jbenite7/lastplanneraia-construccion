---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-09-28
areas: [lps]
fuente: goals/s05-cajon-verdad/goal.md
resumen: El cajón LPS de Programa General deja de afirmar lo que no sabe — sin observación falsa, SOS real, recursos con dato real y descarte confirmado
---

# Goal: el cajón de Programa General dice la verdad

**Decisión de Felipe del 2026-09-28:** arreglo aparte y ya, antes de la ronda de rediseño. Sale de
la crítica de diseño sobre `main`
(`.impeccable/critique/2026-09-28T13-49-37Z__frontend-src-modules-programa-general.md`, P0).

## Objetivo

El cajón LPS de `/programa-general` (`frontend/src/modules/programa-general/components/ProgramaDrawer.tsx`)
no afirma nada que no sepa:

1. **Observación.** Hoy el cajón deja escribirla y responde «Cambios guardados con éxito», pero no
   viaja en el guardado (`ProgramaGeneralPage.tsx:192-205`). El legado nunca la editó y el endpoint
   no la acepta. Se quita el campo editable; no se crea un camino de escritura nuevo.
2. **SOS.** «Declarar Crisis SOS» solo cambia el rótulo. Se conecta a la API real de T02
   (`registrarCrisis`, `frontend/src/shared/lps/api/crisis.ts`), que ya soporta `modulo=PG`.
3. **Recursos Lean.** La matriz de 7 recursos está fija en `'Liberado'`
   (`ProgramaDrawer.tsx:204-246`). Se muestran solo los recursos con dato real en la fila
   (`D_y_E`, `Materiales`, `MdeO`, `Equipos`, o `restriccion_pc_1..4` en Preconstrucción); los que
   no tienen fuente no se muestran.
4. **Descarte.** Esc y el clic en el velo con cambios sin guardar piden confirmación.
5. **Visibilidad y accesibilidad del cajón:** barra de avance real visible (sin tokens
   inexistentes), campo de avance con etiqueta (axe crítico), y el foco entra al abrir y vuelve a
   la fila al cerrar.

## Condición de hecho

PR contra `main` con: pruebas que muerden para los cinco puntos (rojo visto antes del código);
`npm --prefix frontend test`, `typecheck`, `build` (bundle commiteado), `test:design-system:static`
y el spec de navegador de Programa General en RC 0; axe sin críticos en el cajón; revisión
independiente sin hallazgos abiertos; y las 13 variables `G_*` del CI en `success` en ambos temas.
Sin DDL, DML de datos ni cambios de RLS. Sin deploy.

## Archivos de este goal

- Crítica que lo origina: `.impeccable/critique/2026-09-28T13-49-37Z__frontend-src-modules-programa-general.md`
- Estado de goals: [[memoria/goals/estado]]
