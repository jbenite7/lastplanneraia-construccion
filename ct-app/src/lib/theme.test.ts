// @vitest-environment jsdom
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { TEMA_STORAGE_KEY, aplicarTemaAlDocumento, leerTemaGuardado, resolverTemaInicial } from './theme'

// Task 8 paso 2 (Task 8 del plan 2026-08-26-ola1-torre-etapa-piloto.md, entrada 19c/27 de la
// Bitácora): el toggle de tema respeta `prefers-color-scheme` y persiste la elección explícita
// del usuario en localStorage — ACOTADO a esta hoja bajo la bandera del piloto (ruling entrada
// 19c: reintroducir un conmutador GLOBAL reabriría DS-030, retirado con guard propio en
// `linen-removal.test.mjs`, que no vigila `ct-app`). El documento PHP sigue arrancando en
// `data-aia-theme="dark"` (theme-bootstrap.js, sin tocar) — este módulo lo sobreescribe en
// runtime, solo dentro de esta pantalla.
//
// Mismo patrón que `public/js/modules/aia_ui/sidebar_navigation.js` (storageKey constante,
// lectura con validación de enum + fallback null, try/catch alrededor de cualquier acceso a
// localStorage — puede no estar disponible: modo privado, política del navegador, SSR de test).
//
// jsdom no expone `localStorage` para el origen "opaco" por defecto (`about:blank`, sin `url`
// en `environmentOptions.jsdom` de vite.config.ts — compartido por todo `ct-app`, no se toca
// aquí): accederlo lanza `SecurityError: localStorage is not available for opaque origins`
// antes incluso de llegar al código bajo prueba. Se sustituye por un fake mínimo (Map-backed)
// vía `vi.stubGlobal`, suficiente porque `theme.ts` solo depende del contrato
// getItem/setItem, nunca de la implementación real de storage.

function instalarLocalStorageFake(): { getItem: ReturnType<typeof vi.fn>; setItem: ReturnType<typeof vi.fn> } {
  const datos = new Map<string, string>()
  const getItem = vi.fn((clave: string) => (datos.has(clave) ? datos.get(clave)! : null))
  const setItem = vi.fn((clave: string, valor: string) => {
    datos.set(clave, valor)
  })
  vi.stubGlobal('localStorage', { getItem, setItem, clear: () => datos.clear() })
  return { getItem, setItem }
}

beforeEach(() => {
  instalarLocalStorageFake()
})

afterEach(() => {
  vi.unstubAllGlobals()
})

describe('TEMA_STORAGE_KEY', () => {
  it('es una clave propia de este piloto, no la del sidebar ni una compartida', () => {
    expect(TEMA_STORAGE_KEY).toBe('ct-piloto-theme')
  })
})

describe('leerTemaGuardado', () => {
  it('devuelve null cuando no hay nada guardado', () => {
    expect(leerTemaGuardado()).toBeNull()
  })

  it('devuelve "dark" cuando el valor guardado es "dark"', () => {
    localStorage.setItem(TEMA_STORAGE_KEY, 'dark')
    expect(leerTemaGuardado()).toBe('dark')
  })

  it('devuelve "light" cuando el valor guardado es "light"', () => {
    localStorage.setItem(TEMA_STORAGE_KEY, 'light')
    expect(leerTemaGuardado()).toBe('light')
  })

  it('devuelve null ante un valor guardado que no es un tema válido (defensa contra corrupción externa)', () => {
    localStorage.setItem(TEMA_STORAGE_KEY, 'sepia')
    expect(leerTemaGuardado()).toBeNull()
  })

  it('devuelve null sin lanzar cuando localStorage.getItem lanza (modo privado, política del navegador)', () => {
    const { getItem } = instalarLocalStorageFake()
    getItem.mockImplementation(() => {
      throw new Error('SecurityError: acceso a localStorage denegado')
    })
    expect(() => leerTemaGuardado()).not.toThrow()
    expect(leerTemaGuardado()).toBeNull()
  })
})

describe('resolverTemaInicial', () => {
  const originalMatchMedia = window.matchMedia

  afterEach(() => {
    window.matchMedia = originalMatchMedia
  })

  function mockMatchMedia(prefiereClaro: boolean) {
    window.matchMedia = vi.fn().mockImplementation((query: string) => ({
      matches: query === '(prefers-color-scheme: light)' ? prefiereClaro : false,
      media: query,
      onchange: null,
      addListener: vi.fn(),
      removeListener: vi.fn(),
      addEventListener: vi.fn(),
      removeEventListener: vi.fn(),
      dispatchEvent: vi.fn(),
    })) as unknown as typeof window.matchMedia
  }

  it('prioriza la elección explícita guardada sobre la preferencia del sistema', () => {
    mockMatchMedia(true) // el sistema prefiere claro
    localStorage.setItem(TEMA_STORAGE_KEY, 'dark') // pero el usuario ya eligió oscuro antes
    expect(resolverTemaInicial()).toBe('dark')
  })

  it('sin elección guardada, usa "light" si el sistema prefiere claro', () => {
    mockMatchMedia(true)
    expect(resolverTemaInicial()).toBe('light')
  })

  it('sin elección guardada y sin preferencia de claro, usa "dark" (el default del producto, DS-009)', () => {
    mockMatchMedia(false)
    expect(resolverTemaInicial()).toBe('dark')
  })
})

describe('aplicarTemaAlDocumento', () => {
  afterEach(() => {
    document.documentElement.removeAttribute('data-aia-theme')
  })

  it('setea data-aia-theme="light" en <html> y persiste la elección', () => {
    aplicarTemaAlDocumento('light')
    expect(document.documentElement.getAttribute('data-aia-theme')).toBe('light')
    expect(localStorage.getItem(TEMA_STORAGE_KEY)).toBe('light')
  })

  it('setea data-aia-theme="dark" en <html> y persiste la elección', () => {
    aplicarTemaAlDocumento('dark')
    expect(document.documentElement.getAttribute('data-aia-theme')).toBe('dark')
    expect(localStorage.getItem(TEMA_STORAGE_KEY)).toBe('dark')
  })

  it('no lanza si localStorage.setItem falla (persistencia es best-effort, la aplicación visual no depende de ella)', () => {
    const { setItem } = instalarLocalStorageFake()
    setItem.mockImplementation(() => {
      throw new Error('QuotaExceededError')
    })
    expect(() => aplicarTemaAlDocumento('light')).not.toThrow()
    expect(document.documentElement.getAttribute('data-aia-theme')).toBe('light')
  })
})
