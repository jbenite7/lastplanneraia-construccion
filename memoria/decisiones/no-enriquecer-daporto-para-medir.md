---
capa: wiki
tipo: decision
estado: vigente
fecha: 2026-07-30
areas: [qa, datos]
fuente: memoria-claude
origen: lps-aia-no-enriquecer-daporto-para-medir
resumen: Enriquecer el proyecto real 73 (Da Porto) para tener una línea base ancha rompe tests que fijan su estado; usar un proyecto sintético
---
Para defender «el plan no cambió», la tentación es engordar el proyecto real 73 (Da Porto) —correr el
motor de asignación y amarrar todos los frentes que sugiera— y así comparar sobre cientos de filas en
vez de tres. **No hacerlo.**

Varios tests del PDC fijan por escrito el estado real de Da Porto. `test_pdc_v2_vencimientos.php`
asierta «los cuatro paquetes de Da Porto: tres con plan y uno no contratable»; pasar de 3 a 53
paquetes amarrados lo pone rojo, y el fallo **parece un bug del código propio** hasta que se corre la
misma prueba con el código original sobre los mismos datos.

**Why:** el proyecto 73 no es un fixture, es el dato del piloto, y las pruebas lo usan como tal.

**How to apply:** la comparación ancha va en un proyecto sintético propio (los tests del módulo usan
999903, 999940, 999941…), y el proyecto real se deja intacto para la foto fila a fila. Si ya se
enriqueció, se restaura recreando la base desde el volcado. Y ante cualquier test rojo, separar
«código» de «datos» con `git stash` antes de tocar nada: ver [[suite-php-rojos-preexistentes]].
