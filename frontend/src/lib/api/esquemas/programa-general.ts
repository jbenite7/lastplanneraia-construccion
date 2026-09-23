import { z } from 'zod';

export const esquemaFilaActividadPg = z.object({
  unique_id: z.coerce.number(),
  Consecutivo_en_Programa: z.union([z.string(), z.number()]).transform(String).nullable().optional(),
  Id: z.union([z.string(), z.number()]).transform(String).optional(),
  Actividad: z.string(),
  Titulo: z.coerce.number(),
  Fecha_Inicio: z.string().nullable().optional(),
  Fecha_Fin: z.string().nullable().optional(),
  Ruta_Critica: z.coerce.number().optional().default(0),
  Ejecutado: z.coerce.number().nullable().optional(),
  Estado: z.string().nullable().optional(),
  Semanas_Inicio: z.coerce.number().nullable().optional(),
  Estado_Restricciones: z.string().nullable().optional(),
  cantidad_ppto: z.coerce.number().nullable().optional(),
  unidad: z.string().nullable().optional(),
  codigo_actividad: z.string().nullable().optional(),
  Responsable_AIA: z.string().nullable().optional(),
  Sub_Contratista: z.string().nullable().optional(),
  Observaciones: z.string().nullable().optional(),
  alerta_crisis: z.coerce.number().optional().default(0),
});

export type FilaActividadPg = z.infer<typeof esquemaFilaActividadPg>;

export const esquemaContextoPgBase = z.object({
  proyecto: z.object({
    id: z.number(),
    nombre: z.string(),
    codigo: z.string(),
    tipo: z.string().optional(),
  }),
  semana: z.object({
    numero: z.number(),
    confirmada: z.boolean(),
    esPasada: z.boolean(),
  }),
  permisos: z.object({
    puedeVer: z.boolean(),
    puedeEditar: z.boolean(),
    puedeCorteXlsx: z.boolean(),
    puedeLote: z.boolean(),
    readDrawer: z.boolean(),
    writeDrawer: z.boolean(),
  }),
  catalogos: z.object({
    unidades: z.array(z.string()).default([]),
    codigos: z.array(z.string()).default([]),
    profesionales: z.array(z.object({
      id: z.coerce.number(),
      nombre: z.string(),
      cargo: z.string().optional(),
    })).default([]),
    subcontratistas: z.array(z.object({
      id: z.coerce.number(),
      nombre: z.string(),
      especialidad: z.string().optional(),
    })).default([]),
  }),
  csrf_token: z.string(),
});

export const esquemaContextoPg = z.preprocess((val: unknown) => {
  if (!val || typeof val !== 'object') return val;
  const raw = val as Record<string, unknown>;
  const obj = (raw.data && typeof raw.data === 'object' && !('proyecto' in raw) && !('project' in raw))
    ? (raw.data as Record<string, unknown>)
    : raw;

  if ('project' in obj && !('proyecto' in obj)) {
    const proj = (obj.project ?? {}) as Record<string, unknown>;
    const wk = (obj.week ?? {}) as Record<string, unknown>;
    const act = (obj.actions ?? {}) as Record<string, unknown>;
    const csrf = (obj.csrf ?? {}) as Record<string, unknown>;
    const cat = (obj.catalogos ?? {}) as Record<string, unknown>;
    const num = Number(wk.number ?? 0);
    const max = Number(wk.max ?? 0);

    return {
      proyecto: {
        id: Number(proj.id ?? 0),
        nombre: String(proj.name ?? ''),
        codigo: String(proj.name ?? ''),
        tipo: proj.area ? String(proj.area) : undefined,
      },
      semana: {
        numero: num,
        confirmada: Boolean(wk.confirmed),
        esPasada: max > 0 && num < max,
      },
      permisos: {
        puedeVer: true,
        puedeEditar: Boolean(act.editPlanFields ?? false),
        puedeCorteXlsx: Boolean(act.downloadCut ?? false),
        puedeLote: Boolean(act.runBatch ?? false),
        readDrawer: Boolean(act.readDrawer ?? false),
        writeDrawer: Boolean(act.writeDrawer ?? false),
      },
      catalogos: {
        unidades: Array.isArray(cat.unidades) ? cat.unidades : ['m³', 'm²', 'ml', 'kg', 'ton', 'und', 'gl', 'mes', '%'],
        codigos: Array.isArray(cat.codigos) ? cat.codigos : [],
        profesionales: Array.isArray(cat.profesionales) ? cat.profesionales : [],
        subcontratistas: Array.isArray(cat.subcontratistas) ? cat.subcontratistas : [],
      },
      csrf_token: typeof csrf.programaGeneral === 'string' ? csrf.programaGeneral : String(obj.csrf_token ?? ''),
    };
  }
  return obj;
}, esquemaContextoPgBase);

export type ContextoPg = z.infer<typeof esquemaContextoPgBase>;

export const esquemaRespuestaUpdatePg = z.preprocess((val: unknown) => {
  if (val && typeof val === 'object' && 'respuesta' in val && (val as any).respuesta === 'BIEN') {
    return { success: true, ...(val as any) };
  }
  return val;
}, z.object({
  success: z.boolean().default(true),
  respuesta: z.string().optional(),
  estado: z.string().optional(),
  Semana_Inicio: z.coerce.number().optional(),
}));

export type RespuestaUpdatePg = z.infer<typeof esquemaRespuestaUpdatePg>;

export const esquemaRespuestaCortePg = z.object({
  url: z.string().regex(/\.xlsx($|\?)/, 'Debe ser una URL de archivo Excel válido'),
});

export type RespuestaCortePg = z.infer<typeof esquemaRespuestaCortePg>;
