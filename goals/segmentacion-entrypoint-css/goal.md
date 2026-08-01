# Segmentación del entrypoint CSS del design system

Segmentar el entrypoint productivo `public/css/aia-design-system.css` en un núcleo sin vendors de grilla más adjuntos por vendor declarados en el manifiesto de cada superficie, de modo que ninguna superficie cargue CSS de vendors que no usa ni herede side-effects globales ajenos (en particular el bloqueo de scroll de documento de `handsontable-module.css`). El goal ataca por igual tres drivers: corrección (eliminar side-effects globales impuestos por vendors no usados), gobernanza (convertir el campo `vendors` de los manifiestos en contrato ejecutable validado por gate) y rendimiento (dejar de servir ~190 KB de CSS de grilla a superficies ligeras).

La primera iteración entrega la infraestructura completa (partición CSS, runtime PHP, gates de partición y coherencia) y migra únicamente project-selector (manifiesto existente) y las tres superficies de autenticación (`/login`, `/password/forgot`, `/password/reset`) bajo un manifiesto nuevo `auth`. Todas las demás superficies permanecen en el agregador actual, que no se modifica: bytes idénticos, riesgo cero. Programa General y sus archivos protegidos no aparecen en el diff; su migración —y la de cualquier otra superficie— es un goal posterior con su propio gate.

Este goal respeta el contrato vigente del design system (`DESIGN.md`, `docs/design-system/README.md`) y el fact del Sprint 00 de que los estilos compartidos se cargan en orden determinista desde un entrypoint: la segmentación preserva la jerarquía única de capas y un orden determinista por superficie, servido por los mismos mecanismos de cache-busting runtime ya existentes.

La comprensión compartida y verificable está en `facts.md`. El orden de implementación, archivos, pruebas y riesgos estará en `plan.md`. Estos archivos son la autoridad de ejecución del goal; la conversación de diseño que los originó es procedencia histórica.

---

## Cierre formal

**Estado:** HECHO
**Fecha de cierre:** 2026-07-22 (documentado formalmente 2026-07-31)

Infraestructura de partición CSS y runtime PHP funcionando. Superficies auth y project-selector
migradas a `renderForModule`. Equivalencia visual verificada byte a byte. Gates de partición
pasando (11/11).
