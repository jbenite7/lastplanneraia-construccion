// @vitest-environment jsdom
//
// Tests de AlarmaHuerfanas (ct-app, etapa piloto, Task 7 ensamblaje — posición 1 del lienzo de
// Intermedia, CT-8.3: «las restricciones sin análisis ni dueño, con la acción de asignarlas»).
//
// Criterio de "huérfana" (decisión de este sub-paso, documentada en el reporte del rol A): una
// restricción sin análisis NI dueño = `estadoLiberacion === 'sin_gestionar' && responsableAsignado
// === null`. No hay columna `analizada` en `pi_shared_constraints` — `sin_gestionar` es el estado
// que Task 4/CT-7.3 asigna por defecto a una fila que nadie ha tocado, y el AND con
// `responsableAsignado === null` es más estricto que cualquiera de los dos solos (cubre el caso
// borde de una fila que quedara con estado `sin_gestionar` pero con un responsable ya escrito,
// aunque el flujo actual de PanelGestion no debería producirlo).
//
// AlarmaHuerfanas es PRESENTACIONAL: no hace fetch, no conoce la lista completa de restricciones
// ni aplica el criterio de arriba — recibe `huerfanas` YA FILTRADA por quien ensambla la hoja
// (Intermedia.tsx). Esto evita reimplementar el filtro en dos sitios (Intermedia también lo usa
// para `ResumenLookaheadIntermedia.huerfanasCount` que consume Titular) y mantiene este componente
// testeable en aislamiento con fixtures directos, sin mockear `getRestricciones()` aquí.
//
// Mecanismo de la acción (decisión de este sub-paso): un botón "Ver huérfanas" que invoca
// `onVerHuerfanas()` sin argumentos — el padre decide qué hacer con eso (Intermedia.tsx filtra lo
// que le pasa como prop a ListaRestricciones, ver Intermedia.test.tsx). No hay alternar/deshacer
// el filtro desde este componente en este sub-paso — ver el reporte, concern diferido.
//
// Caso borde, cero huérfanas (decisión de este sub-paso, documentada): la sección SÍ se renderiza
// (no desaparece del lienzo — la posición 1 queda estable), pero con un mensaje neutro de
// confirmación y SIN el botón de acción, para no generar una alarma falsa cuando el lookahead está
// sano.
//
// Contrato de interacción con el test: la sección raíz lleva `data-testid="alarma-huerfanas"`
// (para poder acotar consultas de texto/botón sin depender de estructura interna de markup —mismo
// patrón que `data-testid="lista-restricciones"` en ListaRestricciones.test.tsx).

import '@testing-library/jest-dom/vitest'
import { render, screen, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it, vi } from 'vitest'
import { AlarmaHuerfanas } from './AlarmaHuerfanas'
import type { Restriccion } from '../lib/api'

function restriccion(overrides: Partial<Restriccion>): Restriccion {
  return {
    id: 1,
    restriccion: 'Materiales',
    semana: 3,
    actividadBloqueada: 'Vaciado placa piso 3',
    responsableAsignado: null,
    fechaCompromiso: null,
    estadoLiberacion: 'sin_gestionar',
    asignadoPor: null,
    asignadoEn: null,
    diasVencida: null,
    semanaInicioActividadBloqueada: 2,
    actividadesEncadenadas: 1,
    tocaRutaCritica: false,
    ...overrides,
  }
}

describe('AlarmaHuerfanas — con huérfanas', () => {
  it('muestra el conteo y un llamado a la acción accesible', () => {
    const huerfanas = [restriccion({ id: 1 }), restriccion({ id: 2 }), restriccion({ id: 3 })]

    render(<AlarmaHuerfanas huerfanas={huerfanas} onVerHuerfanas={vi.fn()} />)

    const region = screen.getByTestId('alarma-huerfanas')
    expect(region.textContent).toMatch(/3/)
    expect(region.textContent).toMatch(/sin an[aá]lisis/i)
    expect(within(region).getByRole('button', { name: /huérfanas/i })).toBeInTheDocument()
  })

  it('al usar el llamado a la acción, invoca onVerHuerfanas() una vez (el padre decide cómo filtrar/resaltar)', async () => {
    const user = userEvent.setup()
    const onVerHuerfanas = vi.fn()
    render(<AlarmaHuerfanas huerfanas={[restriccion({ id: 1 })]} onVerHuerfanas={onVerHuerfanas} />)

    await user.click(screen.getByRole('button', { name: /huérfanas/i }))

    expect(onVerHuerfanas).toHaveBeenCalledTimes(1)
  })
})

describe('AlarmaHuerfanas — caso borde, cero huérfanas', () => {
  it('no muestra el llamado a la acción: mensaje neutro, sin botón, sin tono alarmante', () => {
    render(<AlarmaHuerfanas huerfanas={[]} onVerHuerfanas={vi.fn()} />)

    const region = screen.getByTestId('alarma-huerfanas')
    expect(within(region).queryByRole('button')).not.toBeInTheDocument()
    // No se exige texto exacto (decisión de copy de rol B) — solo que exista una confirmación
    // neutra/positiva reconocible.
    expect(region.textContent).toMatch(/gestionad[oa]s?|sin pendientes|al d[ií]a/i)
  })
})
