/**
 * `Semanas_Inicio` es un desfase respecto de la semana vigente, no un número de semana
 * (`src/Legacy/modificar_sem_estado.php:50`, `pg_calculate_week_offset`). Spec S05-DEUDA 1.1, punto 3.
 */
export function formatearInicioRelativo(semanas: number | null | undefined): string {
  if (semanas === null || semanas === undefined || !Number.isFinite(semanas)) return '–';
  const n = Math.round(semanas);
  if (n === 0) return 'Esta sem'; // también cubre -0
  return n < 0 ? `Hace ${Math.abs(n)} sem` : `En ${n} sem`;
}
