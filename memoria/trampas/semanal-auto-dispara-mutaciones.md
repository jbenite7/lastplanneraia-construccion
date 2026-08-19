---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-08-03
areas: [qa, lps]
fuente: memoria-claude
origen: lps-aia-browser-qa-pitfalls
resumen: "Abrir /programacion-semanal puede disparar save y auto-program, pero bajo condiciones: save exige rol con gestión y auto-program exige semana válida"
---
**Abrir `/programacion-semanal` puede disparar mutaciones sin interacción**, pero no siempre.
Corregido el 2026-08-03 leyendo el código: la nota afirmaba «en cada carga de página, sin
interacción», y eso describe el peor caso como si fuera el único.

Las dos condiciones, medidas en el origen:

- **`POST /api/semanal/save`** con `opcion: 'sanear'` sale de `loadData()` en
  `public/js/modules/programacion_semanal/hot.js:2074-2084`
  (`if (!sanitizedOnLoad && canManageToolbarActions())` … `data: { opcion: 'sanear', ... }`,
  cita corregida el 2026-08-10; el rango anterior había rotado a `normalizeCellValue()`), tras un
  doble guardián:
  `!sanitizedOnLoad && canManageToolbarActions()`. **Depende del rol**: una cuenta sin permiso de
  gestión no lo dispara nunca.
- **`POST /api/semanal/auto-program`** sale de `run()` en `changeMonitor.js:35-47`, guardado por
  `isRunning || (hasRunOnce && !force)` y exigiendo `db` no vacío y `semana > 0`. **Sin semana
  válida no se dispara.**

Comprobado interceptando POST en esa ruta el 2026-08-03: se observaron tres a
`datosGeneralesPagina.php` y **ninguno** de los dos anunciados, porque no se cumplían las
condiciones. Quien lea la versión anterior de esta nota sale creyendo que basta con abrir la
página, y al no reproducirlo puede concluir que la trampa es falsa. No lo es: es condicional.

**Why:** una verificación pensada como «solo mirar» puede escribir en la base sin que el agente lo
pida — y con un residente con permisos de gestión sobre una semana válida, que es el caso normal
de trabajo, se cumple. Pero una nota que promete lo que no siempre ocurre se desacredita sola la
primera vez que alguien la comprueba.

**How to apply:** para QA visual o de navegación del drawer/shell, seguir prefiriendo
`/dashboard/escalamientos`, que incluye el mismo drawer LPS vía
`views/partials/drawer_unificado.php` sin autosave. Si necesitas medir en
`/programacion-semanal`, intercepta POST y comprueba qué se disparó de verdad en tus condiciones
en vez de asumirlo en cualquier dirección.

Que esto sea condicional **no lo convierte en aceptable**: abrir una pantalla sigue escribiendo en
la base para el rol y la semana adecuados. Está registrado como línea de trabajo propia (F-bis) en
`docs/superpowers/specs/2026-08-03-reparto-trabajo-pendiente-design.md`.

Relacionado: [[sesion-cae-en-el-panel]], [[bitacora-drawer-sin-profesional]].
