import { describe, expect, test } from 'vitest'

// Verifica con matemática WCAG real (no snapshot ni inspección manual) el hallazgo de la
// Bitácora del piloto (entrada 29/30/32) y la corrección de DESIGN.md §Replanteo de estados
// 2026-08-27: el texto sobre una fila/franja con tinte de severidad (--ct-row-rojo/-ambar/-verde,
// invariantes por diseño) debe ser TAMBIÉN invariante (--ct-row-text-primary/-secondary), nunca
// seguir --ct-text-primary/-secondary (que sí cambia con el tema). Escrito en rol de test tras el
// fix real (ver ct-app/src/lib/tokens.css) -- el caso RED que este archivo habría atrapado está
// documentado en cada test como el valor que rompía el contraste antes del fix.

const MINIMO_TEXTO_NORMAL = 4.5

function luminanciaRelativa(hex: string): number {
  const valor = hex.replace('#', '')
  const [r, g, b] = [0, 2, 4].map((i) => parseInt(valor.slice(i, i + 2), 16) / 255)
  const canal = (c: number) => (c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4)
  return 0.2126 * canal(r) + 0.7152 * canal(g) + 0.0722 * canal(b)
}

function contraste(hexA: string, hexB: string): number {
  const [l1, l2] = [luminanciaRelativa(hexA), luminanciaRelativa(hexB)].sort((a, b) => b - a)
  return (l1 + 0.05) / (l2 + 0.05)
}

// Valores reales, tomados de ct-app/src/lib/tokens.css y de public/css/tokens.css (los
// --ds-state-row-*/--ds-state-solid-* que el bridge de ct-app resuelve). Si alguno de estos
// valores cambia en su fuente, este test debe actualizarse junto con ella -- no antes.
const CT_ROW_ROJO = '#3a1f1c'
const CT_ROW_AMBAR = '#383314'
const CT_ROW_VERDE = '#20362a'
const CT_ROW_TEXT_PRIMARY = '#f7faf8'
const CT_ROW_TEXT_SECONDARY = '#c7d4cc'
const CT_CHIP_VERDE_BG = '#57b083'
const CT_CHIP_VERDE_TEXT = '#06281a'

// El bug real medido (bitácora entrada 29): antes del fix, el texto sobre fila con tinte
// heredaba --ct-text-primary/-secondary, que en claro resuelve a estos valores oscuros.
const CT_TEXT_PRIMARY_CLARO_BUG = '#18181b'
const CT_TEXT_SECONDARY_CLARO_BUG = '#52525b'

describe('texto invariante sobre fila/franja con tinte de severidad', () => {
  test.each([
    ['rojo', CT_ROW_ROJO],
    ['ambar', CT_ROW_AMBAR],
    ['verde', CT_ROW_VERDE],
  ])('--ct-row-text-primary sobre --ct-row-%s cumple AA (>=4.5:1)', (_hue, fondo) => {
    expect(contraste(CT_ROW_TEXT_PRIMARY, fondo)).toBeGreaterThanOrEqual(MINIMO_TEXTO_NORMAL)
  })

  test.each([
    ['rojo', CT_ROW_ROJO],
    ['ambar', CT_ROW_AMBAR],
    ['verde', CT_ROW_VERDE],
  ])('--ct-row-text-secondary sobre --ct-row-%s cumple AA (>=4.5:1)', (_hue, fondo) => {
    expect(contraste(CT_ROW_TEXT_SECONDARY, fondo)).toBeGreaterThanOrEqual(MINIMO_TEXTO_NORMAL)
  })

  // El caso RED real: el bug que el fix corrigió. Documentado como test explícito, no solo
  // como comentario, para que una regresión futura (alguien que revierta el fix por error) lo
  // atrape en CI -- no dependa de que alguien vuelva a medir a mano en el navegador.
  test.each([
    ['rojo', CT_ROW_ROJO],
    ['ambar', CT_ROW_AMBAR],
    ['verde', CT_ROW_VERDE],
  ])('el bug real (texto dependiente del tema claro sobre fila con tinte) fallaba AA', (_hue, fondo) => {
    expect(contraste(CT_TEXT_PRIMARY_CLARO_BUG, fondo)).toBeLessThan(MINIMO_TEXTO_NORMAL)
    expect(contraste(CT_TEXT_SECONDARY_CLARO_BUG, fondo)).toBeLessThan(MINIMO_TEXTO_NORMAL)
  })
})

describe('verde de "N listas" (Semaforo.tsx) -- caso opuesto: SÍ depende del tema', () => {
  test('--ct-chip-verde-bg (verde fijo) sobre las filas con tinte cumple AA', () => {
    expect(contraste(CT_CHIP_VERDE_BG, CT_ROW_ROJO)).toBeGreaterThanOrEqual(MINIMO_TEXTO_NORMAL)
    expect(contraste(CT_CHIP_VERDE_BG, CT_ROW_AMBAR)).toBeGreaterThanOrEqual(MINIMO_TEXTO_NORMAL)
    expect(contraste(CT_CHIP_VERDE_BG, CT_ROW_VERDE)).toBeGreaterThanOrEqual(MINIMO_TEXTO_NORMAL)
  })

  test('--ct-chip-verde-bg sobre blanco (franja sin tinte, tema claro) FALLA AA -- por eso Semaforo.css lo sobreescribe ahí', () => {
    expect(contraste(CT_CHIP_VERDE_BG, '#ffffff')).toBeLessThan(MINIMO_TEXTO_NORMAL)
  })

  test('--ct-chip-verde-text (el override real de Semaforo.css en esa franja) cumple AA sobre blanco', () => {
    expect(contraste(CT_CHIP_VERDE_TEXT, '#ffffff')).toBeGreaterThanOrEqual(MINIMO_TEXTO_NORMAL)
  })
})
