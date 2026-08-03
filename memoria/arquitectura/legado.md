---
tipo: modulo
estado: vigente
fecha: 2026-08-03
areas: [arquitectura]
fuente: public/index.php
resumen: "Módulo Carril legado: rutas, controladores, servicios y quién puede usarlo"
---
# Carril legado

**Qué resuelve.** _Pendiente de escribir a mano._

**Dónde encaja.** Fuera de los dos flujos de negocio: es infraestructura de la aplicación.

**Nota del manifiesto.** Rutas que hacen require_once de scripts procedurales: servicios y tablas saldrán indeterminados por diseño.

## Inventario

Lo de abajo lo genera `scripts/wiki-arquitectura.mjs` desde el código. **No lo edites a mano:**
se sobrescribe en cada regeneración. Todo lo de fuera de los marcadores sí es tuyo.

<!-- generado:inicio -->
### Rutas
| Verbo | Ruta | Destino |
| --- | --- | --- |
| GET | `/legacy/cambiar_pagina.php` | `src/Legacy/Endpoints/cambiar_pagina.php` (legado) |
| POST | `/legacy/cambiar_pagina.php` | `src/Legacy/Endpoints/cambiar_pagina.php` (legado) |
| POST | `/legacy/funciones_generales/php/buscadorTabla.php` | `src/Legacy/buscadorTabla.php` (legado) |
| POST | `/legacy/funciones_generales/php/datosGeneralesPagina.php` | `src/Legacy/datosGeneralesPagina.php` (legado) |
| POST | `/legacy/funciones_generales/php/eliminar_semana.php` | `src/Legacy/eliminar_semana.php` (legado) |
| POST | `/legacy/funciones_generales/php/nueva_semana.php` | `src/Legacy/nueva_semana.php` (legado) |
| POST | `/legacy/funciones_generales/php/verificarCICActualizada.php` | `src/Legacy/verificarCICActualizada.php` (legado) |

### Controladores
_Ninguno: carril legado._

### Scripts legados
- `src/Legacy/Endpoints/cambiar_pagina.php`
- `src/Legacy/buscadorTabla.php`
- `src/Legacy/datosGeneralesPagina.php`
- `src/Legacy/eliminar_semana.php`
- `src/Legacy/nueva_semana.php`
- `src/Legacy/verificarCICActualizada.php`

### Servicios
_indeterminado_

### Tablas
_indeterminado_ — o bien todas las rutas de este módulo son legadas (las consultas viven en scripts procedurales y la extracción textual no es fiable ahí), o bien no se pudo leer el esquema real de la base de datos al generar esta página.

### Quién puede
_Sin capacidad propia: la ruta exige sesión y proyecto, no una capacidad específica._
<!-- generado:fin -->
