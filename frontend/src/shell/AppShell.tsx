import { useCallback, useEffect, useRef, useState } from 'react';
import { Outlet, useLocation } from 'react-router-dom';
import type { ArranqueAutenticado } from '../lib/api/esquemas/arranque';
import { MenuCuenta } from './MenuCuenta';
import { esBarraLateralFlotante } from './modoBarraLateral';
import { NavegacionLateral } from './NavegacionLateral';

const ID_PANEL_CONTENIDO = 'contenido';

type PropiedadesAppShell = {
  sesion: ArranqueAutenticado;
  recargar: () => Promise<void>;
};

/**
 * Contenedor reutilizable del shell (Tarea 4, T01): un único `nav`/`main`, skip link, menú de
 * cuenta y outlet de módulos. Compone alrededor de la raíz que ya arma `rutas.tsx` — no es una
 * app nueva. `estado`/`abierto` gobiernan el rail persistente/colapsable de escritorio y el
 * drawer flotante bajo 1180px (mismo umbral que `shell-drawer.js`, ver `modoBarraLateral.ts`);
 * el `<nav>` en sí lo sigue renderizando `NavegacionLateral` — un solo landmark de navegación en
 * todo el árbol.
 */
export function AppShell({ sesion, recargar }: PropiedadesAppShell) {
  const [flotante, setFlotante] = useState(() =>
    esBarraLateralFlotante(typeof window === 'undefined' ? Infinity : window.innerWidth),
  );
  const [abierto, setAbierto] = useState(false);
  const [colapsado, setColapsado] = useState(false);
  const disparadorRef = useRef<HTMLButtonElement>(null);
  const navRef = useRef<HTMLElement>(null);
  const location = useLocation();

  // Respaldo inline al `transform` del drawer (Tarea 4, ronda de arreglos 1). El contrato
  // documental de `shell-sidebar.css` es `data-shell-drawer-open="true"` — la regla que lo
  // consume vive más abajo en el archivo que la regla "cerrado" y comparte su especificidad
  // exacta (`body.aia-shell--sidebar .aia-navigation--sidebar[...]`), así que en teoría el orden
  // de aparición debería bastar. Verificado en el navegador integrado (390×844) que NO basta:
  // con el atributo puesto, `getComputedStyle` seguía devolviendo `translateX(-100%)` — el
  // drawer quedaba "abierto" en el árbol de accesibilidad pero invisible en pantalla. No se
  // tocó `shell-sidebar.css` (es el contrato que este componente debe cumplir, no un archivo que
  // esta tarea posea) ni se investigó más a fondo el motivo exacto del empate de cascada por
  // presupuesto de tiempo; en su lugar, el estilo inline —que siempre gana sobre cualquier regla
  // de hoja de estilos sin `!important`, existente o no— hace que el resultado visual deje de
  // depender de esa ambigüedad. Sigue coexistiendo con el atributo: éste conserva su valor
  // semántico/testeable y las demás reglas de ese selector (z-index, box-shadow) que no compiten
  // por la misma propiedad.
  useEffect(() => {
    const nodo = navRef.current;
    if (!nodo) return;
    nodo.style.transform = flotante && abierto ? 'translateX(0)' : '';
  }, [flotante, abierto]);

  // Layout del rail canónico (docs/design-system + shell-sidebar.css): esta página React es
  // dueña de todo el `<body>` (frontend/index.html no trae otro shell), así que activar la
  // misma clase que usa el shell PHP legado reutiliza su CSS ya existente (posición fija del
  // rail, padding del contenido, drawer bajo el umbral) sin escribir una hoja nueva.
  useEffect(() => {
    document.body.classList.add('aia-shell--sidebar');
    return () => {
      document.body.classList.remove('aia-shell--sidebar');
    };
  }, []);

  useEffect(() => {
    function sincronizar() {
      const ahoraFlotante = esBarraLateralFlotante(window.innerWidth);
      setFlotante(ahoraFlotante);
      if (!ahoraFlotante) setAbierto(false);
    }
    sincronizar();
    window.addEventListener('resize', sincronizar);
    return () => window.removeEventListener('resize', sincronizar);
  }, []);

  // Cierre al cambiar de ruta: solo aplica a navegación dentro del outlet (los hrefs no
  // migrados son recarga completa de página, que ya desmonta este árbol).
  useEffect(() => {
    setAbierto(false);
  }, [location.pathname]);

  // Bloqueo de scroll del body mientras el drawer está abierto.
  useEffect(() => {
    if (!abierto) return;
    const previo = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    return () => {
      document.body.style.overflow = previo;
    };
  }, [abierto]);

  const cerrarDrawer = useCallback(() => {
    setAbierto(false);
    disparadorRef.current?.focus();
  }, []);

  // Escape cierra el drawer y devuelve el foco a su disparador.
  useEffect(() => {
    if (!abierto) return;
    function alTeclado(evento: KeyboardEvent) {
      if (evento.key === 'Escape') {
        evento.preventDefault();
        cerrarDrawer();
      }
    }
    document.addEventListener('keydown', alTeclado);
    return () => document.removeEventListener('keydown', alTeclado);
  }, [abierto, cerrarDrawer]);

  const abrirDrawer = useCallback(() => setAbierto(true), []);

  return (
    <>
      <a className="aia-skip-link" href={`#${ID_PANEL_CONTENIDO}`}>
        Saltar al contenido
      </a>

      {flotante && (
        <button
          ref={disparadorRef}
          type="button"
          className="aia-btn aia-btn--secondary shell-menu-trigger"
          aria-controls="app-shell-nav"
          aria-expanded={abierto}
          aria-label="Abrir menú de navegación"
          onClick={abrirDrawer}
        >
          Menú
        </button>
      )}

      {flotante && abierto && (
        <div className="shell-menu-velo" onClick={cerrarDrawer} aria-hidden="true" />
      )}

      <NavegacionLateral
        ref={navRef}
        sesion={sesion}
        semana={sesion.week?.current ?? null}
        estado={colapsado ? 'collapsed' : 'expanded'}
        alAlternarEstado={() => setColapsado((valor) => !valor)}
        abiertoEnMovil={flotante ? abierto : undefined}
      >
        <MenuCuenta nombre={sesion.user.displayName} csrfToken={sesion.csrfToken} alCambiarProyecto={recargar} />
      </NavegacionLateral>

      <main id={ID_PANEL_CONTENIDO}>
        <Outlet />
      </main>
    </>
  );
}
