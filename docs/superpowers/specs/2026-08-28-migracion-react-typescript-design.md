---
capa: fuente
tipo: spec
estado: vigente
version: "0.1"
fecha: 2026-08-29
areas: [arquitectura, design-system]
fuente: "auditoría del código en shell-minimo-react; decisiones de Felipe del 2026-08-29 y atlas aprobado el 2026-08-30"
resumen: "Programa integral para migrar a una sola SPA React + TypeScript todas las superficies de la aplicación principal, con catálogo exhaustivo de módulos, páginas, vistas y piezas compartidas. /admin/ queda expresamente excluido."
---

# Migración integral a React + TypeScript — diseño v0.1

> **Estado: borrador para revisión de Felipe.** La arquitectura documental fue aprobada el
> 2026-08-29: una spec maestra exhaustiva y una spec hija por módulo. Esta versión enumera todo el
> frontend de la aplicación principal y deja fuera, por decisión explícita, la miniaplicación
> `/admin/`. No autoriza implementación, cambios de datos, DDL/DML, deploy ni publicación.

## 1. Resultado buscado

La aplicación principal termina siendo **una sola SPA React + TypeScript**, servida por PHP en el
mismo origen y montada en producción bajo las rutas actuales. PHP conserva sesión, RBAC, CSRF,
aislamiento por proyecto, servicios, cálculos, reportes y acceso a datos; deja de renderizar las
pantallas de negocio.

La migración no se considera completa porque una pantalla “abra” en React. Cada pieza debe conservar
como mínimo toda capacidad observable del producto actual:

- acceso, permisos y restricciones reales;
- carga, edición, validación, guardado y recarga;
- filtros, búsqueda, orden, conteos, estados, alertas y leyendas;
- importaciones, exportaciones y descargas;
- selección de proyecto y semana;
- drawers, modales, notificaciones y navegación contextual;
- estados de carga, vacío, error, sesión vencida y acceso denegado;
- responsive, teclado, foco y demás requisitos de accesibilidad;
- pruebas de contrato, funcionales y visuales proporcionales al riesgo.

Puede cambiar la apariencia cuando la nueva composición use el design system, pero **no puede
desaparecer una función por accidente ni porque estuviera escondida en JS inline, un modal o una
ruta procedural**.

## 2. Autoridad, relación entre specs y regla de no invención

Esta es la **spec maestra del programa**. Fija el destino, enumera todas las piezas, ordena las
entregas y define cuándo una migración cuenta como cerrada. No sustituye la auditoría profunda de
cada módulo.

Cada frente funcional necesita, antes de su plan de implementación, una **spec hija** que inspeccione
su controlador, rutas, APIs, payloads, vistas, JS, permisos, pruebas y respuesta real. Esa spec hija
es la que puede fijar campos, esquemas Zod, errores y escenarios exactos. Si código y documentación
discrepan, manda el código.

La primera spec hija ya existe:

- `docs/superpowers/specs/2026-08-30-s05-programa-general-react-design.md` — Programa General.

Esta regla evita convertir el inventario global en 210 contratos imaginados. La spec maestra sí
declara **qué debe auditarse y qué resultado debe producir cada pieza**; la spec hija declara la
forma exacta de sus datos.

### 2.1 Atlas documental aprobado el 2026-08-30

Felipe aprobó una spec por cada una de las 26 superficies renderizadas y una spec de transición
para `/dashboard`: 27 IDs S. Los parciales realmente compartidos tienen tres contratos T. Cada
vista/partial de `views/` tiene un único propietario en §12.

| ID | Spec | Estado |
|---|---|---|
| T01 | `2026-08-30-t01-shell-runtime-react-design.md` | Spec autorrevisada; plan 2026-08-30-t01-shell-runtime-react.md escrito; sin decisiones pendientes |
| T02 | `2026-08-30-t02-contexto-lps-react-design.md` | Spec autorrevisada; plan `2026-08-30-t02-contexto-lps-react.md` escrito; sin decisiones pendientes |
| T03 | `2026-08-30-t03-marco-bi-react-design.md` | Spec autorrevisada; plan `2026-08-30-t03-marco-bi-react.md` escrito; sin decisiones pendientes |
| S01 | `2026-08-30-s01-login-react-design.md` | Spec y plan `2026-08-30-s01-login-react.md` aprobados |
| S02 | `2026-08-30-s02-recuperar-clave-react-design.md` | Spec autorrevisada; plan `2026-08-30-s02-recuperar-clave-react.md` escrito; sin decisiones pendientes |
| S03 | `2026-08-30-s03-restablecer-clave-react-design.md` | Spec autorrevisada; plan `2026-08-30-s03-restablecer-clave-react.md` escrito; sin decisiones pendientes |
| S04 | `2026-08-30-s04-selector-proyectos-react-design.md` | Spec autorrevisada; plan `2026-08-30-s04-selector-proyectos-react.md` escrito; sin decisiones pendientes |
| S05 | `2026-08-30-s05-programa-general-react-design.md` | Spec autorrevisada; plan `2026-08-30-s05-programa-general-react.md` escrito; sin decisiones pendientes |
| S06 | `2026-08-30-s06-actualizar-cronograma-react-design.md` | Spec autorrevisada; plan `2026-08-30-s06-actualizar-cronograma-react.md` escrito; sin decisiones pendientes |
| S07 | `2026-08-30-s07-programacion-intermedia-react-design.md` | Spec autorrevisada; plan `2026-08-30-s07-programacion-intermedia-react.md` escrito; sin decisiones pendientes |
| S08 | `2026-08-30-s08-programacion-semanal-react-design.md` | Spec autorrevisada; plan `2026-08-30-s08-programacion-semanal-react.md` escrito; sin decisiones pendientes |
| S09 | `2026-08-30-s09-cnp-react-design.md` | Spec autorrevisada; plan `2026-08-30-s09-cnp-react.md` escrito; sin decisiones pendientes |
| S10 | `2026-08-30-s10-cnc-react-design.md` | Spec autorrevisada; plan `2026-08-30-s10-cnc-react.md` escrito; sin decisiones pendientes |
| S11 | `2026-08-30-s11-cic-react-design.md` | Spec autorrevisada; plan `2026-08-30-s11-cic-react.md` escrito; sin decisiones pendientes |
| S12 | `2026-08-30-s12-plan-compras-react-design.md` | Spec autorrevisada; plan `2026-08-30-s12-plan-compras-react.md` escrito; sin decisiones pendientes |
| S13 | `2026-08-30-s13-profesionales-react-design.md` | Spec autorrevisada; plan `2026-08-30-s13-profesionales-react.md` escrito; sin decisiones pendientes |
| S14 | `2026-08-30-s14-subcontratistas-react-design.md` | Spec autorrevisada; plan `2026-08-30-s14-subcontratistas-react.md` escrito; sin decisiones pendientes |
| S15 | `2026-08-30-s15-control-cambios-react-design.md` | Spec autorrevisada; plan `2026-08-30-s15-control-cambios-react.md` escrito; sin decisiones pendientes |
| S16 | `2026-08-30-s16-indicadores-react-design.md` | Spec autorrevisada; plan `2026-08-30-s16-indicadores-react.md` escrito; sin decisiones pendientes |
| S17 | `2026-08-30-s17-bi-control-tower-react-design.md` | Spec autorrevisada; plan `2026-08-30-s17-bi-control-tower-react.md` escrito; sin decisiones pendientes |
| S18 | `2026-08-30-s18-bi-programa-general-react-design.md` | Spec autorrevisada; plan `2026-08-30-s18-bi-programa-general-react.md` escrito; sin decisiones pendientes |
| S19 | `2026-08-30-s19-bi-curva-s-react-design.md` | Spec autorrevisada; plan `2026-08-30-s19-bi-curva-s-react.md` escrito; sin decisiones pendientes |
| S20 | `2026-08-30-s20-bi-intermedia-react-design.md` | Spec autorrevisada; plan `2026-08-30-s20-bi-intermedia-react.md` escrito; sin decisiones pendientes |
| S21 | `2026-08-30-s21-bi-semanal-react-design.md` | Spec autorrevisada; plan `2026-08-30-s21-bi-semanal-react.md` escrito; sin decisiones pendientes |
| S22 | `2026-08-30-s22-bi-pdc-react-design.md` | Spec autorrevisada; plan `2026-08-30-s22-bi-pdc-react.md` escrito; sin decisiones pendientes |
| S23 | `2026-08-30-s23-bi-contratistas-react-design.md` | Spec autorrevisada; plan `2026-08-30-s23-bi-contratistas-react.md` escrito; sin decisiones pendientes |
| S24 | `2026-08-30-s24-bi-responsables-react-design.md` | Spec autorrevisada; plan `2026-08-30-s24-bi-responsables-react.md` escrito; sin decisiones pendientes |
| S25 | `2026-08-30-s25-escalamientos-react-design.md` | Spec autorrevisada; plan `2026-08-30-s25-escalamientos-react.md` escrito; sin decisiones pendientes |
| S26 | `2026-08-30-s26-design-system-react-design.md` | Spec autorrevisada; plan `2026-08-30-s26-design-system-react.md` escrito; sin decisiones pendientes |
| S27 | `2026-08-30-s27-dashboard-landing-redirect-design.md` | Spec autorrevisada; plan `2026-08-30-s27-dashboard-landing-redirect.md` escrito; sin decisiones pendientes |

El ciclo vigente es individual y corrido: auditar S → escribir y auto-revisar su spec → invocar
`superpowers:writing-plans` para esa S → auto-revisar el plan → continuar con la siguiente. Felipe
delegó las decisiones técnicas y pidió acumular hasta el cierre únicamente decisiones reales de
lógica de negocio, producto, estrategia o PM. Los planes consumen T01–T03 por incrementos verticales;
no crean tres proyectos horizontales que bloqueen valor. Este ciclo documental no autoriza
implementación.

## 3. Alcance

### 3.1 Incluido

- Las rutas de la aplicación principal registradas en `public/index.php`.
- Las superficies servidas hoy desde `views/`.
- El shell React en `frontend/` y su evolución hasta ser la SPA única.
- La absorción de las islas React `pdc-app/` y `ct-app/`.
- Login, recuperación de contraseña, selector de proyecto, cierre de sesión y cambio de proyecto.
- Programa General, Actualizar Cronograma, Programación Intermedia, Programación Semanal, CNP, CNC
  y CIC.
- Profesionales, Subcontratistas, Control de Cambios e Indicadores.
- Plan de Compras v2 y sus ocho pantallas internas.
- Las ocho hojas de Torre de Control BI.
- Escalamientos, crisis, comentarios, avisos y notificaciones.
- Reportes, CSV, XLSX, PDF y otras respuestas descargables invocadas desde pantallas migradas.
- Selección, creación y eliminación de semanas, sesión activa y contexto compartido.
- Drawer unificado, modales compartidos, head de marca, sidebar y estados de error.
- Laboratorio interno del design system.
- Retiro de rutas procedurales y assets legacy cuando pierdan su último consumidor.
- La pantalla de mantenimiento como salida estática generada desde una fuente React, sin volverla
  dependiente de APIs durante una caída.

### 3.2 Excluido

- **Todo `/admin/`**, incluidos `admin/public/index.php`, `admin/src/`, `admin/views/`, AdminLTE y
  sus contratos. No se migra, no se planifica y no se usa como criterio de cierre de este programa.
- Listado de Actividades, Contratos y PDC v1 (`/pdc`, `/api/pdc/*`): fueron eliminados el
  2026-08-04. No son deuda de migración y no deben reaparecer.
- Cambios de RLS, tablas, migraciones, grants, usuarios, credenciales o datos.
- Reescritura de reglas de negocio PHP salvo el mínimo necesario para exponer contratos JSON
  seguros y equivalentes.
- Deploy a producción, que requiere autorización independiente.

## 4. Punto de partida medido

La medición se hizo en la rama `shell-minimo-react`, `HEAD 8012ed0e`, usando el código como fuente.

| Pieza | Estado actual |
|---|---|
| Rutas de la aplicación principal | 210 declaraciones en `public/index.php` |
| Superficies HTML canónicas con ruta propia | 26; `/dashboard` no es pantalla, redirige al landing del rol |
| Vistas y parciales PHP principales | 42 archivos bajo `views/` |
| Presentación operacional adicional | `public/mantenimiento-aia.html` |
| SPA principal | `frontend/`, servida en `/app/` |
| React ya presente en el shell | sesión, login, selector de proyecto, sidebar básico y tema |
| Estado funcional del shell reportado al iniciar esta sesión | 30 pruebas frontend y typecheck en verde |
| Plan de Compras | isla React separada `pdc-app/`, ocho rutas hash y 70 rutas HTTP del dominio |
| Torre de Control | siete hojas legacy y un piloto React separado `ct-app/` para Intermedia |
| Programa General en React | no existe todavía; el shell enlaza a la pantalla PHP |

### 4.1 Correcciones al censo versionado

`docs/design-system/auditoria/censo-modulos.json` registra 201 rutas principales y 27 superficies.
El código actual registra 210 rutas. Las nueve adicionales corresponden principalmente a los cinco
endpoints del shell (`/api/session`, auth y proyectos) y a ampliaciones de BI. Además, el censo cuenta
`/dashboard` como superficie, pero `DashboardController::index()` solo resuelve el landing y
redirige. Esta spec usa 210 rutas y 26 superficies renderizadas.

El censo debe corregirse en un frente documental posterior; no se modifica como efecto lateral de
esta spec.

### 4.2 Qué existe realmente en cada frontend React

#### `frontend/`

- Consulta `/api/session` con Zod.
- Muestra login cuando no hay sesión.
- Muestra selector cuando hay sesión sin proyecto.
- Muestra sidebar y un placeholder con el nombre del proyecto cuando el contexto está listo.
- Usa `frontend/src/lib/api/cliente.ts`, pero ese cliente todavía cubre principalmente JSON.
- Conserva una tabla manual de elementos ocultos por rol en `NavegacionLateral.tsx`; debe desaparecer
  a favor de capacidades y acciones resueltas por el servidor.
- No tiene todavía rutas de módulos, outlet, estados de descarga, uploads ni manejo uniforme de
  errores de dominio.

#### `pdc-app/`

- Ya es React + TypeScript y contiene funcionalidad de negocio real.
- Usa `HashRouter`, un cliente HTTP propio y tipos TypeScript sin validación Zod de entrada.
- Debe **absorberse**, no reescribirse desde cero.

#### `ct-app/`

- Ya es React + TypeScript, pero solo monta la hoja BI de Programación Intermedia.
- No tiene router porque el controlador decide cuándo servir el piloto.
- Debe convertirse en el primer módulo absorbido de BI y luego extenderse a las otras siete hojas.

## 5. Decisiones de arquitectura

### M1 — Una SPA principal

`frontend/` es el único frontend de destino. `pdc-app/` y `ct-app/` son fuentes transitorias que se
mueven a `frontend/src/modules/`; no sobreviven como builds, routers o clientes HTTP paralelos al
cierre del programa.

### M2 — Strangler por ruta, con URL canónica estable

Cada módulo nace primero bajo `/app/<ruta-del-modulo>` para validación aislada. Tras demostrar
paridad, su prefijo canónico se agrega a `SpaRouter::RUTAS_MIGRADAS` y la URL histórica pasa a servir
la SPA. El rollback consiste en retirar ese prefijo; no requiere revertir datos.

Durante la convivencia:

- los enlaces a rutas React usan el router de la SPA;
- los enlaces a legacy realizan navegación completa;
- el usuario conserva la misma cookie de sesión y el mismo proyecto;
- los query params funcionales se preservan;
- nunca existen dos superficies escribiendo en segundo plano a la vez.

### M3 — PHP conserva negocio, seguridad y alcance

React no replica autorización ni reglas de dominio. El servidor sigue siendo dueño de:

- sesión, timeout y cierre de sesión;
- proyecto activo y aislamiento por `project_id`;
- normalización de roles y permisos;
- CSRF;
- validaciones de negocio y transiciones permitidas;
- generación de reportes y archivos;
- cálculos, persistencia y auditoría.

El frontend puede repetir validaciones para dar feedback temprano, pero la respuesta del servidor
es definitiva.

### M4 — El servidor entrega capacidades y acciones; React no interpreta roles

Se elimina toda matriz nueva equivalente a `si rol === X`. Cada contexto de módulo devuelve las
acciones disponibles para ese usuario, proyecto, semana, fila o estado. Las capacidades globales de
`/api/session` sirven para navegación gruesa; las acciones de dominio sirven para botones y edición.

Una acción ausente no se muestra. Una acción presente pero temporalmente inválida se muestra
deshabilitada con razón accesible. El backend vuelve a autorizar cada mutación.

### M5 — Una frontera HTTP, Zod y pruebas PHP

Ningún componente llama `fetch` directamente. Todo tráfico pasa por
`frontend/src/lib/api/cliente.ts` o por adaptadores del mismo directorio que deleguen en él.

El cliente común debe crecer para soportar, sin clientes paralelos:

- JSON GET/POST y formularios;
- `FormData` y progreso de importación cuando aplique;
- `Blob`, CSV, XLSX y PDF;
- cancelación de requests y descarte de respuestas obsoletas;
- errores 400, 401, 403, 404, 409, 422 y 5xx con forma normalizada;
- renovación del estado de sesión tras 401;
- CSRF por ámbito.

Todo endpoint nuevo tiene esquema Zod y prueba de contrato PHP. Todo endpoint existente que React
consuma obtiene esquema Zod antes del corte del módulo. Los tipos se infieren de los esquemas, no se
duplican a mano.

### M6 — El navegador no envía nombres de base ni alcance confiable

El React nuevo no envía `db`, `Base_de_Datos`, prefijos de tablas ni un `project_id` arbitrario como
autoridad. El servidor deriva el proyecto desde la sesión y valida cualquier selector multiobra
contra membresías autorizadas. Cuando un endpoint legacy exige `db`, su spec hija debe introducir una
frontera segura o adaptar el endpoint antes de consumirlo desde React.

### M7 — Componentes y vendors según el trabajo, no una grilla universal

- Tablas semánticas y tarjetas React son la primera opción para lectura, edición responsive y
  conjuntos moderados.
- AG Grid Community se conserva donde la densidad, virtualización o edición tabular real lo
  justifique, en especial durante la absorción de PDC.
- AG Grid Enterprise solo se evalúa con una necesidad demostrada y aprobación de licencia.
- Handsontable, DataTables, jQuery UI, Select2, Tom Select, Bootstrap JS y scripts CDN se retiran al
  perder su último consumidor React.
- No se envuelve Handsontable dentro de React.

Esta decisión reemplaza la regla anterior de “AG Grid para todo” y es coherente con la spec hija de
Programa General, que exige tabla semántica en desktop/tablet y tarjetas editables en móvil.

### M8 — Design system compartido

Todo React consume `public/css/tokens.css` y los contratos canónicos de `DESIGN.md` y
`docs/design-system/`. El CSS de módulo solo puede resolver composición, geometría y responsive; no
crea paletas, tipografías, estados ni skins locales.

Los componentes compartidos se prueban en el laboratorio React antes o junto con su primer
consumidor. Dark sigue siendo la referencia canónica del repo; claro debe mantener paridad porque el
shell ya permite alternarlo.

### M9 — Responsive de producto

Las superficies de producción se validan en:

- desktop canónico: `1180×820`;
- desktop amplio: `1440×900`;
- tablet: ancho entre 768 y 1179 px;
- móvil: `390×844`.

No se permite overflow horizontal de página. Una tabla densa puede tener scroll interno declarado y
accesible. Cuando una tabla no sea operable en móvil, debe tener una vista de tarjetas o formulario
equivalente; no basta con reducir tipografía.

El laboratorio interno conserva como mínimo sus dos viewports desktop contractuales; sus fixtures de
producción también deben demostrar la variante móvil cuando el componente la ofrece.

### M10 — Specs hijas y planes pequeños

Esta spec no se ejecuta como un solo plan. Cada entrega vertical tiene su spec hija, luego un plan
`writing-plans`, luego implementación y verificación. El frente siguiente no empieza hasta que el
anterior cumpla el gate de cierre vigente del repositorio.

### M11 — `/admin/` permanece fuera

La decisión anterior de “migrarlo al final y reevaluar” queda reemplazada: `/admin/` no forma parte
de este programa. La SPA principal no importa código, estilos, rutas ni permisos de esa
miniaplicación.

### M12 — Sin cambios de datos

La migración de frontend no autoriza RLS, DDL, DML, grants, backfills ni manipulación de usuarios o
credenciales. Las pruebas con persistencia usan fixtures autorizadas, restauran el estado y no
convierten una necesidad de UI en una migración de base de datos.

## 6. Arquitectura destino

```text
frontend/
  src/
    app/
      router.tsx
      providers.tsx
    shell/
      sesion/
      navegacion/
      proyecto/
      semana/
      notificaciones/
    design-system/
      components/
      patterns/
      laboratory/
    lib/
      api/
        cliente.ts
        errores.ts
        descargas.ts
        esquemas/
      auth/
      fechas/
      permisos/
    shared/
      drawer-lps/
      estados-pantalla/
      tablas/
    modules/
      programa-general/
      actualizar-cronograma/
      programacion-intermedia/
      programacion-semanal/
      cnp/
      cnc/
      cic/
      profesionales/
      subcontratistas/
      control-cambios/
      indicadores/
      plan-compras/
      bi/
      escalamientos/
  publica un solo bundle en public/app/

src/
  Controllers/Api/     # contratos HTTP, auth, CSRF y respuestas
  Services/            # negocio existente
  Security/            # RBAC y alcance existentes
```

Los nombres finales pueden ajustarse en un plan si preservan estas fronteras. No se crea un
monorepo ni una librería compartida prematura para mantener vivas las tres SPAs.

## 7. Qué significa que una pieza quedó migrada

Una pieza solo puede marcarse cerrada cuando se cumplen todos estos puntos:

1. Su URL canónica sirve React y conserva query params, deep links y navegación hacia atrás.
2. Todas las funciones observables inventariadas tienen escenario de paridad.
3. Todos los endpoints consumidos pasan por el cliente común y tienen esquema Zod.
4. Todo endpoint nuevo o adaptado tiene prueba PHP de contrato, auth, proyecto y CSRF según aplique.
5. El servidor entrega acciones; React no decide permisos con roles hardcodeados.
6. Existen carga, vacío, sin resultados, error recuperable, 401, 403 y éxito cuando correspondan.
7. Desktop, tablet y móvil conservan lectura y operación completa.
8. Teclado, foco, nombres accesibles, anuncios de guardado y modales/drawers cumplen el contrato.
9. Las mutaciones se verifican con escribir → recargar → recuperar; el dato de prueba se restaura.
10. Consola y red no muestran errores ni requests duplicados o inesperados.
11. Hay rollback por ruta y se prueba al menos una vez antes del corte canónico.
12. La vista PHP, JS inline y assets exclusivos se eliminan o quedan documentados como aún
    compartidos; no se conserva código muerto “por si acaso”.

## 8. Catálogo exhaustivo de superficies navegables

Las filas S01–S26 son las superficies HTML canónicas que hoy renderizan contenido. S27 representa
la transición `/dashboard`, contada como superficie por el censo histórico aunque no renderiza HTML.

| ID | Dominio | Ruta actual | Presentación actual | Trabajo necesario para migrarla |
|---|---|---|---|---|
| S01 | Acceso | `/login` y alias `/` | `views/auth/login.view.php` | Consolidar el login ya existente en React; preservar errores, actualización forzada de clave, cancelación, CSRF, retorno y variante de mantenimiento. Cortar `/login` y `/` solo después de probar sesión válida, inválida, vencida y mantenimiento. |
| S02 | Recuperación | `/password/forgot` | `views/auth/password-forgot.view.php` | Crear formulario React con email, CSRF, estados de envío y respuesta no enumerativa; añadir contrato JSON equivalente sin cambiar correo ni tokens. |
| S03 | Recuperación | `/password/reset` | `views/auth/password-reset.view.php` | Preservar token en URL, expiración, enlace inválido, validación/confirmación de contraseña, éxito y retorno a login. |
| S04 | Proyectos | `/proyectos` | `views/core/project_selector.view.php` | Completar el selector React: búsqueda, vacío, tarjetas, proyecto activo, permisos por membresía, destino contextual, cambio de proyecto y BI cuando sea visible. |
| S05 | Programa General | `/programa-general` | `views/programa-general/programa_general.view.php` | Ejecutar la spec hija: lectura, semana, filtros/conteos, tabla y tarjetas, edición, batch, CSV, corte XLSX, leyenda, alertas y drawer. |
| S06 | Cronograma | `/programa-general-actualizar` | `views/programa-general-actualizar/programaGeneralActualizar.view.php` | Migrar importación XLSX, fecha inicial, vista de mapeo, programa completo, autoasociación, revisión pendiente/procesada, validaciones, guardado, eliminación de actualización, bloqueos y retorno a PG. |
| S07 | Programación Intermedia | `/programacion-intermedia` | `views/programacion-intermedia/programacion_intermedia.view.php` | Migrar look-ahead de seis semanas, estados de siete restricciones, responsables, subcontratistas, filtros, view-all, agrupación por gravedad, batch compartido con preview, CSV/XLSX, leyenda, recarga y drawer. |
| S08 | Programación Semanal | `/programacion-semanal` | `views/programacion-semanal/programacion_semanal.view.php` | Migrar compromisos, cierre/reapertura, autoprogramación y log, actividad manual, TNP, cantidades y ejecución real, CNC al incumplir, alertas de cambios, tabla/tarjetas, reportes, CSV, leyenda y drawer. |
| S09 | CNP | `/programacion-semanal/cnp` | `views/programacion-semanal/CNP.view.php` | Migrar lista y tarjetas, filtros/semana, categorías y causas, profesional/empresa, observaciones, guardar y reprogramar; sustituir navegación procedural por rutas React. |
| S10 | CNC | `/programacion-semanal/cnc` | `views/programacion-semanal/CNC.view.php` | Migrar causas de no cumplimiento, categorías dependientes, observación obligatoria para Otra/Otros, edición, guardado, leyenda y navegación interna. |
| S11 | CIC | `/programacion-semanal/cic` | `views/programacion-semanal/CIC.view.php` | Migrar selección de subcontratista, formularios por disciplina, opciones `NR/0/50/100/N/A`, validación, cálculo/guardado, permisos por disciplina, historial/listado, tutorial y navegación interna. |
| S12 | Plan de Compras | `/plan-compras` | `views/plan-compras/app.view.php` + `pdc-app/` | Absorber las ocho pantallas existentes en la SPA principal, cambiar hash por rutas anidadas, unificar cliente/Zod/shell/tema y preservar todos los deep links hash mediante redirects. |
| S13 | Profesionales | `/profesionales` | `views/profesionales/profesionales.view.php` | Migrar grilla editable, alta por fila, normalización, validación de nombre/cargo/email, autosave, bloqueo de borrado con dependencias, eliminación, recarga y CSV. |
| S14 | Subcontratistas | `/subcontratistas` | `views/subcontratistas/subcontratistas.view.php` | Migrar catálogo editable, normalización y unicidad de NIT, email/contacto, autosave, restricciones de borrado por uso, eliminación, recarga y CSV. |
| S15 | Control de Cambios | `/control-cambios` | `views/control-cambios/controlCambios.view.php` | Migrar tabla de órdenes, filtros por columna, alta/edición/eliminación, solicitante, prioridad, tipos, responsable, impactos, importes, fechas, aprobación, soportes, validaciones y generación PDF. |
| S16 | Indicadores | `/indicadores` | `views/indicadores/indicadores.view.php` | Migrar el marco responsive de Power BI, estados de carga/error del iframe, título, permisos y mensajes; mantener el informe externo como fuente, sin reimplementar sus KPIs. |
| S17 | BI | `/bi/control-tower` | layout y vista BI legacy | Migrar resumen ejecutivo, scorecard, drivers, riesgos, acciones recomendadas, filtros, semana/proyectos, drilldowns y linaje. |
| S18 | BI | `/bi/programa-general` | layout y vista BI legacy | Migrar cumplimiento, avance, atraso, radar y detalles CNC/CNP con paginación/filtros. |
| S19 | BI | `/bi/curva-s` | layout y vista BI legacy | Migrar curva plan/real, hitos, tendencias, filtros y lectura accesible equivalente del gráfico. |
| S20 | BI | `/bi/intermedia` | legacy o `ct-app/` según `CT_PILOTO` | Absorber el piloto React: titular, alarma de huérfanas, semáforo, Pareto, restricciones, gestión y linaje; retirar la bandera solo al completar paridad. |
| S21 | BI | `/bi/semanal` | layout y vista BI legacy | Migrar PAC/PPC, compromisos, causas, tendencias, filtros y drilldowns. |
| S22 | BI | `/bi/pdc` | layout y vista BI legacy | Migrar KPIs de compras, detalle, vencimientos y vínculo navegable a Plan de Compras. |
| S23 | BI | `/bi/contratistas` | layout y vista BI legacy | Migrar hoja CIC/proveedores, comparaciones, filtros y detalle. |
| S24 | BI | `/bi/responsables` | layout y vista BI legacy | Migrar hoja CIP/responsables y preservar el alcance “los míos” por defecto para Residente y la opción de ver obra cuando esté autorizada. |
| S25 | Escalamientos | `/dashboard/escalamientos` | `views/dashboard/escalamientos.php` | Migrar columnas/tarjetas corporativas, estados, apertura del drawer, comentarios, crisis y navegación de regreso. |
| S26 | Design system | `/internal/design-system` | `views/design-system/*` | Migrar navegación por familias, candidatos aprobados, grupos UI, semántica de estados, fixtures operacionales y adapters aún vigentes; preservar gate de ambiente y capacidad. |
| S27 | Landing | `/dashboard` | Redirect server-side | Preservar resolución por rol/proyecto/semana y redirigir sin flash ni UI inventada; cubrirlo con contrato de transición propio. |

### 8.1 Aliases, redirects y superficies de sistema

| Pieza | Comportamiento que debe preservarse |
|---|---|
| `/dashboard` | Cubierta por S27: no se convierte en dashboard React; sigue resolviendo el landing autorizado y redirige sin flash. |
| `/_aia/operacion/7f3c9b` | Entrada oculta de mantenimiento; debe reutilizar la UI React de login sin revelar ni debilitar el candado, y seguir publicando el formulario al endpoint oculto. |
| `/dev/entrar` | Sigue siendo redirect de desarrollo, sin pantalla React ni presencia en producción. |
| `/login/cancelar` | Conserva cancelación de cambio forzado y retorno a login. |
| `/logout` | React usa `POST /api/auth/logout`. El GET histórico permanece solo para shells PHP durante convivencia y se desregistra al retirar su último consumidor. |
| `views/errors/error.view.php` | Se divide: errores de navegación pasan a `ErrorBoundary`/pantallas 403-404-500 React; errores `/api/*` continúan como JSON PHP. |
| `public/mantenimiento-aia.html` | Se mantiene como HTML autocontenido de emergencia, pero su fuente visual pasa a un componente React renderizado en build. No ejecuta JS ni llama APIs durante mantenimiento. |
| `/app` | Ruta transitoria del shell y de pilotos. Al final puede redirigir al landing canónico; no es un módulo adicional. |

## 9. Pantallas internas de Plan de Compras v2

Las siguientes ocho pantallas ya existen en `pdc-app/`. La migración es absorción con paridad, no
rediseño funcional desde cero.

| # | Hash actual | Ruta React destino | Trabajo de absorción y paridad |
|---:|---|---|---|
| PDC-1 | `#/ensamble/importar` | `/plan-compras/ensamble/importar` | Portar carga XLSX, preview, confirmación, versiones, activación, impacto de reimportación, comparaciones y mensajes. |
| PDC-2 | `#/ensamble/maestro` | `/plan-compras/ensamble/maestro` | Conservar tabs Pendientes, Catálogo global, Clasificar equipos e Importar SINCO; vínculos, sugerencias, alta manual, desactivar/reactivar y clasificación. |
| PDC-3 | `#/ensamble/presupuesto` | `/plan-compras/ensamble/presupuesto` | Conservar árbol y modo tabla, versión, niveles, tipos, unidades, búsqueda, filtros, totales y advertencias de valoración. |
| PDC-4 | `#/ensamble/comparar` | `/plan-compras/ensamble/comparar` | Conservar selección de dos versiones, nivel, estados de diferencia, totales y tabla comparativa. |
| PDC-5 | `#/ensamble/paquetes` | `/plan-compras/ensamble/paquetes` | Conservar tabs Insumos distintos, Asistente y Paquetes; sugerencias, plan automático, asignar, omitir, desasignar, modalidades y subpaquetes. |
| PDC-6 | `#/ensamble/plan` | `/plan-compras/ensamble/plan` | Conservar tabs Plan, Sin frente, Pendientes de calcular y Desfases; amarres, anclas, fechas, responsables, cálculo y reprogramación preview/apply. |
| PDC-7 | `#/ensamble/plan/pasos` | `/plan-compras/ensamble/plan/pasos` | Conservar agregar, quitar, reordenar, alias, días fijos, duraciones, reset, copia desde otra obra, preview, historial y orígenes. |
| PDC-8 | `#/seguimiento/avance` | `/plan-compras/seguimiento/avance` | Conservar tabs Paquetes, Vencimientos y Flujo de caja; edición de pasos, filtros, detalle, lotes y descarga CSV. |

El alias hash `#/maestro` debe redirigir al destino de Maestro. Los hashes existentes deben seguir
abriendo la pantalla equivalente durante al menos todo el periodo de convivencia.

## 10. Programa por dominio: rutas, permisos y trabajo pendiente

Los conteos suman las 210 rutas actuales. No significan 210 endpoints nuevos: indican la superficie
que cada spec hija debe reconciliar antes de declarar paridad.

| ID | Dominio y rutas actuales | Autoridad de acceso actual | Trabajo vertical requerido |
|---|---|---|---|
| D01 | Autenticación — 13 rutas UI/transición + 3 API (`/api/session`, `/api/auth/*`) | Público antes de entrar; sesión después; ruta oculta de mantenimiento con control propio | Completar login, actualización forzada, forgot/reset, logout, expiración y mantenimiento. Unificar respuestas JSON sin enumerar cuentas. |
| D02 | Selector — 2 rutas UI + 2 API de proyectos | Sesión válida y membresía activa del proyecto | Completar búsqueda/estados/destino, cambiar proyecto, limpiar contexto y actualizar capacidades del shell tras seleccionar. |
| D03 | Programa General — 15 rutas `/programa-general`, `/api/general`, `/api/pg` | `lps.programa_general.ver/editar`, `lps.programa_general_actualizar.editar`; `canManageGeneralProgram`, `canEditPastGeneralProgram` | Ejecutar las cinco entregas de su spec hija y cortar la primera ruta de negocio. |
| D04 | Actualizar Cronograma — 1 superficie; reutiliza APIs de PG | `canManageGeneralProgram` y permisos de actualización del endpoint | Extraer bootstrap/contexto, importar XLSX, mapear, autoasociar, confirmar/eliminar y demostrar trazabilidad y bloqueos. |
| D05 | Programación Intermedia — 8 rutas `/programacion-intermedia`, `/api/pi` | `lps.programacion_intermedia.ver/editar`; `canManageMediumTermProgram`, `canEditConstraints` | Especificar filas/estados exactos, migrar look-ahead y batch compartido, integrar drawer y eliminar filtros de sesión legacy. |
| D06 | Programación Semanal — 9 rutas `/programacion-semanal`, `/api/semanal` | `lps.programacion_semanal.ver/editar`, `SemanalReabrirPolicy`; `canManageWeeklyProgram`, `canManageWeeks` | Migrar el plan semanal completo, su máquina de estados, semanas abiertas/cerradas, TNP, autoprogramación, cambios y reportes. |
| D07 | CNP — 4 rutas `/programacion-semanal/cnp`, `/api/cnp` | `lps.cnp.ver/editar`; capacidad semanal | Migrar lista, causas, reprogramación y edición responsive; compartir contexto y navegación con PS. |
| D08 | CNC — 4 rutas `/programacion-semanal/cnc`, `/api/cnc` | `lps.cnc.ver/editar`; capacidad semanal | Migrar catálogo dependiente de causas, edición/justificación y vista responsive. |
| D09 | CIC — 3 rutas `/programacion-semanal/cic`, `/api/cic` | `lps.cic.ver/editar`, capacidad semanal y disciplinas permitidas por rol | Migrar formularios de evaluación, listado y cálculo sin perder permisos parciales por disciplina. |
| D10 | Plan de Compras — 70 rutas `/plan-compras` | `lps.pdc.ver/importar/maestro`, `lps.paquetes_contratacion.ver/editar/reglas`, CSRF `plan_compras_v2` | Absorber ocho pantallas, reemplazar cliente/tipos propios con cliente común + Zod y eliminar build/hash shell separado. |
| D11 | Profesionales — 4 rutas `/profesionales`, `/api/profesionales` | `lps.profesionales.ver/editar` | Migrar CRUD tabular, validaciones, dependencias, exportación y permisos por acción. |
| D12 | Subcontratistas — 4 rutas `/subcontratistas`, `/api/subcontratistas` | `lps.subcontratistas.ver/editar`; navegación relacionada con `canManagePdC` | Migrar CRUD tabular, NIT, dependencias LPS/PDC, exportación y permisos por acción. |
| D13 | Control de Cambios — 3 rutas `/control-cambios`, `/api/control-cambios` | `lps.control_cambios.ver/editar` | Dividir tabla, filtros y formulario largo en componentes; conservar cálculos, soportes, PDF y todas las validaciones. |
| D14 | Indicadores — 2 rutas `/indicadores`, `/api/indicadores/generar` | UI niega G/S/SG/C; API exige `lps.indicadores.ver` | Unificar la decisión del servidor en un manifiesto de navegación/acción, migrar iframe responsive y conservar generación autorizada. |
| D15 | Torre BI — 31 rutas `/bi`, `/api/bi` | `internal.bi.preview`, flag global para D/R, alcance BI por proyecto; escritura de restricciones con `canEditConstraints` | Absorber `ct-app`, crear router de ocho hojas, Zod para cada reporte/detail, multiobra autorizada, filtros, gráficos accesibles y linaje. |
| D16 | Reportes — 2 rutas parametrizadas `/reportes/{tipo}` | `lps.reportes.generar` | Mantener descargas `corte-programacion`, `restricciones`, `compromisos`, `consolidado-odc`; mantener triggers `curva-s`, `general`, `restricciones-general`, `pdc`, `subcontratistas`, `run-all`; consumirlos por cliente común. |
| D17 | Escalamientos y avisos — 10 rutas `/dashboard`, `/api/lps`, `/api/notifications` | Lectura/edición semanal para comentarios/crisis; notificaciones ligadas al usuario | Convertir drawer y dashboard, menciones, hilo, alta/cierre de crisis con justificación mínima, no leídas y marcar leída. |
| D18 | Núcleo y runtime — 12 rutas `/context`, `/session`, `/runtime` | Sesión, proyecto, permisos de semana y rutas públicas de assets | Completar selector de semana, crear/eliminar semana, touch/timeout, configuración runtime y retirar entrypoints CSS de vendors cuando no tengan consumidores. |
| D19 | Carril procedural — 7 rutas `/legacy/*` | Permisos del flujo consumidor; crear/eliminar semana tienen permisos y CSRF propios | Sustituir navegación, contexto y semana por APIs modernas; comprobar si `buscadorTabla.php` está muerto; retirar cada ruta solo tras búsqueda de consumidores y prueba de flujo. |
| D20 | Laboratorio — 1 ruta `/internal/design-system` | Ambiente development/testing y `internal.design-system.view` | Portar las diez familias, índice UI y fixtures; leer las mismas fuentes JSON; mantener goldens y eliminar dependencias de vendors retirados. |

## 11. Rutas auxiliares y su destino

### 11.1 Carril legacy

| Ruta | Consumidor actual | Destino de migración |
|---|---|---|
| `/legacy/cambiar_pagina.php` GET/POST | PS, CNP, CNC, CIC y utilidades comunes | React Router + selector de semana/contexto; después redirect temporal y retiro. |
| `/legacy/funciones_generales/php/datosGeneralesPagina.php` | `cargarDatosGeneralesPagina2.js`, shell legacy y pruebas | Endpoints de contexto por sesión/proyecto/semana con Zod; retirar el bootstrap global opaco. |
| `/legacy/funciones_generales/php/nueva_semana.php` | flyout “Semanas del Proyecto” y flujos E2E | API moderna de creación con `lps.semana.crear`, CSRF, validaciones y contrato; reutilizar servicio existente, no copiar lógica a React. |
| `/legacy/funciones_generales/php/eliminar_semana.php` | flyout “Semanas del Proyecto” y Control de Cambios | API moderna de eliminación con `lps.semana.eliminar`, preview/confirmación si el contrato actual lo exige y respuesta tipada. |
| `/legacy/funciones_generales/php/verificarCICActualizada.php` | administración de semanas | Incorporar el estado requerido en el contexto/preview de semana; no hacer un POST oculto desde un componente. |
| `/legacy/funciones_generales/php/buscadorTabla.php` | No se encontró consumidor productivo directo en la pasada inicial | Repetir búsqueda en la spec del último módulo tabular; si sigue huérfana, borrar con prueba de ausencia. No crear reemplazo sin consumidor. |

La séptima declaración es el segundo verbo de `cambiar_pagina.php`; hay seis scripts físicos.

### 11.2 Contexto y sesión

- `/context/week` y `/context/clear-week` siguen siendo la única escritura del contexto de semana
  hasta que una spec hija demuestre que necesitan una respuesta más rica.
- `/session/touch` se integra con un único controlador de timeout del shell. No habrá timers por
  módulo.
- `/runtime/frontend-config.js` conserva solo configuración pública necesaria; nunca expone secretos.
- Los entrypoints CSS de adapters legacy se retiran junto al último consumidor, no al migrar el
  primer módulo.

## 12. Inventario de las 42 vistas y parciales PHP

Cada archivo aparece exactamente una vez. “Retirar” significa después del corte y de la verificación,
no durante la construcción del piloto.

| VIEW ID | Archivo | Tipo | Destino y condición de retiro |
|---|---|---|---|
| VIEW-01 | `views/auth/login.view.php` | Pantalla | `shell/PantallaLogin`; retirar tras cortar `/`, `/login` y la variante de mantenimiento. |
| VIEW-02 | `views/auth/password-forgot.view.php` | Pantalla | `shell/auth/PantallaRecuperarClave`; retirar tras probar envío, CSRF y respuesta segura. |
| VIEW-03 | `views/auth/password-reset.view.php` | Pantalla | `shell/auth/PantallaNuevaClave`; retirar tras probar token válido, inválido y expirado. |
| VIEW-04 | `views/bi/_filters.php` | Parcial | Barra de filtros BI compartida, controlada por URL y esquemas de opciones. |
| VIEW-05 | `views/bi/_layout.php` | Layout | Layout de módulo dentro del shell React; retirar cuando las ocho hojas estén cortadas. |
| VIEW-06 | `views/bi/_nav.php` | Parcial | Navegación anidada de las ocho rutas BI con `aria-current`. |
| VIEW-07 | `views/bi/control-tower-piloto.php` | Host de isla | Desaparece al absorber `ct-app` y servir `/bi/intermedia` desde la SPA principal. |
| VIEW-08 | `views/bi/control-tower.php` | Vista compartida BI | Se descompone en componentes por hoja, KPI, gráfico, detalle y linaje; retirar al cortar la última hoja. |
| VIEW-09 | `views/bi/index.php` | Candidato a código muerto | No tiene referencia runtime encontrada; confirmar en la spec BI y borrar, no portar por inercia. |
| VIEW-10 | `views/control-cambios/controlCambios.view.php` | Pantalla | Módulo `control-cambios`; retirar después de tabla, formulario, soportes y PDF. |
| VIEW-11 | `views/core/project_selector.view.php` | Pantalla | `shell/SelectorProyecto`; retirar al cortar `/proyectos`. |
| VIEW-12 | `views/dashboard/escalamientos.php` | Pantalla | Módulo `escalamientos`; retirar con drawer y crisis verificados. |
| VIEW-13 | `views/design-system/families/actions.php` | Fixture | Familia React Actions con todos sus estados aprobados. |
| VIEW-14 | `views/design-system/families/bi-primitives.php` | Fixture | Primitivas BI React con alternativa textual accesible. |
| VIEW-15 | `views/design-system/families/data-display.php` | Fixture | Familia React de tablas, tarjetas, chips y visualización. |
| VIEW-16 | `views/design-system/families/forms-filters.php` | Fixture | Familia React de formularios, búsqueda y filtros. |
| VIEW-17 | `views/design-system/families/foundations.php` | Fixture | Visualizador React de tokens y fundamentos. |
| VIEW-18 | `views/design-system/families/overlays.php` | Fixture | Familia React de modal, drawer, popover y feedback. |
| VIEW-19 | `views/design-system/families/page-structure.php` | Fixture | Familia React de estructura de página y estados completos. |
| VIEW-20 | `views/design-system/families/shell-navigation.php` | Fixture | Familia React del sidebar, contexto, usuario, semana y notificaciones. |
| VIEW-21 | `views/design-system/families/states-feedback.php` | Fixture | Familia React de carga, vacío, error, éxito y guardado. |
| VIEW-22 | `views/design-system/families/vendor-adapters.php` | Fixture | Portar solo adapters aún permitidos; marcar retirados los vendors que desaparezcan. |
| VIEW-23 | `views/design-system/lab.view.php` | Pantalla/layout | Ruta React del laboratorio con navegación por familia y query param estable. |
| VIEW-24 | `views/design-system/operational-fixtures.php` | Fixture compuesto | Fixtures React de auth, semana, tablas, notificaciones, drawer, BI y vendors; el ejemplo visual de admin no amplía el alcance de `/admin/`. |
| VIEW-25 | `views/design-system/ui-group-index.php` | Parcial | Índice React generado desde `ui-groups-inventory.json`. |
| VIEW-26 | `views/errors/error.view.php` | Pantalla de sistema | Estados React 403/404/500 para navegación; respuestas API permanecen JSON. |
| VIEW-27 | `views/indicadores/indicadores.view.php` | Pantalla | Módulo `indicadores`; retirar al validar iframe, permisos y reflow. |
| VIEW-28 | `views/partials/drawer_unificado.php` | Parcial compartido | `shared/drawer-lps`; retirar al cortar PG, PI, PS y Escalamientos, sus cuatro consumidores. |
| VIEW-29 | `views/partials/head_brand.php` | Parcial global | Metadatos, iconos y fuentes pasan a `public/app/index.html`/componente de título; retirar al último consumidor PHP principal. |
| VIEW-30 | `views/partials/shell_sidebar.php` | Parcial global | Shell React completo; retirar al cortar la última pantalla principal legacy. |
| VIEW-31 | `views/plan-compras/app.view.php` | Host de isla | Desaparece al absorber PDC y mover bootstrap a contrato `/plan-compras/api/contexto`. |
| VIEW-32 | `views/profesionales/profesionales.view.php` | Pantalla | Módulo `profesionales`; retirar con CRUD/exportación verificados. |
| VIEW-33 | `views/programa-general-actualizar/programaGeneralActualizar.view.php` | Pantalla | Módulo `actualizar-cronograma`; retirar con importación/mapeo completos. |
| VIEW-34 | `views/programa-general/programa_general.view.php` | Pantalla | Módulo `programa-general`; sigue su spec hija. |
| VIEW-35 | `views/programacion-intermedia/programacion_intermedia.view.php` | Pantalla | Módulo `programacion-intermedia`; retirar con batch/drawer/reportes. |
| VIEW-36 | `views/programacion-semanal/CIC.view.php` | Pantalla | Módulo `cic`; retirar con formularios por disciplina. |
| VIEW-37 | `views/programacion-semanal/CNC.view.php` | Pantalla | Módulo `cnc`; retirar con causas y guardado. |
| VIEW-38 | `views/programacion-semanal/CNP.view.php` | Pantalla | Módulo `cnp`; retirar con causas y reprogramación. |
| VIEW-39 | `views/programacion-semanal/partials/_changeMonitorModal.php` | Modal | Componente React de cambios detectados con sus decisiones y efectos. |
| VIEW-40 | `views/programacion-semanal/partials/modal_reabrir.php` | Modal | Componente React de reapertura, motivo, política y feedback. |
| VIEW-41 | `views/programacion-semanal/programacion_semanal.view.php` | Pantalla | Módulo `programacion-semanal`; retirar después de core, modales, drawer y satélites. |
| VIEW-42 | `views/subcontratistas/subcontratistas.view.php` | Pantalla | Módulo `subcontratistas`; retirar con CRUD/exportación verificados. |

### 12.1 Propietario documental único

| Propietario | VIEW IDs |
|---|---|
| T01 | VIEW-26, VIEW-29, VIEW-30 |
| T02 | VIEW-28 |
| T03 | VIEW-04, VIEW-05, VIEW-06, VIEW-07, VIEW-08, VIEW-09 |
| S01 | VIEW-01 |
| S02 | VIEW-02 |
| S03 | VIEW-03 |
| S04 | VIEW-11 |
| S05 | VIEW-34 |
| S06 | VIEW-33 |
| S07 | VIEW-35 |
| S08 | VIEW-39, VIEW-40, VIEW-41 |
| S09 | VIEW-38 |
| S10 | VIEW-37 |
| S11 | VIEW-36 |
| S12 | VIEW-31 |
| S13 | VIEW-32 |
| S14 | VIEW-42 |
| S15 | VIEW-10 |
| S16 | VIEW-27 |
| S17–S24 | Sin propiedad individual; consumen las piezas compartidas de T03 |
| S25 | VIEW-12 |
| S26 | VIEW-13, VIEW-14, VIEW-15, VIEW-16, VIEW-17, VIEW-18, VIEW-19, VIEW-20, VIEW-21, VIEW-22, VIEW-23, VIEW-24, VIEW-25 |
| S27 | Sin vista: contrato de transición/redirect |

## 13. Piezas JavaScript compartidas que no pueden olvidarse

El inventario de vistas no basta: buena parte del comportamiento vive en `public/js/`. Cada spec
hija debe asignar sus scripts a una de tres salidas: portar, reemplazar por componente compartido o
demostrar que está muerto.

### 13.1 Shell y runtime

- `cargarDatosGeneralesPagina2.js` → contextos tipados de sesión/proyecto/semana.
- `funcionesGenerales6.js` → navegación, semana y acciones de módulo explícitas.
- `SessionExpiredHandler.js` y `SessionTimeoutManager.js` → un provider de sesión.
- `ContextManager.js` → hooks/contexto React sin estado global duplicado.
- `notifications.js` → módulo de notificaciones del shell.
- `sidebar_navigation.js`, `shell_week_admin.js`, `nav_drawer.js`, `shell-drawer.js` → componentes
  del shell, menús y gestión de semanas.
- `theme.js`, `theme-bootstrap.js`, `theme-toggle.js` → provider/conmutador React ya iniciado.
- `rbac_capabilities.js` y mapas por rol → acciones/capacidades del servidor; no se portan como
  otra matriz cliente.

### 13.2 LPS

- `programa_general/hot.js` → spec hija de PG; no se envuelve.
- `programa_actualizar/hot_actualizar.js`, `rule_engine.js`, `decision_logger.js` → módulo de
  actualización, separando cálculo puro, UI y llamadas.
- `programacion_intermedia/hot.js`, `stateMachine.js` → módulo PI.
- `programacion_semanal/hot.js`, `stateMachine.js`, `changeMonitor.js`, `legacyCards.js` → PS y
  sus vistas responsive.
- `lps_drawer.js` → drawer compartido tipado.
- `HandsontableTomSelectEditor.js` → retirar al desaparecer el último Handsontable; no portar.

### 13.3 BI y design system

- `bi-spa.js` → dividir por hoja y absorber en `modules/bi`.
- `bi_chart_theme.js` → helper React de gráficos basado en tokens.
- `bi_filter_drawer.js` → filtros compartidos BI.
- `design_system_lab.js` y helpers `design-system/*` → componentes/fixtures React o retiro si el
  vendor deja de existir.
- `datatable-height-manager.js`, `global-table-align.js`, `mobile-table-fix.js` y
  `tablet-viewport-scale.js` → retirar cuando los layouts React demuestren reflow propio.

## 14. Contrato transversal de estados y accesibilidad

Cada pantalla de producción debe declarar y probar, cuando sean aplicables:

- carga inicial y recarga conservando contexto;
- vacío de negocio;
- sin resultados por filtros;
- error recuperable con reintento;
- sesión vencida;
- proyecto sin acceso o membresía retirada;
- solo lectura;
- guardando, guardado y error de guardado;
- cambios sucios antes de navegar;
- importación/descarga en curso;
- conflicto de negocio o dato desactualizado.

Requisitos mínimos de interacción:

- landmarks y un único `h1` útil;
- orden de foco predecible;
- todo operable con teclado;
- foco atrapado y restaurado en modal/drawer;
- `aria-live` para guardados, errores y cambios de conteo relevantes;
- etiquetas y descripciones de bloqueo;
- targets táctiles suficientes;
- gráficos con resumen textual y datos tabulares cuando la cifra sea decisiva;
- no depender solo de color para estados;
- Axe sin hallazgos critical o serious y revisión manual.

## 15. Estrategia de pruebas

### 15.1 Por endpoint consumido

- esquema Zod unitario, incluidos casos opcionales y errores;
- prueba PHP del contrato real;
- sesión ausente;
- proyecto autorizado y no autorizado;
- permiso de lectura/escritura;
- CSRF en mutaciones;
- aislamiento por proyecto cuando corresponda;
- status HTTP y `Content-Type` correctos.

### 15.2 Por módulo

- pruebas unitarias de lógica pura y validaciones;
- pruebas de componentes para estados e interacción;
- escenario browser del camino principal;
- rol permitido y rol denegado;
- desktop, tablet y móvil;
- tema dark y paridad funcional en claro;
- consola limpia y requests esperados;
- persistencia con restauración;
- golden aprobado cuando cambie la superficie visual.

### 15.3 Por corte de ruta

1. Validar piloto `/app/...` contra legacy con la misma fixture.
2. Probar deep links, refresh, back/forward y query params.
3. Probar rollback del prefijo.
4. Cortar la ruta canónica.
5. Repetir pruebas funcionales y visuales en la ruta canónica.
6. Solo entonces retirar vista y JS exclusivos.

No se regeneran baselines para ocultar regresiones. No se ejecuta DML para “preparar” esta spec.

## 16. Entregas verticales del programa

Cada entrega es un frente publicable y necesita su propia spec hija y plan. El orden fija
dependencias, no una estimación de calendario.

### Entrega 0 — Completar la plataforma del shell

- Router real con layouts, rutas protegidas y estados 403/404.
- Navegación por capacidades/acciones del servidor, retirando el mapa por rol.
- Cliente común para JSON, formularios, uploads y descargas.
- Error normalizado, sesión vencida, timeout y cancelación.
- Logout, cambio de proyecto, selector de semana, notificaciones y títulos.
- Recuperación de clave.
- Componentes base de estados, modal, drawer, tabla y tarjeta.

**Aceptación:** el shell puede alojar un módulo completo sin que ese módulo agregue un cliente HTTP,
un guard de rol o un sistema de estado global propios.

### Entrega 1 — Programa General

Ejecutar las cinco entregas de su spec hija: núcleo de lectura, edición individual, operaciones,
drawer y corte. Es el primer módulo de negocio que cruza.

### Entrega 2 — Actualizar Cronograma

Reutilizar contexto, actividad y validaciones de PG para migrar importación, mapeo, autoasociación,
confirmación y eliminación. Cierra el dominio completo de Programa General.

### Entrega 3 — Profesionales y Subcontratistas

Migrar ambos catálogos como dos rutas dentro de un mismo frente solo si las pruebas y permisos se
mantienen independientes. Proveen componentes de CRUD tabular y selectores que reutilizan PI, PS,
CIC y PDC.

### Entrega 4 — Programación Intermedia

Migrar look-ahead, restricciones, batch, filtros, reportes y drawer. Reutilizar catálogos y patrones
de PG sin acoplar dominios.

### Entrega 5 — Programación Semanal y semanas

Migrar la pantalla principal, gestión de semana, actividad manual, TNP, cierre/reapertura,
autoprogramación, change monitor, reportes y drawer. Mantener CNP/CNC/CIC como rutas legacy hasta
que cada una cierre su propia entrega.

### Entrega 6 — CNP, CNC y CIC

Tres subproyectos con specs hijas separadas y un mismo shell de navegación semanal. CNP primero por
su dependencia de reprogramación; CNC después por el cierre de compromisos; CIC al final por sus
formularios y permisos por disciplina.

### Entrega 7 — Escalamientos, crisis y notificaciones

Completar el drawer compartido fuera de las pantallas de origen, migrar el dashboard y consolidar
avisos del shell. Cerrar duplicados GET/POST solo después de verificar consumidores.

### Entrega 8 — Control de Cambios e Indicadores

Migrar Control de Cambios como formulario complejo independiente. Migrar Indicadores como wrapper
seguro y responsive de Power BI. Integrar sus descargas y permisos.

### Entrega 9 — Absorber Plan de Compras v2

Mover las ocho pantallas, pruebas y utilidades de `pdc-app/` al frontend principal; sustituir el
cliente propio por `cliente.ts`, introducir Zod y convertir hashes a rutas. Retirar el build
`public/pdc-app/` solo después del corte.

### Entrega 10 — Absorber y completar Torre de Control BI

Mover `ct-app/`, cortar Intermedia y migrar las siete hojas restantes. Conservar acceso preview,
flag, audiencia por rol, multiobra y alcance por responsable. Retirar `bi-spa.js`, Chart.js global y
el host `public/ct-app/` al cerrar la octava hoja.

### Entrega 11 — Laboratorio, errores y retiro del runtime legacy

- Cortar el laboratorio React y sus diez familias.
- Completar errores y salida de mantenimiento generada.
- Retirar parciales globales PHP.
- Retirar rutas `/legacy/*` sin consumidores.
- Retirar vendor assets y entrypoints CSS huérfanos.
- Convertir `/app` en redirect al landing canónico.
- Confirmar que `views/` ya no contiene presentación activa de la aplicación principal.

`admin/views/` no participa en esta condición.

## 17. Dependencias entre entregas

```text
Plataforma del shell
  ├─ Programa General ── Actualizar Cronograma
  ├─ Profesionales/Subcontratistas ── Programación Intermedia
  │                                      └─ Programación Semanal
  │                                           └─ CNP ── CNC ── CIC
  │                                                └─ Escalamientos/avisos
  ├─ Control de Cambios / Indicadores
  ├─ Absorción PDC
  └─ Absorción BI

Todos los componentes aprobados ── Laboratorio y retiro final
```

PDC y BI pueden prepararse en paralelo documentalmente, pero su implementación no abre un frente
nuevo mientras el gate de publicación del frente anterior siga pendiente.

## 18. Criterio global de cierre

El programa termina cuando:

- las 26 superficies canónicas sirven React;
- los ocho destinos PDC son rutas del router principal;
- las ocho hojas BI son React dentro del router principal;
- login, recuperación, proyecto, semana, logout y errores pertenecen al shell;
- las 42 vistas/partials PHP están retiradas o, si una resultó muerta, eliminadas con evidencia;
- `pdc-app/` y `ct-app/` ya no se construyen ni publican;
- no quedan consumidores productivos de Handsontable, DataTables, jQuery UI ni los clientes HTTP
  paralelos del frontend principal;
- las rutas procedurales están retiradas o existe una excepción vigente y justificada;
- todas las rutas consumidas tienen Zod y pruebas PHP de contrato;
- la matriz de permisos se resuelve en servidor y no por rol hardcodeado en React;
- las suites frontend, contratos, browser, accesibilidad y visuales están verdes;
- cada frente fue integrado por PR con CI verde según la política vigente;
- no se tocó RLS ni se dejó dato de prueba sin restaurar.

La permanencia de `/admin/` en PHP/AdminLTE no impide este cierre porque está explícitamente fuera
del programa.

## 19. Riesgos y mitigaciones

| Riesgo | Mitigación contractual |
|---|---|
| El inventario se vuelve obsoleto mientras dura el programa | Cada spec hija reconcilia `public/index.php`, vistas y consumidores actuales; el censo se regenera antes del plan. |
| Se porta HTML y se pierden funciones escondidas | Auditoría obligatoria de JS inline, modales, endpoints, reportes y pruebas antes de diseñar la pantalla React. |
| React duplica permisos y diverge | Acciones resueltas por servidor y pruebas de rol permitido/denegado. |
| Se copian contratos legacy inseguros con `db` | El servidor deriva proyecto; el cliente no envía prefijos como autoridad. |
| Tres SPAs sobreviven indefinidamente | PDC y CT tienen entregas de absorción y criterio explícito de eliminación. |
| Una grilla universal degrada móvil o licencia | Selección por caso: semántica/tarjetas primero, AG Grid Community donde se justifique, sin Handsontable. |
| El programa queda en “90 %” con parciales y rutas legacy | Inventario nominal de 42 vistas, siete rutas procedurales y criterio de retiro por consumidor. |
| Migrar mantenimiento rompe el acceso durante una caída | Salida autocontenida generada desde React, sin runtime JS ni APIs. |
| Goldens verdes ocultan pérdida funcional | Contratos y escenarios funcionales preceden a la aprobación visual; no se regeneran baselines sin aprobación. |

## 20. Decisiones reemplazadas de la versión anterior

- “PDC y CT se absorben solo si un mantenimiento futuro lo justifica” queda reemplazada: ambos son
  entregas explícitas del programa integral.
- “AG Grid Community para todo” queda reemplazada por selección de presentación según el trabajo.
- “`admin/` migra de último y se reevaluará” queda reemplazada por exclusión explícita.
- “Recuperación de clave queda fuera del shell” solo describía el shell mínimo; ahora forma parte de
  la Entrega 0 de la migración integral.

## 21. Preguntas abiertas

No queda una decisión arquitectónica bloqueante para escribir specs hijas. Cada spec hija puede
descubrir preguntas de producto propias —por ejemplo, un comportamiento legacy ambiguo—, pero debe
resolverlas antes de `writing-plans` y no asumir silenciosamente que una función puede desaparecer.

## 22. Siguiente paso después de aprobar esta spec

1. Revisar y aprobar o corregir esta v0.1.
2. Mantener Programa General como la primera spec hija y cerrar su revisión.
3. Invocar `superpowers:writing-plans` únicamente para la primera entrega ejecutable del frente
   vigente.
4. No implementar otro módulo desde esta spec maestra sin su spec hija decision-complete.
