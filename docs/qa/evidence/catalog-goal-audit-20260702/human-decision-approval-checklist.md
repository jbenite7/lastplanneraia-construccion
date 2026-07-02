# Registro de aprobacion humana para cerrar 13 familias

Fecha: 2026-07-02  
Estado: decisiones aplicadas en BD.  
Fuente: `human-decision-proposed-actions.json`.

Este checklist convierte la matriz tecnica en decisiones de negocio. El usuario aprobo los 6 lotes y las decisiones quedaron persistidas en el catalogo.

## Regla de seguridad

- Si una familia queda como actividad de seguimiento, permanece activa en `general_pdc_familias`.
- Si una fila representa equipo, recurso, alquiler, suministro, servicio o paquete de compra, se mueve a `general_pdc_contractual_elements` y deja de salir como familia lista en `/listado-actividades/`.
- Si la familia es real pero las reglas son amplias, se mantiene protegida con revision humana hasta afinar reglas.
- Despues de cada aprobacion, debe correr el set minimo de verificacion al final de este documento.

## Lote 1: equipos y recursos para Contratos

Decision recomendada: pasar a `/contratos/`.

Familias:

- `BOMBA_CONCRETO`: Bomba de Concreto.
- `EXCAVADORA`: Excavadora.
- `MALACATE`: Malacate.
- `MONTACARGAS`: Montacargas.
- `MOTORGRUA`: Motorgrua.
- `PLANTA_CONCRETO`: Planta de Concreto.
- `TORREGRUA`: Torregrua.
- `VOLQUETA`: Volqueta.

Efecto esperado al aprobar:

- Crear o activar el elemento contractual correspondiente en `general_pdc_contractual_elements`.
- Inactivar la familia en `general_pdc_familias`.
- Desactivar reglas activas de listado que apunten a esa familia.
- Mantener trazabilidad de la decision en auditoria.

## Lote 2: Aseo

Familia: `ASEO`: Aseo.

Decision aprobada: Aseo siempre debe verse como familia operativa de `/listado-actividades/` cuando se identifique en `programa_consolidado`.

Efecto aplicado:

- Mantener activa la familia `ASEO`.
- Quitar `siempre_revision`.
- Mantener la generacion de contrato o PDC desde `/contratos/`, no desde el nombre de familia.

## Lote 3: Red de Telecomunicaciones

Familia: `RED_TELECOMUNICACIONES`: Red de Telecomunicaciones.

Decision aprobada: Telecomunicaciones y Seguridad y Control son familias validas segun lo que aparezca en `programa_consolidado`; si aparecen ambas, queda en revision humana.

Efecto aplicado:

- Mantener la familia activa porque existe en cronogramas reales.
- Afinar reglas para que datos/voz/redes queden en Telecomunicaciones.
- Separar CCTV/control de acceso en Seguridad y Control.
- Quitar `siempre_revision`.
- Forzar revision humana solo cuando ambas familias aparecen juntas.

## Lote 4: Campamento de Obra

Familia: `CAMPAMENTO`: Campamento de Obra.

Decision aprobada: es contrato, perteneciente a la familia `PRELIMINARES`.

Efecto aplicado:

- Crear elemento contractual de campamento o instalaciones provisionales.
- Inactivar la familia y sus reglas de listado.

## Lote 5: Botada de Escombros

Familia: `BOTADA_ESCOMBROS`: Botada de Escombros.

Decision aprobada: pasar a `/contratos/` como retiro y disposicion.

Efecto esperado:

- Crear o activar `RETIRO Y DISPOSICION DE ESCOMBROS` como elemento contractual.
- Inactivar `BOTADA_ESCOMBROS` como familia activa si no se aprueba como actividad principal.
- Evitar que seguridad, ambiental o demoliciones contaminen esta familia por texto parcial.

## Lote 6: Amenidades Especiales de Cubierta

Familia: `AMENIDADES_CUBIERTA`: Amenidades Especiales de Cubierta.

Decision aprobada: son compras especializadas englobadas en la familia `DOTACION_ZONAS_COMUNES`.

Efecto aplicado:

- Crear `DOTACION_ZONAS_COMUNES` como familia operativa.
- Dejar `AMENIDADES_CUBIERTA` como alias/elemento contractual, no como familia activa.
- Crear compra especializada para Contratos.

## Verificacion minima despues de aprobar

```bash
docker compose exec app php tests/test_human_decision_actions_package.php
docker compose exec app php tests/test_human_decision_matrix_coverage.php
docker compose exec app php tests/test_review_required_families_block_auto_apply.php
docker compose exec app php tests/test_learning_persistence_catalog_db.php
docker compose exec app php tests/test_listado_reclassified_real_projects.php
docker compose exec app php tests/test_pdc_three_projects_perfect_20260702.php
git diff --check
```

## Criterio de cierre

El bloqueo de las 13 familias se cierra solo cuando:

- No haya familias ambiguas activas sin decision.
- Las que queden en Listado sean familias operativas reales.
- Las que pasen a Contratos existan como elementos contractuales activos.
- Ninguna regla activa apunte a familias inactivas.
- JMC, Da Porto y Milan sigan con PDC perfecto.
