---
capa: fuente
tipo: spec
estado: vigente
fecha: 2026-08-03
areas: [proceso]
fuente: docs/superpowers/specs/2026-08-03-cierre-dark-mode-design.md
resumen: Llevar el dark mode de la aplicación a cero hallazgos fuera de excepciones inventariadas, en todo el árbol (app principal, BI, admin/, PDC v2), con gates que…
---

# Cierre de dark mode — diseño validado

**Fecha:** 2026-08-03 · **Alcance:** repositorio `lps-aia` únicamente.
**Base medida:** `docs/superpowers/matriz-dark-mode-2026-08-03.md` (línea de partida: 4 511
hallazgos vivos del audit sobre `main` en `4c46825`).
**Aprobado por el usuario** en grilleo de esta fecha; las ocho decisiones están al pie.

---

## Objetivo

Llevar el dark mode de la aplicación a **cero hallazgos fuera de excepciones inventariadas**, en
todo el árbol (app principal, BI, `admin/`, PDC v2), con gates que digan la verdad y no puedan
volver a mentir. Prioridad de módulos: **PG, PI, PS y PDC v2**.

## Condición de hecho

1. `node scripts/design-system-audit.mjs` sale con código 0, con cada excepción superviviente
   listada en un inventario con su razón, y el gate falla ante una excepción nueva sin justificar.
2. `test:design-system:static` ejecuta **todos** sus pasos siempre (ningún fallo temprano oculta
   pasos posteriores) y reporta el resultado agregado.
3. Los cuatro módulos priorizados: presupuesto de ruta declarado, sin copias locales del chip,
   goldens recapturados con antes/después aprobado explícitamente.
4. Las 8 rutas `/bi/*` y las 14 vistas de `admin/` con presupuesto de ruta y validación visual
   posible (puerta de servicio de `admin/` operativa).
5. El runtime de a11y distingue `incomplete` de `violation`; los `incomplete` se reportan aparte,
   no como rojo.
6. El cierre se declara **contra estas condiciones escritas**, no contra la intención, con
   evidencia por condición en `goals/<slug>/`.

## Fuera de alcance

- Mobile, tablet, viewports <1180 px y el tema `linen` (AGENTS.md — prohibición vinculante).
- Migrar `admin/` fuera de AdminLTE; funcionalidad, datos, RBAC (salvo la puerta de servicio),
  rutas de negocio.
- Las 15 ramas viejas: se inventarían y se reporta cuáles están contenidas en `main`; su borrado o
  fusión lo decide el usuario aparte.
- Línea C (`bloqueado` → matiz azul): **descartada por decisión del usuario** — sin objeto tras el
  trabajo del chip; solo la usan sub-estados de restricción fuera de los siete estados del
  contrato de PG.

## Fases

### Fase 0 · Consolidar el árbol

Fusionar a `main` las 5 ramas vivas (`claude/admiring-bose-b4ef3c`,
`claude/competent-jepsen-dec1c4`, `claude/nostalgic-austin-50d4aa`,
`claude/nostalgic-thompson-dceb00`, `worktree-usabilidad-altas-y-medias` — esta última con 14
commits por detrás: merge de `main` hacia ella primero), correr las pruebas afectadas, commit
atómico por rama, push a `origin/main`. Los cambios sin commitear de cada worktree los consolida
su sesión dueña o se commitean aquí con mensaje que lo diga. Entregable adicional: lista de las 15
ramas viejas con veredicto contenida/única.

**Por qué primero:** medir sobre un árbol incompleto da línea de partida falsa, y el trabajo CSS
posterior tocaría los mismos archivos dos veces.

### Fase 1 · Que los gates dejen de mentir *(habilitante — bloquea todo lo demás)*

| Qué | Hallazgo | Cambio |
|---|---|---|
| La cadena `&&` corta en el primer fallo | M-01 | `test:design-system:static` pasa a un runner (script `.mjs`) que ejecuta todos los pasos, acumula resultados y sale rojo si cualquiera falló — sin ocultar los siguientes |
| `activation` exige árbol limpio y enmascara 3 gates | M-02 | El check de árbol limpio se reporta como aviso separado, no como fallo que corta la cadena |
| axe aplana `incomplete` con `violation` | M-11 | `tests/browser/support/accessibility.mjs:36`: `incomplete` se reporta aparte; solo `violation` pone rojo |
| 2 hex de reserva en `profesionales`/`subcontratistas` | M-03 | Retirar el fallback hex del `var()` (2 líneas) |

Criterio: correr la suite con un fallo inyectado a propósito y comprobar que los pasos
posteriores corren igual y el agregado sale rojo.

### Fase 2 · Puerta de servicio para `admin/` *(habilitante de la fase 5 — en paralelo con la 3)*

Extender el patrón de `src/Core/DevDoor.php` al mini-app `admin/` (que no comparte `src/Core`):
spec propio en `docs/superpowers/specs/`, misma pareja `DEV_DOOR`/`DEV_DOOR_USERS`, guard de no
existencia en producción calcado de `tests/test_dev_door_guard.php`, y sin conceder permisos por
encima de la cuenta. **Es una vía de autenticación nueva: revisión aparte antes de fusionarla.**

### Fase 3 · Los cuatro módulos priorizados

| Módulo | Trabajo |
|---|---|
| **PG** | Recapturar goldens de tabla; cada par antes/después se muestra al usuario y se aprueba explícitamente antes de consagrarlo |
| **PI** | Declarar presupuesto de ruta · retirar la copia local del chip (`programacion-intermedia.css:249,278`) · el mapeo `pi-state-execution-blocked → OK` se presenta al usuario como decisión de dominio con el significado real delante |
| **PS** | Presupuesto de ruta para la vista principal y para CIC, CNC y CNP · retirar los 9 bloques de copia local del chip (`programacion-semanal.css:454–2405`, `buttons.css:51`) |
| **PDC v2** | Resolver los 3 `!important` y 2 `local-vendor-override` de `pdc.css:178-183` o inventariarlos con razón |

Cada retirada de copia local se valida en navegador contra la ruta afectada (1180×820 dark) antes
de darse por hecha.

### Fase 4 · BI

Presupuesto de ruta para las 8 rutas `/bi/*` y corrección de lo que ese presupuesto destape.
`adapters/bi-utilities.css` ya existe; esta fase lo pone bajo vigilancia.

### Fase 5 · `admin/`

Con la puerta de la fase 2: presupuesto y validación visual de las 14 vistas, tokenización sobre
`adapters/admin-lte.css`. AdminLTE permanece como framework (decisión vinculante heredada).

### Fase 6 · El grueso estructural *(la campaña larga — punto natural de corte si se decide parar)*

2 684 hallazgos: 1 697 `unauthorized-important` + 504 `css-outside-layer` + 483
`raw-token-in-module`. Mecánica: inventario de excepciones justificadas (patrón de la línea A) +
techos por ruta que solo bajan; el gate falla ante cualquier subida. Se trabaja por módulo, no por
regla, para que cada tanda sea verificable en navegador. Las demás reglas menores (`off-scale-*`,
`hardcoded-radius`, etc.) entran aquí con el mismo trato.

### Fase 7 · Cierre

Paridad del chip (M-08: `box-shadow` y radio del componente compartido contra PI/PS) · retirar
`cell-state-vocabulary.mjs` (código muerto — nadie lo importa salvo su gate) · actualizar la wiki
(`memoria/`) y el estado de los goals · verificación final contra la **condición de hecho de este
spec, punto por punto y con evidencia**, que es exactamente lo que faltó el 2026-07-31.

**Orden:** 0 → 1 → (2 ∥ 3) → 4 → 5 → 6 → 7.

## Participación del usuario

| Momento | Decisión |
|---|---|
| Fase 2 | Revisar la puerta de servicio de `admin/` antes de fusionarla |
| Fase 3 | Aprobar cada golden antes/después · resolver el mapeo `execution-blocked` (dominio) |
| Fase 6 | Aprobar el inventario inicial de excepciones justificadas |
| Ramas viejas | Decidir borrado/fusión con la lista de la fase 0 delante |

## Riesgos declarados

- **La fase 6 es varias veces el resto junto.** Las fases 0–5 son acotadas y demostrables; la 6 es
  campaña. El corte, si se quiere, va ahí — nunca antes de la fase 1.
- Retirar copias locales del chip puede mover píxeles en PI/PS: por eso cada retirada se valida en
  navegador y los goldens se recapturan con aprobación, no en silencio.
- La puerta de `admin/` toca autenticación: mitigado con spec propio, guard de producción y
  revisión humana explícita.

## Adenda — Fase 3-bis · Unificación de tablas (añadida 2026-08-03, shape con Impeccable)

Cuatro puntos añadidos por el usuario, grillados en entrevista de shape. Modo Operate: la tabla
es herramienta de trabajo — escaneabilidad y consistencia mandan sobre expresión.

| # | Punto | Decisión del usuario |
|---|---|---|
| T-1 | Paridad del chip de estado entre PG, PI y PS (asciende la deuda M-08) | Mismo diseño en los tres: el componente compartido es la única fuente; los matices locales que sobrevivieron el task 13 se reconcilian o se absorben al componente |
| T-2 | Botones `changeType` (gatillo de filtro en cabecera de columna) | Existen en **las tres librerías** con un solo diseño del design system: pequeño, sutil, claro. Donde la librería trae su propio control (menú de AG Grid), se le aplica el mismo skin en vez de duplicar |
| T-3 | Tablas sin bordes de columna, solo de fila | **Exploración en el laboratorio primero**: prototipo A/B con las tres librerías, critique con capturas, decisión del usuario viendo — solo entonces toca producción |
| T-4 | Densidad compacta en todas las tablas | Se extiende la **escala ya medida de `/plan-compras`** (texto 13 px, piso 11 px) a toda la familia de tablas desktop; la excepción de accesibilidad registrada en PRODUCT.md se amplía de una superficie a la familia, sin relajar contraste AA, foco, teclado ni reduced-motion. **Corrección aplicada durante la ejecución (T-5, 2026-08-03):** el alto de fila/control queda en **24 px**, no en los 28 px calcados de `/plan-compras` — 24×24 es el suelo real de WCAG 2.2 SC 2.5.8 (AA) y no había razón para quedarse por encima. Contrato vigente en `--ds-control-compact-min` y DESIGN.md §5 bis |

Método exigido: la exploración y el critique usan las lentes de `impeccable` (critique con
puntuación heurística), `ux-heuristics` (severidad Nielsen) y `refactoring-ui` (jerarquía sin
bordes: espaciado y zebra antes que líneas) — se cargan al ejecutar esas tareas. T-3 y T-4 se
deciden con capturas frente al usuario antes de aplicar; T-1 y T-2 son implementación directa
porque la decisión ya está tomada. Los goldens de la fase 3 (task 15) se recapturan DESPUÉS de
esta fase para no consagrar dos veces.

### T-5 · Botones de acción y chips contadores — ultra compactos y accesibles (añadido 2026-08-03)

Petición del usuario: los botones de acción y los chips contadores se ven demasiado grandes en
todos los módulos. Objetivo: **ultra compactos, ultra accesibles**. Lo confirman las tres lentes
de hoy — «toolbars planas, todos los botones con el mismo peso» y contadores que compiten con los
datos.

**Decisiones tomadas:**

| # | Decisión |
|---|---|
| 1 | **Pequeño a la vista, grande al clic**: la forma visual se compacta y el área clicable se amplía por pseudo-elemento. El foco visible se dibuja sobre la forma visual, nunca sobre el área invisible |
| 1-bis | **Corrección del 2026-08-03, posterior**: el área baja de 32 px a **24 px**. El usuario corrigió el rumbo — «solo necesitamos cumplir con accesibilidad básica, [con] una premisa de optimizar al máximo el tamaño de los elementos, para maximizar el espacio disponible de las tablas». 24×24 px es el **mínimo exacto de WCAG 2.2 SC 2.5.8 (AA)**: no queda margen, cualquier reducción posterior incumple |
| 2 | **Los chips contadores filtran al hacer clic**: son controles, no etiquetas. Conservan afordancia, foco visible y estado activo aunque se compacten |

**Consecuencia normativa:** `PRODUCT.md` exige hoy «objetivos de interacción de al menos 44 px».
Eso es la regla AAA (SC 2.5.5) autoimpuesta; el estándar AA pide 24×24 px (SC 2.5.8). La regla
interna se sustituye para toda la familia de tablas y sus controles por el **piso AA de 24 px**,
con esta razón escrita — mismo procedimiento que la excepción de densidad del 2026-07-29.

**El suelo que no se cruza, en ninguna tarea de esta familia:** objetivos de 24×24 px, contraste
de texto 4.5:1, foco visible, orden de foco, teclado y `prefers-reduced-motion`. Todo lo demás se
optimiza para ganar filas visibles. Cuando un valor quede **en** el mínimo exacto, la tarea debe
decirlo: es una posición sin margen, y quien la toque después necesita saberlo.

## Decisiones del grilleo (2026-08-03)

1. Alcance: **dark mode completo**, los 4 511 a cero — no solo los 4 módulos.
2. «Cero» = **cero fuera de excepciones inventariadas**, cada una con razón y gate contra nuevas.
3. Goldens: **recapturar con antes/después aprobado** por el usuario.
4. Línea C: **descartada, sin objeto**.
5. `admin/`: **extender la puerta de servicio**, no trabajar a ciegas ni excluirlo.
6. Ramas: **fusionar solo las 5 vivas**; las 15 viejas se revisan con lista antes de decidir.
7. Momento del merge: **antes de empezar** el trabajo de dark mode.
8. Prioridad de módulos: **PG, PI, PS, PDC v2**, en la fase 3.
