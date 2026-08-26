import { useId, useState } from 'react'
import type { FormEvent } from 'react'
import { CtApiError, ETIQUETAS_ESTADO, postGestionRestriccion } from '../lib/api'
import type { GestionEstado, Restriccion } from '../lib/api'

// Formulario de gestión de una restricción (ct-app, etapa piloto, Task 7 paso 3b). Unidad
// aislada — no sabe que vive dentro de ListaRestricciones, solo recibe la restriccion y dos
// callbacks. Contrato fijado por PanelGestion.test.tsx (rol A): valida en el borde antes de
// llamar a postGestionRestriccion(), y solo avisa al padre (onGuardada) con el payload exacto
// que se guardó DESPUÉS de que el servidor confirma — nunca antes (D33: sin optimismo previo a
// confirmación, sin refetch).

interface PanelGestionProps {
  restriccion: Restriccion
  onCancel: () => void
  onGuardada: (payload: { responsable: string; fechaCompromiso: string; estado: GestionEstado }) => void
}

// GestionEstado incluye 'no_aplica' (columna real, ver api.ts) aunque D87 solo pide distinguir
// los tres estados operativos — se ofrece igual como opción para no perder datos si una
// restricción ya llega marcada así (concern del rol A, decisión de esta ronda).
const ESTADOS_SELECCIONABLES: GestionEstado[] = ['sin_gestionar', 'en_gestion', 'liberada', 'no_aplica']

function validar(responsable: string, fechaCompromiso: string): string | null {
  if (responsable.trim() === '') {
    return 'El responsable es obligatorio.'
  }
  // <input type="date"> normaliza cualquier valor no-fecha a '' a nivel del propio DOM, así que
  // "vacío" cubre también "formato inválido" (ver cabecera de PanelGestion.test.tsx).
  if (fechaCompromiso.trim() === '') {
    return 'La fecha de compromiso es obligatoria o su formato no es válido.'
  }
  return null
}

export function PanelGestion({ restriccion, onCancel, onGuardada }: PanelGestionProps) {
  const idResponsable = useId()
  const idFecha = useId()
  const idEstado = useId()

  const [responsable, setResponsable] = useState(restriccion.responsableAsignado ?? '')
  const [fechaCompromiso, setFechaCompromiso] = useState(restriccion.fechaCompromiso ?? '')
  const [estado, setEstado] = useState<GestionEstado>(restriccion.estadoLiberacion)
  const [error, setError] = useState<string | null>(null)
  const [enviando, setEnviando] = useState(false)

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()

    const mensajeValidacion = validar(responsable, fechaCompromiso)
    if (mensajeValidacion) {
      setError(mensajeValidacion)
      return
    }

    const payload = { responsable: responsable.trim(), fechaCompromiso, estado }
    setError(null)
    setEnviando(true)
    try {
      await postGestionRestriccion(restriccion.id, payload)
      onGuardada(payload)
    } catch (err: unknown) {
      // Nunca un catch mudo: el mensaje del servidor (CtApiError) o uno genérico siempre se
      // muestra, el panel se queda abierto para reintentar.
      setError(err instanceof CtApiError ? err.message : 'No se pudo guardar la gestión.')
    } finally {
      setEnviando(false)
    }
  }

  return (
    <form onSubmit={handleSubmit}>
      {error && <p role="alert">{error}</p>}

      <div>
        <label htmlFor={idResponsable}>Responsable</label>
        <input
          id={idResponsable}
          type="text"
          value={responsable}
          onChange={(event) => setResponsable(event.target.value)}
        />
      </div>

      <div>
        <label htmlFor={idFecha}>Fecha de compromiso</label>
        <input
          id={idFecha}
          type="date"
          value={fechaCompromiso}
          onChange={(event) => setFechaCompromiso(event.target.value)}
        />
      </div>

      <div>
        <label htmlFor={idEstado}>Estado</label>
        <select id={idEstado} value={estado} onChange={(event) => setEstado(event.target.value as GestionEstado)}>
          {ESTADOS_SELECCIONABLES.map((valor) => (
            <option key={valor} value={valor}>
              {ETIQUETAS_ESTADO[valor]}
            </option>
          ))}
        </select>
      </div>

      <button type="submit" disabled={enviando}>
        Guardar
      </button>
      <button type="button" onClick={onCancel}>
        Cancelar
      </button>
    </form>
  )
}
