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
router y su propio estilo AdminLTE — nada de lo de `src/` se reutiliza aquí.

**Dónde encaja.** Fuera de los dos flujos de negocio: es infraestructura de la aplicación.

Sus vistas (login, dashboard, usuarios, proyectos) están catalogadas en [[VISTAS-MODULOS|docs/VISTAS-MODULOS.md]].

## Menú del panel

| Ruta | Para qué sirve |
| --- | --- |
| `/admin/users` | CRUD de usuarios |
| `/admin/projects` | CRUD de proyectos |
| `/admin/families` | Catálogo de familias |
| `/admin/pdc-maintenance` | Mantenimiento del PDC — no aparece en ninguna otra parte de la wiki ni del sidebar principal; si buscas dónde se ajustan parámetros globales del PDC, es aquí y no en [[plan-de-compras]] |
| `/admin/config` | Configuración general de la app — tampoco aparece en otro lugar |
| `/admin/logout` → `/admin/login` | Cierra la sesión del panel y vuelve al login propio del admin |

**Nota del manifiesto.** Mini-app aislada con su propio front controller (admin/index.php) y su propio router. Ninguna de sus rutas pasa por public/index.php, por eso la zona generada de rutas queda vacía a propósito.

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
