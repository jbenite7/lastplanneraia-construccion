---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-08-19
areas: [design-system]
fuente: goals/cola-estados-severidad/goal.md
resumen: Cerrar los siete pendientes que dejó el trabajo de estados, severidad y color del 2026-08-19 — ejecutando lo técnico y dejando medidas las decisiones de Felipe
---

<!-- cas:cita-textual — registro del frente: cita salidas y comandos tal como se midieron -->
# Frente: cola-estados-severidad

## Objetivo
Cerrar los siete pendientes de la cola del 2026-08-19. Lo técnico se decide y se ejecuta; lo que es
de producto, proceso o diseño se **mide** y se deja listo para decidir en
[[DECISIONES_PENDIENTES]].

## Condición de hecho
Los siete pendientes atendidos: los de implementación, funcionando y verificados en pantalla a
1180×820 dark por sesión real; los de decisión, con su medición hecha y su recomendación escrita.
Suite estática 8/8. Verificación: `bash scripts/publicar.sh --solo-verificar`.

## Contención — medida antes de arrancar
- `public/js/modules/programacion_intermedia/hot.js` → último toque `e1fdbb64`, sesión ya cerrada.
- `public/js/modules/programa_general/hot.js` → último toque `c766a338` (11:38), que fue el **revert**
  del intento anterior de remapeo.
- Las dos sesiones marcadas «vivas» en `.claude/sesiones.md` llevaban horas sin actividad y sus
  frentes constaban cerrados. `semanal-fondo-por-matiz` sí tenía diez commits sin publicar sobre
  `public/css/styles.css`: **se cerró y se publicó primero** (`2fc5998e`), y hasta entonces no se
  tocó ese archivo.

## Lo que se hizo

### 1 · Remapeo de Programa General (implementación)
`Fuera de Ventana` son **25.778 de 65.633 filas (39,3 %)** y no estaba en
`normalizeEstadoToStateKey`: caía en el `default` y la clasificaba `getFallbackStateKey` por
heurística, que con `Semanas_Inicio >= 7` devuelve `actividad-futura`. En pantalla las dos se pintaban
del mismo verde.

`Con Alerta Restricciones` **no existe en ninguna fila**. Sale de `statePresentation` y del contrato.
No era un estado sino un **realce por condición del dato** —las 13.243 filas con restricciones duras
pendientes en la ventana de seis semanas—, así que sigue vivo como chip de filtro.

Dos hallazgos que no estaban en el encargo y salieron al medir:
- `Terminada` y `Sin Datos` declaraban matices distintos y **se pintaban igual**:
  `--ds-cell-state-neutral-bg` y `--ds-cell-state-sin-datos-bg` resuelven los dos a
  `var(--ds-active-surface)`.
- `Fuera de Ventana` **no tenía chip de filtro**: no había forma de aislarla ni de saber cuántas son.

**Causa de raíz:** había **dos listas del mismo vocabulario** —`statePresentation` y `rowClassMap`—
y una se quedaba atrás, con un respaldo silencioso que pinta lo no mapeado como `actividad-futura`.
`rowClassMap` ahora se **deriva** de `statePresentation`.

Medido después: **siete estados, siete fondos distintos, sin colisiones.**

### 5 · La crema de la leyenda de Intermedia (arreglo acotado)
El muestrario de «Restricciones blandas» llevaba un `style=` embebido que reservaba `#fef3c7` para
dos variables **que no existen en ninguna hoja del repo**, así que la reserva era lo que se aplicaba
siempre. Medido: **luminancia 0,893 frente a un máximo de 0,0515** en los ocho estados vecinos.

No es un estado, así que no toma matiz del catálogo: se distingue **por forma**, con borde
discontinuo sobre la superficie elevada. Queda en 0,0263, dentro del rango de sus vecinos.

### 3 y 6 · Dos guards que antes no podían ponerse rojos
- `tests/design-system/state-key-consumption.test.mjs` — **25 estados con `key`, los 25 consumidos;
  30 sin `key`**. Un estado sin clave no se puede unir con su renderer, así que no aparece como
  incumplido: simplemente no aparece. Y no era un problema de PDC: son **siete de los diez módulos**.
- `tests/design-system/coverage-closure.test.mjs` — **32 pantallas reales, 3 sin manifiesto**, 5
  rutas declaradas que ya no responden a GET, y `foundation-shell` declarando **20 rutas con cero
  escenarios**.

Los dos congelan su deuda en un archivo propio y **los dos se comprobaron en rojo a propósito**:
`RC=1` y el fallo nombrado, verde otra vez al retirar el sabotaje.

### 2, 4 y 7 · Medidas y elevadas
En [[DECISIONES_PENDIENTES]], con su medición y su recomendación. Lo que cada una destapó:
- **D-1:** `r0` no es un estado, **es un cruce**: sus 4.384 filas atraviesan cinco estados distintos.
  No cabe en el canal del matiz. La ruta crítica de Semanal sí es subconjunto de un estado: 10 de 249.
- **D-2:** activar la excepción crítica del chip **fundiría nueve estados en tres**, en tres pantallas.
- **D-5:** sí falta una primitiva, y solo una: `navigation` tiene siete variantes y **ninguna es un
  carril de pestañas**. Las 88 utilidades de BI son todas de maquetación y no compiten con el catálogo.

## Cierre

**Cerrado el 2026-08-19.** Condición de hecho verificada con salida real:

| Comprobación | Resultado |
|---|---|
| `npm run test:design-system:static` | **8/8** |
| `bash scripts/publicar.sh --solo-verificar` | 4/4 en `RC=0` |
| Programa General, 1180×820 dark, sesión real | 7 estados, 7 fondos, **sin colisiones** |
| Leyenda de Intermedia, 1180×820 dark | la mancha clara desaparece: 0,893 → 0,0263 |
| Los dos guards nuevos, saboteados a propósito | `RC=1` y el fallo nombrado |

**Límite declarado:** las mediciones son contra la base de **desarrollo**. Producción no se tocó y
sigue necesitando su propia autorización.

## Archivos de este goal
- [[DECISIONES_PENDIENTES]]
- [[goals/ds-f1a-estados-severidad/goal]] · [[goals/semanal-fondo-por-matiz/goal]]
- [[docs/superpowers/specs/2026-08-19-estados-severidad-contrato-design]]
- [[memoria/goals/estado]]
