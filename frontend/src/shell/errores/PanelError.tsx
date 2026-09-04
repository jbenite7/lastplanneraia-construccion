import type { ApiError } from '../../lib/api/cliente';
import { clasificarError } from './clasificarError';

type PropiedadesPanelError = {
  error: ApiError;
  /** Solo aparece el botón "Reintentar" cuando el llamador da una acción concreta. */
  alReintentar?: () => void;
  /** Salida acorde al estado (spec T01 §15, fila 403): la etiqueta la decide el llamador —
   *  "Volver al inicio", "Ir al selector de proyecto", etc. — nunca este componente genérico. */
  alSalir?: { etiqueta: string; onClick: () => void };
};

/**
 * Panel compartido de error (Tarea 8, T01 §15). `role="alert"` para que un lector de pantalla lo
 * anuncie de inmediato sin que el llamador tenga que gestionar foco. El texto viene siempre de
 * `clasificarError()` → `error.message`, que `cliente.ts` ya construye sin insertar HTML o cuerpo
 * crudo — este componente nunca usa `dangerouslySetInnerHTML` ni interpola markup.
 */
export function PanelError({ error, alReintentar, alSalir }: PropiedadesPanelError) {
  const clasificacion = clasificarError(error);
  const campos = clasificacion.camposInvalidos ? Object.entries(clasificacion.camposInvalidos) : [];

  return (
    <section
      className={`aia-alert aia-alert--error aia-panel-error aia-panel-error--${clasificacion.variante}`}
      role="alert"
    >
      <h2>{clasificacion.titulo}</h2>
      <p>{clasificacion.mensaje}</p>

      {campos.length > 0 && (
        <ul className="aia-panel-error__campos">
          {campos.map(([campo, mensajeCampo]) => (
            <li key={campo}>
              <strong>{campo}:</strong> {mensajeCampo}
            </li>
          ))}
        </ul>
      )}

      {clasificacion.correlationId && (
        <p className="aia-panel-error__correlacion">
          Código de referencia: <code>{clasificacion.correlationId}</code>
        </p>
      )}

      {(alReintentar || alSalir) && (
        <div className="aia-panel-error__acciones">
          {alReintentar && (
            <button className="aia-btn aia-btn--primary" onClick={alReintentar} type="button">
              Reintentar
            </button>
          )}
          {alSalir && (
            <button className="aia-btn aia-btn--secondary" onClick={alSalir.onClick} type="button">
              {alSalir.etiqueta}
            </button>
          )}
        </div>
      )}
    </section>
  );
}
