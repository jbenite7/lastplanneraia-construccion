import { useState, type FormEvent } from 'react';
import { z } from 'zod';
import { ApiError, pedir } from '../lib/api/cliente';

const EsquemaRespuestaLogin = z.object({
  success: z.boolean(),
  mustChangePassword: z.boolean(),
  message: z.string().nullable(),
});

type PropiedadesPantallaLogin = {
  alEntrar: () => Promise<void>;
  csrfToken: string;
};

export function PantallaLogin({ alEntrar, csrfToken }: PropiedadesPantallaLogin) {
  const [usuario, setUsuario] = useState('');
  const [clave, setClave] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [enviando, setEnviando] = useState(false);

  async function enviar(evento: FormEvent<HTMLFormElement>) {
    evento.preventDefault();
    setEnviando(true);
    setError(null);

    try {
      const respuesta = await pedir('/api/auth/login', EsquemaRespuestaLogin, {
        method: 'POST',
        headers: { 'X-CSRF-Token': csrfToken },
        body: JSON.stringify({ username: usuario, password: clave }),
      });

      if (!respuesta.success) {
        setError('Usuario o contraseña incorrectos.');
        return;
      }

      // El cambio obligatorio sigue en PHP junto con el flujo de recuperación.
      if (respuesta.mustChangePassword) {
        window.location.href = '/login';
        return;
      }

      await alEntrar();
    } catch (fallo) {
      setError(fallo instanceof ApiError && fallo.status === 401
        ? 'Usuario o contraseña incorrectos.'
        : 'No pudimos conectar. Intenta de nuevo.');
    } finally {
      setEnviando(false);
    }
  }

  return (
    <form onSubmit={enviar} className="aia-card">
      <h1>Entrar</h1>

      {error && <p role="alert" className="aia-alert aia-alert--error">{error}</p>}

      <label htmlFor="usuario">Usuario</label>
      <input
        id="usuario"
        className="aia-input"
        value={usuario}
        onChange={(evento) => setUsuario(evento.target.value)}
        autoComplete="username"
        required
      />

      <label htmlFor="clave">Contraseña</label>
      <input
        id="clave"
        className="aia-input"
        type="password"
        value={clave}
        onChange={(evento) => setClave(evento.target.value)}
        autoComplete="current-password"
        required
      />

      <button type="submit" className="aia-btn" disabled={enviando}>
        {enviando ? 'Entrando…' : 'Entrar'}
      </button>

      <a href="/password/forgot">¿Olvidaste tu contraseña?</a>
    </form>
  );
}
