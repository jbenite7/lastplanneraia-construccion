import { render, screen, fireEvent, act } from '@testing-library/react';
import { describe, it, expect, vi } from 'vitest';
import { ProgramaDrawer } from './ProgramaDrawer';
import { ActividadUI } from '../domain/modelo';

describe('ProgramaDrawer Contextual LPS', () => {
  const mockAct: ActividadUI = {
    unique_id: 101,
    Consecutivo_en_Programa: 'EST-01',
    codigo_actividad: 'EST-01',
    Actividad: 'Excavación mecánica de zapatas eje A-C',
    Titulo: 0,
    Fecha_Inicio: '2026-08-10',
    Fecha_Fin: '2026-08-20',
    Ruta_Critica: 1,
    Ejecutado: 0.25,
    Estado: 'Atrasada',
    cantidad_ppto: 450.0,
    unidad: 'm³',
    Responsable_AIA: 'Ing. Carlos Restrepo',
    Sub_Contratista: 'Excavaciones del Norte S.A.S.',
    Observaciones: 'Lluvia suspendió labores el 18/08.',
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
  };

  const catalogos = {
    unidades: ['m³', 'ton', 'm²', '%'],
    codigos: ['EST-01', 'EST-02'],
    profesionales: [
      { id: 1, nombre: 'Ing. Carlos Restrepo' },
      { id: 2, nombre: 'Ing. María Gómez' },
    ],
    subcontratistas: [
      { id: 1, nombre: 'Excavaciones del Norte S.A.S.' },
      { id: 2, nombre: 'Aceros de Colombia' },
    ],
  };

  it('muestra secciones de plazos, asignaciones opcionales, presupuesto y avance con delta', () => {
    render(
      <ProgramaDrawer
        actividad={mockAct}
        catalogos={catalogos}
        indiceActual={1}
        totalActividades={10}
        onCerrar={vi.fn()}
        onGuardar={vi.fn()}
        onNavigateSeq={vi.fn()}
      />
    );

    expect(screen.getByText('Plazos y Cronograma')).toBeInTheDocument();
    expect(screen.getByText('Responsables & Asignaciones')).toBeInTheDocument();
    expect(screen.getByText(/Opcional en S05/i)).toBeInTheDocument();
    expect(screen.getByText(/Desviación Física/i)).toBeInTheDocument();
    expect(screen.getByText('Excavaciones del Norte S.A.S.')).toBeInTheDocument();
    expect(screen.getByDisplayValue('Excavaciones del Norte S.A.S.')).toBeInTheDocument();
    expect(screen.getByDisplayValue('Ing. Carlos Restrepo')).toBeInTheDocument();
    expect(screen.getByText(/Plazo vencido hace 3 días/i)).toBeInTheDocument();
  });

  it('permite navegación secuencial con botones', () => {
    const onNav = vi.fn();
    render(
      <ProgramaDrawer
        actividad={mockAct}
        catalogos={catalogos}
        indiceActual={1}
        totalActividades={10}
        onCerrar={vi.fn()}
        onGuardar={vi.fn()}
        onNavigateSeq={onNav}
      />
    );

    fireEvent.click(screen.getByTitle(/Actividad Siguiente/i));
    expect(onNav).toHaveBeenCalledWith(1);

    fireEvent.click(screen.getByTitle(/Actividad Anterior/i));
    expect(onNav).toHaveBeenCalledWith(-1);
  });

  it('muestra solo los recursos de liberación con dato real, con etiqueta y estado del catálogo', () => {
    const restricciones = {
      area: 'Construccion',
      restrictions: [
        { key: 'D_y_E', label: 'D y E', hard: true, thresholdPercent: 100, options: ['0%', '100%', 'N/A'] },
        { key: 'Materiales', label: 'Materiales', hard: true, thresholdPercent: 100, options: ['0%', '100%', 'N/A'] },
        { key: 'MdeO', label: 'M de O', hard: true, thresholdPercent: 100, options: ['0%', '100%', 'N/A'] },
        { key: 'Equipos', label: 'Equipos', hard: true, thresholdPercent: 100, options: ['0%', '100%', 'N/A'] },
      ],
      hardRestrictions: ['D_y_E', 'Materiales', 'MdeO', 'Equipos'],
      softRestrictions: [],
    };
    render(
      <ProgramaDrawer
        actividad={{ ...mockAct, D_y_E: '100%', Materiales: '0%', MdeO: null, Equipos: undefined }}
        catalogos={catalogos}
        restricciones={restricciones}
        indiceActual={1}
        totalActividades={10}
        onCerrar={vi.fn()}
        onGuardar={vi.fn()}
        onNavigateSeq={vi.fn()}
      />
    );

    const lista = screen.getByRole('list', { name: /Recursos de liberación/i });
    const items = Array.from(lista.querySelectorAll('li')).map((li) => li.textContent);
    expect(items).toHaveLength(2);
    expect(items[0]).toMatch(/D y E.*100%.*Liberada/);
    expect(items[1]).toMatch(/Materiales.*0%.*Pendiente/);
    // Recursos sin fuente en la fila no se muestran, ni los inventados de antes.
    expect(screen.queryByText('M de O')).toBeNull();
    expect(screen.queryByText('Equipos')).toBeNull();
    expect(screen.queryByText('Seguridad')).toBeNull();
    expect(screen.queryByText('Externos')).toBeNull();
    expect(screen.queryByText(/Liberado$/)).toBeNull();
  });

  it('sin dato de restricciones lo dice en vez de afirmar recursos liberados', () => {
    render(
      <ProgramaDrawer
        actividad={mockAct}
        catalogos={catalogos}
        indiceActual={1}
        totalActividades={10}
        onCerrar={vi.fn()}
        onGuardar={vi.fn()}
        onNavigateSeq={vi.fn()}
      />
    );
    expect(screen.queryByRole('list', { name: /Recursos de liberación/i })).toBeNull();
    expect(screen.getByText(/Sin restricciones registradas/i)).toBeInTheDocument();
    expect(screen.queryByText('Mano de Obra')).toBeNull();
  });

  it('no ofrece escribir observaciones: el guardado no las lleva y el legado nunca las editó', () => {
    const onGuardar = vi.fn();
    render(
      <ProgramaDrawer
        actividad={mockAct}
        catalogos={catalogos}
        indiceActual={1}
        totalActividades={10}
        onCerrar={vi.fn()}
        onGuardar={onGuardar}
        onNavigateSeq={vi.fn()}
      />
    );

    expect(screen.queryByPlaceholderText(/nueva observación/i)).toBeNull();
    expect(screen.queryByLabelText(/Nueva Observación/i)).toBeNull();
    expect(document.querySelector('textarea')).toBeNull();
    // La observación registrada en la fila se ve, en solo lectura.
    expect(screen.getByText('Lluvia suspendió labores el 18/08.')).toBeInTheDocument();

    fireEvent.click(screen.getByRole('button', { name: /Guardar Cambios/i }));
    expect(onGuardar).toHaveBeenCalledTimes(1);
    expect(onGuardar.mock.calls[0][0]).not.toHaveProperty('Observaciones');
  });

  it('deshabilita Cantidad PPTO cuando la unidad seleccionada es %', () => {
    render(
      <ProgramaDrawer
        actividad={{ ...mockAct, unidad: '%' }}
        catalogos={catalogos}
        indiceActual={1}
        totalActividades={10}
        onCerrar={vi.fn()}
        onGuardar={vi.fn()}
        onNavigateSeq={vi.fn()}
      />
    );

    const inputPpto = screen.getByLabelText(/Cantidad PPTO/i);
    expect(inputPpto).toBeDisabled();
  });

  it('deja los campos sin edición y oculta el guardado para un rol de solo lectura', () => {
    render(
      <ProgramaDrawer
        actividad={mockAct}
        catalogos={catalogos}
        indiceActual={1}
        totalActividades={10}
        onCerrar={vi.fn()}
        onGuardar={vi.fn()}
        onNavigateSeq={vi.fn()}
        puedeEditar={false}
      />
    );

    expect(screen.getByLabelText(/Unidad/i)).toBeDisabled();
    expect(screen.getByLabelText(/Avance Real/i)).toBeDisabled();
    expect(screen.queryByRole('button', { name: /Guardar Cambios/i })).not.toBeInTheDocument();
  });

  it('reacciona a atajos de teclado [, ] para navegación secuencial fuera de inputs', () => {
    const onNav = vi.fn();
    render(
      <ProgramaDrawer
        actividad={mockAct}
        catalogos={catalogos}
        indiceActual={2}
        totalActividades={10}
        onCerrar={vi.fn()}
        onGuardar={vi.fn()}
        onNavigateSeq={onNav}
      />
    );

    fireEvent.keyDown(window, { key: ']' });
    expect(onNav).toHaveBeenCalledWith(1);

    fireEvent.keyDown(window, { key: '[' });
    expect(onNav).toHaveBeenCalledWith(-1);
  });

  it('ignora atajos [ y ] cuando el foco está dentro de un campo de texto', () => {
    const onNav = vi.fn();
    render(
      <ProgramaDrawer
        actividad={mockAct}
        catalogos={catalogos}
        indiceActual={2}
        totalActividades={10}
        onCerrar={vi.fn()}
        onGuardar={vi.fn()}
        onNavigateSeq={onNav}
      />
    );

    const campo = screen.getByLabelText(/Avance Real/i);
    fireEvent.keyDown(campo, { key: ']' });
    expect(onNav).not.toHaveBeenCalled();
  });

  it('cierra el drawer al presionar Escape o botón Descartar', () => {
    const onCerrar = vi.fn();
    render(
      <ProgramaDrawer
        actividad={mockAct}
        catalogos={catalogos}
        indiceActual={1}
        totalActividades={10}
        onCerrar={onCerrar}
        onGuardar={vi.fn()}
        onNavigateSeq={vi.fn()}
      />
    );

    fireEvent.keyDown(window, { key: 'Escape' });
    expect(onCerrar).toHaveBeenCalledTimes(1);

    fireEvent.click(screen.getByRole('button', { name: /Descartar/i }));
    expect(onCerrar).toHaveBeenCalledTimes(2);
  });

  it('dispara onGuardar con los datos actualizados al hacer clic en Guardar Cambios o presionar ⌘S', () => {
    const onGuardar = vi.fn();
    render(
      <ProgramaDrawer
        actividad={mockAct}
        catalogos={catalogos}
        indiceActual={1}
        totalActividades={10}
        onCerrar={vi.fn()}
        onGuardar={onGuardar}
        onNavigateSeq={vi.fn()}
      />
    );

    // Cambiar avance real
    const inputReal = screen.getByLabelText(/Avance Real/i);
    fireEvent.change(inputReal, { target: { value: '35' } });

    // Guardar con botón
    fireEvent.click(screen.getByRole('button', { name: /Guardar Cambios/i }));

    expect(onGuardar).toHaveBeenCalledTimes(1);
    expect(onGuardar).toHaveBeenCalledWith(
      expect.objectContaining({
        unique_id: 101,
        Fecha_Inicio: '2026-08-10',
        Fecha_Fin: '2026-08-20',
        unidad: 'm³',
        cantidad_ppto: 450,
        Ejecutado: 35,
        EjecutadoRatio: 0.35,
        codigo_actividad: 'EST-01',
        Responsable_AIA: 'Ing. Carlos Restrepo',
        Sub_Contratista: 'Excavaciones del Norte S.A.S.',
      })
    );

    // Guardar con atajo ⌘S
    fireEvent.keyDown(window, { key: 's', metaKey: true });
    expect(onGuardar).toHaveBeenCalledTimes(2);
  });

  it('renderiza titulo sanitizado sin etiquetas <b> ni <small>, mostrando subtitulo y codigo', () => {
    const actConHtml: ActividadUI = {
      ...mockAct,
      Actividad: '<b>VACIADO DE CONCRETO ZAPATAS, </b> <small>[Capítulo: 1. CIMENTACIÓN, EDIFICIO A]</small>',
      codigo_actividad: 'EST-101',
    };

    render(
      <ProgramaDrawer
        actividad={actConHtml}
        catalogos={catalogos}
        indiceActual={1}
        totalActividades={10}
        onCerrar={vi.fn()}
        onGuardar={vi.fn()}
        onNavigateSeq={vi.fn()}
      />
    );

    // No debe contener tags HTML crudos
    expect(screen.queryByText(/<b>/)).toBeNull();
    expect(screen.queryByText(/<small>/)).toBeNull();

    // Debe renderizar el título limpio dentro de drawer-act-title
    const tituloEl = screen.getByText('VACIADO DE CONCRETO ZAPATAS');
    expect(tituloEl).toBeInTheDocument();
    expect(tituloEl.className).toContain('drawer-act-title');

    // Debe renderizar el subtítulo limpio dentro de drawer-act-subtitle
    const subtituloEl = screen.getByText('1. CIMENTACIÓN, EDIFICIO A');
    expect(subtituloEl).toBeInTheDocument();
    expect(subtituloEl.className).toContain('drawer-act-subtitle');

    // Debe renderizar el código de la actividad
    expect(screen.getByText('EST-101')).toBeInTheDocument();
  });

  it('muestra fechas formateadas de obra y alerta visual de plazo vencido en cronograma', () => {
    render(
      <ProgramaDrawer
        actividad={mockAct}
        catalogos={catalogos}
        indiceActual={1}
        totalActividades={10}
        onCerrar={vi.fn()}
        onGuardar={vi.fn()}
        onNavigateSeq={vi.fn()}
      />
    );

    // Debe renderizar las fechas formateadas en formato obra DD/MM/AAAA
    expect(screen.getByText(/10\/08\/2026/)).toBeInTheDocument();
    expect(screen.getByText(/20\/08\/2026/)).toBeInTheDocument();

    // Alerta de plazo vencido con clase semántica
    const alertaVencido = screen.getByText(/Plazo vencido hace 3 días/i);
    expect(alertaVencido).toBeInTheDocument();
    expect(alertaVencido.className).toContain('date-overdue');
  });

  it('aplica clases delta-neg y delta-ok segun desviacion en micro-medidor', () => {
    // Actividad retrasada (delta negativo)
    const { rerender, container } = render(
      <ProgramaDrawer
        actividad={mockAct}
        catalogos={catalogos}
        indiceActual={1}
        totalActividades={10}
        onCerrar={vi.fn()}
        onGuardar={vi.fn()}
        onNavigateSeq={vi.fn()}
      />
    );

    const deltaNeg = container.querySelector('.gauge-delta-val')!;
    expect(deltaNeg).not.toBeNull();
    expect(deltaNeg.className).toContain('delta-neg');

    // Actividad al día (delta positivo o cero)
    const actAlDia: ActividadUI = {
      ...mockAct,
      avanceRealPct: 50.0,
      avanceTeoricoPct: 50.0,
      deltaPct: 0.0,
      Ejecutado: 0.5,
    };

    rerender(
      <ProgramaDrawer
        actividad={actAlDia}
        catalogos={catalogos}
        indiceActual={1}
        totalActividades={10}
        onCerrar={vi.fn()}
        onGuardar={vi.fn()}
        onNavigateSeq={vi.fn()}
      />
    );

    const deltaOk = container.querySelector('.gauge-delta-val')!;
    expect(deltaOk).not.toBeNull();
    expect(deltaOk.className).toContain('delta-ok');
  });

  describe('SOS real', () => {
    it('llama al servidor y no cambia el rótulo por su cuenta: el estado llega con la fila recargada', async () => {
      let resolver: ((m: string) => void) | undefined;
      const onDeclararSos = vi.fn(() => new Promise<string>((r) => { resolver = r; }));
      render(
        <ProgramaDrawer
          actividad={mockAct}
          catalogos={catalogos}
          indiceActual={1}
          totalActividades={10}
          onCerrar={vi.fn()}
          onGuardar={vi.fn()}
          onNavigateSeq={vi.fn()}
          puedeDeclararSos
          onDeclararSos={onDeclararSos}
        />
      );

      const boton = screen.getByRole('button', { name: /Declarar Crisis SOS/i });
      fireEvent.click(boton);
      expect(onDeclararSos).toHaveBeenCalledWith(101);
      // Mientras vuela: deshabilitado y sin rótulo de alerta activa inventado.
      expect(screen.getByRole('button', { name: /Registrando/i })).toBeDisabled();
      expect(screen.queryByText(/Alerta SOS LPS Activa/i)).toBeNull();

      await act(async () => { resolver?.('Alerta registrada'); });
      expect(screen.getByRole('status')).toHaveTextContent('Alerta registrada');
      // El prop no cambió (el servidor aún no devolvió la fila recargada): sigue sin declararse activa.
      expect(screen.queryByText(/Alerta SOS LPS Activa/i)).toBeNull();
    });

    it('muestra el error del servidor y no declara nada', async () => {
      const onDeclararSos = vi.fn().mockRejectedValue(new Error('Token de seguridad inválido.'));
      render(
        <ProgramaDrawer
          actividad={mockAct}
          catalogos={catalogos}
          indiceActual={1}
          totalActividades={10}
          onCerrar={vi.fn()}
          onGuardar={vi.fn()}
          onNavigateSeq={vi.fn()}
          puedeDeclararSos
          onDeclararSos={onDeclararSos}
        />
      );
      fireEvent.click(screen.getByRole('button', { name: /Declarar Crisis SOS/i }));
      expect(await screen.findByRole('alert')).toHaveTextContent('Token de seguridad inválido.');
      expect(screen.getByRole('button', { name: /Declarar Crisis SOS/i })).toBeEnabled();
    });

    it('sin permiso de escritura del cajón LPS el botón queda deshabilitado', () => {
      render(
        <ProgramaDrawer
          actividad={mockAct}
          catalogos={catalogos}
          indiceActual={1}
          totalActividades={10}
          onCerrar={vi.fn()}
          onGuardar={vi.fn()}
          onNavigateSeq={vi.fn()}
          puedeDeclararSos={false}
          onDeclararSos={vi.fn()}
        />
      );
      expect(screen.getByRole('button', { name: /Declarar Crisis SOS/i })).toBeDisabled();
    });

    it('con la alerta ya activa en la fila lo dice y no ofrece volver a declararla', () => {
      render(
        <ProgramaDrawer
          actividad={{ ...mockAct, alerta_crisis: 1 }}
          catalogos={catalogos}
          indiceActual={1}
          totalActividades={10}
          onCerrar={vi.fn()}
          onGuardar={vi.fn()}
          onNavigateSeq={vi.fn()}
          puedeDeclararSos
          onDeclararSos={vi.fn()}
        />
      );
      expect(screen.getByRole('button', { name: /Alerta SOS LPS Activa/i })).toBeDisabled();
    });
  });

  describe('descarte con cambios sin guardar', () => {
    const montar = (onCerrar = vi.fn()) => {
      const utils = render(
        <ProgramaDrawer
          actividad={mockAct}
          catalogos={catalogos}
          indiceActual={1}
          totalActividades={10}
          onCerrar={onCerrar}
          onGuardar={vi.fn()}
          onNavigateSeq={vi.fn()}
        />
      );
      return { ...utils, onCerrar };
    };

    it('Esc con cambios pide confirmación y no cierra si se cancela', () => {
      const confirmar = vi.spyOn(window, 'confirm').mockReturnValue(false);
      const { onCerrar } = montar();
      fireEvent.change(screen.getByLabelText(/Avance Real/i), { target: { value: '40' } });
      fireEvent.keyDown(window, { key: 'Escape' });
      expect(confirmar).toHaveBeenCalledTimes(1);
      expect(onCerrar).not.toHaveBeenCalled();
      confirmar.mockReturnValue(true);
      fireEvent.keyDown(window, { key: 'Escape' });
      expect(onCerrar).toHaveBeenCalledTimes(1);
      confirmar.mockRestore();
    });

    it('el clic en el velo con cambios pide confirmación', () => {
      const confirmar = vi.spyOn(window, 'confirm').mockReturnValue(false);
      const { onCerrar, container } = montar();
      fireEvent.change(screen.getByLabelText(/Avance Real/i), { target: { value: '40' } });
      fireEvent.click(container.querySelector('.drawer-backdrop')!);
      expect(confirmar).toHaveBeenCalledTimes(1);
      expect(onCerrar).not.toHaveBeenCalled();
      confirmar.mockRestore();
    });

    it('sin cambios, Esc y el velo cierran directo sin preguntar', () => {
      const confirmar = vi.spyOn(window, 'confirm');
      const { onCerrar, container } = montar();
      fireEvent.keyDown(window, { key: 'Escape' });
      fireEvent.click(container.querySelector('.drawer-backdrop')!);
      expect(confirmar).not.toHaveBeenCalled();
      expect(onCerrar).toHaveBeenCalledTimes(2);
      confirmar.mockRestore();
    });
  });
});
