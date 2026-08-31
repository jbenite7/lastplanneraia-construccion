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

export function NavegacionLateral({ sesion }: { sesion: Sesion }) {
  const projectName = sesion.project?.name ?? 'Proyecto';
  const displayName = sesion.user?.displayName ?? 'Usuario';
  const pathname = window.location.pathname;

  return (
    <aside className="aia-navigation aia-navigation--sidebar" aria-label="Aplicación">
      <header className="aia-sidebar__header">
        <strong className="aia-sidebar__brand-name">Last Planner AIA</strong>
        <div className="aia-sidebar__context">
          <span>{projectName}</span>
          <small>{displayName}</small>
        </div>
      </header>

      <nav className="aia-sidebar__nav" aria-label="Navegación del proyecto">
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
      </footer>
    </aside>
  );
}
