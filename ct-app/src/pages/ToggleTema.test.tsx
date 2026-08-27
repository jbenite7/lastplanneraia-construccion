// @vitest-environment jsdom
//
// Toggle de tema visible (Task 8 paso 2, entrada 19c/27 de la Bitácora del plan
// 2026-08-26-ola1-torre-etapa-piloto.md): control que alterna `data-aia-theme` en <html> y
// persiste la elección — ACOTADO a esta hoja bajo la bandera del piloto (ver lib/theme.ts).
// El estado del botón se sincroniza con el atributo real del documento, no con un estado
// interno propio, para que reflejar correctamente lo que `main.tsx` ya aplicó al arrancar.
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import '@testing-library/jest-dom/vitest'
import { cleanup, render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { ToggleTema } from './ToggleTema'
import { TEMA_STORAGE_KEY } from '../lib/theme'

function instalarLocalStorageFake() {
  const datos = new Map<string, string>()
  vi.stubGlobal('localStorage', {
    getItem: (clave: string) => (datos.has(clave) ? datos.get(clave)! : null),
    setItem: (clave: string, valor: string) => {
      datos.set(clave, valor)
    },
    clear: () => datos.clear(),
  })
}

beforeEach(() => {
  instalarLocalStorageFake()
  document.documentElement.setAttribute('data-aia-theme', 'dark')
})

afterEach(() => {
  cleanup()
  document.documentElement.removeAttribute('data-aia-theme')
  vi.unstubAllGlobals()
})

describe('ToggleTema', () => {
  it('arranca reflejando el tema actual del documento (oscuro)', () => {
    render(<ToggleTema />)
    expect(screen.getByRole('button', { name: /tema claro/i })).toBeInTheDocument()
  })

  it('arranca reflejando el tema actual del documento (claro)', () => {
    document.documentElement.setAttribute('data-aia-theme', 'light')
    render(<ToggleTema />)
    expect(screen.getByRole('button', { name: /tema oscuro/i })).toBeInTheDocument()
  })

  it('al hacer clic, alterna el atributo del documento y persiste la elección', async () => {
    const user = userEvent.setup()
    render(<ToggleTema />)

    await user.click(screen.getByRole('button', { name: /tema claro/i }))

    expect(document.documentElement.getAttribute('data-aia-theme')).toBe('light')
    expect(localStorage.getItem(TEMA_STORAGE_KEY)).toBe('light')
    expect(screen.getByRole('button', { name: /tema oscuro/i })).toBeInTheDocument()
  })

  it('un segundo clic vuelve al tema anterior', async () => {
    const user = userEvent.setup()
    render(<ToggleTema />)

    await user.click(screen.getByRole('button', { name: /tema claro/i }))
    await user.click(screen.getByRole('button', { name: /tema oscuro/i }))

    expect(document.documentElement.getAttribute('data-aia-theme')).toBe('dark')
    expect(localStorage.getItem(TEMA_STORAGE_KEY)).toBe('dark')
  })
})
