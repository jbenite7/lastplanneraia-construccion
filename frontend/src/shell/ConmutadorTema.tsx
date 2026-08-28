import { useState } from 'react';
import { aplicarTema, leerTemaGuardado, type Tema } from './tema';

function alternar(tema: Tema): Tema {
  return tema === 'claro' ? 'oscuro' : 'claro';
}

export function ConmutadorTema() {
  const [tema, setTema] = useState<Tema>(leerTemaGuardado);
  const siguiente = alternar(tema);

  function cambiarTema() {
    aplicarTema(siguiente);
    setTema(siguiente);
  }

  return (
    <button
      aria-label={`Cambiar a tema ${siguiente}`}
      aria-pressed={tema === 'oscuro'}
      className="aia-sidebar__utility"
      onClick={cambiarTema}
      type="button"
    >
      <span aria-hidden="true">{tema === 'oscuro' ? '☾' : '☀'}</span>
      <span className="aia-sidebar__label">Tema: {tema}</span>
    </button>
  );
}
