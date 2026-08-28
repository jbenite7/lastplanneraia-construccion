import js from '@eslint/js'
import globals from 'globals'
import babelParser from '@babel/eslint-parser'
import jsxA11y from 'eslint-plugin-jsx-a11y'
import reactHooks from 'eslint-plugin-react-hooks'

// Sin typescript-eslint a propósito: typescript-eslint rechaza correr contra
// TypeScript 7 (el compilador nativo que usa este proyecto) con un hard-stop,
// no un warning -- ver https://github.com/typescript-eslint/typescript-eslint/issues/10940.
// El parser de Babel entiende sintaxis TS/TSX sin depender de la API del
// compilador de TypeScript, así que el lint no queda bloqueado por esa versión.
// El chequeo de tipos lo sigue haciendo `tsc` en build; este lint solo cubre
// reglas de hooks, accesibilidad y JS genérico.
const tsFiles = ['src/**/*.{ts,tsx}']

export default [
  { ignores: ['dist', 'node_modules'] },
  {
    files: tsFiles,
    languageOptions: {
      parser: babelParser,
      parserOptions: {
        requireConfigFile: false,
        babelOptions: {
          presets: ['@babel/preset-typescript'],
          plugins: ['@babel/plugin-syntax-jsx'],
        },
      },
      ecmaVersion: 2020,
      sourceType: 'module',
      globals: globals.browser,
    },
    plugins: {
      'react-hooks': reactHooks,
      'jsx-a11y': jsxA11y,
    },
    rules: {
      ...js.configs.recommended.rules,
      ...reactHooks.configs['recommended-latest'].rules,
      ...jsxA11y.flatConfigs.recommended.rules,
      // Babel strippea las anotaciones de tipo antes de que ESLint vea el AST:
      // un import o una interfaz usados solo como tipo salen como "no definido"
      // o "nunca usado". Sin el analizador de tipos de typescript-eslint no hay
      // forma de distinguir eso de un no-undef/no-unused-vars real; `tsc` en
      // build ya cubre ambos casos con información de tipos de verdad.
      'no-undef': 'off',
      'no-unused-vars': 'off',
    },
  },
  {
    files: ['vite.config.ts'],
    languageOptions: {
      parser: babelParser,
      parserOptions: {
        requireConfigFile: false,
        babelOptions: { presets: ['@babel/preset-typescript'] },
      },
      ecmaVersion: 2020,
      sourceType: 'module',
      globals: globals.node,
    },
    rules: {
      ...js.configs.recommended.rules,
      'no-undef': 'off',
      'no-unused-vars': 'off',
    },
  },
]
