---
capa: wiki
tipo: decision
estado: vigente
fecha: 2026-07-29
areas: [design-system]
fuente: memoria-claude
origen: lps-aia-goal-dark-mode-todos-modulos
resumen: Goal dark-mode-todos-los-modulos (2026-07-25) — decisiones vinculantes F0–F6, incluida la retirada del tema linen y la exclusión de AdminLTE
---
Goal abierto el 2026-07-25 en `goals/dark-mode-todos-los-modulos/`: llevar las 31 superficies
HTML de la app al dark del design system. Siete specs (F0–F6), sin ejecutar aún.

Decisiones vinculantes del usuario (grilleo de Plannotator, 17 preguntas, en
`interview-result.json`) que **no se deducen del código**:

- **El tema `linen` se retira del producto.** Un solo tema: dark. Toca 4 esquemas JSON, los 89
  grupos de `ui-groups-inventory.json` y ~12 tests.
- **`:root` pasa a servir dark** (hoy sirve linen y dark se pinta encima). Invierte qué pasa
  con lo no migrado: cae en oscuro, no en claro.
- **AdminLTE no se toca en este goal** («mejor no lo toquemos», respuesta libre). `admin/`
  quedará en dark y tokenizado pero **no migrado al DS** — única desviación deliberada del
  criterio «migración completa». Sí se vendoriza y sí unifica tokens con
  `public/css/tokens.css`.
- **Tolerancia a regresión en F1** con bitácora, salvo `/programa-general`, que mantiene
  evidencia visual por tramo (excepción acordada en chat).
- Tenemos acceso y autoridad sobre el repo externo `plan-de-compras` (F5).
- **F6 consolida en Tom Select y elimina Select2** (decidido en chat, invierte lo que proponía
  la primera redacción). Razón: Select2 arrastra jQuery, Tom Select no. Cuesta más —Select2
  está en 9 vistas y es el único con adaptador DS—, pero de paso obliga a registrar
  `tom-select` en `VENDOR_ATTACHMENTS`, hoy ausente pese a que dos manifiestos ya lo declaran.
- Orden: F0 → (F1 ∥ F4) → (F2 ∥ F3) → F6; F5 en cualquier momento tras F0.
- **F1 cierra con `styles.css` VIVO, no borrado** (decidido el 2026-07-28, corrige al plan). El
  archivo sobrevive reducido a los literales ya adjudicados como excepción (10 hex + 6 `rgba()`).
  Quedan **derogadas** la «Task final: borrar el archivo» de `plans/F1-styles-css.plan.md` y la
  métrica «líneas: 6.802 → 0» de su tabla de progreso: si las lees tal cual, creerás que la fase
  quedó a medias. Lo único que faltaba al decidirlo era promover la primitiva verde.

Ver [[css-layer-cascade]] para por qué `styles.css` en `layer(module)` gana a las
primitivas del DS — es la causa raíz que ataca F1.
