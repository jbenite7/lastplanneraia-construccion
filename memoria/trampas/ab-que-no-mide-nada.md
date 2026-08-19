---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-08-19
areas: [proceso, qa]
tags: [trampa]
fuente: frente migracion-estados, 2026-08-19
resumen: "Un A/B donde A y B son el mismo código siempre dice «no es mío»: es una máquina de confirmar lo que uno espera"
---

# El A/B que no mide nada

**El síntoma.** Una prueba falla, sospechas que no es tuya, y haces lo correcto: quitas tus cambios,
vuelves a correr, y falla igual. Concluyes que es preexistente. **El fallo se ve idéntico en los dos
lados… porque los dos lados son el mismo código.**

El delator, si usaste `git stash`, aparece al final y es fácil de pasar por alto:

```
$ git stash pop
No stash entries found.
```

**Lo que parece.** Una comparación A/B limpia que exonera tu trabajo.

**Lo que es.** `git stash push <archivos>` **solo guarda cambios sin commitear**. Si ya commiteaste
—y en un frente que avanza por tareas con commit al final de cada una, casi siempre ya commiteaste—
no guarda nada, no avisa en el momento, y la corrida «sin tus cambios» ejecuta exactamente tu
código. El resultado no es un falso negativo al azar: **siempre confirma la hipótesis que querías
confirmar**, que es la peor clase de error de medición.

**Cómo se sale.** Revertir de verdad, y **comprobar que la reversión ocurrió** antes de medir:

```bash
git checkout <sha-del-commit-que-lo-introdujo>~1 -- <archivos>
grep -c '<lo-que-introduje>' <archivos>      # tiene que dar 0
# ... correr la prueba ...
git checkout HEAD -- <archivos>
grep -c '<lo-que-introduje>' <archivos>      # tiene que volver a dar 1
git status --porcelain                        # y el arbol tiene que quedar limpio
```

El `grep` de las dos direcciones es lo que convierte esto en una medición: sin él, estás confiando
en que el comando hizo lo que crees.

**Cuánto costó.** El 2026-08-19, casi nada — se cazó en el mismo turno— pero pudo costar el frente
entero: la conclusión que iba a reportar («el fallo nuevo no es mío») resultó ser **cierta**, y por
eso mismo el error habría pasado sin dejar rastro. Una medición rota que da la respuesta correcta es
peor que una que da la equivocada: no hay nada que la delate después.

Relacionadas: [[contenedor-compartido-durante-verificacion]]
