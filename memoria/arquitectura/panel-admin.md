---
tipo: modulo
estado: vigente
fecha: 2026-08-03
areas: [admin, arquitectura]
fuente: public/index.php
resumen: "Panel admin: crear proyectos, usuarios y familias de catálogo — mini-app aislada con su propio router"
---
# Panel de administración

**Qué resuelve.** Es donde se da de alta un proyecto nuevo, se crean usuarios y se les asigna un
rol por proyecto, y se mantiene el catálogo de familias. No es una sección más de la app: es una
mini-app aparte (ver [[admin-adminlte-adaptador]] antes de tocarla), con su propio login, su propio
router y su propio estilo AdminLTE.

**Corregido 2026-08-18:** esta línea decía «nada de lo de `src/` se reutiliza aquí», y dejó de ser
cierto el 2026-08-03. `admin/` comparte a propósito las piezas de sesión y permisos:
`App\Security\RbacService` y `App\Security\EventService` en `admin/src/Controllers/AdminController.php:5-6`
y `AuthController.php:7-8`, `App\Core\ProgressTracker` en `DashboardController.php:8`,
`App\Core\DevDoor` en `admin/public/index.php:69-73` y `RbacCatalog` en `admin/views/layouts/main.php:52`.
Es compartición deliberada y documentada (`docs/superpowers/specs/2026-08-03-admin-dev-door-design.md`),
no un descuido: duplicar la normalización de roles habría abierto la puerta a que las dos apps
discreparan sobre quién puede qué. Lo que sí sigue siendo propio y **no** se reutiliza: el router, los
modelos, las vistas y el CSS.

**Dónde encaja.** Fuera de los dos flujos de negocio: es infraestructura de la aplicación.

Sus vistas (login, dashboard, usuarios, proyectos) están catalogadas en [[VISTAS-MODULOS|docs/VISTAS-MODULOS.md]].

## Menú del panel

| Ruta | Para qué sirve |
| --- | --- |
| `/admin/login` | Login propio del panel (200 sin sesión) |
| `/admin/logout` | Cierra la sesión del panel y redirige a `/admin/login` |
| `/admin/usuarios` | CRUD de usuarios |
| `/admin/proyectos` | CRUD de proyectos, incluida la gestión de miembros |
| `/admin/matching/family-catalog` | Catálogo de familias de matching |
| `/admin/matching/config` | Configuración de matching |
| `/admin/pdc/limpieza` | Mantenimiento del PDC (conteos y ejecución de limpieza) — no aparece en ninguna otra parte de la wiki ni del sidebar principal; si buscas dónde se ajustan parámetros globales del PDC, es aquí y no en [[plan-de-compras]] |

**Nota del manifiesto.** Mini-app aislada con su propio front controller (admin/index.php) y su propio router. Ninguna de sus rutas pasa por public/index.php, por eso la zona generada de rutas queda vacía a propósito.

**Sin red de seguridad.** Estas rutas se leyeron a mano en `admin/public/index.php` (líneas ~76-138)
y se comprobaron con `curl` contra el contenedor el 2026-08-03: no hay generador que las vigile
como al resto de `memoria/arquitectura/`. Si esta página se desactualiza, nadie lo va a detectar
automáticamente — quien la edite tiene que volver a verificar contra el código.

## Inventario

Lo de abajo lo genera `scripts/wiki-arquitectura.mjs` desde el código. **No lo edites a mano:**
se sobrescribe en cada regeneración. Todo lo de fuera de los marcadores sí es tuyo.

<!-- generado:inicio -->
### Rutas
_Este módulo no declara rutas en `public/index.php`._

### Controladores
_Ninguno: sin rutas propias._

### Servicios
_indeterminado_

### Tablas
_indeterminado_

### Quién puede
_Sin capacidad propia: la ruta exige sesión y proyecto, no una capacidad específica._
<!-- generado:fin -->
