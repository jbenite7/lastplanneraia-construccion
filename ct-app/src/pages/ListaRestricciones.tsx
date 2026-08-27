import { Fragment, useEffect, useState } from 'react'
import { ETIQUETAS_ESTADO, getRestricciones } from '../lib/api'
import type { GestionEstado, Restriccion } from '../lib/api'
import { construirAccionSugerida } from '../lib/accionSugerida'
import { ordenarPorUrgencia } from '../lib/urgencia'
import { PanelGestion } from './PanelGestion'

// Lista de restricciones activas (ct-app, etapa piloto, Task 7 paso 3b + Task 7 ensamblaje). Trae
// los datos con getRestricciones(), los ordena por urgencia real (N4, Task 7 paso 2 — no se
// reimplementa el criterio aquí) y muestra los tres estados de D87 con texto distinguible. D33: el
// botón "Gestionar" abre PanelGestion sobre la misma fila, sin navegar a otra pantalla; al
// guardar, la fila se actualiza con el payload que confirmó el servidor — sin una segunda llamada
// a getRestricciones(). D89 (Task 7 D89, sub-paso posterior): cada fila muestra su acción sugerida
// y contacto (construirAccionSugerida(), ver accionSugerida.ts) en un <p> propio dentro de la
// fila — no en el mismo <span> que ya usan restriccion/estado, para no romper los
// within(fila).getByText(...) de D87/D33 que ya fijó el paso anterior.
//
// Task 7 ensamblaje: prop opcional `restricciones?`. Cuando Intermedia.tsx la pasa (mismo array
// que ya trajo con su único fetch compartido), esta lista usa ese array directo y NO llama a
// getRestricciones() por su cuenta — evita el fetch duplicado que Intermedia.test.tsx verifica
// contando llamadas al mock. Cuando se omite (uso standalone, como en ListaRestricciones.test.tsx,
// que sigue sin tocarse), el comportamiento no cambia: self-fetch al montar. Las gestiones
// guardadas se aplican como un overlay local por id (`ajustesGuardados`) sobre la base que
// corresponda (la propia carga o la prop) — D33 sigue funcionando en modo ensamblado.
//
// Task 7 ensamblaje, fix ronda 1 (hallazgo Important de la revisión): prop opcional
// `onGestionGuardada?: (id, ajuste) => void`. Cuando el padre la pasa (Intermedia.tsx), el
// guardado exitoso NO se queda en el overlay local — se notifica hacia arriba con el mismo
// payload que el servidor ya confirmó, para que Intermedia actualice su propia copia de
// `restricciones` y su `useMemo` de huérfanas/vencidas recalcule. Así el contador de
// AlarmaHuerfanas y el titular bajan de inmediato, sin remount/refetch, y la fila gestionada sale
// sola del filtro "solo huérfanas" (ya no cumple el criterio). Cuando `onGestionGuardada` no
// llega (uso standalone), el comportamiento es el de antes: overlay local. No se escribe en el
// overlay cuando se notifica hacia arriba — evitar el overlay permanente-sin-limpiar que la
// revisión señaló como el riesgo inverso (sombrear para siempre el dato ya fresco que baja por
// prop).

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

export type AjusteGuardado = Pick<Restriccion, 'responsableAsignado' | 'fechaCompromiso' | 'estadoLiberacion'>

interface ListaRestriccionesProps {
  restricciones?: Restriccion[]
  onGestionGuardada?: (id: number, ajuste: AjusteGuardado) => void
}

export function ListaRestricciones({
  restricciones: restriccionesProp,
  onGestionGuardada,
}: ListaRestriccionesProps = {}) {
  const usaCargaPropia = restriccionesProp === undefined

  const [restriccionesCargadas, setRestriccionesCargadas] = useState<Restriccion[] | null>(null)
  const [error, setError] = useState<string | null>(null)
  const [gestionandoId, setGestionandoId] = useState<number | null>(null)
  const [ajustesGuardados, setAjustesGuardados] = useState<Record<number, AjusteGuardado>>({})

  useEffect(() => {
    if (!usaCargaPropia) return
    getRestricciones()
      .then((data) => setRestriccionesCargadas(data))
      .catch((err: unknown) => {
        setError(err instanceof Error ? err.message : 'No se pudieron cargar las restricciones.')
      })
  }, [usaCargaPropia])

  const base = usaCargaPropia ? restriccionesCargadas : (restriccionesProp ?? null)

  const restricciones =
    base === null
      ? null
      : ordenarRestricciones(
          base.map((r) => {
            const ajuste = ajustesGuardados[r.id]
            return ajuste ? { ...r, ...ajuste } : r
          }),
        )

  function handleGuardada(payload: { responsable: string; fechaCompromiso: string; estado: GestionEstado }) {
    if (gestionandoId !== null) {
      const ajuste: AjusteGuardado = {
        responsableAsignado: payload.responsable,
        fechaCompromiso: payload.fechaCompromiso,
        estadoLiberacion: payload.estado,
      }
      if (onGestionGuardada) {
        // El padre sostiene los datos y va a bajar la prop `restricciones` ya actualizada con
        // este mismo ajuste — no se guarda overlay local para no sombrearla luego con un valor
        // que quedaría fijo para siempre.
        onGestionGuardada(gestionandoId, ajuste)
      } else {
        setAjustesGuardados((actuales) => ({ ...actuales, [gestionandoId]: ajuste }))
      }
    }
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
        restricciones.map((r) => {
          const accion = construirAccionSugerida(r.restriccion)

          // PanelGestion va como hermano de la fila, no anidado: si estuviera dentro del mismo
          // <div>, el <select> de estado repite como <option> el mismo texto que ya muestra el
          // span de la fila (p. ej. "Sin gestionar"), y un within(fila).getByText(...) del test
          // encontraría dos coincidencias en vez de una. Ambos siguen dentro del contenedor
          // "lista-restricciones" — la lista sigue montada igual, D33 no cambia.
          return (
            <Fragment key={r.id}>
              <div data-testid={`fila-restriccion-${r.id}`}>
                <span>{r.restriccion}</span>
                <span>{ETIQUETAS_ESTADO[r.estadoLiberacion]}</span>
                <button type="button" onClick={() => setGestionandoId(r.id)}>
                  Gestionar
                </button>
                <p>
                  {accion.texto} <strong>{accion.contacto}</strong>
                </p>
              </div>

              {gestionandoId === r.id && (
                <PanelGestion restriccion={r} onCancel={() => setGestionandoId(null)} onGuardada={handleGuardada} />
              )}
            </Fragment>
          )
        })}
    </div>
  )
}
