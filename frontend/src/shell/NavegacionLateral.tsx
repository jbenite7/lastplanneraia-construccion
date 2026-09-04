import { useId, type ReactNode, type Ref } from 'react';
import type { Sesion } from '../lib/api/esquemas/sesion';
import { ConmutadorTema } from './ConmutadorTema';

/**
 * Renderizador puro del manifiesto de navegación (spec T01 §8.2/§10.2). No hay tabla de
 * roles, catálogo de rutas ni construcción de URLs privilegiadas: `sesion.navigation.groups`
 * ya llega ordenado y filtrado por `ShellNavigationService` (PHP) — un ítem no autorizado no
 * viaja, así que este componente nunca decide a quién ocultarle qué. Lo único que calcula
 * localmente es qué entrada coincide con la URL actual, para el atributo `aria-current`.
 */
function esEntradaActiva(href: string | null, pathname: string): boolean {
  return href !== null && href === pathname;
}

type PropiedadesNavegacionLateral = {
  sesion: Sesion;
  /** Id estable del `<aside>`: el disparador del drawer móvil (`AppShell`) lo referencia vía
   *  `aria-controls`. Un `useId()` interno serviría para el `<nav>` pero no para que un
   *  hermano fuera de este árbol lo apunte. */
  id?: string;
  /** `AppShell` necesita el nodo real del `<aside>` para el respaldo inline del transform del
   *  drawer — ver el comentario sobre `data-shell-drawer-open` más abajo. React 19 acepta `ref`
   *  como prop normal, sin `forwardRef`. */
  ref?: Ref<HTMLElement>;
  /** Selector semanal completo (Tarea 5, T01): número, rango y acciones server-issued.
   *  `AppShell` arma este nodo (`ContextoSemana`) — este componente solo le da su lugar en el
   *  header, igual que ya hace con `children` en el footer para `MenuCuenta`. */
  contextoSemana?: ReactNode;
  /** Estado del rail persistente en escritorio (Tarea 4). `AppShell` es quien lo gobierna. */
  estado?: 'expanded' | 'collapsed';
  alAlternarEstado?: () => void;
  /** Drawer flotante bajo el umbral responsive (Tarea 4, mismo contrato que shell-drawer.js). */
  abiertoEnMovil?: boolean;
  /** Utilidades extra del pie (p. ej. el menú de cuenta que arma `AppShell`). */
  children?: ReactNode;
};

export function NavegacionLateral({
  sesion,
  id = 'app-shell-nav',
  ref,
  contextoSemana = null,
  estado = 'expanded',
  alAlternarEstado,
  abiertoEnMovil,
  children,
}: PropiedadesNavegacionLateral) {
  const projectName = sesion.project?.name ?? 'Proyecto';
  const displayName = sesion.user?.displayName ?? 'Usuario';
  const pathname = window.location.pathname;
  const navId = useId();

  return (
    <aside
      ref={ref}
      id={id}
      className="aia-navigation aia-navigation--sidebar"
      aria-label="Aplicación"
      data-shell-pattern="sidebar"
      data-sidebar-state={estado}
      data-shell-drawer-open={abiertoEnMovil ? 'true' : undefined}
    >
      <header className="aia-sidebar__header">
        {/* `.aia-sidebar__brand` es la clase que `shell-sidebar.css`/`navigation.css`
            fijan a `grid-column: 1` (contrato compartido con el shell PHP, que la
            emite en un `<a>`). Sin este contenedor, `.aia-sidebar__brand-name`
            quedaba huérfano de esa regla y auto-colocado por el grid — la causa
            raíz del bug de encabezado medido 2026-08-30 (ver navigation.css). */}
        <div className="aia-sidebar__brand">
          <strong className="aia-sidebar__brand-name">Last Planner AIA</strong>
        </div>
        <div className="aia-sidebar__context">
          <span>{projectName}</span>
          <small>{displayName}</small>
        </div>
        {contextoSemana}
        {alAlternarEstado && (
          <button
            type="button"
            className="aia-btn aia-btn--secondary aia-sidebar__toggle"
            aria-controls={navId}
            aria-expanded={estado === 'expanded'}
            aria-label={estado === 'expanded' ? 'Colapsar menú' : 'Expandir menú'}
            onClick={alAlternarEstado}
          >
            <span className="aia-sidebar__toggle-label">
              {estado === 'expanded' ? 'Colapsar menú' : 'Expandir menú'}
            </span>
          </button>
        )}
      </header>

      <nav id={navId} className="aia-sidebar__nav" aria-label="Navegación del proyecto">
        {sesion.navigation.groups.map((grupo) => (
          <section className="aia-sidebar__group" aria-labelledby={`grupo-${grupo.id}`} key={grupo.id}>
            <h3 id={`grupo-${grupo.id}`}>{grupo.label}</h3>
            <ul>
              {grupo.items.map((item) => (
                <li key={item.id}>
                  {item.href !== null ? (
                    <a
                      aria-current={esEntradaActiva(item.href, pathname) ? 'page' : undefined}
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
      </footer>
    </aside>
  );
}
