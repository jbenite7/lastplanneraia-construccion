import { Component, type ErrorInfo, type ReactNode } from 'react';

type PropiedadesLimiteErrorRuta = {
  children: ReactNode;
  /** Se invoca además de limpiar el estado interno, cuando el llamador quiere reintentar algo
   *  propio (p. ej. una recarga de datos) al pulsar "Reintentar". */
  alReintentar?: () => void;
};

type EstadoLimiteErrorRuta = { error: Error | null };

/**
 * Error boundary de ruta (Tarea 8, T01 §15 "5xx o fallo de render"): un error de render dentro
 * del outlet nunca deja la pantalla en blanco ni filtra su mensaje/stack crudo al DOM — solo se
 * registra en consola (diagnóstico local, nunca insertado en el árbol) y se pinta un panel
 * accesible (`role="alert"`) con una acción de reintento.
 *
 * Debe ser un componente de clase: React 19 todavía no tiene equivalente funcional para
 * `getDerivedStateFromError`/`componentDidCatch` (ver `rules/ecc/react/patterns.md`).
 */
export class LimiteErrorRuta extends Component<PropiedadesLimiteErrorRuta, EstadoLimiteErrorRuta> {
  state: EstadoLimiteErrorRuta = { error: null };

  static getDerivedStateFromError(error: Error): EstadoLimiteErrorRuta {
    return { error };
  }

  componentDidCatch(error: Error, info: ErrorInfo): void {
    // eslint-disable-next-line no-console -- única vía de diagnóstico de este boundary; nunca se
    // inserta en el DOM, solo en la consola de depuración.
    console.error('LimiteErrorRuta: error de render capturado', error, info.componentStack);
  }

  private reintentar = (): void => {
    this.setState({ error: null });
    this.props.alReintentar?.();
  };

  render(): ReactNode {
    if (this.state.error) {
      return (
        <section className="aia-alert aia-alert--error aia-panel-error aia-panel-error--render" role="alert">
          <h2>Algo salió mal</h2>
          <p>Esta vista no pudo mostrarse. Puedes intentarlo de nuevo.</p>
          <div className="aia-panel-error__acciones">
            <button className="aia-btn aia-btn--primary" onClick={this.reintentar} type="button">
              Reintentar
            </button>
          </div>
        </section>
      );
    }

    return this.props.children;
  }
}
