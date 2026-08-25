---
capa: fuente
tipo: biblia
estado: vigente
fecha: 2026-08-04
areas: [lps, bi]
fuente: docs/flujos/lectura-bi.md
resumen: Escenarios BI-. Los módulos de consulta: qué cifras se muestran, a quién, y de qué proyectos.
---

# Biblia · Lectura · Indicadores y Torre de Control BI

Escenarios `BI-*`. Los módulos de consulta: qué cifras se muestran, a quién, y de qué proyectos.

Formato y reglas: `docs/flujos/README.md`. Verificado por lectura el **2026-08-04**.

Van al final de las cinco tandas **a propósito**: describir una cifra exige haber descrito antes su
origen. Todo lo que estos módulos muestran sale de lo que registran los módulos operativos
(`docs/flujos/lps-*.md`).

---

## BI-001 · Pedir proyectos que no son tuyos se rechaza

El escenario que sostiene todo el aislamiento del portafolio.

- **Rol:** cualquiera con sesión.
- **Precondiciones:** la petición pide uno o varios `project_id` por parámetro.
- **Pasos:**
  1. `BiProjectScope::resolve()` normaliza lo pedido y calcula los proyectos autorizados del
     usuario.
  2. Si **alguno** de los pedidos no está entre los autorizados, lanza `DomainException` con «No
     tienes permiso para consultar esos proyectos».
- **Resultado esperado:** rechazo total, no filtrado parcial. Pedir cinco proyectos de los que solo
  cuatro son tuyos **no devuelve cuatro**: devuelve error. Es la conducta correcta, porque un
  filtrado silencioso ocultaría el intento.
- **Verificación:** lectura — `src/Support/BiProjectScope.php:25-36`.

## BI-002 · Sin proyectos pedidos, manda el de la sesión

- **Pasos:** si no se piden proyectos, se usa `session['project_id']` **siempre que esté entre los
  autorizados**; si no lo está, se cae al primero autorizado; si no hay ninguno, `DomainException`
  «No tienes proyectos autorizados para Control Tower».
- **Resultado esperado:** nunca se responde con datos de un proyecto no autorizado, ni siquiera por
  omisión.
- **Verificación:** lectura — `src/Support/BiProjectScope.php:38-47`.

## BI-003 · Los proyectos autorizados se calculan con permiso, no solo con membresía

- **Pasos:** `authorizedProjectIds()` consulta `project_members` uniendo con usuarios y proyectos,
  filtrando por `Acceso = 1 OR pm.role IN ('A','D','P')`, y **además** descarta cada fila cuyo rol
  no supere `rbac->can('lps.indicadores.ver', $role)`.
- **Resultado esperado:** ser miembro de un proyecto no basta: hace falta el permiso de ver
  indicadores. Un rol sin esa clave no ve el proyecto en BI aunque figure en él.
- **Verificación:** lectura — `src/Support/BiProjectScope.php:161` y siguientes.

> **Tercera variante del mismo filtro, registrada el 2026-08-04.** El criterio «proyecto cerrado
> pero visible para la jefatura» aparece ya en tres sitios y con tres redacciones distintas:
>
> | Dónde | Cómo filtra |
> |---|---|
> | `ProjectSelectorController::index()` | SQL: `Acceso = 1 OR pm.role IN ('A','D')` |
> | `ProjectSelectorController::enterProject()` | PHP, con el rol **ya normalizado** |
> | `BiProjectScope::authorizedProjectIds()` | SQL: `Acceso = 1 OR pm.role IN ('A','D','P')` |
>
> BI incluye **`'P'`**, el alias legado de Director, que el selector omite. Consecuencia: una cuenta
> con `role = 'P'` **vería un proyecto cerrado en la Torre de Control y no en el selector**. Es
> incoherencia, no fuga: `'P'` es Director por definición (`RbacCatalog::roleAliases()`), así que BI
> acierta y el selector se queda corto. Relacionado con `PROY-006` y `PROY-007`.

## BI-004 · La Torre de Control corta en el servidor

- **Rol:** uno sin proyectos autorizados o que pide lo que no le corresponde.
- **Resultado esperado:** **403** desde el servidor, con mensaje escapado
  (`htmlspecialchars`) — no se refleja entrada del usuario sin sanear.
- **Verificación:** lectura — `src/Controllers/Bi/BiViewController.php:277-283`
  (`abortUnauthorizedProjectScope()`).

> **Corrección de cita, 2026-08-25.** Esta línea citaba `:176-182`, que hoy es otro tramo del
> archivo — el método se movió al reordenar `renderView()`. La regla sigue viva, solo cambió el
> número de línea. Es la misma clase de deriva que [[el-tipo-de-una-fuente-lo-dedujo-un-script]]
> nombra para el frontmatter: un archivo que crece desactualiza sus propias citas si nadie las
> vuelve a leer.

## BI-005 · `/indicadores` corta en el servidor — y ya no es un hallazgo

- **Roles denegados por diseño:** `G`, `S`, `SG`, `C` (decisión registrada en
  `memoria/decisiones/powerbi-indicadores.md`).
- **Resultado esperado:** que esos cuatro roles **no reciban el informe**, con la restricción
  aplicada donde no se puede saltar: el servidor.
- **Verificación:** lectura — `src/Controllers/Gestion/IndicadoresController.php:16` declara
  `ROLES_SIN_INFORME = ['G', 'S', 'SG', 'C']`, y `:27-29` corta con `abortUnauthorizedProjectScope()`
  **antes** de `require` la vista — la URL de Power BI nunca llega a construirse para esos cuatro
  roles. Comprobado en ejecución con `test.C` (rol `C`, ver `e2e/tests/biblia/lectura.spec.mjs`
  `BI-007`): `GET /indicadores` responde **403**, no 200.

> **Corregido el 2026-08-25: esto era un hallazgo hasta el commit `4b1a2be0` del 2026-08-06,
> y el propio documento seguía describiendo el estado anterior a esa fecha como si fuera hoy.**
> El registro completo de qué pasó y por qué está en
> `memoria/trampas/indicadores-oculta-en-cliente-bi-en-servidor.md` (ya marcada `derogada`, con la
> fecha del fix) y en `docs/EXPERIMENTS.md` fila 65 (`cerrado 4b1a2be0`). Esta sección de la
> biblia era la única pieza de la wiki que aún no reflejaba el cierre — la cláusula de autoridad
> de este documento («si la biblia y el código divergen, es un bug de uno de los dos») se aplica
> también contra las propias notas hermanas, no solo contra el código.

## BI-007 · El mismo rol restringido, dos mecanismos de negación distintos

Verificado en ejecución, no solo por lectura — es el escenario crítico de T5.

- **Rol:** `C` (Subcontratista), uno de los cuatro restringidos.
- **Pasos:** con el mismo rol en la misma sesión, pedir `/indicadores` y luego `/bi/control-tower`.
- **Resultado esperado y medido:**

  | Ruta | Código | Por qué |
  |---|---|---|
  | `/indicadores` | **403** | `IndicadoresController::index()` corta por rol (BI-005) |
  | `/bi/control-tower` | **404** | `BiPreviewAccessPolicy::canOpen()` corta **antes**, por diseño — «para no confirmar que la pantalla existe» (`BiViewController.php:54-60`, comentario en el propio código) |

  **No son el mismo mecanismo con dos códigos distintos: son dos generaciones de guardia
  superpuestas.** `/bi/*` ganó su gate de módulo completo el 2026-08-13
  (`docs/superpowers/specs/2026-08-13-ocultar-control-tower-design.md`, «el módulo está oculto
  mientras se desarrolla»), y ese gate corre **antes** que el filtro de alcance de proyecto que
  BI-004 describe (`BiProjectScope` → 403). Para un rol sin `PERM_INTERNAL_BI_PREVIEW`, el 403 de
  BI-004 nunca se alcanza a comprobar: lo tapa el 404 del módulo oculto. `/indicadores` no tiene
  ese gate de módulo —nunca estuvo oculto de navegación— así que su único filtro es el de rol,
  y responde 403 limpio.
- **Verificación:** `e2e/tests/biblia/lectura.spec.mjs` · `BI-007`.

## BI-008 · Un rol permitido entra a la Torre de Control; uno denegado no

- **Rol permitido:** `R` (Residente) — ampliado el 2026-08-24, reparto de lienzos por rol
  (`src/Security/RbacManager.php:33`: `PERM_INTERNAL_BI_PREVIEW` es `A || D || R`). El interruptor
  `bi.control_tower.visible` de `general_flags` está en `1` (comprobado en `general_flags` el
  2026-08-25); si algún día pasa a `0`, este escenario también deniega para `R` — es lo que el
  interruptor existe para hacer.
- **Rol denegado:** `C` — mismo mecanismo que BI-007, 404.
- **Resultado esperado:** `GET /bi/control-tower` con `test.R` → **200**; con `test.C` → **404**.
- **Verificación:** `e2e/tests/biblia/lectura.spec.mjs` · `BI-008`.

## BI-006 · Las cifras de BI dependen de lo que registran los módulos operativos

- **Resultado esperado:** el PPC, el PAC y los detalles de CNC/CNP que la Torre de Control presenta
  deben coincidir con lo que producen `APR-*`. Una divergencia entre lo que muestra un informe y lo
  que dice el módulo que lo alimenta es un bug de datos, no una diferencia de criterio.
- **Verificación:** **no comprobable en lectura.** Exige datos reales en ambos lados y compararlos.
  Es el pendiente de más valor de esta tanda.

---

## Escenarios pendientes de esta pasada

- **La comprobación cruzada de `BI-006`**: comparar una cifra de la Torre de Control contra su
  origen en CIC/CNC/CNP con datos reales.
- **Los ocho `reportKey`** que `BiViewController` declara: uno por acción pública, cada uno con su
  escenario.
- **Las 12 rutas `/api/bi/*`**: solo se verificó el mecanismo común de alcance de proyecto, no cada
  informe.
- **Filtros** (`desde`, `hasta`, `sub`, `resp`, `etapa`): qué pasa con valores inválidos o rangos
  invertidos.
