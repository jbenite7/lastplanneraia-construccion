import { forwardRef, useRef, useState, type ChangeEvent } from 'react';
import { IconoOjo, IconoOjoTachado } from './iconos';

type PropiedadesCampoClave = {
  id: string;
  name: string;
  label: string;
  value: string;
  onChange: (valor: string) => void;
  autoComplete: string;
  placeholder?: string;
  /** Ayuda asociada aparte del error (S03, Tarea 6) — por ejemplo la política de
   *  contraseña. Se concatena con el id de error propio del campo cuando ambos existen. */
  describedBy?: string;
  error?: string | null;
  disabled?: boolean;
};

/**
 * Campo de contraseña con alternador visible/oculto (Tarea 8, S01). El error se
 * asocia al input vía `aria-describedby` — nunca queda solo pintado al lado — y el
 * botón de alternar nunca desactiva el propio campo.
 *
 * `forwardRef` (S03, Tarea 6): el padre necesita mover el foco al primer campo con
 * error de política/coincidencia sin depender de `document.getElementById`.
 */
export const CampoClave = forwardRef<HTMLInputElement, PropiedadesCampoClave>(function CampoClave(
  { id, name, label, value, onChange, autoComplete, placeholder, describedBy, error = null, disabled = false },
  ref,
) {
  const [visible, setVisible] = useState(false);
  const idError = `${id}-error`;
  const describedByValue = [describedBy, error ? idError : null].filter(Boolean).join(' ') || undefined;

  // Ref propia además de la reenviada (S03, Tarea 6): el alternador necesita devolver el foco
  // al campo tras cambiar visibilidad, y hacerlo a través de `ref` (que puede ser callback u
  // objeto del padre) no da un nodo del que leer `.focus()` de forma fiable.
  const inputRef = useRef<HTMLInputElement>(null);
  function combinarRefs(nodo: HTMLInputElement | null) {
    inputRef.current = nodo;
    if (typeof ref === 'function') ref(nodo);
    else if (ref) ref.current = nodo;
  }

  function alCambiar(evento: ChangeEvent<HTMLInputElement>) {
    onChange(evento.target.value);
  }

  function alternarVisibilidad() {
    setVisible((valor) => !valor);
    inputRef.current?.focus();
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
          ref={combinarRefs}
          id={id}
          name={name}
          className="aia-input"
          type={visible ? 'text' : 'password'}
          value={value}
          onChange={alCambiar}
          autoComplete={autoComplete}
          placeholder={placeholder}
          disabled={disabled}
          required
          aria-invalid={error ? true : undefined}
          aria-describedby={describedByValue}
        />

        <button
          type="button"
          className="aia-auth__clave-toggle"
          aria-label={visible ? 'Ocultar contraseña' : 'Mostrar contraseña'}
          aria-pressed={visible}
          disabled={disabled}
          onClick={alternarVisibilidad}
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
});
