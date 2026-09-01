import type { ReactNode } from 'react';
import { CajonContextualLps, type AdaptadoresCajonLps } from '../componentes/CajonContextualLps';
import { LpsDrawerProvider } from '../estado/LpsDrawerProvider';
import { useLpsDrawer } from '../estado/useLpsDrawer';
import type { LpsDrawerApi } from '../estado/LpsDrawerProvider';

export interface PropiedadesHarnessLps {
  csrfToken?: string;
  generacionSesion?: number;
  semana?: number | null;
  adaptadores?: AdaptadoresCajonLps;
  children: (api: LpsDrawerApi) => ReactNode;
}

function Puente({ children }: { children: (api: LpsDrawerApi) => ReactNode }) {
  const api = useLpsDrawer();
  return <>{children(api)}</>;
}

/**
 * Arnés de pruebas del cajón contextual LPS: monta el único `LpsDrawerProvider` y la única
 * instancia de `CajonContextualLps`, con un `#contenido` sintético para verificar `inert`
 * (AC-162), y expone la API del provider vía render-prop para que la prueba dispare `abrir()` con
 * un fixture sin tocar `pedir()`/`fetch` directamente. Usan este arnés tanto las pruebas de esta
 * tarea como los cuatro adapters sintéticos de la Tarea 10 (PG/PI/PS/S25).
 */
export function HarnessLps({
  csrfToken = 'token-de-prueba',
  generacionSesion = 0,
  semana = null,
  adaptadores,
  children,
}: PropiedadesHarnessLps) {
  return (
    <LpsDrawerProvider csrfToken={csrfToken} generacionSesion={generacionSesion} semana={semana}>
      <div id="contenido" tabIndex={-1}>
        Contenido de fondo
        <button type="button">Botón de fondo</button>
      </div>
      <Puente>{children}</Puente>
      <CajonContextualLps {...adaptadores} />
    </LpsDrawerProvider>
  );
}
