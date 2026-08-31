---
capa: fuente
tipo: spec
estado: autorrevisado
id: S12
fecha: 2026-08-31
superficie: plan-compras
rutas:
  - "/plan-compras"
  - "/plan-compras/ensamble/importar"
  - "/plan-compras/ensamble/maestro"
  - "/plan-compras/ensamble/presupuesto"
  - "/plan-compras/ensamble/comparar"
  - "/plan-compras/ensamble/paquetes"
  - "/plan-compras/ensamble/plan"
  - "/plan-compras/ensamble/plan/pasos"
  - "/plan-compras/seguimiento/avance"
depende_de: [T01, S05, S06]
views: [VIEW-31]
areas: [arquitectura, frontend, plan-compras, pdc-v2, rbac, accesibilidad, design-system]
fuente: "auditoria de public/index.php, PlanComprasController, ocho controladores PlanCompras API y su trait de respuestas, servicios Pdc, RbacCatalog/RbacService, VIEW-31, pdc-app completa, docs/pdc-v2.md, manifiesto y pruebas PDC v2 en shell-minimo-react, 2026-08-30"
resumen: "Migracion vertical S12 de la isla React Plan de Compras v2 a la SPA principal: ocho pantallas, 13 pestañas estables y una condicional, 69 contratos API preservados, 65 consumos con Zod, acciones efectivas, rutas anidadas con puente hash, AG Grid desktop/tablet, vistas nativas moviles, oscuro/claro y retiro del build separado, sin cambiar dominio, RLS, schema, datos ni permisos."
---

# S12 — Plan de Compras v2 en la SPA React principal

> **Estado:** diseño tecnico autorrevisado. No quedan decisiones de negocio, producto, estrategia o
> PM que bloqueen el plan. Esta spec no autoriza implementacion, commits, DDL/DML, cambios RLS,
> cambios de permisos, deploy, publicacion ni trabajo en `/admin/`. Su plan se escribe a
> continuacion con `superpowers:writing-plans`, conforme al programa aprobado de 27 specs y 27
> planes.

## Relacion con el programa

Esta spec continua las decisiones de:

- [[docs/superpowers/specs/2026-08-28-migracion-react-typescript-design|Migracion React + TypeScript]];
- [[docs/superpowers/specs/2026-08-28-paridad-shell-react-rls-design|Paridad del shell React y RLS]];
- [[docs/superpowers/specs/2026-08-30-t01-shell-runtime-react-design|T01 — shell/runtime React]];
- [[docs/superpowers/specs/2026-08-29-programa-general-react-design|S05 — Programa General React]];
- [[docs/superpowers/specs/2026-08-30-s06-actualizar-cronograma-react-design|S06 — Actualizar cronograma React]];
- [[docs/security/rls-runtime-boundary|Frontera runtime de RLS]];
- [[docs/pdc-v2|Contrato de Plan de Compras v2]].

T01 posee sesion, proyecto, sidebar, temas claro/oscuro, cliente HTTP, rutas y estados globales.
S12 no crea un segundo shell, selector de proyecto, sistema de tema ni cliente. S05 y S06 poseen el
Programa General y la actualizacion del cronograma que Plan de Compras consulta para frentes,
anclas, fechas, desfases y reprogramacion. S12 conserva esa frontera: consume el cronograma, no lo
edita ni redefine su identidad.

VIEW-31 es `views/plan-compras/app.view.php`. A diferencia de otras superficies del programa, la
vista PHP no contiene el producto: aloja una isla React separada construida desde `pdc-app/`. Por
eso S12 es una absorcion de una aplicacion React existente, no una traduccion directa de PHP. El
corte retira VIEW-31, `window.__PDC_BOOTSTRAP__`, el bundle dedicado y el router hash solo cuando la
SPA principal demuestre paridad y cero consumidores.

## Resultado buscado

Plan de Compras seguira siendo el modulo completo de presupuesto, maestro, paquetes, plan y
seguimiento, pero vivira dentro de la SPA principal:

1. conserva las ocho pantallas actuales y sus acciones observables;
2. cambia las rutas hash por rutas anidadas reales bajo `/plan-compras`;
3. preserva favoritos y enlaces antiguos mediante un puente hash de una sola ejecucion;
4. usa sesion, proyecto, sidebar, navegacion y temas del shell React comun;
5. elimina la autoridad del rol crudo en navegador y consume acciones efectivas del servidor;
6. conserva los 69 contratos API registrados y los 65 consumos reales;
7. valida con Zod cada respuesta que la SPA consume;
8. centraliza JSON, multipart, descargas y cancelacion en `frontend/src/lib/api/cliente.ts`;
9. conserva AG Grid Community en desktop/tablet y ofrece vistas nativas operables en movil;
10. mantiene carga de Excel, versiones, maestro, comparativo, paquetes, subpaquetes, fechas, pasos,
    vencimientos, avance y flujo de caja;
11. conserva las claves locales del recorrido y del tamiz;
12. conserva ayuda contextual en las ocho pantallas y el recorrido de seis paradas;
13. hace visibles lectura, permisos, estados vacios, errores, progreso y recuperacion;
14. ofrece capacidad equivalente en oscuro y claro, teclado, lector de pantalla, zoom y touch;
15. retira el build separado solo al final del strangler.

Paridad no obliga a conservar un `HashRouter`, casts TypeScript sin validacion, `window.confirm`,
CSS local con hex, doble bootstrap, botones que aprenden permisos por 403 ni una tabla ilegible en
movil. Si obliga a conservar datos, formulas, opciones, contratos, acciones autorizadas, ayudas,
deep links, mensajes utiles y recuperacion.

## Alcance

### Incluido

- Las ocho pantallas actuales de `pdc-app/`.
- Las siete entradas visibles de navegacion y la pantalla anidada de Pasos.
- Trece pestañas estables y la pestaña condicional de equipos sin clasificar.
- Las 69 combinaciones metodo/ruta API registradas.
- Los 65 contratos consumidos por la UI, todos con esquema Zod estricto.
- Adaptacion de `GET /plan-compras/api/contexto` para acciones, navegacion y configuracion.
- Carga multipart de presupuesto y maestro SINCO, ambos con limite de 10 MB.
- Descarga CSV de flujo de caja con BOM y separador punto y coma.
- Preservacion de query params `version`, `a` y `b`.
- Puente de hashes antiguos a rutas anidadas, con `history.replaceState`.
- AG Grid Community cargado solo dentro del modulo para desktop/tablet.
- Tarjetas, listas y formularios nativos para movil.
- Los modales y paneles propios del modulo, incluidos subpaquetes y confirmaciones accesibles.
- Acciones efectivas resueltas por `RbacService`, incluidas capacidades por pantalla.
- CSRF `plan_compras_v2` en todas las mutaciones actuales.
- Preservacion de aislamiento `project_id` y fronteras global/proyecto existentes.
- Temas oscuro/claro con tokens de `public/css/tokens.css`.
- Ayuda, recorrido, filtros, buscadores, conteos, estados y feedback.
- Pruebas PHP de contrato sin base mutable, Vitest y Playwright completamente interceptado.
- Piloto strangler, rollback de ruta, corte y retiro de artefactos exclusivos.

### Fuera de alcance

- `/admin/` y cualquier ruta, vista, estilo, permiso o dependencia administrativa.
- Cambiar RLS, `ProjectScope`, schema, migraciones, tablas, columnas, indices, triggers, grants,
  usuarios, credenciales, membresias, roles, alias, overrides o datos.
- Ejecutar DDL/DML durante auditoria, diseño, autorrevision o pruebas documentales.
- Reabrir el modelo de presupuesto, maestro, paquetes, subpaquetes, plan o flujo de caja.
- Corregir datos reales, recalcular obras, importar archivos o activar versiones reales.
- Crear UI para cuatro endpoints existentes que hoy no tienen consumidor.
- Eliminar endpoints de compatibilidad por el solo hecho de no tener consumidor React.
- Cambiar formulas de costo, tamiz, cobertura, tasa de acierto, fechas, vencimientos o flujo.
- Cambiar los cinco tipos de negociacion o las cuatro modalidades de contratacion.
- Cambiar la semantica `subpaqueteId = 0`.
- Convertir el motor de sugerencias en aprendizaje de subpaquetes.
- Cambiar el permiso de una operacion, incluido el GET de simulacion que exige editar.
- Cambiar la politica de versiones obsoletas o borrar versiones historicas.
- Introducir selector de semana en Plan de Compras; el modulo usa proyecto, no semana de UI.
- Integrar el drawer contextual T02: el modulo ya posee paneles y dialogs de dominio.
- Sustituir AG Grid por una libreria nueva.
- Usar AG Grid Enterprise.
- Aprobar o regenerar goldens sin autorizacion explicita.
- Desplegar, publicar, commitear o borrar la isla durante esta fase documental.

## Punto de partida medido

### SPA principal

- No existe ruta React principal para Plan de Compras.
- La sidebar navega a `/plan-compras`, servida por `PlanComprasController`.
- `frontend/` ya tiene React 19, React Router 7 y Zod 4.
- `frontend/` no depende aun de AG Grid.
- `frontend/src/lib/api/cliente.ts` es la unica frontera HTTP permitida.
- T01 ya posee proyecto, usuario, sidebar, tema y manejo global de sesion.

### Isla React separada

| Pieza | Medicion auditada |
|---|---:|
| Aplicacion | React 19 + Router 7 + Vite + TypeScript |
| Router | `HashRouter` |
| Pantallas | 8 |
| Entradas principales | 7 |
| Pestañas | 13 estables + 1 condicional |
| Paginas/componentes/tipos/CSS auditados | 8.205 lineas |
| `types.ts` | 782 lineas |
| CSS propio | 1.185 lineas |
| Unit tests | 30 archivos |
| PHP tests PDC | 39 archivos |
| Browser specs PDC con sufijo `.spec.mjs` | 19 archivos |
| Grid | AG Grid Community 36 |
| Validacion runtime | ninguna |
| Cliente HTTP | propio, con `fetch` directo |
| Bootstrap | HTML global o `GET contexto` |
| Tema | shell legacy + CSS propio |
| Responsive actual | principalmente desktop denso |

Las nueve paginas TSX suman 5.227 lineas porque Paquetes incluye además
`PaquetesAsistente.tsx`. La isla tiene su propio `package.json`, `tsconfig`, Vite, ESLint, API,
bootstrap, tipos, estilos y build a `public/pdc-app/assets`.

### Backend

- `public/index.php` registra una ruta de pagina y 69 combinaciones metodo/ruta API.
- Los controladores conservan el envelope:
  `{ok:true,data:T}` o `{ok:false,error:{code,message,...}}`.
- El proyecto se resuelve desde sesion y cada servicio operativo conserva `project_id`.
- Maestro, catalogos, reglas y duraciones tienen alcances globales ya definidos por backend.
- Todas las mutaciones usan CSRF `plan_compras_v2`.
- El contexto actual expone `projectId`, nombre de proyecto, usuario, `usuarioId`, rol crudo y CSRF.
- La vista PHP inyecta el mismo bootstrap, monta sidebar/theme legacy y carga el bundle separado.
- El controlador de pagina consulta semanas solo para los flyouts del shell aunque el modulo oculta
  el chip de semana.

### Pruebas y verificabilidad

Los unit tests de la isla son puros. Muchos tests PHP y browser PDC usan fixtures, sandbox SQL o
restauracion y pueden escribir en la base compartida. No son evidencia segura para la migracion
documental. S12 prescribe contratos con fakes y navegador con red interceptada; no ejecuta las
suites mutables ni asume que un rollback deja de ser DML.

Las dependencias de `pdc-app/` no estan instaladas en este worktree. Por eso esta auditoria no
reclama haber ejecutado su Vitest o TypeScript; usa codigo y pruebas existentes como evidencia
estatica. La suite principal informada por el usuario permanece en 30 pruebas y typecheck verde.

## Defectos y contradicciones observados

1. El cliente propio llama `fetch` fuera de la frontera permitida.
2. Los genericos TypeScript hacen casts, pero ninguna respuesta se valida en runtime.
3. El bootstrap expone rol crudo aunque el navegador no debe inferir autorizacion.
4. Varias paginas muestran acciones y descubren el permiso solo despues de un 403.
5. Maestro intenta `POST maestro/vinculos/generar` al cargar y usa el 403 para degradar a lectura.
6. Los fragmentos hash no llegan al servidor y no son rutas copiables limpias.
7. `PlanComprasController` duplica shell, bootstrap, tema y consulta de semanas.
8. La documentacion habla de ocho pantallas y trece pestañas; el codigo tiene trece estables y una
   pestaña de equipos condicional.
9. Un comentario de importacion dice que Planeacion no puede importar, pero el alias crudo `P -> D`
   hereda hoy capacidades de Director. La migracion conserva la resolucion efectiva real.
10. La seccion antigua de `docs/pdc-v2.md` llama abierta la contradiccion de frentes sin
    `unique_id`; el servicio y los tests posteriores ya resuelven encabezados por la hoja mas
    temprana del subarbol. El codigo vigente manda.
11. `pdc-app/src/lib/planFechas.ts` contiene un byte NUL dentro de la constante de masa
    `sin-elegir`; al portar se reconstruye desde texto fuente valido y se fija con test.
12. `TIPOS_NEGOCIACION_CREABLES` excluye `no_aplica` con un comentario obsoleto: el
    `PaquetesService::TIPOS` vigente ya acepta los cinco valores. El port elimina esa divergencia
    y fija frontend/backend con una caracterizacion literal.
13. La eliminacion de subpaquetes usa `window.confirm`, sin dialog accesible ni control de foco.
14. Las tablas se adaptan ocultando columnas por ancho, pero no ofrecen una experiencia movil
    completa para todas las acciones.
15. El manifiesto de design system solo declara desktop, estado vacio y una captura oscura.
16. El CSS propio mantiene escala y fallbacks de color que no pertenecen al sistema de tokens
    comun.
17. El CSV se descarga por enlace directo; la nueva SPA necesita conservar descarga sin introducir
    un segundo cliente ni perder el nombre de archivo.
18. El historial de versiones genera rutas internas hash con query params que se perderian con una
    migracion ingenua.
19. Cuatro endpoints siguen registrados sin consumidor actual. Borrarlos o crearles UI seria una
    ampliacion no autorizada.
20. Los tests existentes mezclan pruebas puras y escenarios con escritura real; ejecutar toda la
    bateria por rutina viola el limite de esta fase.

## Inventario de informacion y reglas de dominio

### Presupuesto y versiones

- El importador acepta solo `.xlsx`, MIME permitido y maximo 10.485.760 bytes.
- Preview valida todo y no persiste el presupuesto.
- Confirmar consume un token temporal una sola vez y crea la version transaccionalmente.
- Un hash repetido puede producir `sinCambios`; la UI no debe prometer cambios inexistentes.
- El impacto previo informa nuevos sin asignar, desaparecidos asignados, cambios de tipo y valor.
- Las versiones no se borran al activar otra.
- Una version obsoleta conserva motivo y advertencia visible antes de interpretar valores.
- Comparar exige dos versiones distintas.
- La magnitud distingue apariciones de APU e insumos distintos.
- El visor conserva arbol, niveles, tipos de fila, totales, cantidades, costos y avisos del servidor.
- El tamiz usa 0,25 % del costo total como default, redondeado hacia abajo a millones.
- El umbral personal se conserva en `pdc-umbral-global:<projectId>`.

### Maestro global

- Los vinculos conectan insumos de presupuesto de una obra con el maestro global.
- Generar vinculos puede reenganchar coincidencias; una lectura pura no debe fingir ese efecto.
- Para usuarios con `maestro.generateLinks`, se conserva el refresco automatico observable al
  entrar, anunciado como actualizacion y seguido de lectura.
- Usuarios sin esa accion omiten el POST y cargan directamente los vinculos.
- Pendientes pueden crearse en lote desde el presupuesto.
- Un vinculo puede confirmarse con una sugerencia.
- El catalogo permite buscar, incluir retirados, retirar y reactivar.
- Reactivar puede reenganchar pendientes de otras obras y debe informar el conteo.
- El importador SINCO usa preview/confirmacion, conflictos y reenganchados.
- La cola global de equipos existe aunque el proyecto no tenga presupuesto.
- `EQUIPO (SIN CLASIFICAR)` solo cambia a alquilado o comprado tras accion humana.
- La pista de agrupacion ayuda a seleccionar; nunca clasifica automaticamente.
- La pestaña de equipos desaparece cuando la cola queda vacia.
- El endpoint manual de creacion existe, pero no tiene UI actual y no se inventa.

### Paquetes

- La unidad inicial es el insumo distinto, no cada aparicion en APU.
- Filtros: sin asignar, asignados, omitidos y todos.
- La cobertura se expresa por conteo y valor.
- La tasa de acierto es `null` sin base, no 100 %.
- Las sugerencias conservan capa, confianza y evidencia.
- Aceptar una sugerencia y confirmar humano son dimensiones ortogonales.
- Asignar, omitir, desasignar y crear paquete conservan procedencia cuando aplica.
- El asistente paso a paso permite asignar, crear, omitir, saltar, deshacer y ampliar candidatos.
- Crear paquete ofrece los cinco tipos que `PaquetesService::TIPOS` acepta hoy.
- Modalidades exactas:
  `contrato`, `orden_compra`, `consumo_directo`, `no_contratable`.
- Tipos de negociacion conocidos:
  `a_todo_costo`, `suministro`, `mano_obra`, `consumibles`, `no_aplica`.
- Actividades de un insumo se muestran como evidencia y ruta.
- Los endpoints `plan-auto` y `auto-asignar` no tienen consumidor actual.

### Subpaquetes

- Un paquete puede dividirse en lotes contratables sin borrar el paraguas.
- `subpaqueteId = 0` significa sin partir y nunca se interpreta como `null`.
- Partir crea lotes y un resto en la misma operacion.
- El resto recibe lo no movido.
- Un lote conserva nombre, modalidad, frente, responsable y proceso.
- Se puede agregar, actualizar, eliminar y mover insumos entre lotes.
- Borrar el ultimo lote real desparte el paquete y vuelve a cero.
- Un paquete partido no se contrata a si mismo.
- Las operaciones y uniones deben quedar acotadas por paquete y subpaquete.
- El motor de sugerencias sigue a nivel de paquete y aterriza en Resto cuando corresponde.
- `SubpaquetesService::destinos()` sigue siendo la definicion del servidor, aunque su endpoint no
  tenga consumidor actual.

### Plan de fechas

- El plan contiene destinos contratables, amarres y pasos calculados.
- Las cuatro pestañas son Plan, Sin frente, Sin calcular y Desfases.
- Un ancla puede ser frente o actividad elegible.
- Encabezados sin `unique_id` propio se representan mediante la hoja mas temprana de su subarbol,
  sin perder la etiqueta de frente.
- Las sugerencias de frente conservan origen, confianza y evidencia.
- Un amarre manual puede confirmar la sugerencia o corregirla.
- Las correspondencias por proyecto requieren editar; las globales requieren reglas.
- Calcular usa la configuracion efectiva de pasos y conserva avance real.
- Responsable puede asignarse individual o por lote, y puede dejarse vacio.
- Desamarrar exige confirmacion.
- Desfases muestran fecha guardada, fecha vigente y dias movidos.
- Reprogramar siempre pasa por simulacion y seleccion explicita antes de aplicar.
- Fechas reales no se desplazan al aplicar reprogramacion.
- Duraciones provisionales, falta de duracion, huerfanos y plan desactualizado son visibles.

### Pasos de contratacion

- Es una octava pantalla anidada, no una entrada permanente de la barra.
- La configuracion efectiva puede ser default o propia de la obra.
- Se pueden agregar, quitar, ordenar, renombrar y fijar dias.
- Guardar, copiar o restablecer recalcula el plan en la misma operacion.
- Se puede copiar desde una obra accesible, con preview e indicador de configuracion incompleta.
- El historial explica quien cambio, cuando y a que.
- Las duraciones de catalogo global muestran cuantos paquetes las usan.
- Una duracion solo se modifica si la obra actual la usa.
- No se hardcodean siete pasos: el catalogo servidor es canonico.
- Leer pasos e historial requiere ver paquetes; origenes, preview de copia y duraciones requieren
  reglas; las mutaciones requieren reglas y CSRF.

### Seguimiento y flujo de caja

- Seguimiento tiene tres pestañas: Paquetes, Vencimientos y Flujo de caja.
- La lista filtra por busqueda, estado, responsable, frente, atrasados y solo mios.
- `solo mios` usa `usuarioId` del contexto, no el rol.
- El detalle compara fechas planeadas, proyectadas y reales.
- Registrar una fecha real o borrarla es una mutacion con permiso editar.
- Vencimientos agrupa pasos pendientes por cortes:
  vencido, semana 1, 2, 3, 6, adelante y sin fecha.
- El servidor entrega `hoy`, conteos y filas sin fechas; el navegador no usa su reloj como verdad.
- El flujo mensual distingue contratado, permanente y provisional.
- Expone cobertura, porcentaje con fecha propia, excluidos, origen de duracion y metodo.
- El total residual debe cuadrar exactamente con el total distribuido.
- El CSV conserva BOM UTF-8, punto y coma, metodo y nombre descargable.
- Paquetes sin proceso y exclusiones siguen las reglas actuales del servidor.

## Arquitectura de informacion y rutas

### Rutas canonicas

| Pantalla | Ruta hash actual | Ruta React objetivo | Navegacion principal |
|---|---|---|---|
| Cargar presupuesto | `#/ensamble/importar` | `/plan-compras/ensamble/importar` | si |
| Maestro | `#/ensamble/maestro` | `/plan-compras/ensamble/maestro` | si |
| Presupuesto | `#/ensamble/presupuesto` | `/plan-compras/ensamble/presupuesto` | si |
| Comparar | `#/ensamble/comparar` | `/plan-compras/ensamble/comparar` | si |
| Paquetes | `#/ensamble/paquetes` | `/plan-compras/ensamble/paquetes` | si |
| Plan | `#/ensamble/plan` | `/plan-compras/ensamble/plan` | si |
| Pasos | `#/ensamble/plan/pasos` | `/plan-compras/ensamble/plan/pasos` | no |
| Seguimiento | `#/seguimiento/avance` | `/plan-compras/seguimiento/avance` | si |

`/plan-compras` redirige con replace a Cargar presupuesto. El alias hash `#/maestro` migra a la
ruta canonica de Maestro. Cada ruta objetivo debe resolverse por el servidor al shell SPA y por
React al componente lazy correspondiente.

`SpaRouter::sirveLaSpa()` debe excluir primero `/plan-compras/api` y todo descendiente antes de
aceptar el prefijo `/plan-compras`. Esta precedencia es de seguridad: esas 69 rutas conservan
middleware de sesion/proyecto/capacidad y respuesta API; nunca pueden recibir el HTML SPA ni quedar
exentas de autenticacion por compartir prefijo.

### Puente hash

Los fragmentos no llegan al servidor. La ruta shell `/plan-compras` ejecuta antes del router un
puente puro:

1. lee `location.hash` una sola vez;
2. reconoce solo la lista cerrada anterior y `#/maestro`;
3. separa path y query dentro del fragmento;
4. preserva `version`, `a` y `b` cuando corresponden;
5. conserva tambien query params validos ya presentes en la URL;
6. escribe la ruta canonica con `history.replaceState`, nunca `pushState`;
7. elimina el fragmento;
8. no duplica una entrada de historial;
9. deja pasar URLs canonicas sin modificarlas;
10. envia hash desconocido a la pagina segura de no encontrado del modulo, con enlace a Cargar.

Ejemplos:

- `/plan-compras#/ensamble/presupuesto?version=18`
  -> `/plan-compras/ensamble/presupuesto?version=18`;
- `/plan-compras#/ensamble/comparar?a=7&b=12`
  -> `/plan-compras/ensamble/comparar?a=7&b=12`;
- `/plan-compras#/maestro`
  -> `/plan-compras/ensamble/maestro`.

La migracion no reescribe enlaces externos fuera de esta lista ni confia en un path arbitrario
proveniente del fragmento.

### Navegacion interna

- La barra de submodulos usa enlaces, no `role=tab`.
- El enlace activo usa `aria-current=page`.
- Pasos se abre desde Plan y ofrece volver a Plan.
- Las pestañas internas si usan patron tabs accesible.
- Flechas, Home y End mueven foco entre pestañas.
- Cambiar pagina conserva solo query params propios de la pantalla.
- Historial genera rutas canonicas para visor y comparador.
- La sidebar contiene una sola entrada Plan de Compras.
- No aparece selector de semana ni una segunda sidebar.

### Ayuda y recorrido

Cada una de las ocho pantallas conserva un boton de ayuda con:

- que hace;
- que hago yo;
- que pasa despues;
- apartados propios de la pantalla.

El recorrido conserva seis paradas: importar, maestro, presupuesto, paquetes, plan y seguimiento.
Comparar y Pasos se omiten intencionalmente, pero mantienen ayuda. La clave
`aia-pdc-recorrido` y el valor `visto` se conservan. Fallos de `localStorage` son no fatales. Cada
ayuda permite relanzar el recorrido.

## Inventario de contratos HTTP

### Envelope comun

Los 68 endpoints no adaptados conservan exactamente su envelope actual. El cliente comun recibe:

- exito: `{ok:true,data:T}`;
- error: `{ok:false,error:{code:string,message:string,...}}`;
- CSV: respuesta binaria/texto descargable fuera del envelope JSON.

El gateway PDC normaliza el envelope una sola vez y entrega `data` ya validado. No se reescriben los
69 controladores para adoptar otro envelope. Un esquema de error permite extensiones conocidas como
`errores`, pero nunca muestra stack, SQL o ruta interna.

### Presupuesto y contexto — 8

| # | Metodo y ruta | Entrada | Salida principal | Capacidad | UI |
|---:|---|---|---|---|---|
| 1 | GET `/plan-compras/api/contexto` | ninguna | contexto, acciones, navegacion, config, CSRF | `lps.pdc.ver` | si, adaptado |
| 2 | POST `/plan-compras/api/presupuesto/preview` | multipart `archivo` | token, label, resumen, avisos, impacto | `lps.pdc.importar` | si |
| 3 | POST `/plan-compras/api/presupuesto/confirmar` | `{importToken}` | version creada y resumen | `lps.pdc.importar` | si |
| 4 | GET `/plan-compras/api/presupuesto/versiones` | ninguna | versiones | `lps.pdc.ver` | si |
| 5 | POST `/plan-compras/api/presupuesto/activar` | `{versionId}` | confirmacion | `lps.pdc.importar` | si |
| 6 | GET `/plan-compras/api/presupuesto/impacto-version` | ninguna | conteos de impacto | `lps.pdc.ver` | si |
| 7 | GET `/plan-compras/api/presupuesto/arbol` | `?versionId` opcional | arbol, totales, avisos | `lps.pdc.ver` | si |
| 8 | GET `/plan-compras/api/presupuesto/comparar` | `?versionA&versionB` | resumen y diffs | `lps.pdc.ver` | si |

### Maestro — 13

| # | Metodo y ruta | Entrada | Salida principal | Capacidad | UI |
|---:|---|---|---|---|---|
| 9 | GET `/plan-compras/api/maestro` | `?busqueda&incluirInactivos` | catalogo | `lps.pdc.ver` | si |
| 10 | GET `/plan-compras/api/maestro/vinculos` | `?versionId` opcional | resumen y vinculos | `lps.pdc.ver` | si |
| 11 | GET `/plan-compras/api/maestro/sugerencias` | `?vinculoId` | sugerencias | `lps.pdc.ver` | si |
| 12 | POST `/plan-compras/api/maestro/vinculos/generar` | `{versionId?}` | resumen generado | `lps.pdc.maestro` | si |
| 13 | POST `/plan-compras/api/maestro/vinculos/confirmar` | `{vinculoId,maestroId}` | confirmacion | `lps.pdc.maestro` | si |
| 14 | POST `/plan-compras/api/maestro/crear-desde-pendientes` | `{vinculoIds}` | creados y vinculados | `lps.pdc.maestro` | si |
| 15 | POST `/plan-compras/api/maestro` | `{descripcion,unidad,tipoInsumo}` | maestro creado | `lps.pdc.maestro` | no |
| 16 | POST `/plan-compras/api/maestro/desactivar` | `{maestroId}` | revertidos | `lps.pdc.maestro` | si |
| 17 | POST `/plan-compras/api/maestro/reactivar` | `{maestroId}` | reactivado y reenganchados | `lps.pdc.maestro` | si |
| 18 | POST `/plan-compras/api/maestro/importar/preview` | multipart `archivo` | token y resumen | `lps.pdc.maestro` | si |
| 19 | POST `/plan-compras/api/maestro/importar/confirmar` | `{importToken}` | resultado y conflictos | `lps.pdc.maestro` | si |
| 20 | GET `/plan-compras/api/maestro/equipos` | `?q` opcional | cola global | `lps.pdc.ver` | si |
| 21 | POST `/plan-compras/api/maestro/equipos/clasificar` | `{ids,destino}` | clasificados y cola | `lps.pdc.maestro` | si |

### Paquetes — 12

| # | Metodo y ruta | Entrada | Salida principal | Capacidad | UI |
|---:|---|---|---|---|---|
| 22 | GET `/plan-compras/api/paquetes` | `?busqueda` opcional | catalogo | `lps.paquetes_contratacion.ver` | si |
| 23 | GET `/plan-compras/api/paquetes/insumos` | `?filtro&versionId` | insumos | `lps.paquetes_contratacion.ver` | si |
| 24 | GET `/plan-compras/api/paquetes/sugerencias` | `?versionId` opcional | sugerencias | `lps.paquetes_contratacion.ver` | si |
| 25 | GET `/plan-compras/api/paquetes/candidatos` | `?paqueteId&tipoRecurso&versionId` | candidatos | `lps.paquetes_contratacion.ver` | si |
| 26 | GET `/plan-compras/api/paquetes/resumen` | `?versionId` opcional | cobertura y acierto | `lps.paquetes_contratacion.ver` | si |
| 27 | GET `/plan-compras/api/paquetes/insumo-actividades` | `?versionId` opcional | mapa de actividades | `lps.paquetes_contratacion.ver` | si |
| 28 | POST `/plan-compras/api/paquetes` | `{nombre,tipoNegociacion,modalidad?}` | paquete y existente | `lps.paquetes_contratacion.editar` | si |
| 29 | POST `/plan-compras/api/paquetes/asignar` | `{insumos,paqueteId,procedencia?}` | asignados | `lps.paquetes_contratacion.editar` | si |
| 30 | POST `/plan-compras/api/paquetes/omitir` | `{insumos,procedencia?}` | omitidos | `lps.paquetes_contratacion.editar` | si |
| 31 | POST `/plan-compras/api/paquetes/desasignar` | `{insumos}` | desasignados | `lps.paquetes_contratacion.editar` | si |
| 32 | GET `/plan-compras/api/paquetes/plan-auto` | `?versionId&umbral` | preview automatico | `lps.paquetes_contratacion.ver` | no |
| 33 | POST `/plan-compras/api/paquetes/auto-asignar` | `{versionId?,umbral?}` | aplicacion automatica | `lps.paquetes_contratacion.editar` | no |

Cada elemento de `insumos` conserva al menos `descripcionNorm` y `unidad`. La procedencia conserva
origen/capa, confianza, evidencia, confirmacion y paquete sugerido cuando existen.

### Plan — 14

| # | Metodo y ruta | Entrada | Salida principal | Capacidad | UI |
|---:|---|---|---|---|---|
| 34 | GET `/plan-compras/api/plan/frentes` | ninguna | frentes y sinAncla | `lps.paquetes_contratacion.ver` | si |
| 35 | GET `/plan-compras/api/plan/sugerencias` | ninguna | sugerencias y motivos | `lps.paquetes_contratacion.ver` | si |
| 36 | GET `/plan-compras/api/plan/anclas` | ninguna | frentes y actividades | `lps.paquetes_contratacion.ver` | si |
| 37 | GET `/plan-compras/api/plan/correspondencias` | ninguna | mapa y pendientes | `lps.paquetes_contratacion.ver` | si |
| 38 | POST `/plan-compras/api/plan/correspondencias` | `{rama,ancla,alcance}` | guardado | editar; reglas si global | si |
| 39 | GET `/plan-compras/api/plan/desfases` | ninguna | desfases | `lps.paquetes_contratacion.ver` | si |
| 40 | GET `/plan-compras/api/plan/reprogramacion/simular` | ninguna | movidos y huerfanos | `lps.paquetes_contratacion.editar` | si |
| 41 | POST `/plan-compras/api/plan/reprogramacion/aplicar` | `{paqueteIds}` | aplicados e ignorados | `lps.paquetes_contratacion.editar` | si |
| 42 | GET `/plan-compras/api/plan/responsables` | ninguna | responsables elegibles | `lps.paquetes_contratacion.ver` | si |
| 43 | GET `/plan-compras/api/plan` | ninguna | plan, amarres y destinos | `lps.paquetes_contratacion.ver` | si |
| 44 | POST `/plan-compras/api/plan/amarrar` | `{paqueteId,subpaqueteId?,uniqueId,procedencia?}` | amarre | `lps.paquetes_contratacion.editar` | si |
| 45 | POST `/plan-compras/api/plan/desamarrar` | `{paqueteId,subpaqueteId?}` | desamarre | `lps.paquetes_contratacion.editar` | si |
| 46 | POST `/plan-compras/api/plan/calcular` | `{}` | calculados y sinDuracion | `lps.paquetes_contratacion.editar` | si |
| 47 | POST `/plan-compras/api/plan/responsable` | `{paqueteId|paqueteIds,responsableUserId}` | asignados | `lps.paquetes_contratacion.editar` | si |

`responsableUserId` puede ser `null` para quitar responsable. `subpaqueteId` ausente equivale a cero.
La simulacion es GET puro sin CSRF, pero exige editar por politica vigente.

### Pasos — 9

| # | Metodo y ruta | Entrada | Salida principal | Capacidad | UI |
|---:|---|---|---|---|---|
| 48 | GET `/plan-compras/api/plan/pasos` | ninguna | catalogo, proyecto y cobertura | `lps.paquetes_contratacion.ver` | si |
| 49 | POST `/plan-compras/api/plan/pasos/restablecer` | `{}` | recalculo | `lps.paquetes_contratacion.reglas` | si |
| 50 | GET `/plan-compras/api/plan/pasos/historial` | ninguna | historial | `lps.paquetes_contratacion.ver` | si |
| 51 | GET `/plan-compras/api/plan/pasos/origenes` | ninguna | obras accesibles | `lps.paquetes_contratacion.reglas` | si |
| 52 | GET `/plan-compras/api/plan/pasos/copia-preview` | `?origenId` | pasos e incompleta | `lps.paquetes_contratacion.reglas` | si |
| 53 | POST `/plan-compras/api/plan/pasos/copiar` | `{origenId}` | copia y recalculo | `lps.paquetes_contratacion.reglas` | si |
| 54 | GET `/plan-compras/api/plan/duraciones` | ninguna | duraciones usadas | `lps.paquetes_contratacion.reglas` | si |
| 55 | POST `/plan-compras/api/plan/duraciones` | `{duracionRef,dias}` | recalculo | `lps.paquetes_contratacion.reglas` | si |
| 56 | POST `/plan-compras/api/plan/pasos` | `{pasos}` | guardado y recalculo | `lps.paquetes_contratacion.reglas` | si |

Cada paso enviado conserva `clave`, `alias?` y `diasFijos?`. El servidor revalida obra origen y
duracion disponible; el selector del navegador no es autoridad.

### Seguimiento — 6

| # | Metodo y ruta | Entrada | Salida principal | Capacidad | UI |
|---:|---|---|---|---|---|
| 57 | GET `/plan-compras/api/seguimiento/vencimientos` | `?paso&responsable` | hoy, filas, cortes | `lps.paquetes_contratacion.ver` | si |
| 58 | GET `/plan-compras/api/seguimiento/paquete` | `?paqueteId&subpaqueteId` | pasos | `lps.paquetes_contratacion.ver` | si |
| 59 | POST `/plan-compras/api/seguimiento/paso` | `{paqueteId,subpaqueteId?,pasoId,fechaReal}` | paso actualizado | `lps.paquetes_contratacion.editar` | si |
| 60 | GET `/plan-compras/api/seguimiento` | ninguna | resumen y desactualizados | `lps.paquetes_contratacion.ver` | si |
| 61 | GET `/plan-compras/api/seguimiento/flujo-caja.csv` | ninguna | CSV descargable | `lps.paquetes_contratacion.ver` | si |
| 62 | GET `/plan-compras/api/seguimiento/flujo-caja` | ninguna | meses, origenes, excluidos | `lps.paquetes_contratacion.ver` | si |

`fechaReal` acepta fecha ISO o `null` para deshacer. El filtro de responsable acepta ID, `sin` o
ausencia.

### Subpaquetes — 7

| # | Metodo y ruta | Entrada | Salida principal | Capacidad | UI |
|---:|---|---|---|---|---|
| 63 | GET `/plan-compras/api/subpaquetes/destinos` | ninguna | destinos contratables | `lps.paquetes_contratacion.ver` | no |
| 64 | GET `/plan-compras/api/subpaquetes` | `?paqueteId` | paquete, lotes e insumos | `lps.paquetes_contratacion.ver` | si |
| 65 | POST `/plan-compras/api/subpaquetes/partir` | `{paqueteId,nombres}` | estado resultante | `lps.paquetes_contratacion.editar` | si |
| 66 | POST `/plan-compras/api/subpaquetes/agregar` | `{paqueteId,nombre,modalidad}` | estado resultante | `lps.paquetes_contratacion.editar` | si |
| 67 | POST `/plan-compras/api/subpaquetes/actualizar` | `{subpaqueteId,nombre?,modalidad?,responsableUserId?}` | estado resultante | `lps.paquetes_contratacion.editar` | si |
| 68 | POST `/plan-compras/api/subpaquetes/eliminar` | `{subpaqueteId}` | estado resultante | `lps.paquetes_contratacion.editar` | si |
| 69 | POST `/plan-compras/api/subpaquetes/mover` | `{subpaqueteId,insumos}` | estado resultante | `lps.paquetes_contratacion.editar` | si |

Los campos editables conservan nombre, modalidad, frente, responsable y configuracion de proceso
que el controlador actual acepta. La UI no inventa campos si el contrato servido no los contiene.

## Contexto adaptado y acciones efectivas

`GET /plan-compras/api/contexto` es el unico endpoint adaptado. Su respuesta objetivo contiene:

```json
{
  "contractVersion": 2,
  "project": {"id": 27, "name": "Proyecto"},
  "user": {"id": 81, "name": "Persona"},
  "csrfToken": "opaco",
  "config": {
    "maxUploadBytes": 10485760,
    "acceptedBudgetExtensions": [".xlsx"],
    "acceptedMasterExtensions": [".xlsx"]
  },
  "navigation": {
    "defaultRoute": "/plan-compras/ensamble/importar",
    "routes": [
      {"id": "budget-import", "path": "/plan-compras/ensamble/importar", "label": "Cargar presupuesto"},
      {"id": "master", "path": "/plan-compras/ensamble/maestro", "label": "Maestro"}
    ],
    "hashAliases": {
      "#/ensamble/importar": "/plan-compras/ensamble/importar",
      "#/maestro": "/plan-compras/ensamble/maestro"
    }
  },
  "actions": {}
}
```

`user.id` es entero positivo o `null` cuando la sesion no puede resolverse a usuario. El ejemplo
abrevia `routes` y `hashAliases`; el contrato real enumera las ocho rutas y los nueve aliases
cerrados definidos en esta spec. `routes` contiene solo rutas visibles para la sesion.
`actions` es un objeto cerrado con exactamente estos 37 booleanos:

- `budget.view`, `budget.import`, `budget.activate`;
- `master.view`, `master.generateLinks`, `master.manage`, `master.import`,
  `master.classifyEquipment`;
- `packages.view`, `packages.create`, `packages.assign`, `packages.omit`,
  `packages.unassign`;
- `subpackages.view`, `subpackages.split`, `subpackages.create`,
  `subpackages.update`, `subpackages.delete`, `subpackages.move`;
- `plan.view`, `plan.attach`, `plan.detach`, `plan.calculate`,
  `plan.assignResponsible`, `plan.simulateReprogram`, `plan.applyReprogram`,
  `plan.saveProjectCorrespondence`, `plan.saveGlobalCorrespondence`;
- `steps.view`, `steps.viewHistory`, `steps.configure`, `steps.copy`,
  `steps.editDurations`, `steps.reset`;
- `tracking.view`, `tracking.registerActualDate`, `tracking.downloadCashFlow`.

No contiene `role` ni una matriz de roles. Cada booleano se deriva llamando
`RbacService::can()` a la capacidad actual. El backend de cada endpoint sigue revalidando; las
acciones solo evitan controles engañosos.

### Compatibilidad del contexto durante piloto

La isla actual espera claves planas y un `rol` que ninguna de sus paginas usa. Para no romperla
entre el primer cambio de servidor y el corte:

1. el contrato piloto es una union discriminada por `contractVersion: 2`;
2. entrega el objeto objetivo anterior y, temporalmente, las claves planas `projectId`,
   `proyectoNombre`, `usuario`, `usuarioId` y `rol: ""`;
3. nunca entrega el rol real en ese campo transitorio;
4. el schema piloto enumera esas claves deprecadas de forma explicita y el adaptador de la SPA no
   las propaga al dominio;
5. la isla caracteriza que un rol vacio no altera ninguna pantalla;
6. al corte se eliminan las claves planas y queda solo el contrato objetivo;
7. ambos estados del mismo endpoint tienen contrato PHP y Zod.

No se crea version de URL ni un segundo endpoint. La transicion existe solo mientras conviven los
dos consumidores y se retira junto con la isla.

## Matriz efectiva de capacidades

La matriz fallback auditada, antes de overrides, es:

| Rol canonico | PDC ver | Importar | Maestro | Paquetes ver | Paquetes editar | Reglas |
|---|---:|---:|---:|---:|---:|---:|
| A | si | si | si | si | si | si |
| D | si | si | si | si | si | si |
| OT | si | no | si | si | si | si |
| R | si | no | no | si | no | no |
| DCV | si | no | no | si | no | no |
| V | si | no | no | si | no | no |
| G | no | no | no | no | no | no |
| S | no | no | no | no | no | no |
| SG | no | no | no | no | no | no |
| C | no | no | no | no | no | no |

El alias crudo `P` se normaliza a `D`; por tanto hereda la fila D en el runtime actual. Overrides de
RBAC siguen siendo autoridad y pueden producir una combinacion distinta. React no codifica esta
tabla. `lps.pdc.editar` existe en catalogo, pero ninguna de las 69 rutas auditadas lo usa como guard
de mutacion; no se le inventa una accion.

Las lecturas y mutaciones se separan por endpoint. En particular:

- equipos se puede leer con `lps.pdc.ver`, clasificar exige maestro;
- pasos e historial se leen con paquetes ver;
- origenes, preview de copia y duraciones exigen reglas aun siendo GET;
- simulacion de reprogramacion exige editar y no CSRF;
- correspondencia global exige reglas, proyecto exige editar;
- CSV exige paquetes ver;
- todas las mutaciones exigen su capacidad y CSRF.

## Contrato por pantalla

### 1. Cargar presupuesto

La pantalla incluye:

- encabezado, explicacion, ayuda y acceso al recorrido;
- selector de archivo, drag and drop y nombre accesible;
- validacion local de extension/tamaño antes de enviar;
- progreso de preview sin fingir porcentaje de red;
- errores por fila/columna y resumen de validacion;
- advertencias, `sinCambios`, version activa y obsoleta;
- impacto completo antes de confirmar;
- confirmacion all-or-nothing;
- resultado con version, resumen y comparacion posterior cuando aplica;
- historial buscable;
- maximo dos versiones marcadas;
- acceso a visor de una version;
- comparacion ordenada A antigua/B nueva;
- activar version con impacto y confirmacion;
- controles deshabilitados u ocultos con explicacion si falta importar;
- recuperacion de token expirado subiendo de nuevo.

### 2. Maestro

La pantalla incluye:

- resumen de cobertura y pendientes;
- pestañas Pendientes, Catalogo, Importar SINCO y Equipos cuando hay cola;
- refresco automatico de vinculos solo si `master.generateLinks`;
- anuncio de esa actualizacion y fallback de error sin ocultar la lectura;
- seleccion multiple de pendientes;
- creacion desde pendientes;
- sugerencias y confirmacion individual;
- busqueda de catalogo;
- incluir retirados;
- retirar/reactivar con resultado y reenganchados;
- preview/confirmacion de SINCO;
- conflictos y resumen de carga;
- cola global de equipos aunque no haya presupuesto;
- busqueda, pistas, seleccion por lote y clasificacion humana;
- estado de solo lectura real sin provocar 403.

No se añade formulario manual porque el endpoint no tiene consumidor actual.

### 3. Presupuesto

La pantalla incluye:

- selector de version y query `version`;
- aviso de version obsoleta;
- arbol y modo plano;
- expandir por nivel;
- busqueda;
- filtros de tipo y unidad;
- filtros de grilla y chips removibles;
- conteos de filas, tipos y unidades;
- costo total;
- actividades sin cantidad e insumos en cero;
- candidatos del tamiz;
- umbral personal editable y restablecible;
- persistencia local por proyecto;
- estado sin version;
- solo lectura, sin acciones de mutacion.

### 4. Comparar

La pantalla incluye:

- selectores A/B y queries `a`, `b`;
- validacion de versiones distintas;
- advertencia obsoleta por cada lado;
- resumen de costo A/B, delta, sobrecostos, ahorros, nuevos, eliminados y modificados;
- eje de actividades jerarquico;
- eje de insumos con Pareto;
- busqueda, filtros y nivel de expansion;
- delta absoluto, porcentual y estado;
- vacio cuando no hay dos versiones;
- solo lectura.

### 5. Paquetes

La pantalla incluye:

- pestañas Masivo, Asistente y Paquetes;
- filtros sin asignar/asignados/omitidos/todos;
- busqueda, agrupacion, tipo, grilla y chips;
- cobertura por cantidad y valor;
- tasa de acierto con base;
- estado 100 % resuelto;
- seleccion multiple;
- sugerencias con evidencia y confianza;
- aceptar sugerencias;
- asignar, omitir y desasignar;
- crear paquete con uno de los cinco tipos y una de las cuatro modalidades validas;
- tooltip/ruta de actividades;
- compatibilidad de candidatos;
- asistente con siguiente, atras, saltar y deshacer;
- creacion/asignacion/omision desde el asistente;
- lista de paquetes con subtotal y apertura de subpaquetes;
- read-only visible cuando falta editar.

### 6. Plan

La pantalla incluye:

- pestañas Plan, Sin frente, Sin calcular y Desfases;
- resumen de paquetes/destinos;
- tabla o tarjetas con pasos expandibles;
- fechas, frente, arranque, duracion, responsable, retraso y vencimiento;
- asignacion individual y por lote de responsable;
- posibilidad explicita de dejar sin responsable;
- recalculo y resultado;
- enlace a Pasos;
- desamarre confirmado;
- sugerencias de frente y motivos sin propuesta;
- aceptar en lote alta confianza;
- selector manual de anclas;
- correspondencia por proyecto/global segun acciones;
- confirmacion de sugerencia;
- simulacion de reprogramacion;
- seleccion y aplicacion explicita;
- preservacion visible de fechas reales;
- huerfanos y errores accionables.

### 7. Pasos

La pantalla incluye:

- enlace de regreso a Plan;
- configuracion efectiva y origen default/proyecto;
- catalogo disponible;
- agregar, quitar, mover, alias y dias fijos;
- validacion antes de guardar;
- impacto sobre paquetes con plan;
- guardar y resultado de recalculo;
- origenes accesibles;
- preview de copia y advertencia incompleta;
- copiar;
- historial;
- restablecer a defaults;
- duraciones de catalogo usadas por la obra;
- edicion con alcance global explicado;
- secciones de lectura aun cuando reglas no estan disponibles;
- acciones ocultas/deshabilitadas segun contexto, sin sondear 403.

### 8. Seguimiento

La pantalla incluye:

- pestañas Paquetes, Vencimientos y Flujo de caja;
- busqueda, estado, responsable, frente, atrasados y solo mios;
- resumen de estado y plan desactualizado;
- apertura de detalle por destino;
- pasos con fechas programadas/proyectadas/reales;
- registrar o limpiar fecha real;
- validacion de fecha ISO y errores de servidor;
- vencimientos por corte, paso y responsable;
- conteos y filas sin fecha;
- reloj `hoy` del servidor;
- flujo mensual con barras y tabla;
- origen contratado/permanente/provisional;
- cobertura de fechas y excluidos;
- texto metodologico;
- total residual exacto;
- descarga CSV accesible;
- solo lectura para quien no puede registrar avance.

## Arquitectura React objetivo

### Modulo

El codigo se absorbe bajo `frontend/src/modules/plan-compras/`:

- `api/`: schemas Zod, gateway y fixtures por dominio;
- `domain/`: tipos inferidos, calculos puros y adaptadores;
- `routes/`: ocho route components lazy;
- `components/`: navegacion, filtros, tablas, tarjetas, ayuda, tour y dialogs;
- `budget/`, `master/`, `packages/`, `plan/`, `steps/`, `tracking/`;
- `styles/`: capas del modulo consumiendo tokens, sin valores de marca locales.

Se portan primero funciones puras ya probadas. Los tipos de wire se infieren de Zod; no se copia
`types.ts` como una segunda fuente manual.

### Cliente y schemas

Todo HTTP pasa por `frontend/src/lib/api/cliente.ts`. Si T01 aun no lo provee, se amplía una sola vez
para:

- GET JSON con query tipado;
- POST JSON;
- multipart con `FormData`;
- descarga con nombre de archivo;
- headers `X-AIA-Expect-Json` y `X-CSRF-Token`;
- cookies same-origin;
- `AbortSignal`;
- no reintentar mutaciones automaticamente;
- mapear 401/403/409/410/413/422/500;
- distinguir error HTTP, envelope, schema y red.

El gateway PDC recibe el envelope y aplica el esquema del endpoint. Los 65 consumos tienen schemas
cerrados agrupados en contexto, presupuesto, maestro, paquetes, plan, pasos, seguimiento y
subpaquetes. Respuestas con claves no descritas fallan en desarrollo/test; campos deliberadamente
extensibles se declaran de forma explicita, no con `unknown` general.

El CSV usa la misma frontera de cliente. No se permite un `<a href>` que eluda manejo de sesion,
errores y filename si el cliente comun puede descargarlo de forma segura.

### Estado y concurrencia

- Contexto se carga una vez por proyecto y se invalida al cambiar proyecto.
- Cada pagina carga solo sus endpoints.
- Navegar cancela lecturas obsoletas.
- Filtros y pestañas son estado de UI, no autoridad.
- Mutaciones bloquean solo la accion afectada.
- No hay reintento automatico de POST.
- Tras exito se reconcilia con respuesta o recarga explicita del dominio afectado.
- Error conserva seleccion, archivo o borrador cuando es seguro.
- Cambiar version invalida arbol/comparativo/paquetes relacionados.
- Cambiar proyecto borra caches y seleccion local, pero conserva las claves globales declaradas.
- No se mantiene un store paralelo con datos de otro proyecto.

### AG Grid y representaciones

AG Grid Community se agrega a `frontend/` y se carga dentro del chunk de Plan de Compras. No se
registra globalmente en el shell. Desktop/tablet puede conservar:

- orden;
- filtros;
- seleccion;
- expansion;
- celdas numericas;
- columnas adaptables;
- aviso de columnas prescindibles ocultas.

Bajo 768 px cada grid tiene lista/tarjeta/formulario nativo equivalente. No se renderizan a la vez
grid y tarjetas. Ambos consumen la misma coleccion, filtros y acciones. Una tabla financiera o arbol
puede usar scroll horizontal interno declarado si mantiene encabezados, foco y operacion; la pagina
nunca crea overflow horizontal global.

## Responsive

Viewports de aceptacion:

| Nombre | Ancho | Representacion |
|---|---:|---|
| movil estrecho | 390 | tarjetas/listas nativas |
| movil grande | 480 | tarjetas/listas nativas |
| tablet | 768 | grid o tabla compacta |
| desktop canonico | 1180 | grid denso |
| desktop amplio | 1440 | grid denso |

Reglas:

- breakpoint unico de representacion en 768 px;
- una sola representacion montada;
- filtros se abren en panel accesible en movil;
- acciones primarias permanecen visibles;
- seleccion multiple muestra barra de accion fija sin tapar contenido;
- dialogs caben a 320 CSS px y zoom 200 %;
- inputs de fecha y numero no pierden etiqueta;
- ayudas y recorrido no desbordan;
- charts de flujo tienen tabla textual equivalente;
- no se oculta informacion critica solo por ancho;
- targets touch minimo 44 por 44 CSS px;
- orientacion horizontal no es requisito para completar una tarea.

## Estados, validacion y errores

### Estados de carga y vacio

Cada pantalla distingue:

- contexto cargando;
- pagina cargando;
- carga parcial secundaria;
- sin presupuesto;
- sin versiones suficientes;
- sin pendientes;
- sin paquetes;
- sin plan calculado;
- sin vencimientos;
- sin flujo;
- filtros sin resultado;
- solo lectura;
- error recuperable;
- sesion expirada;
- proyecto perdido;
- contrato invalido.

Los vacios indican siguiente accion solo si esta autorizada. No muestran botones que terminaran en
403.

### Validacion cliente y servidor

Cliente valida antes de enviar:

- archivo `.xlsx`, tamaño maximo y presencia;
- dos versiones distintas;
- IDs positivos y `subpaqueteId >= 0`;
- seleccion no vacia;
- nombre requerido y longitud servida;
- tipo/modalidad dentro del catalogo permitido;
- umbral numerico no negativo;
- pasos no vacios, claves unicas, dias enteros/no negativos segun contrato;
- fecha real ISO valida o `null`;
- cantidades, porcentajes y moneda solo para presentacion cuando el servidor los calcula.

Servidor sigue siendo autoridad y repite todas las validaciones. No se fabrican limites que el
backend no tenga; los valores exactos se extraen de contratos o contexto.

### Errores

- 401: shell lleva a sesion expirada conservando return URL segura.
- 403: pagina/accion explica solo lectura y refresca contexto si la autorizacion cambio.
- 404 `NO_VERSION`: muestra estado sin version o version inexistente segun pantalla.
- 409 `NO_PROJECT`: vuelve al selector de proyecto.
- 410 `TOKEN_EXPIRED`: conserva archivo solo si el navegador aun lo tiene y pide preview nuevo.
- 413 `FILE_TOO_LARGE`: informa 10 MB.
- 422: enfoca resumen y enlaza campos/filas invalidas.
- 500: mensaje generico, correlacion si backend la ofrece y reintento manual.
- error Zod: estado de contrato incompatible, nunca datos parciales silenciosos.
- error de red: conserva estado local y ofrece reintentar lectura.
- error de mutacion: no muestra exito optimista irreversible.

Mensajes se anuncian en live region. El foco va al resumen de error o vuelve al disparador al cerrar
un dialog.

## Seguridad, aislamiento y RLS

S12 no modifica RLS. Conserva la frontera documentada:

- sesion y proyecto activos son obligatorios;
- el servidor resuelve `project_id`;
- el navegador no envia un proyecto como autoridad;
- cada lectura/mutacion de obra filtra por `project_id`;
- datos globales se exponen solo por servicios y capacidades existentes;
- origen de copia se revalida contra proyectos accesibles;
- IDs de version, paquete, lote, paso, frente y responsable se revalidan en scope;
- CSRF se adjunta solo a mutaciones;
- CSV exige sesion/capacidad igual que JSON;
- errores no filtran SQL, path ni secretos;
- no se guarda CSRF, rol o datos sensibles en localStorage;
- no se toca schema, grants, usuarios, credenciales ni datos.

Pruebas de aislamiento usan fakes o fixtures interceptados. La verificacion de S12 no crea dos
proyectos reales ni restaura datos.

## Tema, tokens y accesibilidad

### Temas

- oscuro y claro tienen la misma informacion y acciones;
- el tema inicial y la preferencia pertenecen a T01;
- el modulo no fuerza `data-aia-theme=dark`;
- AG Grid recibe colores desde CSS variables derivadas de tokens;
- estados no dependen solo de color;
- no se copian fallbacks hex de `pdc-app/src/styles.css`;
- contraste y focus cumplen WCAG AA;
- graficas de flujo tienen patrones/etiquetas y tabla equivalente.

### Accesibilidad

- un `h1` por pantalla;
- landmarks y nombres de navegacion unicos;
- enlaces de submodulo con `aria-current`;
- tabs internas con roles, relaciones y teclado correctos;
- tablas semanticas o AG Grid con nombre y columnas comprensibles;
- tarjetas preservan orden de lectura;
- selectores buscables usan combobox/listbox valido;
- dialogs tienen titulo, descripcion, focus trap y restauracion;
- confirmacion de borrado no usa `window.confirm`;
- errores se asocian a controles;
- progreso y resultados se anuncian;
- tooltip no es la unica fuente de informacion;
- recorrido se puede omitir, cerrar y relanzar;
- reduced motion desactiva transiciones no esenciales;
- zoom 200 % y reflow no bloquean acciones;
- teclado completa todas las mutaciones;
- touch completa todas las mutaciones.

## Convivencia strangler y corte

### Piloto

Durante piloto:

- la isla sigue siendo ruta canonica;
- la SPA principal expone una ruta piloto interna no enlazada o protegida por flag local;
- ambas consumen los mismos endpoints;
- solo contexto se adapta de forma backward compatible;
- la isla ignora claves nuevas de contexto;
- no se cambia ninguna mutacion;
- evidencia compara resultados con fixtures identicos.

### Corte

El corte canonico ocurre solo si:

1. ocho rutas y alias hash pasan;
2. 65 consumos Zod pasan;
3. matriz de acciones pasa;
4. desktop/tablet/movil pasan;
5. oscuro/claro y accesibilidad pasan;
6. descargas/uploads pasan;
7. no hay imports desde `pdc-app/`;
8. no hay referencias runtime a `/pdc-app/assets`;
9. rollback de ruta se ejercito;
10. assets y vista exclusivos tienen cero consumidores.

Entonces:

- `SpaRouter` sirve `/plan-compras` y descendientes;
- sidebar apunta a ruta canonica;
- se retira `HashRouter`;
- se retira `window.__PDC_BOOTSTRAP__`;
- se retira VIEW-31;
- se retira el controlador de pagina si queda sin consumidores;
- se retira `pdc-app/` y su package/build;
- se retira `public/pdc-app/` generado;
- se actualiza el manifiesto de design system;
- se conservan las 69 APIs;
- se conserva puente hash al menos durante la ventana de compatibilidad acordada.

No se elimina un archivo por nombre: se demuestra cero consumidor con busqueda, build y rutas.

## Estrategia de pruebas

### Caracterizacion pura

Antes de portar:

- tipos/modalidades exactos;
- rutas y navegacion;
- ayuda de ocho pantallas;
- recorrido de seis pasos;
- tamiz y clave local;
- historial de versiones;
- arbol/comparativo;
- filtros, chips y listas;
- paquetes/wizard;
- pasos y validaciones;
- vencimientos;
- flujo de caja;
- subpaquetes;
- NUL accidental reconstruido como `sin-elegir` valido.

### Contratos PHP sin base mutable

Se añaden pruebas de contrato para:

- contexto adaptado y acciones;
- cada familia de schemas consumidos;
- envelopes de exito/error;
- multipart y limite;
- CSV headers/BOM/separador;
- ruta shell y hash bridge;
- capacidades por endpoint;
- scope y CSRF mediante dobles.

Todo endpoint adaptado tiene contrato PHP. No se exige reescribir 68 controladores sin cambio, pero
sus respuestas consumidas quedan caracterizadas por fixtures PHP estables.

### Vitest y Testing Library

Cobertura:

- 65 schemas aceptan fixture canonico y rechazan drift;
- gateway JSON/multipart/download;
- 8 route components;
- 13 pestañas estables y equipos condicional;
- acciones efectivas;
- todos los filtros/conteos;
- uploads y tokens;
- version/query/deep links;
- tabla/tarjetas con paridad;
- dialogs y recuperacion;
- ayuda/recorrido;
- oscuro/claro por tokens;
- no `fetch` fuera de cliente;
- una sola representacion montada.

### Playwright completamente interceptado

Toda red se intercepta antes de navegar. Escenarios:

1. ocho rutas canonicas y siete entradas;
2. tres hashes representativos y alias;
3. importar presupuesto completo y token expirado;
4. Maestro writable/read-only y cola de equipos;
5. visor con version/tamiz;
6. comparador con obsoleta;
7. paquetes masivo/asistente/subpaquetes;
8. plan, amarre, responsable, calculo y reprogramacion simulada;
9. pasos lectura/reglas/copia/historial;
10. seguimiento, fecha real, vencimientos, flujo y CSV;
11. A/D/OT/R/DCV/V y denegados G/C mediante contexto;
12. 390, 480, 768, 1180 y 1440;
13. oscuro/claro;
14. axe, teclado, focus, zoom, reduced motion;
15. 401, 403, 404, 409, 410, 413, 422, 500, red y schema.

Se inspeccionan consola, red, descargas y ausencia de peticiones no interceptadas.

### Verificaciones reales prohibidas

No ejecutar como evidencia S12:

- imports de presupuesto o SINCO contra datos reales;
- activar una version real;
- generar/confirmar vinculos reales;
- clasificar equipos reales;
- asignar/omitir/desasignar insumos reales;
- crear paquetes o subpaquetes reales;
- amarrar, calcular, reprogramar o asignar responsables reales;
- guardar/copiar/restablecer pasos o duraciones reales;
- registrar fechas reales;
- cualquier test PDC que haga INSERT, UPDATE, DELETE o restauracion;
- migraciones, seeds, backfills o reconciliaciones;
- browser specs que preparen sandbox SQL.

## Criterios de aceptacion

1. `/plan-compras` y ocho rutas objetivo se sirven dentro de la SPA principal, mientras
   `/plan-compras/api` y sus descendientes nunca se clasifican como SPA.
2. Las siete entradas principales y Pasos anidado coinciden con el inventario.
3. Los hashes antiguos conocidos migran con replace y preservan `version`, `a`, `b`.
4. Un hash desconocido no se ejecuta ni se interpola como ruta.
5. La sidebar tiene una sola entrada y no aparece selector de semana.
6. No queda `fetch` fuera de `frontend/src/lib/api/cliente.ts`.
7. JSON, multipart y CSV usan la frontera comun.
8. Contexto es el unico endpoint adaptado y tiene contrato PHP.
9. Las otras 68 combinaciones conservan metodo, ruta, payload y envelope.
10. Los cuatro endpoints sin consumidor siguen registrados y no reciben UI inventada.
11. Los 65 consumos reales tienen Zod estricto.
12. El navegador no recibe ni interpreta rol crudo.
13. Acciones efectivas provienen de `RbacService` y cada endpoint revalida.
14. Alias `P`, overrides y fallback conservan comportamiento servidor.
15. Importacion valida `.xlsx`, 10 MB, preview, impacto, confirmacion y expiracion.
16. Historial conserva versiones, obsoletas, activar, visor y comparar.
17. Maestro conserva cobertura, pendientes, catalogo, SINCO y equipos.
18. El refresco automatico de vinculos solo corre con accion efectiva y se anuncia.
19. Presupuesto conserva arbol, plano, filtros, avisos, tamiz y clave local.
20. Comparar conserva dos ejes, resumen, deltas, Pareto y query params.
21. Paquetes conserva tres pestañas, filtros, cobertura, sugerencias y wizard.
22. Los cinco tipos y cuatro modalidades vigentes se ofrecen y quedan fijados contra backend.
23. Subpaquetes conserva partir, resto, CRUD, mover, metadata y semantica cero.
24. Plan conserva cuatro pestañas, amarres, responsables, calculo y desfases.
25. Reprogramacion siempre muestra simulacion antes de aplicar.
26. Pasos conserva lectura, configuracion, copia, historial, reset y duraciones.
27. Seguimiento conserva tres pestañas, detalle, fecha real, vencimientos y flujo.
28. CSV conserva BOM, punto y coma, contenido metodologico y nombre de descarga.
29. Ayuda existe en ocho pantallas y recorrido conserva seis paradas/clave.
30. Los vacios muestran siguiente accion solo si esta autorizada.
31. 401/403/404/409/410/413/422/500, red y schema son distinguibles.
32. Errores de mutacion conservan estado util y no muestran exito falso.
33. AG Grid Community se carga solo en el modulo y sin Enterprise.
34. Desktop/tablet conserva filtros, seleccion, expansion y datos.
35. Movil usa vistas nativas para todas las tareas y no solo columnas ocultas.
36. Una sola representacion esta montada en cada breakpoint.
37. No hay overflow horizontal de pagina en viewports objetivo.
38. Oscuro y claro tienen la misma capacidad y usan tokens.
39. Teclado, lector, touch, zoom 200 %, focus y reduced motion pasan.
40. Dialogs reemplazan `window.confirm` y restauran foco.
41. Cambio de proyecto invalida datos y no mezcla scopes.
42. Ninguna prueba de migracion ejecuta DDL/DML real.
43. Playwright intercepta toda red antes de navegar.
44. Piloto conserva rollback de ruta.
45. El corte demuestra cero consumidor antes de borrar isla, vista y assets.
46. Las 69 APIs permanecen disponibles despues del corte.
47. Manifiesto de design system declara ocho rutas, cinco viewports, ambos temas y estados.
48. No se modifica RLS, schema, grants, usuarios, credenciales, roles ni datos.

## Entregas verticales

### Entrega 1 — Frontera, contratos y rutas

Caracteriza los 69 contratos, adapta contexto, añade 65 schemas, gateway comun, rutas lazy y puente
hash. Entrega shell principal con navegacion y paginas placeholder solo en tests, sin cortar isla.

### Entrega 2 — Presupuesto y maestro

Porta Cargar, Maestro, Presupuesto y Comparar con ayuda, acciones, uploads, versiones, tamiz,
desktop/tablet/movil y temas.

### Entrega 3 — Paquetes y subpaquetes

Porta masivo, wizard, catalogo, sugerencias, cobertura y panel de subpaquetes completo.

### Entrega 4 — Plan y pasos

Porta amarres, calculo, responsables, correspondencias, desfases, reprogramacion y configuracion de
pasos/duraciones.

### Entrega 5 — Seguimiento y calidad

Porta avance, vencimientos, flujo/CSV, completa responsive, accesibilidad, RBAC, estados, tour,
manifiesto y pruebas interceptadas.

### Entrega 6 — Corte

Compara isla/SPA, ejercita rollback, cambia ruta canonica y retira build, bootstrap, vista y assets
exclusivos con prueba de cero consumidores.

## Riesgos y mitigaciones

| Riesgo | Mitigacion |
|---|---|
| Portar una app React como copia literal mantiene dos arquitecturas | schemas, cliente, router, shell y tokens comunes primero |
| 65 schemas se vuelven inmanejables | agrupar por dominio y reutilizar piezas cerradas |
| Drift servidor/tipos manuales | tipos inferidos de Zod y fixtures PHP |
| Hash rompe favoritos | puente cerrado con replace y tests de query |
| Permisos visibles por prueba/error | acciones efectivas en contexto y guard servidor |
| Contexto nuevo rompe isla | extension backward compatible |
| AG Grid agranda bundle global | lazy chunk solo del modulo |
| Movil pierde acciones | variantes nativas por grid y matriz de paridad |
| Datos de proyecto se mezclan | key de cache por proyecto e invalidacion total |
| Upload duplica mutacion al reintentar | no retry automatico y token de preview |
| CSV pierde autenticacion/nombre | cliente comun de descarga con headers validados |
| Se borra endpoint sin UI | inventario 69 y lista explicita de cuatro compat |
| Se ejecutan tests mutables | fakes/intercepcion y lista prohibida |
| Regla documentada esta obsoleta | codigo y tests vigentes prevalecen |
| NUL se copia al nuevo modulo | reconstruccion textual y test de bytes |
| Corte borra activos aun usados | cero-consumidor + build + rollback antes de delete |

## Decisiones descartadas

- Mantener `pdc-app/` como microfrontend permanente.
- Embeber el bundle separado dentro de la SPA principal.
- Conservar `HashRouter`.
- Reescribir las 69 APIs para otro envelope.
- Añadir una API nueva por pantalla.
- Inferir permisos por rol en React.
- Mantener botones y aprender autorizacion por 403.
- Quitar los cuatro endpoints no consumidos.
- Crear UI para autoasignacion o maestro manual sin evidencia.
- Reemplazar AG Grid en desktop.
- Usar el grid encogido como experiencia movil.
- Llevar el modulo al drawer T02.
- Forzar tema oscuro.
- Cambiar reglas de frentes, versiones, paquetes, subpaquetes o flujo.
- Ejecutar pruebas con rollback SQL.
- Cortar y borrar la isla en la primera entrega.

## Decisiones pendientes

Ninguna decision de negocio, producto, estrategia o PM bloquea S12. Las aparentes contradicciones de
pestañas, rol `P`, frentes y tipos creables se resuelven por caracterizacion del codigo vigente,
sin cambiar politica. El backend vigente acepta `no_aplica`; por eso React ofrece los cinco tipos.
Es una constatacion tecnica ya auditada, no una decision de producto.

## Siguiente gate

Invocar `superpowers:writing-plans` para convertir esta spec en un plan TDD por entregas verticales.
No implementar hasta que el plan quede escrito, autorrevisado, trazado 48/48 y el programa
documental de 27 superficies cierre.
