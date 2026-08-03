---
tipo: modulo
estado: vigente
fecha: 2026-08-03
areas: [lps, arquitectura]
fuente: public/index.php
resumen: "Subcontratistas: catálogo editable de subcontratistas/proveedores, compartido entre LPS y PDC"
---
# Subcontratistas

**Qué resuelve.** Mantiene el catálogo de subcontratistas y proveedores en una grilla editable en
vivo. Lo usan ambos flujos: [[submodulo-cic]] los califica dentro del LPS, y
[[plan-de-compras]]/[[contratos]] los usan como destino de un paquete de contratación.

**Dónde encaja.** En los dos flujos. Ver [[flujo-lps]] y [[flujo-pdc]].

Su vista está catalogada en [[VISTAS-MODULOS|docs/VISTAS-MODULOS.md]].

## Inventario

Lo de abajo lo genera `scripts/wiki-arquitectura.mjs` desde el código. **No lo edites a mano:**
se sobrescribe en cada regeneración. Todo lo de fuera de los marcadores sí es tuyo.

<!-- generado:inicio -->
### Rutas
| Verbo | Ruta | Destino |
| --- | --- | --- |
| GET | `/api/subcontratistas/list` | `App\Controllers\Api\SubcontratistasApiController::list` |
| POST | `/api/subcontratistas/list` | `App\Controllers\Api\SubcontratistasApiController::list` |
| POST | `/api/subcontratistas/save` | `App\Controllers\Api\SubcontratistasApiController::save` |
| GET | `/subcontratistas` | `App\Controllers\Gestion\SubcontratistasController::index` |

### Controladores
- `App\Controllers\Api\SubcontratistasApiController`
- `App\Controllers\Gestion\SubcontratistasController`

### Servicios
_indeterminado_

### Tablas
_indeterminado_

### Quién puede
| Capacidad | Roles que la tienen |
| --- | --- |
| `canManageContracts` | A, D, R, OT |
<!-- generado:fin -->
