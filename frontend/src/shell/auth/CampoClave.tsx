import { useState, type ChangeEvent } from 'react';

type PropiedadesCampoClave = {
  id: string;
  name: string;
  label: string;
  value: string;
  onChange: (valor: string) => void;
  autoComplete: string;
  error?: string | null;
  disabled?: boolean;
};

/**
 * Campo de contraseña con alternador visible/oculto (Tarea 8, S01). El error se
 * asocia al input vía `aria-describedby` — nunca queda solo pintado al lado — y el
 * botón de alternar nunca desactiva el propio campo.
 */
export function CampoClave({
  id,
  name,
  label,
  value,
  onChange,
  autoComplete,
  error = null,
  disabled = false,
}: PropiedadesCampoClave) {
  const [visible, setVisible] = useState(false);
  const idError = `${id}-error`;

  function alCambiar(evento: ChangeEvent<HTMLInputElement>) {
    onChange(evento.target.value);
  }

  return (
    <div className="aia-field">
      <label className="aia-label" htmlFor={id}>
        {label}
      </label>

      <div className="input-group">
        <input
          id={id}
          name={name}
          className="aia-input"
          type={visible ? 'text' : 'password'}
          value={value}
          onChange={alCambiar}
          autoComplete={autoComplete}
          disabled={disabled}
          required
          aria-invalid={error ? true : undefined}
          aria-describedby={error ? idError : undefined}
        />

        <button
          type="button"
          className="aia-btn aia-btn--secondary"
          aria-pressed={visible}
          disabled={disabled}
          onClick={() => setVisible((valor) => !valor)}
        >
          {visible ? 'Ocultar contraseña' : 'Mostrar contraseña'}
        </button>
      </div>

      {error && (
        <p id={idError} role="alert" className="aia-helper">
          {error}
        </p>
      )}
    </div>
  );
}
