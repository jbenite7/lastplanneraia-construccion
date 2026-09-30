import type { FilaActividadPg } from '../../../lib/api/esquemas/programa-general';
import { resolverCodigoActividad } from '../domain/modelo';

type PropiedadesCodigoActividad = {
  actividad: Pick<FilaActividadPg, 'codigo_actividad' | 'Id'>;
  className?: string;
};

/**
 * Celda de código de actividad (CÓD.). Sin código guardado muestra la numeración WBS del cronograma,
 * atenuada y con una explicación, para que no se confunda con un código que alguien asignó.
 */
export function CodigoActividad({ actividad, className }: PropiedadesCodigoActividad) {
  const codigo = resolverCodigoActividad(actividad);
  const clases = ['cell-code', codigo.calculado ? 'cell-code--calculado' : '', className ?? ''].filter(Boolean).join(' ');
  return (
    <code
      className={clases}
      title={codigo.calculado ? 'Código calculado desde la numeración WBS del cronograma' : undefined}
    >
      {codigo.texto || '-'}
    </code>
  );
}
