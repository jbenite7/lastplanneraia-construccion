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
- **Verificación:** lectura — `src/Controllers/Bi/BiViewController.php:176-182`.

## BI-005 · `/indicadores` **debería** cortar igual, y hoy no lo hace

- **Roles denegados por diseño:** `G`, `S`, `SG`, `C` (decisión registrada en
  `memoria/decisiones/powerbi-indicadores.md`).
- **Resultado esperado:** que esos cuatro roles **no reciban el informe**, con la restricción
  aplicada donde no se puede saltar: el servidor.
- **Verificación:** lectura — `src/Controllers/Gestion/IndicadoresController.php` **no tiene ningún
  control de rol**, y `views/indicadores/indicadores.view.php:111` declara la URL del informe antes
  de que `:151` decida ocultarla, así que viaja en el HTML de todos.

> **Hallazgo ya registrado**, con su matiz: ese informe es *publish-to-web*, público por enlace por
> diseño, así que no hay filtración de datos privados. Lo que falla es que **una regla declarada
> existe solo como adorno del cliente**, y que dos módulos hermanos —este y `/bi/*`— aplican la
> misma política con dos niveles de garantía distintos. Ver
> `memoria/trampas/indicadores-oculta-en-cliente-bi-en-servidor.md`.

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
