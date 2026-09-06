import { useRef } from 'react';
import type { Severidad } from '../dominio/severidad';
import type { LpsActivityContext } from '../estado/LpsDrawerProvider';
import { useLpsDrawer } from '../estado/useLpsDrawer';

const ETIQUETA_SEVERIDAD: Record<Severidad, string> = {
  critical: 'Crítico',
  attention: 'Atención',
  info: 'Informativo',
  neutral: 'Neutral',
  normal: 'Normal',
};

type PropiedadesDisparadorLps = {
  contexto: LpsActivityContext;
  severidad: Severidad;
  /** Texto visible del botón; por defecto usa `activity.label`. */
  etiqueta?: string;
};

/**
 * Botón nativo que abre el cajón (T02-AC-155/156): cada consumidor lo ubica en su propia
 * toolbar/tarjeta — este componente no decide layout, sólo el contrato de apertura. Nombre
 * accesible incluye siempre la severidad en texto (AC-072: nunca sólo color/icono/emoji).
 */
export function DisparadorLps({ contexto, severidad, etiqueta }: PropiedadesDisparadorLps) {
  const { abrir } = useLpsDrawer();
  const ref = useRef<HTMLButtonElement>(null);
  const texto = etiqueta ?? contexto.activity.label;
  const etiquetaSeveridad = ETIQUETA_SEVERIDAD[severidad];

  return (
    <button
      ref={ref}
      type="button"
      className="aia-btn aia-btn--secondary lps-disparador"
      aria-label={`${texto} — estado ${etiquetaSeveridad}`}
      onClick={() => abrir(contexto, ref.current)}
    >
      <span>{texto}</span>
      <span className={`lps-severidad lps-severidad--${severidad}`}>
        <span className="lps-severidad__punto" aria-hidden="true" />
        {etiquetaSeveridad}
      </span>
    </button>
  );
}
