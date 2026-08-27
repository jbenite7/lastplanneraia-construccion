import './AlarmaHuerfanas.css'
import type { Restriccion } from '../lib/api'

// Alarma de huérfanas (ct-app, etapa piloto, Task 7 ensamblaje — posición 1 del lienzo de
// Intermedia, CT-8.3): «las restricciones sin análisis ni dueño, con la acción de asignarlas».
//
// PRESENTACIONAL: no hace fetch, no conoce la lista completa de restricciones ni aplica el
// criterio de "huérfana" — recibe `huerfanas` YA FILTRADA por quien ensambla la hoja
// (Intermedia.tsx aplica `estadoLiberacion === 'sin_gestionar' && responsableAsignado === null`,
// documentado en AlarmaHuerfanas.test.tsx).
//
// Mecanismo de la acción: un botón "Ver huérfanas" que invoca `onVerHuerfanas()` sin argumentos —
// el padre decide qué hacer con eso (Intermedia.tsx filtra lo que le pasa a ListaRestricciones).
//
// Caso borde, cero huérfanas: la sección SÍ se renderiza (la posición 1 queda estable en el
// lienzo), pero con un mensaje neutro de confirmación y SIN el botón de acción, para no generar
// una alarma falsa cuando el lookahead está sano.

interface AlarmaHuerfanasProps {
  huerfanas: Restriccion[]
  onVerHuerfanas: () => void
}

function pluralRestricciones(count: number): string {
  return count === 1 ? 'restricción' : 'restricciones'
}

export function AlarmaHuerfanas({ huerfanas, onVerHuerfanas }: AlarmaHuerfanasProps) {
  const count = huerfanas.length

  return (
    <section
      data-testid="alarma-huerfanas"
      aria-label="Alarma de restricciones huérfanas"
      className={count > 0 ? 'ct-alarma ct-alarma--atencion' : 'ct-alarma'}
    >
      {count > 0 ? (
        <>
          <p className="ct-alarma-texto">
            {count} {pluralRestricciones(count)} sin análisis ni responsable asignado.
          </p>
          <button type="button" className="ct-alarma-boton" onClick={() => onVerHuerfanas()}>
            Ver huérfanas
          </button>
        </>
      ) : (
        <p className="ct-alarma-texto ct-alarma-texto--neutro">
          Todas las restricciones están gestionadas: sin pendientes huérfanas esta semana.
        </p>
      )}
    </section>
  )
}
