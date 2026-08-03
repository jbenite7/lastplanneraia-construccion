---
tipo: modulo
estado: vigente
fecha: 2026-08-03
areas: [rbac, arquitectura]
fuente: public/index.php
resumen: "Selector de proyectos: pantalla tras el login para elegir en qué proyecto trabajar"
---
# Selector de proyectos

**Qué resuelve.** Es la pantalla que aparece justo después de iniciar sesión (o al usar
`Cambiar proyecto` desde el menú de usuario, ver [[nucleo-y-runtime]]): elegir con qué proyecto
trabajar. El rol de la sesión depende de a qué proyecto entraste, porque `project_members` guarda
el rol por proyecto, no por cuenta.

**Dónde encaja.** Fuera de los dos flujos de negocio: es infraestructura de la aplicación.

## Inventario

Lo de abajo lo genera `scripts/wiki-arquitectura.mjs` desde el código. **No lo edites a mano:**
se sobrescribe en cada regeneración. Todo lo de fuera de los marcadores sí es tuyo.

<!-- generado:inicio -->
### Rutas
| Verbo | Ruta | Destino |
| --- | --- | --- |
| POST | `/proyecto/seleccionar` | `App\Controllers\Core\ProjectSelectorController::select` |
| GET | `/proyectos` | `App\Controllers\Core\ProjectSelectorController::index` |

### Controladores
- `App\Controllers\Core\ProjectSelectorController`

### Servicios
- `ProjectLandingService`

### Tablas
- `general_proyectos_procesos`
- `general_usuarios`
- `project_members`

### Quién puede
_Sin capacidad propia: la ruta exige sesión y proyecto, no una capacidad específica._
<!-- generado:fin -->
