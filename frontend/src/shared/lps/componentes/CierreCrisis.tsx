import { useId, useState } from 'react';

const MINIMO_JUSTIFICACION = 100;

export interface CerrarCrisisInput {
  alertaId: number;
  justificacion: string;
}

type PropiedadesCierreCrisis = {
  alertaId: number;
  /** `!actions.close` del servidor: actor no elegible, sin capacidad o alerta ya no activa. */
  deshabilitado?: boolean;
  /** Adapter inyectado: POST /api/lps/crisis/close vía `cliente.ts`. */
  cerrarCrisis: (input: CerrarCrisisInput) => Promise<void>;
  /**
   * Éxito de cierre no limpia banderas localmente (T02-AC-128): este callback es la señal para
   * que el consumidor pida el snapshot/hilo autoritativo de nuevo (T02-AC-127), no un mutador de
   * estado local.
   */
  alCerrarConExito?: () => void;
};

/**
 * Formulario de cierre formal de crisis (T02-AC-122..129). Puerto de comportamiento de
 * `closeCrisisAlert()` (`lps_drawer.js:1085-1122`): la justificación se recorta y exige al menos
 * 100 caracteres tanto en cliente como en servidor (T02-AC-124); un error de mutación conserva el
 * borrador y no cierra nada por su cuenta (T02-AC-126) — el consumidor decide si el drawer sigue
 * abierto. Sin retry automático (D-T02-09).
 */
export function CierreCrisis({ alertaId, deshabilitado = false, cerrarCrisis, alCerrarConExito }: PropiedadesCierreCrisis) {
  const [justificacion, setJustificacion] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [enviando, setEnviando] = useState(false);
  const idCampo = useId();
  const idError = useId();

  const longitud = justificacion.trim().length;
  const cumpleMinimo = longitud >= MINIMO_JUSTIFICACION;

  async function manejarEnvio(evento: React.FormEvent<HTMLFormElement>): Promise<void> {
    evento.preventDefault();
    if (deshabilitado || enviando) return;

    const recortada = justificacion.trim();
    if (recortada.length < MINIMO_JUSTIFICACION) {
      setError(`La justificación debe tener al menos ${MINIMO_JUSTIFICACION} caracteres (van ${recortada.length}).`);
      return;
    }

    setEnviando(true);
    setError(null);

    try {
      await cerrarCrisis({ alertaId, justificacion: recortada });
      // Éxito: no se limpia `justificacion` de forma optimista — T02-AC-128 es sobre banderas de
      // crisis, pero el mismo espíritu aplica al borrador: el consumidor recarga y, con eso, este
      // componente deja de montarse cuando la alerta ya no está activa.
      alCerrarConExito?.();
    } catch {
      // T02-AC-126: error de cierre conserva justificación y drawer abierto — no se limpia nada.
      setError('No se pudo cerrar la crisis. Intenta de nuevo.');
    } finally {
      setEnviando(false);
    }
  }

  return (
    <form className="lps-cierre-crisis" onSubmit={(evento) => void manejarEnvio(evento)}>
      <label htmlFor={idCampo}>Justificación del cierre</label>
      <textarea
        id={idCampo}
        value={justificacion}
        onChange={(evento) => setJustificacion(evento.target.value)}
        aria-describedby={idError}
        aria-invalid={error !== null}
        disabled={deshabilitado || enviando}
        minLength={MINIMO_JUSTIFICACION}
      />
      <p id={idError} role="status">
        {longitud}/{MINIMO_JUSTIFICACION} caracteres{cumpleMinimo ? ' — mínimo alcanzado' : ''}
        {error ? ` — ${error}` : ''}
      </p>
      <button type="submit" disabled={deshabilitado || enviando || !cumpleMinimo}>
        Cerrar crisis
      </button>
    </form>
  );
}
