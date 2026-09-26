import { useState } from 'react';
import type { SemanaActiva } from '../lib/api/esquemas/contexto';
import { useContextoSemana } from './useContextoSemana';

type PropiedadesDialogosSemana = {
  semana: SemanaActiva | null;
  csrfToken: string;
  recargar: () => Promise<void>;
  abierto: boolean;
  alCerrar: () => void;
};

type DialogoAbierto = 'gestionar' | 'crear' | 'eliminar' | null;

function rango(semana: SemanaActiva, numero: number): string {
  const opcion = semana.options.find((candidata) => candidata.number === numero);
  return opcion ? ` (del ${opcion.startsOn} al ${opcion.endsOn})` : '';
}

/** Diálogos de gestión semanal con el contrato visual y de permisos del shell PHP. */
export function DialogosSemana({ semana, csrfToken, recargar, abierto, alCerrar }: PropiedadesDialogosSemana) {
  const [dialogo, setDialogo] = useState<DialogoAbierto>(null);
  const [fechaInicio, setFechaInicio] = useState('');
  const { crear, creando, eliminarUltima, eliminando, error } = useContextoSemana(csrfToken, recargar);

  if (!abierto || semana === null) return null;

  const semanaActiva = semana;
  const visible = dialogo ?? 'gestionar';
  const siguienteSemana = Math.max(...semanaActiva.options.map((opcion) => opcion.number), semanaActiva.current) + 1;

  function cerrarTodo() {
    setDialogo(null);
    setFechaInicio('');
    alCerrar();
  }

  async function confirmarCrear() {
    if (fechaInicio === '') return;
    if (await crear(fechaInicio)) cerrarTodo();
  }

  async function confirmarEliminar() {
    if (await eliminarUltima(semanaActiva.current)) cerrarTodo();
  }

  if (visible === 'gestionar') {
    return (
      <dialog aria-labelledby="shellWeekManageTitle" className="aia-modal-surface shell-week-dialog" open>
        <h3 id="shellWeekManageTitle">Semanas del Proyecto</h3>
        <div className="shell-week-dialog__actions">
          {semanaActiva.actions.create && <button className="aia-btn" onClick={() => setDialogo('crear')} type="button">Crear semana</button>}
          {semanaActiva.actions.deleteLast && <button className="aia-btn shell-week-dialog__danger" onClick={() => setDialogo('eliminar')} type="button">Eliminar semana {semanaActiva.current}</button>}
          <button className="aia-btn aia-btn--secondary" onClick={cerrarTodo} type="button">Cancelar</button>
        </div>
      </dialog>
    );
  }

  if (visible === 'crear') {
    return (
      <dialog aria-describedby="shellWeekCreateDesc" aria-labelledby="shellWeekCreateTitle" className="aia-modal-surface shell-week-dialog" open>
        <h3 id="shellWeekCreateTitle">Crear Semana {siguienteSemana}</h3>
        <p className="shell-week-dialog__copy" id="shellWeekCreateDesc">La nueva semana se convierte en la semana activa del proyecto.</p>
        <label className="shell-week-dialog__label" htmlFor="shellWeekCreateDate">Fecha de inicio</label>
        <input className="shell-week-dialog__date" id="shellWeekCreateDate" onChange={(evento) => setFechaInicio(evento.target.value)} type="date" value={fechaInicio} />
        {error && <p className="aia-alert aia-alert--error" role="alert">{error}</p>}
        <div className="shell-week-dialog__actions">
          <button className="aia-btn" disabled={creando || fechaInicio === ''} onClick={() => void confirmarCrear()} type="button">{creando ? 'Creando…' : 'Crear semana'}</button>
          <button className="aia-btn aia-btn--secondary" disabled={creando} onClick={() => setDialogo('gestionar')} type="button">Cancelar</button>
        </div>
      </dialog>
    );
  }

  return (
    <dialog aria-describedby="shellWeekDeleteText" aria-labelledby="shellWeekDeleteTitle" className="aia-modal-surface shell-week-dialog" open>
      <h3 id="shellWeekDeleteTitle">Eliminar Semana {semanaActiva.current}</h3>
      <p className="shell-week-dialog__copy" id="shellWeekDeleteText">¿Eliminar la Semana {semanaActiva.current}{rango(semanaActiva, semanaActiva.current)}? Esta acción elimina su programación y no se puede deshacer.</p>
      {error && <p className="aia-alert aia-alert--error" role="alert">{error}</p>}
      <div className="shell-week-dialog__actions">
        <button className="aia-btn shell-week-dialog__danger" disabled={eliminando} onClick={() => void confirmarEliminar()} type="button">{eliminando ? 'Eliminando…' : 'Eliminar semana'}</button>
        <button className="aia-btn aia-btn--secondary" disabled={eliminando} onClick={() => setDialogo('gestionar')} type="button">Cancelar</button>
      </div>
    </dialog>
  );
}
