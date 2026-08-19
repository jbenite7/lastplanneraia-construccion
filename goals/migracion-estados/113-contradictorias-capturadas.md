---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-08-19
areas: [datos, lps]
fuente: goals/migracion-estados/113-contradictorias-capturadas.md
resumen: "Acta de la captura de las 113 filas contradictorias antes del recálculo: es el único registro de qué eran"
---

# Acta de captura de las 113 contradictorias

**Por qué existe este archivo.** El recálculo va a reescribir la columna `Estado` de estas filas.
Después de eso **no habrá forma de saber cuáles eran**, porque lo que las hace identificables es
justamente el estado que van a perder. Esta captura es su único registro.

Se hace **antes** de cualquier otra tarea del frente, y es un paso del plan, no una recomendación.

## La captura

```sql
SELECT project_id, Consecutivo, unique_id, Semana, Estado, Semanas_Inicio,
       Ejecutado, Fecha_Inicio, Fecha_Fin
FROM programa_consolidado
WHERE COALESCE(Titulo,0)<>1 AND Semanas_Inicio>=7
  AND Estado NOT IN ('Actividad Futura','No Requerida')
ORDER BY project_id, Consecutivo;
```

Resultado en `113-contradictorias-capturadas.csv`: **113 filas** más cabecera.

Incluye `Consecutivo` —la **clave primaria real** junto a `project_id`— y no solo `unique_id`,
porque `unique_id` está vacío en 7 686 filas de la tabla y no identifica una fila. Sin
`Consecutivo`, esta captura no permitiría volver a encontrarlas.

## Comparación con el diagnóstico heredado

El diagnóstico del frente (A) se midió sobre `aeaa7a77`. Desde entonces la tabla **creció de
65 549 a 65 557 filas**, así que la captura se compara en vez de darse por buena:

| Familia | Diagnóstico (A) | Ahora | Deriva |
|---|---:|---:|---:|
| `En Curso` | 83 | 83 | 0 |
| `Terminada` | 20 | 20 | 0 |
| `A Tiempo` | 6 | 6 | 0 |
| `Terminada Antes` | 4 | 4 | 0 |
| **Total** | **113** | **113** | **0** |

- **Familias nuevas:** ninguna. **Familias desaparecidas:** ninguna.
- **Proyectos:** los mismos dos, 68 (103 filas) y 63 (10).
- **Filas al 100% de avance con inicio futuro:** **24**, las mismas que reportó el frente (A).

**La deriva es cero.** Las ocho filas que ganó la tabla no tocaron ninguna contradictoria, así que
el diagnóstico heredado sigue describiendo exactamente lo que hay y el frente sigue sin parar.

## Lo que esta captura habilita

- Volver a encontrar las 113 después del recálculo, por `(project_id, Consecutivo)`.
- Decidir el tratamiento de las **24 al 100% de avance** con el dato delante y no de memoria.
- Comprobar, después de aplicar, que ninguna otra fila se comportó como ellas.

Detalle y las tres hipótesis sobre su causa:
`goals/estados-fuera-de-ventana/diagnostico-113-contradictorias.md`.
