import {
  FilaActividadPg,
  ContextoPg,
  RespuestaUpdatePg,
  RespuestaCortePg,
  esquemaContextoPg,
  esquemaFilaActividadPg,
  esquemaRespuestaUpdatePg,
  esquemaRespuestaCortePg,
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

export interface ActualizarEjecucionPayload {
  semana: number;
  db: string;
  csrf_token: string;
}

export interface ClienteHttpPg {
  get: <T>(url: string, schema: z.ZodType<T, any, any>) => Promise<T>;
  postForm: <T>(url: string, data: Record<string, unknown>, schema?: z.ZodType<T, any, any>, headers?: Record<string, string>) => Promise<T>;
}

const defaultCliente: ClienteHttpPg = {
  get: <T>(url: string, schema: z.ZodType<T, any, any>) => pedir(url, schema),
  postForm: async <T>(
    url: string,
    data: Record<string, unknown>,
    schema?: z.ZodType<T, any, any>,
    headers?: Record<string, string>
  ): Promise<T> => {
    const formData = new FormData();
    Object.entries(data).forEach(([k, v]) => {
      if (v !== undefined && v !== null) {
        formData.append(k, String(v));
      }
    });

    const targetSchema = (schema ?? z.unknown()) as z.ZodType<T, any, any>;
    return pedir(url, targetSchema, {
      method: 'POST',
      body: formData,
      headers,
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

    async guardarActividad(payload: GuardarActividadPayload): Promise<RespuestaUpdatePg> {
      return cliente.postForm<RespuestaUpdatePg>(
        `/api/general/update?semana_objetivo=${payload.semana}`,
        {
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
          _csrf_token: payload.csrf_token,
          csrf_token: payload.csrf_token,
        },
        esquemaRespuestaUpdatePg,
        {
          'X-CSRF-Token': payload.csrf_token,
        }
      );
    },

    async generarCorteXlsx(semana: number): Promise<RespuestaCortePg> {
      return cliente.postForm<RespuestaCortePg>(
        '/reportes/corte-programacion',
        { semana },
        esquemaRespuestaCortePg
      );
    },

    async actualizarEjecucion(payload: ActualizarEjecucionPayload): Promise<RespuestaUpdatePg> {
      const params = new URLSearchParams({ db: payload.db, semana: String(payload.semana) });
      return cliente.postForm<RespuestaUpdatePg>(
        `/api/general/update-batch?${params.toString()}`,
        {
          csrf_token: payload.csrf_token,
        },
        esquemaRespuestaUpdatePg,
        { 'X-CSRF-Token': payload.csrf_token }
      );
    },
  };
}
