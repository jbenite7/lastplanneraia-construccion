/**
 * Marca "Last Planner AIA" con enlace a `/proyectos` (Tarea 9b, S04 — pedido de Felipe en la
 * revisión de candidatos: «en todas las vistas de la app quiero ver el logo»). Reproduce la
 * paridad exacta con la barra canónica PHP (`DesignSystemComponent.php:430-432`): mismas clases
 * (`aia-sidebar__brand aia-brand-lockup`, `aia-sidebar__brand-name`) para que
 * `navigation.css` (`.aia-sidebar__brand img`, tamaño `--ds-sidebar-brand-mark-size`, sin
 * filtro) y las reglas de contraste de `.aia-sidebar__brand` le den estilo sin CSS nuevo.
 *
 * `<a href>` normal, no `<Link>` de react-router: durante el piloto `/proyectos` lo sirve PHP
 * (recarga completa), igual que hace el propio `DesignSystemComponent`.
 *
 * Se usa en tres sitios (Tarea 9b): la cabecera del `<aside>` (`BarraLateral`, paridad de
 * escritorio con el rail colapsado "solo ícono" que ya resuelve `navigation.css:480-511`), y la
 * fila `.shell-mobile-topbar` bajo 1180px en las dos pantallas React (`BarraLateral` autónoma y
 * `AppShell`) — de ahí que viva como componente propio en vez de repetirse tres veces.
 */
export function MarcaLockup({ className }: { className?: string }) {
  const clases = className ? `aia-sidebar__brand aia-brand-lockup ${className}` : 'aia-sidebar__brand aia-brand-lockup';

  return (
    <a className={clases} href="/proyectos" aria-label="Last Planner AIA">
      <img src="/public/img/brand/icon.svg" alt="" aria-hidden="true" />
      <strong className="aia-sidebar__brand-name">Last Planner AIA</strong>
    </a>
  );
}
