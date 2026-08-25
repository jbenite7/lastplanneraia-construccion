---
capa: fuente
tipo: spec
estado: cerrado
fecha: 2026-08-24
areas: [bi, rbac]
fuente: brainstorming con Felipe, 2026-08-24
resumen: "amarra las audiencias 'gerencia' y 'obra' de la Torre de Control a roles concretos del RBAC, añade el Residente de Obra con acceso propio, y reemplaza D72 (puerta sin conmutador) por un interruptor explícito para Residente y Admin"
project: lps-aia
---

# Reparto de lienzos de la Torre de Control por rol

## Objetivo

Decidir, para cada rol del sistema, qué lienzo de la Torre de Control ve por defecto al entrar y
si puede ampliar esa vista. Es la continuación de la sección 18.1 de
[[2026-08-20-replanteo-control-tower-design]], que ya definió **qué** hojas componen cada lienzo
(gerencia: 4 hojas · obra: 7 hojas) pero no **quién** —en términos de rol RBAC concreto— entra a
cada uno. Hoy solo Admin (`A`) y Director de Obra (`D`) tienen la capacidad
`internal.bi.preview`; el Residente (`R`), que vive la reunión diaria y semanal que esas pantallas
sirven, no tiene acceso.

## Alcance de esta ronda

Solo tres roles: **Admin**, **Director de Obra**, **Residente**. Los demás roles del catálogo
(Ambiental, SST, SG, OT, DCV, Subcontratista, Visualizador) quedan fuera **a propósito** — decisión
de Felipe, 2026-08-24 — no por olvido. Se revisan en una ronda futura si aparece una necesidad
concreta.

## El reparto

| Rol | Lienzo por defecto | Pantalla de entrada | Ampliación disponible |
|---|---|---|---|
| **Residente (`R`)** | Obra (7 hojas: Intermedia, Programa General, Semanal, Curva S, Plan de Compras, Proveedores, Responsables) | Programación Intermedia | Interruptor propio, en su pantalla: pasa de ver solo su equipo en Responsables (filtro por defecto, D46) a ver toda la obra |
| **Director de Obra (`D`)** | Obra (7 hojas) | Programación Intermedia | Ninguna necesaria — ya ve su obra completa por ser el jefe de esa obra (D46: "el jefe ve su equipo", y el Director es el jefe de toda la obra) |
| **Admin (`A`)** | Ninguno fijo | El último lienzo que haya elegido; gerencia la primera vez | Elige libremente entre el lienzo de gerencia y el de obra, sin restricción — es auditoría/soporte, no una audiencia operativa |

**Navegación:** para el Residente, la Torre entra **visible en su menú** desde el día en que reciba
la capacidad — no repite el patrón de "acceso por URL sin entrada en el sidebar" que tuvo la Torre
misma entre el 13 y el 20 de agosto. Decisión de Felipe, 2026-08-24.

## Decisión que esto reemplaza

**D72** (spec del 2026-08-20, sección 18.1: *"La puerta se elige automáticamente por rol,
normalizado con `RbacService::normalizeRole()`, sin conmutador"*) queda **reemplazada por esta
spec**, no derogada en silencio. La puerta sigue resolviéndose por rol normalizado — eso no cambia
— pero dos roles (`R` y `A`) ahora tienen un interruptor sobre esa puerta automática:

- Residente: interruptor de alcance de datos (su equipo ↔ toda la obra), dentro del mismo lienzo.
- Admin: interruptor de lienzo completo (gerencia ↔ obra), porque no tiene audiencia operativa
  propia.

La prohibición de la sección 17 de la spec padre —**"variantes por audiencia dentro de una hoja
compartida"**— sigue vigente y no la toca esta decisión: el interruptor del Residente no crea una
variante de Responsables por audiencia, cambia el **filtro de datos** que ya existía en el
servidor (D46), no la hoja.

## Lo que no cambia

- El contenido y el orden de las hojas dentro de cada lienzo (sección 8 de la spec padre).
- Que Responsables filtra por equipo del jefe por defecto (D46) — el interruptor del Residente
  amplía ese filtro, no lo reemplaza por otro criterio.
- Que ningún rol fuera de `A`/`D`/`R` tiene hoy capacidad de abrir la Torre.

## Implementación (referencia, no exhaustiva — se detalla en el plan)

- `RbacCatalog::PERM_INTERNAL_BI_PREVIEW` se extiende a `R`.
- El filtro de equipo de Responsables (D46, ya implementado como filtro de servidor) necesita un
  parámetro de alcance (`propio` / `obra`) que el interruptor del Residente controla; sin ese
  parámetro, por defecto es `propio`.
- La pantalla de entrada (Intermedia para `D`/`R`, última elegida o gerencia para `A`) se resuelve
  en el mismo punto donde hoy vive la puerta automática de D72 — no es una ruta nueva, es la misma
  puerta con una excepción de dos roles.
- El interruptor de Admin no requiere nueva capacidad: Admin ya puede abrir cualquier ruta de la
  Torre: es una preferencia de UI, no un permiso nuevo.

## Fuera de alcance

- Los roles no listados en "Alcance de esta ronda".
- El interruptor de replanificación de Curva S y los demás pendientes abiertos de la spec padre
  (sección 18), que siguen abiertos y no los toca esta decisión.
- Cualquier cambio a las 8 hojas mismas: esta spec solo reparte, no rediseña contenido.

---

## Estado verificado — cerrado

Verificado contra el código el 2026-08-25. **`estado: cerrado` es una afirmación deliberada**, no el valor por defecto del backfill.

**Evidencia:** idem plan hermano

Criterio y método: [[docs/superpowers/plans/2026-08-25-estado-real-de-planes-y-specs]].
