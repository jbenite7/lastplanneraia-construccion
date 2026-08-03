---
tipo: modulo
estado: vigente
fecha: 2026-08-03
areas: [design-system, arquitectura]
fuente: public/index.php
resumen: "Módulo Laboratorio del design system: rutas, controladores, servicios y quién puede usarlo"
---
# Laboratorio del design system

**Qué resuelve.** _Pendiente de escribir a mano._

**Dónde encaja.** Fuera de los dos flujos de negocio: es infraestructura de la aplicación.

**Nota del manifiesto.** La capacidad real es la constante RbacCatalog::PERM_INTERNAL_DESIGN_SYSTEM_VIEW; si el valor de la constante cambia, hay que actualizar esta clave.

## Inventario

Lo de abajo lo genera `scripts/wiki-arquitectura.mjs` desde el código. **No lo edites a mano:**
se sobrescribe en cada regeneración. Todo lo de fuera de los marcadores sí es tuyo.

<!-- generado:inicio -->
### Rutas
| Verbo | Ruta | Destino |
| --- | --- | --- |
| GET | `/internal/design-system` | `App\Controllers\Internal\DesignSystemLabController::index` |

### Controladores
- `App\Controllers\Internal\DesignSystemLabController`

### Servicios
_indeterminado_

### Tablas
_indeterminado_

### Quién puede
| Capacidad | Roles que la tienen |
| --- | --- |
| `internal.design-system.view` | A |
<!-- generado:fin -->
