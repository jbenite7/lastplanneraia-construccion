import { execFileSync } from 'node:child_process';

/**
 * Origen ÚNICO de la URL base de los e2e.
 *
 * Vive aparte de `projects.mjs` porque lo importan dos consumidores que no deben acoplarse entre sí:
 * `playwright.config.mjs` (para `use.baseURL`, que resuelve las rutas relativas de `page.goto`) y los
 * helpers de sesión (que construyen URLs absolutas para login/logout). Tenerlo duplicado ya costó un
 * fallo difícil de leer: con defaults distintos, el login entraba en un stack y la navegación
 * relativa saltaba al otro, donde no había sesión — y la suite entera moría en la pantalla de login.
 */

/**
 * Puerto que publica el contenedor `app` del stack de ESTE working tree, o null si no se puede
 * averiguar (docker caído, stack sin levantar, o cwd fuera del repo).
 *
 * Esta máquina levanta varios stacks locales a la vez: el working tree principal publica en 8081 y
 * cada worktree en el suyo (el del PDC, en 8091). Un default fijo hacía que los specs corridos desde
 * un worktree atacaran el stack del vecino, con otro código desplegado y otra base de datos. Cuando
 * el compose es el del principal devuelve 8081, así que el valor efectivo no cambia para quien ya
 * trabajaba ahí.
 */
export function puertoDelStackLocal() {
  try {
    return execFileSync('docker', ['compose', 'port', 'app', '80'], {
      cwd: process.cwd(),
      encoding: 'utf8',
      timeout: 30_000,
      stdio: ['ignore', 'pipe', 'ignore'],
    }).trim().match(/:(\d+)\s*$/)?.[1] ?? null;
  } catch {
    return null;
  }
}

export const BASE_URL = process.env.E2E_BASE_URL
  || `http://localhost:${puertoDelStackLocal() ?? 8081}`;
