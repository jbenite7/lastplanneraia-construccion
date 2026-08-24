<?php

declare(strict_types=1);

// @requiere: puro

// Comprueba de verdad y anuncia su verde en espanol, que es como reporta la mayoria
// de esta suite. Antes del 2026-08-24 el detector lo marcaba SOSPECHOSO porque buscaba
// `pass` y aqui dice `PASA:`. No es un test mudo: si la comprobacion falla, sale 1.
if (1 + 1 !== 2) {
    echo "FALLA: la aritmetica se rompio\n";
    exit(1);
}

echo "PASA: la comprobacion se hizo y se anuncia en espanol\n";
exit(0);
