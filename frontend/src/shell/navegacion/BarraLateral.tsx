import {
  useCallback,
  useEffect,
  useId,
  useRef,
  useState,
  type ReactNode,
  type Ref,
} from 'react';
import { ConmutadorTema } from '../ConmutadorTema';
import { esBarraLateralFlotante } from '../modoBarraLateral';

/**
 * Modelos de presentación del rail (Tarea 7, S04) — no de dominio: quien construye `groups`
 * (`NavegacionLateral` desde `sesion.navigation.groups`, `NavegacionSelectorProyectos` desde
 * `ListaProyectos.navigation`) ya resolvió rol, membresía y visibilidad. `BarraLateral` solo
 * pinta lo que recibe y decide cuál entrada lleva `aria-current`, nunca a quién ocultarle qué.
 */
export type ItemBarraLateral = {
  id: string;
  label: string;
  href: string | null;
  /** Entrada sin destino propio (p. ej. abrir un flyout): se pinta como botón deshabilitado,
   *  igual que ya hacía `NavegacionLateral` (T01) para "Semanas del Proyecto". */
  action?: boolean;
};

export type GrupoBarraLateral = {
  id: string;
  label: string;
  items: readonly ItemBarraLateral[];
};

const SELECTOR_ENFOCABLES =
  'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

type PropiedadesBarraLateral = {
  /** Id de la entrada activa (ya resuelta por quien arma `groups`, contra la URL vigente). */
  activeId: string;
  /** Nombre a mostrar cuando el pie construye su propio bloque de cuenta (ver `children`). */
  accountName: string;
  /** Encabezado del rail bajo la marca — proyecto/usuario en `NavegacionLateral`, ausente en la
   *  pantalla standalone `/proyectos` (no hay un proyecto activo que anunciar ahí). */
  context?: { primary: string; secondary?: string };
  groups: readonly GrupoBarraLateral[];
  /** Solo aplica al bloque de cuenta autoconstruido (ver `children`): si `false`, "Cambiar
   *  proyecto" no aparece — es redundante en la propia pantalla del selector. */
  showChangeProject: boolean;
  /**
   * Modo de gobierno del drawer móvil (Tarea 7, S04). Por defecto `true`: sin un `AppShell`
   * alrededor (la pantalla standalone `/proyectos`), `BarraLateral` administra su propio
   * disparador, velo, `Escape` y foco de entrada — mismo patrón que `AppShell` aplicaba antes
   * solo para sí mismo. `NavegacionLateral` es la única que lo pone en `false`: ese disparador
   * ya vive fuera del `<aside>`, en `AppShell`, desde T01, y un segundo control aquí lo
   * duplicaría.
   */
  barraAutonoma?: boolean;
  id?: string;
  ref?: Ref<HTMLElement>;
  contextoSemana?: ReactNode;
  estado?: 'expanded' | 'collapsed';
  alAlternarEstado?: () => void;
  abiertoEnMovil?: boolean;
  /** Utilidades del pie que arma quien la use (p. ej. `MenuCuenta`, vía `NavegacionLateral`). */
  children?: ReactNode;
  /**
   * Sin `children`, ¿el pie construye su propio bloque de cuenta ("Cerrar sesión", y "Cambiar
   * proyecto" si `showChangeProject`)? Solo la pantalla standalone `/proyectos` lo activa: no
   * tiene un `MenuCuenta` completo con CSRF/logout centralizado a mano. Por defecto `false` —
   * `NavegacionLateral` nunca lo activa, para no duplicar el nombre de cuenta que ya muestra en
   * su propio encabezado (`context.secondary`) cuando no recibió `children`.
   */
  cuentaPropia?: boolean;
};

function fusionarRefs(a: Ref<HTMLElement> | undefined, b: (nodo: HTMLElement | null) => void) {
  return (nodo: HTMLElement | null) => {
    b(nodo);
    if (typeof a === 'function') a(nodo);
    else if (a) (a as { current: HTMLElement | null }).current = nodo;
  };
}

/**
 * Rail genérico del shell (Tarea 7, S04): renderiza marca, contexto opcional, grupos de
 * navegación con exactamente un `aria-current="page"`, `ConmutadorTema` y el pie de cuenta. No
 * interpreta roles ni capacidades — eso ya lo resolvió quien construyó `groups`. Sustituye la
 * parte de renderizado que antes vivía íntegra en `NavegacionLateral` (T01); ese componente
 * ahora es una envoltura delgada que arma `groups`/`activeId` desde `Sesion` y delega aquí.
 */
export function BarraLateral({
  activeId,
  accountName,
  context,
  groups,
  showChangeProject,
  barraAutonoma = true,
  id = 'app-shell-nav',
  ref,
  contextoSemana = null,
  estado: estadoExterno,
  alAlternarEstado: alAlternarEstadoExterno,
  abiertoEnMovil: abiertoEnMovilExterno,
  children,
  cuentaPropia = false,
}: PropiedadesBarraLateral) {
  const navId = useId();
  const [colapsadoPropio, setColapsadoPropio] = useState(false);
  const [abiertoPropio, setAbiertoPropio] = useState(false);
  const [flotante, setFlotante] = useState(() =>
    esBarraLateralFlotante(typeof window === 'undefined' ? Infinity : window.innerWidth),
  );
  const disparadorRef = useRef<HTMLButtonElement>(null);
  const asideRef = useRef<HTMLElement | null>(null);
  const navRef = useRef<HTMLElement | null>(null);

  const estado = barraAutonoma ? (colapsadoPropio ? 'collapsed' : 'expanded') : (estadoExterno ?? 'expanded');
  const alAlternarEstado = barraAutonoma
    ? () => setColapsadoPropio((valor) => !valor)
    : alAlternarEstadoExterno;
  const abierto = barraAutonoma ? abiertoPropio : Boolean(abiertoEnMovilExterno);

  useEffect(() => {
    if (!barraAutonoma) return;
    function sincronizar() {
      setFlotante(esBarraLateralFlotante(window.innerWidth));
    }
    sincronizar();
    window.addEventListener('resize', sincronizar);
    return () => window.removeEventListener('resize', sincronizar);
  }, [barraAutonoma]);

  const cerrarDrawer = useCallback(() => {
    if (!barraAutonoma) return;
    setAbiertoPropio(false);
    disparadorRef.current?.focus();
  }, [barraAutonoma]);

  // Foco de entrada: al abrir el drawer flotante propio, el foco se mueve al primer link de
  // navegación (mismo requisito de T01 §14 que `AppShell` cumple para el suyo) — el toggle de
  // colapso del propio encabezado no cuenta como destino: es control del rail, no contenido.
  useEffect(() => {
    if (!barraAutonoma || !flotante || !abierto) return;
    const primero = navRef.current?.querySelector<HTMLElement>(SELECTOR_ENFOCABLES);
    primero?.focus();
  }, [barraAutonoma, flotante, abierto]);

  // `Escape` cierra el drawer propio y devuelve el foco a su disparador.
  useEffect(() => {
    if (!barraAutonoma || !flotante || !abierto) return;
    function alTeclado(evento: KeyboardEvent) {
      if (evento.key === 'Escape') {
        evento.preventDefault();
        cerrarDrawer();
      }
    }
    document.addEventListener('keydown', alTeclado);
    return () => document.removeEventListener('keydown', alTeclado);
  }, [barraAutonoma, flotante, abierto, cerrarDrawer]);

  return (
    <>
      {barraAutonoma && flotante && (
        <button
          ref={disparadorRef}
          type="button"
          className="aia-btn aia-btn--secondary shell-menu-trigger"
          aria-controls={id}
          aria-expanded={abierto}
          aria-label={abierto ? 'Cerrar menú de navegación' : 'Abrir menú de navegación'}
          onClick={() => setAbiertoPropio((valor) => !valor)}
        >
          Menú
        </button>
      )}

      {barraAutonoma && flotante && abierto && (
        <div className="shell-menu-velo" onClick={cerrarDrawer} aria-hidden="true" />
      )}

      <aside
        ref={fusionarRefs(ref, (nodo) => {
          asideRef.current = nodo;
        })}
        id={id}
        className="aia-navigation aia-navigation--sidebar"
        aria-label="Aplicación"
        data-shell-pattern="sidebar"
        data-sidebar-state={estado}
        data-shell-drawer-open={abierto ? 'true' : undefined}
      >
        <header className="aia-sidebar__header">
          {/* `.aia-sidebar__brand` fija `grid-column: 1` (contrato compartido con el shell PHP) —
              ver el comentario histórico en `NavegacionLateral` antes de este refactor. */}
          <div className="aia-sidebar__brand">
            <strong className="aia-sidebar__brand-name">Last Planner AIA</strong>
          </div>
          {context && (
            <div className="aia-sidebar__context">
              <span>{context.primary}</span>
              {context.secondary && <small>{context.secondary}</small>}
            </div>
          )}
          {contextoSemana}
          {alAlternarEstado && (
            <button
              type="button"
              className="aia-btn aia-btn--secondary aia-sidebar__toggle"
              aria-controls={navId}
              aria-expanded={estado === 'expanded'}
              aria-label={estado === 'expanded' ? 'Colapsar menú' : 'Expandir menú'}
              data-sidebar-toggle=""
              onClick={alAlternarEstado}
            >
              <span className="aia-sidebar__toggle-label">
                {estado === 'expanded' ? 'Colapsar menú' : 'Expandir menú'}
              </span>
            </button>
          )}
        </header>

        <nav
          id={navId}
          ref={(nodo) => {
            navRef.current = nodo;
          }}
          className="aia-sidebar__nav"
          aria-label="Navegación del proyecto"
        >
          {groups.map((grupo) => (
            <section className="aia-sidebar__group" aria-labelledby={`grupo-${grupo.id}`} key={grupo.id}>
              <h3 id={`grupo-${grupo.id}`}>{grupo.label}</h3>
              <ul>
                {grupo.items.map((item) => (
                  <li key={item.id}>
                    {item.href !== null ? (
                      <a
                        aria-current={item.id === activeId ? 'page' : undefined}
                        className="aia-sidebar__link"
                        href={item.href}
                      >
                        <span className="aia-sidebar__label">{item.label}</span>
                      </a>
                    ) : (
                      <button
                        aria-disabled={item.action}
                        aria-label={item.label}
                        className="aia-sidebar__link"
                        disabled={item.action}
                        type="button"
                      >
                        <span className="aia-sidebar__label">{item.label}</span>
                      </button>
                    )}
                  </li>
                ))}
              </ul>
            </section>
          ))}
        </nav>

        <footer className="aia-sidebar__footer">
          <ConmutadorTema />
          {children}
          {!children && cuentaPropia && (
            <div className="aia-menu aia-sidebar__account" data-aia-component="menu">
              <span className="aia-sidebar__account-head" role="presentation">
                {accountName}
              </span>
              {showChangeProject && (
                <a className="aia-sidebar__account-item" href="/proyectos">
                  Cambiar proyecto
                </a>
              )}
              <a className="aia-sidebar__account-item" href="/logout">
                Cerrar sesión
              </a>
            </div>
          )}
        </footer>
      </aside>
    </>
  );
}
