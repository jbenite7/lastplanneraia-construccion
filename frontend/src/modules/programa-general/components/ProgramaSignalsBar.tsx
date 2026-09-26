import React from 'react';
import { ConteosSenales } from '../domain/filtros';

export type ConteosSenalesProps = ConteosSenales & {
  fueraVentana?: number;
  sinDatos?: number;
};

export interface ProgramaSignalsBarProps {
  conteos: ConteosSenalesProps;
  estadoFiltro: string | null;
  onSelectEstado: (estado: string | null) => void;
  className?: string;
  children?: React.ReactNode;
}

interface ChipItem {
  label: string;
  filterKey: string;
  count: number;
  color: string;
  className: string;
  title: string;
}

export const ProgramaSignalsBar: React.FC<ProgramaSignalsBarProps> = ({
  conteos,
  estadoFiltro,
  onSelectEstado,
  className = '',
  children,
}) => {
  const chips: ChipItem[] = [
    {
      label: 'Atrasada',
      filterKey: 'atrasada',
      count: conteos.atrasadas,
      color: 'var(--ds-color-state-critical-text)',
      className: 'chip-red',
      title: 'Atrasada: Actividades cuya fecha fin ya venció y no están al 100%',
    },
    {
      label: 'Con Alerta',
      filterKey: 'con-alerta-restricciones',
      count: conteos.conAlerta,
      color: 'var(--ds-color-state-warning-text)',
      className: 'chip-amber',
      title: 'Con Alerta: En ventana con restricciones pendientes',
    },
    {
      label: 'Debe Iniciar',
      filterKey: 'debe-iniciar',
      count: conteos.debeIniciar,
      color: 'var(--ds-state-solid-orange)',
      className: 'chip-orange',
      title: 'Debe Iniciar: Programada para arrancar en la semana activa',
    },
    {
      label: 'En Curso',
      filterKey: 'en-curso',
      count: conteos.enCurso,
      color: 'var(--ds-color-state-info-text)',
      className: 'chip-blue',
      title: 'En Curso: En ejecución física dentro de plazo',
    },
    {
      label: 'Actividad Futura',
      filterKey: 'actividad-futura',
      count: conteos.futuras,
      color: 'var(--ds-color-state-success-text)',
      className: 'chip-green-future',
      title: 'Actividad Futura: Programada para semanas posteriores',
    },
    {
      label: 'Terminada',
      filterKey: 'terminada',
      count: conteos.terminadas,
      color: 'var(--ds-state-solid-neutral)',
      className: 'chip-neutral',
      title: 'Terminada: Al 100% de ejecución',
    },
    {
      label: 'Fuera de Ventana',
      filterKey: 'fuera-de-ventana',
      count: conteos.fueraVentana ?? 0,
      color: 'var(--ds-state-solid-teal)',
      className: 'chip-teal',
      title: 'Fuera de Ventana: Más allá de 6 semanas',
    },
    {
      label: 'Sin Datos',
      filterKey: 'sin-datos',
      count: conteos.sinDatos ?? 0,
      color: 'var(--ds-state-solid-violet)',
      className: 'chip-violet',
      title: 'Sin Datos: Sin fechas contractuales válidas',
    },
  ];

  return (
    <div
      className={`signals-bar ${className}`.trim()}
      role="region"
      aria-label="Filtros rápidos por señal de estado"
    >
      {children}
      <div
        id="pgLegend"
        className="signals-chips-row state-chips-container"
        role="group"
        aria-label="Filtro rápido por estado operativo"
      >
        {chips.map((c) => {
          const isActive = estadoFiltro === c.label;
          return (
            <button
              key={c.label}
              type="button"
              data-filter={c.filterKey}
              className={`signal-chip state-chip-btn pg-filter-chip ${c.className} ${isActive ? 'active' : ''}`.trim()}
              onClick={() => onSelectEstado(isActive ? null : c.label)}
              aria-pressed={isActive}
              title={c.title}
            >
              <span
                className="signal-dot chip-dot indicator"
                style={{ backgroundColor: c.color }}
                aria-hidden="true"
              />
              <span className="signal-label">{c.label}</span>
              <span className="signal-count chip-count monospace">{c.count}</span>
            </button>
          );
        })}
      </div>
    </div>
  );
};
