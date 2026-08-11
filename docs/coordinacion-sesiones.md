# Coordinación entre sesiones

Cómo trabajan juntas varias sesiones de Claude Code sobre este repositorio. Decidido por el usuario
el 2026-08-10, al abrir el programa de cierre de pendientes
(`docs/superpowers/specs/2026-08-10-programa-cierre-pendientes-design.md`).

## El reparto

Hay **una sesión coordinadora** y **sesiones de ejecución**. El reparto lo declara el usuario, no lo
reclama nadie: ninguna sesión pasa a ser «de ejecución» porque otra lo diga.

| Papel | Qué hace |
|---|---|
| **Coordinadora** | Audita, revisa el trabajo antes de que se publique, y es **la única que le pregunta al usuario**. No implementa frentes. |
| **De ejecución** | Implementa **un frente**, en su propio worktree. Consulta hacia arriba toda decisión que no le toque. |

## Una sesión por frente, estrictamente en orden

El gate de cierre de frente es bloqueante (`AGENTS.md` §Publicación): **no se abre un frente nuevo
mientras el anterior no esté publicado en `main`**. Eso serializa el programa a propósito.

Por tanto: **una sola sesión de ejecución activa a la vez**. Cuando cierra su frente y publica, la
coordinadora abre la siguiente. Varias sesiones existen, pero por turnos, no en paralelo.

Cada sesión de ejecución trabaja en **su propio worktree** (`superpowers:using-git-worktrees`), no
sobre el directorio principal. El 2026-08-10 hubo que integrar `origin/main` tres veces en una
jornada por trabajar todos sobre el mismo árbol, y una vez el trabajo de una sesión sobrescribió el
de otra sin aviso.

## Cómo se consulta una decisión del usuario

**Ninguna sesión de ejecución le pregunta al usuario directamente.** Las preguntas se mandan a la
coordinadora, que las hace con la herramienta nativa de grilleo —una a una, en lenguaje simple, con
recomendación y señalando la opción segura— y devuelve la respuesta.

```
mcp__ccd_session_mgmt__send_message  →  session_id de la coordinadora
```

**Cómo identificar a la coordinadora:** llama a `mcp__ccd_session_mgmt__list_sessions` y busca la
sesión que cumple las tres cosas a la vez:

1. `cwd` es exactamente `/Volumes/Crucial X6/Developer/lps-aia` — la **raíz** del repo, no un
   worktree de `.claude/worktrees/`;
2. `isRunning: true`;
3. no es la tuya.

Si encaja exactamente una, esa es. **Si encajan cero o varias, pregúntale al usuario en el chat cuál
es** en vez de adivinar: mandar una decisión a la sesión equivocada es peor que no mandarla.

### Qué se consulta y qué no

**Se consulta** lo que cambia alcance, toca un contrato o un baseline, borra algo, altera lo que una
prueba mide, se desvía del plan, o elige entre caminos con consecuencias distintas.

**No se consulta** lo mecánico: nombres, orden de pasos, o corregir un dato equivocado del propio
encargo. Eso se resuelve y se sigue.

**Anotar una decisión como «duda» en un informe no es consultarla.** El 2026-08-10 un implementador
vio que su cambio alteraba lo que medían tres pruebas, lo escribió como duda y siguió adelante; hubo
que devolverle la tarea. Si cambia algo de lo anterior, se pregunta **antes** de actuar.

### Las decisiones se acumulan; no se interrumpe al usuario una a una

Decidido por el usuario el 2026-08-10, después de las primeras horas del programa.

**Una sesión de ejecución nunca para.** Cuando encuentra algo que necesita criterio del usuario:

1. **Lo anota en la cola** — `docs/decisiones-pendientes.md`, con el formato que esa página define:
   qué se decide, qué se midió, las opciones reales y la recomendación de quien pregunta.
2. **Se salta ese hallazgo** y sigue con los demás. No lo toca, no lo decide con un supuesto, no lo
   deja a medias.
3. **Sigue hasta terminar su frente.** No espera respuesta, no pregunta al usuario, no se detiene.

La coordinadora presenta **la cola entera al usuario al cerrar el frente**, en una sola tanda de
grilleo. Lo que quedó saltado se retoma con sus respuestas, en una segunda pasada.

**Por qué así, y no avanzando con un supuesto conservador:** el 2026-08-10 un implementador vio que
su cambio alteraba lo que medían tres pruebas, eligió lo que le pareció más seguro, lo anotó como
duda y siguió. Hubo que devolverle la tarea. Saltar deja el hallazgo intacto y barato de retomar;
suponer deja trabajo que quizá haya que deshacer.

**El coste, dicho claro:** algunos hallazgos se cierran en una segunda pasada en vez de la primera.
Se acepta a cambio de que ninguna sesión se quede parada y de que el usuario decida una vez, con
todo delante, en vez de a cachos.

## Qué audita la coordinadora

1. **Que los gates sigan verdes** — suite estática del design system, PHPStan, paridad RBAC, lint de
   la wiki y las pruebas PHP. El 2026-08-10 dos regresiones llegaron a `main` sin que sus autores las
   vieran; las dos aparecieron al verificar **después de integrar**.
2. **El trabajo de las sesiones de ejecución antes de que se publique** — si contradice una decisión
   del usuario, rompe un contrato o repite un defecto ya cerrado.
3. **Que el backlog y el mapa de estado no mientan** — `docs/EXPERIMENTS.md`, los `goal.md` y
   `memoria/`. Es el problema con el que arrancó todo el programa: varias cosas figuraban como hechas
   y estaban rotas, y dos llevaban meses bloqueadas esperando algo imposible.

## Reglas heredadas que siguen valiendo

- **Todo gate se entrega con una mutación que lo pone rojo, ejecutada.** No basta con que pase: hay
  que ver que sabe fallar.
- **Todo paso que quite algo de una lista mide qué cobertura pierde**, no solo qué gana.
- **Verificar después de integrar, no antes.** Traer trabajo ajeno puede romper un verde propio sin
  tocar el diff de uno.
- **Nada se declara hecho sin salida real de comando** de esa sesión.
