import { useCallback, useEffect, useState } from 'react';
import { pedir } from '../lib/api/cliente';
import { EsquemaSesion, type Sesion } from '../lib/api/esquemas/sesion';

/**
 * La sesión que el PHP reporta. Se consulta al arrancar y se recarga después de
 * entrar o de elegir proyecto — es la fuente de verdad, no el estado local.
 */
export function useSesion(): {
  sesion: Sesion | null;
  cargando: boolean;
  error: Error | null;
  recargar: () => Promise<void>;
} {
  const [sesion, setSesion] = useState<Sesion | null>(null);
  const [cargando, setCargando] = useState(true);
  const [error, setError] = useState<Error | null>(null);

  const recargar = useCallback(async () => {
    setCargando(true);
    setError(null);
    try {
      setSesion(await pedir('/api/session', EsquemaSesion));
    } catch (causa) {
      setError(causa instanceof Error ? causa : new Error('No se pudo consultar la sesión'));
    } finally {
      setCargando(false);
    }
  }, []);

  useEffect(() => {
    void recargar();
  }, [recargar]);

  return { sesion, cargando, error, recargar };
}
