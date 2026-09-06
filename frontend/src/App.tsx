import { leerConfiguracionRuntime } from './lib/runtime/configuracion';
import { Rutas } from './shell/rutas';

/**
 * Lee `#aia-runtime-config` UNA vez, al módulo cargar — no en cada render — porque el nodo lo
 * inyecta el servidor en el HTML de esta misma carga de página (Tarea 12, S01) y no cambia
 * mientras la SPA vive. Si el nodo no existe, `leerConfiguracionRuntime()` devuelve
 * `{mode:'application'}`: el arranque normal, sin runtime inyectado, sigue igual.
 */
const configuracionRuntime = leerConfiguracionRuntime();

export function App() {
  return <Rutas configuracionRuntime={configuracionRuntime} />;
}
