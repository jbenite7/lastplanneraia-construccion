---
capa: wiki
tipo: concepto
estado: vigente
fecha: 2026-08-04
areas: [design-system]
tags: [trampa]
fuente: public/css/tokens.css, docs/design-system/tokens.md
resumen: "Los tokens tienen dos capas: --aia-* es la paleta de marca y --ds-* el alias semántico; un módulo consume --ds-*, nunca --aia-* ni un hex"
---
# Por qué los tokens tienen dos capas

`public/css/tokens.css` declara **dos familias de variables**, y la diferencia no es cosmética:

| Capa | Cuántas | Qué es |
|---|---|---|
| `--aia-*` | 37 | La **paleta de marca**: los colores que existen, heredados de la identidad de AIA. |
| `--ds-*` | 295 | El **alias semántico**: para qué sirve cada color en una interfaz. |

`docs/design-system/tokens.md:3-6` lo dice en una línea: `--aia-*` es «paleta y decisiones
heredadas de marca», `--ds-*` son «alias semánticos para componentes y gates nuevos».

**Para qué existe la separación.** Permite cambiar el aspecto sin renombrar nada. Cuando la app
pasó a dark, no hubo que tocar los módulos: bastó reapuntar los `--ds-*` a otros valores, porque
ningún módulo dice «verde», dice «éxito». Un módulo que consumiera `--aia-green-light`
directamente habría bloqueado esa migración.

**La regla práctica, entonces:** un módulo consume `--ds-*`. Nunca `--aia-*`, nunca un hex. Los
`--aia-*` los consume la capa de tokens, no las superficies.

**Quién manda si dos fuentes discrepan.** `public/css/tokens.css`. Está escrito en [[DESIGN]]
(`:218-220`): si el frontmatter del contrato de consumo y `tokens.css` divergen, gana `tokens.css`.

## Dónde se rompe esto en la práctica

- [[comentario-de-token-afirma-uso-inexistente]] — ocho tokens rotulados para un consumidor que
  nunca se cableó. Que un token exista y esté documentado no prueba que alguien lo use.
- [[audit-ve-color-en-comentarios]] — el audit lee texto crudo, así que un hex citado dentro de un
  comentario cuenta contra el presupuesto igual que uno real.
- [[gate-estatico-no-ve-tokens-rotos]] — un token puede apuntar a una variable inexistente y pasar
  los gates de lectura de archivos: eso solo se ve resolviendo valores en el navegador.
- [[guard-valida-declaracion-contra-si-misma]] — un guard que comprueba el JSON contra el JSON, sin
  abrir nunca el CSS.

Mapa del área: [[design-system]].
