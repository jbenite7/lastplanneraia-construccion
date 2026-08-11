---
tipo: trampa
estado: vigente
fecha: 2026-08-10
areas: [qa, design-system, proceso]
fuente: sesión del frente «runner de tests PHP», 2026-08-10; tests/design-system/visual-ci-contract.test.mjs, docs/design-system/manifests/goal-provenance.json
resumen: "Varios archivos de este repo están vigilados por un contrato que los fija por contenido o por hash; editarlos sin mirar quién los vigila publica un gate en rojo sin enterarse"
---
# Mira qué contrato vigila el archivo que tocas

Este repositorio tiene contratos que **fijan otros archivos por contenido o por hash**. Editar uno
de esos archivos sin saber que está vigilado pone un gate en rojo, y quien lo edita no se entera
porque su propia condición de hecho no incluía ese gate.

Pasó **dos veces el 2026-08-10**, a dos sesiones distintas:

- Una tocó `.github/workflows/design-system.yml` para sustituir tres tests listados a mano por el
  runner —una mejora: de 3 tests a 71—. Pero `tests/design-system/visual-ci-contract.test.mjs:156`
  exige que ese workflow contenga literalmente `php tests/test_global_table_safety.php`. Publicó con
  la suite estática en 7/8 sin verlo.
- Otra editó `goals/design-system-nucleo-gobernanza/goal.md` y `facts.md` con autorización del
  usuario. `docs/design-system/manifests/goal-provenance.json` fija los dos por `sha256`. La suite
  cayó a 6/8 y **ningún subagente lo detectó**: lo encontró la verificación final del coordinador.

## La regla que sale de aquí

No es «correr más gates por si acaso» —eso es caro y no enseña nada—. Es:

> **Antes de editar un archivo, comprueba si algún contrato lo vigila.**

```bash
grep -rn "<nombre-del-archivo>" tests/ scripts/ docs/design-system/ | grep -v node_modules
```

Si aparece dentro de una aserción, un manifiesto o un `sha256`, ese contrato entra en tu condición
de hecho aunque tu tarea no lo mencione.

## Contratos vigilantes conocidos

| Contrato | Qué fija |
|---|---|
| `tests/design-system/visual-ci-contract.test.mjs` | Contenido literal de `.github/workflows/design-system.yml` y del compose |
| `docs/design-system/manifests/goal-provenance.json` | `sha256` de `goal.md`, `facts.md` y `plan.md` del goal de gobernanza |
| `docs/design-system/manifests/*.json` | `sha256` y dimensiones de cada golden |
| `docs/design-system/evidence-exceptions.json` | Cada excepción debe **estar en uso**, o falla |
| `docs/rbac-parity-exceptions.json` | Ídem para las divergencias RBAC |

La lista no es cerrada: el `grep` de arriba es la comprobación, no esta tabla.

## Un corolario que costó una vuelta

Con el árbol sucio, la suite estática falla en `node-tests` **y** en `contracts`, los dos con
`activation: worktree and index must be clean`. Parecen dos fallos y es uno: el árbol sin commitear.
Commitea primero y vuelve a medir antes de diagnosticar. Es de la misma familia que
[[el-codigo-de-salida-se-pierde-en-la-tuberia]]: **una medición tomada en el estado equivocado no
dice lo que parece decir.**

Relacionado: [[un-verde-solo-vale-para-el-arbol-donde-se-midio]],
[[gate-solo-cuenta-elementos-no-los-lee]].
