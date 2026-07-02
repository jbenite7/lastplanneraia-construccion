# Matriz historica de decision humana: 13 familias

Fecha: 2026-07-02  
Alcance original: familias activas con `siempre_revision = 1` despues de depurar aliases y elementos contractuales conocidos.  
Estado actual: decisiones aprobadas por el usuario y aplicadas en BD mediante `database/migrations/20260711_apply_human_family_decisions.sql`.

Paquete estructurado de decisiones propuestas: `human-decision-proposed-actions.json`.

## Lectura ejecutiva

Estas 13 filas ya estan protegidas: el motor puede detectarlas, pero no debe dejarlas listas ni preseleccionadas en `/listado-actividades/`.

La decision pendiente no es tecnica sino de criterio de negocio:

- Si representa trabajo operativo que debe verse como actividad de seguimiento, queda en `general_pdc_familias`.
- Si representa equipo, recurso, alquiler, suministro, servicio o paquete de compra, debe pasar a `general_pdc_contractual_elements` para que lo gestione `/contratos/`.
- Si el problema es una regla demasiado amplia, se conserva la familia pero se afinan/desactivan reglas.

## Recomendacion por familia

| Familia | Evidencia viva | Riesgo observado | Recomendacion | Accion si se aprueba |
|---|---|---|---|---|
| Amenidades Especiales de Cubierta | 0 coincidencias en el corte analizado; regla de baja confianza: jacuzzi, hidromasaje, BBQ, piscina, deck. | Parece paquete especializado de compra/contrato mas que familia recurrente de seguimiento. | Mantener en revision; si aparece en cronograma real, decidir por caso. | No tocar todavia o pasar a Contratos como paquete `AMENIDADES ESPECIALES DE CUBIERTA`. |
| Aseo | Muchas coincidencias reales: `Aseo Final`, `Aseo de obra durante construccion`; aparece en JMC y Da Porto. | Puede ser actividad de seguimiento, pero tambien contrato/servicio recurrente. | Mantener como familia operativa canonica. | Quitar `siempre_revision` solo si aceptas que Aseo siempre va en Listado; Contratos genera el paquete asociado desde esa actividad. |
| Bomba de Concreto | 0 coincidencias en corte; reglas: bombeo/servicio de bombeo/concreto. | Es equipo/servicio de apoyo, no frente operativo principal. | Pasar a Contratos. | Crear/activar contractual `BOMBA DE CONCRETO`; inactivar familia y reglas de Listado. |
| Excavadora | 0 coincidencias en corte; regla: excavadora/retroexcavadora/minicargador. | Es equipo/recurso, no familia operativa. | Pasar a Contratos. | Crear/activar contractual `EXCAVADORA`; inactivar familia y reglas de Listado. |
| Malacate | 0 coincidencias en corte; regla: malacate/elevador de obra/montacargas de obra. | Es equipo/recurso. | Pasar a Contratos. | Crear/activar contractual `MALACATE`; inactivar familia y reglas de Listado. |
| Montacargas | 0 coincidencias en corte; regla: montacargas. | Es equipo/recurso. | Pasar a Contratos. | Crear/activar contractual `MONTACARGAS`; inactivar familia y reglas de Listado. |
| Motorgrua | 0 coincidencias en corte; regla: motorgrua/grua movil. | Es equipo/recurso. | Pasar a Contratos. | Crear/activar contractual `MOTORGRUA`; inactivar familia y reglas de Listado. |
| Planta de Concreto | 0 coincidencias en corte; regla: planta de concreto/mezcla. | Es equipo/planta/recurso. | Pasar a Contratos. | Crear/activar contractual `PLANTA DE CONCRETO`; inactivar familia y reglas de Listado. |
| Torregrua | 104 coincidencias en cronogramas Metrolinea; ejemplo: `Montaje torregruas`. | Aunque aparece como actividad programada, el objeto es equipo/alquiler/montaje, no familia constructiva principal. | Pasar a Contratos, con trazabilidad desde el cronograma. | Crear/activar contractual `TORREGRUA`; inactivar familia; Contratos debe sugerir paquete de montaje/alquiler segun fuente. |
| Volqueta | 0 coincidencias en corte; regla: volqueta/camion volqueta. | Es equipo/recurso de transporte. | Pasar a Contratos. | Crear/activar contractual `VOLQUETA`; inactivar familia y reglas de Listado. |
| Red de Telecomunicaciones | 481 coincidencias; aparece en JMC, Milan y Prueba. Ejemplos incluyen datos, CCTV, control de acceso y equipos para redes. | La familia es real, pero las reglas mezclan telecomunicaciones, CCTV y control de acceso. Puede contaminar Seguridad y Control. | Mantener como familia operativa, pero afinar reglas. | Mantener activa; quitar revision solo despues de separar reglas de CCTV/control de acceso si deben ir a Seguridad y Control. |
| Campamento de Obra | 51 coincidencias; ejemplos: `Campamentos y Aseo`, `Instalaciones Provisionales`, `Demoliciones`, `Entregables` bajo capitulo de campamentos. | La familia puede existir, pero la regla por capitulo esta trayendo falsos positivos. | Mantener en revision y afinar reglas antes de aprobar. | Conservar familia solo para fuentes que mencionen campamento/oficina/almacen de obra; evitar que `Demoliciones`, `Entregables` o `Instalaciones Provisionales` caigan aqui por breadcrumb. |
| Botada de Escombros | 7 coincidencias en proyecto Prueba; ejemplo mezcla seguridad industrial, PMA y retiro de escombros. | Parece retiro/disposicion/orden de compra; ademas puede estar mezclado con seguridad o ambiental. | Pasar a Contratos o reagrupar bajo una familia operativa mas amplia de retiros/demoliciones, no dejar como familia lista. | Recomendado: contractual `RETIRO Y DISPOSICION DE ESCOMBROS`; inactivar familia si confirmas que no es actividad principal de Listado. |

## Decisiones sugeridas por lote

### Aprobar como familias operativas

- Aseo.
- Red de Telecomunicaciones, pero solo despues de afinar reglas de CCTV/control de acceso.

### Pasar a Contratos

- Bomba de Concreto.
- Excavadora.
- Malacate.
- Montacargas.
- Motorgrua.
- Planta de Concreto.
- Torregrua.
- Volqueta.
- Botada de Escombros, salvo que prefieras crear una familia operativa mas amplia de retiros/disposicion.

### Mantener en revision

- Amenidades Especiales de Cubierta.
- Campamento de Obra.

## Preguntas concretas para cerrar

1. Aseo: ¿queda como familia operativa normal de `/listado-actividades/`?
2. Red de Telecomunicaciones: ¿CCTV/control de acceso deben ir dentro de Telecomunicaciones o dentro de Seguridad y Control?
3. Campamento de Obra: ¿quieres verlo como familia de seguimiento o solo como contrato/servicio preliminar?
4. Botada de Escombros: ¿debe ser contrato de retiro/disposicion o una familia operativa del listado?
5. Equipos: ¿confirmas que Bomba de Concreto, Excavadora, Malacate, Montacargas, Motorgrua, Planta de Concreto, Torregrua y Volqueta pasan todos a Contratos?

## Estado seguro actual

Mientras no haya respuesta humana, el comportamiento correcto es el actual:

- Siguen visibles para decision en Admin.
- No quedan listas para aplicacion automatica.
- No se preseleccionan en previews reales.
- No contaminan PDC perfecto de JMC, Da Porto y Milan.
