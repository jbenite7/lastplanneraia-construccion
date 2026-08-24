---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-08-13
areas: [design-system, lps]
fuente: public/css/programacion-semanal.css:1827, feedback del usuario con captura de produccion
resumen: un token de marca pensado como fondo (brand-primary-dark) usado como color de texto en dark da ~1,4:1 y el texto desaparece; la tinta en superficies del sistema hereda o usa los pares active-text-*
---
El modal TNP de `/programacion-semanal` mostraba las opciones del desplegable Select2
casi invisibles: `.tnp-option-title` usaba `--ds-color-brand-primary-dark` como `color`.
Ese token es un verde de marca pensado para **fondos** (botones, cabeceras); como tinta
sobre las superficies oscuras del desplegable daba ~1,4:1. Lo destapo el usuario con una
captura de produccion el 2026-08-13.

**La trampa:** los tokens `--ds-color-brand-*` parecen intercambiables con los de texto
porque comparten prefijo y paleta, pero no llevan garantia de contraste contra las
superficies. La tinta segura en superficies del sistema es `inherit` (deja mandar al
componente contenedor, que ya resuelve reposo/resaltado) o los pares
`--ds-active-text-primary`/`-secondary`.

**Segunda mitad del mismo hallazgo:** el campo de busqueda del desplegable Select2 no
tenia ninguna regla propia en todo el arbol y quedaba con los estilos del user-agent
(caja clara con borde `#aaa`, o `#3b3b3b` segun `color-scheme`). El adaptador
`public/css/design-system/adapters/select2.css` lo tokeniza desde el 2026-08-13, con lo
que cubre todos los desplegables Select2 de la app, no solo el TNP.

Relacionada: [[css-layer-cascade]] (el adaptador gana al vendor por orden de capas, no
por especificidad) y [[valor-declarado-no-es-valor-computado]] (el contraste se midio
sobre valores computados en el navegador, no sobre el CSS declarado).
