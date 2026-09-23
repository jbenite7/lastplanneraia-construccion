import { describe, it, expect, vi } from 'vitest';
import {
  esquemaContextoPg,
  esquemaFilaActividadPg,
} from '../../../lib/api/esquemas/programa-general';
import { programaGeneralApi } from './programaGeneralApi';

describe('programaGeneralApi & esquemas', () => {
  it('valida el esquema de una fila de actividad completa', () => {
    const mockFila = {
      unique_id: 101,
      Consecutivo_en_Programa: 'EST-01',
      Actividad: 'Excavación mecánica de zapatas eje A-C',
      Titulo: 0,
      Fecha_Inicio: '2026-08-10',
      Fecha_Fin: '2026-08-20',
      Ruta_Critica: 1,
      Ejecutado: 0.25,
      Estado: 'Atrasada',
      Semanas_Inicio: 33,
      Estado_Restricciones: '0.66',
      cantidad_ppto: 450.0,
      unidad: 'm³',
      codigo_actividad: 'EST-01',
      Responsable_AIA: 'Ing. Carlos Restrepo',
      Sub_Contratista: 'Excavaciones del Norte S.A.S.',
    };

    const parsed = esquemaFilaActividadPg.safeParse(mockFila);
    expect(parsed.success).toBe(true);
  });

  it('formatea el payload para POST /api/general/update incluyendo asignaciones', async () => {
    const mockCliente = {
      get: vi.fn(),
      postForm: vi.fn().mockResolvedValue({ success: true }),
    };
    const api = programaGeneralApi(mockCliente as any);

    await api.guardarActividad({
      unique_id: 101,
      semana: 33,
      Fecha_Inicio: '2026-08-10',
      Fecha_Fin: '2026-08-20',
      unidad: 'm³',
      cantidad_ppto: 450.0,
      Ejecutado: 25.0,
      EjecutadoRatio: 0.25,
      codigo_actividad: 'EST-01',
      Responsable_AIA: 'Ing. Carlos Restrepo',
      Sub_Contratista: 'Excavaciones del Norte S.A.S.',
      csrf_token: 'token123',
    });

    expect(mockCliente.postForm).toHaveBeenCalledWith(
      '/api/general/update?semana_objetivo=33',
      expect.objectContaining({
        unique_id: 101,
        Responsable_AIA: 'Ing. Carlos Restrepo',
        Sub_Contratista: 'Excavaciones del Norte S.A.S.',
      })
    );
  });

  it('valida el esquema de contexto con formato adaptado y catálogos', () => {
    const mockContexto = {
      proyecto: { id: 73, nombre: 'Torre 1', codigo: 'T1' },
      semana: { numero: 33, confirmada: false, esPasada: false },
      permisos: {
        puedeVer: true,
        puedeEditar: true,
        puedeCorteXlsx: true,
        puedeLote: true,
        readDrawer: true,
        writeDrawer: true,
      },
      catalogos: {
        unidades: ['m³', '%'],
        codigos: ['EST-01'],
        profesionales: [{ id: 1, nombre: 'Carlos Restrepo', cargo: 'Director' }],
        subcontratistas: [{ id: 10, nombre: 'Cimentaciones SAS', especialidad: 'Cimentación' }],
      },
      csrf_token: 'csrf123',
    };

    const parsed = esquemaContextoPg.safeParse(mockContexto);
    expect(parsed.success).toBe(true);
  });

  it('adapta el payload crudo del backend PHP en esquemaContextoPg', () => {
    const rawBackendContext = {
      project: { id: 73, name: 'Torre 1', area: 'Construccion' },
      week: { number: 18, max: 20, confirmed: false },
      actions: {
        editPlanFields: true,
        editProgress: true,
        runBatch: true,
        downloadCut: true,
        readDrawer: true,
        writeDrawer: true,
      },
      csrf: {
        programaGeneral: 'csrf_pg_token',
        drawer: 'csrf_drawer_token',
      },
      catalogos: {
        profesionales: [{ id: 1, nombre: 'Carlos Restrepo' }],
        subcontratistas: [{ id: 2, nombre: 'Excavaciones del Norte' }],
      },
    };

    const parsed = esquemaContextoPg.safeParse(rawBackendContext);
    expect(parsed.success).toBe(true);
    if (parsed.success) {
      expect(parsed.data.proyecto.id).toBe(73);
      expect(parsed.data.proyecto.nombre).toBe('Torre 1');
      expect(parsed.data.semana.numero).toBe(18);
      expect(parsed.data.semana.esPasada).toBe(true);
      expect(parsed.data.csrf_token).toBe('csrf_pg_token');
      expect(parsed.data.catalogos.profesionales).toHaveLength(1);
    }
  });

  it('obtiene contexto llamando a cliente.get con /api/programa-general/context', async () => {
    const mockContexto = {
      proyecto: { id: 1, nombre: 'Proyecto Prueba', codigo: 'PRU' },
      semana: { numero: 33, confirmada: false, esPasada: false },
      permisos: {
        puedeVer: true,
        puedeEditar: true,
        puedeCorteXlsx: true,
        puedeLote: true,
        readDrawer: true,
        writeDrawer: true,
      },
      catalogos: {
        unidades: ['m³'],
        codigos: ['EST-01'],
        profesionales: [],
        subcontratistas: [],
      },
      csrf_token: 'csrf123',
    };

    const mockCliente = {
      get: vi.fn().mockResolvedValue(mockContexto),
      postForm: vi.fn(),
    };
    const api = programaGeneralApi(mockCliente as any);

    const ctx = await api.obtenerContexto();
    expect(mockCliente.get).toHaveBeenCalledWith('/api/programa-general/context', esquemaContextoPg);
    expect(ctx.proyecto.id).toBe(1);
  });

  it('obtiene actividades delegando a cliente.get con semana', async () => {
    const mockActividades = [
      {
        unique_id: 101,
        Actividad: 'Excavación',
        Titulo: 0,
      },
    ];

    const mockCliente = {
      get: vi.fn().mockResolvedValue({ data: mockActividades }),
      postForm: vi.fn(),
    };
    const api = programaGeneralApi(mockCliente as any);

    const filas = await api.obtenerActividades(33);
    expect(mockCliente.get).toHaveBeenCalledWith(
      '/api/general/list?semana=33',
      expect.anything()
    );
    expect(filas).toHaveLength(1);
    expect(filas[0].unique_id).toBe(101);
  });

  it('genera corte XLSX llamando a /reportes/corte-programacion', async () => {
    const mockCliente = {
      get: vi.fn(),
      postForm: vi.fn().mockResolvedValue({ data: { url: '/descargas/corte_33.xlsx' } }),
    };
    const api = programaGeneralApi(mockCliente as any);

    const res = await api.generarCorteXlsx(33);
    expect(mockCliente.postForm).toHaveBeenCalledWith('/reportes/corte-programacion', { semana: 33 });
    expect(res.url).toBe('/descargas/corte_33.xlsx');
  });
});
