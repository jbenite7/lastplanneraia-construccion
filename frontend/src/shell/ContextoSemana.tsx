import { useState } from 'react';
import type { SemanaActiva } from '../lib/api/esquemas/contexto';
import { useContextoSemana } from './useContextoSemana';

type PropiedadesContextoSemana = {
  semana: SemanaActiva | null;
  csrfToken: string;
  /** Refresca el bootstrap completo — nunca se pinta una copia local optimista (spec T01 §11). */
  recargar: () => Promise<void>;
};

function formatoRango(startsOn: string, endsOn: string): string {
  return `${startsOn} – ${endsOn}`;
}

/**
 * Selector semanal del rail (Tarea 5, T01): número/rango, y SOLO las acciones que el manifiesto
 * del servidor emite (`semana.actions`) — sin catálogo propio de reglas. Crear y eliminar
 * refrescan el estado canónico completo tras cada mutación; ninguna reintenta automáticamente.
 */
export function ContextoSemana({ semana, csrfToken, recargar }: PropiedadesContextoSemana) {
  const { seleccionando, creando, eliminando, error, seleccionar, crear, eliminarUltima } = useContextoSemana(
    csrfToken,
    recargar,
  );
  const [dialogoCrearAbierto, setDialogoCrearAbierto] = useState(false);
  const [dialogoEliminarAbierto, setDialogoEliminarAbierto] = useState(false);
  const [fechaInicio, setFechaInicio] = useState('');

  if (semana === null) {
    return null;
  }

  const semanaActual = semana;
  const opcionActual = semanaActual.options.find((opcion) => opcion.number === semanaActual.current) ?? null;

  async function alConfirmarCrear() {
    if (fechaInicio === '') return;
    const exito = await crear(fechaInicio);
    if (exito) {
      setDialogoCrearAbierto(false);
      setFechaInicio('');
    }
  }

  async function alConfirmarEliminar() {
    const exito = await eliminarUltima(semanaActual.current);
    if (exito) {
      setDialogoEliminarAbierto(false);
    }
  }

  return (
    <div className="aia-sidebar__week" data-aia-component="contexto-semana">
      <span className="aia-sidebar__week-label">
        Semana {semana.current}
        {opcionActual && <small> · {formatoRango(opcionActual.startsOn, opcionActual.endsOn)}</small>}
      </span>

      {semana.actions.select && semana.options.length > 1 && (
        <label className="aia-sidebar__week-select">
          <span className="aia-visually-hidden">Cambiar de semana</span>
          <select
            value={semana.current}
            disabled={seleccionando}
            onChange={(evento) => void seleccionar(Number(evento.target.value))}
          >
            {semana.options.map((opcion) => (
              <option key={opcion.number} value={opcion.number}>
                Semana {opcion.number} ({formatoRango(opcion.startsOn, opcion.endsOn)})
              </option>
            ))}
          </select>
        </label>
      )}

      <div className="aia-sidebar__week-actions">
        {semana.actions.create && (
          <button
            type="button"
            className="aia-btn aia-btn--secondary"
            onClick={() => setDialogoCrearAbierto(true)}
          >
            Crear semana
          </button>
        )}
        {semana.actions.deleteLast && (
          <button
            type="button"
            className="aia-btn aia-btn--secondary"
            onClick={() => setDialogoEliminarAbierto(true)}
          >
            Eliminar semana {semana.current}
          </button>
        )}
      </div>

      {error && (
        <p role="alert" className="aia-alert aia-alert--error">
          {error}
        </p>
      )}

      {dialogoCrearAbierto && (
        <div role="dialog" aria-label="Crear nueva semana" className="aia-sidebar__week-dialog">
          <label htmlFor="contexto-semana-fecha-inicio">Fecha de inicio</label>
          <input
            id="contexto-semana-fecha-inicio"
            type="date"
            value={fechaInicio}
            onChange={(evento) => setFechaInicio(evento.target.value)}
          />
          <div className="aia-sidebar__week-dialog-actions">
            <button type="button" onClick={() => setDialogoCrearAbierto(false)} disabled={creando}>
              Cancelar
            </button>
            <button
              type="button"
              className="aia-btn aia-btn--primary"
              onClick={() => void alConfirmarCrear()}
              disabled={creando || fechaInicio === ''}
            >
              {creando ? 'Creando…' : 'Crear'}
            </button>
          </div>
        </div>
      )}

      {dialogoEliminarAbierto && (
        <div role="dialog" aria-label={`Eliminar la Semana ${semana.current}`} className="aia-sidebar__week-dialog">
          <p>
            ¿Eliminar la Semana {semana.current}
            {opcionActual && ` (${formatoRango(opcionActual.startsOn, opcionActual.endsOn)})`}? Esta acción no se
            puede deshacer.
          </p>
          <div className="aia-sidebar__week-dialog-actions">
            <button type="button" onClick={() => setDialogoEliminarAbierto(false)} disabled={eliminando}>
              Cancelar
            </button>
            <button
              type="button"
              className="aia-btn aia-btn--danger"
              onClick={() => void alConfirmarEliminar()}
              disabled={eliminando}
            >
              {eliminando ? 'Eliminando…' : 'Eliminar'}
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
