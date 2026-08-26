import { Fragment, useEffect, useState } from 'react'
import { ETIQUETAS_ESTADO, getRestricciones } from '../lib/api'
import type { GestionEstado, Restriccion } from '../lib/api'
import { ordenarPorUrgencia } from '../lib/urgencia'
import { PanelGestion } from './PanelGestion'

// Lista de restricciones activas (ct-app, etapa piloto, Task 7 paso 3b). Trae los datos con
// getRestricciones(), los ordena por urgencia real (N4, Task 7 paso 2 — no se reimplementa el
// criterio aquí) y muestra los tres estados de D87 con texto distinguible. D33: el botón
// "Gestionar" abre PanelGestion sobre la misma fila, sin navegar a otra pantalla; al guardar, la
// fila se actualiza con el payload que confirmó el servidor — sin una segunda llamada a
// getRestricciones(). D89 (acción sugerida) queda diferido a un paso posterior, no se construye
// aquí.

/**
 * Reordena restricciones completas según el orden que produce ordenarPorUrgencia() sobre la
 * proyección de urgencia. Restriccion ya trae los 4 campos de RestriccionUrgencia con el mismo
 * nombre (id, semanaInicioActividadBloqueada, actividadesEncadenadas, tocaRutaCritica), así que
 * no hace falta remapear valores — pero ordenarPorUrgencia() devuelve RestriccionUrgencia[], que
 * en el tipo pierde el resto de campos que la fila necesita para pintarse. Se recupera el objeto
 * completo por id tras el sort en vez de castear el resultado.
 */
function ordenarRestricciones(restricciones: Restriccion[]): Restriccion[] {
  const porId = new Map(restricciones.map((r) => [r.id, r]))
  return ordenarPorUrgencia(restricciones).map((u) => porId.get(u.id)!)
}

export function ListaRestricciones() {
  const [restricciones, setRestricciones] = useState<Restriccion[] | null>(null)
  const [error, setError] = useState<string | null>(null)
  const [gestionandoId, setGestionandoId] = useState<number | null>(null)

  useEffect(() => {
    getRestricciones()
      .then((data) => setRestricciones(ordenarRestricciones(data)))
      .catch((err: unknown) => {
        setError(err instanceof Error ? err.message : 'No se pudieron cargar las restricciones.')
      })
  }, [])

  function handleGuardada(payload: { responsable: string; fechaCompromiso: string; estado: GestionEstado }) {
    setRestricciones((actuales) =>
      (actuales ?? []).map((r) =>
        r.id === gestionandoId
          ? {
              ...r,
              responsableAsignado: payload.responsable,
              fechaCompromiso: payload.fechaCompromiso,
              estadoLiberacion: payload.estado,
            }
          : r,
      ),
    )
    setGestionandoId(null)
  }

  if (error) {
    return <p role="alert">{error}</p>
  }

  return (
    <div data-testid="lista-restricciones">
      {restricciones === null && <p>Cargando restricciones…</p>}

      {restricciones !== null && restricciones.length === 0 && <p>No hay restricciones registradas.</p>}

      {restricciones !== null &&
        restricciones.map((r) => (
          // PanelGestion va como hermano de la fila, no anidado: si estuviera dentro del mismo
          // <div>, el <select> de estado repite como <option> el mismo texto que ya muestra el
          // span de la fila (p. ej. "Sin gestionar"), y un within(fila).getByText(...) del test
          // encontraría dos coincidencias en vez de una. Ambos siguen dentro del contenedor
          // "lista-restricciones" — la lista sigue montada igual, D33 no cambia.
          <Fragment key={r.id}>
            <div data-testid={`fila-restriccion-${r.id}`}>
              <span>{r.restriccion}</span>
              <span>{ETIQUETAS_ESTADO[r.estadoLiberacion]}</span>
              <button type="button" onClick={() => setGestionandoId(r.id)}>
                Gestionar
              </button>
            </div>

            {gestionandoId === r.id && (
              <PanelGestion restriccion={r} onCancel={() => setGestionandoId(null)} onGuardada={handleGuardada} />
            )}
          </Fragment>
        ))}
    </div>
  )
}
