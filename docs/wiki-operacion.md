# Cómo se opera la wiki `memoria/`

Manual de la memoria del proyecto. Lo puede leer y editar una persona: vive en la capa de fuentes,
no dentro de la wiki que describe. `CLAUDE.md` lleva un resumen y apunta aquí.

## Qué es la wiki y qué no es

`memoria/` guarda el porqué de las decisiones, las trampas que ya costaron tiempo y un mapa por
área que enlaza con la documentación que ya existe. Sigue el patrón
[LLM Wiki](https://gist.github.com/karpathy/442a6bf555914893e9891c11519de94f), en tres capas:

| Capa | Dónde | Regla |
|---|---|---|
| Fuentes | `docs/`, `goals/`, los `.md` de la raíz, el código | Se leen. Su contenido no se edita desde la wiki. |
| Wiki | `memoria/` | La escribe el asistente. Nunca se edita a mano. |
| Esquema | este archivo | Explica la estructura y las operaciones. |

**Precedencia ante conflictos: código > `AGENTS.md` > `memoria/`.** Nada de lo que hay en la wiki es
contrato. Si una nota contradice al repo, gana el repo: se corrige la nota y se marca
`estado: derogada` en vez de borrarla. Saber que algo dejó de ser cierto también es memoria.

El vault de Obsidian es la **raíz del repo**, no `memoria/`. Por eso los wikilinks alcanzan a
`docs/`, `goals/` y a los `.md` de la raíz sin copiarlos. La configuración compartida está en
`.obsidian/` y no se usan plugins de comunidad: el vault debe funcionar en cualquier máquina.
Obsidian Bases sí se usa —es nativo— para generar el catálogo del índice desde el frontmatter.

Una excepción a la inmutabilidad de las fuentes, decidida el 2026-08-02: cada `goals/<slug>/goal.md`
termina con una sección «Archivos de este goal» que enlaza a sus hermanos versionados y a
`memoria/goals/estado.md`. Es navegación añadida al pie, no contenido modificado. Al crear un goal
nuevo, añade esa sección. `docs/` no se toca.

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
node scripts/wiki-lint.mjs      # o: npm run test:wiki (incluye las pruebas del módulo)
```

Comprueba **la forma** y sale con código 1 si hay hallazgos:

- enlaces rotos o ambiguos;
- frontmatter incompleto, y `tipo`, `estado` o `areas` fuera de sus listas cerradas;
- notas que empaquetan más de tres hechos;
- páginas que no aparecen en `memoria/index.md` ni las cubre una vista de `memoria/paginas.base`;
- la edad del último pase de `veracidad` (ver más abajo).

**Comprueba y reporta; nunca corrige.** Y no comprueba la verdad: un verde no significa que la wiki
sea correcta, solo que está bien formada.

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

## Escribir una página

**Una nota, un hecho.** Si no cabe en una pantalla, probablemente son dos.

Frontmatter obligatorio:

| Campo | Qué es |
|---|---|
| `tipo` | `decision`, `trampa`, `mapa`, `goal`, `concepto`, `referencia`, `log`, `modulo` o `flujo` |
| `estado` | `vigente`, `derogada`, `abierto` o `cerrado` |
| `fecha` | del hecho, no de la escritura; fechas absolutas, nunca «la semana pasada» |
| `areas` | una o varias de las trece válidas |
| `fuente` | de dónde salió el hecho: un archivo, un comando, una sesión |
| `resumen` | una línea; **es la columna que se ve en el catálogo del índice** |
| `origen` | opcional, si la nota viene de la memoria privada previa |

`resumen` merece atención aparte: si corriges el cuerpo de una nota y no el resumen, la afirmación
vieja sigue circulando por el catálogo aunque la página ya diga otra cosa.

Enlaza con `[[nombre]]` a las notas relacionadas. Un wikilink a una nota que aún no existe es
aceptable como señal de trabajo pendiente, pero el lint lo reporta como enlace roto: escríbelo solo
si vas a crear la nota en la misma pasada.

## Las trece áreas

Lista cerrada, comprobada por el script:

`design-system` · `qa` · `docker` · `worktrees` · `pdc` · `lps` · `datos` · `rbac` · `deploy` ·
`bi` · `admin` · `proceso` · `arquitectura`

Para añadir una: edita primero `AREAS` en `scripts/wiki-lint.mjs` y explica en `memoria/index.md`
qué cubre. Una lista que crece sin control deja de servir para filtrar.

## Los scripts

| Script | Qué hace |
|---|---|
| `scripts/wiki-lint.mjs` | La operación `lint` y la alarma de veracidad. Comprueba y reporta. |
| `scripts/wiki-veracidad.mjs` | Funciones puras de la alarma. No imprime; lo consume el lint. |
| `scripts/wiki-arquitectura.mjs` | Genera las páginas de módulo desde el código. |
| `tests/wiki/veracidad.test.mjs` | Pruebas del módulo, con `node --test`. |

```bash
npm run test:wiki                                # pruebas del módulo + lint
node scripts/wiki-arquitectura.mjs --cobertura   # ninguna ruta sin módulo
node scripts/wiki-arquitectura.mjs --escribir    # actualiza las zonas generadas
```

Las páginas de `memoria/arquitectura/` y `memoria/flujos/` tienen dos zonas. Entre
`<!-- generado:inicio -->` y `<!-- generado:fin -->` manda `scripts/wiki-arquitectura.mjs`, que
extrae del código las rutas con su verbo y destino, los controladores, los servicios, las tablas y
qué rol tiene cada capacidad. **Fuera de los marcadores manda la persona**, y regenerar no lo toca.

Si aparece un módulo nuevo, se declara en `scripts/wiki-arquitectura.modulos.mjs`. Si una ruta nueva
no casa con ningún módulo, `--cobertura` falla en vez de dejarla fuera del mapa en silencio.
