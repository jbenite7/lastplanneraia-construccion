---
capa: fuente
tipo: reporte
estado: vigente
fecha: 2026-08-18
areas: [proceso]
tags: [pendiente]
fuente: decisiones/wiki-t1-ejecutor.md
resumen: Formato: una entrada por decisión que no me toca. escalada = se mandó a la coordinadora y el trabajo que dependía quedó saltado. anotada = no bloquea, seguí…
---

# Cola de decisiones — frente `wiki-t1` (ejecutor)

Formato: una entrada por decisión que no me toca. `escalada` = se mandó a la coordinadora y el
trabajo que dependía quedó saltado. `anotada` = no bloquea, seguí adelante con lo supuesto.

## 1 · escalada · Dónde viven las plantillas y qué exención pide el lint
Fecha: 2026-08-19 · Estado: esperando respuesta
El punto 4 de la Tanda 1 pide plantillas en `templates/`; mi glob declarado es `memoria/templates/**`.
Todo `.md` bajo `memoria/` lo lintea `wiki-lint.mjs` como página de wiki, así que las plantillas
dispararían `INDICE` y `FRONTMATTER`. Tres salidas: (a) exentar `tags: [plantilla]` del lint,
(b) enlazarlas desde `memoria/index.md` (que la Tanda 4 reescribe), (c) meterlas en
`userIgnoreFilters` de `.obsidian/app.json` (Tanda 4, fuera de alcance). Recomendé (a).
Bloquea porque (a) añade una exención a una comprobación existente. Punto 4 saltado hasta respuesta.

## 2 · anotada · Infraestructura CAS ausente en el plugin instalado
Fecha: 2026-08-19
`loop-engineering/0.3.0` no trae el módulo `cas/`; solo el caché `0.2.0` lo tiene, y `.claude/cas-root`
apunta a una ruta inexistente. Declaré el frente con el `cas-frente.sh` de 0.2.0, con `--sin-plan`
(el script solo reconoce encabezados `Task N`/`Fase N` y el plan usa `Tanda N`) y corregí a mano las
líneas `Plan:`/`Fase:` del `goal.md` y el rol de mi fila en `.claude/sesiones.md`.
No bloquea: ninguna respuesta me haría borrar nada de lo escrito. Los gates de rutas, presupuesto y
push NO están activos; el cumplimiento es disciplina, no mecánica.

## 3 · anotada · `capa`, `tags` y fuentes se validan de forma permisiva en v2
Fecha: 2026-08-19
La condición de hecho exige verde «con la wiki actual intacta». Por eso: `capa` y `tags` se validan
solo si están presentes, `capa` se deduce de la ruta cuando falta, y las fuentes se lintean solo si
ya tienen frontmatter. Bandera `--estricto` apagada por defecto para que la Tanda 2 la encienda.
No bloquea: es lo que el propio plan pide; si la coordinadora lo lee como desviación, se cambia sin
borrar nada.
