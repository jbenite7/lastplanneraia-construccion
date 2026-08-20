import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import test from 'node:test';

// Este guard nace de un hueco medido, no de una sospecha.
//
// `state-semantics.json` declara 55 estados. Los guards que ya existian
// comprueban que lo declarado sea COHERENTE -que el matiz este en la escalera,
// que el nivel exista, que el modulo proyecte lo que el contrato dice-, pero
// ninguno comprueba que lo declarado se USE. El agujero se destapo con los siete
// estados del modulo `pdc`: llevan matiz y nivel, pasan todos los guards, y
// ninguna pantalla los pinta. Sus etiquetas solo aparecen en la prosa del
// laboratorio.
//
// Y la causa de que no se detectara es mas honda que ese modulo: un estado sin
// `key` no se puede unir con su renderer, asi que **no aparece como incumplido,
// simplemente no aparece**. Medido el 2026-08-19: 25 estados con clave -los 25
// consumidos por codigo real- y 30 sin ella.
//
// Por eso el guard vigila DOS cosas distintas:
//   1. que ningun estado NUEVO nazca sin `key` (la deuda vieja va congelada en
//      `state-key-debt.json`, visible y cerrada por arriba, nunca autorizada);
//   2. que todo estado CON `key` tenga al menos un consumidor en el codigo.
//
// La segunda es la que impide que el caso `pdc` se repita en un modulo que si
// declara claves.

const REPO = fileURLToPath(new URL('../../', import.meta.url));
const leer = (p) => JSON.parse(readFileSync(new URL(p, import.meta.url), 'utf8'));
const contrato = leer('../../docs/design-system/state-semantics.json');
const deuda = leer('../../docs/design-system/state-key-debt.json');

const RAICES = ['public/js', 'public/css', 'views', 'src', 'pdc-app/src', 'admin'];

function consumidores(clave) {
  try {
    const salida = execFileSync('grep', ['-rlF', clave, ...RAICES], { cwd: REPO, encoding: 'utf8' });
    return salida.trim().split('\n').filter((l) => l && !l.includes('node_modules'));
  } catch {
    return [];
  }
}

test('ningun estado nuevo se declara sin `key`', () => {
  const nuevos = [];
  for (const modulo of contrato.moduleMappings) {
    const tolerados = new Set(deuda.sin_clave[modulo.module] || []);
    for (const estado of modulo.states || []) {
      if (!estado.key && !tolerados.has(estado.label)) {
        nuevos.push(`${modulo.module}: «${estado.label}»`);
      }
    }
  }
  assert.deepEqual(
    nuevos,
    [],
    'Estados declarados sin `key` que no estan en la deuda congelada. Un estado sin clave '
      + 'no se puede unir con su renderer, asi que ningun guard puede comprobar que alguien lo pinte. '
      + 'Declara `key` — o, si de verdad hay que aplazarlo, di por que en docs/design-system/state-key-debt.json.\n  '
      + nuevos.join('\n  '),
  );
});

test('la deuda de claves no crece y sus entradas siguen existiendo', () => {
  const declarados = new Map();
  for (const modulo of contrato.moduleMappings) {
    declarados.set(modulo.module, new Set((modulo.states || []).map((e) => e.label)));
  }
  const fantasmas = [];
  for (const [modulo, etiquetas] of Object.entries(deuda.sin_clave)) {
    const vivas = declarados.get(modulo);
    for (const etiqueta of etiquetas) {
      if (!vivas || !vivas.has(etiqueta)) fantasmas.push(`${modulo}: «${etiqueta}»`);
    }
  }
  // Una tolerancia que sobrevive al estado que toleraba es peor que no tenerla:
  // hace creer que se esta midiendo algo que ya no existe.
  assert.deepEqual(fantasmas, [], 'La deuda tolera estados que ya no estan en el contrato. Quita estas entradas:\n  ' + fantasmas.join('\n  '));

  const total = Object.values(deuda.sin_clave).reduce((n, v) => n + v.length, 0);
  assert.ok(total <= 30, `La deuda de claves subio a ${total}; el maximo medido y congelado es 30.`);
});

test('todo estado con `key` tiene al menos un consumidor en el codigo', () => {
  const huerfanos = [];
  for (const modulo of contrato.moduleMappings) {
    for (const estado of modulo.states || []) {
      if (!estado.key) continue;
      if (consumidores(estado.key).length === 0) {
        huerfanos.push(`${modulo.module}: ${estado.key} («${estado.label}»)`);
      }
    }
  }
  assert.deepEqual(
    huerfanos,
    [],
    'Estados con `key` que ninguna hoja, vista ni modulo consume. Un estado declarado y no pintado '
      + 'ocupa un matiz sin usarlo, y hace parecer cubierto lo que no lo esta:\n  ' + huerfanos.join('\n  '),
  );
});
