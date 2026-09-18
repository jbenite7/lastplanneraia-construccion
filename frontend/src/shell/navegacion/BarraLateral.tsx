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
import { MarcaLockup } from './MarcaLockup';

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

/**
 * Ícono decorativo del botón "Colapsar/Expandir menú" (ronda 2, T9b, hallazgo del coordinador):
 * paridad exacta con `DesignSystemComponent::icon(['name' => 'collapse', 'decorative' => true])`
 * (`src/View/Components/DesignSystemComponent.php:27,45-49`) — mismo trazo, mismos atributos de
 * `<svg>`. Sin él el botón quedaba sin nada visible (solo el `<span>` de etiqueta, oculto en
 * expandido por `navigation.css`). `navigation.css:322-329` ya cubre `.aia-sidebar__toggle-icon`
 * (tamaño y rotación en colapsado); no se agrega CSS nuevo.
 */
function IconoColapsarMenu() {
  return (
    <span className="aia-icon aia-icon--collapse" data-aia-component="icon" aria-hidden="true">
      <svg className="aia-icon__glyph" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
        <path d="m14 6-6 6 6 6" />
      </svg>
    </span>
  );
}

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
  /**
   * T7-6 (ronda de arreglo 1, hallazgo Important del revisor): el bloque de cuenta propio NO
   * puede cerrar sesión con `<a href="/logout">` — ese GET destruye la sesión sin CSRF ni
   * comprobación de método (`LoginController::show()`, registrado como `$router->get` en
   * `public/index.php`), justo lo que `MenuCuenta` prohíbe citando la spec T01 §"no destructive
   * GET". Requerido cuando `cuentaPropia` es `true`: es el mismo `cerrarSesion` que expone
   * `SesionProvider` y que ya usa `MenuCuenta` — el único POST con CSRF contra
   * `/api/auth/logout` de todo el árbol.
   */
  cerrarSesion?: () => Promise<unknown>;
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
  cerrarSesion,
}: PropiedadesBarraLateral) {
  const navId = useId();
  const [colapsadoPropio, setColapsadoPropio] = useState(false);
  const [abiertoPropio, setAbiertoPropio] = useState(false);
  const [cerrandoSesion, setCerrandoSesion] = useState(false);
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

  // Ronda de arreglo 1 (Tarea 8, hallazgo Important): al dejar de ser flotante hay que cerrar el
  // drawer propio, igual que `AppShell.tsx:127-129` hace para el suyo. Sin este cierre,
  // `abiertoPropio` sobrevive al cruce de breakpoint (abrir bajo 1180px y luego ensanchar la
  // ventana) y el efecto de bloqueo de fondo de abajo —atado solo a `[barraAutonoma, abierto]`,
  // sin gate de `flotante`— deja `body.aia-shell-drawer-open`/`overflow: hidden` pegado para
  // siempre: en escritorio ya no hay disparador para cerrarlo y `Escape` está gateado por
  // `flotante` en los efectos vecinos (foco de entrada, atrapa-tab).
  //
  // Ronda de arreglo 1, Tarea 9b (hallazgo del asesor): `setFlotante` corre SIEMPRE, sin
  // importar `barraAutonoma` — es lo que decide el `inert` del `<aside>` de abajo, y en modo no
  // autónomo (`NavegacionLateral`/`AppShell`) antes se congelaba en el valor que tenía al montar.
  // Montar a 800px (`flotante=true`) y luego ensanchar a 1440px dejaría el rail de escritorio
  // marcado `inert` para siempre, porque nadie volvía a poner `flotante` en `false`. Solo el
  // cierre del drawer PROPIO (`setAbiertoPropio`) sigue gateado por `barraAutonoma`: en modo no
  // autónomo ese drawer no es de este componente, lo gobierna `AppShell`.
  useEffect(() => {
    function sincronizar() {
      const ahoraFlotante = esBarraLateralFlotante(window.innerWidth);
      setFlotante(ahoraFlotante);
      if (!ahoraFlotante && barraAutonoma) setAbiertoPropio(false);
    }
    sincronizar();
    window.addEventListener('resize', sincronizar);
    return () => window.removeEventListener('resize', sincronizar);
  }, [barraAutonoma]);

  // Layout del rail canónico (Tarea 8, S04 — pendiente dejado a propósito por la Tarea 7): en modo
  // autónomo esta pantalla (la standalone `/proyectos`) es dueña de todo el `<body>`, igual que
  // `AppShell` lo es del suyo (T01). Activar la misma clase que consume `shell-sidebar.css`
  // reutiliza su CSS ya existente (rail fijo, padding del contenido, drawer bajo 1180px) sin
  // escribir una hoja nueva para eso. En modo no autónomo (`NavegacionLateral`, dentro de
  // `AppShell`) la clase ya la gobierna `AppShell` y tocarla aquí la duplicaría.
  useEffect(() => {
    if (!barraAutonoma) return;
    document.body.classList.add('aia-shell--sidebar');
    return () => {
      document.body.classList.remove('aia-shell--sidebar');
    };
  }, [barraAutonoma]);

  // Bloqueo del fondo mientras el drawer propio está abierto (Tarea 8, S04). A propósito con una
  // CLASE (`aia-shell-drawer-open`, `project-selector-react.css`) y no con `document.body.style`:
  // `AppShell` sí usa `style.overflow` para su propio drawer (T01), pero eso queda fuera del
  // alcance de esta tarea y no se toca. En modo no autónomo el bloqueo también lo gobierna
  // `AppShell`, así que esta clase no debe aplicarse ahí.
  useEffect(() => {
    if (!barraAutonoma || !abierto) return;
    document.body.classList.add('aia-shell-drawer-open');
    return () => {
      document.body.classList.remove('aia-shell-drawer-open');
    };
  }, [barraAutonoma, abierto]);

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

  // T7-6: mismo patrón que `MenuCuenta.alCerrarSesion` — sin `finally`, porque si `cerrarSesion()`
  // resuelve, la sesión ya se invalidó y el árbol se desmonta al recargar; no queda un
  // `setCerrandoSesion(false)` que pisar.
  async function alCerrarSesion() {
    if (!cerrarSesion) return;
    setCerrandoSesion(true);
    await cerrarSesion();
  }

  return (
    <>
      {/* Tarea 9b, S04: fila superior fija con la marca junto al disparador — sustituye al
          botón `position: fixed` en solitario de la ronda anterior (`shell-menu-trigger`,
          `@layer legacy-overrides`, compartido con el shell PHP). En vez de pelear la cascada
          de capas contra ese archivo (`module` va antes que `legacy-overrides` en
          `aia-design-system.css`, así que ninguna regla de `module` puede ganarle), el botón
          deja de llevar esa clase: ahora es un hijo en flujo normal de `.shell-mobile-topbar`
          (`project-selector-react.css`), que reserva su propia fila fija arriba — la misma
          reserva de espacio que antes hacía el padding-left de V1, ahora real en vez de
          simulada. */}
      {barraAutonoma && flotante && (
        <div className="shell-mobile-topbar">
          <button
            ref={disparadorRef}
            type="button"
            className="aia-btn aia-btn--secondary shell-mobile-topbar__trigger"
            aria-controls={id}
            aria-expanded={abierto}
            aria-label={abierto ? 'Cerrar menú de navegación' : 'Abrir menú de navegación'}
            onClick={() => setAbiertoPropio((valor) => !valor)}
          >
            Menú
          </button>
          <MarcaLockup className="shell-mobile-topbar__brand" />
        </div>
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
        // Ronda de arreglo 1 (Tarea 9b, hallazgo Important del revisor): bajo 1180px, cerrado,
        // el `<aside>` solo se saca de la vista con `transform` (`shell-sidebar.css`) — sin
        // `inert` sigue en el orden de tabulación y en el árbol de accesibilidad, y con la marca
        // de esta tarea eso duplica un enlace "Last Planner AIA" enfocable pero invisible. En
        // escritorio (`!flotante`) nunca es `inert`, esté abierto o no ese concepto aquí no
        // aplica.
        inert={flotante && !abierto}
      >
        <header className="aia-sidebar__header">
          {/* `.aia-sidebar__brand` fija `grid-column: 1` (contrato compartido con el shell PHP) —
              ver el comentario histórico en `NavegacionLateral` antes de este refactor. Tarea 9b:
              paridad completa con la barra canónica PHP vía `MarcaLockup` (enlace + ícono +
              nombre), en vez del `<div>` sin enlace ni ícono que dejaba T7. */}
          <MarcaLockup />
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
              <span className="aia-sidebar__toggle-icon">
                <IconoColapsarMenu />
              </span>
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
                        data-destination-id={item.id}
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
          {/*
            Tarea 9, S04 (hallazgo Axe original + ronda de arreglo 1, hallazgo visual V2). Este
            bloque, a diferencia de `MenuCuenta`, no tiene disparador ni estado abierto/cerrado —
            está siempre visible en la pantalla standalone `/proyectos`. Lleva el atributo REAL
            `data-aia-menu-panel` (no una clase propia): es ese selector, en
            `components/primitives.css`, el que da a la vez el fondo opaco
            (`--ds-active-surface-raised`, contra el que `.aia-sidebar__account-head` cumple
            contraste — Axe, 2.04:1 sin él) Y el reset de `<button>`/`<a>`
            (`[data-aia-menu-panel] :is(button, a)`) que espera `.aia-sidebar__account-item`
            (`buttons.css:37` lo excluye del reset genérico a propósito, confiando en que ese
            selector de atributo lo cubra). Una clase propia que solo copiara fondo/borde
            arreglaba el contraste pero dejaba "Cerrar sesión" como botón crudo del navegador
            (V2). Lo único que hay que anular es el `position: absolute` que
            `.aia-sidebar__account [data-aia-menu-panel]` trae para flotar sobre un disparador
            que aquí no existe — lo hace `.aia-sidebar__account-panel--estatico`
            (`project-selector-react.css`).
          */}
          {!children && cuentaPropia && (
            <div className="aia-menu aia-sidebar__account" data-aia-component="menu">
              <div className="aia-sidebar__account-panel--estatico" data-aia-menu-panel>
                <span className="aia-sidebar__account-head" role="presentation">
                  {accountName}
                </span>
                {showChangeProject && (
                  <a className="aia-sidebar__account-item" href="/proyectos">
                    Cambiar proyecto
                  </a>
                )}
                <button
                  type="button"
                  className="aia-sidebar__account-item"
                  disabled={cerrandoSesion}
                  onClick={() => void alCerrarSesion()}
                >
                  {cerrandoSesion ? 'Cerrando sesión…' : 'Cerrar sesión'}
                </button>
              </div>
            </div>
          )}
        </footer>
      </aside>
    </>
  );
}
