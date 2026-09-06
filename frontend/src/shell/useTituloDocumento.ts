import { useEffect } from 'react';
import { useLocation } from 'react-router-dom';

function seccionDesdeRuta(pathname: string): string {
  const segmento = pathname.split('/').filter(Boolean).pop();
  const texto = segmento ? segmento.replace(/-/g, ' ') : 'inicio';
  return texto.charAt(0).toUpperCase() + texto.slice(1);
}

/**
 * Título de documento por ruta (spec T01 §14 "título de documento actualizado por ruta"). Sin
 * esto, un lector de pantalla nunca anuncia que la vista cambió: el `<title>` es lo primero que
 * se lee al enfocar la pestaña o al usar el rotor de encabezados/landmarks.
 *
 * Devuelve el mismo texto que aplica a `document.title` para que `AppShell` pueda reusarlo en su
 * región `aria-live` (spec T01 §14 "anuncios en vivo") sin recalcularlo ni arriesgar que ambos se
 * desincronicen.
 */
export function useTituloDocumento(nombreProyecto: string | undefined): string {
  const location = useLocation();
  const seccion = seccionDesdeRuta(location.pathname);
  const titulo = nombreProyecto
    ? `${seccion} · ${nombreProyecto} · Last Planner AIA`
    : `${seccion} · Last Planner AIA`;

  useEffect(() => {
    document.title = titulo;
  }, [titulo]);

  return titulo;
}
