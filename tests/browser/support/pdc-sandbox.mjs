import { execFileSync } from 'node:child_process';
import { test } from '@playwright/test';
import { BASE_URL, PDC_SANDBOX_PROJECT, puertoDelStackLocal } from '../fixtures/projects.mjs';

export { PDC_SANDBOX_PROJECT };

/**
 * El reseteo del sandbox habla con el MySQL local vía `docker compose exec`, mientras el navegador
 * ataca `E2E_BASE_URL`. Si alguien apunta los specs a otro entorno (staging, otro stack), el
 * navegador escribiría allá y este script sembraría aquí: el proyecto de destino no existiría o
 * llegaría sucio. Mismo guardarraíl que ya usaba `pdc-v2-plan.spec.mjs` para su restauración.
 */
export const SANDBOX_LOCAL = /^https?:\/\/localhost(:|\/|$)/.test(BASE_URL);

const RAZON_NO_LOCAL = `El sandbox del PDC se siembra por «docker compose exec» contra el MySQL local; `
  + `E2E_BASE_URL (${BASE_URL}) no apunta a localhost, así que el proyecto de pruebas no estaría `
  + `sembrado en el entorno que ataca el navegador.`;

/**
 * «Localhost» no basta como guardarraíl: esta máquina levanta VARIOS stacks locales a la vez (el del
 * working tree principal en 8081, el de este worktree en 8091). El default de BASE_URL ya se deriva
 * del stack de cada working tree, así que este chequeo cubre el caso que queda: un E2E_BASE_URL
 * explícito apuntando a otro stack local. Sembrar por docker y atacar por HTTP puertos distintos
 * deja el navegador donde el proyecto 990100 no existe, y el spec revienta en `selectProject` sin
 * explicar por qué.
 */
let puertoStackCache;
function puertoPublicadoDelStack() {
  if (puertoStackCache === undefined) {
    // null = docker caído o sin stack: el seed fallaría igual, pero con peor mensaje.
    puertoStackCache = puertoDelStackLocal();
  }
  return puertoStackCache;
}

function puertoDe(url) {
  try {
    const { port, protocol } = new URL(url);
    return port || (protocol === 'https:' ? '443' : '80');
  } catch {
    return null;
  }
}

/** null si el stack sembrado y el atacado coinciden; si no, el motivo del salto. */
export function razonStackDistinto() {
  const delStack = puertoPublicadoDelStack();
  if (delStack === null) {
    return `No se pudo determinar en qué puerto publica el stack de este working tree `
      + `(«docker compose port app 80» falló). El sandbox del PDC se siembra por «docker compose `
      + `exec», así que sin stack levantado aquí no hay dónde sembrarlo: levántalo con `
      + `«docker compose up -d db app».`;
  }
  const delNavegador = puertoDe(BASE_URL);
  if (delNavegador === delStack) {
    return null;
  }
  return `E2E_BASE_URL apunta a ${BASE_URL}, pero el sandbox se siembra por «docker compose exec» `
    + `en el stack de este working tree, que publica en http://localhost:${delStack}. El navegador `
    + `atacaría un stack donde el proyecto ${PDC_SANDBOX_PROJECT.projectId} no está sembrado. `
    + `Exporta E2E_BASE_URL=http://localhost:${delStack}, o corre los specs desde el working tree `
    + `cuyo stack quieres atacar.`;
}

/** Ejecuta PHP dentro del contenedor `app`, igual que los tests PHP autoejecutables. */
export function phpEnApp(argumentos) {
  return execFileSync('docker', ['compose', 'exec', '-T', 'app', 'php', ...argumentos], {
    cwd: process.cwd(),
    encoding: 'utf8',
    timeout: 60_000,
  });
}

/** SQL directo contra la BD del stack, reusando la conexión configurada de Database.php. */
export function sqlEnApp(codigoPhp) {
  return phpEnApp([
    '-r',
    `require '/var/www/html/vendor/autoload.php';`
    + `require '/var/www/html/src/Core/Database.php';`
    + `$db = Database::getInstance();`
    + codigoPhp,
  ]).trim();
}

/**
 * Deja el sandbox en su estado inicial conocido: sin presupuestos, sin vínculos, sin asignaciones
 * ni plan, y con el cronograma mínimo resembrado. Idempotente por construcción.
 */
export function resetearSandboxPdc() {
  phpEnApp(['/var/www/html/database/seeds/pdc_e2e_sandbox_project.php']);
}

let avisado = false;
function avisarUnaVez(razon) {
  if (razon === null || avisado) {
    return;
  }
  avisado = true;
  console.warn(`\n⚠️  Specs del PDC v2 saltados — ${razon}\n`);
}

/**
 * Registra el ciclo de vida del sandbox para un spec del PDC v2: resetea antes de cada test y
 * salta con un motivo claro si el entorno no permite sembrarlo.
 *
 * Llamar en el cuerpo del módulo, antes de los `test(...)`.
 */
export function usarSandboxPdc() {
  test.beforeEach(() => {
    test.skip(!SANDBOX_LOCAL, RAZON_NO_LOCAL);
    const razon = razonStackDistinto();
    // El motivo de un `test.skip` solo se ve en el reporter html/json, y estos specs se corren con
    // `--reporter=line`: sin esto el salto sería mudo y el guardarraíl no ahorraría el diagnóstico.
    avisarUnaVez(razon);
    test.skip(razon !== null, razon ?? '');
    resetearSandboxPdc();
  });
}
