---
capa: fuente
tipo: goal-doc
estado: abierto
fecha: 2026-08-19
areas: [proceso]
fuente: goals/runtime-budgets-al-ci/goal.md
resumen: Desbloquear el gate runtime-budgets, hoy el único blocked de los nueve de closeout-evidence.json, y dar a full-app-flow procedencia de una corrida real de…
---

<!-- cas:cita-textual — registro del frente: cita salidas y comandos tal como se midieron -->
# Frente: runtime-budgets-al-ci

## Fase del plan
Plan: docs/superpowers/plans/2026-08-19-runtime-budgets-al-ci.md
Fase: Fase 1
Sha verificado: c23b1c6a
Presupuesto: ?

## Objetivo
Desbloquear el gate `runtime-budgets`, hoy el único `blocked` de los nueve de
`closeout-evidence.json`, y dar a `full-app-flow` procedencia de una corrida real de GitHub
Actions en vez del recibo local que lleva hoy. Es andamio declarado para poder medir DS-F0, no
inversión: DS-F3 reemplaza estos gates enteros.

## Condición de hecho
<!-- qué comando, con qué salida, prueba que el frente terminó -->
Verificación: npm run test:design-system:static

## Posture
- No tocar `public/css/programa-general.css`. El rojo histórico ya no lo causa ese archivo.
- No ampliar a los otros ocho gates aunque estén a mano.
- No regenerar ningún baseline ni golden sin autorización explícita del usuario.
- Sin dependencias nuevas.

Ampliación autorizada por el usuario en el chat (2026-08-19): arreglar la regresión de CI de
`docker-compose.override.yml`, y editar `.github/workflows/design-system.yml`, que `gate.yaml`
protege. Sin esa ampliación el frente no puede verificarse: el job donde vive el gate no arranca.

## Leer primero
- `docs/design-system/closeout-evidence.json` — los nueve gates y su procedencia.
- `decisiones/gates-al-ci-ejecutor.md` — D-1 a D-7, lo ya resuelto y lo medido.
- `.github/workflows/design-system.yml` — el `needs:` entre static y runtime.
- `AGENTS.md` §Verificación y §Publicación.

## Archivos declarados
docs/design-system/closeout-evidence.json,docs/design-system/runtime-baseline*,.github/workflows/design-system.yml,tests/browser/**

## Contención
- `.github/workflows/design-system.yml` · sin commits ajenos hoy · nadie más lo declara.
- `docker-compose.override.yml` · 2 commits el 2026-08-18 (86fdca41, 81debea8) · NO se toca.
- `docs/design-system/closeout-evidence.json` · pendiente, sin tocar todavía.

## Cadena de herramientas
<!-- ids del arsenal, máx 8, una línea de porqué cada uno -->

## Medido

### Fase 1 — por qué `runtime-budgets` está `blocked`

Reproducido, no supuesto. Sobre `6abe2436`:

```
$ npm run test:runtime-budget:check
ENOENT: no such file or directory, open '.../test-output/design-system-runtime-budget.json'
RC=1
```

Idéntico al `outputTail` del recibo `docs/design-system/evidence/runtime-budgets.json` (2026-08-11,
sobre `2624ea2d`). **La causa no es el presupuesto ni el baseline:** el recibo se tomó ejecutando
solo la mitad `check`, sin la mitad `measure` que produce el artefacto de medición.

Único dato caducado del recibo: apuntaba a `runtime-baseline-0.3.3.json` y hoy `package.json`
apunta a `0.3.5`. El modo de fallo es el mismo.

**Y el gate no puede ponerse verde fuera del CI, por diseño.** Comprobado pasándole el artefacto
aprobado 0.3.5 como medición:

```
Runtime budget aggregation: CI_GIT_SHA must match the current clean worktree
RC=1
```

Exige `CI_RUN_ID` / `CI_GIT_SHA` / `CI_WORKTREE_FINGERPRINT` de la corrida en curso. Fabricarlos es
justo lo que ese contrato existe para impedir.

### Lo que impedía llegar al CI, y ya no

1. **Facturación de GitHub Actions.** Bloqueó desde el 2026-08-17T01:11Z hasta algún momento entre
   las 20:01 y las 20:59 del 2026-08-18. **Ya levantado**: hay corridas de 2m+ del 2026-08-19.
   Última corrida bloqueada: 32179952231. Primera que ya corre: 32185356507.
2. **El job estático, rojo por el bind de Docker.** Corrida 32203139260 sobre `720b27b9`:
   `node-tests` con `# fail 4` y ocho apariciones de `Failed opening required 'src/...'`.
   `design-system-runtime` en `0s` por su `needs:`. Arreglado en este frente.

Reproducción y cura del bind, en un proyecto de compose aparte para no tocar el stack compartido:

```
# con la ruta inexistente (lo que ve un runner)
PHP Fatal error: Uncaught Error: Failed opening required 'src/View/Components/DesignSystemHeadComponent.php'

# con LPS_CODE_ROOT apuntando al checkout
<script src="/js/modules/aia_ui/theme-bootstrap.js?v=1787099244"></script>
@layer reset, vendor, theme, base, layout, components, utilities, module, legacy-overrides;
```

### Verificación tras integrar `origin/main`

```
$ npm run test:design-system:static
RC=0
  ✔ entrypoint-partition  ✔ unlayered-delivery  ✔ bi-utilities  ✔ table-contract
  ✔ node-tests  ✔ contracts  ✔ consumer-contract  ✔ audit
```

Sobre **4e6d63e3**, que es el sha a publicar.

## Pendientes

- Fase 2: envolver el `check` de runtime-budgets en `gate-receipt.mjs` para que la corrida deje
  recibo. Redactado y sin aplicar; **no se entrega sin una corrida que lo vea fallar**.
- Fase 3: bajar de una corrida verde la procedencia real y escribirla en `closeout-evidence.json`.
  Depende de que el CI llegue a verde, que es lo que este cierre habilita.

## Fase 1 — por qué está `blocked`, reproducido y no recordado

**Medido el 2026-08-19 sobre `ab2c34f1`.** El plan exigía ejecutar el gate a mano y ver el fallo
real. Hecho:

```
npm run test:runtime-budget:check
> node scripts/design-system-runtime-budget.mjs check docs/design-system/runtime-baseline-0.3.5.json test-output/design-system-runtime-budget.json
ENOENT: no such file or directory, open '.../test-output/design-system-runtime-budget.json'
RC=1
```

**No es un baseline caducado.** Falta el archivo de medición, así que se ejecutó su productor:

```
npm run test:runtime-budget:measure
Error: Runtime budget aggregation: CI_GIT_SHA must match the current clean worktree
  at scripts/design-system-runtime-budget-provenance.mjs:36
```

**La causa, entonces, es de diseño y no un defecto:** `readCurrentRuntimeContext`
(`scripts/design-system-runtime-budget-provenance.mjs:125-143`) exige que `CI_RUN_ID`,
`CI_GIT_SHA`, `CI_WORKTREE_FINGERPRINT` y `CI_FIXTURE_SHA256` coincidan con un worktree limpio, y
`CI_RUN_ID` va validado contra una expresión regular. **La medición no se puede producir fuera de
GitHub Actions, y eso es intencional**: el recibo tiene que ser objetivo.

**Consecuencia para el plan: la Fase 2 —«arreglar la causa con el cambio mínimo»— no tiene nada que
arreglar.** No hay causa local. La única vía es la Fase 3: una corrida real de Actions.

### Y ahí apareció el problema de verdad

`gh run list --workflow=design-system.yml --branch=main --limit 40` devuelve
**23 `failure` y 17 `cancelled`. Ni una verde.** El CI de este repositorio no pasa desde hace
decenas de corridas, y no se había notado porque la suite local sí pasa.

La causa era **una sola aserción**: `tests/browser/full-app-flow.spec.mjs:92` exigía en móvil que el
`body` reservara sitio para el carril, justo lo que la spec del menú flotante derogó el 2026-08-14.
Arreglado en `ab2c34f1`, con la comprobación previa de que no escondía una regresión.

**Este frente queda a la espera de esa corrida**: si CI se pone verde, la Fase 3 puede tomar la
procedencia de `full-app-flow` y de `runtime-budgets` de una corrida real, que es lo único que le
falta para cerrar. Y con ella cierra también [[goals/gates-al-ci/goal]], que depende del mismo gate.


## Archivos de este goal

- [[docs/superpowers/specs/2026-08-19-runtime-budgets-al-ci-design|Spec]]
- [[docs/superpowers/plans/2026-08-19-runtime-budgets-al-ci|Plan]]
- [[memoria/goals/estado|Estado de los goals]]

## Publicaciones

- **c23b1c6a** · 2026-08-19 · `bash scripts/publicar.sh` → `66012929..c23b1c6a  HEAD -> main`, RC=0.
  Verificado en la misma invocación: `design-system:static` RC=0, `contrato piloto PG` RC=0,
  `wiki` RC=1 (avisa, no bloquea: 1 hallazgo en 156 páginas). `git rev-parse origin/main` ==
  `git rev-parse HEAD` == `c23b1c6a79d1642761621520ad3fffbb5179e6a0`.
- Vía autorizada por el usuario tras ~8 h sin respuesta a la consulta: reapuntar el contenedor
  compartido al worktree, publicar y devolverlo a la raíz. Devuelto y comprobado.

## Lo que la corrida de CI enseñó, que es el objetivo real del frente

Corrida **32257786649** sobre `c23b1c6a`: **`design-system-static` en verde** — el primero desde el
2026-08-14. El arreglo funciona. Su job de runtime lo canceló la concurrencia (otra sesión publicó
detrás y `cancel-in-progress` mata la anterior).

Corrida **32258012434**, que arrastra el mismo arreglo: `design-system-static` verde otra vez, y
**`design-system-runtime` ARRANCA por primera vez** — antes ni empezaba, por el `needs:`. Llega
hasta el paso 12 de 20:

```
10 Enforce PHPStan baseline                        => success
11 Enforce PHPStan level 6 on the PDC module       => success
12 Correr la suite PHP completa que el CI puede honrar => failure
   104 test(s) descubiertos, 74 seleccionados, 30 omitidos por nivel
   === 74 corridos: 73 pasaron, 1 fallaron
   FAIL: baseline-drift: contractual finish moved with latest reprogramming
19 Measure runtime budgets                         => skipped
20 Check runtime budgets against the baseline      => skipped
```

**Bloqueo nuevo, ajeno a este frente y sin diagnosticar:** `tests/test_bi_programa_general_chart_values.php`
falla con `baseline-drift`. Como GitHub salta todos los pasos posteriores a uno fallido,
`runtime-budgets` sigue sin poder medirse — ahora por otra razón, cuatro pasos más adelante.

**No lo toco.** La palabra `baseline` en el nombre lo pone en la lista de bloqueo incondicional, y
además está fuera del alcance de este frente. Escalado a la coordinadora.

## Riesgo del datatables, casi descartado (por lectura, no por corrida)

No queda en el árbol ninguna referencia con prefijo `/public` a un adaptador:

```
public/css/design-system/vendor-datatables-legacy.css:6:
  @import url("/css/design-system/adapters/datatables.css?v=1.1.0") layer(components);
public/css/aia-design-system.css:33:
  @import url("/css/design-system/adapters/shell-sidebar.css?v=1.1.0");
```

Los dos adaptadores que reventaron el gate en `31566518358` se importan hoy con la ruta que el
baseline 0.3.5 declara. **Límite:** el gate mide URLs servidas en runtime, no el árbol. Probable, no
demostrado.
