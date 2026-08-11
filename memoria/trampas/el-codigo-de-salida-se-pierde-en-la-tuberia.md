---
tipo: trampa
estado: vigente
fecha: 2026-08-10
areas: [qa, proceso]
fuente: autocorrección de la sesión de CI y medición del coordinador, 2026-08-10
resumen: "Encadenar un comando detrás de otro que no puede fallar convierte un gate rojo en una publicación verde; pasó dos veces el mismo día a dos sesiones distintas"
---
# El código de salida se pierde en la tubería

El 2026-08-10 dos sesiones distintas leyeron mal el resultado de una verificación **el mismo día**,
por la misma causa mecánica: **el código de salida que miraron no era el del comando que les
importaba**.

## Las dos veces

**El coordinador**, comprobando si unos scripts de Node fallan de verdad:

```bash
node tests/browser/shell-sidebar-rollout.mjs 2>&1 | tail -4
echo "(salida: $?)"      # ← esto es el exit de `tail`, no el de `node`
```

`tail` casi siempre sale `0`, así que el gate parecía no morder. Al medirlo bien —redirigiendo a
archivo y leyendo `$?` del propio `node`— los scripts sí salían `1`. La conclusión que estuvo a
punto de escribirse era la contraria a la verdad.

**La sesión de CI**, publicando un frente: encadenó el `git push` detrás de un `echo` en el mismo
comando. El `echo` no puede fallar, así que el push se ejecutó aunque la verificación previa había
devuelto `2`. Ese `2` era el guardarraíl del runner negándose a correr sin entorno —Apache aún no
había arrancado tras un rebuild—, exactamente lo que debía hacer. El árbol publicado resultó estar
sano al re-verificarlo, pero **el paso de verificación no gobernó la publicación**, que es su único
trabajo.

## Por qué importa más de lo que parece

El gate de cierre de frente (`AGENTS.md` §Publicación) se apoya entero en que el paso 1 —verificar
con salida real de comandos— pueda **impedir** el paso 6 —publicar—. Si el código de salida se
pierde por el camino, el gate sigue existiendo y deja de gobernar: se convierte en un ritual que
imprime texto.

Y no se nota, porque el síntoma es un verde.

## Cómo evitarlo

- **Nunca encadenes una acción irreversible detrás de algo que no puede fallar.** `echo`, `tail`,
  `head` y `sort` salen `0` casi siempre.
- **Si necesitas ver la salida y el código, sepáralos:** redirige a un archivo, lee `$?` del comando
  real, y luego mira el archivo.
- **En una tubería, `$?` es del último tramo.** Usa `${PIPESTATUS[0]}` en bash, o evita la tubería.
- **Ante un gate que dice «pasa», comprueba que sabe fallar.** Es la misma regla que ya rige en este
  repo —todo gate se entrega con una mutación que lo pone rojo, ejecutada— aplicada a la forma de
  invocarlo, no solo a su contenido.

Relacionado: [[un-verde-solo-vale-para-el-arbol-donde-se-midio]],
[[gate-solo-cuenta-elementos-no-los-lee]], [[test-sin-base-sale-verde]].
