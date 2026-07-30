import { useEffect, useRef, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { PASOS_RECORRIDO, marcarVisto } from '../lib/recorrido'

/**
 * La primera vuelta por el módulo. Se monta una vez, a nivel de módulo, no por pantalla.
 *
 * Va llevando al usuario por las rutas del flujo mientras explica cada parada, así que lo que se
 * ve detrás del panel es la pantalla de verdad y no una ilustración de ella.
 *
 * Omitible en el primer clic y no vuelve solo: quien ya sabe usar el módulo no debería tener que
 * esquivar esto cada mañana. Se relanza a mano desde cualquier botón de ayuda.
 */
export default function Recorrido({
  activo,
  onCerrar,
}: {
  activo: boolean
  onCerrar: () => void
}) {
  const [indice, setIndice] = useState(0)
  const dialogo = useRef<HTMLDialogElement>(null)
  const navegar = useNavigate()
  const paso = PASOS_RECORRIDO[indice]
  const ultimo = indice === PASOS_RECORRIDO.length - 1

  useEffect(() => {
    const el = dialogo.current
    if (!el) return
    if (activo && !el.open) { setIndice(0); el.showModal() }
    if (!activo && el.open) el.close()
  }, [activo])

  // Llevar la pantalla de fondo a la parada actual. Va en su propio efecto porque depende del
  // paso, no de si el recorrido está activo.
  useEffect(() => {
    if (activo && paso) navegar(paso.ruta)
  }, [activo, paso, navegar])

  // Terminar y omitir hacen lo mismo por dentro —no vuelve a salir— y es deliberado: castigar el
  // omitir haciéndolo reaparecer es lo que convierte una ayuda en una molestia.
  function cerrar() {
    marcarVisto()
    onCerrar()
  }

  if (!paso) return null

  return (
    <dialog
      ref={dialogo}
      className="aia-dialog pdc-recorrido"
      aria-labelledby="pdc-recorrido-titulo"
      data-testid="pdc-recorrido"
      onClose={cerrar}
    >
      <div className="aia-modal-surface pdc-recorrido-cuerpo">
        <p className="pdc-recorrido-progreso" data-testid="pdc-recorrido-progreso">
          Paso {indice + 1} de {PASOS_RECORRIDO.length}
        </p>
        <h2 id="pdc-recorrido-titulo" className="pdc-recorrido-titulo">{paso.titulo}</h2>
        <p className="pdc-recorrido-texto">{paso.texto}</p>

        <footer className="pdc-recorrido-pie">
          {/* Omitir va primero y siempre visible: es la salida, y esconderla en una esquina es la
              diferencia entre una ayuda y un peaje. */}
          <button type="button" data-testid="pdc-recorrido-omitir" onClick={cerrar}>
            Omitir
          </button>
          <div className="pdc-recorrido-avance">
            {indice > 0 && (
              <button
                type="button"
                data-testid="pdc-recorrido-atras"
                onClick={() => setIndice((i) => i - 1)}
              >
                Atrás
              </button>
            )}
            <button
              type="button"
              className="pdc-recorrido-principal"
              data-testid="pdc-recorrido-siguiente"
              onClick={() => (ultimo ? cerrar() : setIndice((i) => i + 1))}
            >
              {ultimo ? 'Entendido, empezar' : 'Siguiente'}
            </button>
          </div>
        </footer>
      </div>
    </dialog>
  )
}
