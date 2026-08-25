---
capa: fuente
tipo: biblia
estado: vigente
fecha: 2026-08-04
areas: [lps]
fuente: docs/flujos/README.md
resumen: Qué debe hacer esta aplicación, escenario por escenario. No qué hace: qué debe hacer.
---

# La biblia de flujos

Qué **debe** hacer esta aplicación, escenario por escenario. No qué hace: qué debe hacer.

Esa diferencia es todo el propósito de esta carpeta. El repositorio ya sabía **dónde** está cada
cosa (`memoria/arquitectura/`, generado desde el código) y **por qué** se decidió
(`memoria/decisiones/`). Faltaba la capa que permite afirmar que una función está mal.

## La cláusula de autoridad

> **Si la biblia y el código divergen, es un bug de uno de los dos y hay que resolverlo.**

No se corrige la biblia en silencio para que cuadre con el código. Esto la distingue de la wiki
`memoria/`, cuya regla es la contraria y deliberada: allí *código > AGENTS.md > wiki*, y una nota
que contradice al repo se corrige y se marca `derogada`. Una biblia que pierde por definición
contra el código no podría certificar que el código está mal, que es justo para lo que existe.

Precedencia resultante: **código y biblia se contrastan entre sí**; `AGENTS.md` sigue siendo el
contrato de trabajo; `memoria/` sigue siendo contexto.

Que esto no es teórico lo demuestra el primer hallazgo, encontrado antes de escribir un solo
escenario: `AGENTS.md:23` da por compartidos entre tres módulos unos contratos que hoy solo usa uno
(ver `memoria/trampas/semi-auto-solo-lo-usa-pdc.md`). Ni siquiera el contrato autoritativo está a
salvo de divergir.

## El formato del escenario

Un escenario es **un camino concreto por un flujo** — ni un flujo entero, ni un clic suelto.
«Residente crea un compromiso en semana abierta» es un escenario; «Programación Semanal» no lo es.

| Campo | Qué contiene |
|---|---|
| `id` | `<PREFIJO>-<NNN>`, estable para siempre. Un escenario retirado conserva su número; no se reutiliza |
| Rol | Cuál de los diez roles de `RbacCatalog` lo ejecuta |
| Precondiciones | Estado de datos y de sesión necesarios: semana abierta, proyecto seleccionado, fila existente |
| Pasos | Numerados; **cada uno nombra la variable, el endpoint o la capacidad que toca** |
| Resultado esperado | Qué cambia en pantalla **y** en datos. Las dos mitades, siempre |
| Verificación | Cita `archivo:línea` y, si es crítico, la prueba de `e2e/` que lo cubre |

**Los caminos de error y de permiso denegado son escenarios de primera clase.** Tienen el mismo
peso que el camino feliz, y suelen ser donde viven los bugs.

**Un escenario que solo transcribe lo que el código hace, sobra.** La prueba: ¿se puede discrepar de
él? Si no afirma nada que el código pudiera incumplir, no es un escenario, es un comentario.

## Los dos niveles de verificación

1. **Lectura — todos los escenarios.** Verificado contra el código con cita `archivo:línea` leída de
   verdad. Barato, cubre el 100 %, caza divergencias. Rige la regla del pase de veracidad de la
   wiki: **verificar, no sospechar**. Lo que no se pueda comprobar leyendo se declara «no
   comprobable en lectura» — nunca se da por bueno.
2. **Ejecutable — los críticos.** Prueba Playwright en `e2e/` para los escenarios que **tocan
   permisos, mutan datos o cierran un periodo**.

Cuando una prueba falla hay exactamente dos salidas, y ninguna es tocar la prueba para que pase: o
la biblia describe mal el comportamiento (se corrige la biblia) o el código incumple (hallazgo al
backlog). Esa bifurcación es el motivo de todo esto.

## Cómo se cita un `id` desde una prueba

El título del `test()` empieza por el `id`, de modo que un fallo apunte a la línea de biblia que se
incumple:

```javascript
test('RBAC-006 · Residente ve los controles de semana y Visualizador no', async ({ page }) => {
```

## Qué se hace con un hallazgo

Se registra en `docs/EXPERIMENTS.md` con su ICE y **la pasada continúa**. No se arregla en caliente:
mezclaría documentación con cambios de comportamiento en el mismo commit, y descarrilaría la
auditoría.

Si la duda es *cuál es la conducta correcta* —y no *si el código la cumple*—, la decisión es del
usuario. Esos hallazgos van marcados `decide: usuario`.

## Las cinco tandas

Se ejecutan por matriz esfuerzo/impacto, no en orden narrativo.

| # | Tanda | Prefijos | Estado |
|---|---|---|---|
| T1 | Transversal — autenticación, proyecto, RBAC | `AUTH`, `PROY`, `RBAC` | **primera pasada cerrada** (2026-08-04): 3 documentos, 7 pruebas en verde, 10 hallazgos. Quedan los pendientes que cada documento declara al final |
| T2 | Cascada LPS | `PG`, `CRO`, `PI`, `PS`, `APR`, `CAS` | **primera pasada cerrada** (2026-08-04): 5 documentos, 26 escenarios, 5 pruebas en verde, 5 hallazgos |
| T3 | Plan de Compras **v2** | `PDC` | **rehecha el 2026-08-04.** La primera versión documentaba v1 (`/api/pdc/*`); al deprecarse v1 se retiró y se escribió desde cero sobre `/plan-compras` y sus 70 rutas: 5 escenarios, 0 hallazgos. Falta la cadena de dominio |
| T4 | Soporte | `SOP` | **primera pasada cerrada** (2026-08-04): 3 escenarios, 2 pruebas en verde, 1 hallazgo mayor (CSRF ausente en seis módulos). Escalamientos queda entero |
| T5 | Lectura — indicadores y BI | `BI` | **primera pasada cerrada** (2026-08-04): 6 escenarios, 1 hallazgo. Falta la comprobación cruzada de cifras contra su origen |

T1 va primera pese a no ser el corazón del negocio: es barata y contamina todo lo demás, porque
cada escenario de las otras cuatro empieza con «un rol X con un proyecto en sesión». T5 va última
pese a ser también barata, porque describir una cifra exige haber descrito antes su origen.

El diseño completo está en `docs/superpowers/specs/2026-08-04-biblia-de-flujos-design.md`; el plan
de cada tanda, en `docs/superpowers/plans/2026-08-04-biblia-t<N>-*.md`.
