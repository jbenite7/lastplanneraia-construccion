// Setup global de Vitest (ct-app). `test.globals` está deliberadamente en `false` (los archivos
// de test importan `describe`/`it`/`afterEach`/etc. de 'vitest' de forma explícita, ver
// api.test.ts, ListaRestricciones.test.tsx, PanelGestion.test.tsx) — pero eso significa que
// `@testing-library/react` no encuentra un `afterEach` global al cargar, así que su auto-cleanup
// (`if (typeof afterEach === 'function') afterEach(cleanup)`, ver
// node_modules/@testing-library/react/dist/index.js) nunca se registra por sí solo. Sin este
// archivo, cada `render()` de un test de componente queda montado en el DOM del siguiente test
// del mismo archivo — confirmado en la ronda RED de Task 7 paso 3b: solo el primer test de
// PanelGestion.test.tsx pasaba, los siguientes fallaban con "multiple elements found" porque
// `getByLabelText` encontraba también las etiquetas de los formularios ya renderizados por tests
// anteriores. Este archivo hace explícito lo que el auto-cleanup habría hecho solo.
import { afterEach } from 'vitest'
import { cleanup } from '@testing-library/react'

afterEach(() => {
  cleanup()
})
