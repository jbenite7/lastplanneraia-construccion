import { useEffect, useRef, useState } from 'react';
import type { SemanaActiva } from '../lib/api/esquemas/contexto';
import { useContextoSemana } from './useContextoSemana';

type PropiedadesBarraContexto = {
  proyecto: string;
  modulo: string;
  semana: SemanaActiva | null;
  csrfToken: string;
  recargar: () => Promise<void>;
};

function rango({ startsOn, endsOn }: SemanaActiva['options'][number]) {
  return `Del ${startsOn} al ${endsOn}`;
}

/** Contexto compartido con el shell PHP: proyecto, módulo y selector de semana. */
export function BarraContexto({ proyecto, modulo, semana, csrfToken, recargar }: PropiedadesBarraContexto) {
  const [abierto, setAbierto] = useState(false);
  const chipRef = useRef<HTMLButtonElement>(null);
  const { seleccionar, seleccionando } = useContextoSemana(csrfToken, recargar);

  if (semana === null) return null;

  function cerrar() {
    setAbierto(false);
    chipRef.current?.focus();
  }

  useEffect(() => {
    if (!abierto) return;
    function alEscape(evento: KeyboardEvent) {
      if (evento.key === 'Escape') cerrar();
    }
    document.addEventListener('keydown', alEscape);
    return () => document.removeEventListener('keydown', alEscape);
  }, [abierto]);

  function alTeclado(evento: React.KeyboardEvent<HTMLDivElement>) {
    if (evento.key === 'Escape') {
      evento.preventDefault();
      cerrar();
      return;
    }
    if (evento.key !== 'ArrowDown' && evento.key !== 'ArrowUp') return;

    const opciones = [...evento.currentTarget.querySelectorAll<HTMLButtonElement>('[role="menuitem"]')];
    const indice = opciones.indexOf(document.activeElement as HTMLButtonElement);
    const siguiente = evento.key === 'ArrowDown'
      ? opciones[(indice + 1 + opciones.length) % opciones.length]
      : opciones[(indice - 1 + opciones.length) % opciones.length];
    evento.preventDefault();
    siguiente?.focus();
  }

  return (
    <div className="context-bar" id="shellContextBar">
      <span id="ctxProyecto">{proyecto}</span>
      <span aria-hidden="true">/</span>
      <span id="ctxModulo">{modulo}</span>
      <div className="aia-menu context-week-menu" data-aia-component="menu">
        <button
          ref={chipRef}
          aria-controls="ctxWeekMenu"
          aria-expanded={abierto}
          aria-haspopup="menu"
          className="context-week-chip"
          id="ctxSemanaBadge"
          onClick={() => setAbierto((valor) => !valor)}
          type="button"
        >
          <span id="ctxSemanaTexto">Semana {semana.current}</span>
        </button>
        {abierto && (
          <div data-aia-menu-panel id="ctxWeekMenu" onKeyDown={alTeclado} role="menu">
            {semana.options.map((opcion) => (
              <button
                aria-current={opcion.number === semana.current ? 'true' : undefined}
                disabled={seleccionando || !semana.actions.select}
                key={opcion.number}
                onClick={() => {
                  void seleccionar(opcion.number);
                  setAbierto(false);
                }}
                role="menuitem"
                type="button"
              >
                <span>Semana {opcion.number}</span>
                <small>{rango(opcion)}</small>
              </button>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
