import { expect, test } from 'vitest';
import { navegacionSelectorProyectos } from './NavegacionSelectorProyectos';

test('con navigation nulo (fetch en vuelo), arma solo "Tus proyectos"', () => {
  const grupos = navegacionSelectorProyectos(null);

  expect(grupos).toHaveLength(1);
  expect(grupos[0].items).toEqual([{ id: 'projects', label: 'Tus proyectos', href: '/proyectos' }]);
});

test('con bi.visible=false, tampoco agrega el grupo de BI', () => {
  const grupos = navegacionSelectorProyectos({ bi: { visible: false, href: null } });

  expect(grupos[0].items).toHaveLength(1);
});

test('con bi.visible=true, agrega "Control Tower - Informes" con el href autorizado', () => {
  const grupos = navegacionSelectorProyectos({ bi: { visible: true, href: '/bi/control-tower' } });

  expect(grupos[0].items).toEqual([
    { id: 'projects', label: 'Tus proyectos', href: '/proyectos' },
    { id: 'bi', label: 'Control Tower - Informes', href: '/bi/control-tower' },
  ]);
});
