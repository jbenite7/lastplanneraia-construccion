---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-09-28
areas: [lps]
fuente: goals/s05-cajon-verdad/informe.md
resumen: Informe de ejecución del goal s05-cajon-verdad — qué cambió por punto, decisiones y verificación
---

# Informe: el cajón de Programa General dice la verdad

Rama `fix/s05-cajon-verdad`, desde `f6618ac8`. Sin push, sin PR, sin merge. Sin DDL, sin
escrituras de datos (las pruebas de SOS usan dobles o intercepción de red), sin tocar RLS,
`admin/`, `database/` ni `RbacCatalog`.

## Qué cambió, por punto

| # | Punto | Commit | Qué cambió |
|---|---|---|---|
| 1 | Observación | `b92eec49` | Se quitó el campo editable del cajón y su paso por el guardado. La observación que ya trae la fila se ve en solo lectura, rotulada «Observación registrada (solo lectura)». |
| 2 | SOS | `cc9b0846`, `7b965e88` | «Declarar Crisis SOS» llama a `registrarCrisis` (T02) con `modulo=PG`, `consecutivo=unique_id`, trigger `MANUAL` y el token `lps_drawer`. El rótulo de alerta activa sale de la fila que el servidor devuelve al recargar; mientras la petición está en curso el botón queda deshabilitado; el mensaje o el error del servidor se muestran en el cajón. El segundo commit hace que, si hay cambios sin guardar, se pida confirmación antes del SOS, porque la recarga posterior los descartaría. |
| 3 | Recursos | `02441928` | El esquema conserva las columnas de restricción (`D_y_E`, `Materiales`, `MdeO`, `Equipos`, `Predecesora`, `Pdto_Cons`, `Modelo`, `restriccion_pc_1..4`) y el catálogo `restrictionConfig` del contexto. El cajón lista solo los recursos que tienen valor en la fila, con la etiqueta, las opciones y el umbral del catálogo. Si no hay datos, lo dice. |
| 4 | Descarte | `a27eca70`, `518a78ef` | Con cambios sin guardar, Esc, el velo y la X piden confirmación; sin cambios cierran directo. El segundo commit arregla un borrador fantasma al navegar con `[`/`]`. |
| 5 | Visibilidad y accesibilidad | `e0434a2f` | La barra de avance real ya no pinta su color en línea con tokens inexistentes: usa la clase del módulo más un modificador de atraso. Los estilos en línea con tokens inexistentes y el hex de respaldo pasaron a clases con tokens reales. «Avance Teórico Servidor» ahora tiene etiqueta asociada. El foco entra al cajón al abrir y vuelve a lo que lo abrió al cerrar. |
| — | Spec y bundle | `1abdb839`, `eeb91d9c`, `e29f77e7` | Nueva prueba de navegador del cajón con red interceptada y bundle de `public/app/` reconstruido. |

## Decisiones y su porqué

- **Observación en solo lectura, no borrada.** Es un dato real de la fila (`SELECT *`) y la crítica no la cuestiona como dato, solo como campo editable falso. Así no se pierde lo que ya existe y no se abre un camino de escritura nuevo.
- **Token del SOS: `csrf.drawer`.** `registerCrisis` exige `legacy_require_csrf('lps_drawer')`. Los tokens de guardado (`programa_general_save`) o del shell (`shell_api`) darían 403. El contexto ya lo entregaba, pero el esquema lo descartaba; ahora se guarda como `csrf_drawer`.
- **Permiso del SOS: `permisos.writeDrawer`** (`lps.programacion_semanal.editar`), que es el mismo que exige el backend. No se usa `puedeEditar`, que corresponde a otro permiso.
- **Trigger `MANUAL`.** Es una declaración hecha a mano desde el cajón y es el valor por defecto del backend. Los `SOS-*` son escalamientos por nivel, que en React tiene `AccionesSos` del cajón compartido.
- **Sin modo simulación en PG.** El shell no lo expone: ningún consumidor le pasa `simulado`, el `?? true` es un valor por defecto del cajón compartido y el `localStorage` `lps_simulated_mode` pertenece al legado. Heredar ese `true` habría dejado el botón sin efecto real, que es justo el P0 que se arregla aquí.
- **Alerta ya activa: botón deshabilitado** con «Alerta SOS LPS Activa». Volver a registrar es idempotente (`wasActive`) y no escala, así que ofrecer el botón prometería algo que no pasa.
- **Recurso con valor `N/A`: se muestra como «No aplica».** Es un dato real registrado. Liberada o pendiente se decide con `thresholdPercent` del catálogo, nunca con un umbral propio.
- **La X del encabezado también pide confirmación.** Es un cierre igual de accidental que Esc o el velo. «Descartar» es la intención explícita y cierra sin preguntar.
- **Tokens:** la barra de atraso usa `--ds-active-state-solid-red`, la convención del módulo, que resuelve a `--ds-state-solid-red` de `tokens.css`. La guarda de tokens acepta `tokens.css` y `aia-design-system.css` porque la SPA carga las dos (`frontend/index.html`).
- **La guarda de tokens vive en `tests/design-system/`** (Node, dentro de `test:design-system:static`) y no en Vitest, porque Vite niega leer `public/css` desde `frontend/`. Se comprobó en rojo contra el cajón anterior (reporta `--ds-text-muted`, `--ds-state-danger-text`, `--aia-corporate` y el hex) y en verde con el nuevo.
- **Borrador fantasma (hallazgo del spec de navegador).** Al navegar con `]`, «hay cambios» comparaba los campos viejos contra la actividad nueva durante un render, y un Esc rápido pedía confirmar sin que hubiera cambios. Ahora se compara contra la actividad con la que se cargaron los campos, y el guardado usa ese mismo id. Era intermitente: falló en la primera corrida completa del spec y pasó en una segunda corrida instrumentada. Tras el arreglo pasó 8 de 8 con `--repeat-each=8`.

## Verificación (salida real de esta sesión, HEAD `e29f77e7`)

| Comando | RC |
|---|---|
| `npm --prefix frontend test` (971 pruebas) | 0 |
| `npm --prefix frontend run typecheck` | 0 |
| `npm --prefix frontend run build` (bundle commiteado en `e29f77e7`) | 0 |
| `npm run test:design-system:static` (8/8 compuertas) | 0 |
| `npm run css:minify:check` | 0 |
| `E2E_BASE_URL=http://localhost:8093 npx playwright test tests/browser/s05-programa-general-react.spec.mjs --workers=1` (18 pasadas) | 0 |

- Se usó un contenedor propio (`pg-cajon`, puerto 8093, `LPS_CODE_ROOT` = este worktree) que servía el bundle de la rama. Se apagó al terminar y el `app` compartido no se tocó.
- **axe sobre el cajón abierto** (1180×820, tema de entrada; medido en `72d625e1`, el mismo HEAD lo reafirma porque el spec exige 0 críticos): **0 críticos**. Queda 1 hallazgo serio (`color-contrast`, 17 nodos), 1 moderado (`heading-order`) y 1 menor (`aria-allowed-role`).
- Las pruebas de SOS en navegador van solo en el bloque con red interceptada. El bloque «Servidor Real Docker» no pulsa SOS.

## Pendiente o fuera de alcance (no se tocó)

- No verificado: el texto exacto que ve la persona cuando el servidor rechaza el SOS (403 o 422). Depende de cómo `pedir()` lleva el `mensaje` del backend al error; las pruebas lo simulan con dobles.
- `color-contrast` serio en 17 nodos del cajón: es del tema visual del módulo y le toca a la ronda de rediseño.
- `LpsDrawerProvider` (cajón compartido del shell) pasa `sesion.csrfToken` (`shell_api`) a `registrarCrisis`, que exige `lps_drawer`. Su SOS probablemente responde 403 hoy. Además, su `simulado ?? true` hace que por defecto nunca registre nada.
- Al navegar con `[`/`]` con cambios sin guardar, el cajón los descarta sin preguntar. El goal cubre Esc y el velo, no la navegación.
- El worktree ya tenía un `.env` que no es enlace simbólico. No se modificó.
- Pendiente de la condición de hecho del goal: revisión independiente, PR y las 13 `G_*` del CI en ambos temas. Le tocan al coordinador.
