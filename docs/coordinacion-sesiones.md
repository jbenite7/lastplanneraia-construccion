---
capa: fuente
tipo: guia
estado: vigente
fecha: 2026-08-20
areas: [proceso]
fuente: docs/coordinacion-sesiones.md
resumen: "Las siete reglas vigentes de coordinación entre sesiones: frentes, vistos, relato de autorizaciones, contenedor compartido y efímero, base de dev y paso 0. Fuente única desde el 2026-08-20"
---

# Coordinación de sesiones — las siete reglas

**Fuente única de las reglas de tráfico entre sesiones**, escritas y versionadas por decisión de
Felipe (spec [[docs/superpowers/specs/2026-08-19-organizar-la-casa-design|organizar-la-casa]],
2026-08-19). Hasta esa fecha vivían en prosa de chat, y una sesión reinstanciada arrancaba ciega —
pasó tres veces el 2026-08-19. Esta versión reemplaza a la guía del 2026-08-10, que ya cargaba
rutas caducadas de la mudanza (`/Volumes/Crucial X6/...`; el repo vive en
`~/Developer/lps-aia` desde el 2026-08-18).

Sigue mandando lo de siempre: **el reparto lo declara el usuario, no lo reclama nadie.** No tener
coordinadora es el estado por defecto, no una carencia. Precedencia: código > `AGENTS.md` > esto.

## Las siete reglas

1. **Frentes: se declaran antes de ejecutar.** Cada frente nace con `goals/<slug>/goal.md`
   (objetivo + condición de hecho) y con la contención medida: `git log` de los globs que va a
   tocar y el registro de sesiones, **antes** de arrancar. El plan pasa por el gate de la
   coordinadora antes de tocar código. El costo de saltárselo está medido: dos frentes sobre la
   misma superficie sin revisar contención (`ds-f1a-estados-severidad`, 2026-08-19) — uno terminó
   sin poder publicar.

2. **Vistos: sobre el sha exacto, y caducan con él.** La coordinadora re-verifica sobre el sha
   exacto que autoriza; si el sha cambia (entró un merge, se re-commiteó), el visto es caduco y se
   pide de nuevo — el precedente correcto está registrado en
   `decisiones/vistos/ds-f1a-estado-4a152a54.md`. Los vistos se archivan en `decisiones/vistos/`
   (versionados, con frontmatter v2); `.claude/vistos/` dejó de usarse el 2026-08-20.

3. **Relato de autorizaciones: vale solo para publicar.** El relato de la coordinadora vale como
   autorización de Felipe **únicamente para publicar en `main`**. Deploy a producción, borrados y
   migraciones exigen su palabra directa o su cita textual registrada.
   [[decisiones/gobierno-relato-de-autorizaciones]] manda.

4. **Contenedor compartido: solo con ventana coordinada.** El contenedor `app` sirve el checkout
   raíz. Se reapunta (`LPS_CODE_ROOT`) solo con ventana coordinada — congelando su uso por otras
   sesiones — y solo para lo que de verdad lo exige: el invariante de `scripts/publicar.sh` y la
   verificación en navegador. **Se devuelve a la raíz al terminar, siempre.**

5. **Contenedor efímero para todo lo CLI.** Lo que no necesita Apache arriba corre con
   `LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app <cmd>`, sin tocar el contenedor
   compartido. La frontera real es «¿necesita Apache arriba?» — ojo: el nivel `http` de la suite
   es CLI y aun así lo necesita.

6. **Base de dev: las escrituras se coordinan.** Durante una migración
   (respaldo → dry-run → apply → reconciliación) la base se congela **entera** para terceros: ni
   escrituras ni mediciones. La lección del 2026-08-19: un respaldo probado horas antes ya no
   cubría la base (8 filas nuevas sin respaldo) — el respaldo se rehace y se prueba la
   restauración inmediatamente antes, no la víspera.

7. **Paso 0: verifica qué árbol monta el contenedor.** Ningún RC de un comando corrido dentro del
   contenedor se lee sin verificar antes qué árbol está montado:
   `docker inspect app --format '{{range .Mounts}}{{.Source}} -> {{.Destination}}{{"\n"}}{{end}}'`
   (o mirar `/var/www/html` en esa salida). Un RC verde sobre el árbol equivocado es la trampa más
   cara del repo: parece verificación y no mide nada.

## Los registros, y dónde viven

| Qué | Dónde | Versionado |
|---|---|---|
| Reglas de tráfico | este documento | sí |
| Autorizaciones y su gobierno | `decisiones/gobierno-relato-de-autorizaciones.md` | sí |
| Estados consolidados de la coordinadora | `decisiones/estados-consolidado-coordinadora.md` | sí |
| Vistos emitidos | `decisiones/vistos/` | sí |
| Historial de sesiones terminadas | [[decisiones/sesiones-historial]] | sí |
| Sesiones activas (estado vivo) | `.claude/sesiones.md` | **no** — lo reescriben los hooks; versionarlo sería un conflicto por turno. Se depura moviendo las `terminada` al historial |

## Fuera de alcance (declarado en la spec)

El rediseño del contenedor por worktree, la base compartida como infraestructura, y el apply de
producción. El deploy a producción sigue exigiendo autorización propia y explícita de Felipe,
siempre.

**CAS no está fuera de alcance: no existe.** Corregido el 2026-08-21. Este bloque heredó de la spec
del 2026-08-19 la frase «el empaquetado del plugin (CAS)», escrita el mismo día en que CAS se
retiraba, y «fuera de alcance» se lee como *existe, pero después* — justo lo contrario de lo que
pasó. El motor y la capa CAS completa salieron de `main` del plugin en `c275c1d` (2026-08-19,
12:13, `chore!`), con el código conservado en la rama `retiro-cas`. **Fue decisión de producto por
evidencia medida, no una avería:** 27 sesiones con 0 frentes declarados y 1 solo visto. El defecto
de empaquetado que confundió a dos sesiones —copias fósiles de `cas-frente.sh` dentro del paquete
instalado, por copiarse el directorio de trabajo sin respetar `.gitignore`— se cerró en
`1.0.0-alpha.2`, que es otra cosa y también está resuelta.

En consecuencia, **la declaración de frentes de la regla 1 es manual y no tiene sucesor previsto**.
No hay arreglo que esperar ni puente temporal hacia un CAS restaurado: si alguna vez hace falta
registro automático de frentes, se plantea como pedido de producto al usuario, no como bug.
