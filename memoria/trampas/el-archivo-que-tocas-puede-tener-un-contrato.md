---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-08-10
areas: [qa, proceso]
fuente: sesion
resumen: "Una aserción del CI que exigía nombrar un comando puso rojo el gate; D-CI-1 resolvió el caso midiendo ejecución. Busca contratos antes de editar."
---
El 2026-08-10, el frente del runner de tests PHP sustituyó en
`.github/workflows/design-system.yml` los tres tests que el CI listaba a mano por una llamada al
runner, que ejecuta 71. La cobertura subió de 3 a 71 y el gate `node-tests` se puso **rojo en
`main`**.

El registro de `D-CI-1` (`docs/decisiones-pendientes.md:138-169`) documenta el defecto histórico:
el contrato exigía que el workflow incluyera literalmente el comando del test, aunque el runner ya
lo ejecutaba. Esa aserción de cadena dejó de ser la condición vigente al resolverse D-CI-1.

**Estado actual, verificado en el código:** desde 2026-08-11 el contrato comprueba que el workflow
invoca el runner PHP y que el nivel máximo del CI incluye la etiqueta `@requiere` que declara
`test_global_table_safety.php` (`tests/design-system/visual-ci-contract.test.mjs:306-355`). La
prueba del runner cubre además que los niveles acumulativos no omitan niveles inferiores. La decisión
se ejecutó y se verificó sobre `e66e7672`, según `docs/decisiones-pendientes.md:1691`; el paso
explícito duplicado se retiró. El caso ya no predice que una futura reorganización equivalente vaya
a fallar por perder una cadena literal.

**Why:** quien hizo el cambio verificó lo suyo a conciencia —runner, ambos comandos del CI, códigos
de salida— y aun así publicó en rojo, porque el contrato que vigilaba el archivo editado no estaba
en su lista. La regla que faltaba no es «corre todos los gates por si acaso», que es caro y no
enseña nada: es **antes de editar un archivo, averigua si algo lo aserta**. **How to apply:** un
`grep` del nombre del archivo por `tests/` y `scripts/` antes de tocarlo lo dice en segundos:

```bash
grep -rl 'design-system\.yml' tests/ scripts/
```

Corolario medido el mismo día: la suite estática **no se puede leer con el árbol sucio**. El primer
intento de arreglo salió rojo en `node-tests` y en `contracts`, los dos con
`activation: worktree and index must be clean`. No eran dos fallos sino uno —el arreglo sin
commitear— disfrazado de dos. Commitea y vuelve a medir antes de diagnosticar. Pariente de
[[el-codigo-de-salida-se-pierde-en-la-tuberia]] y de
[[un-verde-solo-vale-para-el-arbol-donde-se-midio]]. Relacionado: [[qa-y-gates]],
[[branch-preexisting-red-gates]].

---

## Un segundo caso el mismo día, para que se vea que no es del CI

`docs/design-system/manifests/goal-provenance.json` fija por `sha256` el `goal.md`, el `facts.md` y
el `plan.md` del goal de gobernanza del design system. Una tarea editó los dos primeros **con
autorización del usuario** y sin actualizar el manifiesto: la suite estática cayó a 6/8 con
`goal provenance: hash mismatch`, y **ningún subagente lo detectó** — lo encontró la verificación
final del coordinador.

Mismo patrón, otro contrato, otro directorio. No es una particularidad del workflow de CI.

## La comprobación, en un comando

```bash
grep -rn "<nombre-del-archivo>" tests/ scripts/ docs/design-system/ | grep -v node_modules
```

Si aparece dentro de una aserción, un manifiesto o un `sha256`, **ese contrato entra en tu condición
de hecho** aunque tu tarea no lo mencione.

## Contratos vigilantes conocidos

| Contrato | Qué fija |
|---|---|
| `tests/design-system/visual-ci-contract.test.mjs` | Contenido literal de `.github/workflows/ci.yml` (renombrado desde `design-system.yml` el 2026-08-24) y del compose |
| `docs/design-system/manifests/goal-provenance.json` | `sha256` de `goal.md`, `facts.md` y `plan.md` del goal de gobernanza |
| `docs/design-system/manifests/*.json` | `sha256`, viewport y dimensiones de cada golden |
| `docs/design-system/evidence-exceptions.json` | Cada excepción debe **estar en uso**, o falla |
| `docs/rbac-parity-exceptions.json` | Ídem para las divergencias RBAC declaradas |

**La lista no es cerrada.** La comprobación es el `grep` de arriba, no esta tabla: cualquiera puede
añadir un contrato nuevo mañana.

