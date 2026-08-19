---
capa: fuente
tipo: evidencia
estado: vigente
fecha: 2026-07-29
areas: [pdc]
fuente: goals/pdc-preparar-b1/evidence/medicion-rematching-2026-07-29.md
resumen: Medición — qué hace hoy el sistema al mover un frente del cronograma
---

# Medición — qué hace hoy el sistema al mover un frente del cronograma

- **Fecha:** 2026-07-29
- **Spec:** `docs/superpowers/specs/2026-07-29-rematching-reprogramacion-design.md`, hecho nº 1.
- **Cómo se midió:** `tests/medicion_rematching_reprogramacion.php` sobre MySQL real (proyecto
  sintético 999950, fixture propio, limpiado al terminar). Salida íntegra al final de este archivo.
- **Por qué existe:** el spec prohíbe construir antes de medir, porque los commits del 2026-07-29
  (`92c5c13`, `a4d0c75`, `bfe7055`) ya habían tocado esta zona.

## El experimento

1. Paquete `MEDICION B2 CONCRETO` (desglose completo, 33 días) amarrado al frente `ESTRUCTURA`
   (2026-09-01) y calculado → arranque 2026-07-30.
2. Se registra `fecha_real = 2026-08-05` en el paso de orden 1 (lo ocurrido).
3. El frente se reprograma a 2026-09-22 (**+21 días**).
4. Se observa qué pasa, con y sin pulsar «Recalcular».
5. Se borra el frente del cronograma y se observa el amarre huérfano.

## Resultado: lo que YA existe (no se reimplementa)

| Pieza del spec | Estado | Dónde |
|---|---|---|
| **Detectar el desfase** | **YA EXISTE** | `PlanFechasService::desfases()` compara la `fecha_ancla` guardada contra la del cronograma en vivo. Devuelve paquete, frente, fecha guardada, fecha actual y `diasMovidos` con signo (+ atrasado / − adelantado). Endpoint `GET /plan-compras/api/plan/desfases` |
| **Avisar en la pantalla del plan** | **YA EXISTE** | Pestaña «Desfases» con conteo, y estado `desfasado` por fila que manda sobre vencido y provisional (`estadoFila()` en `pdc-app/src/lib/planFechas.ts`) |
| **No recalcular solo** | **YA EXISTE Y ES CORRECTO** | Medido: tras mover el frente, el plan **no cambia** por su cuenta. Recalcular es explícito |
| **Lo ocurrido no se borra** | **YA EXISTE** | Medido: `fecha_real = 2026-08-05` sobrevive al recálculo. Es el upsert de `a4d0c75` |
| **Frente borrado → no se reamarra solo** | **YA EXISTE** | Medido: el amarre queda apuntando al frente inexistente y `desfases()` lo reporta con `fechaActual`/`diasMovidos` en `null` |

## Resultado: lo que FALTA

### 1. Aplicar el desfase es imposible hoy — es un bug, no una carencia

Es el hallazgo que reordena el spec. **«Recalcular» no recoge la fecha nueva del frente.**

```
[8] Recalcular: {"ok":true,"calculados":1,"sinDuracion":0}
    cabecera: {"unique_id":8801,"fecha_ancla":"2026-09-01", "fecha_arranque":"2026-07-30", ...}
    => el arranque del paquete se movió 0 día(s).
[9] desfases() tras recalcular: [{... "fechaActual":"2026-09-22","diasMovidos":21}]
```

El frente se movió 21 días, se pulsa «Recalcular», y el plan sigue idéntico y **el desfase sigue
reportándose**. La causa está en `PlanFechasService::amarres()` (línea 1015): lee `fecha_ancla` de
`pdc_paquete_frente`, que es una **copia congelada** del cronograma tomada al amarrar. `calcular()`
nunca la refresca. El único camino que hoy actualiza esa fecha es desamarrar y volver a amarrar.

Consecuencia directa en la pantalla: el botón «Recalcular todo el plan» que la pestaña «Desfases»
ofrece en cada fila (`PlanFechas.tsx`) **no arregla el desfase que esa misma fila está denunciando**.
El usuario pulsa, no pasa nada visible, y el aviso sigue ahí.

### 2. No hay delta antes de aplicar

`calcular()` computa y escribe en el mismo bucle (línea 1078); no existe `simular()`,
`previsualizar()` ni `proyectar()`. Y `desfases()` dice cuánto se movió **el frente**, no cuánto se
moverían **los pasos de cada paquete** — que es lo que el spec pide mostrar.

### 3. El tablero de vencimientos no avisa

`SeguimientoService::resumen()` devuelve
`[paqueteId, nombre, frenteNombre, responsableUserId, responsableNombre, responsableHuerfano,
pasoActual, cumplidos, total, estado, atrasado, finProgramado, finProyectado]`.

Ninguna señal de «esto se calculó contra un cronograma que ya cambió». Quien mire la pestaña de
vencimientos ve fechas viejas presentadas como buenas.

## Recorte de alcance que produce la medición

Del spec original quedan **tres** trabajos, no cuatro:

1. ~~Detectar el desfase~~ — hecho. **Se retira del alcance.**
2. **Aplicar el desfase**, con simulación y confirmación (absorbe «mostrar el delta»). Sube a
   primero por el bug medido.
3. **Avisar en el tablero de vencimientos.**

Y ~~«recalcular conservando lo real»~~ y ~~«no reamarrar solo»~~ ya están verificados sobre datos:
se conservan como **regresión a no romper**, no como trabajo a hacer.

## Salida íntegra de la medición

Reproducible con:

```bash
docker compose exec app php tests/medicion_rematching_reprogramacion.php
```

```
=== MEDICIÓN: qué hace hoy el sistema al mover un frente ===

[1] Amarrado a ESTRUCTURA (2026-09-01) y calculado: {"ok":true,"calculados":1,"sinDuracion":0}

-- Plan calculado contra el cronograma original
   cabecera: {"unique_id":8801,"fecha_ancla":"2026-09-01","fecha_arranque":"2026-07-30","dias_totales":33}
   0 Elaboración de pliegos      dias=3   prog=2026-07-30→2026-08-02 real=—
   1 Entrega de pliegos          dias=2   prog=2026-08-02→2026-08-04 real=—
   2 Recibo de propuestas        dias=7   prog=2026-08-04→2026-08-11 real=—
   3 Cuadros comparativos        dias=4   prog=2026-08-11→2026-08-15 real=—
   4 Legalización                dias=5   prog=2026-08-15→2026-08-20 real=—
   5 Fabricación                 dias=10  prog=2026-08-20→2026-08-30 real=—
   6 Insumos en obra             dias=2   prog=2026-08-30→2026-09-01 real=—

[2] Se registra fecha_real = 2026-08-05 en el paso de orden 1.

[3] El frente ESTRUCTURA se reprograma de 2026-09-01 a 2026-09-22 (+21 días).

-- ¿Cambió el plan por sí solo tras mover el frente?
   (idéntico al anterior salvo la fecha_real escrita en [2])
   => ¿recalculó solo?: NO — el plan sigue con las fechas viejas

[5] PlanFechasService::desfases() devuelve:
   [{"paqueteId":478,"nombre":"MEDICION B2 CONCRETO","frenteNombre":"ESTRUCTURA",
     "fechaGuardada":"2026-09-01","fechaActual":"2026-09-22","diasMovidos":21}]
   => ¿detecta el desfase?: SÍ
   => ¿dice cuántos días se movió el PAQUETE (sus pasos)?: solo dice cuánto se movió el FRENTE.

[6] ¿Existe un método de simulación (simular/previsualizar/proyectar)?:
   NO — calcular() computa y escribe en el mismo bucle

[7] SeguimientoService::resumen() — claves de su payload:
   ["paqueteId","nombre","frenteNombre","responsableUserId","responsableNombre",
    "responsableHuerfano","pasoActual","cumplidos","total","estado","atrasado",
    "finProgramado","finProyectado"]
   => ¿avisa de cronograma cambiado?: NO

[8] Recalcular: {"ok":true,"calculados":1,"sinDuracion":0}
   cabecera: {"unique_id":8801,"fecha_ancla":"2026-09-01","fecha_arranque":"2026-07-30","dias_totales":33}
   => ¿sobrevive fecha_real?: SÍ (2026-08-05)
   => el arranque del paquete se movió 0 día(s).

[9] desfases() tras recalcular:
   [{"paqueteId":478,...,"fechaGuardada":"2026-09-01","fechaActual":"2026-09-22","diasMovidos":21}]

[10] Se borra el frente ESTRUCTURA del cronograma.
   desfases(): [{...,"fechaActual":null,"diasMovidos":null}]
   => ¿se reamarró solo a otro frente?: NO — sigue apuntando al frente 8801 inexistente
```
