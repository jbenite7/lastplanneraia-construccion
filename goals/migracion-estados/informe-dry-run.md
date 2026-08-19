---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-08-19
areas: [datos, lps]
fuente: goals/migracion-estados/informe-dry-run.md
resumen: "Qué haría el recálculo sobre los 16 proyectos, medido sin escribir: 40.664 filas cambiarían y dos familias pueden perder información"
---

# Informe del dry-run del recálculo

**Este informe no autoriza nada.** El `--apply` exige el sí explícito del usuario, y ni el visto de
la coordinadora ni una autorización relatada lo habilitan.

Medido con el contenedor sirviendo el worktree del frente, comprobado antes de cada corrida.

## Qué haría

```
$ docker compose exec -T app php database/migrations/20260819_recalculo_estados.php

  filas que cambiarían     40 664
  filas que quedan igual   24 777
  sin semana activa           116
```

Las 116 «sin semana activa» **no se tocan**: sin semana contra la que calcular no hay estado que
derivar, así que el script las cuenta y las deja intactas.

### La prueba de que el dry-run es dry

```
filas que difieren del respaldo tras el dry-run: 0
```

No es una promesa: es una comparación contra el respaldo tomado antes, fila a fila, con `<=>` para
que las 7 705 filas de estado vacío también cuenten.

## Las transiciones

| De | A | Filas |
|---|---|---:|
| `Actividad Futura` | `Fuera de Ventana` | 13 466 |
| `No Requerida` | `Fuera de Ventana` | 12 271 |
| *(vacío)* | `Capítulo` | 7 705 |
| `En Liberación de Restricciones` | `Actividad Futura` | 5 391 |
| `Debe Iniciar esta Semana y Restricciones Pendientes` | `Debe Iniciar` | 721 |
| `Terminada Antes` | `Terminada` | 317 |
| `A Tiempo` | `En Curso` | 218 |
| **`Terminada`** | **`Capítulo`** | **163** |
| `Debe Iniciar esta Semana` | `Debe Iniciar` | 106 |
| **`Atrasada`** | **`Capítulo`** | **84** |
| `No Requerida` | `Actividad Futura` | 36 |
| **`No Requerida`** | **`Terminada`** | **31** |
| `En Liberación de Restricciones` | `Capítulo` | 28 |
| `En Liberación de Restricciones` | `Fuera de Ventana` | 22 |
| `Debe Iniciar esta Semana y Restricciones Pendientes` | `Capítulo` | 21 |
| …y once transiciones de menos de 20 filas | | |

Las cuatro grandes son las previstas y hacen lo que el frente prometía: matar los estados legacy,
llenar los vacíos y llevar a `Fuera de Ventana` lo que está a 7+ semanas.

## Dos familias que pueden perder información

Ninguna de las dos estaba en el diagnóstico previo. **Las dos van con tratamiento propuesto y sin
ejecutar: decide el usuario.**

### A · Las 24 terminadas con fecha de inicio futura

Heredadas del frente (A) y capturadas en `113-contradictorias-capturadas.csv`. Están al 100% de
avance y empiezan a siete o más semanas. El recálculo las manda a `Fuera de Ventana` y se pierde
que estaban terminadas.

**Tres opciones:**

1. **Migrarlas como al resto.** Simple; se pierde el dato.
2. **Excluirlas del recálculo.** Conservan su estado, incoherente pero informativo, y quedan para
   revisión manual. Crea una excepción permanente.
3. **Migrarlas y conservar el estado anterior en el respaldo**, que ya lo guarda por diseño, más
   sus `Consecutivo` listados en este informe.

**Recomendación: la 3.** Con el respaldo creado y verificado, «se pierde la información» deja de
ser cierto; y dejar 24 filas fuera del recálculo crea una excepción que nadie recordará en la
próxima migración.

### B · Las 296 que perderían su estado al volverse `Capítulo`

**Hallazgo nuevo del dry-run.** Son filas con `Titulo = 1` —capítulos— que hoy llevan un estado de
actividad:

| Estado actual | Filas |
|---|---:|
| `Terminada` | 163 |
| `Atrasada` | 84 |
| `En Liberación de Restricciones` | 28 |
| `Debe Iniciar esta Semana y Restricciones Pendientes` | 21 |
| **Total** | **296** |

`pg_calculate_status()` devuelve `Capítulo` en su primera línea, antes de mirar ninguna fecha, así
que el recálculo les quita el estado.

**Dos lecturas, y la diferencia importa:**

- **Basura heredada.** Un capítulo no debería tener estado; esas 163 «Terminada» son residuo y
  limpiarlas es correcto.
- **`Titulo` mal puesto.** Si son actividades reales marcadas como capítulo por error, pierden un
  estado válido **y además** quedan fuera del eje de estado para siempre.

**Cómo se distingue, y es medible:** un capítulo **agrupa filas debajo**; una actividad no. Si esas
296 no tienen hijos, son actividades mal marcadas.

> **Medición pendiente.** Requiere una ventana de contenedor que la coordinadora todavía no ha
> concedido. **Este hueco no se rellena con una estimación**: hasta medirlo, no se sabe cuál de las
> dos lecturas es la correcta, y de eso depende si estas 296 filas se migran o se apartan.

### Y 31 filas más, menores pero del mismo tipo

`No Requerida -> Terminada`: 31 filas que hoy dicen estar fuera de ventana y que el calculador
reconoce como terminadas. Aquí el recálculo **añade** información en vez de quitarla, así que no
son un riesgo — se anotan por simetría con las otras dos familias.

## Lo que falta antes de poder aplicar

1. La medición de hijos de las 296.
2. Los gates obligatorios de `docs/global-tables-architecture.md` (Task 5).
3. **El sí explícito del usuario sobre este informe.**
