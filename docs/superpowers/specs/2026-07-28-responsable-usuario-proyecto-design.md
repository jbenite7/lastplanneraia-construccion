---
capa: fuente
tipo: spec
estado: cerrado
fecha: 2026-07-28
areas: [proceso]
fuente: docs/superpowers/specs/2026-07-28-responsable-usuario-proyecto-design.md
resumen: pdcplanpaquete.responsable es hoy VARCHAR(100) NOT NULL DEFAULT '': la pestaña «Plan» lo escribe a mano. B1 va a colgar de ese campo el filtro «mis paquetes» y…
---

# Responsable de paquete: de texto libre a usuario del proyecto

**Fecha:** 2026-07-28
**Fase:** previa a B1 (Seguimiento)
**Repos:** `lps-aia-pdc` (rama `pdc-a4-fechas`) y `plan-de-compras`

## Problema

`pdc_plan_paquete.responsable` es hoy `VARCHAR(100) NOT NULL DEFAULT ''`: la pestaña «Plan»
lo escribe a mano. B1 va a colgar de ese campo el filtro «mis paquetes» y las notificaciones,
y una cadena escrita a mano no identifica a nadie — «Juan Pérez», «juan perez» y «J. Pérez»
son tres personas distintas para la base y la misma en la obra.

**El momento es ahora y no después:** la tabla está **vacía** (0 filas, verificado sobre el
MySQL de Docker). No hay ni un responsable escrito, ni en desarrollo ni en producción — la
tabla nació en esta rama, que aún no se mergea. Por eso este cambio **no necesita backfill ni
reconciliación de nombres**, y por eso la columna vieja puede desaparecer sin coste. Dentro de
un mes, con responsables reales escritos por usuarios reales, ninguna de las dos cosas sería
gratis.

## Decisiones (grilleo de 12 preguntas, 2026-07-28)

| Decisión | Elegido | Consecuencia de diseño |
|---|---|---|
| Candidatos | Solo miembros del proyecto | `JOIN project_members` — DAPORTO tiene 17 |
| Sale del proyecto | Se le saca; sus paquetes quedan sin responsable | FK `ON DELETE SET NULL`, nunca RESTRICT |
| Cuántos | Uno solo | Columna escalar, no tabla puente |
| Qué se guarda | Solo el enlace | `responsable_user_id INT NULL`, sin nombre congelado |
| Filtro por cargo | Cualquier miembro | Sin filtro por `cargo` — el dato está sucio |
| Inactivos | No para elegir, sí para mostrar | `activo=1` filtra la lista; la lectura resuelve siempre |
| Sin responsable | Permitido y visible | `NULL` es estado válido y contable |
| Asignación en masa | Sí | El endpoint acepta N paquetes |
| Auditoría | Solo el último cambio | `responsable_asignado_por`, `responsable_asignado_at` |
| Usuarios de prueba | Dejarlos | Fuera de alcance; no se tocan datos ajenos |
| Columna vieja | Borrarla | `DROP COLUMN responsable` en la misma migración |
| Alcance | Todos los proyectos | Sin ramas por obra |

## Modelo de datos

```sql
ALTER TABLE pdc_plan_paquete
  ADD COLUMN responsable_user_id INT NULL DEFAULT NULL AFTER duracion_provisional,
  ADD COLUMN responsable_asignado_por VARCHAR(100) NOT NULL DEFAULT '',
  ADD COLUMN responsable_asignado_at DATETIME NULL DEFAULT NULL,
  ADD KEY idx_ppp_responsable (project_id, responsable_user_id),
  ADD CONSTRAINT fk_ppp_responsable FOREIGN KEY (responsable_user_id)
      REFERENCES general_usuarios (id) ON DELETE SET NULL,
  DROP COLUMN responsable;
```

`ON DELETE SET NULL` implementa la decisión «se le saca y sus paquetes quedan sin
responsable», pero solo cubre el borrado de la **ficha de usuario**. Salir de
`project_members` no borra al usuario, así que ese caso no lo cubre la FK: lo resuelve la
lectura, que marca como *huérfano* al responsable que ya no es miembro (ver abajo). Esa
distinción es deliberada — no se borra el dato, se señala.

El índice lidera por `project_id` como exige `docs/global-tables-architecture.md`.

## Invariante que no se puede romper

`calcular()` conserva el responsable mediante un `ON DUPLICATE KEY UPDATE` que **no lista** esa
columna: lo que no se lista, MySQL lo conserva. Hay un test que lo cubre
(«`responsable` sobrevive a un recálculo»). Las tres columnas nuevas quedan igualmente fuera
de esa lista, y el test se adapta para asertar sobre `responsable_user_id`.

## Lectura: tres estados, no dos

`plan()` devuelve por paquete:

- **`responsableUserId: null`** → sin asignar. Contable: el resumen dice cuántos faltan.
- **asignado y miembro vigente** → `{id, nombre}`.
- **asignado pero ya no es miembro del proyecto (o `activo=0`)** → `{id, nombre, huerfano: true}`.

El tercer estado es lo que impide que sacar a alguien del proyecto haga desaparecer el nombre
de la pantalla en silencio. Se muestra, marcado, y entra en el conteo de «pendientes por
reasignar».

## API

- **`GET /plan-compras/api/plan/responsables`** (nuevo) — miembros del proyecto activos,
  `{id, nombre, cargo}`, ordenados por nombre. Patrón ya existente en
  `ProjectSelectorController.php:36`. RBAC de lectura, igual que el resto del plan.
- **`POST /plan-compras/api/plan/responsable`** (existente, cambia el contrato) — pasa de
  `{paqueteId, responsable}` a **`{paqueteIds: [...], responsableUserId: int|null}`**. Acepta
  N paquetes (asignación en masa) y `null` para desasignar. Valida que el usuario sea miembro
  vigente del proyecto: asignar a alguien de otro proyecto se rechaza con 422, no se guarda.

Romper el contrato en vez de versionarlo es correcto aquí: el único consumidor es la SPA de
este mismo repo, se despliegan juntos, y no hay datos guardados con el formato viejo.

## SPA

`PlanFechas.tsx`: el input de texto pasa a desplegable poblado por el endpoint nuevo, con
opción vacía («Sin asignar»). Se añade selección múltiple + «asignar responsable a los
seleccionados», reutilizando la mecánica que ya existe en la pantalla de paquetes. El resumen
gana un contador de «sin responsable». Un responsable huérfano se pinta marcado.

La lógica pura (agrupar, contar pendientes, decidir el estado de una fila) va a
`src/lib/planFechas.ts`, que ya existe y está cubierto por Vitest — la política del repo es
que lo verificable se prueba y la estética se mira.

## Verificación

- Migración aplicada sobre el MySQL real de Docker (no mocks), reaplicable sin error.
- `tests/test_pdc_v2_plan_fechas.php` en 0 FAIL (hoy 270 PASS), con casos nuevos:
  responsable sobrevive al recálculo, asignación en masa, rechazo de usuario ajeno al
  proyecto, `NULL` como estado válido, y el estado huérfano.
- Ratchet intacto: `tests/test_pdc_v2_brecha_daporto.php` en 7 diferencias.
- `npm run test` y `npm run build` en verde en `plan-de-compras`.
- Bundle copiado **a mano** a `lps-aia-pdc/public/pdc-app/assets/` (`npm run sync` apunta al
  working tree principal, que es de otras sesiones).

## Fuera de alcance

Limpiar los usuarios de prueba de DAPORTO, arreglar el usuario con el correo en el campo
`cargo`, y notificaciones — eso es B1.

---

## Estado verificado — cerrado

Verificado contra el código el 2026-08-25. **`estado: cerrado` es una afirmación deliberada**, no el valor por defecto del backfill.

**Evidencia:** columna responsable_user_id usada en PlanFechasService, SubpaquetesService y SeguimientoService; tests/browser/pdc-v2-responsable.spec.mjs

Criterio y método: [[docs/superpowers/plans/2026-08-25-estado-real-de-planes-y-specs]].
