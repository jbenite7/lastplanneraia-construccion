import type { ListaProyectos } from '../../lib/api/esquemas/proyectos';
import type { GrupoBarraLateral } from '../navegacion/BarraLateral';

/**
 * Arma el único grupo de navegación de la pantalla standalone `/proyectos` (Tarea 7, S04):
 * "Tus proyectos" siempre (apunta a la propia pantalla — nunca hay nada más que mostrar ahí
 * mismo), y "Control Tower - Informes" solo cuando el manifiesto ya cargado lo autoriza. No
 * interpreta roles ni capacidades: `visible`/`href` ya vinieron resueltos por el servidor en
 * `ListaProyectos.navigation` (mismo contrato que `sesion.navigation.bi` en T01).
 *
 * `navigation` es `null` mientras `SelectorProyectos` no ha resuelto su fetch — el grupo sale
 * igual, sin BI, para que el rail nunca quede vacío mientras la lista carga.
 */
export function navegacionSelectorProyectos(
  navigation: ListaProyectos['navigation'] | null,
): readonly GrupoBarraLateral[] {
  return [
    {
      id: 'global',
      label: 'Navegación',
      items: [
        { id: 'projects', label: 'Tus proyectos', href: '/proyectos' },
        ...(navigation?.bi.visible
          ? [{ id: 'bi', label: 'Control Tower - Informes', href: navigation.bi.href }]
          : []),
      ],
    },
  ];
}
