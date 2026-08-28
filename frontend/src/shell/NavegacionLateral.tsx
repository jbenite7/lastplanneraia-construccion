import type { Sesion } from '../lib/api/esquemas/sesion';
import { ConmutadorTema } from './ConmutadorTema';

type EntradaNavegacion = {
  id: string;
  etiqueta: string;
  ruta?: string;
  accion?: true;
};

type GrupoNavegacion = {
  id: string;
  etiqueta: string;
  entradas: readonly EntradaNavegacion[];
};

const ocultasPorRol: Readonly<Record<string, readonly string[]>> = {
  G: ['profesionales', 'subcontratistas', 'plan-compras', 'actualizar-cronograma', 'control-cambios', 'programa-general', 'programacion-intermedia'],
  S: ['profesionales', 'subcontratistas', 'plan-compras', 'actualizar-cronograma', 'control-cambios', 'programa-general', 'programacion-intermedia'],
  SG: ['profesionales', 'subcontratistas', 'plan-compras', 'actualizar-cronograma', 'control-cambios', 'programa-general', 'programacion-intermedia'],
  C: ['profesionales', 'subcontratistas', 'plan-compras', 'actualizar-cronograma', 'control-cambios', 'programa-general', 'programacion-intermedia'],
  V: ['actualizar-cronograma', 'control-cambios'],
  OT: ['actualizar-cronograma'],
  DCV: ['actualizar-cronograma'],
};

const grupos: readonly GrupoNavegacion[] = [
  {
    id: 'informacion',
    etiqueta: 'Información',
    entradas: [
      { id: 'semanas-proyecto', etiqueta: 'Semanas del Proyecto', accion: true },
      { id: 'profesionales', etiqueta: 'Profesionales', ruta: '/profesionales' },
      { id: 'subcontratistas', etiqueta: 'Subcontratistas', ruta: '/subcontratistas' },
      { id: 'indicadores', etiqueta: 'Indicadores LPS', ruta: '/indicadores' },
      { id: 'control-cambios', etiqueta: 'Control de Cambios', ruta: '/control-cambios' },
    ],
  },
  {
    id: 'obra',
    etiqueta: 'Obra',
    entradas: [
      { id: 'programa-general', etiqueta: 'Programa General', ruta: '/programa-general' },
      { id: 'programacion-intermedia', etiqueta: 'Programación Intermedia', ruta: '/programacion-intermedia' },
      { id: 'programacion-semanal', etiqueta: 'Programación Semanal', ruta: '/programacion-semanal' },
      { id: 'actualizar-cronograma', etiqueta: 'Actualizar Cronograma', ruta: '/programa-general-actualizar' },
    ],
  },
  {
    id: 'compras',
    etiqueta: 'Compras',
    entradas: [
      { id: 'plan-compras', etiqueta: 'Plan de Compras', ruta: '/plan-compras' },
    ],
  },
];

function esVisible(entrada: EntradaNavegacion, sesion: Sesion): boolean {
  const rol = sesion.user?.role ?? '';
  if (ocultasPorRol[rol]?.includes(entrada.id)) {
    return false;
  }

  return true;
}

export function NavegacionLateral({ sesion }: { sesion: Sesion }) {
  const projectName = sesion.project?.name ?? 'Proyecto';
  const displayName = sesion.user?.displayName ?? 'Usuario';

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
        {grupos.map((grupo) => {
          const controlTower = sesion.navigation.bi;
          const entradas = grupo.id === 'informacion' && controlTower?.visible && controlTower.href
            ? [{ id: 'control-tower', etiqueta: 'Control Tower - Informes', ruta: controlTower.href }, ...grupo.entradas]
            : grupo.entradas;
          const entradasVisibles = entradas.filter((entrada) => esVisible(entrada, sesion));

          return (
            <section className="aia-sidebar__group" aria-labelledby={`grupo-${grupo.id}`} key={grupo.id}>
              <h3 id={`grupo-${grupo.id}`}>{grupo.etiqueta}</h3>
              <ul>
                {entradasVisibles.map((entrada) => (
                  <li key={entrada.id}>
                    {entrada.ruta ? (
                      <a className="aia-sidebar__link" href={entrada.ruta}>
                        <span className="aia-sidebar__label">{entrada.etiqueta}</span>
                      </a>
                    ) : (
                      <button
                        aria-disabled={entrada.accion}
                        aria-label={entrada.etiqueta}
                        className="aia-sidebar__link"
                        disabled={entrada.accion}
                        type="button"
                      >
                        <span className="aia-sidebar__label">{entrada.etiqueta}</span>
                      </button>
                    )}
                  </li>
                ))}
              </ul>
            </section>
          );
        })}
      </nav>

      <footer className="aia-sidebar__footer">
        <ConmutadorTema />
      </footer>
    </aside>
  );
}
