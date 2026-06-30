import { expect } from '@playwright/test';
import { BASE_URL, REPORTS } from '../fixtures/projects.mjs';
import {
  assertHeaders,
  assertNavbarForProject,
  assertProjectContext,
  assertRestrictionConfig,
  expectUsablePage,
} from './assertions.mjs';
import { getJson, postFormJson } from './session.mjs';

function stamp(project, suffix) {
  return `E2E ${project.key} ${suffix} ${Date.now()} ${Math.floor(Math.random() * 10000)}`;
}

function assertJsonOk(response, label) {
  expect(response.ok, `${label}: ${JSON.stringify(response.payload)}`).toBe(true);
  expect(response.payload.parseError, `${label}: ${JSON.stringify(response.payload)}`).toBeFalsy();
}

async function apiList(page, project, url, body = null) {
  const response = body == null
    ? await getJson(page, url)
    : await postFormJson(page, url, body);
  assertJsonOk(response, url);
  return response.payload;
}

async function smokeHandsontable(page, url) {
  await expectUsablePage(page, url, ['.handsontable', '.htCore', 'body']);
}

export const moduleFlows = {
  programaGeneral: {
    async smoke(page, project) {
      await smokeHandsontable(page, '/programa-general');
      await assertProjectContext(page, project);
      await assertRestrictionConfig(page, project);
      await apiList(page, project, `/api/general/list?db=${project.dbPrefix}&semana=${project.maxWeek}`);
    },
    async edit(page, project) {
      const payload = await apiList(page, project, `/api/general/list?db=${project.dbPrefix}&semana=${project.maxWeek}`);
      expect(payload.data || payload, 'Programa General response must contain data').toBeTruthy();
    },
    async validateTypeSpecificBehavior(page, project) {
      await assertRestrictionConfig(page, project);
    },
    async cleanup() {},
  },

  programacionIntermedia: {
    async smoke(page, project) {
      await smokeHandsontable(page, '/programacion-intermedia');
      await assertProjectContext(page, project);
      await apiList(page, project, '/api/pi/list');
    },
    async edit(page, project) {
      const preview = await postFormJson(page, '/programacion-intermedia/shared-constraints/preview', {
        semana: project.maxWeek,
      });
      assertJsonOk(preview, 'PI shared constraints preview');
    },
    async validateTypeSpecificBehavior(page, project) {
      await assertRestrictionConfig(page, project);
    },
    async cleanup() {},
  },

  programacionSemanal: {
    async smoke(page, project) {
      await smokeHandsontable(page, '/programacion-semanal');
      await assertProjectContext(page, project);
      await apiList(page, project, `/api/semanal/list?db=${project.dbPrefix}&semana=${project.maxWeek}`);
    },
    async edit(page, project) {
      const tnp = await getJson(page, `/api/semanal/tnp-actividades?semana=${project.maxWeek}`);
      assertJsonOk(tnp, 'PS TNP activities');
    },
    async validateTypeSpecificBehavior(page, project) {
      await assertRestrictionConfig(page, project);
    },
    async cleanup() {},
  },

  profesionales: {
    async smoke(page, project) {
      await expectUsablePage(page, '/profesionales', ['.handsontable', '#hot-container', 'body']);
      const payload = await apiList(page, project, `/api/profesionales/list?db=${project.dbPrefix}`, {});
      expect(payload.status, JSON.stringify(payload)).toBe('success');
    },
    async edit(page, project) {
      const name = stamp(project, 'Profesional');
      const email = `e2e-${project.key}-${Date.now()}@example.com`;
      const create = await postFormJson(page, `/api/profesionales/save?db=${project.dbPrefix}`, {
        opcion: 'crear',
        nombre: name,
        email,
        cargo: project.professionalCargo,
      });
      expect(create.payload.status, JSON.stringify(create.payload)).toBe('success');
      const id = create.payload.id;

      const updated = `${name} Updated`;
      const update = await postFormJson(page, `/api/profesionales/save?db=${project.dbPrefix}`, {
        opcion: 'guardar_cambios',
        cambios: [{ id, prop: 'nombre', value: updated }],
      });
      expect(update.payload.status, JSON.stringify(update.payload)).toBe('success');

      const list = await apiList(page, project, `/api/profesionales/list?db=${project.dbPrefix}`, {});
      expect(list.data.some((row) => Number(row.id) === Number(id) && row.nombre === updated)).toBe(true);

      const del = await postFormJson(page, `/api/profesionales/save?db=${project.dbPrefix}`, {
        opcion: 'eliminar',
        id,
      });
      expect(del.payload.status, JSON.stringify(del.payload)).toBe('success');
    },
    async validateTypeSpecificBehavior() {},
    async cleanup() {},
  },

  subcontratistas: {
    async smoke(page, project) {
      await expectUsablePage(page, '/subcontratistas', ['.handsontable .htCore', '#hot-container']);
      await expect(page.locator('.header-actions h4')).toContainText(project.expectedSubcontractorTitle);
      const payload = await apiList(page, project, `/api/subcontratistas/list?db=${project.dbPrefix}`, { opcion: 'listar' });
      expect(payload.status, JSON.stringify(payload)).toBe('success');
    },
    async edit(page, project) {
      const name = stamp(project, project.constructionOnly ? 'Subcontratista' : 'Interesado');
      const email = `e2e-${project.key}-${Date.now()}@example.com`;
      const nit = String(Date.now()).slice(-9);
      const create = await postFormJson(page, `/api/subcontratistas/save?db=${project.dbPrefix}`, {
        opcion: 'crear',
        subcontratista: name,
        correo_contacto: email,
        NIT: nit,
        alcance: `Scope ${project.area}`,
        tipo_proveedor: project.providerType,
      });
      expect(create.payload.status, JSON.stringify(create.payload)).toBe('success');
      const id = create.payload.id;

      const updated = `${name} Updated`;
      const update = await postFormJson(page, `/api/subcontratistas/save?db=${project.dbPrefix}`, {
        opcion: 'guardar_cambios',
        id,
        column: 'subcontratista',
        value: updated,
      });
      expect(update.payload.status, JSON.stringify(update.payload)).toBe('success');

      const list = await apiList(page, project, `/api/subcontratistas/list?db=${project.dbPrefix}`, { opcion: 'listar' });
      expect(list.data.some((row) => Number(row.Id) === Number(id) && row.subcontratista === updated)).toBe(true);

      const del = await postFormJson(page, `/api/subcontratistas/save?db=${project.dbPrefix}`, {
        opcion: 'eliminar',
        Id: id,
      });
      expect(del.payload.status, JSON.stringify(del.payload)).toBe('success');
    },
    async validateTypeSpecificBehavior(page, project) {
      await expect(page.locator('.header-actions h4')).toContainText(project.expectedSubcontractorTitle);
    },
    async cleanup() {},
  },

  indicadores: {
    async smoke(page, project) {
      await expectUsablePage(page, '/indicadores', ['body']);
      const response = await postFormJson(page, '/api/indicadores/generar', {
        db: project.dbPrefix,
        semana: project.maxWeek,
      });
      assertJsonOk(response, 'Indicadores generar');
    },
    async edit() {},
    async validateTypeSpecificBehavior() {},
    async cleanup() {},
  },

  controlCambios: {
    async smoke(page, project) {
      await expectUsablePage(page, '/control-cambios', ['.handsontable', '#dt_cliente', 'body']);
      const payload = await apiList(page, project, `/api/control-cambios/list?db=${project.dbPrefix}`, {});
      expect(payload.data, JSON.stringify(payload)).toBeDefined();
    },
    async edit(page, project) {
      const id = Number(String(Date.now()).slice(-9));
      const common = {
        inputConsecutivo: id,
        inputSolicitanteCambio: 1,
        inputDetalleSolicitanteOtro: '',
        inputFechaSolicitud: '2026-06-30',
        inputPrioridad: 2,
        inputTipoCambioAlcance: 1,
        inputTipoCambioCronograma: 0,
        inputTipoCambioCosto: 0,
        inputTipoCambioCalidad: 0,
        inputTipoCambioRiesgo: 0,
        inputTipoCambioRecurso: 0,
        inputResponsableSolucion: 1,
        inputDetalleResponsableSolucion: '',
        inputJustificacion: 'E2E cambio temporal',
        inputDescripcion: 'E2E control de cambios',
        inputIncidenciaAlcance: 'Sin impacto',
        inputTiempoCronograma: 0,
        inputTiempoCronogramaAfectado: 0,
        inputIncidenciaCronograma: 'Sin impacto',
        inputValorPresupuesto: 0,
        inputCostoDirecto: 0,
        inputCostoDirectoAIU: 0,
        inputCostoDirectoAIUIVA: 0,
        inputValorAprobado: 0,
        inputIncidenciaPresupuesto: 'Sin impacto',
        inputIncidenciaCalidad: 'Sin impacto',
        inputIncidenciaRiesgo: 'Sin impacto',
        inputIncidenciaRecurso: 'Sin impacto',
        inputFechaTentativaDefinicion: '2026-06-30',
        inputFechaEntregaInterventoria: '2026-06-30',
        inputFechaDefinicion: '2026-06-30',
        inputAprobacion: 0,
        soportes: '{"soportes":[]}',
      };

      const create = await postFormJson(page, `/api/control-cambios/save?db=${project.dbPrefix}`, {
        opcion: 'nuevo',
        ...common,
      });
      expect(create.payload.respuesta, JSON.stringify(create.payload)).toBe('BIEN');

      const update = await postFormJson(page, `/api/control-cambios/save?db=${project.dbPrefix}`, {
        opcion: 'modificar',
        ...common,
        inputDescripcion: 'E2E control de cambios actualizado',
      });
      expect(update.payload.respuesta, JSON.stringify(update.payload)).toBe('BIEN');

      const del = await postFormJson(page, `/api/control-cambios/save?db=${project.dbPrefix}`, {
        opcion: 'eliminar',
        Id: id,
      });
      expect(del.payload.respuesta, JSON.stringify(del.payload)).toBe('BIEN');
    },
    async validateTypeSpecificBehavior() {},
    async cleanup() {},
  },

  listadoActividades: {
    async smoke(page, project) {
      await expectUsablePage(page, '/listado-actividades', ['#dt_cliente', 'body']);
      const response = await postFormJson(page, '/api/listado-actividades/list', {});
      assertJsonOk(response, 'Listado actividades list');
    },
    async edit(page) {
      const preview = await postFormJson(page, '/api/listado-actividades/auto/preview', {});
      assertJsonOk(preview, 'Listado actividades semi-auto preview');
      expect(preview.payload.run_id, JSON.stringify(preview.payload)).toBeTruthy();
      expect(preview.payload.analysis?.steps?.length, JSON.stringify(preview.payload)).toBeGreaterThan(0);
    },
    async validateTypeSpecificBehavior(page, project) {
      if (!project.constructionOnly) throw new Error('ListadoActividades must not run for PC projects');
    },
    async cleanup() {},
  },

  contratos: {
    async smoke(page, project) {
      await expectUsablePage(page, '/contratos', ['#dt_cliente', 'body']);
      const response = await postFormJson(page, '/api/contratos/list', { semana: project.maxWeek });
      assertJsonOk(response, 'Contratos list');
    },
    async edit(page) {
      const preview = await postFormJson(page, '/api/contratos/auto/preview', {});
      assertJsonOk(preview, 'Contratos semi-auto preview');
      expect(preview.payload.run_id, JSON.stringify(preview.payload)).toBeTruthy();
      expect(preview.payload.analysis?.steps?.length, JSON.stringify(preview.payload)).toBeGreaterThan(0);
    },
    async validateTypeSpecificBehavior(page, project) {
      if (!project.constructionOnly) throw new Error('Contratos must not run for PC projects');
    },
    async cleanup() {},
  },

  pdc: {
    async smoke(page, project) {
      await expectUsablePage(page, '/pdc', ['#dt_cliente', 'body']);
      const response = await postFormJson(page, '/api/pdc/list', { semana: project.maxWeek });
      assertJsonOk(response, 'PDC list');
    },
    async edit(page) {
      const preview = await postFormJson(page, '/api/pdc/auto/preview', {});
      assertJsonOk(preview, 'PDC semi-auto preview');
      expect(preview.payload.run_id, JSON.stringify(preview.payload)).toBeTruthy();
      expect(preview.payload.analysis?.steps?.length, JSON.stringify(preview.payload)).toBeGreaterThan(0);
    },
    async validateTypeSpecificBehavior(page, project) {
      if (!project.constructionOnly) throw new Error('PDC must not run for PC projects');
    },
    async cleanup() {},
  },

  cnp: {
    async smoke(page, project) {
      await expectUsablePage(page, '/programacion-semanal/cnp', ['#dt_cliente', 'body']);
      const response = await postFormJson(page, '/api/cnp/list', { semana: project.maxWeek });
      assertJsonOk(response, 'CNP list');
    },
    async edit() {},
    async validateTypeSpecificBehavior() {},
    async cleanup() {},
  },

  cnc: {
    async smoke(page, project) {
      await expectUsablePage(page, '/programacion-semanal/cnc', ['#dt_cliente', 'body']);
      const response = await postFormJson(page, '/api/cnc/list', { semana: project.maxWeek });
      assertJsonOk(response, 'CNC list');
    },
    async edit(page) {
      const reasons = await postFormJson(page, '/api/cnc/reasons', { categoria: 'Programación' });
      assertJsonOk(reasons, 'CNC reasons');
    },
    async validateTypeSpecificBehavior() {},
    async cleanup() {},
  },

  cic: {
    async smoke(page, project) {
      await expectUsablePage(page, '/programacion-semanal/cic', ['#dt_cliente', 'body']);
      const response = await postFormJson(page, '/api/cic/list', { semana: project.maxWeek });
      assertJsonOk(response, 'CIC list');
    },
    async edit() {},
    async validateTypeSpecificBehavior() {},
    async cleanup() {},
  },

  reportesConstruccion: {
    async smoke(page, project) {
      for (const job of REPORTS.jsonJobs) {
        const response = await getJson(page, `/reportes/${job}?semana=${project.maxWeek}`);
        assertJsonOk(response, `Reporte JSON ${job}`);
      }
    },
    async edit(page, project) {
      for (const report of REPORTS.constructionDownloads) {
        const response = await getJson(page, `/reportes/${report.type}?db=${project.dbPrefix}&semana=${project.maxWeek}`);
        assertJsonOk(response, `Reporte descarga ${report.type}`);
        expect(response.payload.url, JSON.stringify(response.payload)).toBeTruthy();
        const file = await page.request.get(`${BASE_URL}${response.payload.url}`);
        expect(file.ok(), `${report.type} generated file`).toBe(true);
        const body = await file.body();
        expect(body.length, `${report.type} file should not be empty`).toBeGreaterThan(100);
      }
    },
    async validateTypeSpecificBehavior(page, project) {
      if (!project.constructionOnly) throw new Error('ReportesConstruccion must not run for PC projects');
    },
    async cleanup() {},
  },
};

export async function runModuleFlow(page, project, moduleName) {
  const flow = moduleFlows[moduleName];
  if (!flow) throw new Error(`Unknown module flow: ${moduleName}`);
  await flow.smoke(page, project);
  await flow.validateTypeSpecificBehavior(page, project);
  await flow.edit(page, project);
  await flow.cleanup(page, project);
}

export async function validateProjectShell(page, project) {
  await expectUsablePage(page, '/programa-general', ['.handsontable', 'body']);
  await assertProjectContext(page, project);
  await assertNavbarForProject(page, project);
  await assertRestrictionConfig(page, project);
}
