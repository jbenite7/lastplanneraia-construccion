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
  recargar: () => Promise<void>;
} {
  const [sesion, setSesion] = useState<Sesion | null>(null);
  const [cargando, setCargando] = useState(true);

  const recargar = useCallback(async () => {
    setCargando(true);
    try {
      setSesion(await pedir('/api/session', EsquemaSesion));
    } finally {
      setCargando(false);
    }
  }, []);

  useEffect(() => {
    void recargar();
  }, [recargar]);

  return { sesion, cargando, recargar };
}
