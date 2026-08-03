---
tipo: modulo
estado: vigente
fecha: 2026-08-03
areas: [design-system, arquitectura]
fuente: public/index.php
resumen: "Laboratorio del design system: banco de pruebas visual para tokens y componentes aia-*, no es una vista de negocio"
---
# Laboratorio del design system

**Qué resuelve.** Es el banco de pruebas donde se ven los tokens, componentes y layouts del
[[design-system]] aislados de cualquier dato real de proyecto. Sirve para revisar un cambio visual
antes de llevarlo a un módulo de verdad. Acceso restringido — ver
`App\Security\DesignSystemLabAccessPolicy`. Antes de tocarlo, revisa
[[lab-desktop-layout-suite]], que documenta sus trampas ya medidas.

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
