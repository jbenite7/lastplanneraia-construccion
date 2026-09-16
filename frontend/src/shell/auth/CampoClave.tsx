import { useState, type ChangeEvent } from 'react';
import { IconoOjo, IconoOjoTachado } from './iconos';

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

      {/* Paridad visual (2026-09-16): el alternador vuelve a ser un ícono DENTRO del campo, como
          en el legado. El texto pasa a `aria-label`, así que el nombre accesible no cambia
          («Mostrar contraseña» / «Ocultar contraseña») y `aria-pressed` sigue diciendo el estado. */}
      <div className="aia-auth__campo-icono">
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
          aria-label={visible ? 'Ocultar contraseña' : 'Mostrar contraseña'}
          aria-pressed={visible}
          disabled={disabled}
          onClick={() => setVisible((valor) => !valor)}
        >
          {visible ? <IconoOjoTachado /> : <IconoOjo />}
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
