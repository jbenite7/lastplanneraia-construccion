import { NavegacionLateral } from './NavegacionLateral';
import { PantallaLogin } from './PantallaLogin';
import { SelectorProyecto } from './SelectorProyecto';
import { useSesion } from './useSesion';

/**
 * Tres estados, en este orden: sin sesión, sesión sin proyecto, y todo listo.
 * El orden importa — sin proyecto en sesión, ningún módulo puede consultar datos.
 */
export function Rutas() {
  const { sesion, cargando, recargar } = useSesion();

  if (cargando) {
    return <p role="status">Cargando…</p>;
  }

  if (!sesion?.authenticated) {
    return <PantallaLogin alEntrar={recargar} />;
  }

  if (!sesion.project) {
    return <SelectorProyecto alElegir={recargar} />;
  }

  return (
    <>
      <NavegacionLateral sesion={sesion} />
      <main>
        <h1>{sesion.project.name}</h1>
      </main>
    </>
  );
}
