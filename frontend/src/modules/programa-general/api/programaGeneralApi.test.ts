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

  it('conserva la URL BI autorizada y llama la actualización masiva con CSRF', async () => {
    const parsed = esquemaContextoPg.safeParse({
      project: { id: 1, name: 'Proyecto Prueba', dbPrefix: 'prueba' },
      week: { number: 33, max: 33, confirmed: 0 },
      actions: { runBatch: true },
      csrf: { programaGeneral: 'token123' },
      links: { bi: '/bi/programa-general?project_id=1&semana=33' },
      catalogos: {},
    });
    expect(parsed.success).toBe(true);
    if (parsed.success) {
      expect(parsed.data.enlaces.bi).toBe('/bi/programa-general?project_id=1&semana=33');
    }

    const mockCliente = { get: vi.fn(), postForm: vi.fn().mockResolvedValue({ respuesta: 'BIEN' }) };
    const api = programaGeneralApi(mockCliente as any);
    await api.actualizarEjecucion({ semana: 33, db: 'prueba', csrf_token: 'token123' });
    expect(mockCliente.postForm).toHaveBeenCalledWith(
      '/api/general/update-batch?db=prueba&semana=33',
      { csrf_token: 'token123' },
      esquemaRespuestaUpdatePg,
      { 'X-CSRF-Token': 'token123' }
    );
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

  it('conserva el token CSRF del cajón LPS (clave lps_drawer), distinto del de guardado', () => {
    const parsed = esquemaContextoPg.safeParse({
      project: { id: 1, name: 'Proyecto Prueba', dbPrefix: 'prueba' },
      week: { number: 33, max: 33, confirmed: 0 },
      actions: { writeDrawer: true },
      csrf: { programaGeneral: 'token-pg', drawer: 'token-drawer', shell: 'token-shell' },
      catalogos: {},
    });
    expect(parsed.success).toBe(true);
    if (parsed.success) {
      expect(parsed.data.csrf_token).toBe('token-pg');
      expect(parsed.data.csrf_drawer).toBe('token-drawer');
      expect(parsed.data.permisos.writeDrawer).toBe(true);
    }
  });

  it('declara SOS contra /api/lps/crisis/register con modulo PG, consecutivo = unique_id y el token del cajón', async () => {
    const fetchFalso = vi.fn().mockResolvedValue(new Response(JSON.stringify({
      respuesta: 'OK',
      ok: true,
      mensaje: 'Alerta registrada',
      data: { alertId: 9, wasActive: false },
      target: { kind: 'activity', activityId: 101, module: 'PG', week: 33 },
      meta: { requestId: 'x' },
    }), { status: 200 }));
    vi.stubGlobal('fetch', fetchFalso);
    try {
      const api = programaGeneralApi({ get: vi.fn(), postForm: vi.fn() } as any);
      const res = await api.declararSos({ unique_id: 101, semana: 33, csrfToken: 'token-drawer' });
      expect(res.data).toEqual({ alertId: 9, wasActive: false });
      const [ruta, opciones] = fetchFalso.mock.calls[0];
      expect(String(ruta)).toBe('/api/lps/crisis/register');
      const cuerpo = opciones.body as URLSearchParams;
      expect(cuerpo.get('modulo')).toBe('PG');
      expect(cuerpo.get('consecutivo')).toBe('101');
      expect(cuerpo.get('semana')).toBe('33');
      expect(cuerpo.get('trigger')).toBe('MANUAL');
      expect(cuerpo.get('_csrf_token')).toBe('token-drawer');
    } finally {
      vi.unstubAllGlobals();
    }
  });

  it('conserva en la fila los campos de restricción y en el contexto el catálogo de restricciones', () => {
    const fila = esquemaFilaActividadPg.parse({
      unique_id: 7, Actividad: 'X', Titulo: 0,
      D_y_E: '100%', Materiales: 0.5, MdeO: null, Equipos: 'N/A',
      restriccion_pc_1: '50%',
    });
    expect(fila).toMatchObject({ D_y_E: '100%', Materiales: '0.5', MdeO: null, Equipos: 'N/A', restriccion_pc_1: '50%' });

    const parsed = esquemaContextoPg.safeParse({
      project: { id: 1, name: 'P', dbPrefix: 'p', area: 'Construccion' },
      week: { number: 33, max: 33, confirmed: 0 },
      actions: {},
      csrf: { programaGeneral: 't' },
      restrictionConfig: {
        area: 'Construccion',
        restrictions: [{ key: 'D_y_E', label: 'D y E', hard: true, thresholdPercent: 100, options: ['0%', '100%', 'N/A'] }],
        hardRestrictions: ['D_y_E'],
        softRestrictions: [],
      },
      catalogos: {},
    });
    expect(parsed.success).toBe(true);
    if (parsed.success) {
      expect(parsed.data.restricciones?.restrictions[0]).toEqual(
        { key: 'D_y_E', label: 'D y E', hard: true, thresholdPercent: 100, options: ['0%', '100%', 'N/A'] },
      );
    }
  });
});
