export interface EstadoBadgeConfig {
  claseChip: string;
  colorDot: string;
  texto: string;
}

export function obtenerConfigEstado(estado: string | null | undefined): EstadoBadgeConfig {
  switch (estado) {
    case 'Atrasada':
      return { claseChip: 'chip-red', colorDot: 'var(--ds-state-danger-text)', texto: 'Atrasada' };
    case 'Con Alerta':
      return { claseChip: 'chip-amber', colorDot: 'var(--ds-state-warning-text)', texto: 'Con Alerta' };
    case 'Debe Iniciar':
      return { claseChip: 'chip-orange', colorDot: '#f97316', texto: 'Debe Iniciar' };
    case 'En Curso':
      return { claseChip: 'chip-blue', colorDot: 'var(--ds-state-info-text)', texto: 'En Curso' };
    case 'Actividad Futura':
      return { claseChip: 'chip-green', colorDot: 'var(--ds-state-success-text)', texto: 'Futura' };
    case 'Terminada':
      return { claseChip: 'chip-gray', colorDot: 'var(--ds-text-muted)', texto: 'Terminada' };
    default:
      return { claseChip: 'chip-gray', colorDot: 'var(--ds-text-muted)', texto: estado || 'Sin Datos' };
  }
}
