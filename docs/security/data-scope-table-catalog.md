---
capa: contrato
tipo: seguridad
estado: vigente
fecha: 2026-08-28
---

# Catálogo de alcance de tablas

`TableScopeCatalog` deriva las categorías del schema activo; no mantiene una
segunda lista de tablas operativas. Una tabla con `project_id` es `Project`,
salvo la excepción explícita `Identity`. Una tabla sin `project_id` sólo puede
ser `Identity` o `System` cuando figura en `TableScopeDefinitions`; en cualquier
otro caso queda `Unclassified` y el guard futuro debe denegarla.

## Dueños explícitos

- `Identity`: identidad, membresía y RBAC. La lista exacta está en
  `TableScopeDefinitions::IDENTITY`.
- `System`: configuración, diccionarios, auditoría y metadatos compartidos. La
  lista exacta está en `TableScopeDefinitions::SYSTEM`.
- `Project`: tablas que el schema declara con `project_id`, excepto `Identity`.

No se añaden nombres por coincidencia de prefijo ni para convertir un resultado
de auditoría en verde. Toda alta en `Identity` o `System` debe justificar aquí
su dueño y su semántica.

## Registro de denegadas

La auditoría de 2026-08-29 encontró la siguiente tabla en el schema de
desarrollo:

| Tabla | Estado | Razón |
| --- | --- | --- |
| `backup_licify_general_informe_pdc_20260612` | `denied` | No tiene `project_id` ni una definición explícita de identidad o sistema. |

Task 7 debe converger los hallazgos estructurales antes de activar `--enforce`.
