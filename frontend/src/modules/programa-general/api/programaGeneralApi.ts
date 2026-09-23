import {
  FilaActividadPg,
  ContextoPg,
  esquemaContextoPg,
  esquemaFilaActividadPg,
} from '../../../lib/api/esquemas/programa-general';
import { pedir } from '../../../lib/api/cliente';
import { z } from 'zod';

export interface GuardarActividadPayload {
  unique_id: number;
  semana: number;
  Fecha_Inicio: string | null;
  Fecha_Fin: string | null;
  unidad: string;
  cantidad_ppto: number | null;
  Ejecutado: number | null;
  EjecutadoRatio: number | null;
  codigo_actividad: string;
  Responsable_AIA?: string | null;
  Sub_Contratista?: string | null;
  csrf_token: string;
}

export interface ClienteHttpPg {
  get: <T>(url: string, schema: z.ZodType<T, any, any>) => Promise<T>;
  postForm: (url: string, data: Record<string, unknown>) => Promise<{ success: boolean; data?: unknown }>;
}

const defaultCliente: ClienteHttpPg = {
  get: <T>(url: string, schema: z.ZodType<T, any, any>) => pedir(url, schema),
  postForm: async (url: string, data: Record<string, unknown>) => {
    const formData = new FormData();
    Object.entries(data).forEach(([k, v]) => {
      if (v !== undefined && v !== null) {
        formData.append(k, String(v));
      }
    });
    return pedir(url, z.object({ success: z.boolean(), data: z.unknown().optional() }), {
      method: 'POST',
      body: formData,
    });
  },
};

export const esquemaListaActividadesPg: z.ZodType<{ data: FilaActividadPg[] }> = z.preprocess((val: unknown) => {
  if (Array.isArray(val)) {
    return { data: val };
  }
  return val;
}, z.object({ data: z.array(esquemaFilaActividadPg) }));

export function programaGeneralApi(cliente: ClienteHttpPg = defaultCliente) {
  return {
    async obtenerContexto(): Promise<ContextoPg> {
      return cliente.get('/api/programa-general/context', esquemaContextoPg);
    },

    async obtenerActividades(semana: number): Promise<FilaActividadPg[]> {
      const response = await cliente.get<{ data: FilaActividadPg[] }>(
        `/api/general/list?semana=${semana}`,
        esquemaListaActividadesPg
      );
      return response.data;
    },

    async guardarActividad(payload: GuardarActividadPayload): Promise<{ success: boolean; data?: unknown }> {
      return cliente.postForm(`/api/general/update?semana_objetivo=${payload.semana}`, {
        unique_id: payload.unique_id,
        Fecha_Inicio: payload.Fecha_Inicio,
        Fecha_Fin: payload.Fecha_Fin,
        unidad: payload.unidad,
        cantidad_ppto: payload.cantidad_ppto,
        Ejecutado: payload.Ejecutado,
        EjecutadoRatio: payload.EjecutadoRatio,
        codigo_actividad: payload.codigo_actividad,
        Responsable_AIA: payload.Responsable_AIA ?? '',
        Sub_Contratista: payload.Sub_Contratista ?? '',
        csrf_token: payload.csrf_token,
      });
    },

    async generarCorteXlsx(semana: number): Promise<{ url: string }> {
      const res = await cliente.postForm('/reportes/corte-programacion', { semana });
      return (res.data ? res.data : res) as { url: string };
    },
  };
}
