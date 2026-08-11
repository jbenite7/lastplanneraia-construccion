#!/usr/bin/env node
/**
 * Genera el recibo de un gate de cierre ejecutándolo de verdad.
 *
 * Por qué existe: hasta el 2026-08-11 los recibos de `docs/design-system/evidence/`
 * pesaban 47 bytes y eran dos claves —`gateId` y `result`— sin comando, sin salida,
 * sin fecha y sin árbol medido. `closeout-evidence.json` sí prometía `command`,
 * `exitCode` y `artifactSha256`, pero el artefacto al que apuntaba no permitía
 * comprobar ninguna de las tres cosas. El cierre se avalaba a sí mismo.
 *
 * Un recibo es la salida de un comando, no una declaración. Este script no acepta
 * un resultado como parámetro: ejecuta y anota lo que salga, incluido el rojo.
 *
 *   node scripts/design-system/gate-receipt.mjs <gateId>
 *
 * El comando lo lee de `closeout-evidence.json`, para que el recibo no pueda medir
 * algo distinto de lo que el índice declara.
 */
import { execSync, spawnSync } from 'node:child_process';
import { readFileSync, writeFileSync, mkdirSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const raiz = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const INDICE = path.join(raiz, 'docs/design-system/closeout-evidence.json');
const DIR_RECIBOS = path.join(raiz, 'docs/design-system/evidence');

const gitSeguro = (args, porDefecto) => {
  const r = spawnSync('git', args, { cwd: raiz, encoding: 'utf8' });
  return r.status === 0 ? r.stdout.trim() : porDefecto;
};

export function generarRecibo(gateId, { escribir = true } = {}) {
  const indice = JSON.parse(readFileSync(INDICE, 'utf8'));
  const gate = indice.gates.find((g) => g.id === gateId);
  if (!gate) throw new Error(`gate desconocido: ${gateId}`);

  const declarado = (gate.evidence || [])[0] || {};
  const comando = declarado.command;
  if (!comando) throw new Error(`el gate ${gateId} no declara comando en closeout-evidence.json`);

  // El árbol medido importa tanto como el resultado: un verde solo vale para el
  // árbol donde se midió. Se anota si había cambios sin commitear, porque un recibo
  // tomado sobre un árbol sucio no describe ningún commit.
  const sha = gitSeguro(['rev-parse', 'HEAD'], 'desconocido');
  const sucio = gitSeguro(['status', '--porcelain'], '') !== '';

  const inicio = Date.now();
  const r = spawnSync('sh', ['-c', comando], {
    cwd: raiz,
    encoding: 'utf8',
    env: process.env,
    maxBuffer: 64 * 1024 * 1024,
  });
  const salida = `${r.stdout || ''}${r.stderr || ''}`;
  const exitCode = r.status === null ? -1 : r.status;

  const recibo = {
    gateId,
    // `result` se DERIVA del código de salida. No es un parámetro: es la única
    // forma de que un recibo no pueda afirmar algo distinto de lo que ocurrió.
    result: exitCode === 0 ? 'passed' : 'failed',
    command: comando,
    exitCode,
    measuredAt: new Date(inicio).toISOString(),
    durationMs: Date.now() - inicio,
    tree: { sha, dirty: sucio },
    // Cola de la salida real. No es decorativa: es lo que permite a una persona
    // discutir el veredicto sin volver a ejecutarlo.
    outputTail: salida.split('\n').filter(Boolean).slice(-25).join('\n'),
  };

  if (escribir) {
    mkdirSync(DIR_RECIBOS, { recursive: true });
    writeFileSync(path.join(DIR_RECIBOS, `${gateId}.json`), `${JSON.stringify(recibo, null, 2)}\n`);
  }
  return recibo;
}

/**
 * Comprueba que un recibo describe lo que el índice declara y lo que realmente pasó.
 * Falla cerrado: cualquier ausencia es un fallo, no un aviso.
 */
export function validarRecibo(recibo, gateDeclarado) {
  const fallos = [];
  const exigido = ['gateId', 'result', 'command', 'exitCode', 'measuredAt', 'tree', 'outputTail'];
  for (const clave of exigido) {
    if (recibo[clave] === undefined) fallos.push(`falta la clave '${clave}'`);
  }
  if (fallos.length) return fallos;

  if (recibo.gateId !== gateDeclarado.id) {
    fallos.push(`el recibo dice ser de '${recibo.gateId}' y está archivado como '${gateDeclarado.id}'`);
  }
  const comandoDeclarado = (gateDeclarado.evidence || [])[0]?.command;
  if (comandoDeclarado && recibo.command !== comandoDeclarado) {
    fallos.push(`el recibo midió '${recibo.command}' y el índice declara '${comandoDeclarado}'`);
  }
  // El corazón: `passed` con salida distinta de cero es una mentira, y es
  // exactamente la forma que tenía el cierre anterior de avalarse a sí mismo.
  const esperado = recibo.exitCode === 0 ? 'passed' : 'failed';
  if (recibo.result !== esperado) {
    fallos.push(`declara '${recibo.result}' con exitCode ${recibo.exitCode}; con ese código sería '${esperado}'`);
  }
  // Y el índice tiene que estar de acuerdo con su propio recibo. Sin esto, la
  // comprobación de arriba solo garantiza que el recibo sea coherente CONSIGO
  // MISMO: un gate podía declararse `passed` en el índice mientras su recibo
  // decía honestamente `failed`, y nadie protestaba. Medido el 2026-08-11
  // mutando `runtime` a `passed` con su recibo en `failed`: los cinco tests
  // pasaban. Es la misma forma de la enfermedad que este frente vino a curar
  // —el cierre avalándose a sí mismo—, solo que una capa más adentro.
  if (gateDeclarado.status === 'passed' && recibo.result !== 'passed') {
    fallos.push(
      `el índice declara '${gateDeclarado.id}' como 'passed' y su recibo dice '${recibo.result}'`,
    );
  }
  if (!recibo.tree || typeof recibo.tree.sha !== 'string' || recibo.tree.sha.length < 7) {
    fallos.push('el recibo no dice sobre qué árbol se midió');
  }
  if (Number.isNaN(Date.parse(recibo.measuredAt))) {
    fallos.push(`'measuredAt' no es una fecha: ${recibo.measuredAt}`);
  }
  return fallos;
}

const invocadoDirecto = process.argv[1] && fileURLToPath(import.meta.url) === path.resolve(process.argv[1]);
if (invocadoDirecto) {
  const gateId = process.argv[2];
  if (!gateId) {
    console.error('uso: node scripts/design-system/gate-receipt.mjs <gateId>');
    process.exit(2);
  }
  const recibo = generarRecibo(gateId);
  console.log(`${gateId}: ${recibo.result} (exit ${recibo.exitCode}) sobre ${recibo.tree.sha.slice(0, 8)}${recibo.tree.dirty ? ' [árbol sucio]' : ''}`);
  // El script NO falla cuando el gate falla: su trabajo es levantar acta, no aprobar.
  process.exit(0);
}
