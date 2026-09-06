import { calcularItr, restriccionesBlandas, vistaRestriccionesBlandas, type ConfiguracionRestricciones } from '../dominio/restricciones';
import type { FilaLps } from '../dominio/campos';

type PropiedadesIndicadorRestricciones = {
  fila: FilaLps;
  config: ConfiguracionRestricciones;
};

/**
 * Indicador de restricciones habilitantes duras (ITR) + blandas informativas (T02-AC-050/074).
 * Nunca bloquea nada por sí mismo: sólo muestra liberadas/aplicables, porcentaje y una alternativa
 * textual — la matriz pura vive en `dominio/restricciones.ts`.
 */
export function IndicadorRestricciones({ fila, config }: PropiedadesIndicadorRestricciones) {
  const itr = calcularItr(fila, config);
  const blandas = restriccionesBlandas(config);
  const vistasBlandas = vistaRestriccionesBlandas(fila, config);

  return (
    <section className="lps-restricciones" aria-label="Restricciones habilitantes">
      <p>
        ITR: {itr.liberadas}/{itr.aplicables} liberadas ({itr.porcentaje}%)
      </p>
      <ul className="lps-restricciones__lista">
        {itr.items.map((item) => (
          <li key={item.key} className="lps-restricciones__item">
            <span>{item.label}</span>
            <span>{item.applicable ? `${Math.round(item.progress * 100)}%` : 'No aplica'}</span>
          </li>
        ))}
      </ul>
      {blandas.length > 0 ? (
        <>
          <p>Restricciones informativas (no bloquean):</p>
          <ul className="lps-restricciones__lista">
            {vistasBlandas.map((vista) => (
              <li key={vista.label} className="lps-restricciones__item">
                <span>{vista.label}</span>
                <span>{vista.percent}%</span>
              </li>
            ))}
          </ul>
        </>
      ) : null}
    </section>
  );
}
