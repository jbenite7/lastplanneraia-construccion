---
tipo: modulo
estado: vigente
fecha: 2026-08-03
areas: [admin, arquitectura]
fuente: public/index.php
resumen: "Módulo Panel de administración: rutas, controladores, servicios y quién puede usarlo"
---
# Panel de administración

**Qué resuelve.** _Pendiente de escribir a mano._

**Dónde encaja.** Fuera de los dos flujos de negocio: es infraestructura de la aplicación.

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
