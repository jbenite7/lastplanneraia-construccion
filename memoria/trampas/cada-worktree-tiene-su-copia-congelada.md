---
tipo: trampa
estado: vigente
fecha: 2026-08-11
areas: [proceso, qa]
fuente: reproducido por la coordinadora de coordinating-agent-sessions y medido aquí, 2026-08-11
resumen: "El registro de sesiones vive en la raíz, pero cada worktree se lleva una copia al crearse; leerlo desde un worktree devuelve un archivo viejo con el nombre correcto en la ruta correcta"
---

# Cada worktree tiene su copia congelada del registro

`.claude/sesiones.md` lo mantienen los hooks del plugin de coordinación, y **lo escriben siempre en
la raíz del repo** —vía `git-common-dir`, que es lo correcto: el estado del frente es compartido—.

Pero **cada worktree se lleva su propia copia en el momento en que se crea**, y esa copia **no se
actualiza nunca más**. Leer el registro desde dentro de un worktree devuelve el archivo equivocado.

## Medido

El 2026-08-11, sobre `ad490741`, los **cuatro** worktrees de este repo tenían una copia distinta de
la de la raíz:

```
beautiful-blackwell-414f09: DISTINTA — 4 filas frente a 3
cranky-dhawan-aa8725:       DISTINTA — 3 filas frente a 3
elegant-jones-d4126a:       DISTINTA — 3 filas frente a 3
jovial-sutherland-e7e1ad:   DISTINTA — 4 filas frente a 3
```

**Fíjate en las dos del medio: mismo número de filas y contenido distinto.** Comprobar el recuento
no detecta la divergencia. Hay que comparar el archivo.

## Cómo mordió

Una sesión de prueba corrió `grep -c '^| ' .claude/sesiones.md` desde su worktree para declarar
cuántas sesiones había vivas. Leyó **2**. En la raíz había **3**, incluida su propia fila, que el
script había escrito correctamente segundos antes.

Con ese número se estuvo a punto de diagnosticar un fallo de escritura en el plugin —«el script dice
que escribió y la fila no está»— y de abrir un frente para arreglar algo que **no estaba roto**. Lo
que falló fue la comprobación, no la escritura.

Y la coordinadora, al recibir el dato, construyó una explicación plausible y falsa: que la diferencia
era un desfase de dos minutos entre sellos de tiempo. Encajaba con las horas. No era eso.

## Por qué es peor que las otras de su familia

Esta es pariente de [[el-codigo-de-salida-se-pierde-en-la-tuberia]],
[[el-contador-no-mide-el-archivo]] y [[valor-declarado-no-es-valor-computado]] — todas son
instrumentos que contestan a una pregunta distinta de la que creías hacer.

Pero las demás dan algo raro: un número que no cuadra, un vacío, un cero sospechoso. **Esta devuelve
un archivo con el nombre correcto, en la ruta que esperarías, con el formato correcto y datos
verosímiles.** No hay síntoma. Un markdown congelado al menos nunca cambia; esta miente
pareciéndose.

## Qué hacer

- **Lee el registro desde la raíz del repo, siempre**, aunque trabajes en un worktree:
  `git rev-parse --git-common-dir` te da el camino.
- **No cuentes filas para saber si tu copia está al día.** Compara el archivo: `cmp -s`.
- **Si necesitas saber cuántas sesiones hay vivas**, pregúntaselo a la coordinadora o mira la raíz.
  El dato no está donde tu `pwd`.
- Y recuerda que `grep -c '^| '` **incluye la cabecera de la tabla**: hay que restar 1.

Relacionado: [[aislar-stack-docker-por-worktree]] —el mismo patrón con Docker: lo que sirve tu
contenedor no es tu worktree— y [[el-dom-dice-que-existe-no-que-se-ve]].
