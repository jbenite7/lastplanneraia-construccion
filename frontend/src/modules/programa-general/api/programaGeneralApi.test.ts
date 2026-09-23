import { describe, it, expect, vi } from 'vitest';
import {
  esquemaContextoPg,
  esquemaFilaActividadPg,
  esquemaRespuestaUpdatePg,
  esquemaRespuestaCortePg,
} from '../../../lib/api/esquemas/programa-general';
import { programaGeneralApi } from './programaGeneralApi';

describe('programaGeneralApi & esquemas', () => {
  it('valida el esquema de una fila con Id WBS jerárquico y Consecutivo numérico', () => {
    const mockFila = {
      unique_id: 101,
      Consecutivo_en_Programa: 101, // PDO devuelve número
      Id: '1.1.2', // Ruta WBS jerárquica
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
    if (parsed.success) {
      expect(parsed.data.Id).toBe('1.1.2');
      expect(parsed.data.Consecutivo_en_Programa).toBe('101');
    }
  });

  it('formatea el payload para POST /api/general/update enviando _csrf_token y cabecera X-CSRF-Token', async () => {
    const mockCliente = {
      get: vi.fn(),
      postForm: vi.fn().mockResolvedValue({ success: true, respuesta: 'BIEN', estado: 'En Curso' }),
    };
    const api = programaGeneralApi(mockCliente as any);

    const res = await api.guardarActividad({
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
        _csrf_token: 'token123',
        csrf_token: 'token123',
      }),
      esquemaRespuestaUpdatePg,
      {
        'X-CSRF-Token': 'token123',
      }
    );
    expect(res.respuesta).toBe('BIEN');
  });

  it('valida la respuesta real legacy de GeneralApiController (respuesta: BIEN)', () => {
    const rawLegacy = {
      respuesta: 'BIEN',
      estado: 'Atrasada',
      Semana_Inicio: '33',
    };
    const parsed = esquemaRespuestaUpdatePg.safeParse(rawLegacy);
    expect(parsed.success).toBe(true);
    if (parsed.success) {
      expect(parsed.data.success).toBe(true);
      expect(parsed.data.Semana_Inicio).toBe(33);
    }
  });

  it('valida la respuesta real de ReportController para corte XLSX', async () => {
    const mockCliente = {
      get: vi.fn(),
      postForm: vi.fn().mockResolvedValue({ url: '/public/storage/cortesProgramacion/corte_1_sem33.xlsx' }),
    };
    const api = programaGeneralApi(mockCliente as any);

    const res = await api.generarCorteXlsx(33);
    expect(res.url).toContain('.xlsx');
    expect(mockCliente.postForm).toHaveBeenCalledWith(
      '/reportes/corte-programacion',
      { semana: 33 },
      esquemaRespuestaCortePg
    );
  });

  it('aplica fail-close (false) en permisos cuando el objeto de acciones viene incompleto', () => {
    const rawBackendIncompleto = {
      project: { id: 1, name: 'Proyecto Prueba' },
      week: { number: 33, max: 33, confirmed: 0 },
      actions: {}, // Sin flags de acciones
      csrf: { programaGeneral: 'csrf_test' },
      catalogos: {},
    };

    const parsed = esquemaContextoPg.safeParse(rawBackendIncompleto);
    expect(parsed.success).toBe(true);
    if (parsed.success) {
      expect(parsed.data.permisos.puedeEditar).toBe(false);
      expect(parsed.data.permisos.puedeCorteXlsx).toBe(false);
      expect(parsed.data.permisos.puedeLote).toBe(false);
      expect(parsed.data.permisos.readDrawer).toBe(false);
      expect(parsed.data.permisos.writeDrawer).toBe(false);
    }
  });
});
