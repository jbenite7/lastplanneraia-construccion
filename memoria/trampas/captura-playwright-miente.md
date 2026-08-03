---
tipo: trampa
estado: vigente
fecha: 2026-07-29
areas: [qa, pdc]
fuente: memoria-claude
origen: lps-aia-browser-qa-pitfalls
resumen: "La captura only-on-failure de Playwright se toma tras el finally { logout }: cualquier fallo parece caída de sesión"
---
**La captura de fallo de Playwright miente cuando el spec tiene `finally { logout }`**
(2026-07-29): los specs del PDC v2 envuelven el cuerpo en `try/finally` con `logout(page)`, y la
captura `only-on-failure` se toma **después** de ese teardown → cualquier fallo, sea el que sea, se
«ve» como la pantalla de login y parece caída de sesión (punto de [[sesion-cae-en-el-panel]]). Costó
un diagnóstico entero. Ir al log del contenedor (`docker compose logs app`) y mirar el código/tamaño
de la respuesta, no a la imagen: un `POST …/preview 200 693` con un cuerpo sospechosamente pequeño
fue lo que delató el bug real.

**Why:** la captura de fallo puede mostrar la pantalla equivocada y desviar el diagnóstico. **How to
apply:** ante un fallo de spec PDC v2 con `finally { logout }`, mirar primero
`docker compose logs app` antes que la captura.

Relacionado: [[sesion-cae-en-el-panel]], [[semanal-auto-dispara-mutaciones]].
