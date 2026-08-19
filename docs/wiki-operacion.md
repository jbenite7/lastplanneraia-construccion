# Cómo se opera la wiki (esquema v2)

Manual de la memoria del proyecto. Lo puede leer y editar una persona: vive en la capa de fuentes,
no dentro de la wiki que describe. `CLAUDE.md` lleva un resumen y apunta aquí.

**Qué cambió en v2** (2026-08-19, spec `docs/superpowers/specs/2026-08-18-wiki-v2-visual-design.md`):
el frontmatter deja de ser solo de `memoria/` y se extiende a **todo el vault**; aparecen el campo
`capa` y el vocabulario cerrado de `tags`; se añaden ocho `tipo` para documentos de fuente; y cae la
regla «solo plugins nativos». La metodología no cambia: siguen las mismas tres capas, las mismas
cuatro operaciones y la misma precedencia.

## Qué es la wiki y qué no es

`memoria/` guarda el porqué de las decisiones, las trampas que ya costaron tiempo y un mapa por
área que enlaza con la documentación que ya existe. Sigue el patrón
[LLM Wiki](https://gist.github.com/karpathy/442a6bf555914893e9891c11519de94f), en tres capas:

| Capa (`capa:`) | Dónde | Regla |
|---|---|---|
| `fuente` | `docs/`, `goals/`, `decisiones/`, los `.md` de la raíz, el código | Se leen. **Su cuerpo no se edita desde la wiki**; solo ganan frontmatter. |
| `wiki` | `memoria/` | La escribe el asistente. Nunca se edita a mano. |
| `esquema` | este archivo | Explica la estructura y las operaciones. |

**Precedencia ante conflictos: código > `AGENTS.md` > `memoria/`.** Nada de lo que hay en la wiki es
contrato. Si una nota contradice al repo, gana el repo: se corrige la nota y se marca
`estado: derogada` en vez de borrarla. Saber que algo dejó de ser cierto también es memoria.

El vault de Obsidian es la **raíz del repo**, no `memoria/`. Por eso los wikilinks alcanzan a
`docs/`, `goals/` y a los `.md` de la raíz sin copiarlos. La configuración compartida está en
`.obsidian/`.

### El frontmatter en fuentes es metadato añadido, no contenido editado

Que una fuente lleve frontmatter no rompe la inmutabilidad de la capa: es **un bloque delante del
cuerpo**, de la misma naturaleza que el pie «Archivos de este goal» que se decidió el 2026-08-02.
El cuerpo no se toca, y el lint tampoco lo mira. Dos reglas que lo sostienen:

- Si un archivo **ya trae frontmatter de otra herramienta**, el backfill **fusiona**: añade las
  claves del esquema que falten y no reordena ni reescribe ninguna clave ajena. El caso real es
  `DESIGN.md`, cuyo frontmatter leen el linter Stitch y el panel live; reescribirlo rompería otra
  herramienta sin que nada se pusiera rojo aquí.
- Una fuente entra al lint **solo si declara `capa:`**. Tener un bloque `---` no es declararse parte
  de este esquema; `capa:` sí lo es. Sin ese matiz, `DESIGN.md` salía en rojo por cuatro campos que
  no le tocan.

## Las cuatro operaciones

Cada una termina anexando **una línea** a `memoria/log.md`, con el formato
`- YYYY-MM-DD · operación · asunto · páginas tocadas` y la más reciente abajo. Se escribe al final
de la operación, no antes.

### `ingest`

Al cerrar una tarea o cuando aparece una fuente nueva: lee la fuente, comenta el hallazgo, escribe o
actualiza la página, actualiza `memoria/index.md`, revisa las páginas relacionadas por si alguna
quedó obsoleta, y deja la línea en el log.

### `query`

Preguntas contra la wiki, respondidas **citando páginas**. Si la respuesta era valiosa y no estaba
escrita, se promueve a página: una consulta que hubo que reconstruir dos veces es una nota que
faltaba.

### `lint` — la forma

```bash
node scripts/wiki-lint.mjs             # o: npm run test:wiki (incluye las pruebas del módulo)
node scripts/wiki-lint.mjs --estricto  # además exige que toda fuente declare `capa:`
```

Sale con código 1 si hay hallazgos. **Lintea las tres capas, pero no con las mismas reglas:**

| Capa | Qué se comprueba |
|---|---|
| `wiki` | frontmatter completo, enlaces, un-hecho-por-nota y alcanzabilidad desde el índice |
| `fuente` y `esquema` | **solo el frontmatter**. El cuerpo no se mira |

En la capa wiki, como siempre: enlaces rotos o ambiguos; frontmatter incompleto; `tipo`, `estado`,
`areas`, `tags` y `capa` fuera de sus listas cerradas; notas que empaquetan más de tres hechos;
páginas que no aparecen en `memoria/index.md` ni las cubre una vista de `memoria/paginas.base`; y la
edad del último pase de `veracidad` (ver más abajo).

**Comprueba y reporta; nunca corrige.** Y no comprueba la verdad: un verde no significa que la wiki
sea correcta, solo que está bien formada.

Cuatro decisiones del lint v2 que conviene conocer antes de discutir con él:

1. **`capa` y `tags` se validan solo si están.** Son opcionales a propósito mientras el backfill de
   las fuentes no haya terminado; exigirlos de golpe pondría en rojo cientos de archivos que nadie
   ha tocado. `--estricto` es la forma final, para encenderla cuando el backfill acabe.
2. **`capa` tiene que coincidir con la que implica la ruta.** Una página de `memoria/` que se
   declarase `fuente` conseguiría que el lint dejara de mirarle el cuerpo — justo lo que no debe
   pasar.
3. **Una página con `tags: [plantilla]` es un molde, no una página.** Se le comprueba el
   vocabulario —un `tipo` inventado en un molde se copiaría a cada página que salga de él— y nada
   más: ni sus huecos, ni el marcador `{{date:YYYY-MM-DD}}` que rellena Obsidian, ni que su `capa`
   case con dónde vive (la plantilla de una spec declara `capa: fuente` y vive en `memoria/`), ni
   que esté enlazada desde el índice. La exención cuelga del tag y no de la carpeta: mover
   `memoria/templates/` no debe cambiar cómo se mide.
4. **A una fuente se le exige `resumen` igual que a una página de wiki.** Es la columna «De qué
   va» del catálogo, y 391 filas con esa columna vacía no sirven para filtrar nada.

   Costó decidirlo porque la primera medida decía otra cosa. Con una sola regla de deducción
   quedaban **222** fuentes sin resumen, y eso hacía ver el backfill como 222 textos escritos a
   mano. Medido el 2026-08-19, esos 222 eran un fallo de la deducción y no del repositorio: los
   planes abren con una cita para agentes y la regla se paraba justo antes del `**Goal:**` que era
   el resumen buscado. Con la cascada de cuatro respaldos quedan **17**. La lección, que vale más
   que el número: antes de aceptar que algo es caro, comprueba si lo caro es la medida.

### `veracidad` — la verdad

Que una nota siga siendo cierta contra el código de hoy solo se averigua **leyendo el repositorio**.
Esa mitad es manual, delegable a un subagente de bajo coste, pero exigiéndole que **verifique cada
afirmación en vez de sospecharla**: se confirma citando archivo y línea, o se corrige.

**Alcance por rotación**, para que el coste sea proporcional al riesgo y no crezca con la wiki:

- las áreas cuyo código cambió desde el pase anterior, vistas en `git log`;
- más las páginas más antiguas sin revisar, hasta un tope, para que un área quieta no quede
  eternamente sin verificar.

En dos o tres pases se recorre la wiki entera. Una nota desmentida se corrige y, si ya no aplica, se
marca `estado: derogada`; nunca se borra. Si se corrige el cuerpo, se corrige también el `resumen`.

Línea de bitácora:

```
- 2026-08-03 · veracidad · áreas revisadas: design-system, rbac · 14 páginas · 2 corregidas, 1 derogada · [[design-system]], [[rbac-y-rutas]]
```

## La alarma de veracidad

El pase no depende de que alguien se acuerde. `scripts/wiki-lint.mjs` localiza la última línea
`veracidad` del log y cuenta los commits posteriores que tocan **código o contratos**:

| | |
|---|---|
| Rutas que cuentan | `src/`, `admin/`, `public/`, `tests/`, `scripts/`, `docs/`, `AGENTS.md` |
| Rutas que no cuentan | todo lo demás, en particular `memoria/` |
| Umbral | **más de 40 commits → hallazgo `VERACIDAD`**, salida en rojo |

Se mide en commits y no en días a propósito: este repo hace 100 o más commits en un día de sprint y
ninguno en un fin de semana, así que el reloj de pared no dice nada sobre cuánta deriva entró. Los
commits que solo tocan `memoria/` quedan fuera para que la wiki no dispare su propia alarma al
escribirse.

El umbral vive en `UMBRAL_COMMITS`, al principio de `scripts/wiki-veracidad.mjs`. Se ajusta en una
línea; si lo cambias, deja constancia en el log explicando por qué.

Mientras no exista ninguna línea `veracidad`, el aviso es informativo y **no** hace fallar al lint:
nacer en rojo entrena a ignorar el rojo. El primer pase siembra la línea y arma la alarma.

**Limitación conocida:** el conteo se hace sobre la rama actual. Con varias sesiones trabajando en
worktrees distintos, el número es aproximado.

## El frontmatter, campo por campo

**Una nota, un hecho.** Si no cabe en una pantalla, probablemente son dos.

| Campo | Qué es | ¿Obligatorio? |
|---|---|---|
| `capa` | `fuente`, `wiki` o `esquema`. Le dice al lint qué reglas aplicar | en fuentes, es lo que las mete al lint |
| `tipo` | uno de los diecisiete de abajo | sí |
| `estado` | `vigente`, `derogada`, `abierto` o `cerrado` | sí |
| `fecha` | del hecho, no de la escritura; ISO, nunca «la semana pasada» | sí |
| `areas` | una o varias de las trece válidas | recomendable |
| `tags` | del vocabulario cerrado de abajo; transversales | no |
| `fuente` | de dónde salió el hecho: un archivo, un comando, una sesión | recomendable |
| `resumen` | una línea; **es la columna que se ve en el catálogo del índice** | en la wiki, sí; en fuentes, no |
| `origen` | opcional, si la nota viene de la memoria privada previa | no |

`resumen` merece atención aparte: si corriges el cuerpo de una nota y no el resumen, la afirmación
vieja sigue circulando por el catálogo aunque la página ya diga otra cosa.

Enlaza con `[[nombre]]` a las notas relacionadas. Un wikilink a una nota que aún no existe es
aceptable como señal de trabajo pendiente, pero el lint lo reporta como enlace roto: escríbelo solo
si vas a crear la nota en la misma pasada.

### Los diecisiete `tipo`

**Nueve de la wiki**, los de siempre:
`decision` · `trampa` · `mapa` · `goal` · `concepto` · `referencia` · `log` · `modulo` · `flujo`

**Ocho de fuente**, nuevos en v2:

| `tipo` | Qué documento es | Dónde vive |
|---|---|---|
| `contrato` | manda sobre el trabajo; incumplirlo es un error | `AGENTS.md`, `CLAUDE.md`, `GEMINI.md`, `DESIGN.md`, `docs/design-system/contracts/` |
| `spec` | qué se va a construir y por qué | `docs/superpowers/specs/`, `goals/*/specs/` |
| `plan` | en qué orden y con qué verificación | `docs/superpowers/plans/`, `goals/*/plan.md` |
| `reporte` | qué pasó, fechado | `docs/reportes/`, `goals/*/reports/`, `decisiones/` |
| `evidencia` | una medida cruda que respalda una afirmación | `evidence/`, `runtime-measurements/`, `goals/*/facts.md` |
| `biblia` | referencia de dominio, no de proceso | `GLOSARIO.md`, `PRODUCT.md`, `ROADMAP.md` |
| `guia` | cómo se hace algo | el grueso de `docs/` |
| `goal-doc` | pieza de un goal que no es spec, plan ni evidencia | `goals/*/goal.md` y hermanos |

### Los ocho `tags`

Lista cerrada, comprobada por el script. Son **transversales**: no duplican `tipo` ni `areas`.

| Tag | Cuándo |
|---|---|
| `moc` | la página es el mapa de un área |
| `dashboard` | es un tablero, no un texto |
| `plantilla` | es un molde para escribir otras |
| `pendiente` | queda trabajo abierto dentro |
| `trampa` | describe algo que ya costó tiempo |
| `leer-antes-de-tocar` | hay que leerla antes de editar el área que cubre |
| `generado` | la escribe un script; editarla a mano se pierde |
| `archivo` | trabajo cerrado que se conserva por historia |

### Las trece áreas

Lista cerrada, sin cambios en v2:

`design-system` · `qa` · `docker` · `worktrees` · `pdc` · `lps` · `datos` · `rbac` · `deploy` ·
`bi` · `admin` · `proceso` · `arquitectura`

Para añadir una: edita primero `AREAS` en `scripts/wiki-esquema.mjs` y explica en
`memoria/index.md` qué cubre. Una lista que crece sin control deja de servir para filtrar.

## Plugins de Obsidian

**Regla nueva en v2, que sustituye a la de «solo nativo»:** se permiten plugins de comunidad,
versionados en `.obsidian/plugins/` para que cualquier máquina los tenga al clonar, **pero la wiki
tiene que seguir leyéndose sin ninguno.** Markdown puro y Obsidian Bases —que es nativo— son la
base; los plugins amplifican, no sostienen.

En la práctica: si una página solo dice lo que dice cuando Dataview la renderiza, esa página está
mal escrita. El dato tiene que estar en el frontmatter o en el texto, y la consulta ser una vista
más cómoda del mismo dato, nunca su único portador.

## Los scripts

| Script | Qué hace |
|---|---|
| `scripts/wiki-esquema.mjs` | El vocabulario cerrado y la lectura de frontmatter. Funciones puras. |
| `scripts/wiki-lint.mjs` | La operación `lint` y la alarma de veracidad. Comprueba y reporta. |
| `scripts/wiki-veracidad.mjs` | Funciones puras de la alarma. No imprime; lo consume el lint. |
| `scripts/wiki-frontmatter.mjs` | Backfill del frontmatter en fuentes. **No escribe si no se lo piden.** |
| `scripts/wiki-frontmatter.reglas.mjs` | Las reglas por ruta del backfill. Funciones puras. |
| `scripts/wiki-arquitectura.mjs` | Genera las páginas de módulo desde el código. |
| `scripts/wiki-registro.mjs` | Genera el catálogo del trabajo fechado de `docs/superpowers/`. |
| `tests/wiki/*.test.mjs` | Pruebas de los módulos puros, con `node --test`. |
| `memoria/templates/` | Un molde por `tipo` frecuente. Ver más abajo. |

```bash
npm run test:wiki                                # pruebas de los módulos + lint
node scripts/wiki-arquitectura.mjs --cobertura   # ninguna ruta sin módulo
node scripts/wiki-arquitectura.mjs --escribir    # actualiza las zonas generadas
node scripts/wiki-registro.mjs --escribir        # actualiza el registro de trabajo
```

Corre `wiki-registro.mjs` cuando escribas una spec o un plan nuevos, o cuando archives trabajo
cerrado: empareja spec y plan por su slug y marca lo que vive en `docs/archive/superpowers/`.

### El backfill de frontmatter

```bash
node scripts/wiki-frontmatter.mjs                       # censo, no escribe nada
node scripts/wiki-frontmatter.mjs --detalle             # además, el frontmatter que escribiría
node scripts/wiki-frontmatter.mjs --solo docs/flujos    # acota a un prefijo de ruta
node scripts/wiki-frontmatter.mjs --solo docs/flujos --escribir
```

**El modo por defecto es el ensayo.** Un backfill que toca cientos de archivos tiene que poder
mirarse entero antes de correr, y por eso `--escribir` es explícito y `--solo` existe: se aplica por
tandas, con revisión entre una y otra.

Deduce de la ruta y del propio texto: `capa`, `tipo`, `estado`, `fecha` (del nombre del archivo si
lo lleva, si no del alta en `git log`), `areas`, `tags` y `resumen`. **Cuando no puede deducir, deja
el campo vacío y lo cuenta en el informe** — nunca inventa un valor. Es idempotente: solo añade las
claves que faltan, así que correrlo dos veces no cambia nada la segunda.

El `resumen` sale de una **cascada de cuatro respaldos**, de más informativo a menos. Ninguno
inventa nada: los cuatro toman palabras que el propio documento ya escribió.

| # | De dónde | Cubre | Para qué documento es |
|---|---|---|---|
| 1 | el párrafo tras el `# título` | 169 | el caso normal |
| 2 | la línea `**Goal:** / **Objetivo:**` | 77 | los planes, que abren con una cita para agentes |
| 3 | el párrafo bajo `## Objetivo` | 44 | los `goal.md` y `facts.md` de los frentes |
| 4 | el propio `# título` | 84 | último recurso |
| — | nada; hueco visible | 17 | los rellena una persona |

El informe cuenta cuántos salieron de cada respaldo, para que se vea de un vistazo cuántos son
prosa de verdad (290) y cuántos son solo el título (84). El respaldo 4 es el más pobre y aun así
vale la pena: en el catálogo la otra columna es el nombre del archivo, que muestra el slug
(`2026-07-20-sidebar-canonico-laboratorio`), así que el título añade legibilidad en vez de
repetirla. Un título de una o dos palabras no añade nada y se descarta — mejor un hueco visible.

### Las plantillas

`memoria/templates/` tiene un molde por cada `tipo` que se escribe a menudo: `decision`, `trampa`,
`concepto`, `spec` y `plan`. Los dos últimos son de capa fuente: los escribe una persona en
`docs/superpowers/`, y la plantilla vive aquí solo porque aquí es donde Obsidian las busca.

Cada molde lleva `tags: [plantilla]`, que es lo que lo exime del lint (ver más arriba). Su valor no
está en el frontmatter —eso lo genera el backfill— sino en **las preguntas del cuerpo**: qué se
descartó y por qué, qué desmentiría esta nota, cuánto costó la trampa. Son las que se olvidan
cuando se escribe deprisa, y las que hacen que la nota sirva dentro de seis meses.

Al añadir un molde nuevo: `tags: [plantilla]` y una entrada en esta lista. Si el molde no responde
ninguna pregunta que no esté ya en otro, no hace falta.

### Las zonas generadas

Las páginas de `memoria/arquitectura/` y `memoria/flujos/` tienen dos zonas. Entre
`<!-- generado:inicio -->` y `<!-- generado:fin -->` manda `scripts/wiki-arquitectura.mjs`, que
extrae del código las rutas con su verbo y destino, los controladores, los servicios, las tablas y
qué rol tiene cada capacidad. **Fuera de los marcadores manda la persona**, y regenerar no lo toca.

Si aparece un módulo nuevo, se declara en `scripts/wiki-arquitectura.modulos.mjs`. Si una ruta nueva
no casa con ningún módulo, `--cobertura` falla en vez de dejarla fuera del mapa en silencio.
