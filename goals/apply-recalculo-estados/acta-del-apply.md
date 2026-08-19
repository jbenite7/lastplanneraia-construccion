---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-08-19
areas: [datos, lps]
fuente: goals/apply-recalculo-estados/acta-del-apply.md
resumen: "Acta del recálculo aplicado sobre la base de desarrollo: 40.664 filas migradas, reconciliación exacta y respaldo disponible"
---

# Acta del apply del recálculo de estados

## Qué se ejecutó y con qué autorización

`database/migrations/20260819_recalculo_estados.php --apply`, sobre la base de **desarrollo**.

Autorizado por Felipe: **«Sí, apply completo»**, con el informe del dry-run delante y tres opciones
sobre la mesa —completo, excluyendo las 24 y las 296, o aplazar—. Registrado en
`decisiones/estados-consolidado-coordinadora.md` §2b **y confirmado por él directamente** a la
sesión que ejecutó. Las dos vías, porque la regla escrita en este mismo script decía que ni el
visto de la coordinadora ni una autorización relatada habilitan una migración.

**Ventana de base exclusiva** durante la ejecución y la reconciliación: la coordinadora congeló al
resto de sesiones.

## Antes de ejecutar: la red saltó

El respaldo probado horas antes **ya no cubría la base**:

```
origen=65 565  respaldo=65 557  difieren=0  sin_respaldo=8
```

Ocho filas nuevas sin respaldo. Se **rehízo** y se **volvió a probar la restauración** —2 024 filas
estropeadas sobre una copia, incluidas algunas a `NULL`, restauradas todas— antes de seguir:

```
origen=65 565  respaldo=65 565  difieren=0  sin_respaldo=0
```

Y el dry-run sobre el estado actual dio **exactamente los 40 664 cambios** del informe autorizado.
Las ocho filas nuevas cayeron en «no cambian» y «sin semana activa».

## El resultado

```
$ ... --apply
RC=0   filas que cambiarian 40664 · iguales 24781 · sin semana activa 120
```

### Reconciliación: exacta

```
filas que difieren del respaldo (= migradas)  40 664
previstas por el dry-run                      40 664
COINCIDE EXACTO
```

Y las transiciones reales, una a una, las mismas que el dry-run predijo:
`Actividad Futura -> Fuera de Ventana` 13 466 · `No Requerida -> Fuera de Ventana` 12 271 ·
`(vacío) -> Capítulo` 7 705 · `En Liberación de Restricciones -> Actividad Futura` 5 391 ·
`Debe Iniciar esta Semana y Restricciones Pendientes -> Debe Iniciar` 721 ·
`Terminada Antes -> Terminada` 317.

### Gates

`test_global_table_safety.php` RC=0 · `test_global_table_reconciliation.php` RC=0 ·
suite `--nivel=db` con 45 tests OK y **cinco rojos, todos preexistentes**.

Uno de ellos, `test_bi_filters_apply_to_charts`, apareció en `db` donde antes no estaba, y **se
comprobó que no es del apply**: no menciona la columna `Estado` ni una vez, y falla con el mensaje
idéntico al que daba antes de aplicar. Es el intermitente ya identificado con A/B real.

## Las dos familias en riesgo: qué les pasó de verdad

### Las 24 no perdieron nada, y mi análisis era pesimista

Predije que pasarían a `Fuera de Ventana` y que se perdería el dato de que estaban terminadas. **No
pasó: las 24 siguen siendo `Terminada`.**

El motivo está en el orden del calculador —`src/Legacy/estado_programa_general.php:148`—:

```php
if ($ej >= $doneThreshold) {
    return 'Terminada';
}
```

La comprobación de avance completo va **antes** que la regla de las siete semanas, así que una
actividad terminada no llega nunca a evaluarse como fuera de ventana. El código ya protegía el caso
que yo proponía proteger con el respaldo.

**Se anota como corrección de un análisis propio**, no como un acierto: la recomendación que llegó a
Felipe describía un riesgo que el código no tenía.

### Las 296 quedaron en cero

Ninguna fila con `Titulo = 1` conserva estado de actividad. Se comportaban como capítulos y ahora lo
declaran.

## La distribución nueva

**50 976 actividades reales**, y **los siete estados legacy sin una sola fila**:

| Estado | Filas | % |
|---|---:|---:|
| `Fuera de Ventana` | 25 778 | 50,6% |
| `Terminada` | 10 048 | 19,7% |
| `Actividad Futura` | 9 083 | 17,8% |
| `Atrasada` | 4 102 | 8,0% |
| `Debe Iniciar` | 1 322 | 2,6% |
| `En Curso` | 580 | 1,1% |
| `Sin Datos` | 63 | 0,1% |

**La segunda predicción también falló, y en el otro sentido:** dije que `Actividad Futura` bajaría a
~6,8% y quedó en **17,8%**. No conté con que los 5 391 `En Liberación de Restricciones` entrarían
ahí. La predicción de `Fuera de Ventana` (~51% contra 50,6% real) sí acertó.

## Cómo se deshace

El respaldo `programa_consolidado_estado_respaldo_20260819` conserva el estado de las 65 565 filas
tal como estaban antes:

```bash
LPS_CODE_ROOT="$(pwd)" docker compose run --rm --no-deps app \
  php database/migrations/20260819_recalculo_estados.php --restaurar --apply
```

Probado antes de aplicar, no solo escrito. **No borrar esa tabla** mientras la migración no se dé
por asentada.
