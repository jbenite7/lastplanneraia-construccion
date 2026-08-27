import { construirTitular } from '../lib/titulares'
import type { ResumenLookaheadIntermedia } from '../lib/titulares'

// Titular narrativo de la hoja Intermedia (ct-app, etapa piloto, Task 7 ensamblaje — posición 2
// del lienzo, CT-8.3): «qué está pasando con el lookahead y por qué».
//
// PRESENTACIONAL Y PURO en el sentido de props: recibe un ResumenLookaheadIntermedia ya agregado
// y pinta `construirTitular(resumen).texto` tal cual — no reimplementa la redacción ni el orden
// de prioridad de las seis condiciones, eso ya está fijado y probado en titulares.test.ts.

interface TitularProps {
  resumen: ResumenLookaheadIntermedia
}

export function Titular({ resumen }: TitularProps) {
  const { texto } = construirTitular(resumen)
  return <p>{texto}</p>
}
