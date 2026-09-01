import { useCallback, useEffect, useRef, useState } from 'react';
import { Outlet, useLocation } from 'react-router-dom';
import type { ArranqueAutenticado } from '../lib/api/esquemas/arranque';
import { CajonContextualLps } from '../shared/lps/componentes/CajonContextualLps';
import { LpsDrawerProvider } from '../shared/lps/estado/LpsDrawerProvider';
import { useLpsDrawer } from '../shared/lps/estado/useLpsDrawer';
import { ContextoSemana } from './ContextoSemana';
import { LimiteErrorRuta } from './errores/LimiteErrorRuta';
import { MenuCuenta } from './MenuCuenta';
import { esBarraLateralFlotante } from './modoBarraLateral';
import { NavegacionLateral } from './NavegacionLateral';
import { useTituloDocumento } from './useTituloDocumento';

const SELECTOR_ENFOCABLES =
  'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

const ID_PANEL_CONTENIDO = 'contenido';

type PropiedadesAppShell = {
  sesion: ArranqueAutenticado;
  recargar: () => Promise<void>;
  /** Cierre de sesión centralizado en `ControlActividad` (Tarea 6) — ver `MenuCuenta`. */
  cerrarSesion: () => Promise<unknown>;
  /**
   * `generacion` de `SesionProvider` (Tarea 8, T02): se pasa tal cual al único
   * `LpsDrawerProvider` compuesto aquí, para que un cambio de sesión/proyecto cierre el cajón
   * contextual LPS (T02-AC-021) sin que este componente conozca su lógica interna. Opcional con
   * default `0` a propósito: las pruebas de T01 (`AppShell.*.test.tsx`) no conocen T02 y no deben
   * tocarse sólo para satisfacer esta prop nueva — sin cambio real de generación en esas pruebas,
   * el cajón simplemente nunca ve invalidarse por sesión, que es el comportamiento correcto ahí.
   */
  generacionSesion?: number;
};

/**
 * Cierra el cajón contextual LPS al cambiar de ruta (Tarea 8, T02, ronda de arreglos 1). Vive
 * dentro de `LpsDrawerProvider` (necesita `useLpsDrawer()`), como hermano de `<main>` — el mismo
 * evento que ya limpia el drawer de navegación dos líneas más abajo en `AppShell`, pero replicado
 * aquí porque ese efecto vive fuera del provider y no puede llamar `useLpsDrawer()`. `cerrar()`
 * sobre un cajón ya cerrado es una operación sin efecto (el reductor descarta cualquier acción
 * mientras `status === 'closed'`), así que este componente no necesita comprobar nada antes de
 * llamarlo en cada cambio de `pathname` — incluido el montaje inicial.
 *
 * Motivo del fix: sin esto, la entrada de historial sintética que empuja el provider al abrir
 * (AC-027, back cierra primero el cajón) puede desincronizarse de la del enrutador si el usuario
 * sigue un enlace interno con el cajón todavía abierto — el siguiente "atrás" del navegador
 * quedaría gobernado por una entrada que ya no corresponde a la ruta real.
 */
function CerrarCajonLpsAlNavegar() {
  const { cerrar } = useLpsDrawer();
  const location = useLocation();

  useEffect(() => {
    cerrar();
    // Sólo debe disparar por cambio de ruta — `cerrar` es estable (useCallback en el provider)
    // pero listarla igual no cambia el comportamiento; se omite para dejar la intención explícita.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [location.pathname]);

  return null;
}

/**
 * Contenedor reutilizable del shell (Tarea 4, T01): un único `nav`/`main`, skip link, menú de
 * cuenta y outlet de módulos. Compone alrededor de la raíz que ya arma `rutas.tsx` — no es una
 * app nueva. `estado`/`abierto` gobiernan el rail persistente/colapsable de escritorio y el
 * drawer flotante bajo 1180px (mismo umbral que `shell-drawer.js`, ver `modoBarraLateral.ts`);
 * el `<nav>` en sí lo sigue renderizando `NavegacionLateral` — un solo landmark de navegación en
 * todo el árbol.
 */
export function AppShell({ sesion, recargar, cerrarSesion, generacionSesion = 0 }: PropiedadesAppShell) {
  const [flotante, setFlotante] = useState(() =>
    esBarraLateralFlotante(typeof window === 'undefined' ? Infinity : window.innerWidth),
  );
  const [abierto, setAbierto] = useState(false);
  const [colapsado, setColapsado] = useState(false);
  const disparadorRef = useRef<HTMLButtonElement>(null);
  const navRef = useRef<HTMLElement>(null);
  const contenidoRef = useRef<HTMLElement>(null);
  const location = useLocation();
  const tituloVigente = useTituloDocumento(sesion.project?.name);

  // El navegador desplaza la vista al seguir `href="#contenido"`, pero un `<main>` sin
  // `tabindex` nunca es focoable — un usuario de teclado vería la página saltar pero el foco
  // seguiría en el skip link (spec T01 §14 "skip link al contenido principal"). `tabIndex={-1}`
  // en el `<main>` lo hace focoable programáticamente sin sumarlo al orden de tabulación normal,
  // y este handler mueve el foco ahí de forma explícita en vez de confiar en el comportamiento
  // de foco por fragmento —inconsistente entre navegadores— del `href`.
  const alSaltarAlContenido = useCallback((evento: React.MouseEvent<HTMLAnchorElement>) => {
    evento.preventDefault();
    contenidoRef.current?.focus();
  }, []);

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

  // Foco de entrada: al abrir el drawer flotante, el foco se mueve al primer control enfocable
  // del nav — sin esto, tras un clic en "Menú" el foco queda huérfano en el botón que acaba de
  // desaparecer detrás del velo (spec T01 §14 "apertura/cierre por teclado… trampa y retorno de
  // foco en drawer"). En escritorio (`abierto` sin `flotante`) no aplica: no hay drawer que abrir.
  useEffect(() => {
    if (!abierto || !flotante) return;
    const primero = navRef.current?.querySelector<HTMLElement>(SELECTOR_ENFOCABLES);
    primero?.focus();
  }, [abierto, flotante]);

  // Escape cierra el drawer y devuelve el foco a su disparador. Mientras está abierto, Tab/
  // Shift+Tab quedan atrapados dentro del `<aside>` — de lo contrario el foco se escaparía hacia
  // contenido tapado por el velo, invisible pero todavía en el árbol de tabulación.
  useEffect(() => {
    if (!abierto || !flotante) return;
    function alTeclado(evento: KeyboardEvent) {
      if (evento.key === 'Escape') {
        evento.preventDefault();
        cerrarDrawer();
        return;
      }
      if (evento.key !== 'Tab') return;
      const contenedor = navRef.current;
      if (!contenedor) return;
      const enfocables = contenedor.querySelectorAll<HTMLElement>(SELECTOR_ENFOCABLES);
      if (enfocables.length === 0) return;
      const primero = enfocables[0];
      const ultimo = enfocables[enfocables.length - 1];
      const activo = document.activeElement;
      if (!contenedor.contains(activo)) {
        evento.preventDefault();
        primero.focus();
      } else if (evento.shiftKey && activo === primero) {
        evento.preventDefault();
        ultimo.focus();
      } else if (!evento.shiftKey && activo === ultimo) {
        evento.preventDefault();
        primero.focus();
      }
    }
    document.addEventListener('keydown', alTeclado);
    return () => document.removeEventListener('keydown', alTeclado);
  }, [abierto, flotante, cerrarDrawer]);

  const abrirDrawer = useCallback(() => setAbierto(true), []);

  return (
    <>
      <a className="aia-skip-link" href={`#${ID_PANEL_CONTENIDO}`} onClick={alSaltarAlContenido}>
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
        contextoSemana={<ContextoSemana semana={sesion.week} csrfToken={sesion.csrfToken} recargar={recargar} />}
        estado={colapsado ? 'collapsed' : 'expanded'}
        alAlternarEstado={() => setColapsado((valor) => !valor)}
        abiertoEnMovil={flotante ? abierto : undefined}
      >
        <MenuCuenta
          nombre={sesion.user.displayName}
          csrfToken={sesion.csrfToken}
          alCambiarProyecto={recargar}
          cerrarSesion={cerrarSesion}
        />
      </NavegacionLateral>

      {/* Anuncios en vivo (spec T01 §14): un cambio de ruta actualiza `document.title` y esta
          región lo repite para quien no esté mirando la pestaña — el mismo texto en ambos sitios,
          calculado una sola vez por `useTituloDocumento`. */}
      <div aria-live="polite" className="aia-visually-hidden" role="status">
        {tituloVigente}
      </div>

      {/* Único `LpsDrawerProvider` autenticado del árbol (T02-AC-004): compone aquí, junto al
          `Outlet`, para que cualquier módulo (S05/S07/S08/S25) consuma `useLpsDrawer()` sin volver
          a montar su propio provider/estado/CSS del cajón (T02-AC-005). El cajón en sí
          (`CajonContextualLps`) se monta una sola vez fuera del `<main>`, como hermano del
          contenido — nunca dos instancias, nunca anidado dentro de una ruta hija.

          `.lps-layout-cajon` es la rejilla que prescribe la spec (AC-168, diseño línea 562-563):
          bajo 1180px es un contenedor de bloque normal (el cajón flota por posición fija, como ya
          define `lps-contexto.css`); en 1180+ pasa a `grid` con `minmax(0,1fr)` para `<main>` y
          una columna para el panel — ronda de arreglos 1: antes `<main>` y `<CajonContextualLps />`
          eran hermanos sueltos sin ancestro `grid`/`flex`, así que en escritorio el cajón no
          refluía el contenido, quedaba apilado debajo y su `height:100%` colapsaba a `auto` por
          falta de una altura de fila resuelta. Sin `body.padding-right` en ningún punto — la spec
          lo prohíbe explícitamente y es el patrón que usaba el drawer LPS legado
          (`handsontable-module.css`), no el que reemplaza esta tarea. */}
      <LpsDrawerProvider csrfToken={sesion.csrfToken} generacionSesion={generacionSesion} semana={sesion.week?.current ?? null}>
        <CerrarCajonLpsAlNavegar />
        <div className="lps-layout-cajon">
          <main id={ID_PANEL_CONTENIDO} ref={contenidoRef} tabIndex={-1}>
            <LimiteErrorRuta>
              <Outlet />
            </LimiteErrorRuta>
          </main>
          <CajonContextualLps />
        </div>
      </LpsDrawerProvider>
    </>
  );
}
