import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { describe, it, expect, vi } from 'vitest';
import { ProgramaGeneralPage } from './ProgramaGeneralPage';

const mockActividadesRaw = [
  {
    unique_id: 1,
    Consecutivo_en_Programa: 'CAP-01',
    Actividad: '1. Cimentación',
    Titulo: 1,
    esCapitulo: true,
    capituloNombre: '1. Cimentación',
    avanceRealPct: 30,
    avanceTeoricoPct: 40,
    deltaPct: -10,
    deltaTexto: '-10%',
    esRutaCritica: false,
    plazoVencido: false,
    diasVencimiento: 0,
    alerta_crisis: 0,
  },
  {
    unique_id: 101,
    Consecutivo_en_Programa: 'EST-01',
    codigo_actividad: 'EST-01',
    Actividad: 'Excavación mecánica de zapatas',
    Titulo: 0,
    Fecha_Inicio: '2026-08-10',
    Fecha_Fin: '2026-08-20',
    Ruta_Critica: 1,
    Ejecutado: 0.25,
    Estado: 'Atrasada',
    cantidad_ppto: 450.0,
    unidad: 'm³',
    esCapitulo: false,
    capituloNombre: '1. Cimentación',
    avanceRealPct: 25.0,
    avanceTeoricoPct: 50.0,
    deltaPct: -25.0,
    deltaTexto: '-25%',
    esRutaCritica: true,
    plazoVencido: true,
    diasVencimiento: 3,
    alerta_crisis: 0,
  },
];

const mockContexto = {
  proyecto: { id: 1, nombre: 'Proyecto Prueba', codigo: 'PRU' },
  semana: { numero: 34, confirmada: false, esPasada: false },
  permisos: { puedeVer: true, puedeEditar: true, puedeCorteXlsx: true, puedeLote: true, readDrawer: true, writeDrawer: true },
  enlaces: { bi: '/bi/programa-general?project_id=1&semana=34' },
  catalogos: {
    unidades: ['m³', '%'],
    codigos: ['EST-01'],
    profesionales: [{ id: 1, nombre: 'Ing. Carlos Restrepo' }],
    subcontratistas: [{ id: 1, nombre: 'Excavaciones del Norte S.A.S.' }],
  },
  csrf_token: 'csrf123',
};

const mockGuardar = vi.fn().mockResolvedValue({ ok: true });
const mockCorteXlsx = vi.fn().mockResolvedValue({ ok: true, url: 'http://localhost/corte.xlsx' });
const mockActualizarEjecucion = vi.fn().mockResolvedValue({ respuesta: 'BIEN' });
const mockObtenerActividades = vi.fn().mockResolvedValue(mockActividadesRaw);

vi.mock('./api/programaGeneralApi', () => ({
  programaGeneralApi: () => ({
    obtenerContexto: vi.fn().mockResolvedValue(mockContexto),
    obtenerActividades: mockObtenerActividades,
    guardarActividad: mockGuardar,
    generarCorteXlsx: mockCorteXlsx,
    actualizarEjecucion: mockActualizarEjecucion,
  }),
}));

describe('ProgramaGeneralPage', () => {
  it('monta la página mostrando el título, semana y grilla de actividades', async () => {
    render(<ProgramaGeneralPage />);

    expect(await screen.findByText('Programa General')).toBeInTheDocument();
    expect(screen.getByText('Semana 34 Vigente')).toBeInTheDocument();
    expect(screen.getAllByText('Excavación mecánica de zapatas')[0]).toBeInTheDocument();
    expect(screen.getAllByText('1. Cimentación')[0]).toBeInTheDocument();
  });

  it('abre el Drawer Contextual LPS al hacer clic en una fila de actividad', async () => {
    const { container } = render(<ProgramaGeneralPage />);

    expect(await screen.findByText('Programa General')).toBeInTheDocument();

    const actividadFila = (await screen.findAllByText(/Excavación mecánica de zapatas/i))[0];
    fireEvent.click(actividadFila);

    expect(await screen.findByRole('dialog', { name: /Editor Contextual LPS/i })).toBeInTheDocument();
    expect(screen.getByText('Plazos y Cronograma')).toBeInTheDocument();
    expect(screen.getByText('Responsables & Asignaciones')).toBeInTheDocument();
  });

  it('alterna entre 8 columnas esenciales y 13 columnas completas', async () => {
    render(<ProgramaGeneralPage />);

    expect(await screen.findByText('Programa General')).toBeInTheDocument();
    const btn13 = screen.getByRole('button', { name: /13 Cols Reales/i });
    fireEvent.click(btn13);

    expect(screen.getByRole('columnheader', { name: 'LIB. RESTRICCIONES' })).toBeInTheDocument();
    expect(screen.getByRole('columnheader', { name: 'SEM. INICIO' })).toBeInTheDocument();

    const btn8 = screen.getByRole('button', { name: /8 Cols Esenciales/i });
    fireEvent.click(btn8);

    expect(screen.queryByRole('columnheader', { name: 'LIB. RESTRICCIONES' })).not.toBeInTheDocument();
  });

  it('filtra actividades por texto en la barra de búsqueda', async () => {
    render(<ProgramaGeneralPage />);

    expect(await screen.findByText('Programa General')).toBeInTheDocument();
    const inputBusqueda = screen.getByPlaceholderText(/Buscar actividad, código o responsable/i);

    fireEvent.change(inputBusqueda, { target: { value: 'Inexistente' } });
    expect(screen.queryByText('Excavación mecánica de zapatas')).not.toBeInTheDocument();

    fireEvent.change(inputBusqueda, { target: { value: 'Excavación' } });
    expect((await screen.findAllByText(/Excavación mecánica de zapatas/i)).length).toBeGreaterThan(0);
  });

  it('guarda cambios desde el drawer y muestra notificación toast', async () => {
    render(<ProgramaGeneralPage />);

    expect(await screen.findByText('Programa General')).toBeInTheDocument();
    const actividadFila = (await screen.findAllByText(/Excavación mecánica de zapatas/i))[0];
    fireEvent.click(actividadFila);

    const btnGuardar = await screen.findByRole('button', { name: /Guardar Cambios/i });
    fireEvent.click(btnGuardar);

    await waitFor(() => {
      expect(mockGuardar).toHaveBeenCalled();
      expect(screen.getByText('Cambios guardados con éxito')).toBeInTheDocument();
    });
  });

  it('abre la leyenda y ejecuta la actualización autorizada con el contexto actual', async () => {
    render(<ProgramaGeneralPage />);

    await screen.findByText('Programa General');
    fireEvent.click(screen.getByRole('button', { name: 'Leyenda' }));
    expect(screen.getByRole('dialog', { name: /Guía Operativa/i })).toBeInTheDocument();
    expect(screen.getByText(/Actividad Futura:/)).toBeInTheDocument();
    expect(screen.getByText(/Sin Datos:/)).toBeInTheDocument();
    expect(screen.getByText(/R0-R1-R2\/3-R4\/6/)).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Cerrar leyenda' })).toHaveFocus();
    fireEvent.keyDown(document, { key: 'Escape' });
    expect(screen.queryByRole('dialog', { name: /Guía Operativa/i })).not.toBeInTheDocument();

    mockActualizarEjecucion.mockResolvedValueOnce({ respuesta: 'BIEN', actualizadas: 3, carryover_actualizadas: 2 });
    fireEvent.click(screen.getByRole('button', { name: 'Actualizar Ejecución' }));
    await waitFor(() => {
      expect(mockActualizarEjecucion).toHaveBeenCalledWith({
        semana: 34,
        db: 'PRU',
        csrf_token: 'csrf123',
      });
      expect(screen.getByText(/3 filas y 2 carryovers/)).toBeInTheDocument();
    });
  });

  it('conserva la tabla y filtros durante una recarga fallida y ofrece reintentar', async () => {
    render(<ProgramaGeneralPage />);
    await screen.findByText('Programa General');
    const search = screen.getByPlaceholderText(/Buscar actividad, código o responsable/i);
    fireEvent.change(search, { target: { value: 'Excavación' } });
    mockObtenerActividades.mockRejectedValueOnce(new Error('red caída'));
    fireEvent.click(screen.getByRole('button', { name: 'Recargar' }));
    await screen.findByText(/datos desactualizados/i);
    expect(search).toHaveValue('Excavación');
    expect(screen.getAllByText('Excavación mecánica de zapatas').length).toBeGreaterThan(0);
    fireEvent.click(screen.getByRole('button', { name: 'Reintentar' }));
    await waitFor(() => expect(screen.queryByText(/datos desactualizados/i)).not.toBeInTheDocument());
  });

  it('confirma un borrador del Drawer antes de actualizar ejecución', async () => {
    mockActualizarEjecucion.mockClear();
    const confirmar = vi.spyOn(window, 'confirm').mockReturnValue(false);
    render(<ProgramaGeneralPage />);
    fireEvent.click((await screen.findAllByText('Excavación mecánica de zapatas'))[0]);
    fireEvent.change(screen.getByLabelText('Unidad', { exact: true }), { target: { value: '%' } });
    fireEvent.click(screen.getByRole('button', { name: 'Actualizar Ejecución' }));
    expect(confirmar).toHaveBeenCalled();
    expect(mockActualizarEjecucion).not.toHaveBeenCalled();
    confirmar.mockReturnValue(true);
    fireEvent.click(screen.getByRole('button', { name: 'Actualizar Ejecución' }));
    await waitFor(() => expect(mockActualizarEjecucion).toHaveBeenCalledTimes(1));
    confirmar.mockRestore();
  });
});
