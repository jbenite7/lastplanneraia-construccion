---
tipo: concepto
estado: vigente
fecha: 2026-08-04
areas: [design-system]
fuente: scripts/design-system-contracts.mjs, docs/design-system/homologation.json, family-approvals.json
resumen: "Las diez familias visuales avanzan por candidatos: homologation.json declara cuál está activo y family-approvals.json guarda la aprobación humana trazable que lo permitió"
---
# Homologación: cómo una familia visual gana su aspecto

El design system no aprueba componentes sueltos sino **familias visuales**, y son exactamente diez
(`scripts/design-system-contracts.mjs:167-170`): `foundations`, `shell-navigation`,
`page-structure`, `actions`, `forms-filters`, `states-feedback`, `data-display`, `overlays`,
`vendor-adapters` y `bi-primitives`.

Dos archivos se reparten el trabajo:

- **`homologation.json`** declara, por familia, qué **candidatos** visuales existen y cuál es el
  `activeCandidate`. Es el estado: qué se está probando y qué está vivo.
- **`family-approvals.json`** guarda las **aprobaciones humanas trazables**: quién aprobó
  (`approvedBy`), con qué referencia (`approvalRef`), cuándo, en qué temas y viewports, y con qué
  evidencia.

**El gate cierra el círculo en ambos sentidos** (`scripts/design-system-contracts.mjs:196-244`):
un candidato no puede figurar `approved` sin su aprobación registrada, y una aprobación no puede
apuntar a un candidato inexistente. Toda aprobación sigue exigiendo tema `dark` y los viewports
`1180x820`/`1440x900` como **requeridos** (`REQUIRED_VIEWPORTS`, `:194`) — pero desde DS-032
(2026-08-07) `SUPPORTED_VIEWPORTS` (`:193`) también acepta `390x844`, así que una aprobación en ese
viewport móvil **ya no** es inválida por construcción; sigue sin ser obligatoria y ninguna familia
lo declara todavía (corregido el 2026-08-10, medido contra el código actual).

**Dónde se ve.** El laboratorio (`/internal/design-system`) renderiza el estado real de cada
familia leyendo estos dos archivos (`src/Controllers/Internal/DesignSystemLabController.php:19-53`).
Lo que ves ahí no es una maqueta: es `homologation.json` interpretado.

**Para qué sirve saberlo.** Si quieres cambiar el aspecto de algo compartido, el camino no es
editar el CSS del módulo: es proponer un candidato en su familia, conseguir la aprobación en el
laboratorio, y dejar que `activeCandidate` cambie. La aprobación humana es bloqueante por contrato
(`docs/design-system/contracts/sprint-review-close.md`).

Vecinos: [[manifiesto-de-modulo]] para lo que un módulo declara consumir ·
[[madurez-y-api-estable]] para lo que pasa después de la aprobación. Mapa del área:
[[design-system]].
