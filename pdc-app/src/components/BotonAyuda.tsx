import { useContext, useEffect, useRef, useState } from 'react'
import { ayudaDe } from '../lib/ayuda'
import type { PantallaAyuda } from '../lib/ayuda'
import { AyudaContexto } from '../lib/ayudaContexto'

/**
 * El botón de ayuda de una pantalla. UNO para las ocho: recibe qué pantalla es y saca su contenido
 * de `lib/ayuda.ts`. Un panel por vista era la forma segura de que ocho paneles se fueran
 * separando entre sí.
 *
 * Usa `<dialog>` nativo con la primitiva `aia-dialog` del sistema de diseño —que llega a esta ruta
 * por el agregador `aia-design-system.css`—, y por eso sale gratis lo que más se rompe a mano: el
 * cierre con `Escape`, el foco atrapado dentro y el fondo inerte.
 *
 * Las clases propias van con prefijo `pdc-guia-` y no `pdc-ayuda-`: `.pdc-ayuda` ya existe en este
 * módulo desde antes, y es el texto de ayuda de un campo (atenuado y pequeño). Reutilizar ese
 * nombre habría pintado el panel al 60% de opacidad.
 *
 * Los botones van sin clase: dentro de `.pdc-shell` el módulo ya les da su alto y su tipografía
 * densos, y ponerles `aia-btn` los habría sacado de esa escala.
 */
export default function BotonAyuda({ pantalla }: { pantalla: PantallaAyuda }) {
  const contenido = ayudaDe(pantalla)
  const { relanzarRecorrido } = useContext(AyudaContexto)
  const dialogo = useRef<HTMLDialogElement>(null)
  const disparador = useRef<HTMLButtonElement>(null)
  const [abierto, setAbierto] = useState(false)

  // `showModal()` no es declarativo, así que hay que llamarlo.
  useEffect(() => {
    const el = dialogo.current
    if (!el) return
    if (abierto && !el.open) el.showModal()
    if (!abierto && el.open) el.close()
  }, [abierto])

  return (
    <>
      <button
        ref={disparador}
        type="button"
        className="pdc-guia-boton"
        aria-label={`Ayuda de ${contenido.titulo}`}
        data-testid={`pdc-ayuda-boton-${pantalla}`}
        onClick={() => setAbierto(true)}
      >
        {/* El símbolo que la empresa ya reconoce del visor de cronogramas. Decorativo: lo que
            anuncia el botón es su aria-label, y leer «signo de interrogación» no ayuda a nadie. */}
        <i className="fas fa-question-circle" aria-hidden="true" />
      </button>

      <dialog
        ref={dialogo}
        className="aia-dialog pdc-guia"
        aria-labelledby={`pdc-guia-titulo-${pantalla}`}
        data-testid={`pdc-ayuda-panel-${pantalla}`}
        // Cubre el Escape y el clic en el fondo, que no pasan por nuestro botón de cerrar. Al
        // cerrar devolvemos el foco al disparador: sin esto, quien navega con teclado vuelve al
        // principio de la página y pierde el sitio.
        onClose={() => { setAbierto(false); disparador.current?.focus() }}
      >
        <div className="aia-modal-surface pdc-guia-cuerpo">
          <header className="pdc-guia-encabezado">
            <h2 id={`pdc-guia-titulo-${pantalla}`} className="pdc-guia-titulo">
              {contenido.titulo}
            </h2>
            <button
              type="button"
              data-testid={`pdc-ayuda-cerrar-${pantalla}`}
              onClick={() => setAbierto(false)}
            >
              Cerrar
            </button>
          </header>

          {/* El orden de estos tres bloques es el contrato, no una preferencia de maquetación:
              qué hace esta pantalla · qué tengo que hacer yo · qué pasa después. */}
          <section className="pdc-guia-seccion">
            <h3 className="pdc-guia-pregunta">Qué hace esta pantalla</h3>
            <p className="pdc-guia-texto">{contenido.queHace}</p>
          </section>

          <section className="pdc-guia-seccion">
            <h3 className="pdc-guia-pregunta">Qué tengo que hacer yo aquí</h3>
            <ol className="pdc-guia-pasos">
              {contenido.queHagoYo.map((paso) => (
                <li key={paso} className="pdc-guia-texto">{paso}</li>
              ))}
            </ol>
          </section>

          <section className="pdc-guia-seccion">
            <h3 className="pdc-guia-pregunta">Qué pasa después</h3>
            <p className="pdc-guia-texto">{contenido.quePasaDespues}</p>
          </section>

          {contenido.apartados.length > 0 && (
            <section className="pdc-guia-seccion">
              <h3 className="pdc-guia-pregunta">Las partes de esta pantalla</h3>
              <dl className="pdc-guia-apartados">
                {contenido.apartados.map((a) => (
                  <div key={a.etiqueta} className="pdc-guia-apartado">
                    <dt className="pdc-guia-apartado-etiqueta">{a.etiqueta}</dt>
                    <dd className="pdc-guia-texto">{a.texto}</dd>
                  </div>
                ))}
              </dl>
            </section>
          )}

          {relanzarRecorrido && (
            <footer className="pdc-guia-pie">
              <button
                type="button"
                data-testid={`pdc-ayuda-relanzar-${pantalla}`}
                onClick={() => { setAbierto(false); relanzarRecorrido() }}
              >
                Ver otra vez el recorrido del módulo
              </button>
            </footer>
          )}
        </div>
      </dialog>
    </>
  )
}
