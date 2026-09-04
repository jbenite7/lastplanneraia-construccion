import { useId, useState } from 'react';

export interface EnviarComentarioInput {
  comentario: string;
  parentId?: number;
  menciones?: { roles: string[] };
}

type PropiedadesFormularioComentario = {
  deshabilitado?: boolean;
  /** Cuando se responde a una raíz, muestra la relación visible (T02-AC-165 "respuesta con relación visible"). */
  respondiendoA?: { id: number; autor: string } | null;
  alCancelarRespuesta?: () => void;
  enviar: (input: EnviarComentarioInput) => Promise<void>;
  /** Notifica cada cambio de texto — el cajón lo usa para decidir si hay borrador que proteger (AC-160/161). */
  alCambiarTexto?: (texto: string) => void;
};

/**
 * Formulario de comentario/respuesta (T02-AC-093..104). El borrador se conserva en estado local:
 * un error de `enviar()` no lo limpia (AC-103) y no hay reintento automático (AC-104) — cada click
 * es una llamada nueva.
 */
export function FormularioComentario({
  deshabilitado = false,
  respondiendoA,
  alCancelarRespuesta,
  enviar,
  alCambiarTexto,
}: PropiedadesFormularioComentario) {
  const [texto, setTexto] = useState('');
  const [enviando, setEnviando] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const idCampo = useId();

  async function manejarEnvio(evento: React.FormEvent<HTMLFormElement>): Promise<void> {
    evento.preventDefault();
    const recortado = texto.trim();
    if (deshabilitado || enviando || recortado === '') return;

    setEnviando(true);
    setError(null);
    try {
      await enviar({ comentario: recortado, parentId: respondiendoA?.id });
      setTexto('');
      alCambiarTexto?.('');
    } catch {
      // AC-103: el borrador se conserva tal cual — no se limpia `texto`.
      setError('No se pudo enviar el comentario. Intenta de nuevo.');
    } finally {
      setEnviando(false);
    }
  }

  return (
    <form className="lps-formulario-comentario" onSubmit={(evento) => void manejarEnvio(evento)}>
      {respondiendoA ? (
        <p>
          Respondiendo a <strong>{respondiendoA.autor}</strong>{' '}
          <button type="button" onClick={alCancelarRespuesta}>
            Cancelar respuesta
          </button>
        </p>
      ) : null}
      <label htmlFor={idCampo}>Comentario</label>
      <textarea
        id={idCampo}
        value={texto}
        onChange={(evento) => {
          setTexto(evento.target.value);
          alCambiarTexto?.(evento.target.value);
        }}
        disabled={deshabilitado || enviando}
        aria-invalid={error !== null}
      />
      {error ? (
        <p role="status">{error}</p>
      ) : null}
      <button type="submit" disabled={deshabilitado || enviando || texto.trim() === ''}>
        Comentar
      </button>
    </form>
  );
}
