// @vitest-environment jsdom
//
// Tests de ListaRestricciones (ct-app, etapa piloto, Task 7 paso 3b).
//
// Requisitos de negocio fijados por el brief del dispatcher (no reabiertos aquí):
//
// - D33 (asignar sin salir de la hoja): el panel de gestión abre SOBRE la fila — no navega a otra
//   página/ruta — y la fila refleja el nuevo estado sin recargar. Mecanismo elegido en esta ronda
//   (documentado en el reporte): PanelGestion llama a `postGestionRestriccion()`; cuando la
//   promesa RESUELVE (nunca antes, para no mostrar un estado que el servidor podría rechazar),
//   informa al padre el payload exacto que se guardó (`{responsable, fechaCompromiso, estado}`) vía
//   `onGuardada`, y ListaRestricciones actualiza esa fila en su estado local. Sin refetch — se
//   confirma abajo contando las llamadas a `getRestricciones()` (debe seguir en 1).
// - D87 (los tres estados se ven): sin_gestionar / en_gestion / liberada deben tener texto/rol
//   accesible distinto y visible en la lista. Mapeo de etiquetas (decisión de esta ronda, el color
//   exacto es trabajo de diseño/Impeccable, no de este test): sin_gestionar -> "Sin gestionar",
//   en_gestion -> "En gestión", liberada -> "Liberada".
// - Orden de urgencia: la lista mapea cada fila de `getRestricciones()` (que ya trae los 4 campos
//   de RestriccionUrgencia con el mismo nombre) y ordena con `ordenarPorUrgencia()` real —no se
//   mockea esa librería, ya está implementada y probada (Task 7 paso 2).
// - D89 (acción sugerida) NO entra en este paso — diferido, no hay test para eso aquí.
//
// Contrato de interacción (decisión de diseño de este test, documentada en el reporte): cada fila
// lleva `data-testid="fila-restriccion-{id}"` (para poder verificar ORDEN sin depender de texto
// visible ambiguo) y un botón accesible "Gestionar" que abre el panel para esa fila. El contenedor
// de la lista lleva `data-testid="lista-restricciones"` — se usa para probar que sigue montado
// cuando el panel está abierto (prueba de "no navegó a otra página").

import '@testing-library/jest-dom/vitest'
import { render, screen, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { ListaRestricciones } from './ListaRestricciones'
import type { Restriccion } from '../lib/api'

const { getRestriccionesMock, postGestionRestriccionMock } = vi.hoisted(() => ({
  getRestriccionesMock: vi.fn(),
  postGestionRestriccionMock: vi.fn(),
}))

vi.mock('../lib/api', async (importOriginal) => {
  const real = await importOriginal<typeof import('../lib/api')>()
  return {
    ...real,
    getRestricciones: getRestriccionesMock,
    postGestionRestriccion: postGestionRestriccionMock,
  }
})

function restriccion(overrides: Partial<Restriccion>): Restriccion {
  return {
    id: 1,
    restriccion: 'Modelo',
    semana: 3,
    actividadBloqueada: 'Vaciado placa piso 3',
    responsableAsignado: null,
    fechaCompromiso: null,
    estadoLiberacion: 'sin_gestionar',
    asignadoPor: null,
    asignadoEn: null,
    diasVencida: null,
    semanaInicioActividadBloqueada: 2,
    actividadesEncadenadas: 3,
    tocaRutaCritica: false,
    ...overrides,
  }
}

beforeEach(() => {
  getRestriccionesMock.mockReset()
  postGestionRestriccionMock.mockReset()
})

describe('ListaRestricciones — carga y orden', () => {
  it('trae las restricciones al montar y las pinta en el orden de ordenarPorUrgencia() (N4), no en el orden del backend', async () => {
    // Semanas deliberadamente "desordenadas" en el fixture para que el orden de render solo
    // pueda coincidir si el componente de verdad llamó a ordenarPorUrgencia(): null (peor
    // urgencia, sube al tope) -> 2 -> 5.
    getRestriccionesMock.mockResolvedValue([
      restriccion({ id: 10, restriccion: 'Tardía', semanaInicioActividadBloqueada: 5 }),
      restriccion({ id: 11, restriccion: 'Huérfana-urgente', semanaInicioActividadBloqueada: null }),
      restriccion({ id: 12, restriccion: 'Media', semanaInicioActividadBloqueada: 2 }),
    ])

    render(<ListaRestricciones />)

    // findBy* espera a que el efecto de carga resuelva.
    await screen.findByTestId('fila-restriccion-11')

    const filas = screen.getAllByTestId(/^fila-restriccion-/)
    expect(filas.map((fila) => fila.getAttribute('data-testid'))).toEqual([
      'fila-restriccion-11', // null -> primero
      'fila-restriccion-12', // semana 2
      'fila-restriccion-10', // semana 5 -> último
    ])
    expect(getRestriccionesMock).toHaveBeenCalledTimes(1)
  })

  it('si getRestricciones() rechaza, muestra un error visible en vez de una pantalla en blanco (sin fallo silencioso)', async () => {
    getRestriccionesMock.mockRejectedValue(new Error('NOT_FOUND'))

    render(<ListaRestricciones />)

    expect(await screen.findByRole('alert')).toBeInTheDocument()
  })
})

describe('ListaRestricciones — D87, los tres estados se distinguen', () => {
  it('sin_gestionar, en_gestion y liberada muestran texto accesible distinto por fila', async () => {
    getRestriccionesMock.mockResolvedValue([
      restriccion({ id: 20, estadoLiberacion: 'sin_gestionar' }),
      restriccion({ id: 21, estadoLiberacion: 'en_gestion', responsableAsignado: 'Pipe Ramos' }),
      restriccion({ id: 22, estadoLiberacion: 'liberada', responsableAsignado: 'Ana Ruiz' }),
    ])

    render(<ListaRestricciones />)
    await screen.findByTestId('fila-restriccion-20')

    expect(within(screen.getByTestId('fila-restriccion-20')).getByText(/sin gestionar/i)).toBeInTheDocument()
    expect(within(screen.getByTestId('fila-restriccion-21')).getByText(/en gestión/i)).toBeInTheDocument()
    expect(within(screen.getByTestId('fila-restriccion-22')).getByText(/liberada/i)).toBeInTheDocument()
  })
})

describe('ListaRestricciones — D33, gestionar sin salir de la hoja', () => {
  it('el botón "Gestionar" abre el panel sobre la fila sin desmontar la lista (no navega)', async () => {
    const user = userEvent.setup()
    getRestriccionesMock.mockResolvedValue([restriccion({ id: 30 })])

    render(<ListaRestricciones />)
    const fila = await screen.findByTestId('fila-restriccion-30')

    await user.click(within(fila).getByRole('button', { name: /gestionar/i }))

    // El panel aparece...
    expect(screen.getByLabelText(/responsable/i)).toBeInTheDocument()
    // ...y la lista sigue montada: no fue una navegación a otra pantalla.
    expect(screen.getByTestId('lista-restricciones')).toBeInTheDocument()
    expect(screen.getByTestId('fila-restriccion-30')).toBeInTheDocument()
  })

  it('al guardar con éxito, la fila cambia de estado sin una segunda llamada a getRestricciones() (sin refetch, sin recarga completa)', async () => {
    const user = userEvent.setup()
    getRestriccionesMock.mockResolvedValue([
      restriccion({ id: 31, estadoLiberacion: 'sin_gestionar', responsableAsignado: null, fechaCompromiso: null }),
    ])
    postGestionRestriccionMock.mockResolvedValue({
      ok: true,
      restriccion: { id: 31, responsable: 'Pipe Ramos', fechaCompromiso: '2026-09-01', estado: 'en_gestion' },
    })

    render(<ListaRestricciones />)
    const fila = await screen.findByTestId('fila-restriccion-31')
    expect(within(fila).getByText(/sin gestionar/i)).toBeInTheDocument()

    await user.click(within(fila).getByRole('button', { name: /gestionar/i }))
    await user.type(screen.getByLabelText(/responsable/i), 'Pipe Ramos')
    await user.type(screen.getByLabelText(/fecha.*compromiso/i), '2026-09-01')
    await user.selectOptions(screen.getByLabelText(/estado/i), 'en_gestion')
    await user.click(screen.getByRole('button', { name: /guardar/i }))

    // El panel se cierra y la fila (ya no el formulario) vuelve a mostrarse actualizada.
    expect(await within(screen.getByTestId('fila-restriccion-31')).findByText(/en gestión/i)).toBeInTheDocument()
    expect(screen.queryByLabelText(/responsable/i)).not.toBeInTheDocument()
    expect(postGestionRestriccionMock).toHaveBeenCalledWith(31, {
      responsable: 'Pipe Ramos',
      fechaCompromiso: '2026-09-01',
      estado: 'en_gestion',
    })
    // La actualización vino de la respuesta del POST, no de un segundo GET.
    expect(getRestriccionesMock).toHaveBeenCalledTimes(1)
  })

  it('si postGestionRestriccion falla, la fila NO cambia de estado y el panel sigue abierto con el error', async () => {
    const user = userEvent.setup()
    getRestriccionesMock.mockResolvedValue([restriccion({ id: 32, estadoLiberacion: 'sin_gestionar' })])
    const { CtApiError } = await import('../lib/api')
    postGestionRestriccionMock.mockRejectedValue(
      new CtApiError('FORBIDDEN', 'Sin permiso para gestionar esta restricción.', 403),
    )

    render(<ListaRestricciones />)
    const fila = await screen.findByTestId('fila-restriccion-32')

    await user.click(within(fila).getByRole('button', { name: /gestionar/i }))
    await user.type(screen.getByLabelText(/responsable/i), 'Pipe Ramos')
    await user.type(screen.getByLabelText(/fecha.*compromiso/i), '2026-09-01')
    await user.selectOptions(screen.getByLabelText(/estado/i), 'en_gestion')
    await user.click(screen.getByRole('button', { name: /guardar/i }))

    expect(await screen.findByText(/sin permiso para gestionar esta restricción/i)).toBeInTheDocument()
    // El panel no se cerró: el formulario sigue en pantalla para reintentar.
    expect(screen.getByLabelText(/responsable/i)).toBeInTheDocument()
    // La fila real (fuera del panel) no cambió — sigue sin_gestionar.
    expect(within(screen.getByTestId('fila-restriccion-32')).getByText(/sin gestionar/i)).toBeInTheDocument()
  })
})
