---
tipo: referencia
estado: vigente
verificado: 2026-08-18
fecha: 2026-08-18
areas: [proceso, deploy, qa]
fuente: scripts/publicar.sh, AGENTS.md §Publicación, D-COORD-3
resumen: publicar es invocar `bash scripts/publicar.sh` desde el 2026-08-18; verifica, lee cada código de salida en su propia línea y deniega con RC=1, y al hacerlo obligatorio se le encontraron dos defectos que lo hacían avalar con evidencia de otro árbol
---
**Desde el 2026-08-18, publicar en `main` es invocar `bash scripts/publicar.sh`.** Decisión del
usuario (`D-COORD-3`), declarada en `AGENTS.md` §Publicación paso 6.

```bash
bash scripts/publicar.sh                  # verifica y publica
bash scripts/publicar.sh --solo-verificar # solo verifica
```

**Qué hace y por qué existe.** El gate de cierre vivía solo en la prosa de `AGENTS.md`, y en tres
jornadas seguidas —10, 11 y 12 de agosto— tres sesiones distintas encadenaron la verificación al
comando de publicar y empujaron sin leer el resultado. Las tres veces salió benigno por suerte, no por
procedimiento. Un gate solo gobierna si puede **impedir** la publicación: este lee cada código de
salida **en su propia línea, sin tubería** —tras un pipe `$?` es del último tramo y vale 0 pase lo que
pase, y en zsh `PIPESTATUS` no es lo que uno cree—, comprueba que no haya commits entrantes ni árbol
sucio, y solo entonces publica con `HEAD:main`.

**Qué bloquea y qué solo avisa:**

| Comprobación | ¿Bloquea? |
|---|---|
| `npm run test:design-system:static` | sí |
| `node tests/test_programa_general_sprint_contract.mjs` | sí |
| `npm run test:wiki` | **no**, avisa |

La wiki avisa a propósito: su hallazgo típico es la alarma de veracidad —un contador de commits—, que
pide trabajo pero no dice que lo que vas a publicar esté mal. Bloquear con ella enseñaría a saltarse el
script, y un gate que se ignora enseña a ignorar los demás.

**Lo que NO hace, deliberadamente:** no instala nada en `.git/hooks` ni toca `core.hooksPath`. Eso
cambiaría el entorno de quien clone el repo sin haberlo pedido, y se descartó al decidir la
obligatoriedad. Es un comando que se invoca; por eso la obligación vive en `AGENTS.md`.

## Los dos defectos que tenía al declararlo obligatorio

Ambos medidos y arreglados el 2026-08-18, antes de obligar a usarlo. Valen como advertencia de que un
gate hay que auditarlo con el mismo rigor que lo que vigila.

1. **Su última línea era `git push origin main`** — exactamente lo que el paso 6 de `AGENTS.md`
   prohíbe desde ese mismo día, porque publica a donde apunte `main` en el instante del push y `main`
   es estado compartido. El script que existía para hacer cumplir la regla la incumplía en su propio
   código. Hoy publica `HEAD:main`.

2. **Verificaba el árbol de otra sesión, y daba verde.** Es el lado peligroso de
   [[suite-estatica-miente-en-worktree-secundario]]: `docker-compose.yml` fija
   `name: last-planner-aia` y `docker-compose.override.yml` monta desde
   `${LPS_CODE_ROOT:-<checkout principal>}`, así que desde un worktree las comprobaciones que ejecutan
   PHP en el contenedor medían el árbol compartido. **Medido:** el script dio los tres verdes desde un
   worktree en `06627082` mientras el contenedor servía el principal en `081a33c8` — commits distintos.
   Un gate obligatorio que avala con evidencia ajena es peor que no tener gate. Hoy exporta
   `LPS_CODE_ROOT="$PWD"` y un `COMPOSE_PROJECT_NAME` propio derivado del SHA; comprobado que tras el
   arreglo el conteo del audit responde al árbol local (4.078 → 4.081 al mutarlo).

**Entregado con su mutación, ejecutada**, como exige la regla del repo: con el árbol sucio →
`DENEGADO` y `RC=1`, leído sin tubería. (Leerlo *con* tubería devolvió 0 y por un momento pareció que
el gate no denegaba — el mismo error que el script previene, cometido al medirlo.)

## Laguna conocida, no cerrada

La mutación que se usó para la prueba —un hex crudo con `!important` en un adaptador— **no tumbó el
gate estático**. `docs/design-system/audit-baseline.json` tolera `totalViolations: 7161` y el código va
por 4.081: quedan unas **3.080 violaciones nuevas de margen** antes de que el audit bloquee. La
baseline se congeló muy por encima del estado real, así que hoy el audit no impide introducir hex
crudos nuevos. Merece frente propio; no se toca desde aquí porque bajar una baseline es cambio de
contrato.

Ver también [[verificas-un-arbol-y-publicas-otro]] y
[[el-codigo-de-salida-se-pierde-en-la-tuberia]], que son las dos formas del fallo que este script
existe para impedir.
