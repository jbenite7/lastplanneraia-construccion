# Registro de verificación — los 30 hechos

Fecha: 2026-07-28. Rama `pdc-revision-ux` en los dos repos (`plan-de-compras` y `lps-aia-pdc`),
las dos creadas desde `pdc-a4-fechas` — **no desde `main`**, como decían los planes: en los dos
repos `main` va por detrás y ramificar desde ahí habría dejado fuera todo A4.

## Suites al cerrar

| Gate | Resultado |
|---|---|
| PHP — 22 suites `test_pdc*` | 0 fallos |
| `test_shell_sidebar_partial.php` | PASS |
| PHPStan nivel 6 (`phpstan-pdc.neon`) | `[OK] No errors` |
| Ratchet del motor (`brecha_daporto`) | `la brecha (7) está dentro del techo (7)` |
| Vitest | 181 tests (base: 128) |
| e2e de la SPA (`pdc-v2-*.spec.mjs`) | 14/14 |
| Gate del shell (`shell-sidebar-rollout.mjs`) | 141/141 |
| Contratos del sistema de diseño | PASS (con el árbol limpio) |

## Hecho a hecho

### Plan 1 — arreglos de tabla

| Hecho | Prueba |
|---|---|
| f01 cifras/fechas/unidades completas | `agGrid.test.ts` (`CIFRA` sin `wrapText`) + `autoSizeStrategy` en las 9 tablas |
| f02 el texto largo envuelve | `columnaTexto` → `wrapText` + `autoHeight`; captura del historial sin «…» |
| f03 ancho por contenido | `autoSizeStrategy.type === 'fitCellContents'`; cero `width:` fijos salvo columnas de marca |
| f04 un clic edita | `accionDeClic`; e2e `pdc-v2-responsable` edita con **un** clic |
| f05 el resto de la fila abre el detalle | `accionDeClic('nombre') === 'detalle'`; e2e del Plan expande a 7 pasos |
| f06 Paquetes abre en «Sin asignar» | `filtroInicial` + `filtroDecidido` |
| f07 un solo botón | `ACCION_PROPONER.etiqueta` sin «sembrar» ni «iteración» |
| f08 proponer no escribe | `ACCION_PROPONER.escribe === false`; el endpoint es de lectura |
| f09 «Recalcular» dice qué conserva | nota fija + `test_pdc_v2_plan_fechas.php` (317 PASS) que vigila la promesa |

### Plan 2 — lo que faltaba poder hacer

| Hecho | Prueba |
|---|---|
| f10 desamarrar | `desamarrar()` + e2e `pdc-v2-desamarrar` (el amarre desaparece de la base) |
| f11 el responsable se conserva **siempre** | dos tests PHP: al desamarrar y al reamarrar a otro frente. El segundo falló contra el código viejo antes del arreglo — era un borrado silencioso real |
| f12 sin frente no quedan fechas | assert de `pdc_plan_paso` vacío y cabecera con las cuatro columnas en NULL |
| f13 mismo permiso que amarrar | `test_pdc_v2_rbac_paquetes.php`: no hay permiso nuevo y el guard es `guardEscritura` |
| f14 cambiar de frente desde la tabla | columna «Frente» editable + `uniqueIdPorEtiquetaFrente` |
| f15 elegir la versión oficial | `activar()` + e2e `pdc-v2-historial` |
| f16 avisa, no bloquea | `impactoDeCambiarVersion()` cuenta solo los vínculos del maestro |
| f17 se puede volver a una versión nueva | test: activar V3 después de V1 |
| f18 permiso de importar | `test_pdc_v2_rbac_importar.php` |
| f19 confirmación | panel `pdc-import-confirmar-oficial`, verificado en e2e |
| f20 selector de nivel | `expandirHastaNivel` + e2e del visor (con «Capítulo» desaparece lo de abajo) |
| f21 abre desplegado hasta insumos | e2e del visor: los insumos se ven sin abrir nada |
| f22 lo mismo en el comparador | `pdc-cmp-nivel`, verificado en el e2e del historial |
| f23 del historial al visor | clic en la fila → `?version=`, sin modal |
| f24 dos versiones como máximo | `alternarSeleccion` no admite la tercera |
| f25 «Comparar» con las dos | `rutaComparar` + e2e |

### Plan 3 — navegación

| Hecho | Prueba |
|---|---|
| f26 dentro del shell, con entrada propia | gate del shell 141/141 con `/plan-compras` ya en `MIGRATED` |
| f27 «Cargar presupuesto» | `navegacion.test.ts` + e2e de fundación |
| f28 pestañas dentro de las tres pantallas | `Pestanas` + e2e que abre cada una |
| f29 ninguna tabla escondida | el panel inactivo **no se monta**: el e2e comprueba `toHaveCount(0)` |
| f30 nada roto | la tabla de gates de arriba |

## Tres cosas que saber

1. **La barra lateral tiene UNA entrada, no dos** — *decidido por el dueño del producto el
   2026-07-28: así se queda.* El plan decía que el módulo nuevo y el viejo convivieran con
   etiquetas distintas. No cabe: el rail tiene un presupuesto de altura y el gate exige que su nav
   no haga scroll interno. Medido: con el ítem extra caen 22 comprobaciones; sin él, 0. La entrada
   «Plan de Compras» apunta al módulo nuevo y el viejo sigue servido en `/pdc` por su dirección,
   sin aparecer en el menú.
2. **El indicador del motor se contamina con el sandbox de los e2e.** Los paquetes que crean los
   specs viven en el catálogo global y el motor aprende de lo asignado en otros proyectos, así que
   medir la brecha justo después de correr Playwright da 8 en vez de 7. El seed limpia al empezar
   cada test, nunca al terminar el último. El propio test lo avisa ahora y da el comando.
3. **El módulo entra al inventario del sistema de diseño como `inventory-only`.** Consume el shell
   y pasa su gate, pero no tiene manifiesto de piloto con escenarios y evidencia: eso es trabajo de
   la migración de diseño propiamente dicha, y declararlo sin haberlo hecho sería falso.
