import { expect, test } from 'vitest';
import { leerTokenReset } from './tokenReset';

const TOKEN = 'a'.repeat(64);
const TOKEN_MIXTO = '0123456789abcdef'.repeat(4);

test.each([
  ['', { kind: 'invalid' }],
  ['?', { kind: 'invalid' }],
  ['?token=', { kind: 'invalid' }],
  ['?token=abc', { kind: 'invalid' }],
  [`?token=${'a'.repeat(63)}`, { kind: 'invalid' }],
  [`?token=${'a'.repeat(65)}`, { kind: 'invalid' }],
  [`?token=${'A'.repeat(64)}`, { kind: 'invalid' }],
  [`?token=${'g'.repeat(64)}`, { kind: 'invalid' }],
  [`?token=%20${TOKEN}`, { kind: 'invalid' }],
  [`?token=${TOKEN}%0A`, { kind: 'invalid' }],
  [`?Token=${TOKEN}`, { kind: 'invalid' }],
  [`?token[]=${TOKEN}`, { kind: 'invalid' }],
  [`?token=${TOKEN}&token=${TOKEN}`, { kind: 'invalid' }],
  [`?token=${TOKEN}&token=`, { kind: 'invalid' }],
  [`?token=${TOKEN}`, { kind: 'candidate', token: TOKEN }],
  [`?token=${TOKEN_MIXTO}`, { kind: 'candidate', token: TOKEN_MIXTO }],
  [`token=${TOKEN}`, { kind: 'candidate', token: TOKEN }],
  [`?utm=email&token=${TOKEN}`, { kind: 'candidate', token: TOKEN }],
])('leerTokenReset(%s)', (search, esperado) => {
  expect(leerTokenReset(search)).toEqual(esperado);
});
