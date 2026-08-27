// Toggle de tema para la hoja de Intermedia (Task 8 paso 2, entrada 19c/27 de la Bitácora del
// plan 2026-08-26-ola1-torre-etapa-piloto.md). Respeta `prefers-color-scheme` y persiste la
// elección explícita del usuario en localStorage — ACOTADO a esta hoja bajo la bandera del
// piloto. El documento PHP sigue arrancando en `data-aia-theme="dark"`
// (`public/js/modules/aia_ui/theme-bootstrap.js`, sin tocar); este módulo lo sobreescribe en
// runtime, solo dentro de `ct-app`. Nunca reintroduce un conmutador global — eso reabriría
// DS-030 (retirado con guard propio en `tests/design-system/linen-removal.test.mjs`, que no
// vigila `ct-app`), y esa es decisión de Felipe si algún día se pide, no efecto colateral de
// esta tarea.
//
// Mismo patrón que `public/js/modules/aia_ui/sidebar_navigation.js`: clave de storage propia,
// lectura con validación de enum + fallback a null, y todo acceso a localStorage envuelto en
// try/catch (modo privado, política del navegador, o un test sin storage real pueden lanzar).

export type Tema = 'dark' | 'light'

export const TEMA_STORAGE_KEY = 'ct-piloto-theme'

function esTema(valor: unknown): valor is Tema {
  return valor === 'dark' || valor === 'light'
}

/** Lee la elección explícita del usuario, si existe y es válida. `null` en cualquier otro caso. */
export function leerTemaGuardado(): Tema | null {
  try {
    const crudo = localStorage.getItem(TEMA_STORAGE_KEY)
    return esTema(crudo) ? crudo : null
  } catch {
    return null
  }
}

/**
 * Resuelve el tema con el que la hoja debe arrancar: la elección guardada del usuario tiene
 * prioridad; si no hay ninguna, se usa `prefers-color-scheme` del sistema; sin preferencia de
 * claro, el default es "dark" (DS-009, el modo operativo por defecto del producto).
 */
export function resolverTemaInicial(): Tema {
  const guardado = leerTemaGuardado()
  if (guardado) return guardado

  const prefiereClaro = window.matchMedia('(prefers-color-scheme: light)').matches
  return prefiereClaro ? 'light' : 'dark'
}

/**
 * Aplica el tema al documento (`data-aia-theme` en `<html>`, mismo atributo que
 * `theme-bootstrap.js`/`aia-design-system.css` ya consumen) y persiste la elección. La
 * persistencia es best-effort: si falla (cuota, modo privado), el tema igual se aplica
 * visualmente — la app no depende de que localStorage esté disponible.
 */
export function aplicarTemaAlDocumento(tema: Tema): void {
  document.documentElement.setAttribute('data-aia-theme', tema)
  try {
    localStorage.setItem(TEMA_STORAGE_KEY, tema)
  } catch {
    // best-effort, ver docblock del módulo
  }
}
