---
capa: fuente
tipo: reporte
estado: vigente
fecha: 2026-08-18
areas: [proceso]
tags: [pendiente]
fuente: decisiones/gates-al-ci-ejecutor.md
resumen: Decisiones encoladas — frente gates-al-ci (Fase F-AB), rol ejecutor
---

# Decisiones encoladas — frente `gates-al-ci` (Fase F-AB), rol ejecutor

## D-1 · ESCALADA (bloqueante, ya enviada) — el static del CI lleva en rojo desde el 2026-07-17

Medido sobre `4dc4631a`. `design-system-static` falla en
`tests/test_programa_general_sprint_contract.mjs:41` («PG no debe usar important local») por los
tres `!important` de `public/css/programa-general.css:621-623`, introducidos en `20f08dd2`
(2026-08-07). `design-system-runtime` lleva `needs: design-system-static`, así que el job donde
F-AB debía enchufar los dos gates nunca se ejecuta.

Enviada a la coordinadora el 2026-08-12. **RESUELTA:** decisión del usuario `D-GAC-1` — la
aserción pasa a permitir `!important` dentro de `@layer` y a prohibirlo fuera; el CSS no se toca.
Reparación = fase **F-0** nueva del plan (commit `6d82f723` del `main` local), que sale como chip
aparte. Este frente queda **pausado**, no descartado.

## D-2 · Observación, no bloqueante — la cola de decisiones no está versionada

`git check-ignore -v` sobre `4dc4631a`: `goals/*` (`.gitignore:62`), `decisiones/`
(`.gitignore:404`) y `.claude/` (`.gitignore:219`) están ignorados en este repo. La skill
`coordinating-agent-sessions` exige lo contrario, precisamente porque el 2026-08-11 una cola sin
versionar perdió doce hallazgos sin dejar diff. No lo cambio: `.gitignore` no es archivo de este
frente. Reportado a la coordinadora en la misma escalada.

---

## D-3 · ESCALADA (bloqueante, enviada) — el gate de push impide correr la mutación en CI

Sesión 37818e4a, 2026-08-12, sobre `c014874c`. La Tarea 4 del plan dice «empuja y observa»: la
mutación necesita una corrida de Actions para tener procedencia. Al empujar la rama desechable:

```
git push -u origin mutacion/f-ab-restauracion
-> Falta el visto de la coordinadora para el frente 'gates-al-ci'. Pide el visto ...
   Si es una emergencia real, antepón CAS_SKIP_GATE=1 al comando y quedará anotado.
```

No es una emergencia y **no me autoconcedo el salto**: usar `CAS_SKIP_GATE=1` por conveniencia es
exactamente la racionalización que el gate existe para impedir. Pedido a la coordinadora: **o un
visto para la rama de mutación, o autorización explícita de `CAS_SKIP_GATE=1` limitada a ramas
distintas de `main`**.

**Lo que hice mientras tanto, y su alcance:** monté el runtime aislado **en local**
(`docker-compose.ci.yml`, puerto 18081) y ejecuté allí el ciclo rojo→verde completo. Es **salida
real de comando de esta sesión**, pero **no es procedencia de CI**, y no la presento como tal.

**Lo que sigue faltando exactamente:** una corrida de Actions con la mutación puesta. Ni una más.
La mitad verde con procedencia de CI ya existe (`31566518358`, paso `Enforce full-app-flow gate =>
success` sobre `c014874c`), así que el coste total del desbloqueo es **una** corrida.

## D-4 · Encolada, no bloqueante — el efecto secundario que el cierre parcial dejó anotado

`.github/workflows/design-system.yml`: con los dos gates puestos **antes** de «Run laboratory
gates», un rojo de `runtime-budgets` hace que GitHub **salte** el paso visual. Medido en la corrida
`31566518358`: pasos 18 a 22 en `skipped`. No oculta nada nuevo —ese paso ya fallaba por
`D-GAC-4`— pero mientras `runtime-budgets` siga rojo, el carril visual deja de verse en cada
corrida, y con él el paso `Run Programa General persistence and RBAC gate` (`:22`), que **no**
depende de `D-GAC-4` y sí se está perdiendo.

Remedio nombrado y **no comprobado por mí**: `if: always()` en esos pasos. Lo dejo dicho como
hipótesis, no como diagnóstico: no lo he medido. Es un archivo declarado de este frente, pero
cambiar el orden o la condición de ejecución de pasos ajenos al mío se desvía del plan → se
consulta, no se decide.

## D-5 · Aporte medido a `D-GAC-5` (abierta, del usuario) — la tercera diferencia SÍ es real, y el medidor no tiene la culpa

Sesión 37818e4a, 2026-08-12, sobre `0e45ba1d`. `D-GAC-5` dejó una sub-pregunta con instrucción
explícita de cómo resolverla **sin tocar el baseline**: «(3) se verifica leyendo cómo compone esa
ruta el medidor». Hecho. **Refuta la hipótesis**, y por eso lo subo en vez de anotarlo y seguir.

**Qué hace el medidor, leído:** `tests/browser/design-system-runtime-budget.mjs:54` guarda
`path: url.pathname` — **la ruta tal cual la pidió el navegador**, sin normalizar. `:156` filtra con
`assetPath.includes('/css/design-system/adapters/')`, subcadena sin frontera. `/public/css/…`
**contiene** esa subcadena, así que entra legítimamente. **El medidor no compone nada raro: repite
lo que se le pidió.**

**De dónde sale entonces la ruta rara, medido por dos vías:**

```
public/css/design-system/vendor-datatables-legacy.css:6
  @import url("/public/css/design-system/adapters/datatables.css") layer(components);

recuento de @import de adapters bajo public/css/:        17 con /css   ·  1 con /public/css
recuento de referencias-URL en todo el repo (2.ª vía):   29 con /css   ·  1 con /public/css
```

**Es 1 de 18 (y de 30 por la segunda vía), y es la única sin `?v=1.1.0`.** Está escrita en el
código fuente, no en el instrumento.

**Consecuencia para la decisión del usuario: las TRES diferencias de `adapterAssets` son reales.**
Ninguna es artefacto de medición. La opción «aprobar el baseline de un plumazo» sigue tapando tres
cosas distintas, pero ya no una de ellas es un posible error de instrumento: son una baja legítima,
un alta legítima y **una inconsistencia real de una línea del CSS**.

**Y NO infla `cssGzipBytes` por duplicación.** Comprobado sobre las tres muestras reales del
artefacto `design-system-failure-evidence` de la corrida `31566518358`:

```
sample-1/2/3:  cssGzipBytes 194553  ·  duplicateRequestCount 0  ·  adapterAssets 8 (idénticos)
               initializationMs 858 / 868.3 / 931.5   (máximo 1101.5 → NO violado, 3.ª confirmación)
```

Las dos rutas sirven **el mismo archivo** (`curl` local: 200 y **5859 B las dos**), pero solo se
pide una vez: `duplicateRequestCount = 0`. El +42 % de CSS **no** viene de aquí.

**Un límite del artefacto que conviene saber antes de encargar el frente (b):** la muestra guarda
`assetInventorySha256` —un **hash**— y no la lista de activos. **Con lo publicado hoy no se puede
atribuir de dónde salen los 57 KB**; hace falta una medición instrumentada nueva. Decirlo ahora
ahorra que alguien abra (b) creyendo que el dato ya está.

**Lo que NO hago, y por qué.** El arreglo es una línea, y no es mío: `vendor-datatables-legacy.css`
no está en mis archivos declarados, y cambiarlo **mueve `adapterAssets`**, que es justo la entrada
de una decisión abierta del usuario. Tocarlo sería decidir `D-GAC-5` por la vía de los hechos.
**Medir no es decidir; arreglar sí.**

## D-5 (continuación) · El arreglo, autorizado por el usuario y verificado por medición

**El usuario lo autorizó explícitamente**, saltándose la cola: «arregla esa línea, yo autorizo».
Commit `ef4780b0`. Se cambió **solo el prefijo**; **no** se añadió el `?v=1.1.0` que tienen los otros
diecisiete, porque eso es cache-busting y excede «esa línea».

**Comprobado antes de tocar:** ningún manifiesto de `docs/design-system/manifests/` ancla ese
archivo, y el `@import` interno queda fuera de las comprobaciones del particionador (los
`STANDALONE_ATTACHMENTS` solo se validan por existencia y por la URL del adjunto PHP).

**Efecto, medido con una imagen reconstruida por árbol en el mismo runtime aislado:**

| | `adapterAssets` (datatables) | `cssGzipBytes` |
|---|---|---|
| sin arreglo (`0e45ba1d`) | `/public/css/design-system/adapters/datatables.css` | 195401 |
| con arreglo (`ef4780b0`) | `/css/design-system/adapters/datatables.css` | 195402 |

**El arreglo hace exactamente una cosa y nada más: Δ `cssGzipBytes` = +1 byte** (ruido de gzip).
`npm run test:design-system:static` → `RC=0`, ocho suites.

**La lista que hoy tumba el gate quedó limpia de ruido de medición:**

```
"added": [ "/css/design-system/adapters/datatables.css",
           "/css/design-system/adapters/shell-sidebar.css" ]
```

Dos altas legítimas, las dos en la forma canónica. Lo que queda de `adapterAssets` es **puramente
la pregunta de `D-GAC-5`** —¿se re-aprueba la lista?— sin un tercer elemento sospechoso encima.

**Sigue rojo, y es correcto:** `check` → `RC=1`, violaciones `cssGzipBytes` y `adapterAssets`.
**No se tocó el baseline ni ningún recibo.**

### Un instrumento roto que estuve a punto de reportar, y cómo se cayó

La primera «medición previa al arreglo» dio **exactamente los mismos números** que la posterior.
Encajaba con una conclusión cómoda («el arreglo no mueve nada») y era falsa: `COMPOSE_FILE` es
`docker-compose.yml:docker-compose.ci.yml` y **no incluye el override**, así que el contenedor de CI
lleva el código **horneado en la imagen**. Cambiar de commit en el host no cambia lo que sirve —
medí el contenedor post-arreglo dos veces.

Lo delató que el `adapterAssets` «sin arreglo» mostrara la ruta **corta**, que contradecía un hecho
que ya sabía. Rehecho con `up --build` y verificado **dentro del contenedor**
(`grep` sobre `/var/www/html/...` devolviendo la ruta larga) antes de volver a medir.

**La regla que faltaba, y la dejo escrita:** en este repo, `git checkout` **no** cambia lo que mide
el runtime aislado. Sin `--build`, se está midiendo la imagen anterior.

### Fallo intermitente, observado, no diagnosticado

La primera medición cayó con «La puerta de desarrollo no autenticó a "test.A"». Repetida sin cambiar
nada, pasó. **Hipótesis no comprobada:** el `curl /login` de la espera devuelve 200 antes de que
terminen de correr los ~30 SQL de inicialización, así que el paso «Wait for the application» puede
dar por lista una app cuya base aún se está sembrando. No lo he medido y **no lo presento como
diagnóstico**; lo dejo porque el mismo patrón de espera está en el workflow (`design-system.yml`).

---

## D-6 · ESCALADA (bloqueante) — el contrato fija que el baseline 0.3.3 SEA la medición retrospectiva, y la actualización autorizada de `adapterAssets` rompe esa identidad

Sesión bbd231db (retoma a 37818e4a), 2026-08-12, sobre `0f968d2f` (worktree
`beautiful-blackwell-414f09`).

**Qué se hizo con autorización (D-GAC-5, «arregla la línea y re-mide»):**
- `365c486e`: `vendor-datatables-legacy.css:6` pasa a `/css/design-system/adapters/datatables.css?v=1.1.0`
  (alineada con las otras 17, ahora sí con `?v=1.1.0` — el encargo de la coordinadora lo pedía explícito).
- Medición limpia en runtime aislado local (imagen reconstruida, arreglo verificado DENTRO del
  contenedor): `adapterAssets` = 8 rutas canónicas, `added` solo `datatables.css` y `shell-sidebar.css`.
- `0f968d2f`: baseline `runtime-baseline-0.3.3.json` con esa lista. Solo `adapterAssets`;
  `cssGzipBytes` (195383 medido vs máx 138981) queda abierto como deuda D-GAC-5(b).
- Re-check sobre `0f968d2f`: `CHECK_RC=1` con **una** violación (`cssGzipBytes`). `adapterAssets` limpio.

**Lo que se rompió, medido:** `npm run test:design-system:static` → **RC=1** sobre `0f968d2f`.
`tests/design-system/visual-ci-contract.test.mjs:278` («pilot runtime budgets remain available…»)
exige en `:354` `assert.deepEqual(baselineMetrics, retrospectiveMetrics)`: el baseline debe ser
**idéntico** a `docs/design-system/runtime-measurements/0.3.3-retrospective.json`, que a su vez está
fijado por `measurementSha256` dentro del propio baseline. Cambiar `adapterAssets` sin cambiar la
retrospectiva rompe la identidad; cambiar la retrospectiva sería falsificar una medición histórica.

**Opciones que veo (no decido ninguna: asserts y contratos bloquean siempre):**
- (a) Baseline **nuevo** versionado (p.ej. 0.3.4) con la medición de hoy como fuente, su propio
  measurement/manifest, y actualizar `package.json` + el contrato para apuntar al nuevo. Limpio y
  acorde al diseño del contrato, pero toca contrato y package.json.
- (b) Relajar el assert del contrato para permitir que `adapterAssets` diverja de la retrospectiva.
  Más barato y más feo: el contrato existe para que el baseline no se edite a mano.
- (c) Revertir `0f968d2f` y dejar `adapterAssets` rojo hasta que se decida (a).

**Estado en mi rama:** `365c486e` (línea) + `0f968d2f` (baseline), sin publicar. Con (c) bastaría
descartar el segundo.

## D-7 · ESCALADA (no bloquea mi avance, sí bloquea el paso 22 en CI) — el gate de PG usa `test.C` y el runtime aislado de CI no lo habilita

Misma sesión, mismo sha. Encargo punto 4: correr `Run Programa General persistence and RBAC gate`.

**Medido en runtime aislado local (idéntico al de CI: compose canónico
`lps-aia-design-system-ci-*`, `docker-compose.yml:docker-compose.ci.yml`):**

- Tal cual está el repo: `PG_GATE_RC=1`. Cae `Da Porto read-only roles` en `session.mjs:15`:
  «La puerta de desarrollo no autenticó a "test.C"». Causa: `pg-interactions.spec.mjs:15` usa
  `test.C` y `docker-compose.ci.yml:21` fija `DEV_DOOR_USERS: "test.A,test.R,test.V"`.
- Con `test.C` añadido a esa línea (edición local temporal, **revertida**, no committeada):
  `PG_GATE_RC=0` — 3 passed, 1 skipped (Aeropuerto PC, fuera de `E2E_PROJECT_KEYS=construction`,
  igual que en CI).

**Consecuencia:** la premisa del encargo («no depende de nada pendiente») no se cumple en CI: el
paso 22 fallaría por configuración, no por RBAC. El arreglo es una palabra en
`docker-compose.ci.yml:21`, pero es config del runtime de CI, archivo no declarado por mí y con
líneas fijadas por contrato en otras partes → se consulta, no se decide. Ningún contrato fija hoy
la línea `DEV_DOOR_USERS` (grep sobre tests/design-system y tests/*.php: 0 anclajes).

---

## Respuesta de la coordinadora (sesión 01a82dae), 2026-08-12

- **D-6 → resuelta como (c).** `0f968d2f` (baseline) **se descarta sin publicar**: la re-aprobación
  de `adapterAssets` solo cabe como baseline versionado 0.3.4 con medición propia, y eso arrastra
  la decisión de `cssGzipBytes` — va al usuario junto con D-GAC-5(b) en una sola tanda. Detalle en
  `docs/decisiones-pendientes.md` · D-GAC-6.
- **D-7 → resuelta: se añade `test.C`** a `DEV_DOOR_USERS` en `docker-compose.ci.yml:21`. Commit
  propio aparte + re-medición del gate de PG. Detalle en D-GAC-7.
- **Visto emitido para `365c486e`** (`.claude/vistos/gates-al-ci`). Publica exactamente ese sha:
  `git push origin 365c486e:refs/heads/main` (tras fetch/integración si hay divergencia; si el sha
  cambia por integrar, entrega de nuevo). Después reubica tu rama sin `0f968d2f`.
- Verificación mía por segundo camino sobre `365c486e`: `visual-ci-contract` 12/12; el rojo restante
  de mi corrida estática fue del instrumento (worktree sin `.env`/Docker), no del commit.

---

## Respuesta del usuario, relatada por la coordinadora — 2026-08-18

- **D-7:** confirmado — añadir `test.C` a `DEV_DOOR_USERS` en `docker-compose.ci.yml` y re-medir.
- **D-GAC-5(b):** confirmado — ejecutar el encargo del baseline 0.3.4 tal como está descrito.
- **Contexto nuevo:** el usuario percibe el sistema de gates como «un palo en la rueda» y pidió
  argumentos para mejorarlo y optimizarlo. La coordinadora abrió una línea de trabajo aparte
  (optimización del pipeline de gates dentro del programa de design system); este frente cierra
  con lo suyo (8/8) sin ampliar alcance.
