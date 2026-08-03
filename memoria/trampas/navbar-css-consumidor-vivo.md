---
tipo: trampa
estado: vigente
fecha: 2026-07-25
areas: [design-system]
fuente: memoria-claude
origen: lps-aia-navbar-css-consumidor-vivo
resumen: Borrar una hoja CSS exige grepear también el JS de runtime; el plan F0 falló al declarar navbar.css «solo comentarios históricos»
---
El plan `goals/dark-mode-todos-los-modulos/plans/F0-fundacion-tema.plan.md` justificó borrar
`public/css/navbar.css` con: «Verificado el 2026-07-25: las demás menciones de `navbar.css` en el
repositorio son comentarios históricos». Era falso: `public/js/cargarDatosGeneralesPagina2.js`
inyectaba un `<link>` a esa hoja por JS. El grep de verificación miró entrypoints CSS y prosa, no
inyecciones en runtime. Resultado: 404 + navbar sin estilos (`position:fixed`) pisando la
context-bar en las 3 rutas sin shell. `tests/test_foundation_shell_contract.mjs` también quedó roto
(ENOENT) desde ese commit sin que nadie lo notara.

**Why:** un gate que solo comprueba «el fichero no existe» y «los entrypoints no lo importan» no
prueba que el recurso esté muerto; el consumidor puede construir la URL en JS.

**How to apply:** antes de borrar un asset, grepea su nombre en `public/js`, `views` y `src`
ignorando comentarios, y ejecuta los tests que lo leen (`grep -rl "<asset>" tests/`). El guard vive
ahora en `tests/design-system/dead-theme-removal.test.mjs` («ningun codigo vivo referencia las hojas
del tema legacy borradas»). Relacionado: [[compras-migrado-shell-sidebar]].
