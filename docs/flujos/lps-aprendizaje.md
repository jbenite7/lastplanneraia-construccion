---
capa: fuente
tipo: guia
estado: vigente
fecha: 2026-08-04
areas: [lps]
fuente: docs/flujos/lps-aprendizaje.md
resumen: Escenarios APR-. CNP, CNC y CIC: por qué una actividad no llegó a programarse, por qué no se cumplió, y cómo se califica a quien la ejecuta. Cierran el ciclo y…
---

# Biblia · Cascada LPS · Los tres submódulos de aprendizaje

Escenarios `APR-*`. CNP, CNC y CIC: por qué una actividad no llegó a programarse, por qué no se
cumplió, y cómo se califica a quien la ejecuta. Cierran el ciclo y alimentan los indicadores.

Formato y reglas: `docs/flujos/README.md`. Verificado por lectura el **2026-08-04**.

---

## El vocabulario, fijado por `GLOSARIO.md`

Confundirlos es un error caro, así que se citan literalmente:

| Sigla | Qué es | Fuente |
|---|---|---|
| **CNP** | Causas de No Programación: por qué una actividad del look-ahead **no pudo pasar** al plan semanal | `GLOSARIO.md:48` |
| **CNC** | Causas de No Cumplimiento: por qué una actividad **no se terminó** | `GLOSARIO.md:41` |
| **CIC** | Calificación Integral de Contratistas: evaluación del desempeño del contratista | `GLOSARIO.md:44` |
| **PPC / PAC** | Porcentaje de actividades completadas; la confiabilidad del sistema de planificación | `GLOSARIO.md:40`, `:58` |

> **Corrección hecha de paso el 2026-08-04:** `memoria/flujos/flujo-lps.md` describía el CIC como
> «el cumplimiento medido de lo comprometido». Eso es el PPC/PAC, no el CIC. Corregido en la wiki
> contra el glosario y el código.

## Un cuarto esquema de autorización

Estos tres módulos **no** usan `authorizePermission()` (el de Programa General e Intermedia) **ni**
las guardias propias de Semanal. Usan una tercera vía: la función legada
`rbac_guard_require_permission()` de `src/Legacy/rbac_guard.php`.

Con esto van **cuatro** formas distintas de autorizar en la misma aplicación:

| Módulo | Cómo autoriza |
|---|---|
| Programa General, Intermedia | `$this->authorizePermission('lps.*')` |
| Programación Semanal | `requireSessionDbPrefix()` + `requireWeekEditPolicy()` |
| CNP, CNC, CIC | `rbac_guard_require_permission('lps.*')` (legado) |
| PDC | permisos `lps.pdc.*` (pendiente de T3) |

Que todas acaben consultando el mismo catálogo de permisos no las hace equivalentes: cada una falla
distinto, se prueba distinto y se olvida distinto.

## APR-001 · Ver y editar CNC exigen llaves distintas

- **Pasos:** `list` y `reasons` exigen `lps.cnc.ver` (`:22`, `:91`); `save` exige `lps.cnc.editar`
  (`:46`).
- **Resultado esperado:** consultar las causas y registrarlas son permisos separados. Un rol puede
  leer el análisis sin poder alterarlo.
- **Verificación:** lectura — `src/Controllers/Api/CncApiController.php:22,46,91`.

## APR-002 · Registrar una causa de no programación exige además que la semana lo permita

- **Pasos:** `CnpApiController` exige `lps.cnp.ver` para listar (`:22`), `lps.cnp.editar` para
  guardar (`:46`) y **además** `requireWeekEditPolicy($dbPrefix, $week)` (`:59`) en la
  reprogramación.
- **Resultado esperado:** no basta con el permiso: la semana tiene que estar dentro de la ventana
  que `CAS-001` describe. Reprogramar hacia atrás en el tiempo queda cerrado para el Residente
  fuera de sus dos semanas.
- **Verificación:** lectura — `src/Controllers/Api/CnpApiController.php:22,46,59,87`.

> CNP es el único de los tres que combina permiso **y** política de semana. CNC y CIC no consultan
> la política: sus llaves son solo de permiso.

## APR-003 · Cada rol califica solo las disciplinas que le competen

El escenario más específico del ciclo, y una regla de dominio que no se adivina.

- **Pasos:** `CicApiController::save()` exige `lps.cic.editar` y después llama a
  `RbacCatalog::cicDisciplinesForRole($role)`. Si devuelve lista vacía, responde **403** «Su rol no
  puede calificar disciplinas CIC».
- **Resultado esperado**, leído del código:

| Rol | Disciplinas que puede calificar |
|---|---|
| `A`, `D` | `cal`, `adm`, `gsa`, `sst` — todas |
| `R` (Residente) | **solo `cal`** (calidad) |
| `G` (Ambiental) | solo `gsa` |
| `S` (SST) | solo `sst` |
| `SG` | `gsa` y `sst` |
| `OT` | solo `adm` |
| cualquier otro | ninguna → 403 |

- **Verificación:** lectura — `src/Controllers/Api/CicApiController.php:87-96`,
  `src/Security/RbacCatalog.php:50-63`.

> Es coherente con `RBAC-C` de la biblia transversal: quien firma seguridad es el profesional de
> SST, no la jefatura de obra. Aquí se ve la misma lógica aplicada a la calificación: **el Residente
> califica calidad, no seguridad ni ambiental**.
>
> Y nótese que `cicDisciplinesForRole` **sí** usa `roleAliases()` (`:53`) para normalizar — al
> contrario que el selector de proyecto, que no lo hace (ver `PROY-007`). Dos criterios de
> normalización distintos en la misma aplicación.

## APR-004 · La calificación integral se compone de las cuatro dimensiones

- **Precondiciones:** existen calificaciones de la semana para ese subcontratista.
- **Resultado esperado:** las columnas `Calidad`, `GSA`, `SST` y `ADM` (con sus acumulados)
  alimentan `Cal_Integral` / `Cal_Integral_Acum`, junto a `PAC` y `P_Completado`. La calificación es
  **por subcontratista y por semana**, y la consulta toma la última semana disponible menor o igual
  a la pedida.
- **Verificación:** lectura — `src/Controllers/Api/CicApiController.php:61` (la proyección completa
  de columnas), `:141` (actualización de `P_Completado` y `PAC`).

---

## Escenarios pendientes de esta pasada

- **El cálculo del PPC** y sus casos borde: semana sin compromisos (¿divisor cero?), cumplimiento
  parcial, actividades añadidas después de confirmar. Es el pendiente de más valor de este
  documento.
- **CSRF en estos tres módulos:** no se encontró validación de token en sus `save`. Antes de
  declararlo hallazgo hay que comprobar si `rbac_guard.php` lo cubre por dentro — **no verificado
  todavía**, y por eso no está en el backlog.
- **Cuántos pasos cuesta registrar una causa** (el dato que `docs/CUSTOMER.md` pide para la fase 5
  de `improve-app`): exige recorrido en navegador.
- La coherencia entre estas cifras y las que muestra BI (dependencia que T5 citará).
