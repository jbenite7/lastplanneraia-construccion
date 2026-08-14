---
tipo: decision
estado: vigente
fecha: 2026-08-13
areas: [bi, rbac, arquitectura]
fuente: src/Security/BiPreviewAccessPolicy.php, docs/superpowers/specs/2026-08-13-ocultar-control-tower-design.md
resumen: la Torre de Control BI sigue viva pero fuera de la navegación mientras se desarrolla; la abre solo el rol A por URL directa, con 404 para el resto
---
La [[torre-de-control-bi]] se ocultó de la navegación el 2026-08-13 porque el módulo está a medio
hacer y el equipo tropezaba con pantallas incompletas. **Las rutas siguen vivas**: no se borró
nada, solo dejó de haber por dónde entrar.

## Cómo está hecho

| Pieza | Dónde | Qué hace |
|---|---|---|
| Capacidad | `internal.bi.preview` (`RbacCatalog.php:13`) | Concedida **solo al rol `A`** (`RbacManager.php:30`) |
| Gate | `BiPreviewAccessPolicy::canOpen()` | Resuelve el rol **por usuario, no por proyecto** — la condición es global |
| Vistas | `BiViewController.php:54` | **404, no 403**, para no confirmar que la pantalla existe |
| API | `BiControlTowerApiController.php:34` | Mismo gate |
| Enlaces | `BiAccessComponent.php:41,60` | No pinta accesos a quien no puede abrirlos |

El gate se comprueba **antes** de la lógica de alcance por proyecto, para no pintarle a un Admin
un enlace que `BiProjectScope` le rechazaría después.

## Las dos correcciones que costó

**El gate no puede ser por entorno.** La primera versión apagaba el módulo según `APP_ENV`. El
usuario corrigió que él necesita seguir entrando —no trabaja en local—, así que acabó siendo por
rol. De paso esquivó que el `.env` de pruebas declara `APP_ENV="testing"`.

**Una petición de ocultar no dice a quién, y el valor por defecto deja fuera al que pide.** El
primer corte ocultaba los accesos a **todo el mundo**, incluido el administrador que había pedido
el ocultamiento — leyendo «ocultar los accesos desde el frontend» como «para todos». Lo reportó
desde producción el mismo día. No era una avería sino el diseño, y el diseño estaba mal: ocultar
existía para que el equipo no tropezara con trabajo a medias, no para estorbar a quien lo
desarrolla.

## Para revertirlo

Quitar la llamada a `BiPreviewAccessPolicy` de los cuatro puntos de la tabla. El comentario de
`BiAccessComponent.php:36` lo dice en el propio código, que es donde hay que mirar primero.

Ver también [[el-item-oculto-del-sidebar-rompe-su-propio-modulo]] — ocultar el ítem del sidebar
reventó con 500 las ocho vistas del propio módulo, y es la trampa que este cambio dejó puesta.
