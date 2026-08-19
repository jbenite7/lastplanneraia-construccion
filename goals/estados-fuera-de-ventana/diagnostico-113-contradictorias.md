---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-08-19
areas: [datos, lps]
fuente: goals/estados-fuera-de-ventana/diagnostico-113-contradictorias.md
resumen: "Las 113 filas que empiezan a 7+ semanas y no se llaman Actividad Futura ni No Requerida: qué son, dónde están y tres hipótesis sin elegir"
---

# Las 113 filas contradictorias

**Diagnóstico, no corrección.** Este frente las mide y las describe; corregirlas es del frente de
migración. No se ha tocado un solo dato.

## Qué son

Actividades que arrancan a **siete o más semanas** de la semana activa —o sea, que la regla de
`Fuera de Ventana` alcanza— y que sin embargo llevan un estado incompatible con estar tan lejos.

```sql
SELECT Estado, COUNT(*) n, MIN(Semanas_Inicio) mn, MAX(Semanas_Inicio) mx,
       ROUND(AVG(COALESCE(Ejecutado,0)),3) ej, COUNT(DISTINCT project_id) p
FROM programa_consolidado
WHERE COALESCE(Titulo,0)<>1 AND Semanas_Inicio>=7
  AND Estado NOT IN ('Actividad Futura','No Requerida')
GROUP BY Estado ORDER BY n DESC;
```

| Estado | Filas | Semanas de inicio | Ejecutado medio | Por qué es contradictorio |
|---|---:|---|---:|---|
| `En Curso` | 83 | 7 a 33 | 0,346 | Tiene 35% de avance medio y todavía no ha empezado |
| `Terminada` | 20 | 7 a 20 | 1,000 | Está terminada al 100% y empieza dentro de dos a veinte semanas |
| `A Tiempo` | 6 | 8 a 11 | 0,295 | Se la juzga puntual contra una fecha que no ha llegado |
| `Terminada Antes` | 4 | 7 a 8 | 1,000 | Terminada «antes» de un inicio futuro |
| **Total** | **113** | | | |

`Terminada` y `Terminada Antes` son las más duras: **24 filas al 100% de avance cuya fecha de
inicio está en el futuro**. No hay lectura en la que eso sea coherente.

## Dónde están

**En dos proyectos de los dieciséis**, no repartidas:

```
proyecto 68: 103 filas
proyecto 63:  10 filas
```

Eso descarta que sea un defecto del calculador —que corre igual para todos— y apunta a los datos de
esos dos cronogramas. El proyecto 68 tiene 20 685 filas, de las cuales **13 646 (66%) empiezan a 7+
semanas**: es un cronograma largo, y eso hace más probable que arrastre inconsistencias de una
importación.

## Tres hipótesis, sin elegir

No se elige porque elegir exige datos que este frente no tiene: el histórico de importaciones y las
versiones de cronograma de esos dos proyectos.

1. **Fechas mal importadas desde Project.** Si `Fecha_Inicio` viniera desplazada en una versión del
   cronograma, el avance sería el real y la fecha la equivocada. **A favor:** explica que se
   concentren en dos proyectos y que el mayor sea el más afectado. **En contra:** no explica por sí
   sola que el estado guardado tampoco se haya recalculado después.
2. **`Semanas_Inicio` calculada contra una semana activa que ya no es la actual.** El valor se
   escribe al guardar, contra la semana de ese momento; si el proyecto avanzó de semana y esas
   filas no se volvieron a guardar, el offset quedó viejo. El proyecto 68 tiene **11 semanas
   distintas** registradas. **A favor:** explica la coexistencia de estado y offset incoherentes
   sin necesidad de datos malos. **En contra:** debería producir muchas más de 113 filas.
3. **Estados escritos por una versión anterior del calculador o a mano.** Serían residuo, como
   `No Requerida`. **A favor:** encaja con que cuatro de los estados implicados (`A Tiempo`,
   `Terminada Antes`) sean de los que **hoy no produce nadie**. **En contra:** `En Curso` y
   `Terminada` sí los produce el calculador actual, y son 103 de las 113.

**Ninguna hipótesis explica las cuatro familias a la vez**, así que probablemente hay más de una
causa mezclada.

## Qué implica para el frente de migración

- **Un recálculo masivo las arreglaría en silencio**, escribiéndoles `Fuera de Ventana` sin que
  nadie sepa qué eran. Si se quiere entender la causa, **hay que capturarlas antes de migrar**: la
  consulta de arriba, guardada con sus `unique_id`.
- **Las 24 al 100% de avance merecen mirada aparte.** Un recálculo las mandaría a `Fuera de
  Ventana` y **se perdería el dato de que estaban terminadas**, que es información real aunque la
  fecha sea incoherente. Es la única familia donde migrar puede destruir algo.
- Son el **0,2%** de las 50 966 actividades. No bloquean la migración; sí merecen decidirse en vez
  de arrastrarse.
