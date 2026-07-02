# Auditoria humana aplicada del catalogo de familias

Fecha: 2026-07-02  
Fuente: `general_pdc_familias` activo despues de depurar aliases y elementos contractuales conocidos.

## Estado confirmado

- Los aliases activos ya viven en `general_pdc_family_aliases`.
- Los elementos contractuales conocidos ya viven en `general_pdc_contractual_elements`.
- Las reglas activas ya no apuntan a familias inactivas.
- `OperationalFamilyPolicy` lee aliases y elementos contractuales desde BD.
- El admin bloquea que un alias o elemento contractual activo vuelva a aprobarse como familia operativa.

## Decisiones aplicadas

Estas filas fueron revisadas por el usuario y aplicadas en BD. Ya no queda ninguna familia activa con `siempre_revision = 1`.

| Familia revisada | Categoria | Decision aplicada | Resultado |
|---|---|---|---|
| Amenidades Especiales de Cubierta | ACABADOS | Agrupar bajo Dotación Zonas Comunes y Contratos | `AMENIDADES_CUBIERTA` inactiva; `DOTACION_ZONAS_COMUNES` activa; elemento contractual activo. |
| Aseo | ACABADOS | Mantener como familia operativa | `ASEO` activa sin revision obligatoria global. |
| Bomba de Concreto | EQUIPOS | Pasar a Contratos | Familia inactiva; elemento contractual activo. |
| Excavadora | EQUIPOS | Pasar a Contratos | Familia inactiva; elemento contractual activo. |
| Malacate | EQUIPOS | Pasar a Contratos | Familia inactiva; elemento contractual activo. |
| Montacargas | EQUIPOS | Pasar a Contratos | Familia inactiva; elemento contractual activo. |
| Motorgrua | EQUIPOS | Pasar a Contratos | Familia inactiva; elemento contractual activo. |
| Planta de Concreto | EQUIPOS | Pasar a Contratos | Familia inactiva; elemento contractual activo. |
| Torregrua | EQUIPOS | Pasar a Contratos | Familia inactiva; elemento contractual activo. |
| Volqueta | EQUIPOS | Pasar a Contratos | Familia inactiva; elemento contractual activo. |
| Red de Telecomunicaciones | INSTALACIONES | Mantener como familia y separar Seguridad y Control | Telecom activa sin revision global; CCTV/control de acceso va a `SEGURIDAD_CONTROL`; ambas juntas fuerzan revision humana. |
| Campamento de Obra | PRELIMINARES | Pasar a Contratos bajo Preliminares | Familia inactiva; elemento contractual activo con `familia_id` de Preliminares. |
| Botada de Escombros | URBANISMO | Pasar a Contratos | Familia inactiva; elemento contractual activo como retiro/disposicion de escombros. |

## Evidencia de comportamiento seguro

La prueba `tests/test_review_required_families_block_auto_apply.php` valida que no quedan revisiones obligatorias globales y que Telecomunicaciones + Seguridad y Control bajan a revision cuando aparecen juntas.

Las pruebas `tests/test_learning_persistence_catalog_db.php` y `tests/test_da_porto_jmc_family_patterns.php` validan que las decisiones quedaron persistidas en BD y en reglas de matching.

## Matriz de decision

La matriz historica de estas 13 decisiones esta en `human-decision-matrix-13-families.md`.

## Regla de avance propuesta

Si aparecen nuevas familias ambiguas, repetir el mismo patron: decidir si son familia operativa, alias o contrato; persistir en BD; y agregar prueba enfocada para que el aprendizaje no se pierda.
