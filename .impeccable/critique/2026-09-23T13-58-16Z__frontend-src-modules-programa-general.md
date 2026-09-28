---
target: Programa General React (/app/programa-general)
total_score: 17
max_score: 40
na_heuristics: 
p0_count: 2
p1_count: 2
timestamp: 2026-09-23T13-58-16Z
slug: frontend-src-modules-programa-general
---
Method: dual-agent (A: revisión de diseño, opus · B: detector y navegador, sonnet)

# Crítica — Programa General React (`/app/programa-general`), 2026-09-23

## Design Health Score — 17/40 (Poor, 43 %)

| # | Heurística | Nota | Hallazgo clave |
|---|---|---|---|
| 1 | Visibilidad del estado | 2 | «Guardado» existe; no hay «última actualización» del lote; el encabezado se pierde al hacer scroll |
| 2 | Relación con el mundo real | 2 | Real en m³ contra teórico en %; fechas ISO; «ITR», «PPTO» |
| 3 | Control y libertad | 3 | Esc deshace, filtros limpiables; no se descubre |
| 4 | Consistencia | 1 | Fuente del SO en vez de Montserrat/Inter; controles de 24 y 50 px juntos; SOS sin `aia-btn` |
| 5 | Prevención de errores | 2 | Confirmaciones sí; fechas y filtros en texto libre |
| 6 | Reconocer antes que recordar | 1 | Filtros por sintaxis, leyenda al final, R0 solo en tooltip |
| 7 | Flexibilidad y eficiencia | 1 | Sin orden por columna ni atajos; 16 Tabs hasta la primera fila |
| 8 | Estética minimalista | 1 | 58 % de pantalla antes de la tabla; contexto triplicado; chips saturados en 0 |
| 9 | Recuperación de errores | 2 | Reintentar/Descartar bien; error de filtros sin campo |
| 10 | Ayuda | 2 | Leyenda fuera de alcance |

## Veredicto de especificidad

Intercambiable: CRUD genérico con tabla plana. Pesa además que el contrato AIA no esté aplicado en la
tabla (sin filete ni bandera de gravedad, sin tinte de fila) y que la tipografía del DS no se use
(el cálculo da `-apple-system`; el detector ve fuente por defecto). Detector CLI: limpio (RC 0).
Detector en navegador: 4–11 hallazgos menores (`tight-leading`, `overused-font`, `skipped-heading`
en la leyenda, `gray-on-color` en el rail verde en claro). axe: 0 violaciones en 6 estados.

## Problemas prioritarios

- P0 Densidad real: 4 filas visibles a 1180×820 con 120 actividades y nombres reales; filas de 82 px;
  58 % de la pantalla antes de la tabla.
- P0 Riesgo invisible: «Atrasada» y «En curso» son filas idénticas salvo un chip en la columna 12;
  R0 solo en tooltip; campana en todas las filas.
- P1 Encabezado sticky no funciona (contenedor de scroll mal elegido).
- P1 El cajón LPS a 1180 rompe la tabla: cabeceras truncadas, fechas superpuestas.
- P2 Tipografía y métrica fuera del contrato; acción primaria con el menor contraste de la barra en
  oscuro (4,87:1 contra 13,1:1 de las secundarias).

## Elemento por elemento (recomendación)

Rail: rail neutro en claro; proyecto una vez; un solo control de semana con marca «vigente»; filete
activo en navegación; tema como alternancia de ícono. Cabecera: título de 18–20 px Montserrat en una
banda con contexto y acciones; subtítulo con resumen útil. Barra: primaria sola a la derecha con
«Actualizado hoy 7:02»; «Exportar ▾»; «Recargar» como ícono; foco al diálogo del lote. Búsqueda:
compacta, en la línea de chips, atajo «/». Chips: neutros en reposo, sólido solo con conteo > 0 y
gravedad, ordenados por gravedad; `role=toolbar` con flechas; foco ≠ seleccionado. Filtros de
columna: embudo en el encabezado con popover tipado. Limpiar: solo con filtros activos, «Quitar N».
Tabla: contenedor de scroll con encabezado fijo; capítulos colapsables con resumen; bandera de
gravedad y tinte de fila; Estado junto a Actividad; Avance real/teórico con Δ; fechas relativas y
vencidas marcadas; Unidad+Cantidad en una columna numérica; Id/Sem. inicio/Crítica absorbidos;
señales (R0, RC, hilo con badge). Caption oculto. Leyenda como popover «?». Editor: edición en celda
con «Guardado hh:mm». Cajón: superpuesto, sin redimensionar la tabla; SOS con `aia-btn`. Móvil:
tarjeta de 3 líneas, filtros en hoja inferior.

## Personas

Alex: sin orden, sin atajos, 16 Tabs. Sam: foco y seleccionado idénticos en chips; chip enfocable por
fila; `role=status` anuncia cada tecla; diálogo del lote sin foco. Residente 6:30 a. m.: no ve de un
vistazo qué se cae esta semana; fechas vencidas sin marca; no sabe si el lote ya corrió.

## Dirección de conjunto

La pantalla abre respondiendo «¿qué se me cae esta semana?»: banda de cabecera < 120 px, franja de
señales < 40 px, tabla como contenedor de scroll con ≥ 20 filas visibles a 1180×820, gravedad en el
borde de la fila, filtros desde el encabezado, edición en celda, cajón superpuesto. Solo tokens
existentes.
