import { useState } from 'react';
import { construirTextoSos, triggerSos, urlCorreo, urlWhatsapp } from '../dominio/crisis';

export interface ContactosSos {
  telefono?: string;
  correo?: string;
}

export interface RegistrarCrisisInput {
  consecutivo: number;
  modulo: 'PG' | 'PI' | 'PS';
  trigger: string;
}

type PropiedadesAccionesSos = {
  /** `lps_simulated_mode` (D-T02-10): en simulación sólo se prepara/copia, nunca se muta. */
  simulado: boolean;
  consecutivo: number;
  modulo: 'PG' | 'PI' | 'PS';
  actividad: string;
  subcontratista: string;
  restriccion: string;
  nivelActual: number;
  /** Del dataset ya autorizado del módulo (T02-AC-120) — nunca se registra en logs/evidencia. */
  contactos?: ContactosSos;
  /** `!actions.notifyNext` del servidor (T02-AC-121): nivel terminal o alerta ya cerrada. */
  deshabilitado?: boolean;
  /** Adapter inyectado: POST /api/lps/crisis/register vía `cliente.ts` (nunca un fetch propio aquí). */
  registrarCrisis: (input: RegistrarCrisisInput) => Promise<void>;
  /** Adapter inyectado: `navigator.clipboard.writeText`. */
  copiarAlPortapapeles: (texto: string) => Promise<void>;
  /** Adapter inyectado: `window.open`. Nunca se llama directo desde este componente. */
  abrirCanal: (url: string) => void;
};

type Canal = 'whatsapp' | 'correo';

/**
 * Acciones SOS del cajón de crisis (T02-AC-114..121). Puerto de comportamiento de
 * `triggerEscalate()` (`lps_drawer.js:1174-1246`): en simulación sólo copia el texto; en modo
 * operativo registra primero la alerta (D-T02-09: mutación autoritativa, sin retry automático) y
 * sólo después abre el canal externo — o cae a copiar si el contacto no existe (T02-AC-117). El
 * canal externo es siempre handoff del navegador: esta acción nunca se llama "enviado"
 * (T02-AC-118).
 */
export function AccionesSos({
  simulado,
  consecutivo,
  modulo,
  actividad,
  subcontratista,
  restriccion,
  nivelActual,
  contactos,
  deshabilitado = false,
  registrarCrisis,
  copiarAlPortapapeles,
  abrirCanal,
}: PropiedadesAccionesSos) {
  const [textoManual, setTextoManual] = useState<string | null>(null);
  const [estado, setEstado] = useState<string | null>(null);
  const [enviando, setEnviando] = useState(false);

  async function copiarConFallback(texto: string): Promise<void> {
    try {
      await copiarAlPortapapeles(texto);
      setTextoManual(null);
      setEstado((previo) => previo ?? 'Texto SOS copiado al portapapeles.');
    } catch {
      // T02-AC-119: un fallo de portapapeles conserva texto seleccionable y feedback recuperable.
      setTextoManual(texto);
      setEstado('No se pudo copiar automáticamente. Selecciona y copia el texto manualmente.');
    }
  }

  async function manejarClic(canal: Canal): Promise<void> {
    if (deshabilitado || enviando) return;

    setEnviando(true);
    setEstado(null);

    const texto = construirTextoSos({ consecutivo, actividad, subcontratista, restriccion, nivelActual });

    try {
      if (simulado) {
        await copiarConFallback(texto);
        return;
      }

      // Modo operativo: se registra primero (T02-AC-116) y sólo entonces se decide canal/copia.
      await registrarCrisis({ consecutivo, modulo, trigger: triggerSos(nivelActual) });

      const contacto = canal === 'whatsapp' ? contactos?.telefono : contactos?.correo;
      if (!contacto) {
        setEstado(
          canal === 'whatsapp'
            ? 'Sin teléfono asignado. Se copió el texto SOS al portapapeles.'
            : 'Sin correo asignado. Se copió el texto SOS al portapapeles.',
        );
        await copiarConFallback(texto);
        return;
      }

      abrirCanal(canal === 'whatsapp' ? urlWhatsapp(contacto, texto) : urlCorreo(contacto, texto));
    } finally {
      setEnviando(false);
    }
  }

  return (
    <div className="lps-acciones-sos">
      <p role="status" aria-live="polite">
        {estado}
      </p>
      <button type="button" onClick={() => void manejarClic('whatsapp')} disabled={deshabilitado || enviando}>
        SOS WhatsApp
      </button>
      <button type="button" onClick={() => void manejarClic('correo')} disabled={deshabilitado || enviando}>
        SOS Correo
      </button>
      {textoManual !== null ? (
        <label>
          Texto SOS (copia manual)
          <textarea
            readOnly
            value={textoManual}
            onFocus={(evento) => evento.currentTarget.select()}
          />
        </label>
      ) : null}
    </div>
  );
}
