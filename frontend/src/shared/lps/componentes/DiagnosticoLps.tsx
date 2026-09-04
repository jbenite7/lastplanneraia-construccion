import type { LpsActivityContext } from '../estado/LpsDrawerProvider';

type PropiedadesDiagnosticoLps = {
  contexto: LpsActivityContext;
  ocultaPorFiltros: boolean;
};

/**
 * Diagnóstico textual del encabezado (T02-AC-009, AC-073): estado, brechas, progreso y siguiente
 * acción, sin insertar HTML no confiable — todo el texto pasa por JSX plano (React escapa).
 */
export function DiagnosticoLps({ contexto, ocultaPorFiltros }: PropiedadesDiagnosticoLps) {
  const { activity } = contexto;

  if (activity.isHeader) {
    return (
      <section className="lps-diagnostico" aria-label="Diagnóstico">
        <p>Capítulo — sin acciones de actividad disponibles.</p>
      </section>
    );
  }

  return (
    <section className="lps-diagnostico" aria-label="Diagnóstico">
      <p>
        <strong>Estado:</strong> {activity.state.label}
      </p>
      <p>
        <strong>Avance:</strong> {activity.progress.display}
      </p>
      {activity.state.actions.length > 0 ? (
        <ul>
          {activity.state.actions.map((accion, indice) => (
            // eslint-disable-next-line react/no-array-index-key -- texto de acción sin id propio del servidor.
            <li key={indice}>{accion}</li>
          ))}
        </ul>
      ) : null}
      {ocultaPorFiltros ? (
        <p role="status" className="lps-diagnostico__oculta">
          Oculta por los filtros
        </p>
      ) : null}
    </section>
  );
}
