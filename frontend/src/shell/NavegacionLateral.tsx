import type { ReactNode, Ref } from 'react';
import type { Sesion } from '../lib/api/esquemas/sesion';
import { BarraLateral, type ItemBarraLateral } from './navegacion/BarraLateral';

/**
 * Encuentra la entrada del manifiesto cuyo `href` coincide EXACTAMENTE con la URL actual (spec
 * T01 §8.2/§10.2). El servidor ya resolvió rol, membresía y visibilidad — ningún ítem no
 * autorizado viaja — así que lo único que se calcula aquí es cuál id recibe `aria-current`.
 */
function idActivo(groups: Sesion['navigation']['groups'], pathname: string): string {
  for (const grupo of groups) {
    for (const item of grupo.items) {
      if (item.href !== null && item.href === pathname) return item.id;
    }
  }
  return '';
}

type PropiedadesNavegacionLateral = {
  sesion: Sesion;
  /** Id estable del `<aside>`: el disparador del drawer móvil (`AppShell`) lo referencia vía
   *  `aria-controls`. Un `useId()` interno serviría para el `<nav>` pero no para que un
   *  hermano fuera de este árbol lo apunte. */
  id?: string;
  /** `AppShell` necesita el nodo real del `<aside>` para el respaldo inline del transform del
   *  drawer — ver el comentario sobre `data-shell-drawer-open` en `BarraLateral`. React 19 acepta
   *  `ref` como prop normal, sin `forwardRef`. */
  ref?: Ref<HTMLElement>;
  alEjecutarAccion?: (item: ItemBarraLateral) => void;
  /** Estado del rail persistente en escritorio (Tarea 4). `AppShell` es quien lo gobierna. */
  estado?: 'expanded' | 'collapsed';
  alAlternarEstado?: () => void;
  /** Drawer flotante bajo el umbral responsive (Tarea 4, mismo contrato que shell-drawer.js). */
  abiertoEnMovil?: boolean;
  /** Utilidades extra del pie (p. ej. el menú de cuenta que arma `AppShell`). */
  children?: ReactNode;
};

/**
 * Envoltura delgada (Tarea 7, S04) sobre `BarraLateral`: arma `groups`/`activeId`/`context`
 * desde `Sesion` y delega todo el renderizado — marca, nav, rail/drawer, pie — en el rail
 * genérico. No hay tabla de roles, catálogo de rutas ni construcción de URLs privilegiadas aquí:
 * `sesion.navigation.groups` ya llega ordenado y filtrado por `ShellNavigationService` (PHP).
 *
 * `barraAutonoma={false}`: el disparador del drawer móvil y su velo viven en `AppShell`, fuera
 * de este árbol, desde T01 — dejar que `BarraLateral` gobierne su propio drawer aquí duplicaría
 * ese control.
 */
export function NavegacionLateral({
  sesion,
  id = 'app-shell-nav',
  ref,
  alEjecutarAccion,
  estado,
  alAlternarEstado,
  abiertoEnMovil,
  children,
}: PropiedadesNavegacionLateral) {
  const projectName = sesion.project?.name ?? 'Proyecto';
  const displayName = sesion.user?.displayName ?? 'Usuario';
  const pathname = window.location.pathname;

  return (
    <BarraLateral
      activeId={idActivo(sesion.navigation.groups, pathname)}
      accountName={displayName}
      context={{ primary: projectName, secondary: displayName }}
      groups={sesion.navigation.groups}
      showChangeProject={false}
      barraAutonoma={false}
      id={id}
      ref={ref}
      alEjecutarAccion={alEjecutarAccion}
      estado={estado}
      alAlternarEstado={alAlternarEstado}
      abiertoEnMovil={abiertoEnMovil}
    >
      {children}
    </BarraLateral>
  );
}
