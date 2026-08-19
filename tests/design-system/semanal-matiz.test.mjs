import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const read = (file) => readFile(new URL(`../../${file}`, import.meta.url), 'utf8');

// Este guard lee el CSS, no el contrato. Es la leccion de
// `guard-valida-declaracion-contra-si-misma`: comprobar el JSON contra el JSON
// deja verde una divergencia entre contrato y hoja, y aqui la divergencia es el
// defecto — el chip ya consume matiz y la fila seguia consumiendo cubo de
// alerta, asi que la misma actividad decia dos cosas.
//
// Limite conocido y dicho: leer el TEXTO del CSS tampoco garantiza el render
// —ver `memoria/trampas/guard-de-texto-no-ve-el-parseo.md`—, asi que esto NO
// sustituye la medicion en navegador; caza la divergencia barata sin abrir
// Docker y deja la otra para la sonda.
const FASES = { prog: 'programacion', cal: 'calificacion' };

test('cada fase de Semanal pinta cinco matices distintos en el CSS', async () => {
  const semantics = JSON.parse(await read('docs/design-system/state-semantics.json'));
  const css = await read('public/css/programacion-semanal.css');
  const estados = semantics.moduleMappings.find((m) => m.module === 'programacion-semanal').states;

  for (const prefijo of Object.keys(FASES)) {
    const deLaFase = estados.filter(({ key }) => key.startsWith(`${prefijo}-`));
    assert.equal(deLaFase.length, 5, `la fase ${FASES[prefijo]} debe tener cinco estados`);

    const tintes = deLaFase.map(({ key, hue }) => {
      const regla = css.match(new RegExp(`\\.ps-state-${key}\\b[^{]*\\{([^}]*)\\}`))?.[1];
      assert.ok(regla, `programacion-semanal.css no pinta .ps-state-${key}`);
      const tinte = regla.match(/--ds-state-tint-([a-z]+)/)?.[1];
      assert.equal(tinte, hue, `.ps-state-${key} pinta ${tinte} y el contrato dice ${hue}`);
      return tinte;
    });

    assert.equal(
      new Set(tintes).size, 5,
      `la fase ${FASES[prefijo]} pinta ${new Set(tintes).size} colores para cinco estados: ${tintes}`,
    );
  }
});

// El otro eje. Las repeticiones ENTRE fases son inocuas -nunca conviven, ver
// stateMachine.js:58- pero el filete no depende de la fase: depende del nivel, y
// solo lo llevan los dos que piden accion.
test('el filete de Semanal solo aparece en los niveles que piden accion', async () => {
  const semantics = JSON.parse(await read('docs/design-system/state-semantics.json'));
  const estados = semantics.moduleMappings.find((m) => m.module === 'programacion-semanal').states;
  const conBarra = new Set(['urgent', 'attention']);

  // El contrato de la primitiva: `healthy` y `neutral` no declaran token, asi
  // que un estado de Semanal en esos niveles no puede pintar filete aunque
  // alguien escriba el atributo.
  const tokens = await read('public/css/tokens.css');
  for (const nivel of ['healthy', 'neutral']) {
    assert.doesNotMatch(
      tokens,
      new RegExp(`--ds-severity-rail-width-${nivel}\\s*:`),
      `${nivel} no debe declarar grosor de filete`,
    );
  }

  const niveles = new Set(estados.map(({ level }) => level));
  for (const n of niveles) {
    assert.ok(
      conBarra.has(n) || ['healthy', 'neutral'].includes(n),
      `nivel inesperado en Semanal: ${n}`,
    );
  }
});
