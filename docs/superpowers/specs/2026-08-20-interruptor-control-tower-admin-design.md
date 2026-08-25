---
capa: fuente
tipo: spec
estado: cerrado
fecha: 2026-08-20
areas: [bi, rbac, admin]
fuente: brainstorming con Felipe, 2026-08-20
resumen: interruptor global del Control Tower administrable desde /admin, sobre una tabla de flags nueva, con fail-safe a solo-Admin
---

# Interruptor del Control Tower desde /admin

## Objetivo

Poder prender y apagar la visibilidad del módulo BI (Control Tower) desde `/admin`, sin deploy.
Decisiones de Felipe (2026-08-20): interruptor **global** (no por rol ni por usuario), y con el
switch en apagado **el Admin siempre sigue entrando** — la lección del primer ocultamiento
(2026-08-13), que dejó por fuera a quien lo pidió.

## Semántica

| Estado del flag | Quién ve/abre el Control Tower |
|---|---|
| Encendido (`1`) | Los roles con `internal.bi.preview` en código — hoy `A` y `D` |
| Apagado (`0`) | Solo `A`, por URL directa o accesos pintados |
| Fila ausente, tabla ausente o error de lectura | **Como apagado** (solo `A`) — fail-safe |

El flag no concede nada por sí mismo: **restringe sobre** lo que el código ya concede. Los roles
siguen viviendo en `RbacManager` con sus tests; el interruptor solo decide si la ampliación a
no-Admin está activa.

## Piezas

### 1. Tabla `general_flags` (migración nueva)

```sql
CREATE TABLE general_flags (
  clave VARCHAR(100) PRIMARY KEY,
  valor VARCHAR(255) NOT NULL,
  actualizado_por VARCHAR(100) NOT NULL,
  actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Global de verdad (sin `project_id`, a propósito: el gate del Control Tower ya es global por
diseño). La migración siembra `('bi.control_tower.visible', '1', 'migracion')` — encendido,
porque el estado publicado hoy es «A y D lo ven» y el interruptor no debe cambiar el
comportamiento al llegar.

### 2. Lectura: `App\Core\FlagsService` (clase nueva, `src/Core/`)

- `isOn(string $clave): bool` — `SELECT valor FROM general_flags WHERE clave = ?` vía
  `Database` (prepared). Devuelve `true` solo si la fila existe y `valor === '1'`.
  Cualquier `Throwable` → `false`. Cache por request (propiedad estática), sin TTL ni
  invalidación: una consulta por request es el costo aceptado.
- `BiPreviewAccessPolicy::canOpen()` cambia a:
  - rol `A` → `true` (sin consultar el flag);
  - rol con `internal.bi.preview` pero no `A` → `FlagsService::isOn('bi.control_tower.visible')`;
  - resto → `false`.
- El parámetro `$roleOverride` de pruebas se conserva; se añade un override de flag equivalente
  para poder probar en nivel `puro` sin base.

### 3. Escritura: pantalla en `/admin`

`admin/` es una mini-app aparte y así se respeta: controlador y vista propios.

- Ruta `GET /admin/modulos` — lista los flags conocidos (hoy uno) con su estado, quién lo cambió
  y cuándo. Solo rol `A` (mismo guard que el resto del panel).
- Ruta `POST /admin/modulos` — cambia el valor (`UPDATE ... SET valor=?, actualizado_por=?`),
  con el CSRF del admin (`admin/src/Core/Security.php`). `actualizado_por` = usuario en sesión.
- Enlace en la navegación del admin, sección de configuración.
- El admin escribe con SQL propio contra la tabla compartida (como hace con `general_usuarios`);
  no importa clases de `src/`.

### 4. Tests

- `tests/test_bi_preview_gate.php` se amplía: con flag apagado, `D` no abre y `A` sí; con flag
  encendido, ambos abren; sin tabla/fila, se comporta como apagado. (Los casos existentes de
  roles sin capacidad no cambian.)
- Test nuevo de `FlagsService` (nivel `db`): leer fila existente, fila ausente, valor distinto
  de `'1'`.
- Smoke del admin (nivel `http`): `GET /admin/modulos` responde para `A` y niega a otro rol;
  `POST` sin CSRF es rechazado.

## Errores y bordes

- **Fallo de base al leer el flag:** el módulo queda solo-Admin. Nunca 500 por el flag.
- **Valores raros en `valor`:** todo lo que no sea `'1'` es apagado.
- **Concurrencia:** irrelevante — una fila, escrituras manuales y esporádicas.
- **404, no 403**, para los denegados: se conserva el contrato actual.

## Deploy

Trae clase nueva (`FlagsService`) → `composer install` obligatorio (classmap optimizado), y una
migración `.sql` de esquema+seed → por el cliente `mysql`, antes del smoke. Orden: backup →
pull → composer → migración → smoke.

## Fuera de alcance (a propósito)

- Toggle por rol o por usuario desde /admin (decisión explícita: los roles siguen en código).
- Flags por proyecto, TTL de cache, historial de cambios más allá del último (la fila guarda
  solo el último `actualizado_por/en`; el histórico completo sería otra tabla y nadie lo pidió).
- Usar `general_flags` para otros módulos: la tabla queda lista, pero migrar otros gates es
  trabajo aparte.

## Archivos de este goal

Se creará `goals/<slug>/goal.md` al arrancar la implementación, enlazando este spec y
`memoria/goals/estado.md`.

---

## Estado verificado — cerrado

Verificado contra el código el 2026-08-25. **`estado: cerrado` es una afirmación deliberada**, no el valor por defecto del backfill.

**Evidencia:** idem plan hermano

Criterio y método: [[docs/superpowers/plans/2026-08-25-estado-real-de-planes-y-specs]].
