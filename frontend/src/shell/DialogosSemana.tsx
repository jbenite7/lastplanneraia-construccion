import { useEffect, useRef, useState } from 'react';
import type { SemanaActiva } from '../lib/api/esquemas/contexto';
import { useContextoSemana } from './useContextoSemana';

export type DialogoSemana = { vista: 'crear' } | { vista: 'eliminar'; semana: number };

type PropiedadesDialogosSemana = {
  semana: SemanaActiva | null;
  csrfToken: string;
  recargar: () => Promise<void>;
  /** Qué confirmación mostrar; `null` = ninguna. La lista de semanas vive en el flyout del riel. */
  dialogo: DialogoSemana | null;
  alCerrar: () => void;
};

function rango(semana: SemanaActiva, numero: number): string {
  const opcion = semana.options.find((candidata) => candidata.number === numero);
  return opcion ? ` (del ${opcion.startsOn} al ${opcion.endsOn})` : '';
}

/** Día siguiente al fin de la última semana (`YYYY-MM-DD`); vacío si no hay semanas ni fecha válida. */
function fechaSugerida(semana: SemanaActiva): string {
  const ultima = semana.options.reduce<SemanaActiva['options'][number] | null>(
    (mayor, opcion) => (mayor === null || opcion.number > mayor.number ? opcion : mayor),
    null,
  );
  if (ultima === null) return '';
  const fin = new Date(`${ultima.endsOn}T00:00:00Z`);
  if (Number.isNaN(fin.getTime())) return '';
  fin.setUTCDate(fin.getUTCDate() + 1);
  return fin.toISOString().slice(0, 10);
}

/**
 * Confirmaciones de «Crear semana» y «Eliminar semana» del shell, con el contrato visual y de
 * permisos del shell PHP. Selector, «+ Nueva semana» y papelera viven en el flyout del riel
 * (`FlyoutSemanas`); aquí solo se confirma.
 *
 * Es un `<dialog>` abierto con `showModal()`. Un `<dialog open>` a secas no entra en la capa
 * superior del navegador: queda en flujo normal, o sea al final del `<body>`, fuera de la
 * pantalla (medido el 2026-09-29 en Programa General: `y=746` con un viewport de 746). Con
 * `showModal()` salen gratis el velo y Escape, igual que el legado (`shell_sidebar.php`), y el
 * envoltorio `.aia-dialog` trae el ancho y la hoja inferior en móvil del design system.
 */
export function DialogosSemana({ semana, csrfToken, recargar, dialogo, alCerrar }: PropiedadesDialogosSemana) {
  // `null` = el usuario no ha tocado el campo: se muestra la sugerida, que se recalcula sola. Un
  // estado que copiara la sugerida en un efecto pisaría lo tecleado cada vez que `sesion.week`
  // cambiara de identidad (hallazgo 6 de la revisión).
  const [fechaEditada, setFechaEditada] = useState<string | null>(null);
  const dialogoRef = useRef<HTMLDialogElement>(null);
  const { crear, creando, eliminarUltima, eliminando, error } = useContextoSemana(csrfToken, recargar);
  const visible = dialogo !== null && semana !== null;

  useEffect(() => {
    const elemento = dialogoRef.current;
    if (!visible || !elemento || elemento.open) return;
    elemento.showModal();
  }, [visible]);

  // Cada apertura de «Crear» arranca con la sugerida, no con lo tecleado en el intento anterior.
  if (dialogo === null && fechaEditada !== null) setFechaEditada(null);

  if (dialogo === null || semana === null) return null;

  const semanaActiva = semana;
  const fechaInicio = fechaEditada ?? fechaSugerida(semanaActiva);
  const siguienteSemana = Math.max(...semanaActiva.options.map((opcion) => opcion.number), semanaActiva.current) + 1;

  async function confirmarCrear() {
    if (fechaInicio === '') return;
    if (await crear(fechaInicio)) alCerrar();
  }

  async function confirmarEliminar(numero: number) {
    if (await eliminarUltima(numero)) alCerrar();
  }

  const esCrear = dialogo.vista === 'crear';
  const idTitulo = esCrear ? 'shellWeekCreateTitle' : 'shellWeekDeleteTitle';
  const idTexto = esCrear ? 'shellWeekCreateDesc' : 'shellWeekDeleteText';

  return (
    <div className="aia-dialog" data-aia-component="dialog">
      <dialog
        aria-describedby={idTexto}
        aria-labelledby={idTitulo}
        className="aia-modal-surface shell-week-dialog"
        onClose={alCerrar}
        ref={dialogoRef}
      >
        {dialogo.vista === 'crear' ? (
          <>
            <h3 id="shellWeekCreateTitle">Crear Semana {siguienteSemana}</h3>
            <p className="shell-week-dialog__copy" id="shellWeekCreateDesc">La nueva semana se convierte en la semana activa del proyecto.</p>
            <label className="shell-week-dialog__label" htmlFor="shellWeekCreateDate">Fecha de inicio</label>
            <input className="shell-week-dialog__date" id="shellWeekCreateDate" onChange={(evento) => setFechaEditada(evento.target.value)} type="date" value={fechaInicio} />
            {error && <p className="aia-alert aia-alert--error" role="alert">{error}</p>}
            <div className="shell-week-dialog__actions">
              <button className="aia-btn" disabled={creando || fechaInicio === ''} onClick={() => void confirmarCrear()} type="button">{creando ? 'Creando…' : 'Crear semana'}</button>
              <button className="aia-btn aia-btn--secondary" disabled={creando} onClick={alCerrar} type="button">Cancelar</button>
            </div>
          </>
        ) : (
          <>
            <h3 id="shellWeekDeleteTitle">Eliminar Semana {dialogo.semana}</h3>
            <p className="shell-week-dialog__copy" id="shellWeekDeleteText">¿Eliminar la Semana {dialogo.semana}{rango(semanaActiva, dialogo.semana)}? Esta acción elimina su programación y no se puede deshacer.</p>
            {error && <p className="aia-alert aia-alert--error" role="alert">{error}</p>}
            <div className="shell-week-dialog__actions">
              <button className="aia-btn shell-week-dialog__danger" disabled={eliminando} onClick={() => void confirmarEliminar(dialogo.semana)} type="button">{eliminando ? 'Eliminando…' : 'Eliminar semana'}</button>
              <button className="aia-btn aia-btn--secondary" disabled={eliminando} onClick={alCerrar} type="button">Cancelar</button>
            </div>
          </>
        )}
      </dialog>
    </div>
  );
}
