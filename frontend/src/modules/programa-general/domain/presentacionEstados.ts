export interface EstadoBadgeConfig {
  claseChip: string;
  colorDot: string;
  texto: string;
}

export function obtenerConfigEstado(estado: string | null | undefined): EstadoBadgeConfig {
  switch (estado) {
    case 'Atrasada':
      return { claseChip: 'chip-red', colorDot: 'var(--ds-color-state-critical-text)', texto: 'Atrasada' };
    case 'Con Alerta':
      return { claseChip: 'chip-amber', colorDot: 'var(--ds-color-state-warning-text)', texto: 'Con Alerta' };
    case 'Debe Iniciar':
      return { claseChip: 'chip-orange', colorDot: 'var(--ds-state-solid-orange)', texto: 'Debe Iniciar' };
    case 'En Curso':
      return { claseChip: 'chip-blue', colorDot: 'var(--ds-color-state-info-text)', texto: 'En Curso' };
    case 'Actividad Futura':
      return { claseChip: 'chip-green-future', colorDot: 'var(--ds-color-state-success-text)', texto: 'Futura' };
    case 'Terminada':
      return { claseChip: 'chip-neutral', colorDot: 'var(--ds-state-solid-neutral)', texto: 'Terminada' };
    case 'Fuera de Ventana':
      return { claseChip: 'chip-teal', colorDot: 'var(--ds-state-solid-teal)', texto: 'Fuera de Ventana' };
    default:
      return estado
        ? { claseChip: 'chip-gray', colorDot: 'var(--ds-text-muted)', texto: estado }
        : { claseChip: 'chip-violet', colorDot: 'var(--ds-state-solid-violet)', texto: 'Sin Datos' };
  }
}
