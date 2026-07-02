# Evidencia - Previews reales JMC y Da Porto

Fecha: 2026-07-01  
Tipo: preview semi-automatico sin aplicar cambios  
Alcance: `listado-actividades`, `contratos`, `pdc`

## Contexto validado

| Proyecto | project_id | Base de datos | Area | Semana usada |
|---|---:|---|---|---:|
| Optimizacion Aeropuerto JMC | 68 | optimizacionJMC | Construccion | 5 |
| Da Porto | 73 | da_porto | Construccion | 1 |

Conteo de programa consolidado consultado:

| project_id | Semana | Filas programa_consolidado |
|---:|---:|---:|
| 68 | 1 | 1862 |
| 68 | 5 | 1891 |
| 73 | 1 | 273 |

## Resultado de previews

| Proyecto | Semana | Modulo | Run ID | Total | Preseleccionadas | Gate ready | Gate review | Gate conflict |
|---|---:|---|---|---:|---:|---:|---:|---:|
| Optimizacion Aeropuerto JMC | 5 | listado-actividades | `run_51996d73cc9ce3413696efc7b4683a9f` | 1325 | 787 | 787 | 106 | 432 |
| Optimizacion Aeropuerto JMC | 5 | contratos | `run_6b9b421c1c17318cc04fdb0318f0601f` | 0 | 0 | 0 | 0 | 0 |
| Optimizacion Aeropuerto JMC | 5 | pdc | `run_173f759c81bf04ccab6cd5acb11a767e` | 0 | 0 | 0 | 0 | 0 |
| Da Porto | 1 | listado-actividades | `run_3729eb21ab7e18ef30f8a183b2c0e07e` | 136 | 100 | 100 | 24 | 12 |
| Da Porto | 1 | contratos | `run_fe78c62723622db0b902b079f6777534` | 30 | 21 | 21 | 8 | 1 |
| Da Porto | 1 | pdc | `run_111e5664f03e7a2b04520f71f00b3682` | 67 | 45 | 45 | 22 | 0 |

## Verificacion de consistencia

Los conteos fueron validados contra:

- `semi_auto_runs.total_suggestions`
- `semi_auto_runs.preselected_count`
- cantidad real de filas en `semi_auto_suggestions`
- `apply_payload._analysis.quality_gate.status`

Resultado: los `run_id` anteriores existen y los conteos coinciden entre corrida y sugerencias persistidas.

## Lectura del resultado

- JMC no se redujo a 1 propuesta. En `listado-actividades` genero 1325 propuestas porque la nueva agrupacion multidimensional separa mas que la agrupacion anterior por familia.
- Da Porto genero propuestas en los tres modulos.
- JMC no genero propuestas en `contratos` ni `pdc` para la semana 5 en esta corrida.
- El numero `1 propuesta` mencionado antes corresponde solo al E2E controlado de evidencia visual, no a los datos reales.

