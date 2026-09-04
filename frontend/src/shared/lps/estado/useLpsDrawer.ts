import { useContext } from 'react';
import { ContextoLpsDrawer, type LpsDrawerApi } from './LpsDrawerProvider';

/**
 * Consumidor único del `LpsDrawerProvider` (AC-004). Lanza si se llama fuera del árbol del
 * provider — un adapter de módulo mal compuesto falla ruidoso, no en silencio con `undefined`.
 */
export function useLpsDrawer(): LpsDrawerApi {
  const valor = useContext(ContextoLpsDrawer);
  if (!valor) {
    throw new Error('useLpsDrawer() se llamó fuera de <LpsDrawerProvider>. Compón el provider una sola vez en AppShell.');
  }
  return valor;
}
