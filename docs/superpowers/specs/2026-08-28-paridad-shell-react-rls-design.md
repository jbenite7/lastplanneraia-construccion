---
capa: fuente
tipo: spec
estado: aprobado
fecha: 2026-08-28
areas: [arquitectura, frontend, rbac, seguridad, design-system]
fuente: "sesión de brainstorming con Felipe, 2026-08-28; seis secciones aprobadas en chat"
resumen: "Cierre de paridad funcional del shell React frente al shell legacy, con interfaz renovada, temas claro y oscuro, respuesta móvil/tablet/escritorio y aislamiento por proyecto fail-closed en la capa de aplicación."
---

# Paridad del shell React y RLS — diseño

> **Estado:** las seis secciones y esta transcripción escrita fueron aprobadas por Felipe en la
> sesión del 2026-08-28.

## Relación con el frente anterior

Esta spec continúa, no reemplaza, las decisiones de
[[docs/superpowers/specs/2026-08-28-migracion-react-typescript-design|Migración a React + TypeScript]]
y toma como línea base el trabajo ejecutado por
[[docs/superpowers/plans/2026-08-28-shell-minimo-react|Shell mínimo React]].

El frente anterior probó la frontera técnica: `/app` puede autenticar, seleccionar un proyecto,
mostrar navegación y enlazar los módulos PHP sin apropiarse de sus rutas. El resultado es una
cáscara mínima deliberada. Este diseño define cómo convertirla en el shell de uso real.

## Resultado buscado

El shell React tendrá, como mínimo, todas las capacidades y comportamientos observables que hoy
ofrecen las superficies legacy equivalentes. **Paridad no significa identidad visual:** se permite
y se busca una composición nueva, siempre que no desaparezca ninguna capacidad útil y que toda
regla de seguridad permanezca o se endurezca.

La condición de hecho es conjunta:

1. Login, selección de proyecto y shell global funcionan en React con paridad funcional.
2. Los módulos todavía no migrados siguen abriendo sus rutas PHP completas e intactas.
3. Toda lectura o mutación de datos de proyecto queda aislada por un contexto autorizado y
   falla cerrada cuando ese contexto falta o no coincide.
4. Los modos claro y oscuro funcionan en móvil, tablet y escritorio conforme a la referencia
   visual aprobada.
5. Una matriz trazable demuestra cobertura del legacy, no una impresión subjetiva de semejanza.

## Alcance

### Incluido

- Login diario y sus estados: credenciales, mensajes seguros, visibilidad de contraseña,
  expiración/inactividad y cambio obligatorio de contraseña.
- Selector de proyecto: navegación mínima, búsqueda, conteo vivo, metadatos, estados vacíos,
  Control Tower cuando corresponda, cuenta, salida y tema.
- Shell dentro de un proyecto: navegación completa autorizada, proyecto y módulo activos,
  contexto de semana, cambio/creación/eliminación de semana según capacidad, cuenta, cambio de
  proyecto, cierre de sesión, tema y comportamiento responsive.
- Contratos JSON que el shell necesita y validación runtime en la frontera React.
- Aislamiento por proyecto aplicado en el backend completo, no solo a peticiones originadas en
  React.
- Pruebas de contrato, componentes, flujos, seguridad, accesibilidad y apariencia.

### Fuera de alcance

- Reescribir el contenido de Programa General, Programación Intermedia, Programación Semanal,
  PDC, Control Tower u otros módulos. En este frente siguen siendo páginas completas en su stack
  actual.
- Migrar el panel `admin/`.
- Cambiar la lógica de negocio, el modelo de sesión o el proveedor de autenticación.
- Rehacer los flujos de recuperación y restablecimiento de contraseña. El acceso desde React se
  conserva, pero `/password/forgot` y `/password/reset` siguen en PHP según R12 de la spec de
  migración.
- Reemplazar los tokens, introducir una segunda librería visual o copiar literalmente el aspecto
  del legacy.
- Simular RLS nativo de MySQL. MySQL 8 no lo ofrece; el control diseñado aquí es fail-closed en la
  aplicación, acompañado por mínimos privilegios de base de datos.

## 1. Contrato de paridad

La unidad de comparación es una **capacidad observable**. Cada fila de la matriz de paridad debe
registrar:

- superficie y escenario legacy;
- rol, área y contexto de proyecto aplicables;
- entrada o acción del usuario;
- resultado observable, incluidos carga, vacío, error y éxito;
- contrato backend y ruta React responsables;
- prueba automatizada y evidencia visual cuando corresponda;
- estado: inventariada, implementada, verificada o aceptada.

Una fila no queda cerrada solo porque exista un control parecido. Debe conservar intención,
permisos, datos, transición y recuperación. React puede mejorar jerarquía, copy, accesibilidad o
adaptación responsive sin solicitar equivalencia píxel a píxel.

### Inventario mínimo conocido

| Superficie | Capacidades legacy que deben quedar cubiertas en React |
|---|---|
| Login | Marca y contexto, mensajes de error/éxito, aviso de sesión expirada o cuenta inactiva, usuario, contraseña, mostrar/ocultar, envío con estado ocupado, enlace de recuperación y cambio obligatorio con validación y salida segura |
| Selector | Identidad de usuario, salida, tema, acceso autorizado a Control Tower, búsqueda, conteo anunciado, tarjetas con nombre/área/estado/rol, selección protegida, sin proyectos, sin coincidencias y error recuperable |
| Navegación | Grupos Información/Obra/Compras, destinos y visibilidad resueltos por el servidor, elemento activo, proyecto/usuario, cambio de proyecto y cierre de sesión |
| Contexto semanal | Semana actual, lista y fechas, cambio de semana, destinos semanales por módulo, crear la siguiente y eliminar únicamente la última cuando RBAC lo permita |
| Responsive | Sidebar persistente en escritorio; menú flotante/drawer por debajo de 1180 px; foco, cierre y bloqueo de fondo correctos; sin desbordamiento horizontal |
| Tema | Claro como entrada, oscuro de primera clase, preferencia persistida y aplicación antes del primer render para evitar destellos |

Este inventario es el piso, no un sustituto del barrido ejecutable del legacy que abrirá el plan.

## 2. Arquitectura y flujo de datos

### Enfoque elegido

React se convierte en dueño del shell, mientras PHP sigue siendo dueño de sesión, RBAC, servicios,
datos y módulos no migrados. La convivencia continúa por URL y recarga completa de página.

```text
Navegador
  └─ /app → React + TypeScript
       ├─ bootstrap/sesión ─────────────┐
       ├─ login / cambio de clave       │
       ├─ proyectos / selección         ├─→ controladores JSON PHP
       └─ navegación / semanas          │      └─ servicios de dominio y RBAC
                                        │             └─ ProjectScope
ruta de módulo no migrado ──────────────┘                    └─ Database → MySQL
  └─ recarga completa → vista PHP legacy
```

No habrá iframe, microfrontend ni duplicación de sesión. La cookie PHP de mismo origen sigue siendo
la única sesión. Una visita a una ruta PHP sale de la SPA; volver a `/app` reconstruye el estado
desde el servidor.

### Bootstrap canónico

El servidor produce un modelo de arranque autorizado con:

- estado de autenticación;
- usuario y rol canónico;
- proyecto activo y metadatos necesarios;
- capacidades;
- manifiesto de navegación ya filtrado, con etiqueta, grupo, destino, icono y estado activo;
- contexto semanal y acciones permitidas;
- destinos especiales como BI ya resueltos;
- token CSRF de la superficie.

El contrato puede ampliar `GET /api/session` o extraerse en un endpoint cohesivo si el plan prueba
que el primero quedaría haciendo dos trabajos. La decisión importante es que exista **una sola
fuente server-side**. React no mantiene `ocultasPorRol`, no deduce permisos de códigos de rol y no
construye URLs privilegiadas.

El bootstrap conserva la semántica especial vigente: una visita normal sin sesión responde `200`
con estado anónimo, porque no estar autenticado todavía no es un error. Cuando la cookie estaba
vencida o fue revocada puede incluir una razón no sensible para mostrar el aviso correcto, sin
revelar datos de la sesión anterior.

Los esquemas Zod validan cada respuesta al entrar y generan sus tipos TypeScript. Ningún componente
llama `fetch` directamente: usa el cliente HTTP común y recibe modelos ya validados.

### Unidades del frontend

- `SesionProvider`: carga y refresca el estado canónico; limpia datos cuando la sesión vence.
- `AuthFlow`: login y cambio obligatorio de contraseña; no conoce proyectos ni navegación.
- `ProjectPicker`: consulta, filtra y selecciona proyectos autorizados.
- `AppShell`: coordina layout, drawer, contexto, cuenta y contenido.
- `Navigation`: renderiza exclusivamente el manifiesto del servidor.
- `WeekContext`: presenta semana y ejecuta acciones autorizadas mediante contratos JSON.
- `ThemeProvider`: aplica y persiste claro/oscuro antes del primer render.
- `ApiClient`: CSRF, parseo, Zod, errores tipados y correlación.

Cada unidad expone una interfaz pequeña; la lógica de autorización permanece en PHP.

### Contratos backend

Los controladores del shell son adaptadores JSON sobre servicios existentes. No copian lógica del
legacy. Como mínimo deberán soportar:

- consultar sesión/bootstrap;
- entrar y salir;
- completar o cancelar el cambio obligatorio de contraseña;
- listar y seleccionar proyectos con sus metadatos visibles;
- consultar navegación y semanas dentro del contexto autorizado;
- cambiar de semana;
- crear o eliminar semana mediante los mismos servicios y capacidades vigentes.

Las mutaciones usan CSRF, aceptan identificadores estables y vuelven a consultar el estado canónico
tras el éxito. Los scripts legacy no se invocan desde React como interfaz permanente: cuando aún
contengan lógica, se extrae esa lógica a un servicio compartido y tanto el adaptador viejo como el
JSON llaman al mismo servicio.

## 3. RLS de aplicación fail-closed

### Decisión

El aislamiento se aplica en el servidor y en el punto común de acceso a datos. El `project_id`
proveniente del navegador nunca concede alcance. El contexto se deriva de la sesión y se valida
contra la membresía activa del usuario.

### Contextos permitidos

- **Sin autenticar:** solo contratos públicos de autenticación y recuperación; ninguna consulta
  operativa.
- **Usuario sin proyecto activo:** puede consultar su identidad y su lista de membresías, pero no
  datos operativos.
- **Proyecto único:** contexto normal de la aplicación. Contiene usuario, proyecto, rol y
  capacidades resueltos server-side.
- **Multiproyecto explícito:** reservado a casos como BI. `BiProjectScope` normaliza los IDs y
  verifica que todos pertenezcan al conjunto autorizado antes de crear el alcance.
- **Sistema:** tareas administrativas o de mantenimiento declaradas. No se infiere por ausencia de
  proyecto y requiere una puerta explícita y auditable.

### Clasificación de tablas

Un catálogo único y verificable clasifica cada tabla como:

1. `project-scoped`: contiene `project_id` y exige contexto de proyecto;
2. `identity-membership`: usuarios, proyectos y membresías, accesibles solo por servicios
   dedicados;
3. `system-global`: catálogos realmente compartidos, declarados de forma explícita;
4. `legacy-prefixed`: compatibilidad temporal cuyo prefijo debe resolver a un proyecto autorizado.

Una tabla operativa no clasificada no se trata como global: **se rechaza**. El inventario compara
catálogo, schema real y consultas para evitar que una tabla nueva quede fuera por olvido.

Las tablas `project-scoped` deben tener `project_id NOT NULL` e índices cuyo prefijo acompañe los
patrones de consulta y unicidad. Los cambios de schema serán aditivos, con dry-run, respaldo,
restauración probada y reconciliación según el contrato del repo.

### Reglas del acceso a datos

- Tocar una tabla `project-scoped` sin alcance válido lanza una excepción antes de preparar SQL.
- `queryWithProject()` deja de ejecutar sin filtro cuando falta `project_id`; desaparece el
  fallback actual que solo escribe un warning.
- Que el texto SQL ya contenga `project_id` no basta. El gateway debe inyectar el alcance o validar
  que el valor enlazado coincide con el contexto autorizado.
- Un prefijo legacy se resuelve a su `project_id`; si no resuelve, resuelve a otro proyecto o mezcla
  proyectos, la consulta se rechaza.
- El alcance múltiple solo acepta un conjunto previamente autorizado y queda separado de la API de
  proyecto único.
- El contexto se limpia al terminar cada request, comando o iteración de worker para impedir
  contaminación entre usuarios.
- Las rutas y servicios conservan RBAC. RLS limita filas; no sustituye permisos de acción.

La activación se hace después de inventariar y corregir violaciones, pero el estado final es
fail-closed en todos los entornos. Un modo de auditoría puede ayudar a descubrir consultas durante
la preparación; no será una opción de producción que permita continuar inseguramente.

### Defensa complementaria

La conexión de la aplicación usa un usuario MySQL de mínimos privilegios: operaciones necesarias
sobre datos de aplicación, sin administración de usuarios, grants ni schema en runtime. Prepared
statements, CSRF, RBAC y mensajes no enumerables siguen siendo obligatorios. Esta defensa reduce el
impacto, pero no se presenta como RLS nativo de base de datos. Migraciones y mantenimiento usan una
credencial separada, de duración y procedimiento controlados; nunca elevan la cuenta de runtime.

## 4. Sistema visual y composición

La referencia aprobada y versionada es:

[[docs/superpowers/specs/evidencia/2026-08-28-shell-react-design-system-atlas|Atlas visual del design system del shell React]].

### Fundamentos

- Claro es el modo de entrada; oscuro es una variante completa, no un parche.
- Verde corporativo conduce acciones primarias.
- Naranja identifica contexto de Construcción; Pre-Construcción conserva tratamiento neutral.
- Montserrat se usa para títulos y marca; Inter para lectura e interfaz.
- Colores, radios, sombras, espaciado y estados salen de tokens existentes. No se agregan literales
  locales para aproximar la referencia.
- Botones, campos, chips, avisos, tablas, menús y modales comparten primitivas canónicas.

### Login

En escritorio usa composición de dos paneles: contexto de marca y formulario. En móvil converge en
una sola columna. Incluye avisos, mostrar/ocultar contraseña, estados ocupado/error y diálogo de
cambio obligatorio con foco contenido y salida explícita.

### Selector de proyecto

Usa un shell simplificado, búsqueda y conteo vivo. Las tarjetas muestran la información útil sin
reproducir el ruido visual legacy: tres columnas en escritorio, dos en tablet y una en móvil.
Contempla carga, vacío, búsqueda sin resultados y error recuperable.

### Shell de proyecto

En escritorio muestra sidebar persistente y colapsable. Por debajo de 1180 px usa drawer flotante
con velo, cierre por `Escape`, devolución de foco y bloqueo del fondo. Incluye navegación por grupos,
contexto proyecto/módulo/semana, acciones de semana según capacidades, cuenta, cambio de proyecto,
salida y tema. Los módulos PHP se abren como páginas completas.

Los viewports contractuales de esta superficie son 390 px, 768 px y 1180 px, en ambos temas. No
debe existir desplazamiento horizontal accidental. Todo control tiene nombre accesible, foco visible
y un objetivo táctil adecuado.

## 5. Estados, errores y recuperación

La aplicación distingue estos estados de arranque:

1. cargando bootstrap;
2. anónimo;
3. autenticado sin proyecto;
4. autenticado con proyecto autorizado;
5. sesión vencida o revocada;
6. fallo de carga recuperable.

Nunca se muestra contenido operativo mientras sesión y proyecto sigan sin resolver.

Los endpoints nuevos o ampliados usan un contrato de error estable con código de aplicación,
mensaje seguro, errores de campo cuando existan e identificador de correlación. Los estados HTTP
se interpretan así:

- `401`: sesión ausente o vencida; se limpia estado local y se vuelve al acceso;
- `403`: operación conocida pero no autorizada;
- `404`: recurso inexistente o deliberadamente oculto por aislamiento;
- `409`: conflicto de estado o concurrencia;
- `422`: validación, con mensajes junto a los campos y resumen accesible;
- `5xx`: mensaje seguro, correlación y recuperación sin filtrar detalles internos.

Las lecturas idempotentes admiten reintentos limitados. Las mutaciones no se repiten
automáticamente: deshabilitan el control mientras corren y refrescan el estado canónico al terminar.

Los fallos locales aparecen junto al control, los recuperables de sección dentro de su contexto y
los fallos de render o bootstrap en una pantalla de recuperación. Los límites de error de React
contienen fallos inesperados sin desmontar más superficie de la necesaria.

Los logs incluyen correlación, ruta, usuario y proyecto cuando sea seguro, nunca contraseñas,
tokens, cookies ni contenido sensible. Un rechazo de RLS se registra sin confirmar al cliente la
existencia de datos fuera de su alcance.

## 6. Verificación de paridad y transición

### Capas de prueba

1. **Contratos PHP:** sesión, autenticación, proyectos, navegación, semanas, CSRF y errores.
2. **RLS:** lecturas y mutaciones entre usuarios, roles y dos proyectos distintos; contexto ausente,
   ID manipulado, prefijo ajeno, alcance múltiple parcial y limpieza entre requests.
3. **React:** esquemas, estados, componentes, foco, navegación, permisos renderizados y recuperación.
4. **Extremo a extremo:** login, cambio obligatorio, selector, cambio de proyecto, semana activa,
   administración de semanas, navegación a PHP y cierre de sesión.
5. **Visual y accesibilidad:** claro/oscuro × 390/768/1180; teclado, foco, contraste, nombres
   accesibles y ausencia de overflow.

No se introduce un porcentaje mínimo de cobertura. Los gates son los contratos y escenarios de
riesgo. Cada endpoint consumido por React tiene una prueba contra PHP real y su esquema Zod.

### Matriz de roles y proyectos

La verificación cubre, como mínimo:

- un rol que puede administrar semanas y otro que no;
- un rol con una entrada de navegación visible y otro que la tiene denegada;
- un usuario con acceso a dos proyectos;
- un usuario del proyecto A intentando leer y mutar el proyecto B;
- un alcance BI válido y otro que mezcla un proyecto autorizado con uno ajeno.

El servidor decide; ocultar un enlace en React nunca es la prueba de autorización.

### Transición

React y legacy coexisten mientras se cierran filas de la matriz. Una capacidad sustituye su
equivalente solo cuando está implementada, probada y revisada. Las migraciones permanecen aditivas
durante la convivencia.

La promoción se bloquea ante cualquier fuga entre proyectos, capacidad legacy faltante, fallo
crítico de los flujos principales o regresión relevante de accesibilidad/apariencia.

El rollback puede devolver `/app` a la versión anterior o enviar al usuario a las superficies PHP,
sin perder datos. **RLS no se desactiva para recuperar la interfaz:** se revierte la ruta o versión,
nunca la protección de filas.

## Criterios de aceptación

- La matriz registra el 100 % de las capacidades observables del login, selector y shell legacy y
  todas sus filas están aceptadas.
- `/app` cubre los seis estados de arranque sin mostrar datos antes de resolver el contexto.
- El usuario puede completar login, cambio obligatorio, selección/cambio de proyecto, navegación,
  acciones de semana autorizadas y salida.
- Las rutas no migradas siguen sirviendo sus vistas PHP y preservan su comportamiento.
- No existe un mapa de roles duplicado en React; navegación y acciones llegan autorizadas desde el
  servidor.
- Ninguna consulta operativa puede ejecutarse sin alcance, con un proyecto no autorizado o con un
  prefijo legacy no validado.
- Los flujos de aislamiento A→B fallan tanto en lectura como en mutación, con respuesta no
  enumerable y evidencia automatizada.
- Los seis cruces tema/viewport coinciden funcionalmente con el atlas aprobado y pasan los gates
  de accesibilidad y overflow.
- Las suites PHP, frontend, typecheck, diseño y Playwright pertinentes quedan verdes con salidas
  registradas antes de publicar.

## Secuencia para el futuro plan

La implementación debe respetar dependencias, no el orden visual del documento:

1. inventario ejecutable de paridad, tablas y consultas;
2. contratos de `ProjectScope`, catálogo y pruebas rojas de aislamiento;
3. endurecimiento fail-closed y corrección de consultas detectadas;
4. bootstrap/manifiesto y adaptadores JSON del shell;
5. primitivas y layout responsive conforme al atlas;
6. login y cambio obligatorio;
7. selector de proyecto;
8. navegación, contexto y administración de semanas;
9. matriz E2E/visual completa, convivencia y rollback.

La escritura del plan puede dividir estos bloques en hitos verificables, pero no declarar paridad
antes de completar la matriz ni habilitar el shell sobre un RLS que todavía permita fallback.

## Evidencia y referencias

- [[docs/superpowers/specs/evidencia/2026-08-28-shell-react-design-system-atlas|Referencia visual aprobada de la Sección 4]].
- [[docs/superpowers/specs/2026-08-28-migracion-react-typescript-design|Decisiones de migración React + TypeScript]].
- [[docs/superpowers/plans/2026-08-28-shell-minimo-react|Plan ejecutado del shell mínimo]].
- [[docs/global-tables-architecture|Contrato vigente de tablas globales]].
- `src/Core/Database.php`: frontera actual que debe dejar de ejecutar sin proyecto.
- `src/Support/BiProjectScope.php`: precedente para alcance multiproyecto autorizado.
- `views/auth/login.view.php`, `views/core/project_selector.view.php` y
  `views/partials/shell_sidebar.php`: superficies legacy que alimentan la matriz de paridad.
