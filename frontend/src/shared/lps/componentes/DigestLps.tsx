import { useState } from 'react';
import { compilarDigestSemanal } from '../dominio/digest';
import type { FilaLps } from '../dominio/campos';
import type { ConfiguracionRestricciones } from '../dominio/restricciones';

type PropiedadesDigestLps = {
  /** Filas ya autorizadas del dataset del módulo — nunca una grilla (T02-AC-130/131/133). */
  filas: readonly FilaLps[];
  config: ConfiguracionRestricciones;
  /** La entrega el consumidor (no `new Date()` aquí — dominio puro, ver `digest.ts`). */
  fecha: Date;
  /** Adapter inyectado: `navigator.clipboard.writeText`. Nunca se llama directo (D-T02-01). */
  copiarAlPortapapeles: (texto: string) => Promise<void>;
};

/**
 * Digest semanal consolidado de bloqueos críticos (T02-AC-130..134). Presenta
 * `compilarDigestSemanal` (dominio puro, Tarea 3) — este componente sólo añade la acción de copiar
 * con feedback de éxito/error y texto seleccionable como respaldo (mismo patrón que `AccionesSos`).
 * Existe sólo cuando el consumidor lo monta con una colección visible; no requiere red ni grid.
 */
export function DigestLps({ filas, config, fecha, copiarAlPortapapeles }: PropiedadesDigestLps) {
  const [estado, setEstado] = useState<string | null>(null);
  const [mostrarTextoManual, setMostrarTextoManual] = useState(false);

  const digest = compilarDigestSemanal(filas, config, fecha);

  async function copiar(): Promise<void> {
    try {
      await copiarAlPortapapeles(digest.texto);
      setMostrarTextoManual(false);
      setEstado('Digest copiado al portapapeles.');
    } catch {
      setMostrarTextoManual(true);
      setEstado('No se pudo copiar automáticamente. Selecciona y copia el texto manualmente.');
    }
  }

  return (
    <section className="lps-digest" aria-label="Digest semanal">
      <p role="status" aria-live="polite">
        {estado}
      </p>
      <p>{digest.sinBloqueos ? 'Sin bloqueos críticos esta semana.' : `${Object.keys(digest.bloqueosPorSubcontratista).length} responsable(s) con bloqueos.`}</p>
      <button type="button" onClick={() => void copiar()}>
        Copiar digest
      </button>
      {mostrarTextoManual ? (
        <label>
          Texto del digest (copia manual)
          <textarea readOnly value={digest.texto} onFocus={(evento) => evento.currentTarget.select()} className="lps-digest__texto" />
        </label>
      ) : (
        <pre className="lps-digest__texto">{digest.texto}</pre>
      )}
    </section>
  );
}
