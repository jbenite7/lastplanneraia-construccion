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

      {/* `aia-auth__clave` reemplaza al `input-group` de Bootstrap (Tarea 14): esa clase del
          vendor no llegaba a colocar el botón junto al campo —caía debajo, a ancho casi
          completo— y con `aia-btn--secondary` competía visualmente con el botón de envío.
          Ahora el alternador es una acción textual discreta alineada a la derecha; conserva
          `aria-pressed`, su alto de objetivo táctil y el contraste, que es lo que lo hace
          accesible. Lo que cambia es la presentación, no la semántica. */}
      <div className="aia-auth__clave">
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
          className="aia-auth__clave-toggle"
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
