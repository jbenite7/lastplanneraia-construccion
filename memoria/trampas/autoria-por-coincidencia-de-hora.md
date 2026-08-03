---
tipo: trampa
estado: vigente
fecha: 2026-08-02
areas: [proceso]
fuente: sesion
resumen: "La hora de última actividad de una sesión no prueba que escribiera un archivo; atribuir trabajo por esa coincidencia acusa en falso"
---
Al fundar esta wiki aparecieron en disco archivos sin rastrear que nadie de la conversación había
escrito. `mcp__ccd_session_mgmt__list_sessions` mostraba una sesión con `lastActivityAt` a la
misma hora exacta que el `mtime` de esos archivos, y se le atribuyó el trabajo. **Era falsa**: esa
sesión estaba integrando otro goal, ninguno de sus commits tocaba esas rutas, y de hecho ya había
reportado esos mismos archivos como trabajo suelto ajeno.

Se le envió un aviso acusándola y hubo que corregirlo.

**Why:** `lastActivityAt` mide cuándo respondió la sesión, no qué escribió. En una máquina donde
varias sesiones corren sobre el mismo worktree, las horas coinciden por densidad, no por
causalidad. Y el error no es barato: un aviso mal dirigido puede hacer que otra sesión revierta o
commitee cosas creyendo que son suyas.

**How to apply:** antes de atribuir un archivo sin rastrear a una sesión concreta:

- `git log --stat` y los mensajes de commit de esa sesión — si su trabajo va con otro prefijo de
  ámbito y nunca toca esas rutas, no es suya.
- Búsqueda en transcripciones: sirve para mensajes, **no** para escrituras de archivo. Que no haya
  resultados no exculpa ni incrimina.
- El frontmatter `origen:` de esta wiki apunta a la memoria de la que salió cada nota, no a la
  sesión que la escribió. No sirve para identificar autoría.

Si no se puede probar, se dice «autoría no identificada» y se sigue. Y en el mensaje entre
sesiones se relata lo observado, nunca se afirma quién lo hizo — ver también
[[worktree-compartido-arrastra-commits]], que es la causa de fondo: varias sesiones sobre el mismo
árbol.

El otro filo del mismo episodio: el `git status` de arranque **ya listaba** `memoria/` y el spec
como archivos sin rastrear, y aun así se escribió encima del spec sin leerlo. Ese listado es la
señal que este repositorio enseña a mirar; [[AGENTS]] lo pide de forma explícita: revisar estado y
diff antes de editar, y no incluir cambios ajenos.
