// @vitest-environment jsdom
//
// Tests de PanelGestion (ct-app, etapa piloto, Task 7 paso 3b) — unidad aislada, sin
// ListaRestricciones alrededor (esa integración ya la cubre ListaRestricciones.test.tsx).
//
// Contrato de props fijado por este test (decisión de diseño de esta ronda, documentada en el
// reporte — rol B implementa contra esto):
//
//   interface PanelGestionProps {
//     restriccion: Restriccion
//     onCancel: () => void
//     onGuardada: (payload: { responsable: string; fechaCompromiso: string; estado: GestionEstado }) => void
//   }
//
// `onGuardada` se llama SOLO después de que `postGestionRestriccion()` resuelve con éxito, con el
// mismo payload que se mandó (no con `GestionResponse.restriccion`, que es `unknown` en el
// contrato ya fijado por Task 5 — evita que este componente tenga que validar/castear ese campo).
//
// Requisito de negocio: "el panel de gestión valida antes de enviar: responsable no vacío, fecha
// en formato válido, estado uno de los válidos — si falta algo, muestra error inline y NO llama a
// postGestionRestriccion()" (patrón ya establecido en el repo: validar en el borde del widget).
// Para el campo fecha se usa un <input type="date">: el propio navegador normaliza cualquier
// valor con formato inválido a cadena vacía, así que "formato inválido" y "vacío" son el mismo
// caso de prueba para un input nativo de fecha — decisión documentada en el reporte, no un hueco
// de cobertura.

import '@testing-library/jest-dom/vitest'
import { fireEvent, render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { PanelGestion } from './PanelGestion'
import { CtApiError } from '../lib/api'
import type { Restriccion } from '../lib/api'

const { postGestionRestriccionMock } = vi.hoisted(() => ({
  postGestionRestriccionMock: vi.fn(),
}))

vi.mock('../lib/api', async (importOriginal) => {
  const real = await importOriginal<typeof import('../lib/api')>()
  return {
    ...real,
    postGestionRestriccion: postGestionRestriccionMock,
  }
})

const RESTRICCION_SIN_GESTIONAR: Restriccion = {
  id: 42,
  restriccion: 'Modelo',
  semana: 3,
  actividadBloqueada: 'Vaciado placa piso 3',
  responsableAsignado: null,
  fechaCompromiso: null,
  estadoLiberacion: 'sin_gestionar',
  asignadoPor: null,
  asignadoEn: null,
  diasVencida: null,
  semanaInicioActividadBloqueada: -3,
  actividadesEncadenadas: 12,
  tocaRutaCritica: true,
}

beforeEach(() => {
  postGestionRestriccionMock.mockReset()
})

describe('PanelGestion — prefill desde la restricción', () => {
  it('precarga responsable, fecha y estado actuales cuando la restricción ya tiene gestión', () => {
    const enGestion: Restriccion = {
      ...RESTRICCION_SIN_GESTIONAR,
      responsableAsignado: 'Pipe Ramos',
      fechaCompromiso: '2026-09-01',
      estadoLiberacion: 'en_gestion',
    }

    render(<PanelGestion restriccion={enGestion} onCancel={vi.fn()} onGuardada={vi.fn()} />)

    expect(screen.getByLabelText(/responsable/i)).toHaveValue('Pipe Ramos')
    expect(screen.getByLabelText(/fecha.*compromiso/i)).toHaveValue('2026-09-01')
    expect(screen.getByLabelText(/estado/i)).toHaveValue('en_gestion')
  })

  it('arranca en blanco cuando la restricción no tiene gestión previa (responsable/fecha null)', () => {
    render(<PanelGestion restriccion={RESTRICCION_SIN_GESTIONAR} onCancel={vi.fn()} onGuardada={vi.fn()} />)

    expect(screen.getByLabelText(/responsable/i)).toHaveValue('')
    expect(screen.getByLabelText(/fecha.*compromiso/i)).toHaveValue('')
  })
})

describe('PanelGestion — cancelar', () => {
  it('el botón Cancelar llama a onCancel() y no llama a postGestionRestriccion', async () => {
    const user = userEvent.setup()
    const onCancel = vi.fn()

    render(<PanelGestion restriccion={RESTRICCION_SIN_GESTIONAR} onCancel={onCancel} onGuardada={vi.fn()} />)
    await user.click(screen.getByRole('button', { name: /cancelar/i }))

    expect(onCancel).toHaveBeenCalledTimes(1)
    expect(postGestionRestriccionMock).not.toHaveBeenCalled()
  })
})

describe('PanelGestion — valida antes de enviar', () => {
  it('responsable vacío: muestra error inline, NO llama a postGestionRestriccion ni a onGuardada', async () => {
    const user = userEvent.setup()
    const onGuardada = vi.fn()

    render(<PanelGestion restriccion={RESTRICCION_SIN_GESTIONAR} onCancel={vi.fn()} onGuardada={onGuardada} />)
    // Fecha sí puesta, responsable se deja vacío a propósito.
    fireEvent.change(screen.getByLabelText(/fecha.*compromiso/i), { target: { value: '2026-09-01' } })
    await user.click(screen.getByRole('button', { name: /guardar/i }))

    expect(await screen.findByRole('alert')).toHaveTextContent(/responsable/i)
    expect(postGestionRestriccionMock).not.toHaveBeenCalled()
    expect(onGuardada).not.toHaveBeenCalled()
  })

  it('fecha vacía o con formato inválido: muestra error inline, NO llama a postGestionRestriccion', async () => {
    const user = userEvent.setup()
    const onGuardada = vi.fn()

    render(<PanelGestion restriccion={RESTRICCION_SIN_GESTIONAR} onCancel={vi.fn()} onGuardada={onGuardada} />)
    await user.type(screen.getByLabelText(/responsable/i), 'Pipe Ramos')
    // Un input type="date" normaliza cualquier valor no-fecha a "": este es el caso "formato
    // inválido" y "vacío" a la vez, ver la nota de cabecera del archivo.
    fireEvent.change(screen.getByLabelText(/fecha.*compromiso/i), { target: { value: 'no-es-una-fecha' } })
    await user.click(screen.getByRole('button', { name: /guardar/i }))

    expect(await screen.findByRole('alert')).toHaveTextContent(/fecha/i)
    expect(postGestionRestriccionMock).not.toHaveBeenCalled()
    expect(onGuardada).not.toHaveBeenCalled()
  })

  it('formulario completo y válido: llama a postGestionRestriccion con el payload exacto, sin error inline', async () => {
    const user = userEvent.setup()
    postGestionRestriccionMock.mockResolvedValue({ ok: true, restriccion: {} })

    render(<PanelGestion restriccion={RESTRICCION_SIN_GESTIONAR} onCancel={vi.fn()} onGuardada={vi.fn()} />)
    await user.type(screen.getByLabelText(/responsable/i), 'Pipe Ramos')
    fireEvent.change(screen.getByLabelText(/fecha.*compromiso/i), { target: { value: '2026-09-01' } })
    await user.selectOptions(screen.getByLabelText(/estado/i), 'en_gestion')
    await user.click(screen.getByRole('button', { name: /guardar/i }))

    expect(postGestionRestriccionMock).toHaveBeenCalledWith(42, {
      responsable: 'Pipe Ramos',
      fechaCompromiso: '2026-09-01',
      estado: 'en_gestion',
    })
    expect(screen.queryByRole('alert')).not.toBeInTheDocument()
  })
})

describe('PanelGestion — éxito y error del POST', () => {
  it('éxito: llama a onGuardada con el payload que se guardó', async () => {
    const user = userEvent.setup()
    const onGuardada = vi.fn()
    postGestionRestriccionMock.mockResolvedValue({ ok: true, restriccion: {} })

    render(<PanelGestion restriccion={RESTRICCION_SIN_GESTIONAR} onCancel={vi.fn()} onGuardada={onGuardada} />)
    await user.type(screen.getByLabelText(/responsable/i), 'Pipe Ramos')
    fireEvent.change(screen.getByLabelText(/fecha.*compromiso/i), { target: { value: '2026-09-01' } })
    await user.selectOptions(screen.getByLabelText(/estado/i), 'liberada')
    await user.click(screen.getByRole('button', { name: /guardar/i }))

    await vi.waitFor(() => {
      expect(onGuardada).toHaveBeenCalledWith({
        responsable: 'Pipe Ramos',
        fechaCompromiso: '2026-09-01',
        estado: 'liberada',
      })
    })
  })

  it('CtApiError del POST (403 sin capacidad): muestra el mensaje del servidor, NO un catch mudo, y NO llama a onGuardada', async () => {
    const user = userEvent.setup()
    const onGuardada = vi.fn()
    postGestionRestriccionMock.mockRejectedValue(
      new CtApiError('FORBIDDEN', 'Sin permiso para gestionar esta restricción.', 403),
    )

    render(<PanelGestion restriccion={RESTRICCION_SIN_GESTIONAR} onCancel={vi.fn()} onGuardada={onGuardada} />)
    await user.type(screen.getByLabelText(/responsable/i), 'Pipe Ramos')
    fireEvent.change(screen.getByLabelText(/fecha.*compromiso/i), { target: { value: '2026-09-01' } })
    await user.selectOptions(screen.getByLabelText(/estado/i), 'en_gestion')
    await user.click(screen.getByRole('button', { name: /guardar/i }))

    expect(await screen.findByText(/sin permiso para gestionar esta restricción/i)).toBeInTheDocument()
    expect(onGuardada).not.toHaveBeenCalled()
  })

  it('cualquier otra falla del POST (error de red, 500) también se muestra — nunca se traga en un catch vacío', async () => {
    const user = userEvent.setup()
    postGestionRestriccionMock.mockRejectedValue(new CtApiError('BAD_RESPONSE', 'Respuesta inválida del servidor (HTTP 500).', 500))

    render(<PanelGestion restriccion={RESTRICCION_SIN_GESTIONAR} onCancel={vi.fn()} onGuardada={vi.fn()} />)
    await user.type(screen.getByLabelText(/responsable/i), 'Pipe Ramos')
    fireEvent.change(screen.getByLabelText(/fecha.*compromiso/i), { target: { value: '2026-09-01' } })
    await user.click(screen.getByRole('button', { name: /guardar/i }))

    expect(await screen.findByRole('alert')).toHaveTextContent(/respuesta inválida del servidor/i)
  })
})
