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

## Menú del panel

`Usuarios`, `Proyectos` y `Familias` son el CRUD básico. `Mantenimiento PDC`
(`/admin/pdc-maintenance`) y `Configuración` (`/admin/config`) son dos destinos que solo existen
aquí — no aparecen en ninguna otra parte de la wiki ni del sidebar principal, así que si buscas
dónde se ajustan parámetros globales del PDC o de la app, es en este menú y no en
[[plan-de-compras]].

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
