// Ancho real de la tabla de Handsontable en las vistas con `#hot-container`.
//
// POR QUE EXISTE: `public/css/handsontable-module.css:127-131` fuerza
// `table-layout: auto !important` sobre `#hot-container table` desde
// `layer(vendor)`, anulando el `table-layout: fixed` del propio vendor. Con
// `auto`, el navegador trata los `<col width>` del colgroup como sugerencias y
// colapsa la tabla al ancho de su contenido. El `width: 100% !important` de esa
// misma regla no lo compensa: el padre directo de la tabla es `.wtSpreader`,
// que Handsontable mantiene a `width: 0`, asi que el 100% resuelve a cero.
//
// El escape ya existe en el repo (`handsontable-module.css:134-138`): si
// `#hot-container` lleva la clase `hot-fixed-columns` y la variable
// `--hot-table-width`, vuelve `table-layout: fixed` y la tabla toma el ancho
// declarado. Cinco modulos lo aplican; este test vigila que ninguna vista con
// grilla se quede sin el.
//
// Un `!important` en la hoja del modulo NO sirve como arreglo: para
// declaraciones `!important` el orden de capas se invierte, asi que
// `layer(vendor)` gana a `layer(module)` y a lo no capado.
import { chromium } from 'playwright';
import { BASE_URL } from './fixtures/projects.mjs';
import { login, selectFirstProject } from './support/session.mjs';

const RUTAS = [
  '/programa-general',
  '/programacion-intermedia',
  '/profesionales',
  '/subcontratistas',
  '/programa-general-actualizar',
];

// El master incluye la columna de encabezados de fila, igual que el wtHider.
const TOLERANCIA_PX = 2;

const resultados = [];
const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1180, height: 820 } });

// Este test no busca un proyecto concreto: entra al primero que haya, sea cual sea.
await login(page);
await selectFirstProject(page);

for (const ruta of RUTAS) {
  await page.goto(`${BASE_URL}${ruta}`, { waitUntil: 'load' });
  await page.waitForTimeout(3000);

  const medida = await page.evaluate(() => {
    const tabla = document.querySelector('#hot-container .ht_master table.htCore');
    const hider = document.querySelector('#hot-container .ht_master .wtHider');
    if (!tabla || !hider) return null;

    // Alineacion del clon de encabezados de fila contra el master. Handsontable
    // escribe `height` inline en esos `th` contando con que no lleven relleno
    // vertical; si lo llevan y el modelo es content-box, cada fila del clon
    // crece y los numeros dejan de coincidir con sus filas.
    const tops = (sel) => [...document.querySelectorAll(sel)]
      .slice(0, 8)
      .map((tr) => Math.round(tr.getBoundingClientRect().top));
    const master = tops('#hot-container .ht_master tbody tr');
    const left = tops('#hot-container .ht_clone_left tbody tr');

    return {
      tabla: tabla.offsetWidth,
      hider: hider.offsetWidth,
      layout: getComputedStyle(tabla).tableLayout,
      // Sin encabezados de fila no hay clon izquierdo que alinear.
      desvioFilas: left.length
        ? Math.max(...left.map((t, i) => Math.abs(t - master[i])))
        : 0,
    };
  });

  if (!medida) {
    console.log(`SKIP ${ruta} — sin grilla montada`);
    continue;
  }

  const desfase = Math.abs(medida.hider - medida.tabla);
  const ok = desfase <= TOLERANCIA_PX
    && medida.layout === 'fixed'
    && medida.desvioFilas <= TOLERANCIA_PX;
  resultados.push(ok);
  console.log(
    `${ok ? 'PASS' : 'FAIL'} ${ruta} — tabla ${medida.tabla}px / wtHider ${medida.hider}px `
    + `(desfase ${desfase}px, table-layout: ${medida.layout}, `
    + `desvio del clon de filas ${medida.desvioFilas}px)`,
  );
}

await browser.close();

const fallos = resultados.filter((ok) => !ok).length;
console.log(`\n${resultados.length - fallos}/${resultados.length} rutas con la tabla al ancho declarado`);
process.exit(fallos === 0 ? 0 : 1);
